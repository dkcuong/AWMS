<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use tables\plates;
use tables\orders;
use tables\statuses;
use tables\locations;
use tables\inventory\cartons;
use common\scanner;
use common\order;

class controller extends template
{

    public $errors = [];
    public $updates = [];

    /*
    ****************************************************************************
    */

    function confirmMezzanineTransfersScannersController()
    {
        $this->confirmed = $this->discrepant = $invalidAmounts = [];

        $this->quantity = getDefault($this->post['quantity']);
        $this->piecesPassed = getDefault($_SESSION['piecesPassed'], []);
        $transfersPassed = $this->transfersPassed
            = getDefault($_SESSION['transfersPassed'], []);

        $this->gunHtmlDisplays();

        $transfers = new \tables\transfers($this);

        $scans = $this->getScans();

        if ($scans) {
            if (count($scans) % 2) {
                return $this->errors[]
                    = 'Transfer Number Quantity does not equal Pieces Quantity';
            }

            while (! empty($scans)) {

                $transfersValues[] = array_shift($scans);

                $piecesValue = array_shift($scans);

                $piecesValues[] = $piecesValue;

                if (! ctype_digit($piecesValue) || $piecesValue <= 0) {
                    $invalidAmounts[] = $piecesValue;
                }
            }

            $this->errors = $transfers->checkBarcodes($transfersValues);

            if ($invalidAmounts) {
                $this->errors[] = 'Invalid Carton Amount(s):<br>'
                    . implode('<br>', $invalidAmounts);
            }

            if ($this->errors) {
                return $this->errors;
            }

            $_SESSION['transfersPassed'] = $transfersValues;
            $_SESSION['piecesPassed'] = $piecesValues;

            $this->modelForward('confirmMezzanineTransfers', 'confirm');
        }

        //**********************************************************************

        if ($this->step == 'confirm' && ! $this->quantity && ! $transfersPassed) {
            $this->modelForward('confirmMezzanineTransfers');
        }

        //**********************************************************************

        if ($this->quantity) {

            $this->noErrors = $this->quantity == count($transfersPassed);

            if (! $transfersPassed) {
                return;
            }

            $result = scanner::confirmMezzanineTransfer($transfers,
                $this->transfersPassed, $this->piecesPassed);

            $this->discrepant = $result['discrepant'];
            $this->confirmed = $result['confirmed'];
        }
    }

    /*
    ****************************************************************************
    */

    function searchInactiveInventoryScannersController()
    {
        $this->errors = $discrepancy = [];
        $this->modelTable = FALSE;

        $scans = $this->getScans();
        $processType = getDefault($this->post['processType']);

        if (! $scans || ! $processType) {
            return;
        }

        $inactive = new tables\inventory\inactive($this);

        if ($processType == 'ucc128') {

            $caption = 'UCC';

            $results = $inactive->masterLabelToCarton($scans);

            $nonUCCData = getDefault($results['nonUCCData'], []);
            $invalidUCCs = getDefault($results['invalidUCCs'], []);

            $scans = getDefault($results['validUCCData'], []);
            $discrepancy = array_merge($nonUCCData, $invalidUCCs);

        } else {
            $locations = new tables\locations($this);

            $caption = 'Location Name';

            $invalidLocations = $locations->invalidLocations($scans);

            if ($invalidLocations) {
                $discrepancy = explode(',', $invalidLocations);
            }
        }

        if ($discrepancy) {
            $this->errors[] = 'Invalid '.$caption.'s:<br><br>'
                .implode('<br>', $discrepancy);
            return;
        }

        if (! $scans) {
            $this->errors[] = 'No data for output';
            return;
        }

        $ajax = new datatables\ajax($this);

        $ajax->addControllerSearchParams([
            'values' => $scans,
            'field' => $processType,
        ]);

        $this->includeJS['js/datatables/editables.js'] = TRUE;

        $ajax->output($inactive, [
            'bFilter' => FALSE,
            'order' => ['sku' => 'DESC'],
        ]);

        $this->modelTable = TRUE;
    }

    /*
    ****************************************************************************
    */

    function searchStyleLocationsScannersController()
    {
        $this->errors = $discrepancy = [];
        $this->modelTable = FALSE;

        $scans = $this->getScans();
        $processType = getDefault($this->post['processType']);

        if ($scans && $processType) {

            $processType = $this->post['processType'];

            if ($processType == 'plate') {
                $plates = new tables\plates($this);

                $results = $plates->getInventoryPlates($scans);

                $caption = 'Licence Plate';
            } else {
                $upcs = new tables\inventory\upcs($this);

                $results = $upcs->getUPCs($scans, $processType);

                $caption = strtoupper($processType);
            }

            $keys = $results ? array_keys($results) : [];

            $discrepancy = array_diff($scans, $keys);

            if ($discrepancy) {
                $this->errors[] = 'Invalid ' . $caption . 's:<br><br>'
                    . implode('<br>', $discrepancy);
                return;
            } else {

                $table = new tables\locations\styleLocations($this);

                $table->controllerData($scans, $processType);

                $this->includeJS['js/datatables/editables.js'] = TRUE;

                $this->modelTable = TRUE;
            }
        }
    }

    /*
    ****************************************************************************
    */

    function zeroOutInventoryScannersController()
    {
        $this->response = $this->errors = $discrepancy = $uccs = [];

        $warehouses = new tables\warehouses($this);
        $statuses = new tables\statuses\inventory($this);
        $cartons = new tables\inventory\cartons($this);

        $this->warehouseData = $warehouses->getDropDown();

        $scans = $this->getScans();

        $processType = getDefault($this->post['processType']);

        if (! $scans || ! $processType) {
            return;
        }

        $invID = [];

        if ($processType == 'ucc128') {

            $results = $cartons->masterLabelToCarton($scans);

            $nonUCCData = getDefault($results['nonUCCData'], []);
            $invalidUCCs = getDefault($results['invalidUCCs'], []);

            $discrepancy = array_merge($nonUCCData, $invalidUCCs);

            if ($discrepancy) {
                $this->errors[] = 'Invalid UCCs:<br><br>'
                    .implode('<br>', $discrepancy);
                return;
            }

            $uccData = getDefault($results['returnScanArray'], []);

            foreach ($uccData as $value) {
                $invID[] = key($value);
            }
        } else {
            $locations = new tables\locations($this);

            $clause = '   NOT isShipping
                AND       warehouseID = ?';

            $discrepancy = $locations->invalidLocations($scans, $clause,
                $this->post['warehouseID'], TRUE);

            if ($discrepancy) {
                $this->errors[] = 'Invalid Location Names:<br><br>'
                    .implode('<br>', $discrepancy);
                return;
            }

            $invID = $cartons->getCartonsByLocation($scans);
        }

        if (! $invID) {
            $this->errors[] = 'No cartons subject to zero out were found';
            return;
        }

        $this->response = $cartons->zeroOutInventory($invID, $statuses);
    }

    /*
    ****************************************************************************
    */

    function searchNoMezzanineScannersController()
    {
        $this->errors = $discrepancy = [];
        $this->modelTable = FALSE;

        $noMezzanines = new tables\locations\noMezzanine($this);

        $this->noMezzanineVendors = $noMezzanines->getNoMezzanineVendors();

        $scanData = $this->getScans();

        $scans = $scanData ? $scanData : [];

        $processType = getDefault($this->post['processType']);
        $vendorID = getDefault($this->post['vendorID']);

        // get all products if $scans array is empty

        if ($processType) {

            $upcs = new tables\inventory\upcs($this);

            $results = $upcs->getUPCs($scans, $processType);

            $caption = strtoupper($processType);

            $keys = $scans && $results ? array_keys($results) : [];

            $discrepancy = $scans ? array_diff($scans, $keys) : FALSE;

            if ($discrepancy) {
                $this->errors[] = 'Invalid '.$caption.'s:<br><br>'
                    .implode('<br>', $discrepancy);
                return;
            } else {

                $ajax = new datatables\ajax($this);

                $scans ?
                    $ajax->addControllerSearchParams([
                        'values' => $scans,
                        'field' => $processType,
                        'exact' => TRUE,
                    ]) :
                    NULL;

                $vendorID ?
                    $ajax->addControllerSearchParams([
                        'values' => [$vendorID],
                        'field' => 'co.vendorID',
                    ]) :
                    NULL;

                $this->includeJS['js/datatables/editables.js'] = TRUE;

                $table = new tables\locations\noMezzanine($this);

                $ajax->output($table, [
                    'bFilter' => FALSE,
                    'order' => ['sku' => 'ASC'],
                ]);

                $this->modelTable = TRUE;
            }
        }
    }

