<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function addOrEditOrdersController()
    {
        $this->jsVars['urls']['displayScanNumber']
            = makeLink('orders', 'displayScanNumber', 'barcode');

        $this->jsVars['urls']['insertOrderBatch']
            = customJSONLink('appJSON', 'insertOrderBatch');

        $this->jsVars['urls']['getProductInfo']
            = customJSONLink('appJSON', 'getProductInfo');

        $this->jsVars['urls']['createOrderPickTicket']
            = customJSONLink('appJSON', 'createOrderPickTicket');

        $this->jsVars['urls']['displayWavePicks']
            = makeLink('wavePicks', 'display');

        $this->jsVars['urls']['createPickTicket']
            = customJSONLink('appJSON', 'createPickTicket');

        $this->jsVars['urls']['clearWavePick']
            = customJSONLink('appJSON', 'clearWavePick');

        $this->jsVars['urls']['getWavePickData']
            = customJSONLink('appJSON', 'getWavePickData');

        $this->jsVars['urls']['getWavePickID']
            = customJSONLink('appJSON', 'getWavePickID');

        $this->jsVars['urls']['getNewLabel']
            = customJSONLink('appJSON', 'getNewLabel');

        $this->jsVars['urls']['getShippingFrom']
            = customJSONLink('appJSON', 'getShippingFrom');

        $this->jsVars['urls']['changOrdersBatch']
            = customJSONLink('appJSON', 'changOrdersBatch');

        $this->jsVars['urls']['splitOrderCartons']
            = customJSONLink('appJSON', 'splitOrderCartons');

        $this->jsVars['urls']['getUPCDescription']
            = customJSONLink('appJSON', 'getUPCDescription');

        $this->jsVars['urls']['getClientLabor']
            = customJSONLink('appJSON', 'getClientLabor');

        $this->jsVars['urls']['submitWorkOrder']
            = customJSONLink('appJSON', 'submitWorkOrder');

        $this->jsVars['urls']['printSplitLabels']
            = makeLink('inventory', 'printSplitLabels');

        $this->jsVars['urls']['printVerificationList'] =
        $this->jsVars['urls']['printUCCLabels']
            = makeLink('wavePicks', 'display');

        $this->jsVars['urls']['releaseCanceledOrder']
            = customJSONLink('appJSON', 'releaseCanceledOrder');

        $this->jsVars['urls']['downloadTruckOrderTemplate']
                = makeLink('truckOrderWaves', 'downloadTemplate');

        $this->jsVars['urls']['downloadImportOrderTemplate']
                = makeLink('orders', 'downloadImportTemplate');

        $this->jsVars['urls']['emptyTruckOrder']
                = makeLink('appJSON', 'emptyTruckOrder');

        $this->jsVars['urls']['importTruckOrder']
                = makeLink('truckOrderWaves', 'import');

        $this->jsVars['urls']['getLabor']
            = customJSONLink('appJSON', 'getLabor');
        $this->jsVars['urls']['updateLabor']
            = customJSONLink('appJSON', 'updateLabor');
        $this->jsVars['urls']['submitAddOrEditOrders']
            = customJSONLink('appJSON', 'submitAddOrEditOrders');

        $this->includeJS['custom/js/common/formToArray.js'] = TRUE;
        $this->includeJS['js/jQuery/blocker.js'] = TRUE;
        $this->includeJS['js/datatables/editables.js'] = TRUE;
        $this->includeJS['custom/js/common/scrollToElement.js'] = TRUE;
        $this->includeJS['custom/js/common/workOrders.js'] = TRUE;
        $this->includeCSS['custom/css/includes/workOrders.css'] = TRUE;

        $this->jsVars['includeWorkOrderLabor'] = TRUE;
        $this->jsVars['isOrderCheckInOut'] = TRUE;
        $this->jsVars['skipCloseConfirm'] = FALSE;
        $this->jsVars['processedOrders'] = [];
        $this->jsVars['restoreCanceledOrder'] = $this->restoreCanceledOrder;

        $this->jsVars['checkType'] = $this->checkType =
                isset($this->get['type']) ? 'Check-Out' : 'Check-In';

        $this->jsVars['isCheckOut'] =  $this->checkType == 'Check-Out' ?  1 : 0;

        $updateOrders = [];

        $orders = new tables\orders($this);

        $scanNumbers = $this->postVar('scanOrderNumber', 'getDef', []);

        if ($this->checkType == 'Check-Out') {

            $orderIDs = json_decode($this->get['orderIDs'], 'array');

            $this->jsVars['orderIDs'] = $this->get['orderIDs'];

            $updateOrders = $orders->getOrderNumbersByID($orderIDs);

            $this->onlineOrders = $orders->getOnlineOrderNumbers($updateOrders);

            $this->dbValues = common\orderChecks::getOrderInfo($this, $updateOrders);

            $scanNumbers = array_column($this->dbValues, 'scanOrderNumber');

            common\orderChecks::checkClosedOrders($this, $orders, $updateOrders);
        }

        $this->laborAmounts = common\labor::init($this)->get([
            'cat' => 'op',
            'scanNumbers' => $scanNumbers,
        ]);

        $user = new tables\users($this);
        $vendors = new tables\vendors($this);
        $orderType = new tables\orders\orderTypes($this);
        $truckOrderWaves = new tables\truckOrderWaves($this);
        $workOrderHeaders = new tables\workOrders\workOrderHeaders($this);

        $this->user = $this->dcPerson = $user->get();
        $this->vendor = $vendors->get();
        $this->commodity = $orders->selectCommodity();
        $this->locationtable = $orders->selectShipFrom();
        $this->orderType = $orderType->get();

        $combineFields = array_merge(
                $this->dbFields, $this->checkBoxes, $this->radio, $this->label
        );

        $this->inputFields = array_unique($combineFields);
        $this->radioNCheck = array_merge($this->radio, $this->checkBoxes);

        $truckOrdes = [0];

        if (isset($this->post['scanOrderNumber'])) {

            $buttonFlag = getDefault($this->post['buttonFlag'], NULL);

            $this->isOrderImport = $buttonFlag == 'importOrder';
            $this->isTruckOrderImport = $buttonFlag == 'importTruckOrder';
            $isDuplicate = $buttonFlag == 'duplicate';
            $isSubmit = $buttonFlag == 'submitForm';

            $orderNumbers = $this->post['scanOrderNumber'];

            \common\orderChecks::getOrdersStatusData($this, $orders, $orderNumbers);

            $this->workOrderNumbres =
                    $workOrderHeaders->getByScanOrderNumbers($orderNumbers);

            $repeat = count($orderNumbers);

            if ($isDuplicate) {
                $this->duplicate = $this->post['duplicate'];
                $repeat = 1;
            } elseif ($isSubmit) {
                unset($this->post['duplicate']);
                $this->duplicate = $repeat - 1;
            }

            //assign values to $this->inputValues
            for ($page = 0; $page < $repeat; $page++) {
                \common\orderChecks::getValues($this, $page);
            }

            $truckOrdes = getDefault($orderNumbers, [0]);

            if ($this->isOrderImport) {
                $this->import('orderFiles');
            } elseif ($this->isTruckOrderImport) {
                $this->import('truckOrderFiles');
            } elseif ($isDuplicate) {
                $this->formDuplicate();
            } elseif ($isSubmit) {

                $this->truckOrders =
                        $truckOrderWaves->getExistingTruckOrders($orderNumbers);

                common\orderChecks::formSubmit($this, $orders, $orderNumbers, $updateOrders);
            }
        } else if ($this->dbValues) {
            // Check-Out

            $count = 0;

            $pages = array_keys($this->dbValues);

            foreach ($pages as $id) {
                $this->restoreDBValues($count++, $id);
            }

            $truckOrdes = $orderNumbers = $this->inputValues['scanOrderNumber'];

            $this->workOrderNumbres =
                    $workOrderHeaders->getByScanOrderNumbers($orderNumbers);

            $this->restoreProductDBValues($orders);

            $this->duplicate = $count - 1;
        } else {
            foreach ($this->inputFields as $field) {
                $this->inputValues[$field][0] = NULL;
            }

            $this->duplicate = 0;
        }

        $this->jsVars['searchParams'] =
            $this->getTruckOrderInfo($truckOrdes, $truckOrderWaves);
    }

    /*
    ****************************************************************************
    */

    function searchOrdersController()
    {
        // Export Datatable
        $model = new tables\orders($this);

        $this->jsVars['outputLadingDir'] =
            models\directories::getDir('uploads', 'billoflading');

        $this->jsVars['urls']['displayProcessedLabels'] =
                makeLink('inventory', 'printProcessedLabels');

        $this->jsVars['urls']['displayProcessedPlates'] =
                makeLink('plates', 'display');

        $this->jsVars['processedOrdersStatuses'] =
            $model::getProcessedOrdersStatuses('printLabel');

        //gets sort for hold status else defaults
        $sort = isset($this->get['type']) ? $this->get['type'] : 2;

        $dtOptions = [
            'ajaxPost' => TRUE,
            'order' => [$sort => 'desc'],
        ];

        $this->modelName = $this->jsVars['modelName'] = getClass($model);

        $keys = array_keys($model->fields);

        $this->jsVars['fields'] = ['clientNotes'];

        $fields = array_merge($this->jsVars['fields'],
                ['scanOrderNumber', 'printBOL', 'statusID']);

        foreach ($fields as $field) {
           $this->jsVars['columnNumbers'][$field] = array_search($field, $keys);
        }

        $this->ajax = new \datatables\ajax($this);

        $this->ajax->multiSelectTableController([
            'app' => $this,
            'model' => $model,
            'dtOptions' => $dtOptions,
        ]);

        $this->jsVars['urls']['addOrderClientNotes']
                    = makeLink('appJSON', 'addOrderClientNotes');

        $this->isClient = \access::isClient($this);
    }

    /*
    ****************************************************************************
    */

    function getClientStatusOrdersController()
    {
        $vendor = new tables\vendors($this);

        $this->vendors = $vendor->getAlphabetizedNames();

        $vendorUsers = users\groups::commonClientLookUp($this);

        $orders = new tables\orders($this);
        if (! getDefault($this->get['vendorID'])){
	        $vendorIDs = array_keys($vendorUsers);
            foreach ($vendorIDs as $vendorID){
                $this->orderStatusCount[] = $orders->selectOrderStatusCount($vendorID);
                $this->vendorNames[] = $vendor->getVendorName($vendorID);
            }
        }else{
            $vendorID = getDefault($this->get['vendorID']);
            $this->orderStatusCount[] = $orders->selectOrderStatusCount($vendorID);
            $this->vendorNames[] = $vendor->getVendorName($vendorID);
        }
    }

    /*
    ****************************************************************************
    */

    function displayScanNumberOrdersController()
    {
        $barcode = getDefault($this->get['barcode']);

        if (! $barcode) {
            return;
        }

        barcodephp\create::display([
            'code' => 'BCGcode128',
            'filetype' => 'JPEG',
            'dpi' => 72,
            'scale' => 1,
            'rotation' => 0,
            'fontFamily' => 'Arial.ttf',
            'fontSize' => 12,
            'text' => $barcode,
            'noText' => TRUE,
        ]);
    }

    /*
    ****************************************************************************
    */

    function printPageOrdersController()
    {
        $post = json_decode($this->post['printPageData'], TRUE);

        if (! $post) {
            die('No data passed!');
        }

        $users = new tables\users($this);
        $vendors = new tables\vendors($this);
        $orderTypes = new tables\orders\orderTypes($this);
        $companyAddresses = new tables\orders\companyAddresses($this);
        $truckOrderWaves = new tables\truckOrderWaves($this);

        $this->pdf = $pdf = new \TCPDF('P', 'mm', 'Letter', TRUE, 'UTF-8', FALSE);

        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);
        $pdf->SetAutoPageBreak(TRUE, 0);
        $pdf->SetLeftMargin($this->leftMargin);
        $pdf->setCellPaddings(1, 0, 1, 0);
        $pdf->SetLineWidth(0.1);

        foreach ($post as $pageData) {

            $userID = $pageData['userid'];
            $vendorID = $pageData['vendor'];
            $typeID = $pageData['type'];
            $locationID = $pageData['location'];

            $userIDs[$userID] = TRUE;
            $vendorIDs[$vendorID] = TRUE;
            $typeIDs[$typeID] = TRUE;
            $locationIDs[$locationID] = TRUE;
        }

        $userKeys = array_keys($userIDs);
        $vendorKeys = array_keys($vendorIDs);
        $typeKeys = array_keys($typeIDs);
        $locationKeys = array_keys($locationIDs);

        $this->results = [
            'user' => $users->getByID($userKeys),
            'vendor' => $vendors->getVendorName($vendorKeys),
            'orderType' => $orderTypes->getByID($typeKeys),
            'location' => $companyAddresses->getByID($locationKeys),
        ];

        $firstRun = TRUE;

        $orderNumbers = array_column($post, 'scanOrderNumber');

        $truckOrders = $truckOrderWaves->getOutput($orderNumbers);

        foreach ($post as $pageData) {

            $orderNumber = $pageData['scanOrderNumber'];

            foreach ($this->productTableData as $column => $columnData) {
                if (in_array($column, $this->totalColumns)) {
                    $this->productTableData[$column]['header'][3] = 0;
                }

                if ($firstRun) {
                    $this->tableWidth += $columnData['width'];
                    $this->descriptionWidth += $column > 3 && $column < 8 ?
                            $columnData['width'] : 0;
                }
            }

            foreach ($truckOrderWaves->fields as $key => $value) {
                $pageData['truckOrder']['caption'][$key] = $value['display'];
            }

            $pageData['truckOrder']['data'] =
                    getDefault($truckOrders[$orderNumber], []);

            $this->pagePrint($pageData);

            $firstRun = FALSE;
        }

        $pdf->Output('pdf','I');
    }

    /*
    ****************************************************************************
    */

    function shippingInfoOrdersController()
    {
        $table = new tables\orders\shippingInfo($this);

        $keys = array_keys($table->fields);
        // sort the table by Order Number
        $orderColumn = array_search('scanOrderNumber', $keys);

        $dtOptions = [
            'ajaxPost' => TRUE,
            'order' => [$orderColumn => 'desc'],
        ];

        $ajax = new datatables\ajax($this);

        $ajax->output($table, $dtOptions);

        new datatables\searcher($table);

        new datatables\editable($table);
    }

    /*
    ****************************************************************************
    */

    function shippingReportsOrdersController()
    {
        $model = new tables\orders\shippingReports($this);

        $dtOptions = [
            'ajaxPost' => TRUE,
            'order' => [0 => 'desc'],
        ];

        $this->jsVars['multiSelect'] = TRUE;

        $this->ajax = new \datatables\ajax($this);

        $this->ajax->multiSelectTableController([
            'app' => $this,
            'model' => $model,
            'dtOptions' => $dtOptions,
        ]);
    }

    /*
    ****************************************************************************
    */

    function pickingCheckOutOrdersController()
    {
        $this->includeJS['custom/js/common/formToArray.js'] = TRUE;
        $this->includeCSS['custom/css/includes/scanners.css'] = TRUE;
        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $this->jsVars['urls']['getProductInfo']
            = customJSONLink('appJSON', 'getProductInfo');

        $this->jsVars['urls']['getUPCDescription']
            = customJSONLink('appJSON', 'getUPCDescription');

        $this->jsVars['urls']['picking']
            = customJSONLink('appJSON', 'picking');

        $this->jsVars['urls']['displayWavePicks']
            = makeLink('wavePicks', 'display');

        $orders = new tables\orders($this);
        $user = new tables\users($this);

        $this->jsVars['pickingStatus'] =
                tables\orders::STATUS_PICKING_CHECK_OUT;

        $this->user = $user->get();

        $orderIDs = json_decode($this->get['orderIDs'], 'array');

        $this->orderNumbers = $orders->getOrderNumbersByID($orderIDs);

        $checkResults = $orders->checkIfOrderProcessed($this->orderNumbers);

        foreach ($checkResults as $type => $values) {
            foreach ($values as $orderNumber => $value) {
                $this->closedOrders[$orderNumber] = $value ? $type :
                        getDefault($this->closedOrders[$orderNumber]);
            }
        }

        $results = $orders->getPickingData($this->orderNumbers);

        $this->pickingLocations = $results['pickingLocations'];
        $this->products = $results['products'];
        $this->orderVendors = $results['orderVendors'];

        $this->jsVars['pickingTableColumnClasses'] = [
            'pickingAddRemoveCell' => NULL,
            'pickingSKUCell' => 'pickingSKU',
            'pickingSizeCell' => 'pickingSize',
            'pickingColorCell' => 'pickingColor',
            'pickingUPCCell' => 'pickingUPC',
            'pickingPieceQuantityCell' => NULL,
            'pickingPrimeLocationCell' => NULL,
            'pickingPiecesPickedCell' => 'pickingPiecesPicked',
            'pickingActualLocationCell' => 'pickingActualLocation',
            'cycleCountAssignedToCell' => NULL,
            'cycleCountReportNameCell' => NULL,
            'cycleCountDueDateCell' => NULL,
        ];
    }

    /*
    ****************************************************************************
    */

    function updateShippedOrdersController()
    {
        $this->jsVars['urls']['getTables'] =
            customJSONLink('appJSON', 'getTables');
        $this->jsVars['urls']['updateShippedCartons'] =
            customJSONLink('appJSON', 'updateShippedCartons');

        $this->isAdmin =
            users\groups::commonGroupLookUp($this, 'shpCtnAdmin');

        $sql = 'SELECT n.id,
                       dateCreated,
                       n.scanOrderNumber,
                       COUNT(c.id) AS assocQty,
                       CONCAT(w.shortName, "_", vendorName) AS vendorName
                FROM   neworder n
                JOIN   statuses ns ON ns.id = n.statusID
                JOIN   inventory_cartons c ON c.orderID = n.id
                JOIN   order_batches b ON b.id = n.order_batch
                JOIN   vendors v ON v.id = b.vendorID
                JOIN   warehouses w ON w.id = v.warehouseID
                WHERE  ns.shortName = "SHCO"
                GROUP BY n.id
                ';

        $assoc = $this->queryResults($sql);

        $sql = 'SELECT n.id,
                       p.pickID,
                       COUNT(c.id) as qty
                FROM   neworder n
                JOIN   statuses ns ON ns.id = n.statusID
                JOIN   pick_cartons p ON p.orderID = n.id
                JOIN   inventory_cartons c ON c.id = p.cartonID
                WHERE  ns.shortName = "SHCO"
                AND    p.active
                GROUP BY n.id
                ';

        // cartons that are on wave pick
        // cartons taht were shipped
        // both

        $picks = $this->queryResults($sql);

        $this->jsVars['data'] = [];

        foreach ($assoc as $orderID => $row) {
            $this->jsVars['data'][] = [
                $row['dateCreated'],
                $row['vendorName'],
                getDefault($picks[$orderID]['pickID'], 'Not Found'),
                $row['scanOrderNumber'],
                getDefault($picks[$orderID]['qty'], 0),
                $row['assocQty'],
                $orderID,
            ];
        }
    }

    /*
    ****************************************************************************
    */

   function downloadImportTemplateOrdersController()
    {
        $orders = new tables\orders($this);

        $template = array_column($orders->importFields, 'display');

        csv\export::exportArray($template, 'import_order_template');
    }

    /*
    ****************************************************************************
    */

}
