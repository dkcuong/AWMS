<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use common\pdf;
use labels\create;
use datatables\searcher;
use tables\inventory\cartons;
use common\logger;
use \get\string;
use \reports\inventoryReport;
use tables\inventory\requestChangeStatus;

class controller extends template
{

    /*
    ****************************************************************************
    */

    function parseRow(
        $csvDir, $name, $columns, $clientIDs, &$maxUPCID, &$upcs, $locIDs,
        $measureIDs, $inventoryContainers
    ) {
        $csv = $csvDir.'/'.$name;

        $ext = pathinfo($csv, PATHINFO_EXTENSION);

        $row = 0;

        if ($ext != 'csv' || ($handle = fopen($csv, "r")) === FALSE) {
            return;
        }

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

            $colCount = count($columns);

            $padded = array_pad($data, $colCount, NULL);

            $values = array_combine($columns, $padded);

            $sku = $values['sku'];
            $upc = $values['upc'];
            $uom = $values['uom'];
            $container = $values['container'];

            $client = strtolower($values['warehouse'].$values['client']);

            $row++;

            if (! getDefault($clientIDs[$client])) {

                if (! array_filter($data)) {
                    return;
                }

                vardump($row);
                vardump($client);
                vardump($clientIDs);
                die('Vendor Not Found');
            }

            $values['vendorID'] = $clientIDs[$client]['id'];

            if (isset($upcs[$upc])) {
                $values['upcID'] = $upcs[$upc]['id'];
            } else {
                $upcID = $values['upcID'] = $maxUPCID++;
                $upcs[$upc]['id'] = $newUPCs[$upc]['id'] = $upcID;
            }

            $location = strtolower($values['warehouse'].$values['location']);

            if (! isset($locIDs[$location])) {
                echo strtoupper($values['location']).'<br>';
            }
            //$values['locID'] = $locIDs[$location]['id'];

            $measure = $values['measure'];
            $values['measureID'] = $measureIDs[$measure];

            $batchIndex = $sku . $upc . $uom;

            $tallies[$container]['uoms'][$batchIndex] = $values['uom'];
            $tallies[$container]['upcs'][$batchIndex] = $values['upc'];
            $tallies[$container]['styles'][$batchIndex] = $values['sku'];
            $tallies[$container]['locations'][$batchIndex] = $values['location'];
            $tallies[$container]['warehouse'] = $values['warehouse'];

            unset($values['warehouse'], $values['client'], $values['index'],
                  $values['palletSheetID'], $values['measure']);

            // Group the inventory into batches by UPC/SKU/UOM
            if (isset($inventoryContainers[$container][$batchIndex])) {
                $tallies[$container]['cartons'][$batchIndex] =
                $inventoryContainers[$container][$batchIndex]['initialCount'] +=
                    $values['initialCount'];
            } else {
                $inventoryContainers[$container][$batchIndex] = $values;
                $tallies[$container]['cartons'][$batchIndex][] = $values['initialCount'];
            }
        }
    }

    /*
    ****************************************************************************
    */

    function createTallyInventoryController()
    {
        $this->jsVars['tallyRows'] = 50;

        $this->jsVars['urls']['getContainerTally']
            = customJSONLink('appJSON', 'getContainerTally');
        $this->jsVars['urls']['getContainerNames']
            = customJSONLink('appJSON', 'getContainerNames');
        $this->jsVars['urls']['updateTally']
            = customJSONLink('appJSON', 'updateTally');
        $this->jsVars['urls']['getContainerInfo']
            = customJSONLink('appJSON', 'getContainerInfo');
        $this->jsVars['urls']['printLabels']
            = makeLink('inventory', 'search', 'cartonLabels');

    }

    /*
    ****************************************************************************
    */

    function splitAllInventoryController()
    {
        $this->modelSetListOfSplitPages();

        $container = getDefault($this->post['container']);
        $getContainer = getDefault($this->post['getContainer']);

        $splitContainer = $container ? $container : $getContainer;

        $this->results = $this->styles = $this->maps = [];

        $cartons = new cartons($this);

        if ($container) {
            $this->results = $this->split('name', $container, $cartons);

            if ($this->results['error']) {
                return;
            }
        }

        $this->mapStyles('name', $splitContainer, $cartons);
    }

    /*
    ****************************************************************************
    */

    function componentsInventoryController()
    {
        $this->includeJS['js/datatables/editables.js'] = TRUE;

        $this->addCartons = getDefault($this->post['addCartons']);

        $show = getDefault($this->get['show']);

        $defaultOrderField = 'setDate';
        $defaultOrderDir = 'desc';
        $bFilter = TRUE;

        $this->ajax = new datatables\ajax($this);

        switch ($show) {
            case 'upcs':
                $table = new tables\inventory\upcs($this);
                $defaultOrderField = 'sku';
                break;
            case 'modifyContainers':
                $table = new tables\inventory\modifyContainers($this);

                $fields = array_keys($table->fields);

                $fieldKeys = array_flip($fields);

                $this->jsVars['columnNumbers'] = [
                    'name' => $fieldKeys['name'],
                    'modify' => $fieldKeys['modify'],
                    'recNum' => $fieldKeys['recNum'],
                ];

                break;
            case 'containers':
                $table = new tables\inventory\containers($this);

                $fields = array_keys($table->fields);

                $fieldKeys = array_flip($fields);

                $this->jsVars['columnNumbers'] = [
                    'name' => $fieldKeys['name'],
                    'recNum' => $fieldKeys['recNum'],
                ];

                break;
            case 'batches':
                $table = new tables\inventory\batches($this);

                $table->commonMysqlFilter('twoWeeks', $this, $this->ajax);

                $fields = array_keys($table->fields);

                $fieldKeys = array_flip($fields);

                $this->jsVars['columnNumbers'] = [
                    'batchID' => $fieldKeys['batchID'],
                    'plate' => $fieldKeys['plate'],
                ];

                break;
            case 'cartons':
                $table = new cartons($this);
                $table->commonMysqlFilter('oneMonth', $this, $this->ajax);
                break;
            case 'locBatches':
                $table = new tables\inventory\locBatches($this);
                $table->commonMysqlFilter('twoWeeks', $this, $this->ajax);
                $defaultOrderField = 'l.displayName';
                $defaultOrderDir = 'asc';
                break;
            case 'pallets':
                $table = new tables\inventory\pallets($this);
                $table->commonMysqlFilter('oneMonth', $this, $this->ajax);
                $defaultOrderField = 'id';
                $bFilter = FALSE;
                break;
            case 'plates':
                $table = new tables\inventory\plates($this);
                $table->commonMysqlFilter('oneMonth', $this, $this->ajax);
                $defaultOrderField = 'plate';
                $defaultOrderDir = 'asc';
                $bFilter = FALSE;
                break;
            case 'control':
                $table = new tables\inventory\control($this);
                $defaultOrderField = 'recNum';
                $bFilter = FALSE;
                break;
            case 'vendorPallets':
                $table = new tables\inventory\vendorPallets($this);
                $defaultOrderField = 'vendorName';
                $defaultOrderDir = 'asc';
                $bFilter = FALSE;
                break;
            case 'styleLocations':
                $table = new tables\locations\styleLocations($this);
                $table->commonMysqlFilter('twoWeeks', $this, $this->ajax);
                $defaultOrderField = 'sku';
                $defaultOrderDir = 'asc';
                $bFilter = FALSE;
                break;
            case 'groupLocation':
                $table = new tables\locations\groupLocation($this);
                $defaultOrderField = 'sku';
                $defaultOrderDir = 'asc';
                $bFilter = FALSE;
                break;
            case 'shipped':
                $table = new tables\inventory\shipped($this);
                $defaultOrderField = 'orderShipDate';
                $bFilter = FALSE;
                break;
            case 'noMezzanine':
                $table = new tables\locations\noMezzanine($this);
                $defaultOrderField = 'sku';
                $defaultOrderDir = 'asc';
                $bFilter = FALSE;
                break;
            case 'history':
                $table = new tables\inventory\history($this);
                $table->setMysqlFilters([
                    'ajax' => $this->ajax,
                    'trigger' => TRUE,
                    'searches' => [
                        [
                            'selectField' => 'Log Time Starting',
                            'selectValue' => string::date(-1, 'MONTH'),
                            'clause' => 'logTime > NOW() - INTERVAL 1 MONTH',
                        ],
                    ],
                ]);
                $defaultOrderField = 'logTime';
                $defaultOrderDir = 'asc';
                $bFilter = FALSE;
                break;
            case 'summaryReport':
                $table = new tables\inventory\summaryReport($this);
                $defaultOrderField = 'sku';
                $bFilter = FALSE;
                break;
            default:
                die;
        }

        $dtStructure = [
            'order' => [$defaultOrderField => $defaultOrderDir],
            'bFilter' => $bFilter,
        ];

        $this->batchNumber = $batchNumber = getDefault($this->get['batchID']);
        if ($batchNumber) {

            $sql = 'SELECT   ca.id,
                             plate
                    FROM     inventory_batches b
                    JOIN     inventory_cartons ca ON b.id = ca.batchID
                    WHERE    batchID = ?
                    GROUP BY plate';

            $this->posiblePlates = $this->queryResults($sql, [$batchNumber]);

            $this->ajax->addControllerSearchParams([
                'values' => [$batchNumber],
                'field' => 'batchID',
                'exact' => TRUE,
            ]);
        }

        $multiSelect = \access::isClient($this);

        $this->ajax->output($table, $dtStructure, $multiSelect);

        $this->userClient = users\groups::commonClientLookUp($this);
        $this->jsVars['isClient'] = \access::isClient($this);

        $vendors = new tables\vendors($this);
        $this->vendors = $vendors->get();

        $searcher = new searcher($table);

        $searcher->createMultiSelectTable([
            'title' => 'Select Clients To View',
            'idName' => 'vendorID',
            'trigger' => $multiSelect,
            'subject' => $this->vendors,
            'isClient' => $this->jsVars['isClient'],
            'selected' => $this->userClient,
            'fieldName' => 'fullVendorName',
            'searchField' => 'v.id',
        ]);

        new datatables\editable($table);

        $this->jsVars['modify'] = getDefault($this->get['modify']);
        $this->jsVars['editable'] = getDefault($this->get['editable']);
        $this->jsVars['urls']['addCartons'] = makeLink('inventory', 'components', [
            'show' => 'cartons',
        ]);

        $this->jsVars['urls']['modify'] = $this->jsVars['urls']['modify']
                = makeLink('seldatContainers', 'scan', [
                    'modify' => 'container',
                ]);

        $this->jsVars['urls']['addBatches'] = $this->jsVars['urls']['addBatches']
                = makeLink('seldatContainers', 'scan');

        $this->jsVars['urls']['splitBatches'] =
                makeLink('inventory', 'splitBatches');

        $this->jsVars['urls']['createCartons'] = customJsonLink(
                'appJSON', 'addCartonsToBatch',['batchID' => $batchNumber]
        );

        $this->jsVars['urls']['editLocationBatch'] = customJsonLink(
                'appJSON', 'editLocationBatch'
        );
    }

    /*
    ****************************************************************************
    */

    function availableInventoryController()
    {
        $this->datableFilters = [
            'warehouseID',
            'vendorID',
            'warehouseType'
        ];

        $warehouseType = [];
        foreach ($this->warehouseType as $key => $type) {
            $warehouseType[$key] = [
                'warehouseType' => $type
            ];
        }

        $table = new tables\inventory\available($this);
        $this->ajax = new datatables\ajax($this);

        $dtStructure = [
            'order' => [
                'vendorName' => 'desc'
            ],
            'bFilter' => FALSE
        ];

        $this->ajax->warehouseVendorMultiSelectTableController([
            'app' => $this,
            'model' => $table,
            'dtOptions' => $dtStructure,
            'whsType' => $warehouseType,
            'display' => [
                'showWhsType' => TRUE,
                'warehouseType' => TRUE
            ]
        ]);

        $this->jsVars['multiselect'] = TRUE;
        }

    /*
    ****************************************************************************
    */

    function searchInventoryController()
    {
        $this->includeJS['js/datatables/editables.js'] = TRUE;

        $this->isReprint = isset($this->get['reprint']) ||
            isset($this->get['cartonLabels']);

        // labels are downloaded in PDF - close window
        $uccLabelFile = getDefault($this->post['uccLabelFile']);

        $vendor = getDefault($this->get['vendor']);

        $bFilter = $this->isReprint ? FALSE : TRUE;

        switch ($vendor) {
            case 'eliteBrands':
                $model = new tables\inventory\vendors\eliteBrands($this);
                break;
            default:
                $model = new cartons($this);
                $fieldKeys = array_flip(array_keys($model->fields));
                $this->jsVars['columnNumbers']['created_at'] = $fieldKeys['created_at'];
                $this->jsVars['isCycleCarton'] = TRUE;
        }

        $this->userClient = users\groups::commonClientLookUp($this);

        // Don't want the multi-selector to limit the admin print capablilities
        $this->notReprinting = ! $this->isReprint;

        $this->ajax = new \datatables\ajax($this);

        $model->commonMysqlFilter('oneMonth', $this, $this->ajax);

        $dtOptions = create::getDTLabels([
            'app' => $this,
            'ajax' => $this->ajax,
            'model' => $model,
            'bFilter' => $bFilter,
            'multiSelect' => $this->notReprinting,
        ]);

        $vendors = new tables\vendors($this);

        $this->vendors = $vendors->get();

        $this->jsVars['isClient'] = \access::isClient($this);

        $searcher = new searcher($model);
        $searcher->createMultiSelectTable([
            'title' => 'Select Clients To View',
            'idName' => 'vendorID',
            'trigger' => $this->notReprinting,
            'subject' => $this->vendors,
            'isClient' => $this->jsVars['isClient'],
            'selected' => $this->userClient,
            'fieldName' => 'fullVendorName',
            'searchField' => 'v.id',
        ]);

        new datatables\editable($model);

        if ($uccLabelFile || isset($dtOptions['fileName'])) {

            $filePath = $dtOptions['filePath'] ? $dtOptions['filePath'] :
                models\directories::getDir('uploads', 'uccLabels');

            $uccLabelFile = $dtOptions['fileName'] ? $dtOptions['fileName'] :
                $uccLabelFile;

            // download a PDF file with UCC labels
            pdf::download($filePath, $uccLabelFile);
            // once UCC labels are downloaded in PDF - close the following tab
            // immediately after it is displayed
            $this->jsVars['closeTabOnLoad'] = TRUE;
        }

        $this->jsVars['isReprint'] = $this->isReprint;
        $this->jsVars['urls']['printLabels']
            = makeLink('inventory', 'search', 'cartonLabels');
        $this->jsVars['urls']['splitCarton']
            = customJSONLink('appJSON', 'splitCarton');
        $this->jsVars['splitCartons'] = getDefault($this->get['split']) == 'cartons';
        $this->jsVars['compareOperator'] = getDefault($dtOptions['compareOperator']);
    }

    /*
    ****************************************************************************
    */

    function splitterInventoryController()
    {
        $this->modelSetListOfSplitPages();

        $postUcc = getDefault($this->post['UCC'], []);
        $postUomA = getDefault($this->post['uomA'], []);
        $postUomB = getDefault($this->post['uomB'], []);

        $splitData = [];

        $uccData = array_filter($postUcc);

        foreach ($uccData as $key => $value) {

            $ucc = trim($value);

            $uomA = (int)getDefault($postUomA[$key], 0);
            $uomB = (int)getDefault($postUomB[$key], 0);

            $splitData[$ucc] = [$uomA, $uomB];
        }

        if (! $splitData) {

            $this->splitErrors[] = 'No cartons were submitted';

            return;
        }

        $cartons = new \tables\inventory\cartons($this);

        $this->results = $cartons->split($splitData, TRUE);
    }

    /*
    ****************************************************************************
    */

    function barcodeInventoryController()
    {
        $uccs = isset($this->post['uccs']) ? $this->post['uccs'] : NULL;

        if (isset($this->post['GenerateBarCode'])) {
            labels\create::splitCartonsLabels($this, $uccs);
        }
    }

    /*
    ****************************************************************************
    */

    function splitBatchesInventoryController()
    {
        $batchNumber = $this->get['batchID'];

        $this->results = $this->styles = $this->maps = [];

        $cartons = new cartons($this);

        $this->results = $this->split('batchID', $batchNumber, $cartons);

        $this->mapStyles('batchID', $batchNumber, $cartons);
    }

    /*
    ****************************************************************************
    */

    function listSplitCartonsInventoryController()
    {
        $type = getDefault($this->get['type']);

        $this->unsplit = $this->method = $table = NULL;

        switch ($type) {
            case 'reprint' :
                $table = new tables\printSplitCartonLabels($this);
                $this->method = 'printSplitLabels';
                break;
            case 'unsplit':
                $table = new tables\unsplitCartons($this);
                $this->unsplit = $this->method = 'unsplitCartons';
                $this->jsVars['urls']['unsplitCartons'] =
                    customJSONLink('appJSON', 'unsplitCartons');
                break;
            case 'reprintUnsplit':
                $table = new tables\printUnSplitCartonLabels($this);
                $this->method = 'printUnSplitLabels';
        }

        $this->modelName = getClass($table);

        $ajax = new datatables\ajax($this);

        $customDT = [
            'order' => [
                1 => 'desc',
                2 => 'asc'
            ],
            'bFilter' => FALSE,
        ];

        $dtStructure = $ajax->output($table, $customDT);

        new searcher($table);

        $this->jsVars['dataTables'][$this->method] = $dtStructure->params;
    }

    /*
    ****************************************************************************
    */

    function unsplitCartonsInventoryController()
    {
        $ucc = getDefault($this->get['ucc']);

        $cartons = new cartons($this);

        $ucc128 = $cartons->fields['ucc128']['select'];

        $sql = 'SELECT    ip.id,
                          ip.childID,
                          ip.parentID
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      inventory_splits ip ON ip.parentID = ca.id
                WHERE     ' . $ucc128 . ' = ?';

        $results = $this->queryResults($sql, [$ucc]);

        $sql = 'UPDATE    inventory_splits ls
                JOIN      inventory_cartons ca ON ls.parentID = ca.id
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                SET       ls.active = 0
                WHERE     ' . $ucc128 . ' = ?';

        $this->runQuery($sql, [$ucc]);

        $children = [];

        foreach ($results as $result) {
            $children[] = $result['childID'];
            $parent = $result['parentID'];
        }

        $childrenCount = count($children);

        // New unSplit values for children
        $toValues = array_fill(0, $childrenCount, 1);
        $fromValues = array_fill(0, $childrenCount, 0);

        logger::getFieldIDs('cartons', $this);

        logger::getLogID();

        $this->beginTransaction();

        $sql = 'UPDATE    inventory_cartons ca
                LEFT JOIN inventory_splits sp ON sp.parentID =  ca.id
                SET       unSplit = 1
                WHERE     ca.id IN (' . $this->getQMarkString($children) . ')';

        $this->runQuery($sql, $children);

        $sql = 'UPDATE    inventory_cartons ca
                LEFT JOIN inventory_splits sp ON sp.parentID =  ca.id
                SET       isSplit = 0
                WHERE     ca.id = ?';

        $this->runQuery($sql, [$parent]);

        logger::edit([
            'db' => $this,
            'transaction' => FALSE,
            'primeKeys' => $children,
            'fields' => [
                'locID' => [
                    'fromValues' => $fromValues,
                    'toValues' => $toValues,
                ],
            ]
        ]);

        logger::edit([
            'db' => $this,
            'primeKeys' => $parent,
            'fields' => [
                'isSplit' => [
                    'fromValues' => 1,
                    'toValues' => 0,
                ],
            ],
            'transaction' => FALSE,
        ]);

        $this->commit();

        echo 'Unsplit successfully';
    }

    /*
    ****************************************************************************
    */

    function printSplitLabelsInventoryController()
    {
        if (isset($this->post['uccs'])) {

            $uccData = $this->post['uccs'];

            $uccs = is_array($uccData) ? $uccData : explode(',', $uccData);
        } else {

            $splits = new inventory\splits($this);

            $target = isset($this->get['order']) ? 'order' : 'batch';

            $uccData = $splits->getSplitCartons($target, $this->get[$target]);

            $uccs = array_keys($uccData);
        }

        if ($uccs) {
            labels\create::splitCartonsLabels($this, $uccs);
        } else {
            die('No Cartons Found');
        }
    }

    /*
    ****************************************************************************
    */

    function printLabelsInventoryController()
    {
        $uccData = $this->post['uccs'];
        $fromEDI = $this->post['fromEDI'];
        $orderNumber = $this->post['orderNumber'];

        $uccs = is_array($uccData) ? $uccData : explode(',', $uccData);

        if ($uccs) {
            $fromEDI ? labels\create::printUCCLabelEDIFormat($this, $orderNumber) :
                labels\create::pickedCartonsLabels($this, $uccs);
        } else {
            die('No Cartons Found');
        }
    }

    /*
    ****************************************************************************
    */

    function printUccLabelsInventoryController()
    {
        $orderNumber = $this->get['orderNumber'];

        return \tables\orders::printUccsLabel($this, $orderNumber);
        
    }

    /*
    ****************************************************************************
    */

    function printProcessedLabelsInventoryController()
    {
        $orderNumber = getDefault($this->get['order']);

        return \tables\orders::printUccsLabel($this, $orderNumber, TRUE);
    }

    /*
    ****************************************************************************
    */

    function pickCartonsInventoryController()
    {
        $model = new tables\inventory\pickCartons($this);

        $dtOptions = [
            'ajaxPost' => TRUE,
            'order' => [
                0 => 'desc'
            ]
        ];

        $this->modelName = getClass($model);
        // Export Datatalbe
        $this->ajax = new \datatables\ajax($this);

        $this->ajax->multiSelectTableController([
            'app' => $this,
            'model' => $model,
            'dtOptions' => $dtOptions
        ]);
    }

    /*
    ****************************************************************************
    */

    function pickErrorsInventoryController()
    {
        $model = new tables\inventory\pickErrors($this);

        $dtOptions = [
            'ajaxPost' => TRUE,
            'order' => [
                0 => 'desc'
            ]
        ];

        $this->modelName = getClass($model);
        // Export Datatalbe
        $this->ajax = new \datatables\ajax($this);

        $this->ajax->multiSelectTableController([
            'app' => $this,
            'model' => $model,
            'dtOptions' => $dtOptions
        ]);
    }

    /*
    ****************************************************************************
    */

    function summaryReportInventoryController()
    {
        $model = new tables\inventory\summaryReport($this);

        $keys = array_keys($model->fields);
        // sort the table by Batch Number
        $setDateColumn = array_search('setDate', $keys);

        $this->jsVars['fields'] = [
            'name', 'vendorID', 'upc', 'sku', 'prefix', 'suffix', 'uom'
        ];

        $isClient = \access::isClient($this);

        $this->jsVars['statuses'] = $isClient ? [
                'racked' => tables\inventory\cartons::STATUS_RACKED,
                'reserved' => tables\inventory\cartons::STATUS_RESERVED,
            ] : [
                'inactive' => tables\inventory\cartons::STATUS_INACTIVE,
                'received' => tables\inventory\cartons::STATUS_RECEIVED,
                'racked' => tables\inventory\cartons::STATUS_RACKED,
                'reserved' => tables\inventory\cartons::STATUS_RESERVED,
                'picked' => tables\inventory\cartons::STATUS_PICKED,
                'processed' => tables\inventory\cartons::STATUS_ORDER_PROCESSING,
                'shipping' => tables\inventory\cartons::STATUS_SHIPPING,
                'shipped' => tables\inventory\cartons::STATUS_SHIPPED,
                'discrepant' => tables\inventory\cartons::STATUS_DISCREPANCY
            ];

        $this->jsVars['urls'] = [
            'containers' => makeLink('containers', 'display'),
        ];

        $statusNames = array_keys($this->jsVars['statuses']);

        $fields = array_merge($this->jsVars['fields'], $statusNames, ['recNum']);

        foreach ($fields as $field) {
            $this->jsVars['columnNumbers'][$field] = array_search($field, $keys);
        }

        $dtOptions = [
            'ajaxPost' => TRUE,
            'bFilter' => FALSE,
            'order' => [
                $setDateColumn => 'desc'
            ]
        ];

        $setMysqlFilters = [
            'trigger' => TRUE,
            'searches' => [
                [
                    'selectField' => 'Date Rec Starting',
                    'selectValue' => date('Y-m-d'),
                    'clause' => 'setDate > NOW() - INTERVAL 1 DAY',
                ],
            ],
        ];

        $this->modelName = getClass($model);
        // Export Datatalbe
        $this->ajax = new \datatables\ajax($this);

        $this->ajax->multiSelectTableController([
            'app' => $this,
            'model' => $model,
            'dtOptions' => $dtOptions,
            'setMysqlFilters' => $setMysqlFilters,
        ]);
    }

    /*
    ****************************************************************************
    */

    function summaryCartonsInventoryController()
    {
        $table = new cartons($this);
        $upcs = new tables\upcs($this);
        $statuses = new tables\statuses\inventory($this);

        $ajax = new datatables\ajax($this);

        $this->includeJS['js/datatables/editables.js'] = TRUE;

        $container = $this->post['name'];
        $upc = $upcs->getUPCInfo($this->post['upc']);
        $prefix = $this->post['prefix'];
        $suffix = $this->post['suffix'];
        $uom = $this->post['uom'];
        $status = $statuses->getStatusID($this->post['status']);
        $manualStatus = $statuses->getStatusID($this->post['manualStatus']);

        $values = [$container, $upc['id'], $uom, $status];
        $fields = ['name', 'b.upcID', 'uom', 'ca.statusID'];

        $racked = tables\inventory\cartons::STATUS_RACKED;
        $reserved = tables\inventory\cartons::STATUS_RESERVED;

        $rackID = $statuses->getStatusID($racked);
        $reservedID = $statuses->getStatusID($reserved);

        if ($status == $rackID) {
            $values[] = $manualStatus == $reservedID ? $reservedID : $rackID;
            $fields[] = 'ca.mStatusID';
        }

        $count = count($fields);

        $ajax->addControllerSearchParams([
            'values' => $values,
            'field' => $fields,
            'andOrs' => array_fill(0, $count - 1, 'AND')
        ]);

        $batchData = [
            'prefix' => $prefix,
            'suffix' => $suffix,
        ];

        foreach ($batchData as $field => $value) {
            // specific handle for possible NULL values
            $params = $value ? [
                'values' => [$value],
                'field' => [$field],
            ] : [
                'values' => ['', NULL],
                'field' => [$field, $field],
            ];

            $ajax->addControllerSearchParams($params);
        }

        $ajax->output($table, [
            'bFilter' => FALSE,
            'compareOperator' => 'exact'
        ]);

        new searcher($table);
    }

    /*
    ****************************************************************************
    */

    function printUnSplitLabelsInventoryController()
    {
        $uccs = NULL;
        if (isset($this->post['uccs'])) {

            $uccData = $this->post['uccs'];

            $uccs = is_array($uccData) ? $uccData : explode(',', $uccData);
        }

        if ($uccs) {
            labels\create::unsplitCartonsLabels($this, $uccs);
        } else {
            die('No Cartons Found');
        }
    }

    /*
    ****************************************************************************
    */

    function styleLocationsInventoryController()
    {
        $table = new tables\locations\styleLocations($this);

        $value = $this->post['value'];
        $field = $this->post['field'];

        $isClient = \access::isClient($this);

        // display all clients for Seldat employees only
        $vendorID = $isClient ? $this->post['vendorID'] : NULL;

        $table->controllerData([$value], $field, $vendorID);

        $this->includeJS['js/datatables/editables.js'] = TRUE;

        new searcher($table);
    }

    /*
    ****************************************************************************
    */

    function styleHistoryInventoryController()
    {
        $model = new \tables\inventory\styleHistory($this);
        $this->ajax = new \datatables\ajax($this);

        $this->ajax->warehouseVendorMultiSelectTableController([
            'app' => $this,
            'model' => $model,
            'dtOptions' => ['bFilter' => FALSE],
            'setMysqlFilter' => $this->styleHistoryReportMysqlFilter(),
        ]);

        $this->jsVars['styleHistory'] = TRUE;
    }

    /*
    ****************************************************************************
    */

    function sccInventoryController()
    {
        $this->jsVars['urls'] = [
            'sccCats' => customJSONLink('appJSON', 'sccGet', [
                'acSearch' => 'cats'
            ]),
            'sccCatTypes' => customJSONLink('appJSON', 'sccGet', [
                'acSearch' => 'catTypes'
            ]),
            'getHistory' => customJSONLink('appJSON', 'sccGet', [
                'stock' => 'history'
            ]),
            'sccUpdate' => customJSONLink('appJSON', 'sccUpdate'),
        ];

        $table = tables\inventory\scc\items::init($this);

        $ajax = new datatables\ajax($this);
        $ajax->output($table, ['ajaxPost' => TRUE]);

        $this->jsVars['itemsFieldIDs'] = $ajax->getFieldKeyNames();

        new datatables\searcher($table);

        $this->includeJS['custom/js/scc.js'] = TRUE;
    }

    /*
    ****************************************************************************
    */

    function receivingReportInventoryController()
    {
        $this->ajax = new datatables\ajax($this);

        $table = new tables\inventory\recevingReport($this);

        $table->setMysqlFilters([
            'ajax' => $this->ajax,
            'trigger' => TRUE,
            'searches' => [
                [
                    'selectField' => 'ASN DT Starting',
                    'selectValue' => string::date(-1, 'MONTH'),
                    'clause' => 'co.setDate > NOW() - INTERVAL 1 MONTH',
                ],
            ],
        ]);

        $defaultOrderField = 'asnDt';
        $defaultOrderDir = 'desc';
        $bFilter = FALSE;

        $dtStructure = [
            'order' => [$defaultOrderField => $defaultOrderDir],
            'bFilter' => $bFilter,
        ];

        $this->ajax->output($table, $dtStructure);

        new searcher($table);
    }

    /*
    ****************************************************************************
    */

    function mezzanineTransferredInventoryController()
    {
        $this->message = '';
        $vendors = new tables\vendors($this);
        $warehouses = new tables\warehouses($this);

        $this->warehouse = $warehouses->getWarehouse();
        $this->vendors = $vendors->get();

        $this->includeCSS['css/datatables/searcher.css'] = TRUE;
        $this->jsVars['urls']['getCustomerByWarehouseID'] =
            customJSONLink('appJSON', 'getCustomerByWarehouseID');

        $this->jsVars['customSearcher'] = TRUE;

        $type = getDefault($this->post['download']);

        if ($type) $this->message = inventoryReport::processDownloadCarton($this, $type);

    }

    /*
    ****************************************************************************
    */

    public function getCartonsByPlateInventoryController()
    {
        $this->licensePlate = getDefault($this->get['licensePlate']);
        $this->batch = getDefault($this->get['batch']);

        if (! $this->licensePlate) {
            $next = makeLink('scanners', 'scanLicensePlate');

            return redirect($next);
        }

        $this->includeJS['js/datatables/editables.js'] = TRUE;
        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $table = new tables\inventory\licensePlateCartons($this);
        $this->ajax = new datatables\ajax($this);

        $this->ajax->addControllerSearchParams([
            'values' => [$this->licensePlate],
            'field'  => 'ca.plate'
        ]);

        $this->ajax->addControllerSearchParams([
            'values' => [$this->batch],
            'field'  => 'ca.batchID'
        ]);

        $fields = array_keys($table->fields);
        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers'] = [
            'action'            => $fieldKeys['action']
        ];

        $this->ajax->output($table, [
            'iDisplayLength' => $this->defaultShowData,
            'ajaxPost' => TRUE,
        ]);

        new datatables\searcher($table);

        $this->jsVars['urls']['updateCartonUomByPlate'] =
            makeLink('appJSON', 'updateUomCartonByPlate');

    }

    /*
    ****************************************************************************
    */

    public function getCartonsEditUomByPlatesInventoryController()
    {
        $this->licensePlates = getDefault($_SESSION['licensePlates']);

        if (! $this->licensePlates) {

            $next = makeLink('scanners', 'scanLicensePlate');

            return redirect($next);
        }

        $this->includeJS['js/datatables/editables.js'] = TRUE;

        $table = new tables\inventory\licensePlateBatch($this);
        $this->ajax = new \datatables\ajax($this);

        $this->ajax->addControllerSearchParams([
            'values' => $this->licensePlates,
            'field'  => 'ca.plate'
        ]);

        $fields = array_keys($table->fields);
        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers'] = [
            'action'           => $fieldKeys['action'],
            'licensePlate'     => $fieldKeys['plate']
        ];

        $this->ajax->output($table,[
            'bFilter' => FALSE,
            'compareOperator' => 'exact'
        ]);

        new \datatables\searcher($table);

        $this->jsVars['urls']['getCartonEditByPlate'] =
                makeLink('inventory', 'getCartonsByPlate');

    }

    /*
    ****************************************************************************
    */

    function changeStatusInventoryController()
    {
        $table = new \tables\inventory\requestChangeStatus($this);

        $ajax = new datatables\ajax($this);

        $this->reqID = getDefault($this->get['req']);

        $fields = array_keys($table->fields);
        $fieldKeys = array_flip($fields);
        $this->includeJS['js/jQuery/blocker.js'] = TRUE;
        $this->includeJS['js/datatables/editables.js'] = TRUE;

        $this->jsVars['requestStatusArray'] =
            requestChangeStatus::getRequestStatus();
        $this->jsVars['columnNumbers'] = [
            'req_dtl_id' => $fieldKeys['req_dtl_id'],
            'sts' => $fieldKeys['req_sts']
        ];

        $ajax->addControllerSearchParams([
            'values' => [$this->reqID],
            'field'  => 'r.req_id'
        ]);

        $ajax->output($table, [
            'ajaxPost' => TRUE,
            'order' => [
                $fieldKeys['req_dtl_id'] => 'DESC'
            ]
        ]);

        new datatables\searcher($table);

        $this->jsVars['urls']['processRequest'] =
            customJSONLink('appJSON', 'processRequest');

    }

    /*
    ****************************************************************************
    */
}
