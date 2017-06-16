<?php

namespace tables;

use files\import;
use tables\onlineOrders\mezzanineImportQuery;

class onlineOrders extends _default
{
    const ORDER_NUMBER = 2;

    public $ajaxModel = 'onlineOrders';

    public $primaryKey = 'o.id';

    public $fields = [
        'upsLink' => [
            'ignore' => TRUE,
            'select' => 'o.id',
            'display' => 'Exports',
            'noEdit' => TRUE,
        ],
        'order_batch' => [
            'display' => 'Batch Order',
            'noEdit' => TRUE,
        ],
        'scan_seldat_order_number' => [
            'ignore' => TRUE,
            'updateIgnore' => TRUE,
            'display' => 'Scan Seldat Order Number',
            'noEdit' => TRUE,
        ],
        'reference_id' => [
            'display' => 'Reference ID',
            'required' => TRUE,
            'noEdit' => TRUE,
        ],
        'clientordernumber' => [
            'display' => 'Order ID',
            'required' => TRUE,
            'noEdit' => TRUE,
        ],
        'shipment_id' => [
            'display' => 'Shipment ID',
            'required' => TRUE,
            'noEdit' => TRUE,
        ],
        'shipment_tracking_id' => [
            'display' => 'Shipment Tracking ID',
            'ignore' => TRUE,
        ],
        'shipment_sent_on' => [
            'display' => 'Shipment Sent On',
            'ignore' => TRUE,
            'noEdit' => TRUE,
        ],
        'shipment_cost' => [
            'display' => 'Shipment Cost',
            'ignore' => TRUE,
            'noEdit' => TRUE,
            'isNum' => TRUE,
        ],
        'first_name' => [
            'display' => 'First Name',
            'required' => TRUE,
        ],
        'last_name' => [
            'display' => 'Last Name',
            'required' => TRUE,
        ],
        'shipping_address_street' => [
            'display' => 'Shipping Address Street',
            'required' => TRUE,
        ],
        'shipping_address_street_cont' => [
            'display' => 'Shipping Address Street Cont',
        ],
        'shipping_city' => [
            'display' => 'Shipping City',
            'required' => TRUE,
        ],
        'shipping_state' => [
            'display' => 'Shipping State',
            'required' => TRUE,
            'validation' => 'charLimitCheck',
            'validationArray' => TRUE,
        ],
        'shipping_postal_code' => [
            'display' => 'Shipping Postal Code',
            'lengthLimit' => 10,
        ],
        'shipping_country' => [
            'display' => 'Shipping Country',
            'required' => TRUE,
        ],
        'shipping_country_name' => [
            'display' => 'Shipping Country Name',
        ],
        'product_sku' => [
            'display' => 'Product SKU',
            'required' => TRUE,
        ],
        'upc' => [
            'display' => 'UPC',
            'required' => TRUE,
        ],
        'warehouse_id' => [
            'display' => 'Warehouse ID',
        ],
        'warehouse_name' => [
            'display' => 'Warehouse Name',
        ],
        'product_quantity' => [
            'display' => 'Product Quantity',
            'validation' => 'intval',
            'required' => TRUE,
            'isNum' => TRUE,
            'isPositive' => TRUE,
        ],
        'product_name' => [
            'display' => 'Product Name',
            'required' => TRUE,
        ],
        'product_description' => [
            'display' => 'Product Description',
        ],
        'product_cost' => [
            'display' => 'Product Cost',
            'required' => TRUE,
        ],
        'customer_phone_number' => [
            'display' => 'Customer Phone Number',
        ],
        'order_date' => [
            'display' => 'Order Date',
            'required' => TRUE,
            'searcherDate' => TRUE,
        ],
        'carrier' => [
            'select' => 'n.carrier',
            'display' => 'Carrier',
            'required' => TRUE,
        ],
        'account_number' => [
            'display' => 'Account Number',
        ],
        'seldat_third_party' => [
            'display' => 'Seldat/Third Party',
        ],
        'statusID' => [
            'select' => 's.shortName',
            'display' => 'Status',
            'searcherDD' => 'statuses\\orders',
            'ddField' => 'shortName',
            'hintField' => 'displayName',
            'update' => 'n.statusID',
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'ignore' => TRUE,
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'update' => 'b.vendorID',
        ],
        'dealSiteID' => [
            'select' => 'd.displayName',
            'display' => 'Deal Site',
            'ignore' => TRUE,
            'searcherDD' => 'dealSites',
            'ddField' => 'displayName',
            'update' => 'b.dealSiteID',
        ],
        'noInventory' => [
            'select' => 'st.displayName',
            'display' => 'Error',
            'searcherDD' => 'statuses\\enoughInventory',
            'ddField' => 'displayName',
            'update' => 'n.isError',
            'noEdit' => TRUE,
        ]
    ];

    public $table = 'online_orders o
           JOIN      neworder n ON n.scanordernumber = o.SCAN_SELDAT_ORDER_NUMBER
           LEFT JOIN order_batches b ON b.id = n.order_batch
           LEFT JOIN vendors v ON b.vendorID = v.id
           LEFT JOIN warehouses w ON v.warehouseID = w.id
           LEFT JOIN deal_sites d ON b.dealSiteID = d.id
           LEFT JOIN statuses s ON s.id = n.statusID
           LEFT JOIN statuses st ON st.id = n.isError
           ';

    public $mainField = 'n.clientordernumber';

    public $displaySingle = 'Online Order';

    public $badRows = [];

    public $errorOrders = [];

    public $import = [];

    public $errorDescription = [
        'badRefIDs' => [
            'captionSuffix' => 'Reference IDs are already in the online orders table:',
            'rowSuffix' => '',
        ],
        'badOrderIDs' => [
            'captionSuffix' => 'Order IDs are already in the online orders table:',
            'rowSuffix' => '',
        ],
        'badDate' => [
            'captionSuffix' => 'have invalid date format:',
            'rowSuffix' => 'value must have the following format (year-month-day hh:mm) - example (2015-01-27 09:00):',
        ],
        'noInventory' => [
            'captionSuffix' => 'requested more inventory than we have in the stock:<br>(Orders can be exported to Error Orders file)',
            'descriptionCaption' => 'rows: ',
            'rowSuffix' => 'requests more inventory than available:',
            'caption' => 'upc'
        ],
    ];

