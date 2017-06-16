<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use common\invoicing;
use tables\invoices;
use \common\pdf;

class controller extends template
{

    function listInvoicesController()
    {
        common\vendor::createTable($this);

        $statuses = new tables\statuses\invoice($this);
        $vendors = new tables\vendors($this);

        $vendorSelect = 'CONCAT(w.shortName, "_", vendorName)';

        $this->jsVars['custList'] = $this->billableCusts($vendors);

        $firstDay = strtotime('first day of previous month');
        $this->jsVars['fromDate'] = date('Y-m-d', $firstDay);

        $this->jsVars['customer'] = makeLink('costs', 'clients');

        $lastDay = strtotime('last day of previous month');
        $this->jsVars['toDate'] = date('Y-m-d', $lastDay);

        $this->vendorsHTML = $vendors->getDropdown($vendorSelect);
        $this->statusesHTML = $statuses->getDropdown('displayName');

        $table = new invoices\listInvoice($this);

        $this->modelName = getClass($table);

        // Export Datatalbe
        $ajax = new datatables\ajax($this);

        $ajax->output($table, [
            'ajaxPost' => TRUE,
            'order' => [0 => 'desc'],
        ]);

        new datatables\searcher($table);
        new datatables\editable($table);

        $this->jsVars['deleteColumnNo'] = $this->jsVars['ctcIDColumnNo'] = 0;
        $this->jsVars['defaultColumnNo'] = 1;
        $this->jsVars['invoiceColumnNo'] = 3;
        $this->jsVars['totalColumnNo'] = 7;
        $this->jsVars['paymentDateColumnNo'] = 8;
        $this->jsVars['checkNumberColumnNo'] = 9;
        $this->jsVars['idColumnNo'] = 3;

        $this->jsVars['urls']['searcher'] =
            jsonLink('datatables', ['modelName' => $table->ajaxModel]);

        $this->jsVars['urls']['profilesVendors'] =
            makeLink('vendors', 'profile');

        $this->jsVars['urls']['processInvoices'] =
            makeLink('invoices', 'process');
        $this->editCosts = makeLink('costs', 'clients');

        $this->jsVars['urls']['searchCustContactInfo'] =
            makeLink('appJSON', 'searchCustContactInfo');
        $this->jsVars['urls']['updateCustContactInfo'] =
            makeLink('appJSON', 'updateCustContactInfo');
        $this->jsVars['urls']['deleteCustContacts'] =
            makeLink('appJSON', 'deleteCustContacts');

        $this->jsVars['urls']['updateInvoicePayment'] =
            customJSONLink('appJSON', 'updateInvoicePayment');

        $this->jsVars['urls']['invoiceData'] =
            customJSONLink('appJSON', 'invoiceData');
         $this->jsVars['urls']['getCustomerInfo'] =
            customJSONLink('appJSON', 'getCustomerInfo');

         $this->jsVars['urls']['updateCustomerInfo'] =
            customJSONLink('appJSON', 'updateCustomerInfo');

         $this->jsVars['urls']['invoiceData'] =
            customJSONLink('appJSON', 'invoiceData');

         $this->jsVars['urls']['updateInvoTables'] =
            customJSONLink('appJSON', 'updateInvoTables');

         $this->jsVars['urls']['cancelInvoice'] =
            customJSONLink('appJSON', 'cancelInvoice');

        $this->includeJS['custom/js/common/formToArray.js'] = TRUE;
        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $this->jsVars['currencyCode'] = $this->currencyCode;
    }

    /*
    ****************************************************************************
    */

