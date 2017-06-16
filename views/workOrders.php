<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function addLabelsWorkOrdersView()
    {
        echo $this->labelMakerHTML;
        echo $this->datatablesStructureHTML;
    }

    /*
    ****************************************************************************
    */

    function checkinoutWorkOrdersView()
    {
        echo \common\workOrders::getView($this, $this->isCheckOut);
    }

    /*
    ****************************************************************************
    */

    function searchWorkOrdersView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }

 /*
 ****************************************************************************
 */

}