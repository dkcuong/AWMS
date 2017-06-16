<?php

use \common\pdf;

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

class model extends base
{

    public $modelName = NULL;

    public $vendorsHTML = NULL;

    public $statusesHTML = NULL;

    public $invoiceNo = NULL;

    public $vendorID = NULL;

    public $vendorsProfileLink = NULL;

    public $billTo = [];

    public $shipTo = [];

    public $summary = [];

    public $errors = [];

    public $pdfPortraitPageWidth = 195;

    public $pdfPortraitPageLength = 58;

    public $pdfLeftMargin = 10;

    public $pdfLogoWidth = 100;

    public $pdfSeldatAddressWidth = 60;
    
    public $pdfRowHeight = 4.5;

    public $pdfFontSize = 9;

    public $pdfPageNo = 1;

    public $pdfFooterWidth = 25;

    public $pdfInvoiceFooterHeight = 4;

    public $pdfInvoiceNumberWidth = 10;

    public $currencyCode = 'USD';

    public $pdfSeldatAddress = [
        'Seldat Distribution Inc.',
        '1900 River Road',
        'Burlington, NJ 08016',
    ];

    public $recItems = [];

    public $ordItems = [];
    
    public $rcvUOM = [
        'CONTAINER', 
        'PIECES', 
        'PALLET',
        'CARTON',
        'MONTH',
        'MONTHLY_SMALL_CARTON',
        'MONTHLY_MEDIUM_CARTON',
        'MONTHLY_LARGE_CARTON',
        'VOLUME_CURRENT',
        'VOLUME_RAN'
    ];
    
    public $containerName = NULL;
    
    public $orderNumber = [];
    
    public $cancelNums = [];
    
    public $storRange = NULL;
    
    public $pdfContactAddress = [
        'Renee Williams (732) 348-0000',
        'renee.williams@seldatinc.com',
    ];
    
    public $companyName = 'Seldat Distribution Inc.';
    
    public $pdContactNumber = [
        'Seldat Distribution, Inc',                                              
        '23 Mack Drive',                                                     
        'Edison, NJ',
        '08817', 
        'Phone: [732-348-0000]',
        'Fax:   renee.williams@seldatinc.com',
    ];
    
    public $pdfInvoiceWidth = 120;
    
    public $pdfExtraLength = 17;

    /*
    ****************************************************************************
    */

    function billableCusts($vendors)
    {
        $custList = $vendors->get();
        $billableCusts = [];
        foreach ($custList as $custID => $row) {

            // Check if there is something billable from the time frame for
            // each client
            // For now including all customers

            $billableCusts[$custID] = [
                'radio' => NULL,
                'sts' => NULL,
                'name' => $row['fullVendorName'],
                'invNbr' => NULL,
                'invDT' => NULL,
                'cnclNbr' => NULL,
                'currency' => $this->currencyCode,
                'total' => 0,
                'pmntDT' => NULL,
                'check' => NULL,
                'custID' => $custID,
                'type' => NULl,
            ];
        }

        return $billableCusts;
    }

    /*
    ****************************************************************************
    */


    function displayCustomerAddress($data)
    {
        $pdf = $data['pdf'];
        $array = $data['array'];
        $field = $data['field'];
        $caption = $data['caption'];
        $captionWidth = $data['captionWidth'];
        $valueWidth = $data['valueWidth'];

        $rowHeight = $this->pdfRowHeight;

        if (isset($array[$field])) {
            pdf::myMultiCell($pdf, $captionWidth, $rowHeight, $caption, 0, 'L');
            pdf::myMultiCell($pdf, $valueWidth, $rowHeight, $array[$field], 0, 'L');
        } else {
            pdf::myMultiCell($pdf, $captionWidth + $valueWidth, $rowHeight, NULL, 0);
        }
    }

    /*
    ****************************************************************************
    */

