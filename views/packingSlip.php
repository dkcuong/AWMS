<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{
    function displayPackingSlipView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;

    }
    
    /*
    ****************************************************************************
    */

    function method2EmptyView()
    {
        ?>
        <?php    
    }   

}