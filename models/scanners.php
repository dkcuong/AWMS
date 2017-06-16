<?php

use \common\logger;

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

class model extends base
{
    public $onScanner;

    public $confirmed = [];

    public $discrepant = [];

    public $quantity = 0;

    public $piecesPassed = [];

    public $transfersPassed = [];

    public $errors = [];

    public $noErrors = FALSE;

    public $modelTable = FALSE;

    public $response = [];

    public $warehouseData = NULL;

    public $noMezzanineVendors = [];

    public $licensePlates = [];

    public $flags = [];

    public $tableID = NULL;

    public $tableTitle = NULL;

    public $process = NULL;

    public $byWave = FALSE;

    public $byPlates = FALSE;

    public $step = NULL;

    public $paltesByWarehouse = [];

    public $originalPlates = [];

    public $plateLocations = [];

    public $success = FALSE;

    public $scanOrders = [];

    public $orders = [];

    public $checkOutStep = NULL;

    public $platesAsKeys = [];

    public $updatedStatuses = [];

    public $postOrders = [];

    public $skipGun = FALSE;

    public $status = NULL;

    public $statusName = NULL;

    public $passedCount = 0;

    public $errMsg = NULL;

    public $cartons = [];

    public $workOrderPassed = [];

    public $order = [];

    public $cartonCounts = 0;

    public $extraCartons = [];

    public $missedCartons = [];

    public $batchesPassed = [];

    public $wavePicks = [];

    public $listLocations = [];

    public $shippingInfo = [];

    public $bolOrders = [];

    public $cartonsPassed = [];

    public $existingCartons = [];

    public $processCartons = [];

    public $platesEntered = [];

    public $platesList = [];

    public $confirmSelectClientOrderNumber = [];

    static $importColumns = [
        'ucc' => 'UCC',
        'locations' => 'Location'
    ];

    public $scannerName = '';

    public $warehouseTransfers = [];

    public $warehouseTransferID = NULL;

    public $transferWarehouseIDs = [];

    /*
    ****************************************************************************
    */

    function modelForward($page, $step=FALSE, $noRedirect=FALSE)
    {
        $stepArray = $step ? ['step' => $step] : [];

        // If multiple values were passed
        $stepArray = is_array($step) ? $step : $stepArray;

        $next = makeLink('scanners', $page, $stepArray);

        return $noRedirect ? $next : redirect($next);
    }

    /*
    ****************************************************************************
    */

    function getScans()
    {
        $scans = getDefault($this->post['scans']);

        common\scanner::get($scans);

        if ($scans) {
            $scans = array_values($scans);
        }

        return $scans;
    }

    /*
    ****************************************************************************
    */

    function modelErrorHTML()
    {
        $errors = [];

        foreach ($this->errors as $error) {

            $error = is_array($error) ? implode('<br>', $error) : $error;

            if ($error) {
                $errors[] = '<div class="failedMessage centered">' . $error
                        . '</div>';
            }
        }

        echo $errors ?
                '<div class="centered">' . implode('<br>', $errors) . '</div>' :
                NULL;
    }

    /*
    ****************************************************************************
    */

    function gunHtmlDisplays()
    {
        $title = common\scanner::$scannerTitle;

        //if method is 'plateLocation' or 'shipOnGun', update $title
        $method = $this->get['method'];
        $process = getDefault($this->get['process']);
        $step = getDefault($this->step);

        $caption = NULL;

        if ($method == 'orderEntry') {
            $caption = $title[$process];
        } else {
            $titleValue = $process == 'checkIn' ?
                'Shipping Check-In' : 'Scan To Location';

            $titleShipOnGun = $step == 'in' ?
                'Shipping Check-In' : 'Shipped Check-Out';

            $titleValue = $method == 'shipOnGun' ? $titleShipOnGun : $titleValue;

            $title[$method] = $method == 'plateLocation' || $method == 'shipOnGun'
                ? $titleValue : $title[$method];

            $caption = $title[$method];
        }

        $pics = '<div class="centered"><span id="onlyOnScanner">
                    </span><span id="pageTitle">' . $caption . '</span>
                </div>';

        //making buttons
        $this->jsVars['urls']['goBack'] = $goBackLink
                = makeLink('main', 'mobileMenu');
        $this->jsVars['urls']['logout'] = $link = makeLink('logout');

        $goBack = '<td><a href="' . $goBackLink . '"> Go Back </a></td>';
        $logOut = '<td align="right"><a href="' . $link . '"> Log Out </a></td>';

        $btns = '<tr>' . $goBack . $logOut . '</tr>';

        //combine pictures and buttons

        $output = [
            'pictures' => $pics,
            'buttons' => $btns
        ];

        $this->gunHtmlDisplays = isset($_SESSION['onScanner']) ? $output : NULL;
    }

