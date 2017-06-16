<?php

class controller extends template
{
    /*
    ****************************************************************************
    */    
    
    function listAdjustmentsController()
    { 
        $this->listInventory = getDefault($this->get['display']) == 'inventory';
        
        $table = $this->listInventory ? 
                new tables\inventory\adjustments\inventory($this) : 
                new tables\inventory\adjustments\logs($this);
        
        $fieldKeys = array_keys($table->fields);

        $this->jsVars['fieldColumns'] = array_flip($fieldKeys);
        
        $sortField = $this->listInventory ? 'logTime' : 'dateAdjusted';
        
        $sortColumn = array_search($sortField, $fieldKeys);

        $this->modelName = getClass($table);

        $ajax = new datatables\ajax($this);   

        $ajax->output($table, [
            'order' => [
                $sortColumn => 'desc'
            ],
            'bFilter' => FALSE,
        ]);  

        new datatables\searcher($table);

        $this->jsVars['urls']['getAdjustInventory']
            = makeLink('appJSON', 'getAdjustInventory');

        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $this->jsVars['urls']['adjustInventory']
            = makeLink('appJSON', 'adjustInventory');

        $this->jsVars['modelName'] = getClass($table);
    }
    
    /*
    ****************************************************************************
    */      
}