    function processInvoicesController()
    {
        $this->jsVars['process'] = TRUE;
        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $this->invDt = NULL;
        $this->reprint = FALSE;

        $inv = $this->getVar('inv', 'getDef');

        if ($inv) {
            $this->jsVars['reprint'] = $this->reprint = TRUE;


            $table = new invoices\issueInvoice($this);
            $ajax = new datatables\ajax($this);

            $this->modelName = getClass($table);

            $ajax->output($table, [
                'ajaxPost' => TRUE,
                'order' => [0 => 'desc'],
            ]);

            new datatables\searcher($table);
            new datatables\editable($table);

            $sql = 'SELECT
                            cust_cd,
                            cust_type,
                            IF(
                                h.bill_to_add2,
                                CONCAT(h.bill_to_add1, " ", h.bill_to_add2),
                                h.bill_to_add1
                            ) AS bill_to_add,
                            h.bill_to_city,
                            h.bill_to_state,
                            h.bill_to_cnty,
                            h.bill_to_zip,
                            h.net_terms,
                            ctc_ph,
                            ctc_nm,
                            IF(
                                ship_to_add2,
                                CONCAT(ship_to_add1, " ", ship_to_add2),
                                ship_to_add1
                            ) AS ship_to_add,
                            ship_to_city,
                            ship_to_state,
                            ship_to_cnty,
                            ship_to_zip,

                            inv_id,
                            inv_dt,
                            h.net_terms,
                            cust_nm,
                            h.cust_id,

                            inv_amt

                    FROM   invoice_hdr h
                    JOIN   customer_mstr c ON c.cust_id = h.cust_id
                    LEFT JOIN
                    (
                        SELECT  cc.cust_id,
                                cust_ctc_id,
                                ctc_ph,
                                ctc_nm
                        FROM    customer_ctc cc
                        WHERE   ctc_dft
                        AND     cc.status != "d"
                    ) t ON t.cust_id = c.cust_id
                    WHERE   inv_num = '.intVal($inv);

            $hdr = $this->queryResult($sql);

            $this->billTo = [
                'Customer Code' => getDefault($hdr['cust_cd'], NULL),
                'Customer Name' => getDefault($hdr['cust_nm'], NULL),
                'Address' => getDefault($hdr['bill_to_add'], NULL),
                'City' =>  getDefault($hdr['bill_to_city'], NULL),
                'State' => getDefault($hdr['bill_to_state'], NULL),
                'Country' => getDefault($hdr['bill_to_cnty'], NULL),
                'Zip' => getDefault($hdr['bill_to_zip'], NULL),
                'Terms' => getDefault($hdr['net_terms'], NULL),
                'Tel' => getDefault($hdr['ctc_ph'], NULL),
                'Attn' => getDefault($hdr['ctc_nm'], NULL),
            ];

            $this->shipTo = [
                'Customer Name' => getDefault($hdr['cust_nm'], NULL),
                'Address' => getDefault($hdr['ship_to_add'], NULL),
                'City' => getDefault($hdr['ship_to_city'], NULL),
                'State' => getDefault($hdr['ship_to_state'], NULL),
                'Country' => getDefault($hdr['ship_to_cnty'], NULL),
                'Zip' => getDefault($hdr['ship_to_zip'], NULL),
            ];

            $this->invDt = $hdr['inv_dt'];
            $this->invoiceNo = $inv;
            $this->vendorID = $hdr['cust_id'];

            $sql = 'SELECT  d.inv_id,
                            chg_cd_qty AS quantity,
                            chg_cd_price AS rate,
                            chg_cd_amt AS ccTotal,
                            chg_cd,
                            chg_cd_type,
                            chg_cd_des,
                            d.chg_cd_uom
                    FROM    invoice_dtls d
                    JOIN    charge_cd_mstr c ON c.chg_cd_id = d.chg_cd_id
                    WHERE   c.status <> "d"
                    AND     inv_num = '.intVal($inv);

            $this->jsVars['invoInfo']['details'][$this->vendorID] =
                $this->queryResults($sql);

            //get the container name
            $rcvSql = '
                SELECT  rcv_nbr
                FROM    invoice_hdr h
                JOIN    inv_his_rcv r ON r.inv_id = h.inv_id
                WHERE   inv_num = ' . intVal($inv);

            $recNums =   array_keys($this->queryResults($rcvSql));

            if ($recNums) {
                $containers = new \tables\inventory\containers($this);

                $this->jsVars['invoInfo']['invItemsIDs']['Receiving'] = $recNums;

                if ($recNums && count($recNums) === 1) {
                  $this->containerName = $containers->getContainerName($recNums);
                }
            }

            //get the order number
            $ordSql = '
                SELECT  ord_num
                FROM    invoice_hdr h
                JOIN    inv_his_ord_prc o ON o.inv_id = h.inv_id
                WHERE   inv_num = ' . intVal($inv);

            $orderNums =  array_keys($this->queryResults($ordSql));

            if ($orderNums) {
                $orders = new \tables\orders($this);

                $this->jsVars['invoInfo']['invItemsIDs']['Order Processing'] = $orderNums;
                $clientOrdersNums =  $orders->getClientOrderNums($orderNums);

                $this->cancelNums = $orders->getCancelOrderNums($orderNums);

                if ($this->cancelNums) {
                   $cancelClientOrders = $orders->getClientOrderNums($this->cancelNums);

                   $clientOrdersNums = array_diff($clientOrdersNums, $cancelClientOrders);
                }


                $this->orderNumber = $clientOrdersNums && count($clientOrdersNums) === 1 ?
                            implode('', $clientOrdersNums) : [];

            }


            //get the storage range date
            $storSql = '
                SELECT MIN(inv_date) AS startDate,
                       MAX(inv_date) AS endDate
                FROM   invoice_hdr h
                JOIN   inv_his_month m ON m.inv_id = h.inv_id
                WHERE  h.inv_num = ' . intVal($inv) . '
                GROUP BY h.inv_id';

            $storRes = $this->queryResult($storSql);

            $this->storRange = $storRes['startDate'] . ' TO ' . $storRes['endDate'];


            $this->summary = invoicing::money([
                'Net Order' => $hdr['inv_amt'],
                'Discount' => 0,
                'Freight' => 0,
                'Sales Tax' => 0,
                'Balance Due' => $hdr['inv_amt'],
            ]);

            $this->vendorsProfileLink = makeLink('vendors', 'profile', [
                'vendorID' => $this->vendorID,
            ]);

            $this->jsVars['urls']['updateCustomerInfo'] =
                customJSONLink('appJSON', 'updateCustomerInfo');

            $this->jsVars['urls']['updateInvoiceProcessing'] =
                customJSONLink('appJSON', 'updateInvoiceProcessing');

             $this->jsVars['urls']['cancelInvoice'] =
                customJSONLink('appJSON', 'cancelInvoice');

            $this->jsVars['currencyCode'] = $this->currencyCode;

            $this->detailItems = makeLink('invoices', 'details');

            return;
        }

        $this->includeJS['js/jQuery/blocker.js'] = TRUE;


        $params = $this->getArray('post', 'getDefault');

        $checkSummary =  invoicing::init($this)->checkSummaryDate($params);

        if (! $checkSummary) {
            $this->errors[] = 'Invoice summary table not created for the enddate selected';
        }

        if (getDefault($params['items'])) {
            foreach ($params['items'] as $key => $values) {
                $this->jsVars['storeItems'][$key] = array_keys($values);
            }
        }

        $openCust = getDefault($params['openCust']);
        $passedVendorID = getDefault($params['vendorID']);

        $vendorID = $this->vendorID = $this->jsVars['vendorID'] =
             $params['custID'] = $passedVendorID ? $passedVendorID : $openCust;

        $this->jsVars['startDate'] = $params['startDate'] =
            getDefault($params['fromDate']);
        $this->jsVars['endDate'] = $params['endDate'] =
            getDefault($params['toDate']);

        $params['details'] = TRUE;

        $this->jsVars['invoInfo'] =
            invoicing::init($this)->updateInvoTables($params);

        //get the storage range
        $this->storRange = $params['startDate'] . ' TO ' . $params['endDate'];

        //get the container name
        $containers = new \tables\inventory\containers($this);

        $recNums = getDefault($this->jsVars['invoInfo']['invItemsIDs']['Receiving']);

        if ($recNums && count($recNums) === 1) {
          $this->containerName = $containers->getContainerName($recNums);
        }

        //get the order number
        $orders = new \tables\orders($this);

        $orderNums = getDefault($this->jsVars['invoInfo']['invItemsIDs']['Order Processing']);

        $clientOrdersNums = $orders->getClientOrderNums($orderNums);

        $this->cancelNums = $orders->getCancelOrderNums($orderNums);

        if ($this->cancelNums) {
            $cancelClientOrders = $orders->getClientOrderNums($this->cancelNums);

            $clientOrdersNums = array_diff($clientOrdersNums, $cancelClientOrders);
        }

        $this->orderNumber = $clientOrdersNums && count($clientOrdersNums) === 1 ?
                        implode('', $clientOrdersNums) : [];



        $table = new invoices\issueInvoice($this);
        $headers = new \invoices\headers($this);

        $this->invoiceNo = $this->jsVars['invoiceNo'] =
                $headers->getNextInvoiceNumber();

        $sql = 'INSERT INTO invoice_hdr
                SET inv_num = ?';

        $this->runQuery($sql, [$this->invoiceNo]);

        $this->modelName = getClass($table);

        // Export Datatalbe
        $ajax = new datatables\ajax($this);

        $ajax->output($table, [
            'ajaxPost' => TRUE,
            'order' => [0 => 'desc'],
        ]);

        new datatables\searcher($table);
        new datatables\editable($table);

        $customer = new \common\customer($this);

        //get the billTo address
        $billToValues = $customer->getBillTo($this->vendorID);

        $this->billTo = [
            'Customer Code' => getDefault($billToValues['cust_cd'], NULL),
            'Customer Name' => getDefault($billToValues['vendorName'], NULL),
            'Address' => getDefault($billToValues['bill_to_add'], NULL),
            'City' =>  getDefault($billToValues['bill_to_city'], NULL),
            'State' => getDefault($billToValues['bill_to_state'], NULL),
            'Country' => getDefault($billToValues['bill_to_cnty'], NULL),
            'Zip' => getDefault($billToValues['bill_to_zip'], NULL),
            'Terms' => getDefault($billToValues['net_terms'], NULL),
            'Tel' => getDefault($billToValues['ctc_ph'], NULL),
            'Attn' => getDefault($billToValues['ctc_nm'], NULL),
        ];

        //get the billTo address
        $shipToValues = $customer->getShipTo($this->vendorID);

        $this->shipTo = [
            'Customer Name' => getDefault($shipToValues['vendorName'], NULL),
            'Address' => getDefault($shipToValues['ship_to_add'], NULL),
            'City' => getDefault($shipToValues['ship_to_city'], NULL),
            'State' => getDefault($shipToValues['ship_to_state'], NULL),
            'Country' => getDefault($shipToValues['ship_to_cnty'], NULL),
            'Zip' => getDefault($shipToValues['ship_to_zip'], NULL),
        ];

        $this->summary = invoicing::money([
            'Net Order' => $this->jsVars['invoInfo']['sums'][$vendorID],
            'Discount' => 0,
            'Freight' => 0,
            'Sales Tax' => 0,
            'Balance Due' => $this->jsVars['invoInfo']['sums'][$vendorID],
        ]);

        $this->vendorsProfileLink = makeLink('vendors', 'profile', [
            'vendorID' => $this->vendorID,
        ]);

        $this->jsVars['urls']['updateCustomerInfo'] =
            customJSONLink('appJSON', 'updateCustomerInfo');

        $this->jsVars['urls']['updateInvoiceProcessing'] =
            customJSONLink('appJSON', 'updateInvoiceProcessing');

         $this->jsVars['urls']['cancelInvoice'] =
            customJSONLink('appJSON', 'cancelInvoice');

        $this->jsVars['currencyCode'] = $this->currencyCode;

        $this->detailItems = makeLink('invoices', 'details');
    }

