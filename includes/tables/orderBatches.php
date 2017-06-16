<?php

namespace tables;

class orderBatches extends _default
{
    public $ajaxModel = 'orderBatches';

    public $primaryKey = 'o.id';

    public $fields = [
        'batchOrder' => [
            'select' => 'ob.id',
            'display' => 'Batch Order',
        ],
        'scan_seldat_order_number' => [
            'display' => 'Scan Seldat Order Number',
        ],
        'reference_id' => [
            'display' => 'Reference ID',
        ],
        'clientordernumber' => [
            'display' => 'Order ID',
        ],
        'shipment_id' => [
            'display' => 'Shipment ID',
        ],
        'shipment_tracking_id' => [
            'display' => 'Shipment Tracking ID',
        ],
        'shipment_sent_on' => [
            'display' => 'Shipment Sent On',
        ],
        'shipment_cost' => [
            'display' => 'Shipment Cost',
        ],
        'shippingName' => [
            'select' => 'CONCAT_WS(
                            " ",
                            n.first_name,
                            n.last_name
                        )',
            'display' => 'Shipping Name',
        ],
        'shipping_address_street' => [
            'select' => 'CONCAT_WS(" ",
                            o.shipping_address_street,
                            o.shipping_address_street_cont
                        )',
            'display' => 'Shipping Address Street',
        ],
        'shipping_city' => [
            'display' => 'Shipping City',
        ],
        'shipping_state' => [
            'display' => 'Shipping State',
        ],
        'shipping_postal_code' => [
            'display' => 'Shipping Postal Code',
        ],
        'shipping_country' => [
            'display' => 'Shipping Country',
        ],
        'shipping_country_name' => [
            'display' => 'Shipping Country Name',
        ],
        'product_sku' => [
            'display' => 'Product SKU',
        ],
        'upc' => [
            'display' => 'UPC',
        ],
        'warehouse_id' => [
            'display' => 'Warehouse ID',
        ],
        'warehouse_name' => [
            'display' => 'Warehouse Name',
        ],
        'product_quantity' => [
            'display' => 'Product Quantity',
        ],
        'product_name' => [
            'display' => 'Product Name',
        ],
        'product_description' => [
            'display' => 'Product Description',
        ],
        'product_cost' => [
            'display' => 'Product Cost',
        ],
        'customer_phone_number' => [
            'display' => 'Customer Phone Number',
        ],
        'order_date' => [
            'display' => 'Order Date',
        ],
        'carrier' => [
            'select' => 'n.carrier',
            'display' => 'Carrier',
        ],
        'account_number' => [
            'display' => 'Account Number',
        ],
        'seldat_third_party' => [
            'display' => 'Seldat/Third Party',
        ],
        'status' => [
            'select' => 's.displayName',
            'display' => 'Status',
            'searcherDD' => 'statuses\\invoice',
            'ddField' => 'displayName',
            'update' => 'o.status',
        ],
        'vendorID' => [
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'update' => 'ob.vendorID',
        ],
        'dealSiteID' => [
            'select' => 'd.displayName',
            'display' => 'Deal Site',
            'searcherDD' => 'dealSites',
            'ddField' => 'displayName',
            'update' => 'ob.dealSiteID',
        ],
    ];

    public $table = 'neworder n
        JOIN      online_orders o ON o.SCAN_SELDAT_ORDER_NUMBER = n.scanordernumber
        JOIN      order_batches ob ON ob.id = n.order_batch
        JOIN      vendors v ON v.id = ob.vendorID
        JOIN      warehouses w ON w.id = v.warehouseID
        JOIN      deal_sites d ON d.id = ob.dealSiteID
        LEFT JOIN statuses s ON s.id = n.isError
        JOIN      statuses os ON os.id = n.statusID
        ';

    public $where = '(n.isError IS NULL
        OR        s.shortName = "ENIN"
        AND       s.category = "orderErrors"
        )
        AND       os.shortName != "CNCL"
        AND       os.category = "orders"
        ';

    static $errorMsg = NULL;

    const STATUS_ACTIVE = 'AC';


    /*
    ****************************************************************************
    */

    function getCheckInArray($scans)
    {
        $vendors = new vendors($this->app);

        //process data Input
        $data = $this->processDataScan($scans);

        $batch = getDefault($data['batch'], NULL);
        $batches = getDefault($data['batches'], []);
        $passedOrders = getDefault($data['passedOrders'], []);
        $passedBatches = getDefault($data['passedBatches'], []);
        $passedBatches = array_values($passedBatches);

        //validate data Input
        $isValidate = $this->validateDataInput([
            'batch' => $batch,
            'passedBatches' => $passedBatches,
            'passedOrders' => $passedOrders,
        ]);

        if (! $isValidate) {
            return [
                'batches' => [],
                'errMsg' => self::$errorMsg,
            ];
        }

        $batchesWavePicks = $this->getBatchesWavePicks($passedBatches);

        $this->checkPassedBatchesWavePicks($batchesWavePicks, $passedBatches);
        $this->checkPassedBatches($batches, $passedBatches);

        $batchNumbers = array_keys($batches);
        $orderNumbers = array_keys($passedOrders);

        $batchVendors = $vendors->getByBatchNumber($batchNumbers);
        $orderVendors = $vendors->getByScanOrderNumber($orderNumbers);

        $this->checkPassedBatchesOrderNumbers([
            'batches' => $batches,
            'batchVendors' => $batchVendors,
            'orderVendors' => $orderVendors,
        ]);

        return [
            'batches' => $batches,
            'wavePicks' => $batchesWavePicks,
            'errMsg' => self::$errorMsg,
        ];
    }

    /*
    ****************************************************************************
    */

    function getWavePicksOrders()
    {
        $fields = $this->getFieldValues($this->fields);

        $sql = 'SELECT  ' . $fields . '
                FROM    ' . $this->table;

        $results = $this->app->ajaxQueryResults($sql);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getByOrderNumber($scanOrderNumber, $field='id')
    {
        if (! $scanOrderNumber) {
            return NULL;
        }

        $sql = 'SELECT    ob.' . $field . '
                FROM      order_batches ob
                JOIN      neworder n ON n.order_batch = ob.id
                WHERE     n.scanordernumber = ?';

        $result = $this->app->queryResult($sql, [$scanOrderNumber]);

        return $result ? $result[$field] : NULL;
    }

    /*
    ****************************************************************************
    */

    function insertDefaultBatch($vendor, $dealSiteID, $returnLastID=FALSE)
    {
        if (! ($vendor && $dealSiteID)) {
            return FALSE;
        }

        $sql = 'INSERT INTO order_batches (
                    vendorID,
                    dealSiteID
                  ) VALUES (
                    ?, ?
                  )';

        $this->app->runQuery($sql, [$vendor, $dealSiteID]);

        $result = $returnLastID ? $this->app->lastInsertID() : FALSE;

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getBatchesWavePicks($batches)
    {
        if (! $batches) {
            return FALSE;
        }

        $results = [];

        $clauses = array_fill(0, count($batches), 'order_batch = ?');

        $clauseString = implode(' OR ', $clauses);

        $sql = 'SELECT DISTINCT
                          order_batch,
                          pc.pickID
                FROM      neworder n
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      pick_cartons pc ON pc.orderID = n.id
                WHERE     active
                AND       (' . $clauseString . ')';

        $data = $this->app->queryResults($sql, $batches);

        if (! $data) {
            return $results;
        }

        foreach ($data as $batchID => $wavePick) {
            $results[$batchID] = $wavePick['pickID'];
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function updateOrdersBatch($orders, $batch, $pickID)
    {
        $clauses = array_fill(0, count($orders), 'scanordernumber = ?');
        $clauseString = implode(' OR ', $clauses);

        $batchUpdateParams = $orders;

        $sql = $this->getQueryUpdateOrderBatch($clauseString);

        array_unshift($batchUpdateParams, $batch);
        array_unshift($batchUpdateParams, $batch);

        $this->app->runQuery($sql, $batchUpdateParams);

        $pickTicketUpdateParams = $orders;

        $sql = $this->getQueryUpdateOrderBatchActive($clauseString);

        array_unshift($pickTicketUpdateParams, $pickID);

        $result = $this->app->runQuery($sql, $pickTicketUpdateParams);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function updateBatch($batches, $wavePicks)
    {
        if (! $batches) {
            return;
        }

        $this->app->beginTransaction();

        foreach ($batches as $batch => $orders) {
            $this->updateOrdersBatch($orders, $batch, $wavePicks[$batch]);
        }

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function changOrdersBatch($vendorID, $orderNumbers)
    {
        $dealSites = new dealSites($this->app);
        $locations = new locations($this->app);
        $vendors = new vendors($this->app);
        $waveStatuses = new statuses\wavePicks($this->app);

        $dealSiteID = $dealSites->getWholesaleID();
        $batch = $this->insertDefaultBatch($vendorID, $dealSiteID, TRUE);
        $warehouseID = $vendors->getVendorWarehouse($vendorID);
        $waveStatusID = $waveStatuses->getStatusID(self::STATUS_ACTIVE);
        $freeShippingLanes = $locations->getFreeShippingLocations($warehouseID);

        reset($freeShippingLanes);
        $shippingLaneID = key($freeShippingLanes);

        $wavePickInsert = $this->getQueryWavePickInsert();

        $this->app->runQuery($wavePickInsert, [$shippingLaneID, $waveStatusID]);

        $pickID = $this->app->lastInsertID();

        $this->app->beginTransaction();

        $this->updateOrdersBatch($orderNumbers, $batch, $pickID);

        $this->app->commit();

        return $batch;
    }

    /*
    ****************************************************************************
    */

    function isWholeSale($id)
    {
        $sql = 'SELECT    dealSiteID
                FROM      order_batches b
                JOIN      deal_sites d ON d.id = b.dealSiteID
                WHERE     b.id = ?
                AND       displayName = "Wholesale"
                LIMIT 1';

        $result = $this->app->queryResult($sql, [$id]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function isWholeSaleDealSite($id)
    {
        $sql = 'SELECT    id
                FROM      deal_sites d
                WHERE     d.id = ?
                AND       displayName = "Wholesale"
                LIMIT 1';

        $result = $this->app->queryResult($sql, [$id]);
        return $result;
    }

    /*
    ****************************************************************************
    */

    function getQueryUpdateOrderBatch($clauseString)
    {
        if (! $clauseString) {
            return NULL;
        }

        $sql = 'UPDATE    neworder n
                JOIN      order_batches ob ON ob.id = order_batch
                SET       pickID = ?,
                          order_batch = ?
                WHERE     ' . $clauseString;

        return $sql;
    }

    /*
    ****************************************************************************
    */

    function getQueryUpdateOrderBatchActive($clauseString)
    {
        if (! $clauseString) {
            return FALSE;
        }

        $sql = 'UPDATE  neworder n
                JOIN    order_batches ob ON ob.id = n.order_batch
                JOIN    pick_cartons pc ON pc.orderID = n.id
                SET     pc.pickID = ?
                WHERE   active
                AND     ' . $clauseString;

        return $sql;
    }

    /*
    ****************************************************************************
    */

    function getQueryWavePickInsert()
    {
        $sql = 'INSERT INTO pick_waves (
                                locID,
                                statusID
                           ) VALUES (
                                ?, ?
                           )';
        return $sql;
    }

    /*
    ****************************************************************************
    */

    function processDataScan($scans)
    {
        $batches = $passedOrders = $passedBatches = [];
        $batch = NULL;

        if (! $scans) {
            return;
        }

        foreach ($scans as $scan) {
            if (! $batch) {
                // batchNumber to open
                $batch = $scan;
                continue;
            }

            $passedBatches[$batch] = $batch;

            if ($batch == $scan) {
                // batchNumber to close
                $batch = NULL;
                continue;
            }

            if (isset($passedOrders[$scan])) {
                $break = self::$errorMsg ? '<br>' : NULL;
                self::$errorMsg .= $break . 'Order Number ' . $scan .
                    ' has been passed twice. No duplicate values are allowed.';
            }

            $passedOrders[$scan] = TRUE;

            $batches[$batch][] = $scan;
        }

        $results = [
            'batch' => $batch,
            'batches' => $batches,
            'passedBatches' => $passedBatches,
            'passedOrders' => $passedOrders,
        ];

        return $results;
    }

    /*
    ****************************************************************************
    */

    function validateDataInput($params)
    {
        $batch = $params['batch'];
        $passedBatches = $params['passedBatches'];
        $passedOrders = $params['passedOrders'];

        $reuslt = TRUE;

        if ($batch) {
            $break = self::$errorMsg ? '<br>' : NULL;
            self::$errorMsg .= $break . 'Batch Number ' . $batch .
                    ' is missing closing entry';
            $reuslt = FALSE;
        }

        if (! $passedBatches) {
            $break = self::$errorMsg ? '<br>' : NULL;
            self::$errorMsg .= $break . 'Missing Batch Numbers';
            $reuslt = FALSE;
        }

        if (! $passedOrders) {
            $break = self::$errorMsg ? '<br>' : NULL;
            self::$errorMsg .= $break . 'Missing Order Numbers';
            $reuslt = FALSE;
        }

        return $reuslt;
    }

    /*
    ****************************************************************************
    */

    function checkPassedBatchesWavePicks($batchesWavePicks, $passedBatches)
    {
        if (! $passedBatches) {
            return FALSE;
        }

        foreach ($passedBatches as $batch) {
            if (! isset($batchesWavePicks[$batch])) {
                $break = self::$errorMsg ? '<br>' : NULL;
                self::$errorMsg .= $break . 'Batch Number ' . $batch .
                        ' is missing Wave Pick';
            }
        }
    }

    /*
    ****************************************************************************
    */

    function checkPassedBatches($batches, $passedBatches)
    {
        if (! $passedBatches) {
            return FALSE;
        }

        foreach ($passedBatches as $batch) {
            if (! isset($batches[$batch])) {
                $break = self::$errorMsg ? '<br>' : NULL;
                self::$errorMsg .= $break . 'Batch Number ' . $batch .
                        ' is missing orders';
            }
        }
    }

    /*
    ****************************************************************************
    */

    function checkPassedBatchesOrderNumbers($params)
    {
        $batches = $params['batches'];
        $batchVendors = $params['batchVendors'];
        $orderVendors = $params['orderVendors'];

        foreach ($batches as $batch => $orderNumbers) {

            $error = FALSE;

            if (! isset($batchVendors[$batch])) {

                $break = self::$errorMsg ? '<br>' : NULL;
                self::$errorMsg .= $break . 'Batch Number ' . $batch .
                        ' was not found';

                $error = TRUE;
            }

            $this->checkPassedBatchesOrderVendorNumbers([
                'batch' => $batch,
                'error' => $error,
                'orderNumbers' => $orderNumbers,
                'orderVendors' => $orderVendors,
                'batchVendors' => $batchVendors,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function checkPassedBatchesOrderVendorNumbers($params)
    {
        $batch = $params['batch'];
        $error = $params['error'];
        $orderNumbers = $params['orderNumbers'];
        $orderVendors = $params['orderVendors'];
        $batchVendors = $params['batchVendors'];

        if (! $orderNumbers) {
            return FALSE;
        }

        foreach ($orderNumbers as $orderNumber) {

            if (! isset($orderVendors[$orderNumber])) {

                $break = self::$errorMsg ? '<br>' : NULL;
                self::$errorMsg .= $break . 'Order Number ' . $orderNumber .
                        ' was not found';

                $error = TRUE;
            }

            if (! $error
            && $batchVendors[$batch] != $orderVendors[$orderNumber]) {

                $break = self::$errorMsg ? '<br>' : NULL;
                self::$errorMsg .= $break . 'Order Number ' . $orderNumber .
                        ' can not be assigned to Batch Number ' . $batch .
                        ' due to vendors incompatibility';
            }
        }
    }

    /*
    ****************************************************************************
    */

    function getBatchInfo($batch)
    {
        $sql = 'SELECT    upc,
                          SUM(product_quantity) AS quantity,
                          vendorID,
                          n.id AS orderID
                FROM      neworder n
                JOIN      online_orders oo
                    ON oo.SCAN_SELDAT_ORDER_NUMBER = n.scanordernumber
                JOIN      order_batches b ON n.order_batch = b.id
                JOIN      statuses s ON s.id = n.statusID
                WHERE     b.id = ?
                AND       s.shortName != "' . orders::STATUS_CANCELED . '"
                GROUP BY  upc';

        $result = $this->app->queryResults($sql, [$batch]);

        return $result;
    }

    /*
    ****************************************************************************
    */

}