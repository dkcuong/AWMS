<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function modifyUpdatesListView()
    {
        echo $this->searcherHTML;

        if ($this->get['type'] == 'listFeature') { ?>
            <div id="filterFeature">
                <?php echo $this->multiSelectTableStarts['versionID'];
                echo $this->multiSelectTableEnd; ?>
            </div>
        <?php }

        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;

        if ($this->get['type'] == 'listFeature') { ?>
            <button id="manageVersion" onclick="redirectToListFeature();">
                Manage Versions</button>
        <?php }
        elseif ($this->get['type'] == 'releaseVersion') { ?>
            <button id="listFeature" onclick="redirectToVersion();">
                Manage Features</button>
        <?php }
        if ($this->addRows) {
            echo $this->searcherAddRowButton;
            echo $this->searcherAddRowFormHTML;
        }
    }

    /*
    ****************************************************************************
    */

}