<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    /*
    ****************************************************************************
    */

    function confirmMezzanineTransfersScannersView()
    {
        if (! getDefault($this->get['step'])) {

            $this->modelErrorHTML();

            echo $this->gunHtmlDisplays['pictures']; ?>

            <form id="scannerForm" method="post">
            <table id="scanner">
            <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Scan Mezzanine Transfer Barcode <br>
            2. Scan Amount of Pieces Transfered <br>
            3. Repeat as necessary <br>
            4. Click Submit
            </td>
            </tr> <?php

            $this->btnsForCodeGun();
            $this->textArea();

            echo $this->gunHtmlDisplays['buttons']; ?>

            </table>
            </form><?php

            return;
        }

        if (getDefault($this->get['step']) == 'confirm' && ! $this->quantity) { ?>

            <form method="POST">
            <table id="confirm">
            <tr>
                <td colspan="2"><b>Verify Mezzanine Transfers Quantity<b></td>
            </tr>
            <tr>
              <td>Enter Transfer quantity</td>
              <td><input type="text" name="quantity"></td>
            </tr>
            <tr><td></td>
                <td><input type="submit" name="submit" value="Submit"></td></tr><?php

            if ($this->gunHtmlDisplays) {
                echo $this->gunHtmlDisplays['buttons'];
            } ?>

            </table>
            </form><?php
        }

        // Update and create invoices

        if ($this->quantity) {
            if ($this->noErrors) {
                $this->mezzanineTransferConfirmResults();
            } else { ?>

            <table id="rejected">
                <tr>
                    <td colspan="2"><b>You have entered the incorrect quantity</b></td>
                </tr>
                <tr>
                    <td colspan="2">Number of transfers scanned: <?php
                        echo count($this->transfersPassed); ?><br>
                    Quantity entered: <?php echo $this->quantity; ?></td>
                </tr><?php

                if ($this->gunHtmlDisplays) {
                    echo $this->gunHtmlDisplays['buttons'];
                } ?>

            </table><?php

            }
        }
    }

    /*
    ****************************************************************************
    */

    function searchInactiveInventoryScannersView()
    {
        $this->modelErrorHTML();
        if (! $this->modelTable) {
        ?>
        <form method="post">
        <table id="scanner">
        <tr>
            <td id="instructions" colspan="2">
            <input type="radio" name='processType' value="locID" checked>
            Location Name<br>
            <input type="radio" name='processType' value="ucc128">
            UCC / Master Label<br>
            </td>
        </tr>
        <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Enter a Location/UCC one per line.<br>
            2. Click Submit
            </td>
        </tr>
        <tr>
            <td>
            <textarea name="scans" id="scans" rows="20" cols="20"></textarea>
            </td>
            <td rowspan="2">
            <input type="submit" name="inactiveInventorySearch" value="Submit">
            </td>
        </tr>
        </table>
        </form>
        <?php
        } else {
            echo $this->datatablesStructuresHTML['inactive'];
        }
    }

    /*
    ****************************************************************************
    */

    function searchStyleLocationsScannersView()
    {
        $this->modelErrorHTML();
        if (! $this->modelTable) {
        ?>
        <form method="post">
        <table id="scanner">
        <tr>
            <td id="instructions" colspan="2">
            <input type="radio" name='processType' value="sku" checked>
            Stock Keeping Unit (SKU)<br>
            <input type="radio" name='processType' value="upc">
            Universal Product Code (UPC)<br>
            <input type="radio" name='processType' value="plate">
            License Plate<br>
            </td>
        </tr>
        <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Enter a SKU/UPC/Plate one per line.<br>
            2. Click Submit
            </td>
        </tr>
        <tr>
            <td>
            <textarea name="scans" id="scans" rows="20" cols="20"></textarea>
            </td>
            <td rowspan="2">
            <input type="submit" name="stylesLocationSearch" value="Submit">
            </td>
        </tr>
        </table>
        </form>
        <?php
        } else {
            echo $this->datatablesStructuresHTML['styleLocations'];
        }
    }

    /*
    ****************************************************************************
    */

    function zeroOutInventoryScannersView()
    {
        $this->modelErrorHTML();
        if (! $this->response) {
        ?>
        <form method="post">
        <table id="scanner">
        <tr>
            <td id="instructions" colspan="2">
            <input type="radio" name="processType" class="changeSelect"
                   value="locID" checked>
            Location Name<br>
            <input type="radio" name="processType" class="changeSelect"
                   value="ucc128">
            UCC / Master Label<br>
            </td>
        </tr>
        <tr>
            <td id="instructions" colspan="2">
                <select id="warehouse" name="warehouseID" style="width: 100%;">
        <?php foreach ($this->warehouseData as $warehouseID => $warehouseName) { ?>
                    <option value="<?php echo $warehouseID; ?>"><?php
                        echo $warehouseName?> </option>
        <?php } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Enter a Location/UCC one per line.<br>
            2. Click Submit
            </td>
        </tr>
        <tr>
            <td>
            <textarea name="scans" id="scans" rows="20" cols="20"></textarea>
            </td>
            <td rowspan="2">
            <input type="submit" name="inactiveInventorySearch" value="Submit">
            </td>
        </tr>
        </table>
        </form>
        <?php
        } else {
        ?>
        <table style="border-spacing: 20px 0px;">
        <tr>
            <td style="vertical-align: top;">
            <?php
            if (getDefault($this->response['zeroOutCartonsInfo'])) {
                $count = count($this->response['zeroOutCartonsInfo']);
                ?>
                <table id="approved">
                <tr>
                    <td colspan="3"><b>Zeroed Out Inventory<br>
                        Total: <?php echo $count; ?> carton(s)</b>
                    </td>
                </tr>
                <tr>
                    <td>Carton</td>
                    <td>Location Name</td>
                    <td>License Plate</td>
                </tr>
                <?php
                foreach ($this->response['zeroOutCartonsInfo'] as $info) { ?>
                    <tr>
                        <td><?php echo $info['ucc128']; ?></td>
                        <td><?php echo $info['displayName']; ?></td>
                        <td><?php echo $info['plate']; ?></td>
                    </tr>
                <?php
                }
                ?>
                </table>
                <?php
            } ?>
            </td>
            <td style="vertical-align: top;">
            <?php if (getDefault($this->response['notZeroOutCartonsInfo'])) {
                $count = count($this->response['notZeroOutCartonsInfo']);
                ?>
                <table id="rejected">
                <tr>
                    <td colspan="5"><b>Zero Out Rejected Inventory<br>
                        Total: <?php echo $count; ?> carton(s)</b>
                    </td>
                </tr>
                <tr>
                    <td>Carton</td>
                    <td>Status</td>
                    <td>Manual Status</td>
                    <td>Split</td>
                    <td>Unsplit</td>
                </tr>
                <?php
                foreach ($this->response['notZeroOutCartonsInfo'] as $ucc => $info) { ?>
                    <tr>
                        <td><?php echo $ucc; ?></td>
                        <td><?php echo $info['status']; ?></td>
                        <td><?php echo $info['mStatus']; ?></td>
                        <td><?php echo $info['isSplit'] ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $info['unSplit'] ? 'Yes' : 'No'; ?></td>
                    </tr>
                <?php
                }
                ?>
                </table>
                <?php
            } ?>
            </td>
        </tr>
        </table>
        <?php
        }
    }

    /*
    ****************************************************************************
    */

    function searchNoMezzanineScannersView()
    {
        $this->modelErrorHTML();
        if (! $this->modelTable) { ?>
        <form method="post">
        <table id="scanner">
        <tr>
            <td id="instructions" colspan="2">
            <select id="vendor" name="vendorID">
                <option value="0">All Clients</option>
                <?php foreach ($this->noMezzanineVendors as $vendorID => $name) { ?>
                    <option value="<?php echo $vendorID; ?>"><?php echo $name['vendor']; ?> </option>
                <?php } ?>
            </select>
            <hr/>
            <input type="radio" name='processType' value="sku" checked>
            Stock Keeping Unit (SKU)<br>
            <input type="radio" name='processType' value="upc">
            Universal Product Code (UPC)<br>
            </td>
        </tr>
        <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Enter a SKU/UPC one per line.<br>
            2. Click Submit
            </td>
        </tr>
        <tr>
            <td>
            <textarea name="scans" id="scans" rows="20" cols="20"></textarea>
            </td>
            <td rowspan="2">
            <input type="submit" name="noMezzanineSearch" value="Submit">
            </td>
        </tr>
        </table>
        </form>
        <?php
        } else {
            echo $this->datatablesStructuresHTML['noMezzanine'];
        }
    }

    /*
    ****************************************************************************
    */

    function searchStyleUOMsScannersView()
    {
        $this->modelErrorHTML();
        if (! $this->modelTable) {
        ?>
        <form method="post">
        <table id="scanner">
        <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Enter a SKU one per line.<br>
            2. Click Submit
            </td>
        </tr>
        <tr>
            <td>
            <textarea name="scans" id="scans" rows="20" cols="20"></textarea>
            </td>
            <td rowspan="2">
            <input type="submit" name="stylesUOMSearch" value="Submit">
            </td>
        </tr>
        </table>
        </form>
        <?php
        } else {
            echo $this->datatablesStructuresHTML['styleUOMs'];
        }
    }

    /*
    ****************************************************************************
    */

    function receivingToStockScannersView()
    {
        // Starting Page
        if (! getDefault($this->get['step'])) {
            $this->modelErrorHTML();
            echo $this->gunHtmlDisplays['pictures'];
            ?>
            <form id="scannerForm" method="post">
            <table id="scanner">
            <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Scan license plate to open<br>
            2. Scan carton number(s)<br>
            3. Scan license plate to close<br>
            4. Repeat as necessary<br>
            5. Click Submit Button
            </td>
            </tr>

            <?php $this->btnsForCodeGun(); ?>
            <?php $this->textArea(); ?>

            <?php echo $this->gunHtmlDisplays['buttons']; ?>
            </table>

            </form><?php

        }

        // Verify license plate values passed

        if (getDefault($this->get['step']) == 'confirmValues') { ?>

            <form method="POST">
            <table id="confirm">
            <tr>
                <td colspan="2"><b>Verify License Plate Carton Quantities<b></td>
            </tr>
            <tr>
              <td>License Plate</td>
              <td>Enter Quantity</td>
            </tr>
            <?php
            foreach (array_keys($this->licensePlates) as $plate) { ?>
                <tr>
                  <td><?php echo $plate; ?></td>
                  <td><input type="text" name="quantities[<?php echo $plate; ?>]"></td>
                </tr><?php
            }
            ?>
            <tr><td></td>
                <td><input type="submit" name="submit" value="Submit"></td></tr>

            </table>
            </form><?php
        }

        // Update and create invoices

        if (getDefault($this->get['step']) == 'compareValues') { ?>

            <table id="<?php echo $this->tableID; ?>">
            <tr>
                <td colspan="3"><b><?php echo $this->tableTitle; ?></b></td>
            </tr>

            <?php

            $this->receivingToStockConfirmResults();

            echo $this->gunHtmlDisplays['buttons']; ?>

            </table><?php
        }
    }

    /*
    ****************************************************************************
    */

    function shipOnGunScannersView()
    {
        ?>
        <div class="centered">
          <div class="failedMessage" style="display: none"></div>
          <div class="showsuccessMessage" style="display: none"> <?php
        if ($this->step == 'in') { ?>
            Order and cartons statuses have been updated to 'Shipping Check In'<?php
        } else {  ?>
            Current License Plate Number is good <br> Continue scan or Submit  <?php
        }?>
          </div>

        </div>

        <?php
        if (! getDefault($this->checkOutStep)) {        ?>

        <form id="autoForm" method="post">
        <table id="scanner">
        <tr>
        <td id="instructions" colspan="2">
        Scan <span id="needToScan">License Plate Number</span>:  <br>
        </td>
        </tr>
        <tr>
            <td>
                <textarea id="autoScans" name="scans"></textarea>
            </td>
        </tr>
        <?php
        if ($this->step == 'out') { ?>
        <tr>
            <td>
                <input align="bottom" type="submit" name="submitPlate" value="Submit">
                &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input align="right" type="submit" name="startOver" value="Start Over">
            </td>

        </tr>  <?php
        }?>
        </table>
        </form>

        <?php

        }

        if (getDefault($this->checkOutStep) == 'manuallyInput') {
            $this->shipCheckOutInfoInputTable();
        }

        if (getDefault($this->checkOutStep) == 'final') {
            $this->shipCheckOutResultTable();
        }
    }

    /*
    ****************************************************************************
    */

    function plateLocationScannersView()
    {
        if (! getDefault($this->get['step'])) {
            $this->modelErrorHTML();
            echo $this->gunHtmlDisplays['pictures']; ?>
            <form id="scannerForm" method="post">
            <table id="scanner">
            <?php if ($this->process) { ?>
            <tr>
            <td id="instructions" colspan="2">
            <input type="radio" name='processType' value="plates" <?php echo $this->byPlates; ?>>
            Scan License Plate Numbers<br>
            <input type="radio" name='processType' value="waveIDs" <?php echo $this->byWave; ?>>
            Scan Wave Picks<br>
            </td>
            </tr>
            <?php } ?>
            <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Scan License Plate / Wave Pick <br>
            2. Scan Location Number <br>
            3. Repeat as necessary <br>
            4. Click Submit
            </td>
            </tr>

            <?php $this->btnsForCodeGun(); ?>
            <?php $this->textArea(); ?>

            <?php echo $this->gunHtmlDisplays['buttons']; ?>

            </table>
            </form><?php
        }

        // Verify license plate values passed

        if ($this->step == 'confirm' && ! $this->quantity) {
            $this->verifyLicensePlateQuantityTable();
        }

        // Update the locations

        if ($this->quantity) {
            $this->confirmLicensePlates();
        }
    }

    /*
    ****************************************************************************
    */

    function orderCheckInScannersView()
    {
        if (! $this->step && ! $this->updates) {
            $this->modelErrorHTML(); ?>
            <form id="scannerForm" method="post">
            <table id="scanner">
            <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Scan New Order Number <br>
            2. Repeat as necessary <br>
            3. Click Submit
            </td>
            </tr>
            <tr>
                <td>
                <textarea name="scans" id="scans" rows="20" cols="20"></textarea></td>
                <td><?php $this->submitButton(); ?>
                <input type="submit" id="rescan" name="rescan" value="Re-Scan">
                </td>
            </tr>
            </table>
            </form><?php
        }

        //**********************************************************************

        if ($this->step == 'confirm' && ! $this->quantity) {
            ?>
            <form method="post">
            <table id="confirm">
            <tr>
                <td colspan="2"><b>Verify Scanned Order Quantity<b></td>
            </tr>
            <tr>
                <td>Enter quantity of orders scanned</td>
                <td><input type=text name=quantity></td>
            </tr>
            <tr>
                <td><input type="submit" name="submit" value="Submit"></td></tr>
            </tr>
            </table>
            </form><?php
        }

        //**********************************************************************

        if ($this->quantity) {
            ?>
            <table id="<?php echo $this->tableID; ?>">
            <tr>
                <td colspan="2"><b><?php echo $this->tableTitle; ?></b></td>
            </tr>
            <tr>
            <td>
            <?php if ($this->error) { ?>
                REJECTED-PLEASE RESCAN</td>
            <?php } else { ?>
                Approved - Check In Completed
            <?php } ?>
            </td>
            </tr>
            </table><?php
        }

    }

    /*
    ****************************************************************************
    */

    function automatedScannersView()
    {
        echo $this->automatedScannerView;
    }

    /*
    ****************************************************************************
    */

    function orderEntryScannersView()
    {
        if (! $this->step && ! $this->updates) {
            $this->modelErrorHTML();

            if (! $this->skipGun) {
                // Order Check-Out does not have gun scanner
                echo $this->gunHtmlDisplays['pictures'];
            } ?>

            <form id="scannerForm" method="post">
            <table id="scanner">
            <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>

        <?php if($this->process == 'orderCheckOut') {?>

            1. Scan <br>
            <input type="radio" name='orderNumberType' value="scanOrderNumber" checked>
                New Order Number <br>
            <input type="radio" name='orderNumberType' value="clientordernumber">
                Client Order Number <br>
            <input type="radio" name='orderNumberType' value="customerOrderNumber">
                Customer Order Number <br>

        <?php } else { ?>

            1. Scan New Order Number <br>

        <?php } ?>

            2. Repeat as necessary <br>
            3. Click Submit
            </td>
            </tr>
            <tr>
                <td>
                <textarea name="scans" id="scans" rows="20" cols="20"></textarea></td>
                <td>

            <?php $this->submitButton();

            $onScanner = getDefault($_SESSION['onScanner']);

            if ($this->process == 'orderProcessingCheckIn' && ! $onScanner) { ?>

                <input align=bottom type=submit name=printLading value="Print BoL">

            <?php } ?>

                <br><br>
                <input type="submit" id="rescan" name="rescan" value="Re-Scan">
                </td>
            </tr>

            <?php if (! $this->skipGun) {
                // Order Check-Out does not have gun scanner
                echo $this->gunHtmlDisplays['buttons'];
            } ?>

            </table>
            </form>

        <?php }

        //**********************************************************************

        if ($this->step == 'confirm' && ! $this->quantity) { ?>

            <form method="post">
            <table id="confirm">

            <?php if ($this->process == 'orderProcessCheckOut') { ?>

                <tr>
                    <td colspan="3"><b>Enter License Plate Quantity per Order Scanned</b></td>
                </tr>

                <?php

                $orderNumbers = array_keys($this->processCartons);

                foreach ($orderNumbers as $orderNumber) { ?>

                <tr>
                    <td><?php echo $orderNumber; ?></td>
                    <td>
                        <input type="text" name="quantity[]">
                        <input type="hidden" name="orderNumber[]"
                               value="<?php echo $orderNumber; ?>">
                    </td>
                </tr>

                <?php }
            } else { ?>

                <tr>
                    <td colspan="2"><b>Verify Order Quantity Scanned</b></td>
                </tr>
                <tr>
                    <td>Enter quantity of orders scanned</td>
                    <td><input type=text name=quantity></td>
                </tr>

            <?php } ?>

                <tr>
                    <td colspan="2"><input type="submit" name="submit"
                        value="Submit" class="confirmSubmit"></td>
                </tr>
            </table>
            </form>

        <?php }

        //**********************************************************************

        if ($this->step == 'selectOrder') {
            ?>
            <form method="post" id="confirmSelectClientOrder">
                <table id="confirm" border="1" count-order="<?php echo count($this->duplicateClientOrder) ?>">

                    <tr>
                        <td colspan="5"><b>Select client order number</b></td>
                    </tr>
                    <tr>
                        <td>Client Name</td>
                        <td>Client Order Number</td>
                        <td>Scan Order Number</td>
                        <td>Customer Order Number</td>
                        <td></td>
                    </tr>
                    <?php
                    $index = 1;
                    foreach ($this->duplicateClientOrder as $clientOrder => $rows) {
                        foreach ($rows as $values) { ?>
                            <tr class="<?php echo $index%2 ? 'old' : 'new'; ?>">
                                <td><?php echo $values['clientName']; ?></td>
                                <td><?php echo $values['clientordernumber']; ?></td>
                                <td><?php echo $values['scanOrderNumber']; ?></td>
                                <td><?php echo $values['customerordernumber']; ?></td>
                                <td>
                                    <input type="radio" name="<?php echo $clientOrder; ?>"
                                           value="<?php echo $values['vendorID']; ?>"
                                           order-index="<?php echo $index; ?>">
                                </td>
                            </tr>
                        <?php }

                        $index++;
                    }?>
                    <tr>
                        <td colspan="5">
                            <input type="submit" name="submit" value="Submit" class="confirmSelectClientOrder">
                        </td>
                    </tr>
                </table>
            </form>
        <?php }

        //**********************************************************************

        if ($this->quantity && $this->ordersPassed) {
            if (! $this->process != 'orderCheckOut') {
                $this->displayOrderEntryResult();
            }

            if (! $this->skipGun) {
                echo $this->gunHtmlDisplays['buttons'];
            } ?>

        <form id="platesDetail" method="post"
                    action="<?php echo makeLink('plates', 'display', [
                            'search' => 'plates',
                            'level' => 'order']) ?>"  target="_blank">
            <div style="display: none;">
                <input type="hidden"  name="printAll" id="printAll"
                   value="all">
                <input type="hidden"  name="search" id="search"
                   value="plates">
                <input type="hidden"  name="level" id="level"
                   value="order">
                <input type="hidden"  name="order" id="order"
                   value="<?php echo implode(',', array_keys($this->ordersPassed)); ?>">
            </div>
        </form>

            </table><?php
        }
    }

    /*
    ****************************************************************************
    */

    function shippedScannersView()
    {
        if (($this->errors || ! $this->get['step']) && $this->noErrors) {
            $this->modelErrorHTML();
            echo $this->gunHtmlDisplays['pictures']; ?>

            <form id="scannerForm" method="post">
            <table id="scanner">
            <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Scan Bill Of Lading Label to open<br>
            2. Scan License Plate Number(s)<br>
            3. Scan Bill Of Lading Label to close<br>
            4. Repeat as necessary <br>
            5. Click Submit
            </td>
            </tr>

            <?php $this->btnsForCodeGun(); ?>
            <?php $this->textArea(); ?>
            <?php echo $this->gunHtmlDisplays['buttons']; ?>

            </table>
            </form><?php
        }
        // Verify license plate values passed
        if (getDefault($this->get['step']) == 'confirmBOL') {
            $this->shipCheckOutBillOfLadingInputTable();
        }
        if (getDefault($this->get['step']) == 'BOLInfo') {
            $this->shipCheckOutInfoTable();
        }
        // Update the locations
        if (getDefault($this->get['step']) == 'confirmValues') {
            $this->shipCheckOutResultTable();
        }
    }

     /*
    ****************************************************************************
    */

    function orderHoldScannersView()
    {
        if (! $this->step && ! $this->updates) {
            $this->modelErrorHTML(); ?>
            <table>
                <tr><td><form action="<?php echo  makeLink('orders', 'search', [
                    'editable' => 'display',
                    'firstDropdown' => 'holdStatusID',
                ]);?>">
                    <input type="submit" value="View Hold Orders"></form>
                </td></tr>
                <form id="scannerForm" method="post">
            </table>
            <table id="scanner">
            <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Select Order Hold Status: <br>
            <input type="radio" name='holdStatus' value="24" checked>
                Put on Hold<br>
            <input type="radio" name='holdStatus' value="25">
                Take off Hold<br>
            1. Scan New Order Number <br>
            2. Repeat as necessary <br>
            3. Click Submit
            </td>
            </tr>
            <tr>
                <td>
                <textarea name="scans" id="scans" rows="20" cols="20"></textarea></td>
                <td><?php $this->submitButton();
            if ($this->process == 'orderProcessingCheckIn') {
            ?>
                <input align=bottom type=submit name=printLading value="Print BoL">
            <?php
            }
            ?>
                <br><br>
                <input type="submit" id="rescan" name="rescan" value="Re-Scan">
                </td>
            </tr>
            </table>
            </form>
                <?php
        }

        //**********************************************************************

        if ($this->step == 'confirm' && ! $this->quantity) {
            ?>
            <form method="post">
            <table id="confirm">
            <tr>
                <td colspan="2"><b>Verify Order Quantity Scanned</b></td>
            </tr>
            <tr>
                <td>Enter quantity of orders scanned</td>
                <td><input type=text name=quantity></td>
            </tr>
            <tr>
                <td colspan="2">
                    <input type="submit" name="submit" value="Submit"
                           class="confirmSubmit">
                </td>
            </tr>
            </table>
            </form><?php
        }

        //**********************************************************************

        if ($this->quantity) {
            ?>
            <table id="<?php echo $this->tableID; ?>">
            <?php
            if ($this->error || $this->process != 'orderCheckOut') {
                // Order Check-Out scanner needs only laydown result output.
            ?>
                <tr>
                    <td colspan="2"><b><?php echo $this->tableTitle; ?></b></td>
                </tr>
            <?php
            }
            if ($this->error) { ?>
                <tr>
                    <td colspan="2">Number of orders scanned: <?php echo $this->passedCount; ?><br>
                    Quantity entered: <?php echo $this->quantity; ?></td>
                </tr>
            <?php } else {
                if ($this->process != 'orderCheckOut') {
                    // Order Check-Out scanner does not need success result output.
                    // It redirects to orders/addOrEdit controller instead
             ?>
                <tr>
                    <td>Order</td>
                    <td>New Hold Status</td>
                </tr>
                <?php foreach ($this->ordersPassed as $order) { ?>
                    <tr>
                        <td><?php echo $order['target']; ?></td>
                        <td><?php if ($this->status != ''){
                            echo $this->statusName;
                        }else {
                            echo 'Off Hold';
                        }?>
                        </td>
                    </tr>
                    <?php }
                }
            } ?>
            </table><?php
        }
    }


    /*
    ****************************************************************************
    */

    function workOrderCheckInScannersView()
    {
        if (! $this->step && ! $this->updates) {
            $this->modelErrorHTML(); ?>

        <form id="scannerForm" method="post">
            <div class="failedMessage centered hidden"></div>
            <table id="scanner">
                <tr>
                    <td id="instructions" colspan="2">
                      Instructions: <br>
                      1. Scan Work Order Number to open<br>
                      2. Scan Order Number<br>
                      3. Scan Work Order Number to close<br>
                      4. Repeat as necessary <br>
                      5. Click Submit
                    </td>
                </tr>
                <?php $this->textArea(); ?>
            </table>
        </form>

        <?php }
    }

   /*
    ****************************************************************************
    */

    function workOrderCheckOutScannersView()
    {
        if (! $this->step && ! $this->updates) {
            $this->modelErrorHTML(); ?>

        <form id="scannerForm" method="post">
            <div class="failedMessage centered hidden"></div>
            <table id="scanner">
                <tr>
                    <td id="instructions" colspan="2">
                      Instructions: <br>
                      1. Scan Work Order Number to open<br>
                      2. Repeat as necessary <br>
                      3. Click Submit
                    </td>
                </tr>
                <?php $this->textArea(); ?>
            </table>
        </form>

        <?php }
    }

   /*
    ****************************************************************************
    */

    function adjustScannersView()
    {
        if (! $this->step && ! $this->updates) {
            $this->modelErrorHTML();
            echo $this->gunHtmlDisplays['pictures']; ?>
            <form id="scannerForm" method="post">
            <table id="scanner">
            <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Scan locations to open <br>
            2. Scan license plate to open <br>
            3. Scan carton number(s) <br>
            4. Scan license plate to close <br>
            5. Scan locations to close <br>
            6. Repeat as necessary <br>
            7. Click Submit
            </td>
            </tr>
            <?php $this->btnsForCodeGun(); ?>
            <?php $this->textArea(); ?>
            <?php echo $this->gunHtmlDisplays['buttons']; ?>
            </table>
            </form><?php

        }
        //**********************************************************************

        if ($this->step == 'confirm' && ! $this->quantities) {
            ?>
            <form method="POST">
            <table id="confirm">
            <tr>
                <td colspan="2"><b>Verify Location Carton Quantities<b></td>
            </tr>
            <tr>
              <td>Location Scanned</td>
              <td>Enter Carton Quantity</td>
            </tr>
            <?php

            foreach ($this->cartonsPassed['locations'] as $location) { ?>
                <tr>
                  <td><?php echo $location; ?></td>
                  <td><input type="text" name="quantities[<?php echo $location; ?>]"></td>
                </tr><?php
            }
            ?>
            <tr>
                <td colspan="2">
                    <input type="submit" name="submit" value="Submit"
                           class="confirmSubmit">
                </td>
            </table>
            </form><?php
        }

        //**********************************************************************

        if ($this->quantities) { ?>

            <table id="<?php echo $this->tableID; ?>">

            <?php if ($this->error) { ?>

                <tr>
                    <td colspan="3"><b><?php echo $this->tableTitle; ?></b></td>
                </tr>
                <tr>
                    <td>Location Name</td>
                    <td>Quantity Scanned</td>
                    <td>Quantity Entered</td>
                </tr>

                <?php foreach ($this->cartonsPassed['locations'] as $location) { ?>

                    <tr>
                        <td>
                            <?php echo $location; ?>
                        </td>
                        <td class="numberCell">
                            <?php echo $this->passedCount[$location]; ?>
                        </td>
                        <td class="numberCell">
                            <?php echo $this->quantities[$location]; ?>
                        </td>
                    </tr>

                <?php } ?>

            </table>

        <?php
            }
            if (! $this->error) {
                if ($this->missedCartons) { ?>
                <table id="rejected">
                <tr>
                    <td colspan="2"><b><?php echo $this->tableTitle; ?></b></td>
                </tr>
                <tr>
                <?php
                    foreach ($this->missedCartons as $carton) { ?>
                    <tr>
                      <td><?php echo $carton; ?></td>
                      <td>DS</td>
                    </tr> <?php
                    }
                    echo $this->gunHtmlDisplays['buttons'];
                    ?>
                    </tr>
                </table>
               <?php
                }
                if ($this->extraCartons) { ?>
                    <table id="approved">
                        <tr>
                            <td colspan="2">
                                <b><?php echo "Extra Cartons Found"; ?></b>
                            </td>
                        </tr>
                        <tr>
                <?php
                    foreach ($this->extraCartons as $carton) { ?>
                        <tr>
                          <td><?php echo $carton; ?></td>
                          <td>RK</td>
                        </tr> <?php
                    }
                    echo $this->gunHtmlDisplays['buttons'];
                    ?>
                        </tr>
                    </table>
                <?php
                }

                if ($this->existingCartons) {

                    $colspan = isset($_SESSION['onScanner']) ? 2 : 1;

                    ?>
                     <table id="approved">
                <tr>
                    <td colspan="<?php echo $colspan; ?>"><b>
                    <?php
                    echo 'Cartons scanned that are already in the system.<br>'
                       . 'Status is "RK"'; ?></b></td>
                </tr>
                <tr>
                <?php
                    foreach ($this->existingCartons as $carton) { ?>
                        <tr>
                          <td colspan="<?php echo $colspan; ?>"><?php echo $carton; ?></td>
                        </tr> <?php
                    } ?>
                        </tr>
                        <?php echo $this->gunHtmlDisplays['buttons']; ?>
                    </table>
                <?php
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    function shipCheckOutInfoInputTable()
    {?>
            <form method="POST" id="shippingCheckOut">

            <table id="confirm" class="wider">
                <tr>
                    <td colspan="6"><b>Enter Shipment Information for these Orders<b></td>
                </tr>
                <tr>
                <td><b>Order Number</b></td>
                <?php foreach ($this->shippingInfo as $row) { ?>
                    <td><b><?php echo $row['display'].'<br>('.$row['length'].' '
                        . $row['format'].')'; ?></b></td>
                <?php } ?>
                </tr>
                <?php foreach ($this->orders as $orderID => $row) { ?>
                    <tr>
                        <td><?php echo $row['scanOrderNumber']; ?>
                            <input name="generateBOLs[<?php echo $orderID; ?>][newOrderID]"
                                   type="hidden" value="<?php echo $orderID; ?>"></td>
                        <?php foreach (array_keys($this->shippingInfo) as $name) {
                            $value = getDefault($this->postOrders[$orderID][$name]); ?>
                            <td><input class="<?php echo $name; ?>"
                                       name="generateBOLs[<?php echo $orderID; ?>][<?php echo $name; ?>]"
                                       value="<?php echo $value; ?>" type="text"></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
                <tr><td colspan="6" align="right"><input name="submit" type="submit" value="Submit"></td></tr>

            </table>
            </form> <?php

    }

    /*
    ****************************************************************************
    */

    function shipCheckOutInfoTable()
    {?>
            <form method="POST" id="shippingCheckOut">

            <table id="confirm" class="wider">
                <tr>
                    <td colspan="<?php echo count($this->shippingInfo) + 1;?>">
                        <b>Enter Shipment Information for these Bill Of Lading<b>
                    </td>
                </tr>
                <tr>
                <td><b>Bill Of Lading Number</b></td>
                <?php foreach ($this->shippingInfo as $row) { ?>
                    <td><b><?php echo $row['display'].'<br>('.$row['length'].' '
                        . $row['format'].')'; ?></b></td>
                <?php } ?>
                </tr>
                <?php foreach ($this->BOLs as $bolNumber) { ?>
                    <tr>
                        <td><?php echo $bolNumber; ?>
                            <input name="BOLNums[<?php echo $bolNumber; ?>]"
                                   type="hidden" value="<?php echo $bolNumber; ?>"></td>
                        <?php foreach (array_keys($this->shippingInfo) as $name) {
                            $value = getDefault($this->postBOLs[$bolNumber][$name]); ?>
                            <td><input class="<?php echo $name; ?>"
                                       name="generateBOLs[<?php echo $bolNumber; ?>][<?php echo $name; ?>]"
                                       value="<?php echo $value; ?>" type="text"></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="<?php echo count($this->shippingInfo) + 1;?>"
                        align="right"><input name="submit" type="submit" value="Submit">
                    </td>
                </tr>

            </table>
            </form> <?php

    }

    /*
    ****************************************************************************
    */

    function shipCheckOutBillOfLadingInputTable()
    {
        if (! $this->errors) { ?>

            <form method="POST" id="shippingCheckOut">

                <table id="confirm" class="wider">
                    <tr>
                        <td colspan="6">
                            <b>Enter License Plate Quantity for Bill Of Ladings<b>
                        </td>
                    </tr>
                    <tr>
                        <td><b>Bill Of Lading Number</b></td>
                        <td><b>License Plate Quantity</b></td>
                    </tr>

                    <?php echo $this->confirmShippingBoLs(); ?>

                    <tr>
                        <td colspan="6" align="right">
                            <input name="submit" type="submit" value="Submit">
                        </td>
                    </tr>

                </table>
            </form> <?php
        } else { ?>
            <table id="<?php echo $this->tableID; ?>">
                <tr>
                    <td colspan="2">
                        <b><?php echo $this->tableTitle; ?></b>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        Number of Licens Plate(s) scanned:
                        <?php echo count($this->platesAsKeys); ?><br>
                        Quantity entered: <?php echo $this->quantity; ?>
                    </td>
                </tr>
            </table>
            <?php
        }

    }

    /*
    ****************************************************************************
    */

    function shipCheckOutResultTable()
    { ?>
        <table id="<?php echo $this->tableID; ?>">
            <?php if ($this->noErrors) { ?>
                <tr>
                    <td colspan="6"><b><?php echo $this->tableTitle; ?></b></td>
                </tr>
                <tr>
                    <td>License Plate</td>
                    <td>Order</td>
                    <td>Status</td>
                    <td>Condition</td>
                    <td>Carton</td>
                    <td>Status</td>
                </tr>
                <?php foreach ($this->updatedStatuses as $plate => $info) { ?>
                    <tr>
                        <td><?php echo $info['plate']; ?></td>
                        <td><?php echo $info['scanOrder']; ?></td>
                        <td><?php echo $info['orderStatus']; ?></td>
                        <td><?php echo $info['shippingStatus']; ?></td>
                        <td><?php echo $info['ucc']; ?></td>
                        <td><?php echo $info['invStatus']; ?></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="2"><b><?php echo $this->tableTitle; ?></b></td>
                </tr>
                <tr>
                    <td colspan="2">Number of license plates scanned: <?php echo count($this->platesAsKeys); ?><br>
                    Quantity entered: <?php echo $this->quantity; ?></td>
                </tr>
            <?php } ?>

            <?php echo $this->gunHtmlDisplays['buttons']; ?>

        </table> <?php
    }

    /*
    ****************************************************************************
    */

    function batchScannersView()
    {
       if (! $this->step && ! $this->updates) {
            $this->modelErrorHTML();
            echo $this->gunHtmlDisplays['pictures']; ?>

        <form id="scannerForm" method="post">
            <table id="scanner">
                <tr>
                   <td id="instructions" colspan="2">
                        Instructions: <br>
                        1. Scan batch number to open <br>
                        2. Scan order number(s) <br>
                        3. Scan batch number to close <br>
                        4. Repeat as necessary<br>
                        5. Click Submit
                    </td>
                </tr> <?php
                $this->btnsForCodeGun();
                $this->textArea();
                echo $this->gunHtmlDisplays['buttons']; ?>
            </table>
        </form><?php
        }

        //**********************************************************************

        if ($this->step == 'confirm' && ! $this->quantities) { ?>
        <form method="POST">
            <table id="confirm">
                <tr>
                    <td colspan="2"><b>Verify Wave Pick Order Quantities<b></td>
                </tr>
                <tr>
                    <td>Order Scanned</td>
                    <td>Enter Quantity</td>
                </tr><?php
                foreach ($this->batchesPassed as $batch => $orderNumbers) { ?>
                    <tr>
                      <td><?php echo $batch; ?></td>
                      <td><input type="text"
                                 name="quantities[<?php echo $batch; ?>]"></td>
                    </tr><?php
                } ?>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="submit" value="Submit"
                               class="confirmSubmit">
                    </td>
                </tr>
            </table>
        </form><?php
        }

        //**********************************************************************

        if ($this->quantities) { ?>
            <table id="<?php echo $this->tableID; ?>"> <?php
            if ($this->error) { ?>
                <tr>
                    <td colspan="3"><b><?php echo $this->tableTitle; ?></b></td>
                </tr>
                <tr>
                    <td>Batch Scanned</td>
                    <td>Quantity Scanned</td>
                    <td>Quantity Entered</td>
                </tr>
                <tr><?php
                foreach ($this->quantities as $batch => $quantity) { ?>
                <tr>
                    <td><?php echo $batch; ?></td>
                    <td class="numberCell">
                        <?php echo count($this->batchesPassed[$batch]); ?>
                    </td>
                    <td class="numberCell"><?php echo $quantity; ?></td>
                </tr><?php
                } ?>
                </tr><?php
            } else { ?>
                <tr>
                    <td colspan="4"><b><?php echo $this->tableTitle; ?></b></td>
                </tr>
                <tr>
                    <td>Order Scanned</td>
                    <td>New Batch</td>
                </tr>
                <tr><?php
                foreach ($this->batchesPassed as $batch => $orderNumbers) {
                    foreach ($orderNumbers as $orderNumber) { ?>
                    <tr>
                        <td><?php echo $orderNumber; ?></td>
                        <td><?php echo $batch; ?></td>
                    </tr><?php
                    }
                } ?>
                </tr><?php
            }

            echo $this->gunHtmlDisplays['buttons']; ?>

            </table><?php
        }
    }

    /*
    ****************************************************************************
    */

    function shippedOrdersScannersView()
    {
        ?>
        <div class="centered">
        <div class="failedMessage" style="display: none"></div>
        <div class="showsuccessMessage" style="display: none">
            Order and cartons statuses have been updated to 'Shipped'
            <span class="orderShipped" style="display: none">
                <br>All order cartons are shipped
            <span>
        </div>
        </div>
        <form id="autoForm" method="post">
        <table id="scanner">
        <tr>

        <?php $disabled = getDefault($_SESSION['scanInput']['orderID']) ?
                'disabled' : NULL; ?>

        <td id="instructions" colspan="2">
        <input type="radio" name="useUPC" value="useUPC" id="useUPC"
               class="useUPC" checked <?php echo $disabled; ?>>UPC scan
        <input type="radio" name="useUPC" value="noUPC" id="noUPC"
               class="useUPC" <?php echo $disabled; ?>>No UPC scan
        <br>
        <input type="radio" name="useTracking" value="useTrackingID"
               id="useTrackingID" class="useTracking"
               checked <?php echo $disabled; ?>>Tracking ID
        <input type="radio" name="useTracking" value="noTrackingID"
               id="noTrackingID" class="useTracking"
               <?php echo $disabled; ?>>No Tracking ID
        <br>
        Scan <span id="needToScan">Packing Slip</span>:  <br>
        </td>
        </tr>
        <tr><td><textarea id="autoScansOld" name="scans"></textarea></td>
        </tr>
        <tr>
            <td>
                <table id="descriptionTable" width="100%" data-order-number="">
                </table>
            </td>
        </tr>
        <tr id="descriptionType">
            <td>
                <button id="showInventory" class="descriptionType">
                    Display All
                </button>
                <button id="shippedInventory" class="descriptionType">
                    Shipped
                </button>
                <button id="remainingInventory" class="descriptionType">
                    Remaining
                </button>
                <button id="hideInventory" class="descriptionType">
                    Hide All
                </button>
            </td>
        </tr>
        </table>
        </form>

        <div id="passwordDialog">
            <input type="password" id="shipPassword" name="password">
        </div>

        <?php
    }

    /*
    ****************************************************************************
    */

    function locationsScannersView()
    {
        if (! getDefault($this->get['step'])) {
            $this->modelErrorHTML();
            echo $this->gunHtmlDisplays['pictures']; ?>
            <form id="scannerForm" method="post">
                <table id="scanner">
                    <tr>
                        <td id="instructions" colspan="2">
                            Instructions: <br>
                            1. Scan Location Number(s) <br>
                            2. Repeat as necessary <br>
                            3. Click Submit
                        </td>
                    </tr>
                <?php $this->btnsForCodeGun(); ?>
                <?php $this->textArea(); ?>

                <?php echo $this->gunHtmlDisplays['buttons']; ?>

                </table>
            </form><?php
        }

           // Verify license plate values passed

        if ($this->step == 'confirm' && ! $this->quantity) {
            ?>
            <form method="POST">
                <table id="confirm">
                    <tr>
                        <td colspan="2">
                            <b>Verify Location Number(s) Quantity<b>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            Enter Location number quantity
                        </td>
                        <td>
                            <input type="text" name="quantity">
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <input type="submit" name="submit" value="Submit">
                        </td>
                    </tr>
                </table>
            </form><?php
        }

        if ($this->quantity) {
            if (! $this->noErrors) {?>
                <table id="<?php echo $this->tableID; ?>">
                    <tr>
                        <td colspan="2">
                            <b><?php echo $this->tableTitle; ?></b>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            Number of Location(s) scanned:
                            <?php echo count($this->listLocations); ?><br>
                            Quantity entered: <?php echo $this->quantity; ?>
                        </td>
                    </tr>
                </table>
           <?php }
        }
    }

    /*
    ****************************************************************************
    */

    function reprintLicensePlateScannersView()
    {
        if (($this->errors || $this->missingInput)) { ?>
            <table id="rejected"><?php
            if ($this->errors) {?>
                <tr>
                    <td colspan="2">
                        <b>Location(s) input wrong</b>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?php $message = '';
                        foreach ($this->errors as $key => $values) {
                            $message .= '- ' . $values . '<br>';
                        }
                        echo $message;?>
                    </td>
                </tr>
            <?php } elseif ($this->missingInput) {?>
                <tr>
                    <td colspan="2">
                        <b>Please input Location(s)</b>
                    </td>
                </tr>
            <?php }?>
            </table><?php
        }
        if (! $this->licensePlate){?>
            <form id="scannerForm" method="post">
                <table id="scanner">
                    <tr>
                        <td id="instructions" colspan="2">
                            Instructions: <br>
                            1. Scan Location Name <br>
                            2. Repeat as necessary <br>
                            3. Click Submit
                        </td>
                    </tr>
                    <?php
                        $this->btnsForCodeGun();
                        $this->textArea();
                    ?>
                </table>
            </form><?php
            return;
        }?>
        <form method="post" enctype="multipart/form-data">
            <table id="licensePlate">
                <tbody>
                    <col width="40">
                    <col width="180">
                    <col width="800">
                    <tr>
                        <td class="titleTable" colspan="3">
                            License Plate at Each Locations
                        </td>
                    </tr>
                    <tr class="headerTable">
                        <td id="numberIndex">No</td>
                        <td>Location Name</td>
                        <td>License Plate</td>
                    </tr>
                    <?php $this->tableLicensePlateOutput();?>
                    <tr>
                        <td colspan="3">
                            <input type="submit" name="printPDF" value="Print PDF">
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <?php
    }

    /*
    ****************************************************************************
    */

    function transferMezzanineScannersView()
    {?>
        <div class="transferMezzanine">
            <form  id="form-upload" method="post" enctype="multipart/form-data">
                Import Transfer Files
                <span class="formatFile">(accept Excel)</span>
                <input multiple type="file" name="file">
                <input type="submit" value="Import" name="import">
                <input type="submit" value="Download template" name="template"/>
            </form>
        </div>
        <?php if ($this->errors) { ?>
            <table id="rejected">
                <tr>
                    <td colspan="2"> <?php
                        $message = '';
                        foreach ($this->errors as $key => $values) {
                            $message .= '- ' . $values . '<br>';
                        }
                        echo $message;?>
                    </td>
                </tr>
            </table><?php
        } elseif ($this->success) { ?>
            <div class="successMessage">
                <div class="alert">Transfer UCC(s) to Mezzanine location successful!</div>
            </div>
        <?php } ?>
        <form id="scannerForm" method="post">
        <table id="scanner">
            <tr>
                <td id="instructions" colspan="2">
                    Instructions: <br>
                    1. Scan Mezzanine Location to open<br>
                    2. Scan carton number(s)<br>
                    3. Scan Mezzanine Location to close<br>
                    4. Repeat as necessary <br>
                    5. Click Submit
                </td>
            </tr><?php
            $this->btnsForCodeGun();
            $this->textArea();?>
        </table>
        </form><?php
    }

    /*
    ****************************************************************************
    */

    function inventoryTransferScannersView()
    {
        // Starting Page
        if (! getDefault($this->get['step'])) {
            $this->modelErrorHTML();
            echo $this->gunHtmlDisplays['pictures'];
            ?>
            <form id="scannerForm" method="post">
            <table id="scanner">
            <tr>
            <td id="instructions" colspan="2">
            Instructions: <br>
            1. Scan license plate to open<br>
            2. Scan Location Number to open<br>
            3. Scan carton number(s)<br>
            4. Scan Location Number to close<br>
            5. Scan license plate to close<br>
            6. Repeat as necessary<br>
            7. Click Submit Button
            </td>
            </tr>

            <?php $this->btnsForCodeGun(); ?>
            <?php $this->textArea(); ?>

            <?php echo $this->gunHtmlDisplays['buttons']; ?>
            </table>

            </form><?php

        }

        // Verify license plate values passed

        if (getDefault($this->get['step']) == 'confirmValues') { ?>

            <form method="POST">
            <table id="confirm">
            <tr>
                <td colspan="3"><b>Verify License Plate Carton Quantities<b></td>
            </tr>
            <tr>
              <td>License Plate</td>
              <td>Location</td>
              <td>Enter Quantity</td>
            </tr>
            <?php
            foreach (array_keys($this->licensePlates) as $plate) { ?>
                <tr>
                  <td><?php echo $plate; ?></td>
                  <td><?php echo $this->licensePlates[$plate]['locationInfo']['locationName']; ?></td>
                  <td><input type="text" name="quantities[<?php echo $plate; ?>]"></td>
                </tr><?php
            }
            ?>
            <tr><td></td><td></td>
                <td><input type="submit" name="submit" value="Submit"></td></tr>

            </table>
            </form><?php
        }

        if (getDefault($this->get['step']) == 'compareValues') { ?>

            <table id="<?php echo $this->tableID; ?>">
            <tr>
                <td colspan="5"><b><?php echo $this->tableTitle; ?></b></td>
            </tr>

            <?php

            $this->inventoryTransferConfirmResults();

            echo $this->gunHtmlDisplays['buttons']; ?>

            </table><?php
        }
    }

    /*
    ****************************************************************************
    */

    function downloadCartonHistoryScannersView()
    {
        if (($this->error || $this->missingInput)) { ?>
            <table id="rejected"><?php
            if ($this->error) {?>
                <tr>
                    <td colspan="2">
                        <?php echo $this->error; ?>
                    </td>
                </tr>
            <?php } elseif ($this->missingInput) {?>
                <tr>
                    <td colspan="2">
                        <b>Please in put UCC(s)</b>
                    </td>
                </tr>
            <?php }?>
            </table><?php
        }
        if (! $this->data){?>
            <form id="scannerForm" method="post">
            <table id="scanner">
                <tr>
                    <td id="instructions" colspan="2">
                        <?php echo $this->vendors;?>
                    </td>
                </tr>
                <tr>
                    <td id="instructions" colspan="2">
                        Instructions: <br>
                        1. Scan UCC128 <br>
                        2. Repeat as necessary <br>
                        3. Click Submit
                    </td>
                </tr>
                <?php
                $this->btnsForCodeGun();
                $this->textArea();
                ?>
            </table>
            </form><?php
        } else {?>
            <div class="successMessage">
                <table id="downloadCarton" border="1">
                    <tbody>
                    <col width="300">
                    <col width="300">
                    <tr>
                        <td class="titleTable" colspan="3">
                            Cartons history summary
                        </td>
                    </tr>
                    <tr class="headerTable">
                        <td>Summary Type</td>
                        <td>Summary Total</td>
                    </tr>
                    <?php
                        foreach ($this->data as $key => $value) {?>
                            <tr>
                                <td><?php echo $key;?></td>
                                <td><?php echo count($value);?></td>
                            </tr>
                        <?php }
                    ?>
                    </tbody>
                </table>
                <div class="alert">
                    <a target="_blank" href="<?php echo $this->downloadLink?>">Download Excel.</a>
                </div>
            </div>
            <?php
        }
    }

    /*
    ****************************************************************************
    */

    function warehouseOutboundTransferScannersView()
    {
        if (! $this->step) {

            $this->modelErrorHTML(); ?>

            <form id="scannerForm" method="post">
            <table id="scanner">

                <?php $this->transferWarehouses(); ?>

                <tr>
                    <td id="instructions" colspan="2">
                        Instructions: <br>
                        1. Scan Manifest to open <br>
                        2. Scan License Plates <br>
                        3. Scan Manifest to close <br>
                        4. Click Submit
                    </td>
                </tr>

                <?php
                $this->btnsForCodeGun();
                $this->textArea();

                echo $this->gunHtmlDisplays['buttons']; ?>

            </table>
            </form>

        <?php }

        //**********************************************************************

        if ($this->step == 'confirm' && ! $this->quantities) { ?>

        <form method="POST">
            <table id="confirm">
                <tr>
                    <td colspan="2"><b>Verify License Plates Quantities<b></td>
                </tr>
                <tr>
                    <td>Manifest</td>
                    <td>Enter Quantity</td>
                </tr>

                <?php
                $manifests = array_keys($this->outboundPlates);

                foreach ($manifests as $manifest) { ?>

                    <tr>
                      <td><?php echo $manifest; ?></td>
                      <td><input type="text"
                                 name="quantities[<?php echo $manifest; ?>]"></td>
                    </tr>

                <?php } ?>

                <tr>
                    <td colspan="2">
                        <input type="submit" name="submit" value="Submit"
                               class="confirmSubmit">
                    </td>
                </tr>
            </table>
        </form>

        <?php }

        //**********************************************************************

        if ($this->quantities) { ?>

            <table id="<?php echo $this->tableID; ?>">

            <?php if ($this->error) { ?>

                <tr>
                    <td colspan="3"><b><?php echo $this->tableTitle; ?></b></td>
                </tr>
                <tr>
                    <td>Manifest</td>
                    <td>Quantity Scanned</td>
                    <td>Quantity Entered</td>
                </tr>
                <tr>

                <?php foreach ($this->quantities as $manifest => $quantity) { ?>

                <tr>
                    <td><?php echo $manifest; ?></td>
                    <td class="numberCell">
                        <?php echo count($this->outboundPlates[$manifest]); ?>
                    </td>
                    <td class="numberCell"><?php echo $quantity; ?></td>
                </tr>

                <?php } ?>

                </tr>

            <?php } else { ?>

                <tr>
                    <td colspan="4"><b><?php echo $this->tableTitle; ?></b></td>
                </tr>
                <tr>
                    <td>Manifest</td>
                    <td>License Plate</td>
                </tr>
                <tr>

                <?php foreach ($this->outboundPlates as $manifest => $manifestData) {

                    $licensePlates = array_keys($manifestData);

                    foreach ($licensePlates as $licensePlate) { ?>

                    <tr>
                        <td><?php echo $manifest; ?></td>
                        <td><?php echo $licensePlate; ?></td>
                    </tr>

                    <?php }
                } ?>

                </tr>
            <?php }

            echo $this->gunHtmlDisplays['buttons']; ?>

            </table><?php
        }
    }

    /*
    ****************************************************************************
    */

    function warehouseInboundTransferScannersView()
    {
        if (! $this->step) {

            $this->modelErrorHTML(); ?>

            <form id="scannerForm" method="post">
            <table id="scanner">

                <?php $this->transferWarehouses(); ?>

                <tr>
                    <td id="instructions" colspan="2">
                        Instructions: <br>
                        1. Scan License Plate <br>
                        2. Scan Location Number  <br>
                        3. Repeat as necessary  <br>
                        4. Click Submit
                    </td>
                </tr>

                <?php
                $this->btnsForCodeGun();
                $this->textArea();

                echo $this->gunHtmlDisplays['buttons']; ?>

            </table>
            </form>

        <?php }

        //**********************************************************************

        if ($this->step == 'confirm' && ! $this->quantity) {
            $this->verifyLicensePlateQuantityTable();
        }

        //**********************************************************************

        if ($this->quantity) {
            $this->confirmLicensePlates();
        }
    }

    /*
    ****************************************************************************
    */

    function warehouseTransferConsolidationScannersView()
    {
        $this->modelErrorHTML(); ?>

        <form id="scannerForm" method="post">
        <table id="scanner">
            <tr>
                <td><?php echo $this->warehouseVendor; ?></td>
            </tr>
            <tr>
                <td align="center">
                    <input type="submit" id="submit" name="submit" />
                </td>
            </tr>
        </table>
        </form>

    <?php }

    /*
    ****************************************************************************
    */

    function printMultiLicenseScannersView()
    {
            if ($this->missingInput) {?>
            <table id="rejected">
                <tr>
                    <td colspan="2">
                        <b><?php echo $this->missingInput;?></b>
                    </td>
                </tr>
            </table>
            <?php }?>
            <form id="scannerForm" method="post">
                <table id="scanner">
                    <tr>
                        <td id="instructions" colspan="2">
                            Instructions: <br>
                            1. Scan License Plate <br>
                            2. Repeat as necessary <br>
                            3. Click Submit
                        </td>
                    </tr>
                    <?php
                    $this->btnsForCodeGun();
                    $this->textArea();
                    ?>
                </table>
            </form>
        <?php
    }

    /*
    ****************************************************************************
    */

    function scanLicensePlateScannersView()
    {

        if (($this->errors || $this->missingInput)) { ?>
            <table id="rejected"><?php
            if ($this->errors) {?>
                <tr>
                    <td colspan="2">
                        <b>License Plate(s) input wrong</b>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?php $message = '';
                        foreach ($this->errors as $key => $values) {
                            $message .= '- ' . $values . '<br>';
                        }
                        echo $message;?>
                    </td>
                </tr>
            <?php } elseif ($this->missingInput) {?>
                <tr>
                    <td colspan="2">
                        <b>Please in put License Plate(s)</b>
                    </td>
                </tr>
            <?php }?>
            </table><?php
        }

        if (! $this->licensePlate) { ?>
            <form id="scannerForm" method="post">
            <table id="scanner">
                <tr>
                    <td id="instructions" colspan="2">
                        Instructions: <br>
                        1. Scan License Plate(s)<br>
                        2. Repeat as necessary <br>
                        3. Click Submit
                    </td>
                </tr>
                <?php
                $this->btnsForCodeGun();
                $this->textArea();
                ?>
            </table>
            </form><?php
            return;
        }
    }

    /*
    ****************************************************************************
    */

    function changeCartonStatusScannersView()
    {   if ($this->errors) { ?>
        <table id="rejected">
        <tr>
            <td colspan="2"> <?php
                $message = '';
                foreach ($this->errors as $key => $values) {
                    $message .= '- ' . $values . '<br>';
                }
                echo $message;?>
            </td>
        </tr>
        </table><?php
    } elseif ($this->success) { ?>
        <div class="successMessage">
            <div class="alert">Created request successful!</div>
        </div>
    <?php } ?>
        <form id="scannerForm" method="post">
            <table id="scanner">
                <tr>
                    <td id="instructions" colspan="2">
                        <div>
                            <label>Status to change: </label>
                            <select name="sts" style="float: right">
                                <?php foreach ($this->sts as $id => $sts) { ?>
                                    <option value="<?php echo $id; ?>"
                                        <?php echo $sts=='RK' ? 'selected' : ''?>>
                                        <?php echo $sts?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div style="display: inline-block;width: 100%;margin-top: 3px;">
                            <label>Manual Status to change: </label>
                            <select name="mSts" style="float: right;">
                                <?php foreach ($this->sts as $id => $sts) { ?>
                                    <option value="<?php echo $id; ?>"
                                        <?php echo $sts=='RK' ? 'selected' : ''?>>
                                        <?php echo $sts?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td id="instructions" colspan="2">
                        Instructions: <br>
                        1. Scan carton number(s)<br>
                        2. Repeat as necessary <br>
                        3. Click Submit
                    </td>
                </tr><?php
                $this->btnsForCodeGun();
                $this->textArea();?>
            </table>
        </form>
        <?php
    }

    /*
    ****************************************************************************
    */
}
