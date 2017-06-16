<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function searchUsersView()
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

    function resetPasswordUsersView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;

        if ($this->isDevelop) {?>
            <button id="resetAllPassword">Reset All Passwords</button><br>
            <h4 class="email-content">Email content:</h4>
            <a href="#" class="toggleLink">Display Input</a>
            <br>
            <div class="toggleDiv">
                <textarea id="emailValue" cols="60" rows="10"></textarea>
            </div>
        <?php }
    }

    /*
    ****************************************************************************
    */
}