    /*
    ****************************************************************************
    */

    function searchStyleUOMsScannersController()
    {
        $this->errors = $discrepancy = [];
        $this->modelTable = FALSE;

        $scans = $this->getScans();

        if ($scans) {

            $upcs = new tables\inventory\upcs($this);

            $results = $upcs->getUPCs($scans, 'sku');

            $keys = $results ? array_keys($results) : [];

            $discrepancy = array_diff($scans, $keys);

            if ($discrepancy) {
                $this->errors[] = 'Invalid SKUs:<br><br>'
                    .implode('<br>', $discrepancy);
                return;
            } else {

                $this->jsVars['compareOperator'] = 'exact';

                $ajax = new datatables\ajax($this);
                $table = new tables\inventory\styleUOMs($this);

                $ajax->addControllerSearchParams([
                    'values' => $scans,
                    'field' => 'sku',
                ]);

                $this->includeJS['js/datatables/editables.js'] = TRUE;

                $ajax->output($table, [
                    'bFilter' => FALSE,
                    'order' => ['sku' => 'DESC'],
                ]);

                $this->modelTable = TRUE;
            }
        }
    }

    /*
    ****************************************************************************
    */

    function receivingToStockScannersController()
    {
        $this->gunHtmlDisplays();

        $plates = new plates($this);
        $cartons = new cartons($this);

        // Scans are submitted
        $scans = $this->getScans();

        if ($scans) {

            common\logger::createLogScanInput([
                'app' => $this,
                'scanInput' => $this->post['scans']
            ]);

            $result = $cartons->masterLabelToCarton($scans);

            if (! getDefault($result['returnScanArray'])) {
                return $this->errors = ['Invalid input'];
            }

            // check plates errors
            $plateResults = $plates->platesArray($result['returnScanArray']);

            if ($plateResults['errors']) {
                return $this->errors = $plateResults['errors'];
            } else {
                $licensePlates = $plateResults['licensePlates'];
            }

            $plateData = $licensePlates['mapped'];

            $duplicateCartons = $this->checkDuplicateReceivingUCCs($plateData);

            if ($duplicateCartons) {

                array_unshift($duplicateCartons, 'Cartons that have been '
                    . 'scanned multiple times:');

                $this->errors[] = $duplicateCartons;
            }

            $plateErrors = $plates->check($licensePlates['plates']);

            if ($plateErrors) {
                $this->errors[] = implode('<br>', $plateErrors);
            }

            if ($result['invalidUCCs']) {
                $this->errors[] = 'Cartons that do not exist:<br>'
                    . implode('<br>', $result['invalidUCCs']);
            }

            $invIDs = scanner::getInvIDs($licensePlates['cartons']);

            $receivedCartons = $this->checkReceivedCartons([
                'invIDs' => $invIDs,
                'plateCartons' => $licensePlates['cartons'],
                'cartons' => $cartons,
            ]);

            $this->errors = array_merge($this->errors, $receivedCartons);

            if ($this->errors) {
                return $this->errors;
            }

            $_SESSION['licensePlates'] = $this->getPlateCounts(
                $licensePlates['mapped'], $result['masterLabels']
            );

            $_SESSION['invIDs'] = $invIDs;

            $this->modelForward('receivingToStock', 'confirmValues');
        }

        // Store scan values as a session array

        if (getDefault($this->get['step']) == 'confirmValues') {

            if (! getDefault($_SESSION['licensePlates'])) {
                $this->modelForward('receivingToStock');
            }

            $this->licensePlates = $_SESSION['licensePlates'];
        }

        // Verify license plate values passed

        if (getDefault($this->post['quantities'])) {
            $_SESSION['compareValues'] = $this->post['quantities'];
            $this->modelForward('receivingToStock', 'compareValues');
        }

        // Store confirmation variables in session

        if (getDefault($this->get['step']) == 'compareValues') {
            if (! getDefault($_SESSION['compareValues'])) {
                $this->modelForward('receivingToStock');
            }

            $quantities = $_SESSION['compareValues'];

            $this->licensePlates = $licensePlates = $_SESSION['licensePlates'];

            $invIDs = $_SESSION['invIDs'];

            // Unset session vars so employee cant go back and re-use
            unset($_SESSION['licensePlates']);
            unset($_SESSION['compareValues']);
            unset($_SESSION['invIDs']);

            $plateKeys = array_keys($licensePlates);

            foreach ($plateKeys as $plate) {
                // Compare the quantities submited
                $confirmed = $quantities[$plate];

                if ($confirmed != $licensePlates[$plate]['count']) {
                    $this->flags[$plate] = [
                        'scanned' => $licensePlates[$plate]['count'],
                        'confirmed' => $confirmed,
                    ];
                }
            }

            $noErrors = ! $this->flags;

            if ($noErrors) {
                $cartons->receivingToStock($licensePlates);
            }

            $this->tableID = $noErrors ? 'approved' : 'rejected';
            $this->tableTitle = $noErrors
                ? 'Your scans have been approved'
                : 'No updates have been made. See errors below.';
        }
    }

    /*
    ****************************************************************************
    */

    function plateLocationScannersController()
    {
        $this->gunHtmlDisplays();

        $this->process = $process = getDefault($this->get['process'], FALSE);

        $processType = getDefault($this->post['processType']);

        if ($processType == 'waveIDs') {
            return $this->errors[] = 'Not support Wave Picks. Please scan
            License Plate Number';
        }

        $this->byWave = $processType == 'waveIDs' ? 'checked' : FALSE;
        $this->byPlates = ! $this->byWave ? 'checked' : FALSE;

        $statuses = $process == 'checkIn' ? [
            'order' => 'LSCI',
            'carton' => 'LS',
        ] : [
            'order' => 'LSCO',
            'carton' => 'LS',
        ];

        $redicrectName = 'plateLocation';
        $isChangeStatus = FALSE;
        if ($this->scannerName == 'inventoryTransfer') {
            $redicrectName = 'inventoryTransfer';
            $isChangeStatus = TRUE;
        }

        $reqStatuses = [];
        switch ($process) {
            case 'checkIn':
                $reqStatuses = [
                    'title' => 'Bill Of Ladings',
                    'order' => 'BOL',
                    'carton' => 'OP',
                ];
                break;
            case 'checkOut':
                $reqStatuses = [
                    'title' => 'Shipping Check-In',
                    'order' => 'LSCI',
                    'carton' => 'LS',
                ];
        }

        $locationTitle = $process ? 'Shipping Location' : 'Location';

        $this->originalPlates = $pageVars = [];

        $plates = new plates($this);
        $orders = new orders($this);
        $locations = new locations($this);
        $inventoryCartons = new cartons($this);

        $this->step = getDefault($this->get['step']);

        $this->quantity = getDefault($this->post['quantity']);
        $locationsPassed = $this->plateLocations =
            getDefault($_SESSION['plateLocations']);
        $this->paltesByWarehouse = getDefault($_SESSION['paltesByWarehouse']);

        // Scans are submitted

        $scans = $this->getScans();
        if ($scans) {

            common\logger::createLogScanInput([
                'app' => $this,
                'scanInput' => $this->post['scans'],
                'inputOption' => $processType,
            ]);

            $plateValues = $locationValues = [];

            if (count($scans) % 2) {
                return $this->errors[]
                    = 'License Plate Quantity does not equal Location Quantity';
            }

            while (! empty($scans)) {
                $plateValues[] = array_shift($scans);
                $locationValues[] = array_shift($scans);
            }

            $countPlateValue = count($plateValues);

            $this->originalPlates = $plateValues;

            if ($locationTitle == 'Location') {
                if (! $plates->validPlatesInCartons($plateValues)) {

                    return $this->errors[] = 'License Plate Not Found';
                }
            } else {
                $orderID = $orders->getOrderIDByLicensePlate($plateValues);

                if (! $orderID) {
                    return $this->errors[] = 'These license plate your input not
                    the same order';
                }

                $plateQuantity = $plates->getOrderLicensePlate($orderID);

                if (! $plates->validPlates($plateValues)) {
                    return $this->errors[] = 'License Plate Not Found';
                } elseif ($countPlateValue != $plateQuantity) {

                    return $this->errors[] = 'Your have entered the incorrect
                    quantity of license plate. You must enter '.$plateQuantity.
                        ' license plate';

                }
            }

            // Check that all orders and inventory_cartons for the license plate
            // are the correct statuses
            if ($process) {
                $errors = $inventoryCartons->badPlatesOrdersStatus($plateValues,
                    $reqStatuses);

                if ($errors) {
                    return $this->errors[] = $errors;
                }
            }

            $invalidLocations = $locations->invalidLocations($locationValues);

            if ($invalidLocations) {
                return $this->errors[]
                    = 'Locations '.$invalidLocations.' Not Found';
            }

            if ($process == 'checkIn') {
                $badLocations = $locations->invalidLocations($locationValues, 'isShipping');

                if ($badLocations) {
                    $this->errors[] = $badLocations.' Not Shipping Locations';
                    return;
                }
            } else {
                $platesLocations = array_combine($plateValues, $locationValues);

                $locationResults = $plates->checkLocationValues($platesLocations);

                if ($plates->errors) {
                    $this->errors[] = $plates->errors;
                    return;
                } else {
                    $this->paltesByWarehouse = $locationResults;
                }
            }

            //******************************************************************

            $ordersInPlates = $plates->getOrdersFromPlate($plateValues);

            //Check if any order is Error Order
            $errOrders = $orders->onHoldOrError([
                'order' => $ordersInPlates,
                'select' => 'isError',
            ]);

            if ($errOrders) {
                $this->errors[] = $errOrders;
                return;
            }

            // Check if any order is on hold.
            $onHoldOrders = $orders->onHoldOrError(['order' => $ordersInPlates]);

            if ($onHoldOrders) {
                $this->errors[] = $onHoldOrders;
                return;
            }

            //******************************************************************

            $_SESSION['plateLocations'] = $this->plateLocations
                = array_combine($plateValues, $locationValues);
            $_SESSION['paltesByWarehouse'] = $this->paltesByWarehouse;

            $pageVars = [
                'step' => 'confirm',
                'process' => $process,
            ];

            $this->modelForward($redicrectName, $pageVars);
        }

        //**********************************************************************

        if (getDefault($this->get['step']) == 'confirm' && ! $this->quantity && ! $locationsPassed) {
            $this->modelForward($redicrectName, $pageVars);
        }

        //**********************************************************************
        // Verify quantities passed

        if ($this->quantity) {
            // Unset session vars so employee cant go back and re-use
            unset($_SESSION['compareValues']);
            unset($_SESSION['plateLocations']);
            unset($_SESSION['paltesByWarehouse']);

            $plateCount = count($this->plateLocations);
            $this->noErrors = $this->quantity == $plateCount;

            if ($this->noErrors) {
                $process
                    ? $inventoryCartons->updateShipStatus([
                        'updates' => $this->plateLocations,
                        'statuses' => $statuses,
                    ])
                    : $plates->updateLocationsByPlates($this->paltesByWarehouse,
                        $isChangeStatus);
            }

            $this->tableID = $this->noErrors ? 'approved' : 'rejected';
            $this->tableTitle = $this->noErrors
                ? 'License plate locations have been updated'
                : 'You have entered the incorrect quantity';
        }
    }

