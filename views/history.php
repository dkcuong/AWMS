<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function adminHistoryView()
    {
        echo $this->datatablesStructureHTML;
    }
    
    /*
    ****************************************************************************
    */

    function method2EmptyView()
    {
        ?>
        <?php    
    }    

}