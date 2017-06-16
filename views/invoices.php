<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function listInvoicesView()
    { ?>
        <h3>Invoice Processing</h3>

        <form id="billable" method="post" target="_blank" action="<?php
            echo $this->jsVars['urls']['processInvoices']; ?>">

        <span class="customFilter">
        Client:
        <select name="vendorID" id="vendorID" class="invoicesSearch">
            <option value="">All Customers</option>
             <?php foreach ($this->vendorsHTML as $vendorID => $name) { ?>

                 <option value="<?php echo $vendorID; ?>">
                     <?php echo $name; ?>
                 </option>

             <?php } ?>
        </select>
        <button id="profilesVendors">Edit Client Profile</button>
        </span>

        <br>

        <span class="customFilter">
        Period: From
        <input type="text" id="fromDate" name="fromDate"
               class="datepicker invoicesSearch">
        To
        <input type="text" id="toDate" name="toDate"
               class="datepicker invoicesSearch">
        </span>

        <span id="invCats" class="customFilter withCheckboxes">
        <input type="checkbox" id="recSelected" name="receivingChecked">
        View Receiving
        <input type="checkbox" id="storSelected" name="storageChecked">
        View Storage
        <input type="checkbox" id="orderSelected" name="processingChecked">
        Order Processing
        </span>

        <button id="createInv">Create Invoice</button>

        <div id="invHolder" class="tableHolders">
        <span class="customFilter">
        Status:
        <select id="statusID" name="statusID" class="invoicesSearch">
            <option>Display All</option>
            <option value="1" selected>Open</option>
            <option value="2">Invoiced</option>
            <option value="3">Paid</option>
        </select>
        </span>
        <button class="printStatementButton">Print Invoice Statement</button>

        <table class="display" id="invoices"></table>
        </div>

        <div id="dtsHolder" class="tableHolders">
        <span class="customFilter withCheckboxes">
        <input type="checkbox" id="selectProcessing">
        Select All
        </span>

        <span id="note" class="highlight" style="display:none">
            *Cancelled Orders showing as Red
        </span>

        <table class="display" id="details"></table>
        </div>

        </form>

        <form id="printStatement" method="POST" target="_blank"
            action="<?php echo makeLink('invoices', 'printStatement'); ?>">
            <div style="display: none;">
                <input id="printSatementVendorID" name="vendorID">
                <input id="printSatementFromDate" name="fromDate">
                <input id="printSatementToDate" name="toDate">
                <input id="printSatementTableData" name="tableData">
            </div>
        </form>

        <div id="profileDialog">
            <form method="post" action="<?php echo $this->editCosts; ?>"
                  target="_blank">
                <input name="custID" type="hidden" id="hiddenCustID">
                <button>Edit Customer Rates</button>
            </form>
                <br>

            <span class="inlineBlock">
                <span id="customerInfo" class="inlineBlock">


                    <h3>Customer Information</h3>

                    <table id="billTo">            <tr>                <td><span class="red">*</span> Customer Code</td>
                    <td  colspan="5">
                        <input type="text" name="cust_cd" class="custCode  serialize"
                               value="">
                    </td>            </tr>            <tr>                <td><span class="red">*</span> Customer Type</td>
                    <td  colspan="5">
                        <input type="text" name="cust_type" class="custType  serialize"
                               value="">
                    </td>            </tr>            <tr>                <td><span class="red">*</span> Customer Name</td>
                    <td  colspan="5">
                        <input type="text" name="cust_nm" class="custName  serialize"
                               value="">
                    </td>            </tr>            <tr>                <td><span class="red">*</span> Bill Address</td>
                    <td >
                        <input type="text" name="bill_to_add1" class="billAdd  serialize"
                               value="">
                    </td>            </tr>            <tr>                <td><span class="red">*</span> City</td>
                    <td >
                        <input type="text" name="bill_to_city" class="custCity  serialize"
                               value="">
                    </td>                <td><span class="red">*</span> State</td>
                    <td>
                        <input type="text" name="bill_to_state" class="custState  serialize"
                               value="">
                    </td>                <td><span class="red">*</span> Zip</td>
                    <td>
                        <input type="text" name="bill_to_zip" class="custZip  serialize"
                               value="">
                    </td>            </tr>           <tr>        <td> <span class="red">*</span> Country</td>
                    <td  colspan="5">
                        <input type="text" name="bill_to_cnty" class="custCnty  serialize"
                               value="">
                    </td>            </tr>          <tr>         <td><span class="red">*</span> PYMNT-Term</td>
                    <td  colspan="5">
                        <input type="text" name="net_terms" class="terms  serialize"
                               value="">
                    </td>            </tr>        </table>

                <h3>Ship To Address</h3>

            <table id="shipTo">            <tr>                <td><span class="red">*</span> Customer Name</td>
                    <td  colspan="5">
                        <input type="text" name="cust_nm" class="custName  serialize"
                               value="">
                    </td>            </tr>            <tr>                <td><span class="red">*</span> Ship Address</td>
                    <td  colspan="5">
                        <input type="text" name="ship_to_add1" class="shipAdd  serialize"
                               value="">
                    </td>            </tr>            <tr>                <td><span class="red">*</span> City</td>
                    <td >
                        <input type="text" name="ship_to_city" class="shipCity  serialize"
                               value="">
                    </td>                <td><span class="red">*</span> State</td>
                    <td>
                        <input type="text" name="ship_to_state" class="shipState  serialize"
                               value="">
                    </td>                <td><span class="red">*</span> Zip</td>
                    <td>
                        <input type="text" name="ship_to_zip" class="shipZip  serialize"
                               value="">
                    </td>            </tr>  <tr>        <td> <span class="red">*</span> Country</td>
                    <td  colspan="5">
                        <input type="text" name="ship_to_cnty" class="shipCnty  serialize"
                               value="">
                    </td>            </tr>

            </table>


            <button id="updateCustomer">Update</button>
            </span>



            <h3>Contact Information</h3>

            <?php
            echo $this->datatablesStructuresHTML['customerContact'];
            echo $this->searcherAddRowButton;
            ?>
            <button id="deleteContacts">Delete Contacts</button>
            </span>

            <?php echo $this->searcherAddRowFormHTML; ?>
        </div>


        <div id="paymentDialog">
            Paid Date<input type="text" id="paidDate" name="date"
                            class="datepicker payment">
            <br><br>
            Paid Type<input type="text" id="paidType" name="type"
                            class="payment" maxlength="10">
            <br><br>
            Paid Reference<input type="text" id="paidReference" name="reference"
                                 class="payment"maxlength="50">
            <br><br>
            <button id="submitPayment">Submit</button>
        </div>

        <?php
    }

    /*
    ****************************************************************************
    */

    function processInvoicesView()
    {
        if ($this->errors) { ?>

        <div class="failedMessage"><?php echo implode('<br>', $this->errors); ?></div>

            <?php

            return;
        } ?>

        <h3 class="pageTitle">Invoice Issue</h3>
        <div class="infoBlock">
            <button id="cancelInvoice">Cancel</button>
            <button class="printInvoice">Print</button>
            <br>
            <br>
            Invoice No:
            <input readonly type="text" id="invoiceNo"
                   value="<?php echo sprintf('%010d', $this->invoiceNo); ?>">
        </div>

        <h2>Invoice</h2>

        <div id="billTo" class="infoBlock" style="width:49%; float: left">
            <table class="clientDetails">
                <col width="100">
                <col width="200">
                <tr><th></th>
                    <th>Bill To </th>
                </tr>
                <tr><td><span class="red">*</span> Customer Name</td>
                    <td><input type="text" name="cust_nm" class="custName serialize"
                               value="<?php echo $this->billTo['Customer Name']; ?>"></td>
                </tr>
                <tr><td><span class="red">*</span> Address</td>
                    <td><input type="text" name="bill_to_add1" class="billAdd serialize"
                               value="<?php echo $this->billTo['Address']; ?>"></td>
                </tr>
                <tr><td><span class="red">*</span> City</td>
                    <td><input type="text" name="bill_to_city" class="custCity serialize"
                               value="<?php echo $this->billTo['City']; ?>"></td>
                </tr>
                <tr><td><span class="red">*</span> State</td>
                    <td><input type="text" name="bill_to_state" class="custState serialize"
                               value="<?php echo $this->billTo['State']; ?>"></td>
                </tr>
                <tr><td><span class="red">*</span> Country</td>
                    <td><input type="text" name="bill_to_cnty" class="custCnty serialize"
                               value="<?php echo $this->billTo['Country']; ?>"></td>
                </tr>
                <tr><td><span class="red">*</span> Zip</td>
                    <td><input type="text" name="bill_to_zip" class="custZip serialize"
                               value="<?php echo $this->billTo['Zip']; ?>"></td>
                </tr>
                <tr><td> Tel</td>
                    <td><input type="text" name="ctc_tel" class="ctcTel" disabled="disabled"
                               value="<?php echo $this->billTo['Tel']; ?>"></td>
                </tr>
                <tr><td> Attn</td>
                    <td><input type="text" name="bill_to_contact" class="ctcAttn" disabled="disabled"
                               value="<?php echo $this->billTo['Attn']; ?>"></td>
                </tr>
            </table>
        </div>



        <div id="shipTo" class="infoBlock" style="width:49%; float: right">

            <table class="clientDetails">
                <col width="100">
                <col width="200">
                <tr><th></th>
                    <th>Ship To</th>
                </tr>
                <tr><td><span class="red">*</span> Customer Name </td>
                    <td><input type="text" name="cust_nm" class="custName serialize"
                               value="<?php echo $this->shipTo['Customer Name']; ?>"></td>
                </tr>
                <tr><td><span class="red">*</span> Address</td>
                    <td><input type="text" name="ship_to_add1" class="shipAdd serialize"
                               value="<?php echo $this->shipTo['Address']; ?>"></td>
                </tr>
                <tr><td><span class="red">*</span> City</td>
                    <td><input type="text" name="ship_to_city" class="shipCity serialize"
                               value="<?php echo $this->shipTo['City']; ?>"></td>
                </tr>
                <tr><td><span class="red">*</span> State</td>
                    <td><input type="text" name="ship_to_state" class="shipState serialize"
                               value="<?php echo $this->shipTo['State']; ?>"></td>
                </tr>
                <tr><td><span class="red">*</span> Country</td>
                    <td><input type="text" name="ship_to_cnty" class="shipCnty serialize"
                               value="<?php echo $this->shipTo['Country']; ?>"></td>
                </tr>
                <tr><td><span class="red">*</span> Zip</td>
                    <td><input type="text" name="ship_to_zip" class="shipZip serialize"
                               value="<?php echo $this->shipTo['Zip']; ?>"></td>
                </tr>
            </table>
            <br/>
            <button id="updateInvCust" data-ref-cust="<?php echo $this->vendorID ?>">Update</button>
        </div>

        <table id="titleTable" width="100%">
            <tr>
               <th>CUST</th>
               <th>CUST REF</th>
               <th>INV NBR</th>
               <th>SHPMNT NBR</th>
               <th>INV DT</th>
               <th>TERMS</th>
            </tr>
            <tr class="body">
               <td><?php echo $this->billTo['Customer Name']; ?></td>
               <td id="custRef"><?php echo $this->billTo['Customer Code']; ?></td>
               <td><?php echo $this->invoiceNo; ?></td>
               <td></td>
               <td id="invDt"><?php echo $this->invDt ? $this->invDt : models\config::getDateTime('date'); ?></td>
               <td id="terms"><?php echo $this->billTo['Terms']; ?></td>
            </tr>
        </table>

        <br>
        <br>
        <table id="invoiceCCs">
        <tbody>
        <?php if (isset($this->jsVars['invoInfo']['details'][$this->vendorID])) {

            $this->recItems =  getDefault($this->jsVars['invoInfo']['invItemsIDs']['Receiving']);
            $recNums = $this->recItems ? array_values($this->recItems) : [];

            $this->ordItems =  getDefault($this->jsVars['invoInfo']['invItemsIDs']['Order Processing']);
            $orderNums = $this->ordItems ? array_values($this->ordItems) : [];

            $this->ordItems = $this->cancelNums ?
                    array_diff($this->ordItems, $this->cancelNums) : $this->ordItems;

            foreach ($this->jsVars['invoInfo']['details'][$this->vendorID] as $row) { ?>
                <tr>
                    <td><?php echo $row['chg_cd']; ?></td>
                    <td><?php  echo $row['chg_cd_des']; ?></td>
                    <td>
                    <?php

                        $volDesc = getDefault($row['chg_cd_type']) === 'STORAGE' ?
                                getDefault($row['chg_cd_uom']) : NULL;


                        if ($row['chg_cd_type'] === 'RECEIVING'
                                && in_array($row['chg_cd_uom'], $this->rcvUOM)
                                &&  count($recNums) > 1) {
                        ?>
                        <button class="rcvDetail">View detail</button>

                        <?php
                        } else if ($row['chg_cd_type'] === 'RECEIVING'
                                && in_array($row['chg_cd_uom'], $this->rcvUOM)
                                &&  count($recNums) === 1) {

                            echo 'Container# ' . $this->containerName;

                        } else if ($row['chg_cd_type'] === 'ORD_PROC'
                                && $row['chg_cd_uom'] === 'ORDER'
                                &&  count($this->ordItems) > 1) {
                        ?>
                        <button class="ordDetail">View detail</button>

                        <?php
                        } else if ($row['chg_cd_type'] === 'ORD_PROC'
                                && $row['chg_cd_uom'] === 'ORDER'
                                &&  count($this->ordItems) === 1) {

                            echo 'Order# ' . $this->orderNumber;

                        } else if ($row['chg_cd_type'] === 'STORAGE'
                                && strpos($volDesc, 'VOLUME') !== FALSE) {

                            $storRange = 'CUFT  - ' . $this->storRange;

                            echo  'CUFT  - ' . $this->storRange;

                        }  else if ($row['chg_cd_type'] === 'STORAGE') {

                            $storRange = $this->storRange;

                            echo  $this->storRange;
                        }
                    ?>

                    </td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td><?php echo $row['chg_cd_uom']; ?></td>
                    <td><?php echo $row['rate']; ?></td>
                    <td><?php echo $row['ccTotal']; ?></td>
                </tr>
            <?php }
        } ?>
        </tbody>
        </table>

        <div class="infoBlock" style="width: 20%; float: right">

            <table id="summaryTable">

        <?php foreach ($this->summary as $caption => $text) { ?>

                <tr>
                    <td style="text-align: right;"><?php echo $caption; ?></td>
                    <td style="text-align: right;"><?php echo $text; ?></td>
                </tr>

        <?php } ?>

            </table>
            <button class="printInvoice">Print</button>
        </div>

        <form id="printPage" method="POST" target="_blank"
            action="<?php echo makeLink('invoices', 'printPage'); ?>">
            <div style="display: none;">
                <input id="invoiceNo" name="invoiceNo"
                       value="<?php echo $this->invoiceNo; ?>">
                <input id="printPageData" name="printPageData">
                <input type="hidden"  name="rcvItems" id="rcvListItems"
                   value="<?php echo implode(',', $recNums); ?>">
                <input type="hidden"  name="container" id="container"
                   value="<?php echo $this->containerName; ?>">
                <input type="hidden"  name="ordItems" id="ordListItems"
                   value="<?php echo implode(',', $orderNums); ?>">
                <input type="hidden" name="storRange" id="storRangeDates"
                   value="<?php echo $storRange; ?>">
            </div>
        </form>

       <?php

       ?>
        <form id="viewRcvDetail" method="post" action="<?php echo $this->detailItems; ?>"
                            target="_blank">
            <div style="display: none;">
                <input type="hidden"  name="recItems" id="recItems"
                   value="<?php echo implode(',', $this->recItems); ?>">
            </div>
        </form>

        <form id="viewOrdDetail" method="post" action="<?php echo $this->detailItems; ?>"
                            target="_blank">
            <div style="display: none;">
                <input type="hidden"  name="ordItems" id="ordItems"
                   value="<?php echo implode(',', $this->ordItems); ?>">
            </div>
        </form>

        <?php
    }

    /*
    ****************************************************************************
    */

    function printPageInvoicesView()
    {


    }

    /*
    ****************************************************************************
    */

    function chargeCodeMasterInvoicesView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
        echo $this->searcherAddRowButton;
        echo $this->searcherAddRowFormHTML;
    }

    /*
    ****************************************************************************
    */

    function detailsInvoicesView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }

    /*
    ****************************************************************************
    */

}
