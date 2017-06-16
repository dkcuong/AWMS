<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function displayPackingSlipController()
    {
        
        $ajax = new datatables\ajax($this);
        $table = new tables\packingSlip\packingSlip($this);

        $fields = array_keys($table->fields);

        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers'] = [
            'batch' => $fieldKeys['batch'],
            'order' => $fieldKeys['SCAN_SELDAT_ORDER_NUMBER'],
        ];
                
        $this->jsVars['urls']['print'] = makeLink('printPackingSlip', 'print');

        $dtStructure = [
            'order' => ['batch' => 'desc'],
            'bFilter' => FALSE,
        ];

        $ajax->output($table, $dtStructure);    

        new datatables\searcher($table);
    }

    /*
    ****************************************************************************
    */

    function method2EmptyController()
    {
    }
}