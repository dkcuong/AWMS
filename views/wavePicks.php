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
    
    function createWavePicksView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton; 
    }
    
    /*
    ****************************************************************************
    */

    function listWavePicksView()
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
