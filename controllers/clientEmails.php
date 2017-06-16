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
    
    function searchClientEmailsController()
    {
        $this->jsVars['urls']['bolCron'] = customJSONLink('appJSON', 'bolCron');
                
        $table = new tables\clientEmails($this);
        $ajax = new datatables\ajax($this);   
        
        $ajax->output($table, [
            'ajaxPost' => TRUE
        ]);                

        new datatables\searcher($table);
        $editable = new datatables\editable($table);
        
        $editable->canAddRows();    
    }

    /*
    ****************************************************************************
    */
}
