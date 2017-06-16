<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{
    function listMasterLabelsView()
    {
        echo $this->searcherHTML; 
        $modelName = $this->modelName;
        echo datatables\structure::tableHTML($modelName);
        echo $this->searcherExportButton;        
    }  

}