<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function printPrintPackingSlipController()
    {
        $printParam = $this->getPrintParam($this->get['query']);
       
        $this->getPackingSlip($printParam);
        
        $this->displayPackingSlip();
    }

    /*
    ****************************************************************************
    */

}