    /*
    ****************************************************************************
    */

    function shipOnGunScannersController()
    {
        $postOrders = [];
        $post = getDefault($this->post);

        if (isset($post['startOver'])) {
            unset($_SESSION['platesScanned']);

        }

        $this->step = getDefault($this->get['shippingStep']);

        $this->gunHtmlDisplays();

        $this->errors = [];
        $this->success = FALSE;
        $this->scanOrders = $scanOrders = $this->getScans();
        $this->jsVars['urls']['checkOut']
            = makeLink('appJSON', 'onlineOrderCheckOut');

        $this->jsVars['step'] = $this->step == 'in'
            ? 'ShippingCheckIn'
            : 'ShippingCheckOut';

        // "information manually input" in check out
        if (isset($post['submitPlate'])) {

            $platesScanned = getDefault($_SESSION['platesScanned']);
            if ($platesScanned) {
                $sql = 'SELECT orderID,
                               scanOrderNumber
                        FROM   inventory_cartons c
                        JOIN   newOrder n ON n.id = c.orderID
                        WHERE  plate
                               IN ('.$this->getQMarkString($platesScanned).')';

                $this->orders = $this->queryResults($sql, $platesScanned);
                $this->checkOutStep = 'manuallyInput';

            } else {
                $this->jsVars['noPlateInShipOut'] = TRUE;
            }
        }

        //final step in check out

        if (isset($post['orders'])) {
            // Verify quantities passed
            $postOrders = getDefault($this->post['orders']);

            $this->platesAsKeys = array_flip($_SESSION['platesScanned']);

            $plateCount = count($this->platesAsKeys);

            // Get total number of plates for all orders

            $orderIDs = array_column($postOrders, 'newOrderID');

            $sql = 'SELECT p.id
                    FROM   inventory_cartons c
                    JOIN   licensePlate p ON p.id = c.plate
                    WHERE  c.orderID IN ('.$this->getQMarkString($orderIDs).')';

            $orderPlates = $this->queryResults($sql, $orderIDs);

            $this->quantity = count($orderPlates);

            // Make sure the quantity of plates for the order is equal to the
            // quantity of plates submitted

            $this->noErrors = $noErrors = $this->quantity == $plateCount;

            $statuses = [
                'order' => 'SHCO',
                'carton' => 'SH',
            ];

            $inventoryCartons = new cartons($this);

            if ($this->noErrors) {
                $inventoryCartons->updateShipStatus([
                    'updates' => $this->platesAsKeys,
                    'statuses' => $statuses,
                    'updateLoc' => FALSE,
                ]);

                $this->updatedStatuses
                    = $inventoryCartons->getShippedStatuses($this->platesAsKeys);

                $orders = new orders($this);
                $orders->addShippingInfo($postOrders);

                unset($_SESSION['platesScanned']);
            }

            $this->tableID = $noErrors ? 'approved' : 'rejected';
            $this->tableTitle = $noErrors
                ? 'License plates have been shipped'
                : 'You have entered the incorrect quantity';

            $this->checkOutStep = 'final';
        }

        $this->postOrders = $postOrders;
    }

    /*
    ****************************************************************************
    */

