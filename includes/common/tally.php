<?php

namespace common;

use tables\inventory\cartons;

class tally
{
    public $mvc;
    public $tally;
    public $recNum;
    public $tallyID;
    public $container;
    public $rowCartons = [];

    static $getTally = [];
    static $nextPlate;
    static $plateBatch;
    static $firstPlate;
    static $selectParams = [];
    static $nextCartonID;
    static $checkProducts = TRUE;
    static $errorMsg = '';

    const DEACTIVE = 0;
    const ACTIVE = 1;
    const CATEGORY_INVENTORY = 'inventory';
    const LOCK_YES = 'YES';
    const LOCK_NO = 'NO';
    const NUMBER_OF_COLUMN = 4;

    /*
    ****************************************************************************
    */

    function __construct($params)
    {
        $this->mvc = $params['mvc'];
        $this->tally = getDefault($params['tally']);
        $this->recNum = getDefault($params['recNum']);
        $this->container = getDefault($params['container']);
        $this->forcedGo = getDefault($params['forcedGo']);
        return $this;
    }

    /*
    ****************************************************************************
    */

    function transferContainers($oldRows)
    {
        foreach ($oldRows as $recNum => $tally) {
            $this->tally = $tally;

            $row = reset($tally);

            $this->recNum = $recNum;
            $this->container = $row['container'];

            $this->checkProducts();

            $this->createTally();

            $this->getTallyIDs([
                'app' => $this->mvc,
                'recNums' => $recNum,
            ]);

            $this->unsetRows();

            $this->insertRows();

            $this->getRowIDs();

            $this->unsetCartons();

            $this->insertCartons();
        }
    }

    /*
    ****************************************************************************
    */

