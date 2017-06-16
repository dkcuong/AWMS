<?php

namespace tables;

class locations extends _default
{
    public $primaryKey = 'id';

    public $ajaxModel = 'locations';

    public $fields = [
        'id' => [],
        'displayName' => [],
        'isShipping' => [],
    ];

    public $table = 'locations';

    const NAME_LOCATION_STAGING = 'staging';
    const NAME_LOCATION_BACK_TO_STOCK = 'Back To Stock';
    const NAME_CATEGORY_INVENTORY = 'inventory';
    const PREFIX_REGULAR_LOCATION = '1';
    const PREFIX_MEZZANINE_LOCATION = '2';
    const PREFIX_LOCATION_NAME = '0';
    const LETTER_MEZZANINE_LOCATION = 'Z';
    const LENGTH_VALID_LOCATION = 4;

    /*
    ****************************************************************************
    */

    function invalidLocations($locs, $clause=1, $param=[], $returnArray=FALSE)
    {
        $param = is_array($param) ? $param : [$param];

        $locsNoHyphens = $this->getLocationsNoHyphens($locs);

        $locsName = $this->getLocationDisplayName([
            'locs' => $locs,
            'param' => $param,
            'locsNoHyphens' => $locsNoHyphens,
            'clause' => $clause
        ]);

        $badLocs = $this->getBadLocations([
            'locs' => $locs,
            'locsNoHyphens' => $locsNoHyphens,
            'locsName' => $locsName,
        ]);

        return $returnArray ? $badLocs : implode(', ', $badLocs);
    }

    /*
    ****************************************************************************
    */

    function getByPlate($plate)
    {
        $sql = 'SELECT  l.displayName AS location
                FROM    inventory_cartons ca
                JOIN    locations l ON ca.locID = l.id
                WHERE   plate = ?';

        $result = $this->app->queryResult($sql, [$plate]);

        return $result ? $result['location'] : NULL;
    }

    /*
    ****************************************************************************
    */

    function getLocationCartonQuantity($location)
    {
        $sql = 'SELECT  COUNT(ca.id) AS quantity
                FROM    inventory_cartons ca
                JOIN    locations l ON l.id = ca.locID
                WHERE   displayName = ?
                GROUP BY locID';

        $result = $this->app->queryResult($sql, [$location]);

        return intVal($result['quantity']);
    }

    /*
    ****************************************************************************
    */