    function orderEntryScannersController()
    {
        $this->process = getDefault($this->get['process'], 'orderCheckOut');

        $params = [
            'orderCheckOut' => [
                'requires' => orders::STATUS_ENTRY_CHECK_IN,
                'status' => orders::STATUS_ENTRY_CHECK_OUT,
                'errorName' => 'Entry Checked-In',
            ],
            'routedCheckIn' => [
                'status' => orders::STATUS_ROUTING_CHECK_IN,
            ],
            'routedCheckOut' => [
                'requires' => orders::STATUS_ROUTING_CHECK_IN,
                'status' => orders::STATUS_ROUTING_CHECK_OUT,
                'errorName' => 'Routed Checked-In',
            ],
            'pickingCheckIn' => [
                'requires' => orders::STATUS_ENTRY_CHECK_OUT,
                'status' => orders::STATUS_PICKING_CHECK_IN,
                'errorName' => 'Order Checked-Out',
            ],
            'pickingCheckOut' => [
                'requires' => [
                    orders::STATUS_PICKING_CHECK_IN,
                    orders::STATUS_PICKING_CHECK_OUT,
                    orders::STATUS_PROCESSING_CHECK_IN,
                    orders::STATUS_PROCESSING_CHECK_OUT,
                    orders::STATUS_BILL_OF_LADING,
                    orders::STATUS_SHIPPING_CHECK_IN,
                    orders::STATUS_SHIPPED_CHECK_OUT,
                ],
                'status' => orders::STATUS_PICKING_CHECK_OUT,
                'errorName' => 'Picking Checked-In',
            ],
            'orderProcessingCheckIn' => [
                'requires' => orders::STATUS_PICKING_CHECK_OUT,
                'status' => orders::STATUS_PROCESSING_CHECK_IN,
                'errorName' => 'Picking Checked-Out',
            ],
            'orderProcessCheckOut' => [
                'requires' => orders::STATUS_PROCESSING_CHECK_IN,
                'status' => orders::STATUS_PROCESSING_CHECK_OUT,
                'errorName' => 'Order Processing Checked-In',
            ],
            'errOrderRelease' => [
                'requires' => orders::STATUS_ERROR,
                'status' => orders::STATUS_NO_ERROR,
                'errorName' => 'Error Order',
            ],
            'cancel' => [
                'status' => orders::STATUS_CANCELED,
            ],
        ];

        // Order Check-Out and Picking Check-Out pages do not have gun scanner
        $this->skipGun = in_array($this->process, [
            'orderCheckOut',
            'pickingCheckOut'
        ]);

        switch ($this->process) {
            case 'errOrderRelease':

                $orderStatuses = new statuses\enoughInventory($this);

                break;

            case 'routedCheckIn':
            case 'routedCheckOut':

                $orderStatuses = new statuses\routed($this);

                break;

            default:

                $orderStatuses = new statuses\orders($this);

                break;
        }

        $classes = [
            'orderStatuses' => $orderStatuses,
            'orders' => new orders($this),
        ];

        switch ($this->process) {
            case 'orderCheckOut':
                $classes['truckOrderWaves'] = new \tables\truckOrderWaves($this);
                break;
            case 'orderProcessCheckOut':
                $classes['cartons'] = new cartons($this);
                $classes['cartonStatuses'] = new statuses\inventory($this);
                $classes['locations'] = new locations($this);
                break;
            case 'cancel':
                $classes['wavePicks'] = new tables\wavePicks($this);
                break;
            default:
                break;
        }

        $this->scanOrders = $this->getScans();

        if (! $this->skipGun) {
            $this->gunHtmlDisplays();
        }

        $status = $params[$this->process]['status'];

        $this->status = $orderStatuses->getStatusID($status);
        $this->statusName = $orderStatuses->getStatusName($this->status);

        $this->step = getDefault($this->get['step']);
        $this->quantity = getDefault($this->post['quantity']);

        $this->ordersPassed = getDefault($_SESSION['orders']);
        $this->processCartons = getDefault($_SESSION['processCartons']);
        $this->duplicateClientOrder =
                getDefault($_SESSION['confirmSelectClientOrderNumber']);

        if (! ($this->ordersPassed || $this->duplicateClientOrder)) {
            $this->step = $this->quantity = NULL;
        }

        if ($this->scanOrders) {
            // setting errors property inside checkOrderEntryScanner() function
            $orderNumbers = $this->checkOrderEntryScanner($params, $classes);

            if ($this->errors && array_filter($this->errors)) {
                return;
            }

            $_SESSION['orders'] = $orderNumbers;

            if ($this->confirmSelectClientOrderNumber) {

                $_SESSION['confirmSelectClientOrderNumber'] =
                        $this->confirmSelectClientOrderNumber;

                $this->modelForward('orderEntry', [
                    'step' => 'selectOrder',
                    'process' => $this->process,
                ]);
            }

            $pageVars = [
                'step' => 'confirm',
                'process' => $this->process,
            ];

            $this->modelForward('orderEntry', $pageVars);
        }

        //**********************************************************************

        if ($this->step == 'confirm') {
            if ($this->process == 'orderProcessCheckOut' && $this->ordersPassed) {

                $orderNumbers = array_column($this->ordersPassed, 'target');

                $this->processCartons = $_SESSION['processCartons'] =
                        $classes['cartons']->getProcessingCartonCount($orderNumbers);
            }

            if (! $this->quantity && ! $this->ordersPassed
             && ! $this->duplicateClientOrder) {

                $this->modelForward('orderEntry');
            }
        }

        //**********************************************************************

        if ($this->quantity) {
            if ($this->process == 'orderProcessCheckOut') {
                $this->error = $this->checkEnteredPlatesQuantity();
            } else {
                $this->passedCount = count($this->ordersPassed);
                $this->error = $this->quantity != $this->passedCount;
            }

            if (! $this->error && $this->ordersPassed) {
                $this->updateOrderEntryScannerOrders($classes);
            }

            unset($_SESSION['orders']);

            $this->tableID = $this->error ? 'rejected' : 'approved';

            $successInfo = $this->process == 'errOrderRelease' ?
                'Error Orders have been released' :
                'Order statuses have been updated';

            $this->tableTitle = $this->error ?
                'You have entered the incorrect quantity' : $successInfo;
        }

        //**********************************************************************

        if ($this->step == 'selectOrder' && isset($this->post['submit'])) {
            $post = $this->post;

            $this->correctPassedOrder($classes);

            unset($_SESSION['confirmSelectClientOrderNumber']);

            $pageVars = [
                'step' => 'confirm',
                'process' => $this->process,
            ];

            $this->modelForward('orderEntry', $pageVars);
        }
    }

    /*
    ****************************************************************************
    */

    function shippedOrdersScannersController()
    {
        $this->errors = [];
        $this->success = FALSE;

        unset($_SESSION['scanInput']);

        $this->scanOrders = $scanOrders = $this->getScans();
        $this->jsVars['urls']['checkOut'] =
                makeLink('appJSON', 'onlineOrderCheckOut');
        $this->jsVars['urls']['checkOnlineOrdersShipPassword'] =
                makeLink('appJSON', 'checkOnlineOrdersShipPassword');
    }

    /*
    ****************************************************************************
    */

    function automatedScannersController()
    {
        $scanner = new common\automated($this);

        $scanner->createDisplay();
    }

    /*
    ****************************************************************************
    */

