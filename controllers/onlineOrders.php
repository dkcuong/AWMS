<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{
    /*
    ****************************************************************************
    */

    function importOnlineOrdersController()
    {
        $this->beginStore();

        $vendors = new tables\vendors($this);
        $dealSites = new tables\dealSites($this);
        $this->info = new tables\onlineOrders\importsInfo();
        $onlineOrders = $this->onlineOrders =  new tables\onlineOrders($this);
        $this->batches = new tables\batches($this);

        $this->vendors = $vendors->get();
        $this->dealSites = $dealSites->get();

        $this->fileSubmitted = getDefault($_FILES) && ! $_FILES['file']['error'];

        if (getDefault($_FILES) && $_FILES['file']['error']) {
            die('Error Submitting a File');
        }

        if ($this->fileSubmitted) {

            $this->importer = new excel\importer($this, $onlineOrders);

            $onlineOrders->excelImport($this->importer);

            if ($this->importer->errors) {
                $onlineOrders->errors = array_merge($onlineOrders->errors,
                        $this->importer->errors);
            }

            $_SESSION['badRows'] = $this->importer->badRows;
            $_SESSION['errorOrders'] = $this->importer->errorOrders;

            if ($_SESSION['errorOrders']) {
                $_SESSION['errorOrders']['fileName'] = $_FILES['file']['name'];
            }
        }

        if (isset($this->post['exportErrorOrders']) &&
                getDefault($_SESSION['errorOrders'])) {

            $fileName = $_SESSION['errorOrders']['fileName'];

            $pos = strpos($fileName, '.');

            $exportTo = $pos === FALSE ? $fileName : substr($fileName, 0, $pos);

            $fieldKeys = [];

            foreach ($_SESSION['errorOrders']['titles'] as $title) {
                $fieldKeys[] = ['title' => $title];
            }

            \excel\exporter::ArrayToExcel([
                'data' => $_SESSION['errorOrders']['data'],
                'fileName' => $exportTo . ' error orders ' . date('Y-m-d h:i:s'),
                'fieldKeys' => $fieldKeys,
            ]);

            die;
        }

        if (isset($this->post['exportBad'])) {
            excel\exporter::header('Failed_Rows');
            excel\exporter::arrayToTable($_SESSION['badRows']);
            die;
        }

        if (isset($this->post['export'])) {

            $orders = $onlineOrders->getOnlineOrders();

            excel\exporter::header('online_orders_export');
            $onlineOrders->listOrderTable($orders, $export=TRUE);
            die;
        }

        if (isset($this->post['template'])) {
            $onlineOrders->listOrderTableTemplate();
            die;
        }

        $this->commitStored();

        $sortColumn = $this->declareDatatableJsVars($onlineOrders);

        $ajax = new datatables\ajax($this);

        $dtStructure = $ajax->output($onlineOrders, [
            'ajaxPost' => TRUE,
            'order' => [
                $sortColumn => 'desc'
            ]
        ]);

        $requestClass = appConfig::get('site', 'requestClass');
        $this->jsVars['dataTables'][$requestClass] = $dtStructure->params;
        $this->jsVars['urls']['getMultiRowOrders'] = makeLink('appJSON',
                'getMultiRowOrders');
        $this->jsVars['urls']['listExported'] = makeLink('onlineOrders',
                'listExported');
        $this->jsVars['urls']['printLabel'] = makeLink('onlineOrders',
                'printLabel');
    }

    /*
    ****************************************************************************
    */

    function listExportedOnlineOrdersController()
    {
        $order = getDefault($this->get['orderNo']);
        // Default order IDs to order if it was passed
        $batch = getDefault($this->get['batch']);

        $this->shortages = [];

        // Need online order table for creation
        $this->onlineOrders = new tables\onlineOrders($this);
        $onlineOrders = new tables\onlineOrders($this);
        $exports = new tables\onlineOrderExports($this);

        $orderIDs = $batch ? $this->onlineOrders->getBatchOrdersID($batch) :
            [$order];

        if (count($orderIDs)) {
            // Check if the order requested has been exported
            $ordersExist = $exports->checkOrderExport($orderIDs);

            $ordersInfo = $onlineOrders->getExportOrdersInfo($orderIDs);

            $quantities = [];

            foreach ($ordersInfo as $orderInfo) {

                $upcID = $orderInfo['upcID'];

                $quantities[$upcID] = getDefault($quantities[$upcID], 0);

                $quantities[$upcID] += $orderInfo['quantity'];
            }

            $orderInfo = reset($ordersInfo);

            $this->getExportInventory($orderInfo['vendorID'], $quantities);

            $difference = array_diff_key($ordersInfo, $ordersExist);

            if ($difference) {

                $this->calculateOrdersVolumes($difference);

                if ($this->shortages) {

                    ksort($this->shortages);

                    $this->includeCSS['custom/css/includes/scanners.css'] = TRUE;

                    return;
                }

                $exportID = $exports->getNextID('online_orders_exports');

                $this->beginTransaction();

                $this->createCarrierExport($exportID);

                $this->commit();
            }
        }

        // Export Datatalbe

        $exportKeys = array_keys($exports->fields);

        $searchField = $order ? 'orderID' : 'order_batch';

        $orderColumn = array_search($searchField, $exportKeys);

        $ajax = new datatables\ajax($this);

        $dtStructure = $ajax->output($exports, [
            'ajaxPost' => TRUE,
            'order' => [
                0 => 'desc'
            ],
            'columns' => [
                $orderColumn => [
                    'searchable' => TRUE
                ]
            ]
        ]);

        new datatables\searcher($exports);
        new datatables\editable($exports);

        $this->jsVars['dataTables']['onlineOrderExports'] = $dtStructure->params;
        $this->jsVars['urls']['checkExportTable']
                = makeLink('appJSON', 'checkExportTable');
    }

    /*
    ****************************************************************************
    */

    function importCarrierOnlineOrdersController()
    {
        if (isset($this->post['exportBad'])) {
            excel\exporter::header('Failed_Rows');
            excel\exporter::arrayToTable($_SESSION['badRows']);
            die;
        }

        $this->fileSubmitted = getDefault($_FILES) && ! $_FILES['file']['error'];

        $onlineOrders = new tables\onlineOrders($this);

        $orders = $onlineOrders->getOnlineOrders();

        if (isset($this->post['export'])) {
            excel\exporter::header('online_orders_export');
            $onlineOrders->listOrderTable($orders, $export=TRUE);
            die;
        }

        $onlineOrderExports = new tables\onlineOrderExports($this);

        if ($this->fileSubmitted) {

            $pathInfo = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);

            $this->importer = $pathInfo == 'csv' ?
                    new csv\importer($this, $onlineOrderExports) :
                    new excel\importer($this, $onlineOrderExports);

            $this->importer->uploadPath = \models\directories::getDir('uploads',
                    'onlineOrdersImportsUPSLabels');

            $this->importer->insertFile();

            $this->importer->badRows = $onlineOrderExports->badRows;

            $_SESSION['badRows'] = $this->importer->badRows;
        }

        $sortColumn = $this->declareDatatableJsVars($onlineOrders);

        $ajax = new datatables\ajax($this);

        $customDT = [
            'ajaxPost' => TRUE,
            'order' => [
                $sortColumn => 'desc'
            ]
        ];

        $dtStructure = $ajax->output($onlineOrders, $customDT);

        $this->jsVars['urls']['listExported'] = makeLink('onlineOrders',
                'listExported');
        $this->jsVars['dataTables']['onlineOrders'] = $dtStructure->params;
        $this->jsVars['urls']['printLabel'] = makeLink('onlineOrders',
                'printLabel');
    }

    /*
    ****************************************************************************
    */

    function searchOnlineOrdersController()
    {
        $model = new tables\onlineOrders($this);

        $sortColumn = $this->declareDatatableJsVars($model);

        $dtOptions = [
            'ajaxPost' => TRUE,
            'order' => [
                $sortColumn => 'desc'
            ]
        ];

        $this->modelName = getClass($model);
        // Export Datatalbe
        $this->ajax = new \datatables\ajax($this);

        $this->ajax->multiSelectTableController([
            'app' => $this,
            'model' => $model,
            'dtOptions' => $dtOptions,
        ]);

        $modelName = [
            'modelName' => 'onlineOrders'
        ];

        $this->jsVars['urls']['filter'] = jsonLink('filterSearcher', $modelName);
        $this->jsVars['urls']['searcher'] = jsonLink('datatables', $modelName);
        $this->jsVars['urls']['listExported'] = makeLink('onlineOrders',
                'listExported');
        $this->jsVars['urls']['printLabel'] = makeLink('onlineOrders',
                'printLabel');
    }

    /*
    ****************************************************************************
    */

    function editDirectoriesOnlineOrdersController()
    {
        $show = getDefault($this->get['show']);

        $orderColumn = 0;

        switch ($show) {
            case 'providers':
                $table = new tables\onlineOrders\exportsProviders($this);
                break;
            case 'packages':
                $table = new tables\onlineOrders\exportsPackages($this);
                break;
            case 'services':
                $table = new tables\onlineOrders\exportsServices($this);
                break;
            case 'billTo':
                $table = new tables\onlineOrders\exportsBillTo($this);
                break;
            case 'signatures':
                $table = new tables\onlineOrders\exportsSignatures($this);
                break;
            default:
                die;
        }

        $ajax = new datatables\ajax($this);

        $ajax->output($table, [
            'ajaxPost' => TRUE,
            'order' => [
                $orderColumn => 'asc'
            ]
        ]);

        new datatables\searcher($table);
        $editable = new datatables\editable($table);

        $editable->canAddRows();
    }

    /*
    ****************************************************************************
    */

    function wavePicksOnlineOrdersController()
    {
        $onlineOrders = new tables\wavePicks\create($this);

        // Export Datatalbe
        $ajax = new datatables\ajax($this);

        $ajax->output($onlineOrders, [
            'ajaxPost' => TRUE,
            'order' => [
                1 => 'desc'
            ]
        ]);

        $this->onlineOrders = $onlineOrders;

        $modelName = [
            'modelName' => 'onlineOrders'
        ];

        new datatables\searcher($onlineOrders);
        new datatables\editable($onlineOrders);

        $this->jsVars['urls']['filter'] = jsonLink('filterSearcher', $modelName);
        $this->jsVars['urls']['searcher'] = jsonLink('datatables', $modelName);
        $this->jsVars['urls']['listExported'] = makeLink('onlineOrders',
                'listExported');
    }


    /*
    ****************************************************************************
    */

    function listFailsOnlineOrdersController()
    {
        $searchTime = getDefault($this->post['searchTime'], time());

        $fails = new tables\onlineOrdersFails($this);

        $results = $fails->getFails($searchTime);

        // Export Datatalbe
        $ajax = new datatables\ajax($this);

        $dtStructure = $ajax->output($fails, [
            'ajaxPost' => TRUE,
        ]);

        if (isset($this->post['export'])) {
            excel\exporter::header('online_orders_import_failures_export');
            $fails->listFailTable($results, $export=TRUE);
            die;
        }

        $this->fails = $fails;

        $this->jsVars['dataTables']['onlineOrdersFails'] = $dtStructure->params;
        $this->jsVars['urls']['listExported'] = makeLink('onlineOrders',
                'listExported');
    }

    /*
    ****************************************************************************
    */

    function incorrectOnlineOrdersController()
    {
        $model = new tables\incorrectOnlineOrders($this);

        $this->modelName = getClass($model);

        // Export Datatable
        $ajax = new datatables\ajax($this);

        $ajax->output($model, [
            'ajaxPost' => TRUE,
            'bFilter' => FALSE
        ]);
    }

    /*
    ****************************************************************************
    */

    function listUpdateFailsOnlineOrdersController()
    {
        $searchTime = getDefault($this->post['searchTime'], time());

        $fails = new tables\onlineOrdersFailsUpdate($this);

        $results = $fails->getFails($searchTime);

        // Export Datatalbe
        $ajax = new datatables\ajax($this);

        $dtStructure = $ajax->output($fails, [
            'ajaxPost' => TRUE,
        ]);

        if (isset($this->post['export'])) {
            excel\exporter::header('online_orders_update_failures_export');
            $fails->listFailTable($results, $export=TRUE);
            die;
        }

        $this->fails = $fails;

        $this->jsVars['dataTables']['onlineOrdersFails'] = $dtStructure->params;
        $this->jsVars['urls']['listExported'] = makeLink('onlineOrders',
                'listExported');
    }
    /*
    ****************************************************************************
    */

    function printLabelOnlineOrdersController()
    {
        $getOrderNumber = $this->get['orderNumber'];

        $sql = $this->getBarcode($getOrderNumber);

        $results = $this->queryResults($sql, [$getOrderNumber]);

        $pdf = new labels\orderLabels;

        $pdf->writePDFPage($pdf);

        $count = 0;

        $title = 'New Order';

        $barcode = NULL;

        foreach ($results as $row) {

            $barcode = $row['SCAN_SELDAT_ORDER_NUMBER'];
            $orderDate = $row['dateCreated'];

            $strOrderDate = strtotime($orderDate);

            $date = date('Y-m-d', $strOrderDate);

            for ($i = 0; $i <= 2; $i++) {

                $txt = $title."\n".$date."\n".$barcode;

                $pdf->writeBarcodes($pdf, $barcode, $txt);
            }

            ++$count % 10 ? $pdf->Ln() : $pdf->AddPage();
        }

        $pdf->Output($title . '_' . $barcode . 'pdf', 'I');

        return $pdf;
    }

    /*
    ****************************************************************************
    */
    function openOnlineOrdersReportOnlineOrdersController()
    {
        $model = new tables\onlineOrders\openOnlineOrdersReport($this);

        $this->modelName = getClass($model);

        $this->ajax = new datatables\ajax($this);

        $fields = array_keys($model->fields);

        $fieldKeys = array_flip($fields);

        $this->jsVars['onlineOrders']['dateColumnNo'] = $fieldKeys['vendorName'];
        $this->jsVars['isOpenReportOnlineOrder'] = true;

        $this->ajax->warehouseVendorMultiSelectTableController([
            'app' => $this,
            'model' => $model,
            'dtOptions' => [
                'bFilter' => FALSE,
                'order' => [
                    'vendorName' => 'ASC',
                ],
            ],
            'warehouseField' => 'shortName',
            'vendorFieldName' => 'vendorName',
            'searcher' => FALSE,
        ]);

    }

    /*
    ****************************************************************************
    */
}