    function updateTallySheet()
    {
        $this->checkProducts();

        if (isset($this->mvc->results['badProducts'])) {
            return;
        }

        $this->checkCartons();

        if (isset($this->mvc->results['badTally'])
        && $this->forcedGo != self::LOCK_YES)
        {
            return;
        }

        $this->getReceivingNumber();

        $this->createTally();

        $this->getTallyIDs([
            'app' => $this->mvc,
            'recNums' => $this->recNum,
        ]);

        $this->unsetRows();

        $this->insertRows();

        $this->getRowIDs();

        $this->unsetCartons();

        $this->insertCartons();

        $this->mvc->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    static function getContainerProducts($params)
    {
        if (isset(self::$selectParams['cartonsByInfo'])) {
            return self::$selectParams['cartonsByInfo'];
        }

        $app = $params['app'];
        $recNums = $params['recNums'];

        $results = [];

        $cartons = self::getCartonsByContainers($app, $recNums);

        if (! $cartons) {
            return $results;
        }

        foreach ($cartons as $caID => $row) {

            $container = $row['name'];
            $upc = $row['upc'];
            $sku = $row['sku'];
            $batch = $row['batchID'];

            $results[$container][$batch][$sku][$upc][] = $caID;
        }

        self::$selectParams['cartonsByInfo'] = $results;
        self::$selectParams['containerCartons'] = $cartons;

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkProducts($nextCartonID=FALSE)
    {
        // Make sure all the styles and UPCs are valid
        $this->rowCartons = [];

        $badProducts = $accounted = $cartonsByInfo = [];

        self::$nextCartonID = $nextCartonID ? $nextCartonID :
            self::$nextCartonID;

        $cartonsByInfo = self::getContainerProducts(['app' => $this->mvc]);

        $results = [];

        foreach ($this->tally as $id => $row) {

            $rowCartons = $row['cartonCount'] * $row['plateCount'];

            if (self::$checkProducts) {
                $results = $this->getCartonIDProducts([
                    'tallyRow' => $row,
                    'cartonsByInfo' => &$cartonsByInfo,
                    'rowCartons' => $rowCartons,
                    'accounted' => &$accounted,
                ]);
            } else {
                $results = $this->setNextCartonIDRows($rowCartons, $results);
            }

            if (! $results) {
                $badProducts[$id] = TRUE;
            }

            $this->setCartonForRowCartonID($id, $results);
        }

        if ($badProducts) {
            $this->mvc->results['badProducts'] = $badProducts;
        }
    }

    /*
    ****************************************************************************
    */

    function checkCartons()
    {
        // Check if all container cartons are accounted
        $containerUPCs = $this->getContainerUPCs($this->container);

        if (! $containerUPCs) {
            return FALSE;
        }

        $upcsPassed = $this->getUpcsPassed($this->tally);

        if (! $upcsPassed) {
            return FALSE;
        }

        $badTally = [];
        foreach ($containerUPCs as $upc => $row) {

            if (getDefault($upcsPassed[$upc]) == $row['cartonCount']) {
                continue;
            }

            $badTally[$row['sku']] = [
                'actual' => $row['cartonCount'],
                'passed' => $upcsPassed[$upc],
            ];
        }

        if ($badTally) {
            $this->mvc->results['badTally'] = $badTally;
        }

        return $badTally;
    }

    /*
    ****************************************************************************
    */

    function getReceivingNumber()
    {
        // Get the rec num
        $sql = 'SELECT  co.recNum
                FROM    inventory_cartons ca
                JOIN    inventory_batches b ON ca.batchID = b.id
                JOIN    inventory_containers co ON co.recNum = b.recNum
                WHERE   co.name = ?
                AND NOT isSplit
                AND NOT unSplit
                LIMIT   1';

        $data = $this->mvc->queryResult($sql, [$this->container]);

        $result = $data ? $data['recNum'] : NULL;

        $this->recNum = $result;

        return $result;
    }

    /*
    ****************************************************************************
    */

    function createTally()
    {
        // Create the tally
        $sql = 'INSERT INTO tallies (
                    recNum,
                    rowCount
                ) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE
                    rowCount = VALUES(rowCount)';

        $rowCount = count($this->tally);

        $result =  $this->mvc->runQuery($sql, [$this->recNum, $rowCount]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function storeNextTallyRowID($nextID)
    {
        self::$selectParams['tallyRowIDs'] = [];
        self::$selectParams['tallyRowID'] = $nextID;
    }

    /*
    ****************************************************************************
    */

    static function projectTallyIDs($recNums, $nextID)
    {
        foreach ($recNums as $recNum) {
            self::$selectParams['tallyIDs'][$recNum] = [
                'id' => $nextID++,
            ];
        }
    }

    /*
    ****************************************************************************
    */

    static function getTallyIDs($params)
    {
        if (isset(self::$selectParams['tallyIDs'])) {
            return;
        }

        $app = $params['app'];
        $recNum = $params['recNums'];
        $recNums = is_array($recNum) ? $recNum : [$recNum];

        // Get the tally ID
        $qMarkString = $app->getQMarkString($recNums);

        $sql = 'SELECT  recNum,
                        id
                FROM    tallies
                WHERE   recNum IN (' . $qMarkString . ')';

        $results = $app->queryResults($sql, $recNums);

        self::$selectParams['tallyIDs'] = $results;

        return $results;
    }

    /*
    ****************************************************************************
    */

    function unsetRows()
    {
        // Unset all rows for this container

        $sql = 'UPDATE    tally_rows r
                JOIN      tallies t ON t.id = r.tallyID
                SET       active = ?
                WHERE     recNum = ?';

        $result = $this->mvc->runQuery($sql, [self::DEACTIVE, $this->recNum]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function insertRows()
    {
        // Insert the tally rows
        $recNum = $this->recNum;
        $tallyID = self::$selectParams['tallyIDs'][$recNum]['id'];

        $this->insertListTallyRows($tallyID);

        $firstRowID = getDefault(self::$selectParams['firstRowID']);
        $previousRowIDs =
            getDefault(self::$selectParams['previousRowIDs'][$recNum], []);

        if (! $firstRowID) {
            return;
        }

        $totalRows = count($this->tally);
        $previousCount = count($previousRowIDs);

        self::$selectParams['tallyRowIDs'][$tallyID] = $previousRowIDs;

        if ($previousCount > $totalRows) {
            return;
        }

        $newRows = $totalRows - $previousCount;

        for ($i = 0; $i < $newRows; $i++) {
            self::$selectParams['tallyRowIDs'][$tallyID][] = $firstRowID++;
        }
    }

    /*
    ****************************************************************************
    */

    static function queryRowIDs($params)
    {
        $results = [];

        $app = $params['app'];
        $recNum = $params['recNums'];
        $recNums = is_array($recNum) ? $recNum : [$recNum];

        $data = self::getRecNumFromTallyRows($app, $recNums);

        if (! $data) {
            return $results;
        }

        foreach ($data as $rowID => $row) {
            $recNum = $row['recNum'];
            $results[$recNum][] = $rowID;
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getPreviousRowIDs($params)
    {
        if (! isset(self::$selectParams['previousRowIDs'])) {
            self::$selectParams['previousRowIDs'] = self::queryRowIDs($params);
        }
    }

    /*
    ****************************************************************************
    */

    function getRowIDs()
    {
        $recNum = $this->recNum;

        // Get the row IDs
        $results = self::queryRowIDs([
            'app' => $this->mvc,
            'recNums' => $recNum,
        ]);

        $this->tallyRows = $results[$recNum];
    }

    /*
    ****************************************************************************
    */

    function unsetCartons()
    {
        // Unset all cartons for this container

        $sql = 'UPDATE    tally_rows r
                JOIN      tallies t ON t.id = r.tallyID
                JOIN      tally_cartons c ON c.rowID = r.id
                SET       c.active = ?
                WHERE     recNum = ?';

        $this->mvc->runQuery($sql, [self::DEACTIVE, $this->recNum]);
    }

    /*
    ****************************************************************************
    */

    function insertCartons()
    {
        // Insert cartons

        $sql = 'INSERT INTO tally_cartons (
                    rowID,
                    invID,
                    active
                ) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    rowID = VALUES(rowID),
                    active = 1';

        $recNum = $this->recNum;

        $tallyID = self::$selectParams['tallyIDs'][$recNum]['id'];

        foreach ($this->rowCartons as $cartons) {
            $this->insertCartonRow($cartons, $tallyID, $sql);
        }
    }

    /*
    ****************************************************************************
    */

    function insertCartonRow($cartons, $tallyID, $sql)
    {
        // If less cartons were on the RC Log in the containre
        if (! isset(self::$selectParams['tallyRowIDs'][$tallyID])) {
            return;
        }

        $nextRowID = array_shift(self::$selectParams['tallyRowIDs'][$tallyID]);

        foreach ($cartons as $cartonID) {
            $this->mvc->runQuery($sql, [
                $nextRowID,
                $cartonID,
                self::ACTIVE
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    static function get($db, $passed, $all = false)
    {
        $container = getDefault($passed['container']);

        if (isset(self::$getTally[$container])) {
            return self::$getTally[$container];
        }

        list($params, $clauses) = self::getParamsClauseOfTally([
            'app' => $db,
            'passed' => $passed,
        ]);

        $sql = self::buildQueryGetTallyRows($clauses, $all);

        if (! $all) {
            array_push($params, cartons::STATUS_INACTIVE, cartons::STATUS_SHIPPED);
        }

        $results = $db->queryResults($sql, $params);

        return isset($passed['recNums']) ?
            self::setTallyRowContainer($results) : $results;
    }

    /*
    ****************************************************************************
    */

    static function checkRecLock($app, $recNums)
    {
        if (! $recNums) {
            return;
        }

        $recNumInput = is_array($recNums) ? $recNums : [$recNums];

        $qMarkString = $app->getQMarkString($recNumInput);

        $sql = 'SELECT    co.recNum,
                          IF (locked, "yes", "no") AS locked
                FROM      inventory_containers co
                LEFT JOIN tallies t ON t.recNum = co.recNum
                WHERE     co.recNum IN (' . $qMarkString . ')';

        if (is_array($recNums)) {
            $data = $app->queryResults($sql, $recNumInput);
            $result = self::setSelectParamsLock($data);
        } else {
            $data = $app->queryResult($sql, $recNumInput);
            $result = $data ? $data['locked'] : NULL;
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function getContainerLocations($app, $locations, $recNums)
    {

        if (isset(self::$selectParams['locations'])) {
            return;
        }

        if (! ($locations && $recNums)) {
            return FALSE;
        }

        $uniqueLocations = array_unique($locations);
        $qMarkUniqueLocations = $app->getQMarkString($uniqueLocations);
        $qMarkRecNums = $app->getQMarkString($recNums);

        $sql = 'SELECT CONCAT_WS("-", co.recNum, l.id) AS rowKey,
                       l.displayName,
                       recNum,
                       l.id
                FROM   locations l
                JOIN   warehouses w ON w.id = l.warehouseID
                JOIN   vendors v ON v.warehouseID = w.id
                JOIN   inventory_containers co ON co.vendorID = v.id
                WHERE  l.displayName IN (' . $qMarkUniqueLocations . ')
                AND    recNum IN (' . $qMarkRecNums . ')';

        $params = array_merge($uniqueLocations, $recNums);

        $results = $app->queryResults($sql, $params);

        self::setSelectParamsLocations($results);
    }

    /*
    ****************************************************************************
    */

    static function getStatuses($params)
    {
        if (isset(self::$selectParams['statuses'])) {
            return;
        }

        $app = $params['app'];

        $sql = 'SELECT shortName,
                       id
                FROM   statuses
                WHERE  shortName IN (?, ?)
                AND    category = ?';

        $results = $app->queryResults($sql, [
            cartons::STATUS_RACKED,
            cartons::STATUS_RECEIVED,
            self::CATEGORY_INVENTORY
        ]);

        self::$selectParams['statuses'] = $results;

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getUser($params)
    {
        $app = $params['app'];

        if (isset(self::$selectParams['userID'])) {
            return;
        }

        $users = new \tables\users($app);

        $username = \access::getUserInfoValue('username');
        $user = $users->lookUp($username);

        return self::$selectParams['userID'] = $user['id'];
    }

    /*
    ****************************************************************************
    */

    static function tallyToRows($params)
    {
        self::decodePostVars($params);

        $app = $params['app'];

        $rcLogForm = getDefault($params['rcLogForm']);
        $rowCount = $rcLogForm ? $app->post['rowCount'] : 0;

        $post = $app->post;

        if (! isset($post['upcs'])) {
            return FALSE;
        }

        $batchUpc = self::getBatchesUpcToStyle($post);

        $batches = $batchUpc['batches'];
        $upcToStyle = $batchUpc['upcToStyle'];

        $results = $rcLogForm ?
            self::parseRCLog($post, $rowCount, $batches) :
            self::parseInventory($post, $batches);

        $tallyInfo = $results['tallyInfo'];
        $requiredLocations = $results['locations'];
        $palletCount = $results['palletCount'];

        $tallyRows = self::getTallyInfoToRows($tallyInfo, $upcToStyle);

        return [
            'batches' => $batches,
            'tallyRows' => $tallyRows,
            'locations' => $requiredLocations,
            'palletCount' => $palletCount,
        ];
    }

    /*
    ****************************************************************************
    */

    function getCartonsPerRow()
    {
        if (! isset($this->rowCartons)) {
            return;
        }

        $tally = $this->tally;
        $rowCartons = $this->rowCartons;

        $cartonsPerRow = [];
        foreach ($tally as $row) {

            $this->processToGetCartonsPerRow([
                'plateCount' => $row['plateCount'],
                'cartonCount' => $row['cartonCount'],
                'rowCartons' => &$rowCartons,
                'cartonsPerRow' => &$cartonsPerRow,
                'upc' => $row['upc'],
            ]);
        }

        return $cartonsPerRow;
    }

    /*
    ****************************************************************************
    */

    function getRowIdByBatchAndPlate($batchID, $plate)
    {
        $sql = 'SELECT    rowID
                FROM      inventory_cartons ic
                JOIN      tally_cartons tc ON tc.invID = ic.id
                WHERE     ic.batchID = ?
                AND       ic.plate = ?
                LIMIT 1';

        $result = $this->mvc->queryResult($sql, [$batchID, $plate]);

        return $result ? $result['rowID'] : NULL;
    }

    /*
    ****************************************************************************
    */

    static function decodePostVars($params)
    {
        $app = $params['app'];

        // Had to json encode cartons and locations because they made too many
        // post vars
        $arrJson = ['cartons', 'locations'];

        foreach ($arrJson as $name) {

            $values = getDefault($app->post[$name]);

            if (is_string($values)) {
                $app->post[$name] = json_decode($app->post[$name], TRUE);
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function setNextPlateInfo($model)
    {
        self::$plateBatch = $model->getNextID('plate_batches');
        self::$firstPlate = self::$nextPlate =
            $model->getNextID('licensePlate');
    }

    /*
    ****************************************************************************
    */

    static function prepareForSubmit($params)
    {
        $app = $params['app'];
        $rcLogForm = $params['rcLogForm'];
        $recNums = $params['recNums'];
        $neededLocations = getDefault($params['neededLocations']);

        $nextPlate = NULL;

        $model = new \tables\_default($app);

        self::checkRecLock($app, $recNums);

        self::getContainerProducts($params);

        if ($rcLogForm) {
            // RC Log
            self::processPrepareForSubmitRCLog([
                'app' => $app,
                'myParams' => $params,
                'recNums' => $recNums,
                'model' => $model
            ]);
        } else {
            // Import Inventory
            $nextPlate = self::processPrepareForSubmitImportInventory([
                'app' => $app,
                'neededLocations' => $neededLocations,
                'recNums' => $recNums,
                'model' => $model
            ]);
        }

        $tallyUserID = self::getUser(['app' => $app]);

        self::getStatuses($params);

        logger::getFieldIDs('cartons', $app);

        logger::getLogID();

        self::setNextPlateInfo($model);

        return [
            'userID' => $tallyUserID,
            'nextPlate' => $nextPlate,
        ];
    }

    /*
    ****************************************************************************
    */

    static function submitRCLog($params)
    {
        $app = $params['app'];
        $importInventory = getDefault($params['importInventory']);

        $rcLogForm = getDefault($params['rcLogForm']);
        self::$checkProducts = getDefault($params['checkProducts'], TRUE);

        $post = $app->post;

        $isValidInput = self::validateRCLogData($post);

        if (! $isValidInput) {
            return self::$errorMsg;
        }

        $recNum = $post['recNum'];

        $isLocked = isset(self::$selectParams['locks'][$recNum]) ?
            self::$selectParams['locks'][$recNum] :
            self::checkRecLock($app, $recNum);

        if (self::LOCK_YES == strtoupper($isLocked)) {
            return 'This container has already been submitted';
        }

        $results = self::tallyToRows($params);

        $batches = $results['batches'];
        $locations = $results['locations'];
        $tallyRows = $results['tallyRows'];
        $palletCount = $results['palletCount'];

        // Get Locations
        self::getContainerLocations($app, $locations, [$recNum]);
        $locations = self::$selectParams['locations'][$recNum];

        // Create Tally Sheet
        $tallySheet = new tally([
            'mvc' => $app,
            'tally' => $tallyRows,
            'recNum' => $recNum,
            'container' => $post['container'],
        ]);

        $tallySheet->checkProducts(self::$nextCartonID);

        $rcLogForm ? FALSE : $tallySheet->createTally();

        $optimized = isset(self::$selectParams['tallyIDs']);

        $tallySheet->getTallyIDs([
            'app' => $app,
            'recNums' => $recNum,
        ]);

        $tallySheet->tallyID = self::$selectParams['tallyIDs'][$recNum]['id'];

        $tallySheet->unsetRows();

        $tallySheet->insertRows();

        $tallySheet->unsetCartons();

        $tallySheet->insertCartons();

        // Get the cartons per row to update the plates and locs later
        $cartonsPerRow = $tallySheet->getCartonsPerRow();

        // Create License Plates
        $foundPlates = self::getLicensePlateWhenSubmitRCLog([
            'app' => $app,
            'optimized' => $optimized,
            'palletCount' => $palletCount
        ]);

        self::getStatuses(['app' => $app]);
        $statuses = self::$selectParams['statuses'];

        $preData = self::getParamsClauseOfSubmitRCLog([
            'app' => $app,
            'recNum' => $recNum,
            'batches' => $batches,
            'foundPlates' => $foundPlates,
            'finalCartonsPerRow' => $cartonsPerRow,
            'locations' => $locations,
            'statuses' => $statuses,
            'importInventory' => $importInventory
        ]);

        $storeParams = $preData['storeParams'];
        $storeQueries = $preData['storeQueries'];
        $changingCartons = $preData['changingCartons'];
        $newLocIDs = $preData['newLocIDs'];
        $newPlates = $preData['newPlates'];
        $newStatusIDs = $preData['newStatusIDs'];

        self::getPreviousCartonStatusesLocsPlates([
            'app' => $app,
            'changingCartons' => $changingCartons,
            'newLocIDs' => $newLocIDs,
            'newPlates' => $newPlates,
            'newStatusIDs' => $newStatusIDs,
        ]);

        foreach ($storeQueries as $index => $sql) {
            $params = $storeParams[$index];
            $app->runQuery($sql, $params);
        }

        self::updateLockTalliesByRecNum($app, $recNum);

        \common\labelMaker::inserts([
            'pdo' => $app,
            'model' => new \tables\users($app),
            'userID' => self::$selectParams['userID'],
            'quantity' => self::$nextPlate - self::$firstPlate,
            'labelType' => 'plate',
            'firstBatchID' => self::$plateBatch,
            'makeTransaction' => FALSE,
        ]);

        \import\vendorData::unifyContainerPlates([
            'app' => $app,
            'recNums' => [$recNum],
            'containerLocs' => self::$selectParams['containerLocs'],
            'makeTransaction' => FALSE,
        ]);

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    static function parseRCLog($post, $rowCount, &$batches)
    {
        if (empty($post['locations'])) {
            return FALSE;
        }

        $palletCount = $page = $counter = 0;
        $locations = $tallyInfo = [];

        foreach ($post['locations'] as $index => $location) {
            // Changes values everytime you go up one page
            if ($counter == $rowCount * self::NUMBER_OF_COLUMN) {
                $counter = 0;
                $page++;
            }

            $offset = $counter++ % self::NUMBER_OF_COLUMN;
            $column = $offset + $page * self::NUMBER_OF_COLUMN;
            $cartons = getDefault($post['cartons'][$index]);

            if (! isset($batches[$column]['pallets'])
            || ! $location || ! $cartons) {
                continue;
            }

            $batchID = $batches[$column]['batch'];
            $upc = $batches[$column]['upc'];

            $tallyInfo[$batchID][$upc][$cartons][$location] =
                isset($tallyInfo[$batchID][$upc][$cartons][$location]) ?
                $tallyInfo[$batchID][$upc][$cartons][$location] + 1 : 1;

            $palletCount++;

            $locations[] = $location;

            $batches[$column]['pallets'][] = [
                'cartons' => $cartons,
                'location' => $location,
            ];
        }

        $results = [
            'tallyInfo' => $tallyInfo,
            'locations' => $locations,
            'palletCount' => $palletCount,
        ];

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function parseInventory($post, &$batches)
    {
        $locations = $tallyInfo = [];

        $originalBatch = $batches;

        foreach ($originalBatch as $id => $row) {
            $upc = $row['upc'];
            $batchID = $row['batch'];
            $cartons = $post['cartons'][$id][0];
            $location = strtoupper($post['locations'][$id][0]);

            $tallyInfo[$batchID][$upc][$cartons][$location] =
                isset($tallyInfo[$batchID][$upc][$cartons][$location])
                ? $tallyInfo[$batchID][$upc][$cartons][$location] + 1 : 1;

            $batches[$id]['pallets'][] = [
                'cartons' => $cartons,
                'location' => $location,
            ];
        }

        return [
            'tallyInfo' => $tallyInfo,
            'locations' => $locations,
            'palletCount' => count($batches),
        ];
    }

    /*
    ****************************************************************************
    */

    static function getCartonsByInfo($batch)
    {
        foreach (self::$selectParams['cartonsByInfo'] as $values) {

            if (! isset($values[$batch])) {
                continue;
            }

            foreach ($values[$batch] as $data) {
                foreach ($data as $cartons) {
                    foreach ($cartons as $carton) {
                        $return[] = $carton;
                    }
                }
            }
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    static function getCartonsByContainers($app, $recNums)
    {
        if (! $recNums) {
            return FALSE;
        }

        $qMarkString = $app->getQMarkString($recNums);

        $sql = 'SELECT    ca.id,
                          name,
                          u.upc,
                          u.sku,
                          locID,
                          mLocID,
                          plate,
                          batchID,
                          statusID,
                          mStatusID
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON ca.batchID = b.id
                JOIN      inventory_containers co ON co.recNum = b.recNum
                LEFT JOIN upcs u ON b.upcID = u.id
                WHERE     co.recNum IN (' . $qMarkString . ')
                AND NOT   ca.isSplit
                AND NOT   ca.unSplit
                ORDER BY  ca.id ASC';

        $results = $app->queryResults($sql, $recNums);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getContainerUPCs($container)
    {
        $sql = 'SELECT    u.upc,
                          u.sku,
                          COUNT(ca.id) AS cartonCount
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON ca.batchID = b.id
                JOIN      inventory_containers co ON co.recNum = b.recNum
                LEFT JOIN upcs u ON b.upcID = u.id
                WHERE     co.name = ?
                AND NOT   ca.isSplit
                AND NOT   ca.unSplit
                GROUP BY  upc';

        $results = $this->mvc->queryResults($sql, [$container]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getUpcsPassed($tally)
    {
        $results = [];

        if (! $tally) {
            return $results;
        }

        foreach ($tally as $row) {
            $upc = $row['upc'];

            $tmpUpc = isset($results[$upc]) ? $results[$upc] : 0;
            $tmpUpc += $row['cartonCount'] * $row['plateCount'];

            $results[$upc] = $tmpUpc;
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function setNextCartonIDRows($rowCartons, $arrNextCartonID)
    {
        $results = $arrNextCartonID;

        if (! $rowCartons) {
            return [];
        }

        for ($i = 0; $i < $rowCartons; $i++) {
            $results[self::$nextCartonID] = TRUE;
            self::$nextCartonID += 1;
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getCartonIDProducts($params)
    {
        $tallyRow = $params['tallyRow'];
        $cartonsByInfo = &$params['cartonsByInfo'];
        $rowCartons = $params['rowCartons'];
        $accounted = &$params['accounted'];

        $batch = $tallyRow['batch'];

        $accounted[$batch] = getDefault($accounted[$batch], 0);
        $accounted[$batch] += $rowCartons;

        $upc = $tallyRow['upc'];
        $sku = $tallyRow['style'];
        $container = $this->container;

        $batchCartons = &$cartonsByInfo[$container][$batch][$sku][$upc];

        $results = array_splice($batchCartons, 0, $rowCartons);

        return $results;

    }

    /*
    ****************************************************************************
    */

    function buildQueryInsertTallyRows()
    {
        $sql = 'INSERT INTO tally_rows (
                    tallyID,
                    rowNum,
                    plateCount,
                    cartonCount,
                    active
                ) VALUE (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    plateCount = VALUES(plateCount),
                    cartonCount = VALUES(cartonCount),
                    active = 1';
        return $sql;
    }

    /*
    ****************************************************************************
    */

    function insertListTallyRows($tallyID)
    {
        $sql = $this->buildQueryInsertTallyRows();

          foreach ($this->tally as $row) {

            $this->mvc->runQuery($sql, [
                $tallyID,
                $row['rowNum'],
                $row['plateCount'],
                $row['cartonCount'],
                self::ACTIVE
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    static function getRecNumFromTallyRows($app, $recNums)
    {
        if (! $recNums) {
            return [];
        }

        $qMarkString = $app->getQMarkString($recNums);

        $sql = 'SELECT    r.id,
                          recNum
                FROM      tally_rows r
                LEFT JOIN tallies t ON t.id = r.tallyID
                WHERE     recNum IN (' . $qMarkString . ')
                ORDER BY  r.id ASC';

        $results = $app->queryResults($sql, $recNums);

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getParamsClauseOfTally($params)
    {
        $app = $params['app'];
        $passed = $params['passed'];
        $clauses = $sqlParams = [];

        if (isset($passed['batches'])) {
            $qMarkBatches = $app->getQMarkString($passed['batches']);
            $clauses[] = 'AND b.id IN (' . $qMarkBatches . ')';
            $sqlParams = $passed['batches'];
        }

        if (isset($passed['recNums'])) {
            $qMarkRecNums = $app->getQMarkString($passed['recNums']);
            $clauses[] = 'AND b.recNum IN (' . $qMarkRecNums . ')';
            $sqlParams = $passed['recNums'];
        }

        if (isset($passed['container'])) {
            $clauses[] = 'AND name = ?';
            $sqlParams[] = $passed['container'];
        }

        if (isset($passed['recNum'])) {
            $clauses[] = 'AND co.recNum = ?';
            $sqlParams[] = $passed['recNum'];
        }

        return [$sqlParams, $clauses];
    }

    /*
    ****************************************************************************
    */

    static function buildQueryGetTallyRows($clauses, $all = false)
    {
        if (! $clauses) {
            return;
        }

        $clauseString = implode(' ', $clauses);

        $where = '1
                ' . $clauseString . '
                AND       (
                    trc.active
                    OR trs.active
                )
                AND       (
                    tcc.active
                    OR tcs.active
                )
                AND       NOT ca.isSplit
                AND       NOT ca.unSplit
                ';

        if (! $all) {
            $where .= '
                AND       s.shortName NOT IN (?, ?)
            ';
        }

        $sql = 'SELECT    CONCAT_WS(
                            "-",
                            IF(tcc.rowID, tcc.rowID, tcs.rowID),
                            ca.locID
                          ) AS rowID,
                          u.sku AS style,
                          upc,
                          IF(
                            trc.plateCount,
                            trc.plateCount,
                            trs.plateCount
                          ) AS plateCount,
                          COUNT(
                            IF(
                                tcc.rowID,
                                tcc.rowID,
                                tcs.rowID
                            )
                          ) AS cartonCount,
                          co.name AS container
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON ca.batchID = b.id
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      upcs u ON u.id = b.upcID
                JOIN      statuses s ON s.id = ca.statusID
                LEFT JOIN inventory_splits sp ON sp.childID = ca.id
                LEFT JOIN inventory_cartons ic ON ic.id = sp.childID
                LEFT JOIN tally_cartons tcc ON tcc.invID = ca.id
                LEFT JOIN tally_rows trc ON tcc.rowID = trc.id
                LEFT JOIN tally_cartons tcs ON tcs.invID = sp.ParentID
                LEFT JOIN tally_rows trs ON tcs.rowID = trs.id
                WHERE    ' . $where . '
                GROUP BY  rowID, ca.locID
                ORDER BY  b.id ASC, ca.locID, ca.id ASC';

        return $sql;
    }

    /*
    ****************************************************************************
    */

    static function setTallyRowContainer($containers)
    {
        if (! $containers) {
            return FALSE;
        }

        foreach ($containers as $rowID => $row) {
            $container = $row['container'];
            self::$getTally[$container][$rowID] = $row;
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    static function setSelectParamsLock($recNumsLocked)
    {
        if (! $recNumsLocked) {
            return FALSE;
        }

        foreach ($recNumsLocked as $recNum => $row) {
            self::$selectParams['locks'][$recNum] = $row['locked'];
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    static function setSelectParamsLocations($locationRecNums)
    {
        if (! $locationRecNums) {
            return FALSE;
        }

        foreach ($locationRecNums as $displayName => $row) {

            $displayName = $row['displayName'];
            $recNum = $row['recNum'];

            self::$selectParams['locations'][$recNum][$displayName]['id'] =
                $row['id'];
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    static function getBatchesUpcToStyle($post)
    {
        $batches = $upcToStyle = [];

        foreach ($post['upcs'] as $index => $upc) {
            if (! $upc) {
                continue;
            }

            $batches[] = [
                'upc' => $upc,
                'batch' => $post['batches'][$index],
                'pallets' => [],
            ];

            $upcToStyle[$upc] = $post['styles'][$index];

        }

        $result = [
            'batches' => $batches,
            'upcToStyle' => $upcToStyle
        ];

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function getTallyInfoToRows($tallyInfo, $upcToStyle)
    {
        $results = [];

        if (! $tallyInfo) {
            return $results;
        }

        $rowNum = 0;

        foreach ($tallyInfo as $batchID => $upcs) {
            foreach ($upcs as $upc => $row) {
                self::getTallyInfoToRowsOfUpcCartons([
                    'results' => &$results,
                    'rowNum' => &$rowNum,
                    'upc' => $upc,
                    'infoUpcs' => $row,
                    'batch' => $batchID,
                    'style' => $upcToStyle[$upc],
                ]);
            }
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getTallyInfoToRowsOfUpcCartons($params)
    {
        $results = &$params['results'];
        $rowNum = &$params['rowNum'];
        $upc = $params['upc'];
        $infoUpcs = $params['infoUpcs'];
        $batchID = $params['batch'];
        $style = $params['style'];

        foreach ($infoUpcs as $cartons => $locations) {
            foreach ($locations as $pallets) {
                $results[] = [
                    'rowNum' => $rowNum++,
                    'upc' => $upc,
                    'batch' => $batchID,
                    'plateCount' => $pallets,
                    'cartonCount' => $cartons,
                    'style' => $style,
                ];
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function processPrepareForSubmitRCLog($params)
    {
        $app = $params['app'];
        $myParams = $params['myParams'];
        $recNums = $params['recNums'];
        $model = $params['model'];

        $rows = self::tallyToRows($myParams);

        self::getContainerLocations($app, $rows['locations'], $recNums);

        self::getTallyIDs($myParams);

        self::$selectParams['firstRowID'] = $model->getNextID('tally_rows');

        self::getPreviousRowIDs($myParams);
    }

    /*
    ****************************************************************************
    */

    static function processPrepareForSubmitImportInventory($params)
    {
        $app = $params['app'];
        $neededLocations = $params['neededLocations'];
        $recNums = $params['recNums'];
        $model = $params['model'];

        self::getContainerLocations($app, $neededLocations, $recNums);

        $nextTallyID = $model->getNextID('tallies');

        self::projectTallyIDs($recNums, $nextTallyID);

        $nextTallyRowID = $model->getNextID('tally_rows');
        self::storeNextTallyRowID($nextTallyRowID);

        $nextPlate = self::$nextPlate = $model->getNextID('licensePlate');

        return $nextPlate;
    }

    /*
    ****************************************************************************
    */

    static function getLicensePlateWhenSubmitRCLog($params)
    {
        $app = $params['app'];
        $optimized = $params['optimized'];
        $palletCount = $params['palletCount'];

        $results = [];

        self::getUser(['app' => $app]);
        $userID = self::$selectParams['userID'];

        if ($optimized) {
            for ($i = 0; $i < $palletCount; $i++) {
                $results[] = self::$nextPlate++;
            }
        } else {
            $plates = new \tables\plates($app);

            $plateCount = $plates->insert($userID, $palletCount);

            $data = self::getLicensePlateWithLimit($app, $plateCount);

            $results = array_keys($data);
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getLicensePlateWithLimit($app, $limit)
    {
        if ($limit < 1){
            return [];
        }

        $sql = 'SELECT   id
                FROM     licensePlate
                ORDER BY id DESC
                LIMIT    ' . $limit;

        $results = $app->queryResults($sql);

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getQueryUpdateInventoryLocationsAndLicense($params)
    {
        $app = $params['app'];
        $nextCartons = $params['nextCartons'];
        $clauses = $params['clauses'];
        $statusID = $params['statusID'];

        $qMarkCartons = $app->getQMarkString($nextCartons);
        $clauseString = implode(',', $clauses);

        $sql = 'UPDATE  inventory_cartons ca
                JOIN    inventory_batches b ON b.id = ca.batchID
                JOIN    inventory_containers co ON co.recNum = b.recNum
                SET     ' . $clauseString . ',
                        ca.statusID = ' . $statusID . ',
                        ca.mStatusID = ' . $statusID . '
                WHERE   co.recNum = ?
                AND     ca.id IN (' . $qMarkCartons . ')';

        return $sql;
    }

    /*
    ****************************************************************************
    */

    static function updateLockTalliesByRecNum($app, $recNum)
    {
        if (! $recNum) {
            return FALSE;
        }

        $sql = 'UPDATE tallies
                SET    locked = 1,
                       logTime = NOW()
                WHERE  recNum = ?';

        $result = $app->runQuery($sql, [$recNum]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function getParamsClauseOfSubmitRCLog($params)
    {
        $app = $params['app'];
        $batches = $params['batches'];
        $foundPlates = $params['foundPlates'];
        $finalCartonsPerRow = $params['finalCartonsPerRow'];
        $locations = $params['locations'];
        $statuses = $params['statuses'];
        $importInventory = $params['importInventory'];
        $recNum = $params['recNum'];

        $storeParams = $storeQueries = $newLocIDs = $newPlates =
            $newStatusIDs = $changingCartons = $lookupCartons = [];


        foreach ($batches as $row) {

            if (! isset($row['pallets'])) {
                continue;
            }

            $upc = $row['upc'];
            $batch = $row['batch'];

            foreach ($row['pallets'] as $info) {

                $nextPlate = array_shift($foundPlates);

                $cartonCount = $info['cartons'];

                $nextCartons = $importInventory ?
                        self::getCartonsByInfo($batch) :
                        array_shift($finalCartonsPerRow[$upc][$cartonCount]);

                if (! $nextCartons) {
                    continue;
                }

                $params = [$nextPlate, $recNum];

                $nNextCartons = count($nextCartons);

                $tmpNewPlates = array_fill(0, $nNextCartons, $nextPlate);
                $newPlates = array_merge($newPlates, $tmpNewPlates);

                $clauses = ['ca.plate = ?'];

                $loc = $info['location'];

                $locID = getDefault($locations[$loc]['id']);

                if ($locID) {
                    array_unshift($params, $locID, $locID);
                    array_unshift($clauses, 'ca.locID = ?', 'ca.mLocID = ?');

                    $tmpNewLocIDs = array_fill(0, $nNextCartons, $locID);
                    $newLocIDs = array_merge($newLocIDs, $tmpNewLocIDs);
                }

                $shortStatus = $locID ?
                        cartons::STATUS_RACKED : cartons::STATUS_RECEIVED;

                $statusID = $statuses[$shortStatus]['id'];

                $tmpNewStatusIDs = array_fill(0, $nNextCartons, $statusID);
                $newStatusIDs = array_merge($newStatusIDs, $tmpNewStatusIDs);

                // Update Inventory Locations and License Plates
                $storeQueries[] =
                        self::getQueryUpdateInventoryLocationsAndLicense([
                            'app' => $app,
                            'nextCartons' => $nextCartons,
                            'clauses' => $clauses,
                            'statusID' => $statusID
                        ]);

                // Add info to containerLocs params so it will not have to be
                // queried in unifyContainerPlates
                $containerLocs = &self::$selectParams['containerLocs'][$recNum];

                $containerLocs[$locID][$nextPlate] = [];

                $containerLocs[$locID][$nextPlate] = $nextCartons;

                $lookupCartons[] = $nextCartons;

                $changingCartons = array_merge($changingCartons, $nextCartons);

                $storeParams[] = array_merge($params, $nextCartons);
            }
        }

        $result = [
            'storeParams' => $storeParams,
            'storeQueries' => $storeQueries,
            'lookupCartons' => $lookupCartons,
            'changingCartons' => $changingCartons,
            'newLocIDs' => $newLocIDs,
            'newPlates' => $newPlates,
            'newStatusIDs' => $newStatusIDs,
        ];

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function getPreviousCartonStatusesLocsPlates($params)
    {
        $app = $params['app'];
        $changingCartons = $params['changingCartons'];
        $newLocIDs = $params['newLocIDs'];
        $newPlates = $params['newPlates'];
        $newStatusIDs = $params['newStatusIDs'];

        // Get the previous carton statuses, locs and plates for logs
        if (! $changingCartons) {
            return;
        }

        $oldLocIDs = $oldMlocIDs = $oldStatusIDs = $oldMstatusIDs =
                $oldPlates =  [];

        foreach ($changingCartons as $invID) {
            $oldLocIDs[] =
                self::$selectParams['containerCartons'][$invID]['locID'];
            $oldMlocIDs[] =
                self::$selectParams['containerCartons'][$invID]['mLocID'];
            $oldPlates[] =
                self::$selectParams['containerCartons'][$invID]['plate'];
            $oldStatusIDs[] =
                self::$selectParams['containerCartons'][$invID]['statusID'];
            $oldMstatusIDs[] =
                self::$selectParams['containerCartons'][$invID]['mStatusID'];
        }

        logger::edit([
            'db' => $app,
            'primeKeys' => $changingCartons,
            'fields' => [
                'locID' => [
                    'fromValues' => $oldLocIDs,
                    'toValues' => $newLocIDs,
                ],
                'mLocID' => [
                    'fromValues' => $oldMlocIDs,
                    'toValues' => $newLocIDs,
                ],
                'plate' => [
                    'fromValues' => $oldPlates,
                    'toValues' => $newPlates,
                ],
                'statusID' => [
                    'fromValues' => $oldStatusIDs,
                    'toValues' => $newStatusIDs,
                ],
                'mStatusID' => [
                    'fromValues' => $oldMstatusIDs,
                    'toValues' => $newStatusIDs,
                ],
            ],
            'transaction' => FALSE,
        ]);

        return self::$selectParams;
    }

    /*
    ****************************************************************************
    */

    function setCartonForRowCartonID($id, $cartons)
    {
        if (! $cartons) {
            return;
        }

        foreach ($cartons as $cartonID) {
            $this->rowCartons[$id][] = $cartonID;
        }

        return $this->rowCartons;
    }

    /*
    ****************************************************************************
    */

    function processToGetCartonsPerRow($params)
    {
        $plateCount = $params['plateCount'];
        $firstCartonCount = $params['cartonCount'];
        $rowCartons = &$params['rowCartons'];
        $cartonsPerRow = &$params['cartonsPerRow'];
        $upc = $params['upc'];

        for ($i = 0; $i < $plateCount; $i++) {
            $cartonCount = $firstCartonCount;
            $nextCartons = array_shift($rowCartons);

            // If the cartons counts are greater than the required, splice
            if (count($nextCartons) > $cartonCount) {
                $cartonsPerRow[$upc][$cartonCount][] =
                    array_splice($nextCartons, $cartonCount);
                if ($nextCartons) {
                    array_unshift($rowCartons, $nextCartons);
                }
            } else {
                $cartonsPerRow[$upc][$cartonCount][] = $nextCartons;
            }
        }

    }

    /*
    ****************************************************************************
    */

    static function validateRCLogData($post)
    {
        if (! $post['recNum']) {
            self::$errorMsg = 'RecNum is not NULL!';
            return FALSE;
        }

        if (! $post['locations']) {
            self::$errorMsg = 'Locations is not NULL!';
            return FALSE;
        }

        if (! $post['upcs']) {
            self::$errorMsg = 'Upcs is not NULL!';
            return FALSE;
        }

        if (! $post['container']) {
            self::$errorMsg = 'Container is not NULL!';
            return FALSE;
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    static function updateTallyPrinted($app, $recNum)
    {
        if (! $recNum) {
            return FALSE;
        }

        $sql = 'UPDATE    inventory_containers co
               LEFT JOIN tallies t ON t.recNum = co.recNum
               SET       rcLabelPrinted = 1, rcLogPrinted = 1
               WHERE     co.recNum = ?';

        $result = $app->runQuery($sql, [$recNum]);

        return $result;
    }

    /*
    ****************************************************************************
    */

}
