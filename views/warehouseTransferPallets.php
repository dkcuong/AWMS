<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

     /*
    ****************************************************************************
    */

    function searchWarehouseTransferPalletsView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }

    /*
    ****************************************************************************
    */

}
