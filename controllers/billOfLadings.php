<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function listAddBillOfLadingsController()
    {
        $labels = new tables\billOfLadingLabels($this);

        $ajax = new datatables\ajax($this);

        $ajax->output($labels, [
            'ajaxPost' => TRUE,
        ]);

        $users = new tables\users($this);

        new common\labelMaker($this, $labels, $users);

    }

    /*
    ****************************************************************************
    */

    function addShipmentBillOfLadingsController()
    {
        $updateOrders = [];
        $this->includeJS['custom/js/common/scrollToElement.js'] = TRUE;
        $this->jsVars['urls']['displayBOLabel']
        = makeLink('billOfLadings', 'displayBOLabel', 'barcode');

        $this->jsVars['urls']['getOrderInfo']
            = customJSONLink('appJSON', 'getOrderInfo');
        $this->jsVars['urls']['getShipFromInfo']
            = customJSONLink('appJSON', 'getShipFromInfo');

        $this->jsVars['urls']['displayScanNumber']
            = makeLink('billOfLadings', 'displayBOLLabel', 'barcode');

        $this->jsVars['urls']['getAutocompleteOrderNumber'] =
            customJSONLink('appJSON', 'getAutocompleteOrderNumber');
        $this->jsVars['urls']['getNewLabel']
            = customJSONLink('appJSON', 'getNewLabel');

        $this->jsVars['restoreCanceledOrder'] = $this->restoreCanceledOrder;

        $orders = new tables\orders($this);
        $vendors = new tables\vendors($this);

        $this->checkType = isset($this->get['type']) ? 'Check-Out' : 'Check-In';

        $this->duplicateNumber = $this->missingInputOrders
            = $this->missingMandatoryValues
            = $vendorsArray = $updateOrders = [];

        $this->vendor = $vendors->get();
        $this->commodity = $orders->selectCommodity();
        $this->locationtable = $orders->selectShipFrom();

        $carrierNNote = array_keys($this->carrierAndNote);
        $combineFields = array_merge($carrierNNote, $this->carrierAndNote,
            $this->checkBoxes, $this->dbFields, $this->radio,
            $this->freightchargetermby);

        $this->inputFields = array_unique($combineFields);
        if (isset($this->post['bollabel']) && ! isset($this->post['scanOrderNumbers'])) {
            $this->missingInputOrders = 'Not Found ScanOrderNumber';
        }
        if (isset($this->post['bollabel']) && ! $this->missingInputOrders) {

            $isSubmit = $this->post['buttonFlag'] == 'Submit';
            $bolLabel = $this->post['bollabel'];

            $repeat = count($bolLabel);

            //assign values to $this->inputValues
            for ($page = 0; $page < $repeat; $page++) {
                $this->getValues($page);
            }
            $isSubmit && $this->formSubmit($orders, $bolLabel, $updateOrders);
        } elseif ($this->dbValues) {
            foreach ($this->inputFields as $field) {
                $this->inputValues[$field][0] = NULL;
            }
        } else {
            foreach ($this->inputFields as $field) {
                $this->inputValues[$field][0] = NULL;
            }
        }
        if ($this->missingMandatoryValues || $this->integerOnly) {
            $_SESSION['scanOrderNumbers'] = $this->post['scanOrderNumbers'];
        }
        if (isset($_SESSION['scanOrderNumbers'])) {

            $orderNumbers['orderLists'] = $_SESSION['scanOrderNumbers'];

            $this->orderLists = $orders->getOrderInfoResults($orderNumbers);

            unset($_SESSION['scanOrderNumbers']);
        }

        $this->duplicate = 0;

        $this->jsVars['isAddShipment'] = TRUE;
    }

    /*
    ****************************************************************************
    */

    function displayBOLLabelBillOfLadingsController()
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

    function searchBillOfLadingsController()
    {
        $table = new tables\billOfLadings($this);

        $this->isEdit = isset($this->get['editable']);

        $this->jsVars['urls']['generateManualBOL'] =
            customJSONLink('appJSON', 'generateManualBOL');

        // Export Datatable
        $ajax = new datatables\ajax($this);

        $ajax->output($table, [
            'ajaxPost' => TRUE
        ]);

        new datatables\searcher($table);

        $editable = new datatables\editable($table);
    }

    /*
    ****************************************************************************
    */

    function displayBOLabelBillOfLadingsController()
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

    function displayBillOfLadingsController()
    {
        $getTerm = getDefault($this->get['term']);

        $term = getDefault($this->post['term'], $getTerm);

        $getSearch = getDefault($this->get['search']);

        $search = getDefault($this->post['search'], $getSearch);

        if (! $search || ! $term) {
            return FALSE;
        }
        $billOfLadings = new labels\billOfLadings();

        $billOfLadings->addBillOfLadingLabels([
            'db' => $this,
            'term' => $term,
            'search' => $search
        ]);
    }
}