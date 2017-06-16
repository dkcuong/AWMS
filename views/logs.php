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
    
    function componentsLogsView()
    {
        if ($this->multiSelect) {
            $this->ajax->multiSelectTableView($this, 'vendorID');
        } else {
            echo $this->searcherHTML;
            echo $this->datatablesStructureHTML;
            echo $this->searcherExportButton;            
        }
    }
    
    /*
    ****************************************************************************
    */


}