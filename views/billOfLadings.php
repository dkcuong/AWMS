<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function listAddBillOfLadingsView()
    {
        echo $this->labelMakerHTML;
        echo $this->datatablesStructureHTML;
    }

    /*
    ****************************************************************************
    */

    function addShipmentBillOfLadingsView()
    {
        $masterOrders = new \tables\orders\masterOrders('bollabel');

        $masterOrders->restoreCanceledOrder = $this->restoreCanceledOrder;

        if (isset($this->success) && $this->success) {
            $success = $this->success;
            $color = $success ? '#090' : '#f00';
            $prefix = $success ? '' : 'Error.ing ';
            $suffix = $success ? ' was successfully.' : ''; ?>
            <font size="5" color="<?php echo $color; ?>"><?php
            echo $prefix.'Bill Of Lading # '.$success.$suffix; ?>
            </font>
            <br>
            <?php
            exit;
        }
        
        $missingMandatory = $this->missingMandatoryValues; ?>
            
        <table width="100%">
            <b id="mandarotyNote">
                All Fields That Have <span class="red">*</span> Are Mandatory.
                (If no data is available, just insert NA)
            </b>
        </table>
            
        <form id="optionForm" method="post" enctype="multipart/form-data" >
            <table id="optionSubmit">
                <tbody>
                <tr>
                    <td class="red">Client #</td>
                    <td>
                        <select class="vendorSize" id="vendorID" name="vendorID" data-post>
                            <option value="">Select a Client</option><?php
                            foreach ($this->vendor as $id => $row) {?>
                                <option value="<?php echo $id; ?>"><?php
                                echo $row['fullVendorName']; ?></option><?php
                            } ?>
                        </select>
                    </td>
                    <td>Created Order day(s)</td>
                    <td>
                        <select class="vendorSize" id="createdOrderDay" 
                                name="createdOrderDay" data-post>
                            <option value="">Select</option>
                            <option value="1">Today</option>
                            <option value="7">7 days before</option>
                            <option value="30">One Month before</option>
                        </select>

                    </td>
                </tr>
                <tr>
                    <td class="red">Scan Order Number</td>
                    <td>
                        <input type="text" id="ordernumber" name="ordernumber" 
                               style="width: 215px;" data-post>
                    </td>
                    <td>
                        <input class="generateOption" type="button" name="Add" 
                               value="Add">
                    </td>
                </tr>
                </tbody>
            </table>
        </form>

        <form method="POST" id="shippingForm"  name="shippingForm" >
        <?php if ($this->missingInputOrders) { ?>
            
            <div style="padding-left: 5px;">
                <font color=red><?php echo $this->missingInputOrders; ?></font>
            </div>
            
        <?php } ?>

            <input type="hidden" name="vendorID" class="vendorID">
            
        <?php for ($i = 0; $i <= $this->duplicate; $i++) {
            if (access::getUserID() && getDefault($this->post["buttonFlag"]) == 'Submit') {
                $masterOrders->errorOutput([
                    'errorFields' => $missingMandatory,
                    'fieldCaptions' => $this->checkAllFields,
                    'formCount' => $i,
                    'errorMessage' => $this->errorMessages['checkAllFields'],
                ]);
                $masterOrders->errorOutput([
                    'errorFields' => $this->integerOnly,
                    'fieldCaptions' => $this->integerValuesOnly,
                    'formCount' => $i,
                    'errorMessage' => $this->errorMessages['integerValuesOnly'],
                ]);
            } ?>

            <div class="singleOrder"> <?php

            $data = [
                'checkType' => $this->checkType,
                'inputvalues' => $this->inputValues,
                'closedOrders' => $this->closedOrders,
                'menu' => $this->menu
            ]; ?>
                    
            <table border="1">
                <col width="435px">
                
            <?php
            $firstElement = TRUE;
            $missingClass = isset($missingMandatory['shipfromid'][$i])
                ? 'missField': NULL;
            
            foreach ($this->forView['shipFromArea'] as $show => $variable) {
                if ($firstElement) { ?>
                                
                <tr>
                    <td colspan="3" nowrap="nowrap" class="<?php echo $missingClass; ?>">
                        <strong>Shipping From</strong>
                        <input class="shipfromid" name="shipfromid[]"
                               value="<?php echo getDefault($this->inputValues['shipfromid'][$i],''); ?>"
                               data-post="" type="hidden">
                    </td>
                </tr>
                                    
                    <?php
                    $firstElement = FALSE;
                }
                
                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'field' => $variable,
                    'inputSingleCell' => TRUE,
                    'mandatory' => FALSE,
                    'inputColSpan' => 2,
                    'maxLength' => 100,
                    'titleColSpan' => 4,
                    'spanClass' => $variable. 'Span',
                    'inputClass' => $variable,
                    'disabled' => TRUE
                ]);
            }

            $firstElement = TRUE;
            $missingClass = isset($missingMandatory['shiptoname'][$i])
                         || isset($missingMandatory['shiptoaddress'][$i])
                         || isset($missingMandatory['shiptocity'][$i])
                ? 'missField': NULL;
            
            foreach ($this->forView['shiptoArea'] as $show => $variable) {
                if ($firstElement) { ?>
                    
                <tr>
                    <td colspan="3" nowrap="nowrap" class="<?php echo $missingClass; ?>">
                        <strong>Shipping To</strong>
                    </td>
                </tr>
                    
                    <?php
                    $firstElement = FALSE;
                }
                
                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'field' => $variable,
                    'inputSingleCell' => TRUE,
                    'mandatory' => TRUE,
                    'inputColSpan' => 2,
                    'maxLength' => 100,
                    'titleColSpan' => 4,
                ]);
            }
            
            $firstElement = TRUE;

            foreach ($this->forView['3rdPartyArea'] as $show => $variable) {
                if ($firstElement) { ?>
                                
                <tr>
                    <td colspan="3" nowrap="nowrap">
                        <strong>Third Party Address</strong>
                    </td>
                </tr>
                                
                    <?php
                    $firstElement = FALSE;
                }
                
                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'field' => $variable,
                    'inputSingleCell' => TRUE,
                    'mandatory' => FALSE,
                    'inputColSpan' => 2,
                    'maxLength' => 100,
                    'titleColSpan' => 4,
                ]);
            }
            $missingClass = isset($missingMandatory['specialinstruction'][$i])
                ? 'missField': NULL; ?>
                    
                <tr>
                    <td valign="top" class= "<?php echo $missingClass; ?>">
                        <?php
                        $masterOrders->displayTextArea($data, [
                            'page' => $i,
                            'title' => 'Special Instructions:',
                            'field' => 'specialinstruction',
                            'textAreaClass' => 'specialinstruction',
                            'maxLength' => 400,
                            'mandatory' => TRUE,
                            'width' => 400

                        ]);?>
                    </td>
                </tr>
            </table>
            <table border="1">
                <col width="20px">
                <col width="180px">
                <col width="150px">
                <col width=200px>

            <?php foreach ($this->forView['bolColumn'] as $show => $variable) {

                $missingClass = isset($this->integerOnly[$variable][$i])
                             || isset($missingMandatory[$variable])
                    ? 'missField' : NULL;
                
                $param = [
                    'page' => $i,
                    'title' => $show,
                    'mandatory' => TRUE,
                    'field' => $variable,
                    'tdClass' => $missingClass,
                    'titleColSpan' => 2,
                    'inputColSpan' => 2,
                ];

                if ($variable == 'bollabel') {
                    $param['spanClass'] = 'bollabelSpan';
                    $param['inputClass'] = 'bollabel';
                    $param['disabled'] = TRUE;
                    $param['tdClass'] = $this->getTDClass('bolID', 'integerOnly');
                } else {
                    $param['maxLength'] = 20;
                }

                $masterOrders->createInputBox($data, $param);
            }
            
            $reservedOrder = NULL;
            
            if (isset($this->inputValues['reservedBillOfLadinglabel'][$i])) {
                $reservedOrder = $this->inputValues['reservedBillOfLadinglabel'][$i];
            } ?>
                        
                <tr>
                    <td class="noPrint" colspan="4" align="center">
                        <button class="generateBillOfLadinglabel">
                            Reserve a Bill Of Lading Label
                        </button>
                    </td>
                </tr>
                <tr>
                    <td colspan="4" align="center">
                        <span class="barcode"></span><br>
                    <span class="barcodeFooter">
                        <?php echo getDefault($this->inputValues['bollabel'][$i]); ?>
                        </span>
                    </td>
                </tr>
                    
            <?php
            $missingClass = isset($missingMandatory['carriernote'][$i])
                ? 'missField': NULL; ?>
                            
                <tr>
                    <td colspan="4" nowrap="nowrap" class="<?php echo $missingClass; ?>">
                        <strong>Carrier Information</strong>
                    </td>
                </tr>
                        
            <?php
            $missingClass = isset($missingMandatory['carriername'][$i])
                ? 'missField' : NULL;
            
            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Carrier Name',
                'field' => 'carriername',
                'titleColSpan' => 2,
                'inputColSpan' => 2,
                'maxLength' => 20,
                'mandatory' => TRUE,
                'tdClass' => $missingClass
            ]);
            
            foreach ($this->forView['fedexColumn'] as $show => $variable) {
                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'firstTitle' => $show,
                    'title' => $variable[1],
                    'mandatory' => TRUE,
                    'radioField' => $variable[0],
                    'radioName' => 'carrier',
                    'field' => $variable[2],
                    'spanClass' => 'radioName',
                    'maxLength' => 100
                ]);
            }

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'firstTitle' => 'Routing Required for Pickup',
                'title' => 'Route by Date:',
                'mandatory' => TRUE,
                'radioField' => 'routing',
                'radioName' => 'carrier',
                'field' => 'routebydate',
                'inputClass' => 'datepicker',
                'maxLength' => 30
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'firstTitle' => 'Will Call/Client Arranged:',
                'title' => 'Note:',
                'mandatory' => TRUE,
                'radioField' => 'willcall',
                'radioName' => 'carrier',
                'field' => 'willcallnote',
                'titleColSpan' => 2,
                'inputSingleCell' => TRUE,
                'maxLength' => 100
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'firstTitle' => 'Specific Carrier Requested:',
                'title' => 'Note:',
                'mandatory' => TRUE,
                'radioField' => 'specificcarrier',
                'radioName' => 'carrier',
                'field' => 'specificnote',
                'titleColSpan' => 2,
                'inputSingleCell' => TRUE,
                'maxLength' => 100
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'firstTitle' => 'Ship To Labels:',
                'title' => 'Note:',
                'mandatory' => TRUE,
                'radioField' => 'shiptolabels',
                'radioName' => 'carrier',
                'field' => 'shiptolabelsnote',
                'titleColSpan' => 2,
                'inputSingleCell' => TRUE,
                'maxLength' => 100
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'firstTitle' => 'EDI/ASN/UCC128:',
                'title' => 'Note:',
                'mandatory' => TRUE,
                'radioField' => 'ediasn',
                'radioName' => 'carrier',
                'field' => 'ediasnnote',
                'titleColSpan' => 2,
                'inputSingleCell' => TRUE,
                'maxLength' => 100
            ]);

            $missingClass = isset($missingMandatory['commodity'][$i])
                ? 'missField': NULL;

            $masterOrders->createMenuBox($data, [
                'page' => $i,
                'title' => 'Commodity Description',
                'field' => 'commodity',
                'index' => 'description',
                'array' => $this->commodity,
                'titleColSpan' => 2,
                'inputColSpan' => 2,
                'mandatory' => TRUE,
                'emptyOption' => TRUE,
                'tdClass' => $missingClass
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Other Document',
                'mandatory' => FALSE,
                'checkField' => 'otherdocument',
                'checkName' => 'otherdocument',
                'field' => 'otherdocumentinform',
                'inputColSpan' => 2,
                'maxLength' => 200,
            ]);

            $missingClass = isset($missingMandatory['freightchargetermby'][$i])
                         || isset($missingMandatory['freightchargeterminfo'][$i])
                         || isset($this->integerOnly['freightchargeterminfo'][$i])
                ? 'missField': NULL; ?>

            </table>

            <table border="1">
                <col width="20px">
                <col width="180">
                <col width="230">
                <tr class="<?php echo $missingClass; ?>">
                    <td colspan="2"><strong>Freight Charge Terms</strong></td>
                    <td><strong>Cost</strong></td>
                </tr>
            
            <?php foreach ($this->forView['freightchargetermby'] as $show => $variable) {
                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'radioField' => $variable[0],
                    'radioName' => 'freightchargetermby',
                    'field' => $variable[1],
                    'spanClass' => 'radioName',
                    'inputColSpan' => 2,
                    'mandatory' => TRUE,
                    'maxLength' => 250,
                    'tdClass' => $this->getTDClass('freightchargeterminfo', 'integerOnly', $variable[1]),
                ]);
            }

            $missingClass = isset($missingMandatory['feetermby'][$i])
                ? 'missField' : NULL;?>
                        
                <tr class="<?php echo $missingClass; ?>">
                    <td colspan="3"><strong>Fee Terms</strong></td>
                </tr>
                                    
            <?php foreach ($this->forView['feetermby'] as $show => $variable) {

                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'radioField' => $variable,
                    'radioName' => 'feetermby',
                    'mandatory' => TRUE,
                    'tdClass' => 'feetermby',
                    'spanClass' => 'radioName',
                    'titleColSpan' => 4
                ]);
            }

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Customer check acceptable',
                'checkField' => 'acceptablecustomer',
                'tdClass' => 'standardColumn',
                'checkName' => 'acceptablecustomer',
                'titleColSpan' => 4
            ]);

            $missingClass = isset($missingMandatory['trailerloadby'][$i])
                ? 'missField': NULL;?>

            </table>
            <table border="1">
                <col width="20px">
                <col width="180px">
                <col width="350px">
                <tr class="<?php echo $missingClass; ?>">
                    <td colspan="4"><strong>Trailer Load</strong></td>
                </tr>
                        
            <?php foreach ($this->forView['trailerloadby'] as $show => $variable) {

                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'radioField' => $variable,
                    'radioName' => 'trailerloadby',
                    'mandatory' => TRUE,
                    'tdClass' => 'trailerloadby ',
                    'spanClass' => 'radioName',
                    'titleColSpan' => 4
                ]);
            }

            $missingClass = isset($missingMandatory['trailercountedby'][$i])
                ? 'missField' : NULL;?>
                        
                <tr class="<?php echo $missingClass; ?>">
                    <td colspan="5"><strong>Freight Counted</strong></td>
                </tr>
                        
            <?php foreach ($this->forView['trailercountedby'] as $show => $variable) {
                
                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'radioField' => $variable,
                    'radioName' => 'trailercountedby',
                    'mandatory' => TRUE,
                    'tdClass' => 'trailercounted',
                    'spanClass' => 'radioName',
                    'titleColSpan' => 4
                ]);
            } ?>
                        
                <input type="hidden" class="ordernumbers[]"
            </table>

            <table width="100%" border="1" class="productTable">
                <col width="40px">
                <col width="30px">
                <col width="100px">
                <col width="100px">
                <col width="50px">
                <col width="50px">
                <col width="50px">
                <col width="50px">
                <col width="70px">
                <col width="70px">
                <col width="80px">
                <col width="130px">
                <tr>
                    <th colspan="12" class="orderProductsTitle">
                        Customer Order Information
                    </th>
                </tr>
                <tr>
                    <th class="noPrint">Add /<br>Rem.</th>
                    <th>#</th>
                    <th>Order Number #</th>
                    <th>Customer PO #</th>
                    <th># PKGS</th>
                    <th>Units</th>
                    <th>Weight</th><br>
                    <th>PLTS</th>
                    <th>Client <br>DEPT #</th>
                    <th>Client <br>Pick Ticket</th>
                    <th>Client<br>PO #</th>
                    <th>Additional Shipper <br>Info</th>
                </tr>
                <?php
                    if (isset($this->orderLists)) {
                        $index = 1;
                        foreach ($this->orderLists as $order => $data) {?>
                            <tr>
                                <td class="noPrint">
                                    <input type="checkbox" class="addRemove"
                                           name="scanOrderNumbers[]" checked
                                           value="<?php echo $data['ordernumber']; ?>"
                                           data-val="<?php echo $data['ordernumber']; ?>"
                                           data-post="<?php echo $data['ordernumber']; ?>">
                                </td>
                                <td><?php echo $index; ?></td>
                                <td><?php echo $data['ordernumber']; ?></td>
                                <td><?php echo $data['customerordernumber']; ?></td>
                                <td><?php echo $data['pkgs']; ?></td>
                                <td><?php echo $data['cartonUnit']; ?></td>
                                <td><?php echo $data['weight']; ?></td>
                                <td><?php echo $data['countPlates']; ?></td>
                                <td><?php echo $data['deptid']; ?></td>
                                <td><?php echo $data['clientpickticket']; ?></td>
                                <td><?php echo $data['clientordernumber']; ?></td>
                                <td><?php echo $data['additionalshipperinformation']; ?></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php
                            $index++;
                        }
                    }
                ?>
            </table>
            <br>
            </div>
            
        <?php } ?>
            
            <div class="pageBreak"></div>
            
            <br>
            
            <div align="right">
                <input id="buttonFlag" name="buttonFlag" type="hidden" />
                <input id="submit" type=submit value=Submit name=Submit
                       class="button skipCloseConfirm" media="print">
            </div>
        </form>
        <?php
    }

    /*
    ****************************************************************************
    */

    function searchBillOfLadingsView()
    {
        echo $this->searcherHTML;
        if ($this->isEdit) { ?>
        <div id="searcher">
            <form method="post" onsubmit="generateNewManualBOL(this); return false;">
                <label for="Adjust">Input Order#(s) for new BOL</label>
                <input id="ordernumbersAdjust" type="text"
                       placeholder="Input Order#(s) (separating Order#(s) with a comma)"
                       style="width:40%" />
                <input name="submit" type="submit" value="Submit" />
                <div id="displayMessage"></div>
            </form>
        </div>
        <?php }
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }

}