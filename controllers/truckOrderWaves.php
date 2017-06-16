<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

   function downloadTemplateTruckOrderWavesController()
    {
        $truckOrderWaves = new tables\truckOrderWaves($this);

        $template = array_column($truckOrderWaves->fields, 'display');

        csv\export::exportArray($template, 'truck_order_template');
    }

    /*
    ****************************************************************************
    */

}