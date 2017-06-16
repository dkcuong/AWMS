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

    function recordTallySheetsReceivingController()
    {
        $this->palletRows = $this->jsVars['palletRows'] = 20;

        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $this->jsVars['urls']['getContainerInfoForRC']
            = customJSONLink('appJSON', 'getContainerInfoForRC');
        $this->jsVars['urls']['getContainerNames']
            = customJSONLink('appJSON', 'getContainerNames');
        $this->jsVars['urls']['getLocationNames']
            = customJSONLink('appJSON', 'getLocationNames');
        $this->jsVars['urls']['readyToComplete']
            = customJSONLink('appJSON', 'readyToComplete');
        $this->jsVars['urls']['updateRCLogPrint']
            = customJSONLink('appJSON', 'updateRCLogPrint');
        $this->jsVars['urls']['updateRCLabel']
            = customJSONLink('appJSON', 'updateRCLabel');
        $this->jsVars['urls']['completeRCLog']
            = customJSONLink('appJSON', 'completeRCLog');

        $this->jsVars['urls']['getLabor']
            = customJSONLink('appJSON', 'getLabor');
        $this->jsVars['urls']['updateLabor']
            = customJSONLink('appJSON', 'updateLabor');

        $this->jsVars['urls']['updateBatchDimension']
            = customJSONLink('appJSON', 'updateBatchDimension');

        $this->jsVars['urls']['checkLocationCycleCount']
            = customJSONLink('appJSON', 'checkLocationCycleCount');

        $this->jsVars['urls']['labelReceiving']
            = makeLink('receiving', 'label', ['recNum' => '']);
        $this->jsVars['urls']['printPlates']
            = makeLink('plates', 'display', [
                'search' => 'recNum',
                'term' => '',
            ]);

        $this->printLabelsLink = makeLink('inventory', 'search', 'cartonLabels');

        $this->jsVars['urls']['checkRCLogLocations']
            = customJSONLink('appJSON', 'checkRCLogLocations');

        $this->jsVars['uccLabelDir'] =
            models\directories::getDir('uploads', 'uccLabels');

        $this->jsVars['urls']['getPrintUccLabelsFile']
            = customJSONLink('appJSON', 'getPrintUccLabelsFile');

        $this->container = $this->jsVars['container'] =
                getDefault($this->post['name'], NULL);
    }

    /*
    ****************************************************************************
    */

    function labelReceivingController()
    {
        labels\rcLabel::get([
            'inventory' => new tables\inventory\cartons($this)
        ]);

    }

    /*
    ****************************************************************************
    */

    function createReceivingController()
    {
        $vendors = new tables\vendors($this);
        $this->vendors = $vendors->get();

        $this->errors = NULL;
        $this->success = FALSE;

        $this->jsVars['urls']['createNewReceiving'] =
            customJSONLink('appJSON', 'createReceiving');
        $this->jsVars['urls']['display'] = makeLink('receiving', 'display');

        $this->vendorID = getDefault($this->post['vendorID'], NULL);
        $this->setAutoDate = getDefault($this->post['setAutoDate'],
            date('m/d/Y h:i:s a', time()));

        $this->userID = access::getUserID();

        $isCreate = getDefault($this->post['create']);

        if ($isCreate) {

            $receiving = new \tables\receiving($this);
            $vendor = new \tables\vendors($this);

            $ref = getDefault($this->post['ref']);
            $note = getDefault($this->post['note'], '');
            $userID = getDefault($this->post['userID']);
            $vendorID = getDefault($this->post['vendorID']);

            if (! $vendorID) {
                return $this->errors[] = 'Please choose client!';
            }

            $warehouseID = $vendor->getVendorWarehouse($vendorID);
            $this->receivingID = $receiving->getNextID('receivings');

            if (! getDefault($_FILES['files']['error'][0])) {
                $result = $receiving->uploadAttachFiles($this);

                if ($result) {
                    return $this->errors = $result;
                }
            }

            $params = [
                'warehouseID' => $warehouseID,
                'vendorID' => $vendorID,
                'ref' => $ref,
                'note' => $note,
                'userID' => $userID
            ];

            //  Check data input
            $result = $receiving->checkDataInput($params);

            if ($result) {
                return $this->errors[] = $result;
            }

            $receiving->addNewReceiving($params);

            $this->success = TRUE;
        }
    }

    /*
    ****************************************************************************
    */

    function displayReceivingController()
    {
        $table = new \tables\receiving($this);

        $ajax = new datatables\ajax($this);

        $fields = array_keys($table->fields);

        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers'] = [
            'receiving' => $fieldKeys['id'],
            'statuses' => $fieldKeys['statuses'],
            'action' => $fieldKeys['action'],
        ];

        $sortColumn = $fieldKeys['created_at'];

        $ajax->output($table, [
            'ajaxPost' => TRUE,
            'order' => [$sortColumn => 'desc'],
        ]);

        new datatables\searcher($table);

        $this->jsVars['urls']['createReceiving'] =
            makeLink('receiving', 'create');

        $this->jsVars['urls']['updateReceiving'] =
            makeLink('receiving', 'update');

        $this->jsVars['urls']['deleteReceiving'] =
            customJSONLink('appJSON', 'deleteReceiving');

    }

    /*
    ****************************************************************************
    */

    function updateReceivingController()
    {
        $this->jsVars['urls']['checkRCLogContainer'] =
            customJSONLink('appJSON', 'checkRCLogContainer');

        $this->jsVars['urls']['updateReceivingStatus'] =
            customJSONLink('appJSON', 'updateReceivingStatus');

        $this->jsVars['urls']['confirmUpdateReceivingStatus'] =
            customJSONLink('appJSON', 'confirmUpdateReceivingStatus');

        $this->jsVars['urls']['display'] =
            makeLink('receiving', 'display');

        $receiving = new \tables\receiving($this);
        $statuses = new \tables\statuses\receiving($this);

        $this->jsVars['recNum'] = $this->receivingID =
            getDefault($this->get['receivingID'], NULL);
        $this->jsVars['cancel'] =
            $statuses->getStatusID($receiving::CANCEL_STATUS);

        $this->isView = getDefault($this->get['view']);

        $this->isView ? $this->receivingID = $this->isView : NULL;

        $this->receivingData = $receiving->getReceivingData($this->receivingID);

        $currentStatusID = $this->receivingData['statusID'];

        $this->jsVars['statusArray'] = $this->statuses =
            $receiving->getStatuses($currentStatusID);

        $this->containers = $receiving->checkReceivingContainer($this->receivingID);

        $this->fileArray = $receiving->getFileList($this->receivingID);

        $fileID = getDefault($this->get['viewFile']);

        if ($fileID) {
            $path = \models\directories::getDir('uploads', 'receiving');
            $fileName = common\receiving::getFileName($this, $fileID);
            common\receiving::downloadFile($path . '/' . $fileName, $fileName,
                'text/plain');
        }

        if ($this->containers) {

            $ajax = new datatables\ajax($this);
            $table = new \tables\receivingContainers($this);

            $this->includeJS['js/datatables/editables.js'] = TRUE;

            $ajax->addControllerSearchParams([
                'values' => [$this->receivingID],
                'field'  => 'r.id'
            ]);

            $ajax->output($table, [
                'ajaxPost' => TRUE
            ]);

            new datatables\searcher($table);
        }
    }

    /*
    ****************************************************************************
    */

    function generateReceivingController()
    {
        $this->notification = [];
        $this->data = [];
        $containers = new \tables\inventory\containers($this);
        $receiving = new \tables\receiving($this);

        $isSubmit = getDefault($this->post['submit']);

        if ($isSubmit) {

            // Get all container haven't Receiving
            $containersInfo = $containers->getInfoContainerMissingReceiving();
            if (! $containersInfo) {
                $this->notification['warning'] = 'Not found container missing
            receiving.';
                return;
            }
            // Prepare receivingID for each container
            $receiving->prepareReceivingID($containersInfo);

            // Create receiving for each container
            $results = $receiving->createMissingContainerReceiving($containersInfo);

            if ($results) {
                $this->notification['success'] = 'Created ' . $results
                    . ' receiving successful.';
                $this->data = $containersInfo;
            }
        }
    }

    /*
    ****************************************************************************
    */

    function actualReceivingController()
    {
        $this->ajax = new \datatables\ajax($this);

        $this->ajax->warehouseVendorMultiSelectTableController([
            'app' => $this,
            'model' => new \tables\receiving\actual($this),
            'dtOptions' => ['bFilter' => FALSE],
            'setMysqlFilter' => $this->receivingReportMysqlFilter(),
        ]);
        $this->jsVars['multiselect'] = TRUE;
    }

    /*
    ****************************************************************************
    */

    function inspectionReportReceivingController()
    {
        $this->ajax = new \datatables\ajax($this);

        $this->ajax->warehouseVendorMultiSelectTableController([
            'app' => $this,
            'model' => new \tables\receiving\inspectionReports($this),
            'dtOptions' => ['bFilter' => FALSE],
            'setMysqlFilter' => $this->receivingReportMysqlFilter(),
        ]);

        $this->jsVars['multiselect'] = TRUE;
    }

    /*
    ****************************************************************************
    */

    function containerReportReceivingController()
    {
        $model = new \tables\receiving\containerReports($this);
        $this->ajax = new \datatables\ajax($this);

        $fields = array_keys($model->fields);

        $this->jsVars['fieldKeys'] = $fieldKeys = array_flip($fields);

        foreach ($model->fields as $key => $values) {

            $background = getDefault($values['backgroundColor']);

            $this->jsVars['columnColors'][$background][] = $fieldKeys[$key];
        }

        $this->jsVars['backgroundColors'] = $model->backgroundColors;

        $this->ajax->warehouseVendorMultiSelectTableController([
            'app' => $this,
            'model' => $model,
            'dtOptions' => ['bFilter' => FALSE],
            'setMysqlFilter' => $this->receivingReportMysqlFilter(),
        ]);

        $titles = array_column($model->fields, 'display');

        $this->jsVars['titles'] = array_flip($titles);
        $this->jsVars['multiselect'] = TRUE;

        $this->includeJS['custom/js/common/multiSelectFilter.js'] = TRUE;
        $this->includeJS['custom/js/common/datatableColoring.js'] = TRUE;
    }

    /*
    ****************************************************************************
    */

}
