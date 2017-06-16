<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use orders\lading;

class controller extends template
{

    function displayLadingController()
    {
        $orderNumbers = explode(',', $this->post['orders']);

        lading::displayLadings($this, $orderNumbers);
    }

    /*
    ****************************************************************************
    */    
}