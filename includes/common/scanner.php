<?php

namespace common;

use tables\orders;
use tables\inventory\cartons;
use tables\locations;
use tables\users;
use access;
use tables\vendors;

class scanner
{
    static public $emailTo = [
        'nadia.chavez@seldatinc.com'
    ];

    static $scannerTitle = [
        'receivingToStock' => 'Receiving / Back To Stock',
        'plateLocation' => 'Scan To Location',
        'routedCheckIn' => 'Routed Check-In',
        'routedCheckOut' => 'Routed Check-Out',
        'pickingCheckIn' => 'Picking Check-In',
        'orderProcessingCheckIn' => 'Order Processing Check-In',
        'orderProcessCheckOut' => 'Order Processing Check-Out',
        'shippingCheckIn' => 'Shipping Check In',
        'shipped' => 'Shipped Check-Out',
        'shippedOrders' => 'Online Orders Shipping',
        'errOrderRelease' => 'Error Order',
        'cancel' => 'Cancel Order',
        'adjust' => 'Adjust Cartons',
        'batch' => 'Move Order to a Batch',
        'locations' => 'Location Inquery Scanner',
        'inventoryTransfer' => 'Inventory Transfer Scanner',
        'confirmMezzanineTransfers' => 'Confirm Mezzanine Transfer',
        'warehouseOutboundTransfer' => 'Warehouse Outbound Transfer',
        'warehouseInboundTransfer' => 'Warehouse Inbound Transfer',
    ];

    static $app;

    static $contentTitle = [
        'UPC',
        'SKU',
        'Location',
        'Requested',
        'Picked',
        'Cycle Name',
        'Assigned To'
    ];

    const ONLINE_ORDERS_SHIP_PASSWORD = '74459201e474d056327719e3a3d4fc36';

    /*
    ****************************************************************************
    */

    static function get(&$scans)
    {
        if (! $scans) {
            return FALSE;
        }

        $scans = preg_replace('/\s+/u',' ',$scans );
        $scans = str_replace(" ", "\n", $scans);
        $scanArray = explode("\n", $scans);
        $trimmedScans = array_map('trim', $scanArray);
        self::removeConsecDups($trimmedScans);
        $noBlanks = array_filter($trimmedScans);
        $scans = $noBlanks;
    }

    /*
    ****************************************************************************
    */