    function displayInvoiceHeader($data)
    {
        $pdf = $data['pdf'];
        $invoiceNo = $data['invoiceNo'];
        $detailsStru = $data['detailsStru'];
        $invoiceHeader = $data['invoiceHeader'];
        $shipToValues = $data['shipToValues'];

        $pageWidth = $this->pdfPortraitPageWidth;
        $rowHeight = $this->pdfRowHeight;
        $fontSize = $this->pdfFontSize;

        $pdf->AddPage('P');

        $formattedInvoiceNo =
                str_pad($invoiceNo, $this->pdfInvoiceNumberWidth, '0', STR_PAD_LEFT);
        
        $titleParam = [
            'pdf' => $pdf,
            'invoiceNo' => $formattedInvoiceNo,
            'invoiceHeader' => $invoiceHeader
        ];

        $rowCount = $this->displayInvoiceTitle($titleParam);

        $estimateMargin = 5;

        $columnWidth = floor(($pageWidth - $estimateMargin) / 2);

        $margin = $pageWidth - $columnWidth * 2;

        $captionWidth = 30;

        $valueWidth = $columnWidth - $captionWidth;

        $addresses = [
            'Customer Name' => [
                'vendorName',
                'vendorName',
            ],
            'Address' => [
                'bill_to_add',
                'ship_to_add',
            ],
            'City' => [
                'bill_to_city',
                'ship_to_city',
            ],
            'State' => [
                'bill_to_state',
                'ship_to_state',
            ],
            'Country' => [
                'bill_to_cnty',
                'ship_to_cnty',
            ],
            'Zip' => [
                'bill_to_zip',
                'ship_to_zip',
            ],
            'Tel' => [
                'ctc_ph',
            ],
            'Attn' => [
                'ctc_nm',
            ],
        ];

        $pdf->SetFont('helvetica', 'B', $fontSize);

        pdf::myMultiCell($pdf, $captionWidth, $rowHeight, NULL, 0);
        pdf::myMultiCell($pdf, $valueWidth, $rowHeight, 'Bill To', 0, 'L');
        pdf::myMultiCell($pdf, $margin, $rowHeight, NULL, 0);
        pdf::myMultiCell($pdf, $captionWidth, $rowHeight, NULL, 0);
        pdf::myMultiCell($pdf, $valueWidth, $rowHeight, 'Ship To', 0, 'L');

        $pdf->Ln();

        $pdf->SetFont('helvetica', '', $fontSize);

        foreach ($addresses as $caption => $field) {

            $billField = getDefault($field[0]);
            $shipField = getDefault($field[1]);

            if (isset($invoiceHeader[$billField]) || isset($shipToValues[$shipField])) {

                $this->displayCustomerAddress([
                    'pdf' => $pdf,
                    'array' => $invoiceHeader,
                    'field' => $billField,
                    'caption' => $caption,
                    'captionWidth' => $captionWidth,
                    'valueWidth' => $valueWidth,
                ]);

                pdf::myMultiCell($pdf, $margin, $rowHeight, NULL, 0, 'R');

                $this->displayCustomerAddress([
                    'pdf' => $pdf,
                    'array' => $shipToValues,
                    'field' => getDefault($shipField),
                    'caption' => $caption,
                    'captionWidth' => $captionWidth,
                    'valueWidth' => $valueWidth,
                ]);

                $rowCount++;

                $pdf->Ln();
            }
        }

        $rowCount++;

        $pdf->Ln();

        $headerStru = [
            'CUST' => [
                'width' => 40,
                'value' => $invoiceHeader['vendorName'],
            ],
            'CUST REF' => [
                'width' => 30,
                'value' => $invoiceHeader['cust_ref'],
            ],
            'INV NBR' => [
                'width' => 20,
                'value' => $formattedInvoiceNo,
            ],
            'SHPMNT NBR' => [
                'width' => 40,
                'value' => NULL,
            ],
            'INV DT' => [
                'width' => 20,
                'value' => $invoiceHeader['inv_dt'],
            ],
            'TERMS' => [
                'width' => 45,
                'value' => $invoiceHeader['net_terms'],
            ],
        ];


        $pdf->SetFont('helvetica', 'B', $fontSize);

        foreach ($headerStru as $caption => $values) {
            pdf::myMultiCell($pdf, $values['width'], $rowHeight, $caption, 0, 'C', 1);
        }

        $pdf->SetFont('helvetica', '', $fontSize);

        $rowCount++;

        $pdf->Ln();

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Line($x, $y, $x + $pageWidth, $y);

        $headerValues = [
            'CUST' => [
                'width' => 40,
                'value' => $invoiceHeader['vendorName'],
            ],
            'CUST REF' => [
                'width' => 30,
                'value' => $invoiceHeader['cust_ref'],
            ],
            'INV NBR' => [
                'width' => 25,
                'value' => $formattedInvoiceNo,
            ],
            'SHPMNT NBR' => [
                'width' => 35,
                'value' => NULL,
            ],
            'INV DT' => [
                'width' => 35,
                'value' => $invoiceHeader['inv_dt'],
            ],
            'TERMS' => [
                'width' => 30,
                'value' => $invoiceHeader['net_terms'],
            ],
        ];


        foreach ($headerValues as $caption => $values) {
            pdf::myMultiCell($pdf, $values['width'], $rowHeight,
                    $values['value'], 0, 'L', 1, TRUE);
        }

        $rowCount += 2;

        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont('helvetica', 'B', $fontSize);

        foreach ($detailsStru as $key => $stru) {
            pdf::myMultiCell($pdf, $stru['width'], $rowHeight, $key, 0, 'C', 1);
        }

        $pdf->SetFont('helvetica', '', $fontSize);

        $rowCount++;

        $pdf->Ln();

        $y = $pdf->GetY();

        $pdf->Line($x, $y, $x + $pageWidth, $y);

        return $rowCount;
    }

