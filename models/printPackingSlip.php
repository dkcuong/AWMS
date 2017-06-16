<?php

use common\pdf;

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

class model extends base 
{

    public $orderInfo = [];
    public $upcInfo = [];

    function getPrintParam($url)
    {
        $params = explode('/', $url);

        $printParams = [];

        foreach ($params as $key => $value) {
            if ($key % 2) {
                $printParams[$param] = $value;
            } else {
                $param = $value;
            }
        }

        return $printParams;
    }

    /*
    ****************************************************************************
    */

    function getPackingSlip($params)
    {
        $where = NULL;

        $queryParams = [];

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'batch':
                    $field = 'order_batch';
                    $queryParams[] = $value;
                    break;
                case 'order':
                    $field = 'SCAN_SELDAT_ORDER_NUMBER';
                    $queryParams[] = $value;
                    break;
                default:
                    $field = NULL;
                    break;
            }

            if ($field) {
                $clause = $where ? ' OR ' : 'WHERE ';
                $where .= $clause . $field . ' = ?';
            }
        }

        $sql = 'SELECT    SCAN_SELDAT_ORDER_NUMBER, 
                          d.imageName, 
                          d.displayName, 
                          n.clientordernumber AS orderId,
                          shipment_id AS PO,
                          order_date AS orderDate,
                          n.carrier AS shippingService,
                          CONCAT_WS(
                              " ", first_name, last_name
                          ) AS fullName, 
                          CONCAT_WS(
                              " ", 
                              shipping_address_street, 
                              shipping_address_street_cont
                          ) AS streetAddress,
                          CONCAT_WS(
                              " ", 
                              shipping_city, 
                              shipping_state, 
                              shipping_postal_code
                          ) AS cityAddress,
                          shipping_country_name,
                          customer_phone_number
                FROM      online_orders oo
                JOIN      neworder n 
                        ON n.scanordernumber = oo.SCAN_SELDAT_ORDER_NUMBER
                JOIN      order_batches b ON b.id = n.order_batch
                JOIN      deal_sites d ON b.dealSiteID = d.id 
                ' . $where . '
                ORDER BY  scanordernumber DESC';

        $this->orderInfo = $this->queryResults($sql, $queryParams);
                
        $sql = 'SELECT    oo.id AS id,
                          SCAN_SELDAT_ORDER_NUMBER, 
                          product_quantity AS quantity,
                          IF (u.id, u.id, "Not Found") AS upcID, 
                          IF (u.id, u.sku, "Not Found") AS sku, 
                          IF (u.id, u.color, "Not Found") AS color, 
                          IF (u.id, u.size, "Not Found") AS size, 
                          IF (u.id, u.upc, "Not Found") AS upc, 
                          CONCAT_WS(
                              " ", product_name, product_description
                          ) AS details,
                          shipment_tracking_id
                FROM      online_orders oo
                JOIN      neworder n ON n.scanordernumber =
                            oo.SCAN_SELDAT_ORDER_NUMBER
                LEFT JOIN upcs u ON u.upc = oo.upc
                ' . $where . '
                ORDER BY  SCAN_SELDAT_ORDER_NUMBER ASC, 
                          shipment_tracking_id ASC,
                          oo.upc ASC, 
                          u.id ASC';

        $this->upcInfo = $this->queryResults($sql, $queryParams);
    }

    /*
    ****************************************************************************
    */
    
    function displayPackingSlip()
    {
        $pdf = new \TCPDF('P', 'mm', 'Letter', TRUE, 'UTF-8', FALSE);

        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);  
        $pdf->SetAutoPageBreak(TRUE, 0);     
        $pdf->SetLeftMargin(10);
        $pdf->setCellPaddings(0, 0, 0, 0 );  

        $pdf->AddPage();
        
        $appURL = appConfig::getAppURL();
        $imageDir = $appURL . '/custom/images/dealsitelogos/';

        $orderCount = 0;
        
        if ($this->orderInfo) {
            foreach ($this->orderInfo as $key => $order) {
                
                $orderCount && $pdf->AddPage();
               
                $orderCount++;
                
                $logo = $imageDir.$order['imageName'].'/logo.png';
               
                if(getimagesize($logo)) {
                    $pdf->Image($logo, '', '', '', '', '', '', '', FALSE, 300, 
                            'R', FALSE, FALSE, 0, FALSE, FALSE, FALSE);
                    $pdf->Ln(40);
                }

                $pdf->SetFont('helvetica', '', 12);

                pdf::myMultiCell($pdf, 160, 10, 'Ship To: ', 0, 'L');
                
                $pdf->Ln(0);

                $shippingCountry = $order['shipping_country_name'] ? 
                        "\n" . $order['shipping_country_name'] : NULL;
                
                $customerPhone = $order['customer_phone_number'] ? 
                        "\n" . $order['customer_phone_number'] : NULL;
                
                $shipTo = "\n" . $order['fullName']
                    . "\n" . $order['streetAddress']
                    . "\n" . $order['cityAddress']
                    . $shippingCountry
                    . $customerPhone . "\n\n";

                $pdf->SetFont('helvetica', 'B', 14);

                pdf::myMultiCell($pdf, 160, 30, $shipTo, 0, 'L');
                
                $pdf->Ln();

                $orderInfo = 'Order #: ' . $order['orderId']
                    . "\n" . 'PO #: ' . $order['PO']
                    . "\n" . 'Order Date: ' . $order['orderDate']
                    . "\n" . 'Shipping Service: ' . $order['shippingService']
                    . "\n" . $key; 

                $pdf->SetFont('helvetica', '', 11);

                pdf::myMultiCell($pdf, 160, 30, $orderInfo, 0, 'L');
                
                $pdf->Ln();

                $pdf->write1DBarcode($key, 'C128', '', '', 60, 16, 0.4, '', 'N');

                pdf::myMultiCell($pdf, 5, 5, '', 0);
                
                $pdf->Ln();

                $this->displayTable($pdf, $key);
            }
        }

        $pdf->Output('pdf','I');
    }

    /*
    ****************************************************************************
    */
    
    function displayTable($pdf, $key)
    {
        $count = 0;
        
        $headers = [
            'Quantity', 
            'UPC ID', 
            'UPC', 
            'Style' . "\n" . 'Color' . "\n" . 'Size', 
            'Product Details', 
            'Tracking Number'
        ];

        $pdf->SetFont('helvetica', 'B', 11);
        
        foreach ($this->upcInfo as $upc) {
            if ($key == $upc['SCAN_SELDAT_ORDER_NUMBER']) {
                
                $count || $this->displayHeader($pdf, $headers);
                
                $y = $pdf->GetY();
                
                if ($y > 240) {
                    $pdf->AddPage();
                }
                
                $pdf->Ln();
                
                $values = [
                    $upc['quantity'], 
                    $upc['upcID'], 
                    $upc['upc'], 
                    $upc['sku'] . "\n" . $upc['color'] . "\n" . $upc['size'], 
                    $upc['details'],
                    $upc['shipment_tracking_id']
                ];

                $this->displayTableRows($values, $pdf);

                $count++;
                
            } else {
                if ($count > 0) {
                    return;
                }
            }
        }
    }
 
    /*
    ****************************************************************************
    */
    
    function displayTableRows($values, $pdf)
    {
        $columnCount = 0;
        
        foreach ($values as  $value) {

            $width = $this->getColumnWidth(++$columnCount);

            $pdf->SetFont('helvetica', '', 10);

            if ($columnCount == 6 && $value) {

                //Get current write position.
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $style = [
                    'text' => $value,
                    'padding' => 1,
                    'fontsize' => 10,
                    'label' => $value,
                ];
                $pdf->write1DBarcode($value, 'C128', $x + 2, '', 56, 10, 0.4, 
                        $style, 'T');

                //Reset X,Y so wrapping cell wraps around the barcode's cell.
                $pdf->SetXY($x,$y);

                $value = NULL;
            }

            pdf::myMultiCell($pdf, $width, 14, $value);
        }
    }
 
    /*
    ****************************************************************************
    */
    
    function displayHeader($pdf, $headers)
    {
        $columnCount = 1;

        foreach ($headers as $header) {

            $width = $this->getColumnWidth($columnCount++);

            pdf::myMultiCell($pdf, $width, 16, $header);
        }                                
    }
 
    /*
    ****************************************************************************
    */
    
    function getColumnWidth($columnCount)
    {
        $width = 0;
        
        switch ($columnCount++) {
            case 1:
            case 2:
                $width = 17;
                break;
            case 3:
                $width = 30;
                break;
            case 4:
            case 5:
                $width = 35;
                break;
            default:
                $width = 60;
                break;
        }

        return $width;
    }
 
    /*
    ****************************************************************************
    */
    
}