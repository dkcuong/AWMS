<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function searchMinMaxRangesView()
    { ?>

        <div id="minMaxRange" class="inputBlock">
            <div class="message">
                Add Mezzanine Min Max Range
                <br>
                <?php echo $this->vendorNames; ?>
                Start Location <input type="text" id="startLocation" class="location" 
                                      placeholder="(autocomplete)">
                End Location <input type="text" id="endLocation" class="location" 
                                    placeholder="(autocomplete)">
                <input type="button" id="submitRange" value="Submit"
                       class="inputBlockSubmit">
            </div>
        </div>
        <?php

        $this->ajax->multiSelectTableView($this);
    }
    
    /*
    ****************************************************************************
    */

}