    /*
    ****************************************************************************
    */

    function displayInvoiceTitle($param)
    {
        $pdf = $param['pdf'];
        $invoiceNo = $param['invoiceNo'];
                
        $custRef = $param['invoiceHeader']['cust_ref'];
        $invDate = $param['invoiceHeader']['inv_dt'];
        
        $pageWidth = $this->pdfPortraitPageWidth;
        $rowHeight = $this->pdfRowHeight;
        $leftMargin = $this->pdfLeftMargin;
        $fontSize = $this->pdfFontSize;
        $invoiceWidth = $this->pdfInvoiceWidth;

        $invoiceMargin = $pageWidth - $this->pdfLogoWidth;
        $invoiceHeight = $rowHeight * 2;

        $valueWidth = 30;
        $columnMargin = $pageWidth - $invoiceWidth;
       

        $rowCount = 1;

        //logo
        $appURL = \models\config::getAppURL();
        $logo = $appURL . '/custom/images/headerSmall.png';
        
        $y = $pdf->GetY();
        
        $invoiceTitle = [
            'Date' => $invDate,
            'Invoice#' => $invoiceNo,
            'Customer ID' => $custRef
            
        ];
   
        if ( getimagesize($logo) ) {
            $pdf->Image($logo, '5', '10', '', 23, '', '', '', FALSE, 300, 'L',
                false, false, 0, false, false, false);
             
            $pdf->SetFont('helvetica', '', $fontSize + 6);

            $text = 'INVOICE ';
            
            $pdf->SetXY($invoiceMargin, $y);

            pdf::myMultiCell($pdf, $invoiceMargin, $invoiceHeight, $text, 0, 'R', 1);
            
            $pdf->Ln();

            $y = $pdf->GetY();
            
            foreach ($invoiceTitle as $caption => $value) {
                
                $pdf->SetFont('helvetica', '', $fontSize);
                
                $text = $caption . CHR(32);

                $pdf->SetXY($invoiceMargin, $y);

                pdf::myMultiCell($pdf, $columnMargin, $rowHeight, $text, 0, 'R');
                pdf::myMultiCell($pdf, $valueWidth, $rowHeight, $value, 1, 'L');

                $y += $rowHeight;

                $rowCount++;
            }
                 
            $pdf->Ln(10);
        }

        $addressWidth = $this->pdfSeldatAddressWidth;

        $titleHeight = $rowHeight * count($this->pdfSeldatAddress);
        $titleWidth = $pageWidth - $addressWidth;

        $y = $pdf->GetY();
        
        $pdf->SetFont('helvetica', '', $fontSize);

        foreach ($this->pdfSeldatAddress as $address) {

            $pdf->SetXY($leftMargin, $y);

            pdf::myMultiCell($pdf, $addressWidth, $rowHeight, $address, 0, 'L');

            $y += $rowHeight;
            
            $rowCount++;
        }

        $pdf->Ln();

        $pdf->SetFont('helvetica', '', $fontSize + 6);

        $text = $invoiceNo ? 'INVOICE ' . $invoiceNo : NULL;

        pdf::myMultiCell($pdf, $titleWidth, $titleHeight, $text, 0, 'L', 1);
        
        $rowCount++;
        
        $pdf->Ln();

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Line($x, $y, $x + $pageWidth, $y, [
            'width' => 0.5,
        ]);

        // adding 5 to compensate cells height greater than normal cell height
        $rowCount += 5;
        
        return $rowCount;
    }
    
