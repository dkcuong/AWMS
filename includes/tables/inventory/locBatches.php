<?php

namespace tables\inventory;

use \common\logger;
use \tables\history;

class locBatches extends \tables\_default
{
    public $primaryKey = 'CONCAT(l.id, "-", b.id)';

    public $ajaxModel = 'inventory\\locBatches';

    public $fields = [
        'l.displayName' => [
            'display' => 'Location',
            'noEdit' => TRUE,
        ],
        'vendor' => [
            'display' => 'Client Name',
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'searcherDD' => 'vendors',
            'noEdit' => TRUE,
        ],
        'setDate' => [
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'orderBy' => 'co.recNum',
            'noEdit' => TRUE,
        ],
        'sku' => [
            'select' => 'u.sku',
            'display' => 'SKU',
            'update' => 'u.sku',
            'noEdit' => TRUE,
        ],
        'color1' => [
            'select' => 'u.color',
            'display' => 'Color',
            'noEdit' => TRUE,
        ],
        'size1' => [
            'select' => 'u.size',
            'display' => 'Size',
            'noEdit' => TRUE,
        ],
        'quantity' => [
            'select' => 'COUNT(c.id)',
            'display' => 'Actual Cartons',
            'noEdit' => TRUE,
            'groupedFields' => 'c.id'
        ],
        'totalPiecesRK' => [
            'select' => 'SUM(IF (s.shortName = "RK", uom, 0))',
            'display' => 'Total Pieces (RK)',
            'groupedFields' => 'uom'
        ],
        'totalPiecesIN' => [
            'select' => 'SUM(IF (s.shortName = "IN", uom, 0))',
            'display' => 'Total Pieces (IN)',
            'noEdit' => TRUE,
            'groupedFields' => 'uom'
        ],
    ];

    public $where = 'NOT l.isShipping
                 AND NOT c.isSplit
                 AND NOT c.unSplit
                 AND NOT l.isShipping
                 AND s.shortName IN ("IN", "RK")';

    public $groupBy = 'l.id, b.id';

    const CARTON_LOGGER_VALUE = 'cartons';
    const MSG_NO_FOUND_LOCATION_BATCH = 'Location Batch is not found';
    const MSG_TOTAL_PIECES_IS_ZERO = 'Can not update when totalPieces is zero';
    const MSG_NEW_PIECES_BIGGER = 'Can not update when Pieces is bigger';
    const MSG_NEW_PIECES_INVALID = 'New total pieces is Invalid';
    const MSG_LOCATION_BATCH_BLANK = 'Location Or Batch not NULL';
    const FIELD_TOTALPIECES_RK = 'totalPiecesRK';
    const CASE_SET_INACTIVE_LOCATION_BATCH = -1;
    const STATUS_ACTIVE_TALLY_CARTON = 1;

    private $errorMsg = '';


    /*
    ****************************************************************************
    */

    function table()
    {
        return 'inventory_cartons c
            JOIN    locations l ON l.id = c.locID
            JOIN    inventory_batches b ON b.id = c.batchID
            JOIN    upcs u ON b.upcId = u.id
            JOIN    statuses s ON s.id = c.statusID
            JOIN    inventory_containers co ON co.recNum = b.recNum
            JOIN    vendors v ON v.id = co.vendorID
            JOIN    warehouses w ON w.id = v.warehouseID';
    }

    /*
    ****************************************************************************
    */

    function updateLocationBatch($params)
    {
        $columnID = $params['columnID'];
        $arrID = $params['arrID'];
        $value = $params['value'];

        //set inActive for row selected
        if (self::CASE_SET_INACTIVE_LOCATION_BATCH == $columnID) {
            return $this->setInactive($arrID);
        }

        $isValidDataPiecesUpdate = $this->isValidDataPiecesUpdate([
            'columnID' => $columnID,
            'arrID' => $arrID,
            'value' => $value
        ]);

        if (! $isValidDataPiecesUpdate) {
            echo $this->errorMsg;
            return FALSE;
        }

        list($locationID, $batchID) = explode('-', $arrID);

        $result = $this->updateTotalPieces($locationID, $batchID, $value);

        if (! $result) {
            echo $this->errorMsg;
        }
        return $result;
    }

    /*
    ****************************************************************************
    */

