<?php

namespace common;

use \common\order;

class automated
{

    public $app;

    public static $menu = [];

    public $scannerTitles = [
        'cancel' => 'Order Number',
        'routeIn' => 'Order Number',
        'racking' => 'License Plate',
        'setError' => 'Order Number',
        'routeOut' => 'Order Number',
        'pickingIn' => 'Order Number',
        'processIn' => 'Order Number',
        'receiving' => 'License Plate',
        'adjustment' => 'Location',
        'unsetError' => 'Order Number',
        'uccLocation' => 'Carton UCC',
        'plateQuantity' => 'License Plate',
        'plateLocation' => 'License Plate',
        'scanProcessOut' => 'Order Number',
        'waveProcessOut' => 'Order Number',
        'shippingCheckIn' => 'License Plate',
        'locationQuantity' => 'Location',
        'switchOrderBatch' => 'Batch Number',
        'shippingCheckOut' => 'License Plate',
        'confirmMezzanineTransfers' => 'Mezzanine Transfer',
    ];

    public $requestMethods = [
        'cancel' => 'processCancelScan',
        'routeIn' => 'processRouteInScan',
        'racking' => 'processRackingScan',
        'routeOut' => 'processRouteOutScan',
        'setError' => 'processSetErrorScan',
        'receiving' => 'processReceivingScan',
        'pickingIn' => 'processPickingInScan',
        'processIn' => 'processProcessInScan',
        'adjustment' => 'processAdjustmentScan',
        'unsetError' => 'processUnsetErrorScan',
        'uccLocation' => 'processUCCLocationScan',
        'plateQuantity' => 'processPlateQuantityScan',
        'plateLocation' => 'processPlateLocationScan',
        'scanProcessOut' => 'processScanProcessOutScan',
        'waveProcessOut' => 'processWaveProcessOutScan',
        'shippingCheckIn' => 'processShippingCheckInScan',
        'locationQuantity' => 'processLocationQuantityScan',
        'shippingCheckOut' => 'processShippingCheckOutScan',
        'switchOrderBatch' => 'processSwitchOrderBatchScan',
        'confirmMezzanineTransfers' => 'processConfirmMezzanineTransfersScan',
    ];

    public $completeMessages = [
        'cancel' => 'Order has been Cancelled',
        'routeIn' => 'Order has been set to  Routed Check-In',
        'racking' => 'License plate has been racked',
        'routeOut' => 'Order has been set to Routed Check-Out',
        'setError' => 'Order has been set to Error Order Status',
        'receiving' => 'Cartons have been stored to license plate',
        'pickingIn' => 'Order has been set to Picking Check-In',
        'processIn' => 'Order has been set to Order Process Check-In',
        'adjustment' => 'Location has been repopulated',
        'unsetError' => 'Order has been unset from Error Order Status',
        'uccLocation' => NULL,
        'plateLocation' => NULL,
        'plateQuantity' => NULL,
        'scanProcessOut' =>
            'Order and inventory have been set to Order Process Check-Out',
        'waveProcessOut' =>
            'Order and inventory have been set to Order Process Check-Out',
        'shippingCheckIn' => 'License plate has been set to Shipping Check-In',
        'shippingCheckOut' => 'License plate has been set to Shipped Check-Out',
        'locationQuantity' => NULL,
        'switchOrderBatch' => 'Orders have been moved to new batch number',
        'confirmMezzanineTransfers' => 'Mezzanine transfer was confirmed',
    ];

    public $endErrors = [
        'receiving' => 'Invalid Plate or Carton UCC',
        'adjustment' => 'Invalid Location, License Plate or Carton UCC',
        'uccLocation' => 'Invalid Carton UCC',
        'plateQuantity' => 'Invalid License Plate',
        'plateLocation' => 'Invalid License Plate',
        'locationQuantity' => 'Invalid Location',
        'shippingCheckOut' => 'Invalid License Plate',
        'scanProcessOut' => 'Invalid Order Number',
        'switchOrderBatch' => 'Invalid Batch Number or Order Number',
        'confirmMezzanineTransfers' => 'Invalid Mezzanine Transfer Number',
    ];

    public $titles = [
        'cancel' => 'Scan Order To Cancel',
        'routeIn' => 'Set Order To Routing Check-In',
        'racking' => 'Scan License Plate To Location',
        'routeOut' => 'Set Order To Routing Check-Out',
        'setError' => 'Set Order To Error Status',
        'receiving' => 'Receiving / Back To Stock',
        'pickingIn' => 'Set Order To Picking Check-In',
        'processIn' => 'Set Order To Order Process Check-In',
        'unsetError' => 'Usnet Order From Error Status',
        'adjustment' => 'Zero-out location and repopulate with new cartons',
        'uccLocation' => 'Find Carton Location',
        'plateQuantity' => 'Get number of Cartons on a License Plate',
        'plateLocation' => 'Get Location of License Plate',
        'shippingCheckIn' => 'Set Order License Plate To Shipping Check-In',
        'shippingCheckOut' => 'Set Order License Plate To Shipped Check-Out',
        'scanProcessOut' =>
            'Set Order To Order Process Check-Out By Scan Order Number',
        'waveProcessOut' => 'Set Order To Order Process Check-Out By Wave Pick',
        'locationQuantity' => 'Get number of Cartons at Location',
        'switchOrderBatch' => 'Move Orders To New Batch Nubmer',
        'confirmMezzanineTransfers' => 'Confirm Mezzanine Transfer',
    ];

