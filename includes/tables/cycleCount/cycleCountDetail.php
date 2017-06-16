<?php

namespace tables\cycleCount;

use common\pdf;
use tables\_default;
use tables\inventory\cartons;
use tables\statuses;
use tables\vendors;

class cycleCountDetail extends _default
{
    public $displaySingle = 'SKU';

    public $primaryKey = 'c.count_item_id';

    public $ajaxModel = 'cycleCount\cycleCountDetail';

    static $rowHeight = 6;

    static $titleHeight = 10;

    static $default = 6;

    static $pageIndex = 1;

    const PLATE_LENGTH = 8;

    public $sizeColor;

    public $orderBy = 'c.count_item_id DESC';

    public $mainField = 'c.count_item_id';

    public $table = 'cycle_count cc
                    JOIN count_items c ON cc.cycle_count_id = c.cycle_count_id
                    JOIN locations l ON l.id = c.sys_loc
                    LEFT JOIN locations ls ON ls.id = c.act_loc
                    JOIN vendors v ON v.id = c.vnd_id
                    JOIN warehouses w ON w.id = v.warehouseID';

    public $customAddRows = 'cycleCount\cycleCountDetail';

    public $customInsert = 'cycleCount\cycleCountDetail';

    public $mainTable = 'cycle_count';

    const STATUS_NEW = 'NW';
    const STATUS_OPEN = 'OP';
    const STATUS_NOT_APPLICABLE = 'NA';
    const STATUS_RECOUNT = 'RC';
    const STATUS_ACCEPTED = 'AC';
    const STATUS_DELETED = 'DL';

    const TEXT_COUNT_ITEM_STATUS_NEW = 'New';
    const TEXT_COUNT_ITEM_STATUS_OPEN = 'Open';
    const TEXT_COUNT_ITEM_STATUS_NOT_APPLICABLE = 'Not applicable';
    const TEXT_COUNT_ITEM_STATUS_RECOUNT = 'Recount';
    const TEXT_COUNT_ITEM_STATUS_ACCEPTED = 'Accepted';
    const TEXT_COUNT_ITEM_STATUS_DELETED = 'Deleted';

    const VALUE_SYSTEM_DEFAULT_VALUE_ADD_NEW_SKU = 0;

    /*
    ****************************************************************************
    */

    function __construct($app = FALSE)
    {
        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */

    function fields()
    {
        $customJSONLink = customJSONLink('appJSON',
            'autocompleteLocationsWarehouse');

        return [
            'c.count_item_id' => [
                'display' => 'Item ID',
                'noEdit' => TRUE,
                'allowNull' => TRUE
            ],
            'vendorName' => [
                'select' => 'CONCAT(w.shortName, "_", vendorName)',
                'display' => 'Customer',
                'noEdit' => TRUE,
                'canAdd' => TRUE,
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
                'update' => 'vendorName',
                'allowNull' => TRUE
            ],
            'sku' => [
                'display' => 'SKU',
                'noEdit' => TRUE,
                'canAdd' => TRUE,
                'autocomplete' => TRUE
            ],
            'size' => [
                'display' => 'Size',
                'noEdit' => TRUE,
                'canAdd' => TRUE,
                'autocomplete' => TRUE
            ],
            'color' => [
                'display' => 'Color',
                'noEdit' => TRUE,
                'canAdd' => TRUE,
                'autocomplete' => TRUE
            ],
            'pack_size' => [
                'display' => 'UOM',
                'noEdit' => TRUE,
                'canAdd' => TRUE,
                'inputNumber' => TRUE
            ],
            'allocate_qty' => [
                'display' => 'Allocate Qty',
                'noEdit' => TRUE,
            ],
            'sys_qty' => [
                'select' => 'ROUND(
                                IF(cc.cycle_count_by_uom = "each",
                                    c.sys_qty / pack_size,
                                    c.sys_qty
                                )
                            )',
                'display' => 'Total Cartons',
                'noEdit' => TRUE,
                'canAdd' => TRUE,
                'allowNull' => TRUE
            ],
            'total_piece' => [
                'select' => 'ROUND(
                                IF(cc.cycle_count_by_uom = "each",
                                    c.sys_qty,
                                    c.sys_qty * pack_size
                                )
                            )',
                'display' => 'Total Pieces',
                'noEdit' => TRUE
            ],
            'act_qty' => [
                'display' => 'Act Qty',
                'inputNumber' => TRUE
            ],
            'sys_loc' => [
                'select' => 'l.displayName',
                'display' => 'Sys Loc',
                'noEdit' => TRUE,
                'canAdd' => TRUE,
                'allowNull' => TRUE
            ],
            'act_loc' => [
                'select' => 'ls.displayName',
                'autocomplete' => 'locations',
                'autocompleteSelect' => 'displayName',
                'autocompleteLink' => $customJSONLink,
                'display' => 'Act Loc',
                'update' => 'c.act_loc',
                'updateOverwrite' => TRUE,
                'updateTable' => 'locations',
                'updateField' => 'displayName'
            ],
            'sts' => [
                'select'  =>
                    'CASE WHEN c.sts = "' . self::STATUS_OPEN .
                            '" THEN "' . self::TEXT_COUNT_ITEM_STATUS_OPEN . '"
                          WHEN c.sts = "' . self::STATUS_RECOUNT .
                            '" THEN "' . self::TEXT_COUNT_ITEM_STATUS_RECOUNT . '"
                          WHEN c.sts = "' . self::STATUS_ACCEPTED .
                            '" THEN "' . self::TEXT_COUNT_ITEM_STATUS_ACCEPTED . '"
                          WHEN c.sts = "' . self::STATUS_NEW .
                            '" THEN "' . self::TEXT_COUNT_ITEM_STATUS_NEW . '"
                          WHEN c.sts = "' . self::STATUS_DELETED .
                            '" THEN "' . self::TEXT_COUNT_ITEM_STATUS_DELETED . '"
                          ELSE "' . self::TEXT_COUNT_ITEM_STATUS_NOT_APPLICABLE . '"
                      END',
                'display' => 'Status',
                'noEdit' => TRUE
            ]
        ];
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