    /*
    ****************************************************************************
    */

    function printPageInvoicesController()
    {
        $customer = new \common\customer($this);

        $getInvoiceNo = getDefault($this->get['invoiceNo']);
        $invoiceNo = $getInvoiceNo ? $getInvoiceNo : $this->post['invoiceNo'];

        $rcvItems = explode(',', getDefault($this->post['rcvItems']));
        $container = getDefault($this->post['container']);

        $ordItems = explode(',', getDefault($this->post['ordItems']));

        $storRange = $this->post['storRange'];

        $containers = new \tables\inventory\containers($this);
        $rcvResult = $containers->getContainerReceivedDate($rcvItems);

        $orders = new \tables\orders($this);
        $ordResult = $orders->getOrderDetails($ordItems);


        $pageLength = $this->pdfPortraitPageLength;
        $fontSize = $this->pdfFontSize;
        $invoiceFooterHeight = $this->pdfInvoiceFooterHeight;

        $headers = new \invoices\headers($this);
        $details = new \invoices\details($this);

        $invoiceHeader = $headers->get($invoiceNo);
        $invoiceDetails = $details->get($invoiceNo);

        $this->pdf = $pdf = new \TCPDF('P', 'mm', 'Letter', TRUE, 'UTF-8', FALSE);

        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);
        $pdf->SetAutoPageBreak(TRUE, 0);
        $pdf->SetLeftMargin($this->pdfLeftMargin);
        $pdf->setCellPaddings(1, 0, 1, 0);
        $pdf->SetLineWidth(0.1);
        $pdf->SetFont('helvetica', '', $fontSize);
        $pdf->SetFillColor(240, 240, 240);

