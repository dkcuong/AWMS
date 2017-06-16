<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function searchLocationsView()
    {
        if (getDefault($this->get['display']) == 'locationinfo') {
            \locations\minMax::importMinMaxHTML($this, $this->importer);
        }
        
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;

        if ($this->addRows) {
            echo $this->searcherAddRowButton;
            echo $this->searcherAddRowFormHTML;
        } 
        
        if (getDefault($this->get['display']) == 'locationinfo') {
            echo \locations\minMax::importTemplateHTML();
        }
    }
    
    /*
    ****************************************************************************
    */
    
    function adminSearchLocationsView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }
    
    /*
    ****************************************************************************
    */

    function searchLocationsUtilizationLocationsView()
    {
        $this->ajax->warehouseVendorMultiSelectTableView($this, 'searchLocationsUtilizationLocations');
    }

    /*
    ****************************************************************************
    */

}