<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function reportOpenOrdersView()
    {
        $this->ajax->warehouseVendorMultiSelectTableView($this, 'openOrders');
    }

    /*
    ****************************************************************************
    */

}
