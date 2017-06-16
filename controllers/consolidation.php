<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function displayConsolidationController()
    {
        
        $ajax = new datatables\ajax($this);
        
        $table = new tables\Consolidation($this);

        $dtStructure = [
            'order' => ['vendorName' => 'desc'],
            'bFilter' => FALSE,
        ];

        $ajax->output($table, $dtStructure);

        new datatables\searcher($table);

        
        
    }

    /*
    ****************************************************************************
    */

    function waveOneConsolidationController()
    {
        $clients = new tables\vendors($this);
        
        $vendorFullNames = $clients->fields['fullVendorName']['select'];
        $this->clientList = $clients->getDropDown($vendorFullNames);
        
        $this->printLabels = makeLink('inventory', 'search', 'cartonLabels');

        $this->printPlates = makeLink('plates', 'display');
        
        $this->jsVars['url']['clientConsolidate'] = 
            customJSONLink('appJSON', 'clientConsolidate');
        $this->jsVars['urls']['getUPCMovements'] = 
            customJSONLink('appJSON', 'getUPCMovements');
        $this->jsVars['urls']['consolidationMove'] = 
            customJSONLink('appJSON', 'consolidationMove');
    }
    
}