<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function createWavePicksController()
    {
        $table = new tables\orderBatches($this);
        $ajax = new datatables\ajax($this);

        $fields = array_keys($table->fields);

        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers']['batchOrder'] = $fieldKeys['batchOrder'];

        $sortColumn = $fieldKeys['scan_seldat_order_number'];

        $ajax->output($table, [
            'order' => [$sortColumn => 'desc'],
            'bFilter' => FALSE,
        ]);

        new datatables\searcher($table);

        $this->jsVars['urls']['checkMezzanineStorage'] = customJsonLink(
            'appJSON', 'checkMezzanineStorage'
        );
    }

    /*
    ****************************************************************************
    */

    function listWavePicksController()
    {
        $table = new tables\wavePicks($this);
        $ajax = new datatables\ajax($this);

        $fields = array_keys($table->fields);

        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers'] = [
            'orderBatch' => $fieldKeys['order_batch'],
            'scanOrderNumber' => $fieldKeys['scanOrderNumber'],
        ];

        $this->jsVars['urls']['display'] = makeLink('wavePicks', 'display');

        $sortColumn = $fieldKeys['dateCreated'];

        $ajax->output($table, [
            'ajaxPost' => TRUE,
            'order' => [$sortColumn => 'desc'],
        ]);

        new datatables\searcher($table);

        $editable = new datatables\editable($table);

        $editable->canAddRows();
    }

    /*
    ****************************************************************************
    */

    function displayWavePicksController()
    {
        $this->jsVars['urls']['displayUPC']
            = makeLink('wavePicks', 'displayUPC', 'barcode');

        $order = getDefault($this->get['order'], NULL);
        $batch = getDefault($this->get['batch'], NULL);

        $wavePicks = new inventory\wavePicks($this);

        $wavePicks->printType = getDefault($this->get['printType'], 'wavePick');
        $wavePicks->printByOrder = getDefault($this->get['printByOrder']);
        $wavePicks->wavePickType = getDefault($this->get['wavePickType'], 'original');

        $wavePicks->createWavePickPDF($order, $batch);
    }

    /*
    ****************************************************************************
    */

    function displayUPCWavePicksController()
    {
        $barcode = getDefault($this->get['barcode']);

        if (! $barcode) {
            return;
        }

        barcodephp\create::display([
            'code' => 'BCGcode128',
            'filetype' => 'JPEG',
            'dpi' => 72,
            'scale' => 1,
            'rotation' => 0,
            'fontFamily' => 'Arial.ttf',
            'fontSize' => 12,
            'text' => $barcode,
            'noText' => TRUE,
        ]);
    }

    /*
    ****************************************************************************
    */
}