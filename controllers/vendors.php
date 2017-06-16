<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function searchVendorsController()
    {
        $table = new tables\vendors($this);

        // Export Datatable
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