<?php

namespace tables;

use \common\order;
use \common\logger;

class wavePicks extends _default
{
    public $ajaxModel = 'wavePicks';

    public $primaryKey = 'pc.id';

    public $fields = [
        'order_batch' => [
            'display' => 'Batch Number',
        ],
        'dateCreated' => [
            'select' => 'ob.dateCreated',
            'display' => 'Date Created'
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'scanOrderNumber' => [
            'display' => 'Scan Order Number',
        ],
        'upc' => [
            'select' => 'p.upc',
            'display' => 'UPC',
        ],
        'uom' => [
            'select' => 'SUM(uom)',
            'display' => 'Quantity',
            'groupedFields' => 'uom',
        ],
        'status' => [
            'select' => 's.displayName',
            'display' => 'Status',
            'searcherDD' => 'statuses\\wavePicks',
            'ddField' => 'displayName',
        ],
        'location' => [
            'select' => 'l.displayName',
            'display' => 'Loacation',
        ],
    ];

    // with "GROUP BY upc, scanOrderNumber" clause the query works faster than
    // with "GROUP BY scanOrderNumber, upc"

    public $groupBy = 'upc, scanOrderNumber';

    /*
    ****************************************************************************
    */

    function table()
    {
        return 'pick_waves pw
                JOIN (
                    SELECT    pickID,
                              pc.id,
                              batchID,
                              pc.orderID,
                              SUM(uom) AS uom
                    FROM      pick_cartons pc
                    JOIN      inventory_cartons ca ON ca.ID = pc.cartonID
                    WHERE     isOriginalPickTicket
                    AND       pc.active
                    GROUP BY  pickID, batchID, pc.orderID
                ) pc ON pc.pickID = pw.id
                JOIN      locations l ON l.id = pw.locID
                JOIN      neworder n ON n.id = pc.orderID
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      vendors v ON v.id = ob.vendorID
                JOIN      warehouses w ON w.id = v.warehouseID
                JOIN      statuses s ON s.id = pw.statusID
                JOIN      inventory_batches b ON b.id = pc.batchID
                JOIN      upcs p ON p.id = b.upcID
                ';
    }

    /*
    ****************************************************************************
    */

    function getFreeCartons($params, $batch, $mezzanineClause)
    {
        $warehouses = new warehouses($this->app);
        $vendors = new vendors($this->app);

        $warehouseID = $warehouses->getOrderBatchWarehouse($batch);
        $vendorID = $vendors->getByBatchNumber($batch);

        $sqlParams[] = [
            'warehouseID' => $warehouseID,
            'vendorID' => $vendorID,
            'mezzanineClause' => $mezzanineClause,
            'params' => $params,
        ];

        $sqlData = $this->getFreeCartonsQuery($sqlParams);

        $result = $this->app->queryResults($sqlData['sql'], $sqlData['params']);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getFreeCartonsQuery($data)
    {
        $cartons = new inventory\cartons($this->app);

        $ucc128 = $cartons->fields['ucc128']['select'];

        $qParams = $subQueries = [];

        foreach ($data as $values) {

            $warehouseID = $values['warehouseID'];
            $vendorID = $values['vendorID'];
            $mezzanineClause = $values['mezzanineClause'];
            $params = $values['params'];

            $subQuery = '(
                    SELECT    ca.id,
                              upcID,
                              ' . $ucc128 . ' AS ucc128,
                              uom,
                              upc,
                              prefix,
                              suffix,
                              l.displayName AS location,
                              IF(
                                  sp.childID IS NOT NULL AND sp.active, 1, 0
                              ) AS splitCarton,
                              CONCAT(
                                  co.vendorID,
                                  b.id,
                                  LPAD(uom, 3, 0)
                              ) AS batch,
                              isMezzanine,
                              ca.cartonID AS cartonIndex
                    FROM      inventory_containers co
                    JOIN      inventory_batches b ON b.recNum = co.recNum
                    JOIN      inventory_cartons ca ON ca.batchID = b.id
                    JOIN      statuses s ON ca.statusID = s.id
                    JOIN      upcs p ON b.upcID = p.id
                    JOIN      locations l ON ca.locID = l.id
                    LEFT JOIN inventory_splits sp ON sp.childID = ca.id
                    LEFT JOIN pick_cartons pc ON pc.cartonID = ca.id
                    LEFT JOIN neworder n ON n.id = pc.orderID
                    WHERE     s.shortName = "' . inventory\cartons::STATUS_RACKED. '"
                    AND       category = "inventory"
                    AND       ca.statusID = mStatusID
                    AND       ! l.isShipping
                    AND       ' . $mezzanineClause . '
                    AND       l.displayName NOT IN ('
                         . '"' . locations::NAME_LOCATION_STAGING . '", '
                         . '"' . locations::NAME_LOCATION_BACK_TO_STOCK . '"'
                    . ')
                    AND       l.warehouseID = ?
                    AND       co.vendorID = ?
                    AND       upc = ?
                    AND       NOT isSplit
                    AND       NOT unSplit
                    AND       (pc.id IS NULL
                        OR NOT pc.active
                    )
                    AND       p.active
                    GROUP BY  ca.id
                    ORDER BY  upc,
                              uom DESC,
                              batchID ASC,
                              l.displayName ASC,
                              ca.id ASC';

            foreach ($params as $upcs => $quantity) {

                $qParams[] = $warehouseID;
                $qParams[] = $vendorID;
                $qParams[] = $upcs;

                $subQueries[] = $subQuery . '
                    LIMIT '.intval($quantity) . ')';
            }
        }

        return [
            'sql' => implode(' UNION ', $subQueries),
            'params' => $qParams,
        ];
    }

    /*
    ****************************************************************************
    */

    function getBatchesUPCs($orderNumbers, $isTruckOrder)
    {
        $qMarks = $this->app->getQMarkString($orderNumbers);

        if ($isTruckOrder) {

            $truckOrderData = $mezzanineData = $closedOrders = $truckOrders = [];

            $orders = new orders($this->app);

            $processedOrders = $orders->checkIfOrderProcessed($orderNumbers);

            foreach ($orderNumbers as $orderNumber) {
                if ($processedOrders['processedOrders'][$orderNumber]) {
                    $closedOrders[] = $orderNumber;
                } else {
                    $truckOrders[] = $orderNumber;
                }
            }

            if ($closedOrders) {
                // closed Truck Orders products data may differ from truck_waves
                // table since it can be changed in Picking Check Out page
                $qMarks = $this->app->getQMarkString($closedOrders);

                $sql = 'SELECT    pc.id,
                                  upc,
                                  SUM(ca.uom) AS quantity,
                                  b.upcID,
                                  n.scanOrderNumber,
                                  n.id AS orderID,
                                  p.sku,
                                  size,
                                  color,
                                  n.customerordernumber
                        FROM 	  neworder n
                        JOIN      pick_cartons pc ON pc.orderID = n.id
                        JOIN      inventory_cartons ca ON ca.id = pc.CartonID
                        JOIN      inventory_batches b ON b.id = ca.batchID
                        JOIN      upcs p ON p.id = b.upcID
                        JOIN      locations l ON l.id = ca.locID
                        WHERE     n.pickID
                        AND       isMezzanine
                        AND       pc.isOriginalPickTicket
                        AND       pc.active
                        AND       p.active
                        AND       n.scanOrderNumber IN (' . $qMarks . ')
                        GROUP BY  upc,
                                  scanOrderNumber
                        ';

                $mezzanineData = $this->app->queryResults($sql, $closedOrders);
            }

            if ($truckOrders) {

                $qMarks = $this->app->getQMarkString($truckOrders);

                $sql = 'SELECT    tow.id,
                                  u.upc,
                                  SUM(tow.quantity) AS quantity,
                                  upcID,
                                  n.scanOrderNumber,
                                  sku,
                                  color,
                                  size,
                                  n.id AS orderID,
                                  n.customerordernumber
                        FROM      neworder n
                        JOIN      truck_orders t
                            ON LPAD(CONCAT(t.userID, t.assignNumber), 10, "0") = n.scanOrderNumber
                        JOIN      truck_order_waves tow ON tow.truckOrderID = t.id
                        JOIN      upcs u ON u.id = tow.upcID
                        WHERE     tow.active
                        AND       u.active
                        AND       n.scanOrderNumber IN (' . $qMarks . ')
                        GROUP BY  upc,
                                  t.userID,
                                  assignNumber
                        ';

                $truckOrderData = $this->app->queryResults($sql, $truckOrders);
            }

            return array_merge($truckOrderData, $mezzanineData);
        }

        $sql = 'SELECT    pc.id,
                          upc,
                          SUM(ca.uom) AS quantity,
                          b.upcID,
                          n.scanOrderNumber,
                          n.id AS orderID,
                          p.sku,
                          size,
                          color,
                          n.customerordernumber,
                          n.edi,
                          n.isPrintUccEdi
                FROM 	  neworder n
                JOIN      pick_cartons pc ON pc.orderID = n.id
                JOIN      inventory_cartons ca ON ca.id = pc.CartonID
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      upcs p ON p.id = b.upcID
                JOIN      locations l ON l.id = ca.locID
                WHERE     n.pickID
                AND       NOT isMezzanine
                AND       pc.isOriginalPickTicket
                AND       pc.active
                AND       p.active
                AND       n.scanOrderNumber IN (' . $qMarks . ')
                GROUP BY  upc,
                          scanOrderNumber
                ';

        $reservedOrders = $this->app->queryResults($sql, $orderNumbers);

        // select from online_orders table
        $sql = 'SELECT    o.id,
                          o.upc,
                          SUM(GREATEST(0, o.product_quantity)) AS quantity,
                          u.ID AS upcID,
                          n.scanOrderNumber,
                          u.sku,
                          color,
                          size,
                          n.id AS orderID,
                          n.customerordernumber,
                          n.edi,
                          n.isPrintUccEdi
                FROM      neworder n
                JOIN      online_orders o
                    ON o.SCAN_SELDAT_ORDER_NUMBER = n.scanOrderNumber
                JOIN      upcs u ON o.upc = u.upc
                LEFT JOIN statuses s ON s.id = n.isError
                WHERE     n.pickID IS NULL
                AND       (isError IS NULL
                    OR  s.shortName = "ENIN"
                    AND s.category = "orderErrors"
                )
                AND       n.scanOrderNumber IN (' . $qMarks . ')
                GROUP BY  o.upc,
                          SCAN_SELDAT_ORDER_NUMBER
                HAVING    quantity > 0
                ';

        $onlineOrders = $this->app->queryResults($sql, $orderNumbers);

        return array_merge($reservedOrders, $onlineOrders);
    }

    /*
    ****************************************************************************
    */

    function clear($orders)
    {
        if (! $orders) {
            return;
        }

        $pickCartons = new inventory\pickCartons($this->app);
        $cartons = new inventory\cartons($this->app);
        $statuses = new statuses\inventory($this->app);

        $statusID = $statuses->getStatusID(inventory\cartons::STATUS_RACKED);

        $cartonData = $pickCartons->getByOrderNumber($orders,
                'ca.locID, ca.statusID, mLocID, mStatusID');

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        foreach ($orders as $orderNumber) {

            $sql = 'UPDATE neworder
                    SET    numberofcarton = NULL,
                           numberofpiece = NULL,
                           pickid = NULL
                    WHERE  scanOrderNumber = ?';

            $this->app->runQuery($sql, [$orderNumber]);

            $sql = 'UPDATE inventory_cartons ca
                    JOIN   pick_cartons pc ON pc.cartonID = ca.id
                    JOIN   neworder o ON o.id = pc.orderID
                    SET    mStatusID = ?,
                           mLocID = locID
                    WHERE  ca.statusID = ?
                    AND    scanOrderNumber = ?
                    AND    active';

            $this->app->runQuery($sql, [$statusID, $statusID, $orderNumber]);

            $sql = 'UPDATE pick_cartons pc
                    JOIN   neworder o ON o.id = pc.orderID
                    SET    active = 0
                    WHERE  scanOrderNumber = ?';

            $this->app->runQuery($sql, [$orderNumber]);

            $sql = 'UPDATE pick_errors pe
                    JOIN   neworder o ON o.id = pe.orderID
                    SET    active = 0
                    WHERE  scanOrderNumber = ?';

            $this->app->runQuery($sql, [$orderNumber]);

            if (isset($cartonData[$orderNumber])) {
                $cartons->logCartonManualData($cartonData[$orderNumber]);
            }
        }

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function processOrderProducts($param)
    {
        $result = [
            'status' => false,
            'errors' => []
        ];
        $products = getDefault($param['products'], []);
        $orderProducts = getDefault($param['orderProducts'], []);
        $orderBatches = getDefault($param['orderBatches']);
        $isOrderCheckOut = getDefault($param['isOrderCheckOut']);
        $shortageProducts = getDefault($param['shortageProducts'], []);
        $upcList = getDefault($param['upcList'], []);
        $isTruckOrder = $param['isTruckOrder'];

        foreach ($orderProducts as $key => $value) {
            if (! $value && ! isset($shortageProducts[$key])) {
                // removing orders with empty products table
                unset($orderProducts[$key]);
            }
        }

        foreach ($shortageProducts as $key => $value) {
            if (! isset($orderProducts[$key])) {
                $orderProducts[$key] = FALSE;
            }
        }

        if (! $orderProducts) {
            // there are no products to process
            return $result;
        }

        $orders = new \tables\orders($this->app);

        $ordersNumbers = array_keys($orderProducts);

        $skipResults = $this->checkSkipProcessing($ordersNumbers, $orders);

        $closedOrders = $skipResults['closedOrders'];
        $skipProcessing = $skipResults['skipProcessing'];

        if ($skipProcessing) {
            // exit current function if all sumbitted orders are alreday processsed
            return $result;
        }

        $wavePickInsert = 'INSERT INTO pick_waves (
                                locID,
                                statusID
                           ) VALUES (
                                ?, ?
                           )';

        $errorPickUpdate = 'UPDATE pick_errors
                                SET active = 0
                                WHERE orderID = ?';

        $sqlNeworder = 'UPDATE neworder n
                        SET    pickID = ?
                        WHERE  n.scanOrderNumber = ?';

        $waveStatuses = new statuses\wavePicks($this->app);
        $inventoryStatuses = new statuses\inventory($this->app);
        $upcs = new \tables\inventory\upcs($this->app);
        $locations = new \tables\locations($this->app);

        $ordersIDs = order::getIDs($this->app, $ordersNumbers);

        $waveStatusID = $waveStatuses->getStatusID('AC');

        $rackedStatus = \tables\inventory\cartons::STATUS_RACKED;
        $reservedStatus = \tables\inventory\cartons::STATUS_RESERVED;

        $statusIDs = $inventoryStatuses->getStatusIDs([$rackedStatus, $reservedStatus]);

        $reservedStatusID = $statusIDs[$reservedStatus]['id'];
        $rackedStatusID = $statusIDs[$rackedStatus]['id'];

        $orderWarehouses = $orders->getOrdersWarehouses($ordersNumbers);
        $shippingLocations = $locations->getFreeShippingLocations();

        if ($orderBatches) {
            // order check-in: batches are submitted from the user form
        } else {
            // order check-out, online-orders: batches are from neworder file
            foreach ($orderWarehouses as $scanOrderNumber => $value) {
                $batch = $value['order_batch'];
                $orderBatches[$batch][$scanOrderNumber] = NULL;
            }
        }

        $dbProducts = $orders->getDBProducts($ordersNumbers, $isTruckOrder);

        $dbOrders = [];

        foreach ($dbProducts as $cartonID => $dbProduct) {
            if ($isTruckOrder !== NULL
             && ($isTruckOrder && ! $dbProduct['isMezzanine']
              || ! $isTruckOrder && $dbProduct['isMezzanine'])) {
                // skip Mezzanine inventory when processing "Master Cartons"
                // table and skip Regular Warehouse inventory when processing
                // "Mixed Items Cartons" table. Process all cartons (either
                // Mezzanine or Regular Warehouse only) if it is NOT a Track Order
                continue;
            }

            $order = $dbProduct['scanOrderNumber'];
            $dbOrders[$order]['cartons'][] = $cartonID;
        }

        if ($upcList) {
            foreach ($upcList as $upc => $value) {
                $upcIDs[$upc]['id'] = $value['upcID'];
            }
        } else {
            $productUpcs = [];

            foreach ($products as $product) {
                $upcValues = array_keys($product);
                $productUpcs = $productUpcs + array_flip($upcValues);
            }

            $upcIDs = $upcs->getUPCs(array_keys($productUpcs));
        }

        $batchPickInfo = $this->getBatchPickInfo($ordersNumbers);

        $maxPickID = $this->getMaxWavePick();
        $pickID = getDefault($maxPickID, 0);

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        try {
            $this->app->beginTransaction();

            // create wave picks for batches that do not have ones
            foreach ($batchPickInfo as $batch => $value) {
                if (!$value['pickID']) {
                    // create a new wave pick for the current batch

                    $result = $this->shippingLocation([
                        'warehouseID'       => $value['warehouseID'],
                        'shippingLocations' => $shippingLocations,
                        'batch'             => $batch
                    ]);

                    $locID = $result['locID'];
                    $shippingLocations = $result['shippingLocations'];

                    $batchPickInfo[$batch]['pickID'] = ++$pickID;

                    $this->app->runQuery($wavePickInsert, [$locID, $waveStatusID]);
                }
            }

            foreach ($ordersNumbers as $orderNumber) {

                if ($closedOrders[$orderNumber]) {
                    // do not make any changes if an order is processed
                    continue;
                }

                $isContinue = $this->process([
                    'ordersIDs'        => $ordersIDs,
                    'orderNumber'      => $orderNumber,
                    'errorPickUpdate'  => $errorPickUpdate,
                    'shortageProducts' => $shortageProducts,
                    'products'         => $products,
                    'upcIDs'           => $upcIDs,
                    'orderBatches'     => $orderBatches,
                    'batchPickInfo'    => $batchPickInfo,
                    'isOrderCheckOut'  => $isOrderCheckOut,
                    'sqlNeworder'      => $sqlNeworder,
                    'dbOrders'         => $dbOrders,
                    'orderProducts'    => $orderProducts,
                    'reservedStatusID' => $reservedStatusID,
                    'rackedStatusID'   => $rackedStatusID,
                    'isTruckOrder'     => $isTruckOrder,
                ]);

                if ($isContinue) {
                    continue;
                }
            }

            $this->app->commit();
            return [
                'status' => true
            ];
        } catch (\Exception $e){
            
            $this->app->success = false;
            $this->app->noShippingLane = true;

            $result ['errors'][] = $e->getMessage();
            return $result;
        }
    }

    /*
    ****************************************************************************
    */

    function process($data)
    {
        $ordersIDs = $data['ordersIDs'];
        $orderNumber = $data['orderNumber'];
        $errorPickUpdate = $data['errorPickUpdate'];
        $shortageProducts = $data['shortageProducts'];
        $products = $data['products'];
        $upcIDs = $data['upcIDs'];
        $orderBatches = $data['orderBatches'];
        $batchPickInfo = $data['batchPickInfo'];
        $isOrderCheckOut = $data['isOrderCheckOut'];
        $sqlNeworder = $data['sqlNeworder'];
        $dbOrders = $data['dbOrders'];
        $orderProducts = $data['orderProducts'];
        $reservedStatusID = $data['reservedStatusID'];
        $rackedStatusID = $data['rackedStatusID'];
        $isTruckOrder = $data['isTruckOrder'];

        // $isTruckOrder variable can be of 3 values:
        // NULL  - $orderNumber does not have a truck order. I.e. either a Regular
        //         Order (invantory shall be taken from the Regular Warehouse) or
        //         an Online Order (invantory shall be taken from the Mezzanine)
        // FALSE - $orderNumber has a Truck Order and invantory shall be taken
        //         from the Regular Warehouse only (Master Cartons table)
        // TRUE  - $orderNumber has a Truck Order and invantory shall be taken
        //         from the Mezzanine only (Mixed Items Cartons table)

        $orderID = $ordersIDs[$orderNumber];

        $this->app->runQuery($errorPickUpdate, [$orderID]);

        if (isset($shortageProducts[$orderNumber])) {
            $this->insertWavePickErrors([
                'products' => $products,
                'orderNumber' => $orderNumber,
                'orderID' => $orderID,
                'upcIDs' => $upcIDs,
            ]);
            // invoke continue
            return TRUE;
        }

        $pickID = $this->getPickID([
            'orderBatches' => $orderBatches,
            'batchPickInfo' => $batchPickInfo,
            'orderNumber' => $orderNumber,
            'isOrderCheckOut' => $isOrderCheckOut,
            'sql' => $sqlNeworder
        ]);

        $pickID || die('Missing Pick ID');

        $submittedCartons = $dbCartons = [];

        if (isset($dbOrders[$orderNumber])) {
            // get reserved cartons from database
            $dbCartons = $dbOrders[$orderNumber]['cartons'];
        }

        if (isset($orderProducts[$orderNumber])) {
            // get submitted table cartons derive from a user form
            $submittedCartons = $orderProducts[$orderNumber];
        }

        $cartonsToDelete = array_diff($dbCartons, $submittedCartons);

        if ($cartonsToDelete) {
            $this->deactivateWavePickCartons([
                'invIDs' => array_values($cartonsToDelete),
                'reservedStatusID' => $reservedStatusID,
                'rackedStatusID' => $rackedStatusID,
                'isTruckOrder' => $isTruckOrder,
            ]);
        }

        $cartonsToInsert = array_diff($submittedCartons, $dbCartons);

        if ($cartonsToInsert) {
            $this->insertWavePickCartons([
                'invIDs' => array_values($cartonsToInsert),
                'reservedStatusID' => $reservedStatusID,
                'rackedStatusID' => $rackedStatusID,
                'orderID' => $orderID,
                'pickID' => $pickID,
            ]);
        }

        // skip continue
        return FALSE;
    }

    /*
    ****************************************************************************
    */

    function insertWavePickErrors($data)
    {
        $products = $data['products'];
        $orderNumber = $data['orderNumber'];
        $orderID = $data['orderID'];
        $upcIDs = $data['upcIDs'];

        $errorPickInsert = 'INSERT INTO pick_errors (
                                orderID,
                                quantity,
                                upcID
                            ) VALUES (
                                ?, ?, ?
                            )';

        foreach ($products[$orderNumber] as $upc => $product) {

            $quantities = array_column($product, 'quantity');

            $this->app->runQuery($errorPickInsert, [
                $orderID,
                array_sum($quantities),
                $upcIDs[$upc]['id'],
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function deactivateWavePickCartons($data)
    {
        $invIDs = $data['invIDs'];
        $reservedStatusID = $data['reservedStatusID'];
        $rackedStatusID = $data['rackedStatusID'];
        $isTruckOrder = $data['isTruckOrder'];

        $deleteJoin = 'JOIN    inventory_cartons ca ON ca.id = pc.cartonID
                       JOIN    locations l ON l.id = ca.locID';

        $updateJoin = 'JOIN    locations l ON l.id = ca.locID';

        if ($isTruckOrder === NULL) {
            // $orderNumber does not have a truck order. The order is a normal
            // regular order (invantory shall be taken from regular warehouse) or
            // an online order (invantory shall be taken from the Mezzanine)

            // no extra clauses are required
            $deleteJoin = $updateJoin = $where = NULL;
        } else {
            if ($isTruckOrder) {
                // $orderNumber has a truck order and invantory shall be taken
                // from the Mezzanine only (Mixed Items Cartons table)
                $where = 'AND     isMezzanine';
            } else {
                // $orderNumber has a truck order and invantory shall be taken
                // from the regular warehouse only (Master Cartons table)
                $where = 'AND     NOT isMezzanine';
            }
        }

        $qMarks = $this->app->getQMarkString($invIDs);

        $this->deactivateByCartonID($invIDs, $deleteJoin, $where);

        $sqlUpdate = 'UPDATE inventory_cartons ca
                      ' . $updateJoin . '
                      SET    mStatusID = statusID
                      WHERE  ca.id IN (' . $qMarks . ')
                      ' . $where;

        $this->app->runQuery($sqlUpdate, $invIDs);

        $this->mStatusLog($invIDs, $reservedStatusID, $rackedStatusID);
    }

    /*
    ****************************************************************************
    */

    function insertWavePickCartons($data)
    {
        $invIDs = $data['invIDs'];
        $reservedStatusID = $data['reservedStatusID'];
        $rackedStatusID = $data['rackedStatusID'];
        $orderID = $data['orderID'];
        $pickID = $data['pickID'];

        $orderPickInsert = 'INSERT IGNORE pick_orders (
                                orderID
                            ) VALUES (
                                ?
                            )';

        $this->app->runQuery($orderPickInsert, [$orderID]);

        $cartonPickInsert = 'INSERT INTO pick_cartons (
                                orderID,
                                cartonID,
                                pickID
                            ) VALUES (
                                ?, ?, ?
                            ) ON DUPLICATE KEY UPDATE
                                pickID = ?,
                                active = 1,
                                isOriginalPickTicket = 1';

        foreach ($invIDs as $cartonID) {
            $this->app->runQuery($cartonPickInsert, [
                $orderID,
                $cartonID,
                $pickID,
                $pickID,
            ]);
        }

        $qMarks = $this->app->getQMarkString($invIDs);

        $sqlUpdate = 'UPDATE inventory_cartons
                      SET    mStatusID = ?
                      WHERE  id IN (' . $qMarks . ')';

        $params = $invIDs;

        array_unshift($params, $reservedStatusID);

        $this->app->runQuery($sqlUpdate, $params);

        $this->mStatusLog($invIDs, $rackedStatusID, $reservedStatusID);
    }

    /*
    ****************************************************************************
    */

    function mStatusLog($invIDs, $fromStatusID, $toStatusID)
    {
        logger::edit([
            'db' => $this->app,
            'primeKeys' => $invIDs,
            'fields' => [
                'mStatusID' => [
                    'fromValues' => array_fill(0, count($invIDs), $fromStatusID),
                    'toValues' => $toStatusID,
                ],
            ],
            'transaction' => FALSE,
        ]);
    }

    /*
    ****************************************************************************
    */

    function checkSkipProcessing($ordersNumbers, $orders=NULL)
    {
        $orders = $orders ? $orders : new \tables\orders($this->app);

        $checkResults = $orders->checkIfOrderProcessed($ordersNumbers);

        $closedOrders = $checkResults['processedOrders'];

        $skipProcessing = $this->checkIfSkipProcessing($closedOrders);

        return [
            'closedOrders' => $closedOrders,
            'skipProcessing' => $skipProcessing,
        ];
    }

    /*
    ****************************************************************************
    */

    function checkIfSkipProcessing($closedOrders)
    {
        foreach ($closedOrders as $value) {
            if (! $value) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function getPickID($data)
    {
        $orderBatches = $data['orderBatches'];
        $batchPickInfo = $data['batchPickInfo'];
        $orderNumber = $data['orderNumber'];
        $isOrderCheckOut = $data['isOrderCheckOut'];
        $sqlNeworder = $data['sql'];

        $pickID = NULL;

        foreach ($orderBatches as $batch => $orders) {

            $scanOrderNumbers = array_keys($orders);

            foreach ($scanOrderNumbers as $scanOrderNumber) {
                if ($scanOrderNumber == $orderNumber) {

                    $pickID = $batchPickInfo[$batch]['pickID'];

                    if ($isOrderCheckOut) {
                        // pick_cartons data will prevail over online_orders
                        $this->app->runQuery($sqlNeworder, [$batch, $orderNumber]);
                    }

                    return $pickID;
                }
            }
        }

        return $pickID;
    }

    /*
    ****************************************************************************
    */

    function getMaxWavePick()
    {
        $sql = 'SELECT    id
                FROM      pick_waves
                ORDER BY  id DESC
                LIMIT 1
                ';

        $result = $this->app->queryResult($sql);

        return getDefault($result['id'], 0);
    }

    /*
    ****************************************************************************
    */

    function shippingLocation($param)
    {
        $warehouseID = $param['warehouseID'];
        $shippingLocations = $param['shippingLocations'];
        $batch = $param['batch'];

        $locID = NULL;

        foreach($shippingLocations as $id => $location) {
            if ($location['warehouseID'] == $warehouseID) {
                // select shipping location
                $locID = $id;
                break;
            }
        }

        if ($locID) {
            // remove selected location from the list of available locations
            unset($shippingLocations[$locID]);
        } else {
            throw new \Exception('There are no free Shipping Lanes for batch # ' . $batch);
        }

        return [
            'locID' => $locID,
            'shippingLocations' => $shippingLocations,
        ];
    }

    /*
    ****************************************************************************
    */

    function getBatchPickInfo($orders)
    {
        $clause = 'scanOrderNumber IN ('.$this->app->getQMarkString($orders).')';

        $sql = 'SELECT DISTINCT
                          n.order_batch,
                          warehouseID,
                          b.pickID
                FROM      neworder n
                JOIN      order_batches b ON b.id = n.order_batch
                JOIN      vendors v ON v.id = b.vendorID
                LEFT JOIN (
                    SELECT    n.order_batch,
                              pw.id AS pickID
                    FROM      neworder n
                    JOIN      neworder b ON b.order_batch = n.order_batch
			    	JOIN      pick_cartons pc ON pc.orderID = n.id
                	JOIN      pick_waves pw ON pw.id = pc.pickID
                	WHERE     b.' . $clause . '
	                AND       pc.active
                ) b ON b.order_batch = n.order_batch
                WHERE     ' . $clause;

        $param = array_merge($orders, $orders);

        $results = $this->app->queryResults($sql, $param);

        return $results;
     }

    /*
    ****************************************************************************
    */

    function createPickTicket($data)
    {
        $post = $data['post'];
        $orders = $data['tables']['orders'];
        $orderBatches = getDefault($data['tables']['orderBatches'], []);
        $truckOrderWaves = getDefault($data['tables']['truckOrderWaves'], []);
        $ucc128 = $data['ucc128'];
        $returnInventory = getDefault($data['returnInventory'], FALSE);

        $orderNumber = $post['orderNumber'];
        $tableData = getDefault($post['tableData'], []);
        $children = getDefault($post['children'], []);

        $checkResults = $orders->checkIfOrderProcessed($orderNumber);

        $isClosed = $checkResults['processedOrders'][$orderNumber];

        $batch = $returnInventory ? NULL :
                $orderBatches->getByOrderNumber($orderNumber);

        if ($isClosed) {

            $error = $returnInventory ? [
                'Order # <strong>' . $orderNumber . '</strong> is processed',
            ] : FALSE;

            return [
                'error' => $error,
                'products' => NULL,
                'batch' => $batch,
            ];
        }

        $result = $orders->getSubmittedTableData([
            'orderNumber' => $orderNumber,
            'tableData' => $tableData,
        ]);

        if ($result['productErrors']) {
            return [
                'error' => $result['productErrors'],
                'splitProducts' => FALSE,
                'batch' => FALSE,
            ];
        }

        if ($returnInventory) {
            $isTruckOrder = $returnInventory == 'mezzanine';
        } else {

            $truckOrders = $truckOrderWaves->getExistingTruckOrders([$orderNumber]);

            $isTruckOrder = $truckOrders ? FALSE : NULL;
        }

        $products[$orderNumber] = $result['products'];

        $results = $this->checkSubmittedData([
            'products' => $products,
            'orderNumber' => $orderNumber,
            'children' => $children,
            'isTruckOrder' => $isTruckOrder,
            'ucc128' => $ucc128,
            'orders' => $orders,
        ]);

        $productErrors = $results['productErrors'];
        $splitProducts = $results['splitProducts'];
        $orderProducts = $results['orderProducts'];

        if ($productErrors || $splitProducts) {
            return [
                'error' => $productErrors,
                'products' => $products,
                'splitProducts' => $splitProducts,
                'batch' => FALSE,
            ];
        }

        if ($returnInventory) {
            $this->processOrderProducts([
                'products' => $products,
                'orderProducts' => $orderProducts,
                'isOrderCheckOut' => TRUE,
                'isTruckOrder' => $isTruckOrder,
            ]);
        } else {

            $productErrors = $splitProducts = FALSE;

            if ($truckOrders) {

                $truckProducts = $truckOrderWaves->getTruckProducts([$orderNumber]);

                $results = $this->checkSubmittedData([
                    'products' => $truckProducts,
                    'orderNumber' => $orderNumber,
                    'children' => $children,
                    'isTruckOrder' => TRUE,
                    'ucc128' => $ucc128,
                    'orders' => $orders,
                ]);

                $productErrors = $results['productErrors'];
                $splitProducts = $results['splitProducts'];
                $truckOrderProducts = $results['orderProducts'];
            }

            if ($productErrors || $splitProducts) {
                return [
                    'error' => $productErrors,
                    'splitProducts' => $splitProducts,
                    'batch' => FALSE,
                ];
            }

            $this->processOrderProducts([
                'products' => $products,
                'orderProducts' => $orderProducts,
                'isOrderCheckOut' => TRUE,
                'isTruckOrder' => $isTruckOrder,
            ]);

            if ($truckOrders) {
                $this->processOrderProducts([
                    'products' => $truckProducts,
                    'orderProducts' => $truckOrderProducts,
                    'isOrderCheckOut' => TRUE,
                    'isTruckOrder' => TRUE,
                ]);
            }
        }

        return [
            'error' => FALSE,
            'splitProducts' => FALSE,
            'inventory' => $orderProducts,
            'batch' => $batch,
        ];
    }

    /*
    ****************************************************************************
    */

    public function checkSubmittedData($data)
    {
        $products = $data['products'];
        $orderNumber = $data['orderNumber'];
        $children = $data['children'];
        $isTruckOrder = $data['isTruckOrder'];
        $orders = $data['orders'];
        $ucc128 = $data['ucc128'];

        $splitProducts = $productErrors = FALSE;
        $orderProducts = [];

        if ($products[$orderNumber]) {

            $result = $orders->checkSubmittedTableData([
                'products' => $products,
                'order' => $orderNumber,
                'children' => $children,
                'splitArray' => TRUE,
                'ucc128' => $ucc128,
                'isTruckOrder' => $isTruckOrder,
            ]);

            $productErrors = $result['productErrors'];
            $orderProducts = $result['orderProducts'];

            if (! $productErrors) {
                // display split dialog only if there is no other errors
                $splitProducts = $result['splitProducts'];
            }
        } else {
            $productErrors = ['Product data are missing!'];
        }

        return [
            'productErrors' => $productErrors,
            'splitProducts' => $splitProducts,
            'orderProducts' => $orderProducts,
        ];
    }

    /*
    ****************************************************************************
    */

    public function createReportData($params)
    {
        $batchID = $params['batchOrder'];
        $data = $params['data'];

        $status = $params['status'];

        \common\report::recordReportsSent($this->app, [$batchID], $status, $data);
    }

    /*
    ****************************************************************************
    */

    public function deactivateByCartonID($invIDs, $join=NULL, $where=NULL)
    {
        if (! $invIDs) {
            return;
        }

        $qMarks = $this->app->getQMarkString($invIDs);

        $sql = 'UPDATE pick_cartons pc
                ' . $join . '
                SET    pc.active = 0,
                       pc.isOriginalPickTicket = 0
                WHERE  pc.cartonID IN (' . $qMarks . ')
                ' . $where;

        $this->app->runQuery($sql, $invIDs);
    }

    /*
    ****************************************************************************
    */

}
