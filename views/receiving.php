<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function createRCLogReceivingView()
    {?>
        <input type="text" id="container">
        <input type="submit" id="createRCLog" value="Create RC Log"> <?php
    }

    /*
    ****************************************************************************
    */
    function recordTallySheetsReceivingView()
    {
        ?>
        <div class="dontPrint">
            <span id="enterCotnainer">Enter Container:</span>
            <input id="container" type="text" placeholder="(autocomplete)"
                   value="<?php echo $this->container; ?>">
            <input id="updateContainer" type="submit" value="Update Container">
            <button class="laterButtons" id="saveRCLog">Save RC Log</button>
            <button class="laterButtons" id="saveRCLabel">Save RC Label</button>
            <button class="laterButtons" id="completeRCLog">Complete RC Log</button>
            <button class="laterButtons" id="saveCartonLabels">Save UCC Carton Labels</button>
            <button class="laterButtons" id="savePlates">Save License Plates</button>
        </div>
        <div id="laborDiv">
        Rush Labor Amount ($) <input type="text" value="<?php echo $this->labor; ?>"
                           id="labor">
        Overtime Labor Amount ($) <input type="text" value="<?php echo $this->otLabor; ?>"
                           id="otLabor">
        <button id="submitLabor">Update</button>
        </div>
        <div id="alertMessages"></div>
        <div class="palletSheetDIV">
        <table id="rcLog" class="rcLogs">
            <tr>
                <th colspan="5">SELDAT</th>
                <th colspan="6" class="vendorName">Vendor Name: </th>
                <th colspan="5" class="date">Date: </th>
                <th colspan="4" class="containerName">Name: </th>
            </tr>
            <tr style="display:none;">
            <?php for ($i=0; $i<4; $i++) { ?>
                  <td colspan="2" class="batch">Batch #</td>
                  <td colspan="3" class="batchID"></td>
            <?php } ?>
            </tr>
            <tr>
            <?php for ($i=0; $i<4; $i++) { ?>
                  <td colspan="2" class="style">Style - <?php echo $i + 1 ?></td>
                  <td colspan="3" class="sku"></td>
            <?php } ?>
            </tr>
            <tr>
            <?php for ($i=0; $i<4; $i++) { ?>
                  <td colspan="2">Color</td>
                  <td colspan="3" class="color"></td>
            <?php } ?>
            </tr>
            <tr>
                <?php for ($i=0; $i<4; $i++) { ?>
                    <td colspan="2">Description</td>
                    <td colspan="3" class="description"></td>
                <?php } ?>
            </tr>
            <tr>
            <?php for ($i=0; $i<4; $i++) { ?>
                  <td colspan="2">UPC</td>
                  <td class="upc"></td>
                  <td class="totalCartons">MASTER CARTON</td>
                  <td class="initialCount"></td>
            <?php } ?>
            </tr>
            <tr>
            <?php for ($i=0; $i<4; $i++) { ?>
                  <td colspan="2" class="uoms">UOM</td>
                  <td class="uom"></td>
                  <td class="sizes">SIZE</td>
                  <td class="size"></td>
            <?php } ?>
            </tr>
            <tr>
            <?php for ($i = 0; $i < 4; $i++) { ?>
                  <td colspan="2" class="prefixs">CLIENT PO</td>
                  <td class="prefix"></td>
                  <td class="suffixs">SUFFIX</td>
                  <td class="suffix"></td>
            <?php } ?>
            </tr>
            <tr>
            <?php for ($i=0; $i<4; $i++) { ?>
                  <td colspan="3" class="cartonTitle">CARTON COUNT</td>
                  <td colspan="2" class="locationCell">LOCATION</td>
            <?php } ?>
            <?php for ($r=0; $r<$this->palletRows; $r++) { ?>
                <tr>
                <?php for ($i=0; $i<4; $i++) { ?>
                      <td class="lineNumbers"><?php echo $r + 1 ?></td>
                      <td colspan="2">
                          <input name="cartonCounts[]" type="text" class="cartonCount"></td>
                      <td colspan="2">
                          <input name="locInputs[]" type="text" class="locInputs"></td>
                <?php } ?>
                </tr>
            <?php } ?>

            <tr>
            <?php for ($i=0; $i<4; $i++) { ?>
                <td colspan="5" class="textCenter">DIMENSIONS</td>
            <?php } ?>
            </tr>
            <tr>
            <?php for ($i=0; $i<4; $i++) { ?>
                <td rowspan="3" class="inchesCell"><div class="inches">INCH</div></td>

                <td class="dimLetters">L</td>
                <td colspan="3"><div class="length"></div></td>
            <?php } ?>
            </tr>
            <tr>
            <?php for ($i=0; $i<4; $i++) { ?>
                <td class="dimLetters">W</td>
                <td colspan="3"><div class="width"></div></td>
            <?php } ?>
            </tr>
            <tr>
            <?php for ($i=0; $i<4; $i++) { ?>
                <td class="dimLetters">H</td>
                <td colspan="3"><div class="height"></div></td>
            <?php } ?>
            </tr>
            <tr>
            <?php for ($i=0; $i<4; $i++) { ?>
                <td colspan="2">WEIGHT</td>
                <td>LBS</td>
                <td colspan="2" class="weight"></td>
            <?php } ?>
            </tr>
            <tr>
            <?php for ($i=0; $i<4; $i++) { ?>
                  <td colspan="3">TOTAL CARTONS RECVD</td>
                  <td colspan="2" class="receivedCartons"></td>
            <?php } ?>
            </tr>
            <tr>
            <?php for ($i=0; $i<4; $i++) { ?>
                  <td colspan="3">TOTAL UNITS RECVD</td>
                  <td colspan="2" class="units"></td>
            <?php } ?>
            </tr>
        </table>

        <form id="printLabels" action="<?php echo $this->printLabelsLink; ?>"
              method="post" target="_blank"></form>
        </div>
        <?php
    }

    /*
    ****************************************************************************
    */

    function displayReceivingView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
        echo '<button id="redirectToCreate">Create New</button>';
    }

    /*
    ****************************************************************************
    */

    function createReceivingView()
    {
        if ($this->errors) {
            $message = '<div class="failedMessage centered">';
            foreach ($this->errors as $id => $error) {
                $message .= $error . '<br>';
            }
            $message .= '</div>';
            echo $message;
        } elseif ($this->success) {
            $message = '<div class="successMessage centered" style="display: inline-block">
                Create receiving <b>'. $this->receivingID .'</b> successful!</div>';
            echo $message;
        }?>
        <form id="form-create" method="post" enctype="multipart/form-data">
            <table id="receivingForm">
                <tbody>
                    <col width="70">
                    <col width="180">
                    <col width="90">
                    <col width="180">
                    <tr>
                        <td class="red">Client #</td>
                        <td>
                            <select class="vendorSize" id="vendorID" name="vendorID" data-post>
                                <option value="0">Select a Client</option><?php
                                foreach ($this->vendors as $id => $row) {
                                    $selected = $this->vendorID == $id ? " selected" : NULL;?>
                                    <option <?php echo $selected; ?>
                                    value="<?php echo $id; ?>"><?php
                                    echo $row['fullVendorName']; ?></option><?php
                                } ?>
                            </select>
                        </td>
                        <td class="red">Ref #</td>
                        <td>
                            <input type="text" id="ref" name="ref"
                                   style="width: 215px;" data-post>

                        </td>
                    </tr>
                    <tr>
                        <td>Attach File(s): </td>
                        <td>
                            <input type="file" name="files[]" multiple/>
                        </td>
                        <input id="userID" name="userID" type="hidden"
                               value="<?php echo $this->userID; ?>" data-post>
                        <td>Description</td>
                        <td>
                            <textarea id="note" name="note" rows="3" cols="28"></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="submitUpdate">
                <input  class="backToReceiving" type="button" name="create" value="Back">
                <input id="createReceiving" type="submit" name="create" value="Create">
            </div>
        </form>

    <?php
    }

    /*
    ****************************************************************************
    */

    function updateReceivingView()
    {?>
        <form id="form-update" method="post" enctype="multipart/form-data">
            <table id="receivingForm">
                <tbody>
                    <col width="70">
                    <col width="180">
                    <col width="90">
                    <col width="180">
                    <tr>
                        <?php
                            $displayOption = $this->isView ? 'readonly' : 'disabled';
                        ?>
                        <td>Client #</td>
                        <td>
                            <input type="text" id="receiving" name="receivingNumber"
                                   value="<?php echo $this->receivingData['vendorName']?>"
                                   style="width: 215px;"  maxlength="8" data-post <?php echo $displayOption?>>
                        </td>
                        <td>Ref # </td>
                        <td>
                            <input type="text" id="receiving" name="receivingNumber"
                                   value="<?php echo $this->receivingData['ref']?>"
                                   style="width: 215px;" data-post <?php echo $displayOption?>>
                        </td>
                    </tr>
                    <tr><?php
                        if (! $this->isView) {?>
                            <td class="red">Status</td>
                            <td>
                            <select class="receivingStatus" name="receivingStatus" data-post=""><?php
                                foreach ($this->statuses as $id => $row) {
                                    $selected = $this->receivingData['statusID'] == $id
                                        ? " selected" : NULL;?>
                                    <option <?php echo $selected; ?>
                                    value="<?php echo $id; ?>">
                                    <?php echo $row['displayName']; ?></option><?php
                                } ?>
                            </select>
                            </td><?php
                        } else {
                            foreach ($this->statuses as $id => $row) {
                                if ($this->receivingData['statusID'] == $id) {
                                    $status = $row['displayName'];
                                }
                            }?>
                            <td>Status</td>
                            <td>
                            <input type="text" id="receiving" name="receivingNumber"
                                   value="<?php echo $status?>"
                                   style="width: 215px;" data-post readonly>
                            </td><?php
                        } ?>
                        <td>Description</td>
                        <td>
                            <textarea name="note" rows="3" cols="28" <?php echo $displayOption ?>>
                                <?php echo $this->receivingData['note']?>
                            </textarea>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <?php if ($this->fileArray) {
                                $message = '<br>File(s):<ul>';
                                foreach ($this->fileArray as $id => $row) {
                                    $message .= '<li><a href="' . makeLink('receiving',
                                        'update') . '/viewFile/' . $id
                                        . '">' . $row['filename'] . '</a></li>';
                                }
                                $message .= '</ul>';
                                echo $message;
                            } else echo '<div class="failedMessage">File be empty</div>';?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4" class="submitUpdate">
                            <?php if (! $this->isView) {?>
                                <input  class="backToReceiving" type="button" name="create" value="Back">
                                <input id="submitUpdate" type="submit" name="update" value="Update" re>
                            <?php } else {?>
                                <input class="backToReceiving" type="button" name="cancel" value="Back">
                            <?php }?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form><?php
        if ($this->containers) {
            echo $this->datatablesStructureHTML;
        } else {
            $message = '<div class="failedMessage">No container(s)</div>';
            echo $message;
        }

    }

    /*
    ****************************************************************************
    */

    function generateReceivingView()
    {?>
        <form method="post">
            <button id="generate" name="submit" value="Generate">Generate Receiving</button>
        </form>
        <?php if (isset($this->notification['warning'])) {?>
        <div class="centered">
                <span class="failedMessage">
                    <?php echo $this->notification['warning']?>
                </span>
        </div>
    <?php }
        if (isset($this->notification['success'])) {?>
            <div class="centered">
                <div class="successMessage">
                    <?php echo $this->notification['success']?>
                </div>
            </div>

        <?php }
        if ($this->data) {?>
            <table id="approved" style="">
                <tr>
                    <td colspan="2"><b>Containers are missing receiving</b></td>
                </tr>
                <tr>
                    <td><b>Receiving</b></td>
                    <td><b>Container Name</b></td>
                </tr>
                <?php foreach ($this->data as $data) {?>
                    <tr>
                        <td><?php echo $data['receivingID']?></td>
                        <td><?php echo $data['name']?></td>
                    </tr>
                <?php }?>
            </table>
        <?php }

    }

    /*
    ****************************************************************************
    */

    function actualReceivingView()
    {
        $this->ajax->warehouseVendorMultiSelectTableView($this, 'actualReveiving');
    }

    /*
    ****************************************************************************
    */

    function inspectionReportReceivingView()
    {
        $this->ajax->warehouseVendorMultiSelectTableView($this, 'inspectionReport');
    }

    /*
    ****************************************************************************
    */

    function containerReportReceivingView()
    {
        $this->ajax->warehouseVendorMultiSelectTableView($this, 'containerReport');
    }

    /*
    ****************************************************************************
    */

}