        $shipToValues = $customer->getShipTo($invoiceHeader['cust_id']);

        $detailsStru = [
            '' => [
                'width' => 10,
                'align'  => 'C',
            ],
            'DESC' => [
                'width' => 90,
                'field' => 'chg_cd_desc',
                'desc' => TRUE,
                'align'  => 'L',
            ],
            'QTY' => [
                'width' => 10,
                'field' => 'chg_cd_qty',
                'align'  => 'L',
            ],
            'UOM' => [
                'width' => 30,
                'field' => 'chg_cd_uom',
                'align'  => 'L',
            ],
            'PRICE' => [
                'width' => 30,
                'field' => 'chg_cd_price',
                'currency' => TRUE,
                'align'  => 'L',
            ],
            'AMT' => [
                'width' => 25,
                'field' => 'chg_cd_amt',
                'currency' => TRUE,
                'align'  => 'L',
            ],
        ];

        $headerParams = [
            'pdf' => $pdf,
            'invoiceNo' => $invoiceNo,
            'detailsStru' => $detailsStru,
            'invoiceHeader' => $invoiceHeader,
            'shipToValues' => $shipToValues,
        ];

        $rowCount = $this->displayInvoiceHeader($headerParams);

        $netOrder = $discount = $freight = $salesTax = 0;
        $currencyCode = NULL;