    /*
    ****************************************************************************
    */

    function displayInvoiceFooter($pdf, $currencyCode, $rowCount, $data)
    {
        $rowHeight = $this->pdfRowHeight;
        $fontSize = $this->pdfFontSize;
        $pageWidth = $this->pdfPortraitPageWidth;
        $footerWidth = $this->pdfFooterWidth;

        $footerMargin = $pageWidth - $footerWidth;

        $amount = 0;

        foreach ($data as $caption => $value) {

            $rowCount++;
            
            pdf::myMultiCell($pdf, $footerMargin, $rowHeight, $caption, 0, 'R', 1);
            pdf::myMultiCell($pdf, $footerWidth, $rowHeight,
                    $currencyCode . ' ' . number_format($value, 2), 0, 'C', 1);

            $amount += $value;

            $pdf->Ln();
        }

        $pdf->Ln();

        $pdf->SetFont('helvetica', 'B', $fontSize);

        pdf::myMultiCell($pdf, $footerMargin, $rowHeight, 'Balance Due', 0, 'R', 1);
        pdf::myMultiCell($pdf, $footerWidth, $rowHeight,
                $currencyCode . ' ' . number_format($amount, 2), 0, 'C', 1);
        
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', $fontSize);
        
        $rowCount += 4;
        
        return $rowCount;
    }
    
    /*
    ****************************************************************************
    */

    function displayPageFooter($pdf, $rowCount)
    {
        $pageWidth = $this->pdfPortraitPageWidth;
        $rowHeight = $this->pdfRowHeight;
        
        for ($count = 0; $count < max(1, $this->pdfPortraitPageLength - $rowCount); $count++) {
            pdf::myMultiCell($pdf, $pageWidth, $rowHeight, NULL, 0, 'C');
            $pdf->Ln();
        }

        $text = 'Page ' . $this->pdfPageNo++;

        pdf::myMultiCell($pdf, $pageWidth, $rowHeight, $text, 0, 'C', 1);
    }

    /*
    ****************************************************************************
    */

