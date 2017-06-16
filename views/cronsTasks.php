<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function searchCronsTasksView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
        
        echo $this->searcherAddRowButton;
        echo $this->searcherAddRowFormHTML;
    }

    /*
    ****************************************************************************
    */

}