<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function searchMinMaxController()
    {        
        $this->jsVars['urls']['getLocationNames'] 
            = customJSONLink('appJSON', 'getLocationNames');

        $this->jsVars['urls']['getAutocompleteUpc'] =
            customJSONLink('appJSON', 'getAutocompleteUpc');

        $this->jsVars['urls']['getAutocompleteSku'] =
            customJSONLink('appJSON', 'getAutocompleteSku');

        $this->jsVars['urls']['submitMinMax'] =
            customJSONLink('appJSON', 'submitMinMax');

        $this->jsVars['urls']['updateMinMax'] =
            customJSONLink('appJSON', 'updateMinMax');

        $this->jsVars['urls']['updateClientMinMax'] =
            customJSONLink('appJSON', 'updateClientMinMax');
        
        $this->includeJS['custom/js/common/locationAutocomplete.js'] = TRUE;

        $vendors = new tables\vendors($this);
        $table = new tables\minMax($this);
        
        $this->importer = new excel\importer($this, $table);

        if (getDefault($_FILES) && $_FILES['file']['error']) {
            die('Error Submitting a File');
        }

        if (isset($this->post['template'])) {

            \locations\minMax::importTemplate($table->fields);

            die;
        }

        $this->fileSubmitted = getDefault($_FILES);

        if ($this->fileSubmitted) {

            $this->importer->uploadPath = \models\directories::getDir('uploads',
                    'minMaxImportsFiles');

            $this->importer->insertFile();
        }

        $this->vendorNames = $vendors->getVendorDropdown();
        $this->minMaxInputs = $table->getMinMaxInputs();

        $dtOptions = [
            'bFilter' => FALSE,
            'order' => [0 => 'asc'],
        ];

        $this->modelName = getClass($table);
        // Export Datatalbe
        $this->ajax = new \datatables\ajax($this);
                
        $this->ajax->multiSelectTableController([
            'app' => $this,
            'model' => $table, 
            'dtOptions' => $dtOptions, 
        ]);
    }

    /*
    ****************************************************************************
    */
        
}