    static function removeConsecDups(&$array)
    {
        $prevValue = NULL;
        foreach ($array as $key => $value) {
            if ($value == $prevValue) {
                unset($array[$key]);
            } else {
                $prevValue = $value;
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function processOnlineOrderScan($data)
    {
        $app = $data['app'];
        $classes = $data['classes'];
        $useTracking = $app->results['useTracking'] = $data['useTracking'];
        $app->results['useUPC'] = $data['useUPC'];

        $online = $classes['onlineOrders'];

        $orderNumber = $data['orderNumber'] =
                getDefault($_SESSION['scanInput']['orderID']);

        $app->results['complete'] = $app->results['orderShipped'] = FALSE;

        if (! $orderNumber) {

            self::processOrderScan($data);

            return;
        }

        $trackingID = $data['trackingID'] = $app->results['trackingID'] =
                getDefault($_SESSION['scanInput']['trackingID']);

        if ($useTracking && ! $trackingID) {

            self::processTrackingScan($data);

            if ($app->results['error']) {
                unset($_SESSION['scanInput']);
            }

            return;
        }

        $requiredUPCs = $data['requiredUPCs'] =
                $online->checkOrderTrackingID($orderNumber, $trackingID);

        // $_SESSION['scanInput'] is being chnages in getScanInput() function
        self::getScanInput($data);

        if ($app->results['error']) {

            unset($_SESSION['scanInput']);

            return;
        }

        $complete = TRUE;

        $cartonIDs = [];

        foreach ($_SESSION['scanInput']['cartons'] as $upc => $invIDs) {

            $cartonIDs = array_merge($cartonIDs, $invIDs);

            $required = $requiredUPCs[$upc]['product_quantity'];

            $piecesScanned = count($invIDs);

            if ($piecesScanned > $required) {

                unset($_SESSION['scanInput']);

                return $app->results['error'] = 'UPC Excess:<br> Required '
                        . 'Pieces: ' . $required . '<br> Pieces Scanned: '
                        . $piecesScanned;
            }

            $complete = $piecesScanned == $requiredUPCs[$upc]['product_quantity'];
        }

        $app->results['upc'] = $upc;
        $app->results['cartonsScanned'] = $piecesScanned;

        if (! $useTracking) {
            $app->results['trackingID'] = 'No Tracking ID';
        }

        if ($complete) {

            $orderID = $online->processInventory([
                'orderNumber' => $orderNumber,
                'trackingID' => $trackingID,
                'invIDs' => $cartonIDs,
                'classes' => $classes,
            ]);

            $app->results['orderShipped'] =
                    self::checkIfOrderShipped($app, $orderNumber, $orderID);

            unset($_SESSION['scanInput']);

            $app->results['next'] = 'Packing Slip';

            $app->results['complete'] = TRUE;
        } else {

            $app->results['orderShipped'] = FALSE;

            $app->results['next'] = 'UPC ' . $upc . ' Required Cartons ('
                    . $piecesScanned . ' of '
                    . $requiredUPCs[$upc]['product_quantity'] . ')';
        }
    }

    /*
    ****************************************************************************
    */

    static function processOrderScan($data)
    {
        $app = $data['app'];
        $classes = $data['classes'];
        $scan = $data['scans'];
        $useUPC = $data['useUPC'];
        $useTracking = $app->results['useTracking'] = $data['useTracking'];

        $online = $classes['onlineOrders'];
        $orders = $classes['orders'];

        $primary = [
            'assoc' => 'id',
            'field' => $online->primaryKey
        ];

        $app->results['error'] = FALSE;

        // Check the order exist and is not shipped
        $checkOrder = $online->valid($scan, 'scan_seldat_order_number', $primary);

        $caption = 'Online Order # ' . $scan;

        if (! $checkOrder['valid']) {
            return $app->results['error'] = $caption . ' does not exist';
        }

        $results = $orders->getOrderProcessed([$scan]);

        if ($results[$scan]['isCancelled']) {
            return $app->results['error'] = $caption . ' is cancelled';
        }

        if ($results[$scan]['isHold']) {
            return $app->results['error'] = $caption . ' is currently on hold';
        }

        if ($results[$scan]['isError']) {
            return $app->results['error'] = $caption . ' is an Error Order';
        }

        $_SESSION['scanInput']['orderID'] = $app->results['orderNumber'] = $scan;

        if (! $useUPC) {
            if ($results[$scan]['isClosed']) {

                unset($_SESSION['scanInput']);

                return $app->results['error'] = 'Some cartons from ' . $caption
                        . ' were shipped.<br>Use "UPC san" option instead.';
            }

            $result = $online->getCartonsByOrder($scan);

            if ($result['errors']) {
                return $app->results['error'] = implode('<br>', $result['errors']);
            }

            $online->processInventory([
                'orderNumber' => $scan,
                'invIDs' => $result['invIDs'],
                'classes' => $classes,
            ]);

            $app->results['orderShipped'] = $app->results['complete'] = TRUE;

            unset($_SESSION['scanInput']);

            return $app->results['next'] = 'Packing Slip';
        }

        self::getProcessDescription($data);

        if (! $useTracking) {
            foreach ($app->results['description'] as &$values) {
                $values['trackingID'] = 'No Tracking ID';
            }
        }

        return $app->results['next'] = $useTracking ? 'Tracking ID' :
                'Required UPCs';
    }

    /*
    ****************************************************************************
    */

    static function processTrackingScan($data)
    {
        $app = $data['app'];
        $classes = $data['classes'];
        $scan = $data['scans'];
        $orderNumber = $data['orderNumber'];

        $online = $classes['onlineOrders'];

        $primary = [
            'assoc' => 'id',
            'field' => $online->primaryKey
        ];

        // Make sure the tracking ID exists
        $checkTracking = $online->valid($scan, 'shipment_tracking_id', $primary);

        if (! $checkTracking['valid']) {
            return $app->results['error'] = 'Shipment Tracking ID Not Found: '
                    . $scan;
        }

        // Make sure the tracking ID matches the order
        $requiredUPCs = $online->checkOrderTrackingID($orderNumber, $scan);

        if (! $requiredUPCs || ! key($requiredUPCs)) {
            return $app->results['error'] = 'Incorrect Order - Tracking Number';
        }

        $_SESSION['scanInput']['upc'] = NULL;
        $_SESSION['scanInput']['trackingID'] = $app->results['trackingID'] =
                $scan;
    }

    /*
    ****************************************************************************
    */

    static function getScanInput($data)
    {
        $app = $data['app'];
        $classes = $data['classes'];
        $scan = $data['scans'];
        $orderNumber = $data['orderNumber'];
        $requiredUPCs = $data['requiredUPCs'];

        $online = $classes['onlineOrders'];

        $upc = (string)(float)$scan;

        $requiredUPC = $_SESSION['scanInput']['upc'] =
                getDefault($_SESSION['scanInput']['upc'], 0);

        if (! isset($requiredUPCs[$upc]) || $requiredUPC && $requiredUPC != $upc) {
            return $app->results['error'] = 'Unrequired UPC scanned: ' . $upc;
        }

        $isShipped = self::checkIfOrderUPCShipped($app, $orderNumber, $upc);

        if ($isShipped) {
            return $app->results['error'] = 'All UPC ' . $upc . ' cartons '
                    . 'requested for Order # ' . $orderNumber . ' were shipped';
        }

        $usedInvIDs = getDefault($_SESSION['scanInput']['usedInvIDs'], []);

        // Make sure all carton UPCs are correct
        $invID = $online->getCartonByUPC($orderNumber, $upc, $usedInvIDs);

        if (! $invID) {
            return $app->results['error'] = 'No more cartons available';
        }

        $_SESSION['scanInput']['upc'] = $upc;
        $_SESSION['scanInput']['cartons'][$upc][] =
                $_SESSION['scanInput']['usedInvIDs'][] = $invID;
    }

    /*
    ****************************************************************************
    */

    function getTargetCartonsArray($scans, $target='scanordernumber', $isTransfer=FALSE)
    {
        // Get target cartons array
        $plural = $target.'s';
        $pluralCartons = $plural.'Cartons';
        $pluralUCCs = $plural.'UCCs';

        $openTarget = $openPlate = NULL;
        $lookingFor = $target;

        $scanTargets = [
            'plates' => [],
            $plural => [],
            'cartons' => [],
            'structured' => [],
            $pluralCartons => [],
            'platesCartons' => [],
            $pluralUCCs => []
        ];

        foreach ($scans as $key => $scan) {

            if ($openTarget == $scan && $scans[$key-1] != $openTarget) {
                $lookingFor = $target;
                continue;
            }

            if ($openPlate == $scan && $scans[$key-1] != $openTarget) {
                $lookingFor = 'plate';
                continue;
            }

            switch ($lookingFor) {
                case $target:
                    $scanTargets['structured'][$scan] = [];
                    $openTarget = $scan;
                    $scanTargets[$plural][] = $scan;
                    $scanTargets[$pluralCartons][$openTarget] = [];
                    $lookingFor = $isTransfer ? 'UCC' : 'plate';
                    break;
                case 'plate':
                    if (is_array($scan)) {
                        // UCC goes immediately after Order (Wave Pick) Number
                        // License Plate is not submitted
                        return FALSE;
                    }

                    $scanTargets['structured'][$openTarget][$scan] = [];
                    $openPlate = $scan;
                    $scanTargets['plates'][$scan] = TRUE;
                    $scanTargets['platesCartons'][$scan]['cartonNumbers'] = [];
                    $lookingFor = 'carton';
                    break;
                case 'carton':
                    $scanTargets['structured'][$openTarget][$openPlate][] = $scan;
                    $scanTargets[$pluralCartons][$openTarget][] = $scan;
                    $scanTargets['cartons'][] = $scan;
                    $scanTargets['platesCartons'][$openPlate]['cartonNumbers'][] = $scan;
                    break;
                case 'UCC':
                    if (is_array($scan)) {
                        foreach ($scan as $cartonID => $value) {
                            $scanTargets[$pluralUCCs][$openTarget][$cartonID] = $value;
                        }
                    } else {
                        $scanTargets[$pluralUCCs][$openTarget][] = $scan;
                    }

                    break;
            }

        }

        return $scanTargets;
    }


    /*
    ****************************************************************************
    */

    static function checkCartonMezzanineWarehouse($data, &$errors)
    {
        $plates = $data['plates'];
        $noMezzanineOrders = $data['noMezzanineOrders'];
        $mezzanineCartons = $data['mezzanineCartons'];
        $orderNumber = $data['orderNumber'];

        foreach ($plates as $cartons) {
            foreach ($cartons as $carton) {

                $ucc = key($carton);
                $invID = reset($carton);

                if (isset($noMezzanineOrders[$orderNumber])
                        && isset($mezzanineCartons[$invID])) {

                    $errors[] = 'Mezzanine carton ' . $ucc . ' can not be '
                        . 'processed within regular order ';
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function orderProcessingCheckOut($data)
    {
        $app = $data['app'];
        $ordersPassed = $data['ordersPassed'];
        $cartons = $data['classes']['cartons'];
        $orderStatuses = $data['classes']['orderStatuses'];
        $cartonStatuses = $data['classes']['cartonStatuses'];
        $locations = $data['classes']['locations'];

        $orderNumbers = array_keys($ordersPassed);
        $orderIDs = array_values($ordersPassed);

        $platesEntered = [];

        foreach ($data['platesEntered'] as $orderNumber => $platesCount) {

            $orderID = $ordersPassed[$orderNumber];

            $platesEntered[$orderID] = $platesCount;
        }

        $cartonStatusIDs = $cartonStatuses->getStatusIDs([
            cartons::STATUS_RACKED,
            cartons::STATUS_ORDER_PROCESSING,
        ]);

        $orderNewStatusID =
                $orderStatuses->getStatusID(orders::STATUS_PROCESSING_CHECK_OUT);

        $processingInventory = $cartons->getProcessingInventory($orderIDs);

        $platesRequired = array_sum($platesEntered);

        $plateResults = self::createProcessingPlates($app, $platesRequired);

        $plateResults['errors'] && die($plateResults['errors']);

        $plates = $plateResults['licensePlates'];

        if ($plateResults['errors']) {
            return $plateResults['errors'];
        }

        $orderFromValues = $orderStatuses->getOrderStatusIDs($orderIDs);

        $orderLodFieldIDs = logger::getFieldIDs('orders', $app);
        $orderLogID = logger::getLogID();

        logger::getFieldIDs('cartons', $app);
        logger::getLogID();

        $app->beginTransaction();

        $orderPlates = self::orderProcessCartons([
            'app' => $app,
            'processingInventory' => $processingInventory,
            'plates' => $plates,
            'platesEntered' => $platesEntered,
            'statusID' => $cartonStatusIDs[cartons::STATUS_ORDER_PROCESSING]['id'],
        ]);

        logger::setFieldIDs($orderLodFieldIDs);
        logger::setLogID($orderLogID);

        self::orderProcessOrders([
            'app' => $app,
            'orderIDs' => $orderIDs,
            'fromValues' => $orderFromValues,
            'statusID' => $orderNewStatusID,
        ]);

        $app->commit();

        $return = [];

        $orderKeys = array_flip($ordersPassed);

        foreach ($orderPlates as $orderID => $plates) {

            $orderNumber = $orderKeys[$orderID];

            $return[$orderNumber] = array_keys($plates);
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    static function orderProcessCartons($data)
    {
        $app = $data['app'];
        $processingInventory = $data['processingInventory'];
        $plates = $data['plates'];
        $platesEntered = $data['platesEntered'];
        $statusID = $data['statusID'];

        $cartonSql = '
            UPDATE    inventory_cartons
            SET       plate = ?,
                      statusID = ?,
                      mStatusID = ?,
                      orderID = ?
            WHERE     id = ?
        ';

        $fields = $orderUsedPlates = [];

        foreach ($processingInventory as $orderID => $values) {

            $orderPlates = array_splice($plates, 0, $platesEntered[$orderID]);

            $plateCount = count($orderPlates);

            foreach ($values as $key => $carton) {

                $plate = $orderPlates[$key % $plateCount];

                $orderUsedPlates[$orderID][$plate] = TRUE;

                $invIDs[] = $carton['invID'];

                $fields['plate']['fromValues'][] = $carton['plate'];
                $fields['statusID']['fromValues'][] = $carton['statusID'];
                $fields['mStatusID']['fromValues'][] = $carton['mStatusID'];
                $fields['orderID']['fromValues'][] = $carton['orderID'];

                $fields['plate']['toValues'][] = $plate;
                $fields['statusID']['toValues'][] = $statusID;
                $fields['mStatusID']['toValues'][] = $statusID;
                $fields['orderID']['toValues'][] = $orderID;

                $app->runQuery($cartonSql, [
                    $plate,
                    $statusID,
                    $statusID,
                    $orderID,
                    $carton['invID']
                ]);
            }
        }

        logger::edit([
            'db' => $app,
            'primeKeys' => $invIDs,
            'fields' => $fields,
            'transaction' => FALSE,
        ]);

        return $orderUsedPlates;
    }

    /*
    ****************************************************************************
    */

    static function orderProcessOrders($data)
    {
        $app = $data['app'];
        $orderIDs = $data['orderIDs'];
        $fromValues = $data['fromValues'];
        $statusID = $data['statusID'];

        $orderSql = '
            UPDATE    neworder
            SET       statusID = ?
            WHERE     id IN (' . $app->getQMarkString($orderIDs) . ')';

        array_unshift($orderIDs, $statusID);

        $app->runQuery($orderSql, $orderIDs);

        logger::edit([
            'db' => $app,
            'primeKeys' => array_keys($fromValues),
            'fields' => [
                'statusID' => [
                    'fromValues' => array_values($fromValues),
                    'toValues' => $statusID,
                ],
            ],
            'transaction' => FALSE,
        ]);
    }

    /*
    ****************************************************************************
    */

    static function getInvIDs($inventoty)
    {
        $invIDs = [];

        foreach ($inventoty as $cartonData) {
            if (! is_array($cartonData)) {
                // skip invalid UCCs
                continue;
            }

            $invIDs[] = key($cartonData);
        }

        return $invIDs;
    }

    /*
    ****************************************************************************
    */

    static function sendShippingMail($app, $orders)
    {
        if (getDefault($_SESSION['onScanner'])) {
            // do not generate BoL if the user is on the mobile page
            return;
        }

        $results = self::getLading($app, $orders);

        foreach ($results as $order) {
            // creating Bill of Lading with timestamp in its name
            $path = \models\directories::getDir('uploads', 'billoflading');

            $file = $path.'BillOfLading'.$order.'_'.date('Y-m-d-H-i-s').'.pdf';

            \orders\lading::output($app, [$order], $file);

            // sending Bill of Lading
            $subject = 'Bill of Lading '.$order;
            $text = 'Bill of Lading';

            \PHPMailer\send::mail([
                'recipient' => self::$emailTo,
                'subject' => $subject,
                'body' => $text,
                'files' => $file,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    static function getLading($app, $scans)
    {
        $qMarkString = $app->getQMarkString($scans);

        $sql = 'SELECT    ca.id,
                          o.scanordernumber,
                          p.id AS licenseplate
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      licenseplate p ON p.id = ca.plate
                JOIN      neworder o ON o.id = ca.orderId
                WHERE     ca.plate IN (' . $qMarkString . ')';

        $results = $app->queryResults($sql, array_flip($scans));

        foreach ($results as $result) {
            $key = $result['licenseplate'];
            $scans[$key] = $result['scanordernumber'];
        }

        return $scans;
    }

    /*
    ****************************************************************************
    */

    static function confirmMezzanineTransfer($transfers, $transfersPassed, $piecesPassed)
    {
        $discrepant = $confirmed = [];

        $transferNumbers = array_combine($transfersPassed, $piecesPassed);

        $transferPieces = $transfers->getBarcodePieces($transfersPassed);

        $transfers->app->beginTransaction();

        foreach ($transferPieces as $transferNo => $quantity) {

            $discrepancy = $transferNumbers[$transferNo] - $quantity['pieces'];

            $discrepancy ? $discrepant[] = $transferNo : $confirmed[] = $transferNo;

            $transfers->updateDiscrepancy($transferNo, $discrepancy);
        }

        $transfers->app->commit();

        return [
            'discrepant' => $discrepant,
            'confirmed' => $confirmed
        ];
    }

    /*
    ****************************************************************************
    */

    static function rejectOnlineOrdersMessage($scanOrders, $orders)
    {
        $returnOrderArray = TRUE;

        $online = $orders->getOnlineOrderNumbers($scanOrders, $returnOrderArray, TRUE);

        if ($online) {

            $errMsg = NULL;

            foreach ($online as $onlineOrderNumber) {
                $break = $errMsg ? '<br>' : NULL;
                $errMsg .= $break . $onlineOrderNumber;
            }

            return 'Online Orders are not allowed:<br><br>' . $errMsg;
        }

        return;
    }

    /*
    ****************************************************************************
    */

    static function checkDataInput($app, $uccInfo, $locationsUCCs)
    {
        $return = $dataTransfer = $vendorLocations = $locationVendors =
                $clientList = [];

        $uccWarehouse = NULL;
        $return['errors'] = [];

        $statuses = new \tables\statuses\inventory($app);
        $locations = new locations($app);
        $cartons = new cartons($app);

        $statusID = $statuses->getStatusID(cartons::STATUS_RACKED);

        $locationArray = array_keys($locationsUCCs);

        $allowMezzanine = FALSE;

        $locationWarehouses = $locations->getLocationWarehouses($locationArray,
                $allowMezzanine);

        $invalidLocation =
            array_diff($locationArray, array_keys($locationWarehouses));

        if ($invalidLocation) {
            $return['errors'][] = 'Locations ' . implode(',', $invalidLocation)
                . ' not is Mezzanine Location.';
            return $return;
        }

        foreach ($locationsUCCs as $location => $uccs) {

            $locationWarehouse = getDefault($locationWarehouses[$location]);

            if (! $locationWarehouse) {

                $return['errors'][] = 'Location ' . $location . ' was not found';

                continue;
            }

            $targetWarehouses = $locationWarehouses[$location]['warehouses'];
            $targetLocation = $locationWarehouses[$location]['locID'];

            foreach ($uccs as $ucc) {

                $info = getDefault($uccInfo[$ucc]);

                if (! $info) {

                    $return['errors'][] = 'UCC ' . $ucc . ' was not found';

                    continue;
                }

                if (! in_array($info['warehouseID'], $targetWarehouses)) {
                    $return['errors'][] = $location . ' and ' . $ucc . ' belong'
                            . ' to different warehouses.';
                }

                if ($info['statusID'] != $statusID || $info['mStatusID'] != $statusID) {
                    $return['errors'][] = 'UCC ' . $ucc . ' Regular and Manual '
                            . 'Statuses should be "Racked"';
                }

                if ($info['isSplit'] || $info['unSplit']) {
                    $return['errors'][] = 'UCC ' . $ucc . ' Split / Unsplit '
                            . 'cartons are not allowed';
                }

                $vendorID = $info['vendorID'];

                $locationVendors[$location][$vendorID] = TRUE;
                $vendorLocations[$vendorID][] = $location;

                $uccWarehouse = $info['warehouseID'];

                $dataTransfer[$targetLocation][$ucc] = $uccInfo[$ucc];
            }
        }

        foreach ($vendorLocations as $vendorID => $locIDs) {
            $params[] = [
                'field' => 'l.displayName',
                'values' => array_unique($locIDs),
                'joinClause' => '
                    JOIN      locations l ON l.id = ca.locID
                    ',
                'vendorID' => $vendorID,
                'whereClause' => 'v.warehouseID = ?',
                'whereParams' => [$uccWarehouse],
            ];
        }

        foreach ($locationVendors as $location => $values) {

            $vendorCount = count($values);

            if ($vendorCount > 1) {
                $return['errors'][] = 'UCCs that belong to different Clients '
                        . 'were scanned for Location Name ' . $location;
            }
        }

        $results = $cartons->getAmbiguousVendors($params);

        foreach ($results as $values) {

            $vendor = $values['fullVendorName'];
            $location = $values['displayName'];

            $clientList[$location][$vendor] = TRUE;
        }

        foreach ($clientList as $location => $values) {

            $vendorNames = array_keys($values);

            $return['errors'][] = 'Location Name ' . $location . ' has Racked '
                    . 'inventory already assigned to different Client(s): ';

            $return['errors'] = array_merge($return['errors'], $vendorNames);
        }

        return $return['errors'] ? $return : ['dataTransfer' => $dataTransfer];
    }

    /*
    ****************************************************************************
    */

    static function sendShippingMailByBOLs($app, $bolIDs)
    {
        foreach ($bolIDs as $bolID) {
            // creating Bill of Lading with timestamp in its name
            $path = \models\directories::getDir('uploads', 'billoflading');

            $file = $path.'/BillOfLading'.$bolID.'_'.date('Y-m-d-H-i-s').'.pdf';

            $pdf = new \pdf\creator();

            \orders\lading::output($app, $pdf, $bolID);

            $pdf->output($file, 'F');

            // sending Bill of Lading
            $subject = 'Bill of Lading '.$bolID;
            $text = 'Bill of Lading';

            \PHPMailer\send::mail([
                'recipient' => self::$emailTo,
                'subject' => $subject,
                'body' => $text,
                'files' => $file,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    static function checkIfOrderShipped($app, $orderNumber, $orderID)
    {
        $sql = 'SELECT SUM(quantity) AS quantity
                FROM (
                    (
                        -- cartons requested by online order
                        SELECT    SUM(product_quantity) AS quantity
                        FROM      online_orders oo
                        WHERE     SCAN_SELDAT_ORDER_NUMBER = ?
                    ) UNION (
                        -- cartons shipped
                        SELECT    -1 * SUM(uom) AS quantity
                        FROM      inventory_cartons ca
                        WHERE     orderID = ?
                    )
                ) a';

        $results = $app->queryResult($sql, [$orderNumber, $orderID]);

        return $results['quantity'] <= 0;
    }

    /*
    ****************************************************************************
    */

    static function checkIfOrderUPCShipped($app, $orderNumber, $upc)
    {

        $sql = 'SELECT SUM(quantity) AS quantity
                FROM (
                    (
                        -- cartons requested by online order
                        SELECT    SUM(product_quantity) AS quantity
                        FROM      online_orders oo
                        WHERE     SCAN_SELDAT_ORDER_NUMBER = ?
                        AND       upc = ?
                    ) UNION (
                        -- cartons shipped
                        SELECT    -1 * SUM(uom) AS quantity
                        FROM      inventory_cartons ca
                        JOIN      inventory_batches b ON b.id = ca.batchID
                        JOIN      upcs u ON u.id = b.upcID
                        JOIN      neworder n ON n.id = ca.orderID
                        WHERE     scanOrderNumber = ?
                        AND       upc = ?
                    )
                ) a';

        $results = $app->queryResult($sql, [$orderNumber, $upc, $orderNumber, $upc]);

        return $results['quantity'] <= 0;
    }

    /*
    ****************************************************************************
    */

    static function workOrderCheckInVerify($payload)
    {
        $app = $payload['app'];
        $scans = $payload['scans'];

        self::get($scans);

        $errors = $workOrders = [];

        $workOrderHeaders = new \tables\workOrders\workOrderHeaders($app);

        $orders = new \tables\orders($app);

        $results = $workOrderHeaders->getCheckInArray($scans);

        if ($results['errors']) {
            return [
                'errors' => $results['errors'],
                'workOrders' => [],
            ];
        }

        // Check if any order is an Error Order
        $errors[] = $orders->onHoldOrError([
            'order' => $results['passedOrders'],
            'select' => 'isError',
        ]);

        // Check if any order is On Hold
        $errors[] = $orders->onHoldOrError([
            'order' => $results['passedOrders']
        ]);

        // Check if any order is an Online Order
        $errors[] = self::rejectOnlineOrdersMessage($results['passedOrders'], $orders);

        $actualErrors = array_filter($errors);

        return [
            'errors' => array_values($actualErrors),
            'workOrders' => $results['workOrders'],
        ];
    }

    /*
    ****************************************************************************
    */

    static function workOrderCheckIn($app, $scans)
    {
        logger::createLogScanInput([
            'app' => $app,
            'scanInput' => implode('<br>', $scans),
        ]);
    }

    /*
    ****************************************************************************
    */

    static function workOrderCheckOutVerify($payload)
    {
        $app = $payload['app'];
        $scans = $payload['scans'];

        self::get($scans);

        $workOrderHeaders = new \tables\workOrders\workOrderHeaders($app);

        return $workOrderHeaders->getCheckOutArray($scans);
    }

    /*
    ****************************************************************************
    */

    static function pickingCheckOut($app, $data)
    {
        $classes = [
            'orders' => new \tables\orders($app),
            'cartons' => new \tables\inventory\cartons($app),
            'wavePicks' => new \tables\wavePicks($app),
            'cartonStatuses' => new \tables\statuses\inventory($app),
            'users' => new users($app)
        ];

        $locations = new \tables\locations($app);

        $invIDs = $processInventory = $parentUCCs = $return = $children =
                $cycleCountData = $backToStock = $discrepantLocationKeys =
                $cycleCountLocations = [];

        $statusIDs = $classes['cartonStatuses']->getStatusIDs([
            cartons::STATUS_RACKED,
            cartons::STATUS_RESERVED,
            cartons::STATUS_PICKED,
            cartons::STATUS_DISCREPANCY,
        ]);

        $dataSendingEmail = $cycleCountDataAllOrder = [];

        foreach ($data as $orderNumber => $orderProducts) {
            if (! isset($orderProducts['data'])) {
                continue;
            }

            $orderInfo = self::getOrderInfo($app, $orderNumber);

            $vendorID = $orderProducts['vendorID'];

            foreach ($orderProducts['data'] as $warehouseType => $values) {

                $results = self::getPickingInventory([
                    'app' => $app,
                    'values' => $values,
                    'orderNumber' => $orderNumber,
                    'warehouseType' => $warehouseType,
                    'classes' => $classes,
                    'processInventory' => $processInventory,
                    'invIDs' => $invIDs,
                    'parentUCCs' => $parentUCCs,
                    'children' => $children,
                ]);

                if ($results['errors']) {
                    return [
                        'errors' => $results['errors'],
                        'processingResults' => NULL,
                    ];
                }

                $processInventory = $results['processInventory'];
                $invIDs = $results['invIDs'];
                $parentUCCs = $results['parentUCCs'];
                $children = $results['children'];

                $params = [
                    'values' => $values,
                    'vendorID' => $vendorID,
                    'orderNumber' => $orderNumber,
                    'orderInfo' => $orderInfo,
                    'cycleCountDataAllOrder' => &$cycleCountDataAllOrder,
                    'classUser' => $classes['users']
                ];

                $cycleCountData = self::getCycleCounts($params);
            }

            $dataSendingEmail[$orderNumber] = $cycleCountData;
        }

        $pickingSplits = self::getPickingSplitChildren($classes['cartons'],
                $invIDs, $children);

        $orderNumbers = array_keys($data);

        if ($pickingSplits) {
            $backToStock = $locations->getTypeLocationsByOrderNumber(
                    $orderNumbers, locations::NAME_LOCATION_BACK_TO_STOCK);
        }

        self::updateOrdersToPicked($app, $classes['orders'], $orderNumbers);


        logger::getFieldIDs('cartons', $app);

        logger::getLogID();

        $app->beginTransaction();

        self::updateCartonsToPicked($app, $invIDs, $statusIDs);

        self::storePickingSplitChildren([
            'app' => $app,
            'pickingSplits' => $pickingSplits,
            'children' => $children,
            'backToStockLocations' => $backToStock,
        ]);

        $app->commit();

        self::addCycleCounts($app, $cycleCountDataAllOrder);

        // Send email
        self::sendMail($dataSendingEmail);

        $cartons = $classes['cartons'];

        $inventoryUCCs = $cartons->getUCCs($invIDs);

        $results['processInventory'] = [];

        foreach ($processInventory as $orderNumber => $info) {
            foreach ($info['UCCs'] as $ucc) {
                $results['processInventory'][$orderNumber]['UCCs'][] =
                    $inventoryUCCs[$ucc];
            }

            $results['processInventory'][$orderNumber]['isPrintUCCEDI'] =
                $info['isPrintUCCEDI'];

            $results['processInventory'][$orderNumber]['isFromEDI'] =
                $info['isFromEDI'];
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function updateOrdersToPicked($app, $orders, $orderNumbers)
    {
        $ordersPassed = $orders->getIDByOrderNumber($orderNumbers);

        $statuses = new \tables\statuses\orders($app);

        $statusID = $statuses->getStatusID(\tables\orders::STATUS_PICKING_CHECK_OUT);

        order::updateAndLogStatus([
            'orderIDs' => array_column($ordersPassed, 'id'),
            'statusID' => $statusID,
            'tableClass' => $orders,
        ]);
    }

    /*
    ****************************************************************************
    */

    static function updateCartonsToPicked($app, $invIDs, $statusIDs)
    {
        $sql = 'UPDATE inventory_cartons
                SET    statusID = ?,
                       mStatusID = ?
                WHERE  id IN (' . $app->getQMarkString($invIDs) . ')';

        $pickedStatusID = $statusIDs[cartons::STATUS_PICKED]['id'];
        $rackedStatusID = $statusIDs[cartons::STATUS_RACKED]['id'];
        $reservedStatusID = $statusIDs[cartons::STATUS_RESERVED]['id'];

        $params = array_merge([$pickedStatusID, $pickedStatusID], $invIDs);

        $app->runQuery($sql, $params);

        logger::edit([
            'db' => $app,
            'primeKeys' => $invIDs,
            'fields' => [
                'statusID' => [
                    'fromValues' => array_fill(0, count($invIDs), $rackedStatusID),
                    'toValues' => $pickedStatusID,
                ],
                'mStatusID' => [
                    'fromValues' => array_fill(0, count($invIDs), $reservedStatusID),
                    'toValues' => $pickedStatusID,
                ],
            ],
            'transaction' => FALSE,
        ]);
    }

    /*
    ****************************************************************************
    */

    static function getPickingInventory($data)
    {
        $app = $data['app'];
        $values = $data['values'];
        $orderNumber = $data['orderNumber'];
        $warehouseType = $data['warehouseType'];
        $classes = $data['classes'];
        $processInventory = $data['processInventory'];
        $invIDs = $data['invIDs'];
        $parentUCCs = $data['parentUCCs'];
        $children = $data['children'];
        $orderInfo = self::getNewOrderInfoByID($app, $orderNumber);

        $results = self::getPickingData([
            'post' => [
                'orderNumber' => $orderNumber,
                'tableData' => $values,
            ],
            'tables' => $classes,
            'ucc128' => $classes['cartons']->fields['ucc128']['select'],
            'returnInventory' => $warehouseType,
        ]);

        if ($results['errors'] || $results['orderProducts']['error']) {

            $errors = getDefault($results['errors'], []);
            $productsErrors = getDefault($results['orderProducts']['error'], []);

            return [
                'errors' => array_merge($errors, $productsErrors),
                'processInventory' => NULL,
                'invIDs' => NULL,
            ];
        }

        foreach ($results['orderProducts']['inventory'] as $orderNumber => $inventory) {

            $array = getDefault($processInventory[$orderNumber], []);

            $processInventory[$orderNumber]['UCCs'] = array_merge($array, $inventory);

            $invIDs = array_merge($invIDs, $inventory);
        }

        if ($orderInfo['clientCode'] == vendors::VENDOR_CODE_GO_LIVE_WORK) {
            $processInventory[$orderNumber]['isPrintUCCEDI'] =
                $orderInfo['edi'] ?  ! $orderInfo['isPrintUccEdi'] : TRUE;
        } else {
            $processInventory[$orderNumber]['isPrintUCCEDI'] = TRUE;
        }

        $processInventory[$orderNumber]['isFromEDI'] = $orderInfo['edi'];

        if ($results['parentUCCs']) {
            $parents = getDefault($parentUCCs[$orderNumber], []);

            $parentUCCs[$orderNumber] =
                array_merge($parents, $results['parentUCCs']);
            $children[$orderNumber] = $results['children'];
        }

        return [
            'errors' => FALSE,
            'processInventory' => $processInventory,
            'invIDs' => $invIDs,
            'children' => $children,
            'parentUCCs' => $parentUCCs,
        ];
    }

    /*
    ****************************************************************************
    */

    static function getPickingData($data)
    {
        $wavePicks = $data['tables']['wavePicks'];
        $cartons = $data['tables']['cartons'];
        // the 1-st attempt to create a Pick Ticket
        $results = $wavePicks->createPickTicket($data);

        if (getDefault($results['errors'])) {
            return [
                'errors' => $results['errors'],
                'orderProducts' => [],
                'children' => [],
                'parentUCCs' => [],
            ];
        }

        $splitProducts = getDefault($results['splitProducts'], []);

        if (! $splitProducts) {
            // a Pick Ticket was created without cartons split
            return [
                'errors' => [],
                'orderProducts' => $results,
                'children' => [],
                'parentUCCs' => [],
            ];
        }

        $splitData = $children = [];

        foreach ($splitProducts as $splitProduct) {
            foreach ($splitProduct as $splits) {
                $splitData = self::getCartonSplitData($splits, $splitData);
            }
        }
        // splitting cartons for the 2-nd attempt to create a Pick Ticket
        $splitResult = $cartons->split($splitData);

        if (getDefault($splitResult['error'])) {
            return [
                'errors' => $splitResult['error'],
                'orderProducts' => [],
                'children' => [],
                'parentUCCs' => [],
            ];
        }
        // the 2-nd attempt to create a Pick Ticket (after cartons split)
        $afterSplitResults = $wavePicks->createPickTicket($data);

        if (getDefault($afterSplitResults['errors'])) {
            return [
                'errors' => $afterSplitResults['errors'],
                'orderProducts' => [],
                'children' => [],
                'parentUCCs' => [],
            ];
        }

        foreach ($splitResult['combined'] as $splitValues) {

            $first = array_slice($splitValues, 0, 1);
            $childOne = reset($first);

            $second = array_slice($splitValues, 1, 1);
            $childTwo = reset($second);

            $children[] = $childOne['invID'];
            $children[] = $childTwo['invID'];
        }

        return [
            'errors' => [],
            'orderProducts' => $afterSplitResults,
            'children' => $children,
            'parentUCCs' => array_keys($splitData),
        ];
    }

    /*
    ****************************************************************************
    */

    static function getCartonSplitData($splits, $splitData)
    {
        foreach ($splits as $split) {

            $ucc = $split['ucc128'];

            $splitData[$ucc] = [
                $split['portionOne'],
                $split['portionTwo'],
            ];
        }

        return $splitData;
    }

    /*
    ****************************************************************************
    */

    static function getProcessDescription($data)
    {
        $app = $data['app'];
        $classes = $data['classes'];
        $orderNumber = $data['scans'];
        $useTracking = $app->results['useTracking'] = $data['useTracking'];

        $onlineOrders = $classes['onlineOrders'];
        $cartons = $classes['cartons'];

        $requested = $onlineOrders->getOrderRequestedInventory($orderNumber,
                $useTracking);
        $shipped = $cartons->getOrderShippedInventory($orderNumber);

        foreach ($requested as &$upcInventory) {

            $upc = $upcInventory['upc'];

            $shippedQuantity = getDefault($shipped[$upc], 0);

            if ($shippedQuantity > 0) {

                $upcInventory['shipped'] = getDefault($upcInventory['shipped'], 0) +
                        min($shippedQuantity, $upcInventory['requested']);

                $shipped[$upc] -= $upcInventory['shipped'];
            }
        }

        $app->results['description'] = $requested;
    }

    /*
    ****************************************************************************
    */

    static function getPickingSplitChildren($cartons, $invIDs, $children)
    {
        if (! $children) {
            return [];
        }

        $invKeys = array_flip($invIDs);

        $inventory = [];

        foreach ($children as $orderNumber => $values) {
            foreach ($values as $key => $invID) {
                if (isset($invKeys[$invID])) {
                    unset($children[$orderNumber][$key]);
                }
            }

            if ($children[$orderNumber]) {

                $children[$orderNumber] = array_values($children[$orderNumber]);

                $inventory = array_merge($inventory, $children[$orderNumber]);
            } else {
                unset($children[$orderNumber]);
            }
        }

        if (! $inventory) {
            return [];
        }

        $results = $cartons->getOldCartonInfo($inventory);

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function storePickingSplitChildren($data)
    {
        $app = $data['app'];
        $pickingSplits = $data['pickingSplits'];
        $children = $data['children'];
        $backToStock = $data['backToStockLocations'];

        if (! $pickingSplits) {
            return;
        }

        foreach ($pickingSplits as $invID => $value) {

            $backToStockLocation = self::cartonBackToStockLocation($children,
                    $invID, $backToStock);

            $sql = 'UPDATE    inventory_cartons
                    SET       locID = ?
                    WHERE     id = ?';

            $app->runQuery($sql, [$backToStockLocation, $invID]);

            logger::edit([
                'db' => $app,
                'primeKeys' => [$invID],
                'fields' => [
                    'locID' => [
                        'fromValues' => $value['locID'],
                        'toValues' => $backToStockLocation,
                    ],
                ],
                'transaction' => FALSE,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    static function cartonBackToStockLocation($children, $invID, $backToStock)
    {
        foreach ($children as $orderNumber => $orderInventory) {

            $keys = array_flip($orderInventory);

            if (isset($keys[$invID])) {
                return $backToStock[$orderNumber];
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function createProcessingPlates($app, $platesRequired)
    {
        $labelsCreated = labelMaker::inserts([
            'model' => new \tables\_default($app),
            'userID' => \access::getUserID(),
            'quantity' => intVal($platesRequired),
            'labelType' => 'plate',
        ]);

        if (! $labelsCreated) {
            return [
                'errors' => [
                    'Error creating License Plates'
                ],
                'licensePlates' => NULL,
            ];
        }

        $sql = 'SELECT    id
                FROM      licenseplate
                ORDER BY  id DESC
                LIMIT ' . intVal($platesRequired);

        $labelResults = $app->queryResults($sql);

        return [
            'errors' => FALSE,
            'licensePlates' => array_keys($labelResults),
        ];
    }

    /*
    ****************************************************************************
    */

    static function getCycleCounts($dataInput)
    {
        $users = $dataInput['classUser'];
        $cycleCountData = [];
        $orderInfo = $dataInput['orderInfo'][$dataInput['orderNumber']];
        $cycleCountDataAllOrder = &$dataInput['cycleCountDataAllOrder'];

        foreach ($dataInput['values'] as $value) {
            $assignToID = $value['cycleCountAssignedTo'];

            if (! isset($value['pickingPieceQuantity'])) {
                continue;
            }

            if ($value['quantity'] < $value['pickingPieceQuantity']
            || $value['pickingPrimeLocation'] != $value['cartonLocation']) {

                $cycleCountDataAllOrder[$dataInput['vendorID']][] =
                $cycleCountData[$dataInput['vendorID']][] = [
                    'cycleCountAssignedTo' => $value['cycleCountAssignedTo'],
                    'cycleCountReportName' => $value['cycleCountReportName'],
                    'cycleCountDueDate' => $value['cycleCountDueDate'],
                    'location' => $value['pickingPrimeLocation'],
                    'mailInfo' => [
                        'emails' => [
                            $orderInfo['userCreated'],
                            $orderInfo['dcUser'],
                            $users->getUser($assignToID)['email'],
                        ],
                        'cycleCreatedBy' =>
                            $users->getUser(access::getUserID())['fullName'],
                        'dataInput' => [
                            $value['upc'],
                            $value['sku'],
                            $value['pickingPrimeLocation'],
                            $value['pickingPieceQuantity'],
                            $value['quantity'],
                            $value['cycleCountReportName'],
                            $users->getUser($assignToID)['fullName']
                        ],
                    ]
                ];
            }
        }

        return $cycleCountData;
    }

    /*
    ****************************************************************************
    */

    static function addCycleCounts($app, $cycleCounts)
    {
        if (! $cycleCounts) {
            return;
        }

        $cycleCount = new \tables\cycleCount\cycleCount($app);
        $vendors = new \tables\vendors($app);

        $vendorIDs = array_keys($cycleCounts);

        $vendorWarehouses = $vendors->getWarehouseByVendorIDs($vendorIDs);

        foreach ($cycleCounts as $vendorID => $values) {
            foreach ($values as $value) {

                $post = [
                    'reportName' => $value['cycleCountReportName'],
                    'description' => 'Picking Check Out Discrepancies',
                    'warehouseID' => $vendorWarehouses[$vendorID],
                    'dueDate' => $value['cycleCountDueDate'],
                    'assigned' => $value['cycleCountAssignedTo'],
                    'byType' => \tables\cycleCount\cycleCount::TYPE_LOCATION,
                    'byUOM' => 'carton',
                    'byColorSize' => 0,
                    'locationFrom' => $value['location'],
                    'locationTo' => $value['location'],
                    'sku' => FALSE,
                    'customer' => FALSE,
                    'createBy' => \access::getUserID(),
                    'status' => \tables\cycleCount\cycleCount::STATUS_ASSIGNED,
                ];
                // need to move validate and process Cycle Count functions out
                // of loops after refactoring processCreateNewCycleCount()
                $checkResults = $cycleCount->validateCycleDataInput($post);

                if ($checkResults['errors']) {
                    continue;
                }

                $cycleCount->processCreateNewCycleCount($post);
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function getOrderInfo($app, $orderNumber)
    {
        $userDB = $app->getDBName('users');

        $sql = 'SELECT  scanordernumber,
                        u.email AS userCreated,
                        dc.email AS dcUser,
                        CONCAT(u.firstName," ",u.lastName) AS userFullName,
                        CONCAT(dc.firstName," ",dc.lastName) AS dcFullName
                FROM    neworder n
                JOIN    ' . $userDB . '.info u ON u.id = n.userid
                JOIN    ' . $userDB . '.info dc ON dc.id = n.dcUserID
                WHERE   scanordernumber = ?';

        $results = $app->queryResults($sql, [$orderNumber]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function sendMail($mailInfo)
    {
        $emails = [];
        $tableTitle = $tableContent = NULL;

        foreach (self::$contentTitle as $item) {
            $tableTitle .= '<td>' . $item . '</td>';
        }

        foreach ($mailInfo as $orderNumber => $values) {
            $subject = 'Picking discrepancy in Order #' . $orderNumber;
            $message = 'Picking process for order #' . $orderNumber
                . ' has created a picking discrepancy. <br><br>';
            foreach ($values as $value) {
                foreach ($value as $row) {

                    $emails = array_merge($emails, $row['mailInfo']['emails']);
                    $tableContent .= '<tr>';
                    foreach ($row['mailInfo']['dataInput'] as  $item) {
                        $tableContent .= '<td>' . $item . '</td>';
                    }
                    $tableContent .= '</tr>';
                }
            }

            $message .= '<table border="1"><tr style="font-weight: bold;text-align: center;">'
                . $tableTitle . '</tr>' . $tableContent . '</table>';

            \PHPMailer\send::mail([
                'recipients' => array_unique($emails),
                'subject' => $subject,
                'body' => $message,
            ]);

        }
    }

    /*
    ****************************************************************************
    */

    static function getNewOrderInfoByID($app, $orderNumber)
    {
        $sql = 'SELECT  v.clientCode,
                        edi,
                        isPrintUccEdi
                FROM    neworder n
                JOIN    order_batches ob ON ob.id = n.order_batch
                JOIN    vendors v ON v.id = ob.vendorID
                WHERE   scanOrderNumber = ?';

        $results = $app->queryResult($sql, [$orderNumber]);

        return $results;
    }

    /*
    ****************************************************************************
    */

}