        $detailsRowCount = count($invoiceDetails);

        $count = 0;

        foreach ($invoiceDetails as $values) {

            $rowCount++;
            $count++;

            $isLastRow = $count == $detailsRowCount;

            if ($rowCount > $pageLength
             || $isLastRow && $rowCount > $pageLength) {

                $this->displayPageFooter($pdf, $rowCount);

                $rowCount = $this->displayInvoiceHeader($headerParams);

                $rowCount++;
            }

            $detailParams = [
                'pdf' => $pdf,
                'detailsStru' => $detailsStru,
                'uom' => $values['chg_cd_uom'],
                'type' => $values['chg_cd_type'],
                'currency' => $values['chg_cd_cur'],
                'rcvItems' => $rcvItems,
                'ordItems' => $ordItems,
                'rcvResult' => $rcvResult,
                'ordResult' => $ordResult,
                'container' => $container,
                'storRange' => $storRange,
                'count' => $count,
                'rowCount' => $rowCount,
                'values' => $values
            ];


            $rowCount = $this->displayDetailRow($detailParams);

            $currencyCode = $values['chg_cd_cur'];

            $netOrder += $values['chg_cd_amt'];

            $pdf->Ln();
        }

        $pdf->Ln();

        $rowCount = $this->displayInvoiceFooter($pdf, $currencyCode, $rowCount, [
            'Net Order' => $netOrder,
            'Discount' => $discount,
            'Freight' => $freight,
            'Sales Tax' => $salesTax
        ]);


        if ($rowCount + $this->pdfExtraLength > $pageLength) {

            $this->displayPageFooter($pdf, $rowCount);

            $pdf->AddPage('P');

            $rowCount = 0;
        }

        $rowCount++;

        $rowCount = $this->displayContact($pdf, $rowCount);

        $pdf->Ln();

        $this->displayDetach($pdf, $headerParams, $rowCount);

        $pdf->Ln();
        $pdf->Ln();

        $containerParams = [
            'pdf' => $pdf,
            'data' => $rcvResult,
            'headerStru' => $this->containerHeader(),
            'title' => 'LIST OF CONTAINERS'
        ];

        $orderParams = [
            'pdf' => $pdf,
            'data' => $ordResult,
            'headerStru' => $this->orderHeader(),
            'title' => 'LIST OF ORDERS'
        ];

        if (count($rcvItems) > 1) {
            $this->displayAttachementList($containerParams);
        }

        $pdf->Ln();
        $pdf->Ln();


        if (count($ordItems) > 1) {
            $this->displayAttachementList($orderParams);
        }

        $fileName = $invoiceHeader['vendorName'] . '_(Inv)_'
                        . sprintf('%010d', $invoiceNo) . '.pdf';

        $pdf->setTitle($fileName);

