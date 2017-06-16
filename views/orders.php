<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{
    function addOrEditOrdersView()
    {
        $state = $this->checkType == 'Check-Out' ? 'out' : 'in';

        $masterOrders = new \tables\orders\masterOrders();

        $masterOrders->restoreCanceledOrder = $this->restoreCanceledOrder;

        if (isset($this->success) && $this->success) {
            return $this->displayResults();
        } ?>

        <table width="100%">
            <button id="print" class="button" media="print">Print</button>
            <strong id="mandarotyNote"> All Fields That Have <span class="red">*</span>
                Are Mandatory. (If no data is available, just insert NA)
            </strong>
        </table>

        <form id="splitUCCs" method="POST" target="_blank"
            action="<?php echo makeLink('inventory', 'printSplitLabels'); ?>">
        </form>

        <form id="printPage" method="POST" target="_blank"
            action="<?php echo makeLink('orders', 'printPage'); ?>">
            <div style="display: none;">
                <input id="printPageData" name="printPageData">
            </div>
        </form>

        <?php echo \common\workOrders::getView($this); ?>

        <form method="POST" id="orderForm" name="orderForm">

            <?php if ($this->checkType == 'Check-In') { ?>

            <div id="orderImport" class="message">

                <button type="button" class="downloadImportOrderTemplate">
                    Download Template
                </button>
                <br><br>
                <input type="file" id="orderImportFile" name="orderFiles">
                <button id="orderImportSubmit">Import</button>

                <div id="orderImportResults">

                    <?php $this->displayImportResults('orderImport'); ?>

                </div>
            </div>

            <?php } ?>

            <div id="splitDialog">
                <font color=red>Some cartons need split:</font><br><br>
                <table id="splitDialogTable" border="1" class="splitTable">
                    <tr>
                        <th>UPC</th>
                        <th>UCC128</th>
                        <th>UOM A</th>
                        <th>UOM B</th>
                    </tr>
                </table>
                <br>
                <button class="splitCartons">Split cartons</button>
                <span class="processing">
                    Processing split cartons. Please wait ...
                </span>
            </div>

        <?php if ($this->checkType == 'Check-In') {
            // products section is not mundatory in Order Check In page
            if (isset($this->post['buttonFlag'])
                && $this->post['buttonFlag'] == 'submitForm'
                && ! $this->duplicateNumber && ! $this->missingValues
                && ! $this->missingMandatoryValues && ! $this->integerOnly
                && ! $this->nonUTF && ! $this->isOnHold
                && ! $this->productErrors
                && ! $this->reprintWavePickd
                && ! $this->splitProducts
                && ($this->checkType == 'Check-In' || ! $this->missingProducts)
                ) { ?>

            <table border="1" id="batchTable">
                <tr>
                    <td>Vendor</td>
                    <td>Order</td>
                    <td>Batch</td>
                    <td></td>
                </tr>

                <?php $this->batchOrderTable(); ?>

            </table>
            <br>

            <?php }
        }

        for ($i = 0; $i <= $this->duplicate; $i++) { ?>

            <div id="orderNumber<?php echo $i+1 ?>" class="singleOrder"> <?php

            $checkRegularPost = isset($this->post['userid']);
            if ($checkRegularPost && $this->post['buttonFlag'] == 'submitForm') {
                $masterOrders->errorOutput([
                    'errorFields' => $this->missingValues,
                    'fieldCaptions' => $this->checkFieldsInDB,
                    'formCount' => $i,
                    'errorMessage' => $this->errorMessages['checkFieldsInDB'],
                ]);

                $masterOrders->errorOutput([
                    'errorFields' => $this->missingValues,
                    'fieldCaptions' => $this->checkFields,
                    'formCount' => $i,
                    'errorMessage' => $this->errorMessages['checkFields'],
                ]);

                $masterOrders->errorOutput([
                    'errorFields' => $this->missingMandatoryValues,
                    'fieldCaptions' => $this->checkAllFields,
                    'formCount' => $i,
                    'errorMessage' => $this->errorMessages['checkAllFields'],
                ]);
            }

            $masterOrders->errorOutput([
                'errorFields' => $this->nonUTF,
                'fieldCaptions' => $this->nonUTFCheck,
                'formCount' => $i,
                'errorMessage' => $this->errorMessages['nonUTFCheck'],
            ]);

            $masterOrders->errorOutput([
                'errorFields' => $this->integerOnly,
                'fieldCaptions' => $this->integerValuesOnly,
                'formCount' => $i,
                'errorMessage' => $this->errorMessages['integerValuesOnly'],
            ]);

            $masterOrders->errorOutput([
                'errorFields' => $this->duplicateNumber,
                'fieldCaptions' => $this->duplicateOrderNumbers,
                'formCount' => $i,
                'errorMessage' => $this->errorMessages['duplicateOrderNumbers'],
            ]);

            $scanOrderNumber = $this->inputValues['scanOrderNumber'][$i];
            $workOrderID =
                    getDefault($this->workOrderNumbres[$scanOrderNumber]['wo_id']);
            $workOrderNumber =
                    getDefault($this->workOrderNumbres[$scanOrderNumber]['wo_num']);

            $closedOrder = getDefault($this->closedOrders[$scanOrderNumber]);
            $canceledOrder = getDefault($this->canceledOrders[$scanOrderNumber]);

            $validTruckOrder = isset($this->truckOrders[$scanOrderNumber])
                       && ! $closedOrder;

            $canceledTruckOrder = isset($this->truckOrders[$scanOrderNumber])
                       && $canceledOrder;

            $truckOrder = $validTruckOrder || $this->isTruckOrderImport
                       || $canceledTruckOrder;

            $checkType = $this->checkType;

            if (isset($this->post['scanOrderNumber'])) {

                $params = [
                    'splitProducts' => $this->splitProducts,
                    'productErrors' => $this->productErrors,
                    'missingProducts' => $this->missingProducts,
                    'postOrderNumber' => $this->post['scanOrderNumber'],
                    'missingMandatoryValues' => $this->missingMandatoryValues,
                    'reprintWavePick' => $this->reprintWavePick,
                ];

                $masterOrders->productErrorOutput($checkType, $params,
                        $scanOrderNumber);
            }

            if (! isset($this->onlineOrders[$scanOrderNumber])) { ?>

                <input type="hidden" name="first_name[]" data-post>

            <?php } ?>

            <table border="1" class="firstCol">
                <col width="150px">
                <col width="130px">

            <?php

            $accessUserID = $this->checkType == 'Check-Out' ? NULL :
                    access::getUserID();

            $data = [
                'checkType' => $this->checkType,
                'inputvalues' => $this->inputValues,
                'closedOrders' => $this->closedOrders,
                'menu' => $this->menu
            ];

            $masterOrders->createMenuBox($data, [
                'page' => $i,
                'title' => 'User',
                'field' => 'userid',
                'index' => 'username',
                'array' => $this->user,
                'preset' => $accessUserID,
                'disabled' => TRUE
            ]);

            $lastNameTitle = 'Customer Name';

            if (isset($this->onlineOrders[$scanOrderNumber])) {

                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => 'Customer First Name',
                    'field' => 'first_name',
                    'maxLength' => 100,
                    'checkType'
                ]);

                $lastNameTitle = 'Customer Last Name';
            }

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => $lastNameTitle,
                'field' => 'last_name',
                'maxLength' => 100,
                'tdClass' => $this->getTDClass('last_name'),
            ]);

            $param = $this->getMenuParam([
                'page' => $i,
                'title' => 'Client Vendor Name',
                'field' => 'vendor',
                'array' => $this->vendor,
                'index' => 'fullVendorName',
                'mandatory' => TRUE,
                'disabled' => 'Check-Out',
                'emptyOption' => TRUE,
                'tdClass' => $this->getTDClass('vendor', 'missingValues'),
            ]);

            $masterOrders->createMenuBox($data, $param);

            foreach ($this->forView['userColumn'] as $show => $variable) {

                $value = $this->inputValues[$variable][$i];

                $param = [
                    'page' => $i,
                    'title' => $show,
                    'mandatory' => TRUE,
                    'field' => $variable,
                    'tdClass' => $this->getTDClass($variable, 'missingValues')
                ];

                if ($variable == 'scanOrderNumber') {
                    $param['spanClass'] = 'scanordernumberSpan';
                    $param['inputClass'] = 'scanOrderNumber';
                    $param['disabled'] = TRUE;
                } else {
                    $param['maxLength'] = 20;
                }

                $masterOrders->createInputBox($data, $param);
            }

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Client Department ID',
                'mandatory' => FALSE,
                'field' => 'deptid',
                'maxLength' => 100,
                'checkType'
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Client Pick TicKet',
                'mandatory' => FALSE,
                'field' => 'clientpickticket',
                'maxLength' => 100,
                'checkType'
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Start Ship Date',
                'mandatory' => TRUE,
                'field' => 'startshipdate',
                'inputClass' => 'datepicker shipDates',
                'maxLength' => 30,
                'tdClass' => $this->getTDClass('startshipdate', 'missingValues'),
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Cancel Date',
                'mandatory' => TRUE,
                'field' => 'canceldate',
                'inputClass' => 'datepicker cancelDates',
                'maxLength' => 30,
                'tdClass' => $this->getTDClass('canceldate', 'missingValues'),
            ]);

            $masterOrders->createMenuBox($data, [
                'page' => $i,
                'title' => 'Order Type',
                'field' => 'type',
                'index' => 'typeName',
                'array' => $this->orderType,
                'mandatory' => TRUE,
                'emptyOption' => TRUE,
                'tdClass' => $this->getTDClass('type', TRUE),
            ]);

            if ($state == 'in') { ?>

                <tr>
                    <td class="noPrint" colspan="2" align="center">
                        <button class="generateScanOrderNumber">
                            Reserve a Scan Order Number
                        </button>
                    </td>
                </tr>

            <?php } ?>

                <tr>
                    <td colspan="2" align="center">
                        <span class="barcode"></span><br>
                        <span class="barcodeFooter"><?php
                            echo $scanOrderNumber; ?>
                        </span>
                    </td>
                <tr>
            </table>

            <table border="1">
                <col width="3">
                <col width="88">
                <col width="100">
                <col width="30">
                <col width="60">

            <?php foreach ($this->forView['EcoOrReg'] as $show => $variable) {
                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'radioField' => $variable,
                    'radioName' => 'EcoOrReg',
                    'tdClass' => 'ecoCol ' . $this->getTDClass('EcoOrReg'),
                    'spanClass' => 'radioName',
                    'titleColSpan' => 4
                ]);
            }

            foreach ($this->forView['StandardColumn'] as $show => $variable) {
                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'radioField' => $variable,
                    'radioName' => 'service',
                    'tdClass' => 'standardColumn ' . $this->getTDClass('service'),
                    'spanClass' => 'standardColumn',
                    'titleColSpan' => 4,
                ]);
            }

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => '# of Cartons',
                'field' => 'numberofcarton',
                'spanClass' => 'numberofcarton',
                'inputClass' => 'numberofcarton',
                'disabled' => TRUE,
                'titleColSpan' => 3,
                'inputColSpan' => 2,
                'rightToLeft' => TRUE,
                'tdClass' => $this->getTDClass('numberofcarton'),
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => '# of Pieces',
                'field' => 'numberofpiece',
                'spanClass' => 'numberofpiece',
                'inputClass' => 'numberofpiece',
                'disabled' => TRUE,
                'titleColSpan' => 3,
                'inputColSpan' => 2,
                'rightToLeft' => TRUE,
                'tdClass' => $this->getTDClass('numberofpiece'),
            ]);

            $masterOrders->createInputBox($data, [
                 'page' => $i,
                 'title' => 'Total Volume',
                 'field' => 'totalVolume',
                 'spanClass' => 'totalVolume',
                 'inputClass' => 'totalVolume',
                 'disabled' => TRUE,
                 'titleColSpan' => 3,
                 'inputColSpan' => 2,
                 'rightToLeft' => TRUE,
                 'tdClass' => $this->getTDClass('totalVolume'),
             ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Total Weight',
                'field' => 'totalWeight',
                'spanClass' => 'totalWeight',
                'inputClass' => 'totalWeight',
                'disabled' => TRUE,
                'titleColSpan' => 3,
                'inputColSpan' => 2,
                'rightToLeft' => TRUE,
                'tdClass' => $this->getTDClass('totalWeight'),
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Pick ID',
                'field' => 'pickid',
                'spanClass' => 'pickid',
                'inputClass' => 'pickid',
                'disabled' => TRUE,
                'titleColSpan' => 2,
                'inputColSpan' => 3,
            ]);

            $masterOrders->createMenuBox($data, [
                'page' => $i,
                'title' => 'Shipping From',
                'field' => 'location',
                'index' => 'companyName',
                'titleColSpan' => 2,
                'inputColSpan' => 3,
                'array' => $this->locationtable,
                'emptyOption' => TRUE,
            ]);

            $missingClass = $this->getTDClass('PickListColNoCheck', 'missingValues');

            if ($state == 'out') { ?>

                <tr>
                    <td colspan="3" valign="top" class="picklistColumn
                        <?php echo $missingClass; ?>">

                        <span class=red>* </span> Choose At Least One From Below
                    </td>
                </tr>

            <?php }

            foreach ($this->forView['picklistColumn'] as $show => $variable) {

                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'checkField' => $variable,
                    'tdClass' => 'picklistColumn',
                    'checkName' => $variable,
                    'titleColSpan' => 4
                ]);
            }

            $missingClass = $this->getTDClass('cartonLabelsColNoCheck', 'missingValues');

            $mandatoryRow = '
                <tr>
                    <td colspan="3" valign="top" class="cartonLabelsColNoCheck ' . $missingClass . '">
                        <span class=red>* </span> Choose At Least One From Below
                    </td>
                </tr>';

            echo $state == 'out' ? $mandatoryRow : NULL;

            foreach ($this->forView['shiptoColumn'] as $show => $variable) {
                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'checkField' => $variable,
                    'checkName' => $variable,
                    'titleColSpan' => 4,
                    'tdClass' => $this->getTDClass('shiptoColumn'),
                ]);
            }

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Other Label:',
                'checkField' => 'otherlabel',
                'checkName' => 'otherlabel',
                'field' => 'otherlabelinform',
                'tdClass' => 'twoRowsTd',
                'inputSingleCell' => TRUE,
                'maxLength' => 200,
                'titleColSpan' => 4,
            ]); ?>

            </table>
            <table border="1">
            <col width="20px">
            <col width="100px">
            <col width="167px">

            <?php

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Samples #',
                'field' => 'samples',
                'inputColSpan' => 2,
                'rightToLeft' => TRUE,
                'maxLength' => 10,
                'tdClass' => $this->getTDClass('samples', 'integerOnly'),
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Pick Pack #',
                'mandatory' => 'Check-Out',
                'field' => 'pickpack',
                'inputColSpan' => 2,
                'rightToLeft' => TRUE,
                'maxLength' => 10,
                'tdClass' => $this->getTDClass('pickpack', 'integerOnly'),
            ]);

            foreach ($this->forView['sampleColumn'] as $show => $variable) {
                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'radioField' => $variable[0],
                    'radioName' => 'label',
                    'field' => $variable[1],
                    'spanClass' => 'radioName',
                    'inputColSpan' => 2,
                    'maxLength' => 20,
                    'tdClass' => $this->getTDClass('labelinfo', 'integerOnly', $variable[1]),
                ]);
            }

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => '# of Pallets',
                'field' => 'NOpallets',
                'inputColSpan' => 2,
                'rightToLeft' => TRUE,
                'maxLength' => 10,
                'tdClass' => $this->getTDClass('NOpallets', 'integerOnly'),
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => '# of Physical Labor Hrs',
                'field' => 'physicalhours',
                'inputColSpan' => 2,
                'rightToLeft' => TRUE,
                'maxLength' => 4,
                'tdClass' => $this->getTDClass('physicalhours', 'integerOnly'),
            ]);

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => '# of Over Time Labor Hrs',
                'field' => 'overtimehours',
                'inputColSpan' => 2,
                'rightToLeft' => TRUE,
                'maxLength' => 4,
                'tdClass' => $this->getTDClass('overtimehours', 'integerOnly')
            ]);

            $disableCanceled = $canceledOrder ? 'disabled' : NULL;
            $disableClosed = $closedOrder ? 'disabled' : NULL;
            $disableClosedOnly = $canceledOrder ? 'disabled' : NULL;
            $disabledClass = $disableCanceled ? ' disabledButton' : NULL;

            ?>
            <tr>
                <td colspan="2">
                    Rush Labor Amt ($)<br>

                    <?php
                        $masterOrders->displayInput($data, [
                            'page' => $i,
                            'isFieldOrder' => FALSE,
                            'field' => 'rushAmt',
                            'value' => getDefault($this->laborAmounts['rushAmt']),
                            'inputColSpan' => 2,
                            'rightToLeft' => TRUE,
                            'maxLength' => 4,
                            'inputClass' => 'labor'
                        ]);
                    ?>

                    </td>
                <td colspan="2">
                    Overtime Labor Amt ($)<br>

                    <?php
                        $masterOrders->displayInput($data, [
                            'page' => $i,
                            'isFieldOrder' => FALSE,
                            'field' => 'otAmt',
                            'value' => getDefault($this->laborAmounts['otAmt']),
                            'inputColSpan' => 2,
                            'rightToLeft' => TRUE,
                            'maxLength' => 4,
                            'inputClass' => 'otLabor'
                        ]);
                    ?>

                </td>
           </tr>
           <tr>
               <td colspan="3" align="center">
                   <button class="submitLabor <?php echo $disabledClass ?>" <?php echo $disableClosed;?>>
                    Update Labor Amt ($)</button>
               </td>
           </tr>

           <?php

            $masterOrders->createInputBox($data, [
                'page' => $i,
                'title' => 'Additional Shipper Info',
                'field' => 'additionalshipperinformation',
                'mandatory' => FALSE,
                'titleColSpan' => 2,
                'maxLength' => 20,
                'tdClass' => $this->getTDClass('deptid', 'missingValues'),
            ]);

            foreach ($this->forView['isVAS'] as $show => $variable) {
                $masterOrders->createInputBox($data, [
                    'page' => $i,
                    'title' => $show,
                    'radioField' => $variable,
                    'radioName' => 'isVAS',
                    'spanClass' => 'radioName',
                    'titleColSpan' => 2
                ]);
            } ?>

            <tr>
                <td class="noPrint" colspan="3" align="center">

            <?php

            if ($workOrderNumber) { ?>

                    <button class="editWorkOrderNumber <?php echo $disabledClass ?>"
                            data-work-order-id="<?php echo $workOrderID; ?>"
                            data-work-order="<?php echo $workOrderNumber; ?>">
                        Edit Work Order Number <?php echo $workOrderNumber; ?>
                    </button>

            <?php } else { ?>

                    <button class="editWorkOrderNumber <?php echo $disabledClass ?>"
                        <?php echo $disableClosed;?>>
                        Generate a Work Order
                    </button>

            <?php } ?>

                </td>
            </tr>

            <?php

            $masterOrders->createMenuBox($data, [
                'page' => $i,
                'title' => 'DC Person',
                'field' => 'dcUserID',
                'array' => $this->user,
                'index' => 'username',
                'emptyOption' => TRUE,
                'titleColSpan' => 2,
            ]); ?>
                <?php if ($this->checkType == 'Check-Out') { ?>

                <tr>
                    <td nowrap="nowrap" colspan="2">Created Type</td>
                    <td>
                        <span style="font-weight: bold;" class="totalWeight spanCell">
                        <?php echo  $this->dbValues[$i]['edi'] ? 'EDI' : 'USER'?>
                        </span>
                    </td>
                </tr>

                <tr>
                    <td nowrap="nowrap" colspan="2">UCC Print Type</td>
                    <td>
                    <span style="font-weight: bold;" class="totalWeight spanCell">
                    <?php echo  $this->dbValues[$i]['edi'] && $this->dbValues[$i]['isPrintUccEdi'] ? 'LINGO' : 'AWMS'?>
                    </span>
                    </td>
                </tr>

                <?php } ?>

        </table>
        <br>

        <div class="textAreasPrint"></div>
        <div class="pageBreak"></div><?php

            if ($closedOrder) {
                $closedOrder = 'data-closed-order';
            }

            $tableTitle = $truckOrder ? self::TRUCK_ORDER_PRODUCTS_TABLE_CAPTION :
                    self::REGULAR_ORDER_PRODUCTS_TABLE_CAPTION;

            $duplButton = $this->checkType == 'Check-In'
                      && ! $truckOrder && ! $this->duplicate;

            if ($this->checkType == 'Check-In') {
                $hideTruckOrderRadio = $this->duplicate ?
                        'style="display: none;"' : NULL;
            } else {
                $hideTruckOrderRadio = (count($this->dbValues) == 1
                                     && (! $closedOrder || $canceledOrder)) ?
                        NULL : 'style="display: none;"';
            }

            $displayTruckOrder = $validTruckOrder && ! $hideTruckOrderRadio
                              || $this->isTruckOrderImport || $canceledTruckOrder ?
                    NULL : 'style="display: none;"';

            if (! $i && ($canceledOrder || ! $closedOrder)) { ?>

        <div id="orderCategoryDiv" class="message" <?php echo $hideTruckOrderRadio; ?>>
            <input type="radio" name="orderCategory[<?php echo $i; ?>]" data-post
                   class="regular<?php echo $disabledClass; ?>"
                   value="regularOrder" <?php echo $disableClosedOnly; ?>
                   data-table-caption="<?php echo self::REGULAR_ORDER_PRODUCTS_TABLE_CAPTION; ?>"
                   <?php echo $truckOrder ? NULL : 'checked'; ?>>Regular Order
            <input type="radio" name="orderCategory[<?php echo $i; ?>]"
                   class="truck<?php echo $disabledClass; ?>" data-post
                   value="truckOrder" <?php echo $disableClosedOnly; ?>
                   data-table-caption="<?php echo self::TRUCK_ORDER_PRODUCTS_TABLE_CAPTION; ?>"
                   <?php echo $truckOrder ? 'checked' : NULL; ?>>ECOMM Truck
        </div>

            <?php } ?>

        <table width="100%" border="1" class="productTable" <?php echo $closedOrder; ?>>
            <col width="30px">
            <col width="20px">
            <col width="36px">
            <col width="40px">
            <col width="70px">
            <col width="55px">
            <col width="55px">
            <col width="75px">
            <col width="55px">
            <col width="45px">
            <col width="80px">
            <col width="67px">
            <col width="67px">
            <col width="45px">
            <tr>

            <?php

            $mandatory = $this->checkType == 'Check-Out' ?
                    '<span class="red">*</span> ' : NULL;

            $missingClass = getDefault($this->missingProducts[$scanOrderNumber])
                         && $this->checkType == 'Check-Out' ? 'missField' : NULL; ?>

                <th colspan="14" class="orderProductsTitle <?php echo $missingClass; ?>">
                    <?php echo $mandatory; ?>
                    <span class="orderProductsTableCaption"><?php echo $tableTitle; ?></span>
                </th>
                <th class="printOnly"></th>
            </tr>
            <tr>
                <th rowspan="3" class="noPrint">Add /<br>Rem.</th>
                <th rowspan="3">#</th>
                <th rowspan="3">UPCID</th>
                <th rowspan="2">Cartons</th>
                <th colspan="4">Descriptions:</th>
                <th rowspan="3">UOM</th>
                <th rowspan="2">Quantity</th><br>
                <th rowspan="3">Location</th>
                <th rowspan="3">Prefix</th>
                <th rowspan="3">Suffix</th>
                <th rowspan="3">Pieces<br>Availabe</th>
                <th class="printOnly" rowspan="3">Volume</th>
                <th class="printOnly" rowspan="3">Weight</th>
            </tr>
            <tr>
                <th>Style</th>
                <th>Size</th>
                <th>Color</th>
                <th>UPC</th>
            </tr>

            <?php
            $totals = $masterOrders->getProductTotals($this->post, $i); ?>

            <tr>

            <?php
            $totalCartons = $totals['totalCartons'] ? $totals['totalCartons'] :
                NULL; ?>

            <th>
                <span class="totalCartons"><?php echo $totalCartons; ?></span>
            </th>

            <?php
            $totalPieces = $totals['totalPieces'] ? $totals['totalPieces'] :
                NULL; ?>

                <th colspan="4">&nbsp</th>
                <th>
                    <span class="totalPieces"><?php echo $totalPieces; ?></span>
                </th>
            </tr>

            <?php

            $productData = [
                'checkType' => $this->checkType,
                'inputvalues' => $this->inputValues,
                'closedOrders' => $this->closedOrders,
                'postData' => $this->post,
            ];
            $count = $masterOrders->productRows($productData, $i, $closedOrder);
            $oddRowsClass = $count % 2 ? NULL : 'oddRows';

            if (! $closedOrder) { ?>

            <tr class="<?php echo $oddRowsClass; ?>">
                <td class="addRemove">
                    <button class="addRemoveDescription"
                            data-post data-table-index="<?php echo $i; ?>"
                            data-row-index="<?php echo $count; ?>"
                            data-col-index="0" >+</button>
                </td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>

            <?php } ?>

        </table>
        <br><br>

            <?php

            if (! $i) { ?>

        <div id="truckOrderImport" class="message" <?php echo $displayTruckOrder; ?>>
            <button type="button" <?php echo $disableClosed; ?>
                    class="downloadTruckOrderTemplate<?php echo $disabledClass; ?>">
                Download Template
            </button>
            <br><br>
            <input type="file" id="truckImportFile" name="truckOrderFiles"
                   class="<?php echo $disabledClass; ?>" <?php echo $disableClosed; ?>>
            <button id="truckImportSubmit" class="<?php echo $disabledClass; ?>"
                    <?php echo $disableClosed; ?>>Import</button>
        </div>

        <div id="truckOrderImportResults" class="<?php echo $disabledClass; ?>"
            <?php echo $displayTruckOrder; ?>>

            <?php $this->displayImportResults('truckOrderImport'); ?>

        </div>

        <div id="truckOrderData" <?php echo $displayTruckOrder; ?>>
            <h3 id="datatableTitle">Mixed Items Cartons</h3>
            <div id="truckOrderDataTable">

                <?php
                echo getDefault($this->datatablesStructuresHTML['truckOrderWaves'], NULL);

                $disabledClass = $disableCanceled || $disableClosed ?
                        ' disabledButton' : NULL; ?>

                <br>
                <input type="button" id="emptyTruckOrder"
                       class="<?php echo $disabledClass; ?>"
                       value="Remove Mixed Items Cartons"
                       <?php echo $disableClosed; ?>>
            </div>
        </div>

            <?php } ?>

        <div class="dvAddNewRow">
            <button class="btnAddNewRow">Add</button>
            <input size="3" type="text" class="addRowAmount" />
            <span>Row</span>
        </div>

        <div class="textAreas">

            <?php

            $noteData = [
                'checkType' => $this->checkType,
                'inputvalues' => $this->inputValues,
                'closedOrders' => $this->closedOrders,
            ];

            $masterOrders->displayTextArea($noteData, [
                'page' => $i,
                'title' => 'Order Processing Notes:',
                'field' => 'ordernotes',
                'textAreaClass' => 'ordernotes',
                'maxLength' => 400,
                'tdClass' => $this->getTDClass('ordernotes'),
            ]);

            if ($this->checkType == 'Check-In' && $i) { ?>

                <br>
                <input type="submit" value="Remove Order" name="removeOrder"
                       class="removeOrder" media="print">
            <?php } ?>

            <br><br>
            <input type = hidden value= "<?php echo $this->checkoutInput; ?>"
                   name ="checkoutInput">
            <input type=hidden name="dateentered[]"
                   value="<?php echo date('Y-m-d'); ?>" data-post>
        </div>

            <?php if ($this->checkType != 'Check-In') {

                $caption = $closedOrder ? 'View' : 'Create';
                $pickID = $this->inputValues['pickid'][$i];

                if ($canceledOrder) { ?>

        <button class="releaseCanceledOrder" data-table-index="<?php echo $i; ?>">
            Release Canceled Order
        </button>

                <?php } ?>

        <button class="displayPickTicket<?php echo $disabledClass;?>"
                <?php echo $disableCanceled; ?>><?php echo $caption; ?> Pick Ticket
        </button>
        <button class="clearWavePick<?php echo $disabledClass;?>"
                <?php echo $disableClosed; ?>>Clear Pick Ticket
        </button>
        <button class="changeBatch<?php echo $disabledClass;?>"
                <?php echo $disableClosed; ?>>Move to a New Batch
        </button>
        <button class="displayWavePick<?php echo $disabledClass;?>"
                <?php echo $disableCanceled;?> data-wave-pick="<?php echo $pickID ?>">
            View Wave Pick
        </button>
        <button class="printSplitLabels<?php echo $disabledClass;?>"
                <?php echo $disableCanceled;?> data-print-type="order">
            Print Split Labels by Order Number
        </button>
        <button class="printSplitLabels<?php echo $disabledClass;?>"
                <?php echo $disableCanceled;?> data-print-type="batch">
            Print Split Labels by Wave Pick
        </button>
        <button class="printVerificationList<?php echo $disabledClass;?>"
                <?php echo $disableCanceled;?> data-print-type="order">
            Print Processing Verification by Order Number
        </button>
        <button class="printVerificationList<?php echo $disabledClass;?>"
                <?php echo $disableCanceled;?> data-print-type="batch">
            Print Processing Verification by Wave Pick
        </button>
        <button class="printUCCLabels<?php echo $disabledClass;?>"
                <?php echo $disableCanceled;?> data-print-type="order">
            Print UCC Labels by Order Number
        </button>
        <button class="printUCCLabels<?php echo $disabledClass;?>"
                <?php echo $disableCanceled;?> data-print-type="batch">
            Print UCC Labels by Wave Pick
        </button>

        <br><br>

            <?php } ?>

            </div>

            <div class="pageBreak"></div>

        <?php }

        $displayDuplicate = $duplButton ? NULL : 'style="display: none;"'; ?>

        <br>
        <div id="listActionButton">
        <input id="buttonFlag" name="buttonFlag" type="hidden" />
        <input id="submitForm" type="button" value="Submit" name="Submit"
               class="button skipCloseConfirm" media="print">
        <?php if ($this->checkType == 'Check-Out') {?>
        <input type="hidden" name="orderIDs" value="<?php echo $this->get['orderIDs'] ?>"
                data-post />
        <?php } ?>
        <input type="button" id="duplicateButton" value="Duplicate"
               name="DuplicateButton" class="button skipCloseConfirm" media="print"
               <?php echo $displayDuplicate; ?>>
        <input type="number" id="duplicateAmount" value="<?php echo $this->duplicate; ?>"
               size=4 name="duplicate" class="button" media="print" data-post
               <?php echo $displayDuplicate; ?>>
        </div>
        </form>
        <br>

        <?php
    }

    /*
    ****************************************************************************
    */

    function searchOrdersView()
    {
        $buttons = $this->isClient ? NULL : '
            <button id="selectAll">Select All</button>
            <button id="deselectAll">Deselect All</button>
            <button id="printBOL">Print selected BOL</button>
        ';

        $this->ajax->multiSelectTableView($this, NULL, $buttons);?>

        <form id="lading" target="_blank" method="POST" style="display: none;"
              action="<?php echo makeLink('lading', 'display'); ?>">

            <input id="ladingOrders" name="orders" type="hidden" />
        </form>

        <div id="commentForm" title="Add Notes">
            <div id="addNote">
                <textarea id="commentNote" name="comment" rows="2" cols="30">
                </textarea>
            </div>
        </div>
            <?php
    }

     /*
    ****************************************************************************
    */

    function getClientStatusOrdersView()
    {
        ?>
        <table><tr><td>
        <?php
        foreach ($this->vendors as $id => $row) {
            $clientStatusLink = makeLink('orders', 'getClientStatus',[
                'vendorID' => $id]);
        ?>
            <a class="message" href="<?php echo $clientStatusLink ?>">
                View - <?php echo $row['fullVendorName']; ?>
            </a><br>
            <?php
        }

        ?>
            </td><td valign="top">
                <table class="orderStatus">
                    <tr>
        <?php
        if (getDefault($this->orderStatusCount)) {

            foreach ($this->orderStatusCount as $ids=>$id) {

                if ($id) {
                    $newOrderCount = 0;
                    $onHoldCount = 0;
                    $errCount = 0;
                    $vendorName = '';

                    foreach ($id as $orderID=>$row) {

                        if (! $vendorName){

                             $vendorName = $row['fullVendorName']; ?>

                        <td colspan="3"><?php echo $vendorName; ?></td></tr><tr><?php

                        }

                        switch (getDefault($row['shortName'])) {
                            case "WMCI":
                                $newOrderCount += $row['orderCount'];
                                break;
                            case "WMCO":
                                $newOrderCount += $row['orderCount'];
                                break;
                            case "NOHO":
                                $newOrderCount += $row['orderCount'];
                                break;
                            case "ONHO":
                                $onHoldCount += $row['orderCount'];
                                break;

                        }

                        $errCount += $row['isError'];

                    }
                    $openOrderCount = $newOrderCount - $onHoldCount - $errCount;
                    ?>

                    <td id="blue" class="orderStatus">
                        <?php echo $newOrderCount ; ?><br>New Orders
                    </td>
                    <td id="green" class="orderStatus">
                        <?php echo $openOrderCount; ?><br>Open Orders
                    </td>
                    <td id="orange" class="orderStatus">
                        <?php echo $onHoldCount; ?><br>On Hold
                    </td>
                    <td id="red" class="orderStatus">
                        <?php echo $errCount; ?><br>Orders With Errors
                    </td>
                </tr>

                <?php
                } else {
                ?>
                    <tr>
                        <td><?php echo $this->vendorNames[$ids]; ?></td>
                    </tr>
                    <tr>
                        <td class="orderStatus">No Orders for this Client!</td>
                    <tr>
                <?php
                }
            }
        } else {
            ?>
            <tr><td id = "blue" class="orderStatus">No default clients for user<td></tr>

        <?php
        }
        ?>
        </tr>
            </table></td></tr></table
        <?php

    }

    /*
    ****************************************************************************
    */

    function shippingInfoOrdersView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }

    /*
    ****************************************************************************
    */

    function shippingReportsOrdersView()
    {
        $this->ajax->multiSelectTableView($this, 'vendorID');
    }

    /*
    ****************************************************************************
    */

    function pickingCheckOutOrdersView()
    {
        $count = 1; ?>

        <div id="pickingContainer">
        <div id="orderQuantity" class="orderData">
            <?php echo 'Orders Quantity: ' . count($this->orderNumbers); ?>
        </div>
        <br>

        <?php

        $tableCount = 0;

        foreach ($this->orderNumbers as $orderNumber) { ?>

            <div class="orderData orderDiv"
                 data-order-number="<?php echo $orderNumber; ?>"
                 data-vendor="<?php echo $this->orderVendors[$orderNumber]; ?>">

                <span class="orderCount"><?php echo 'Orders # ' . $count++ ; ?></span>
                <span class="orderNumber"><?php echo $orderNumber; ?></span>

            <?php $wavePickType = $this->closedOrders[$orderNumber] ? 'picked' :
                    'original'; ?>

                <button class="viewPickTicket"
                        data-wave-pick-type="<?php echo $wavePickType; ?>">

                    View Pick Ticket
                </button>
                <br>

            <?php

            $tableCount = $this->displayPickingTables($orderNumber, $tableCount); ?>

            </div>
            <br>

        <?php }

        $closedOrders = array_filter($this->closedOrders);

        $closedOrdersCount = count($closedOrders);
        $ordersCount = count($this->orderNumbers);

        if ($closedOrdersCount != $ordersCount) { ?>
        <!-- display Submit button if only there are orders to process -->
        <button id="picking">Submit</button>

        <?php } ?>

        </div>

        <table id="approved" class="pickingResultTable">
            <tr>
                <td colspan="5">
                    <strong>Order statuses and Pick Tickets have been updated</strong>
                </td>
            </tr>
            <tr>
                <td>Order Scanned</td>
                <td>New Status</td>
                <td>Pick Ticket</td>
                <td>UCC Labels</td>
                <td>Split Labels</td>
            </tr>
        </table>

        <form class="pickedSplitLabels" target="_blank" method="POST"
              action="<?php echo makeLink('inventory', 'printSplitLabels'); ?>">

            <a class="printPickedSplitLabels" href="#">Print</a>
            <span class="noSplitLabels">No Splits</span>
            <input class="pickedSplitUccs" name="uccs">
        </form>
        <form class="pickedUccLabels" target="_blank" method="POST"
              action="<?php echo makeLink('inventory', 'printLabels') ?>">
            <button class="printPickedUCCLabels">Print</button>
            <input class="pickedUccs" name="uccs">
            <input type="hidden" class="isFromEDI" name="fromEDI">
            <input type="hidden" class="orderNumber" name="orderNumber">
        </form>

    <?php }

    /*
    ****************************************************************************
    */

    function updateShippedOrdersView()
    {
        ?>
        <table class="display" id="shippingReport"></table>

        <div id="orderCartonsDialog" title="Edit Order Carton Statuses">
            <form id="updateForm" method="post">
                <?php if ($this->isAdmin) { ?>
                <span class="message updateCartonMessages">
                    <b>Ship Selected Cartons</b><br>
                Ship cartons that you have checked to shipped status.<br>
                <button id="toggleChecks">Toggle All</button>
                <button id="updateCartons">Ship Cartons</button>
                </span>
                <span class="message updateCartonMessages">
                    <b>Ship Order Cartons</b><br>
                Ship all cartons displayed below to shipped status.<br>
                <button id="updateAllCartons">Ship Order Cartons</button>
                </span>
                <span id="wavePickSpan" class="message updateCartonMessages">
                    <b>Cancel Wave Pick</b><br>
                Cancel wave pick <span id="wavePick"></span> to unreserve all
                racked reserved cartons.<br>
                <button id="cacnelWave">Cancel Wave Pick</button>
                </span>
                <?php } ?>
            <table class="display" id="orderCartons"></table>
            </form>
        </div>
        <?php
    }

    /*
    ****************************************************************************
    */

}
