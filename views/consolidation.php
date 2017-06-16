<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function displayConsolidationView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }
    
    /*
    ****************************************************************************
    */

    function waveOneConsolidationView()
    {
        ?>
        <div class="dontPrint">
        
            <span id="selectMessage">Select the client whos inventory you would like 
                to consolidation:<span>

            <select id="consolidateClient">
                <option>Select</option>
                <?php foreach ($this->clientList as $clientID => $name) { ?>
                    <option value="<?php echo $clientID; ?>"><?php echo $name; ?></option>
                <?php } ?>
            </select><br>

            <?php foreach ($this->wavesInfo as $id => $row) { ?>

            <div id="<?php echo $id; ?>" class="message waveMessage consolidateHidden">

                <div class="waveTitle">Wave <?php echo $row['number']; ?> Consolidation:</div>
                <button class="selectAll consolidateHidden">Select All</button>

                <button class="createReport consolidateHidden">Preview Consolidation 
                    Requirements</button>

                <button class="confirmConsolidation consolidateHidden">Confirm Consolidation</button>

                <button class="printUCCs consolidateHidden">Print UCCs</button>

                <button class="printPlates consolidateHidden">Print License Plates</button>
                
                <button class="startWaveTwo consolidateHidden">Continue to Wave Two</button>
            </div>

            <?php } ?>


            <table id="locsSaved" class="consolidateHidden"></table>
        </div>

        <table id="movements"></table>

        <form id="printLabels" method="post" target="_blank"
              action="<?php echo $this->printLabels; ?>"></form>
        
        <form id="printPlatesLabels" method="post" target="_blank"
              action="<?php echo $this->printPlates; ?>"></form>
        
        <?php    
    }    

}