    function shippedScannersController()
    {
        $countPlates = [];
        $this->noErrors = TRUE;
        $this->gunHtmlDisplays();

        $process = getDefault($this->get['process'], 'checkIn');
        $inventoryCartons = new cartons($this);
        $shippingInfo = new tables\orders\shippingInfo($this);

        $this->shippingInfo = $shippingInfo->fields;

        unset($this->shippingInfo['vendor']);
        unset($this->shippingInfo['scanOrderNumber']);

        $this->shippingInfo = array_merge($this->shippingInfo, [
            'trackingNumber' => [
                'length' => 35,
                'format' => 'Alpha-Numeric',
                'display' => 'Tracking Number'
            ]
        ]);

        $this->jsVars['shippingInfo'] = $this->shippingInfo;
        $statuses = [
            'order' => 'SHCO',
            'carton' => 'SH',
        ];
        $scans = $this->getScans();
        $billoflading = new \tables\billOfLadings($this);
        if ($scans) {
            common\logger::createLogScanInput([
                'app' => $this,
                'scanInput' => $this->post['scans'],
            ]);

            $results = $billoflading->getCheckInArray($this, $scans);
            $results['returnArray'] ? $_SESSION['returnArray'] = $returnArray =
                $results['returnArray'] : $this->errors[] = 'Data input wrong.';

            if ($this->errors) {
                return $this->errors;
            }

            $bolArrayInput = array_keys($results['returnArray']);

            $bolData = $billoflading->getOrdersByBOL($returnArray);

            $plateInfo = $bolData['plates'];

            $_SESSION['bolOrders'] = $bolData['orders'];

            foreach ($bolArrayInput as $bolNumber) {
                if (! isset($plateInfo[$bolNumber])) {
                    $this->errors['bolNumberInvalid'][] = 'Bill of lading '
                        . $bolNumber . ' not exists!';
                }
            }

            if ($this->errors) {
                return $this->errors;
            }

            foreach ($plateInfo as $bolIDs => $plates) {
                $countPlates[$bolIDs] = count($plates);
                foreach ($results['returnArray'][$bolIDs] as $plateInput) {
                    if (! in_array($plateInput, $plates)) {
                        $this->errors['plateInvalid'][] = 'License plate '
                            . $plateInput . ' not exists in ' . $bolIDs;
                    }
                }
            }

            foreach ($results['returnArray'] as $bolIDs => $plateScans) {
                if (count($plateScans) != $countPlates[$bolIDs]) {
                    $this->errors['countPlates'][] = $bolIDs . ' need '
                        . $countPlates[$bolIDs] . ' license plate.';
                }
            }

            if ($this->errors) {
                return $this->errors;
            }

            $_SESSION['billOfLadingKeys'] = array_flip($results['bolIDs']);
            $_SESSION['platesAsKeys'] = array_flip($results['platesAsKeys']);
            $_SESSION['countPlates'] = $countPlates;

            $pageVars = [
                'step' => 'confirmBOL',
                'process' => $process,
            ];
            $this->modelForward('shipped', $pageVars);
        }

        //Checking Plates of BIll Of Lading
        if (getDefault($this->get['step']) == 'confirmBOL') {

            $this->bolOrders = $_SESSION['bolOrders'];
            $this->platesAsKeys = $_SESSION['platesAsKeys'];
            $bolNumbers = array_keys($_SESSION['billOfLadingKeys']);
            $this->BOLs = $bolNumbers;

            $countPlates = $_SESSION['countPlates'];
            $postBOLs = getDefault($this->post['BOLs']);
            $postBOLQuantities = getDefault($this->post['BOLQuantities']);

            $_SESSION['orderConditions'] = getDefault($this->post['orderConditions']);

            if ($postBOLs && $postBOLQuantities) {
                $returnArray = $_SESSION['returnArray'];
                foreach ($returnArray as $bolID => $plateScans) {
                    $plateQuantity = count($plateScans);
                    if ($postBOLQuantities[$bolID] != $plateQuantity
                        && $postBOLQuantities[$bolID] != $countPlates[$bolID]) {
                        $this->noErrors = FALSE;
                        $this->quantity = $postBOLQuantities[$bolID];
                        $this->errors[] = 'License Plate quantity for ' . $bolID . ' not match';
                    }
                }
                $this->tableID = ! $this->errors ? 'approved' : 'rejected';
                $this->tableTitle = ! $this->errors
                    ? 'License plates have been shipped'
                    : 'You have entered the incorrect quantity';
                if ($this->errors) {
                    return $this->errors;
                }
                $pageVars = [
                    'step' => 'BOLInfo',
                    'process' => $process,
                ];
                $this->modelForward('shipped', $pageVars);
            }

        }
        if (getDefault($this->get['step']) == 'BOLInfo') {
            $bolNumbers = array_keys($_SESSION['billOfLadingKeys']);
            $this->BOLs = $bolNumbers;
            $generateBOLs = getDefault($this->post['generateBOLs']);
            if ($generateBOLs) {
                $billoflading->updateBOLInfo($generateBOLs);

                $pageVars = [
                    'step' => 'confirmValues',
                    'process' => $process,
                ];
                $this->modelForward('shipped', $pageVars);
            }
        }

        if (getDefault($this->get['step']) == 'confirmValues') {

            $this->platesAsKeys = $_SESSION['platesAsKeys'];
            $bolNumbers = array_keys($_SESSION['billOfLadingKeys']);
            $this->bolIDs = $bolNumbers;

            $sql = 'SELECT orderID,
                           scanOrderNumber,
                           plate
                    FROM   inventory_cartons c
                    JOIN   newOrder n ON n.id = c.orderID
                    WHERE  plate
                           IN ('.$this->getQMarkString($_SESSION['platesAsKeys']).')';

            $plates = array_keys($_SESSION['platesAsKeys']);

            $results = $this->queryResults($sql, $plates);

            $orderIDs = array_keys($results);


            $sql = 'SELECT p.id
                    FROM   inventory_cartons c
                    JOIN   licensePlate p ON p.id = c.plate
                    WHERE  c.orderID IN ('.$this->getQMarkString($orderIDs).')';

            $orderPlates = $this->queryResults($sql, $orderIDs);

            $this->tableID = $orderPlates ? 'approved' : 'rejected';
            $this->tableTitle = $orderPlates ? 'Shipped Check-Out successful!' :
                'Shipped Check-Out wrong.';

            if ($orderPlates) {

                $inventoryCartons->updateShipStatus([
                    'updates' => $this->platesAsKeys,
                    'statuses' => $statuses,
                    'updateLoc' => FALSE,
                    'orderConditions' => $_SESSION['orderConditions'],
                ]);

                $this->updatedStatuses
                    = $inventoryCartons->getShippedStatuses($this->platesAsKeys);

                scanner::sendShippingMailByBOLs($this, $this->bolIDs);
            }
        }
    }

    /*
    ****************************************************************************
    */

    function workOrderCheckInScannersController()
    {
        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $this->jsVars['urls']['workOrderCheckInVerify']
            = customJSONLink('appJSON', 'workOrderCheckInVerify');

        $this->jsVars['urls']['workOrders']
            = makeLink('workOrders', 'checkInOut', [
                'type' => 'checkIn'
            ]);
    }

    /*
    ****************************************************************************
    */

    function workOrderCheckOutScannersController()
    {
        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $this->jsVars['urls']['workOrderCheckOutVerify']
            = customJSONLink('appJSON', 'workOrderCheckOutVerify');

        $this->jsVars['urls']['workOrders']
            = makeLink('workOrders', 'checkInOut', [
                'type' => 'checkOut'
            ]);
    }

    /*
    ****************************************************************************
    */

    function orderHoldScannersController()
    {
        $this->process = getDefault($this->get['process'], 'checkIn');
        $statuses = new statuses($this);

        if(isset($this->post['holdStatus'])){$_SESSION['holdStatus'] = $this->post['holdStatus'];}

        $holdStatus = isset($_SESSION['holdStatus']) ? $_SESSION['holdStatus']: '';

        $this->status = $holdStatus;

        if ($this->status){
            $this->statusName = $statuses->getStatusName($this->status);
        }

        $this->step = getDefault($this->get['step']);

        $ordersPassed = getDefault($_SESSION['orders']);
        $this->quantity = getDefault($this->post['quantity']);
        $this->scanOrders = $scanOrders = $this->getScans();
        $orders = new orders($this);

        if ($scanOrders) {

            $primary = [
                'assoc' => 'id',
                'field' => $orders->primaryKey
            ];

            common\logger::createLogScanInput([
                'app' => $this,
                'scanInput' => $this->post['scans'],
                'inputOption' => $this->statusName
            ]);

            $results = $orders->valid($scanOrders, 'scanordernumber', $primary);

            if (! $results['valid']) {

                $errMsg = NULL;
                foreach ($results['perRow'] as $result) {
                    if (! $result['id']) {
                        $break = $errMsg ? '<br>' : NULL;
                        $errMsg .= $break . $result['target'];
                    }
                }

                $this->errors[] = 'Order(s) Not Found:<br><br>'
                    . $errMsg;
                return;
            }

            $_SESSION['orders'] = $results['perRow'];

            $pageVars = [
                'step' => 'confirm',
                'process' => $this->process,
            ];

            $this->modelForward('orderHold', $pageVars);
        }

        //**********************************************************************

        if ($this->step == 'confirm' && ! $this->quantity && ! $ordersPassed) {
            $this->modelForward('orderHold');
        }

        //**********************************************************************

        if ($this->quantity) {

            unset($_SESSION['orders']);
            unset($_SESSION['holdStatus']);
            $this->passedCount = count($ordersPassed);
            $this->ordersPassed = $ordersPassed;
            $this->error = $this->quantity != $this->passedCount;

            if (! $this->error) {

                unset($_SESSION['orderType']);

                order::updateAndLogStatus([
                    'orderIDs' => array_column($ordersPassed, 'id'),
                    'statusID' => $holdStatus,
                    'field' => 'holdStatusID',
                    'tableClass' => $orders,
                ]);
            }

            $this->tableID = $this->error ? 'rejected' : 'approved';
            $this->tableTitle = $this->error
                ? 'You have entered the incorrect quantity'
                : 'Order statuses have been updated';
        }
    }


    /*
    ****************************************************************************
    */

    function adjustScannersController()
    {
        $this->error = FALSE;
        $this->gunHtmlDisplays();

        $plates = new plates($this);
        $locations = new locations($this);
        $inventory = new tables\inventory\adjustments\inventory($this);
        $cartons = new cartons($this);
        $scanner = new scanner($this);

        $this->step = getDefault($this->get['step']);
        $this->cartonCounts = [];
        $this->cartonsPassed = getDefault($_SESSION['adjust']);

        $this->quantities = getDefault($this->post['quantities']);
        $scans = $this->getScans();

        if ($scans) {

            $classes = [
                'locations' => $locations,
                'cartons' => $cartons,
                'plates' => $plates,
                'scanner' => $scanner,
            ];

            $checkResults = $inventory->checkAdjustScannerInput($scans, $classes);

            $scanAdjust = $checkResults['scanAdjust'];

            $this->errors = $checkResults['errors'];

            if ($this->errors) {
                return;
            }

            $_SESSION['adjust'] = $scanAdjust;

            $this->modelForward('adjust', 'confirm');
        }

        //**********************************************************************

        if ($this->step == 'confirm' && ! $this->quantities && ! $this->cartonsPassed) {
            $this->modelForward('adjust');
        }

        //**********************************************************************

        if ($this->quantities) {

            $this->passedCount = [];

            unset($_SESSION['adjust']);

            foreach ($this->quantities as $location => $quantity) {

                $passed = $this->passedCount[$location] =
                    count($this->cartonsPassed['locationsCartons'][$location]);

                if (! $this->error && $quantity != $passed) {
                    $this->error = TRUE;
                }
            }

            if (! $this->error) {

                $results = $inventory->process($this->cartonsPassed);

                $this->extraCartons = $results['extraCartons'];
                $this->missedCartons = $results['missedCartons'];
                $this->existingCartons = array_diff($this->cartonsPassed['cartons'],
                    $results['extraCartons'], $results['missedCartons']);
            }

            $this->tableID = $this->error ? 'rejected' : 'approved';
            $this->tableTitle = $this->error ?
                'You have entered an incorrect carton quantity' :
                'Inventory statuses have been updated';
        }
    }

