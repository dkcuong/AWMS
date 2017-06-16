<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use common\seldatContainers;

class controller extends template
{
    function scanSeldatContainersController()
    {

        $this->setUrlScanContainer();
        $this->setIncludeJsScanContainer();

        $upcs = new tables\inventory\upcs($this);
        $cartons = new tables\inventory\cartons($this);
        $containers = new tables\inventory\containers($this);
        $ajax = new datatables\ajax($this);

        // Hacky code to preserve post values for autosave until Vadzim fixes
        // this page
        $savePostValues = $this->post;
        $ajax->output($upcs);
        $this->post = $savePostValues;

        $this->containerValues = [];

        $this->modify = isset($this->get['modify']);
        $this->tableCells = $cells = seldatContainers::$tableCells;

        // remove columns without inputs from a set of inputs
        unset($cells['rowNo']);
        unset($cells['newUPC']);

        $tableInputClasses = array_keys($cells);

        $this->jsVars['modify'] = $this->modify;
        $this->jsVars['tableInputClasses'] = array_values($tableInputClasses);
        $this->jsVars['tableCells'] = $this->tableCells;
        $this->jsVars['measurements'] = $cartons->measurements;
        $this->jsVars['runAutosave'] = TRUE;

        // For adding styles to containers
        $this->editContainer = $this->jsVars['editContainer'] =
                getDefault($this->get['container']);

        // Load an auto-save container if available
        $autoSave = getDefault($_SESSION['autoSaveContainer'], []);

        $useSessionRows = ! $this->post;

        // Don't use auto save on modify or add batches to containers pages
        $autoSaveValues = $this->editContainer || ! is_array($autoSave) ? [] :
            $autoSave;

        foreach ($autoSaveValues as $name => $value) {

            $postValue = getDefault($this->post[$name]);

            if ($value && ! $postValue) {
                $this->post[$name] = $value;
            }
        }

        $this->modifyRows = FALSE;
        $this->modifyBatches = $this->recNum = NULL;

        if ($this->editContainer) {

            $this->containerValues =
                    $containers->getReceivingNumberData($this->editContainer);

            foreach ($this->containerValues as $key => $values) {
                $this->post[$key] = $values;
            }

            $this->modify ? $this->getDBValues() :
                $this->modelGetContainerInfo($this->editContainer);
        }

        $this->setValueDefaultScanContainer($useSessionRows);

        $this->jsVars['urls']['checkReceiving']
            = customJSONLink('appJSON', 'checkReceiving');
    }

    /*
    ****************************************************************************
    */

    function barcodeSeldatContainersController()
    {
        $cartons = new tables\inventory\cartons($this);

        $container = getDefault($this->get['container']);

        $containerCartons = $cartons->getContainerCartons($container);

        if (! $containerCartons) { ?>
            <div class="failedMessage">
                No active cartons have been selected
            </div>
        <?php
            return;
        }

        labels\create::forCartons([
            'db' => $this,
            'labels' => $containerCartons,
        ]);
    }

    /*
    ****************************************************************************
    */

    function importSeldatContainersController()
    {
        $template = getDefault($this->post['template']);

        $this->jsVars['runAutosave'] = FALSE;

        if ($template) {
            $this->downloadTemplate();
        }

        $this->setUrlScanContainer();
        $this->setIncludeJsScanContainer();
        $this->setValueDefaultImportContainer();

        $this->fileSubmitted =
                getDefault($_FILES) && ! $_FILES['file']['error'];

        $importResult = import\inventoryBatch::processUploadFile($this);
        if ($importResult === FALSE) {
            $this->errorFile =
                'Please check file uploaded, the format is not correct';
        }
        import\inventoryBatch::processDownloadBadUpcs($this);

        $cartons = new tables\inventory\cartons($this);
        $vendors = new tables\vendors($this);

        $this->jsVars['measurements'] = $cartons->measurements;
        $this->allVendors = $vendors->get();

        $this->setValueDefaultScanContainer();
    }

    /*
    ****************************************************************************
    */

    private function downloadTemplate()
    {
        $exporter = new \excel\exporter($this);
            // exel output then exist
        $exporter->ArrayToExcel([
            'data' => [
                ['501-TNM', 'SL', 'NA', 'TITANIUM',
                    '20', '10', '002-D-L2-01']
            ],
            'fileName' => 'templateImport',
            'fieldKeys' => [
                ['title' => 'SKU'],
                ['title' => 'SUFFIX'],
                ['title' => 'SIZE'],
                ['title' => 'COLOR'],
                ['title' => 'UOM'],
                ['title' => 'CTNS'],
                ['title' => 'LOCATION']
            ],
        ]);
    }

}