    /*
    ****************************************************************************
    */

    function btnsForCodeGun()
    {
        if ($this->onScanner) {
            $method = getDefault($this->get['method'], NULL);?>
            <tr>
              <td class="centered" colspan="2">
                <input type="submit" id="scanSubmit" class = "<?php
                    echo $method; ?>Submit" name="Submit" value="Submit">
                <?php
                for ($i = 0; $i < 20; $i++) {
                    echo '&nbsp;';
                } ?>
                <input align=bottom type=submit name=rescan value=Re-Scan>
              </td>
            </tr>  <?php
        }
    }

    /*
    ****************************************************************************
    */

    function submitButton()
    {
        ?>
        <input type="submit" id="scanSubmit" name="Submit" value="Submit"><br>
        <div id="scanCount" class="message"><span>0</span>: Rows</div>
        <br><br><br><br><br><br><br><br><br><?php
    }

    /*
    ****************************************************************************
    */

    function textArea()
    {
        if (! $this->onScanner) {
            $method = getDefault($this->get['method'], NULL); ?>
            <tr>
              <td>
                  <textarea name="scans" id="scans" rows="20"></textarea>
              </td>
              <td>
                <input type="submit" id="scanSubmit" class="<?php
                    echo $method; ?>Submit" name="Submit" value="Submit"><br>
                    <div id="scanCount" class="message"><span>0</span>: Rows</div>
                    <br><br><br><br><br><br><br><br><br>
                <input align=bottom type=submit id="rescan" name=rescan value=Re-Scan>
              </td>
            </tr>
        <?php
        } else { ?>
            <tr>
              <td colspan="2">
                  <textarea name="scans" id="scans" rows="10"></textarea>
              </td>
            </tr> <?php
        }
    }

    /*
    ****************************************************************************
    */

    function updateManualLocation($orderNumbers)
    {
        $pickCartons = new \tables\inventory\pickCartons($this);
        $locations = new \tables\locations($this);
        $cartons = new \tables\inventory\cartons($this);

        $orderCartonData = $pickCartons->getByOrderNumber($orderNumbers, 'mLocID');
        $locationData = $locations->getTypeLocationsByOrderNumber($orderNumbers);

        logger::getFieldIDs('cartons', $this);

        logger::getLogID();

        $this->beginTransaction();

        foreach ($orderNumbers as $orderNumber) {

            $cartonData = $orderCartonData[$orderNumber];

            $invIDs = array_keys($cartonData);

            $stagingLocID = $locationData[$orderNumber];

            foreach ($cartonData as &$carton) {
                $carton['locID'] = $stagingLocID;
            }

            $qMarkString = $this->getQMarkString($invIDs);

            $sql = 'UPDATE inventory_cartons
                    SET    mLocID = ?
                    WHERE  id IN (' . $qMarkString . ')';

            $params = $invIDs;

            array_unshift($params, $stagingLocID);

            $this->runQuery($sql, $params);

            $cartons->logCartonManualData($cartonData);
        }

        $this->commit();
    }

    /*
    ****************************************************************************
    */

    function removeCartonNumber($uccs)
    {
        $sql = 'SELECT      ca.id,
                            CONCAT(v.id, b.id, LPAD(uom, 3, 0)) AS uccResidue
                FROM        inventory_cartons ca
                LEFT JOIN   inventory_batches b
                ON          b.id = ca.batchID
                LEFT JOIN   inventory_containers co
                ON          co.recNum = b.recNum
                LEFT JOIN   vendors v
                ON          v.id = co.vendorID
                WHERE       CONCAT(
                                v.id,
                                b.id,
                                LPAD(uom, 3, 0),
                                LPAD(cartonID, 4, 0))
                IN          (' . $this->getQMarkString($uccs) . ')';

        return $this->queryResults($sql, $uccs);
    }