    /*
    ****************************************************************************
    */

    function batchScannersController()
    {
        $this->gunHtmlDisplays();

        $this->error = FALSE;

        $this->step = getDefault($this->get['step']);

        $this->batchesPassed = getDefault($_SESSION['batchesPassed']);
        $this->wavePicks = getDefault($_SESSION['wavePicks']);

        $this->quantities = getDefault($this->post['quantities']);
        $scans = $this->getScans();

        $orderBatches = new \tables\orderBatches($this);

        if ($scans) {

            common\logger::createLogScanInput([
                'app' => $this,
                'scanInput' => $this->post['scans']
            ]);

            $result = $orderBatches->getCheckInArray($scans);

            if ($result['errMsg']) {
                $this->errors[] = $result['errMsg'];
                return;
            }

            $_SESSION['batchesPassed'] = $result['batches'];
            $_SESSION['wavePicks'] = $result['wavePicks'];

            $this->modelForward('batch', 'confirm');
        }

        //**********************************************************************

        if ($this->step == 'confirm' && ! $this->quantities && ! $this->batchesPassed) {
            $this->modelForward('batch');
        }

        //**********************************************************************

        if ($this->quantities) {

            unset($_SESSION['batchesPassed']);
            unset($_SESSION['wavePicks']);

            foreach ($this->quantities as $batch => $quantity) {

                $passed = count($this->batchesPassed[$batch]);

                if ($quantity != $passed) {
                    $this->error = TRUE;
                    break;
                }
            }

            if ($this->batchesPassed) {
                $orderBatches->updateBatch($this->batchesPassed, $this->wavePicks);
            }

            $this->tableID = $this->error ? 'rejected' : 'approved';
            $this->tableTitle = $this->error
                ? 'You have entered an incorrect order quantity'
                : 'Orders assignments to Wave Picks have been updated';
        }
    }

    /*
    ****************************************************************************
    */

    function locationsScannersController(){

        $this->gunHtmlDisplays();

        $locations = new locations($this);

        $inventoryCartons = new cartons($this);

        $this->process = $process = getDefault($this->get['process'], FALSE);

        $this->step = getDefault($this->get['step']);

        $this->quantity = getDefault($this->post['quantity']);

        $locationsPassed = $this->listLocations =
            getDefault($_SESSION['listLocations']);

        // Scans are submitted

        $scans = $this->getScans();
        if ($scans) {

            $this->listLocations = $scans;

            $_SESSION['listLocations'] = $this->listLocations;

            $invalidLocations =
                $locations->invalidLocations($this->listLocations);

            if ($invalidLocations) {
                return $this->errors[]
                    = 'Locations '.$invalidLocations.' Not Found';
            }

            $pageVars = [
                'step' => 'confirm',
                'process' => $process,
            ];

            $this->modelForward('locations', $pageVars);
        }

        //**********************************************************************

        if (getDefault($this->get['step']) == 'confirm'
            && !$this->quantity && !$locationsPassed) {
            $this->modelForward('locations', $pageVars);
        }

        //**********************************************************************
        // Verify quantities passed

        if ($this->quantity) {
            // Unset session vars so employee cant go back and re-use
            unset($_SESSION['listLocations']);

            $locationsCount = count($this->listLocations);

            $this->noErrors = $this->quantity == $locationsCount;

            if ($this->noErrors) {
                $pageVars = [
                    'show' => 'groupLocation',
                ];

                $_SESSION['locationSearch'] = $this->listLocations;

                $next = makeLink('inventory', 'components', $pageVars);

                return redirect($next);
            } else {
                $this->tableID = 'rejected';

                $this->tableTitle = 'You have entered the incorrect quantity';
            }
        }
    }

    /*
    ****************************************************************************
    */

    function reprintLicensePlateScannersController()
    {
        $this->errors = FALSE;
        $this->missingInput = FALSE;
        $this->licensePlate =[];

        $plates = new plates($this);
        $locations = new locations($this);

        $scanInput = getDefault($this->post['scans']);

        if (isset($this->post['scans']) && ! $scanInput) {
            return $this->missingInput = TRUE;
        }

        $scans = $this->getScans();

        if ($scans) {

            $locationInvalid = $locations->checkLocationInvalid($scans);

            if ($locationInvalid) {

                return $this->errors = $locationInvalid;

            }

            $this->jsVars['urls']['plateEachLocation'] =
            $this->licensePlate = $plates->getPlateEachLocation($scans);

            $_SESSION['licensePlate'] = $this->licensePlate;
        }

        if (isset($this->post['printPDF'])) {

            $plates->printPDFLicensePlate($_SESSION['licensePlate']);

        }
    }

    /*
    ****************************************************************************
    */

    function transferMezzanineScannersController()
    {
        $this->errors = FALSE;
        $this->success = FALSE;

        $inventoryCartons = new \tables\inventory\cartons($this);
        $scanners = new scanner($this);
        $transfer = new \tables\transfers($this);
        $seldatOriginal = new \tables\seldatOriginal($this);

        $template = getDefault($this->post['template']);
        $isScan = isset($this->post['scans']);
        $data = getDefault($this->post['scans']);
        $getScans = $seldatOriginal->getInputScan($data);

        // Don't assume getScans returns an array
        $dataInput = $getScans ? $getScans : [];

        if ($template) {
            $this->transferTemplate();
            die();
        }  elseif (getDefault($_FILES) && $_FILES['file']['error']) {
            return $this->errors[] = 'Please input file to import';
        }

        $isImport = getDefault($_FILES) && ! $_FILES['file']['error'];

        // Upload file
        if ($isImport) {
            // Process import transfer
            $results = $transfer->processImportTransfer($this);

            if (isset($results['errors'])) {
                return $this->errors = $results['errors'];
            }

            $dataInput = $results;

        } elseif ($isScan) {
            ///save log scan input
            common\logger::createLogScanInput([
                'app' => $this,
                'scanInput' => implode('<br>', $dataInput),
                'inputOption' => 'transferMezzanine'
            ]);
        }

        if ($isImport || $isScan) {

            $verifiedScans = $inventoryCartons->masterLabelToCarton($dataInput);

            $invalidUCCs = getDefault($verifiedScans['invalidUCCs'], []);
            $returnScanArray = getDefault($verifiedScans['returnScanArray']);

            if (! $returnScanArray) {
                $this->errors[] = 'Data input wrong.';
            }

            foreach ($invalidUCCs as $ucc) {
                $this->errors[] = 'UCC ' . $ucc . ' is invalid';
            }

            if ($this->errors) {
                return $this->errors;
            }

            $scanArray = array_values($returnScanArray);

            $cartonArray =
                $scanners->getTargetCartonsArray($scanArray, 'location', TRUE);

            $validUCCData = getDefault($verifiedScans['validUCCData']);

            if (! $validUCCData) {
                return $this->errors[] = 'All UCC(s) are invalid.';
            }

            $clauses = [
                'fields' => [
                    'vendorID',
                    'v.warehouseID',
                    'batchID',
                    'cartonID',
                    'locID',
                    'mLocID',
                    'displayName'
                ],
                'join' => '
                    JOIN      vendors v ON v.id = co.vendorID
                    JOIN      locations l ON l.id = ca.locID
                    '
            ];

            $uccInfo = $inventoryCartons->getByUCC($validUCCData, $clauses);

            $locationsUCCs = $cartonArray['locationsUCCs'];

            $checkResults = scanner::checkDataInput($this, $uccInfo, $locationsUCCs);

            if (isset ($checkResults['errors'])) {
                return $this->errors = $checkResults['errors'];
            }

            $result = $transfer->processTransfer($checkResults['dataTransfer'],
                    $inventoryCartons);

            $result ? $this->errors = $result['errors'] : $this->success = TRUE;
        }
    }

    /*
    ****************************************************************************
    */

