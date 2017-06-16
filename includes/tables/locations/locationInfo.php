<?php

namespace tables\locations;

class locationInfo extends \tables\_default
{
    public $displaySingle = 'Location';

    public $primaryKey = 'l.id';

    public $ajaxModel = 'locations\locationInfo';

    public $fields = [
        'vendor' => [
            'select' => '-- If there is a UPC but no inventory
                        IF (
                            u.id IS NOT NULL AND v.id IS NULL, 
                            "No Inventory Found",
                            CONCAT(w.shortName, "_", v.vendorName)
                        )',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'update' => 'li.vendorID',
            'noEdit' => TRUE,
        ],
        'location_name' => [
            'select' => 'l.displayName',
            'display' => 'Location Name',
            'autocomplete' => 'locations',
            'autocompleteSelect' => 'displayName',
            'update' => 'locID',
            'updateOverwrite' => TRUE,
            'updateTable' => 'locations',
            'updateField' => 'displayName',
            'required' => TRUE,
        ],
        'upc' => [
            'select' => 'u.upc',
            'display' => 'UPC',
            'autocomplete' => 'upcs',
            'autocompleteSelect' => 'upc',
            'update' => 'li.upcID',
            'updateOverwrite' => TRUE,
            'updateTable' => 'upcs',
            'updateField' => 'upc',
            'required' => TRUE,
        ],
        'sku' => [
            'select' => 'u.sku',
            'display' => 'SKU',
            'noEdit' => TRUE,
        ],
        'size' => [
            'select' => 'u.size',
            'display' => 'Size',
            'noEdit' => TRUE,
        ],
        'color' => [
            'select' => 'u.color',
            'display' => 'Color',
            'noEdit' => TRUE,
        ],
        'min_count' => [
            'select' => 'minCount',
            'display' => 'Min Count',
            'isNum' => 'unl',
            'required' => TRUE,
            'lengthLimit' => 8,
            'isPositive' => TRUE,
        ],
        'max_count' => [
            'select' => 'maxCount',
            'display' => 'Max Count',
            'isNum' => 'unl',
            'required' => TRUE,
            'lengthLimit' => 8,
            'isPositive' => TRUE,
        ],
        'active' => [
            'select' => 'IF(li.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'li.active',
            'updateOverwrite' => TRUE
        ],
        'mezzanineSum' => [
            'select' => 'SUM(IF(
                            c.statusID = c.mStatusID AND s.shortName = "RK", c.uom, 0
                        ))',
            'display' => 'Mezzanine Total',
        ],
        'warehouseSum' => [
            'select' => '(
                SELECT SUM(sc.uom) AS uomSum
                FROM   upcs su 
                JOIN   inventory_batches sb ON sb.upcID = su.id
                JOIN   inventory_cartons sc ON sb.id = sc.batchID
                JOIN   statuses ss ON ss.id = sc.statusID
                WHERE  u.id = su.id
                AND    sc.statusID = sc.mStatusID
                AND    ss.shortName = "RK"
            )',
            'display' => 'Warehouse Total',
        ]
    ];

    public $table = 'locations l
        LEFT JOIN locations_info li ON l.id = li.locID
        LEFT JOIN inventory_cartons c ON c.locID = l.id
        LEFT JOIN statuses s ON s.id = c.statusID
        LEFT JOIN upcs u ON u.id = li.upcID
        LEFT JOIN inventory_batches b ON b.upcID = u.id
        LEFT JOIN inventory_containers co ON co.recNum = b.recNum
        LEFT JOIN vendors v ON v.id = co.vendorID
        LEFT JOIN warehouses w ON v.warehouseID = w.id

        ';

    public $baseTable = 'locations l';

    public $where = 'l.isMezzanine';

    public $groupBy = 'l.id';
    
    public $mainField = 'l.id';

    public $customInsert = 'locations\locationInfo';   

    public $errorDescription = [
        'invalidLocations' => [
            'captionSuffix' => 'with invalid Location Names:'
        ],
        'duplicateLocationUpc' => [
            'captionSuffix' => 'with duplicate UPC / Location Name:'
        ],
    ];
    
    public $errorFile = [
        'invalidUPCs' => [
            'captionSuffix' => 'with invalid UPCs:'
        ],
        'warehouseMismatch' => [
            'captionSuffix' => 'with UPC / Location Names warehouses mismatch at rows:'
        ],
        'minMaxMismatch' => [
            'captionSuffix' => 'with quantity mismatch at rows:'
        ],
        'unknownError' => [
            'captionSuffix' => 'with unknown error at rows:'
        ]
    ];
    
    /*
    ****************************************************************************
    */
 
    function getLocationsToReplenish($target=NULL, $filterIDs=[])
    {
        switch ($target) {
            case NULL:
                break;
            case 'vendorID':
                $target = 'AND co.' . $target;
                break;
            case 'warehouseID':
                $target = 'AND v.' . $target;
                break;
            default:
                die('Invalid target field');
        }
        
        $params = is_array($filterIDs) ? $filterIDs : [$filterIDs];
        
        $qMarks = $params ? $this->app->getQMarkString($params) : NULL;
        $whereClause = $params ? $target .' IN (' . $qMarks . ')' : NULL;
        
        $sql = 'SELECT    li.id, 
                          li.vendorID, 
                          CONCAT(w.shortName, "_", vendorName) AS vendor,
                          li.locID, 
                          l.displayName AS location,
                          li.upcID,
                          upc,
                          minCount,
                          SUM(uom) AS quantity,
                          maxCount - SUM(uom) AS supplement
                FROM      locations_info li
                JOIN      locations l ON l.id = li.locID
                JOIN      vendors v ON v.id = li.vendorID
                JOIN      warehouses w ON w.id = v.warehouseID
                JOIN      upcs u ON u.id = li.upcID
                JOIN      inventory_containers co ON co.vendorID = v.id
                JOIN      inventory_batches b ON b.recNum = co.recNum
                JOIN      inventory_cartons ca ON ca.batchID = b.id
                JOIN      statuses s ON s.id = ca.statusID
                JOIN      locations cl ON cl.id = ca.locID
                WHERE     li.upcID = b.upcID
                ' . $whereClause . '
                AND       s.shortName = "RK"
                AND       category = "inventory"
                AND       ca.statusID = ca.mStatusID
                AND       NOT isSplit
                AND       NOT unSplit
                AND       cl.isMezzanine
                AND       li.active
                GROUP BY  li.vendorID,
                          li.upcID,
                		  li.locID
                HAVING    quantity < minCount
                ';

        $result = $this->app->queryResults($sql, $params);

        foreach ($result as &$values) {
            // remove extra fields that were required for the query HAVING clause
            unset($values['minCount']);
            unset($values['quantity']);
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getByVendorUpcIDs($vendorUpcIDs)
    {       
        if (! $vendorUpcIDs) {
            return [];
        }

        $locationList = $params = [];

        foreach ($vendorUpcIDs as $vendorID => $upcIDs) {

            $upcQMarks = $this->app->getQMarkString($upcIDs);

            $clauses[] = 'vendorID = ? AND upcID IN (' . $upcQMarks . ')';

            $params = array_merge($params, [$vendorID], $upcIDs);
        }

        $sql = 'SELECT    li.id,
                          vendorID,
                          upcID,
                          locID,
                          displayName as location
                FROM      locations_info li
                JOIN      locations l ON l.id = li.locID
                WHERE     (' . implode(' OR ', $clauses) . ')
                AND       li.active
                GROUP BY  vendorID, 
                          upcID';

        $results = $this->app->queryResults($sql, $params);

        foreach ($results as $values) {

            $upcID = $values['upcID'];
            $vendorID = $values['vendorID'];

            $locationList[$vendorID][$upcID] = [
                'locID' => $values['locID'],
                'location' => $values['location']
            ];
        }

        return $locationList;
    }
    
    /*
    ****************************************************************************
    */   
 
  	function insertTable()
    {
        return $this->table;
    }

    /*
    ****************************************************************************
    */

    function customInsert($post)
    {
        $vendorID = $post['vendor'];
        $locationName = getDefault($post['location_name']);
        $upc = getDefault($post['upc']);
        $upcID = $this->getUPCID([$upc]);
        if (! $upcID) {
            die('UPC input not match or not active.');
        }

        $locID = $this->getLocationVendor($vendorID, $locationName);
        if (! $locID) {
            die('Wrong Warehouse Mezzanine Location.');
        }

        $isDuplicate = $this->checkDuplicateUPCLocation($locID, $upcID, $vendorID);

        if ($isDuplicate) {
            die('Vendor-Location-UPC Group exist in Mezzanine Location Info.');
        }

        $minValue = getDefault($post['minCount']);
        $maxValue = getDefault($post['maxCount']);
        $active = getDefault($post['active']);

        $sql = 'INSERT INTO locations_info (
                    vendorID,
                    locID,
                    upcID,
                    minCount,
                    maxCount,
                    active
                ) VALUES (
                    ?, ?, ?, ?, ?, ?
                )';

        $ajaxRequest = TRUE;

        $param = [$vendorID, $locID, $upcID, $minValue, $maxValue, $active];

        $this->app->runQuery($sql, $param, $ajaxRequest);

    }
  
    /*
    ****************************************************************************
    */

    function getUPCID($upc)
    {
    $qMarks = $this->app->getQMarkString($upc);

    $sql = 'SELECT  id
            FROM    upcs
            WHERE   upc IN (' . $qMarks . ')
            AND     active';

        $result = $this->app->queryResult($sql, $upc);

        $upcID = $result ? $result['id'] : NULL;

        return $upcID;
    }

    /*
    ****************************************************************************
    */

    function getLocationVendor($vendorID, $location)
    {
        $sql = 'SELECT  l.id
                FROM    locations l
                JOIN    warehouses w ON w.id = l.warehouseID
                JOIN    vendors v ON v.warehouseID = w.id
                WHERE   l.displayName = ?
                AND     v.id = ?
                AND     l.isMezzanine';

        $results = $this->app->queryResult($sql, [$location, $vendorID]);

        $locID = $results ? $results['id'] : NULL;

        return $locID;
    }

    /*
    ****************************************************************************
    */

    function checkDuplicateUPCLocation($locID, $upcID, $vendorID)
    {
        $sql = 'SELECT  li.id
                FROM    locations_info li
                JOIN    locations l ON l.id = li.locID
                JOIN    upcs u ON u.id = li.upcID
                WHERE   l.id = ?
                AND     li.vendorID = ?
                AND     u.id = ?
                AND     l.isMezzanine
                AND     li.active';

        $results = $this->app->queryResult($sql, [
            $locID,
            $vendorID,
            $upcID
        ]);

        return $results ? TRUE : FALSE;
    }
    
    /*
    ************************************************************************
    */
    
    function insertFile()
    {   
        $this->errors = $upcRows = $locationRows = $submittedUpcs = 
                $submittedLocations = $upcLocation = $insertValues = [];

        // Loop through each row of the worksheet in turn
        foreach ($this->importData as $rowIndex => $rowData) {
            if ($rowIndex == 1) {
                
                $this->handleColumnTitles($rowData);

                \excel\importer::checkTableErrors($this);

                if ($this->errors) {
                    return;
                }

                $this->getColumnKeys($rowData);

                continue;
            }

            // No blank rows
            if (! \array_filter($rowData)) {
                continue;
            }

            $upc = $rowData[$this->upcKey];
            $location = $rowData[$this->locationKey];

            $upcRows[$upc][] = $rowIndex;
            $locationRows[$location][] = $rowIndex;
            
            $key = $upc . ' / ' . $location;
            
            $upcLocation[$key][] = TRUE;
        }

        unset($this->importData[1]);

        $submittedUpcs = array_keys($upcRows);
        $submittedLocations = array_keys($locationRows);
        
        $ucps = new \tables\upcs($this->app);
        $locations = new \tables\locations($this->app);

        $mezzanineLocations = 
                $locations->getMezzanineLocationsData($submittedLocations);
        $upcWarehouses = $ucps->getUpcsWarehouses($submittedUpcs);

        $locationsWarehouses = [];

        foreach ($mezzanineLocations as $locID => $values) {

            $location = $values['displayName'];
            $warehouseID = $values['warehouseID'];
            
            $locationsWarehouses[$location][] = $warehouseID;
        }

        foreach ($this->importData as $rowIndex => $rowData) {

            \excel\importer::checkCellErrors([
                'model' => $this,
                'rowData' => $rowData,
                'rowIndex' => $rowIndex,
            ]);

            $upc = $rowData[$this->upcKey];
            $location = $rowData[$this->locationKey];
            $minCount = $rowData[$this->minCountKey];
            $maxCount = $rowData[$this->maxCountKey];

            if ($minCount > $maxCount) {
                $this->errors['minMaxMismatch'][$rowIndex][] = TRUE;
            }            

            $key = $upc . ' / ' . $location;
            
            if (! isset($upcLocation[$key])) {
                $this->errors['duplicateLocationUpc'][$rowIndex][] = $key;
            }

            unset($upcLocation[$key]);
                        
            $locationWarehouses = getDefault($locationsWarehouses[$location]);
            
            if ($location && ! $locationWarehouses) {
                $this->errors['invalidLocations'][$rowIndex][] = $location;
            }

            $upcWarehouse = getDefault($upcWarehouses[$upc]);
            
            if (! $upcWarehouse) {
                $this->errors['invalidUPCs'][$upc][] = TRUE;
            }

            if (! $locationWarehouses || ! $upcWarehouse) {
                continue;
            }

            $this->errors['warehouseMismatch'][$rowIndex][] = TRUE;
            
            foreach ($locationWarehouses as $warehouseID) {
                if ($warehouseID == $upcWarehouse) {

                    unset($this->errors['warehouseMismatch'][$rowIndex]);

                    break;
                }
            }
            
            $insertValues[$key] = [
                'upc' => $upc,
                'location' => $location
            ];
        }

        if (! isset($rowData)) {
            // the file has proper extension, but actually is not an Excel file
            return $this->errors['wrongType'] = TRUE;
        }

        if (! getDefault($this->errors['warehouseMismatch'])) {
            unset($this->errors['warehouseMismatch']);
        }
                
        if ($insertValues && ! $this->errors) {

            $results = $this->getUpcLocationVendor($insertValues);

            $this->checkRowConsistency($results, $insertValues);
        }
            
        if (! $this->errors) {
            
            $sql = 'INSERT INTO locations_info (
                    vendorID,
                    locID,
                    upcID,
                    minCount,
                    maxCount
                ) VALUES (
                    ?, ?, ?, ?, ?
                ) ON DUPLICATE KEY UPDATE 
                    minCount = ?,
                    maxCount = ?,
                    active = 1
                ';

            $this->app->beginTransaction();

            foreach ($this->importData as $rowIndex => $rowData) {

                $upc = $rowData[$this->upcKey];
                $location = $rowData[$this->locationKey];
                $minCount = $rowData[$this->minCountKey];
                $maxCount = $rowData[$this->maxCountKey];

                $key = $upc . ' / ' . $location;
            
                $this->app->runQuery($sql, [
                    $results[$key]['vendorID'],
                    $results[$key]['locID'],
                    $results[$key]['upcID'],
                    $minCount,
                    $maxCount,
                    $minCount,
                    $maxCount                    
                ]);
            }

            $this->app->commit();
        }
    }        
    
    /*
    ************************************************************************
    */

    function getColumnKeys($rowData)
    {
        $fieldKeys = array_flip($rowData);

        $this->upcKey = $fieldKeys['upc'];
        $this->locationKey = $fieldKeys['location_name'];
        $this->minCountKey = $fieldKeys['min_count'];
        $this->maxCountKey = $fieldKeys['max_count'];
    }
    
    /*
    ************************************************************************
    */

    function getUpcLocationVendor($data)
    {
        if (! $data) {
            return [];
        }

        foreach ($data as $values) {

            $params[] = $values['upc'];
            $params[] = $values['location'];
        }
        
        $clauses = array_fill(0, count($data), 'upc = ? AND l.displayName = ?');
        
        $sql = 'SELECT    CONCAT(upc, " / ", l.displayName),
                          vendorID,
                          l.id AS locID,
                          upcID
                FROM      upcs u
                JOIN      inventory_batches b ON b.upcID = u.id
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      vendors v ON v.id = co.vendorID
                JOIN      locations l ON l.warehouseID = v.warehouseID
                WHERE     ' . implode(' OR ', $clauses);

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }
    
    /*
    ************************************************************************
    */
    
    function checkRowConsistency($results, $insertValues)
    {
        if (count($results) != count($insertValues)) {

            $keys = array_keys($insertValues);

            $count = 0;

            foreach ($keys as $key) {

                $count++;

                if (! isset($results[$key])) {
                    $this->errors['unknownError'][$count][] = TRUE;
                }
            }
        }
    }
    
    /*
    ************************************************************************
    */
    
    function importSuccess()
    { 
        \locations\minMax::importSuccessHTML();
    }
    
    /*
    ************************************************************************
    */
    
}