    function updateTotalPieces($locationID, $batchID, $value)
    {
        $result = TRUE;
        $statusIDs = $this->getInventoryStatuses([
            cartons::STATUS_INACTIVE,
            cartons::STATUS_RACKED
        ]);

        $cartons = new cartons($this->app);

        $data = $this->getTotalPeciesOfLocationBatch($locationID, $batchID);
        //check invalid location batch
        $isValidData = $this->checkValidateLocationBatchPecies($data, $value);

        if (! $isValidData) {
            return FALSE;
        }

        $totalPieces = $data['totalPieces'];
        //no change
        if ($totalPieces == $value) {
            return TRUE;
        }

        $params = [
            'locationID' => $locationID,
            'batchID' => $batchID,
            'totalPieces' => $totalPieces,
            'newValue' => $value,
            'statusIDs' => $statusIDs,
            'cartons' => $cartons,
        ];

        //old > new
        if ($totalPieces > $value) {
            $result = $this->updateTotalPiecesSmaller($params);
        } else {
            $result = $this->updateTotalPiecesBigger($params);
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getLatestTallyCarton($cartonID)
    {
        $sql = 'SELECT  rowID
                FROM    tally_cartons
                WHERE   invID = ?
                LIMIT   0, 1';

        $result = $this->app->queryResult($sql, [$cartonID]);

        return $result['rowID'];
    }

    /*
    ****************************************************************************
    */

    function addTallyCarton($tally, $cartonID)
    {
        $sql = 'INSERT INTO tally_cartons (
                    rowID,
                    invID,
                    active
                ) VALUE (?, ?, ?)';

        $result = $this->app->runQuery($sql, [
            $tally,
            $cartonID,
            self::STATUS_ACTIVE_TALLY_CARTON
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function setInactive($arrID)
    {
        $isValidLocBatch = $this->isValidLocBatch($arrID);

        if (! $isValidLocBatch) {
            return FALSE;
        }

        $statusIDs = $this->getInventoryStatuses([
            cartons::STATUS_INACTIVE,
            cartons::STATUS_RACKED
        ]);

        if (! $statusIDs) {
            return FALSE;
        }

        $inStatusID = $statusIDs[cartons::STATUS_INACTIVE]['id'];
        $rackStatusID = $statusIDs[cartons::STATUS_RACKED]['id'];

        //begin transaction
        $this->app->beginTransaction();

        foreach ($arrID as $row) {

            list($locID, $batchID) = explode('-', $row);

            $this->updateStatusCartonByLocBatch([
                'inStatusID' => $inStatusID,
                'locID' => $locID,
                'batchID' => $batchID,
                'rackStatusID' => $rackStatusID,
            ]);
        }

        //end transaction
        $this->app->commit();

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function getTotalPeciesOfLocationBatch($locationID, $batchID)
    {
        //get current totalPieces
        $sql = 'SELECT  SUM(uom) totalPieces
                FROM    inventory_cartons c
                JOIN    inventory_batches b ON b.id = c.batchID
                JOIN    locations l ON l.id = c.locID
                JOIN    statuses s ON s.id = c.statusID
                WHERE   l.id = ?
                AND     b.id = ?
                AND     NOT l.isShipping
                AND     NOT c.isSplit
                AND     NOT c.unSplit
                AND     NOT l.isShipping
                AND     s.shortName = ?';

        $result = $this->app->queryResult($sql, [
            $locationID,
            $batchID,
            cartons::STATUS_RACKED
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function updateTotalPiecesSmaller($params)
    {
        $locationID = $params['locationID'];
        $batchID = $params['batchID'];
        $totalPieces = $params['totalPieces'];
        $newValue = $params['newValue'];
        $statusIDs = $params['statusIDs'];
        $cartonsClass = $params['cartons'];

        $inactiveStatus = cartons::STATUS_INACTIVE;
        $rackedStatus = cartons::STATUS_RACKED;

        $inactiveStatusID = $statusIDs[$inactiveStatus]['id'];
        $rackedStatusID = $statusIDs[$rackedStatus]['id'];

        //calc diff
        $diff = $totalPieces - $newValue;

        //get cartons in (locationID + batchID)
        $cartons = $this->getCartonsInLocationBatch($locationID, $batchID,
                $rackedStatusID);

        if (! $cartons) {

            $this->errorMsg = sprintf('No found carton on locationID: %s, '
                . 'batchID: %s', $locationID, $batchID);

            return FALSE;
        }

        $smallerDiff = $equalDiff = $biggerDiff = [];

        foreach ($cartons as $invID => $carton) {

            $updateStatusParams = [
                'invID' => $invID,
                'oldStatusID' => $carton['statusID'],
                'newStatusID' => $inactiveStatusID,
            ];

            if ($carton['uom'] < $diff) {
                //case: $carton['uom'] < $diff (update carton's status to IN)
                $diff -= $carton['uom'];

                $smallerDiff[$invID] = [
                    'foreignKeysData' => history::getForeignKeysData([
                        'model' => $cartonsClass,
                        'field' => 'statusID',
                        'rowID' => $invID,
                        'toValue' => $inactiveStatusID,
                        'fromValue' => $carton['statusID'],
                    ]),
                    'cartons' => $cartonsClass,
                ];

                $smallerDiff[$invID] = $smallerDiff[$invID] + $updateStatusParams;

                continue;
            }

            if ($carton['uom'] == $diff) {
                $equalDiff[$invID] = $updateStatusParams;
            } elseif ($carton['uom'] > $diff) {

                $results = $this->uomBiggerDiffPrepare([
                    'carton' => $carton,
                    'batchID' => $batchID,
                    'diff' => $diff,
                    'cartons' => $cartonsClass,
                ]);

                if ($results['error']) {

                    $this->errorMsg = implode('<br>', $results['error']);

                    return FALSE;
                }

                $biggerDiff[$invID] = [
                    'combined' => $results['combined'],
                    'diff' => $diff,
                ];

                $biggerDiff[$invID]['latestTally'] =
                        $this->getLatestTallyCarton($invID);

                $biggerDiff[$invID] = $biggerDiff[$invID] + $updateStatusParams;
            }

            break;
        }

        // Get the carton logger field keys before the transaction is started
        logger::getFieldIDs(self::CARTON_LOGGER_VALUE, $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        foreach ($smallerDiff as $invID => $values) {
            $this->processUomSmallerDiff($values);
        }

        foreach ($equalDiff as $invID => $values) {
            $this->processUomEqualDiff($values);
        }

        foreach ($biggerDiff as $invID => $values) {
            $this->processUomBiggerDiff($values);
        }

        $this->app->commit();

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function updateTotalPiecesBigger($params)
    {
        $locationID = $params['locationID'];
        $batchID = $params['batchID'];
        $totalPieces = $params['totalPieces'];
        $newValue = $params['newValue'];
        $cartons = $params['cartons'];

        $statusIDs = $params['statusIDs'];
        $statusIDRacked = $statusIDs[cartons::STATUS_RACKED]['id'];

        $diff = $newValue - $totalPieces;

        $logFields = $cartons->getAddCartonsToBatchLogFields();

        $nextCarton = $this->getBatchNextCartonID($batchID);

        if (! $nextCarton) {
            $this->errorMsg = 'Could not get Max Carton ID of BatchID: ' 
                    . $batchID;
            return FALSE;
        }

        $cartonLatest = $this->getRowCartonLatestOfBatch($locationID, $batchID,
                $statusIDRacked);

        if (! $cartonLatest) {
            $this->errorMsg = 'Could not get Row Carton latest of BatchID: ' 
                    . $batchID;
            return FALSE;
        }

        $tallyCartonLatest = $this->getLatestTallyCarton($cartonLatest['id']);

        $nextInvID = $cartons->getNextID('inventory_cartons');

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        logger::startAdd($cartons);

        $this->app->beginTransaction();

        $batchData = [
            'batchID' => $batchID,
            'uom' => $diff,
            'plate' => $cartonLatest['plate'],
            'locID' => $locationID,
            'mLocID' => $locationID,
            'cartonID' => $nextCarton['nextCartonID'],
            'statusID' => $statusIDRacked,
            'mStatusID' => $statusIDRacked
        ];

        $this->insertNextCartonForLocationBatch($batchData);

        logger::add($this->app);

        $tallyCartonLatest &&
                $this->addTallyCarton($tallyCartonLatest, $nextInvID);

        foreach ($logFields as $logField) {
            $logData[$logField] = [
                'fromValues' => 0,
                'toValues' => array_fill(0, 1, $batchData[$logField]),
            ];
        }

        logger::edit([
            'db' => $this->app,
            'primeKeys' => $nextInvID,
            'fields' => $logData,
            'transaction' => FALSE,
        ]);

        $this->app->commit();

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function getCartonsInLocationBatch($locationID, $batchID, $rackedStatusID)
    {
        $sql = 'SELECT    ca.id,
                          uom,
                          cartonID,
                          statusID,
                          vendorID
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      locations l ON l.id = ca.locID
                WHERE     locID = ?
                AND       batchID = ?
                AND       NOT isSplit
                AND       NOT unSplit
                AND       NOT isShipping
                AND       statusID = ?
                ORDER BY  cartonID DESC';

        $result = $this->app->queryResults($sql, [
            $locationID,
            $batchID,
            $rackedStatusID
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function updateAndLogCartonNewStatusID($data)
    {
        $invID = $data['invID'];
        $oldStatusID = $data['oldStatusID'];
        $newStatusID = $data['newStatusID'];

        $sql = 'UPDATE  inventory_cartons
                SET     statusID = ?,
                        mStatusID = ?
                WHERE   id = ?';

        $this->app->runQuery($sql, [$newStatusID, $newStatusID, $invID]);

        logger::edit([
            'db' => $this->app,
            'primeKeys' => $invID,
            'fields' => [
                'statusID' => [
                    'fromValues' => $oldStatusID,
                    'toValues' => $newStatusID,
                ],
            ],
            'transaction' => FALSE,
        ]);
    }

    /*
    ****************************************************************************
    */

    function splitCartonTotalPiecesSmaller($params)
    {
        $carton = $params['carton'];
        $batchID = $params['batchID'];
        $sub = $params['sub'];
        $diff = $params['diff'];
        $cartons = $params['cartons'];

        $ucc = $carton['vendorID'] . $batchID .
                str_pad($carton['uom'], 3, '0', STR_PAD_LEFT) .
                str_pad($carton['cartonID'], 4, '0', STR_PAD_LEFT);

        $results = $cartons->split([
            $ucc => [$sub, $diff]
        ]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function processUomSmallerDiff($params)
    {
        $this->updateAndLogCartonNewStatusID($params);

        history::addUpdate([
            'model' => $params['cartons'],
            'foreignKeysData' => $params['foreignKeysData'],
            'transaction' => FALSE,
        ]);
    }

    /*
    ****************************************************************************
    */

    function uomBiggerDiffPrepare($params)
    {
        $carton = $params['carton'];
        $batchID = $params['batchID'];
        $diff = $params['diff'];
        $cartons = $params['cartons'];

        $sub = $carton['uom'] - $diff;

        $results = $this->splitCartonTotalPiecesSmaller([
            'carton' => $carton,
            'batchID' => $batchID,
            'sub' => $sub,
            'diff' => $diff,
            'cartons' => $cartons,
        ]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function processUomBiggerDiff($data)
    {
        $combined = $data['combined'];
        $diff = $data['diff'];
        $latestTally = getDefault($data['latestTally']);

        $update = FALSE;

        foreach ($combined as $children) {
            foreach ($children as $child) {

                $invID = $child['invID'];

                if ($child['uom'] == $diff && ! $update) {
                    // make inactive only the first split carton (in case of
                    // child cartons have the same uom, e.g. parent: 6 = 3 + 3)
                    $data['invID'] = $invID;

                    $this->updateAndLogCartonNewStatusID($data);

                    $update = TRUE;

                    continue;
                }

                $latestTally && $this->addTallyCarton($latestTally, $invID);
            }
        }
    }

    /*
    ****************************************************************************
    */

    function processUomEqualDiff($data)
    {
        $this->updateAndLogCartonNewStatusID($data);
    }

    /*
    ****************************************************************************
    */

    function getInventoryStatuses($arrStatusName)
    {
        $statusInventory = new \tables\statuses\inventory($this->app);

        $statusIDs = $statusInventory->getStatusIDs($arrStatusName);

        return $statusIDs;
    }

    /*
    ****************************************************************************
    */

    function checkValidateLocationBatchPecies($data, $newValue)
    {
        if (! $data) {
            $this->errorMsg = self::MSG_NO_FOUND_LOCATION_BATCH;
            return FALSE;
        }

        $totalPieces = $data['totalPieces'];

        if (! $totalPieces) {
            $this->errorMsg = self::MSG_TOTAL_PIECES_IS_ZERO;
            return FALSE;
        }

        //add more pieces < 999 (Max number pieces of one Carton)
        $tmpMaxPieces = $totalPieces + cartons::MAX_CARTON_ID;
        if ($tmpMaxPieces < $newValue) {
            $this->errorMsg = self::MSG_NEW_PIECES_BIGGER;
            return FALSE;
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function getBatchNextCartonID($batchID)
    {
        $sql = 'SELECT  MAX(cartonID) + 1 AS nextCartonID
                FROM    inventory_cartons
                WHERE   batchID = ?';

        $result = $this->app->queryResult($sql, [$batchID]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getRowCartonLatestOfBatch($locationID, $batchID, $statusIDRacked)
    {
        //get tallyRow cartonLatest
        $sql = 'SELECT    ca.id,
                          ca.plate
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      locations l ON l.id = ca.locID
                WHERE     l.id = ?
                AND       b.id = ?
                AND       NOT isSplit
                AND       NOT unSplit
                AND       NOT l.isShipping
                AND       statusID = ?
                ORDER BY  cartonID DESC
                LIMIT     1';

        $result = $this->app->queryResult($sql, [
            $locationID,
            $batchID,
            $statusIDRacked
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function insertNextCartonForLocationBatch($params)
    {
        $sql = 'INSERT INTO inventory_cartons (
                    batchID,
                    uom,
                    plate,
                    locID,
                    mLocID,
                    cartonID,
                    statusID,
                    mStatusID
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?
                )';

        $this->app->runQuery($sql, [
                $params['batchID'],
                $params['uom'],
                $params['plate'],
                $params['locID'],
                $params['mLocID'],
                $params['cartonID'],
                $params['statusID'],
                $params['mStatusID'],
            ]
        );
    }

    /*
    ****************************************************************************
    */

    function updateStatusCartonByLocBatch($params)
    {
        $locID = $params['locID'];
        $batchID = $params['batchID'];
        $inStatusID = $params['inStatusID'];
        $rackStatusID = $params['rackStatusID'];

        $sql = 'UPDATE  inventory_cartons
                SET     statusID = ?,
                        mStatusID = ?
                WHERE   locID = ?
                AND     batchID = ?
                AND     statusID = ?';

        $sqlParams = [
            $inStatusID,
            $inStatusID,
            $locID,
            $batchID,
            $rackStatusID
        ];

        $result = $this->app->runQuery($sql, $sqlParams);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function isValidLocBatch($arrLocBatch)
    {
        if (! $arrLocBatch) {
            $this->errorMsg = 'Location & Batch is not blank.';
            return FALSE;
        }

        if (! is_array($arrLocBatch)) {
            $this->errorMsg = 'Location & Batch is invalid.';
            return FALSE;
        }

        foreach($arrLocBatch as $row) {
            if (! $row) {
                $this->errorMsg = 'Location & Batch is invalid.';
                return FALSE;
            }

            $idLocationBatch = explode('-', $row);

            if (! $idLocationBatch) {
                $this->errorMsg = self::MSG_LOCATION_BATCH_BLANK;
                return FALSE;
            }

            if (! ($idLocationBatch[0] && $idLocationBatch[1])) {
                $this->errorMsg = 'Data is invalid.';
                return FALSE;
            }
       }

       return TRUE;
    }

    /*
    ****************************************************************************
    */

    function isValidDataPiecesUpdate($params)
    {
        $arrID = $params['arrID'];
        $columnID = $params['columnID'];
        $value = $params['value'];

        $isValidData = $this->isValidLocBatch([$arrID]);

        if (! $isValidData) {
            return FALSE;
        }

        $fieldIDs = array_keys($this->fields);
        $field = getDefault($fieldIDs[$columnID]);

        if (self::FIELD_TOTALPIECES_RK != $field) {
            return FALSE;
        }

        if (! (is_numeric($value) && $value > 0)) {
            echo self::MSG_NEW_PIECES_INVALID;
            return FALSE;
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */
}
