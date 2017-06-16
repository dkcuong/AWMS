<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function searchMinMaxView()
    { ?>

        <div id="addMinMax" class="inputBlock">
            <div class="message">
                <a href="#" class="toggleLink">Hide</a> Add Mezzanine Min Max Location
                <br>
                <div class="toggleDiv">
                <?php echo $this->vendorNames; ?>
                <select id="category">
                    <option>UPC</option>
                    <option>SKU</option>
                </select>
                <input type="text" id="upc" placeholder="(autocomplete)">
                <input type="text" id="sku" placeholder="(autocomplete)" hidden>
                <span class="skuDescription" disabled hidden>Color</span>
                <input type="text" class="skuDescription" id="color" disabled hidden>
                <span class="skuDescription" disabled hidden>Size</span>
                <input type="text" class="skuDescription" id="size" disabled hidden>
                <br>
                Location <input type="text" id="location" class="location"
                                placeholder="(autocomplete)">
                <?php echo $this->minMaxInputs; ?>
                <input type="button" id="submitMinMax" value="Submit"
                       class="inputBlockSubmit">
                </div>
            </div>
        </div>

        <div id="clientMinMax" class="inputBlock">
            <div class="message">
                <a href="#" class="toggleLink">Hide</a> Mezzanine Min Max by Client Name
                <br>
                <div class="toggleDiv">
                <?php echo $this->vendorNames; ?>
                <?php echo $this->minMaxInputs; ?>
                <input type="button" id="submitClientMinMax" value="Submit"
                       class="inputBlockSubmit">
                </div>
            </div>
        </div>

        <div style="clear: right;">
        </div>
        <?php

        \locations\minMax::importMinMaxHTML($this, $this->importer);

        $button = \locations\minMax::importTemplateHTML();
        
        $this->ajax->multiSelectTableView($this, NULL, $button);
    }
    
    /*
    ****************************************************************************
    */

}