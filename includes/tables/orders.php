<?php

namespace tables;

use common\vendor;
use get\string;
use inventory\wavePicks;
use \labels\create;

class orders extends _default
{
    public $ajaxModel = 'orders';

    public $primaryKey = 'o.id';

    public $fields = [
        'printBOL' => [
            'select' => 'bolID',
            'display' => 'Print BOL',
            'noEdit' => TRUE,
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'update' => 'o.userID',
            'noEdit' => TRUE,
        ],
        'first_name' => [
            'select' => 'first_name',
            'display' => 'First Name',
            'maxLength' => 25,
        ],
        'last_name' => [
            'select' => 'last_name',
            'display' => 'Last Name/Customer Name',
            'maxLength' => 100,
        ],
        'clientNotes' => [
            'select' => 'clientNotes',
            'display' => 'Client Note',
        ],
        'edi' => [
            'display' => 'Created Type',
            'select' => 'IF(o.edi, "EDI", "User")',
            'isNum' => 20,
            'noEdit' => TRUE,
        ],
        'isPrintUccEdi' => [
            'display' => 'UCCPrint Type',
            'select' => 'IF(o.edi AND o.isPrintUccEdi, "LINGO", "AWMS")',
            'isNum' => 20,
            'noEdit' => TRUE,
        ],
        'vendor' => [
            'display' => 'Client',
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'update' => 'b.vendorID',
        ],
        'clientOrderNumber' => [
            'select' => 'clientOrderNumber',
            'display' => 'Client Order Number',
            'maxLength' => 20,
        ],
        'customerOrderNumber' => [
            'select' => 'customerOrderNumber',
            'display' => 'Customer Order Number',
            'maxLength' => 20,
        ],
        'scanOrderNumber' => [
            'select' => 'o.scanordernumber',
            'noEdit' => TRUE,
            'display' => 'Order Number',
            'isNum' => 10,
        ],
        'batchOrder' => [
            'select' => 'order_batch',
            'display' => 'Batch Order',
            'noEdit' => TRUE,
            'isNum' => 8,
        ],
        'scanWorkOrder' => [
            'display' => 'Scan Work Order',
            'isNum' => 10,
        ],
        'bolNumber' => [
            'display' => 'BOL Number',
            'maxLength' => 20,
        ],
        'scanPicking' => [
            'display' => 'Scan Picking',
            'maxLength' => 100,
        ],
        'companyName' => [
            'display' => 'Location',
            'searcherDD' => 'orders\companyAddresses',
            'ddField' => 'companyName',
            'update' => 'o.location',
        ],
        'numberofCarton' => [
            'display' => 'Carton Quantity',
            'isNum' => TRUE,
        ],
        'numberofpiece' => [
            'display' => 'Pieces Quantity',
            'isNum' => TRUE,
        ],
        'startshipdate' => [
            'display' => 'Start Ship Date',
            'searcherDate' => TRUE,
        ],
        'cancelDate' => [
            'display' => 'Cancel Date',
            'searcherDate' => TRUE,
        ],
        'type' => [
            'select' => 'ot.typeName',
            'display' => 'Order Type',
            'searcherDD' => 'orders\\orderTypes',
            'ddField' => 'typeName',
            'update' => 'o.type',
        ],
        'service' => [
            'display' => 'Service',
        ],
        'pickList' => [
            'select' => 'IF(pickList, "Yes", "No")',
            'display' => 'Pick List',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'picklist',
        ],
        'packingList' => [
            'select' => 'IF(packingList, "Yes", "No")',
            'display' => 'Packing List',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'packinglist',
        ],
        'preBol' => [
            'select' => 'IF(preBol, "Yes", "No")',
            'display' => 'Prebol',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'prebol',
        ],
        'commercialInvoice' => [
            'select' => 'IF(commercialInvoice, "Yes", "No")',
            'display' => 'Commercial Invoice',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'commercialinvoice',
        ],
        'otherdocumentinform' => [
            'display' => 'Other Docment Info',
            'maxLength' => 200,
        ],
        'shipToLabels' => [
            'select' => 'IF(shipToLabels, "Yes", "No")',
            'display' => 'Ship to Labels',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'shiptolabels',
        ],
        'ediAsn' => [
            'select' => 'IF(ediAsn, "Yes", "No")',
            'display' => 'EDI ASN',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'ediasn',
        ],
        'cartonContent' => [
            'select' => 'IF(cartonContent, "Yes", "No")',
            'display' => 'Carton Content',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'cartoncontent',
        ],
        'otherlabelinform' => [
            'display' => 'Other Label Info',
            'maxLength' => 200,
        ],
        'carrier' => [
            'select' => 'o.carrier',
            'display' => 'Carrier',
            'maxLength' => 20,
        ],
        'carrierNote' => [
            'display' => 'Carrier Note',
            'maxLength' => 100,
        ],
        'orderNotes' => [
            'display' => 'Order Notes',
            'maxLength' => 400,
        ],
        'dateEntered' => [
            'display' => 'Date Entered',
            'searcherDate' => TRUE,
        ],
        'saleorderid' => [
            'display' => 'Sales Order ID',
            'maxLength' => 100,
        ],
        'pickID' => [
            'display' => 'Pick ID',
            'isNum' => 20,
        ],
        'samples' => [
            'display' => 'Samples',
            'isNum' => 10,
        ],
        'pickPack' => [
            'display' => 'Pick Pack',
            'isNum' => 10,
        ],
        'label' => [
            'display' => 'Label Type',
            'maxLength' => 13,
        ],
        'labelInfo' => [
            'display' => 'Label Information',
            'maxLength' => 20,
        ],
        'cartonOfContent' => [
            'display' => 'Cartons of Content',
            'maxLength' => 10,
        ],
        'noPallets' => [
            'display' => 'NO Pallets',
            'maxLength' => 10,
        ],
        'noRushHours' => [
            'display' => 'NO Rush Hours',
            'isNum' => 10,
        ],
        'statusID' => [
            'select' => 'os.shortName',
            'display' => 'Status',
            'noEdit' => TRUE
        ],
        'routedStatusID' => [
            'select' => 'osr.shortName',
            'display' => 'Routed Status',
            'searcherDD' => 'statuses\\routed',
            'ddField' => 'shortName',
            'hintField' => 'displayName',
            'update' => 'o.routedStatusID',
            'canEmptyFieldValue' => TRUE,
        ],
        'holdStatusID' => [
            'select' => 's.displayName',
            'display' => 'Hold Status',
            'searcherDD' => 'statuses\\hold',
            'ddField' => 'displayName',
            'update' => 'o.holdStatusID',
            'canEmptyFieldValue' => TRUE,
        ],
        'shippingCondition' => [
            'select' => 'ssts.shortName',
            'display' => 'Condition',
            'searcherDD' => 'statuses\\shipping',
            'ddField' => 'shortName',
            'hintField' => 'displayName',
            'backgroundColor' => 'orange',
        ],
        'noInventory' => [
            'select' => 'osts.displayName',
            'display' => 'Error',
            'searcherDD' => 'statuses\\enoughInventory',
            'ddField' => 'displayName',
            'update' => 'o.isError',
            'noEdit' => TRUE,
        ],

    ];

    public $displaySingle = 'Order';

    public $primeTable = 'neworder';

    public $mainTable = 'orders';

    public $mainField = 'scanOrderNumber';

    public $groupBy = 'o.id';

    const STATUS_ON_HOLD = 'ONHO';

    const STATUS_OFF_HOLD = 'NOHO';

    const STATUS_NO_ERROR = 'ENIN';

    const STATUS_ERROR = 'LOIN';

    const STATUS_CANCELED = 'CNCL';

    const STATUS_ENTRY_CHECK_IN = 'WMCI';

    const STATUS_ENTRY_CHECK_OUT = 'WMCO';

    const STATUS_ROUTING_CHECK_IN = 'RTCI';

    const STATUS_ROUTING_CHECK_OUT = 'RTCO';

    const STATUS_PICKING_CHECK_IN = 'PKCI';

    const STATUS_PICKING_CHECK_OUT = 'PKCO';

    const STATUS_PROCESSING_CHECK_IN = 'OPCI';

    const STATUS_PROCESSING_CHECK_OUT = 'OPCO';

    const STATUS_BILL_OF_LADING = 'BOL';

    const STATUS_SHIPPING_CHECK_IN = 'LSCI';

    const STATUS_SHIPPED_CHECK_OUT = 'SHCO';

    const STATUS_SHIPPING_CANCELED_ORDER = 'CNCL';

    const STATUS_SHIPPING_WORK_ORDER_NOT_SHIPPING = 'WONS';

    const STATUS_SHIPPING_SHIPPING = 'SHIP';

    const CATEGORY_HOLD = 'hold';

    const CATEGORY = 'orders';

    const CATEGORY_ORDER_ERROR = 'orderErrors';

    public $importFields = [
        'ordernum' => [
            'display' => 'OrderNum',
            'table' => 'neworder',
            'field' => 'clientordernumber',
            'required' => TRUE,
        ],
        'custref' => [
            'display' => 'CustRef',
            'required' => FALSE,
        ],
        'po' => [
            'display' => 'PO',
            'table' => 'neworder',
            'field' => 'customerordernumber',
            'required' => TRUE,
        ],
        'shiptonum' => [
            'display' => 'ShipToNum',
            'required' => FALSE,
        ],
        'shipto' => [
            'display' => 'ShipTo',
            'table' => 'neworder',
            'field' => 'last_name',
            'required' => FALSE,
        ],
        'shipadd1' => [
            'display' => 'ShipAdd1',
            'table' => 'neworder',
            'field' => 'shiptoaddress',
            'required' => FALSE,
        ],
        'shipadd2' => [
            'display' => 'ShipAdd2',
            'table' => 'neworder',
            'field' => 'shiptoaddress',
            'required' => FALSE,
        ],
        'shipcity' => [
            'display' => 'ShipCity',
            'table' => 'neworder',
            'field' => 'shiptocity',
            'required' => FALSE,
        ],
        'shipstate' => [
            'display' => 'ShipState',
            'required' => FALSE,
        ],
        'shipzip' => [
            'display' => 'ShipZip',
            'required' => FALSE,
        ],
        'billto' => [
            'display' => 'BillTo',
            'required' => FALSE,
        ],
        'billadd1' => [
            'display' => 'BillAdd1',
            'required' => FALSE,
        ],
        'billadd2' => [
            'display' => 'BillAdd2',
            'required' => FALSE,
        ],
        'billcity' => [
            'display' => 'BillCity',
            'required' => FALSE,
        ],
        'billstate' => [
            'display' => 'BillState',
            'required' => FALSE,
        ],
        'billzip' => [
            'display' => 'BillZip',
            'required' => FALSE,
        ],
        'markid' => [
            'display' => 'MarkID',
            'required' => FALSE,
        ],
        'markfor' => [
            'display' => 'MarkFor',
            'required' => FALSE,
        ],
        'markadd1' => [
            'display' => 'MarkAdd1',
            'required' => FALSE,
        ],
        'markadd2' => [
            'display' => 'MarkAdd2',
            'required' => FALSE,
        ],
        'markcity' => [
            'display' => 'MarkCity',
            'required' => FALSE,
        ],
        'markstate' => [
            'display' => 'MarkState',
            'required' => FALSE,
        ],
        'markzip' => [
            'display' => 'MarkZip',
            'required' => FALSE,
        ],
        'prodid' => [
            'display' => 'ProdID',
            'table' => 'upcs',
            'field' => 'sku',
            'required' => TRUE,
        ],
        'retaileritemnumber' => [
            'display' => 'RetailerItemNumber',
            'required' => FALSE,
        ],
        'upc' => [
            'display' => 'UPC',
            'table' => 'upcs',
            'field' => 'upc',
            'required' => TRUE,
        ],
        'casepack' => [
            'display' => 'CasePack',
            'table' => 'pick_errors',
            'field' => 'uom',
            'required' => TRUE,
            'isNum' => TRUE,
            'isPositive' => TRUE,
        ],
        'order_qty' => [
            'display' => 'Order Qty',
            'table' => 'pick_errors',
            'field' => 'quantity',
            'required' => TRUE,
            'isNum' => TRUE,
            'isPositive' => TRUE,
        ],
        'shipdate' => [
            'display' => 'ShipDate',
            'table' => 'neworder',
            'field' => 'startshipdate',
            'required' => TRUE,
        ],
        'canceldate' => [
            'display' => 'CancelDate',
            'table' => 'neworder',
            'field' => 'canceldate',
            'required' => TRUE,
        ],
        'freightterms' => [
            'display' => 'FreightTerms',
            'required' => FALSE,
        ],
        'shipvia' => [
            'display' => 'ShipVia',
            'required' => FALSE,
        ],
        'dept' => [
            'display' => 'Dept',
            'required' => FALSE,
        ],
        'potype' => [
            'display' => 'PoType',
            'required' => FALSE,
        ],
        'vendornum' => [
            'display' => 'VendorNum',
            'required' => FALSE,
        ],
        'colorcode' => [
            'display' => 'ColorCode',
            'required' => FALSE,
        ],
        'color' => [
            'display' => 'Color',
            'table' => 'upcs',
            'field' => 'color',
            'required' => TRUE,
        ],
        'smallparcelaccount_#' => [
            'display' => 'SmallParcelAccount #',
            'required' => FALSE,
        ],
        'customer_id' => [
            'display' => 'Customer ID',
            'required' => FALSE,
        ],
        'size' => [
            'display' => 'SIZE',
            'table' => 'upcs',
            'field' => 'size',
            'required' => TRUE,
        ],
        'size_desc' => [
            'display' => 'SIZE DESC',
            'required' => FALSE,
        ],
        'seq' => [
            'display' => 'SEQ',
            'required' => FALSE,
        ],
        'itemdescription' => [
            'display' => 'ItemDescription',
            'table' => 'neworder',
            'field' => 'ordernotes',
            'required' => FALSE,
        ],
    ];

