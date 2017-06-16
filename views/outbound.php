<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{
    function ordersOutboundView()
    {
        $this->ajax->warehouseVendorMultiSelectTableView($this, 'ordersOutbound');
    }

    /*
    ****************************************************************************
    */

}