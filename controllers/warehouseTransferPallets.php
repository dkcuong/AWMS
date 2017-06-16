<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function searchWarehouseTransferPalletsController()
    {
        $table = new tables\warehouseTransfers\warehouseTransferPallets($this);

        $ajax = new datatables\ajax($this);

        $ajax->output($table, [
            'order' => [0 => 'desc']
        ]);

        new datatables\searcher($table);
    }

    /*
    ****************************************************************************
    */

}