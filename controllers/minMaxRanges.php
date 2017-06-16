<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function searchMinMaxRangesController()
    {
        $this->jsVars['urls']['getLocationNames'] 
            = customJSONLink('appJSON', 'getLocationNames');

        $this->jsVars['urls']['submitMinMaxRange'] =
            customJSONLink('appJSON', 'submitMinMaxRange');

        $this->jsVars['urls']['updateMinMaxRange'] =
            customJSONLink('appJSON', 'updateMinMaxRange');

        $this->includeJS['custom/js/common/locationAutocomplete.js'] = TRUE;
        $this->includeCSS['custom/css/includes/minMax.css'] = TRUE;
        
        $vendors = new tables\vendors($this);
        $table = new tables\minMaxRanges($this);

        $this->vendorNames = $vendors->getVendorDropdown();

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