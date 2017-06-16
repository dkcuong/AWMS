<?php

namespace tables\cycleCount;

use common\logger;
use tables\_default;
use tables\inventory\cartons;
use tables\locations;
use tables\statuses;

class cycleCount extends _default
{
    public $primaryKey = 'cc.cycle_count_id';

    public $ajaxModel = 'cycleCount\cycleCount';

    public $orderBy = 'cc.cycle_count_id DESC';

    public $groupBy = 'cc.cycle_count_id';

    public $mainField = 'cc.cycle_count_id';

    const STATUS_NEW = 'NW';
    const STATUS_OPEN = 'OP';
    const STATUS_ASSIGNED = 'AS';
    const STATUS_CYCLE = 'CC';
    const STATUS_RECOUNT = 'RC';
    const STATUS_COMPLETE = 'CP';
    const STATUS_DELETED = 'DL';

    const TEXT_STATUS_CYCLE_NEW = 'New';
    const TEXT_STATUS_CYCLE_OPEN = 'Open';
    const TEXT_STATUS_CYCLE_ASSIGNED= 'Assigned';
    const TEXT_STATUS_CYCLE_CYCLED = 'Cycled';
    const TEXT_STATUS_CYCLE_RECOUNT = 'Recount';
    const TEXT_STATUS_CYCLE_COMPLETED = 'Completed';
    const TEXT_STATUS_CYCLE_DELETED= 'Deleted';

    const TYPE_CUSTOMER = 'CS';
    const TYPE_SKU = 'SK';
    const TYPE_LOCATION = 'LC';
    const COUNT_BY_CARTON = 'CT';
    const COUNT_BY_EACH = 'EA';
    const HAS_COLOR_SIZE_YES = 1;
    const HAS_COLOR_SIZE_NO = 0;
    const NUMBER_ALLOW_INSERT_RECORD = 999;

    const STATUS_INVENTORY_CARTON_LOCK = 'LK';
    const STATUS_INVENTORY_CARTON_ADJUSTED = 'AJ';

    public $statuses;

    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */

    function fields()
    {
        return [
            'action_delete' => [
                'select' => 'cc.cycle_count_id',
                'display' => 'Select',
                'noEdit' => TRUE,
                'ignoreSearch' => TRUE,
                'ignoreExport' => TRUE
            ],
            'cycle_count_id' => [
                'select' => 'cc.cycle_count_id',
                'display' => 'Cycle ID',
                'noEdit' => TRUE
            ],
            'warehouse' => [
                'select' => 'w.displayName',
                'display' => 'Warehouse',
                'noEdit' => TRUE,
                'searcherDD' => 'warehouses',
                'ddField' => 'displayName',
            ],
            'client' => [
                'select' => '
                    GROUP_CONCAT(
                        DISTINCT v.vendorName ORDER BY v.vendorName ASC
                    )',
                'display' => 'Client',
                'noEdit' => TRUE,
                'groupedFields' => 'v.vendorName',
            ],
            'name_report' => [
                'display' => 'Report Name',
                'noEdit' => TRUE
            ],
            'due_dt' => [
                'display' => 'Due Date',
                'searcherDate' => TRUE,
                'noEdit' => TRUE
            ],
            'type' => [
                'select' =>
                'CASE  WHEN type = "' . self::TYPE_CUSTOMER . '" THEN "Customer"
                       WHEN type = "' . self::TYPE_SKU . '" THEN "SKU"
                       ELSE "Location"
                 END',
                'display' => 'Type',
                'noEdit' => TRUE
            ],
            'totalLoc' => [
                'display' => 'Total Locations',
                'select' => 'COUNT(DISTINCT ci.sys_loc)',
                'noEdit' => TRUE
            ],
            'totalSKU' => [
                'display' => 'Total SKUs',
                'select' => 'COUNT(DISTINCT ci.sku)',
                'noEdit' => TRUE

            ],
            'created_by' => [
                'select' => 'CONCAT(us.firstName, " ", us.lastName)',
                'display' => 'Assigned By',
                'noEdit' => TRUE
            ],
            'asgd_id' => [
                'select' => 'CONCAT(u.firstName, " ", u.lastName)',
                'display' => 'Assigned To',
                'noEdit' => TRUE
            ],
            'completed_dt' => [
                'display' => 'Completed Date',
                'searcherDate' => TRUE,
                'noEdit' => TRUE
            ],
            'sts' => [
                'select' =>
                    'CASE WHEN cc.sts = "' . self::STATUS_CYCLE . '" THEN "Cycled"
                          WHEN cc.sts = "' . self::STATUS_OPEN . '" THEN "Open"
                          WHEN cc.sts = "' . self::STATUS_ASSIGNED . '" THEN "Assigned"
                          WHEN cc.sts = "' . self::STATUS_RECOUNT . '" THEN "Recount"
                          WHEN cc.sts = "' . self::STATUS_COMPLETE . '" THEN "Completed"
                          ELSE "Deleted"
                     END',
                'searcherDD' => 'statuses\cycleCount',
                'ddField' => 'displayName',
                'display' => 'Status'
            ],
            'action' => [
                'select' => 'has_color_size',
                'display' => 'Action',
                'ignoreSearch' => TRUE,
                'ignoreExport' => TRUE
            ]
        ];
    }

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'cycle_count cc
                LEFT JOIN ' . $userDB . '.info u ON u.id = cc.asgd_id
                JOIN      ' . $userDB . '.info us ON us.id = cc.created_by
                JOIN      warehouses w ON w.id = cc.whs_id
                JOIN      count_items ci ON ci.cycle_count_id = cc.cycle_count_id
                JOIN      vendors v on v.id = ci.vnd_id';
    }

    /*
    ****************************************************************************
    */

    function processCreateCycleCount($data)
    {
        $userID = \access::getUserID();
        $status = $data['assigneeTo'] ?
            self::STATUS_ASSIGNED : self::STATUS_OPEN;

        $return = [
            'status' => FALSE,
            'errors' => FALSE,
            'warning' => FALSE
        ];

        $post = [
            'reportName'   => getDefault($data['reportName']),
            'description'  => getDefault($data['description']),
            'warehouseID'  => getDefault($data['warehouseID']),
            'dueDate'      => getDefault($data['dueDate']),
            'assigned'     => getDefault($data['assigneeTo']),
            'byType'       => strtoupper(getDefault($data['filterBy'])),
            'byUOM'        => getDefault($data['cycleCountByOUM']),
            'byColorSize'  => getDefault($data['cycleCountByColorSize']),
            'locationFrom' => getDefault($data['locFrom']),
            'locationTo'   => getDefault($data['locTo']),
            'sku'          => getDefault($data['sku']),
            'customer'     => getDefault($data['customer']),
            'createBy'     => $userID,
            'status'       => $status
        ];

        // Check data submit
        $checkResults = $this->validateCycleDataInput($post);

        if ($checkResults['errors']) {
            $return['errors'] = $checkResults['errors'];
            return $return;
        }

        // Process create cycle count
        $results = $this->processCreateNewCycleCount($post);

        if ($results) {
            $return['status'] = TRUE;
        } else {
            $return['errors'][] = 'No Inventory for Cycle Count.';
        }

        return $return;
    }

    /*
   ****************************************************************************
   */

    function processCreateNewCycleCount($input)
    {
        $nextCycleID = $this->getNextID('cycle_count');
        $nextItemID = $this->getNextID('count_items');
        $statuses = new statuses\inventory($this->app);
        $lockID = $statuses->getStatusID(cartons::STATUS_LOCKED);

        $return = $this->getCartonData($input);

        if (! $return['itemData']) {
            return FALSE;
        }

        $input['dataInput'] = $return['dataInput'];

        $this->processInsertData([
            'dataInput' => $input,
            'cycleID'   => $nextCycleID,
            'itemID'    => $nextItemID,
            'itemData'  => $return['itemData'],
            'lockID'    => $lockID
        ]);

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function createCycleCount($data)
    {
        $reportName = getDefault($data['reportName']);
        $warehouseID = getDefault($data['warehouseID']);
        $description = getDefault($data['description']);
        $dueDate = getDefault($data['dueDate']);
        $assigned = getDefault($data['assigned']);
        $byType = getDefault($data['byType']);
        $byUOM = getDefault($data['byUOM']);
        $createBy = getDefault($data['createBy']);
        $byColorSize = getDefault($data['byColorSize']);
        $status = getDefault($data['status']);
        $dataInput = json_encode(getDefault($data['dataInput']));

        $params = [
            $reportName,
            $byUOM,
            $warehouseID,
            $byType,
            $description,
            $dueDate,
            $createBy,
            $createBy,
            $assigned,
            $status,
            $byColorSize,
            $dataInput
        ];

        $qMark = $this->app->getQMarkString($params);

        $sql = 'INSERT INTO cycle_count (
                    name_report,
                    cycle_count_by_uom,
                    whs_id,
                    type,
                    descr,
                    due_dt,
                    created_by,
                    updated_by,
                    asgd_id,
                    sts,
                    has_color_size,
                    data
                ) VALUES (' . $qMark . ')';

        $this->app->runQuery($sql, $params);
    }

    /*
    ****************************************************************************
    */

    function processInsertData($params)
    {
        $cycleID = getDefault($params['cycleID']);
        $itemData = getDefault($params['itemData']);
        $itemID = getDefault($params['itemID']);
        $lockID = getDefault($params['lockID']);
        $dataInput = getDefault($params['dataInput']);
        $optionalValue = getDefault($params['optionalValue']);

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        if ($dataInput) {
            // Create new Cycle Count
            $this->createCycleCount($dataInput);
        }

        // Insert Count Item
        $this->insertCountItem($cycleID, $itemData, $optionalValue);

        // Insert Lock carton
        $results = $this->insertLockCarton($itemData, $itemID);

        // Lock inventory carton
        $this->updateInventoryCartonStatus($results, $lockID);

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function insertLockCarton($data, $countItemID)
    {

        $params = $paramsSql = $cartonIDs = $invIDs = $statusIDs = [];

        $sql = 'INSERT INTO locked_cartons (
                        vnd_id,
                        whs_id,
                        count_item_id,
                        invt_ctn_id,
                        sts,
                        mn_sts_id,
                        mn_loc_id
                    ) VALUES ';

        foreach ($data as $cartons) {

            if (! $cartons['systemQty']) {
                continue;
            }

            $index = 0;
            $length = count($cartons['cartons']);

            foreach ($cartons['cartons'] as $row) {

                $invIDs[] = $row['id'];
                $statusIDs[] = $row['statusID'];

                $tmp = [];
                $params[] = $tmp[] = getDefault($row['vendorID']);
                $params[] = $tmp[] = getDefault($row['warehouseID']);
                $params[] = $tmp[] = $countItemID;
                $params[] = $tmp[] = getDefault($row['id']);
                $params[] = $tmp[] = getDefault($row['statusID']);
                $params[] = $tmp[] = getDefault($row['mStatusID']);
                $params[] = $tmp[] = getDefault($row['mLocID']);

                $qMark = $this->app->getQMarkString($tmp);
                $paramsSql[] = '(' . $qMark . ')';

                $index++;

                if (! ($index % self::NUMBER_ALLOW_INSERT_RECORD)
                || $index == $length) {

                    $tmpSql = $sql . implode(',', $paramsSql);

                    $this->app->runQuery($tmpSql, $params);

                    $params = $paramsSql = [];
                }
            }

            $countItemID++;
        }

        return [
            'cartonIDs' => $invIDs,
            'statusIDs' => $statusIDs
        ];
    }

    /*
    ****************************************************************************
    */

    function insertCountItem($cycleCountID, $data, $optionalValue)
    {
        $actQty = getDefault($optionalValue['actQty'], NULL);
        $actLoc = getDefault($optionalValue['actLoc'], NULL);

        $status = processAuditCarton::STATUS_COUNT_ITEM_NEW;

        $sql = 'INSERT INTO count_items (
                    cycle_count_id,
                    vnd_id,
                    upc_id,
                    sku,
                    size,
                    color,
                    pack_size,
                    pcs,
                    allocate_qty,
                    act_qty,
                    act_loc,
                    sys_qty,
                    sys_loc,
                    accepted,
                    sts
                ) VALUES ';

        $params = $paramsSql = [];

        foreach ($data as $row) {
            if (! $row['systemQty']) {
                continue;
            }

            $tmp = [];
            $params[] = $tmp[] = $cycleCountID;
            $params[] = $tmp[] = getDefault($row['vendorID']);
            $params[] = $tmp[] = getDefault($row['upcID']);
            $params[] = $tmp[] = getDefault($row['sku']);
            $params[] = $tmp[] = getDefault($row['size']);
            $params[] = $tmp[] = getDefault($row['color']);
            $params[] = $tmp[] = getDefault($row['packSize']);
            $params[] = $tmp[] = getDefault($row['byUOM']);
            $params[] = $tmp[] = getDefault($row['allocateQty']);
            $params[] = $tmp[] = $actQty;
            $params[] = $tmp[] = $actLoc;
            $params[] = $tmp[] = getDefault($row['systemQty']);
            $params[] = $tmp[] = getDefault($row['locID']);
            $params[] = $tmp[] = getDefault($row['setDate']);
            $params[] = $tmp[] = $status;

            $qMark = $this->app->getQMarkString($tmp);
            $paramsSql[] = '(' . $qMark . ')';

            $tmpSql = $sql .  implode(',', $paramsSql);

            $this->app->runQuery($tmpSql, $params);

            $params = $paramsSql = [];
        }
    }

    /*
    ****************************************************************************
    */

    function updateInventoryCartonStatus($data, $statusID)
    {
        $invIDs = getDefault($data['cartonIDs']);
        $statusIDs = getDefault($data['statusIDs']);

        if (! ($invIDs && $statusID)) {
            return false;
        }

        $qMark = $this->app->getQMarkString($invIDs);

        $params = array_merge([$statusID], $invIDs);

        $sql = 'UPDATE  inventory_cartons
                SET     statusID = ?
                WHERE   id IN (' . $qMark . ')';

        $this->app->runQuery($sql, $params);

        // logging cartons status
        logger::edit([
            'db' => $this->app,
            'primeKeys' => $invIDs,
            'fields' => [
                'statusID' => [
                    'fromValues' => $statusIDs,
                    'toValues' => $statusID
                ]
            ],
            'transaction' => FALSE
        ]);
    }

    /*
    ****************************************************************************
    */

    function getCartonData($data)
    {
        $whereClause = NULL;
        $measureType = strtoupper($data['byUOM']);

        $statuses = new statuses\inventory($this->app);

        $statusIDs = $statuses->getStatusIDs([
            cartons::STATUS_RACKED,
            cartons::STATUS_RESERVED,
            cartons::STATUS_LOCKED,
            cartons::STATUS_DISCREPANCY,
        ]);

        $params[] = $data['warehouseID'];

        switch ($data['byType']) {
            case self::TYPE_LOCATION:
                // by location
                $locFrom = getDefault($data['locationFrom']);
                $locTo = getDefault($data['locationTo']);

                $locationArray = $this->getLocationID($data['warehouseID'],
                        $locFrom, $locTo);

                if (! $locationArray) {
                    return FALSE;
                }

                $locInput = array_keys($locationArray);

                $clause = $this->getClause('locID', $locInput, []);

                if ($clause) {
                    $whereClause .= $clause['where'];
                    $params = array_merge($params, $clause['dataInput']);
                    $dataInput = $clause['dataInput'];
                } else {
                    return FALSE;
                }

                break;
            case self::TYPE_SKU:
                // by sku
                $skus = $this->getInputSKU($data['sku']);

                $clause = $this->getClause('sku', $skus, []);

                if ($clause) {
                    $whereClause .= $clause['where'];
                    $params = array_merge($params, $clause['dataInput']);
                    $dataInput = $clause['dataInput'];
                } else {
                    return FALSE;
                }

                break;
            default:
                // by customer
                $whereClause .= ' AND ic.vendorID = ?';

                $dataInput[] = $params[] = $data['customer'];

                break;
        }

        $whereClause .= 'AND ((statusID = ? AND ca.mStatusID IN(?, ?))
                                OR statusID = ?) ';

        $params[] = $statusIDs[cartons::STATUS_RACKED]['id'];
        $params[] = $statusIDs[cartons::STATUS_RACKED]['id'];
        $params[] = $statusIDs[cartons::STATUS_RESERVED]['id'];
        $params[] = $statusIDs[cartons::STATUS_DISCREPANCY]['id'];

        $sql = $this->getCycleCountQuery([
            'measureType' => $measureType,
            'whereClause' => $whereClause,
            'cycleType' => $data['byType']
        ]);

        $results = $this->app->queryResults($sql, $params);

        $results = $this->reStructureData($measureType, $results, $statusIDs);

        return [
            'itemData' => $results,
            'dataInput' => $dataInput
        ];
    }

    /*
    ****************************************************************************
    */

    public function getClause($field, $fieldData, $reservedData)
    {
        if ($reservedData) {
            // remove skus or locations that have reserved inventory
            $fieldData = array_diff($fieldData, $reservedData);
        }

        if (! $fieldData) {
            return FALSE;
        }

        $qMarks = $this->app->getQMarkString($fieldData);

        return [
            'where' => ' AND ' . $field . ' IN (' . $qMarks . ')',
            'dataInput' => $fieldData
        ];
    }

    /*
    ****************************************************************************
    */

    function reStructureData($type, $cartonData, $statusIDs)
    {
        $itemData = [];

        $statusIdRS = $statusIDs[cartons::STATUS_RESERVED]['id'];
        $statusIdRK = $statusIDs[cartons::STATUS_RACKED]['id'];
        // group by (v.id, u.id, l.id, ca.uom) for count items
        foreach ($cartonData as $cartonID => $carton) {
            $primaryKey = $carton['primaryKey'];
            $carton['id'] = $cartonID;
            $systemQty = trim($type) === 'EACH' ? $carton['packSize'] : 1;

            if (isset($itemData[$primaryKey])) {
                if ($carton['mStatusID'] == $statusIdRS) {
                    $itemData[$primaryKey]['allocateQty'] += $systemQty;
                } else {
                    $itemData[$primaryKey]['systemQty'] += $systemQty;
                }

            } else {
                if ($carton['mStatusID'] == $statusIdRS) {
                    $carton['allocateQty'] = $systemQty;
                    $carton['systemQty'] = 0;
                } else {
                    $carton['systemQty'] = $systemQty;
                    $carton['allocateQty'] = 0;
                }

                $itemData[$primaryKey] = $carton;
            }

            if ($carton['mStatusID'] == $statusIdRK) {
                $itemData[$primaryKey]['cartons'][] = $carton;
            }
        }

        return $itemData;
    }

    /*
    ****************************************************************************
    */

    public function getCycleCountQuery($data)
    {
        $type = strtoupper($data['measureType']);
        $where = $data['whereClause'];
        $addSKU = getDefault($data['addSKU']);
        $hasSizeColor = getDefault($data['hasSizeColor']);

        $arrType = ['CARTON', 'EACH'];

        if (! in_array($type, $arrType)) {
            die('Wrong type!');
        }

        $fieldList = '
            ca.id,
            setDate,
            vendorID,
            upcID,
            CONCAT(v.id, u.id, l.id, ca.uom) AS primaryKey,
            sku,
            size,
            color,
            uom AS packSize,
            "' . $type . '" AS byUOM,
            locID,
            mLocID,
            statusID,
            mStatusID,
            v.warehouseID';

        $groupBy = '
            v.id,
            l.id,
            ca.id,
            ca.uom';

        $where .= $addSKU ? 'AND v.id = ?
                             AND uom = ?' : '';

        $where .= $hasSizeColor ? 'AND u.size = ?
                                   AND u.color = ?' : '';

        $results = '
            SELECT    ' . $fieldList . '
            FROM      inventory_containers ic
            JOIN      inventory_batches ib ON ib.recNum = ic.recNum
            JOIN      inventory_cartons ca ON ca.batchID = ib.id
            JOIN      upcs u ON u.id = ib.upcID
            JOIN      locations l ON l.id = ca.locID
            JOIN      vendors v ON v.id = ic.vendorID
            WHERE     NOT isSplit
            AND       NOT unSplit
            AND       v.warehouseID = ?
            ' . $where . '
            GROUP BY ' . $groupBy;

        return $results;
    }

    /*
    ****************************************************************************
    */

    public function getCycleCountInfoById($id)
    {
        $userDB = $this->app->getDBName('users');

        $sql = 'SELECT    cc.name_report,
                          cc.cycle_count_id ,
                          cc.cycle_count_by_uom,
                          cc.whs_id,
                          cc.type,
                          cc.descr,
                          cc.due_dt,
                          cc.created_by,
                          cc.updated_by,
                          cc.has_color_size AS bySizeColor,
                          cc.created,
                          cc.updated,
                          cc.asgd_id,
                          CONCAT(u.firstName, " ",u.lastName) AS assigner,
                          CONCAT(us.firstName, " ",us.lastName) AS assigneeBy,
                          ci.vnd_id,
                          cc.sts,
                          cc.data,
                          (CASE WHEN cc.type = "LC" THEN "Location"
                               WHEN cc.type = "SK" THEN "SKU"
                               ELSE "Customer"
                          END) AS cycleType,
                          (CASE WHEN cc.sts = "CC" THEN "Cycled"
                               WHEN cc.sts = "OP" THEN "Open"
                               WHEN cc.sts = "AS" THEN "Assigned"
                               WHEN cc.sts = "RC" THEN "Recount"
                               ELSE "Completed"
                          END) AS status
                FROM      cycle_count cc
                LEFT JOIN ' . $userDB . '.info u ON u.id = cc.asgd_id
                LEFT JOIN ' . $userDB . '.info us ON us.id = cc.created_by
                JOIN      count_items ci
                ON        cc.cycle_count_id = ci.cycle_count_id
                WHERE     cc.cycle_count_id = ?
                GROUP BY  cc.cycle_count_id';

        $results = $this->app->queryResult($sql, [$id]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getInputSKU($data)
    {
        $return = preg_split('/[\s,]+/', $data, 0, PREG_SPLIT_NO_EMPTY);

        return $data ? array_map('trim', $return) : [];
    }

    /*
    ****************************************************************************
    */

    function getLocationID($warehouse, $locFrom, $locTo)
    {
        $locIDFrom = $this->getRangeLocationID($locFrom, TRUE);
        $locIDTo = $this->getRangeLocationID($locTo);

        if (! ($locIDFrom && $locIDTo)) {
            return FALSE;
        }

        $sql = 'SELECT    id,
                          displayName
                FROM      locations
                WHERE     displayName BETWEEN ? AND ?
                AND       warehouseID = ?
                ORDER BY  displayName';

        $results = $this->app->queryResults($sql, [
            $locIDFrom,
            $locIDTo,
            $warehouse
        ]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    private function getRangeLocationID($locationName, $point=FALSE)
    {
        $field = $point ? 'MIN(displayName)' : 'MAX(displayName)';

        $sql = 'SELECT  ' . $field . ' AS displayName
                FROM    locations
                WHERE   displayName LIKE ?';

        $result = $this->app->queryResult($sql, [$locationName . '%']);

        return $result['displayName'];
    }

    /*
    ****************************************************************************
    */

    function getCycleStatus($cycleID)
    {
        $sql = 'SELECT  has_color_size AS hasSizeColor,
                        sts AS cycleStatus,
                        cycle_count_by_uom AS byUOM
                FROM    cycle_count
                WHERE   cycle_count_id = ?';

        $results = $this->app->queryResult($sql, [$cycleID]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getSKUByClientID($sku, $clientID)
    {
        if (! $sku) {
            return FALSE;
        }

        $results = [];

        $params = [$sku . '%', $clientID];

        $sql = 'SELECT    u.sku
                FROM      vendors v
                JOIN      inventory_containers ic ON ic.vendorID = v.id
                JOIN      inventory_batches ib ON ib.recNum = ic.recNum
                JOIN      upcs u ON ib.upcID = u.id
                WHERE     u.sku LIKE ?
                AND       v.id = ?
                LIMIT     10';

        $skus = $this->app->queryResults($sql, $params);

        if (! $skus) {
            return $results;
        }

        $keySKUs = array_keys($skus);

        foreach ($keySKUs as $row) {
            $results[] = [
                'value' => $row
            ];
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getSKUByWarehouseID($sku, $warehouseID)
    {
        if (! $sku || ! $warehouseID) {
            return FALSE;
        }

        $results = [];

        $sql = 'SELECT  u.sku
                FROM    upcs u
                JOIN    inventory_batches ib ON ib.upcID = u.id
                JOIN    inventory_containers ic ON ic.recNum = ib.recNum
                JOIN    vendors v ON v.id = ic.vendorID
                JOIN    warehouses w ON w.id = v.warehouseID
                WHERE   u.sku LIKE ?
                AND     w.id = ?
                LIMIT   10';

        $params = [$sku . '%', $warehouseID];

        $skus = $this->app->queryResults($sql, $params);

        if (! $skus) {
            return $results;
        }

        $keySKUs = array_keys($skus);

        foreach ($keySKUs as $row) {
            $results[] = [
                'value' => $row
            ];
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function loadUPCInfoFromAjax($params)
    {
        $results = [];
        $input = $params['input'];
        $clientID = $params['clientID'];
        $sku = $params['sku'];
        $type = strtolower($params['type']);

        if (! $this->validateGetTypeBySku($params)) {
            return FALSE;
        }

        $field = $type == 'size' ? 'u.size' : 'u.color';

        $paramSQl = [$input . '%', $clientID, $sku];

        $sql = 'SELECT  ' . $field . '
                FROM    vendors v
                JOIN    inventory_containers ic ON ic.vendorID = v.id
                JOIN    inventory_batches ib ON ib.recNum = ic.recNum
                JOIN    upcs u ON ib.upcID = u.id
                WHERE   ' . $field . ' LIKE ?
                AND     v.id = ?
                AND     u.sku = ?
                LIMIT   10';

        $data = $this->app->queryResults($sql, $paramSQl);

        if (! $data) {
            return $results;
        }

        $data = array_keys($data);

        foreach ($data as $row) {
            $results[] = [
                'value' => $row
            ];
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function validateGetTypeBySku($params)
    {
        $input = $params['input'];
        $clientID = $params['clientID'];
        $sku = $params['sku'];
        $type = $params['type'];

        if (! in_array($type, ['size', 'color'])) {
            die('Wrong type!');
        }

        return $sku && $clientID && $input;
    }

    /*
    ****************************************************************************
    */

    public static function searchLocationByWarehouse(
        $app, $warehouseID, $searchValue
    ) {
        $locations = self::getLocationsCycleByWarehouseId(
                $app, $warehouseID, $searchValue);

        $values = $locations ? array_keys($locations) : [];

        if (! is_string(reset($values))) {
            foreach ($values as &$value) {
                $value = [
                    'value' => $value,
                ];
            }
        }
        return $values;
    }

    /*
    ****************************************************************************
    */

    public static function getLocationsCycleByWarehouseId (
        $app, $warehouseID, $search
    ) {
        if (! $warehouseID) {
            return [];
        }

        $sql = 'SELECT  displayName
                FROM    locations
                WHERE   warehouseID = ?
                AND     displayName LIKE ?
                AND     NOT isShipping
                LIMIT   10';

        $results = $app->queryResults($sql, [$warehouseID, $search . '%']);

        return $results;
    }

    /*
    ****************************************************************************
    */

    public static function checkLocationOnWarehouse(
        $app, $location, $warehouseID
    ) {
       $locations = new locations($app);

        $result =
                $locations->checkWarehouseLocation ([$location], $warehouseID);

        return $result ? TRUE : FALSE;
    }

    /*
    ****************************************************************************
    */

    public function updateCycleCountStatus($cycleID, $status)
    {
        $params = is_array($cycleID) ? $cycleID : [$cycleID];

        $qMark = $this->app->getQMarkString($params);

        $sql = 'UPDATE  cycle_count
                SET     sts = ?
                WHERE   cycle_count_id IN (' . $qMark . ')';

        array_unshift($params, $status);

        $this->app->runQuery($sql, $params);
    }

    /*
    ****************************************************************************
    */

    function validateCycleDataInput($data)
    {
        $reservedData = $errors = [];

        switch ($data['byType']) {
            case self::TYPE_LOCATION:
                $locations = [
                    'locFrom' => $data['locationFrom'],
                    'locTo' => $data['locationTo']
                ];

                $errors = $this->checkLocationInput($locations,
                        $data['warehouseID']);

                break;
            case self::TYPE_SKU:
                // by sku
                $skus = $this->getInputSKU($data['sku']);

                $errors = $this->checkSKUInput($data['byColorSize'],
                    $skus, $data['warehouseID']);

                break;
            default:
                // by customer
                break;
        }

        return [
            'reservedData' => $reservedData,
            'errors' => $errors ? $errors : FALSE
        ];
    }

    /*
    ****************************************************************************
    */

    function checkSKUInput($byColorSize, $data, $warehouseID)
    {
        $return = $errors = [];

        $qMark = $this->app->getQMarkString($data);

        $params = array_merge($data, [$warehouseID]);

        $sql = 'SELECT   sku,
                         COUNT(u.id) AS amount
                FROM     upcs u
                JOIN     inventory_batches b ON b.upcID = u.id
                JOIN     inventory_containers co ON co.recNum = b.recNum
                JOIN     vendors v ON v.id = co.vendorID
                WHERE    sku IN (' . $qMark . ')
                AND      warehouseID = ?
                GROUP BY sku';

        $results = $this->app->queryResults($sql, $params);

        $invalidSKU = array_diff($data, array_keys($results));

        if ($invalidSKU) {
            $return[] = 'The following SKU(s): <strong>'
                . implode(', ', $invalidSKU)
                . '</strong> does not exist in warehouse.';

        }

        foreach ($results as $sku => $value) {
            if (! $byColorSize && $value['amount'] > 1) {
                $errors[] = $sku;
            }
        }

        if ($errors) {
            $return[] = 'The following SKU(s): <strong>'
                . implode(', ', $errors)
                . '</strong> have size and color.';
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function checkLocationInput($locationInput, $warehouseID)
    {
        if (! $locationInput) {
            return ['Input Locations range'];
        }
        // LIMIT to 50 invalid locations in order to prevent system crash when
        // otputting large array of invalid locations
        $sql = 'SELECT    displayName
                FROM      locations
                WHERE     displayName BETWEEN ? AND ?
                AND       warehouseID != ?
                LIMIT 50';

        $results = $this->app->queryResults($sql, [
            $locationInput['locFrom'],
            $locationInput['locTo'],
            $warehouseID,
        ]);

        $invalidLocations = array_keys($results);

        return $invalidLocations ? [
            'The following Location Names belong to other warehouse(s):<br><br>'
          . '<strong>' . implode('<br>', $invalidLocations) . '</strong>'
        ] : FALSE;
    }

    /*
    ****************************************************************************
    */

    public static function getStatusCycleCount()
    {
        return [
            self::STATUS_NEW        => self::TEXT_STATUS_CYCLE_NEW,
            self::STATUS_OPEN       => self::TEXT_STATUS_CYCLE_OPEN,
            self::STATUS_ASSIGNED   => self::TEXT_STATUS_CYCLE_ASSIGNED,
            self::STATUS_CYCLE      => self::TEXT_STATUS_CYCLE_CYCLED,
            self::STATUS_RECOUNT    => self::TEXT_STATUS_CYCLE_RECOUNT,
            self::STATUS_COMPLETE   => self::TEXT_STATUS_CYCLE_COMPLETED,
            self::STATUS_DELETED    => self::TEXT_STATUS_CYCLE_DELETED
        ];
    }

    /*
    ****************************************************************************
    */

    public function processDeleteCycleCount($cycleIDs)
    {
        $cycleIDs = is_array($cycleIDs) ? $cycleIDs : [$cycleIDs];

        $validate = $this->isValidateDeleteCycleCount($cycleIDs);

        if (! $validate['status']) {
            return $validate;
        }

        $processAudit = new processAuditCarton($this->app);

        $this->statuses[cartons::STATUS_LOCKED] =
                $processAudit->statusLockCarton;

        $data = $this->getDataDeleteCycle($cycleIDs);

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        $this->deleteCycleCounts($data, $cycleIDs, $processAudit);

        $this->app->commit();

        $reuslt = [
            'status' => TRUE,
            'msg' => 'Delete successful'
        ];

        return $reuslt;
    }

    /*
    ****************************************************************************
    */

    private function checkRoleDeleteCycle($cycleIDs)
    {
        $qMarks = $this->app->getQMarkString($cycleIDs);

        $sql = 'SELECT  cycle_count_id
                FROM    cycle_count
                WHERE   cycle_count_id IN (' . $qMarks . ')
                AND     created_by != ?';

        $userID = \access::getUserID();

        $params = array_merge($cycleIDs, [$userID]);

        $result = $this->app->queryResults($sql, $params);

        return array_keys($result);
    }

    /*
    ****************************************************************************
    */

    public function deleteCycleCounts($invIDs, $cycleIDs, $model)
    {
        if (! $invIDs) {
            return FALSE;
        }
        $model->restoreInventoryStatus($invIDs);
        $model->deleteLockedCarton(array_keys($invIDs));
        $this->updateStatusCountItemByCycle($cycleIDs, self::STATUS_DELETED);
        $this->updateCycleCountStatus($cycleIDs, self::STATUS_DELETED);

    }

    /*
    ****************************************************************************
    */

    private function updateStatusCountItemByCycle($cycleID, $status)
    {
        $params = is_array($cycleID) ? $cycleID : [$cycleID];

        $qMark = $this->app->getQMarkString($params);

        $sql = 'UPDATE  count_items
                SET     sts = ?
                WHERE   cycle_count_id IN (' . $qMark . ')';

        array_unshift($params, $status);

        $this->app->runQuery($sql, $params);
    }

    /*
    ****************************************************************************
    */

    private function getCartonLockedHasCountItem($cycleID)
    {
        if (! $cycleID) {
            return [];
        }

        $params = is_array($cycleID) ? $cycleID : [$cycleID];

        $qMark = $this->app->getQMarkString($params);

        $sql = ' SELECT   lc.invt_ctn_id,
                          lc.sts,
                          lc.mn_sts_id
                 FROM     locked_cartons lc
                 JOIN     count_items ci ON ci.count_item_id = lc.count_item_id
                 WHERE    ci.cycle_count_id IN (' . $qMark . ')';

        $result = $this->app->queryResults($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */

    private function getCartonLockedNoneCountItem($cycleID)
    {
        if (! $cycleID) {
            return [];
        }

        $params = is_array($cycleID) ? $cycleID : [$cycleID];

        $qMark = $this->app->getQMarkString($params);

        $sql = 'SELECT  ic.id
                FROM    inventory_cartons ic
                JOIN    inventory_batches ib ON ib.id = ic.batchID
                JOIN    inventory_containers icc ON icc.recNum = ib.recNum
		JOIN 	count_items coi
                ON      (coi.upc_id = ib.upcID
                        AND coi.pack_size = ic.uom
                        AND coi.vnd_id = icc.vendorID)
                WHERE   coi.sys_loc = ic.locID
                AND     ic.statusID = ?
                AND     coi.cycle_count_id IN (' . $qMark . ')';

        array_unshift($params, $this->statuses[cartons::STATUS_LOCKED]);

        $result = $this->app->queryResults($sql, $params);

        return array_keys($result);
    }

    /*
    ****************************************************************************
    */

    private function isValidateDeleteCycleCount($cycleIDs)
    {
        $result = [
            'status' => FALSE
        ];

        if (! $cycleIDs) {
            $result['msg'] = 'Cycle Id is invalid!';
            return $result;
        }

        $canNotDelete = $this->checkRoleDeleteCycle($cycleIDs);

        if ($canNotDelete) {
            $result['msg'] = 'You can not delete cycle count IDs: ' .
                implode(',', $canNotDelete);
            return $result;
        }

        $result['status'] = TRUE;

        return $result;
    }

    /*
    ****************************************************************************
    */

    public function getDataDeleteCycle($cycleIDs)
    {
        $cartonLocked = $this->getCartonLockedCycleCount($cycleIDs);

        return $cartonLocked;
    }

    /*
    ****************************************************************************
    */

    private function getCartonLockedCycleCount($cycleIDs)
    {
        $result = $this->getCartonLockedHasCountItem($cycleIDs);

        if (! $result) {
            $result = $this->getCartonLockedNoneCountItem($cycleIDs);
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    function processSearchSKU($sku)
    {
        if (! $sku) {
            return FALSE;
        }

        $sql = 'SELECT  cc.cycle_count_id
                FROM    cycle_count cc
                JOIN    count_items ci ON ci.cycle_count_id = cc.cycle_count_id
                WHERE   ci.sku = ?
                AND     cc.sts NOT IN (?, ?)';

        $result = $this->app->queryResults($sql, [
            $sku,
            self::STATUS_COMPLETE,
            self::STATUS_DELETED
        ]);

        return array_keys($result);
    }

    /*
    ****************************************************************************
    */

    function checkUserInCycleGroup()
    {
        $userDB = $this->app->getDBName('users');

        $sql = 'SELECT  u.id
                FROM    ' . $userDB . '.info u
                JOIN    user_groups ug ON ug.userID = u.id
                JOIN    groups g ON g.id = ug.groupID
                WHERE   g.hiddenName = "cycleCountOperators"
                AND     ug.active
                AND     u.id = ?';

        $userID = \access::getUserID();
        $result = $this->app->queryResults($sql, [$userID]);

        return $result ? TRUE : FALSE;
    }

    /*
    ****************************************************************************
    */

}