    /*
    ****************************************************************************
    */

    function getUccResidues($cartonsInOrder)
    {
        $uccResidueCount = [];
        foreach ($cartonsInOrder as $orderNumber => $cartonUccs) {
            $uccResidues = $this->removeCartonNumber($cartonUccs);

            foreach ($uccResidues as $residue) {
                $residueValue = $residue['uccResidue'];
                $uccResidueCount[$orderNumber][$residueValue]
                    = isset($uccResidueCount[$orderNumber][$residueValue])
                      ? ++$uccResidueCount[$orderNumber][$residueValue]
                      : 1;
            }
        }

        return $uccResidueCount;
    }

    /*
    ****************************************************************************
    */

    function mezzanineTransferConfirmResults()
    { ?>

        <table style="border-spacing: 20px 0px;">
            <tr> <?php

            $this->displayTransferConfirmResult($this->confirmed, 'Confirmed');

            if ($this->gunHtmlDisplays) { ?>

                </tr><tr><?php
            }

            $this->displayTransferConfirmResult($this->discrepant, 'Discrepant'); ?>

            </tr><?php

            echo $this->gunHtmlDisplays['buttons']; ?>

        </table><?php
    }

    /*
    ****************************************************************************
    */

    function displayTransferConfirmResult($results, $title)
    {
        $tableID = $title == 'Confirmed' ? 'approved' : 'rejected';
        $colspan = $this->gunHtmlDisplays ? 'colspan="2"' : NULL;

        if ($results) { ?>

            <td style="vertical-align: top;" <?php echo $colspan; ?>>
                <table id="<?php echo $tableID; ?>">
                    <tr><td><?php echo $title; ?> Transfers</td></tr><?php

            foreach ($results as $transferNumber) { ?>

                    <tr><td><?php echo $transferNumber; ?></td></tr><?php
            } ?>

                </table>
            </td><?php
        }
    }

    /*
    ****************************************************************************
    */

    function orderCancelErrorMessage($scanOrders, $orders)
    {
        $errors = [];

        $checkResults = $orders->checkIfOrderProcessed($scanOrders);

        foreach ($scanOrders as $scanOrder) {

            $isClosed = $checkResults['processedOrders'][$scanOrder];

            if ($isClosed) {

                $message = $checkResults['canceledOrders'][$scanOrder] ?
                        'already been canceled. Go to Order Check-Out page to '
                        . 'release it.' : 'been processed.';

                $errors[] = 'Order ' . $scanOrder . ' has ' . $message;
            }
        }

        $this->errors[] = implode('<br>', $errors);
    }

    /*
    ****************************************************************************
    */

    function tableLicensePlateOutput()
    {
        $count = 1;
        $plates = '';
        foreach ($this->licensePlate as $location => $plateArray) {
            $message = '<tr><td style="text-align: center;">' . $count . '</td>';
            $message .= '<td>' . $location . '</td>';
            if (is_array($plateArray)) {
                foreach ($plateArray as $key => $plate) {
                    if ($plate === end($plateArray)) {
                        $plates .= $plate;
                    } else {
                        $plates .= $plate . ', ';
                    }
                }
            } else {
                $plates = 'License plate empty';
            }
            $message .= '<td>' . $plates . '</td></tr>';
            $count++;
            $plates = '';
            echo $message;
        }
    }

    /*
    ****************************************************************************
    */

    function checkDuplicateReceivingUCCs($licensePlates)
    {
        $checkedCartons = $duplicateCartons = [];

        foreach ($licensePlates as $plateCartons) {
            foreach ($plateCartons['cartonNumbers'] as $carton) {
                if (! is_array($carton)) {
                    // skip invalid UCCs
                    continue;
                }

                $invID = key($carton);

                $ucc = $carton[$invID];

                if (isset($checkedCartons[$ucc])) {

                    $duplicateCartons[] = $ucc;

                    continue;
                }

                $checkedCartons[$ucc] = TRUE;
            }
        }

        return $duplicateCartons;
    }

    /*
    ****************************************************************************
    */