    public $display = [
        'cancel' => 'Cancelled',
        'routeIn' => 'Routing Check-In',
        'setError' => 'Error Status',
        'routeOut' => 'Routing Check-Out',
        'pickingIn' => 'Picking Check-In',
        'processIn' => 'Order Process Check-In',
        'unsetError' => 'Non-error Status',
        'scanProcessOut' => 'Order Process Check-Out',
        'waveProcessOut' => 'Order Process Check-Out',
    ];

    public $scan;
    public $request;
    public $location;
    public $storedUCC;
    public $storedPlate;
    public $storedPlateCount;
    public $storedBatch;
    public $storedOrder;
    public $warehouseID;
    public $storedVendor;
    public $storedCartons;
    public $storedLocation;
    public $storedClientID;
    public $storedOrderClient;
    public $storedMezzanineTransfer;

    public $objects = [];

    public $done = FALSE;



    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        $this->app = $app;
    }

    /*
    ****************************************************************************
    * PIECES
    ****************************************************************************
    */

    function processPlateLocation($params)
    {
        $shippingCheckOut = getDefault($params['shippingCheckOut']);

        $this->getStored('plate');

        $this->call('checkPlate', [
            'skip' => $this->storedPlate,
            'shouldBeUsed' => TRUE
        ]);

        $this->call('checkLocation');

        $plateResult = $this->call('checkPlateLocation');

        $this->call('updatePlateLocation', [
            'shippingCheckOut' => $shippingCheckOut,
            'plateLocations' => $plateResult,
        ]);

    }

    /*
    ****************************************************************************
    */

    function checkBatch()
    {
        if ($this->storedBatch) {
            return;
        }

        $batch = $this->scan;

        $vendors = $this->getObj('vendors');

        // Confirm Valid Plate
        $clientID = $vendors->getByBatchNumber($batch);
        if (! $clientID) {
            $error = 'Batch Number Not Found: '.$batch;
            return $this->error($error);
        }

        $this->setSessionValue('batch', $batch);

        $this->setSessionValue('clientID', $clientID);

        $this->setResult('next', 'Order Number(s)');
    }

    /*
    ****************************************************************************
    */

    function checkPlate($params=[])
    {
        $this->getStored('plate');

        $skip = getDefault($params['skip']);

        if ($skip) {
            return;
        }

        $shouldBeUsed = getDefault($params['shouldBeUsed']);

        $scan = $this->scan;

        $plates = $this->getObj('plates');

        // Confirm Valid Plate
        if (! $plates->validPlates([$scan])) {
            $error = 'License Plate Number Not Found: '.$scan;
            return $this->error($error);
        }

        // Make sure plate is not used
        $plateUsed = $plates->getInventoryPlates([$scan]) ? TRUE : FALSE;

        // Return an error if the plate is used and not supposed to be and
        // vise versa
        if ($shouldBeUsed != $plateUsed) {

            $messagee = $shouldBeUsed ? 'does not have inventory' :
                'has been used';

            $error = 'License Plate '.$scan.' '.$messagee.'.';

            return $this->error($error);
        }

        $this->setSessionValue('plate', $scan);

        $noNext = getDefault($params['noNext']);
        if ($noNext) {
            return;
        }

        $next = $shouldBeUsed ? 'Location' : 'Carton UCC(s) or License Plate';

        $next = isset($params['customNext']) ? $params['customNext'] : $next;

        $this->setResult('next', $next);
    }

    /*
    ****************************************************************************
    */

    function checkPlateCount($params=[])
    {
        $this->getStored('plateCount');

        $skip = getDefault($params['skip']);

        if ($skip) {
            return;
        }

        $orderNumber = $this->storedOrder;
        $scan = $this->scan;

        $cartons = $this->getObj('cartons');

        $results = $cartons->getProcessingCartonCount([$orderNumber]);

        // Confirm Valid Plate Count
        if ($scan > $results[$orderNumber]) {

            $error = 'Number of entered License Plates can not be greater than '
                    . 'number of cartons';

            return $this->error($error);
        }

        $this->setSessionValue('plateCount', $results);

        $this->setResult('next', 'Order Number to Close');
    }

    /*
    ****************************************************************************
    */

    function checkAdjustmentLocation()
    {
        $scan = $this->scan;
        $locations = $this->getObj('locations');

        $warehouses = $locations->getPossibleWarehouseIDsByDisplayNames($scan);

        if (! $warehouses) {
            return;
        }

        $this->setResult('next', 'License Plate');
        $this->setSessionValue('location', $scan);
        $result = $this->setSessionValue('warehouses', $warehouses);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkLocation($params=[])
    {
        // Default skip value is if there is no stored plate
        $skip = isset($params['skip']) ? $params['skip'] : ! $this->storedPlate;

        if ($skip) {
            return;
        }

        $shippingRequired = getDefault($params['shippingRequired']);

        $scan = $this->scan;
        $locations = $this->getObj('locations');

        $results = $locations->search([
            'term' => $scan,
            'search' => 'displayName',
            'oneResult' => TRUE,
        ]);

        if ($shippingRequired && ! $results['isShipping']) {
            return $this->error('Locations '.$scan.' is not for shipping');
        }

        if ($results) {
            return;
        }

        $this->unsetSession('plate');
        $error = $this->error('Locations '.$scan.' Not Found');

        return $error;
    }

    /*
    ****************************************************************************
    */

    function checkBatchOrders()
    {
        $scan = $this->scan;
        $vendors = $this->getObj('vendors');

        $orderVendor = $vendors->getByScanOrderNumber([$scan]);

        if (! $orderVendor) {
            return $this->error('Order Not Found');
        }

        $clientID = getDefault($orderVendor[$scan]);

        $this->getStored('clientID');

        if ($clientID != $this->storedClientID) {
            $error = 'This order has a different client than the batch selected';
            return $this->error($error);
        }

        $_SESSION['scanInput']['orders'][$scan] = TRUE;
        $next = 'Order Numbers(s) or Closing Batch Number';
        $batchOrder = $this->setResult('next', $next);

        return $batchOrder;
    }

    /*
    ****************************************************************************
    */

    function checkUCC($params=[])
    {
        // Default skip value
        // No need to check if there is no plate
        $skip = isset($params['skip']) ? $params['skip'] : ! $this->storedPlate;

        if ($skip) {
            return;
        }

        $scan = $this->scan;
        $vendors = $this->getObj('vendors');

        $cartonClients = $vendors->getIDByUCCs([$scan], 'fullVendorName');

        if (! $cartonClients) {
            return;
        }

        if (! $this->checkMatchingClients($cartonClients)) {
            return;
        }

        $this->storedCartons = [$scan];

        $_SESSION['scanInput']['uccs'][$scan] = TRUE;

        $noNext = getDefault($params['noNext']);
        if ($noNext) {
            return;
        }

        $result = $this->setResult('next', 'Carton UCC(s) or License Plate');

        return $result;
    }

    /*
    ****************************************************************************
    */

    function checkMatchingClients($cartonClients)
    {
        $orderClient = $this->getSessionValue('orderClient');

        $cartonClient = reset($cartonClients);

        if ($orderClient && $orderClient != $cartonClient) {
            $this->error('Order and carton belong to different clients.');
            return FALSE;
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function checkMasterLabel($params=[])
    {
        $skip = isset($params['skip']) ? $params['skip'] : ! $this->storedPlate;

        if ($skip) {
            return;
        }

        $scan = $this->scan;
        $vendors = $this->getObj('vendors');
        $cartons = $this->getObj('cartons');

        $masterResults = $cartons->masterResults([$scan]);

        $master = $masterResults['master'];

        // If not a valid UCC, check for master label
        if (! $master['batchNumber']) {
            return;
        }

        $results = $cartons->masterUCCs($master['batchNumber']);

        $this->storedCartons = $uccs = array_keys($results);

        $cartonClients = $vendors->getIDByUCCs($uccs, 'fullVendorName');

        if (! $this->checkMatchingClients($cartonClients)) {
            return;
        }

        foreach ($this->storedCartons as $ucc) {
            $_SESSION['scanInput']['uccs'][$ucc] = TRUE;
        }

        $noNext = getDefault($params['noNext']);
        if ($noNext) {
            return;
        }

        $masterLabel = $this->setResult('next', 'Carton UCC(s) or License Plate');

        return $masterLabel;
    }

    /*
    ****************************************************************************
    */

    function defaultOrderScanner()
    {
        $update = $required = [];

        switch ($this->request) {
            case 'routeIn':
                $update = [
                    'newStatus' => 'RTCI',
                    'field' => 'routedStatusID',
                ];
                break;
            case 'routeOut':
                $required = [
                    'value' => 'RTCI',
                    'target' => 'routedStatusID',
                ];
                $update = [
                    'newStatus' => 'RTCO',
                    'field' => 'routedStatusID',
                ];
                break;
            case 'pickingIn':
                $required = ['value' => 'WMCO'];
                $update = ['newStatus' => 'PKCI'];
                break;
            case 'processIn':
                $required = ['value' => 'PKCO'];
                $update = ['newStatus' => 'OPCI'];
                break;
            case 'setError':
                $required = [
                    'value' => 'No Error',
                    'target' => 'noInventory',
                    'errorOrHold' => TRUE,
                ];
                $update = [
                    'newStatus' => 'LOIN',
                    'field' => 'isError',
                ];
                break;
            case 'unsetError':
                $required = [
                    'value' => 'Error',
                    'target' => 'noInventory',
                    'errorOrHold' => TRUE,
                ];
                $update = [
                    'newStatus' => 'ENIN',
                    'field' => 'isError',
                ];
                break;
            case 'unsetError':
                $required = [
                    'value' => 'Error',
                    'target' => 'noInventory',
                    'errorOrHold' => TRUE,
                ];
                $update = [
                    'newStatus' => 'ENIN',
                    'field' => 'isError',
                ];
                break;
            case 'cancel':
                $update = [
                    'newStatus' => 'CNCL',
                    'field' => 'statusID',
                ];
                break;
            default:
                return;
        }

        $this->getStored('order');

        $this->checkForClosingOrder($update);

        $this->call('checkValidOrder', [
            'update' => $update,
            'requiredStatus' => $required,
        ]);
    }

    /*
    ****************************************************************************
    */

    function checkValidOrder($params=[])
    {
        $skip = getDefault($params['skip']);

        if ($skip) {
            return;
        }

        $orderNumber = $this->scan;

        $orders = $this->getObj('orders');

        $newStatus = getDefault($params['update']['newStatus']);

        $requiredStatus = getDefault($params['requiredStatus']);

        // If validating these types don't check
        $errorOrHold = getDefault($params['requiredStatus']['errorOrHold']);

        // Check that the order exists
        $results = $orders->search([
            'term' => $orderNumber,
            'search' => 'scanOrderNumber',
        ]);

        if (! $results) {
            return $this->error('Order not found');
        }

        $order = reset($results);

        $orderClient = getDefault($order['vendor']);
        if (! $orderClient) {
            return $this->error('This order does not belong to a client');
        }

        $this->setSessionValue('orderClient', $orderClient);

        // Check for correct status if one is required
        if ($requiredStatus) {

            $target = getDefault($requiredStatus['target'], 'statusID');

            $orderStatus = $order[$target];

            if ($orderStatus != $requiredStatus['value']) {
                $requiredMessage = 'Only '.$requiredStatus['value']
                    . ' orders can be set to '.$newStatus;

                $wrongStatus = $orderNumber.' status is set to '.$orderStatus
                    . '. '.$requiredMessage;
                $statusUnset = $orderNumber.' status is not set. '
                    . $requiredMessage;

                $error = $orderStatus ? $wrongStatus : $statusUnset;

                return $this->error($error);
            }
        }

        if (! $errorOrHold) {

            $errorOrder = $orders->onHoldOrError([
                'order' => $orderNumber,
                'select' => 'isError',
            ]);

            if ($errorOrder) {
                return $this->error('Order is set to Error Status');
            }

            $onHold =  $orders->onHoldOrError([
                'order' => $orderNumber
            ]);

            if ($onHold) {
                return $this->error('Order is set to Error Status');
            }
        }

        $this->call('checkIfOrderCanBeCancelled', $orders);

        if ($this->done) {
            return;
        }

        $this->setSessionValue('order', $orderNumber);

        $next = getDefault($params['next'], 'Order again to Confirm');

        $this->setResult('next', $next);
    }

    /*
    ****************************************************************************
    */

    function checkIfOrderCanBeCancelled($orders)
    {
        if ($this->request != 'cancel') {
            return;
        }

        $order = $this->scan;

        $checkResults = $orders->checkIfOrderProcessed($order);

        $isClosed = $checkResults['processedOrders'][$order];

        if ($isClosed) {

            $message = $checkResults['canceledOrders'][$order] ?
                    'already been canceled.' : 'been processed.';

            return $this->error('Order can not be cancelled. It has ' . $message);
        }

        $wavePicks = $this->getObj('wavePicks');

        $wavePicks->clear([$order]);
    }

    /*
    ****************************************************************************
    */

    function checkPlateLocation()
    {
        $scan = $this->scan;
        $plate = $this->storedPlate;
        $plates = $this->getObj('plates');

        $valid = $plates->checkLocationValues([$plate => $scan]);

        $this->error('License plate not found.', ! $valid);

        return $valid;
    }

    /*
    ****************************************************************************
    */

    function endError()
    {
        if ($this->done) {
            return;
        }

        $this->unsetSession();
        $error = $this->endErrors[$this->request];
        $this->error($error);
    }

    /*
    ****************************************************************************
    * CLOSERS
    ****************************************************************************
    */

    function checkForClosingOrder($params)
    {
        if (! $this->storedOrder) {
            return;
        }

        // Close and update if the stored plate matches the scan
        if ($this->storedOrder != $this->scan) {
            return $this->error('Order scanned did not match.');
        }

        $field = getDefault($params['field'], 'statusID');
        $newStatus = $params['newStatus'];

        $orders = $this->getObj('orders');
        $statuses = $this->getObj('statuses');

        $newStatusID = $statuses->getStatusID($newStatus);

        order::getIDs($orders->app, $this->storedOrder);

        order::updateAndLogStatus([
            'statusID' => $newStatusID,
            'field' => $field,
            'tableClass' => $orders,
        ]);

        $complete = $this->complete();

        return $complete;
    }

    /*
    ****************************************************************************
    */

    function checkForInnerClosingPlate($next)
    {
        if ($this->storedPlate != $this->scan) {
            return;
        }

        $this->storePlateCartons();

        $this->setSessionValue('closingPlate', $this->scan);
        $this->setResult('next', $next);
    }

    /*
    ****************************************************************************
    */

    function storePlateCartons()
    {
        if ($this->request != 'processIn') {
            return;
        }

        $plateCartons = $this->getSessionValue('plateCartons');

        $uccs = $this->getSessionValue('uccs');

        $plate = $this->storedPlate;

        $plateCartons[$plate] = $uccs;

        $this->setSessionValue('uccs', []);

        $this->setSessionValue('plateCartons', $plateCartons);

        $this->unsetSession('plate');
    }

    /*
    ****************************************************************************
    */

    function checkOPClosingOrder()
    {
        // Close and update if the stored order matches the scan
        if ($this->storedOrder != $this->scan) {
            return;
        }

        $this->storedPlateCount = $this->getStored('plateCount');

        $orderNumber = $this->storedOrder;
        $platesEntered = $this->storedPlateCount;

        if (! $platesEntered) {
            return $this->error('No license plate count scanned.');
        }

        $orders = $this->getObj('orders');

        $orderID = $orders->getIDByOrderNumber($orderNumber);

        $this->results = scanner::orderProcessingCheckOut([
            'app' => $this->app,
            'ordersPassed' => [
                $orderNumber => $orderID
            ],
            'platesEntered' => $platesEntered,
            'classes' => [
                'cartons' => $this->getObj('cartons'),
                'orderStatuses' => $this->getObj('orderStatuses'),
                'cartonStatuses' => $this->getObj('cartonStatuses'),
            ],
        ]);

        $complete = $this->complete();

        return $complete;
    }

    /*
    ****************************************************************************
    */

    function checkForAdjustmentClosingLocation()
    {
        // Close and update if the stored plate matches the scan
        if ($this->storedLocation != $this->scan) {
            return;
        }

        if (! $this->getSessionValue('closingPlate')) {
            return $this->error('No closing license plate scanned.');
        }

        $adjustments = $this->getObj('adjustments');

        $scanner = $this->getObj('scanner');

        // Recreate scan array
        $uccKeys = $this->getSessionValue('uccs');

        $scans = array_keys($uccKeys);

        array_push($scans, $this->storedPlate);
        array_unshift($scans, $this->storedPlate);

        array_push($scans, $this->storedLocation);
        array_unshift($scans, $this->storedLocation);

        $this->getStored('clientID');

        $classes = [
            'locations' => $this->getObj('locations'),
            'cartons' => $this->getObj('cartons'),
            'plates' => $this->getObj('plates'),
            'scanner' => $scanner,
        ];

        $checkResults = $adjustments->checkAdjustScannerInput($scans, $classes);

        $scanAdjust = $checkResults['scanAdjust'];

        $adjustments->process($scanAdjust);

        $complete = $this->complete();

        return $complete;
    }

    /*
    ****************************************************************************
    */

    function updatePlateLocation($params)
    {
        $plateLocations = $params['plateLocations'];
        $shippingCheckOut = getDefault($params['shippingCheckOut'], TRUE);

        $plates = $this->getObj('plates');
        $cartons = $this->getObj('cartons');

        $plate = $this->storedPlate;
        $plateLocations = $shippingCheckOut ? [
               $plate => $this->scan
        ] : $params['plateLocations'];

        $shippingCheckOut ?
            $cartons->updateShipStatus([
                'updates' => $plateLocations,
                'statuses' => [
                    'order' => 'LSCI',
                    'carton' => 'LS',
                ],
            ]) :

            $plates->updateLocationsByPlates($plateLocations);

        $complete = $this->complete();

        return $complete;
    }

    /*
    ****************************************************************************
    */

    function checkLocationCartonVendor()
    {
        // Check that the location has the same warehouse as the vendors
        $uccs = $this->getSessionValue('uccs');

        if (! $uccs) {
            return;
        }

        $vendors = $this->getObj('vendors');

        $cartons = array_keys($uccs);

        $uccVendors = $vendors->getIDByUCCs($cartons);

        if (count($uccVendors) > 1) {
            return $this->error('Cartons submitted belong to multiple clients.');
        }

        $clientID = reset($uccVendors);

        $this->setSessionValue('clientID', $clientID);

        $warehouse = $vendors->getVendorWarehouse($clientID);

        $locationWarehouses = $this->getSessionValue('warehouses');

        if (! isset($locationWarehouses[$warehouse])) {
            $error = 'Location and Cartons submitted are in different warehouses';
            return $this->error($error);
        }
    }

    /*
    ****************************************************************************
    */

    function getUCCLocation($uccs)
    {
        if (! $uccs) {
            return;
        }

        $uccKeys = array_keys($uccs);

        $cartons = $this->getObj('cartons');

        $results = $cartons->search([
            'glue' => 'OR',
            'term' => $uccKeys,
            'search' => $cartons->fields['ucc128']['select'],
        ]);

        // Make sure there is only one location
        $locations = [];
        $location = FALSE;

        foreach ($results as $row) {
            $location = $row['locID'];
            $locations[$location] = TRUE;
        }

        $error = 'This master label represents cartons at multiple locations';
        if (count($locations) > 1) {
            return $this->error($error);
        }

        if ($location) {
            $message = 'Carton Location Found: '.$location;
            $this->setResult('customMessage', $message);

            return $this->complete();
        }

        return $this->error('Carton Not Found');
    }

    /*
    ****************************************************************************
    */

    function getPlateLocation()
    {
        $locations = $this->getObj('locations');

        $location = $locations->getByPlate($this->scan);

        $message = $location ? $this->scan.': Location '.$location
            : 'License Plate '.$this->scan.' has not been Racked';

        $this->setResult('customMessage', $message);

        $complete = $this->complete();

        return $complete;
    }

    /*
    ****************************************************************************
    */

    function getPlateCount()
    {
        $cartonPlates = $this->getObj('cartonPlates');

        $result = $cartonPlates->search([
            'term' => $this->scan,
            'search' => 'plate',
            'oneResult' => TRUE,
        ]);

        $quantity = $result['initialCount'];

        $message = $this->scan.' Carton Quantity: '.$quantity;
        $this->setResult('customMessage', $message);

        $complete = $this->complete();

        return $complete;
    }

    /*
    ****************************************************************************
    */

    function getLocationCount()
    {
        $locations = $this->getObj('locations');

        $quantity = $locations->getLocationCartonQuantity($this->scan);

        $message = $this->scan.' Carton Quantity: '.$quantity;
        $this->setResult('customMessage', $message);

        $complete = $this->complete();

        return $complete;
    }

    /*
    ****************************************************************************
    */

    function checkForClosingPlate()
    {
        $storedPlate = $this->storedPlate;

        // Close and update if the stored plate matches the scan
        if ($storedPlate != $this->scan) {
            return;
        }

        $cartons = $this->getObj('cartons');

        $scans = $this->getSessionValue('uccs');

        $uccs = array_keys($scans);

        $results = $cartons->masterLabelToCarton($uccs);

        $cartons->receivingToStock([
            $storedPlate => [
                'cartonNumbers' => $results['returnScanArray']
            ]
        ]);

        $complete = $this->complete();

        return $complete;
    }

    /*
    ****************************************************************************
    */

    function checkForClosingBatch()
    {
        $this->getStored('batch');

        $storedBatch = $this->storedBatch;

        // Close and update if the stored plate matches the scan
        if ($storedBatch != $this->scan) {
            return;
        }

        $scans = $this->getSessionValue('orders');

        if (! $scans) {
            return $this->error('No orders were submitted.');
        }

        $orders = array_keys($scans);
        $orderBatches = $this->getObj('orderBatches');

        // Recreate scan array
        array_push($orders, $storedBatch);
        array_unshift($orders, $storedBatch);

        $results = $orderBatches->getCheckInArray($orders);

        // Check for errrors
        $error = getDefault($results['errMsg']);
        if ($error) {
            return $this->error($error);
        }

        $orderBatches->updateBatch($results['batches'],
            $results['wavePicks']);

        $complete = $this->complete();

        return $complete;
    }

    /*
    ****************************************************************************
    * MAINFUNCTIONS
    ****************************************************************************
    */

    function processShippingCheckOutScan()
    {
        $this->getStored('plate');

        $this->shipPlate();

        $this->call('checkPlate', [
            'skip' => FALSE,
            'customNext' => 'License Plate again to Confirm',
            'shouldBeUsed' => TRUE
        ]);
    }

    /*
    ****************************************************************************
    */

    function shipPlate()
    {
        $plate = $this->storedPlate;

        if (! $this->storedPlate) {
            return;
        }

        // Close and update if the stored plate matches the scan
        if ($this->storedPlate != $this->scan) {
            return $this->error('License Plate scanned did not match.');
        }

        $cartons = $this->getObj('cartons');

        $statuses = [
            'order' => 'SHCO',
            'carton' => 'SH',
        ];

        $plateAsKey = [$plate => TRUE];

        $cartons->updateShipStatus([
            'updates' => $plateAsKey,
            'statuses' => $statuses,
            'updateLoc' => FALSE,
        ]);

        $cartons->getShippedStatuses($plateAsKey);

        \common\scanner::sendShippingMail($statuses['carton'], $plateAsKey);

        $complete = $this->complete();

        return $complete;
    }

    /*
    ****************************************************************************
    */

    function processShippingCheckInScan()
    {
        $this->processPlateLocation(['shippingCheckOut' => TRUE]);
    }

    /*
    ****************************************************************************
    */

    function processRackingScan()
    {
        $this->processPlateLocationScan();
    }

    /*
    ****************************************************************************
    */

    function processPlateLocationScan()
    {
        $this->call('checkPlate', [
            'skip' => FALSE,
            'noNext' => TRUE,
            'shouldBeUsed' => TRUE
        ]);

        $this->call('getPlateLocation');
    }

    /*
    ****************************************************************************
    */

    function processPlateQuantityScan()
    {
        $this->call('checkPlate', [
            'skip' => FALSE,
            'noNext' => TRUE,
            'shouldBeUsed' => TRUE
        ]);

        $this->call('getPlateCount');
    }

    /*
    ****************************************************************************
    */

    function processLocationQuantityScan()
    {
        $this->call('checkLocation', ['skip' => FALSE]);

        $this->call('getLocationCount');
    }

    /*
    ****************************************************************************
    */

    function processUCCLocationScan()
    {
        $this->getStored('ucc');

        $params = [
            'noNext' => TRUE,
            'skip' => $this->storedUCC,
        ];

        $this->call('checkUCC', $params);

        $this->call('checkMasterLabel', $params);

        $this->getStored('ucc');

        $uccs = $this->getSessionValue('uccs');

        $this->getUCCLocation($uccs);
    }

    /*
    ****************************************************************************
    */

    function processScanProcessOutScan()
    {
        $this->processOuts();
    }

    /*
    ****************************************************************************
    */

    function processWaveProcessOutScan()
    {
        $this->processOuts('wavePickID');
    }

    /*
    ****************************************************************************
    */

    function processOuts1($type='scanordernumber')
    {
        $this->sessionPush('scans', $this->scan);

        $this->getStored('order');
        $this->getStored('plate');

        $this->call('checkOPClosingOrder', $type);

        $this->call('checkForInnerClosingPlate', 'Order or Next License Plate');

        $this->call('checkValidOrder', [
            'next' => 'License Plate',
            'skip' => $this->storedOrder,
            'update' => ['newStatus' => 'OPCO'],
            'requiredStatus' => ['value' => 'OPCI'],
        ]);

        $this->getStored('plate');

        $this->call('checkPlate', [
            'skip' => $this->storedPlate || ! $this->storedOrder
        ]);

        $this->call('checkUCC');

        $this->call('checkMasterLabel');
    }

    /*
    ****************************************************************************
    */

    function processOuts($type='scanordernumber')
    {
        $this->getStored('order');
        $this->getStored('plate');

        $this->call('checkOPClosingOrder', $type);

        $this->call('checkValidOrder', [
            'next' => 'License Plate Count',
            'skip' => $this->storedOrder,
            'requiredStatus' => ['value' => 'OPCI'],
        ]);

        $this->getStored('plate');

        $this->call('checkPlateCount', [
            'skip' => $this->storedPlate || ! $this->storedOrder
        ]);
    }

    /*
    ****************************************************************************
    */

    function processAdjustmentScan()
    {
        $this->getStored('location');
        $this->getStored('plate');

        $this->call('checkForAdjustmentClosingLocation');

        $this->call('checkForInnerClosingPlate', 'Location');

        $this->call('checkAdjustmentLocation');

        $this->getStored('plate');

        $this->call('checkPlate', [
            'skip' => $this->storedPlate || ! $this->storedLocation
        ]);

        $this->call('checkUCC');

        $this->call('checkMasterLabel');

        $this->checkLocationCartonVendor();
    }

    /*
    ****************************************************************************
    * GETTER SETTERS
    ****************************************************************************
    */

    function complete()
    {
        $this->done = TRUE;
        $this->unsetSession();
        $this->setResult('complete');
    }

    /*
    ****************************************************************************
    */

    static function showTitle($app, $title=NULL)
    { ?>
        <div id="scannerTitleContainer" style="background-image:
            url('<?php echo $app->imageDir.'/smallLogoBG.png'; ?>');">
            <img id="smallLogo" src="<?php echo $app->imageDir.'/smallLogo.png'; ?>">
                <span id="mobileTitle"><?php echo $title; ?></span>
        </div><?php
    }

    /*
    ****************************************************************************
    */

    static function mobileLink($title='Go Back', $link=FALSE)
    {
        $link = $title == 'Sign Out' ? makeLink('logout') : $link;

        ?><a href="<?php echo $link; ?>" class="mobileMenuLink">
            <?php echo $title; ?>
        </a><br><?php
    }

    /*
    ****************************************************************************
    */

    static function getMenuHTML($app)
    {
        $menu = self::getMenu();

        $submenu = getDefault($app->get['submenu']);

        $title = isset($menu[$submenu]) ? $menu[$submenu]['display'] :
            'Mobile Menu';

        ob_start();

        self::showTitle($app, $title);

         ?>

        <div id="scannerMenu"><?php
        if ($submenu) {
            foreach ($menu[$submenu]['pages'] as $page => $data) {
                $pageArray = is_array($data);

                $link = $pageArray ? $data['link'] :
                makeLink('scanners', 'automated', ['request' => $page]);

                $title = $pageArray ? $data['title'] : $data;

                self::mobileLink($title, $link);
            }

            $link = makeLink('main', 'mobileMenu');
            self::mobileLink('Go Back', $link);

        } else {
            $link = makeLink('main', 'mobileMenu', ['old' => 'menu']);
            self::mobileLink('OLD MENU', $link);

            foreach ($menu as $submenu => $row) {
                $link = makeLink('main', 'mobileMenu', ['submenu' => $submenu]);
                self::mobileLink($row['display'], $link);
            }

        }

        self::mobileLink('Sign Out'); ?>

        </div><?php

        return ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    static function getMenu()
    {
        if (self::$menu) {
            return self::$menu;
        }

        self::$menu = [
            'inventoryControl' => [
                'display' => 'Inventory Control',
                'pages' => [
                    'receiving' => 'Receiving / Back To Stock',
                    'racking' => 'Scan To Location',
                    'adjustment' => 'Adjust Cartons',
                    'switchOrderBatch' => 'Move Order To Batch',
                    'confirmMezzanineTransfers' => 'Confirm Mezzanine Transfer',
                ],
            ],
            'shipping' => [
                'display' => 'Shipping',
                'pages' => [
                    'routeIn' => 'Routed Checked-In',
                    'routeOut' => 'Routed Checked-Out',
                    'pickingIn' => 'Picking Checked-In',
                    'processIn' => 'Order Processing Check-In',
                    'scanProcessOut' => 'Order Processing Check-Out',
                    'waveProcessOut' => 'Wave Order Processing Check-Out',
                    'shippingCheckIn' => 'Shipping Check-In',
                    'shippingCheckOut' => 'Shipped Check-Out',
                    'setError' => 'Set Error Orders',
                    'unsetError' => 'Unset Error Orders',
                    'cancel' => 'Cancel Orders',
                ],
            ],
            'searches' => [
                'display' => 'Searches',
                'pages' => [
                    'uccLocation' => 'Search UCC\'s Location',
                    'plateQuantity' => 'Search License Plate\'s Carton Quantity',
                    'plateLocation' => 'Search Locations of License Plate',
                    'locationQuantity' => 'Search Location\'s Carton Quantity',
                    'locationInqueries' => [
                        'title' => 'Location Inqueries',
                        'link' => makeLink('scanners', 'locations'),
                    ],
                ],
            ],
        ];

        return self::$menu;

    }

    /*
    ****************************************************************************
    */

    function sessionPush($name, $value)
    {
        if (! isset($_SESSION['scanInput'][$name])) {
            return $_SESSION['scanInput'][$name] = [$value];
        }

        $_SESSION['scanInput'][$name][] = $value;
    }

    /*
    ****************************************************************************
    */

    function getStored($name)
    {
        switch ($name) {
            case 'ucc':
                $value = $this->storedUCC = $this->getSessionValue($name);
                break;
            case 'plate':
                $value = $this->storedPlate = $this->getSessionValue($name);
                break;
            case 'plateCount':
                $value = $this->storedPlateCount = $this->getSessionValue($name);
                break;
            case 'batch':
                $value = $this->storedBatch = $this->getSessionValue($name);
                break;
            case 'order':
                $value = $this->storedOrder = $this->getSessionValue($name);
                break;
            case 'cartons':
                $value = $this->storedCartons = $this->getSessionValue($name);
                break;
            case 'location':
                $value = $this->storedLocation = $this->getSessionValue($name);
                break;
            case 'clientID':
                $value = $this->storedClientID = $this->getSessionValue($name);
                break;
            case 'orderClient':
                $value = $this->storedOrderClient = $this->getSessionValue($name);
                break;
            case 'mezzanineTransfer':
                $value = $this->storedMezzanineTransfer = $this->getSessionValue($name);
                break;
        }

        return $value;
    }

    /*
    ****************************************************************************
    */

    function call($method, $params=[])
    {
        if (! $this->done) {
            $this->setSessionValue('lastMethod', $method);
            return $this->$method($params);
        }
    }

    /*
    ****************************************************************************
    */

    function error($value, $trigger=TRUE)
    {
        if (! $trigger) {
            return;
        }

        $this->done = TRUE;
        $this->unsetSession();
        $this->setResult('error', $value);
    }

    /*
    ****************************************************************************
    */

    function setResult($key, $value=TRUE)
    {
        $this->done = $key == 'next' ? TRUE : $this->done;

        $this->app->results[$key] = $value;
    }

    /*
    ****************************************************************************
    */

    function setSessionValue($names, $value)
    {
        if (! is_array($names)) {
            return $_SESSION['scanInput'][$names] = $value;
        }

        $key = array_shift($names);
        $subKey = array_shift($names);
        $subSubKey = array_shift($names);

        $_SESSION['scanInput'][$key][$subKey][$subSubKey] = $value;
    }

    /*
    ****************************************************************************
    */

    function unsetSession($name=FALSE)
    {
        if ($name) {
            unset($_SESSION['scanInput'][$name]);
        }

        unset($_SESSION['scanInput']);
    }

    /*
    ****************************************************************************
    */

    function getSessionValue($name=FALSE)
    {
        if ($name) {
            return getDefault($_SESSION['scanInput'][$name]);
        }

        return getDefault($_SESSION['scanInput']);
    }

    /*
    ****************************************************************************
    */

    function setObjects($objects)
    {
        $this->objects = $objects;
    }

    /*
    ****************************************************************************
    */

    function getObj($objectName)
    {
        return $this->objects[$objectName];
    }

    /*
    ****************************************************************************
    * READY FINISHED
    ****************************************************************************
    */

    function processSwitchOrderBatchScan()
    {
        $this->call('checkForClosingBatch');

        $this->call('checkBatch');

        $this->call('checkBatchOrders');
    }

    /*
    ****************************************************************************
    */

    function processReceivingScan()
    {
        $this->getStored('plate');

        $this->call('checkForClosingPlate');

        $this->call('checkPlate', [
            'skip' => $this->storedPlate,
        ]);

        $this->call('checkUCC');

        $this->call('checkMasterLabel');
    }

    /*
    ****************************************************************************
    */

    function ajax()
    {
        $app = $this->app;

        $scans = getDefault($app->post['scans']);
        $this->request = getDefault($app->post['request']);

        $this->setResult('scanInput', $scans);

        $app->results = [
            'next' => FALSE,
            'error' => FALSE,
            'complete' => FALSE,
        ];

        scanner::get($scans);

        $method = getDefault($this->requestMethods[$this->request]);

        $method or die;

        $methodExists = is_callable([$this, $method]);

        foreach ($scans as $scan) {
            // Reset done for next scan
            $this->done = FALSE;
            $this->scan = $scan;

            // Common scanner functionality is here
            $this->defaultOrderScanner();

            // When using common scanner, may not need request method
            $methodExists ? $this->$method() : NULL;

            $this->endError();
        }

        $session = $this->getSessionValue();

        $this->setResult('scanInput', $session);

        return $app->results;
    }

    /*
    ****************************************************************************
    * DISPLAY
    ****************************************************************************
    */

    function createDisplay()
    {
        // Unset the session when displaying a new scanner
        $this->unsetSession();

        $this->setAutomatedView();

        $this->app->errors = [];

        $this->app->success = FALSE;

        $this->app->scanOrders = $scanOrders = $this->app->getScans();

        $this->app->jsVars['urls']['automatedScanner'] =
            makeLink('appJSON', 'automatedScanner');

        $this->app->jsVars['request'] = getDefault($this->app->get['request']);

        $this->app->jsVars['scannerTitles'] = $this->scannerTitles;
    }

    /*
    ****************************************************************************
    */

    function getBackLinkAndLogout($request)
    {
        $menu = $this->getMenu();

        $fonndMenu = FALSE;

        foreach ($menu as $title => $submenu) {
            if (isset($submenu['pages'][$request])) {
                $fonndMenu = $title;
            }
        }

        $link = makeLink('main', 'mobileMenu', ['submenu' => $fonndMenu]); ?>

        <table id="goBackTable"><tr><td id="backLink"><?php
        $this->mobileLink('Go Back', $link); ?>
        </td><td id="signOutLink"><?php
        echo showLink('Sign Out', 'logout'); ?>
        </td></tr></table><?php

    }

    /*
    ****************************************************************************
    */

    function setAutomatedView()
    {
        $request = getDefault($this->app->get['request']);

        $title = getDefault($this->titles[$request]);
        $target = getDefault($this->scannerTitles[$request]);
        $message = getDefault($this->completeMessages[$request]);

        $title or die();

        ob_start();
        ?>
        <div class="centered">
        <h3><?php echo $title; ?></h3>
        <div class="warningMessage" style="display: none" id="processingDiv">
            Processing...</div>
        <div class="failedMessage" style="display: none"></div>
        <div class="showsuccessMessage" style="display: none">
            <?php echo $message; ?></div>
        </div>
        <form id="autoForm" method="post">
        <table id="scanner">
        <tr>
        <td id="instructions" colspan="2">
        Scan <span id="needToScan"><?php echo $target; ?></span>:  <br>
        </td>
        </tr>
        <tr>
            <td>
                <textarea id="autoScans" name="scans"></textarea>
            </td>
        </tr>
        </table>
        <div class="centered" style="display: none" id="errorDiv"></div><?php

        $this->getBackLinkAndLogout($request); ?>

        </form><?php

        $this->app->automatedScannerView = ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function processConfirmMezzanineTransfersScan()
    {
        $this->getStored('mezzanineTransfer');

        $transferNumber = $this->getSessionValue('transferNumber');

        $transfers = $this->getObj('transfers');

        $scan = $this->scan;

        if (! $transferNumber) {

            $checkBarcodes = $transfers->checkBarcodes([$scan]);

            if ($checkBarcodes) {
                return $this->errors = reset($checkBarcodes);
            }

            $this->setSessionValue('transferNumber', $scan);

            return $this->setResult('next', 'Carton Amount');
        }

        if (! ctype_digit($scan) || $scan <= 0) {
            return $this->error('Invalid Carton Amount was Scanned');
        }

        scanner::confirmMezzanineTransfer($transfers, [$transferNumber], [$scan]);

        $this->complete();
    }

    /*
    ****************************************************************************
    */

}
