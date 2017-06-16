<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function listAddOrderLabelsView()
    {
        echo $this->labelMakerHTML;
        echo $this->datatablesStructureHTML;
    }
    
    /*
    ****************************************************************************
    */

    function method2EmptyView()
    {
        ?>
        <?php    
    }

    /*
    ****************************************************************************
    */

    function printUCCOrderLabelsView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
    }


}