    function customInsertNewSKU($post)
    {
        $cycleCount = new cycleCount($this->app);
        $statuses = new statuses\inventory($this->app);
        $vendors = new vendors($this->app);

        $cycleID = getDefault($post['cycleID']);
        $nextItemID = $this->getNextID('count_items');
        $whereClause = 'AND u.sku = ?
                        AND ((statusID = ? AND ca.mStatusID IN (?, ?))
                        OR statusID = ?) ';
        $cycleType = strtoupper($post['cycleType']);
        $cycleCountByOUM = strtoupper($post['cycleCountByOUM']);
        $post['sku'] = trim($post['sku']);

        $statusIDs = $statuses->getStatusIDs([
            cartons::STATUS_RACKED,
            cartons::STATUS_RESERVED,
            cartons::STATUS_LOCKED,
            cartons::STATUS_DISCREPANCY
        ]);

        $post['rackID'] = $statusIDs[cartons::STATUS_RACKED]['id'];
        $post['lockID'] = $statusIDs[cartons::STATUS_LOCKED]['id'];
        $post['reservedID'] = $statusIDs[cartons::STATUS_RESERVED]['id'];
        $post['discID'] = $statusIDs[cartons::STATUS_DISCREPANCY]['id'];

        // Validate input data
        $post['upcID'] = $this->validateDataInputAddNew($post);

        if (! $post['upcID']) {
            return 'SKU # ' . $post['sku'] . ' was not found in the inventory';
        }

        // Get locationID
        $post['actualLoc'] = $vendors->getLocationIDByWarehouse($post['act_loc'],
                $post['warehouseID']);

        if (! $post['actualLoc']) {
            return 'Location ' . $post['act_loc'] . ' does not exist.';
        }

        // Check count items is already exist in count items.
        $skuResults = $this->checkForExistingSKU($post);

        if ($skuResults) {
            return 'SKU ' . $post['sku'] . ' with UOM=' . $post['pack_size']
                . ' in location ' . $post['act_loc'] . ' already exist in'
                . ' report. Please change quantity for the SKU.';
        }

        // Check sku was cycled in other report
        $results = $this->getCycleCountInfoHaveLockedCarton($post);

        if ($results) {
            return 'SKU ' . $post['sku'] . ' is currentle included to '
                    . 'active Cycle Counts: ' . implode(', ', $results);
        }

        if ($cycleType == $cycleCount::TYPE_SKU) {

            // add SKU already exist on cycle count
            if ($this->isAddSkuAlreadyExist($post)) {

                $this->insertNewSKU($post);

            } else {

                $sql = $cycleCount->getCycleCountQuery([
                    'measureType' => $cycleCountByOUM,
                    'whereClause' => $whereClause,
                    'cycleType' => $cycleType,
                    'hasSizeColor' => $post['hasSizeColor'],
                    'addSKU' => TRUE
                ]);

                $params = [
                    $post['warehouseID'],
                    $post['sku'],
                    $post['rackID'],
                    $post['rackID'],
                    $post['reservedID'],
                    $post['discID'],
                    $post['customer'],
                    $post['pack_size'],
                ];

                if ($post['hasSizeColor']) {
                    $params[] = $post['size'];
                    $params[] = $post['color'];
                }

                $results = $this->app->queryResults($sql, $params);

                if (! $results) {
                    return 'SKU ' . $post['sku'] . ' with UOM='
                    . $post['pack_size'] . ' does not have data.';
                } else {
                    $data = $cycleCount->reStructureData($cycleCountByOUM,
                        $results, $statusIDs);
                }

                // Process insert data
                $cycleCount->processInsertData([
                    'cycleID'   => $cycleID,
                    'itemID'    => $nextItemID,
                    'itemData'  => $data,
                    'lockID'    => $post['lockID'],
                    'optionalValue' => [
                        'actLoc'    => $post['actualLoc'],
                        'actQty'    => $post['act_qty']
                    ]
                ]);
            }

        } else {

            // check sku have carton (all status).
            $result = $this->checkNewSKUHaveCarton($post);

            if (! $result) {
                return 'SKU ' . $post['sku'] . ' does not have inventory carton.';
            }

            if ($cycleType == $cycleCount::TYPE_LOCATION) {

                // Check location in range
                $info = $cycleCount->getCycleCountInfoById($post['cycleID']);
                $locationInput = json_decode($info['data'], TRUE);

                if (! in_array($post['actualLoc'], $locationInput)) {
                    return 'Location ' . $post['act_loc'] . ' not in range of'
                            . ' cycle count.';
                }
            }

            // Insert data to count items
            $this->insertNewSKU($post);
        }
    }