    public $errorFile = [
        'notFoundUPC' => [
            'captionSuffix' => 'with not found UPC in LOCATIONS INFO List error UPC below:'
        ],
        'wrongUPCs' => [
            'captionSuffix' => 'with UPCs that belong to another Client(s):'
        ],
        'invalidUPCs' => [
            'captionSuffix' => 'with invalid UPCs'
        ],
        'warehouse' => [
            'captionSuffix' => 'with no more carton in Mezzanine and Inventory. List errors UPC below:'
        ],
        'outStock' => [
            'captionSuffix' => 'with no more carton in Inventory. List error UPC below:'
        ],
        'problemRef' => [
            'captionSuffix' => 'and multiple Order IDs point to each of the Reference ID(s) below:'
        ],
        'problemOrderID' => [
            'captionSuffix' => 'and multiple Reference IDs point to each of the Order ID(s) below:'
        ],
        'badRows' => [
            'captionSuffix' => 'with invalid orders.<br>Please export the list of failed rows, correct them and resubmit them in a new batch.',
        ]
    ];

    public $charLimit = [
        [
            'premise' => [
                'Carrier' => 'UPS',
                'Shipping Country' => 'US',
            ],
            'charLimit' => 2,
        ],
        [
            'premise' => [
                'Carrier' => 'UPS',
                'Shipping Country' => 'USA',
            ],
            'charLimit' => 2,
        ],
    ];

    /*
    ****************************************************************************
    */

    function insert($username, $quantity)
    {
        $sql = 'INSERT INTO neworderlabel (
                    userID
                )
                SELECT  id
                FROM    users_access
                WHERE   username = ?';

        for ($i = 0; $i<$quantity; $i++) {
            $this->app->runQuery($sql, [$username]);
        }
        