    function inventoryTransferScannersController()
    {
        $this->gunHtmlDisplays();

        $plates = new plates($this);
        $cartons = new cartons($this);
        $locations = new locations($this);

        // Scans are submitted
        $scans = $this->getScans();

        if ($scans) {

            common\logger::createLogScanInput([
                'app' => $this,
                'scanInput' => $this->post['scans']
            ]);

            $result = $cartons->masterLabelToCarton($scans);

            if (! getDefault($result['returnScanArray'])) {
                return $this->errors = ['Invalid input'];
            }

            // check plates errors
            $plateResults =
                    $plates->platesLocationsArray($result['returnScanArray']);

            if ($plateResults['errors']) {
                return $this->errors = $plateResults['errors'];
            }

            $licensePlates = $plateResults['licensePlates'];
            $plateData = $licensePlates['mapped'];
            $plateValues = array_keys($licensePlates['plates']);
            $arrlocations = $licensePlates['locations'];

            $duplicateCartons = $this->checkDuplicateReceivingUCCs($plateData);

            if ($duplicateCartons) {

                array_unshift($duplicateCartons, 'Cartons that have been '
                    . 'scanned multiple times:');

                $this->errors[] = $duplicateCartons;
            }

            if ($result['invalidUCCs']) {
                $this->errors[] = 'Cartons that do not exist:<br>'
                    . implode('<br>', $result['invalidUCCs']);
            }

            $invalidLocations = $locations->invalidLocations($arrlocations);

            if ($invalidLocations) {
                return $this->errors[]
                    = 'Locations '.$invalidLocations.' Not Found';
            }

            $plateErrors = $plates->checkExisted($plateValues);

            if ($plateErrors) {
                $this->errors[] = implode('<br>', $plateErrors);
            }

            $platesLocations = array_combine($plateValues, $arrlocations);

            $locationResults =
                    $plates->checkLocationValuesToTransfer($platesLocations);

            if ($plates->errors) {
                $this->errors[] = $plates->errors;
                return;
            } else {
                $this->paltesByWarehouse = $locationResults;
            }

            foreach ($locationResults as $licenseID => $locationInfo) {
                $licensePlates['mapped'][$licenseID]['locationInfo'] =
                        $locationInfo;
            }

            $invIDs = scanner::getInvIDs($licensePlates['cartons']);

            //Check carton has racked
            $receivedCartons = $this->checkHasRackedCartons($invIDs,
                    $licensePlates['cartons']);

            $this->errors = array_merge($this->errors, $receivedCartons);

            if ($this->errors) {
                return $this->errors;
            }

            //******************************************************************

            $_SESSION['invIDs'] = $invIDs;

            $_SESSION['licensePlates'] = $this->getPlateCounts(
                $licensePlates['mapped'], $result['masterLabels']
            );

            $this->modelForward('inventoryTransfer', 'confirmValues');
        }

        // Store scan values as a session array

        if (getDefault($this->get['step']) == 'confirmValues') {

            if (! getDefault($_SESSION['licensePlates'])) {
                $this->modelForward('inventoryTransfer');
            }

            $this->licensePlates = $_SESSION['licensePlates'];
        }

        // Verify license plate values passed
        if (getDefault($this->post['quantities'])) {
            $_SESSION['compareValues'] = $this->post['quantities'];
            $this->modelForward('inventoryTransfer', 'compareValues');
        }

        // Store confirmation variables in session
        if (getDefault($this->get['step']) == 'compareValues') {
            if (! getDefault($_SESSION['compareValues'])) {
                $this->modelForward('inventoryTransfer');
            }

            $quantities = $_SESSION['compareValues'];
            $invIDs = $_SESSION['invIDs'];

            $this->licensePlates = $licensePlates = $_SESSION['licensePlates'];

            // Unset session vars so employee cant go back and re-use
            unset($_SESSION['licensePlates']);
            unset($_SESSION['compareValues']);
            unset($_SESSION['invIDs']);

            $plateKeys = array_keys($licensePlates);

            foreach ($plateKeys as $plate) {
                // Compare the quantities submited
                $confirmed = $quantities[$plate];

                if ($confirmed != $licensePlates[$plate]['count']) {
                    $this->flags[$plate] = [
                        'scanned' => $licensePlates[$plate]['count'],
                        'confirmed' => $confirmed,
                    ];
                }
            }

            $noErrors = ! $this->flags;

            if ($noErrors) {
                $this->results =
                        $cartons->inventoryTransfer($licensePlates, $invIDs);
            }

            $this->tableID = $noErrors ? 'approved' : 'rejected';
            $this->tableTitle = $noErrors
                ? 'Your scans have been approved'
                : 'No updates have been made. See errors below.';
        }
    }

    /*
    ****************************************************************************
    */

    function downloadCartonHistoryScannersController()
    {
        $this->error = FALSE;
        $this->missingInput = FALSE;
        $this->data =[];

        $cartons = new cartons($this);
        $vendors = new \tables\vendors($this);
        $this->vendors = $vendors->getVendorDropdown();

        $this->downloadLink =
            makeLink('scanners', 'downloadCartonHistory', 'download');

        if (isset($this->get['download'])) {
            $downloadData = $_SESSION['cartonDownloadData'];

            $cartons->processDownloadCartonHistory($downloadData);

            return FALSE;
        }

        $scanInput = getDefault($this->post['scans']);

        if (isset($this->post['scans']) && ! $scanInput) {
            return $this->missingInput = TRUE;
        }

        $vendorID = getDefault($this->post['customer']);

        $scans = $this->getScans();

        if ($scans) {
            common\logger::createLogScanInput([
                'app' => $this,
                'scanInput' => implode('<br>', $scans),
                'inputOption' => 'downloadCartonHistory'
            ]);

            $UCC128Invalid = $cartons->checkUCC128Invalid($scans);

            if ($UCC128Invalid) {
                return $this->error = 'UCCs invalid: '
                    . implode(', ', $UCC128Invalid);
            }

            $this->jsVars['urls']['plateEachLocation'] =
            $this->data = $cartons->getDownloadData($vendorID, $scans);

            $_SESSION['cartonDownloadData'] = $this->data;
        }
    }

    /*
    ****************************************************************************
    */

    function warehouseOutboundTransferScannersController()
    {
        $this->error = FALSE;

        $this->gunHtmlDisplays();

        $warehouseTransfers =
                new tables\warehouseTransfers\warehouseTransfers($this);
        $warehouseTransferPallets =
                new tables\warehouseTransfers\warehouseTransferPallets($this);

        $this->warehouseTransfers = $warehouseTransfers->getDropdown('description');

        $this->step = getDefault($this->get['step']);

        $this->outboundPlates = getDefault($_SESSION['outboundPlates']);
        $this->warehouseTransferID = getDefault($_SESSION['warehouseTransferID']);

        $this->quantities = getDefault($this->post['quantities']);

        $scans = $this->getScans();

        if ($scans) {

            common\logger::createLogScanInput([
                'app' => $this,
                'scanInput' => implode('<br>', $scans),
                'inputOption' => 'warehouseTransfer'
            ]);

            $manifestStart = array_shift($scans);
            $manifestEnd = array_pop($scans);

            if ($manifestStart != $manifestEnd) {
                return $this->errors = [
                    'Opening and closing manifests do not match'
                ];
            }

            $result = $warehouseTransferPallets->checkTransferPlates(
                    $manifestStart, $scans, $this->post['warehouseTransferID']
            );

            if ($result['errors']) {
                return $this->errors = $result['errors'];
            }

            $_SESSION['warehouseTransferID'] = $this->post['warehouseTransferID'];
            $_SESSION['outboundPlates'] = $result['plates'];

            $this->modelForward('warehouseOutboundTransfer', 'confirm');
        }

        //**********************************************************************

        $confirmStep = $this->get['step'] == 'confirm';

        if ($confirmStep && ! $this->quantities && ! $this->outboundPlates) {
            $this->modelForward('warehouseOutboundTransfer');
        }

        //**********************************************************************

        if ($this->quantities) {

            unset($_SESSION['warehouseOutboundTransfer']);
            unset($_SESSION['warehouseTransfer']);

            foreach ($this->quantities as $manifest => $quantity) {

                $passed = count($this->outboundPlates[$manifest]);

                if ($quantity != $passed) {
                    $this->error = TRUE;
                    break;
                }
            }

            if (! $this->error) {
                $warehouseTransferPallets->create($this->warehouseTransferID,
                        $this->outboundPlates);
            }

            $this->tableID = $this->error ? 'rejected' : 'approved';
            $this->tableTitle = $this->error
                ? 'You have entered an incorrect license plate quantity'
                : 'License Plates assigned to Manifests';
        }
    }