    /*
    ****************************************************************************
    */

    function validateDataInputAddNew($post)
    {
        $sku = $post['sku'];
        $size = getDefault($post['size'], 'NA');
        $color = getDefault($post['color'], 'NA');
        $vendorID = getDefault($post['customer']);

        $sql = 'SELECT  u.id AS upcID
                FROM    upcs u
                JOIN    inventory_batches ib ON ib.upcID = u.id
                JOIN    inventory_containers ic ON ic.recNum = ib.recNum
                WHERE   ic.vendorID = ?
                AND     u.sku = ?
                AND     u.size = ?
                AND     u.color = ?';

        $result = $this->app->queryResult($sql, [
            $vendorID,
            $sku,
            $size,
            $color
        ]);

        return $result['upcID'];
    }

    /*
    ****************************************************************************
    */

    function checkForExistingSKU($post)
    {
        $locID = getDefault($post['actualLoc']);
        $vendorID = getDefault($post['customer']);
        $packSize = getDefault($post['pack_size']);
        $upcID = getDefault($post['upcID']);
        $cycleID = getDefault($post['cycleID']);

        $params = [
            $cycleID,
            $vendorID,
            $upcID,
            $locID,
            $packSize
        ];

        $sql = 'SELECT  ci.count_item_id
                FROM    count_items ci
                JOIN    vendors v ON v.id = ci.vnd_id
                WHERE   ci.cycle_count_id = ?
                AND     v.id = ?
                AND     upc_id = ?
                AND     sys_loc = ?
                AND     pack_size = ?
                LIMIT   1';

        $result = $this->app->queryResult($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkReservedData($post, $whereClause)
    {
        $cycleCount = new cycleCount($this->app);

        $sql = $cycleCount->getCycleCountQuery([
            'measureType' => strtoupper($post['cycleCountByOUM']),
            'whereClause' => $whereClause,
            'getReserved' => TRUE,
            'cycleType' => getDefault($post['cycleType'])
        ]);

        $results = $this->app->queryResults($sql, [
            getDefault($post['warehouseID']),
            getDefault($post['sku']),
            getDefault($post['rackID']),
            getDefault($post['reservedID'])
        ]);

        return $results ? TRUE : FALSE;
    }

    /*
    ****************************************************************************
    */

    function checkNewSKUHaveCarton($post)
    {
        $inventory = new statuses\inventory($this->app);
        $statusID = $inventory->getStatusID(cartons::STATUS_INACTIVE);
        $upcID = getDefault($post['upcID']);
        $vendorID = getDefault($post['customer']);

        $sql = 'SELECT  ca.id
                FROM    inventory_cartons ca
                JOIN    inventory_batches ib ON ib.id = ca.batchID
                JOIN    inventory_containers ic ON ic.recNum = ib.recNum
                WHERE   ib.upcID = ?
                AND     ic.vendorID = ?
                AND     ca.statusID != ?
                AND     NOT isSplit
                AND     NOT unSplit
                LIMIT   1';

        $result = $this->app->queryResult($sql, [
            $upcID,
            $vendorID,
            $statusID
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getCycleCountInfoHaveLockedCarton($post)
    {
        $statuses = new statuses\inventory($this->app);

        $sql = 'SELECT  cc.cycle_count_id
                FROM    inventory_cartons ca
                JOIN    locked_cartons lc ON lc.invt_ctn_id = ca.id
                JOIN    count_items ci ON ci.count_item_id = lc.count_item_id
                JOIN    cycle_count cc ON cc.cycle_count_id = ci.cycle_count_id
                WHERE   ci.upc_id = ?
                AND     cc.cycle_count_id != ?
                AND     ci.vnd_id = ?
                AND     statusID = ?';

        $result = $this->app->queryResults($sql, [
            $post['upcID'],
            $post['cycleID'],
            $post['customer'],
            $statuses->getStatusID(cartons::STATUS_LOCKED)
        ]);

        return array_keys($result);
    }

    /*
    ****************************************************************************
    */

    function insertNewSKU($post)
    {
        $cycle = new cycleCount($this->app);
        $cycleID = getDefault($post['cycleID']);
        $vendorID = getDefault($post['customer']);
        $sku = $post['sku'];
        $size = getDefault($post['size'], '');
        $color = getDefault($post['color'], '');
        $pcs = trim(strtoupper($post['cycleCountByOUM']));

        $actualLoc = $post['actualLoc'];
        $upcID = $post['upcID'];
        $packSize = $post['pack_size'];
        $actualQty = $post['act_qty'];

        $param = [
            $cycleID,
            $vendorID,
            $upcID,
            $sku,
            $size,
            $color,
            $packSize,
            $pcs,
            self::VALUE_SYSTEM_DEFAULT_VALUE_ADD_NEW_SKU,
            $actualLoc,
            self::VALUE_SYSTEM_DEFAULT_VALUE_ADD_NEW_SKU,
            $actualLoc,
            $actualQty,
            $cycle::STATUS_NEW
        ];

        $qMark = $this->app->getQMarkString($param);

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
                    sys_loc,
                    sys_qty,
                    act_loc,
                    act_qty,
                    sts
                ) VALUES (' . $qMark . ')';

        $this->app->runQuery($sql, $param);
    }

    /*
    ****************************************************************************
    */

    function getDatatable($cycleID)
    {
        $sql = 'SELECT    c.count_item_id,
                          sku,
                          size,
                          color,
                          pcs,
                          l.displayName AS sys_loc,
                          ls.displayName AS act_loc,
                          allocate_qty,
                          sys_qty,
                          act_qty,
                          created_dt,
                          CONCAT(w.shortName, "_", v.vendorName) AS vendorName,
                          pack_size AS uom,
                          sys_qty
                FROM      cycle_count cc
                JOIN      count_items c ON cc.cycle_count_id = c.cycle_count_id
                JOIN      locations l ON l.id = c.sys_loc
                LEFT JOIN locations ls ON ls.id = c.act_loc
                JOIN      vendors v ON v.id = c.vnd_id
                JOIN      warehouses w ON w.id = v.warehouseID
                WHERE     cc.cycle_count_id = ?';

        $results = $this->app->queryResults($sql, [$cycleID]);

        return $results ? $results : FALSE;
    }

    /*
    ****************************************************************************
    */

    function printPDFCycleCount($cycleID, $params)
    {
        $cycleCount = new cycleCount($this->app);
        $cycleInfo = $cycleCount->getCycleCountInfoById($cycleID);
        $this->sizeColor = $params['hasSizeColor'];
        $sizing = $this->sizing();

        $data = $this->getDatatable($cycleID);

        $this->pdf = new \TCPDF('P', 'mm', 'Letter', TRUE, 'UTF-8', FALSE);
        $this->pdf->setPrintHeader(FALSE);
        $this->pdf->setPrintFooter(FALSE);
        $this->pdf->SetAutoPageBreak(TRUE, 0);
        $this->pdf->SetLeftMargin(10);
        $this->pdf->setCellPaddings(0, 0, 0, 0);

        $this->pdf->AddPage();

        $this->cyclePageHeader($cycleID);
        $this->cycleInfoContent($cycleCount, $cycleInfo);

        $params['hasSizeColor'] ?
            $this->cycleCountContent($data, $cycleID, $params, $sizing) :
            $this->cycleCountContentNonSizeColor($data, $cycleID, $params,
                $sizing);

        $fileName = $cycleInfo['name_report'];

        $this->pdf->Output('CR ' . $cycleID . '_' . $fileName . '.pdf', 'I');
    }

    /*
    ****************************************************************************
    */

    function cyclePageHeader($cycleID)
    {

        $this->pdf->SetFont('helvetica', 'B', 13);

        $text = 'DETAIL CYCLE COUNT #' . $cycleID;

        pdf::myMultiCell($this->pdf, 185, self::$rowHeight, $text, 0, 'C');

        $page = self::$pageIndex;

        pdf::myMultiCell($this->pdf, 10, self::$rowHeight, $page, 0, 'R');

        $this->pdf->Ln(15);

        $this->pdf->SetFont('helvetica', '', 9);
    }

    /*
    ****************************************************************************
    */

    function cycleInfoContent($table, $cycleInfo)
    {
        $contentSizing = $this->sizing('content');

        $reportName = $cycleInfo['name_report'] ?
            $cycleInfo['name_report'] : 'N/A';
        $description = $cycleInfo['descr'] ? $cycleInfo['descr'] : 'N/A';
        $dueDate = $cycleInfo['due_dt'] ? $cycleInfo['due_dt'] : 'N/A';
        $assigneeBy = $cycleInfo['assigneeBy'] ?
            $cycleInfo['assigneeBy'] : 'N/A';
        $assigner = $cycleInfo['assigner'] ? $cycleInfo['assigner'] : 'N/A';
        $cycleType = $cycleInfo['type'] != $table::TYPE_CUSTOMER ?
            $cycleInfo['type'] == $table::TYPE_SKU ? 'SKU' : 'Location' :
            'Customer';

        $this->pdf->SetFont('helvetica', 'B', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['cycleName']['title'],
            self::$rowHeight, 'Cycle Name:', 0, 'L');

        $this->pdf->SetFont('helvetica', '', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['cycleName']['value'],
            self::$rowHeight, $reportName, 0, 'L');

        $this->pdf->SetFont('helvetica', 'B', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['dueDate']['title'],
            self::$rowHeight, 'Due Date:', 0, 'L');

        $this->pdf->SetFont('helvetica', '', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['dueDate']['value'],
            self::$rowHeight, $dueDate, 0, 'R');

        $this->pdf->Ln();
        $this->pdf->SetFont('helvetica', 'B', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['description']['title'],
            self::$rowHeight, 'Description:', 0, 'L');

        $this->pdf->SetFont('helvetica', '', 11);

        $this->pdf->SetFillColor(255, 255, 255);

        $this->pdf->MultiCell('', '', $description, '', 'L', 1, 1, '' ,'', true);

        $this->pdf->Ln(1);

        $this->pdf->SetFont('helvetica', 'B', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['assignBy']['title'],
            self::$rowHeight, 'Assigned by:', 0, 'L');

        $this->pdf->SetFont('helvetica', '', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['assignBy']['value'],
            self::$rowHeight, $assigneeBy, 0, 'L');

        $this->pdf->SetFont('helvetica', 'B', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['assignTo']['title'],
            self::$rowHeight, 'Assigned to:', 0, 'L');

        $this->pdf->SetFont('helvetica', '', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['assignTo']['value'],
            self::$rowHeight, $assigner, 0, 'L');

        $this->pdf->SetFont('helvetica', 'B', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['status']['title'],
            self::$rowHeight, 'Status:', 0, 'L');

        $this->pdf->SetFont('helvetica', '', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['status']['value'],
            self::$rowHeight, $cycleInfo['status'], 0, 'R');

        $this->pdf->Ln();

        $this->pdf->SetFont('helvetica', 'B', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['cycleType']['title'],
            self::$rowHeight, 'Cycle Type:', 0, 'L');

        $this->pdf->SetFont('helvetica', '', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['cycleType']['value'],
            self::$rowHeight, $cycleType, 0, 'L');

        $this->pdf->SetFont('helvetica', 'B', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['byUOM']['title'],
            self::$rowHeight, 'By UOM:', 0, 'L');

        $this->pdf->SetFont('helvetica', '', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['byUOM']['value'],
            self::$rowHeight, strtoupper($cycleInfo['cycle_count_by_uom']), 0, 'L');

        $this->pdf->SetFont('helvetica', 'B', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['bySizeColor']['title'],
            self::$rowHeight, 'By Color & Size:', 0, 'L');

        $this->pdf->SetFont('helvetica', '', 11);

        pdf::myMultiCell($this->pdf, $contentSizing['bySizeColor']['value'],
            self::$rowHeight, $cycleInfo['bySizeColor'] ? 'Yes' : 'No', 0, 'R');

        $this->pdf->Ln();

    }

    /*
    ****************************************************************************
    */

    function cycleCountContent($data, $cycleID, $params, $sizing)
    {
        $index = 1;
        $pageHeight = 0;

        $this->pdf->Ln(5);
        $this->pdf->SetFont('helvetica', 'B', 11);

        pdf::myMultiCell($this->pdf, $sizing['customer'], self::$rowHeight,
            'Customer');
        pdf::myMultiCell($this->pdf, $sizing['sku'], self::$rowHeight, 'SKU');
        pdf::myMultiCell($this->pdf, $sizing['size'], self::$rowHeight, 'Size');
        pdf::myMultiCell($this->pdf, $sizing['color'], self::$rowHeight, 'Color');
        pdf::myMultiCell($this->pdf, $sizing['uom'], self::$rowHeight, 'UOM');
        pdf::myMultiCell($this->pdf, $sizing['allocateQty'], self::$rowHeight, 'Alc Qty');
        pdf::myMultiCell($this->pdf, $sizing['totalCarton'], self::$rowHeight,
            'Ttl Ctn');
        pdf::myMultiCell($this->pdf, $sizing['totalPiece'], self::$rowHeight,
            'Ttl Pieces');
        pdf::myMultiCell($this->pdf, $sizing['actQty'], self::$rowHeight,
            'Act Qty');
        pdf::myMultiCell($this->pdf, $sizing['sysLoc'], self::$rowHeight,
            'Sys Loc');
        pdf::myMultiCell($this->pdf, $sizing['actLoc'], self::$rowHeight,
            'Act Loc');

        $this->pdf->Ln();
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->setCellPaddings(1, 0, 1, 0);

        foreach ($data as $row) {
            $totalCarton = $row['pcs'] == "EACH" ?
                $row['sys_qty'] / $row['uom'] : $row['sys_qty'];
            $totalPiece = $row['pcs'] == "EACH" ? $row['sys_qty'] :
                $row['sys_qty'] * $row['uom'];

            $rowHeight = ceil(count($row) / self::$default) * self::$titleHeight;

            $cycleStatus = trim(strtoupper($params['cycleStatus']));

            $isShowAct = $cycleStatus == cycleCount::STATUS_CYCLE ||
                $cycleStatus == cycleCount::STATUS_COMPLETE;

            $actLoc = $isShowAct ? $row['act_loc'] : '';
            $actQty = $isShowAct ? $row['act_qty'] : '';

            $this->pdf->MultiCell($sizing['customer'], self::$titleHeight,
                $row['vendorName'], 1, 'C', 1, 0, '', '', true, 0, false, true,
                10, 'M');

            $this->pdf->MultiCell($sizing['sku'], self::$titleHeight,
                $row['sku'], 1, 'C', 1, 0, '', '', true, 0, true, true, 5, 'B');

            $this->pdf->MultiCell($sizing['size'], self::$titleHeight,
                $params['hasSizeColor'] ? $row['size'] : 'NA', 1, 'C', 1, 0, '',
                '', true, 0, false, true, 10, 'M');
            $this->pdf->MultiCell($sizing['color'], self::$titleHeight,
                $params['hasSizeColor'] ? $row['color'] : 'NA', 1, 'C', 1, 0,
                '', '', true, 0, false, true, 10, 'M');
            $this->pdf->MultiCell($sizing['uom'], self::$titleHeight,
                $row['uom'], 1, 'C', 1, 0, '', '', true, 0, false, true, 10, 'M');
            $this->pdf->MultiCell($sizing['allocateQty'], self::$titleHeight,
                $row['allocate_qty'], 1, 'C', 1, 0, '', '', true, 0, false, true, 10,
                'M');

            $this->pdf->MultiCell($sizing['totalCarton'], self::$titleHeight,
                $totalCarton, 1, 'C', 1, 0, '', '', true, 0, false, true, 10,
                'M');
            $this->pdf->MultiCell($sizing['totalPiece'], self::$titleHeight,
                $totalPiece, 1, 'C', 1, 0, '', '', true, 0, false, true, 10,
                'M');

            if ($row['sys_qty'] != $actQty) {
                $this->pdf->SetTextColor(255, 0, 0);
            }

            $this->pdf->MultiCell($sizing['actQty'], self::$titleHeight,
                $actQty, 1, 'C', 1, 0, '', '', true, 0, false, true, 10, 'M');

            $this->pdf->SetTextColor(0, 0, 0);

            $this->pdf->MultiCell($sizing['sysLoc'], self::$titleHeight,
                $row['sys_loc'], 1, 'C', 1, 0, '', '', true, 0, false, true, 10,
                'M');

            if ($row['sys_loc'] != $actLoc) {
                $this->pdf->SetTextColor(255, 0, 0);
            }

            $this->pdf->MultiCell($sizing['actLoc'], self::$titleHeight,
                $actLoc, 1, 'C', 1, 0, '', '', true, 0, false, true, 10, 'M');

            $this->pdf->SetTextColor(0, 0, 0);

            $this->pdf->Ln();

            $index++;

            $pageHeight += $rowHeight;

            if ($pageHeight >= 400) {
                $pageHeight = 0;
                self::$pageIndex++;
                $this->pdf->AddPage();
                $this->cyclePageHeader($cycleID);
            }
        }
    }

    /*
    ****************************************************************************
    */

    function cycleCountContentNonSizeColor($data, $cycleID, $params, $sizing)
    {
        $index = 1;
        $pageHeight = 0;

        $this->pdf->Ln(5);
        $this->pdf->SetFont('helvetica', 'B', 11);

        pdf::myMultiCell($this->pdf, $sizing['customer'], self::$rowHeight,
            'Customer');
        pdf::myMultiCell($this->pdf, $sizing['sku'], self::$rowHeight, 'SKU');
        pdf::myMultiCell($this->pdf, $sizing['uom'], self::$rowHeight, 'UOM');
        pdf::myMultiCell($this->pdf, $sizing['allocateQty'], self::$rowHeight, 'Alc Qty');
        pdf::myMultiCell($this->pdf, $sizing['totalCarton'], self::$rowHeight,
            'Ttl Ctn');
        pdf::myMultiCell($this->pdf, $sizing['totalPiece'], self::$rowHeight,
            'Ttl Pieces');
        pdf::myMultiCell($this->pdf, $sizing['actQty'], self::$rowHeight,
            'Act Qty');
        pdf::myMultiCell($this->pdf, $sizing['sysLoc'], self::$rowHeight,
            'Sys Loc');
        pdf::myMultiCell($this->pdf, $sizing['actLoc'], self::$rowHeight,
            'Act Loc');

        $this->pdf->Ln();
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->setCellPaddings(1, 0, 1, 0);

        foreach ($data as $row) {
            $totalCarton = $row['pcs'] == "EACH" ?
                $row['sys_qty'] / $row['uom'] : $row['sys_qty'];
            $totalPiece = $row['pcs'] == "EACH" ? $row['sys_qty'] :
                $row['sys_qty'] * $row['uom'];

            $rowHeight = ceil(count($row) / self::$default) * self::$titleHeight;

            $cycleStatus = trim(strtoupper($params['cycleStatus']));

            $isShowAct = $cycleStatus == cycleCount::STATUS_CYCLE ||
                $cycleStatus == cycleCount::STATUS_COMPLETE;

            $actLoc = $isShowAct ? $row['act_loc'] : '';
            $actQty = $isShowAct ? $row['act_qty'] : '';

            $this->pdf->MultiCell($sizing['customer'], self::$titleHeight,
                $row['vendorName'], 1, 'C',
                1, 0, '', '', true, 0, false, true, 10, 'M');

            $this->pdf->setCellPaddings(1, 1, 1, 1);

            $this->pdf->MultiCell($sizing['sku'], self::$titleHeight,
                $row['sku'], 1, 'C', 1, 0, '', '', true, 0, false, true, 10, 'M');
            $this->pdf->MultiCell($sizing['uom'], self::$titleHeight,
                $row['uom'], 1, 'C', 1, 0, '', '', true, 0, false, true, 10, 'M');
            $this->pdf->MultiCell($sizing['allocateQty'], self::$titleHeight,
                $row['allocate_qty'], 1, 'C', 1, 0, '', '', true, 0, false, true, 10, 'M');
            $this->pdf->MultiCell($sizing['totalCarton'], self::$titleHeight,
                $totalCarton, 1, 'C', 1, 0, '', '', true, 0, false, true, 10, 'M');
            $this->pdf->MultiCell($sizing['totalPiece'], self::$titleHeight,
                $totalPiece, 1, 'C', 1, 0, '', '', true, 0, false, true, 10, 'M');

            if ($row['sys_qty'] != $actQty) {
                $this->pdf->SetTextColor(255, 0, 0);
            }

            $this->pdf->MultiCell($sizing['actQty'], self::$titleHeight, $actQty,
                1, 'C', 1, 0, '', '', true, 0, false, true, 10, 'M');

            $this->pdf->SetTextColor(0,0,0);

            $this->pdf->MultiCell($sizing['sysLoc'], self::$titleHeight,
                $row['sys_loc'], 1, 'C', 1, 0, '', '', true, 0, false, true, 10,
                'M');

            if ($row['sys_loc'] != $actLoc) {
                $this->pdf->SetTextColor(255, 0, 0);
            }

            $this->pdf->MultiCell($sizing['actLoc'], self::$titleHeight, $actLoc,
                1, 'C', 1, 0, '', '', true, 0, false, true, 10, 'M');

            $this->pdf->SetTextColor(0, 0, 0);

            $this->pdf->Ln();

            $index++;

            $pageHeight += $rowHeight;

            if ($pageHeight >= 400) {
                $pageHeight = 0;
                self::$pageIndex++;
                $this->pdf->AddPage();
                $this->cyclePageHeader($cycleID);
            }
        }
    }

    /*
    ****************************************************************************
    */

    public static function getStatusCountItem()
    {
        return [
            self::STATUS_NEW => self::TEXT_COUNT_ITEM_STATUS_NEW,
            self::STATUS_OPEN => self::TEXT_COUNT_ITEM_STATUS_OPEN,
            self::STATUS_RECOUNT => self::TEXT_COUNT_ITEM_STATUS_RECOUNT,
            self::STATUS_NOT_APPLICABLE =>
                self::TEXT_COUNT_ITEM_STATUS_NOT_APPLICABLE,
            self::STATUS_ACCEPTED => self::TEXT_COUNT_ITEM_STATUS_ACCEPTED,
            self::STATUS_DELETED => self::TEXT_COUNT_ITEM_STATUS_DELETED
        ];
    }

    /*
    ****************************************************************************
    */

    function isAddSkuAlreadyExist($post)
    {
        $vendorID = getDefault($post['customer']);
        $upcID = getDefault($post['upcID']);
        $cycleID = getDefault($post['cycleID']);

        $sql = 'SELECT  COUNT(ci.count_item_id) AS amount
                FROM    count_items ci
                JOIN    vendors v ON v.id = ci.vnd_id
                WHERE   ci.cycle_count_id = ?
                AND     v.id = ?
                AND     upc_id = ?';

        $result = $this->app->queryResult($sql, [
            $cycleID,
            $vendorID,
            $upcID
        ]);

        return $result['amount'] ? TRUE : FALSE;
    }

    /*
    ****************************************************************************
    */

    function sizing($part=NULL)
    {
        return $part !== 'content' ? $this->sizeColor ? [
            'customer' => 30,
            'sku' => 25,
            'size' => 15,
            'color' => 15,
            'uom' => 14,
            'allocateQty' => 10,
            'totalCarton' => 10,
            'totalPiece' => 20,
            'actQty' => 15,
            'actLoc' => 23,
            'sysLoc' => 23
        ] : [
            'customer' => 33,
            'sku' => 33,
            'uom' => 14,
            'allocateQty' => 15,
            'totalCarton' => 15,
            'totalPiece' => 20,
            'actQty' => 15,
            'actLoc' => 30,
            'sysLoc' => 30
        ] : [
            'cycleName' => [
                'title' => 30,
                'value' => 100
            ],
            'dueDate' => [
                'title' => 40,
                'value' => 25
            ],
            'description' => [
                'title' => 30,
                'value' => ''
            ],
            'assignBy' => [
                'title' => 30,
                'value' => 30
            ],
            'assignTo' => [
                'title' => 30,
                'value' => 40
            ],
            'status' => [
                'title' => 40,
                'value' => 25
            ],
            'cycleType' => [
                'title' => 30,
                'value' => 30
            ],
            'byUOM' => [
                'title' => 30,
                'value' => 40
            ],
            'bySizeColor' => [
                'title' => 40,
                'value' => 25
            ]
        ];
    }

    /*
    ****************************************************************************
    */
}