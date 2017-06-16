<?php

namespace tables\inventory;

use excel\exporter;
use \format\nonUTF;
use models\config;
use \tables\statuses;
use \common\logger;
use \common\order;
use \common\scanner;
use \common\tally;

class cartons extends \tables\_default
{
    const MAX_CARTON_ID = 999;

    const TRANSFER_UOM = 1;

    const INV_ORDERS_TABLE = 'inventory_cartons ca
           JOIN   inventory_batches b ON b.id = ca.batchID
           JOIN   inventory_containers co ON co.recNum = b.recNum';

    const STATUS_INACTIVE = 'IN';

    const STATUS_RECEIVED = 'RC';

    const STATUS_RACKED = 'RK';

    const STATUS_RESERVED = 'RS';

    const STATUS_PICKED = 'PK';

    const STATUS_ORDER_PROCESSING = 'OP';

    const STATUS_SHIPPING = 'LS';

    const STATUS_SHIPPED = 'SH';

    const STATUS_DISCREPANCY = 'DS';

    const STATUS_LOCKED = 'LK';

    public $primaryKey = 'ca.id';

    public $ajaxModel = 'inventory\\cartons';

    public $fields = [
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'noEdit' => TRUE,
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'name' => [
            'select' => 'co.name',
            'display' => 'Container',
            'noEdit' => TRUE,
        ],
        'containerRecNum' => [
            'select' => 'b.recNum',
            'display' => 'Receiving Number',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches b',
        ],
        'measureID' => [
            'select' => 'm.displayName',
            'display' => 'Measurement System',
            'searcherDD' => 'inventory\\measure',
            'ddField' => 'displayName',
            'noEdit' => TRUE,
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'noEdit' => TRUE,
        ],
        'setDate' => [
            'select' => 'co.setDate',
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'orderBy' => 'ca.id',
            'noEdit' => TRUE,
        ],
        'batchID' => [
            'display' => 'Batch Number',
            'noEdit' => TRUE,
            'acTable' => 'inventory_cartons',
        ],
        'sku' => [
            'select' => 'p.sku',
            'batchFields' => TRUE,
            'display' => 'Style Number',
            'noEdit' => TRUE,
            'acTable' => 'upcs p',
        ],
        'uom' => [
            'batchFields' => TRUE,
            'select' => 'LPAD(UOM, 3, 0)',
            'display' => 'UOM',
            'update' => 'UOM',
            'acTable' => 'inventory_cartons',
        ],
        'prefix' => [
            'batchFields' => TRUE,
            'display' => 'Prefix',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'suffix' => [
            'select' => 'suffix',
            'batchFields' => TRUE,
            'display' => 'Suffix',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'height' => [
            'batchFields' => TRUE,
            'display' => 'Height',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'width' => [
            'batchFields' => TRUE,
            'display' => 'Width',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'length' => [
            'batchFields' => TRUE,
            'display' => 'Length',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'weight' => [
            'batchFields' => TRUE,
            'display' => 'Weight',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'eachHeight' => [
            'batchFields' => TRUE,
            'display' => 'Each-Height',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'eachWidth' => [
            'batchFields' => TRUE,
            'display' => 'Each-Width',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'eachLength' => [
            'batchFields' => TRUE,
            'display' => 'Each-Length',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'eachWeight' => [
            'batchFields' => TRUE,
            'display' => 'Each-Weight',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'upcID' => [
            'select' => 'p.upc',
            'batchFields' => TRUE,
            'display' => 'UPC',
            'noEdit' => TRUE,
            'acTable' => 'upcs p',
        ],
        'size1' => [
            'select' => 'size',
            'batchFields' => TRUE,
            'display' => 'Size',
            'noEdit' => TRUE,
            'acTable' => 'upcs',
        ],
        'color1' => [
            'select' => 'color',
            'batchFields' => TRUE,
            'display' => 'Color',
            'noEdit' => TRUE,
            'acTable' => 'upcs',
        ],
        'initialCount' => [
            'batchFields' => TRUE,
            'display' => 'Initial Count of Cartons',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'cartonID' => [
            'display' => 'Carton ID',
            'noEdit' => TRUE,
            'acTable' => 'inventory_cartons c',
        ],
        'ucc128' => [
            'select' => 'CONCAT(co.vendorID,
                            b.id,
                            LPAD(ca.uom, 3, 0),
                            LPAD(ca.cartonID, 4, 0)
                        )',
            'customClause' => TRUE,
            'display' => 'UCC128',
            'noEdit' => TRUE,
            'acDisabled' => TRUE,
        ],
        'orderID' => [
            'select' => 'n.scanordernumber',
            'display' => 'Order Number',
            'isNum' => 10,
            'allowNull' => TRUE,
            'update' => 'orderID',
            'updateOverwrite' => TRUE,
            'updateTable' => 'neworder',
            'updateField' => 'scanordernumber',
            'acTable' => 'neworder n',
        ],
        'plate' => [
            'display' => 'License Plate',
            'isNum' => 8,
            'allowNull' => TRUE,
            'noEdit' => TRUE,
            'acTable' => 'inventory_cartons c',
        ],
        'locID' => [
            'select' => 'l.displayName',
            'display' => 'Location',
            'update' => 'locID',
            'updateOverwrite' => TRUE,
            'updateTable' => 'locations',
            'updateField' => 'displayName',
            'acTable' => 'locations l',
        ],
        'mLocID' => [
            'select' => 'lm.displayName',
            'display' => 'Manual Location',
            'update' => 'mLocID',
            'updateOverwrite' => TRUE,
            'updateTable' => 'locations',
            'updateField' => 'displayName',
            'acTable' => 'locations lm',
        ],
        'statusID' => [
            'select' => 's.shortName',
            'display' => 'Status',
            'noEdit' => TRUE
        ],
        'mStatusID' => [
            'select' => 'sm.shortName',
            'display' => 'Manual Status',
            'noEdit' => TRUE
        ],
        'created_at' => [
            'ignoreSearch' => true,
            'noEdit' => TRUE
        ]
    ];

    public $where = 'NOT isSplit
        AND       NOT unSplit';

    public $displaySingle = 'Carton';

    public $baseTable = 'inventory_containers co
                         JOIN  inventory_batches b ON co.recNum = b.recNum
                         JOIN  inventory_cartons ca ON b.id = ca.batchID
                         JOIN  vendors v ON v.id = co.vendorID
                         JOIN  upcs u ON u.id = b.upcID
                         JOIN  statuses s ON s.id = ca.statusID
                         JOIN  statuses sm ON sm.id = ca.mStatusID';

    public $mainTable = 'inventory_cartons';

    public $mainField = 'ucc128';

    public $measurements = [
        'length' => [
            'convert' => 0.3937,
            'metric' => [
                'min' => 5,
                'max' => 120,
                'unit' => 'cm'
            ],
            'imperial' => [
                'min' => 2,
                'max' => 48,
                'unit' => 'in'
            ],
        ],
        'width' => [
            'convert' => 0.3937,
            'metric' => [
                'min' => 5,
                'max' => 120,
                'unit' => 'cm'
            ],
            'imperial' => [
                'min' => 2,
                'max' => 48,
                'unit' => 'in'
            ],
        ],
        'height' => [
            'convert' => 0.3937,
            'metric' => [
                'min' => 5,
                'max' => 150,
                'unit' => 'cm'
            ],
            'imperial' => [
                'min' => 2,
                'max' => 60,
                'unit' => 'in'
            ],
        ],
        'weight' => [
            'convert' => 2.2046,
            'metric' => [
                'min' => 0.1,
                'max' => 40,
                'unit' => 'kg'
            ],
            'imperial' => [
                'min' => 0.125,
                'max' => 80,
                'unit' => 'lb'
            ],
        ],
    ];

    /*
    ****************************************************************************
    */

    function __construct($app = FALSE)
    {
        \common\vendor::addConditionByVendor($app, $this);

        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */


    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'inventory_containers co
                JOIN      inventory_batches b ON co.recNum = b.recNum
                JOIN      inventory_cartons ca ON b.id = ca.batchID
                JOIN      vendors v ON v.id = co.vendorID
                JOIN      warehouses w ON v.warehouseID = w.id
                JOIN      ' . $userDB . '.info u ON u.id = co.userID
                LEFT JOIN locations l ON l.id = ca.locID
                LEFT JOIN locations lm ON lm.id = ca.mLocID
                LEFT JOIN neworder n ON n.id = ca.orderID
                JOIN      statuses s ON ca.statusID = s.id
                JOIN      statuses sm ON ca.mStatusID = sm.id
                LEFT JOIN statuses os ON os.id = n.statusID
                JOIN      upcs p ON p.id = b.upcID
                JOIN      measurement_systems m ON m.id = co.measureID';
    }

    /*
    ****************************************************************************
    */

    function cartonFieldsOnly()
    {
        return [
            'batchID' => [
                'display' => 'Batch Number',
                'noEdit' => TRUE,
            ],
            'cartonID' => [
                'display' => 'Carton ID',
                'noEdit' => TRUE,
            ],
            'uom' => [
                'batchFields' => TRUE,
                'select' => 'LPAD(uom, 3, 0)',
                'display' => 'UOM',
                'noEdit' => TRUE,
            ],
            'plate' => [
                'display' => 'License Plate',
                'isNum' => 8,
                'allowNull' => TRUE,
            ],
            'locID' => [
                'select' => 'l.displayName',
                'display' => 'Location',
            ],
            'mLocID' => [
                'select' => 'lm.displayName',
                'display' => 'Manual Location',
            ],
            'orderID' => [
                'select' => 'n.scanordernumber',
                'display' => 'Order Number',
                'isNum' => 10,
                'allowNull' => TRUE,
            ],
            'statusID' => [
                'select' => 'ca.statusID',
                'display' => 'Status',
            ],
            'mStatusID' => [
                'select' => 'ca.mStatusID',
                'display' => 'Manual Status',
            ],
            'vendorCartonID' => [
            ],
        ];

    }

    /*
    ****************************************************************************
    */

    function masterResults($numberToCheck, $scanArray=FALSE)
    {
        $master = [
            'batchNumber' => [],
            'positionInList' => [],
        ];

        $masterLabels = [];

        if ($numberToCheck) {

            $scans = $scanArray ? $scanArray : $numberToCheck;

            $barcodes = array_values($numberToCheck);

            $masterCodes = $this->getMasterCode($barcodes);

            foreach ($scans as $listNumber => $scanValue) {
                if (! is_array($scanValue) && isset($masterCodes[$scanValue])) {
                    $master['batchNumber'][] =
                        $masterCodes[$scanValue]['batchnumber'];
                    $master['positionInList'][] = $listNumber;

                    $masterLabels[$listNumber] = $scanValue;
                }
            }
        }

        return [
            'master' => $master,
            'masterLabels' => $masterLabels
        ];
    }

    /*
    ****************************************************************************
    */

    function getMasterCode($barcodes)
    {
        $qMarks = $this->app->getQMarkString($barcodes);

        $sql = 'SELECT    barcode,
                          batchnumber
                FROM      masterlabel
                WHERE     barcode IN (' . $qMarks . ')';

        $result = $this->app->queryResults($sql, $barcodes);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function masterUCCs($batchNumbers)
    {
        if (! $batchNumbers) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($batchNumbers);

        $ucc128 = $this->fields['ucc128']['select'];

        $sql = 'SELECT  ' . $ucc128 . ',
                        ca.id AS invID,
                        batchID
                FROM    inventory_cartons ca
                JOIN    inventory_batches b ON b.id = ca.batchID
                JOIN    inventory_containers co ON co.recNum = b.recNum
                WHERE   batchId IN (' . $qMarks . ')
                AND     NOT isSplit
                AND     NOT unSplit';

        $result = $this->app->queryResults($sql, $batchNumbers);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function masterLabelToCarton($scanArray)
    {
        $scanArray = array_values($scanArray);

        // Check for old uccs from before the Shalom merge
        $params = [];

        foreach ($scanArray as $scan) {

            $intVal = (float)$scan;

            if ($intVal) {
                $params[] = $scan;
            }
        }

        if (! $params) {
            return [];
        }

        $oldUCCs = $this->getOldUCCs($params);

        foreach ($scanArray as $id => $oldUCC) {
            if (isset($oldUCCs[$oldUCC])) {
                $scanArray[$id] = $oldUCCs[$oldUCC]['newUCC'];
            }
        }

        $masterLabelResults =
            \labels\create::getNewTallyLabelsCartons($this->app, $scanArray);
        // Check if tally batches are in the scan
        $returnScanArray = $scanArray = $masterLabelResults['uccs'];

        $numberToCheck = [];

        foreach ($scanArray as $key => $scanNumber) {
            if (! is_array($scanNumber) && strlen($scanNumber) == 20) {
                $numberToCheck[$key] = $scanNumber;
            }
        }

        $results = $this->masterResults($numberToCheck, $scanArray);

        $master = $results['master'];

        $numberToCheckKeys = array_flip($numberToCheck);
        // remove masterLabels from a list of invalis UCCs
        foreach ($results['masterLabels'] as $masterLabel) {
            unset($numberToCheckKeys[$masterLabel]);
        }

        $numberToCheckValues = array_flip($numberToCheckKeys);

        $invalidUCCs = [];

        if ($numberToCheckValues) {

            $param = array_values($numberToCheckValues);

            $results = $this->getByUCC($param);

            foreach ($numberToCheckValues as $key => $ucc) {

                $invID = getDefault($results[$ucc]['id'], NULL);

                if ($invID) {
                    $returnScanArray[$key] = [
                        $invID => $ucc
                    ];

                    continue;
                }

                if (! isset($masterLabelResults['masterLabels'][$ucc])) {
                    $invalidUCCs[$key] = $ucc;
                }
            }
        }

        // add cartons submitted by Master Labels
        $uccsOriginal = $this->masterUCCs($master['batchNumber']);

        $eachBatch = [];
        foreach ($uccsOriginal as $ucc => $batch) {

            $invID = $batch['invID'];
            $batch = $batch['batchID'];

            $eachBatch[$batch][] = [
                $invID => $ucc
            ];
        }

        $indexIncrease = 0;

        foreach ($master['positionInList'] as $position) {
            $currentScan = $scanArray[$position];
            $currentBatch = substr($currentScan, 5, 8);

            //total cartons in current batch
            if (! isset($eachBatch[$currentBatch])) {
                continue;
            }

            $uccsInBatch = $eachBatch[$currentBatch];
            $cartonAmount = count($uccsInBatch);

            $newPosition = $position + $indexIncrease;
            array_splice($returnScanArray, $newPosition, 1, $uccsInBatch);
            $indexIncrease = $indexIncrease + $cartonAmount - 1;
	    }

        foreach ($invalidUCCs as $invalidUCC) {
            foreach ($returnScanArray as $key => $value) {
                if ($invalidUCC == $value) {
                    // remove invalid UCCs from a list of valid UCCs
                    unset($returnScanArray[$key]);
                    continue 2;
                }
            }
        }

        $validUCCData = $nonUCCData = [];

        foreach ($returnScanArray as $key => $value) {
            if (is_array($value)) {
                $validUCCData[] = reset($value);
            } else {
                $nonUCCData[] = $value;
            }
        }

        return [
            'returnScanArray' => $returnScanArray,
            'validUCCData' => $validUCCData,
            'invalidUCCs' => $invalidUCCs,
            'nonUCCData' => $nonUCCData,
            'masterLabels' => $masterLabelResults['masterLabels'],
        ];
    }

    /*
    ****************************************************************************
    */

    function getOldUCCs($params)
    {
        $qMarks = $this->app->getQMarkString($params);

        $sql = 'SELECT oldUCC,
                       newUCC
                FROM   inventory_merge_converse
                WHERE  oldUCC IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function processCheckCartonNumbers($licensePlates, $status, $found)
    {
        $missing = [];

        foreach ($licensePlates as $plate => $info) {
            foreach ($info['cartonNumbers'] as $cartonNumber) {
                if (! isset($found[$cartonNumber])) {
                    $missing[$plate][] = 'Carton number ' . $cartonNumber
                        . ' was not found in inventory';
                } else if ($found[$cartonNumber]['status'] == $status) {
                    $missing[$plate][] = 'Carton number ' . $cartonNumber
                        . ' has already been received';
                }
            }
        }

        return $missing;
    }

    /*
    ****************************************************************************
    */

    function getReceivedCartons($invIDs)
    {
        if (empty($invIDs)) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($invIDs);

        $sql = 'SELECT    ca.id
                FROM      inventory_cartons ca
                JOIN      statuses s ON ca.statusID = s.id
                WHERE     ca.id IN (' . $qMarks . ')
                AND       shortName = ?
                ';

        $params = $invIDs;

        $params[] = self::STATUS_RECEIVED;

        $results = $this->app->queryResults($sql, $params);

        return array_keys($results);
    }

    /*
    ********************************************************************************
    */

    function getContainersByUCCs($cartonNumbers)
    {
        if (! $cartonNumbers) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($cartonNumbers);

        $sql = 'SELECT    DISTINCT recNum
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                WHERE     ca.id IN (' . $qMarks .')
                ';

        $result = $this->app->queryResults($sql, $cartonNumbers);

        return $result;
    }

    /*
    ********************************************************************************
    */

    function receivingToStock($licensePlates, $status='RC', $invIDs=[])
    {
        // If status is RC, set location to NULL
        $receiving = $status == self::STATUS_RECEIVED;

        if (! $invIDs) {
            foreach ($licensePlates as $licensePlate => $info) {

                $orderInvIDs = scanner::getInvIDs($info['cartonNumbers']);

                $invIDs = array_merge($invIDs, $orderInvIDs);
            }
        }

        $oldCartonInfo = $this->getOldCartonInfo($invIDs);

        $statuses = new \tables\statuses\inventory($this->app);

        $statusID = $statuses->getStatusID($status);

        $updateLocations = $receiving ? 'locID = NULL, mLocID = NULL,' : NULL;

        $updateSQL = 'UPDATE inventory_cartons
                      SET    '.$updateLocations.'
                             plate = ?,
                             statusID = ?,
                             mStatusID = ?
                      WHERE  id = ?';

        $insertSQL = 'INSERT INTO inventory_control (
                            licenseplate,
                            status,
                            inventoryID
                      ) VALUES (
                            ?, "' . self::STATUS_RECEIVED . '", ?
                      )';

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        $inventory = $oldMStatuses = $oldStatuses = $oldPlates =  $newStatuses =
            $newPlates = [];

        foreach ($licensePlates as $licensePlate => $info) {
            foreach ($info['cartonNumbers'] as $cartonData) {

                $inventory[] = $invID = key($cartonData);

                $oldPlates[] = $oldCartonInfo[$invID]['plate'];
                $oldStatuses[] = $oldCartonInfo[$invID]['statusID'];
                $oldMStatuses[] = $oldCartonInfo[$invID]['mStatusID'];

                $newPlates[] = $licensePlate;
                $newStatuses[] = $statusID;

                $params = [$licensePlate, $statusID, $statusID, $invID];
                $this->app->runQuery($updateSQL, $params);

                $this->app->runQuery($insertSQL, [$licensePlate, $invID]);
            }
        }

        logger::edit([
            'db' => $this->app,
            'primeKeys' => $inventory,
            'fields' => [
                'plate' => [
                    'fromValues' => $oldPlates,
                    'toValues' => $newPlates,
                ],
                'statusID' => [
                    'fromValues' => $oldStatuses,
                    'toValues' => $newStatuses,
                ],
                'mStatusID' => [
                    'fromValues' => $oldMStatuses,
                    'toValues' => $newStatuses,
                ],
            ],
            'transaction' => FALSE,
        ]);

        $this->app->commit();
    }

    /*
    ********************************************************************************
    */

    function updateReceiveStock($licensePlates, $status='RC', $invIDs=[])
    {
        if (! $invIDs) {
            foreach ($licensePlates as $licensePlate => $info) {

                $orderInvIDs = scanner::getInvIDs($info['cartonNumbers']);

                $invIDs = array_merge($invIDs, $orderInvIDs);
            }
        }

        $oldCartonInfo = $this->getOldCartonInfo($invIDs);

        $statuses = new \tables\statuses\inventory($this->app);

        $statusID = $statuses->getStatusID($status);

        $updateSQL = 'UPDATE inventory_cartons
                      SET    plate = ?,
                             statusID = ?,
                             mStatusID = ?
                      WHERE  id = ?';

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        $inventory = $oldMStatuses = $oldStatuses = $oldPlates =  $newStatuses =
            $newPlates = [];

        foreach ($licensePlates as $licensePlate => $info) {
            foreach ($info['cartonNumbers'] as $cartonData) {

                $inventory[] = $invID = key($cartonData);

                $oldPlates[] = $oldCartonInfo[$invID]['plate'];
                $oldStatuses[] = $oldCartonInfo[$invID]['statusID'];
                $oldMStatuses[] = $oldCartonInfo[$invID]['mStatusID'];

                $newPlates[] = $licensePlate;
                $newStatuses[] = $statusID;

                $params = [$licensePlate, $statusID, $statusID, $invID];
                $this->app->runQuery($updateSQL, $params);
            }
        }

        logger::edit([
            'db' => $this->app,
            'primeKeys' => $inventory,
            'fields' => [
                'plate' => [
                    'fromValues' => $oldPlates,
                    'toValues' => $newPlates,
                ],
                'statusID' => [
                    'fromValues' => $oldStatuses,
                    'toValues' => $newStatuses,
                ],
                'mStatusID' => [
                    'fromValues' => $oldMStatuses,
                    'toValues' => $newStatuses,
                ],
            ],
            'transaction' => FALSE,
        ]);

        $this->app->commit();
    }

    /*
    ********************************************************************************
    */

    function getOldCartonInfo($invIDs, $fields=NULL)
    {
        if (! $invIDs) {
            return [];
        }

        $selectFields = $fields ? $fields : '
            plate,
            locID,
            mLocID,
            statusID,
            mStatusID
            ';

        $qMarks = $this->app->getQMarkString($invIDs);

        $sql = 'SELECT  id,
                        ' . $selectFields . '
                FROM    inventory_cartons
                WHERE   id IN (' . $qMarks . ')';

        $result = $this->app->queryResults($sql, $invIDs);

        return $result;
    }

    /*
    ********************************************************************************
    */

    function submitReceivingToStock($params)
    {
        $updateLocations = $params['updateLocations'];
        $licensePlates = $params['licensePlates'];
        $oldCartonInfo = $params['oldCartonInfo'];
        $statusID = $params['statusID'];

        $updateSQL = $this->makeUpdateSQLQuery($updateLocations);
        $insertSQL = $this->makeInsertSQLQuery();

        $oldMStatuses = $oldStatuses = $oldPlates =  $newStatuses = $newPlates = [];

        foreach ($licensePlates as $licensePlate => $info) {
            foreach ($info['cartonNumbers'] as $cartonData) {

                $inventory[] = $invID = key($cartonData);

                $oldPlates[] = $oldCartonInfo[$invID]['plate'];
                $oldStatuses[] = $oldCartonInfo[$invID]['statusID'];
                $oldMStatuses[] = $oldCartonInfo[$invID]['mStatusID'];

                $newPlates[] = $licensePlate;
                $newStatuses[] = $statusID;

                $params = [$licensePlate, $statusID, $statusID, $invID];
                $this->app->runQuery($updateSQL, $params);

                $this->app->runQuery($insertSQL, [$licensePlate, $invID]);
            }
        }

        logger::edit([
            'db' => $this->app,
            'primeKeys' => $inventory,
            'fields' => [
                'plate' => [
                    'fromValues' => $oldPlates,
                    'toValues' => $newPlates,
                ],
                'statusID' => [
                    'fromValues' => $oldStatuses,
                    'toValues' => $newStatuses,
                ],
                'mStatusID' => [
                    'fromValues' => $oldMStatuses,
                    'toValues' => $newStatuses,
                ],
            ],
            'transaction' => FALSE,
        ]);
    }

    /*
    ********************************************************************************
    */

    function getPlatesOrdersStatus($plates)
    {
        $qMarks = $this->app->getQMarkString($plates);

        $ucc128 = $this->fields['ucc128']['select'];

        $sql = 'SELECT    ca.id,
                          plate,
                          s.shortName AS cartonStatus,
                          os.shortName AS orderStatus,
                          n.scanordernumber AS orderNumber,
                          ' . $ucc128 . ' AS cartonNumber,
                          orderID
                FROM      inventory_containers co
                JOIN      inventory_batches b ON co.recNum = b.recNum
                JOIN      inventory_cartons ca ON b.id = ca.batchID
                JOIN      statuses s ON ca.statusID = s.id
                LEFT JOIN neworder n ON n.id = ca.orderID
                LEFT JOIN statuses os ON os.id = n.statusID
                WHERE     plate IN (' . $qMarks . ')
                ORDER BY  plate ASC';

        $result = $this->app->queryResults($sql, $plates);

        return $result;
    }

    /*
    ********************************************************************************
    */

    function badPlatesOrdersStatus($plates, $statuses)
    {
        $errors = $processedPlates = $orderNum = [];

        $results = $this->getPlatesOrdersStatus($plates);

        foreach ($results as $result) {
            if ($result['orderStatus'] === \tables\orders::STATUS_SHIPPING_CHECK_IN) {
                $orderNum[] = $result['orderNumber'];
            }
        }

        $uniqueOrders = array_unique($orderNum);

        foreach ($uniqueOrders as $order) {
            $errors[] = 'You can not set this order ' . $order . ' to Shipping Check-In status because it is'
                            . ' already Shipping Check-In status <br/>';
        }


        foreach ($results as $result) {

            $plate = $result['plate'];

            $processedPlates[$plate] = TRUE;

            $correctStatus = $result['orderStatus'] != $statuses['order'];

            // Wave pick orders with substitutions will not have order IDs
            if ($result['orderID'] && $correctStatus) {
                $errors[] = 'Order ' . $result['orderNumber'] . ' is not set '
                . 'to ' . $statuses['title'] . ' for license plate ' . $plate;
            }

            if ($result['cartonStatus'] != $statuses['carton']) {
                $errors[] = 'Carton ' . $result['cartonNumber'] . ' is not set '
                    . 'to ' . $statuses['title'] . ' for license plate ' . $plate;
            }
        }

        foreach ($plates as $plate) {
            if (! isset($processedPlates[$plate])) {
                $errors[] = 'No results for license plate ' . $plate;
            }
        }

        return array_unique($errors);
    }

    /*
    ****************************************************************************
    */

    function getLocationID($locations)
    {
        $qMarks = $this->app->getQMarkString($locations);
        // Get location IDs
        $sql = 'SELECT UPPER(displayName),
                           id
                    FROM   locations
                    WHERE  displayName IN (' . $qMarks . ')';

        $result = $this->app->queryResults($sql, $locations);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getCartonInfoByPlate($plates)
    {
        $qMarks = $this->app->getQMarkString($plates);
        $sql = 'SELECT  id,
                        plate,
                        locID,
                        statusID,
                        mStatusID
                FROM    inventory_cartons
                WHERE   plate IN (' . $qMarks . ')';

        $result = $this->app->queryResults($sql, $plates);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function submitUpdateShipStatus($params)
    {
        $app = $params['app'];
        $updates = $params['updates'];
        $updateLoc = $params['updateLoc'];
        $locIDs = $params['locIDs'];
        $cartonStatusID = $params['cartonStatusID'];
        $orderStatusID = $params['orderStatusID'];
        $cartonInfo = $params['cartonInfo'];

        $newValuesByPlate = [];
        foreach ($updates as $plate => $loc) {
            $ucLoc = strtoupper($loc);

            $locID = $updateLoc ? $locIDs[$ucLoc]['id'] : NULL;

            // If this is a shipped updated the plate value is in the loc var
            $data = $updateLoc
                ? [$locID, $locID, $cartonStatusID, $cartonStatusID,
                    $orderStatusID, $plate]
                : [$cartonStatusID, $cartonStatusID, $orderStatusID, $plate];

            $newValuesByPlate[$plate] = [
                'locID' => $locID,
                'statusID' => $cartonStatusID,
            ];

            $this->runUpdateShipStatus($updateLoc, $data);
        }

        $invIDs = $newManStats = $oldManStats = $newStatuses = $oldStatuses =
        $newLocs = $oldLocs = [];

        foreach ($cartonInfo as $invID => $row) {
            $invIDs[] = $invID;

            $plate = $row['plate'];

            $newManStats[] = $newValuesByPlate[$plate]['statusID'];

            $newLocs[] = $newValuesByPlate[$plate]['locID'];

            $oldLocs[] = $row['locID'];
            $oldStatuses[] = $row['statusID'];
            $oldManStats[] = $row['mStatusID'];
        }

        $fieldUpdates = [
            'statusID' => [
                'fromValues' => $oldStatuses,
                'toValues' => $newManStats,
            ],
            'mStatusID' => [
                'fromValues' => $oldManStats,
                'toValues' => $newManStats,
            ]
        ];

        if ($updateLoc) {
            $fieldUpdates['locID'] = [
                'fromValues' => $oldLocs,
                'toValues' => $newLocs,
            ];
        }

        logger::edit([
            'db' => $app,
            'primeKeys' => $invIDs,
            'fields' => $fieldUpdates,
            'transaction' => FALSE,
        ]);
    }

    /*
    ****************************************************************************
    */

    function runUpdateShipStatus($updateLoc, $params)
    {
        $locationSetClause = $updateLoc ? 'locID = ?, mLocID = ?,' : NULL;

        $sql = 'UPDATE    inventory_cartons ca
                LEFT JOIN neworder n ON n.id = ca.orderId
                SET       ' . $locationSetClause . '
                          ca.statusID = ?,
                          ca.mStatusID = ?,
                          n.statusID = ?,
                          orderShipDate = CURDATE()
                WHERE     plate = ?';

        $result = $this->app->runQuery($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function updateShipStatus($data)
    {
        $updates = $data['updates'];
        $statuses = $data['statuses'];
        $updateLoc = getDefault($data['updateLoc'], TRUE);
        $orderConditions = getDefault($data['orderConditions']);

        $statusesTable = new statuses($this->app);
        $orderStatusID = $statusesTable->getStatusID($statuses['order']);
        $cartonStatusID = $statusesTable->getStatusID($statuses['carton']);

        $locIDs = [];
        if ($updateLoc) {
            $locations = array_values($updates);
            // Get location IDs
            $locIDs = $this->getLocationID($locations);
        }

        // Get carton IDs by their prveious plates and statuses for logging
        $plates = array_keys($updates);
        $cartonInfo = $this->getCartonInfoByPlate($plates);

        $orders = new \tables\orders($this->app);

        $licencePlates = array_keys($updates);

        $orderIDs = $orders->getByPlate($licencePlates);

        if ($orderConditions) {

            $orderNumbrs = array_keys($orderConditions);

            $shippingOrders = $orders->getIDByOrderNumber($orderNumbrs);

            $shippingOrderIDs = [];

            foreach ($orderConditions as $orderNumber => $shippingStatusID) {
                $shippingOrderIDs[$shippingStatusID][] =
                        $shippingOrders[$orderNumber]['id'];
            }

            foreach ($shippingOrderIDs as $shippingStatusID => $values) {
                order::updateAndLogStatus([
                    'orderIDs' => $values,
                    'statusID' => $shippingStatusID,
                    'field' => 'shippingStatusID',
                    'tableClass' => $orders,
                ]);
            }
        }

        order::updateAndLogStatus([
            'orderIDs' => array_values($orderIDs),
            'statusID' => $orderStatusID,
            'tableClass' => $orders,
        ]);

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        $this->submitUpdateShipStatus([
            'app' => $this->app,
            'updates' => $updates,
            'updateLoc' => $updateLoc,
            'locIDs' => $locIDs,
            'cartonStatusID' => $cartonStatusID,
            'orderStatusID' => $orderStatusID,
            'cartonInfo' => $cartonInfo
        ]);

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function getNotProcessedCartons($params)
    {
        $processedStatus = self::STATUS_ORDER_PROCESSING;

        $ucc128 = $this->fields['ucc128']['select'];
        $qMarks = $this->app->getQMarkString($params);

        $sql = 'SELECT    ' . $ucc128 . ',
                          statusID,
                          ca.id,
                          s.shortName != "' . $processedStatus . '" AS received
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      statuses s ON ca.statusID = s.id
                WHERE     ca.id IN (' . $qMarks . ')
                AND       NOT isSplit
                AND       NOT unSplit
                ';

        $result = $this->app->queryResults($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkForReceived($uccData)
    {
        if (! $uccData) {
            return [
                'valid' => FALSE,
                'empty' => 'No Cartons Were Submitted'
            ];
        }

        $params = array_keys($uccData);

        $results = $this->getNotProcessedCartons($params);

        $valid = TRUE;
        $byRows = [];

        foreach ($uccData as $ucc) {

            $found = isset($results[$ucc]);

            $received = getDefault($results[$ucc]['received']);

            $valid = $found && $received ? $valid : FALSE;

            if (! $found || ! $received) {
                $byRows[$ucc] = [
                    'ucc' => $ucc,
                    'notFound' => ! $found,
                    'notReceieved' => ! $received,
                ];
            }
        }

        return [
            'valid' => $valid,
            'byRows' => $byRows,
        ];
    }

    /*
    ********************************************************************************
    */

    function getShippedStatuses($plates)
    {
        $plateIDs = array_keys($plates);

        $ucc128 = $this->fields['ucc128']['select'];

        $qMarks = $this->app->getQMarkString($plates);

        $sql = 'SELECT    ca.id,
                          plate,
                          l.displayName AS location ,
                          os.shortName AS orderStatus,
                          n.scanordernumber AS scanOrder,
                          ss.shortName AS shippingStatus,
                          ' . $ucc128 . ' AS ucc,
                          s.shortName AS invStatus
                FROM      inventory_containers co
                JOIN      inventory_batches b ON co.recNum = b.recNum
                JOIN      inventory_cartons ca ON b.id = ca.batchID
                LEFT JOIN locations l ON l.id = ca.locID
                LEFT JOIN neworder n ON n.id = ca.orderID
                JOIN      statuses s ON ca.statusID = s.id
                JOIN      statuses sm ON ca.mStatusID = sm.id
                LEFT JOIN statuses os ON os.id = n.statusID
                LEFT JOIN statuses ss ON ss.id = n.shippingStatusID
                WHERE     plate IN (' . $qMarks. ')';

        $result = $this->app->queryResults($sql, $plateIDs);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getUPCIDs()
    {
        $sql = 'SELECT  upc,
                        upcID
                FROM    ' . $this->table . '
                GROUP BY upc';

        $result = $this->app->queryResults($sql);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getMaxID($target, $default)
    {
        $sql = 'SELECT MAX(' . $target . ') AS max
                FROM inventory_cartons';

        $upc = $this->app->queryResult($sql);
        return $upc['max'] ? $upc['max'] : $default;
    }

    /*
    ****************************************************************************
    */

    function get($container=FALSE)
    {
        $fields = $this->getSelectFields();
        $sql = 'SELECT ' . $fields . '
                FROM   ' . $this->table . '
                WHERE  name = ?';

        $result = $this->app->queryResults($sql, [$container]);

        return $result;
    }

    /*
    ********************************************************************************
    */

    function getDiscrepantCartonData($orderNumber, $invIDs)
    {
        $reservedCartons = $this->getReservedCartons([$orderNumber]);
        $shippedCartons = $this->getShippedCartons($invIDs);

        $matched = array_intersect_key($shippedCartons, $reservedCartons);
        $discrepancy = array_diff_key($shippedCartons, $reservedCartons);

        $resolveCartons = [];

        foreach ($reservedCartons as $invID => $reserved) {

            $reservedUPC = $reserved['upc'];
            $reservedUOM = $reserved['uom'];

            if (isset($matched[$invID])) {
                continue;
            }

            foreach ($discrepancy as $key => $value) {
                if ($reservedUPC == $value['upc'] && $reservedUOM == $value['uom']) {

                    $resolveCartons[] = $invID;

                    unset($discrepancy[$key]);

                    break;
                }
            }
        }

        $cartonData = $this->getOldCartonInfo($resolveCartons);

        return [
            'resolveCartons' => $resolveCartons,
            'cartonData' => $cartonData,
        ];
    }

    /*
    ****************************************************************************
    */

    function resolveDiscrepancy($data)
    {
        $invIDs = $data['resolveCartons'];
        $cartonData = $data['cartonData'];

        if (! $invIDs) {
            return;
        }

        $qMarks = $this->app->getQMarkString($invIDs);

        $pickSql = 'UPDATE pick_cartons
                    SET    active = 0
                    WHERE  cartonID IN (' . $qMarks . ')
                    AND    active
                    ';

        $cartonsSql = 'UPDATE inventory_cartons
                       SET    mLocID = locID,
                              mStatusID = statusID
                WHERE  id IN (' . $qMarks . ')';

        $this->app->runQuery($pickSql, $invIDs);

        $this->app->runQuery($cartonsSql, $invIDs);
        // logger data shall be defined in a function this function is called from
        $this->logCartonManualData($cartonData);
    }

    /*
    ****************************************************************************
    */

    function getReservedCartons($orders)
    {
        $qMarks = $this->app->getQMarkString($orders);

        $sql = 'SELECT    ca.id,
                          upc,
                          uom
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      pick_cartons pc ON pc.cartonID = ca.id
                JOIN      upcs p ON p.id = b.upcID
                JOIN 	  neworder o ON o.id = pc.orderID
                WHERE     scanordernumber IN (' . $qMarks . ')
                AND       pc.active
                ';

        $result = $this->app->queryResults($sql, $orders);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getShippedCartons($invIDs)
    {
        $qMarks = $this->app->getQMarkString($invIDs);

        $sql = 'SELECT    ca.id,
                          upc,
                          uom
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      upcs p ON p.id = b.upcID
                WHERE     ca.id IN (' . $qMarks . ')
                ';

        $result = $this->app->queryResults($sql, $invIDs);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function processDiscrepancy($discrepancy, $reserved)
    {
        $resolveUcc = [];
        foreach ($discrepancy as $key => $value) {
            if ($reserved['upc'] == $value['upc']
                && $reserved['uom'] == $value['uom']
            ) {
                $resolveUcc[] = $reserved['id'];
                unset($discrepancy[$key]);
                break;
            }
        }

        return $resolveUcc;
    }

    /*
    ********************************************************************************
    */

    function updateLocations($cartonIDs, $locationID)
    {
        $qMarks = $this->app->getQMarkString($cartonIDs);
        $sql = 'UPDATE inventory_cartons
                SET    locID = ?,
                       mLocID = ?
                WHERE  id IN (' . $qMarks . ')';

        $param = $cartonIDs;

        array_unshift($param, $locationID);
        array_unshift($param, $locationID);

        $this->app->runQuery($sql, $param);
    }

    /*
    ********************************************************************************
    */

    function getChildrenData($parentID, $limit = FALSE)
    {
        $sql = 'SELECT    ca.id,
                          cartonID,
                          uom
                FROM      inventory_cartons ca
                JOIN      inventory_splits sp ON ca.id = sp.childID
                WHERE     sp.parentID = ?
                ORDER BY  ca.cartonID ASC';

        if ($limit) {

            $sql = $sql.'
                LIMIT 1';

            $result = $this->app->queryResult($sql, [$parentID]);
        } else {
            $result =  $this->app->queryResults($sql, [$parentID]);
        }

        return $result;
    }

    /*
    ********************************************************************************
    */

    function getChildrenUCCs($data)
    {
        $clauses = $params = [];

        $ucc128 = $this->fields['ucc128']['select'];

        foreach ($data as $parentID => $uom) {

            $clauses[] = 'parentID = ? AND uom = ?';

            $params[] = $parentID;
            $params[] = $uom;
        }

        $sql = 'SELECT    parentID,
                          childID,
                          uom,
                          ' . $ucc128. ' AS ucc128
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      inventory_splits sp ON ca.id = sp.childID
                WHERE     ' . implode(' OR ', $clauses);

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ********************************************************************************
    */

    function getUPCsFromIDs($invIDs)
    {
        $qMarks = $this->app->getQMarkString($invIDs);

        $sql = 'SELECT      ca.id,
                            upc,
                            uom
                FROM        inventory_cartons ca
                JOIN        inventory_batches b ON b.id = ca.batchID
                JOIN        upcs u ON b.upcID = u.id
                WHERE       ca.id IN (' . $qMarks . ')';

        $result = $this->app->queryResults($sql, $invIDs);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getByUCC($uccs, $selectClauses=NULL)
    {
        $fields = getDefault($selectClauses['fields'], NULL);
        $selectJoin = getDefault($selectClauses['join']);

        $ucc128 = $this->fields['ucc128']['select'];

        $clauses = $this->getByUCCSelectClauses($uccs);

        $selectFields = $fields ? implode(',', $fields) . ',' : NULL;

        $sql = 'SELECT    ' . $ucc128 . ',
                          statusID,
                          mStatusID,
                          ' . $selectFields . '
                          uom,
                          isSplit,
                          unSplit,
                          ca.id
                FROM      inventory_containers co
                JOIN      inventory_batches b ON b.recNum = co.recNum
                JOIN      inventory_cartons ca ON ca.batchID = b.id
                ' . $selectJoin . '
                WHERE     ' . $clauses['where'];

        $results = $this->app->queryResults($sql, $clauses['params']);

        return $results;
    }
    /*
    ********************************************************************************
    */

    function getCartonDataByUCC($uccs, $status)
    {
        $ucc128 = $this->fields['ucc128']['select'];

        $clauses = $this->getByUCCSelectClauses($uccs);

        $where = $clauses['where'];
        $params = $clauses['params'];
        $join = NULL;

        if ($status) {
            $where .= ' AND s.shortName = ? ';
            $params[] = self::STATUS_RACKED;
            $join = 'JOIN      statuses s ON s.id = ca.statusID';
        }

        $sql = 'SELECT    ' . $ucc128 . ',
                          batchID,
                          vendorID,
                          plate,
                          locID,
                          mLocID,
                          vendorCartonID,
                          statusID,
                          mStatusID,
                          uom,
                          isSplit,
                          unSplit,
                          ca.id
                FROM      inventory_containers co
                JOIN      inventory_batches b ON b.recNum = co.recNum
                JOIN      inventory_cartons ca ON ca.batchID = b.id
                ' . $join . '
                WHERE     ' . $where;

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ********************************************************************************
    */

    function getByUCCSelectClauses($uccs)
    {
        $clauses = $params = $uccData = [];

        foreach ($uccs as $ucc) {

            $vendorID = (int)substr($ucc, 0, 5);
            $batchID = (int)substr($ucc, 5, 8);
            $uom = (int)substr($ucc, 13, 3);

            $uccData[$vendorID][$batchID][$uom][] = (int)substr($ucc, 16, 4);
        }

        foreach ($uccData as $vendorID => $vendorData) {
            foreach ($vendorData as $batchID => $batchData) {
                foreach ($batchData as $uom => $cartonIDs) {

                    $qMarks = $this->app->getQMarkString($cartonIDs);

                    $clauses[] = 'co.vendorID = ?
                        AND ca.batchID = ?
                        AND ca.uom = ?
                        AND ca.cartonID IN (' . $qMarks. ')';

                    $params = array_merge(
                            $params, [$vendorID, $batchID, $uom], $cartonIDs
                    );
                }
            }
        }

        return [
            'where' => '(' . implode(' OR ', $clauses) . ')',
            'params' => $params,
        ];
    }

    /*
    ********************************************************************************
    */

    function makeErrorCellValue($params)
    {
        $minWidth = $params['minWidth'];
        $cellValue = $params['cellValue'];
        $minValue = $params['minValue'];
        $maxValue = $params['maxValue'];
        $decimals = $params['decimals'];
        $maxWidth = $params['maxWidth'];

        $cellError = $errors = NULL;

        if ($minWidth && ! $cellValue) {
            $cellError = 'must not be empty';
        } elseif ($minValue) {
            if (! is_numeric($cellValue)) {
                $cellError = 'must be a number';
            } else if ($cellValue < $minValue || $cellValue > $maxValue) {
                $cellError = 'must not be less than ' . $minValue
                    . ' or greater than ' . $maxValue;
            } else {

                $cellValue *= pow(10, $decimals);
                $cellValue .= '';

                if (strstr($cellValue, '.' )) {
                    $message = $decimals > 0
                        ? 'exceed ' . $decimals . ' digits' : 'be present';
                    $cellError = 'decimal part must not '.$message;
                }
            }
        } elseif ($minWidth || $maxWidth) {
            if ($maxValue && ! is_numeric($cellValue)) {

                $cellError = 'must be a number';
            } elseif ($minWidth && strlen(trim($cellValue)) < $minWidth) {

                $cellError = isset($cellError) ? $cellError : NULL;
                $conjunction = $cellError ? ' and ' : NULL;

                $cellError .= $conjunction . 'text length must not be less then '
                    . $minWidth . ' characters';
            } elseif ($maxWidth && strlen(trim($cellValue)) > $maxWidth) {

                $conjunction = getDefault($cellError) ? ' and ' : NULL;

                $cellError .= $conjunction . 'text length must not exceed '
                    . $maxWidth . ' characters';
            }
        }

        return $cellError;
    }

    /*
    ********************************************************************************
    */

    function checkCellValue($param)
    {
        $rowError = getDefault($param['rowError']);
        $cellName = getDefault($param['cellName']);
        $cellValue = getDefault($param['cellValue']);
        $measurement = getDefault($param['measurement'], 'imperial');
        $decimals = getDefault($param['decimals'], 0);
        $minWidth = getDefault($param['minWidth']);
        $maxWidth = getDefault($param['maxWidth']);
        $returnArray = getDefault($param['returnArray']);
        $errors = NULL;
        $dimension = strtolower($cellName);

        if (isset($this->measurements[$dimension])) {

            $minValue = getDefault($param['minValue'],
                    $this->measurements[$dimension][$measurement]['min']);
            $maxValue = getDefault($param['maxValue'],
                    $this->measurements[$dimension][$measurement]['max']);
        } else {
            $minValue = getDefault($param['minValue']);
            $maxValue = getDefault($param['maxValue']);
        }

        $cellError = $this->makeErrorCellValue([
            'minWidth' => $minWidth,
            'cellValue' => $cellValue,
            'minValue' => $minValue,
            'maxValue' => $maxValue,
            'decimals' => $decimals,
            'maxWidth' => $maxWidth,
        ]);

        $this->addCellErrorMessage($errors, [
            'cellName' => $cellName,
            'cellError' => $cellError,
            'returnArray' => $returnArray,
            'rowError' => $rowError,
        ]);

        if (! is_numeric($cellValue) && nonUTF::check($cellValue)) {
            $this->addCellErrorMessage($errors, [
                'cellName' => $cellName,
                'cellError' => 'has bad character(s)',
                'returnArray' => $returnArray,
                'rowError' => $rowError,
            ]);
        }

        return $errors;
    }

    /*
    ****************************************************************************
    */

    function addCellErrorMessage(&$errors, $data)
    {
        $cellName = $data['cellName'];
        $cellError = $data['cellError'];
        $returnArray = $data['returnArray'];
        $rowError = $data['rowError'];

        if (! $cellError) {
            return FALSE;
        }

        if ($returnArray) {
            $errors[] = [
                'field' => $cellName,
                'error' => $cellError,
            ];
        } else {
            $break = $rowError ? '<br>' : NULL;
            $errors = $break . '<strong>' . $cellName . ':</strong> ' .
                $cellError;
        }
    }

    /*
    ****************************************************************************
    */

    function checkCubicValue($param)
    {
        $rowError = getDefault($param['rowError']);
        $height = $param['height'];
        $width = $param['width'];
        $length = $param['length'];
        $returnArray = getDefault($param['returnArray']);

        $errors = NULL;

        if ($height == $width && $width == $length){
            $this->addCellErrorMessage($errors, [
                'cellName' => 'HxWxL',
                'cellError' => 'dimensions cannot be a Cube!',
                'returnArray' => $returnArray,
                'rowError' => $rowError,
            ]);
        }

        return $errors;
    }

    /*
    ****************************************************************************
    */

    function checkCartonDimension($data)
    {
        $value = $data['value'];
        $dimension = $data['dimension'];
        $measurement = $data['measurement'];

        $result = $this->checkCellValue([
            'cellName' => strtoupper($dimension),
            'cellValue' => $value,
            'measurement' => $measurement,
            'decimals' => 2,
            'minWidth' => 1,
            'returnArray' => TRUE,
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkCartonSize($value, $caption='SIZE')
    {
        $result = $this->checkCellValue([
            'cellName' => $caption,
            'cellValue' => $value,
            'minWidth' => 1,
            'maxWidth' => 45,
            'returnArray' => TRUE,
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkCartonColor($value, $caption='COLOR')
    {
        $result = $this->checkCellValue([
            'cellName' => $caption,
            'cellValue' => $value,
            'minWidth' => 1,
            'maxWidth' => 45,
            'returnArray' => TRUE,
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkCartonInitialCount($value, $caption='CARTON')
    {
        $result = $this->checkCellValue([
            'cellName' => $caption,
            'cellValue' => $value,
            'minValue' => 1,
            'maxValue' => self::MAX_CARTON_ID,
            'returnArray' => TRUE,
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkCartonUOM($value)
    {
        $result = $this->checkCellValue([
            'cellName' => 'UOM',
            'cellValue' => $value,
            'minValue' => 1,
            'maxValue' => self::MAX_CARTON_ID,
            'minWidth' => 1,
            'returnArray' => TRUE,
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkCartonSKU($value, $caption='SKU')
    {
        $result = $this->checkCellValue([
            'cellName' => $caption,
            'cellValue' => $value,
            'minWidth' => 1,
            'maxWidth' => 45,
            'returnArray' => TRUE,
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkCartonPrefix($value, $caption='CLIENT PO')
    {
        $result = $this->checkCellValue([
            'cellName' => $caption,
            'cellValue' => $value,
            'minWidth' => 1,
            'maxWidth' => 80,
            'returnArray' => TRUE,
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkCartonSuffix($value)
    {
        $result = $this->checkCellValue([
            'cellName' => 'SUFFIX',
            'cellValue' => $value,
            'maxWidth' => 25,
            'returnArray' => TRUE,
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getZeroOutReady($invIDs)
    {
        $qMarks = $this->app->getQMarkString($invIDs);

        $ucc128 = $this->fields['ucc128']['select'];

        $sql = 'SELECT    ca.id,
                          ' . $ucc128 . ' AS ucc128,
                          l.displayName,
                          plate
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      statuses s ON s.id = ca.statusID
                JOIN      locations l ON l.id = ca.locID
                WHERE     category = "inventory"
                AND       shortName = "' . self::STATUS_RACKED . '"
                AND       statusID = mStatusID
                AND       NOT isSplit
                AND       NOT unSplit
                AND       ca.id IN (' . $qMarks . ')
                ';

        $result = $this->app->queryResults($sql, $invIDs);

        return $result;
    }

    /*
    ********************************************************************************
    */

    function getCartonsByLocation($locations)
    {
        $qMarks = $this->app->getQMarkString($locations);

        $noHyphens = [];

        foreach ($locations as $location) {
            $noHyphens[] = str_replace('-', NULL, $location);
        }

        $sql = 'SELECT    ca.id
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      locations l ON l.id = ca.locID
                WHERE     displayName IN (' . $qMarks . ')
                OR        displayName IN (' . $qMarks . ')
                ';

        $params = array_merge($locations, $noHyphens);

        $results = $this->app->queryResults($sql, $params);

        return array_keys($results);
    }

    /*
    ****************************************************************************
    */

    function getNotZeroOutInfo($invIDs)
    {
        $qMarks = $this->app->getQMarkString($invIDs);

        $ucc128 = $this->fields['ucc128']['select'];

        $sql = 'SELECT    ' . $ucc128 . ',
                          s.shortName AS status,
                          ms.shortName AS mStatus,
                          isSplit,
                          unSplit
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      statuses s ON s.id = ca.statusID
                JOIN      statuses ms ON ms.id = ca.mStatusID
                WHERE     ca.id IN (' . $qMarks . ')
                ';

        $result = $this->app->queryResults($sql, $invIDs);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkIfMezzanine($invIDs)
    {
        $sql = 'SELECT    ca.id
                FROM      inventory_cartons ca
                JOIN      locations l ON l.id = ca.locID
                WHERE     isMezzanine
                AND       ca.id IN (' . $this->app->getQMarkString($invIDs) . ')
                ';

        $result = $this->app->queryResults($sql, $invIDs);

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function customStrPad($params)
    {
        $string = $params['input'];
        $padLength = $params['padLength'];
        $padString = isset($params['padString']) ? $params['padString'] : ' ';
        $padType = isset($params['padType']) ? $params['padType'] :
            STR_PAD_RIGHT;

        $result = str_pad($string, $padLength, $padString, $padType);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getProcessedBatches($bathes)
    {
        if (! $bathes) {
            return [];
        }
        $qMarks = $this->app->getQMarkString($bathes);
        $sql = 'SELECT   batchID,
                         uom
                FROM     inventory_cartons ca
                JOIN     statuses s ON s.id = ca.statusID
                WHERE    batchID IN (' . $qMarks . ')
                AND      (isSplit
                    OR  category = "inventory"
                    AND shortName != "' . self::STATUS_INACTIVE . '"
                    AND shortName != "' . self::STATUS_RECEIVED . '"
                )
                GROUP BY batchID';

        $results = $this->app->queryResults($sql, $bathes);

        $keys = array_keys($results);
        $values = array_column($results, 'uom');
        $return = array_combine($keys, $values);

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getInventoryData($receivingNumber)
    {
        $sql = 'SELECT  ca.id,
                        name AS container,
                        ca.batchId AS batchnumber,
                        upc,
                        u.sku,
                        LPAD(uom, 3, 0) AS uom,
                        prefix,
                        suffix,
                        height,
                        width,
                        length,
                        weight,
                        eachHeight,
                        eachWidth,
                        eachLength,
                        eachWeight,
                        size AS size1,
                        color AS color1,
                        initialCount AS carton
                FROM    inventory_cartons ca
                JOIN    inventory_batches b ON b.id = ca.batchID
                JOIN    inventory_containers co ON co.recNum = b.recNum
                JOIN    upcs u ON u.id = b.upcId
                WHERE   co.recNum = ?
                GROUP BY ca.batchId';

        $result = $this->app->queryResults($sql, [$receivingNumber]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getContainerCartons($container)
    {
        if (! $container) {
            return [];
        }

        $ucc128 = $this->fields['ucc128']['select'];

        $sql = 'SELECT    ca.id,
                          ' . $ucc128 . ' AS ucc128,
                          u.sku,
                          prefix,
                          upc,
                          color,
                          size,
                          b.id AS batchID,
                          uom,
                          vendorID,
                          cartonID,
                          b.upcID,
                          co.name AS container,
                          l.displayName AS location
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      upcs u ON u.id = b.upcId
                JOIN      locations l ON l.id = ca.locID
                JOIN      statuses s ON s.id = ca.statusID
                WHERE     co.name = ?
                AND       s.shortName != "' . self::STATUS_INACTIVE . '"
                ORDER BY  ca.id DESC';

        $result = $this->app->queryResults($sql, [$container]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function zeroOutInventory($invIDs, $statuses)
    {
        $zeroOutReady = $this->getZeroOutReady($invIDs);

        $zeroOutReadyIDs = array_keys($zeroOutReady);

        $zeroOutNotReady = array_diff($invIDs, $zeroOutReadyIDs);

        $zeroOutNotReadyValues = array_values($zeroOutNotReady);

        $notZeroOutCartonsInfo = $zeroOutNotReady
                ? $this->getNotZeroOutInfo($zeroOutNotReadyValues) : [];

        if ($zeroOutReadyIDs) {

            $oldStatusID = $statuses->getStatusID(self::STATUS_RACKED);
            $newStatusID = $statuses->getStatusID(self::STATUS_INACTIVE);

            $uccCount = count($zeroOutReadyIDs);
            $fromValues = array_fill(0, $uccCount, $oldStatusID);

            logger::getFieldIDs('cartons', $this->app);

            logger::getLogID();

            $this->app->beginTransaction();

            $this->updateStatus([
                'target' => $zeroOutReadyIDs,
                'status' => $newStatusID,
                'statusField' => 'statusID',
                'table' => 'inventory_cartons',
                'transaction' => FALSE,
            ]);

            $this->updateStatus([
                'target' => $zeroOutReadyIDs,
                'status' => $newStatusID,
                'statusField' => 'mStatusID',
                'table' => 'inventory_cartons',
                'transaction' => FALSE,
            ]);

            logger::edit([
                'db' =>  $this->app,
                'primeKeys' => $zeroOutReadyIDs,
                'fields' => [
                    'statusID' => [
                        'fromValues' => $fromValues,
                        'toValues' => $newStatusID,
                    ],
                    'mStatusID' => [
                        'fromValues' => $fromValues,
                        'toValues' => $newStatusID,
                    ],
                ],
                'transaction' => FALSE,
            ]);

            $this->app->commit();
        }

        return [
            'zeroOutCartonsInfo' => $zeroOutReady,
            'notZeroOutCartonsInfo' => $notZeroOutCartonsInfo,
        ];
    }

    /*
    ****************************************************************************
    */

    function getUPCQuantity($vendorUpcs, $isMezzanine=NULL)
    {
        if (! $vendorUpcs) {
            return [];
        }

	    $dataUPCs = $clauses = $params = [];

        $whereClause = 1;
        $joinClause = NULL;

        if ($isMezzanine !== NULL) {
            $whereClause = $isMezzanine ? 'isMezzanine' : 'NOT isMezzanine';
            $joinClause = 'JOIN locations l ON l.id = ca.locID';
        }

        foreach ($vendorUpcs as $vendorID => $upcs) {

            $upcQMarks = $this->app->getQMarkString($upcs);

            $clauses[] = 'vendorID = ? AND upc IN (' . $upcQMarks . ')';

            $params = array_merge($params, [$vendorID], $upcs);
        }

        $sql = 'SELECT    ca.id,
                          vendorID,
						  u.upc,
                          SUM(uom) AS totalPieces
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      upcs u ON u.id = b.upcID
                JOIN      statuses s ON s.id = ca.statusID
                ' . $joinClause . '
                WHERE     (' . implode(' OR ', $clauses) . ')
                AND       s.shortName = "RK"
                AND       statusID = mStatusID
                AND       category = "inventory"
                AND       NOT isSplit
                AND       NOT unSplit
                AND       ' . $whereClause . '
                GROUP BY  vendorID,
                          upc';

        $results = $this->app->queryResults($sql, $params);

	    foreach ($results as $values) {

            $upc = $values['upc'];
            $vendorID = $values['vendorID'];

		    $dataUPCs[$vendorID][$upc] = $values['totalPieces'];
        }

        return $dataUPCs;
    }

    /*
    ****************************************************************************
    */

    function getMezzanineTransferInventory($pieceTotals, $ignoreCartons)
    {
        $ucc128 = $this->fields['ucc128']['select'];

        $sqlTop = '
            SELECT    ca.id,
                      batchID,
                      uom,
                      cartonID,
                      locID,
                      vendorID,
                      upcID,
                      upc,
                      u.sku,
                      u.color,
                      u.size,
                      l.displayName AS locationName,
                      ' . $ucc128 . ' AS ucc128
            FROM      inventory_cartons ca
            JOIN      inventory_batches b ON b.id = ca.batchID
            JOIN      inventory_containers co ON co.recNum = b.recNum
            JOIN      locations l ON l.id = ca.locID
            JOIN      upcs u ON u.id = b.upcID
            JOIN      statuses s ON s.id = ca.statusID
            WHERE     upcID = ?
            AND       vendorID = ?';

        $sqlBottom = '
            AND       NOT isMezzanine
            AND       shortName = "RK"
            AND       category = "inventory"
            AND       statusID = mStatusID
            AND       NOT isSplit
            AND       NOT unSplit
            ORDER BY  uom DESC,
                      locID ASC,
                      batchID DESC
            ';

        $unionParams = $subqueries = [];

        $subqueryCount = 0;

        foreach ($pieceTotals as $vendorID => $values) {
            foreach ($values as $upcID => $limit) {

                $unionParams[] = $upcID;
                $unionParams[] = $vendorID;

                $clause = NULL;

                if ($ignoreCartons) {

                    $qMarks = $this->app->getQMarkString($ignoreCartons);

                    $clause = ' AND ca.id NOT IN (' . $qMarks . ')';

                    $unionParams = array_merge($unionParams, $ignoreCartons);
                }

                $subqueries[] = $sqlTop . $clause . $sqlBottom;

                $limits[] = $limit;

                $subqueryCount++;
            }
        }

        $results = $this->app->queryUnionResults([
            'limits' => $limits,
            'mysqlParams' => $unionParams,
            'subqueries' => $subqueries,
            'subqueryCount' => $subqueryCount,
        ]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getAmbiguousVendors($data)
    {
        $where = $params = [];

        foreach ($data as $datum) {

            $field = $datum['field'];
            $values = $datum['values'];
            $joinClause = getDefault($datum['joinClause'], NULL);
            $vendorID = $datum['vendorID'];
            $whereClause = getDefault($datum['whereClause'], 1);
            $whereParams = getDefault($datum['whereParams'], []);

            $qMarks = $this->app->getQMarkString($values);

            $where[] = $field . ' IN (' . $qMarks . ')
                AND       co.vendorID != ?
                AND       (' . $whereClause . ')';

            $params = array_merge($params, $values, [$vendorID], $whereParams);
        }

        $sql = 'SELECT    ca.id,
                          ' . $field . ',
                          vendorID,
                          CONCAT(w.shortName, "_", vendorName) AS fullVendorName
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                ' . $joinClause . '
                JOIN      vendors v ON v.id = co.vendorID
                JOIN      warehouses w ON w.id = v.warehouseID
                JOIN      statuses s ON s.id = ca.statusID
                WHERE     (' . implode(' OR ', $where) . ')
                AND       category = "inventory"
                AND       s.shortName = "' . self::STATUS_RACKED . '"
                GROUP BY  co.vendorID,
                          ' . $field;

        $result = $this->app->queryResults($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getPickCartonsByID($invIDs)
    {
        $qMarks = $this->app->getQMarkString($invIDs);

        $sql = 'SELECT    ca.id,
                          pc.orderID,
                          pickID
                FROM      inventory_cartons ca
                JOIN      pick_cartons pc ON pc.cartonID = ca.id
                WHERE     pc.active
                AND       ca.id IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $invIDs);

        return $results;
      }

    /*
    ****************************************************************************
    */

    function split($cartonsToSplit, $status = FALSE)
    {
        $return = [
            'error' => FALSE,
            'cartonsToSplit' => FALSE,
            'children' => FALSE,
            'combined' => FALSE
        ];

        if (! $cartonsToSplit) {

            $return['error'] = 'No cartons to split';

            return $return;
        }

        $splits = new \inventory\splits($this->app, FALSE);
        $wavePicks = new \tables\wavePicks($this->app);

        $invIDs = $batchKeys = $params = $splitErrors = $children = $parents = [];

        $uccs = array_keys($cartonsToSplit);

        $splitCartons = $this->getCartonDataByUCC($uccs, $status);

        if (! $splitCartons) {

            $return['error'][] = 'Parent carton must be Racked';

            return $return;
        }

        foreach ($uccs as $ucc) {

            $carton = getDefault($splitCartons[$ucc]);

            if (! $carton) {
                $splitErrors[] = 'Carton # ' . $ucc . ' does not exist';
            } elseif ($carton['isSplit'] || $carton['unSplit']) {
                $splitErrors[] = 'Carton # ' . $ucc . ' has already been split';
            } else {
                $invIDs[] = $carton['id'];
            }
        }

        if ($splitErrors) {

            $return['error'] = $splitErrors;

            return $return;
        }

        foreach ($cartonsToSplit as $ucc => $uoms) {

            $carton = $splitCartons[$ucc];

            $uom = $carton['uom'];
            $batchID = $carton['batchID'];

            if (array_sum($uoms) != $uom) {

                $uomString = implode(' + ', $uoms);

                $splitErrors[] = 'Split quantities ' . $uomString
                        . ' does not equal to original carton uom ' . $uom
                        . ' for Carton  # ' . $ucc;
            }

            $params[$ucc] = [
                'newUOMs' => $uoms,
                'insertData' => $carton,
            ];

            $splits->parents[$ucc] = $carton['id'];

            $batchKeys[$batchID] = TRUE;
        }

        if ($splitErrors) {

            $return['error'] = $splitErrors;

            return $return;
        }

        $combined = $this->splitCartons([
            'invIDs' => $invIDs,
            'batchIDs' => array_keys($batchKeys),
            'params' => $params,
            'classes' => [
                'splits' => $splits,
                'wavePicks' => $wavePicks,
            ],
        ]);

        foreach ($combined as $ucc => $values) {

            $parents[] = $ucc;

            $uccKeys = array_keys($values);

            $children = array_merge($children, $uccKeys);
        }

        $return['cartonsToSplit'] = $parents;
        $return['children'] = $children;
        $return['combined'] = $combined;

        return $return;
    }

    /*
    ****************************************************************************
    */

    function splitCartons($data)
    {
        $invIDs = $data['invIDs'];
        $batchIDs = $data['batchIDs'];
        $params = $data['params'];
        $splits = $data['classes']['splits'];
        $wavePicks = $data['classes']['wavePicks'];

        $return = [];

        $reservedSplits = $this->getPickCartonsByID($invIDs);

        $batchMaxes = $splits->getBatchesMaxes($batchIDs);

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->nextID = $this->getNextID('inventory_cartons');

        $this->app->beginTransaction();

        $splits->splitCarton($invIDs);

        foreach ($params as $ucc => $param) {

            $insertData = $param['insertData'];

            $batch = $insertData['batchID'];

            $this->app->maxCartons[$batch] = getDefault(
                $this->app->maxCartons[$batch],
                $batchMaxes[$batch]['maxCarton']
            );

            $param['ucc'] = $ucc;

            $splitData = $splits->createChildren($param);

            $return = $return + $splitData;

            $invID = $splits->parents[$ucc];

            if (isset($reservedSplits[$invID]['orderID'])) {
                // update pick_cartons table is reserved cartons were split
                $this->updateReservedCartons([
                    'orderID' => $reservedSplits[$invID]['orderID'],
                    'pickID' => $reservedSplits[$invID]['pickID'],
                    'parent' => $invID,
                    'children' => array_column($splitData[$ucc], 'invID'),
                    'wavePicks' => $wavePicks,
                ]);
            }
        }

        $this->app->commit();

        return $return;
    }

    /*
    ****************************************************************************
    */

    function updateReservedCartons($param)
    {
        $orderID = $param['orderID'];
        $pickID = $param['pickID'];
        $parent = $param['parent'];
        $children = $param['children'];
        $wavePicks = $param['wavePicks'];

        $insertSql = '
            INSERT INTO pick_cartons (
                orderID,
                cartonID,
                pickID
            ) VALUES (
                ?, ?, ?
            )';

        foreach ($children as $cartonID) {
            $this->app->runQuery($insertSql, [$orderID, $cartonID, $pickID]);
        }

        $wavePicks->deactivateByCartonID([$parent]);
    }

    /*
    ****************************************************************************
    */

    function getCartonWarehouse($invIDs)
    {
        if (! $invIDs) {
            return [];
        }

        $cartons = new cartons($this->app);

        $ucc128 = $cartons->fields['ucc128']['select'];

        $qMarks = $this->app->getQMarkString($invIDs);

        $sql = 'SELECT    ' . $ucc128 . ',
                          warehouseID
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      vendors v ON v.id = co.vendorID
                WHERE     ca.id IN (' . $qMarks . ')';

        $resutls = $this->app->queryResults($sql, $invIDs);

        $return = [];

        foreach ($resutls as $ucc => $values) {
            $return[$ucc] = $values['warehouseID'];
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function logCartonManualData($loggedCartons)
    {
        foreach ($loggedCartons as $invID => $carton) {

            $fields = [];

            if (isset($carton['mStatusID'])
             && $carton['mStatusID'] != $carton['statusID']) {

                $fields['mStatusID'] = [
                    'fromValues' => $carton['mStatusID'],
                    'toValues' => $carton['statusID'],
                ];
            }

            if (isset($carton['mLocID'])
             && $carton['mLocID'] != $carton['locID']) {

                $fields['mLocID'] = [
                    'fromValues' => $carton['mLocID'],
                    'toValues' => $carton['locID'],
                ];
            }

            if ($fields) {
                logger::edit([
                    'db' => $this->app,
                    'primeKeys' => $invID,
                    'fields' => $fields,
                    'transaction' => FALSE,
                ]);
            }
        }
    }

    /*
    ****************************************************************************
    */

    function getAddCartonsToBatchAllowedStatuses()
    {
        $statuses = new \tables\statuses\inventory($this->app);

        $results = $statuses->getStatusIDs([
            self::STATUS_INACTIVE,
            self::STATUS_RECEIVED,
            self::STATUS_RACKED,
            self::STATUS_LOCKED,
        ]);

        $return = [];

        foreach ($results as $key => $values) {

            $statusID = $values['id'];

            $return[$statusID] = $key;
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getAddCartonsToBatchLogFields()
    {
        return ['plate', 'locID', 'mLocID', 'statusID', 'mStatusID'];
    }

    /*
    ****************************************************************************
    */

    function addCartonsToBatch($post, $batchID)
    {
        $cartonCount = getDefault($post['addCartons']);

        $logFields = $this->getAddCartonsToBatchLogFields();

        $logData = [];

        if (! $cartonCount) {
            return [
                'cartonAdded' => 0,
                'error' => 'No Cartons Quantity was submitted',
            ];
        }

        $batches = new batches($this->app);
        $tallies = new tally([
            'mvc' => $this->app
        ]);

        $plate = $post['posiblePlates'];

        $rowID = $tallies->getRowIdByBatchAndPlate($batchID, $plate);

        if (! $rowID) {
            return [
                'cartonAdded' => 0,
                'error' => 'Row ID was not defined',
            ];
        }

        $fields = $this->cartonFieldsOnly();

        $insertFields = array_keys($fields);

        $qMarks = $this->app->getQMarkString($insertFields);

        $sql = 'INSERT INTO inventory_cartons (
                    ' . implode(', ', $insertFields) . '
                ) VALUES (
                    ' . $qMarks . '
                )';

        $nextCartonID = $this->getBatchNextCartonID($batchID);
        $nextInvID = $this->getNextID('inventory_cartons');
        $recNum = $batches->getRecNumByID($batchID);
        $batchData = $this->getBatchData($insertFields, $batchID, $plate);

        $allowedStatuses = $this->getAddCartonsToBatchAllowedStatuses();

        $statusID = $batchData['statusID'];

        if (! isset($allowedStatuses[$statusID])) {
            return [
                'cartonAdded' => 0,
                'error' => 'Only License Plates with ' . implode(', ', $allowedStatuses)
                    . ' carton statuses are allowed',
            ];
        }

        $tallies->recNum = $recNum;

        tally::$selectParams['tallyIDs'] = $tallies->getTallyIDs([
            'app' => $this->app,
            'recNums' => $recNum,
        ]);

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        logger::startAdd($this);

        $this->app->beginTransaction();

        for ($i = 0; $i < $cartonCount; $i++) {

            $batchData['cartonID'] = sprintf('%04d', $nextCartonID++);

            $this->app->runQuery($sql, array_values($batchData));

            $tallies->rowCartons[$rowID][] = $nextInvID++;

            // Log the new cartons
            logger::add($this->app);
        }
        // tally::$selectParams property is used in $tallies->insertCartons()
        $tallyID = tally::$selectParams['tallyIDs'][$recNum]['id'];

        tally::$selectParams['tallyRowIDs'][$tallyID][] = $rowID;

        $tallies->insertCartons();

        $count = count($tallies->rowCartons[$rowID]);

        foreach ($logFields as $logField) {
            $logData[$logField] = [
                'fromValues' => 0,
                'toValues' => array_fill(0, $count, $batchData[$logField]),
            ];
        }

        logger::edit([
            'db' => $this->app,
            'primeKeys' => $tallies->rowCartons[$rowID],
            'fields' => $logData,
            'transaction' => FALSE,
        ]);

        $this->app->commit();

        return [
            'cartonAdded' => $cartonCount,
            'error' => NULL,
        ];
    }

    /*
    ****************************************************************************
    */

    function getBatchNextCartonID($batchID)
    {
        $sql = 'SELECT    cartonID
                FROM      inventory_cartons
                WHERE     batchID = ?
                ORDER BY  cartonID DESC
                LIMIT 1';

        $result = $this->app->queryResult($sql, [$batchID]);

        return $result['cartonID'] + 1;
    }

    /*
    ****************************************************************************
    */

    function getBatchData($insertFields, $batchID, $plate)
    {
        $params[] = $plate;

        $plateValue = $plate ? ' = ?' : 'IS NULL';

        $params = $plate ? [$batchID, $plate] : [$batchID];

        $sql = 'SELECT    ' . implode(', ', $insertFields) . '
                FROM      inventory_cartons
                WHERE     batchID = ?
                AND       plate ' . $plateValue . '
                ORDER BY  cartonID DESC
                LIMIT 1';

        $result = $this->app->queryResult($sql, $params);

        return $result;
    }


    /*
   ****************************************************************************
   */

    function getInfoCartonByID($id)
    {

        $sql = 'SELECT ca.*,
                CONCAT(
                    co.vendorID,
                    b.id,
                    LPAD(ca.uom, 3, 0),
                    LPAD(ca.cartonID, 4, 0)
                ) AS UCC128

            FROM inventory_cartons ca
            JOIN inventory_batches b ON b.id = ca.batchID
            JOIN inventory_containers co ON b.recNum = co.recNum
            WHERE ca.id = ?';

        $result = $this->app->queryResult($sql, [$id]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getUCCs($invIDs)
    {
        if (! $invIDs) {
            return [];
        }

        $ucc128 = $this->fields['ucc128']['select'];

        $sql = 'SELECT    ca.id,
                          ' . $ucc128 . ' AS ucc128
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                WHERE     ca.id IN (' . $this->app->getQMarkString($invIDs) . ')
                ';

        $resutls = $this->app->queryResults($sql, $invIDs);

        $keys = array_keys($resutls);
        $ids = array_column($resutls, 'ucc128');

        return array_combine($keys, $ids);
    }

    /*
    ****************************************************************************
    */

    function getProcessedCartonsByOrder($orderNumber)
    {
        $orderNumbers = is_array($orderNumber) ? $orderNumber : [$orderNumber];

        if (! $orderNumbers) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($orderNumbers);

        $ucc128 = $this->fields['ucc128']['select'];

        $sql = 'SELECT    ' . $ucc128 . ' AS ucc128
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      neworder n ON n.id = ca.orderID
                WHERE     scanOrderNumber IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $orderNumbers);

        return array_keys($results);
    }

    /*
    ********************************************************************************
    */

    function getOrderShippedInventory($orderNumber)
    {
        if (! $orderNumber) {
            return [];
        }

        $sql = 'SELECT    upc,
                          SUM(uom) AS shipped
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      upcs u ON u.id = b.upcID
                JOIN      neworder n ON n.id = ca.orderID
                WHERE     scanOrderNumber = ?
                GROUP BY  upc';

        $shippedResults = $this->app->queryResults($sql, [$orderNumber]);

        $shippedKeys = array_keys($shippedResults);

        $shippedValues = array_column($shippedResults, 'shipped');

        return array_combine($shippedKeys, $shippedValues);
    }

    /*
    ****************************************************************************
    */

    function getProcessingCartonCount($orderNumbers)
    {
        if (! $orderNumbers) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($orderNumbers);

        $sql = 'SELECT    scanordernumber,
                          COUNT(pc.id) AS cartonCount
                FROM      pick_cartons pc
                JOIN 	  neworder o ON o.id = pc.orderID
                WHERE     scanordernumber IN (' . $qMarks . ')
                AND       pc.active
                GROUP BY  scanordernumber';

        $results = $this->app->queryResults($sql, $orderNumbers);

        $keys = array_keys($results);

        $values = array_column($results, 'cartonCount');

        return array_combine($keys, $values);
    }

    /*
    ****************************************************************************
    */

    function getProcessingInventory($orderIDs)
    {
        if (! $orderIDs) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($orderIDs);

        $sql = 'SELECT    pc.id,
                          ca.id AS invID,
                          plate,
                          ca.statusID,
                          mStatusID,
                          ca.orderID,
                          pc.orderID AS newOrderID
                FROM      inventory_cartons ca
                JOIN 	  pick_cartons pc ON pc.cartonID = ca.id
                WHERE     pc.orderID IN (' . $qMarks . ')
                AND       pc.active';

        $results = $this->app->queryResults($sql, $orderIDs);

        $return = [];

        foreach ($results as $values) {

            $newOrderID = $values['newOrderID'];

            unset($values['newOrderID']);

            $return[$newOrderID][] = $values;
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    public function getCartonCloneByID($ivnIDs)
    {
        if (! $ivnIDs) {
             return [];
        }

        $params = is_array($ivnIDs) ? $ivnIDs : [$ivnIDs];

        $sql = 'SELECT  id,
                        id,
                        uom,
                        plate,
                        locID,
                        mLocID,
                        statusID,
                        mStatusID,
                        batchID
                FROM    inventory_cartons
                WHERE   id IN (' . $this->app->getQMarkString($params) . ')';

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    public function getMaxCartonIDByBatch($batchIDs)
    {
        if (! $batchIDs) {
            return [];
        }

        $params = is_array($batchIDs) ? $batchIDs : [$batchIDs];

        $qMarks = $this->app->getQMarkString($params);

        $sql = 'SELECT      batchID,
                            MAX(cartonID) + 1 AS nextCartonID
                FROM        inventory_cartons
                WHERE       batchID IN (' . $qMarks . ')
                GROUP BY    batchID';

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getProcessedPlatesByOrder($orderNumber)
    {
        $orderNumbers = is_array($orderNumber) ? $orderNumber : [$orderNumber];

        if (! $orderNumbers) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($orderNumbers);

        $sql = 'SELECT DISTINCT plate
                FROM      inventory_cartons ca
                JOIN      neworder n ON n.id = ca.orderID
                WHERE     scanOrderNumber IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $orderNumbers);

        return array_keys($results);
    }

    /*
    ********************************************************************************
    */

    static function getRackedStatusID($app)
    {
        $statuses = new statuses\inventory($app);
        $result = $statuses->getStatusID(self::STATUS_RACKED);

        return $result;
    }

    /*
    ********************************************************************************
    */

    function inventoryTransfer($licensePlates, $invIDs, $warehouseTransfer=NULL)
    {
        $warehouseTransferPallets =
                new \tables\warehouseTransfers\warehouseTransferPallets($this->app);

        $selectFields = '
            plate,
            locID,
            mLocID
        ';

        $oldCartonInfo = $this->getOldCartonInfo($invIDs, $selectFields);

        $updateSQL = 'UPDATE    inventory_cartons
                      SET       plate = ?,
                                locID = ?,
                                mLocID = ?
                      WHERE     id IN ';

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        $arrInventoryIDs = $oldPlates = $newPlates = $oldLocIDs = $locationIDs
                = $newLocIDs = $oldMlocIDs = $newMlocIDs = [];

        foreach ($licensePlates as $licensePlate => $info) {

            $locID = $info['locationInfo']['locID'];

            $params = [];

            foreach ($info['cartonNumbers'] as $cartonData) {

                $params[] = $invID = key($cartonData);
                $oldPlates[] = $arrInventoryIDs[$invID]['oldPlate']
                        = $oldCartonInfo[$invID]['plate'];
                $oldLocIDs[] = $arrInventoryIDs[$invID]['oldLocID']
                        = $oldCartonInfo[$invID]['locID'];
                $oldMlocIDs[] = $oldCartonInfo[$invID]['mLocID'];
                $newPlates[] = $licensePlate;
                $newLocIDs[] = $newMlocIDs[] = $locID;
                $locationIDs[$oldCartonInfo[$invID]['locID']] = TRUE;
            }

            $locationIDs[$locID] = TRUE;

            $qMarkString = $this->app->getQMarkString($params);

            $sql = $updateSQL . '(' . $qMarkString . ' )';

            array_unshift($params, $licensePlate, $locID, $locID);

            $this->app->runQuery($sql, $params);
        }

        logger::edit([
            'db' => $this->app,
            'primeKeys' => array_keys($arrInventoryIDs),
            'fields' => [
                'plate' => [
                    'fromValues' => $oldPlates,
                    'toValues' => $newPlates,
                ],
                'locID' => [
                    'fromValues' => $oldLocIDs,
                    'toValues' => $newLocIDs,
                ],
                'mLocID' => [
                    'fromValues' => $oldMlocIDs,
                    'toValues' => $newMlocIDs,
                ],
            ],
            'transaction' => FALSE,
        ]);

        if ($warehouseTransfer) {
            $warehouseTransferPallets->updateWarehouseTransfer(
                    $warehouseTransfer['plateVendors'],
                    $warehouseTransfer['transferID']
            );
        }

        $this->app->commit();

        $locationIDs = array_keys($locationIDs);
        $locationInfos =
                \tables\locations::getLocationByIds($this->app, $locationIDs);

        $results = [
            'invIDs' => $arrInventoryIDs,
            'locationInfos' => $locationInfos
        ];

        return $results;
    }

    /*
    ********************************************************************************
    */

    static function getNotRackedCartons($app, $invIDs)
    {
        if (empty($invIDs)) {
            return [];
        }

        $invIDs = (array) $invIDs;

        $qMarks = $app->getQMarkString($invIDs);

        $sql = 'SELECT    ca.id
                FROM      inventory_cartons ca
                JOIN      statuses s ON ca.statusID = s.id
                WHERE     ca.id IN (' . $qMarks . ')
                AND       shortName != ?
                ';
        $params = array_merge($invIDs, [self::STATUS_RACKED]);

        $results = $app->queryResults($sql, $params);

        return array_keys($results);
    }

    /*
    ****************************************************************************
    */

    function checkUCC128Invalid($dataInput)
    {
        $return = [];
        foreach ($dataInput as $ucc) {
            if (! ctype_digit($ucc) || strlen($ucc) != 20) {
                $return[] = $ucc;
            }
        }
        return $return;
    }

    /*
    ****************************************************************************
    */

    function getDownloadData($vendorID, $dataInput)
    {
        if (! ($vendorID && $dataInput)) {
            die('Data wrong!');
        }

        $userDB = $this->app->getDBName('users');
        $qMarks = $this->app->getQMarkString($dataInput);

        $getLogSQL = 'SELECT  lv.id,
                              CONCAT(co.vendorID,
                                b.id,
                                LPAD(ca.uom, 3, 0),
                                LPAD(ca.cartonID, 4, 0)
                              ) AS ucc128,
                              logTime AS timeValue,
                              lf.displayName,
                              u.firstName,
                              u.lastName,
                            IF (lf.displayName = "locID", fl.displayName,
                              IF (lf.displayName = "statusID", fs.shortName,
                                IF (lf.displayName = "mStatusID", fms.shortName,
                                  IF (lf.displayName = "mLocID", fml.displayName,
                                  fromValue)))) AS fromValue,
                            IF (lf.displayName = "locID", tl.displayName,
                              IF (lf.displayName = "statusID", ts.shortName,
                                IF (lf.displayName = "mStatusID", tms.shortName,
                                  IF (lf.displayName = "mLocID", tml.displayName,
                                  toValue)))) AS  toValue
                        FROM      logs_values lv
                        JOIN      logs_cartons lc ON lc.id = lv.logID
                        LEFT JOIN ' . $userDB . '.info u ON u.id = lc.userID
                        JOIN      logs_fields lf ON lf.id = lv.fieldID
                        JOIN      inventory_cartons ca ON ca.id = lv.primeKey
                        JOIN      inventory_batches b ON b.id = ca.batchID
                        JOIN      inventory_containers co ON co.recNum = b.recNum
                        JOIN      upcs p ON p.id = b.upcID
                        JOIN      vendors v ON v.id = co.vendorID
                        JOIN      warehouses w ON w.id = v.warehouseID
                        LEFT JOIN locations fl ON fl.id = lv.fromValue
                        LEFT JOIN locations tl ON tl.id = lv.toValue
                        LEFT JOIN locations fml ON fml.id = lv.fromValue
                        LEFT JOIN locations tml ON tml.id = lv.toValue
                        LEFT JOIN neworder fn ON fn.id = lv.fromValue
                        LEFT JOIN neworder tn ON tn.id = lv.toValue
                        LEFT JOIN statuses fs ON fs.id = lv.fromValue
                        LEFT JOIN statuses ts ON ts.id = lv.toValue
                        LEFT JOIN statuses fms ON fms.id = lv.fromValue
                        LEFT JOIN statuses tms ON tms.id = lv.toValue
                        WHERE     vendorID = ?
                        AND       CONCAT(
                                    co.vendorID,
                                    b.id,
                                    LPAD(uom, 3, 0),
                                    LPAD(cartonID, 4, 0)
                                  ) IN (' . $qMarks . ')';

        $params = array_merge([$vendorID], $dataInput);

        $logResults = $this->app->queryResults($getLogSQL, $params);

        $getHistorySQL = 'SELECT      h.id,
                                      CONCAT(
                                        co.vendorID,
                                        b.id,
                                        LPAD(ca.uom, 3, 0),
                                        LPAD(ca.cartonID, 4, 0)
                                      ) AS ucc128,
                                      actionTime AS timeValue,
                                      u.firstName,
                                      u.lastName,
                                      hf.displayName,
                                      fs.shortName AS fromValue,
                                      ts.shortName AS toValue
                            FROM      history h
                            JOIN      inventory_cartons ca ON ca.id = h.rowID
                            JOIN      inventory_batches b ON b.id = ca.batchID
                            JOIN      inventory_containers co ON co.recNum = b.recNum
                            LEFT JOIN ' . $userDB . '.info u ON u.id = h.userID
                            JOIN      history_fields hf ON hf.id = h.fieldID
                            LEFT JOIN statuses fs ON fs.id = h.fromValueID
                            LEFT JOIN statuses ts ON ts.id = h.toValueID
                            WHERE     vendorID = ?
                            AND       CONCAT(
                                        co.vendorID,
                                        b.id,
                                        LPAD(uom, 3, 0),
                                        LPAD(cartonID, 4, 0)
                                      ) IN (' . $qMarks . ')';

        $historyResults = $this->app->queryResults($getHistorySQL, $params);

        return  $data = [
            'Transactional' => $logResults,
            'Manual' => $historyResults
        ];

    }

    /*
    ****************************************************************************
    */

    function processDownloadCartonHistory($data)
    {
        $index = 1;
        $sheetIndex = 0;
        $lengthSheet = count($data);
        $objPHPExcel = new \PHPExcel();

        foreach ($data as $key => $values) {

            $objPHPExcel->setActiveSheetIndex($sheetIndex);
            $timeTitle = $key === 'Transactional' ? 'Log Time' : 'Action Time';
            $objPHPExcel->getActiveSheet()->SetCellValue('A1', 'UCC128');
            $objPHPExcel->getActiveSheet()->SetCellValue('B1', $timeTitle);
            $objPHPExcel->getActiveSheet()->SetCellValue('C1', 'Display Name');
            $objPHPExcel->getActiveSheet()->SetCellValue('D1', 'First Name');
            $objPHPExcel->getActiveSheet()->SetCellValue('E1', 'Last Name');
            $objPHPExcel->getActiveSheet()->SetCellValue('F1', 'From Value');
            $objPHPExcel->getActiveSheet()->SetCellValue('G1', 'To Value');

            foreach ($values as $row) {
                $index++;
                $objPHPExcel->getActiveSheet()->setCellValueExplicit('A' . $index,
                    $row['ucc128'], \PHPExcel_Cell_DataType::TYPE_STRING);
                $objPHPExcel->getActiveSheet()->SetCellValue('B' . $index,
                    $row['timeValue']);
                $objPHPExcel->getActiveSheet()->SetCellValue('C' . $index,
                    $this->getDisplayName($row['displayName']));
                $objPHPExcel->getActiveSheet()->SetCellValue('D' . $index,
                    $row['firstName']);
                $objPHPExcel->getActiveSheet()->SetCellValue('E' . $index,
                    $row['lastName']);
                $objPHPExcel->getActiveSheet()->SetCellValue('F' . $index,
                    $row['fromValue']);
                $objPHPExcel->getActiveSheet()->SetCellValue('G' . $index,
                    $row['toValue']);
            }

            $objPHPExcel->getActiveSheet()->setTitle("$key");
            if ($sheetIndex + 1 < $lengthSheet) {
                $objPHPExcel->createSheet();
                $sheetIndex++;
            }
        }

        $numberDate = config::getDateTime('currentTime');
        $fileName = 'Download_carton_history_' . $numberDate;

        exporter::header($fileName);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /*
    ****************************************************************************
    */

    function getDisplayName($input)
    {
        $return = [
            'locID' => 'Location Name',
            'mLocID' => 'Manual Location',
            'statusID' => 'Status Name',
            'mStatusID' => 'Manual Status',
            'plate' => 'License Plate',
            'isSplit' => 'Split Carton',
            'unSplit' => 'Merge Carton',
            'orderID' => 'Order Number',
        ];

        return isset($return[$input]) ? $return[$input] : $input;
    }

    /*
    ****************************************************************************
    */

    function getWarehouseTransferCartons($plates)
    {
        if (! $plates) {
            return [];
        }

        $statuses = new statuses\inventory($this->app);

        $sql = 'SELECT  id,
                        plate,
                        locID
                FROM    inventory_cartons
                WHERE   plate IN (' . $this->app->getQMarkString($plates) . ')
                AND     statusID = ?
                AND     NOT isSplit
                AND     NOT unSplit';

        $plates[] = $statuses->getStatusID(self::STATUS_RACKED);

        $result = $this->app->queryResults($sql, $plates);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getMutiplePlatesLocation($vendorID, $statusID)
    {
        $sql = 'SELECT    ca.id,
                          ca.locID,
                          plate
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      (
                    SELECT    ca.locID
                    FROM      inventory_cartons ca
                    JOIN      inventory_batches b ON b.id = ca.batchID
                    JOIN      inventory_containers co ON co.recNum = b.recNum
                    WHERE     ca.statusID = ?
                    AND       co.vendorID = ?
                    AND       NOT isSplit
                    AND       NOT unSplit
                    AND       ca.locID IS NOT NULL
                    GROUP BY  ca.locID
                    HAVING    COUNT(DISTINCT plate) > 1
                ) ol ON ol.locID = ca.locID
                JOIN      locations l ON l.id = ol.locID
                WHERE     ca.statusID = ?
                AND       co.vendorID = ?
                AND       NOT isSplit
                AND       NOT unSplit
                ';

        $results = $this->app->queryResults($sql, [
            $statusID,
            $vendorID,
            $statusID,
            $vendorID,
        ]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function updateLicensePlates($data)
    {
        $locationRes = $data['locationResults'];
        $platesLocRes = $data['plateLocationResults'];
        $updateInventory = $data['updateInventory'];
        $statusID = $data['statusID'];

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        $invIDs = $oldPlates = $newPlates = [];

        foreach ($locationRes as $locID => $values) {

            $params = array_unique($values);

            $newPlate = $platesLocRes[$locID];

            foreach ($updateInventory[$locID] as $invID => $plate) {

                $invIDs[] = $invID;

                $oldPlates[] = $plate;
                $newPlates[] = $newPlate;
            }

            $sql = '
                UPDATE    inventory_cartons ca
                SET       plate = ?
                WHERE     ca.statusID = ?
                AND       plate IN (' . $this->app->getQMarkString($params) . ')
                AND       NOT isSplit
                AND       NOT unSplit
                ';

            array_unshift($params, $newPlate, $statusID);

            $this->app->runQuery($sql, $params);
        }

        logger::edit([
            'db' => $this->app,
            'primeKeys' => $invIDs,
            'fields' => [
                'plate' => [
                    'fromValues' => $oldPlates,
                    'toValues' => $newPlates,
                ]
            ],
            'transaction' => FALSE,
        ]);

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

}