    function displayStatementHeader($data)
    {
        $pdf = $data['pdf'];
        $tableStru = $data['tableStru'];
        $billTo = $data['billTo'];

        $pageWidth = $this->pdfPortraitPageWidth;
        $rowHeight = $this->pdfRowHeight;
        $fontSize = $this->pdfFontSize;

        $pdf->AddPage('P');

        $rowCount = $rowCount = $this->displayInvoiceTitle($pdf);

        $pdf->SetFont('helvetica', '', $fontSize + 8);

        $vendorText = 'INVOICE STATEMENT for ' . $billTo['vendorName'];

        pdf::myMultiCell($pdf, $pageWidth, $rowHeight + 3, $vendorText, 0);

        $rowCount++;

        $pdf->Ln();

        $fromDate = strtotime($data['fromDate']);
        $toDate = strtotime($data['toDate']);

        $dateText = date('F j, Y', $fromDate) . ' ~ ' . date('F j, Y', $toDate);

        pdf::myMultiCell($pdf, $pageWidth, $rowHeight + 3, $dateText, 0);

        $pdf->SetFont('helvetica', '', $fontSize);

        $rowCount += 3;

        $pdf->Ln();

        $margin = 5;

        $columnWidth = floor(($pageWidth - $margin) / 2) - $margin;

        $captionWidth = 30;

        $valueWidth = $columnWidth - $captionWidth;

        $addresses = [
            'Customer Name' => 'vendorName',
            'Address' => 'bill_to_add',
            'City' => 'bill_to_city',
            'State' => 'bill_to_state',
            'Country' => 'bill_to_cnty',
            'Zip' => 'bill_to_zip',
            'Tel' => 'ctc_ph',
            'Attn' => 'ctc_nm',
        ];

        $pdf->SetFont('helvetica', 'B', $fontSize);

        pdf::myMultiCell($pdf, $captionWidth, $rowHeight, NULL, 0);
        pdf::myMultiCell($pdf, $valueWidth, $rowHeight, 'Bill To', 0, 'L');

        $pdf->Ln();

        $pdf->SetFont('helvetica', '', $fontSize);

        foreach ($addresses as $caption => $field) {
            if (isset($billTo[$field])) {

                $this->displayCustomerAddress([
                    'pdf' => $pdf,
                    'array' => $billTo,
                    'field' => $field,
                    'caption' => $caption,
                    'captionWidth' => $captionWidth,
                    'valueWidth' => $valueWidth,
                ]);

                $rowCount++;

                $pdf->Ln();
            }
        }

        $rowCount++;

        $pdf->Ln();

        $pdf->SetFont('helvetica', 'B', $fontSize);

        foreach ($tableStru as $key => $stru) {
            pdf::myMultiCell($pdf, $stru['width'], $rowHeight, $key, 0, 'C', 1);
        }

        $pdf->SetFont('helvetica', '', $fontSize);

        $rowCount++;

        $pdf->Ln();

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Line($x, $y, $x + $pageWidth, $y);

        return $rowCount;
    }

    /*
    ****************************************************************************
    */
    
    function displayAttachementList($containerParams) 
    {
        $pdf = $containerParams['pdf'];
        $data = $containerParams['data'];
        $headerStru = $containerParams['headerStru'];
        $title = $containerParams['title'];
        
        $pageWidth = $this->pdfPortraitPageWidth;
        $rowHeight = $this->pdfRowHeight;
        $fontSize = $this->pdfFontSize;
        
        $pageLength = $this->pdfPortraitPageLength;

        $rowCount = $count = 0;
        
        $pdf->SetFont('helvetica', 'B', $fontSize);
        $pdf->Ln();
        $pdf->Ln();
        pdf::myMultiCell($pdf, $pageWidth, $rowHeight, $title, 0, 'C', 1);
        
        $pdf->Ln();
        $pdf->Ln();

        $rowCount++;
        
        foreach ($headerStru as $caption => $values) {
            pdf::myMultiCell($pdf, $values['width'], $rowHeight, $caption, 0, 'C', 1);
        }
        
        $pdf->SetFont('helvetica', '', $fontSize);

        $pdf->Ln();

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Line($x, $y, $x + $pageWidth, $y);

        $detailsRowCount = count($data);
        
        $headerParams = [
            'pdf' => $pdf,
            'fontSize' => $fontSize,
            'rowHeight' => $rowHeight,
            'pageWidth' => $pageWidth,
            'headerStru' => $headerStru
        ];

        foreach ($data as $values) {
            $rowCount++;
            $count++;

            $isLastRow = $count == $detailsRowCount;

            if ($rowCount > $pageLength || $isLastRow 
                    && $rowCount > $pageLength ) {

                $this->displayPageFooter($pdf, $rowCount);
                       
                $pdf->AddPage('P');
                
                $pdf->Ln();
                 
                $rowCount = $this->displayListHeader($headerParams);
                
                $rowCount++;
            }
           
            
            foreach ($headerStru as $stru) {
                $field = getDefault($stru['field']);
                $text = $values[$field];
                
                pdf::myMultiCell($pdf, $stru['dWidth'], $rowHeight, $text, 0,
                                  $stru['align'], 1, $rowCount % 2);
            }
            
            $pdf->Ln();
        }

        $rowCount += 3;

        $this->displayPageFooter($pdf, $rowCount);
        
        return $rowCount;
    }