    /*
    ****************************************************************************
    */

    function warehouseInboundTransferScannersController()
    {
        $this->error = FALSE;

        $this->gunHtmlDisplays();

        $warehouseTransfers =
                new tables\warehouseTransfers\warehouseTransfers($this);
        $warehouseTransferPallets =
                new tables\warehouseTransfers\warehouseTransferPallets($this);

        $this->warehouseTransfers = $warehouseTransfers->getDropdown('description');

        $this->step = getDefault($this->get['step']);

        $this->quantity = getDefault($this->post['quantity']);

        $this->warehouseTransferID = getDefault($_SESSION['warehouseTransferID']);
        $this->transferWarehouseIDs = getDefault($_SESSION['transferWarehouseIDs']);
        $this->plateLocations = getDefault($_SESSION['plateLocations']);

        $scans = $this->getScans();

        if ($scans) {

            $_SESSION['warehouseTransferID'] = $transferID =
                    $this->post['warehouseTransferID'];

            $_SESSION['transferWarehouseIDs'] = $transferWarehouseIDs =
                    $warehouseTransfers->getWarehouseIDs($transferID);

            $results = $warehouseTransferPallets->checkInboundPlates(
                    $transferID, $transferWarehouseIDs['inWarehouseID'], $scans
            );

            if ($results['errors']) {
                return $this->errors = $results['errors'];
            }

            $_SESSION['plateLocations'] = $this->plateLocations
                = $results['outTransferPlates'];

            $this->modelForward('warehouseInboundTransfer', 'confirm');
        }

        //**********************************************************************

        $confirmStep = $this->get['step'] == 'confirm';

        if ($confirmStep && ! $this->quantity && ! $this->plateLocations) {
            $this->modelForward('warehouseInboundTransfer');
        }

        //**********************************************************************

        if ($this->quantity) {

            unset($_SESSION['plateLocations']);
            unset($_SESSION['warehouseTransferID']);

            $plateCount = count($this->plateLocations);

            $this->noErrors = $this->quantity == $plateCount;

            if ($this->noErrors) {
                $warehouseTransferPallets->updateTransfer(
                        $this->warehouseTransferID,
                        $this->transferWarehouseIDs,
                        $this->plateLocations
                );
            }

            $this->tableID = $this->noErrors ? 'approved' : 'rejected';
            $this->tableTitle = $this->noErrors
                ? 'License plate locations have been updated'
                : 'You have entered the incorrect quantity';
        }
    }

    /*
    ****************************************************************************
    */

    function warehouseTransferConsolidationScannersController()
    {
        $this->error = FALSE;

        $vendors = new \tables\vendors($this);
        $cartons = new cartons($this);
        $statuses = new statuses\inventory($this);

        $this->warehouseVendor = $vendors->getVendorDropdown();

        if (getDefault($this->post['customer'])) {

            $vendorID = $this->post['customer'];

            $statusID = $statuses->getStatusID(cartons::STATUS_RACKED);

            // getting locations that have more than one License Plates
            $results = $cartons->getMutiplePlatesLocation($vendorID, $statusID);

            if (! $results) {
                return $this->errors = ['No License Plates to consolidate'];
            }

            $updateInventory = $locationRes = [];

            foreach ($results as $invID => $row) {

                $locID = $row['locID'];

                $locationRes[$locID][] = $updateInventory[$locID][$invID] =
                        $row['plate'];
            }

            $locIDs = array_keys($locationRes);

            $platesRequired = count($locIDs);

            // Generating new Licence Plates
            $platesCreated = \common\scanner::createProcessingPlates($this,
                    $platesRequired);

            if (! $platesCreated['errors']) {

                $newPlates = $platesCreated['licensePlates'];

                $platesLocRes = array_combine($locIDs, $newPlates);

                //update new license plates
                $cartons->updateLicensePlates([
                    'locationResults' => $locationRes,
                    'plateLocationResults' => $platesLocRes,
                    'updateInventory' => $updateInventory,
                    'statusID' => $statusID,
                ]);

                //Print new license plates
                $labels = new labels\licensePlates();

                $labels->addLicensePlate([
                    'db' => $this,
                    'term' => $newPlates,
                    'search' => 'plates',
                    'level'  => 'carton',
                    'printAll' => NULL,
                    'fileName' => 'Mulitiple_License_Plates',
                ]);
            }
        }
    }

    /*
    ****************************************************************************
    */

    function printMultiLicenseScannersController()
    {

        $labels = new labels\licensePlates();
        $this->missingInput = FALSE;
        $this->errors = NULL;

        $scanInput = getDefault($this->post['scans']);

        if (isset($this->post['scans']) && ! $scanInput) {
            return $this->missingInput = 'Please input License Plate(s)';
        }

        $scans = getDefault($this->post['scans']);

        common\scanner::get($scans);

        if ($scans) {
            $scans = array_values($scans);

            $labels->addLicensePlate([
                'db' => $this,
                'term' => $scans,
                'search' => 'plates',
                'fileName' => 'Multiple_License_Plate'
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function scanLicensePlateScannersController()
    {
        if (isset($_SESSION['licensePlates'])) {
            unset($_SESSION['licensePlates']);
        }

        $this->errors = FALSE;
        $this->missingInput = FALSE;
        $this->licensePlate = [];

        $plates = new tables\inventory\licensePlateBatch($this);

        $scanInput = getDefault($this->post['scans']);

        if (isset($this->post['scans']) && ! $scanInput) {
            return $this->missingInput = TRUE;
        }

        $scans = $this->getScans();

        if ($scans) {

            $platesInvalid = $plates->checkPlatesInvalid($scans);

            if ($platesInvalid) {
                return $this->errors = $platesInvalid;
            }

            $_SESSION['licensePlates'] = $scans;

            $next = makeLink('inventory', 'getCartonsEditUomByPlates');

            return redirect($next);

            $this->jsVars['urls']['plateEachLocation'] =
            $this->licensePlate = $plates->getPlateEachLocation($scans);

        }
    }

    /*
    ****************************************************************************
    */

    function changeCartonStatusScannersController()
    {
        $this->errors = [];
        $this->success = [];

        $cartons = new cartons($this);
        $cartonsSts = new statuses\inventory($this);
        $inventoryCartons = new \tables\inventory\cartons($this);
        $requestChangeStatus = new \tables\inventory\requestChangeStatus($this);
        $this->sts = $cartonsSts->getDropdown('shortName');

        $scans = $this->getScans();

        $sts = getDefault($this->post['sts']);
        $mSts = getDefault($this->post['mSts']);

        if (! $scans || ! $sts || ! $mSts) {
            return;
        }

        if ($scans) {
            ///save log scan input
            common\logger::createLogScanInput([
                'app' => $this,
                'scanInput' => implode('<br>', $scans),
                'inputOption' => 'changeCartonStatus'
            ]);

            $UCC128Invalid = $cartons->checkUCC128Invalid($scans);

            if ($UCC128Invalid) {
                return $this->errors[] = 'UCCs invalid: '
                    . implode(', ', $UCC128Invalid);
            }

            $verifiedScans = $inventoryCartons->masterLabelToCarton($scans);

            $invalidUCCs = getDefault($verifiedScans['invalidUCCs'], []);
            $returnScanArray = getDefault($verifiedScans['returnScanArray']);

            if (! $returnScanArray) {
                $this->errors[] = 'Data input wrong.';
            }

            foreach ($invalidUCCs as $ucc) {
                $this->errors[] = 'UCC ' . $ucc . ' is invalid';
            }

            if ($this->errors) {
                return $this->errors;
            }

            $validUCCData = getDefault($verifiedScans['validUCCData']);

            if (! $validUCCData) {
                return $this->errors[] = 'All UCC(s) are invalid.';
            }

            // Check data
            $return = $requestChangeStatus->checkScanInput([
                'sts' => $sts,
                'mSts' => $mSts,
                'uccData' => $returnScanArray
            ]);

            if ($return['errors']) {
                return $this->errors = $return['errors'];
            }

            // Processing
            $results = $requestChangeStatus->processSendRequest($return['data']);

            $results ? $this->success = TRUE : $this->errors[] = 'Send email failed';
        }


    }

    /*
    ****************************************************************************
    */
}