    function checkReceivedCartons($data)
    {
        $invIDs = $data['invIDs'];
        $plateCartons = $data['plateCartons'];
        $cartons = $data['cartons'];

        $receivedCartons = $cartons->getReceivedCartons($invIDs);

        $return = $errors = [];

        foreach ($receivedCartons as $invID) {
            foreach ($plateCartons as $carton) {

                $key = key($carton);

                if ($key == $invID) {

                    $errors[] = $carton[$key];

                    continue 2;
                }
            }
        }

        if ($errors) {
            $return[] = 'Cartons that have already been received:<br>'
                    . implode('<br>', $errors);
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function checkHasRackedCartons($invIDs, $plateCartons)
    {
        $return = $errors = [];

        if (! $invIDs) {
            return $return;
        }

        $data = \tables\inventory\cartons::getNotRackedCartons($this, $invIDs);

        foreach ($data as $invID) {
            foreach ($plateCartons as $carton) {

                $key = key($carton);

                if ($key == $invID) {

                    $errors[] = $carton[$key];

                    continue 2;
                }
            }
        }

        if ($errors) {
            $return[] = 'Cartons that have not Racked:<br>'
                    . implode('<br>', $errors);
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function receivingToStockConfirmResults()
    {
        if ($this->flags) { ?>

            <tr>
              <td>License Plate</td>
              <td>Quantity Scanned</td>
              <td>Quantity Entered</td>
            </tr>

            <?php foreach ($this->flags as $plate => $quantities) { ?>

            <tr>
                <td><?php echo $plate; ?></td>
                <td><?php echo $quantities['scanned']; ?></td>
                <td><?php echo $quantities['confirmed']; ?></td>
            </tr>

            <?php }
        } else { ?>

            <tr>
                <td>License Plate</td>
                <td>Carton</td>
                <td>Status</td>
             </tr>

            <?php

            $this->displayReceivingToStockResults();
        }
    }

    /*
    ****************************************************************************
    */

    function inventoryTransferConfirmResults()
    {
        if ($this->flags) { ?>

             <tr>
               <td>License Plate</td>
               <td>Quantity Scanned</td>
               <td>Quantity Entered</td>
             </tr>

             <?php foreach ($this->flags as $plate => $quantities) { ?>

             <tr>
                 <td><?php echo $plate; ?></td>
                 <td><?php echo $quantities['scanned']; ?></td>
                 <td><?php echo $quantities['confirmed']; ?></td>
             </tr>

             <?php }
        } else { ?>

            <tr>
                <td>UCC</td>
                <td>Old License Plate</td>
                <td>Old Location</td>
                <td>New License Plate</td>
                <td>New Location</td>
             </tr>

            <?php

            $this->displayInventoryTransferResults(TRUE);
        }
   }

    /*
    ****************************************************************************
    */

    function displayInventoryTransferResults()
    {
        foreach ($this->licensePlates as $plate => $plateCartons) {
            foreach ($plateCartons['cartonNumbers'] as $carton) {

                $invID = key($carton);
                $ucc = $carton[$invID];
                $oldLocID = $this->results['invIDs'][$invID]['oldLocID'];
                $oldLocationName =
                        $this->results['locationInfos'][$oldLocID]['displayName'];
                $oldPlates = $this->results['invIDs'][$invID]['oldPlate'];

                ?>

                <tr>
                    <td><?php echo $ucc; ?></td>
                    <td><?php echo $oldPlates; ?></td>
                    <td><?php echo $oldLocationName; ?></td>
                    <td><?php echo $plate; ?></td>
                    <td><?php echo $plateCartons['locationInfo']['locationName']; ?></td>
                </tr>
            <?php }
        }
    }

    /*
    ****************************************************************************
    */

    function displayReceivingToStockResults()
    {
        foreach ($this->licensePlates as $plate => $plateCartons) {
            foreach ($plateCartons['cartonNumbers'] as $carton) {

                $invID = key($carton);

                $ucc = $carton[$invID]; ?>

                <tr>
                    <td><?php echo $plate; ?></td>
                    <td><?php echo $ucc; ?></td>
                    <td>RC</td>
                </tr>
            <?php }
        }
    }

    /*
    ****************************************************************************
    */

    function transferTemplate()
    {
        $exporter = new \excel\exporter($this);
        $params = [
            'fileName' => 'ucc_transfer_mezzanine_template',
            'fieldKeys' => [
                ['title' => 'UCC'],
                ['title' => 'Location']
            ],
            'data' => [
                ['11195100000910200009', 'Z-12-A-L1-01'],
                ['11202100000740200010', 'Z-12-A-L1-02'],
                ['11195100000910200004', 'Z-12-A-L1-01'],
                ['11204100000030200001', 'Z-12-A-L1-02']
            ]
        ];

        $exporter->ArrayToExcel($params);
    }

    /*
    ****************************************************************************
    */

    function getPlateCounts($licensePlates, $masterLabels)
    {
        foreach ($licensePlates as &$plateData) {
            foreach ($plateData['cartonNumbers'] as $invID) {

                $results = $this->subtractMasterLabelCount([
                    'plateData' => $plateData,
                    'masterLabels' => $masterLabels,
                    'ucc' => reset($invID)
                ]);

                $plateData = $results['plateData'];
                $masterLabels = $results['masterLabels'];
            }
        }

        return $licensePlates;
    }

    /*
    ****************************************************************************
    */

    function subtractMasterLabelCount($data)
    {
        $plateData = $data['plateData'];
        $masterLabels = $data['masterLabels'];
        $ucc = $data['ucc'];

        foreach ($masterLabels as $masterLabel => $masterCartons) {
            if (in_array($ucc, $masterCartons)) {
                // subtarct amount of cartons in a Master Label to get actual
                // amount of scanned
                $plateData['count'] -= count($masterCartons) - 1;

                unset($masterLabels[$masterLabel]);

                return [
                    'plateData' => $plateData,
                    'masterLabels' => $masterLabels,
                ];
            }
        }

        return [
            'plateData' => $plateData,
            'masterLabels' => $masterLabels,
        ];
    }

    /*
    ****************************************************************************
    */

    function checkOrderEntryScanner($params, $classes)
    {
        $process = $this->process;
        $scanOrders = $this->scanOrders;

        $orders = $classes['orders'];
        $requires = getDefault($params[$process]['requires']);

        if ($scanOrders && $process == 'orderCheckOut') {

            $this->truckOrders =
                $classes['truckOrderWaves']->getExistingTruckOrders($scanOrders);

            if ($this->truckOrders && count($scanOrders) > 1) {

                $this->errors[] = 'Truck Orders should be scanned separately';

                return;
            }
        }

        $target = 'scanOrderNumber';

        $paramsLogScanInput = [
            'app' => $this,
            'scanInput' => $this->post['scans']
        ];

        $prefix = NULL;

        if ($process == 'orderCheckOut') {

            $_SESSION['orderType'] = $paramsLogScanInput['inputOption'] =
                    $target = $this->post['orderNumberType'];

            switch ($target) {
                case 'clientordernumber':
                    $prefix = 'Client ';
                    break;
                case 'customerOrderNumber':
                    $prefix = 'Customer ';
                    break;
                default:
                    $prefix = 'Scan ';
                    break;
            }

            $this->checkOrderDuplicates($target, $orders, $prefix);
        }

        logger::createLogScanInput($paramsLogScanInput);

        $results = $orders->valid($scanOrders, $target, [
            'assoc' => 'id',
            'field' => $orders->primaryKey
        ]);

        $validOrders = array_column($results['perRow'], 'target');

        $invalidOrders = array_diff($scanOrders, $validOrders);

        if ($invalidOrders) {

            $this->errors[] = $prefix . 'Order(s) Not Found:<br><br>'
                    . implode('<br>', $invalidOrders);

            return;
        }

        //Check if any order is Error Order
        $errOrders = $orders->onHoldOrError([
            'order' => $scanOrders,
            'where' => $target,
            'select' => 'isError',
        ]);

        if ($errOrders && $process != 'errOrderRelease') {

            $this->errors[] = $errOrders;

            return;
        }

        //Check if any order is onhold
        $onHoldOrders = $orders->onHoldOrError([
            'order' => $scanOrders,
            'where' => $target,
        ]);

        if ($onHoldOrders) {

            $this->errors[] = $onHoldOrders;

            return;
        }

        if ($process == 'pickingCheckIn') {

            $stagingErrors = $orders->checkStagingLocation($this, $scanOrders);

            if ($stagingErrors) {

                $this->errors[] = implode('<br>', $stagingErrors);

                return;
            }
        }

        if (! in_array($process, ['errOrderRelease', 'orderCheckOut', 'cancel'])) {

            $error = common\scanner::rejectOnlineOrdersMessage($scanOrders, $orders);

            if ($error) {

                $this->errors[] = $error;

                return;
            }
        }

        if (isset($this->post['printLading'])) {

            if (getDefault($_SESSION['onScanner'])) {
                // do not generate BoL if the user is on the mobile page
                $this->errors[] = 'BOL for mobile users is not allowed';

                return;
            }

            $ordernumbers = array_column($results['perRow'], 'target');

            lading::displayLadings($this, $ordernumbers);

            return;
        }

        if ($process != 'orderCheckOut' && $requires) {

            $missing = $this->getMissingOrdersErrors($classes, $params, $target);

            if ($missing) {
                return;
            }
        }

        if ($process == 'cancel') {
            $this->orderCancelErrorMessage($scanOrders, $orders);
        }

        return $results['perRow'];
    }

    /*
    ****************************************************************************
    */

    function checkOrderDuplicates($target, $orders, $prefix)
    {
        if ($target != 'scanOrderNumber') {

            $duplicates = $orders->duplicateCheck($target, $this->scanOrders);

            if ($duplicates) {
                $this->errors[] = $prefix . 'Order(s) that refer to multiple Scan'
                        . ' Order Number:<br><br>' . implode('<br>', $duplicates);
            }

            if ($target == 'clientordernumber') {

                $results = $orders->checkDuplicateClientOrderNumber($this->scanOrders);

                $return = [];

                foreach ($results as $scanOrderNumber => $values) {
                    $values['scanOrderNumber'] = $scanOrderNumber;
                    $return[$values['clientordernumber']][] = $values;
                }

                $this->confirmSelectClientOrderNumber = $return;

            }
        }
    }

    /*
    ****************************************************************************
    */

    function getMissingOrdersErrors($classes, $params, $target)
    {
        $process = $this->process;
        $scanOrders = $this->scanOrders;
        // If checking out make sure all orders have been checked in
        $missing = $requiredStatusIDs = [];

        $statuses = $classes['orderStatuses'];
        $requires = getDefault($params[$process]['requires']);
        $errorName = getDefault($params[$process]['errorName']);

        if ($requires) {

            $requires = is_array($requires) ? $requires : [$requires];

            $requiredStatusID = $statuses->getStatusIDs($requires);

            $keys = array_keys($requiredStatusID);
            $values = array_column($requiredStatusID, 'id');

            $requiredStatusIDs = array_combine($keys, $values);
        }

        if ($requiredStatusIDs) {
            switch ($process) {
                case 'errOrderRelease':
                    $statusField = 'isError';
                    break;
                case 'routedCheckIn':
                case 'routedCheckOut':
                    $statusField = 'routedStatusID';
                    break;
                default:
                    $statusField = 'statusID';
                    break;
            }

            $statusChecks = $statuses->checkStatus($scanOrders, $target,
                $requiredStatusIDs, $statusField);

            $checkValues = array_keys($statusChecks);

            $missing = array_diff_key($scanOrders, $checkValues);
        }

        if ($missing) {

            $prefix = $process == 'errOrderRelease' ? 'an' : 'set to';

            $errors = [];

            foreach ($missing as $missingOrder) {
                $errors[] = 'Order ' . $missingOrder . ' is not ' . $prefix
                        . ' ' . $errorName;
            }

            $this->errors[] = implode('<br>', $errors);

            return TRUE;
        }

        return FALSE;
    }

    /*
    ****************************************************************************
    */

    function updateOrderEntryScannerOrders($classes)
    {
        $process = $this->process;
        $ordersPassed = $this->ordersPassed;

        $orderIDs = array_column($ordersPassed, 'id');

        if ($process == 'errOrderRelease') {
            $field = 'isError';
        } else {
            $field = in_array($process, ['routedCheckIn', 'routedCheckOut']) ?
                    'routedStatusID' : 'statusID';
        }

        $orderNumbers = array_column($ordersPassed, 'target');

        switch ($process) {
            case 'orderCheckOut':

                unset($_SESSION['orderType']);

                $json = json_encode($orderIDs);

                $link = makeLink('orders', 'addOrEdit', [
                    'type' => 'Check-Out',
                    'orderIDs' => urlEncode($json)
                ]);

                redirect($link);

                break;
            case 'pickingCheckOut':

                $json = json_encode($orderIDs);

                $link = makeLink('orders', 'pickingCheckOut', [
                    'orderIDs' => urlEncode($json)
                ]);

                redirect($link);

                break;
            case 'orderProcessCheckOut':

                unset($_SESSION['processCartons']);

                $values = array_column($ordersPassed, 'id');

                $this->ordersPassed = \common\scanner::orderProcessingCheckOut([
                    'app' => $this,
                    'ordersPassed' => array_combine($orderNumbers, $values),
                    'platesEntered' => $this->platesEntered,
                    'classes' => $classes,
                ]);

                foreach ($this->ordersPassed as $orderNumber => $plates) {
                    foreach ($plates as $plate) {
                        $this->platesList[] = $plate;
                    }
                }

                break;
            default:
                \common\order::updateAndLogStatus([
                    'orderIDs' => $orderIDs,
                    'statusID' => $this->status,
                    'field' => $field,
                    'tableClass' => $classes['orders'],
                ]);
        }

        switch ($process) {
            case 'pickingCheckIn':
                $this->updateManualLocation($orderNumbers);
                break;
            case 'cancel':
                $classes['wavePicks']->clear($orderNumbers);
                break;
            default:
                break;
        }
    }

    /*
    ****************************************************************************
    */

    function displayOrderEntryResult()
    { ?>

        <table id="<?php echo $this->tableID; ?>">

        <?php if ($this->error) {
            if ($this->process == 'orderProcessCheckOut') { ?>

                <tr>
                    <td colspan="3">
                        <strong>
                            <?php echo $this->tableTitle; ?><br>
                            Number of entered License Plates can not be greater than number of cartons
                        </strong>
                    </td>
                </tr>

                <tr>
                    <td>Order Number</td>
                    <td>Plates Entered</td>
                    <td>Carton Count</td>
                </tr>

                <?php foreach ($this->error as $orderNumber => $values) { ?>

                <tr>
                    <td><?php echo $orderNumber; ?></td>
                    <td><?php echo $values['entered']; ?></td>
                    <td><?php echo $values['cartonCount']; ?></td>
                </tr>

                <?php }
            } else { ?>

            <tr>
                <td colspan="2">Number of orders scanned: <?php echo $this->passedCount; ?><br>
                Quantity entered: <?php echo $this->quantity; ?></td>
            </tr>

            <?php }
        } else {
            if ($this->process == 'orderProcessCheckOut') { ?>

            <tr>
                <td colspan="3">
                    <strong><?php echo $this->tableTitle; ?></strong>
                </td>
            </tr>
            <tr>
                <td>Order</td>
                <td>New Status</td>
                <td colspan="2"><a class="printAllPlate"  href="#">License Plate</a></td>
            </tr>

                <?php foreach ($this->ordersPassed as $orderNumber => $plates) {
                    foreach ($plates as $plate) { ?>

                <tr>
                    <td><?php echo $orderNumber; ?></td>
                    <td><?php echo $this->statusName; ?></td>
                    <td><a class="printLicenseplate" target="_blank"
                           href="<?php echo makeLink('plates', 'display', [
                            'search' => 'plates',
                            'term' => $plate,
                            'level' => 'order',
                         ]); ?>"><?php echo $plate; ?></a>
                    </td>
                </tr>

                    <?php }
                }
            } else { ?>

            <tr>
                <td colspan="2">
                    <strong><?php echo $this->tableTitle; ?></strong>
                </td>
            </tr>
            <tr>
                <td>Order</td>
                <td>New Status</td>
            </tr>

                <?php foreach ($this->ordersPassed as $order) { ?>

            <tr>
                <td><?php echo $order['target']; ?></td>
                <td><?php echo $this->statusName; ?></td>
            </tr>

                <?php }
            }
        }
    }

    /*
    ****************************************************************************
    */

    function checkEnteredPlatesQuantity()
    {
        $platesCheck = [];

        $this->platesEntered = array_combine($this->post['orderNumber'],
                $this->post['quantity']);

        foreach ($this->processCartons as $orderNumber => $cartonCount) {

            $entered = $this->platesEntered[$orderNumber];

            if ($entered < 0 || $cartonCount < $entered) {
                $platesCheck[$orderNumber] = [
                    'entered' => (int)$entered,
                    'cartonCount' => $cartonCount,
                ];
            }
        }

        return $platesCheck;
    }

    /*
    ****************************************************************************
    */

    function correctPassedOrder($classes)
    {
        $post = $this->post;
        foreach ($this->ordersPassed as &$values) {
            $clientID = getDefault($post[$values['target']]);
            if ($clientID) {
                $values['id'] =
                $classes['orders']->getOrderIDByClientAndClientOrder($clientID,
                    $values['target']);
            }
        }

        $_SESSION['orders'] = $this->ordersPassed;
    }

    /*
    ****************************************************************************
    */

    function transferWarehouses()
    { ?>

        <tr>
            <td id="instructions" colspan="2">

                Select a Transfer <select name="warehouseTransferID">

                <?php

                $count = 0;

                foreach ($this->warehouseTransfers as $transferID => $transfer) {

                    $selected = $count == 0 ? ' selected' : NULL; ?>

                    <option value="<?php echo $transferID; ?>" <?php echo $selected; ?>>
                        <?php echo $transfer; ?>
                    </option>

                    <?php

                    $count++;
                } ?>

                </select>

            </td>
        </tr>

    <?php }

    /*
    ****************************************************************************
    */

    function verifyLicensePlateQuantityTable()
    { ?>

        <form method="POST">
        <table id="confirm">
            <tr>
                <td colspan="2"><b>Verify License Plate Quantity<b></td>
            </tr>
            <tr>
                <td>Enter license plate quantity</td>
                <td><input type="text" name="quantity"></td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" name="submit" value="Submit"></td>
            </tr>

        </table>
        </form>

    <?php }

    /*
    ****************************************************************************
    */

    function confirmLicensePlates()
    { ?>

        <table id="<?php echo $this->tableID; ?>">
        <tr>
            <td colspan="2"><b><?php echo $this->tableTitle; ?></b></td>
        </tr>

        <?php if ($this->noErrors) { ?>
            <tr>
                <td>License Plate</td>
                <td>Location</td>
            </tr>

            <?php foreach ($this->plateLocations as $plate => $locations) { ?>

            <tr>
                <td><?php echo $plate; ?></td>
                <td><?php echo $locations; ?></td>
            </tr>

            <?php }
        } else { ?>

            <tr>
                <td colspan="2">Number of license plates scanned: <?php echo count($this->plateLocations); ?><br>
                Quantity entered: <?php echo $this->quantity; ?></td>
            </tr>

        <?php }

       echo $this->gunHtmlDisplays['buttons']; ?>

        </table>

    <?php }

    /*
    ****************************************************************************
    */

    function confirmShippingBoLs()
    {
        $statuses = new \tables\statuses\shipping($this);

        // getStatusIDs($descriptions=[], $returnQuery=FALSE, $field='shortName')
        $shippingStatuses = $statuses->getStatusIDs([], FALSE, 'displayName');

        $shippingOptions = NULL;

        foreach ($shippingStatuses as $status => $values) {

            $selected = $status == \tables\orders::STATUS_SHIPPING_SHIPPING ?
                    ' selected' : NULL;

            $shippingOptions .= '
                <option value="' . $values['id'] . '" '
                    . $selected . '>' . $values['displayName'] . '
                </option>';
        }

        foreach ($this->BOLs as $bolNumber) { ?>
            <tr>
                <td><?php echo $bolNumber; ?>
                    <input type="hidden" value="<?php echo $bolNumber; ?>"
                           name="BOLs[<?php echo $bolNumber; ?>][newBOL]">
                </td>

                <?php $value = getDefault($this->postOrders[$bolNumber]); ?>

                <td>
                    <input type="text" value="<?php echo $value; ?>" required
                           class="<?php echo 'BOLQuantities[' . $bolNumber . ']'; ?>"
                           name="BOLQuantities[<?php echo $bolNumber; ?>]">
                </td>
            </tr>

            <?php foreach ($this->bolOrders[$bolNumber] as $orderNumber) { ?>

            <tr>
                <td><span class="bolOrder"><?php echo $orderNumber; ?></span></td>
                <td>
                    <select name="orderConditions[<?php echo $orderNumber; ?>]" >
                        <?php echo $shippingOptions; ?>
                    </select>
                </td>
            </tr>

            <?php }
        }
    }

    /*
    ****************************************************************************
    */

}