        return $quantity;
    }

    /*
    ****************************************************************************
    */

    function getInfo(&$orderLabel)
    {
        $sql = 'SELECT  ' . $this->getSelectFields() . '
                FROM    ' . $this->table . '
                WHERE   ' . $this->fields['barcode']['select'] . ' = ?';

        $orderLabel = $this->app->queryResult($sql, [$orderLabel]);
    }


    /*
    ****************************************************************************
    */

    function addToPDF($orderLable, $date)
    {
        $imagePath = makeLink('barcodes', 'display', ['text', $orderLable, 'noText']);
        $orderLable = [$orderLable];
        ob_start(); ?>
        <html>
        <head>
        <style>
            table {
            width: 100%;
                border-collapse: collapse;
            }
            tr {

            }
            td {
                width: 38mm;
                height: 21.2mm;
                margin: 0 1mm;
                text-align: center;
                vertical-align:middle;
            }
            img {
                width: 135px;
                height: 40px;
            }
        </style>
        </head>
        <body>
        <table border="1" >
            <?php
            foreach ($orderLable as $oneLabel) { ?>
                <tr><?php
                for ($x=0; $x<=2; $x++) { ?>
                    <td align="center" height="105">
                        <font size="4"> New Order <BR><?php echo $oneLabel; ?>
                        <?php echo $date; ?><BR></font>
                        <img src="<?php echo $imagePath; ?>">
                    </td>
                    <?php
                } ?>
                </tr><?php
            } ?>
        </table>
        </body>
        </html>
        <?php die;
        $this->app->pdf->html = ob_get_clean();
        $this->app->pdf->writePDFPage()->writePDFPage()
                       ->writePDFPage()->writePDFPage();

        return $this;
    }

    /*
    ****************************************************************************
    */

    function listOrderTableTemplate()
    {
        $sql = 'SELECT  fieldName,
                        description,
                        mandatory
                FROM    order_description';

        $descriptions = $this->app->queryResults($sql);

        $fieldKeys = $data = [];

        $col = 0;

        foreach ($descriptions as $key => $values) {

            $fieldKeys[] = [
              'title' => $key,
            ];

            $data[0][$col] = $values['description'];
            $data[1][$col] = $values['mandatory'] ? 'Required' : 'Optional';

            for ($row = 2; $row < 6; $row++) {
                $data[$row][$col] = NULL;
            }

            switch (strtolower($key)) {
                case 'reference id':

                    $data[2][$col] = 'The next row is an example one:';
                    $data[3][$col] = '123-456';

                    break;
                case 'shipping state':

                    $data[3][$col] = 'Use 2 characters abbreviation';
                    $data[4][$col] = 'NJ';
                    $data[5][$col] = 'TX';

                    break;
                case 'shipping postal code':

                    $data[3][$col] = 'Up to 10 characters';
                    $data[4][$col] = '08807 ';
                    $data[5][$col] = '08765-1234';

                    break;
                case 'order date':

                    $data[3][$col] = '2015-08-17 09:00';
                    $data[4][$col] = '2012-02-29 23:07';

                    break;
                default:
                    break;
            }

            $col++;
        }

        \excel\exporter::ArrayToExcel([
            'data' => $data,
            'fileName' => 'online_orders_export_template',
            'fieldKeys' => $fieldKeys,
        ]);
    }

    /*
    ****************************************************************************
    */

    function listOrderTable($onlineOrders, $export=FALSE)
    {
        unset($this->fields['upsLink']);
        ?>
        <table id="onlineOrders" class="display" cellspacing="0" width="100%">
            <thead>
                <tr>
                <?php foreach ($this->fields as $field) { ?>
                    <th><?php echo $field['display']; ?></th>
                <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($export) {
                    foreach ($onlineOrders as $rowID => $row) { ?>
                        <tr>
                        <?php foreach ($row as $cellID => $cell) {
                            if ($cellID != 0) {?>
                                <td><?php echo $cell; ?></td>
                            <?php } ?>
                        <?php } ?>
                        </tr><?php
                    }
                }?>
            </tbody>
        </table><?php
    }

    /*
    ****************************************************************************
    */

    function getOnlineOrders($orderID=FALSE, $assoc=FALSE)
    {
        $params = $orderID ? [$orderID] : [];
        $clause = $orderID ? 'o.id = ?' : 1;
        $selectField =$this->getFieldValues($this->fields);

        $sql = 'SELECT   ' . $selectField . '
                FROM     ' . $this->table . '
                WHERE    ' . $clause;

        $result = $assoc ? $this->app->queryResults($sql, $params) :
            $this->app->ajaxQueryResults($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getExportOrdersInfo($orderIDs)
    {
        $qMarkString = $this->app->getQMarkString($orderIDs);

        $sql = 'SELECT    o.id,
                          n.id AS orderID,
                          scanOrderNumber,
                          clientOrderNumber,
                          c.companyName AS from_company,
                          c.state AS from_state,
                          c.city AS from_city,
                          c.address AS from_address,
                          c.zip AS from_postal,
                          c.country AS from_country,
                          c.phone AS from_phone,
                          first_name AS to_company,
                          last_name AS to_name,
                          shipping_address_street,
                          shipping_address_street_cont,
                          shipping_city,
                          shipping_state,
                          shipping_postal_code,
                          shipping_country,
                          sg.id AS signatureID,
                          pr.id AS providerID,
                          pc.id AS packageID,
                          sr.id AS serviceID,
                          bl.id AS billToID,
                          u.id AS upcID,
                          u.upc,
                          vendorID,
                          product_quantity AS quantity
                FROM      online_orders o
                JOIN      neworder n ON n.scanordernumber = o.SCAN_SELDAT_ORDER_NUMBER
                JOIN      order_batches ba ON ba.id = n.order_batch
                JOIN      vendors v ON v.id = ba.vendorID
                JOIN      warehouses w ON w.id = v.warehouseID
                JOIN      company_address c ON c.id = w.locationID
                JOIN      online_orders_exports_signatures sg
                JOIN      online_orders_exports_providers pr
                JOIN      online_orders_exports_packages pc
                JOIN      online_orders_exports_services sr
                JOIN      online_orders_exports_bill_to bl
                JOIN      upcs u ON u.upc = o.upc
                WHERE     o.id IN (' . $qMarkString . ')
                AND       sg.displayName = "None Required"
                AND       sg.active
                AND       pr.displayName = "UPS"
                AND       pr.active
                AND       pc.displayName = "Your Packaging"
                AND       pc.providerID = pr.id
                AND       pc.active
                AND       sr.displayName = "UPS Ground"
                AND       sr.providerID = pr.id
                AND       sr.active
                AND       bl.displayName = "Sender"
                AND       bl.active
                -- order by customers and delivery properties since there can be
                -- multiple orders per one customer in one batch
                ORDER BY  first_name,
                          last_name,
                          shipping_address_street,
                          shipping_address_street_cont,
                          shipping_city,
                          shipping_state,
                          shipping_postal_code,
                          shipping_country
                ';

        $result = $this->app->queryResults($sql, $orderIDs);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function customDTInfo($data)
    {
        $sql = 'SELECT   SCAN_SELDAT_ORDER_NUMBER
                FROM     online_orders
                WHERE    SCAN_SELDAT_ORDER_NUMBER = ?
                GROUP BY reference_id
                HAVING   count(id) > 1';

        $multiRows = [];
        foreach ($data as $rowID => $row) {
            $orderNumber = $row[onlineOrders::ORDER_NUMBER];
            $result = $this->app->queryResult($sql, [$orderNumber]);
            $multiRows[$rowID] = $result ? TRUE : FALSE;
        }

        return $multiRows;
    }

    /*
    ****************************************************************************
    */

    function getReferenceIDs($values)
    {
        $qMarks = $this->app->getQMarkString($values);

        $sql = 'SELECT    LOWER(reference_id),
                          id
                FROM      online_orders
                WHERE     reference_id IN (' . $qMarks . ')
                GROUP BY  reference_id';

        $results = $this->app->queryResults($sql, $values);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkOrderTrackingID($order, $trackingID)
    {
        $params = [$order];

        $clause = NULL;

        if ($trackingID) {

            $clause = 'AND       shipment_tracking_id = ?';

            $params[] = $trackingID;
        }

        $sql = 'SELECT    upc,
                          SUM(product_quantity) AS product_quantity
                FROM      online_orders
                WHERE     scan_seldat_order_number = ?
                ' . $clause . '
                GROUP BY  upc';

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getCartonByUPC($orderNumber, $upc, $usedInvIDs)
    {
        $clauses = $this->getClauses();

        $select = 'SELECT    ca.id
                   FROM      ' . $clauses['from'];

        $clause = $clauses['where'] . '
            AND       scanOrderNumber = ?';

        if ($usedInvIDs) {

            $qMarks = $this->app->getQMarkString($usedInvIDs);

            $clause .=  'AND       ca.id NOT IN (' . $qMarks . ')';
        }

        $params = array_merge([$upc, $orderNumber], $usedInvIDs);

        $reserved = $this->getReservedCartonByUPC($select, $clause, $params);

        $select .= '
            JOIN      inventory_containers co ON co.recNum = b.recNum
            ';

        $result = $reserved ? $reserved :
                $this->getFreeCartonByUPC($select, $clause, $params);

        return $result['id'];
    }

    /*
    ****************************************************************************
    */

    function getCartonsByOrder($orderNumber)
    {
        $errors = [];

        $clauses = $this->getClauses();

        $select = '
            SELECT    ca.id,
                      upc
            FROM      ' . $clauses['from'];

        $requested = $this->getOrderReservedInventory($select, $clauses,
                $orderNumber);

        $available = $this->getFreeInventory($select, $clauses, $requested);

        $availableInventory = $available['available']['inventory'];
        $upcData = $available['available']['upcData'];
        $invIDs = $available['invIDs'];

        foreach ($available['balance'] as $upc => $balance) {
            if (! isset($upcData[$upc])) {

                $errors[] = 'UPC ' . $upc . ' has no Mezzanine cartons';

                continue;
            }

            $balance = $balance - $upcData[$upc];

            if ($balance > 0) {
                $errors[] = 'UPC ' . $upc . ' has shortage of ' . $balance
                        . ' piece(s)';
            }

            if (! $errors) {

                $freeInvIDs = array_keys($availableInventory, $upc);

                $invIDs = array_merge($invIDs, $freeInvIDs);
            }
        }

        return [
            'invIDs' => $invIDs,
            'errors' => $errors,
        ];
    }

    /*
    ****************************************************************************
    */

    function getOrderReservedInventory($select, $clauses, $orderNumber)
    {
        $subqueries = $params = $limits = $invIDs = [];

        $inventory = $this->getOrderRequestedInventory($orderNumber);

        foreach ($inventory as $upc => $values) {
            if ($values['shipped']) {

                unset($inventory[$upc]);

                continue;
            }

            $subqueries[] = $select . '
                JOIN      pick_cartons pc ON pc.cartonID = ca.id
                JOIN      neworder n ON n.id = pc.orderID
                WHERE     ' . $clauses['where'] . '
                AND       scanOrderNumber = ?
                AND       pc.active
                ';

            $params[] = $upc;
            $params[] = $orderNumber;

            $limits[] = $requested[$upc] = $values['requested'];
        }

        $results = $this->app->queryUnionResults([
            'limits' => $limits,
            'subqueries' => $subqueries,
            'mysqlParams' => $params,
            'subqueryCount' => count($limits),
        ]);

        return [
            'requested' => $requested,
            'reserved' => $this->getInventoryTotals($results),
        ];
    }

    /*
    ****************************************************************************
    */

    function getFreeInventory($select, $clauses, $requested)
    {
        $subqueries = $params = $limits = $invIDs = $balances = [];

        $reservedInventory = $requested['reserved']['inventory'];
        $upcData = $requested['reserved']['upcData'];

        foreach ($requested['requested'] as $upc => $requestedQuantity) {

            $reservedQuantity = getDefault($upcData[$upc], 0);

            $reservedInvIDs = array_keys($reservedInventory, $upc);

            $balance = $requestedQuantity - $reservedQuantity;

            $inventoryToAdd = $balance >= 0 ? $reservedInvIDs :
                    array_slice($reservedInvIDs, 0, $requestedQuantity);

            $invIDs = array_merge($invIDs, $inventoryToAdd);

            if ($balance > 0) {

                $qMarks = $this->app->getQMarkString($reservedInvIDs);

                $excludeInventoryClause = $reservedInvIDs ?
                        'AND       ca.id NOT IN (' . $qMarks . ')' : NULL;

                $subqueries[] = $select . '
                    WHERE     ' . $clauses['where'] . '
                    ' . $excludeInventoryClause . '
                    AND       ca.statusID = ca.mStatusID
                    ';

                $params = array_merge($params, [$upc], $reservedInvIDs);

                $limits[] = $balances[$upc] = $balance;
            }
        }

        if ($subqueries) {

            $results = $this->app->queryUnionResults([
                'limits' => $limits,
                'subqueries' => $subqueries,
                'mysqlParams' => $params,
                'subqueryCount' => count($limits),
            ]);

            $available = $this->getInventoryTotals($results);
        } else {
            $available = [
                'inventory' => [],
                'upcData' => [],
            ];
        }

        return [
            'balance' => $balances,
            'available' => $available,
            'invIDs' => $invIDs,
        ];
    }

    /*
    ****************************************************************************
    */

    function getInventoryTotals($inventory)
    {
        $invIDs = array_keys($inventory);

        $invUPCs = array_column($inventory, 'upc');

        $cartonUPCs = array_combine($invIDs, $invUPCs);

        return [
            'inventory' => $cartonUPCs,
            'upcData' => array_count_values($cartonUPCs),
        ];
    }

    /*
    ****************************************************************************
    */

    function getClauses()
    {
        return [
            'from' => 'inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      upcs u ON u.id = b.upcID
                JOIN      statuses s ON s.id = ca.statusID
                JOIN      locations l ON l.id = ca.locID',
            'where' => 'isMezzanine
                AND       s.shortName = "' . inventory\cartons::STATUS_RACKED . '"
                AND       NOT isSplit
                AND       NOT unSplit
                AND       upc = ?',
        ];
    }

    /*
    ****************************************************************************
    */

    function getReservedCartonByUPC($select, $clause, $params)
    {
        $sql =  $select . '
                JOIN      pick_cartons pc ON pc.cartonID = ca.id
                JOIN      neworder n ON n.id = pc.orderID
                WHERE     ' . $clause . '
                AND       pc.active
                ORDER BY  b.id ASC
                LIMIT 1';

        $result = $this->app->queryResult($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getFreeCartonByUPC($select, $clause, $params)
    {
        $sql =  $select . '
                JOIN      order_batches ob ON ob.vendorID = co.vendorID
                JOIN      neworder n ON n.order_batch = ob.id
                WHERE     ' . $clause . '
                AND       ca.statusID = ca.mStatusID
                ORDER BY  b.id ASC
                LIMIT 1';

        $result = $this->app->queryResult($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getBatchOrdersID($batch)
    {
        $sql = 'SELECT    o.id
                FROM      online_orders o
                JOIN      neworder n
                    ON n.scanordernumber = o.SCAN_SELDAT_ORDER_NUMBER
                WHERE     order_batch = ?';

        $results = $this->app->queryResults($sql, [$batch]);

        $batchOrderID = array_keys($results);

        return $batchOrderID;
    }

    /*
    ****************************************************************************
    */

    function createOnlineOrders($nextBatchID, $nextLabelID)
    {
        $userID = 812;

        \common\labelMaker::inserts([
            'model' => $this,
            'userID' => $userID,
            'quantity' => 1,
            'labelType' => 'order',
            'firstBatchID' => $nextBatchID,
            'makeTransaction' => FALSE,
        ]);

        $result = str_pad($userID, 4, '0', STR_PAD_LEFT) . $nextLabelID;

        return $result;
    }

    /*
    ************************************************************************
    */

    function excelImport($importer)
    {
        $this->errors = $this->badRows = $this->errorOrders =
                $this->ordersInRef = $this->refsInOrder = $this->problemRef =
                $this->problemOrderID = $this->inputNames =
                $this->ignoredIndexes = [];

        $this->import = FALSE;

        $vendorID = $this->app->post['vendorID'];
        $dealSiteID = $this->app->post['dealSiteID'];

        if (! $vendorID || ! $dealSiteID) {
            return;
        }

        $importer->uploadPath = \models\directories::getDir('uploads',
                    'onlineOrdersImportsFiles');

        if (! $importer->loadFile()) {
            return $importer->errors['wrongType'] = TRUE;
        }

        $importer->objPHPExcel or die('No file loaded.');

        if ($importer->objPHPExcel->getSheetCount() > 1) {
            $importer->errors['multipleSheets'] = TRUE;
            return FALSE;
        }

        $this->importData = [];

        foreach ($importer->objPHPExcel->getSheet(0)->getRowIterator() as $row) {
            $getRow = $importer->getRow($row);

            $rowData = $getRow['rowData'];
            $rowIndex = $getRow['rowIndex'];

            $this->importData[$rowIndex] = $rowData;
        }

        $lastColumn = 0;

        foreach ($this->importData[1] as $key => $value) {
            if ($value) {
                $lastColumn = $key;
            }
        }

        // truncating array to amount of columns in its 1-st row
        foreach ($this->importData as $key => $value) {
            $this->importData[$key] = array_splice($value, 0,
                $lastColumn + 1);
        }

        $this->insertFile($vendorID, $dealSiteID);

        $importer->import = $this->import;
        $importer->errorOrders = $this->errorOrders;
    }

    /*
    ************************************************************************
    */

    static function storeQuantities(&$quantities, &$upcRows, $params)
    {
        $upc = $params['upc'];
        $order = $params['order'];
        $quantities[$upc]['quantity'] =
            getDefault($quantities[$upc]['quantity'], 0) + $params['quantity'];

        $quantities[$upc]['orders'][$order] = TRUE;

        $upcRows[$upc][] = $params['rowIndex'];
    }

    /*
    ************************************************************************
    */

    function insertFile($vendorID, $dealSiteID)
    {
        $upcRows = $quantities = $errorRows = [];

        $orderBatch = new orderBatches($this->app);

        $isWholeSale = $orderBatch->isWholeSaleDealSite($dealSiteID);

        // Loop through each row of the worksheet in turn
        $lastRefID = $orderNumber = $lastOrderNumber = NULL;

        $orderIDs = [];

        foreach ($this->importData as $rowIndex => $rowData) {

            if ($rowIndex == 1) {

                $this->handleColumnTitles($rowData);

                \excel\importer::checkTableErrors($this);

                if ($this->errors) {
                    return;
                }

                $this->getColumnKeys();

                $queries = $this->getQueries($rowData);

                $wmsOrderSQL = $queries['wmsOrderSQL'];
                $failSQL = $queries['failSQL'];
                $ordersSQL = $queries['ordersSQL'];

                continue;
            }

            // No blank rows
            if (! \array_filter($rowData)) {
                continue;
            }

            $order = $rowData[$this->clientOrderNumberKey];

            $orderIDs[] = [
                'refIDs' => $rowData[$this->refIDKey],
                'clients' => $order,
            ];

            self::storeQuantities($quantities, $upcRows, [
                'upc' => $rowData[$this->upcKey],
                'order' => $order,
                'rowIndex' => $rowIndex,
                'quantity' => $rowData[$this->quantityKey],
            ]);
        }

        // keys in lower case to perform case insensitive isset() for $refID
        $refIDs = array_column($orderIDs, 'refIDs');
        $getRefOrOrderIDs = $this->getReferenceIDs($refIDs);

        $this->refIDs = array_change_key_case($getRefOrOrderIDs);

        foreach ($orderIDs as $row) {
            $this->orderAndRefIntersection($row['refIDs'], $row['clients']);
        }

        $titles = $this->importData[1];

        unset($this->importData[1]);

        $params = [
            'quantities' => $quantities,
            'vendorID' => $vendorID,
            'inventoryCheck' => TRUE,
        ];

        $lackInventory = $quantities ? $this->checkUPCs($params) : [];

        if (isset($this->errors['noInventory'])) {
            $this->errors['noInventory']['description'] = $upcRows;
        }

        // Check if noInventory is the only error
        $errCategory = array_keys($this->errors);
        $onlyNoInventory = $errCategory === ['noInventory'];

        if ($this->errors && ! $onlyNoInventory) {
            return;
        }

        if (! $isWholeSale) {
            $this->checkMinMaxSetting($vendorID, $upcRows);
        }

        $statuses = new \tables\statuses($this->app);
        $locations = new \tables\locations($this->app);

        $enoughInventoryStatus = orders::STATUS_NO_ERROR;
        $orderCheckInStatus = orders::STATUS_ENTRY_CHECK_IN;

        $statuseIDs = $statuses->getStatusIDs([
            $enoughInventoryStatus,
            $orderCheckInStatus
        ]);

        $nonErrorOrderStatusID =
                getDefault($statuseIDs[$enoughInventoryStatus]['id']);
        $orderStatusID = getDefault($statuseIDs[$orderCheckInStatus]['id']);

        $locInfo = $locations->getLocationfromVendor([$vendorID]);

        $location = getDefault($locInfo[$vendorID]['locationID'], NULL);

        $nextBatchID = $this->getNextID('label_batches');
        $nextLabelID = $this->getNextID('neworderlabel');
        $batchNumber = $this->getNextID('order_batches');

        $this->app->beginTransaction();

        foreach ($this->importData as $rowIndex => $rowData) {

            if (! array_filter($rowData)) {
                // skip the 1-st and blank rows
                continue;
            }

            $isImport = 'isImport';
            $refID = $this->checkRefID($rowData, $rowIndex, $isImport);
            $clientOrder = getDefault($rowData[$this->clientOrderNumberKey], NULL);

            $results = \excel\importer::checkCellErrors([
                'model' => $this,
                'rowData' => $rowData,
                'rowIndex' => $rowIndex,
            ]);

            $convertedRow = $results['rowData'];

            if (isset($lackInventory[$clientOrder])) {
                // do not load error orders (LOIN) - they will be downloaded,
                // fixed and imported later in aseparate file
                $errorRows[] = $rowData;

                continue;
            }

            $costsColumnsError = isset($this->costsColumns) ?
                    $this->checkCostsColumns($convertedRow, $rowIndex) : FALSE;

            $this->exceptErrors($results);

            $results['errors'] = $results['errors'] || $costsColumnsError;

            if (! $results['errors'] && ! $this->badRows) {

                // Use last order number if ref id is repeated
                $orderNumber = $lastRefID == $refID ? $lastOrderNumber :
                    $this->createOnlineOrders($nextBatchID++, $nextLabelID++);

                // Insert this order into the WMS order table
                $firstName = $convertedRow[$this->firstNameKey];
                $lastName = $convertedRow[$this->lastNameKey];
                $carrier = $convertedRow[$this->carrierKey];
                $userId = \access::getUserID();
                
                $this->app->runQuery($wmsOrderSQL, [
                    $userId,
                    $clientOrder,
                    $orderNumber,
                    $firstName,
                    $lastName,
                    $batchNumber,
                    $carrier,
                    $location,
                    $orderStatusID,
                    $nonErrorOrderStatusID,
                    $firstName,
                    $lastName,
                ]);

                // Update order number and batch order before insertion
                $convertedRow['scan_seldat_order_number'] = $orderNumber;

                $this->removeExtraFields($convertedRow);

                $queryInput = array_diff_key($convertedRow, $this->ignoredIndexes);

                $this->app->runQuery($ordersSQL, array_values($queryInput));

                $lastOrderNumber = $orderNumber;

                $lastRefID = $refID;

                $this->import = TRUE;

            } else {

                $params = array_diff_key($convertedRow, $this->ignoredIndexes);

                $this->removeCostsFields($params);

                $this->app->runQuery($failSQL, array_values($params));
            }
        }

        $this->app->commit();

        if (! isset($convertedRow)) {
            // the file has proper extension, but actually is not an Excel file
            return $this->errors['wrongType'] = TRUE;
        }

        // Create the batch order that has been assigned to these online orders
        $this->app->batches->create($vendorID, $dealSiteID);

        $this->errorOrders = $errorRows ? [
            'titles' => $titles,
            'data' => $errorRows,
        ] : [];
    }

    /*
    ************************************************************************
    */

    function exceptErrors(&$result)
    {
        $excepts = ['noInventory', 'noMinMaxSetting'];
        $result['errors'];

        foreach ($excepts as $except) {
            if (! isset($result['errors'][$except])) {
                continue;
            }

            unset($result['errors'][$except]);
        }
    }

    /*
    ************************************************************************
    */

    function importOrTransfer($params)
    {
        $query = $params['query'];
        $vendorID = $params['vendorID'];
        $upcRows = $params['upcItems'];

        $params['info']->get('upcsInfo', [$query, 'oneQuery'], [$vendorID, $upcRows]);
        $this->scanForFreeLocation($params['info'], $vendorID);

        $import = new onlineOrders\importToMezzanine($params);

        $params['info']->get('uomsInfo', [
            $this, 'getUomListInWarehouse'
        ], [$upcRows, $vendorID]);

        $results = $import->findBatches();

        $results ? $results['transferTool']->
            splitQuery->storeChildCartonIDs($results['foundBatches']) : NULL;

        $import->process();

        return $results;
    }

    /*
    ************************************************************************
    */

    function checkMinMaxSetting($vendorID, $upcRows)
    {
        $info = new mezzanineImportQuery($this->app);
        $minMax = new minMaxRanges($this->app);
        $upcsInformation = $info->oneQuery($vendorID, $upcRows);

        foreach ($upcsInformation as $upc => $item) {
            if ($item['hasMinMaxSetting']) {
                continue;
            }
            $freeLocation = $minMax->getFreeLocation($vendorID);

            if (! $freeLocation) {
                $this->errors['noMinMaxSetting'][$upc][] = $item;
                $this->errors['noMinMaxSetting']['description'][$upc][] =
                    $upcRows[$upc];
            }
        }
    }

    /*
    ************************************************************************
    */

    function scanForFreeLocation($info, $vendorID)
    {
        $minMax= new minMaxRanges($this->app);

        $minMixInfo = $minMax->getLocationRangeByVendor($vendorID);

        $freeLocation = $minMax->getFreeLocation($vendorID);

        foreach ($info->values['upcsInfo'] as $upc => &$item) {
            if (! $item['hasMinMaxSetting']) {
                $item['min'] = $minMixInfo['minCount'];
                $item['max'] = $minMixInfo['maxCount'];
                $item['minMaxLocID'] = $freeLocation['id'];
                $item['hasMinMaxSetting'] = 1;
            }
        }
    }

    /*
    ************************************************************************
    */

    function handleColumnTitles(&$rowData)
    {
        $this->costsColumns = [
            'shipment_tracking_id' => [
                'key' => '',
                'value' => '',
            ],
            'shipment_sent_on' => [
                'key' => '',
                'value' => '',
            ],
            'shipment_cost' => [
                'key' => '',
                'value' => '',
            ],
        ];

        $costsColumns = [];

        $count = 0;

        foreach ($rowData as $key => &$display) {

            $display = strToLower(trim($display));
            // replace spaces with underscorse for fields
            $display = str_replace([' ', '/'], '_', $display);

            if (isset($this->costsColumns[$display])) {
                // storing submitted costs columns to check whether all
                // of them are submitted
                $this->costsColumns[$display]['key'] = $key;
                $costsColumns[$display] = TRUE;
            }

            $this->indexArrayFill($display, $key, $rowData);

            if ($display == 'order_date') {
                $this->dateKeys[$count] = TRUE;
            }

            $count++;
        }

        // checking whether all costs columns are submitted
        if ($costsColumns && count($costsColumns) != count($this->costsColumns)) {
            $this->errors['missingColumns'] = \array_diff_key(
                    $this->costsColumns,
                    $costsColumns
            );
        }
    }

    /*
    ****************************************************************************
    */

    function checkCostsColumns($rowData, $rowIndex)
    {
        $costs = $errors = FALSE;

        $costsColumns = array_keys($this->costsColumns);

        foreach ($costsColumns as $key) {
            // emptying costs array for the current row
            $this->costsColumns[$key]['value'] = NULL;
        }

        $rowKeys = array_keys($rowData);

        foreach ($rowKeys as $key) {

            $input = $rowData[$key];
            $field = $this->inputNames[$key];

            if ($field != 'shipment_tracking_id'
             && isset($this->costsColumns[$field]) && $input) {

                $costs = TRUE;
                $this->costsColumns[$field]['value'] = $input;
            }
        }

        if (! $costs) {
            // no errors in costs column were found
            return FALSE;
        }

        foreach ($costsColumns as $display) {
            if ($display != 'shipment_tracking_id'
             && ! $this->costsColumns[$display]['value']) {

                $this->errors['missingReqs'][$rowIndex][] = $display;
                $this->badRows[] = $rowData;
                $this->costsColumns[$display]['value'] = '';
                $errors = TRUE;
            }
        }

        return $errors;
    }

    /*
    ****************************************************************************
    */

    function indexArrayFill($display, $key, $rowData)
    {
        switch ($display) {
            case 'shipping_first_name':
                $display = 'first_name';
                break;
            case 'shipping_last_name':
                $display = 'last_name';
                break;
            case 'order_id':
                $display = 'clientordernumber';
                break;
            default:
                break;
        }

        \excel\importer::indexArrayFill([
            'model' => $this,
            'display' => $display,
            'key' => $key,
            'rowData' => $rowData
        ]);
    }

    /*
    ************************************************************************
    */

    function getColumnKeys()
    {
        $fieldKeys = array_flip($this->inputNames);

        $this->refIDKey = $fieldKeys['reference_id'];
        $this->clientOrderNumberKey = $fieldKeys['clientordernumber'];
        $this->firstNameKey = $fieldKeys['first_name'];
        $this->lastNameKey = $fieldKeys['last_name'];
        $this->carrierKey = $fieldKeys['carrier'];
        $this->upcKey = $fieldKeys['upc'];
        $this->quantityKey = $fieldKeys['product_quantity'];
        $this->shippingCountry = $fieldKeys['shipping_country'];

        $this->unsetKeys = [
            $fieldKeys['clientordernumber'],
            $fieldKeys['first_name'],
            $fieldKeys['last_name'],
            $fieldKeys['carrier']
        ];
    }

    /*
    ************************************************************************
    */

    function getQueries($rowData)
    {
        // in an import file 'first_name' column title can be substituted with
        // 'shipping_first_name' and 'last_name' with 'shipping_last_name'
        $swapColumnNames = [
            'shipping_first_name' => 'first_name',
            'shipping_last_name' => 'last_name',
        ];

        $wmsOrderSQL = '
            INSERT INTO neworder (
                userid,
                clientordernumber,
                scanordernumber,
                first_name,
                last_name,
                order_batch,
                carrier,
                location,
                statusID,
                isError
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?
            ) ON DUPLICATE KEY UPDATE
                first_name = ?,
                last_name = ?
            ';

        $failsColumns = $rowData;

        $columnKeys = array_flip($failsColumns);

        foreach ($swapColumnNames as $importName => $fieldName) {
            if (isset($columnKeys[$importName])) {

                $key = $columnKeys[$importName];

                $failsColumns[$key] = $fieldName;
            }
        }

        $this->removeCostsFields($failsColumns);
        $qMarkColumn = $this->app->getQMarkString($failsColumns);

        $failSQL = '
            INSERT INTO online_orders_fails (
                ' . implode(',', $failsColumns) . '
            ) VALUES (
                ' . $qMarkColumn . '
            )';

        $rowData[] = 'scan_seldat_order_number';

        $this->removeExtraFields($rowData);
        $qMarkRow =$this->app->getQMarkString($rowData);

        $ordersSQL = '
            INSERT INTO online_orders (
                ' . implode(',', $rowData) . '
            ) VALUES (
                ' . $qMarkRow . '
            )';

        return [
            'wmsOrderSQL' => $wmsOrderSQL,
            'failSQL' => $failSQL,
            'ordersSQL' => $ordersSQL
        ];
    }

    /*
    ************************************************************************
    */

    function checkRefID($rowData, $rowIndex, $isImport)
    {
        if (! $isImport) {
            return;
        }

        $refIDKey = getDefault($rowData[$this->refIDKey], NULL);

        if (! $refIDKey) {
            $this->badRows[] = $rowData;
        } elseif (isset($this->refIDs)) {

            $refID = strtolower($refIDKey);
            // strtolower - case insensitive isset()
            if (getDefault($this->refIDs[$refID])) {
                // Reference ID is in database
                $this->errors['badRefIDs'][$rowIndex][] = $refIDKey;
                $this->badRows[] = $rowData;
            }
        }

        return $refIDKey;
    }

    /*
    ************************************************************************
    */

    function removeCostsFields(&$rowData)
    {
        $costsKeys = array_column($this->costsColumns, 'key');

        foreach ($costsKeys as $fieldKey) {
            unset($rowData[$fieldKey]);
        }
    }

    /*
    ************************************************************************
    */

    function removeExtraFields(&$rowData)
    {
        foreach ($this->unsetKeys as $key) {
            unset($rowData[$key]);
        }
    }

    /*
    ****************************************************************************
    */

    function orderAndRefIntersection($order, $ref)
    {
        //Check if any Ref corresponds to multiple OrderID
        if (isset($this->ordersInRef[$ref])) {
            if ($this->ordersInRef[$ref] != $order) {
                //more than one order in current ref
                $this->errors['problemRef'][$ref] = TRUE;
            }
        } else {
            $this->ordersInRef[$ref] = $order;
        }
        //Check if any OrderID corresponds to multiple Ref
       if (isset($this->refsInOrder[$order])) {
            if ($this->refsInOrder[$order] != $ref) {
                //more than one ref in current order
                $this->errors['problemOrderID'][$order] = TRUE;
            }
        } else {
            $this->refsInOrder[$order] = $ref;
        }
    }

    /*
    ****************************************************************************
    */

    function checkWavePick($batches)
    {
        if (! $batches) {
            return [];
        }

        $errors = $upcData = $upcParams = $vendorUpcs = $batchVendors = [];

        $pickCartons = new inventory\pickCartons($this->app);
        $cartons = new inventory\cartons($this->app);

        $reservedInventory = $pickCartons->getReservedByBatch($batches);
        $requestedInventory = $this->getRequestedInventory($batches);

        foreach ($requestedInventory as $values) {

            $batch = $values['batch'];
            $vendorID = $values['vendorID'];
            $vendor = $values['vendor'];
            $upc = $values['upc'];
            $upcID = $values['upcID'];

            $vendorUpcs[$vendorID][$upc] = TRUE;

            $requested = $values['supplement'];
            $reserved = $this->getReserved($reservedInventory, $batch, $upc);

            $quantity = $reserved - $requested;

            $errors[$batch]['vendorID'] = $vendorID;
            $errors[$batch]['vendor'] = $vendor;
            $errors[$batch]['cartons'][$upc]['upcID'] = $upcID;
            $errors[$batch]['cartons'][$upc]['shortage'] = abs($quantity);

            if ($quantity < 0) {
                $errors[$batch]['cartons'][$upc]['shortage'] = abs($quantity);
            }
        }

        if (! $errors) {
            return [];
        }

        foreach ($vendorUpcs as $vendorID => $upcKeys) {
            $upcParams[$vendorID] = array_keys($upcKeys);
        }

        // only Mezzanine inventory will be selected
        $isMezzanine = TRUE;

        $inventory = $cartons->getUPCQuantity($upcParams, $isMezzanine);

        foreach ($errors as $batch => $values) {

            $vendorID = $values['vendorID'];

            foreach ($values['cartons'] as $upc => $upcData) {

                $shortage = $upcData['shortage'];

                $available = getDefault($inventory[$vendorID][$upc], 0);

                if ($shortage > $available) {
                    $errors[$batch]['cartons'][$upc]['shortage'] -= $available;
                } else {
                    unset($errors[$batch]['cartons'][$upc]);
                }

                $inventory[$vendorID][$upc] = $available - $shortage;

                if ($inventory[$vendorID][$upc] <= 0) {
                    unset($inventory[$vendorID][$upc]);
                }
            }
        }

        foreach ($errors as $batch => $values) {
            if (! $values['cartons']) {
                unset($errors[$batch]);
            }
        }

        return $errors;
    }

    /*
    ****************************************************************************
    */

    function getReserved($reservedInventory, $batch, $upc)
    {
        foreach ($reservedInventory as $reserved) {
            if ($batch == $reserved['order_batch'] && $upc == $reserved['upc']) {
                return $reserved['uom'];
            }
        }

        return 0;
    }

    /*
    ****************************************************************************
    */

    function getRequestedInventory($batcIDs)
    {
        if (! $batcIDs) {
            return [];
        }

       $qMarks = $this->app->getQMarkString($batcIDs);

       $sql = 'SELECT     o.id,
                          ob.id AS batch,
                          ob.vendorID,
                          CONCAT(w.shortName, "_", vendorName) AS vendor,
                          u.id AS upcID,
                          u.upc,
                          SUM(product_quantity) AS supplement
                FROM      online_orders o
                JOIN      neworder n ON n.scanordernumber = o.SCAN_SELDAT_ORDER_NUMBER
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      deal_sites ds ON ds.id = ob.dealSiteID
                JOIN      upcs u ON u.upc = o.upc
                JOIN      vendors v ON v.id = ob.vendorID
                JOIN      warehouses w ON w.id = v.warehouseID
                WHERE     order_batch IN (' . $qMarks . ')
                AND       ds.displayName != "Wholesale"
                GROUP BY  vendorID,
                          upc';

        $results = $this->app->queryResults($sql, $batcIDs);

        return $results;
    }

    /*
    ****************************************************************************
    */

    public function getUomListInWarehouse($upcs, $vendorID)
    {
        $rackedStatusID = inventory\cartons::getRackedStatusID($this->app);
        
        $selectCartonCount = ' COUNT(ca.id) AS cartonCount';
        $selectAllCartonOfUpc = ' ca.id,
                                 ca.locID,
                                 ca.mLocID,
                                 ca.batchID';
        
        $groupCartonCount = '   GROUP BY    ca.uom,
                                            b.upcID
                                ORDER BY    uom ASC';
        
        $select = '-- getUomListInWarehouse 
                    SELECT  ca.id,
                            upc,
                            ca.uom,';
        
        $from = 'FROM  inventory_cartons ca
                JOIN  inventory_batches b ON ca.batchID = b.id
                JOIN  upcs u ON b.upcID = u.id
                JOIN  inventory_containers co ON co.recNum = b.recNum
                JOIN  locations l ON l.id = ca.locID';
        
        $where = 'WHERE NOT isMezzanine
                AND   ca.statusID = ?
                AND   ca.statusID = ca.mStatusID
                AND   ca.uom
                AND   u.upc IN (' . $this->app->getQMarkString($upcs) . ')
                AND   co.vendorID = ?
                AND   NOT ca.isSplit
                AND   NOT ca.unSplit';
        
        $sqlGetUomList = 
                $select . ' ' . $selectCartonCount . ' '.
                $from . ' ' .
                $where . ' ' .
                $groupCartonCount;
        
        $sqlGetAllCartons = 
                $select . ' ' . $selectAllCartonOfUpc . ' ' .
                $from . ' ' .
                $where;
        
        $params = [$rackedStatusID];
        $params = array_merge($params, array_keys($upcs));
        $params[] = $vendorID;

        $uomList = $this->app->queryResults($sqlGetUomList, $params);

        $allCartonList = $this->app->queryResults($sqlGetAllCartons, $params);
        
        $upcUOMs = [];
        
        foreach ($uomList as $row) {
            $upc = $row['upc'];
            $uom = $row['uom'];

            $upcUOMs[$upc][$uom]['cartonCount'] = $row['cartonCount'];
        }
        
        foreach ($allCartonList as $carton) {
            $upcUOMs[$carton['upc']][$carton['uom']]['cartons'][] = $carton;
        }

        return $upcUOMs;
    }

    /*
    ****************************************************************************
    */

    public function charLimitCheck($data)
    {
        foreach ($this->charLimit as $limits) {

            $carrier = $data['rowData'][$this->carrierKey];
            $shippingCountry = $data['rowData'][$this->shippingCountry];

            $charLimit = $limits['charLimit'];

            if ($limits['premise']['Carrier'] == $carrier
             && $limits['premise']['Shipping Country'] == $shippingCountry
             && strlen($data['input']) > $charLimit ) {

                return ' length exceeds ' . $charLimit . ' characters';
            }
        }

        return FALSE;
    }

    /*
    ****************************************************************************
    */

    function getOrderRequestedInventory($orderNumber, $useTracking=FALSE)
    {
        if (! $orderNumber) {
            return [];
        }

        $fields = ! $useTracking ? 'upc' : '
            CONCAT_WS("-", shipment_tracking_id, upc),
            shipment_tracking_id AS trackingID
            ';

        $groupBy = ! $useTracking ? 'upc' : '
            shipment_tracking_id,
            upc
            ';

        $sql = 'SELECT    ' . $fields . ',
                          upc,
                          SUM(product_quantity) AS requested,
                          0 AS shipped
                FROM      online_orders oo
                WHERE     SCAN_SELDAT_ORDER_NUMBER = ?
                GROUP BY  ' . $groupBy;

        $results = $this->app->queryResults($sql, [$orderNumber]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function processInventory($data)
    {
        $orderNumber = $data['orderNumber'];
        $trackingID = getDefault($data['trackingID']);
        $invIDs = $data['invIDs'];
        $orders = $data['classes']['orders'];
        $cartons = $data['classes']['cartons'];
        $cartonStatuses = $data['classes']['cartonStatuses'];
        $orderStatuses = $data['classes']['orderStatuses'];

        $statusID = $cartonStatuses->getStatusID(inventory\cartons::STATUS_SHIPPED);

        \common\order::getIDs($this->app, $orderNumber);

        \common\order::updateAndLogStatus([
            'statusID' => $orderStatuses->getStatusID(orders::STATUS_SHIPPED_CHECK_OUT),
            'tableClass' => $orders,
        ]);

        $orderID = $orders->getIDByOrderNumber($orderNumber);

        $params = array_merge([$orderID, $statusID, $statusID], $invIDs);

        $discrepantCartonData = $cartons->getDiscrepantCartonData($orderNumber,
                $invIDs);

        $results = $cartons->getOldCartonInfo($invIDs, 'statusID, mStatusID');

        $sql = 'UPDATE    inventory_cartons
                SET       orderID = ?,
                          statusID = ?,
                          mStatusID = ?
                WHERE     id IN (' . $this->app->getQMarkString($invIDs) . ')
                ';

        $target = $trackingID ? $trackingID : $orderNumber;
        $field = $trackingID ? 'shipment_tracking_id' : 'SCAN_SELDAT_ORDER_NUMBER';

        \common\logger::getFieldIDs('cartons', $this->app);

        \common\logger::getLogID();

        $this->app->beginTransaction();

        foreach ($results as $invID => $cartonData) {
            \common\logger::edit([
                'db' => $this->app,
                'primeKeys' => $invID,
                'fields' => [
                    'statusID' => [
                        'fromValues' => $cartonData['statusID'],
                        'toValues' => $statusID,
                    ],
                    'mStatusID' => [
                        'fromValues' => $cartonData['mStatusID'],
                        'toValues' => $statusID,
                    ],
                ],
                'transaction' => FALSE,
            ]);
        }

        $this->app->runQuery($sql, $params);

        $cartons->updateStatus([
            'target' => $target,
            'field' => $field,
            'status' => 'Shipped',
            'table' => 'online_orders',
            'transaction' => FALSE,
        ]);

        $cartons->resolveDiscrepancy($discrepantCartonData);

        $this->app->commit();

        return $orderID;
    }

    /*
    ****************************************************************************
    */
}