    public $orderNumberKey = NULL;

    public $shipToAddress1Key = NULL;

    public $shipToAddress2Key = NULL;

    public $upcKey = NULL;

    public $uomKey = NULL;

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'neworder o
            JOIN      order_batches b ON b.id = o.order_batch
            JOIN      vendors v ON v.id = b.vendorID
            LEFT JOIN ' . $userDB . '.info u ON o.userID = u.id
            LEFT JOIN company_address a ON o.location = a.id
            JOIN      warehouses w ON v.warehouseID = w.id
            LEFT JOIN online_orders oo
                ON   o.scanOrderNumber = oo.SCAN_SELDAT_ORDER_NUMBER
            LEFT JOIN statuses s ON o.holdStatusID = s.id
            LEFT JOIN statuses os ON o.statusID = os.id
            LEFT JOIN statuses osr ON o.routedStatusID = osr.id
            LEFT JOIN statuses osts ON o.isError = osts.id
            LEFT JOIN statuses ssts ON o.shippingStatusID = ssts.id
            LEFT JOIN order_types ot ON ot.id = o.type
            LEFT JOIN shipping_orders so ON so.orderID = o.id
            LEFT JOIN order_notes ons ON ons.orderID = o.id
            ';
    }
    /*
    ****************************************************************************
    */

    function addShippingInfo($orders)
    {
        $this->app->beginTransaction();

        foreach ($orders as $order) {

            $markString = $this->app->getQMarkString($order);

            $sql = 'INSERT INTO orders_shipping_info (
                        newOrderID,
                        transMC,
                        scac,
                        proNumber,
                        shipType,
                        trailerNumber
                    ) VALUES (
                        ' . $markString . '
                    ) ON DUPLICATE KEY UPDATE
                        transMC = ?,
                        scac = ?,
                        proNumber = ?,
                        shipType = ?,
                        trailerNumber = ?
                    ';

            $insertParams = $updateParams = array_values($order);

            array_shift($updateParams);

            $params = array_merge($insertParams, $updateParams);

            $this->app->runQuery($sql, $params);
        }

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function checkFieldValue($fieldName, $fieldValue)
    {
        $isArray = is_array($fieldValue);
        $fieldValue = $isArray ? $fieldValue : [$fieldValue];

        $markString = $this->app->getQMarkString($fieldValue);
        $clause = $isArray
            ? ' IN (' . $markString . ')'
            : ' = ?';

        $sql = 'SELECT    o.id,
                          vendorID AS vendor,
                          startshipdate,
                          scanOrderNumber
                FROM      neworder o
                JOIN      order_batches b ON b.id = o.order_batch
                JOIN      vendors v ON v.id = b.vendorID
                WHERE     ' . $fieldName . $clause;

        $results = $isArray ?
            $this->app->queryResults($sql, $fieldValue) :
            $this->app->queryResult($sql, $fieldValue);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function selectShipFrom()
    {
        $sql = 'SELECT     ca.id,
                           companyName
                FROM       company_address ca
                JOIN       warehouses w ON w.locationID = ca.id
                ORDER BY   ca.id';

        $results = $this->app->queryResults($sql);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function selectOrderStatusCount($vendorID)
    {
        $sql = 'SELECT    n.id,
                          s.shortName,
                          n.isError,
                          COUNT(n.statusID) AS orderCount,
                          COUNT(n.isError) AS isError,
                          CONCAT(w.shortName, "_", vendorName) AS fullVendorName
                FROM      statuses s
                JOIN      neworder n
                JOIN      order_batches b ON b.id = n.order_batch
                JOIN      vendors v ON v.id = b.vendorID
                JOIN      warehouses w ON v.warehouseID = w.id
                WHERE     s.id IN (n.holdStatusID, n.statusID, n.isError)
                AND       s.shortName IN (
                              "' . self::STATUS_ON_HOLD .'",
                              "' . self::STATUS_ERROR . '",
                              "' . self::STATUS_ENTRY_CHECK_IN . '",
                              "' . self::STATUS_ENTRY_CHECK_OUT . '"
                          )
                AND       b.vendorID = ?
                GROUP BY  n.id, s.id';

        $results = $this->app->queryResults($sql, [$vendorID]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function selectCommodity()
    {
        $sql = 'SELECT    id,
                          description
                FROM      commodity ';

        $results = $this->app->queryResults($sql);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function onHoldOrError($params)
    {
        $orderNumber = $params['order'];
        $where = getDefault($params['where'], 'scanOrderNumber');
        $select = getDefault($params['select'], 'holdStatusID');

        $allowed = [
            'order_batch' => 'Order Batch',
            'scanOrderNumber' => 'Scan Order Number',
            'clientordernumber' => 'Client Order Number ',
            'customerOrderNumber' => 'Customer Order Number ',
        ];

        if (! isset($allowed[$where])) {
            return 'Field ' . $where . ' is not allowed to be used';
        }

        $shortName = $msg = $category = NULL;

        $success = $this->setMessageOfHoldOrError([
            'select' => $select,
            'shortName' => &$shortName,
            'msg' => &$msg,
            'category' => &$category,
        ]);

        if (! $success) {
            die('Invalid Status');
        }

        $orderOnHoldOrError = $this->getOrderonHoldOrError([
                'orderNumber' => $orderNumber,
                'where' => $where,
                'select' => $select,
                'shortName' => $shortName,
                'category' => $category
            ]);

        $errMsg = NULL;

        $show = $allowed[$where];

        $targetNumbers = array_keys($orderOnHoldOrError);
        foreach ($targetNumbers as $targetNumber) {
            $initial = $errMsg ? '<br>' : NULL;
            $errMsg .= $initial . $show . ' "' . $targetNumber . '" ' . $msg;
        }

        return $errMsg;
    }

    /*
    ****************************************************************************
    */

    function checkForValidClient($scanOrders, $target)
    {
        if (! $scanOrders) {
            return [
                'valid' => FALSE,
                'empty' => 'No Orders Were Submitted'
            ];
        }

        $target = $target ? $target : 'scanOrderNumber';

        $valid = TRUE;
        $results = [];
        foreach ($scanOrders as $id => $plates) {

            $vendorID = $this->getVendorIDByTarget($target, $id);

            if (! $vendorID) {
                continue;
            }

            $this->checkValidCartonOfVendor([
                'id' => $id,
                'plates' => $plates,
                'vendorID' => $vendorID,
                'valid' => &$valid,
                'results' => &$results,
            ]);
        }

        $resultsFinal = [
            'valid' => $valid,
            'byRows' => $results,
        ];

        return $resultsFinal;
    }

    /*
    ****************************************************************************
    */

    function getProductDescription($cartonIDs)
    {
        $markString = $this->app->getQMarkString($cartonIDs);

        $sql = 'SELECT    ca.id,
                          upc,
                          SUM(uom) AS pieces
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      upcs p ON p.id = b.upcID
                WHERE     ca.id IN (' . $markString . ')
                GROUP BY  upc';

        $results = $this->app->queryResults($sql, $cartonIDs);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getWavePickProducts($orders)
    {
        $markString = $this->app->getQMarkString($orders);

        $sql = 'SELECT    ca.id,
                          scanOrderNumber,
                          upc,
                          SUM(uom) AS pieces
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      pick_cartons pc ON pc.cartonID = ca.id
                JOIN      upcs p ON p.id = b.upcID
                JOIN 	  neworder o ON o.id = pc.orderID
                WHERE     scanOrderNumber IN (' . $markString . ')
                AND       pc.active
                AND       p.active
                GROUP BY  pc.orderID,
                          upc';

        $results = $this->app->queryResults($sql, $orders);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getProductInventory($param)
    {
        $scanOrderNumber = $param['order'];
        $value = $param['value'];
        $upc = $param['upc'];
        $children = getDefault($param['children'], []);
        $vendor = getDefault($param['vendor']);
        $isTruckOrder = $param['isTruckOrder'];
        $processedCartons = getDefault($param['processedCartons'], []);
        $ucc128 = $param['ucc128'];
        $vendors = $param['vendors'];

        $uomClause = 1;
        $upcDescriptionClause = $processedCartonsClause = NULL;
        $upcDescriptionParam = $uomParam = $limits = $subqueries = [];

        if (! $vendor) {
            $vendor = $vendors->getByScanOrderNumber($scanOrderNumber);
        }

        if ($processedCartons) {
            $markString = $this->app->getQMarkString($processedCartons);

            $processedCartonsClause = ' AND ca.id NOT IN (' . $markString . ')';
        }

        $demand = $value['quantity'];
        $cartonLocation = getDefault($value['cartonLocation']);
        $prefix = getDefault($value['prefix']);
        $suffix = getDefault($value['suffix']);
        $uom = getDefault($value['uom'], []);

        if ($uom) {
            // add requested quantity to a list of selected UOMs
            $uom[] = $demand;

            $markString = $this->app->getQMarkString($uom);
            $uomClause = 'uom IN (' . $markString . ')';
            $uomParam = $uom;
        }

        if ($cartonLocation !== FALSE) {
            $upcDescriptionClause .= ' AND l.displayName = ?';
            $upcDescriptionParam[] = $cartonLocation;
        }

        if ($prefix !== FALSE) {
            $upcDescriptionClause .= ' AND prefix = ?';
            $upcDescriptionParam[] = $prefix;
        }

        if ($suffix !== FALSE) {
            // db field's null value is an equivalent of empty value
            $upcDescriptionClause .= $suffix !== '' ? ' AND suffix = ?' :
                    ' AND (suffix = ? OR suffix IS NULL)';

            $upcDescriptionParam[] = $suffix;
        }

        // Cartons that were included into a Pick Ticket associated with
        // this order
        $subqueries = $this->getProductInventorySubqueries([
            'upcDescriptionClause' => $upcDescriptionClause,
            'uomClause' => $uomClause,
            'children' => $children,
            'isTruckOrder' => $isTruckOrder,
            'processedCartonsClause' => $processedCartonsClause,
            'ucc128' => $ucc128,
        ]);

        $unionParams = $this->getProductInventoryUnionParams([
            'scanOrderNumber' => $scanOrderNumber,
            'upc' => $upc,
            'upcDescriptionParam' => $upcDescriptionParam,
            'uomParam' => $uomParam,
            'children' => $children,
            'processedCartons' => $processedCartons,
            'vendor' => $vendor
        ]);

        $limits[] = FALSE;
        $limits[] = FALSE;
        // Each third query has a limit
        $limits[] = intVal($demand);

        $subqueryCount = 3;

        $singeUOMParams = $singeUOMSubqueries = $singeUOMLimits = [];

        if (! $uom) {

            $quantity = $value['quantity'];

            $singeUOMSubqueries = $this->getProductInventorySubqueries([
                'upcDescriptionClause' => $upcDescriptionClause,
                'uomClause' => 'uom = ?',
                'children' => [],
                'isTruckOrder' => $isTruckOrder,
                'processedCartonsClause' => $processedCartonsClause,
                'ucc128' => $ucc128,
            ]);

            $singeUOMParams = $this->getProductInventoryUnionParams([
                'scanOrderNumber' => $scanOrderNumber,
                'upc' => $upc,
                'upcDescriptionParam' => $upcDescriptionParam,
                'uomParam' => [$quantity],
                'children' => [],
                'processedCartons' => $processedCartons,
                'vendor' => $vendor
            ]);
            // limit every query results to 1 as long as we need a single carton
            // that has UOM equal to $quantity
            $singeUOMLimits = array_fill(0, $subqueryCount, 1);

            $subqueryCount *= 2;
        }

        return $this->app->queryUnionResults([
            'limits' => array_merge($limits, $singeUOMLimits),
            'subqueries' => array_merge($subqueries, $singeUOMSubqueries),
            'mysqlParams' => array_merge($unionParams, $singeUOMParams),
            'subqueryCount' => $subqueryCount,
        ]);
    }

    /*
    ****************************************************************************
    */

    function getDBProducts($orders)
    {
        $markString = $this->app->getQMarkString($orders);

        $sql = 'SELECT    ca.id,
                          scanOrderNumber,
                          isMezzanine
                FROM      neworder n
                JOIN      pick_cartons pc ON pc.orderID = n.id
                JOIN      inventory_cartons ca ON ca.id = pc.cartonID
                JOIN      locations l ON l.id = ca.locID
                WHERE     scanOrderNumber IN (' . $markString . ')
                AND       active';

        $results = $this->app->queryResults($sql, $orders);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getOnlineOrderNumbers($orders, $keys=FALSE, $isWholesale = FALSE)
    {
        if (! $orders) {
            return [];
        }

        $wholesale = $isWholesale ? 'd.displayName != "Wholesale" AND' : NULL;
        $markString = $this->app->getQMarkString($orders);
        $sql = 'SELECT    SCAN_SELDAT_ORDER_NUMBER,
                          o.id
                FROM      online_orders o
                JOIN      neworder n ON o.SCAN_SELDAT_ORDER_NUMBER = n.scanordernumber
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      deal_sites d ON d.id = ob.dealSiteID
                WHERE     ' . $wholesale . '
                SCAN_SELDAT_ORDER_NUMBER IN (' . $markString . ')';

        $results = $this->app->queryResults($sql, $orders);

        return $keys ? array_keys($results) : $results;
    }

    /*
    ****************************************************************************
    */

    function getClosedOrdersProducts($orders)
    {
        if (! $orders) {
            return [];
        }

        $markString = $this->app->getQMarkString($orders);

        $sql = 'SELECT    pc.id,
                          batchID,
                          n.scanOrderNumber,
                          COUNT(ca.id) AS cartonCount,
                          SUM(uom) AS quantity,
                          upcID,
                          uom,
                          u.sku,
                          color,
                          size,
                          upc,
                          locID,
                          displayName AS cartonLocation,
                          prefix,
                          suffix,
                          0 AS available,
                          0 AS volume,
                          0 AS weight
                FROM      pick_cartons pc
                JOIN 	  neworder n ON n.id = pc.orderID
                JOIN      inventory_cartons ca ON ca.id = pc.cartonID
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      locations l ON l.id = ca.locID
                JOIN      upcs u ON u.id = b.upcID
                WHERE     scanOrderNumber IN (' . $markString . ')
                AND       NOT isSplit
                AND       NOT unSplit
                AND       isOriginalPickTicket
                AND       pc.active
                AND       u.active
                GROUP BY  scanOrderNumber,
                          u.sku,
                          size,
                          color,
                          upc,
                          uom,
                          displayName,
                          prefix,
                          suffix';

        $dbProducts = $this->app->queryResults($sql, $orders);

        $batches = new inventory\batches($this->app);

        $results = $batches->addUnitDimensions($dbProducts);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getNotClosedOrdersProducts($orders)
    {
        if (! $orders) {
            return [];
        }

        // products from error orders (no cartons were reserved)
        $failedOrders = $this->getProductsFromErrorOrders($orders);
        $dbProducts = array_values($failedOrders);
        // select orders from online_orders file

        $selectedOrders = array_column($failedOrders, 'scanOrderNumber');
        $processedOrders = array_unique($selectedOrders);
        $unprocessedOrders = array_diff($orders, $processedOrders);

        if (! $unprocessedOrders) {
            return $dbProducts;
        }

        $unprocessedOrders = array_values($unprocessedOrders);

        $onlineOrders = $this->getOnlineOrderNumbers($unprocessedOrders, TRUE);

        $originalOrders = array_diff($unprocessedOrders, $onlineOrders);

        if ($onlineOrders) {
            // products from online_orders with reserved cartons
            $reservedOnlineOrders =
                $this->getReservedOnlineOrders($onlineOrders);

            $dbProducts = array_merge($dbProducts, $reservedOnlineOrders);

            // remove orders with reserved cartons from the list of orders that
            // will be selected from online_orders table
            $selectedOrders = array_column($reservedOnlineOrders,
                'scanOrderNumber');
            $processedOrders = array_unique($selectedOrders);
            $unprocessedOrders = array_diff($onlineOrders, $processedOrders);

            if ($unprocessedOrders) {
                // products from online orders (no cartons were reserved)
                $notReservedOnlineOrders =
                    $this->getNotReservedOnlineOrders($unprocessedOrders);

                $dbProducts =
                    array_merge($dbProducts, $notReservedOnlineOrders);
            }
        }

        if ($originalOrders) {
            // products from original neworders with reserved cartons

            $reservedOriginalOrders = $this->getReservedOriginalOrders($orders);
            $dbProducts = array_merge($dbProducts, $reservedOriginalOrders);
        }

        $batches = new inventory\batches($this->app);

        $results = $batches->addUnitDimensions($dbProducts);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getOrdersWarehouses($orders)
    {
        $qMarks = $this->app->getQMarkString($orders);

        $sql = 'SELECT    scanOrderNumber,
                          order_batch,
                          v.warehouseID
                FROM      neworder n
                JOIN      order_batches b ON b.id = n.order_batch
                JOIN      vendors v ON v.id = b.vendorID
                WHERE     n.scanOrderNumber IN (' . $qMarks . ')';

        $result = $this->app->queryResults($sql, $orders);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getWavePickOrdersByBatch($batch)
    {
        $sql = 'SELECT    scanOrderNumber
                FROM      neworder n
                JOIN      statuses s ON s.id = n.statusID
                WHERE     order_batch = ?
                AND       s.shortName != "' . orders::STATUS_CANCELED . '"
                ';

        $results = $this->app->queryResults($sql, [$batch]);

        return array_keys($results);
    }

    /*
    ****************************************************************************
    */

    function checkIfOrderProcessed($orderNumbers)
    {
        if (! $orderNumbers) {
            return;
        }

        $params = is_array($orderNumbers) ? $orderNumbers : [$orderNumbers];

        $results = $this->getOrderProcessed($params);

        $processedOrders = $canceledOrders = [];

        foreach ($results as $orderNumber => $values) {
            // need to return boolean result
            $processedOrders[$orderNumber] = $values['isClosed'] ? TRUE : FALSE;
            $canceledOrders[$orderNumber] = $values['isCancelled'];
        }

        foreach ($params as $orderNumber) {

            $processedOrders[$orderNumber] =
                    getDefault($processedOrders[$orderNumber]);

            $canceledOrders[$orderNumber] =
                    getDefault($canceledOrders[$orderNumber]);
        }

        return [
            'processedOrders' => $processedOrders,
            'canceledOrders' => $canceledOrders
        ];
    }

    /*
    ****************************************************************************
    */

    function getSubmittedTableData($param)
    {
        $orderNumber = $param['orderNumber'];
        $tableData = $param['tableData'];

        $productErrors = $products = [];
        $row = 0;

        foreach ($tableData as $data) {

            $quantity = $data['quantity'];
            $upc = $data['upc'];

            $uoms = getDefault($data['uom'], []);
            // use UOMs as keys to avoid duplicate values
            $data['uom'] = array_flip($uoms);

            $row++;

            if (! $quantity || ! $upc) {

                $column = $quantity ? 'UPC' : 'Quantity';

                $productErrors[] = 'Order Number # ' . $orderNumber . ', '
                        . 'Order Products table, row # ' . $row . ': ' . $column
                        . ' is a mandatory value';

                continue;
            }

            if ($quantity < 0) {

                $productErrors[] = 'Order Number # ' . $orderNumber . ', '
                        . 'Order Products table, row # ' . $row . ': quantity '
                        . ' can not be negative';

                continue;
            }

            $data['products'] = $products;

            $products = $this->processProductsTableData($data);
        }
        // converting UOMs as keys back to values
        foreach ($products as &$upcData) {
            foreach ($upcData as &$values) {
                $values['uom'] = array_keys($values['uom']);
            }
        }

        ksort($products);

        return [
            'products' => $products,
            'productErrors' => $productErrors,
        ];
    }

    /*
    ****************************************************************************
    */

    function checkSubmittedTableData($param)
    {
        $shortageError = getDefault($param['shortageError']);
        $splitArray = getDefault($param['splitArray']);

        $cartonsToSplit = $productErrors = $shortageProducts = [];

        $result = $this->checkProductDescriptions($param);

        $shortageItems = $result['shortageItems'];
        $splitProducts = $result['splitProducts'];
        $orderProducts = $result['orderProducts'];
        $processedCartons = $result['processedCartons'];

        foreach ($shortageItems as $orderNumber => $shortages) {
            foreach ($shortages as $upc => $shortage) {
                $errorMessage = 'There is not enough inventory for UPC <strong>'
                    . $upc . '</strong> requested for Order # <strong>'
                    . $orderNumber . '</strong>. Shortage is <strong>'
                    . $shortage . '</strong> piece(s)';

                if ($shortageError) {
                    $shortageProducts[] = $errorMessage;
                } else {
                    $productErrors[] = $errorMessage;
                }
            }
        }

        if (! $productErrors && ! $splitArray && $splitProducts) {
            $splitProducts = $productErrors[] = $this->getHTMLCartonsSplit([
                'splitProducts' => $splitProducts,
                'cartonsToSplit' => $cartonsToSplit,
                'printPickTicket' => ! $shortageError
            ]);
        }

        return [
            'productErrors' => $productErrors,
            'orderProducts' => $orderProducts,
            'cartonsToSplit' => $cartonsToSplit,
            'shortageProducts' => $shortageProducts,
            'splitProducts' => $splitProducts,
            'processedCartons' => $processedCartons,
        ];
    }

    /*
    ****************************************************************************
    */

    function checkProductDescriptions($param)
    {
        $orderNumber = $param['order'];
        $products = $param['products'];
        $children = getDefault($param['children'], []);
        $processedCartons = getDefault($param['processedCartons'], []);

        $splitProducts = $shortageItems = $orderProducts = [];

        $checkResults = $this->checkIfOrderProcessed($orderNumber);

        $productParams = [
            'processedCartons' => $processedCartons,
            'orderProducts' => $orderProducts,
            'shortageItems' => $shortageItems,
            'splitProducts' => $splitProducts,
        ];

        $isClosed = $checkResults['processedOrders'][$orderNumber];

        if (! isset($products[$orderNumber]) || $isClosed) {
            return $productParams;
        }

        $param['vendors'] = new vendors($this->app);
        $param['children'] = $children;

        foreach ($products[$orderNumber] as $upc => $values) {

            $param['upc'] = $upc;
            $param['values'] = $values;

            $this->processProductOrder($productParams, $param);

            extract($productParams);
        }

        if (isset($splitProducts[$orderNumber]) && ! $splitProducts[$orderNumber]) {
            $splitProducts = [];
        }

        return [
            'processedCartons' => $processedCartons,
            'orderProducts' => $orderProducts,
            'shortageItems' => $shortageItems,
            'splitProducts' => $splitProducts,
        ];
    }

    /*
    ****************************************************************************
    */

    function getByPlate($plate)
    {
        $result = [];

        if (! $plate) {
            return FALSE;
        }

        if (is_array($plate)) {
            $result = $this->getByPlateArray($plate);
        } else {
            $result = $this->getByPlateString($plate);
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    private function getByPlateString($plate)
    {
        $sql = 'SELECT n.id
                FROM   neworder n
                LEFT   JOIN inventory_cartons ca ON ca.orderID = n.id
                WHERE  plate = ?';

        $result = $this->app->queryResult($sql, [$plate]);

        return $result ? $result['id'] : NULL;
    }

    /*
    ****************************************************************************
    */

    private function getByPlateArray($plate)
    {
        $clauses = array_fill(0, count($plate), 'plate = ?');
        $clauseString = implode(' OR ', $clauses);

        $sql = 'SELECT scanOrderNumber,
                       n.id
                FROM   neworder n
                LEFT   JOIN inventory_cartons ca ON ca.orderID = n.id
                WHERE  ' . $clauseString;

        $data = $this->app->queryResults($sql, $plate);

        $results = [];

        if (! $data) {
            return $results;
        }

        foreach ($data as $orderNumber => $order) {
            $results[$orderNumber] = $order['id'];
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getUPCDescription($param)
    {
        $processed = json_decode(getDefault($param['processed']));

        $selectFiels = 'ca.id,
                        upcID,
                        prefix,
                        suffix,
                        uom';

        $param['selectFields'] = $selectFiels . ',
            cartonLocation,
            SUM(uom) AS available';

        $param['unitSelectFields'] = $selectFiels . ',
            l.displayName AS cartonLocation';

        $param['groupBy'] = 'upcID,
                             cartonLocation,
                             uom,
                             prefix,
                             suffix';

        $resultQuery = $this->buildQueryUPCDescription($param);

        $sqlParams = $resultQuery['params'];
        $sql = $resultQuery['sql'];

        $data = $this->app->queryResults($sql, $sqlParams);

        $results =  $this->multiUOMResult($data, $processed);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function multiUOMResult($cartons, $processed)
    {
        $uoms = $cartonLocations = $prefixes = $suffixes = [];

        $upcParams = compact('uoms', 'cartonLocations', 'prefixes', 'cartons',
                'suffixes');
        $this->setInfomationForUPCs($upcParams);
        extract($upcParams);

        if ($processed) {
            $return = $cartons;
        } else {
            $return = $this->getInfomationAvailableOfUPCs($cartons);
        }

        $results = [
            'results' => $cartons,
            'data' => $return,
            'uom' => $uoms,
            'cartonLocation' => $cartonLocations,
            'prefix' => $prefixes,
            'suffix' => $suffixes,
        ];

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkDuplicateRow($result, $return)
    {
        foreach ($return as $key => $value) {

            $isDuplicateRow = $this->isDuplicateRow($result, $value);
            if ($isDuplicateRow) {
                return $key;
            }
        }

        return -1;
    }

    /*
    ****************************************************************************
    */

    function getProductInfo($data)
    {
        $text = getDefault($data['term']);
        $vendorID = getDefault($data['clientID']);
        $mezzanineClause = getDefault($data['mezzanineClause'], 'NOT isMezzanine');

        if (! $text || ! $vendorID) {
            return FALSE;
        }

        $rackedStatusID = inventory\cartons::STATUS_RACKED;

        $sql = 'SELECT    CONCAT_WS("  ",
                           sku,
                           size,
                           color,
                           upc,
                           totalUnits,
                           upcID
                         ) AS productInfo,
                         ca.*
               FROM (
                   SELECT    u.sku,
                             size,
                             color,
                             upc,
                             SUM(uom) AS totalUnits,
                             upcID
                   FROM      inventory_cartons ca
                   JOIN      inventory_batches b ON b.id = ca.batchID
                   JOIN      inventory_containers co ON co.recNum = b.recNum
                   JOIN      statuses s ON s.id = ca.statusID
                   JOIN      upcs u ON u.id = b.upcID
                   JOIN      locations l ON l.id = ca.locID
                   WHERE     vendorID = ?
                   AND       s.shortName = "' . $rackedStatusID . '"
                   AND       statusID = mStatusID
                   AND       l.displayName NOT IN ('
                        . '"' . locations::NAME_LOCATION_STAGING . '", '
                        . '"' . locations::NAME_LOCATION_BACK_TO_STOCK . '"'
                   . ')
                   AND       ' . $mezzanineClause . '
                   AND       NOT isSplit
                   AND       NOT unSplit
                   AND       (upc LIKE ?
                       OR u.sku LIKE ?
                       OR size LIKE ?
                       OR color LIKE ?
                   )
                   GROUP BY  upc, u.sku, size, color
               ) AS ca';

        $likeText =  '%' . $text . '%';

        $params = [
            $vendorID,
            $likeText,
            $likeText,
            $likeText,
            $likeText
        ];

        $results = $this->app->queryResults($sql, $params);

        $rows = [];
        foreach ($results as $row => $value) {
            $rows[] = [
                'value' => $row,
                'upc' => $value['upc'],
                'sku' => $value['sku'],
                'size' => $value['size'],
                'color' => $value['color'],
                'totalUnits' => $value['totalUnits'],
                'upcID' => $value['upcID'],
            ];
        }

        return $rows;
    }

    /*
    ****************************************************************************
    */

    function getNoMezzanineOrders($orderNumbers)
    {
        $qMarks = $this->app->getQMarkString($orderNumbers);

        $sql = 'SELECT    scanordernumber
                FROM      neworder n
                JOIN      order_batches b ON b.id = n.order_batch
                JOIN      deal_sites d ON d.id = b.dealSiteID
                WHERE     scanordernumber IN (' . $qMarks . ')
                AND       displayName = "Wholesale"';

        $results = $this->app->queryResults($sql, $orderNumbers);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getIDByOrderNumber($orderNumbers)
    {
        $isArray = is_array($orderNumbers);

        $params = $isArray ? $orderNumbers : [$orderNumbers];

        $results = $this->retrieveNeworder($params);

        return $isArray ? $results : $results[$orderNumbers]['id'];
    }

    /*
    ****************************************************************************
    */

    function getOrderNumbersByID($ids)
    {
        $isArray = is_array($ids);

        $params = $isArray ? $ids : [$ids];

        $results = $this->getNewOrderByID($params);

        $return = $isArray ? array_column($results, 'scanOrderNumber') :
            $results[$ids]['scanordernumber'];

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getFieldsValuesBy($orderNumbers, $fields)
    {
        $isOrderArray = is_array($orderNumbers);

        $params = $isOrderArray ? $orderNumbers : [$orderNumbers];

        $clauses = array_fill(0, count($params), 'scanordernumber = ?');

        $isFieldsArray = is_array($fields);

        $select = $isFieldsArray ? implode(', ', $fields) : $fields;

        $condition = implode(' OR ', $clauses);

        $sql = 'SELECT    scanOrderNumber,
                          ' . $select . '
                FROM      neworder
                WHERE     ' . $condition;

        $results = $this->app->queryResults($sql, $params);

        if (! $isFieldsArray && ! $isOrderArray) {
            return  $results[$orderNumbers][$fields];
        }
        return $results;
    }

    /*
    ****************************************************************************
    */

    private function isReserveCartons($data)
    {
        $cartonID = $data['cartonID'];
        $processedCartons = $data['processedCartons'];
        $location = $data['carton']['cartonLocation'];
        $prefix = $data['carton']['prefix'];
        $suffix = $data['carton']['suffix'];
        $value = $data['value'];

        $cartonLocation = getDefault($value['cartonLocation']);
        $cartonPrefix = getDefault($value['prefix']);
        $cartonSuffix = getDefault($value['suffix']);

        $sameLocation = $cartonLocation === FALSE || $location == $cartonLocation;
        $samePrefix = $cartonPrefix === FALSE || $prefix == $cartonPrefix;
        $sameSuffix = $cartonSuffix === FALSE || $suffix == $cartonSuffix;

        return ! isset($processedCartons[$cartonID])
            && $sameLocation && $samePrefix && $sameSuffix;
    }

    /*
    ****************************************************************************
    */

    private function reserveCartons(&$reserveParams)
    {
        $reserveParams['reservedPieces'] += $reserveParams['uom'];
        $reserveParams['reserved'][] = $reserveParams['cartonID'];

        //Continue the foreach
        $continue = 0;

        if ($reserveParams['reservedPieces'] == $reserveParams['demand']) {
            // a proper combination is found among reserved cartons
            $orderNumber = $reserveParams['orderNumber'];

            foreach ($reserveParams['reserved'] as $cartonID) {
                $reserveParams['processedCartons'][$cartonID] = TRUE;
                $reserveParams['orderProducts'][$orderNumber][] = $cartonID;
            }

            $continue = 2;
        }

        return $continue;
	}

    /*
    ****************************************************************************
    */

    private function processProductOrder(&$productParams, $param)
    {
        $processedCartons = &$productParams['processedCartons'];
        $orderProducts 	= &$productParams['orderProducts'];
        $splitProducts = &$productParams['splitProducts'];
        $shortageItems = &$productParams['shortageItems'];
        $upc = $param['upc'];
        $values = $param['values'];
        $orderNumber = $param['order'];

        foreach ($values as $value) {

            $param['value'] = $value;

            $inventoryCartons = $this->getProductInventory($param);

            $demand = $value['quantity'];
            // truck orders inventory do not have predefined location/prefix/suffix
            $prefix = getDefault($value['prefix']);
            $suffix = getDefault($value['suffix']);
            $location = getDefault($value['cartonLocation']);

            $locationKey = $location === FALSE ? 'ANY LOCATION' : $location;
            $prefixKey = $prefix === FALSE ? 'ANY PREFIX' : $prefix;
            $suffixKey = $suffix === FALSE ? 'ANY SUFFIX' : $suffix;

            $key = 'location: ' . $locationKey . ', ' . 'prefix: ' . $prefixKey
                 . ', ' . 'suffix: ' . $suffixKey;

            // check among reserved for this order cartons
            $reserved = $inventory = $splitCartons = $originalCartons = [];
            $reservedPieces = $pieces = 0;

            foreach($inventoryCartons as $cartonID => $carton) {

                $valueParams = compact('carton', 'value',
                    'processedCartons', 'cartonID');
                if (! $this->isReserveCartons($valueParams)) {
                    continue;
                }

                $uom = $carton['uom'];
                $ucc128 = $carton['ucc128'];

                if ($carton['parentID']) {
                    $splitCartons[$uom][$cartonID] = $ucc128;
                } else {
                    $originalCartons[$uom][$cartonID] = $ucc128;
                }

                $pieces += $uom;

                if (! $carton['pickTicket']) {
                    continue;
                }

                $reserveParams = compact('reservedPieces', 'uom', 'cartonID',
                'orderNumber', 'processedCartons', 'orderProducts', 'demand',
                    'reserved');
                $reserveCartonResult = $this->reserveCartons($reserveParams);
                extract($reserveParams);

                if ($reserveCartonResult) {
                    continue 2;
                }
            }

            if ($demand > $pieces) {
                $shortageItems[$orderNumber][$upc] = $demand - $pieces;
                continue;
            }

            // check among available inventory
            $this->checkAmongInventory($originalCartons, $splitCartons,
                $inventory);

            // attempt to find a combination of cartons that fit product quantity
            $combinedValues = compact('inventory','splitProducts', 'upc', 'key',
                'orderNumber', 'demand', 'processedCartons', 'orderProducts');

            $this->combineCartonsInventory($combinedValues);

            extract($combinedValues);

            if ($demand > 0) {

                $splitProducts[$orderNumber][$upc][$key]['portionTwo'] = $demand;
                $splitProducts[$orderNumber][$upc][$key]['portionOne']
                    = $splitProducts[$orderNumber][$upc][$key]['portionOne'] - $demand;

                $cartonID = $splitProducts[$orderNumber][$upc][$key]['cartonID'];

                if (! isset($processedCartons[$cartonID])) {
                    $processedCartons[$cartonID] = TRUE;
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    private function combineCartonsInventory(&$combinedValues)
    {
        $demand = &$combinedValues['demand'];
        $inventory = $combinedValues['inventory'];
        $upc = $combinedValues['upc'];
        $key = $combinedValues['key'];
        $orderNumber = $combinedValues['orderNumber'];
        $splitProducts = &$combinedValues['splitProducts'];
        $processedCartons = &$combinedValues['processedCartons'];
        $orderProducts = &$combinedValues['orderProducts'];

        foreach ($inventory as $uom => $cartons) {
            foreach ($cartons as $cartonID => $ucc128) {
                if ($uom > $demand) {

                    $splitProducts[$orderNumber][$upc][$key] = [
                        'cartonID' => $cartonID,
                        'ucc128' => $ucc128,
                        'portionOne' => $uom,
                    ];

                    // uoms of cartons within this array as greater than remnant
                    continue 2;
                }

                $demand -= $uom;

                $processedCartons[$cartonID] = TRUE;
                $orderProducts[$orderNumber][] = $cartonID;

                if ($demand == 0) {
                    // cartons that fully fit product quantity were allocated
                    $splitProducts = $this->unsetEmptySplit([
                        'splitProducts' => $splitProducts,
                        'orderNumber' => $orderNumber,
                        'upc' => $upc,
                        'key' => $key,
                    ]);

                    break 2;
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    function unsetEmptySplit($data)
    {
        $splitProducts = $data['splitProducts'];
        $orderNumber = $data['orderNumber'];
        $upc = $data['upc'];
        $key = $data['key'];

        if (! getDefault($splitProducts[$orderNumber])) {

            unset($splitProducts[$orderNumber]);

            return $splitProducts;
        }

        unset($splitProducts[$orderNumber][$upc][$key]);

        if (! getDefault($splitProducts[$orderNumber][$upc])) {
            unset($splitProducts[$orderNumber][$upc]);
        }

        if (! getDefault($splitProducts[$orderNumber])) {
            unset($splitProducts[$orderNumber]);
        }

        return $splitProducts;
    }

    /*
    ****************************************************************************
    */

    function checkAmongInventory($originalCartons, $splitCartons, &$inventory)
    {
        foreach ($originalCartons as $uom => $cartons) {
            foreach ($cartons as $cartonID => $ucc) {
                $inventory[$uom][$cartonID] = $ucc;
            }
        }

        foreach ($splitCartons as $uom => $cartons) {
            foreach ($cartons as $cartonID => $ucc) {
                $inventory[$uom][$cartonID] = $ucc;
            }
        }

        krsort($inventory);
    }

    /*
    ****************************************************************************
    */

    private function getOrderonHoldOrError($params)
    {
        $orderNumber = $params['orderNumber'];
        $where = $params['where'];
        $select = $params['select'];
        $shortName = $params['shortName'];
        $category = $params['category'];
        $results = [];

        if (! $orderNumber) {
            return [];
        }

        $params = is_array($orderNumber) ? array_values($orderNumber) :
            [$orderNumber];

        $markString = $this->app->getQMarkString($params);

        $sql = 'SELECT    ' . $where . ',
                          ' . $select . '
                FROM      neworder n
                JOIN      statuses s ON s.id = n.' . $select . '
                WHERE     ' . $where . '
                IN        (' . $markString . ')
                AND       shortName = ?
                AND       category = ?';

        $params[] = $shortName;
        $params[] = $category;

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function setMessageOfHoldOrError($params)
    {
        $select = $params['select'];
        $shortName = &$params['shortName'];
        $msg = &$params['msg'];
        $category = &$params['category'];

        $result = TRUE;

        switch ($select) {
            case 'holdStatusID':

                $shortName = self::STATUS_ON_HOLD;
                $msg = 'is currently On Hold';
                $category = self::CATEGORY_HOLD;
                break;
            case 'isError':

                $shortName = self::STATUS_ERROR;
                $msg = 'is an Error Order';
                $category = self::CATEGORY_ORDER_ERROR;
                break;
            default:

                $result = FALSE;
                break;
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getVendorIDByTarget($target, $id)
    {
        $sql = 'SELECT b.vendorID AS vendor
                FROM   neworder n
                JOIN   order_batches b ON b.id = n.order_batch
                WHERE ' . $target . ' = ? ';

        $result = $this->app->queryResult($sql, [$id]);

        if (! $result) {
            return  FALSE;
        }
        $vendor = $result['vendor'];

        return $vendor;
    }

    /*
    ****************************************************************************
    */

    function checkValidCartonOfVendor($params)
    {
        $id = $params['id'];
        $plates = $params['plates'];
        $vendorID = $params['vendorID'];
        $valid = &$params['valid'];
        $results = &$params['results'];

        if (! $plates) {
            return;
        }

        foreach ($plates as $plate => $cartons) {

            foreach ($cartons as $carton) {
                $carton = reset($carton);

                if ((string)$vendorID != substr($carton,0, 5)) {

                    $valid = FALSE;
                    $results[$carton] = [
                        'order'  => $id,
                        'ucc' => $carton,
                        'clientNotMatch' => FALSE,
                    ];
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    private function getProductInventorySubqueries($params)
    {
        $upcDescriptionClause = $params['upcDescriptionClause'];
        $uomClause = $params['uomClause'];
        $children = $params['children'];
        $isTruckOrder = $params['isTruckOrder'];
        $processedCartonsClause = $params['processedCartonsClause'];
        $ucc128 = $params['ucc128'];

        $subqueries = [];

        $rackedStatusID = inventory\cartons::STATUS_RACKED;

        $mezzanineClause = $isTruckOrder ? 'isMezzanine' : 'NOT isMezzanine';

        $childrenClause = 0;

        if ($children) {
            // passing cartons that were split at last Create Pick Ticket attempt
            $qMarks = $this->app->getQMarkString($children);

            $childrenClause = $ucc128 . ' IN (' . $qMarks . ')';
        }

        $select = 'SELECT    ca.id,
                             ' . $ucc128 . ' AS ucc128,
                             upc,
                             uom,
                             l.displayName AS cartonLocation,
                             prefix,
                             suffix,
                             sp.parentID';

        $tables = 'FROM      inventory_containers co
                   JOIN      inventory_batches b ON b.recNum = co.recNum
                   JOIN      inventory_cartons ca ON ca.batchID = b.id
                   JOIN      statuses s ON s.id = ca.statusID
                   JOIN      upcs p ON p.id = b.upcID
                   JOIN      locations l ON l.id = ca.locID
                   LEFT JOIN inventory_splits sp ON sp.childID = ca.id';

        $subqueries[] =
            $select . ',
            1 AS pickTicket
            ' . $tables . '
            LEFT JOIN pick_cartons pc ON pc.cartonID = ca.id
            LEFT JOIN neworder n ON n.id = pc.orderID
            WHERE     scanOrderNumber = ?
            AND       upc = ?
            AND       pc.active
            AND       s.shortName = "' . $rackedStatusID . '"
            AND       NOT isSplit
            AND       NOT unSplit
            AND       l.displayName NOT IN ('
                . '"' . \tables\locations::NAME_LOCATION_STAGING . '", '
                . '"' . \tables\locations::NAME_LOCATION_BACK_TO_STOCK . '"'
            . ')
            AND       p.active
            AND       ' . $mezzanineClause
            . $upcDescriptionClause . '
            AND (' . $uomClause . ' OR ' . $childrenClause . ')
            ' . $processedCartonsClause . '
            ORDER BY  uom DESC,
                      batchID ASC,
                      l.displayName ASC,
                      ca.id DESC';

        $subqueryPrefix =
            $select . ',
            0 AS pickTicket
            ' . $tables . '
            WHERE     vendorID = ?
            AND       upc = ?
            AND       NOT isSplit
            AND       NOT unSplit
            AND       s.shortName = "' . $rackedStatusID . '"
            AND       ca.statusID = mStatusID
            AND       ' . $mezzanineClause
            . $upcDescriptionClause;

        $subquerySuffix =
            $processedCartonsClause . '
            ORDER BY  uom DESC,
                      batchID ASC,
                      l.displayName ASC,
                      ca.id DESC';

        // Split cartons that were not included into a Pick Ticket
        // associated with this order
        $subqueries[] =
            $subqueryPrefix . '
            AND sp.id IS NOT NULL' . $subquerySuffix;

        // Full cartons that were not included into a Pick Ticket
        // associated with this order
        $subqueries[] =
            $subqueryPrefix . '
            AND sp.id IS NULL AND ' . $uomClause . $subquerySuffix;

        return $subqueries;
    }

    /*
    ****************************************************************************
    */

    private function getProductInventoryUnionParams($params)
    {
        $scanOrderNumber = $params['scanOrderNumber'];
        $upc = $params['upc'];
        $upcDescriptionParam = $params['upcDescriptionParam'];
        $uomParam = $params['uomParam'];
        $children = $params['children'];
        $processedCartons = $params['processedCartons'];
        $vendor = $params['vendor'];

        // UNION 1
        $unionOneParams = array_merge(
            [$scanOrderNumber, $upc],
            $upcDescriptionParam,
            $uomParam,
            $children
        );

        foreach ($processedCartons as $cartonID => $value) {
            $unionOneParams[] = $cartonID;
        }

        // UNION 2
        $unionTwoParams = array_merge(
            $unionOneParams,
            [$vendor, $upc],
            $upcDescriptionParam
        );

        foreach ($processedCartons as $cartonID => $value) {
            $unionTwoParams[] = $cartonID;
        }

        // UNION 3
        $unionThreeParams = array_merge(
            $unionTwoParams,
            [$vendor, $upc],
            $upcDescriptionParam,
            $uomParam
        );

        foreach ($processedCartons as $cartonID => $value) {
            $unionThreeParams[] = $cartonID;
        }

        $unionParams = $unionThreeParams;

        return $unionParams;
    }

    /*
    ****************************************************************************
    */

    private function getProductsFromErrorOrders ($orders)
    {
        $markString = $this->app->getQMarkString($orders);
        $sql = 'SELECT    pe.id,
                          0 AS batchID,
                          n.scanOrderNumber,
                          upcID,
                          0 AS cartonCount,
                          0 AS uom,
                          quantity,
                          u.sku,
                          u.color,
                          u.size,
                          upc,
                          uom,
                          "ANY LOCATION" AS cartonLocation,
                          "ANY PREFIX" AS prefix,
                          "ANY SUFFIX" AS suffix,
                          0 AS available,
                          0 AS volume,
                          0 AS weight
                FROM      pick_errors pe
                JOIN 	  neworder n ON n.id = pe.orderID
                JOIN      upcs u ON u.id = pe.upcID
                WHERE     scanOrderNumber IN (' . $markString . ')
                AND       pe.active';

        $failedOrders = $this->app->queryResults($sql, $orders);

        return $failedOrders;
    }

    /*
    ****************************************************************************
    */

    private function getReservedOnlineOrders($onlineOrders)
    {
        $markString = $this->app->getQMarkString($onlineOrders);

        $sql = 'SELECT    pc.id,
                          0 AS batchID,
                          n.scanOrderNumber,
                          upcID,
                          COUNT(pc.id) AS cartonCount,
                          uom,
                          SUM(ca.uom) AS quantity,
                          u.sku,
                          color,
                          size,
                          u.upc,
                          displayName AS cartonLocation,
                          prefix,
                          suffix,
                          0 AS available,
                          0 AS volume,
                          0 AS weight
                FROM      pick_cartons pc
                JOIN 	  neworder n ON n.id = pc.orderID
                JOIN      inventory_cartons ca ON ca.id = pc.cartonID
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      locations l ON l.id = ca.locID
                JOIN      upcs u ON u.id = b.upcID
                WHERE     scanOrderNumber IN (' . $markString . ')
                AND       n.pickID
                AND       u.active
                AND       (pc.active
                    OR isOriginalPickTicket
                )
                AND       NOT isMezzanine
                AND       NOT isSplit
                AND       NOT unSplit
                GROUP BY  n.scanOrderNumber, u.sku, size, color, upc, uom,
                          displayName, prefix, suffix';

        $reservedOnlineOrders = $this->app->queryResults($sql, $onlineOrders);

        return $reservedOnlineOrders;
    }

    /*
    ****************************************************************************
    */

    private function getNotReservedOnlineOrders($unprocessedOrders)
    {
        $markString = $this->app->getQMarkString($unprocessedOrders);

        $sql = 'SELECT  o.id,
                        0 AS batchID,
                        n.scanOrderNumber,
                        u.id AS upcID,
                        0 AS cartonCount,
                        0 AS uom,
                        SUM(product_quantity) AS quantity,
                        u.sku,
                        color,
                        size,
                        o.upc,
                        "" AS cartonLocation,
                        "" AS prefix,
                        "" AS suffix,
                        0 AS available,
                        0 AS volume,
                        0 AS weight
            FROM      online_orders o
            JOIN 	  neworder n
            ON        n.scanOrderNumber = o.SCAN_SELDAT_ORDER_NUMBER
            JOIN      upcs u ON u.upc = o.upc
            WHERE     SCAN_SELDAT_ORDER_NUMBER IN (' . $markString . ')
            AND       n.pickID IS NULL
            GROUP BY  SCAN_SELDAT_ORDER_NUMBER, product_sku, upc';

        $notReservedOnlineOrders = $this->app->queryResults($sql,
              array_values($unprocessedOrders));

        return $notReservedOnlineOrders;
    }

    /*
    ****************************************************************************
    */

    function getReservedOriginalOrders($orders)
    {
        $markString = $this->app->getQMarkString($orders);

        $sql = 'SELECT    pc.id,
                          batchID,
                          n.scanOrderNumber,
                          COUNT(ca.id) AS cartonCount,
                          SUM(uom) AS quantity,
                          upcID,
                          uom,
                          u.sku,
                          color,
                          size,
                          upc,
                          locID,
                          displayName AS cartonLocation,
                          prefix,
                          suffix,
                          0 AS available,
                          0 AS volume,
                          0 AS weight
                FROM      pick_cartons pc
                JOIN      neworder n ON n.id = pc.orderID
                JOIN      order_batches ba ON ba.id = n.order_batch
                JOIN      inventory_cartons ca ON ca.id = pc.cartonID
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      locations l ON l.id = ca.locID
                JOIN      upcs u ON u.id = b.upcID
                WHERE     scanOrderNumber IN (' . $markString . ')
                AND       (pc.active
                    OR isOriginalPickTicket
                )
                AND       NOT isMezzanine
                AND       NOT isSplit
                AND       NOT unSplit
                AND       u.active
                GROUP BY  scanOrderNumber, u.sku, size, color, upc, uom,
                          displayName, prefix, suffix';

        $param = array_values($orders);

        $reservedOriginalOrders = $this->app->queryResults($sql, $param);

        return $reservedOriginalOrders;
    }

    /*
    ****************************************************************************
    */

    private function getHTMLCartonsSplit($params)
    {
        $splitProducts = $params['splitProducts'];
        $cartonsToSplit = $params['cartonsToSplit'];
        $printPickTicket = $params['printPickTicket'];

        ob_start(); ?>
        <br class="splitDivBreak">
        <div class="splitCartonsDiv">
        Some cartons need split:<br>
        <table border="1" class="splitTable">
            <tr>
                <th>UPC</th>
                <th>UCC128</th>
                <th>UOM A</th>
                <th>UOM B</th>
            </tr><?php

        foreach ($splitProducts as $orderNumber => $values) {
            foreach ($values as $upc => $errors) {
                foreach ($errors as $error) {

                    $ucc = $error['ucc128'];
                    $uomA = $error['portionOne'];
                    $uomB = $error['portionTwo'];

                    $cartonsToSplit[$ucc] = [
                        'uomA' => $uomA,
                        'uomB' => $uomB,
                    ]; ?>

            <tr>
                <td><?php echo $upc; ?></td>
                <td class="ucc"><?php echo $ucc; ?></td>
                <td align="center" class="uomA"><?php echo $uomA; ?></td>
                <td align="center" class="uomB"><?php echo $uomB; ?></td>
            </tr>

                <?php }
            }
        }  ?>

        </table>
        <br>
        <button class="splitCartons"
                data-order-number="<?php echo $orderNumber; ?>"
                data-print-pick-ticket="<?php echo $printPickTicket; ?>">
            Split cartons
        </button>
        <span class="processing" style=" display: none;">
            Processing split cartons. Please wait ...
        </span>
        </div><?php

        $strHtml = ob_get_clean();

        return $strHtml;
    }

    /*
    ****************************************************************************
    */

    function getOrderProcessed($params)
    {
        $qMarks = $this->app->getQMarkString($params);

        $orderStatus = self::getProcessedOrdersStatuses('orderProcessed');

        $sql = 'SELECT    scanOrderNumber,
                          s.shortName,
                          FIND_IN_SET(s.shortName, "' . implode(',', $orderStatus) . '") > 0
                          OR hs.shortName = "' . self::STATUS_ON_HOLD . '"
                          OR es.shortName = "' . self::STATUS_ERROR . '" AS isClosed,
                          s.shortName = "' . self::STATUS_CANCELED . '" AS isCancelled,
                          hs.shortName = "' . self::STATUS_ON_HOLD . '" AS isHold,
                          es.shortName = "' . self::STATUS_ERROR . '" AS isError
                FROM      neworder n
                JOIN      statuses s ON s.id = n.statusID
                LEFT JOIN statuses hs ON hs.id = n.holdStatusID
                LEFT JOIN statuses es ON es.id = n.isError
                WHERE     scanOrderNumber IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    private function processProductsTableData($data)
    {
        $products = $data['products'];
        $quantity = $data['quantity'];
        $upc = $data['upc'];
        $uom = getDefault($data['uom'], []);
        $cartonLocation = getDefault($data['cartonLocation']);
        $prefix = getDefault($data['prefix']);
        $suffix = getDefault($data['suffix']);

        $products[$upc] = getDefault($products[$upc], []);

        foreach ($products[$upc] as $key => $values) {

            $sameLocation = $values['cartonLocation'] === $cartonLocation;
            $samePrefix = $values['prefix'] === $prefix;
            $sameSuffix = $values['suffix'] === $suffix;

            if ($sameLocation && $samePrefix && $sameSuffix) {

                $products[$upc][$key]['quantity'] += $quantity;

                $uomValues = array_keys($uom);

                foreach ($uomValues as $value) {
                    // store UOM as a key to avoid duplicates
                    $products[$upc][$key]['uom'][$value] = TRUE;
                }

                return $products;
            }
        }

        $products[$upc][] = [
            'uom' => $uom,
            'cartonLocation' => $cartonLocation,
            'prefix' => $prefix,
            'suffix' => $suffix,
            'quantity' => $quantity,
        ];

        return $products;
    }

    /*
    ****************************************************************************
    */

    function buildQueryUPCDescription($data)
    {
        $orderNumber = $data['orderNumber'];
        $selectField = $data['selectFields'];
        $unitSelectFields = $data['unitSelectFields'];
        $groupBy = $data['groupBy'];

        $select = 'SELECT    ' . $selectField;
        $unitSelect = 'SELECT    ' . $unitSelectFields;

        $where = $this->getWhereQueryUPCDescription($data);

        $sql = $this->getQueryUPCDescription([
            'select' => $select,
            'unitSelect' => $unitSelect,
            'where' => $where['clause'],
            'groupBy' => $groupBy,
        ]);

        $params = array_merge($where['params'], $where['params'], [$orderNumber]);

        return [
            'sql' => $sql,
            'params' => $params,
        ];
    }

    /*
    ****************************************************************************
    */

    private  function getWhereQueryUPCDescription($param)
    {
        $vendorID = $param['vendorID'];
        $upcIDs = getDefault($param['upcIDs']);
        $uomList = getDefault($param['uom']);
        $cartonLocation = getDefault($param['cartonLocation']);
        $prefix = getDefault($param['prefix']);
        $suffix = getDefault($param['suffix']);
        $mezzanineClause = getDefault($param['mezzanineClause'], 'NOT isMezzanine');

        $sqlParams = [];

        $where =  'WHERE     vendorID = ?
                   AND       ' . $mezzanineClause . '
                   AND       l.displayName NOT IN ('
                        . '"' . locations::NAME_LOCATION_STAGING . '", '
                        . '"' . locations::NAME_LOCATION_BACK_TO_STOCK . '"'
                   . ')';

        if ($upcIDs) {
            $qMarkUpcIdString = $this->app->getQMarkString($upcIDs);
            $where .= ' AND upcID IN ('  . $qMarkUpcIdString . ')';
            $sqlParams = $upcIDs;
        }

        array_unshift($sqlParams, $vendorID);

        if ($uomList !== FALSE) {
            $qMarkUomString = $this->app->getQMarkString($uomList);
            $where .= ' AND uom IN ('  . $qMarkUomString . ')';
            $sqlParams = array_merge($sqlParams, $uomList);
        }

        if ($cartonLocation !== FALSE) {
            $where .= ' AND l.displayName = ?';
            $sqlParams[] = $cartonLocation;
        }

        if ($prefix !== FALSE) {
            $where .= ' AND prefix = ?';
            $sqlParams[] = $prefix;
        }

        if ($suffix !== FALSE) {
            $where .= ' AND suffix = ?';
            $sqlParams[] = $suffix;
        }

        return [
            'clause' => $where,
            'params' => $sqlParams,
        ];
    }

    /*
    ****************************************************************************
    */

    private function getQueryUPCDescription($params)
    {
        $select = $params['select'];
        $unitSelect = $params['unitSelect'];
        $where = $params['where'];
        $groupBy = $params['groupBy'];

        $rackedStatusID = inventory\cartons::STATUS_RACKED;

        $tables = 'FROM      inventory_containers co
                   JOIN      inventory_batches b ON b.recNum = co.recNum
                   JOIN      inventory_cartons ca ON ca.batchID = b.id
                   JOIN      locations l ON l.id = ca.locID';

        $sql = $select . '
            FROM      (
            -- Cartons that are not included into any Pick Ticket
                    ' . $unitSelect . '
                    ' . $tables . '
                    JOIN      statuses s ON s.id = ca.statusID
                    ' . $where . '
                    AND       NOT isSplit
                    AND       NOT unSplit
                    AND       s.shortName = "' . $rackedStatusID . '"
                    AND       ca.statusID = mStatusID
                UNION
            -- Cartons that were included into a Pick Ticket pertaining to the order
                    ' . $unitSelect . '
                    ' . $tables . '
                    JOIN      pick_cartons pc ON pc.cartonID = ca.id
                    JOIN      neworder n ON n.id = pc.orderID
                    ' . $where . '
                    AND       n.scanOrderNumber = ?
                    AND       pc.active
            ) ca
            GROUP BY  ' . $groupBy;

        return $sql;
    }

    /*
    ****************************************************************************
    */

    private function isDuplicateRow($result, $value)
    {
        if ($result['upcID'] != $value['upcID']) {
            return FALSE;
        }

        if ($result['cartonLocation'] != $value['cartonLocation']) {
            return FALSE;
        }

        if ( $result['prefix'] != $value['prefix']) {
            return FALSE;
        }

        if ($result['suffix'] != $value['suffix']) {
            return FALSE;
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    private function setInfomationForUPCs(&$upcParams)
    {
        $cartons = $upcParams['cartons'];
        $uoms = &$upcParams['uoms'];
        $cartonLocations = &$upcParams['cartonLocations'];
        $prefixes = &$upcParams['prefixes'];
        $suffixes = &$upcParams['suffixes'];

        foreach ($cartons as $result) {

            $upcID = $result['upcID'];
            $uom = $result['uom'];
            $cartonLocation = $result['cartonLocation'];
            $prefix = $result['prefix'];
            $suffix = $result['suffix'];

            // use in_array because prefixes and siffixes can be empty string

            $uoms[$upcID] = getDefault($uoms[$upcID], []);
            if (! in_array($uom, $uoms[$upcID])) {
                $uoms[$upcID][] = $uom;
            }

            $cartonLocations[$upcID] = getDefault($cartonLocations[$upcID], []);
            if (! in_array($cartonLocation, $cartonLocations[$upcID], TRUE)) {
                $cartonLocations[$upcID][] = $cartonLocation;
            }

            $prefixes[$upcID] = getDefault($prefixes[$upcID], []);
            if (! in_array($prefix, $prefixes[$upcID], TRUE)) {
                $prefixes[$upcID][] = $prefix;
            }

            $suffixes[$upcID] = getDefault($suffixes[$upcID], []);
            if (! in_array($suffix, $suffixes[$upcID], TRUE)) {
                $suffixes[$upcID][] = $suffix;
            }
        }
    }

    /*
    ****************************************************************************
    */

    private function getInfomationAvailableOfUPCs($cartons)
    {
        $return = [];

        foreach ($cartons as $result) {

            $key = $this->checkDuplicateRow($result, $return);

            if ($key == -1) {
                $uom = $result['uom'];
                $result['uom'] = [$uom];
                $return[] = $result;

            } else {
                $return[$key]['uom'][] = $result['uom'];

                if (isset($return[$key]['available'])) {
                    $return[$key]['available'] += $result['available'];
                }

                if (isset($return[$key]['cartonCount'])) {
                    $return[$key]['cartonCount'] += $result['cartonCount'];
                }

                if (isset($return[$key]['quantity'])) {
                    $return[$key]['quantity'] += $result['quantity'];
                }

                if (isset($return[$key]['volume'])) {
                    $return[$key]['volume'] += $result['volume'];
                }

                if (isset($return[$key]['weight'])) {
                    $return[$key]['weight'] += $result['weight'];
                }
            }
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function markAffectedOrders($affectedOrders)
    {
        if (! $affectedOrders) {
            return;
        }

        $params = array_keys($affectedOrders);

        $qMarks = $this->app->getQMarkString($params);

        $sql = 'UPDATE neworder
                SET    reprintPickTicket = 1
                WHERE  scanordernumber IN (' . $qMarks . ')';

        $this->app->runQuery($sql, $params);
    }

    /*
    ****************************************************************************
    */

    private function retrieveNeworder($params)
    {
        $clauses = array_fill(0, count($params), 'scanordernumber = ?');
        $condition = implode(' OR ', $clauses);

        $sql = 'SELECT    scanOrderNumber,
                          id
                FROM      neworder
                WHERE     ' . $condition;

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    private function getNewOrderByID($params)
    {
        $clauses = array_fill(0, count($params), 'id = ?');
        $condition = implode(' OR ', $clauses);
        $sql = 'SELECT    id,
                          scanOrderNumber
                FROM      neworder
                WHERE     ' . $condition;

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getOrderIDByLicensePlate($data)
    {
        $qMarks = $this->app->getQMarkString($data);

        $sql = 'SELECT    orderID AS orderID
                FROM      inventory_cartons
                WHERE     plate IN (' . $qMarks . ')
                AND       orderID IS NOT NULL
                GROUP BY  orderID';

        $results = $this->app->queryResults($sql, $data);

        $arrayData = array_keys($results);

        $isValidOrder = count($arrayData) == 1 ? TRUE : FALSE;

        return $isValidOrder ? $arrayData[0] : FALSE;
    }

    /*
    ****************************************************************************
    */

    function getOrderInfoResults($data)
    {
        $vendorID = getDefault($data['vendorID']);
        $scanOrderNumber = getDefault($data['scanordernumber']);
        $createdOrderDay = getDefault($data['createdOrderDay']);
        $orderLists = getDefault($data['orderLists']);
        $clause = 'AND v.id = ? ';
        $params = [$vendorID];
        if ($createdOrderDay) {
            $clause .= $createdOrderDay > 1 ?
                'AND DATE(dateentered) >= DATE_ADD(NOW(), INTERVAL -' .
                $createdOrderDay . ' DAY)' :
                'AND DATE(dateentered) >= DATE(NOW())';
        }
        if($scanOrderNumber) {
            $clause .= $scanOrderNumber ? ' AND n.scanordernumber IN (?)' : NULL;
            $params = array_merge($params, [$scanOrderNumber]);
        }
        else if ($orderLists) {
            $qMarks = $this->app->getQMarkString($orderLists);
            $clause = 'AND n.scanordernumber IN (' . $qMarks . ')';
            $params = $orderLists;
        }
        $sql = 'SELECT    orderID,
                          scanordernumber AS ordernumber,
                          customerordernumber,
                          clientpickticket,
                          deptid,
                          additionalshipperinformation,
                          clientordernumber,
                          SUM(cartonCount) AS pkgs,
                          SUM(cartonUom) AS cartonUnit,
                          SUM(weight) AS weight,
                          COUNT(DISTINCT ca.plate) AS countPlates
                FROM (
                    SELECT    n.id AS orderID,
                              n.scanordernumber,
                              n.customerOrderNumber,
                              n.clientordernumber,
                              n.clientpickticket,
                              n.deptid,
                              n.additionalshipperinformation,
                              upcId,
                              COUNT(ca.id) AS cartonCount,
                              SUM(ca.uom) AS cartonUom,
                              ROUND(COUNT(ca.id) * b.weight, 2) AS weight,
                              ca.plate
                    FROM      inventory_cartons ca
                    JOIN      inventory_batches b ON b.id = ca.batchId
                    JOIN      inventory_containers co ON co.recNum = b.recNum
                    JOIN      neworder n ON n.id = ca.orderID
                    LEFT JOIN shipping_orders so ON so.orderID = n.id
                    JOIN      vendors v ON v.id = co.vendorID
                    JOIN      statuses s ON s.id = n.statusID
                    WHERE     s.shortName = "OPCO"
                    AND       (so.id IS NULL
                        OR NOT so.active
                    )         ' . $clause . '
                    GROUP BY  n.scanordernumber, upcId
                ) ca
                GROUP BY  scanordernumber';
        $result = $this->app->queryResults($sql, $params);

        return $result ? $result : FALSE;
    }

    /*
    ****************************************************************************
    */

    function getAutoCompleteOrderNumber($data)
    {
        $term = $data['term'];

        if (! $term) {
            return FALSE;
        }

        $results = [];
        $orderNumbers = [];

        $sql = 'SELECT    scanordernumber
                FROM      neworder n
                JOIN      statuses s ON s.id = n.statusID
                WHERE     s.shortName = "OPCO"
                          AND scanordernumber LIKE ?
                GROUP BY  n.id
                LIMIT     10';
        $result = $this->app->queryResults($sql, ['%' . $term . '%']);

        if ($result) {
            $orderNumbers[] = array_keys($result);
        }

        if (! $orderNumbers) {
            return $results;
        }

        foreach ($orderNumbers as $keys => $row) {
            foreach ($row as $key => $value) {
                $results[] = ['value' => $value];
            }
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkStagingLocation($app, $orderNumbers)
    {
        $errors = [];

        $locations = new \tables\locations($app);

        $locationData = $locations->getTypeLocationsByOrderNumber($orderNumbers);

        foreach ($orderNumbers as $orderNumber) {
            if (isset($locationData[$orderNumber])) {
                continue;
            }

            $errors[] = 'Staging location for Order Number: ' . $orderNumber
                    . ' is not found ';
        }

        return $errors;
    }

    /*
    ****************************************************************************
    */

    function orderClientNotes($data)
    {
        //get the post data
        $orderNum = $data['orderNum'];
        $comment = $data['notes'];

        //select orderID from neworder table
        $sql = 'SELECT  id FROM neworder
                WHERE   scanordernumber = ?';

        $orderResult = $this->app->queryResult($sql, [$orderNum]);

        $orderID = $orderResult['id'];

        //select the container from order_notes table
        $sql = 'SELECT  * FROM order_notes
                WHERE   orderID = ?';

        $notesResults = $this->app->queryResults($sql, [$orderID]);

        if ($notesResults) {
            //Update client notes to the order_notes table
            $sql = 'UPDATE  order_notes
                    SET    clientNotes = ?
                    WHERE  orderID = ?';

             $this->app->runQuery($sql, [$comment, $orderID]);
        } else {
            //Insert into order_notes table
            $sql = 'INSERT INTO order_notes (
                    orderID,
                    clientNotes
                    ) VALUES (?, ?)';
             $this->app->runQuery($sql, [$orderID, $comment]);
        }


        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function getOrderData($scanOrderNumbers)
    {
        if (! $scanOrderNumbers) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($scanOrderNumbers);

        $userDB = $this->app->getDBName('users');

        $sql = 'SELECT    scanOrderNumber,
                          scanOrderNumber,
                          vendorID,
                          CONCAT(wa.shortName, "_", vendorName) AS vendor,
                          u.userName,
                          companyName AS location,
                          startShipDate AS shipDate
                FROM      neworder n
                JOIN      order_batches b ON b.id = n.order_batch
                JOIN      ' . $userDB . '.info u ON u.id = n.userID
                JOIN      vendors v ON v.id = b.vendorID
                JOIN      warehouses wa ON v.warehouseID = wa.id
                JOIN      company_address a ON a.id = n.location
                WHERE     scanOrderNumber IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $scanOrderNumbers);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getCustomerOrderNum($scanOrderNumber)
    {
        $sql = 'SELECT    customerordernumber
                FROM      neworder n
                WHERE     scanordernumber = ?';

        $result = $this->app->queryResult($sql, $scanOrderNumber);

        return $result['customerordernumber'];
    }

    /*
    ****************************************************************************
    */

    function getPickingData($orderNumbers)
    {
        $vendors = new vendors($this->app);

        $params = $returnProducts = [];

        $products = $this->getReservedCartonsForPicking($orderNumbers);
        $orderVendors = $vendors->getByScanOrderNumber($orderNumbers);

        foreach ($products as $values) {

            $orderNumber = $values['scanOrderNumber'];
            $warehouseType = $values['isMezzanine'] ? 'mezzanine' : 'regular';

            if (! isset($params[$orderNumber])) {
                $params[$orderNumber] = [
                    'vendorID' => $orderVendors[$orderNumber],
                ];
            }

            $returnProducts[$orderNumber][$warehouseType][] = $values;

            $params[$orderNumber]['data'][$warehouseType]['upcIDs'][] =
                    $values['upcID'];
        }

        return [
            'pickingLocations' => $this->getProductLocationsData($params),
            'products' => $returnProducts,
            'orderVendors' => $orderVendors,
        ];
    }

    /*
    ****************************************************************************
    */

    function getReservedCartonsForPicking($orders)
    {
        $qMarks = $this->app->getQMarkString($orders);

        $sql = 'SELECT    pc.id,
                          n.scanOrderNumber,
                          SUM(uom) AS quantity,
                          upcID,
                          sku,
                          color,
                          size,
                          upc,
                          displayName AS cartonLocation,
                          isMezzanine
                FROM      pick_cartons pc
                JOIN      neworder n ON n.id = pc.orderID
                JOIN      inventory_cartons ca ON ca.id = pc.cartonID
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      locations l ON l.id = ca.locID
                JOIN      upcs u ON u.id = b.upcID
                WHERE     scanOrderNumber IN (' . $qMarks . ')
                AND       pc.active
                AND       u.active
                AND       NOT isSplit
                AND       NOT unSplit
                GROUP BY  scanOrderNumber,
                          isMezzanine,
                          upc,
                          displayName
                ORDER BY  scanOrderNumber ASC,
                          isMezzanine ASC,
                          displayName ASC,
                          upc ASC';

        $param = array_values($orders);

        $results = $this->app->queryResults($sql, $param);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getProductLocationsData($data)
    {
        $sqls = $params = [];

        if (! $data) {
            return [];
        }

        foreach ($data as $orderNumber => $orderProducts) {
            foreach ($orderProducts['data'] as $warehouseType => $values) {

                $query = $this->getProductLocationsDataQuery([
                    'orderNumber' => $orderNumber,
                    'vendorID' => $orderProducts['vendorID'],
                    'upcIDs' => $values['upcIDs'],
                    'warehouseType' => $warehouseType,
                ]);

                $sqls[] = $query['sql'];

                $params = array_merge($params, $query['params']);
            }
        }

        $return = $this->runProductLocationsDataQuery($sqls, $params);

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getProductLocationsDataQuery($data)
    {
        $orderNumber = $data['orderNumber'];
        $vendorID = $data['vendorID'];
        $upcIDs = $data['upcIDs'];
        $warehouseType = $data['warehouseType'];

        $buildParams = [
            'orderNumber' => $orderNumber,
            'vendorID' => $vendorID,
            'upcIDs' => $upcIDs,
            'selectFields' => '
                id,
                orderNumber,
                upcID,
                cartonLocation,
                isMezzanine',
            'unitSelectFields' => '
                CONCAT_WS("-", ca.id, "'. $orderNumber . '") AS id,
                "' . $orderNumber . '" AS orderNumber,
                upcID,
                l.displayName AS cartonLocation,
                isMezzanine',
            'groupBy' => '
                orderNumber,
                upcID,
                cartonLocation,
                isMezzanine',
            'mezzanineClause' => $warehouseType == 'mezzanine' ?
                'isMezzanine' : 'NOT isMezzanine',
        ];

        $query = $this->buildQueryUPCDescription($buildParams);

        return $query;
    }

    /*
    ****************************************************************************
    */

    function runProductLocationsDataQuery($sqls, $params)
    {
        $return = [];

        $results = $this->app->queryResults(implode(' UNION ', $sqls), $params);

        foreach ($results as $values) {

            $orderNumber = $values['orderNumber'];
            $upcID = $values['upcID'];
            $location = $values['cartonLocation'];
            $warehouseType = $values['isMezzanine'] ? 'mezzanine' : 'regular';

            $return[$orderNumber][$warehouseType][$upcID][$location] = TRUE;
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function duplicateCheck($target, $orderNumbers)
    {
        if (! $orderNumbers) {
            return [];
        }

        $groupBy = $target;

        $lower = strtolower($target);

        $groupBy .= $lower == 'clientordernumber' ? ', ob.vendorID' : '';

        $qMarks = $this->app->getQMarkString($orderNumbers);

        $sql = 'SELECT    ' . $target . '
                FROM      neworder o
                JOIN      order_batches ob ON ob.id = o.order_batch
                WHERE     ' . $target . ' IN (' . $qMarks . ')
                GROUP BY  ' . $groupBy . '
                HAVING COUNT(' . $target . ') > 1';

        $results = $this->app->queryResults($sql, $orderNumbers);

        return array_keys($results);
    }

    /*
    ****************************************************************************
    */

    function checkDuplicateClientOrderNumber($orderNumbers)
    {
        if (! $orderNumbers) {
            return [];
        }

        $statuses = new \tables\statuses\orders($this->app);

        $wmciStatusID = $statuses->getStatusID(self::STATUS_ENTRY_CHECK_IN);

        $qMarks = $this->app->getQMarkString($orderNumbers);

        $sql = 'SELECT    scanordernumber,
                          clientordernumber,
                          customerordernumber,
                          CONCAT(w.shortName,"_",v.vendorName) AS clientName,
                          ob.vendorID
                FROM      neworder o
                JOIN      order_batches ob ON ob.id = o.order_batch
                JOIN	  vendors v ON v.id = ob.vendorID
                JOIN 	  warehouses w ON w.id = v.warehouseID
                WHERE     clientordernumber IN (' . $qMarks . ')
                AND       o.statusID = ?
                GROUP BY  scanordernumber';

        $params = array_merge($orderNumbers, [$wmciStatusID]);

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getOrderIDByClientAndClientOrder($clientID, $clientOrders)
    {
        if (! $clientID || ! $clientOrders) {
            return [];
        }

        $isArray = is_array($clientOrders);

        $params = $isArray ? $clientOrders : [$clientOrders];

        $qMarks = $this->app->getQMarkString($params);

        $sql = 'SELECT    o.ID,
                          o.clientordernumber
                FROM      neworder o
                JOIN      order_batches ob ON ob.id = o.order_batch
                WHERE     clientordernumber IN (' . $qMarks . ')
                AND       ob.vendorID = ?';

        $params[] = $clientID;

        $results = $this->app->queryResults($sql, $params);

        if ($isArray) {
            return array_column($results, 'clientordernumber');
        } else {

            $return = array_keys($results);

            return reset($return);
        }
    }

    /*
    ****************************************************************************
    */

    function getOrderDetails($orders)
    {

        $qMarks = $this->app->getQMarkString($orders);

        $sql = 'SELECT    n.id,
                          vendorName,
                          first_name,
                          last_name,
                          clientordernumber,
                          customerordernumber,
                          scanOrderNumber,
                          scanworkorder,
                          bolNumber,
                          numberofcarton AS carton,
                          numberofpiece AS piece,
                          totalVolume AS volume,
                          NOpallets AS pallets,
                          startshipdate,
                          s.shortName AS status,
                          edi,
                          isPrintUccEdi
                FROM      neworder n
                JOIN      order_batches b ON b.id = n.order_batch
                JOIN      vendors v ON v.id = b.vendorID
                JOIN      statuses s ON s.id = n.statusID
                WHERE     scanordernumber IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $orders);

        return $results;
    }


    /*
    ****************************************************************************
    */

    function getClientOrderNums($scanOrderNumber)
    {
        $isArray = is_array($scanOrderNumber);

        $params = $isArray ? $scanOrderNumber : [$scanOrderNumber];

        $sql = 'SELECT    clientordernumber
                FROM      neworder n
                WHERE     scanordernumber IN (' . $this->app->getQMarkString($params) . ')';

        $result = $this->app->queryResults($sql, $params);

        return array_keys($result);
    }

    /*
    ****************************************************************************
    */

    function getCancelOrderNums($scanOrderNumber)
    {
        $isArray = is_array($scanOrderNumber);

        $params = $isArray ? $scanOrderNumber : [$scanOrderNumber];

        $status =  self::STATUS_CANCELED;

        $statuses = new \tables\statuses\orders($this->app);
        $statusIds = $statuses->getOrderStatusID($status);

        $sql = 'SELECT    order_nbr
                FROM      ord_sum_cncl c
                JOIN      neworder n ON n.scanordernumber = c.order_nbr
                JOIN      statuses s ON s.id = n.statusID
                WHERE     s.id IN (' . implode(",", $statusIds) . ')
                AND       order_nbr IN (' . $this->app->getQMarkString($params) . ')';

        $result = $this->app->queryResults($sql, $params);

        return array_keys($result);
    }

    /*
    ****************************************************************************
    */

    static function getProcessedOrdersStatuses($category='original')
    {
        $originalProcessedStatuses = [
            self::STATUS_PROCESSING_CHECK_OUT,
            self::STATUS_SHIPPING_CHECK_IN,
            self::STATUS_BILL_OF_LADING
        ];

        $originalNotProcessedStatuses = [
            self::STATUS_ENTRY_CHECK_IN,
            self::STATUS_ENTRY_CHECK_OUT,
            self::STATUS_PICKING_CHECK_IN,
            self::STATUS_PICKING_CHECK_OUT,
            self::STATUS_PROCESSING_CHECK_IN,
        ];

        switch ($category) {
            case 'printLabel':

                $originalProcessedStatuses[] = self::STATUS_SHIPPED_CHECK_OUT;

                return $originalProcessedStatuses;
            case 'orderSummary':
                return array_merge($originalProcessedStatuses,
                        $originalNotProcessedStatuses
                );
            case 'orderProcessed':
                return array_merge($originalProcessedStatuses, [
                    self::STATUS_SHIPPED_CHECK_OUT,
                    self::STATUS_PICKING_CHECK_OUT,
                    self::STATUS_PROCESSING_CHECK_IN,
                    self::STATUS_CANCELED
                ]);
            case 'orderNotProcessed':
                return $originalNotProcessedStatuses;
            default:
                return $originalProcessedStatuses;
        }
    }

    /*
    ****************************************************************************
    */

    function insertFile()
    {
        $vendorID = reset($this->app->post['vendor']);

        $importData = [];

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

            $results = \importer\importer::checkCellErrors([
                'model' => $this,
                'rowData' => $rowData,
                'rowIndex' => $rowIndex,
            ]);

            $importData = $this->getImportData($results['rowData'], $importData,
                    $rowIndex);
        }
        // $this->checkImportData() function run changes $this->errors property
        $upcCheckResults = $this->checkImportData($importData, $vendorID);

        if ($this->errors) {
            return;
        }

        $batches = new orderBatches($this->app);
        $locations = new locations($this->app);
        $statuses = new statuses\orders($this->app);
        $dealSites = new dealSites($this->app);

        $locInfo = $locations->getLocationfromVendor([$vendorID]);

        $userID = \access::getUserID();
        $location = getDefault($locInfo[$vendorID]['locationID'], NULL);
        $dateEntered = date('Y-m-d');
        $orderStatusID = $statuses->getStatusID(self::STATUS_ENTRY_CHECK_IN);

        $dealSiteID = $dealSites->getWholesaleID();

        $nextBatchID = $this->getNextID('label_batches');
        $nextLabelID = $this->getNextID('neworderlabel');
        $batchNumber = $this->getNextID('order_batches');
        $nextOrderID = $this->getNextID('neworder');

        \common\labelMaker::inserts([
            'model' => $this,
            'userID' => $userID,
            'quantity' => count($importData['neworder']),
            'labelType' => 'order',
            'firstBatchID' => $nextBatchID,
            'makeTransaction' => FALSE,
        ]);

        $this->app->beginTransaction();

        foreach ($importData['neworder'] as $values) {

            $orderNumber = str_pad($userID, 4, '0', STR_PAD_LEFT) . $nextLabelID++;

            $this->importOrders($values, [
                $userID,
                $orderNumber,
                $batchNumber,
                $location,
                $dateEntered,
                $orderStatusID
            ]);

            $clientOrderNumber = $values['clientordernumber'];

            $this->importProducts([
                'productData' => $importData['pick_errors'][$clientOrderNumber],
                'nextOrderID' => $nextOrderID++,
                'upcCheckResults' => $upcCheckResults,
            ]);

            $success[$orderNumber] = TRUE;
        }

        // Create the batch order that has been assigned to these online orders

        $batches->insertDefaultBatch($vendorID, $dealSiteID);

        $this->app->commit();

        return $success;
    }

    /*
    ****************************************************************************
    */

    function checkImportData($importData, $vendorID)
    {
        if (isset($importData['importErrors'])) {
            $this->tableDataMismatchErrors($importData['importErrors']);
        }

        $clientOrderNumbers = array_column($importData['neworder'],
                'clientordernumber');

        $existingClientOrderNumbers = $this->getOrderIDByClientAndClientOrder(
                $vendorID, $clientOrderNumbers
        );

        if ($existingClientOrderNumbers) {
            $this->errors['existingClientOrderNumbers'] =
                    array_flip($existingClientOrderNumbers);
        }

        $upcCheckResults = $this->checkImportedUPCs($importData['upcs']);

        if ($upcCheckResults['errors']) {
            $this->errors['discrepantUPCs'] =
                    array_flip($upcCheckResults['errors']);
        } else {
            $this->checkUPCs([
                'quantities' => $importData['upcs'],
                'vendorID' => $vendorID,
            ]);
        }

        return $upcCheckResults;
    }

    /*
    ****************************************************************************
    */

    function tableDataMismatchErrors($importErrors)
    {
        foreach ($importErrors as $errorType => $errorData) {
            foreach ($errorData as $key => $errorRows) {

                $uniqueRows = array_unique($errorRows);

                $this->errors[$errorType] = array_fill_keys($uniqueRows, [$key]);
            }
        }
    }

    /*
    ****************************************************************************
    */

    function importOrders($values, $neworderValues)
    {
        $neworderFields = '
            userid,
            scanordernumber,
            order_batch,
            location,
            dateentered,
            statusID
            ';

        $insertFields = $neworderFields;

        foreach ($values as $field => $value) {

            $insertFields .= ', ' . $field;

            $neworderValues[] = $value;
        }

        $sql = 'INSERT INTO neworder (' . $insertFields . ') VALUES (
            ' . $this->app->getQMarkString($neworderValues) . ')';

        $this->app->runQuery($sql, $neworderValues);
    }

    /*
    ****************************************************************************
    */

    function importProducts($data)
    {
        $productData = $data['productData'];
        $nextOrderID = $data['nextOrderID'];
        $upcCheckResults = $data['upcCheckResults'];

        $sql = 'INSERT INTO pick_errors (
                orderID,
                uom,
                quantity,
                upcID
            ) VALUES (
                ?, ?, ?, ?
            )';

        foreach ($productData as $upc => $quantities) {
            foreach ($quantities as $uom => $quantity) {
                $this->app->runQuery($sql, [
                    $nextOrderID,
                    $uom,
                    $quantity,
                    $upcCheckResults['upcIDs'][$upc],
                ]);
            }
        }
    }

    /*
    ****************************************************************************
    */

    function getColumnKeys($rowData)
    {
        $fieldKeys = array_flip($rowData);
        $importFields = array_keys($this->importFields);

        foreach ($importFields as $columnName) {

            $importField = $this->importField($columnName);

            $this->importFields[$columnName]['key'] = $fieldKeys[$importField];
        }

        $this->orderNumberKey = $fieldKeys['ordernum'];
        $this->shipToAddress1Key = $fieldKeys['shipadd1'];
        $this->shipToAddress2Key = $fieldKeys['shipadd2'];
        $this->upcKey = $fieldKeys['upc'];
        $this->uomKey = $fieldKeys['casepack'];
    }

    /*
    ****************************************************************************
    */

    function getImportData($rowData, $importData, $rowIndex)
    {
        $orderNumber = $rowData[$this->orderNumberKey];
        $upc = $rowData[$this->upcKey];
        $uom = $rowData[$this->uomKey];

        $importData['upcs'][$upc] = getDefault($importData['upcs'][$upc], []);

        foreach ($this->importFields as $columnData) {

            $table = getDefault($columnData['table']);
            $field = getDefault($columnData['field']);

            if (! $table || ! $field) {
                continue;
            }

            $key = $columnData['key'];

            switch ($key) {
                case $this->shipToAddress1Key:

                    $value = trim($rowData[$this->shipToAddress1Key]) . ' '
                        . trim($rowData[$this->shipToAddress2Key]);

                    break;
                case $this->shipToAddress2Key:
                    continue 2;
                default:

                    $value = $rowData[$key];

                    break;
            }

            switch ($table) {
                case 'upcs':
                case 'pick_errors':

                    $importData = $this->importProductData([
                        'importData' => $importData,
                        'value' => trim($value),
                        'table' => $table,
                        'field' => $field,
                        'orderNumber' => $orderNumber,
                        'upc' => $upc,
                        'uom' => $uom,
                        'rowIndex' => $rowIndex,
                    ]);

                    break;
                default:

                    $importData = $this->setImportValue([
                        'importData' => $importData,
                        'value' => trim($value),
                        'table' => $table,
                        'field' => $field,
                        'type' => 'mismatchOrderData',
                        'typeValue' => $orderNumber,
                        'rowIndex' => $rowIndex,
                    ]);

                    break;
            }
        }

        return $importData;
    }

    /*
    ****************************************************************************
    */

    function importProductData($data)
    {
        $importData = $data['importData'];
        $value = $data['value'];
        $table = $data['table'];
        $field = $data['field'];
        $orderNumber = $data['orderNumber'];
        $upc = $data['upc'];
        $uom = $data['uom'];
        $rowIndex = $data['rowIndex'];

        switch ($field) {
            case 'upc':
            case 'uom':
                break;
            case 'sku':
            case 'color':
            case 'size':

                $importData = $this->setImportValue([
                    'importData' => $importData,
                    'value' => $value,
                    'table' => $table,
                    'field' => $field,
                    'type' => 'mismatchUPCData',
                    'typeValue' => $upc,
                    'rowIndex' => $rowIndex,
                ]);

                break;
            case 'quantity':

                $importData[$table][$orderNumber][$upc][$uom] =
                    getDefault($importData[$table][$orderNumber][$upc][$uom], 0);

                $importData[$table][$orderNumber][$upc][$uom] += $value;

                break;
            default:

                $importData[$table][$orderNumber][$upc][$uom][$field] = $value;

                break;
        }

        return $importData;
    }

    /*
    ****************************************************************************
    */

    function setImportValue($data)
    {
        $importData = $data['importData'];
        $value = $data['value'];
        $table = $data['table'];
        $field = $data['field'];
        $type = $data['type'];
        $typeValue = $data['typeValue'];
        $rowIndex = $data['rowIndex'];

        $insertValue = in_array($field, ['startshipdate', 'canceldate']) ?
                substr($value, 0, 4) . '-' . substr($value, 4, 2) . '-' . substr($value, 6, 2) :
                $value;

        if (isset($importData[$table][$typeValue][$field])) {
            if ($importData[$table][$typeValue][$field] != $insertValue) {
                $importData['importErrors'][$type][$typeValue][$field] = $rowIndex;
            }
        } else {

            $importData[$table][$typeValue][$field] = $insertValue;
        }

        return $importData;
    }

    /*
    ****************************************************************************
    */

    function checkImportedUPCs($importedUpcData)
    {
        $errors = $upcsToAdd = [];

        $upcs = new upcs($this->app);

        $importedUPCs = array_keys($importedUpcData);

        $upcInfo = $upcs->getUPCInfo($importedUPCs);

        foreach ($importedUpcData as $upc => $values) {
            if (! isset($upcInfo[$upc])) {

                $upcsToAdd[$upc] = $values;

                continue;
            }
            // check if UPCs DB values sku/color/size differ from imported ones
            $isValid = $this->checkUpcError($upcInfo[$upc], $values);

            if (! $isValid) {
                $errors[] = $upc;
            }
        }

        // check if sku/color/size DB value UPC differs from the imported UPC
        $differentUCPs = $upcs->getCheckDuplicates($importedUpcData);

        $invlidUPCs = array_merge($errors, $differentUCPs);

        if (! $invlidUPCs) {
            $upcs->handleImportedUPCs($importedUPCs, $upcsToAdd);
        }

        $results = $upcs->getUpcs($importedUPCs);

        $keys = array_keys($results);
        $upcIDs = array_column($results, 'id');

        return [
            'errors' => $errors,
            'upcIDs' => array_combine($keys, $upcIDs),
        ];
    }

    /*
    ****************************************************************************
    */

    function checkUpcError($dbValues, $importedValues)
    {
        foreach (['sku', 'color', 'size'] as $field) {
            if ($dbValues[$field] != $importedValues[$field]) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    public static function printUccsLabel($app, $orderNumber, $isProcessed = FALSE)
    {
        if (! $orderNumber) {
            die('No Order Number Passed');
        }

        $objOrder = self::getOrderInfo($app, $orderNumber);

        if (! $objOrder) {
            die('No Order Found');
        }

        $uccs = explode(',', $objOrder['uccs']);
        $fromEDI = $objOrder['edi'];

        if (! $uccs) {
            die('No Cartons Found');
        }

        if ($objOrder['clientCode'] != vendors::VENDOR_CODE_GO_LIVE_WORK ||
            ($objOrder['clientCode'] == vendors::VENDOR_CODE_GO_LIVE_WORK &&
            !$fromEDI )
        ) {
            return $isProcessed ? 
                \labels\create::processedCartonsLabels($app, $orderNumber) :
                create::pickedCartonsLabels($app, $uccs);
        }

        if ($objOrder['isPrintUccEdi']) {
            die(wavePicks::MESSAGE_ORDER_UCC_FROM_LINGO);
        }

        return create::printUCCLabelEDIFormat($app, $orderNumber);
    }

    /*
    ****************************************************************************
    */

    private static function getOrderInfo($app, $orderNumber)
    {
        $sql = 'SELECT    scanOrderNumber,
                          edi,
                          isPrintUccEdi,
	                      scanOrderNumber,
	                      vendorName,
	                      clientCode,
                          GROUP_CONCAT(
                                CONCAT(
                                    co.vendorID,
                                    b.id,
                                    LPAD(ca.uom, 3, 0),
                                    LPAD(ca.cartonID, 4, 0)
                                )
                            ) AS uccs
                FROM      neworder n
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      vendors v ON v.id = ob.vendorID
                JOIN      pick_cartons pc ON pc.orderID = n.id
                JOIN      inventory_cartons ca ON ca.id = pc.cartonID
                JOIN      inventory_batches b on b.id = ca.batchID
                JOIN      inventory_containers co on co.recNum = b.recNum
                WHERE     scanOrderNumber = ?
                GROUP BY  scanordernumber';

        $results = $app->queryResult($sql, [$orderNumber]);

        return $results;
    }

    /*
    ****************************************************************************
    */
}