        $pdf->Output($fileName,'I');
    }

    /*
    ****************************************************************************
    */

    function printStatementInvoicesController()
    {
        $post = $this->post;

        $vendorID = $post['vendorID'];
        $tableData = json_decode($post['tableData'], TRUE);

        $customer = new \common\customer($this);

        $pageLength = $this->pdfPortraitPageLength;
        $rowHeight = $this->pdfRowHeight;
        $fontSize = $this->pdfFontSize;

        $this->pdf = $headerParams['pdf'] = $pdf =
                new \TCPDF('P', 'mm', 'Letter', TRUE, 'UTF-8', FALSE);

        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);
        $pdf->SetAutoPageBreak(TRUE, 0);
        $pdf->SetLeftMargin($this->pdfLeftMargin);
        $pdf->setCellPaddings(1, 0, 1, 0);
        $pdf->SetLineWidth(0.1);
        $pdf->SetFont('helvetica', '', $fontSize);
        $pdf->SetFillColor(240, 240, 240);

        $headerParams['tableStru'] = [
            '' => [
                'width' => 10,
            ],
            'INV NBR' => [
                'width' => 30,
                'field' => 'invNbr',
            ],
            'INV DT' => [
                'width' => 40,
                'field' => 'invDT',
            ],
            'TTL' => [
                'width' => 40,
                'field' => 'total',
            ],
            'INV STS' => [
                'width' => 35,
                'field' => 'sts',
            ],
            'PMNT DT' => [
                'width' => 40,
                'field' => 'pmntDT',
            ],
        ];

        $headerParams['billTo'] = $customer->getBillTo($vendorID);
        $headerParams['fromDate'] = $post['fromDate'];
        $headerParams['toDate'] = $post['toDate'];

        $rowCount = $this->displayStatementHeader($headerParams);

        $count = $total = 0;

        foreach ($tableData as $values) {

            $rowCount++;
            $count++;

            if ($rowCount > $pageLength) {

                $rowCount = $this->displayStatementHeader($headerParams);

                $rowCount++;
            }

            foreach ($headerParams['tableStru'] as $stru) {

                $field = getDefault($stru['field']);

                $text = $field ? $values[$field] : $count;

                pdf::myMultiCell($pdf, $stru['width'], $rowHeight, $text, 0,
                        'L ', 1, $rowCount % 2 == 0);
            }

            $total += str_replace($this->currencyCode, NULL, $values['total']);

            $pdf->Ln();
        }

        $pdf->SetFont('helvetica', 'B', $fontSize);

        foreach ($headerParams['tableStru'] as $stru) {

            $field = getDefault($stru['field']);

            $text = $field == 'total' ? 'USD ' . number_format($total, 2) : NULL;

            pdf::myMultiCell($pdf, $stru['width'], $rowHeight, $text, 0, 'L ', 1);
        }

        $pdf->SetFont('helvetica', '', $fontSize);

        $rowCount++;

        $this->displayPageFooter($pdf, $rowCount);

        $pdf->Output('pdf','I');
    }

    /*
    ****************************************************************************
    */


    function chargeCodeMasterInvoicesController()
    {
        $table = new tables\customer\chargeCodeMaster($this);

        // Export Datatable
        $ajax = new datatables\ajax($this);

        $ajax->output($table, [
            'ajaxPost' => TRUE
        ]);

        new datatables\searcher($table);

        $editable = new datatables\editable($table);

        $editable->canAddRows();
    }

     /*
    ****************************************************************************
    */

    function detailsInvoicesController()
    {
        $rec = getDefault($this->post['recItems']);
        $ord = getDefault($this->post['ordItems']);

        $recItems = $rec ? explode(",", $rec) : [];
        $ordItems = $ord ? explode(",", $ord) : [];

        if ($recItems) {
            $table = new tables\inventory\containers($this);

            // Export Datatable
            $ajax = new datatables\ajax($this);

            $ajax->addControllerSearchParams([
                    'values' => $recItems,
                    'field' => 'recNum',
                    'exact' => TRUE,
            ]);
        } else if ($ordItems) {
            $table = new tables\orders($this);

            // Export Datatable
            $ajax = new datatables\ajax($this);

            $ajax->addControllerSearchParams([
                    'values' => $ordItems,
                    'field' => 'scanOrderNumber',
                    'exact' => TRUE,
            ]);
       }

        $this->includeJS['js/datatables/editables.js'] = TRUE;

        $ajax->output($table, [
            'ajaxPost' => TRUE
        ]);

        new datatables\searcher($table);
    }

    /*
    ****************************************************************************
    */

 }