    /*
    ****************************************************************************
    */
    
    function displayListHeader($params) 
    {
        $rowCount = 0;
        
        $pdf = $params['pdf'];
        $fontSize = $params['fontSize'];
        $rowHeight = $params['rowHeight'];
        $pageWidth = $params['pageWidth'];
        $headerStru = $params['headerStru'];

        $pdf->SetFont('helvetica', 'B', $fontSize);

        $pdf->Ln();

        foreach ($headerStru as $caption => $row) {
            pdf::myMultiCell($pdf, $row['width'], $rowHeight, $caption, 0, 'C', 1);
        }

        $pdf->Ln();
        $pdf->SetFont('helvetica', '', $fontSize);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->Line($x, $y, $x + $pageWidth, $y);

        $pdf->Ln();

        $rowCount += 4;
        
        return $rowCount;
    }
    
    /*
    ****************************************************************************
    */
    
    function containerHeader()
    {
        $containerStru = [
            'CLIENT' => [
                'dWidth' => 45,
                'width' => 35,
                'field' => 'vendorName',
                'align'  => 'L',
            ],
            'CONTAINER' => [
                'dWidth' => 35,
                'width' => 40,
                'field' => 'name',
                'align'  => 'L',
            ],
            'RECEIVING NBR' => [
                'dWidth' => 35,
                'width' => 40,
                'field' => 'recNum',
                'align'  => 'L',
            ],
            'MEASUREMENT SYSTEM' => [
                'dWidth' => 50,
                'width' => 45,
                'field' => 'measureID',
                'align'  => 'L',
            ],
            'RECEIVED DATE' => [
                'dWidth' => 30,
                'width' => 35,
                'field' => 'date',
                'align'  => 'L',
            ]
        ];
        
        return $containerStru;
    }
    
    /*
    ****************************************************************************
    */
    
    
    function orderHeader()
    {
        $orderStru = [
            'CLIENT' => [
                'dWidth' => 37,
                'width' => 35,
                'field' => 'vendorName',
                'align'  => 'L',
            ],
            'CLIENT NBR' => [
                'dWidth' => 30,
                'width' => 30,
                'field' => 'clientordernumber',
                'align'  => 'L',
            ],
            'CUST NBR' => [
                'dWidth' => 30,
                'width' => 30,
                'field' => 'customerordernumber',
                'align'  => 'L',
            ],
            'SCAN NBR' => [
                'dWidth' => 30,
                'width' => 30,
                'field' => 'scanOrderNumber',
                'align'  => 'L',
            ],
            'BOL NBR' => [
                'dWidth' => 30,
                'width' => 30,
                'field' => 'bolNumber',
                'align'  => 'L',
            ],
            'SHIP DATE' => [
                'dWidth' => 25,
                'width' => 22,
                'field' => 'startshipdate',
                'align'  => 'L',
            ],
            'STATUS' => [
                'dWidth' => 15,
                'width' => 22,
                'field' => 'status',
                'align'  => 'L',
            ]
        ];
        
        return $orderStru;
    }
    
    /*
    ****************************************************************************
    */
    
