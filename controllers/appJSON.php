<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use common\pdf;
use common\tally;
use common\labor;
use common\logger;
use tables\plates;
use common\scanner;
use models\directories;
use import\vendorData;
use common\seldatContainers;
use tables\consolidation\waves;
use tables\inventory\scc\items as sccItems;

class controller extends template
{

    /*
    ****************************************************************************
    */
    const UPC_ACTIVE = 1;
    const UPC_INACTIVE = 0;
    const MEZZANINE_TRANSFER_STATUS = 'METR';

    function seldatUPCAppJSONController()
    {
        $request = getDefault($this->post['request']);

        if ($request != 'newUPC') {
            return $this->results = FALSE;
        }

        $upcs = new tables\upcs($this);

        $this->results = $upcs->seldatUPC();
    }

    /*
    ****************************************************************************
    */

    function autoSaveContainerAppJSONController()
    {
        if (isset($this->post['clearAutoSave'])) {
            unset($_SESSION['autoSaveContainer']);

            return $this->results = TRUE;
        }

        if (!isset($this->post['autoSaveContainer'])) {
            return $this->results = FALSE;
        }

        $_SESSION['autoSaveContainer'] = $this->post['autoSaveContainer'];

        return $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function createPlatesAppJSONController()
    {
        $payload = $this->post['payload'];

        $this->results = vendorData::createPlates([
            'app'       => $this,
            'recNums'   => $payload['recNums'],
            'client'    => $payload['client'],
            'warehouse' => $payload['warehouse'],
        ]);
    }

    /*
    ****************************************************************************
    */

    function createRCLabelsAppJSONController()
    {
        $payload = $this->post['payload'];

        $this->results = vendorData::createRCLabels([
            'app'       => $this,
            'recNums'   => $payload['recNums'],
            'client'    => $payload['client'],
            'warehouse' => $payload['warehouse'],
        ]);
    }

    /*
    ****************************************************************************
    */

    function createUCCLabelsAppJSONController()
    {
        $batches = json_decode($this->post['batches']);

        $sql = 'SELECT   warehouseID
                FROM     inventory_batches b
                JOIN     inventory_containers c ON c.recNum = b.recNum
                JOIN     vendors v ON v.id = c.vendorID
                WHERE    b.id IN (' . $this->getQMarkString($batches) . ')
                GROUP BY warehouseID';

        $results = $this->queryResults($sql, $batches);

        $warehouseArray = array_keys($results);

        if (count($warehouseArray) != 1) {
            die('Batches are from more than one warehouse');
        }

        $sql = 'SELECT id,
                       shortName
                FROM   warehouses';

        $warehouses = $this->queryResults($sql);

        $warehouseID = reset($warehouseArray);

        $firstBatch = reset($batches);
        $lastBatch = end($batches);

        $warehouse = $warehouses[$warehouseID]['shortName'];

        $labelDir = vendorData::getLableDirPath('UCCLabels', $warehouse);

        $fileName = $labelDir . '/Batch_' . $firstBatch . '_To_' . $lastBatch
            . '_UCC_Labels.pdf';

        labels\create::byBatches([
            'app'         => $this,
            'batches'     => $batches,
            'download'    => TRUE,
            'save'        => $fileName,
            'warehouseID' => $warehouseID,
            'isDownload'  => TRUE,
        ]);

        $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function updateBatchDimensionAppJSONController()
    {
        $value = getDefault($this->post['value']);
        $target = getDefault($this->post['target']);
        $batchID = getDefault($this->post['batchID']);

        $value && $target && $batchID or die('Missing Update Parameter');

        is_numeric($value) or die('Value Submitted Must Be Numeric');

        $dimensions = ['width', 'length', 'height'];

        in_array($target, $dimensions) or die('Invalid Dimension');

        $sql = 'SELECT  length,
                        width,
                        height,
                        m.displayName AS measurementSystem
                FROM    inventory_containers co
                JOIN    inventory_batches b ON b.recNum = co.recNum
                JOIN    measurement_systems m ON m.id = co.measureID
                WHERE   b.id = ?';

        $result = $this->queryResult($sql, [$batchID], $ajax = TRUE);

        $cartons = new tables\inventory\cartons($this);

        $error = NULL;

        switch ($target) {
            case 'length':
                $error = $cartons->checkCellValue([
                    'cellName'  => 'LENGTH',
                    'cellValue' => $value,
                    'decimals'  => 2,
                    'minWidth'  => 1,
                    'maxWidth'  => 2,
                ]);

                $error .= $cartons->checkCubicValue([
                    'rowError' => $error,
                    'height'   => $result['height'],
                    'width'    => $result['width'],
                    'length'   => $value,
                ]);

                break;
            case 'width':
                $error = $cartons->checkCellValue([
                    'cellName'  => 'WIDTH',
                    'cellValue' => $value,
                    'decimals'  => 2,
                    'minWidth'  => 1,
                    'maxWidth'  => 2,
                ]);

                $error .= $cartons->checkCubicValue([
                    'rowError' => $error,
                    'height'   => $result['height'],
                    'width'    => $value,
                    'length'   => $result['length'],
                ]);

                break;
            case 'height':
                $error = $cartons->checkCellValue([
                    'cellName'  => 'HEIGHT',
                    'cellValue' => $value,
                    'decimals'  => 2,
                    'minWidth'  => 1,
                    'maxWidth'  => 2,
                ]);

                $error .= $cartons->checkCubicValue([
                    'rowError' => $error,
                    'height'   => $value,
                    'width'    => $result['width'],
                    'length'   => $result['length'],
                ]);

                break;
        }

        if ($error) {
            die($error);
        }

        $sql = 'UPDATE inventory_batches
                SET    ' . $target . ' = ?
                WHERE  id = ?';

        $results = $this->runQuery($sql, [$value, $batchID], $ajax = TRUE);

        $queryFailed = is_string($results);

        if ($queryFailed) {
            die($queryFailed);
        } else {
            $this->results = $value;
        }
    }

    /*
    ****************************************************************************
    */

    function consolidationMoveAppJSONController()
    {
        $movements = waves::ajaxWave($this);

        $users = new tables\users($this);

        $username = access::getUserInfoValue('username');
        $userInfo = $users->lookUp($username);

        $userID = $userInfo['id'];

        $isWaveTwo = $this->post['wave'] == 'two';

        $plates = new plates($this);

        $locations = $cartonLookups = $fromTos = $newLocs = $searchUPCs = [];

        foreach ($movements as $upc => $moves) {

            $lookupClause = [];

            foreach ($moves as $row) {

                // Wave two reports will have the UPCs in the move data
                if ($isWaveTwo) {
                    $upc = $row['upc'];
                }

                $searchUPCs[$upc] = TRUE;

                $toLoc = $row['to'];
                $fromLoc = $row['from'];

                // Keep track of each locations destination
                $newLocs[$toLoc] = TRUE;

                // By old loc as well
                $fromTos[$fromLoc] = $toLoc;

                $locations[$toLoc] = TRUE;
                $locations[$fromLoc] = TRUE;

                // These clauses and params will be used to look up carton IDs
                // of the cartons being moved
                $cartonLookups['params'][] = $upc;
                $cartonLookups['params'][] = $fromLoc;
                $cartonLookups['clauses'][] = 'upc = ? AND l.displayName = ?';
            }
        }

        $cartonLookups['clauses'] = isset($cartonLookups['clauses']) ?
            $cartonLookups['clauses'] : [1];

        $locationIDs = [];
        if ($locations) {
            $sql = 'SELECT displayName,
                           id
                    FROM   locations
                    WHERE  displayName IN (' . $this->getQMarkString($locations) . ')';

            $locationIDs = $this->queryResults($sql, array_keys($locations));
        }

        $upcIDs = [];
        if ($searchUPCs) {
            $sql = 'SELECT upc,
                           id
                    FROM   upcs
                    WHERE  upc IN (' . $this->getQMarkString($searchUPCs) . ')';

            $upcIDs = $this->queryResults($sql, array_keys($searchUPCs));
        }

        $cartonIDs = [];
        if (isset($cartonLookups['params'])) {
            $sql = 'SELECT    ca.id,
                              upc,
                              l.displayName AS prevLoc
                    FROM      inventory_containers co
                    LEFT JOIN inventory_batches b ON co.recNum = b.recNum
                    LEFT JOIN inventory_cartons ca ON ca.batchID = b.id
                    LEFT JOIN upcs u ON b.upcID = u.id
                    LEFT JOIN locations l ON l.id = ca.locID
                    LEFT JOIN statuses s ON s.id = ca.statusID
                    WHERE     NOT isSplit
                    AND       NOT unSplit
                    AND       s.shortName = "RK"
                    AND       (' . implode(' OR ', $cartonLookups['clauses']) . ')';

            $cartonIDs = $this->queryResults($sql, $cartonLookups['params']);
        }

        $sql = 'INSERT INTO consolidations (userID) VALUES (?)';

        $this->runQuery($sql, [$userID]);

        $conID = $this->lastInsertID();

        // Get the values for the logs before the transaction starts

        $logValues = waves::iterateMoves([
            'db'          => $this,
            'upcIDs'      => $upcIDs,
            'action'      => 'getLogParams',
            'clientID'    => $this->post['clientID'],
            'movements'   => $movements,
            'isWaveTwo'   => $isWaveTwo,
            'locationIDs' => $locationIDs,
        ]);

        $logNewLocs = $logValues['newLocs'];
        $logCount = $logValues['logCount'];
        $logParams = $logValues['logParams'];

        $cartonsClause = 'locID = ?
                AND       upcID = ?
                AND       vendorID = ?
                AND       NOT ca.isSplit
                AND       NOT ca.unSplit
                AND       s.shortName = "RK"';

        // Make a cartons clause for each location
        $cartonsClauses = array_fill(0, $logCount, $cartonsClause);

        $cartonsQueryTable = 'inventory_cartons ca
                      JOIN    inventory_batches b ON b.id = ca.batchID
                      JOIN    inventory_containers co ON co.recNum = b.recNum
                      JOIN    statuses s ON s.id = ca.statusID';

        // Log the changes to cartons locations
        $sql = 'SELECT  ca.id,
                        locID,
                        mLocID,
                        upcID,
                        vendorID
                FROM    ' . $cartonsQueryTable . '
                WHERE   ' . implode(' OR ', $cartonsClauses);

        $movingCartons = $this->queryResults($sql, $logParams);

        // Create the consolidation log

        $sql = 'INSERT INTO consolidation_waves (
                    consolidationID,
                    cartonID,
                    prevLocID,
                    newLocID
                ) VALUES (?, ?, ?, ?)';

        // Get the carton logger field keys before the transaction is started
        logger::getFieldIDs('cartons', $this);

        logger::getLogID();

        $this->beginTransaction();

        foreach ($cartonIDs as $cartonID => $row) {
            $prevLoc = $row['prevLoc'];
            $newLoc = $fromTos[$prevLoc];

            $this->runQuery($sql, [
                $conID,
                $cartonID,
                $locationIDs[$prevLoc]['id'],
                $locationIDs[$newLoc]['id'],
            ]);
        }

        // Move all the cartons to their new locations

        $sql = 'UPDATE    ' . $cartonsQueryTable . '
                SET       locID = ?,
                          mLocID = ?
                WHERE     ' . $cartonsClause;

        waves::iterateMoves([
            'db'          => $this,
            'sql'         => $sql,
            'upcIDs'      => $upcIDs,
            'action'      => 'moveCartons',
            'clientID'    => $this->post['clientID'],
            'movements'   => $movements,
            'isWaveTwo'   => $isWaveTwo,
            'locationIDs' => $locationIDs,
        ]);

        $invIDs = $fromLocs = $toLocs = [];

        foreach ($movingCartons as $invID => $rows) {
            $invIDs[] = $invID;
            $upcID = $rows['upcID'];
            $fromLocs[] = $fromLocID = $rows['locID'];
            $fromMLocs[] = $rows['mLocID'];
            $clientID = $rows['vendorID'];
            $toLocs[] = $logNewLocs[$fromLocID][$clientID][$upcID];
        }

        logger::edit([
            'db' => $this,
            'primeKeys'   => $invIDs,
            'fields'      => [
                'locID'  => [
                    'fromValues' => $fromLocs,
                    'toValues'   => $toLocs,
                ],
                'mLocID' => [
                    'fromValues' => $fromMLocs,
                    'toValues'   => $toLocs,
                ],
            ],
            'transaction' => FALSE,
        ]);

        $this->commit();

        // Log the cartons moving to the license plates

        $platesNeeded = count($newLocs);

        $newLocCartonsClause = 'l.displayName = ?
                      AND       NOT isSplit
                      AND       NOT unSplit
                      AND       s.shortName = "RK"';

        $newLocsClauses = array_fill(0, $platesNeeded, $newLocCartonsClause);

        // Get the cartons and their previous license plates

        $newLocsQueryTable = 'inventory_cartons c
                    LEFT JOIN locations l ON l.id = c.locID
                    LEFT JOIN statuses s ON s.id = c.statusID';

        $sql = 'SELECT c.id,
                       plate,
                       l.displayName AS location
                FROM   ' . $newLocsQueryTable . '
                WHERE  ' . implode(' OR ', $newLocsClauses);

        $newLocsValues = array_keys($newLocs);

        $results = $this->queryResults($sql, $newLocsValues);

        $oldPlates = [];
        foreach ($results as $invID => $row) {
            $location = $row['location'];
            $oldPlates[$location][$invID] = $row['plate'];
        }

        // Move all cartons in final locations to their new license plates

        $sql = 'UPDATE    ' . $newLocsQueryTable . '
                SET       c.plate = ?
                WHERE     ' . $newLocCartonsClause;

        // Create a new plate for each final location

        $nextPlate = $plates->getNextID('licensePlate');

        $plates->insert($userID, $platesNeeded);

        $this->beginTransaction();

        $logCartons = $logNewPlates = $logOldPlates = [];

        foreach ($newLocsValues as $location) {
            $this->runQuery($sql, [
                $nextPlate,
                $location,
            ]);

            foreach ($oldPlates[$location] as $invID => $oldPlate) {
                $logCartons[] = $invID;
                $logOldPlates[] = $oldPlate;
                $logNewPlates[] = $nextPlate;
            }

            $nextPlate++;
        }

        logger::edit([
            'db' => $this,
            'primeKeys'   => $logCartons,
            'fields'      => [
                'plate' => [
                    'fromValues' => $logOldPlates,
                    'toValues'   => $logNewPlates,
                ],
            ],
            'transaction' => FALSE,
        ]);

        $this->commit();

        $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function getUPCMovementsAppJSONController()
    {
        $report = waves::ajaxWave($this);

        $waveNumber = isset($report['all']) ? 'two' : 'one';

        $this->results = [
            'wave'   => $waveNumber,
            'report' => $report,
        ];
    }

    /*
    ****************************************************************************
    */

    function clientConsolidateAppJSONController()
    {
        $clientID = $this->get['clientID'];

        $this->results = ['report' => FALSE];

        $waves = new waves($this);

        $waveOne = $waves->getReport([
            'clientID' => $clientID,
        ]);

        if ($waveOne) {
            $report = [];
            foreach ($waveOne as $upc => $moves) {
                $tmpUPC = $moves ? $moves[0] : $moves;
                $tmpUPC['quantity'] = count($moves);
                $tmpUPC['upc'] = $upc;
                $report[] = $tmpUPC;
            }

            return $this->results = [
                'wave'   => 'one',
                'report' => $report,
            ];
        }

        // Get wave two report
        $waveTwo = $waves->getReport([
            'clientID' => $clientID,
            'setWave'  => 'two',
        ]);

        if ($waveTwo) {
            $this->results = [
                'wave'   => 'two',
                'report' => $waveTwo,
            ];
        }
    }

    /*
    ****************************************************************************
    */

    function reformatParam($params, $index = '')
    {

        if (!$index) {
            return FALSE;
        }

        $data = array_keys($params);
        $arrayCheckedRow = explode(',', $index);
        array_pop($arrayCheckedRow);

        foreach ($data as $index => $value) {
            if (!in_array($index, $arrayCheckedRow)) {
                unset($data[$index]);
            }
        }

        return array_values($data);

    }

    /*
    ****************************************************************************
    */

    function splitCartonAppJSONController()
    {
        $ucc = $this->post['ucc'];
        $uom = $this->post['uom'];
        $piecesCount = $this->post['newUOM'];

        // Recalculate the values
        $cartonCount = floor($uom / $piecesCount);
        $remainderPieces = $uom % $piecesCount;

        // Get the array of UOMs
        $uoms = array_fill(0, $cartonCount, $piecesCount);

        if ($remainderPieces) {
            array_push($uoms, $remainderPieces);
        }

        $cartons = new tables\inventory\cartons($this);

        $this->results = $cartons->split([
            $ucc => $uoms,
        ]);
    }

    /*
    ****************************************************************************
    */

    function addPalletSheetsAppJSONController()
    {
        $userDB = $this->getDBName('users');

        $vendorID = getDefault($this->post['vendorID']);

        $palletNumber = getDefault($this->post['palletNumber']);

        if (!$vendorID || !$palletNumber) {
            return $this->results = FALSE;
        }

        $sql = 'INSERT INTO pallet_sheets (
                    vendorID,
                    userID
                ) VALUES (?, (
                    SELECT id
                    FROM   ' . $userDB . '.info
                    WHERE  username = ?
                    LIMIT  1
                ))';

        $username = access::getUserInfoValue('username');
        $this->runQuery($sql, [$vendorID, $username]);

        $palletSheetID = $this->lastInsertID();

        $sql = 'INSERT INTO pallet_sheet_batches (palletSheetID) VALUES (?)';

        $this->beginTransaction();

        for ($counter = 0; $counter < $palletNumber; $counter++) {
            $this->runQuery($sql, [$palletSheetID]);
        }

        $lastPalletSheet = $this->lastInsertID();

        $this->commit();

        $firstPalletSheet = $lastPalletSheet - $palletNumber + 1;

        $this->results = range($firstPalletSheet, $lastPalletSheet);
    }

    /*
    ****************************************************************************
    */

    function getContainerNamesAppJSONController()
    {
        $container = getDefault($this->get['term']);

        if (!$container) {
            return $this->results = FALSE;
        }

        $sql = 'SELECT    name
                FROM      inventory_containers
                WHERE     name LIKE ?
                GROUP BY  name
                LIMIT     10';

        $results = $this->queryResults($sql, [$container . '%']);

        $labels = [];
        foreach (array_keys($results) as $row) {
            $labels[] = [
                'value' => $row,
            ];
        }

        $this->results = $labels;
    }

    /*
    ****************************************************************************
    */

    function getLocationNamesAppJSONController()
    {
        $locations = new tables\locations($this);

        $this->results = $locations->getLocationNames($this->get);
    }

    /*
    ****************************************************************************
     */

    function getAutocompleteUpcAppJsonController()
    {
        $upc = getDefault($this->get['term']);

        $upcs = new tables\upcs($this);

        $this->results = $upcs->getAutocomplete('upc', $upc);
    }

    /*
    ****************************************************************************
    */

    function getAutocompleteSkuAppJSONController()
    {
        $sku = getDefault($this->get['term']);

        $upcs = new tables\upcs($this);

        $this->results = $upcs->getSkuAutocomplete($sku);
    }

    /*
    ****************************************************************************
     */

    function submitMinMaxAppJSONController()
    {
        $minMax = new tables\minMax($this);

        $this->results = $minMax->checkMinMaxInput($this->post);
    }

    /*
    ****************************************************************************
     */

    function updateMinMaxAppJSONController()
    {
        $minMax = new tables\minMax($this);

        $this->results = $minMax->updateMinMax($this->post);
    }

    /*
    ****************************************************************************
     */

    function updateClientMinMaxAppJSONController()
    {
        $minMax = new tables\minMax($this);

        $this->results = $minMax->updateClientMinMax($this->post);
    }

    /*
    ****************************************************************************
     */

    function submitMinMaxRangeAppJSONController()
    {
        $minMaxRanges = new tables\minMaxRanges($this);

        $this->results = $minMaxRanges->checkMinMaxRange($this->post);
    }

    /*
    ****************************************************************************
     */

    function updateMinMaxRangeAppJSONController()
    {
        $minMaxRanges = new tables\minMaxRanges($this);

        $this->results = $minMaxRanges->updateMinMaxRange($this->post);
    }

    /*
    ****************************************************************************
     */

    function getContainerInfoAppJSONController()
    {
        $container = getDefault($this->get['container']);

        if (!$container) {
            return $this->results = FALSE;
        }

        $sql = 'SELECT      b.id,
                            upc,
                            u.sku
                FROM        inventory_containers c
                LEFT JOIN   inventory_batches b ON c.recNum = b.recNum
                LEFT JOIN   upcs u ON b.upcID = u.id
                WHERE       c.recNum = ?
                GROUP BY    b.id';

        $this->results = $this->queryResults($sql, [$container]);

    }

    /*
    ****************************************************************************
    */

    function getContainerTallyAppJSONController()
    {
        $container = getDefault($this->get['container']);

        if (!$container) {
            return $this->results = FALSE;
        }

        $this->results = tally::get($this, [
            'container' => $container,
        ]);

        $this->results = $this->results ? array_values($this->results) : FALSE;
    }

    /*
    ****************************************************************************
    */

    function updateTallyAppJSONController()
    {
        $tally = new tally([
            'mvc'       => $this,
            'tally'     => getDefault($this->post['tally']),
            'container' => getDefault($this->post['container']),
            'forcedGo'  => getDefault($this->post['forcedGo']),
        ]);

        $tally->updateTallySheet();
    }

    /*
    ****************************************************************************
    */

    function readyToCompleteAppJSONController()
    {
        $recNum = getDefault($this->get['recNum']);

        $this->results = \common\receiving::readyToComplete($this, $recNum);
    }

    /*
    ****************************************************************************
    */

    function updateStyleRowsAppJSONController()
    {
        $upc = getDefault($this->post['upc']);

        if (!$upc) {
            return $this->results = FALSE;
        }

        $upcs = new \tables\upcs($this);

        $this->results = $upcs->getStyleRows($upc);
    }

    /*
    ****************************************************************************
    */

    function addModelAppJSONController()
    {
        $quantity = intVal($this->post['quantity']);
        $userID = getDefault($this->post['userID']);
        $model = getDefault($this->post['model']);

        if (! $model) {
            $this->results = FALSE;

            return;
        }

        $tableModel = 'tables\\' . $model;

        $object = new $tableModel($this);

        $object->insert($userID, $quantity);

        $this->results = $quantity;
    }

    /*
    ****************************************************************************
    */

    function getMultiRowOrdersAppJSONController()
    {
        $onlineOrders = new tables\onlineOrders($this);

        $this->results = $onlineOrders->getMultiRowOrdersByOrders();
    }

    /*
    ****************************************************************************
    */

    function checkOnlineOrdersShipPasswordAppJSONController()
    {
        $password = md5($this->post['password']);

        $this->results = $password == scanner::ONLINE_ORDERS_SHIP_PASSWORD;
    }

    /*
    ****************************************************************************
    */

    function onlineOrderCheckOutAppJSONController()
    {
        $scans = getDefault($this->post['scans']);
        $step = getDefault($this->post['step']);
        $useUPC = json_decode($this->post['useUPC']);
        $useTracking = json_decode($this->post['useTracking']) && $useUPC;

        $this->results = [
            'next' => FALSE,
            'error' => FALSE,
            'complete' => FALSE,
            'description' => NULL,
            'orderNumber' => NULL,
            'trackingID' => NULL,
            'upc' => NULL,
            'cartonsScanned' => NULL,
        ];

        common\scanner::get($scans);

        if (! $scans) {
            return;
        }

        foreach ($scans as $scan) {
            if ($step) {

                $method = 'process' . $step . 'Scan';

                common\scanner::$method($this, $scan);

            } else {

                $classes = [
                    'cartonStatuses' => new tables\statuses\inventory($this),
                    'orderStatuses' => new tables\statuses\orders($this),
                    'orders' => new tables\orders($this),
                    'onlineOrders' => new tables\onlineOrders($this),
                    'cartons' => new tables\inventory\cartons($this),
                ];

                common\scanner::processOnlineOrderScan([
                    'app' => $this,
                    'classes' => $classes,
                    'scans' => $scan,
                    'useUPC' => $useUPC,
                    'useTracking' => $useTracking,
                ]);
            }
        }
    }

    /*
    ****************************************************************************
    */

    function automatedScannerAppJSONController()
    {
        $scanner = new common\automated($this);

        $scanner->setObjects([
            'plates'         => new plates($this),
            'orders'         => new tables\orders($this),
            'online'         => new tables\onlineOrders($this),
            'vendors'        => new tables\vendors($this),
            'cartons'        => new tables\inventory\cartons($this),
            'scanner'        => new common\scanner($this),
            'statuses'       => new tables\statuses($this),
            'wavePicks'      => new tables\wavePicks($this),
            'locations'      => new tables\locations($this),
            'inventory'      => new tables\old\inventory($this),
            'adjustments'    => new tables\inventory\adjustments\inventory($this),
            'orderBatches'   => new tables\orderBatches($this),
            'cartonPlates'   => new tables\inventory\plates($this),
            'orderStatuses'  => new tables\statuses\orders($this),
            'cartonStatuses' => new tables\statuses\inventory($this),
            'transfers'      => new tables\transfers($this),
        ]);

        $this->results = $scanner->ajax();
    }

    /*
    ****************************************************************************
    */

    function completeRCLogAppJSONController()
    {
        $this->results = \common\receiving::checkAndCompleteRCLog($this);
    }

    /*
    ****************************************************************************
    */

    function updateRCLogPrintAppJSONController()
    {
        $recNum = getDefault($this->get['recNum']);

        \common\receiving::updateRCLogPrint($this, $recNum);

        $this->results = \common\receiving::readyToComplete($this, $recNum);
    }

    /*
    ****************************************************************************
    */

    function updateRCLabelAppJSONController()
    {
        $recNum = getDefault($this->get['recNum']);

        \labels\rcLabel::updateRCLabel($this, $recNum);

        $this->results = \common\receiving::readyToComplete($this, $recNum);
    }

    /*
    ****************************************************************************
    */

    function getContainerInfoForRCAppJSONController()
    {
        $container = getDefault($this->get['container']);

        $this->results =
            \common\receiving::getContainerInfoForRC($this, $container);
    }

    /*
    ****************************************************************************
    */

    function checkRCLogLocationsAppJSONController()
    {
        $locations = new tables\locations($this);

        $locationNames = getDefault($this->post['locations']);
        $recNum = getDefault($this->post['recNum']);

        $results = $locations->checkRCLogLocations($locationNames, $recNum);

        $this->results = $results;
    }

    /*
    ****************************************************************************
    */

    function getClientCostsAppJSONController()
    {
        $vendorID = $this->get['vendorID'];
        $prefix = strtoupper($this->get['prefix']) == 'ALL'
            ? FALSE : $this->get['prefix'];

        $chargeMaster = new tables\customer\chargeCodeMaster($this);

        $vendorCosts = $chargeMaster->getClientCharges($vendorID, $prefix);

        $this->results = $vendorCosts ? $vendorCosts : TRUE;
    }

    /*
    ****************************************************************************
    */

    function updateClientCostsAppJSONController()
    {
        $vendorID = $this->post['vendorID'];
        $chgID = $this->post['chgID'];
        $cost = $this->post['cost'];

        if (!$vendorID || !$chgID || !$cost) {
            $this->results = FALSE;
        }

        //check to existing client charge code exist in invoice_cost table
        $sql = 'SELECT    *
                FROM      invoice_cost
                WHERE     cust_id = ?
                AND       chg_cd_id = ?
                ';

        $insertParam = $param = [$vendorID, $chgID];

        $userID = \access::getUserID();

        $selectResult = $this->queryResults($sql, $param);

        if (!$selectResult) {
            $sql = 'INSERT INTO invoice_cost (
                                    cust_id,
                                    chg_cd_id,
                                    chg_cd_price,
                                    create_by
                    ) VALUES (
                                ?, ?, ?, ?
                    )';
            array_push($insertParam, $cost, $userID);

            $this->runQuery($sql, $insertParam);
        } else {
            $status = \common\auditing::UPDATE;

            $sql = 'UPDATE  invoice_cost
                    SET     chg_cd_price = ?,
                            status = ?,
                            update_by = ?
                    WHERE   cust_id = ?
                    AND     chg_cd_id = ?';

            $updateParam = [$cost, $status, $userID, $vendorID, $chgID];

            $this->runQuery($sql, $updateParam);
        }

        $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function deleteClientCostsAppJSONController()
    {
        $vendorID = $this->post['vendorID'];
        $chgID = $this->post['chgID'];

        $userID = \access::getUserID();

        if (!$vendorID || !$chgID) {
            $this->results = FALSE;
        }

        $sql = 'UPDATE  invoice_cost
                SET     status = ?,
                        update_by = ?
                WHERE   cust_id = ?
                AND     chg_cd_id = ?';

        $status = \common\auditing::DELETE;

        $param = [$status, $userID, $vendorID, $chgID];

        $this->runQuery($sql, $param);

        $this->results = TRUE;

    }

    /*
    ****************************************************************************
    */

    function checkSeldatUPCAppJSONController()
    {
        $this->results = seldatContainers::submitContainer($this);
    }

    /*
    ****************************************************************************
    */

    function addCartonsToBatchAppJSONController()
    {
        $cartons = new tables\inventory\cartons($this);

        $batchID = $this->get['batchID'];

        $this->results = $cartons->addCartonsToBatch($this->post, $batchID);
    }

    /*
    ****************************************************************************
    */

    function getProductInfoAppJSONController()
    {
        $orders = new tables\orders($this);

        $this->results = $orders->getProductInfo($this->get);
    }

    /*
    ****************************************************************************
    */

    function insertOrderBatchAppJSONController()
    {
        $vendor = getDefault($this->get['vendor']);

        $orderBatches = new tables\orderBatches($this);
        $dealSites = new tables\dealSites($this);

        $dealSiteID = $dealSites->getWholesaleID();

        $batch = $orderBatches->insertDefaultBatch($vendor, $dealSiteID, TRUE);

        $this->results = [$vendor, $batch];

        return $this->results;
    }

    /*
    ****************************************************************************
    */

    function getWavePickDataAppJSONController()
    {
        $orderNumber = getDefault($this->get['orderNumber']);
        $processed = getDefault($this->get['processed']);

        if (!$orderNumber) {
            return $this->results = FALSE;
        } else {

            $orders = new tables\orders($this);
            $pickCartons = new \tables\inventory\pickCartons($this);

            $this->results = $pickCartons->getPickCartonData([
                'orderNumber' => [$orderNumber],
                'orders'      => $orders,
                'processed'   => $processed,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function createPickTicketAppJSONController()
    {
        $orders = new tables\orders($this);
        $orderBatches = new tables\orderBatches($this);
        $wavePicks = new tables\wavePicks($this);
        $truckOrderWaves = new tables\truckOrderWaves($this);
        $cartons = new tables\inventory\cartons($this);

        $result = $wavePicks->createPickTicket([
            'post'   => $this->post,
            'tables' => [
                'orders'          => $orders,
                'orderBatches'    => $orderBatches,
                'truckOrderWaves' => $truckOrderWaves,
            ],
            'ucc128' => $cartons->fields['ucc128']['select'],
        ]);

        $this->results = $result;
    }

    /*
    ****************************************************************************
    */

    function clearWavePickAppJSONController()
    {
        $orderNumber = $this->post['orderNumber'];

        $wavePicks = new tables\wavePicks($this);

        $wavePicks->clear([$orderNumber]);

        $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function getWavePickIDAppJSONController()
    {
        $scanOrderNumber = getDefault($this->get['scanOrderNumber']);

        $sql = 'SELECT    order_batch
                FROM      neworder
                WHERE     scanordernumber = ?
                ';

        $result = $this->queryResult($sql, [$scanOrderNumber]);
        $this->results = $result['order_batch'];
    }

    /*
    ****************************************************************************
    */

    function getNewLabelAppJSONController()
    {
        $activeUser = access::getUserID();

        $userID = getDefault($this->get['userID'], $activeUser);
        $quantity = getDefault($this->get['quantity'], 1);
        $type = getDefault($this->get['type'], 'order');

        $this->results = FALSE;

        $model = new tables\_default($this);

        if ($userID) {
            $result = \common\labelMaker::inserts([
                'model'     => $model,
                'userID'    => $userID,
                'quantity'  => $quantity,
                'labelType' => $type,
            ]);

            if ($result) {
                switch ($type) {
                    case 'order':
                        $table = 'neworderlabel';
                        $field = 'CONCAT(
                                    LPAD(userID, 4, 0),
                                    assignNumber
                                 )';
                        $key = 'assignNumber';
                        break;
                    case 'plate':
                        $table = 'licenseplate';
                        $field = 'ID';
                        $key = 'ID';
                        break;
                    case 'work':
                        $table = 'workorderlabel';
                        $field = 'CONCAT(
                                    LPAD(userID, 4, 0),
                                    assignNumber
                                 )';
                        $key = 'assignNumber';
                        break;
                    case 'bill':
                        $table = 'billofladings';
                        $field = 'CONCAT(
                                    LPAD(userID, 4, 0),
                                    assignNumber
                                 )';
                        $key = 'assignNumber';
                        break;
                }

                $sql = 'SELECT    ' . $field . ' AS label
                        FROM      ' . $table . '
                        ORDER BY  ' . $key . ' DESC
                        LIMIT 1
                        ';

                $result = $this->queryResult($sql);

                $this->results = $result['label'];
            }
        }
    }

    /*
    ****************************************************************************
    */

    function getAdjustInventoryAppJSONController()
    {
        $table = new tables\inventory\adjustments\inventory($this);

        $this->results = $table->getAdjustInventory($this->post);
    }

    /*
    ****************************************************************************
    */

    function adjustInventoryAppJSONController()
    {
        $cartons = json_decode($this->post['cartons'], TRUE);
        $newStatus = $this->post['newStatus'];
        $newLocation = $this->post['newLocation'];
        $locationUpdate = json_decode($this->post['locationUpdate']);

        $wavePicks = new \tables\wavePicks($this);
        $statuses = new \tables\statuses\inventory($this);

        // Select the cartons to get field IDs
        $params = [];
        $clauses = [];

        foreach ($cartons as $carton) {

            $params[] = $carton['batchID'];
            $params[] = $carton['cartonID'];

            $clauses[] = 'batchID = ? AND cartonID = ?';
        }

        $sql = 'SELECT  id,
                        locID,
                        mLocID,
                        statusID,
                        mStatusID
                FROM    inventory_cartons
                WHERE   ' . implode(' OR ', $clauses);

        $cartonInfo = $this->queryResults($sql, $params);

        $locID = NULL;

        if ($newLocation) {

            $sql = 'SELECT id
                    FROM   locations
                    WHERE  displayName = ?';

            $result = $this->queryResult($sql, [$newLocation]);

            $locID = $result['id'];
        }

        $statusID = $statuses->getStatusID($newStatus);

        $sql = 'UPDATE  inventory_cartons ca
                SET     statusID = ?,
                        mStatusID = ?,
                        locID = ?,
                        mLocID = ?
                WHERE   ca.id = ?
                ';

        logger::getFieldIDs('cartons', $this);

        logger::getLogID();

        $this->beginTransaction();

        foreach ($cartonInfo as $invID => $row) {

            $oldLocID = $row['locID'];
            $oldMLocID = $row['mLocID'];
            $oldStatusID = $row['statusID'];
            $oldMStatusID = $row['mStatusID'];

            // When not a location update, use old actual location
            $newLocID = $locationUpdate ? $locID : $oldLocID;

            $this->runQuery($sql, [
                $statusID,
                $statusID,
                $newLocID,
                $newLocID,
                $invID
            ]);

            $wavePicks->deactivateByCartonID([$invID]);

            logger::edit([
                'db' => $this,
                'primeKeys'   => $invID,
                'fields'      => [
                    'statusID'  => [
                        'fromValues' => $oldStatusID,
                        'toValues'   => $statusID,
                    ],
                    'mStatusID' => [
                        'fromValues' => $oldMStatusID,
                        'toValues'   => $statusID,
                    ],
                    'locID'     => [
                        'fromValues' => $oldLocID,
                        'toValues'   => $newLocID,
                    ],
                    'mLocID'    => [
                        'fromValues' => $oldMLocID,
                        'toValues'   => $newLocID,
                    ],
                ],
                'transaction' => FALSE,
            ]);
        }

        $this->commit();

        $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function getShippingFromAppJSONController()
    {
        $vendorID = $this->post['vendorID'];

        $locations = new tables\locations($this);
        $result = $locations->getLocationfromVendor([$vendorID]);

        $this->results = getDefault($result[$vendorID]['locationID'], NULL)
            ? $result[$vendorID]['locationID']
            : 'noLocationID';
    }

    /*
    ****************************************************************************
    */

    function getPrintUccLabelsFileAppJSONController()
    {
        $recNum = $this->get['recNum'];

        $batches = new tables\inventory\batches($this);

        $recNumBatches = $batches->getByRecNum([$recNum]);

        $batchValues = array_column($recNumBatches, 'batch');
        $batchKeys = array_flip($batchValues);

        $badBatches = $batches->checkBadBatches($batchKeys);

        if ($badBatches) {

            $recNumBatches = $batches->getByRecNum([$recNum], TRUE);

            $this->results = $recNumBatches ?
                pdf::getUCCLabelsDownloadName($recNumBatches) : FALSE;
        } else {
            $this->results = FALSE;
        }
    }

    /*
    ****************************************************************************
    */

    function downloadLadingAppJSONController()
    {
        $filePath = directories::getDir('uploads', 'billoflading');
        $fileName = $this->get['fileName'];

        pdf::download($filePath, $fileName);
    }

    /*
    ****************************************************************************
    */

    function changOrdersBatchAppJSONController()
    {
        $orderNumbers = $this->get['orderNumbers'];
        $vendorID = $this->get['vendorID'];

        $orderBatches = new tables\orderBatches($this);

        $this->results = $orderBatches->changOrdersBatch($vendorID, $orderNumbers);
    }

    /*
    ****************************************************************************
    */

    function splitOrderCartonsAppJSONController()
    {
        $splitData = getDefault($this->post['tableData'], []);

        $cartons = new tables\inventory\cartons($this);

        $this->results = $cartons->split($splitData);
    }

    /*
    ****************************************************************************
    */

    function getUPCDescriptionAppJSONController()
    {
        $orders = new tables\orders($this);

        $this->results = $orders->getUPCDescription($this->post);
    }

    /*
    ****************************************************************************
    */

    function checkExportTableAppJSONController()
    {
        $onlineOrderExports = new tables\onlineOrderExports($this);
        $exportsSignatures = new tables\onlineOrders\exportsSignatures($this);
        $exportsProviders = new tables\onlineOrders\exportsProviders($this);
        $exportsPackages = new tables\onlineOrders\exportsPackages($this);
        $exportsServices = new tables\onlineOrders\exportsServices($this);
        $exportsBillTo = new tables\onlineOrders\exportsBillTo($this);

        $data = [
            'tableData'         => $this->post['table'],
            'exportsSignatures' => $exportsSignatures,
            'exportsProviders'  => $exportsProviders,
            'exportsPackages'   => $exportsPackages,
            'exportsServices'   => $exportsServices,
            'exportsBillTo'     => $exportsBillTo,
        ];

        $exportErrors = $onlineOrderExports->checkExportTable($data);

        $this->results = $exportErrors ? $exportErrors : FALSE;
    }

    /*
    ****************************************************************************
    */

    function editLocationBatchAppJSONController()
    {
        $model = new tables\inventory\locBatches($this);

        $post = $this->post;

        $result = $model->updateLocationBatch([
            'columnID' => $post['columnId'],
            'arrID'    => $post['id'],
            'value'    => $post['value'],
        ]);

        if (!$result) {
            return;
        }

        echo $post['value'];
    }

    /*
    ****************************************************************************
    */

    function updateUpcInfoAppJsonController()
    {
        $newUpc = $this->post['upcAdjust'];

        $listUPCs = $this->post['listUPCs'];

        $getUpcs = new \tables\upcs($this);
        $newUpcInfo = $getUpcs->getUPCInfo($newUpc);

        if (!$newUpcInfo['id']) {
            return $this->results = FALSE;
        }

        $upcs = array_values($listUPCs);

        $sql = 'SELECT      b.id,
                            b.upcID
                FROM        upcs u
                LEFT JOIN   inventory_batches b ON b.upcID = u.id
                WHERE upc IN (' . $this->getQMarkString($upcs) . ')';
        $results = $this->queryResults($sql, $upcs);

        $this->beginTransaction();

        foreach ($results as $batchID => $value) {

            //Update upc info in inventory batches
            $sql = 'UPDATE  inventory_batches
                    SET     upcID = ?
                    WHERE   id = ?';
            $this->runQuery($sql, [
                $newUpcInfo['id'],
                $batchID,
            ]);

            //set inactive for upcs
            $sql = 'UPDATE  upcs
                    SET     active = ?
                    WHERE   id = ?';
            $this->runQuery($sql, [
                self::UPC_INACTIVE,
                $value['upcID'],
            ]);
        }

        $this->commit();

        $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function checkScanContainerCellAppJSONController()
    {
        $field = $this->post['field'];
        $value = $this->post['value'];
        $measurement = $this->post['measurement'];

        $cartons = new \tables\inventory\cartons($this);

        $cellInfo = seldatContainers::$tableCells[$field];

        $errors = seldatContainers::checkValue([
            'value'        => $value,
            'cellInfo'     => $cellInfo,
            'app'          => $this,
            'measurement'  => $measurement,
            'cartonsClass' => $cartons,
        ]);

        $this->results = $errors ? $errors : FALSE;
    }

    /*
    ****************************************************************************
    */

    function checkImportContainerCellAppJSONController()
    {
        seldatContainers::$tableCells = import\inventoryBatch::getTableCells();

        $this->checkScanContainerCellAppJSONController();
    }

    /*
    ****************************************************************************
    */

    function getStartDateStorageAppJSONController()
    {
        $request = getDefault($this->post['request']);
        $vendorID = getDefault($this->post['vendorID']);

        if ($request != 'startDate' || !$vendorID) {
            return $this->results = FALSE;
        }

        $sql = 'SELECT    MIN(DATE(startDate)) AS minStartDate
                FROM      invoices_storage i
                LEFT JOIN inventory_containers co ON co.recNum = i.recNum
                WHERE     co.vendorID = ?
                AND       i.invoiceID IS NULL';

        $result = $this->queryResult($sql, [$vendorID]);

        $this->results = $result['minStartDate'];
    }

    /*
    ****************************************************************************
    */

    function updateFeatureShowStatusAppJSONController()
    {
        $userID = access::getUserID();
        $request = getDefault($this->post['request']);

        if ($request != 'updateFeatureShowStatus') {
            return $this->results = FALSE;
        }

        $sql = 'UPDATE  version_info
                SET     isShow = 0
                WHERE   userID = ?';

        $this->runQuery($sql, [$userID]);
    }

    /*
    ****************************************************************************
    */

    function checkImportSeldatUPCAppJSONController()
    {
        $this->results = import\inventoryBatch::importInventoryBatches($this);
    }

    /*
    ****************************************************************************
    */

    function checkMezzanineLocationAppJsonController()
    {
        $fieldName = getDefault($this->post['fieldName']);
        $fieldValue = getDefault($this->post['fieldValue']);
        $vendorName = getDefault($this->post['vendorName']);

        if (!$fieldName || !$fieldValue || !$vendorName) {
            return $this->results = FALSE;
        }

        $sql = 'SELECT  l.id,
                        v.id
                FROM    locations l
                JOIN    warehouses w ON w.id = l.warehouseID
                JOIN    vendors v ON v.warehouseID = w.id
                WHERE   ' . $fieldName . ' = ?
                AND     CONCAT(w.shortName, "_", vendorName) = ?';

        $result = $this->queryResults($sql, [
            $fieldValue,
            $vendorName,
        ]);

        $this->results = $result ? TRUE : FALSE;
    }

    /*
    ****************************************************************************
    */

    function releaseCanceledOrderAppJSONController()
    {
        $orderNumber = getDefault($this->get['orderNumber']);

        $statuses = new tables\statuses\orders($this);
        $orders = new tables\orders($this);

        $status = tables\orders::STATUS_ENTRY_CHECK_IN;

        $statusID = $statuses->getStatusID($status);

        $orderID = $orders->getIDByOrderNumber($orderNumber);

        common\order::updateAndLogStatus([
            'orderIDs'   => [$orderID],
            'statusID'   => $statusID,
            'tableClass' => $orders,
        ]);

        return $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function checkMezzanineStorageAppJsonController()
    {
        $mezzanine = new \tables\onlineOrders\mezzanineImportQuery($this);
        $orderBatch = new \tables\orderBatches($this);
        $wavePick = new \tables\wavePicks($this);
        $minMax = new \tables\minMaxRanges($this);

        $request = getDefault($this->post['request']);
        $batch = getDefault($this->post['batchOrder']);
        $this->results = $enoughInventory = TRUE;
        $upcs = $freeLocation = $notMinMaxSettingUPC = [];

        if ($request != 'checkStorage' || !$batch) {
            return $this->results = FALSE;
        }

        $batchInfo = $orderBatch->getBatchInfo($batch);
        $orderInfo = reset($batchInfo);

        $isWholeSale = $orderBatch->isWholeSale($batch);

        // Not to check valid mezzanine quantity when order picked
        // OR wholesale
        if ($isWholeSale) {
            return $this->results;
        }

        $shortages = [];

        $upcsInformation = $mezzanine->oneQuery($orderInfo['vendorID'],
                $batchInfo, $batch);

        $reportStatus = self::MEZZANINE_TRANSFER_STATUS;
        $isReportLogged = \common\report::isLogged($this, $batch, $reportStatus);

        foreach ($upcsInformation as $upc => $upcInfo) {
            $quantity = $batchInfo[$upc]['quantity'];
            $actualQuantity = $upcInfo['actualQuantity'];
            $min = $upcInfo['min'] ? $upcInfo['min'] : 0;
            $transferRequired = $actualQuantity - $quantity < $min;
            $enoughInventory = $actualQuantity < $quantity ? FALSE : TRUE;

            $freeLocation = $minMax->getFreeLocation($orderInfo['vendorID']);

            //upc not in range or have not any free location
            if (!$upcInfo['hasMinMaxSetting'] && !$freeLocation && !$isReportLogged) {
                return $this->results = [
                    'type' => 'notInRange',
                    'upc'  => $upc,
                ];
            }

            //Upc not min/max setting but in range
            if (!$upcInfo['hasMinMaxSetting']
                && !$isReportLogged
            ) {
                $upcs[] = $upc;
                continue;
            }

            // not enough in mezzanine, do transfer to this upc
            if ($transferRequired) {
                $upcs[] = $upc;

                $shortage = $quantity - $actualQuantity;

                if ($shortage > 0) {
                    $shortages[] = 'UPC: ' . $upc . ', shortage -  ' . $shortage
                        . ' piece(s)';
                }
            }
        }

        //Log for Cron
        if (!$isReportLogged && $upcs) {
            $wavePick->createReportData([
                'batchOrder' => $batch,
                'data'       => $upcs,
                'status'     => $reportStatus,
            ]);
        }

        if ($shortages) {
            return $this->results = [
                'type'      => 'notEnoughMezzanine',
                'shortages' => $shortages,
            ];
        }

        return $this->results;
    }

    /*
    ****************************************************************************
    */

    function unsplitCartonsAppJSONController()
    {
        $parents = getDefault($this->post['parentID']);

        if (!$parents) {
            return $this->results = [
                'errors'      => ['No parent cartons were submitted'],
                'mergeCarton' => FALSE,
            ];
        }

        $cartons = new \tables\inventory\cartons($this);
        $unSplitCarton = new \tables\unsplitCartons($this);

        $results = $unSplitCarton->getSplitData($cartons, $parents);

        if ($results['errors']) {
            return $this->results = [
                'errors'      => $results['errors'],
                'mergeCarton' => FALSE,
            ];
        }

        logger::getFieldIDs('cartons', $this);

        logger::getLogID();

        $mergeCarton = $unSplitCarton->mergeCartons($results['parentIDs']);

        return $this->results = [
            'errors'      => FALSE,
            'mergeCarton' => $mergeCarton ? $mergeCarton : FALSE,
        ];
    }

    /*
    ****************************************************************************
    */

    function deleteReceivingAppJSONController()
    {
        $receivingID = getDefault($this->post['receivingID']);

        if (!$receivingID) {
            return $this->results = FALSE;
        }

        $receiving = new \tables\receiving($this);

        $receiving->updateReceiving($receivingID);

        return $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function updateReceivingStatusAppJSONController()
    {
        $this->errors = [];
        $receiving = new \tables\receiving($this);

        $receivingID = getDefault($this->post['receivingNumber'], NULL);

        $statusID = getDefault($this->post['receivingStatus']);

        $receiving->updateReceiving($receivingID, $statusID);

        return $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function checkRCLogContainerAppJSONController()
    {
        $receivingID = getDefault($this->post['receivingNumber']);
        $receivingStatus = getDefault($this->post['receivingStatus']);
        $option = getDefault($this->post['confirm']);

        if (!$receivingID) {
            return $this->results = FALSE;
        }

        $receiving = new \tables\receiving($this);

        $data = [
            'receivingNumber' => $receivingID,
            'status'          => $receivingStatus,
        ];

        $results = $receiving->checkReceivedContainer($receivingID, $option);

        $option === 'checkRCLog' ? $this->results = [
            'status'   => $results['status'],
            'quantity' => $results['missingContainer'],
            'data'     => $data,
        ] : $this->results = [
            'status' => $results,
            'data'   => $data,
        ];

        if (isset($results['notRCLog'])) {
            $this->results['notRCLog'] = $results['notRCLog'];
        }

        return $this->results;

    }

    /*
    ****************************************************************************
    */

    function getReceivingNumberAppJSONController()
    {
        $receiving = new tables\receiving($this);

        $this->results = $receiving->getReceivingNumber($this->get);
    }

    /*
    ****************************************************************************
    */

    function confirmUpdateReceivingStatusAppJSONController()
    {
        $confirm = getDefault($this->post['confirm']);
        $data = getDefault($this->post['data']);

        if ($confirm != 'confirmUpdate' || !$data) {
            return $this->results = FALSE;
        }

        $receiving = new \tables\receiving($this);

        $receiving->updateReceiving($data['receivingNumber'], $data['status']);

        return $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function updateInvoiceMonthAppJSONController()
    {
        if (!getDefault($this->post)) {
            return $this->results = FALSE;
        }

        //get invoice status name from tables\statuses.php
        $invoice = new tables\statuses\invoice($this);
        $this->results = $invoice->getInvoiceMonthSelect($this->post);

        return json_encode($this->results);
    }

    /*
    ****************************************************************************
    */

    function getOrderInfoAppJSONController()
    {
        $data = getDefault($this->get['params']);

        $orders = new tables\orders($this);

        $scanordernumber = $data['scanordernumber'];

        $results = $orders->checkIfOrderProcessed($scanordernumber);

        $orderInfo = $errors = FALSE;

        if ($results['canceledOrders'][$scanordernumber]) {
            $errors = 'Order # ' . $scanordernumber . ' is a canceled order';
        } else {

            $orderInfo = $orders->getOrderInfoResults($data);
            // send FALSE to JavaScript if an empty array returned
            $errors = ! $orderInfo ? 'Order is not found. Make sure it
                has "Order Processing Check-Out" status' : FALSE;
        }

        return $this->results = [
            'errors'  => $errors,
            'results' => $orderInfo,
        ];
    }

    /*
    ****************************************************************************
    */

    function getAutocompleteOrderNumberAppJSONController()
    {
        $orders = new tables\orders($this);

        $this->results = $orders->getAutoCompleteOrderNumber($this->get);
    }

    /*
    ****************************************************************************
    */

    function getShipFromInfoAppJSONController()
    {
        $data = getDefault($this->get['params']);

        $orders = new tables\billOfLadings($this);

        $this->results = $orders->getShipFrom($data);
    }

    /*
    ****************************************************************************
    */

    function emptyTruckOrderAppJSONController()
    {
        $orderNumber = $this->post['orderNumber'];

        $truckOrderWaves = new tables\truckOrderWaves($this);

        $truckOrderWaves->emptyTruckOrder([$orderNumber]);

        $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function generateManualBOLAppJSONController()
    {
        $activeUser = access::getUserID();
        $userID = getDefault($this->get['userID'], $activeUser);
        if (!$userID) {
            $errors[] = 'Nor found User Access.';
        }

        $this->results = $errors = FALSE;

        $model = new tables\_default($this);
        $bolModel = new \tables\billOfLadings($this);

        $result = \common\labelMaker::inserts([
            'model'     => $model,
            'userID'    => $userID,
            'quantity'  => 1,
            'labelType' => 'bill',
        ]);

        if (!$result) {
            $errors[] = 'Cannot generate new Bill Of Lading Label.';
        }

        $orderNumbers = str_replace(' ', '', $this->post['orderNumbers']);
        $scanOrderNumbers = explode(',', $orderNumbers);

        $orderIDs = $bolModel->getOrderIDs($scanOrderNumbers);
        if (count($scanOrderNumbers) != count($orderIDs)) {
            $errors[] = 'Order # not match.';
        }

        foreach ($orderIDs as $orderNum => $data) {
            if (isset($data['shippingID']) && $data['shippingID']) {
                $errors[] = $orderNum . 'has been existed in Bill Of Lading ('
                    . $data['bolID'] . ')';
            }
            $shipFromValue = getDefault($data['shipFrom']);
            $shipFrom[$shipFromValue] = TRUE;
        }

        if (count($shipFrom) > 1) {
            $errors[] = 'Multi Orders have different shipping from location.';
        }
        if ($errors) {
            $this->results = ['errors' => $errors];
        } else {
            $results = $bolModel->addOrderNumberForBOL($orderIDs, $shipFrom);
            $this->results = $results ? $results :
                    ['errors' => 'Insert Data is errors'];
        }
    }

    /*
    ****************************************************************************
    */

    function addClientNotesAppJSONController()
    {
        if (!getDefault($this->post)) {
            return $this->results = FALSE;
        }

        $containerReceived = new tables\containersReceived($this);

        $containerReceived->containerClientNotes($this->post);

        $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function addOrderClientNotesAppJSONController()
    {
        if (!getDefault($this->post)) {
            return $this->results = FALSE;
        }

        $orders = new tables\orders($this);

        $orders->orderClientNotes($this->post);

        $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function updateCustomerInfoAppJSONController()
    {
        $customer = new \common\customer($this);

        $display = getDefault($this->post['display'], NULL);

        $result = $customer->updateCustomerMaster($this->post['data'],
            $this->post['vendorID'], $display);

        $this->results = $result ? $result : TRUE;

    }

    /*
    ****************************************************************************
    */

    function insertCustomerInfoAppJSONController()
    {
        $customer = new \common\customer($this);

        $result = $customer->insertCustomerMaster($this->post['data']);

        $this->results = $result ? $result : TRUE;
    }

    /*
    ****************************************************************************
    */

    function insertCustomerContactAppJSONController()
    {
        $table = new \tables\customer\customerContact($this);

        $errors = $table->insertContact($this->post['data']);

        $this->results = $errors ? $errors : TRUE;
    }

    /*
    ****************************************************************************
    */

    function updateCustomerContactAppJSONController()
    {
        $table = new \tables\customer\customerContact($this);

        $errors = $table->updateContact($this->post['data'],
                $this->post['primeKey']);

        $this->results = $errors ? $errors : TRUE;
    }

    /*
    ****************************************************************************
    */

    function deleteCustContactsAppJSONController()
    {
        $ctcIDs = $this->postVar('ctcIDs', 'getDef');
        $custID = $this->postVar('custID', 'getDef');

        $qMarks = $this->getQMarkString($ctcIDs);

        $sql = 'UPDATE customer_ctc
                SET    status = "d"
                WHERE  cust_id = ?
                AND    cust_ctc_id IN (' . $qMarks . ')';

        $params = $ctcIDs;
        array_unshift($params, $custID);

        $this->runQuery($sql, $params);

        $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function updateInvoicePaymentAppJSONController()
    {
        $invoiceHaader = new \invoices\headers($this);

        $results = $invoiceHaader->updateInvoicePayment($this->post);

        $this->results = $results ? $results : TRUE;
    }

    /*
    ****************************************************************************
    */

    function getCustomerInfoAppJSONController()
    {
        $vendorID = $this->post['vendorID'];

        $customer = new \common\customer($this);

        //get the billTo address
        $billToValues = $customer->getBillTo($vendorID);

        //get the billTo address
        $shipToValues = $customer->getShipTo($vendorID);

        $custInfo = array_merge($billToValues, $shipToValues);

        $this->results = $custInfo ? $custInfo : TRUE;
    }

    /*
    ****************************************************************************
    */

    function searchCustContactInfoAppJSONController()
    {
        $contacts = new tables\customer\customerContact($this);

        $contact = $contacts->search([
            'searchTerms' => [
                'cust_id' => $this->getVar('custID', 'getDef'),
                'ctc_dft' => TRUE,
            ],
            'oneResult'   => TRUE,
        ]);

        $this->results = getDefault($contact['cust_ctc_id'], FALSE);
    }

    /*
    ****************************************************************************
    */

    function getLaborAppJSONController()
    {
        $recNum = $this->getVar('recNum');

        $cat = $this->getVar('cat', 'getDef', labor::ACTUAL);

        //check if RC Log complete
        $sql = 'SELECT   recNum
                FROM     tallies t
                JOIN     wrk_hrs_rcv w ON w.rcv_num = t.recNum
                WHERE    recNum = ?
                AND      locked
                AND      cat = ?';

        $tallyRes = $this->queryResult($sql, [$recNum, $cat]);

        if ( ! $tallyRes ) {
            $cat = $this->getVar('cat', 'getDef', labor::ESTIMATED);
        }

        $sql = 'SELECT   type,
                         amount
                FROM     wrk_hrs_rcv
                WHERE    rcv_num = ?
                AND      cat = ?
                ORDER BY id
               ';

        $result = $this->queryResults($sql, [$recNum, $cat]);

        $labor = [
          'rushAmt'  => getDefault($result[labor::RUSH]['amount']),
          'otAmt'   => getDefault($result[labor::OVERTIME]['amount'])
        ];

        $this->results = array_map('floatval', $labor);
    }

    /*
    ****************************************************************************
    */

    function updateLaborAppJSONController()
    {
        $type = $this->postVar('type');

        $field = $table = $target = NULL;

        $cat = $this->getVar('cat', 'getDef', labor::ESTIMATED);

        switch ($type) {
            case 'rc':
                $target = $this->postVar('recNum');
                $table = 'wrk_hrs_rcv';
                $field = 'rcv_num';

                //check if RC Log complete
                $sql = 'SELECT   recNum
                        FROM     tallies
                        WHERE    recNum = ?
                        AND      locked';

                $tallyRes = $this->queryResult($sql, [$target]);

                if ( $tallyRes ) {
                    $cat = $this->getVar('cat', 'getDef', labor::ACTUAL);
                }

                break;
            case 'op':
                $target = $this->postVar('scanNumber');
                $table = 'wrk_hrs_ord_prc';
                $field = 'scan_ord_nbr';

                $cat = $this->postVar('actual') ? 'a' : 'e';

                break;
        }

        $sql = 'INSERT INTO '.$table.'
                SET         '.$field.' = ?,
                            type = ?,
                            amount = ?,
                            cat = ?,
                            create_by = ?
                ';

        $rushAmount = $this->postVar('rushAmount');
        $otAmount = $this->postVar('otAmount');

        $this->beginTransaction();

        $this->runQuery($sql, [
                $target,
                labor::RUSH,
                $rushAmount,
                $cat,
                access::getUserID()
        ]);

        $this->runQuery($sql, [
                $target,
                labor::OVERTIME,
                $otAmount,
                $cat,
                access::getUserID()
        ]);

        $this->commit();

        $amount = [
          'rushAmount' => floatVal($rushAmount),
          'otAmount'  => floatVal($otAmount)
        ];

        $this->results = $amount;
    }

    /*
    ****************************************************************************
    */

    function updateCustContactInfoAppJSONController()
    {
        $post = $this->getArray('post');

        $table = new \tables\customer\customerContact($this);

        // Deactivate all the contacts of the customer
        // Set the new default contact

        $sql = 'UPDATE customer_ctc
                SET    ctc_dft = 0
                WHERE  cust_id = ?';

        $custID = $post['custID'];

        $this->runQuery($sql, [$custID]);

        $errors = $table->updateContact([
            'ctc_dft' => 1,
        ], $post['ctcID']);

        $this->results = $errors ? $errors : TRUE;
    }

    /*
    ****************************************************************************
    */

    function getChargeCodesAppJSONController()
    {
        $prefix = $this->post['prefix'];
        $vendorID = $this->post['vendorID'];

        $chargeMaster = new tables\customer\chargeCodeMaster($this);

        $chargeCodes = $chargeMaster->getClientCharges($vendorID, $prefix);

        $this->results = $chargeCodes ? $chargeCodes : TRUE;
    }

    /*
    ****************************************************************************
    */

    function getOrderDataByWorkOrderNumberAppJSONController()
    {
        $data = $this->post['data'];

        $isCheckOut = json_decode($data['isCheckOut']);

        $model = $isCheckOut ? new tables\workOrders\workOrderHeaders($this) :
            new tables\orders($this);

        $field = $isCheckOut ? 'workOrderNumbers' : 'scanOrderNumbers';

        $this->results = $model->getOrderData($data[$field]);
    }

    /*
    ****************************************************************************
    */

    function getClientLaborAppJSONController()
    {
        $vendorIDs = array_column($this->get['data'], 'vendorID');
        $workOrderNumbers = array_column($this->get['data'], 'workOrderNumber');

        $uniqueVendors = array_unique($vendorIDs);

        $vendorKeys = array_values($uniqueVendors);

        $workOrderHeaders = new tables\workOrders\workOrderHeaders($this);

        $headers = $workOrderHeaders->getWorkOrderHeader($workOrderNumbers);

        $workOrderIDs = array_column($headers, 'wo_id');

        $this->results = [
            'header' => $headers,
            'tables' => \common\workOrderLabor::getWorkOrderLaborTables($this, $vendorKeys),
            'values' => \common\workOrderLabor::getWorkOrderLaborValues($this, $workOrderIDs),
            'hours'  => \common\workOrders::getWorkingHours($this, $workOrderNumbers),
        ];
    }

    /*
    ****************************************************************************
    */

    function workOrderCheckInVerifyAppJSONController()
    {
        $this->results = common\scanner::workOrderCheckInVerify([
            'app'   => $this,
            'scans' => $this->post['scans'],
        ]);
    }

    /*
    ****************************************************************************
    */

    function workOrderCheckOutVerifyAppJSONController()
    {
        $this->results = common\scanner::workOrderCheckOutVerify([
            'app'   => $this,
            'scans' => $this->post['scans'],
        ]);
    }

    /*
    ****************************************************************************
    */

    function updateInvoTablesAppJSONController()
    {
        $this->results = common\invoicing::init($this)->updateInvoTables();
    }

    /*
    ****************************************************************************
    */

    function submitWorkOrderAppJSONController()
    {
        \common\workOrders::updateWorkOrders($this, $this->post['data']);

        $this->results = TRUE;
    }

    /*
    ****************************************************************************
    */

    function updateInvoiceCustomerInfoAppJSONController()
    {
        $customer = new \common\customer($this);

        $result = $customer->updateInvoiceCustomerInfo($this->post['data'], $this->post['vendorID']);

        $this->results = $result ? $result : TRUE;

    }

    /*
    ****************************************************************************
    */

    function updateInvoiceProcessingAppJSONController()
    {
        $this->results =
            common\invoicing::init($this)->updateInvoiceProcessing($this->post);
    }

    /*
    ****************************************************************************
    */

    function cancelInvoiceAppJSONController()
    {
        $this->results =
            common\invoicing::init($this)->cancelInvoice($this->post['invoiceNo']);
    }

    /*
    ****************************************************************************
    */

    function pickingAppJSONController()
    {
        $this->results = common\scanner::pickingCheckOut($this, $this->post);
    }

    /*
    ****************************************************************************
    */

    function getCustomerByWarehouseIDAppJSONController()
    {
        $warehouseID = getDefault($this->post['warehouseID']);
        $vendor = new tables\vendors($this);

        $this->results = $vendor->getVendorDropdown($warehouseID);
    }

    /*
    ****************************************************************************
    */

    function getCustomerNameByWarehouseIDAppJSONController()
    {
        $locations = new tables\locations($this);
        $location = getDefault($this->get['term']);
        $warehouseID = getDefault($this->get['warehouseID']);

        $this->results = $locations->getCustomerNameByWarehouseID($location,
            $warehouseID);
    }

    /*
    ****************************************************************************
    */

    function addCustomRowAppJSONController()
    {
        $cycleDetail = new tables\cycleCount\cycleCountDetail($this);

        $this->results = $cycleDetail->customInsertNewSKU($this->post);
    }

    /*
    ****************************************************************************
    */

    function saveCycleAppJSONController()
    {
        $processTable = new tables\cycleCount\processDiscrepancyByCountItem($this);

        $this->results = $processTable->processSaveCycleItemData($this->post);
    }

    /*
    ****************************************************************************
    */

    function getSKUByClientIDAppJSONController()
    {
        $cycle = new tables\cycleCount\cycleCount($this);
        $sku = getDefault($this->get['term']);
        $clientID = getDefault($this->get['clientID']);

        $this->results = $cycle->getSKUByClientID($sku, $clientID);
    }

    /*
    ****************************************************************************
    */

    function getSKUByWarehouseIDAppJSONController()
    {
        $cycle = new tables\cycleCount\cycleCount($this);
        $sku = getDefault($this->get['term']);
        $warehouseID = getDefault($this->get['warehouseID']);

        $this->results = $cycle->getSKUByWarehouseID($sku, $warehouseID);
    }

    /*
    ****************************************************************************
    */

    function loadUPCInfoFromAjaxAppJSONController()
    {
        $cycle = new tables\cycleCount\cycleCount($this);
        $input = getDefault($this->get['term']);
        $clientID = getDefault($this->get['clientID']);
        $sku = getDefault($this->get['sku']);
        $type = getDefault($this->get['type']);

        $this->results = $cycle->loadUPCInfoFromAjax([
            'input' => $input,
            'clientID' => $clientID,
            'sku' => $sku,
            'type' => $type,
        ]);
    }

    /*
    ****************************************************************************
    */

    public function acceptCountItemsAppJSONController()
    {
        $countItems = $this->post['countItems'];

        $processData = new tables\cycleCount\processAuditCarton($this);

        $this->results = $processData->acceptCountItems($countItems);
    }

    /*
    ****************************************************************************
    */

    public function recountCountItemsAppJSONController()
    {
        $countItems = $this->post['countItems'];

        $processData = new tables\cycleCount\processAuditCarton($this);

        $result = $processData->recountCountItems($countItems);

        $this->results = $result;
    }

    /*
    ****************************************************************************
    */

    public function autocompleteLocationsWarehouseAppJSONController()
    {
        $warehouseID = $this->get['warehouseID'];
        $searchValue = $this->get['term'];

        $result = tables\cycleCount\cycleCount::searchLocationByWarehouse(
            $this, $warehouseID, $searchValue);

        $this->results = $result;
    }

    /*
    ****************************************************************************
    */

    public function checkLocationOnWarehouseAppJSONController()
    {
        $fieldValue = $this->post['fieldValue'];
        $warehouseID = $this->post['warehouseID'];

        $this->results = tables\cycleCount\cycleCount::checkLocationOnWarehouse(
            $this, $fieldValue, $warehouseID);
    }

    /*
    ****************************************************************************
    */

    public function createCycleCountAppJSONController()
    {
        $data = $this->post['data'];
        $cycleCount = new tables\cycleCount\cycleCount($this);

        $this->results = $cycleCount->processCreateCycleCount($data);
    }

    /*
    ****************************************************************************
    */

    public function getTablesAppJSONController()
    {
        $order = $this->getVar('order');

        $cartons = new tables\inventory\cartons($this);
        $vendors = new tables\vendors($this);

        $ucc = $cartons->fields['ucc128']['select'];
        $vendor = $vendors->fields['fullVendorName']['select'];

        $sql = 'SELECT '.$ucc.',
                       n.scanOrderNumber,
                       cs.shortName AS status,
                       cms.shortName AS mStatus,
                       '.$vendor.' AS vendor
                FROM   neworder n
                JOIN   statuses ns ON ns.id = n.statusID
                JOIN   inventory_cartons ca ON ca.orderID = n.id
                JOIN   statuses cs ON cs.id = ca.statusID
                JOIN   statuses cms ON cms.id = ca.mStatusID
                JOIN   inventory_batches b ON b.id = ca.batchID
                JOIN   inventory_containers co ON co.recNum = b.recNum
                JOIN   vendors v ON v.id = co.vendorID
                JOIN   warehouses w ON w.id = v.warehouseID
                WHERE  n.scanOrderNumber = ?
                ';

        $shippedCartons = $this->queryResults($sql, [$order]);

        $sql = 'SELECT '.$ucc.',
                       n.scanOrderNumber,
                       cs.shortName AS status,
                       cms.shortName AS mStatus,
                       '.$vendor.' AS vendor
                FROM   neworder n
                JOIN   statuses ns ON ns.id = n.statusID
                JOIN   pick_cartons p ON p.orderID = n.id
                JOIN   inventory_cartons ca ON ca.id = p.cartonID
                JOIN   statuses cs ON cs.id = ca.statusID
                JOIN   statuses cms ON cms.id = ca.mStatusID
                JOIN   inventory_batches b ON b.id = ca.batchID
                JOIN   inventory_containers co ON co.recNum = b.recNum
                JOIN   vendors v ON v.id = co.vendorID
                JOIN   warehouses w ON w.id = v.warehouseID
                WHERE  n.scanOrderNumber = ?
                AND    p.active
                ';

        $waveCartons = $this->queryResults($sql, [$order]);

        $all = array_merge($shippedCartons, $waveCartons);

        $pickOnly = array_diff_key($all, $shippedCartons);
        $shippedOnly = array_diff_key($all, $waveCartons);

        $both = array_diff_key($all, $shippedOnly, $pickOnly);
        $subQty = count($shippedOnly);

        $types = [
            'pickOnly' => $pickOnly,
            'both' => $both,
            'shippedOnly' => $shippedOnly,
        ];

        foreach ($types as $cat => $array) {
            foreach ($array as $ucc => &$row) {

                $row['isPick'] = 'Is In Order Wave Pick';
                $row['isShipped'] = 'Was Shipped With Order';
                switch ($cat) {
                    case 'pickOnly':
                        $row['isShipped'] = NULL;
                        break;
                    case 'shippedOnly':
                        $row['isPick'] = NULL;
                }

                $row['ucc'] = $ucc;

                $this->results['cartons'][] = $row;
            }
        }

        $this->results['subQty'] = abs($subQty);
    }

    /*
    ****************************************************************************
    */

    public function deleteCycleCountAppJSONController()
    {
        $cycleIDs = $this->post['cycleID'];

        $cycleCount = new tables\cycleCount\cycleCount($this);

        $this->results = $cycleCount->processDeleteCycleCount($cycleIDs);
    }

    /*
    ****************************************************************************
    */

    public function updateShippedCartonsAppJSONController()
    {
        $isAdmin = users\groups::commonGroupLookUp($this, 'shpCtnAdmin');
        if (! $isAdmin) {
            return;
        }

        $order = $this->postVar('order');

        $cancelWave = $this->postVar('cancelWave', 'getDef');

        $shippedCartons = new orders\shippedCartons($this);

        if ($cancelWave) {
            logger::getFieldIDs('cartons', $this);
            $logID = logger::getLogID();

            $wavePicks = new tables\wavePicks($this);
            $wavePicks->clear([$order]);

            $shippedCartons->log($logID, $order);

            $this->results = $order;
        } else {
            $target = $this->postVar('target');
            $this->results = $shippedCartons->update($order, $target);
        }
    }

    /*
    ****************************************************************************
    */

    public function checkLocationCycleCountAppJSONController()
    {
        $recNum = $this->get['recNum'];
        $submitLocations = getDefault($this->get['locations']);

        $results = \common\receiving::getLocationCycleCount($this, $recNum,
                $submitLocations);

        $errors = [];

        foreach ($results as $location => $values) {
            $errors[] = '<br>At Location # <strong>' . $location . '</strong> '
                    . 'created by <strong>' . implode(', ', $values) . '</strong>';
        }

        if ($errors) {
            array_unshift($errors, 'RC Log cannot be created due to active '
                    . 'Cycle Count(s):');
        }

        $this->results['errors'] = $errors ? $errors : FALSE;
    }

    /*
    ****************************************************************************
    */

    public function submitAddOrEditOrdersAppJSONController()
    {
        $this->results = \common\orderChecks::submitAddOrEditOrders($this);
    }

    /*
    ****************************************************************************
    */

    function searchSKUAutoCompleteAppJSONController()
    {
        $upcs = new tables\upcs($this);
        $sku = getDefault($this->get['sku']);

        $this->results = $upcs->searchSKUAutoComplete($sku);
    }

    /*
    ****************************************************************************
    */

    public function processSearchSKUAppJSONController()
    {
        $cycleCount = new tables\cycleCount\cycleCount($this);
        $sku = getDefault($this->post['sku']);

        $this->results = $cycleCount->processSearchSKU($sku);
    }

    /*
    ****************************************************************************
    */

    function checkVolumeRatesAppJSONController()
    {
       $charge = new tables\customer\chargeCodeMaster($this);

       $results = $charge->checkVolumeRates($this->post);

       if (! $results) {
           return $this->results['warning'] = 'Volume need to set on the system'
               . ' before input rate';
       }

       $this->results['success'] = TRUE;
    }

    /*
    ****************************************************************************
    */

    public function resetPasswordAppJSONController()
    {
        $resetPassword = new tables\users\resetPassword($this);

        $this->results  = $resetPassword->resetPassword($this);
    }

    /*
    ****************************************************************************
    */

    function sccGetAppJSONController()
    {
        $this->results = sccItems::init($this)->jsonGet();
    }

    /*
    ****************************************************************************
    */

    function sccUpdateAppJSONController()
    {
        $container = seldatContainers::init([
            'upcs' => new tables\upcs($this),
            'upcCats' => new tables\inventory\upcsCategories($this)
        ]);

        $vars = models\vars::init()->set('containers', $container);

        $this->results = sccItems::init($this, $vars)->jsonUpdate();
    }

    /*
    ****************************************************************************
    */

    function checkReceivingAppJSONController()
    {
        $receiving = new \tables\receiving($this);

        $this->results = $receiving->getReceivingData($this->get['receivingID']);
    }

    /*
   ****************************************************************************
   */

    public function updateUomCartonByPlateAppJSONController()
    {
        $licensePlateCarton = new tables\inventory\licensePlateCartons($this);

        $this->results = $licensePlateCarton->processUpdateNewUomCartons();
    }

    /*
    ****************************************************************************
    */

    function reqJWTAppJSONController()
    {
        $password = $this->postVar('password', 'getDef');
        $username = $this->postVar('username', 'getDef');

        if (! $username || ! $password) {
            $this->results = models\jwt::noAuth();
            return;
        }

        $token = models\jwt::init(appConfig::JWT_AUTH_SECRET)->token([
            'password' => $password,
            'fullName' => $username,
        ]);

        if (appConfig::JWT_TEST_MODE) {
            $this->results = $token;
            return;
        }

        $request = new curl\get([
            'url' => appConfig::SHORIFY_LISTENER,
            'post' => TRUE,
            'values' => ['token' => $token],
        ]);

        $this->results = [
            'response' => $request->result
        ];
    }

    /*
    ****************************************************************************
    */

    function apiAppJSONController()
    {
        $results = models\jwt::init(appConfig::JWT_AUTH_SECRET)
            ->validityResponse();

        $name = getDefault($results['data']['name']);
        $password = getDefault($results['data']['user_pwd']);
        $pwd = md5($password);

        if (! $name || ! $password) {
            $this->results = $results;
            return;
        }

        $userData = access::getUserInfo([
            'db' => $this,
            'term' => $name,
            'search' => 'username',
        ]);

        if (getDefault($userData['username']) != $name || getDefault($userData['password']) != $pwd) {
            $this->results =  models\jwt::noValid();
            return;
        }

        $this->results = tables\api\containers::init($this)->filter();
    }

    /*
    ****************************************************************************
    */

    function processRequestAppJSONController()
    {
        $table = new \tables\inventory\requestChangeStatus($this);

        $type = getDefault($this->post['type']);
        $reqDtlIDs = getDefault($this->post['reqDtlIDs']);

        if (! $reqDtlIDs) $this->results = 'Please select request item!';

        $this->results = $table->processRequest($type, $reqDtlIDs);
    }

    /*
    ****************************************************************************
    */
}

