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
    
    function searchClientEmailsView()
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