     function displayDetailRow($param)
    {
        $rowHeight = $this->pdfRowHeight;

        $ordItems = $param['ordItems'];
        $rcvItems = $param['rcvItems'];
        $rcvResult = $param['rcvResult'];
        $ordResult = $param['ordResult'];
        $values = $param['values'];
        $storRange = $param['storRange'];

        $currency = $param['currency'];
        $type = $param['type'];

        $pdf = $param['pdf'];
        $detailsStru = $param['detailsStru'];

        $rowCount = $param['rowCount'];

        $addRow = [];

        $columnCount = 0;

        $detail = NULL;

        $highlight = $param['count'] % 2;

        foreach ($detailsStru as $stru) {

            $columnCount++;

            switch ($columnCount) {
                case 1:
                case 2:
                    $addRow[$columnCount] = $stru['width'];
                break;

                default:
                    $addRow[3] = getDefault($addRow[3], 0);
                    $addRow[3] += $stru['width'];
                break;
            }

            $field = getDefault($stru['field']);

            if ($field) {

                $prefix = getDefault($stru['currency']) ? $currency . ' ' : NULL;

                if ( in_array($param['uom'], $this->rcvUOM)
                        && count($rcvItems) === 1
                        && $type === 'RECEIVING'
                        && getDefault($stru['desc'])) {

                    $setDate = implode(',' , array_column($rcvResult, 'date'));

                    $detail =  'Container# ' . $param['container'] .
                               ' Date: ' . $setDate;

                } else if ( $param['uom'] === 'ORDER'
                        && count($ordItems) === 1
                        && $type === 'ORD_PROC'
                        && getDefault($stru['desc'])) {

                    $clientOrder = implode(',' , array_column($ordResult, 'clientordernumber'));

                    $detail = 'Order# ' .  $clientOrder;
                } else if ($type === 'STORAGE' 
                        && getDefault($stru['desc'])) {
                    $detail = $storRange;
                }

                $text = $prefix . $values[$field];

            } else {
                $text = $param['count'];
            }

            pdf::myMultiCell($pdf, $stru['width'], $rowHeight, $text, 0,
                    $stru['align'], 1, $highlight);
        }

        if ($detail) {

            $pdf->Ln();

            pdf::myMultiCell($pdf, $addRow[1], $rowHeight, '', 0, 'C', 1,
                    $highlight);

            pdf::myMultiCell($pdf, $addRow[2], $rowHeight, $detail, 0, 'L', 1,
                    $highlight);

            pdf::myMultiCell($pdf, $addRow[3], $rowHeight, '', 0, 'C', 1,
                    $highlight);

            $rowCount++;
        }
 
        return $rowCount;
    }
    
    /*
    ****************************************************************************
    */
     
    function displayContact($pdf, $rowCount)
    {
        $pageLength = $this->pdfPortraitPageLength;
        $rowHeight = $this->pdfRowHeight;
        $fontSize = $this->pdfFontSize;
        $leftMargin = $this->pdfLeftMargin;
        $pageWidth = $this->pdfPortraitPageWidth;

        $columnWidth = floor(($pageWidth - $leftMargin) / 2);

        $margin = $pageWidth - $columnWidth * 2;
        
        $pdf->Ln();

        $pdf->SetFont('helvetica', 'B', $fontSize);
        $pdf->SetTextColor(0, 0, 128);
        
        $contact = 'If you have any questions about this invoice,please contact';
        $make = 'Make all checks payable to';
        
        pdf::myMultiCell($pdf, $columnWidth, $rowHeight, $contact, 0, 'L', 1);
        pdf::myMultiCell($pdf, $margin, $rowHeight, NULL, 0, 'L', 1);
        pdf::myMultiCell($pdf, $columnWidth, $rowHeight, $make, 0, 'R', 1);
 
        $pdf->Ln();
   
        $rowCount++;
        
        $count = 1;
        
        foreach ($this->pdfContactAddress as $value) {
            
           $pdf->SetFont('helvetica', '', $fontSize);

            pdf::myMultiCell($pdf, $columnWidth, $rowHeight, $value, 0, 'C');
            pdf::myMultiCell($pdf, $margin, $rowHeight, NULL, 0, 'L', 1);
            
            if ($count === 1) {
                pdf::myMultiCell($pdf, $columnWidth, $rowHeight, $this->companyName, 0, 'R', 1);
            } else {
                pdf::myMultiCell($pdf, $columnWidth, $rowHeight, NULL, 0, 'R', 1);
            }
            
            $count++;
            
            $pdf->Ln();
            
            $rowCount++;
        }
        
        $pdf->SetFont('helvetica', 'B', $fontSize);
            
        $thank = 'Thank You For Your Business!';
        
        pdf::myMultiCell($pdf, $pageWidth, $rowHeight, $thank, 0, 'C', 1);
        
        $pdf->Ln();
        
        $rowCount++;
 
        $pdf->SetTextColor(0, 0, 0);
    
        return $rowCount;
    }
    