    static function getLocationIndex($array, $checkLocation=FALSE, $straight=FALSE)
    {

        $location = $array['location'];
        $firstLetter = substr($location, 0, 1);

        $locBits = explode('-', $location);

        $result = self::PREFIX_LOCATION_NAME . $location;

        $checkLocation = getDefault($checkLocation);
        $straightSortOrder = getDefault($straight);

        $params = [
            'locBits' => $locBits,
            'checkLocation' => $checkLocation,
            'straightSortOrder' => $straightSortOrder
        ];

        if (is_numeric($firstLetter)) {
            // Regular location - Prefix is "0"

            $sorted = self::sortLocation($params);

            $result = self::PREFIX_REGULAR_LOCATION . $sorted;
        } elseif (self::LETTER_MEZZANINE_LOCATION == strtoupper($firstLetter)) {
            // Mezzanine location - Prefix is "2"

            array_shift($locBits);

            $params['locBits'] = $locBits;

            $sorted = count($locBits) != self::LENGTH_VALID_LOCATION ?
                implode(NULL, $locBits) : self::sortLocation($params);

            $result = self::PREFIX_MEZZANINE_LOCATION . $sorted;
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function sortLocation($params)
    {
        $locBits = $params['locBits'];
        $checkLocation = $params['checkLocation'];
        $straightSortOrder = $params['straightSortOrder'];

        $lengthLocationValid = self::LENGTH_VALID_LOCATION;

        if (count($locBits) < $lengthLocationValid && $checkLocation) {
            echo 'invalid location';
            die;
        }

        for ($i = 0; $i < $lengthLocationValid; $i++) {
            $locBits[$i] = getDefault($locBits[$i], NULL);
        }

        list($locBit1, $locBit2, $locBit3, $locBit4) = $locBits;

        $result = $locBit1 . $locBit3 . $locBit2 . $locBit4;

        if ($straightSortOrder) {
            $result = $locBit1 . $locBit2 . $locBit3 . $locBit4;
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getLocIDs($updates)
    {
        $locations = [];

        if (! $updates) {
            return $locations;
        }

        list($params, $clauses) = $this->getParamsClauseOfLocsUpdates($updates);
        $locationsName =
                $this->getLocationsDisplayNameByClause($params, $clauses);

        if (! $locationsName) {
            return $locations;
        }

        foreach ($locationsName as $location => $row) {
            $warehouseID = $row['warehouseID'];
            $locations[$warehouseID][$location] = $row['id'];
        }

        return $locations;

    }

    /*
    ****************************************************************************
    */

    function getShippingLocationsByName()
    {
        $fields = implode(', ', $this->fields);

        $sql = 'SELECT displayName,
                       ' . $fields . '
                FROM   ' . $this->table . '
                WHERE  isShipping';

        $result = $this->app->queryResults($sql);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getFreeShippingLocations($warehouseID=NULL)
    {
        $clause = NULL;

        $params = [];

        if ($warehouseID) {

            $clause = ' AND warehouseID = ? ';

            $params = [$warehouseID, $warehouseID];
        }

        $sql = 'SELECT  l.id,
                        warehouseID
                FROM    locations l
                LEFT JOIN (
                    -- locations where Pick Ticket orders were not shipped yet
                    SELECT    l.id
                    FROM      locations l
                    JOIN      pick_waves pw ON pw.locID = l.id
                    JOIN      pick_cartons pc ON pc.pickID = pw.id
                    JOIN      neworder n ON n.id = pc.orderID
                    JOIN      statuses s ON s.id = n.statusID
                    WHERE     isShipping
                    AND       s.shortName NOT IN (
                                  "' . orders::STATUS_SHIPPED_CHECK_OUT . '",
                                  "' . orders::STATUS_CANCELED . '")
                    ' . $clause . '
                    AND       active
                    GROUP BY  l.id
                ) ul ON ul.id = l.id
                WHERE isShipping
                ' . $clause . '
                AND       ul.id IS NULL';

        $result = $this->app->queryResults($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getMissingLocations($locationNames)
    {
        $qMarks = $this->app->getQMarkString($locationNames);

        $sql = 'SELECT  displayName
                FROM    locations
                WHERE   displayName IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $locationNames);

        $existingLocations = array_keys($results);

        $missingLocations = array_diff($locationNames, $existingLocations);

        return array_values($missingLocations);
    }

    /*
    ****************************************************************************
    */

    function getBatchShippingLocation($batch)
    {
        $sql = 'SELECT  l.id,
                        l.displayName
                FROM    locations l
                JOIN    pick_waves pw ON pw.locID = l.id
                JOIN    pick_cartons pc ON pc.pickID = pw.id
                JOIN    neworder n ON n.id = pc.orderID
                WHERE   order_batch = ?
                AND     pc.isOriginalPickTicket
                AND     pc.active
                LIMIT   1';

        $result = $this->app->queryResult($sql, [$batch]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkWarehouseLocation($params, $warehouseID)
    {
        $qMarks = $this->app->getQMarkString($params);

        $locationClause = $params ? 'l.displayName IN (' . $qMarks . ')' : 1;

        $sql = 'SELECT    l.displayName,
                          l.id
                FROM      locations l
                JOIN      warehouses w ON w.id = l.warehouseID
                WHERE     ' . $locationClause . '
                AND       ! isShipping
                AND       l.displayName NOT IN ('
                    . '"' . self::NAME_LOCATION_STAGING . '", '
                    . '"' . self::NAME_LOCATION_BACK_TO_STOCK . '"'
                . ')
                AND       w.id = ?
                ORDER BY  l.id DESC';

        $params[] = $warehouseID;

        $results = $this->app->queryResults($sql, $params);

        $keys = array_keys($results);

        $values = array_column($results, 'id');

        return array_combine($keys, $values);
    }

    /*
    ****************************************************************************
    */

    function getPossibleWarehouseIDsByDisplayNames($displayName)
    {
        $displayNames = is_array($displayName) ? $displayName : [$displayName];

        $qMarkString = $this->app->getQMarkString($displayNames);

        $sql = 'SELECT warehouseID,
                       displayName AS location
                FROM   locations
                WHERE  displayName IN (' . $qMarkString . ')';

        $results = $this->app->queryResults($sql, $displayNames);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getLocationsWrongWarehouses($locations, $warehouseID)
    {
        $qMarkString = $this->app->getQMarkString($locations);

        $sql = 'SELECT  CONCAT_WS("-", w.id, l.id) AS entryKey,
                        l.displayName AS location,
                        w.displayName AS warehouse
                FROM    warehouses w
                JOIN    locations l ON l.warehouseID = w.id
                WHERE   l.displayName IN (' . $qMarkString . ')
                AND     w.id != ?';

        $locations[] = $warehouseID;

        $results = $this->app->queryResults($sql, $locations);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getUsedLocations($locations, $warehouseID)
    {
        $qMarkString = $this->app->getQMarkString($locations);

        $sql = 'SELECT l.displayName
                FROM   locations l
                JOIN   inventory_cartons ca ON ca.locID = l.id
                JOIN   statuses s ON s.id = ca.statusID
                WHERE  l.displayName IN (' . $qMarkString . ')
                AND    s.shortName = ?
                AND    l.warehouseID = ?
                ';

        $locations[] = inventory\cartons::STATUS_RACKED;
        $locations[] = $warehouseID;

        $results = $this->app->queryResults($sql, $locations);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getLocationfromVendor($vendorIDs)
    {
        $qMarkString = $this->app->getQMarkString($vendorIDs);

        $sql = 'SELECT  v.id,
                        locationID
                FROM    warehouses w
                JOIN    vendors v ON v.warehouseID = w.id
                WHERE   v.id IN (' . $qMarkString . ')';

        $results = $this->app->queryResults($sql, $vendorIDs);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getMezzanineLocationsData($data)
    {
        if (! $data) {
            return [];
        }

        $params = [];

        $locationData = reset($data);

        if (is_array($locationData)) {
            // warehouse ID is passed
            foreach ($data as $warehouseID => $locIDs) {

                $qMarks = $this->app->getQMarkString($locIDs);

                $clauses[] = 'warehouseID = ? AND displayName IN (' . $qMarks . ')';

                $params = array_merge($params, [$warehouseID], $locIDs);
            }
        } else {
            // disregard warehouse ID
            $qMarks = $this->app->getQMarkString($data);

            $clauses = ['displayName IN (' . $qMarks . ')'];

            $params = $data;
        }

        $sql = 'SELECT    id,
                          displayName,
                          warehouseID
                FROM      ' . $this->table . '
                WHERE     (' . implode(' OR ', $clauses) . ')
                AND       isMezzanine';

        $result = $this->app->queryResults($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getLocationNames($data)
    {
        $location = $data['term'];
        $recNum = getDefault($data['recNum']);
        $vendorID = getDefault($data['vendorID']);
        $clause = getDefault($data['clause'], 1);

        // $location != '0' - allows to display results when only "0" is input
        if (! $location && $location != '0') {
            return FALSE;
        }

        $results = [];

        $join = $clause = NULL;

        $params = [$location . '%'];

        if ($recNum) {

            $join = 'JOIN      warehouses w ON w.id = l.warehouseID
                     JOIN      vendors v ON v.warehouseID = w.id
                     JOIN      inventory_containers co ON co.vendorID = v.ID';

            $clause = 'AND       recNum = ? ';

            $params[] = $recNum;
        } else if ($vendorID) {

            $join = 'JOIN      vendors v ON v.warehouseID = l.warehouseID';

            $clause = 'AND       v.id = ? ';

            $params[] = $vendorID;
        }

        $sql = 'SELECT    l.displayName
                FROM      locations l
                ' . $join . '
                WHERE     l.displayName LIKE ?
                ' . $clause . '
                AND       NOT isShipping
                AND       l.displayName NOT IN ('
                    . '"' . self::NAME_LOCATION_STAGING . '", '
                    . '"' . self::NAME_LOCATION_BACK_TO_STOCK . '"'
                . ')
                GROUP BY  l.displayName
                LIMIT     10';

        $locationNames = $this->app->queryResults($sql, $params);

        if (! $locationNames) {
            return $results;
        }

        $keyLocationNames = array_keys($locationNames);

        foreach ($keyLocationNames as $row) {
            $results[] = ['value' => $row];
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getUPCBackupLocations($upcs, $vendorID, $mezzanineClause)
    {
        $backupLocations = [];

        $locations = $this->getInfoLocationByUpcs([
            'vendorID' => $vendorID,
            'mezzanineClause' => $mezzanineClause,
            'upcs' => $upcs,
        ]);

        if (! $locations) {
            return $backupLocations;
        }

        foreach ($locations as $row) {

            $upc = $row['upc'];
            $location = $row['location'];
            $pieces = $row['pieces'];

            $backupLocations[$upc][$location] = $pieces;
        }

        return $backupLocations;
    }

    /*
    ****************************************************************************
    */

    function getLocationDisplayName($data)
    {
        $locs = $data['locs'];
        $param = $data['param'];
        $locsNoHyphens = $data['locsNoHyphens'];
        $clause = $data['clause'];
        $vendorID = getDefault($data['vendorID']);

        $qMarks = $this->app->getQMarkString($locs);

        $joinClause = $vendorID ? '
                JOIN    vendors v ON v.warehouseID = l.warehouseID' : NULL;
        $whererClause = $vendorID ? 'v.id = ?' : 1;
        $vendorParam = $vendorID ? [$vendorID] : [];

        $sql = 'SELECT  UCASE(
                            REPLACE(displayName, "-", "")
                        ),
                        l.id
                FROM    locations l
                ' . $joinClause . '
                WHERE   ' . $whererClause . '
                AND     (displayName IN (' . $qMarks . ')
                AND     ' . $clause . '
                OR      displayName IN (' . $qMarks . ')
                AND     ' . $clause . ')';

        $params = array_merge($vendorParam, $locs, $param, $locsNoHyphens, $param);

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getLocationsNoHyphens($locs)
    {
        $results = [];

        foreach ($locs as $loc) {
            $results[] = str_replace('-', NULL, $loc);
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getBadLocations($params)
    {
        $locs = $params['locs'];
        $locsNoHyphens = $params['locsNoHyphens'];
        $locsName = $params['locsName'];

        $results = [];

        foreach ($locsNoHyphens as $index => $loc) {
            $originalLoc = $locs[$index];
            $upperCase = strtoupper($loc);

            if (! isset($locsName[$upperCase])) {
                $results[] = $originalLoc;
            }
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getParamsClauseOfLocsUpdates($locs)
    {
        $params = $clauses = [];

        foreach ($locs as $loc) {

            $params[] = $loc['warehouseID'];
            $params[] = $loc['locationName'];

            $clauses[] = 'warehouseID = ? AND displayName = ?';
        }

        $results = [$params, $clauses];

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getLocationsDisplayNameByClause($params, $clauses)
    {
        $clausesString = implode(' OR ', $clauses);

        $sql = 'SELECT  UCASE(displayName),
                        warehouseID,
                        id
                FROM    locations
                WHERE   ' . $clausesString;

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getInfoLocationByUpcs($params)
    {
        $vendorID = $params['vendorID'];
        $mezzanineClause = $params['mezzanineClause'];
        $upcs = $params['upcs'];

        $qMarks = $this->app->getQMarkString($upcs);

        $sql = 'SELECT    CONCAT(u.id, "-", l.displayName),
                          upc,
                          l.displayName AS location,
                		  SUM(uom) AS pieces
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      statuses s ON s.id = ca.statusID
                JOIN      locations l ON l.id = ca.locID
                JOIN      upcs u ON u.id = b.upcID
                WHERE     category = ?
                AND       shortName = ?
                AND       NOT isSplit
                AND       NOT unSplit
                AND       vendorID = ?
                AND       upc IN (' . $qMarks . ')
                AND       ' . $mezzanineClause . '
                AND       NOT isShipping
                GROUP BY  upc,
                          l.id
                ORDER BY  upc ASC,
                          l.displayName ASC';

        array_unshift(
            $upcs,
            self::NAME_CATEGORY_INVENTORY,
            inventory\cartons::STATUS_RACKED,
            $vendorID
        );

        $results = $this->app->queryResults($sql, $upcs);

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getLocationsByWarehouseId($app, $warehouseID)
    {
        if (! $warehouseID) {
            return [];
        }

        $sql = 'SELECT  displayName,
                        isShipping,
                        isMezzanine
                FROM    locations
                WHERE   warehouseID = ?';

        $results = $app->queryResults($sql, [$warehouseID]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkMinMaxLocation($location, $vendorID)
    {
        $sql = 'SELECT    l.id
                FROM      locations l
                JOIN      vendors v ON v.warehouseID = l.warehouseID
                WHERE     l.displayName = ?
                AND       v.id = ?
                AND       isMezzanine
                ';

        $results = $this->app->queryResult($sql, [$location, $vendorID]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkLocationInvalid($dataInput)
    {
        $data = [];

        $sql = 'SELECT 	  displayName
                FROM      locations
                WHERE     displayName = ?';

        foreach ($dataInput as $key => $location) {

            $queryResults = $this->app->queryResult($sql, [$location]);

            $queryResults ? array_push($data, $queryResults['displayName'] )
                : FALSE;

        }

        $results = array_diff($dataInput, $data);

        return $results ? array_values($results) : FALSE;

    }

    /*
    ****************************************************************************
    */

    function getLocationWarehouses($displayName, $allowMezzanine)
    {
        $displayNames = is_array($displayName) ? $displayName : [$displayName];

        $clause = $allowMezzanine ? NULL : 'AND    isMezzanine';

        $qMarks = $this->app->getQMarkString($displayNames);

        $sql = 'SELECT id,
                       displayName,
                       warehouseID
                FROM   locations
                WHERE  displayName IN (' . $qMarks . ')'
                . $clause;

        $results = $this->app->queryResults($sql, $displayNames);

        $return = [];

        foreach ($results as $id => $values) {

            $displayName = $values['displayName'];

            $return[$displayName]['locID'] =  $id;
            $return[$displayName]['warehouses'][] = $values['warehouseID'];
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getTypeLocationsByOrderNumber($orderNumbers, $type=self::NAME_LOCATION_STAGING)
    {
        $params = is_array($orderNumbers) ? $orderNumbers : [$orderNumbers];

        $qMarks = $this->app->getQMarkString($params);

        $sql = 'SELECT    scanOrderNumber,
                          l.id
                FROM      locations l
                JOIN      vendors v ON v.warehouseID = l.warehouseID
                JOIN      order_batches b ON b.vendorID = v.id
                JOIN      neworder n ON n.order_batch = b.id
                WHERE     scanOrderNumber IN (' . $qMarks . ')
                AND       displayName = ?';

        $params[] = $type;

        $results = $this->app->queryResults($sql, $params);

        $keys = array_keys($results);

        $values = array_column($results, 'id');

        return array_combine($keys, $values);
    }

    /*
    ****************************************************************************
    */

    function checkRCLogLocations($locationNames, $recNum)
    {
        if (! $locationNames || ! $recNum) {
            return FALSE;
        }

        $return['wrongWarehouse'] = $return['usedLocations']= FALSE;

        $warehouses = new warehouses($this->app);

        $warehouseID = $warehouses->getWarehouseByRecNum($recNum);

        $submittedLocations = is_array($locationNames) ? $locationNames :
            [$locationNames];

        foreach ($submittedLocations as $location) {
            $zeroLocations[] = '0' . $location;
        }

        $clauseLocations = array_merge($submittedLocations, $zeroLocations);

        $validLocations = $this->checkWarehouseLocation($clauseLocations, $warehouseID);

        $return['validLocations'] = $validLocations ? $validLocations : FALSE;

        $validLocationValues = array_keys($validLocations);

        // Bug fix: if there are two locations return with only one starting
        // with a zero, false errors were returned
        // Also the errors broke the complete RC Log button
        $clauseLocations =
            count($clauseLocations) > count($validLocationValues) ?
            $validLocationValues : $clauseLocations;

        $checkWarehouseLocations = array_diff($clauseLocations, $validLocationValues);

        if ($checkWarehouseLocations) {
            // check if location belongs to a different warehouse
            $checkWarehouseLocations = array_values($checkWarehouseLocations);

            $results = $this->getLocationsWrongWarehouses(
                    $checkWarehouseLocations, $warehouseID
            );

            foreach ($results as $values) {

                $location = $values['location'];
                $warehouse = $values['warehouse'];

                $return['wrongWarehouse'][$location][] = $warehouse;
            }
        }

        if ($validLocationValues) {
            foreach ($validLocationValues as $location) {
                $zeroLocations[] = '0' . $location;
            }

            $clauseLocations = array_merge($validLocationValues, $zeroLocations);

            $results = $this->getUsedLocations($validLocationValues, $warehouseID);

            $return['usedLocations'] = $results ? array_keys($results) : FALSE;
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getCustomerNameByWarehouseID($location, $warehouseID)
    {
        // $location != '0' - allows to display results when only "0" is input
        if (! $location && $location != '0') {
            return FALSE;
        }

        $results = [];

        $params = [$location . '%', $warehouseID];

        $sql = 'SELECT    displayName
                FROM      locations l
                WHERE     displayName LIKE ?
                AND       warehouseID = ?
                AND       NOT isShipping
                AND       l.displayName NOT IN ('
                    . '"' . self::NAME_LOCATION_STAGING . '", '
                    . '"' . self::NAME_LOCATION_BACK_TO_STOCK . '"'
                . ')
                GROUP BY  displayName
                LIMIT     10';

        $locationNames = $this->app->queryResults($sql, $params);

        if (! $locationNames) {
            return $results;
        }

        $keyLocationNames = array_keys($locationNames);

        foreach ($keyLocationNames as $row) {
            $results[] = ['value' => $row];
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getMezzanineLocations($locationNames)
    {
        if (! $locationNames) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($locationNames);

        $sql = 'SELECT    displayName,
                          isMezzanine
                FROM      locations
                WHERE     displayName IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $locationNames);

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getLocationByIds($app, $locationIDs, $fields = NULL)
    {
        if (! $locationIDs) {
            return;
        }

        $selectFields = $fields ? $fields :
                'id,
                displayName,
                warehouseID';

        $locationIDs = (array) $locationIDs;

        $qMarks = $app->getQMarkString($locationIDs);

        $sql = 'SELECT    ' . $selectFields . '
                FROM      locations
                WHERE     id IN (' . $qMarks . ')';

        $results = $app->queryResults($sql, $locationIDs);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getByName($locations, $warehouseID)
    {
        if (! $locations || ! $warehouseID) {
            return [];
        }

        $qMarkString = $this->app->getQMarkString($locations);

        $sql = 'SELECT  id,
                        displayName
                FROM    locations
                WHERE   displayName IN (' . $qMarkString . ')
                AND     warehouseID = ?';

        $locations[] = $warehouseID;

        $results = $this->app->queryResults($sql, $locations);

        $locIDs = array_keys($results);

        $names = array_column($results, 'displayName');

        return array_combine($names, $locIDs);
    }

    /*
    ****************************************************************************
    */

    static function sortLocations($values, $locationKeys=[])
    {
        foreach ($values as $value) {

            $locationName = $value['location'];

            if (isset($locationKeys[$locationName])) {
                continue;
            }

            $isCheck = FALSE;
            $straight = TRUE;

            $locationKeys[$locationName] =
                    self::getLocationIndex($value, $isCheck, $straight);
        }

        return $locationKeys;
    }

    /*
    ****************************************************************************
    */

}
