<?php

namespace tables;

class minMaxRanges extends _default
{
    public $primaryKey = 'mmr.id';

    public $ajaxModel = 'minMaxRanges';
    
    public $fields = [
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'update' => 'mmr.vendorID',
            'noEdit' => TRUE,
        ],
        'startLocation' => [
            'select' => 'sl.displayName',
            'display' => 'Start Location',
            'noEdit' => TRUE,
        ],
        'endLcation' => [
            'select' => 'el.displayName',
            'display' => 'End Location',
            'noEdit' => TRUE,
        ],
        'active' => [
            'select' => 'IF(mmr.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'mmr.active',
            'updateOverwrite' => TRUE,
        ]
    ];

    public $table = 'min_max_ranges mmr
        JOIN      vendors v ON v.id = mmr.vendorID
        JOIN      warehouses w ON v.warehouseID = w.id
        JOIN      locations sl ON sl.id = mmr.startLocID
        JOIN      locations el ON el.id = mmr.endLocID';

    public $where = 'v.active';

    public $mainField = 'mmr.id';

    /*
    ****************************************************************************
    */
   
    function checkMinMaxRange($data)
    {
        $vendorID = $data['vendorID'];
        $startLocation = $data['startLocation'];
        $endLocation = $data['endLocation'];

        $locations = new locations($this->app);
        
        $return = $results = [];
        
        if (! $startLocation) {
            $return['errors'][] = 'Missing Start Location Name';
        } else {

            $result = $locations->checkMinMaxLocation($startLocation, $vendorID);

            if (! $result) {
                $return['errors'][] = 'Invalid Start Location Name';
            }
        }
        
        if (! $endLocation) {
            $return['errors'][] = 'Missing Start Location Name';
        } else {

            $result = $locations->checkMinMaxLocation($endLocation, $vendorID);

            if (! $result) {
                $return['errors'][] = 'Invalid End Location Name';
            }
        }
        
        if (isset($return['errors'])) {
            return $return;
        }
        
        if ($endLocation < $startLocation) {
            $return['errors'][] = 'Start Location Name can not preceede ' 
                    . 'End Location Name';
        } else {

            $minMaxRanges = new minMaxRanges($this->app);

            $results = $minMaxRanges->getAmbiguousRange($vendorID, 
                    $startLocation, $endLocation);

            foreach ($results as $result) {
                $return['errors'][] = 'Input range intersects a range "' 
                        . $result['range'] . '" associated with ' 
                        . $result['fullVendorName'];
            }
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getAmbiguousRange($vendorID, $startLocation, $endLocation=NULL)
    {
        if (! $endLocation) {
            $clause = '? BETWEEN sl.displayName AND el.displayName';
                        
            $params = [$vendorID, $vendorID, $startLocation];
        } else {
            $clause = '? BETWEEN sl.displayName AND el.displayName 
                    OR ? BETWEEN sl.displayName AND el.displayName
                    OR sl.displayName BETWEEN ? AND ?
                    OR el.displayName BETWEEN ? AND ?';
            
            $params = [$vendorID, $vendorID, $startLocation, $endLocation, 
                $startLocation, $endLocation, $startLocation, $endLocation];
        }

        $sql = 'SELECT    v.id,
                          CONCAT(w.shortName, "_", v.vendorName) AS fullVendorName,
                          CONCAT(sl.displayName, " - ", el.displayName) AS `range`
                FROM      min_max_ranges mmr
                JOIN      vendors v ON v.id = mmr.vendorID
                JOIN      warehouses w ON v.warehouseID = w.id
                JOIN      locations sl ON sl.id = mmr.startLocID
                JOIN      locations el ON el.id = mmr.endLocID
                JOIN      vendors vv
                WHERE     v.id != ?
                AND       vv.id = ?
                AND       vv.warehouseID = v.warehouseID
                AND       w.id = v.warehouseID
                AND       (' . $clause . ')';

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */    
    
    function updateMinMaxRange($data)
    {
        $vendorID = $data['vendorID'];
        $startLocation = $data['startLocation'];
        $endLocation = $data['endLocation'];

        $sql = 'SELECT    l.displayName,
                          l.id
                FROM      locations l
                JOIN      vendors v ON v.warehouseID = l.warehouseID
                WHERE     v.id = ?
                AND       l.displayName IN (?, ?)';

        $selectParams = [$vendorID, $startLocation, $endLocation];

        $results = $this->app->queryResults($sql, $selectParams);

        $startLocID = $results[$startLocation]['id'];
        $endLocID = $results[$endLocation]['id'];
        
        $sql = 'INSERT INTO min_max_ranges (
                    vendorID,
                    startLocID,
                    endLocID
                ) VALUES (
                    ?, ?, ?
                ) ON DUPLICATE KEY UPDATE 
                    startLocID = ?,
                    endLocID = ?,
                    active = 1
                ';

        $params = [$vendorID, $startLocID, $endLocID, $startLocID, $endLocID];

        $this->app->runQuery($sql, $params);
        
        return TRUE;
    }

    /*
    ****************************************************************************
    */

    public function getFreeLocation($vendorID, $limit = 1)
    {
        $range = $this->getLocationRangeByVendor($vendorID);
        $results = [];

        if ($range) {
            $sql = 'SELECT l.*
                    FROM locations l
                    JOIN vendors v ON v.warehouseID = l.warehouseID
                    LEFT JOIN inventory_cartons ca ON ca.locID = l.id
                    WHERE isMezzanine
                    AND v.id = ?
                    AND l.id BETWEEN ? AND ?
                    AND ca.id IS NULL
                    GROUP BY ca.locID
                    LIMIT ' . $limit;

            $params = [$vendorID, $range['startLocID'], $range['endLocID']];

            $results = $this->app->queryResult($sql, $params);
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    public function getLocationRangeByVendor($vendorID)
    {
        $sql = 'SELECT *
                FROM min_max_ranges
                WHERE vendorID = ?';

        $results = $this->app->queryResult($sql, [$vendorID]);

        return $results;
    }

    /*
    ****************************************************************************
    */

}