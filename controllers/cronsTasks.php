<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function searchCronsTasksController()
    {
        $show = getDefault($this->get['show']);
      
        $orderColumn = 1;
        
        switch ($show) {
            case 'tasks':
                $table = new tables\crons\tasks($this);
                break;
            default: 
                die;
        }
        
        // Export Datatalbe
        $ajax = new datatables\ajax($this);

        $ajax->output($table, [
            'ajaxPost' => TRUE,
            'order' => [$orderColumn => 'asc'],
        ]);                
                
        new datatables\searcher($table);
        
        $editable = new datatables\editable($table);
        
        if (isset($table->customAddRows)) {
            $table->customAddRows();
        } else {
            $editable->canAddRows();
        }
    }

    /*
    ****************************************************************************
    */

}