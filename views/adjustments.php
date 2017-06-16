<?php
class view extends controller
{
    
    /*
    ****************************************************************************
    */    
    
    function listAdjustmentsView()
    {
        echo $this->searcherHTML; 

        if ($this->listInventory) { ?>
            <button id="toggleAll" data-status="off">Toggle All</button>
            <button id="adjustButton">Adjust</button>
            <br>
            <br><?php
        }
        
        echo datatables\structure::tableHTML($this->modelName);
        echo $this->searcherExportButton;  

        if ($this->listInventory) { ?> 
            <div id="adjustForm" title="Adjust Inventory">
                <div id="updateStatus">
                    Status <select id="statusDD"></select>
                </div>
                <div id="locationBox">
                <div id="resetLocation">
                    <input name="locationType" type="radio" value="reset" checked>
                    Revert Location(s)
                </div>
                <div id="updateLocation">
                    <input name="locationType" type="radio" value="update">
                    Select Location <select id="locationDD"></select>
                </div>
                </div>
                <br>
            </div><?php
        }
    }
    
    /*
    ****************************************************************************
    */    
}
