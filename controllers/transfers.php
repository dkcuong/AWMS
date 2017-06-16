<?php

use labels\create;
use inventory\transfers;

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function listTransfersController()
    {
        $table = new tables\transfers($this);
        $vendors = new \tables\vendors($this);
        $this->ajax = new datatables\ajax($this);

        $this->vendors = $vendors->get();

        $this->jsVars['urls']['urlProcessImport'] =
            makeLink('transfers', 'import');

        $this->jsVars['urls']['printPickTicket'] =
            makeLink('transfers', 'display');

        $this->jsVars['urls']['printLabels']
            = makeLink('transfers', 'cartonLabels');

        $fields = array_keys($table->fields);

        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers'] = [
            'id' => $fieldKeys['id'],
            'printLabels' => $fieldKeys['printLabels'],
            'confirmation' => $fieldKeys['confirmation'],
        ];

        $sortColumn = $fieldKeys['createDate'];

        $this->ajax = new \datatables\ajax($this);

        $dtOptions = [
            'ajaxPost' => TRUE,
            'order' => [$sortColumn => 'desc'],
        ];

        $this->ajax->multiSelectTableController([
            'app' => $this,
            'model' => $table,
            'dtOptions' => $dtOptions,
        ]);

        $this->isClient = \access::isClient($this);

        $this->jsVars['listTransfer'] = TRUE;
    }

    /*
    ****************************************************************************
    */

    function cartonLabelsTransfersController()
    {
        $transferID = $this->get['transfer'];

        if (! $transferID) {
            die('No transfer was submitted!');
        }

        create::transferCartonsLabels($this, $transferID);
    }

    /*
    ****************************************************************************
    */

    function displayTransfersController()
    {
        try {

            $transferID = getDefault($this->get['id']);

            if (! $transferID) {
                throw new Exception('Missing ID');
            }

            inventory\transfers::pdfOutput([
                'app' => $this,
                'transferID' => $transferID
            ]);

        } catch (Exception $e) {
            $this->jsVars['messageError'] = $e->getMessage();
        }
    }

    /*
    ****************************************************************************
    */

    function importTransfersController()
    {
        if (isset($this->get['template'])) {
            $this->downloadTemplate();
            die();
        }

        try {

            $toolTransfer = new transfers($this);
            $data = $this->getRequestFromExcel();

            $toolTransfer->checkValidateData($data);

            $this->beginStore();

            $onlineOrders = new tables\onlineOrders($this);

            $quantities = $upcRows = [];

            foreach ($data as $row) {

                $upc = $row['upc'];

                $quantities[$upc]['quantity'] =
                        getDefault($quantities[$upc]['quantity'], 0);

                $quantities[$upc]['quantity'] += $row['pieces'];

                $upcRows[$upc][] = TRUE;
            }

            $info = new tables\onlineOrders\importsInfo();

            $onlineOrders->importOrTransfer([
                'app' => $this,
                'info' => $info,
                'query' => new tables\onlineOrders\mezzanineImportQuery($this),
                'method' => 'transfer',
                'upcRows' => $upcRows,
                'vendorID' => $toolTransfer->getClientID(),
                'upcItems' => $quantities,
                'importData' => $data,
                'manualTransfer' => TRUE,
                'tableOnlineOrder' => $onlineOrders,
            ]);

            $errors = getDefault($onlineOrders->errors);

            if ($errors) {
                echo 'Transfer Import Error Data:';
                dieDump($errors);
            }

            $this->commitStored();

           $transferID = $info->get('transferID');

            $includeJS = ! $toolTransfer->warningMsg ? NULL :
                    'app.alert("Warning!\\n' . $toolTransfer->warningMsg . '")';

            inventory\transfers::pdfOutput([
                'app' => $this,
                'transferID' => $transferID,
                'includeJS' => $includeJS
            ]);

        } catch (Exception $e) {
            $this->jsVars['messageError'] = $e->getMessage();
        }
    }

    /*
    ****************************************************************************
    */

}