<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{
    function searchUpcsView()
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

    function editCategoriesUpcsView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;

        if ($this->addRows) {
            echo $this->searcherAddRowButton;
            echo $this->searcherAddRowFormHTML;
        }
    }

    /*
    ****************************************************************************
    */
    function editUpcsView()
    {
        echo $this->searcherHTML;?>
        <div class="message">
            <form method="post" onsubmit="updateUpcInfo(this); return false;">
                <label for="Adjust">Input an adjust UPC</label>
                <input id="upcAdjust" type="text" placeholder="(autocomplete)"
                       style="width:45%"/>
                <input name="submit" type="submit" value="Adjust" />
            </form>
        </div>

        <?php
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }

    /*
    ****************************************************************************
    */
}