    /*
    ****************************************************************************
    */
     
    function displayDetach($pdf, $params, $rowCount)
    {
       
        $rowHeight = $this->pdfRowHeight;
        $fontSize = $this->pdfFontSize;
        $leftMargin = $this->pdfLeftMargin;
        $pageWidth = $this->pdfPortraitPageWidth;
        
        $invoiceNo = $params['invoiceNo'];
        
        $formattedInvoiceNo =
                str_pad($invoiceNo, $this->pdfInvoiceNumberWidth, '0', STR_PAD_LEFT);
        
        $custRef = $params['invoiceHeader']['cust_ref'];
               
        $columnWidth = floor(($pageWidth - $leftMargin) / 2);
        
        $margin = $pageWidth - $columnWidth * 2;
        
        $valueWidth = 30;
        
        $detachTitle = [
            'DATE' => NULL,
            'Invoice#' => $formattedInvoiceNo,
            'Customer ID' => $custRef,
        ];
        
        $pdf->Ln();

        $pdf->SetFont('helvetica', 'B', $fontSize);
        
        $pdf->SetLineStyle([
                    'width' => 0.5, 
                    'cap' => 'square', 
                    'join' => 'miter', 
                    'dash' => 3, 
                    'color' => [0,0,0]
        ]);
        
        $pdf->SetTextColor(0, 0, 0);

        $detach = 'Please detach the portion below and return it with your payment.';
        
        pdf::myMultiCell($pdf, $columnWidth, $rowHeight, $detach, 0, 'L', 1);
        
        $pdf->Ln();
        $pdf->Ln();
        
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        
        $pdf->SetXY($leftMargin, $y);

        $pdf->Line($x, $y, $x + $pageWidth, $y);
        
        $pdf->Ln();

        $pdf->SetFont('helvetica', 'B', $fontSize);
         
        pdf::myMultiCell($pdf, $pageWidth, $rowHeight + 5, 'REMITTANCE', 0, 'C', 1);

        $pdf->Ln();
  
        $pdf->SetFont('helvetica', '', $fontSize);

        $y = $pdf->GetY();
        
        $rowCount += 3;

       
        foreach ($this->pdContactNumber as $row) {
           pdf::myMultiCell($pdf, $columnWidth, $rowHeight, $row, 0, 'L', 1);
           $pdf->Ln();
           $rowCount++;
        }
 
        $pdf->SetFont('helvetica', '', $fontSize);
        
        $pdf->SetLineStyle([
                    'width' => 0.1, 
                    'cap' => 'square', 
                    'join' => 'miter', 
                    'dash' => 0, 
                    'color' => [0,0,0]
        ]);
       

        foreach ($detachTitle as $caption => $value) {

            $pdf->SetXY($columnWidth - $margin, $y);

            $text = $caption . CHR(32);
            pdf::myMultiCell($pdf, $columnWidth, $rowHeight, $text, 0, 'R');
            pdf::myMultiCell($pdf, $valueWidth, $rowHeight, $value, 1, 'L');
            
            $y += $rowHeight;
        }

        $y += $rowHeight;
        $pdf->SetXY($columnWidth - $margin, $y);
        pdf::myMultiCell($pdf, $columnWidth, $rowHeight, NULL, 0, 'R');
        pdf::myMultiCell($pdf, $valueWidth, $rowHeight, NULL, 0, 'L');
        
        $y += $rowHeight;
        $pdf->SetXY($columnWidth - $margin, $y);
        pdf::myMultiCell($pdf, $columnWidth, $rowHeight, 'AMOUNT ENCLOSED', 0, 'R');
        pdf::myMultiCell($pdf, $valueWidth, $rowHeight, NULL, 1, 'L');
 
        $this->displayPageFooter($pdf, $rowCount + $this->pdfInvoiceFooterHeight);

    }
    
    
    /*
    ****************************************************************************
    */ 
}
