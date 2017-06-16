<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function adminHistoryController()
    {
        $ajax = new datatables\ajax($this);
        
        $table = new tables\history($this);
        
        $table->getJSFields();
        
        $ajax->output($table);
    }

    /*
    ****************************************************************************
    */

    function method2EmptyController()
    {
    }
    
}