<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function searchWarehouseTransfersController()
    {
        $table = new tables\warehouseTransfers\warehouseTransfers($this);
        $ajax = new datatables\ajax($this);

        $fields = array_keys($table->fields);

        $fieldKeys = array_flip($fields);

        $sortColumn = $fieldKeys['transferDate'];

        $ajax->output($table, [
            'order' => [$sortColumn => 'desc'],
            'bFilter' => FALSE,
        ]);

        new datatables\searcher($table);

        $editable = new datatables\editable($table);

        $editable->canAddRows();
    }

    /*
    ****************************************************************************
    */

}