<?php

namespace orders;

use models\config;
use tables\users\access;

class lading
{
    const COMPANY_NAME = 'C/O SELDAT INC, ';

    const ROW_HEIGHT = 5;
    const FONT_SIZE = 9;
    const PAGE_WIDTH = 192;
    const PAGE_HEIGHT = 52;
    const LEFT_CAPTION_WIDTH = 27;
    const LEFT_TEXT_WIDTH = 69;
    const RIGHT_CAPTION_WIDTH = 42;
    const RIGHT_TEXT_WIDTH = 54;

    const CARRIER_ORDER_WIDTH = 30;
    const CARRIER_PKGS_WIDTH = 15;
    const CARRIER_UNIT_WIDTH = 15;
    const CARRIER_WEIGHT_WIDTH = 15;
    const CARRIER_DEPT_WIDTH = 15;
    const CARRIER_PLTS_WIDTH = 13;
    const CARRIER_PICK_TICKET_WIDTH = 24;
    const CARRIER_REF_WIDTH = 18;

    const CUSTOMER_QUANTITY_WIDTH = 15;
    const CUSTOMER_TYPE_WIDTH = 15;
    const CUSTOMER_WEIGHT_WIDTH = 18;
    const CUSTOMER_HM_WIDTH = 10;
    const CUSTOMER_NMFC_WIDTH = 15;
    const CUSTOMER_CLASS_WIDTH = 15;

    const CARRIER_HEADER_HEIGHT = 4;

    const LADING_HEADER_HEIGHT = 30;
    const LADING_FOOTER_HEIGHT = 13;

    static $pdf;
    static $shippingInfoWidth;
    static $orderCount;

    /*
    ****************************************************************************
    */

    static function generateOutput($orderResult, $orderNumbers, $app)
    {
        $carrierResults = self::getCarrierResults($orderNumbers, $app);
        $customerResults = self::getCustomerResults($orderNumbers, $app);

        self::$pdf->AddPage();

        self::$pdf->setStoredAttr('height', self::ROW_HEIGHT);

        self::ladingHeader(1);

        self::$pdf->setStoredAttr('width', self::PAGE_WIDTH / 2);
        self::$pdf->setStoredAttr('align', 'C');
        self::makeOutputTitleText($orderResult);

        self::$pdf->setStoredAttr('width', self::PAGE_WIDTH);

        self::carrierInfo($carrierResults, $orderNumbers);
        self::customerInfo($orderResult, $customerResults, $orderNumbers);

        self::makeOutputTableCell($orderResult);
    }

    /*
    ****************************************************************************
    */

    static function makeOutputTitleText($orderResult)
    {
        self::sectionTitle([
            'text' => 'SHIP FROM',
            'fill' => TRUE,
        ]);

        self::$pdf->htmlCell([
            'text' => 'Bill of Lading Number:',
            'align' => 'L',
            'width' => 45,
            'border' => '',
        ]);

        self::$pdf->SetFont('helvetica', 'B', self::FONT_SIZE);

        self::$pdf->htmlCell([
            'text' => $orderResult['bolLabel'],
            'align' => 'L',
            'width' => 51,
            'border' => 'R',
        ]);

        self::$pdf->Ln();

        self::titleText([
            'caption' => 'Name:',
            'text' => $orderResult['vendorName'],
        ]);

        $padding = 30;

        self::titleText([
            'text' => '',
            'textBarcode' => TRUE,
            'captionWidth' => $padding,
            'textWidth' => self::PAGE_WIDTH / 2 - $padding * 2,
            'extraTextWidth' => $padding,
            'textBorder' => 0,
            'extraTextBorder' => 'R',
        ]);

        self::$pdf->Ln();

        self::titleText([
            'rowAmount' => 1,
            'caption' => 'Address:',
            'textBorder' => 'R',
            'text' => self::COMPANY_NAME .' '. $orderResult['address'],
        ]);

        self::titleText([
            'rowAmount' => 1,
            'caption' => 'CARRIER NAME: ',
            'text' => $orderResult['carrierName'],
            'captionWidth' => self::RIGHT_CAPTION_WIDTH,
            'textWidth' => self::RIGHT_TEXT_WIDTH,
            'captionBorder' => 'LT',
            'textBorder' => 'RT',
        ]);

        self::$pdf->Ln();

        self::titleText([

            'caption' => 'City/State/Zip:',
            'text' => self::cityStateZIP($orderResult['city'],
                $orderResult['state'], $orderResult['zip']),
        ]);

        self::titleText([
            'caption' => '',
            'text' => '',
            'captionWidth' => self::RIGHT_CAPTION_WIDTH,
            'textWidth' => self::RIGHT_TEXT_WIDTH,
        ]);

        self::$pdf->Ln();

        self::$pdf->Image(self::getImages('cell_image'), '102', '46', '', '3', '', '', '', FALSE, 300, '',
            false, false, 0, false, false, false);
        self::titleText([
            'rowAmount' => 1,
            'caption' => 'SID#:',
            'extraText' => 'FOB:    ',
            'textBorder' => '',
            'textWidth' => self::LEFT_TEXT_WIDTH - 20,
            'extraTextWidth' => 20,
            'extraTextAlign' => 'R',
        ]);

        self::titleText([
            'rowAmount' => 1,
            'caption' => 'Trailer Number:',
            'text' => $orderResult['trailerNumber'],
            'captionWidth' => self::RIGHT_CAPTION_WIDTH,
            'textWidth' => self::RIGHT_TEXT_WIDTH,
        ]);

        self::$pdf->Ln();

        self::sectionTitle([
            'text' => 'SHIP TO',
            'fill' => TRUE,
        ]);

        self::titleText([
            'rowAmount' => 1,
            'caption' => 'Seal Number(s):',
            'captionWidth' => self::RIGHT_CAPTION_WIDTH,
            'textWidth' => self::RIGHT_TEXT_WIDTH,
            'captionBorder' => 'LB',
            'textBorder' => 'RB',
        ]);

        self::$pdf->Ln();

        self::titleText([
            'caption' => 'Name:',
            'text' => $orderResult['shipto'],
        ]);

        self::titleText([
            'caption' => 'SCAC: ',
            'text' => $orderResult['scac'],
            'captionWidth' => self::RIGHT_CAPTION_WIDTH,
            'textWidth' => self::RIGHT_TEXT_WIDTH,
        ]);

        self::$pdf->Ln();

        self::titleText([
            'caption' => 'Address:',
            'text' => $orderResult['shiptoaddress'],
            'rowAmount' => 1,
        ]);

        self::titleText([
            'caption' => 'Pro Number:',
            'text' => $orderResult['proNumber'],
            'rowAmount' => 1,
            'captionWidth' => self::RIGHT_CAPTION_WIDTH,
            'textWidth' => self::RIGHT_TEXT_WIDTH,
        ]);

        self::$pdf->Ln();

        self::titleText([
            'caption' => 'City/State/Zip:',
            'text' => $orderResult['shiptocity'],
            'textWidth' => self::LEFT_TEXT_WIDTH - 20,
            'textBorder' => 0,
            'extraTextWidth' => 20,
            'extraTextAlign' => 'R',
        ]);

        self::titleText([
            'text' => '',
            'border' => 'LRTB',
        ]);

        self::$pdf->Ln();
        self::$pdf->Image(self::getImages('cell_image'), '102', '83.5', '', '3', '', '', '', FALSE, 300, '',
            false, false, 0, false, false, false);
        self::titleText([
            'caption' => 'TEL#: ',
            'extraText' => 'FOB:    ',
            'textWidth' => self::LEFT_TEXT_WIDTH - 20,
            'textBorder' => 0,
            'extraTextWidth' => 20,
            'extraTextAlign' => 'R',
            'border' => 'R',
        ]);

        self::titleText([
            'text' => '',
            'textBarcode' => TRUE,
            'captionWidth' => $padding,
            'textWidth' => self::PAGE_WIDTH / 2 - $padding * 2,
            'extraTextWidth' => $padding,
            'textBorder' => 0,
            'extraTextBorder' => 'R',
            'border' => 'L',
        ]);

        self::$pdf->Ln();

        self::sectionTitle([
            'text' => 'THIRD PARTY FREIGHT CHARGES BILL TO',
            'fill' => TRUE,
        ]);

        self::sectionTitle([
            'border' => 'RB',
        ]);

        self::$pdf->Ln();

        self::titleText([
            'caption' => 'Name:',
            'text' => $orderResult['partyname'],
        ]);

        self::titleText([
            'caption' => 'Freight Charge Terms: (freight charges are prepaid unless marked otherwise)',
            'captionWidth' => 96,
            'textWidth' => 0,
            'fontsize' => 7
        ]);

        self::titleText([
            'caption' => '',
            'text' => '',
            'captionWidth' => 6,
            'textWidth' => 0,
            'fontsize' => 7,
            'border' => 'LRTB'
        ]);

        self::$pdf->Ln();

        self::titleText([
            'caption' => 'Address:',
            'text' => $orderResult['partyaddress'],
            'rowAmount' => 1,
        ]);

        self::titleText([
            'rowAmount' => 1,
        ]);

        self::$pdf->Ln();

        self::titleText([
            'caption' => 'City/State/Zip:',
            'text' => $orderResult['partycity'],
        ]);

        $width = (self::RIGHT_CAPTION_WIDTH + self::RIGHT_TEXT_WIDTH) / 3;
        switch($orderResult['freightchargetermby']) {
            case 'freightchargetermbycollect':
                self::selectOptionImage([
                    'image' => self::getImages('tick_image'),
                    'width' => '147.5',
                    'height' => '113.5'

                ]);
                break;
            case 'freightchargetermbyprepaid':
                self::selectOptionImage([
                'image' => self::getImages('tick_image'),
                'width' => '115',
                'height' => '113.5'
                ]);
                break;
            case 'freightchargetermby3rdparty':
                self::selectOptionImage([
                    'image' => self::getImages('tick_image'),
                    'width' => '178',
                    'height' => '113.5'

                ]);
                break;
            default:
                break;
        }

        self::titleText([
            'caption' => '__ Prepaid',
            'text' => '__ Collect',
            'extraText' => '__ 3rdParty',
            'textBorder' => 0,
            'captionWidth' => $width,
            'textWidth' => $width,
            'extraTextWidth' => $width,
            'captionAlign' => 'C',
            'textAlign' => 'C',
            'extraTextAlign' => 'C',
        ]);

        self::$pdf->Ln();

        self::titleText([
            'caption' => 'SPECIAL INSTRUCTIONS:',
            'captionBorder' => 'LT',
            'textBorder' => 'RT',
            'captionWidth' => 50,
            'textWidth' => 46,
            'fontsize' => 8
        ]);


        $caption = 'Master Bill of Lading with attached underlying Bills of Lading';

        $captionWidth = self::RIGHT_CAPTION_WIDTH + self::RIGHT_TEXT_WIDTH;


        self::$pdf->Image(self::getImages('cell_image'), '119', '123', '', '4', '', '', '', FALSE, 300, '',
            false, false, 0, false, false, false);
        self::titleText([
            'caption' => '',
            'captionBorder' => 'LRT',
            'captionWidth' => 30,
            'textWidth' => 0,
            'align' => 'C',
            'border' => 'LRT'
        ]);

        self::titleText([
            'caption' => $caption,
            'captionBorder' => 'LRT',
            'captionWidth' => $captionWidth - 30,
            'textWidth' => 0,
        ]);

        self::$pdf->Ln();

        self::titleText([
            'caption' => '      ' . $orderResult['specialinstruction'],
            'captionBorder' => 'LRB',
            'captionWidth' => $captionWidth,
            'textWidth' => 0,
        ]);
        self::titleText([
            'caption' => '            (checkbox)',
            'captionBorder' => 'LR',
            'captionWidth' => 30,
            'textWidth' => 0,
            'border' => 'R',
            'fontsize' => 7
        ]);
        self::titleText([
            'caption' => '',
            'captionBorder' => 'LRB',
            'captionWidth' => $captionWidth - 30,
            'textWidth' => 0,
        ]);
        self::$pdf->Ln();

    }

    /*
    ****************************************************************************
    */

    static function makeOutputTableCell($shippingInfo)
    {
        self::tableCell([
            'rowAmount' => 3,
            'text' => '  Where the rate is dependent on value, shippers are '
                . 'required to state specially in writing the agreed or '
                . 'declared value of the property as follows:' . "\n"
                . '  "The agreed or declared value of the property is '
                . 'specially stated by the shipper to be not exceeding '
                . '__________ per __________"',
            'align' => 'L',
        ]);

        switch ($shippingInfo['feetermby']) {
            case 'feetermbycollect':
                self::selectOptionImage([
                    'image' => self::getImages('cell_checked_image'),
                    'width' => '132',
                    'height' => 196 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);

                self::selectOptionImage([
                    'image' => self::getImages('cell_image'),
                    'width' => '152',
                    'height' => 196 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);
                break;
            case 'feetermbyprepaid':
                self::selectOptionImage([
                    'image' => self::getImages('cell_image'),
                    'width' => '132',
                    'height' => 196 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);

                self::selectOptionImage([
                    'image' => self::getImages('cell_checked_image'),
                    'width' => '152',
                    'height' => 196 + (self::$orderCount - 1) * self::ROW_HEIGHT
                ]);
                break;
        }

        self::selectOptionImage([
            'image' => $shippingInfo['acceptablecustomer'] = 'YES' ? self::getImages('cell_checked_image') : self::getImages('cell_image'),
            'width' => '166',
            'height' => 201 + (self::$orderCount - 1) * self::ROW_HEIGHT

        ]);

        self::tableCell([
            'rowAmount' => 3,
            'text' => '  COD Amount $____________________ ' . "\n"
                . '  Fee terms:        Collect        Prepaid' . "\n"
                . '         Customer check acceptable      ',
            'align' => 'L',
        ]);

        self::$pdf->Ln();

        self::tableCell([
            'width' => self::PAGE_WIDTH,
            'text' => ' NOTE: Liability Limitation for loss or damage in '
                . 'this shipment may be applicable. See 49 U.S.C. - '
                . '14706(c)(1)(A) and (B)',
            'align' => 'L',
        ]);

        self::$pdf->Ln();

        self::tableCell([
            'rowAmount' => 3,
            'text' => '  RECEIVED, subject to individually determined rates '
                . 'or contracts that have been agreed upon in writing '
                . 'between the carrier and shipper, otherwise to the '
                . 'rates,classifications and rules that have been '
                . 'established by the carrier and are available to the '
                . 'shipper, on request, and to all applicable states '
                . 'and federal regulations',
            'align' => 'L',
        ]);

        self::tableCell([
            'rowAmount' => 3,
            'text' => '  The carrier shall not make delivery of this '
                . 'shipment without payment of freight and all other '
                . 'lawful charges' . "\n\n"
                . '    _____________________________ Shipper Signature',
            'align' => 'L',
        ]);

        self::$pdf->Ln();

        self::tableCell([
            'rowAmount' => 5,
            'width' => self::PAGE_WIDTH / 3,
            'text' => '  SHIPPER SIGNATURE / DATE' . "\n"
                . '  This is to certify that the above named materials '
                . 'are properly classified, packaged, marked and labeled, '
                . 'and are proper condition for transportation according '
                . 'to the applicable regulations of the DOT.' . "\n\n"
                . '_____________________ / _____________',
            'align' => 'L',
        ]);

        switch ($shippingInfo['trailerloadby']) {
            case 'trailerloadbyshipper':
                self::selectOptionImage([
                    'image' => self::getImages('cell_checked_image'),
                    'width' => '77',
                    'height' => 235.5 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);

                self::selectOptionImage([
                    'image' => self::getImages('cell_image'),
                    'width' => '77',
                    'height' => 240.2 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);
                break;
            case 'trailerloadbydriver':
                self::selectOptionImage([
                    'image' => self::getImages('cell_image'),
                    'width' => '77',
                    'height' => 235.5 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);

                self::selectOptionImage([
                    'image' => self::getImages('cell_checked_image'),
                    'width' => '77',
                    'height' => 240.2 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);
                break;
        }


        self::tableCell([
            'rowAmount' => 5,
            'width' => self::PAGE_WIDTH / 6,
            'text' => '   Trailer Loaded' . "\n\n"
                . '      By Shipper' . "\n"
                . '      By Driver',
            'align' => 'L',
            'border' => 'B',
            'valign' => 'T',
        ]);


        switch ($shippingInfo['trailercountedby']) {
            case 'trailercountedbyshipper':
                self::selectOptionImage([
                    'image' => self::getImages('cell_checked_image'),
                    'width' => '107.5',
                    'height' => 233.5 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);

                self::selectOptionImage([
                    'image' => self::getImages('cell_image'),
                    'width' => '107.5',
                    'height' => 238 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);

                self::selectOptionImage([
                    'image' => self::getImages('cell_image'),
                    'width' => '107.5',
                    'height' => 246 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);
                break;
            case 'trailercountedbydriverpallets':
                self::selectOptionImage([
                    'image' => self::getImages('cell_image'),
                    'width' => '107.5',
                    'height' => 233.5 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);

                self::selectOptionImage([
                    'image' => self::getImages('cell_checked_image'),
                    'width' => '107.5',
                    'height' => 238 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);

                self::selectOptionImage([
                    'image' => self::getImages('cell_image'),
                    'width' => '107.5',
                    'height' => 246 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);
                break;
            case 'trailercountedbydriverpieces':
                self::selectOptionImage([
                    'image' => self::getImages('cell_image'),
                    'width' => '107.5',
                    'height' => 233.5 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);

                self::selectOptionImage([
                    'image' => self::getImages('cell_image'),
                    'width' => '107.5',
                    'height' => 238 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);

                self::selectOptionImage([
                    'image' => self::getImages('cell_checked_image'),
                    'width' => '107.5',
                    'height' => 246 + (self::$orderCount - 1) * self::ROW_HEIGHT

                ]);
                break;
        }

        self::tableCell([
            'rowAmount' => 5,
            'width' => self::PAGE_WIDTH / 6,
            'text' => '   Freight Counted' . "\n\n"
                . '     By Shipper' . "\n"
                . '     By Driver/pallets' . "\n"
                . ' said to contain' . "\n"
                . '     By Driver/Pieces',
            'align' => 'L',
            'border' => 'B',
            'valign' => 'T',
        ]);

        self::$pdf->SetFont('helvetica', '', self::FONT_SIZE);

        self::tableCell([
            'rowAmount' => 5,
            'width' => self::PAGE_WIDTH / 3,
            'text' => '  CARRIER SIGNATURE / PICKUP DATE' . "\n"
                . '  Carrier acknowledges receipt of packaged and '
                . 'required placards. Carrier certifies emergency '
                . 'response information was made available and/or '
                . 'carrier has the U.S. DOT emergency response '
                . 'guidebook or equivalent documentation in the '
                . 'vehicle.' . "\n\n"
                . '_____________________ / _____________',
            'align' => 'L',
        ]);
    }

    /*
    ****************************************************************************
    */

    static function output($app, $pdf, $bolID)
    {
        self::$pdf = $pdf;

        $orderResults = self::getOrderResults([$bolID], $app);
        $orders = self::getOrderNumbers($bolID, $app);
        $orderNumbers = array_keys($orders);

        self::$orderCount = count($orderNumbers);
        self::$pdf->setPrintHeader(FALSE);

        self::generateOutput($orderResults, $orderNumbers, $app);

        return self::$pdf;
    }

    /*
    ****************************************************************************
    */

    static function getOrderNumbers($bolID, $app)
    {
        $sql = 'SELECT scanordernumber
                FROM shipping_orders so
                JOIN neworder n ON n.id = so.orderID
                WHERE bolID = ?
                GROUP BY scanordernumber';

        $result = $app->queryResults($sql, [$bolID]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function getOrderResults($bolID, $app)
    {
        $sql = 'SELECT
                    si.id,
                    si.shiptoname AS shipto,
                    si.shiptoaddress,
                    si.shiptocity,
                    si.partyname,
                    si.partyaddress,
                    si.partycity,
                    si.bolLabel AS bolLabel,
                    si.carriername AS carrierName,
                    si.trailernumber AS trailerNumber,
                    si.sealnumber,
                    si.scac,
                    si.pronumber AS proNumber,
                    si.freightchargetermby,
                    si.specialinstruction,
                    si.feetermby,
                    si.acceptablecustomer,
                    si.trailerloadby,
                    si.trailercountedby,
                    v.vendorName,
                    com.address,
                    com.city,
                    com.state,
                    com.zip,
                    ct.description AS commodityDescription,
                    ct.nmfc AS commondityNmfc,
                    ct.class AS commondityClass
                FROM shipping_info si
                JOIN shipping_orders so ON so.bolid = si.bollabel
                JOIN neworder n ON n.id = so.orderID
                JOIN pick_cartons pc ON pc.orderID = n.id
                JOIN inventory_cartons ca ON pc.cartonID = ca.id
                JOIN inventory_batches b ON ca.batchID = b.id
                JOIN inventory_containers co ON co.recNum = b.recNum
                JOIN vendors v ON v.id = co.vendorID
                JOIN company_address com ON com.id = n.location
                LEFT JOIN commodity ct ON ct.id = si.commodity
                WHERE
                    bolLabel = ?
                GROUP BY bolLabel';


        $result = $app->queryResult($sql, $bolID);

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function getCarrierResults($orderNumbers, $app)
    {
        $qMarks = $app->getQMarkString($orderNumbers);
        $sql = 'SELECT    scanordernumber AS ordernumber,
                          customerordernumber,
                          clientpickticket,
                          deptid,
                          additionalshipperinformation,
                          clientordernumber,
                          SUM(cartonCount) AS pkgs,
                          SUM(cartonUom) AS cartonUnit,
                          SUM(weight) AS weight,
                          COUNT(DISTINCT ca.plate) AS countPlates
                FROM (
                    SELECT    n.scanordernumber,
                              n.customerOrderNumber,
                              n.clientordernumber,
                              n.clientpickticket,
                              n.deptid,
                              n.additionalshipperinformation,
                              upcId,
                              COUNT(ca.id) AS cartonCount,
                              SUM(ca.uom) AS cartonUom,
                              ROUND(COUNT(ca.id) * b.weight, 2) AS weight,
                              ca.plate AS plate
                    FROM      inventory_cartons ca
                    JOIN      inventory_batches b ON b.id = ca.batchId
                    JOIN      neworder n ON n.id = ca.orderId
                    WHERE     n.scanordernumber IN ('. $qMarks .')
                    GROUP BY  n.scanordernumber, upcId
                ) ca
                GROUP BY  scanordernumber';

        $result = $app->queryResults($sql, $orderNumbers);

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function getCustomerResults($orderNumbers, $app)
    {
        $qMarks = $app->getQMarkString($orderNumbers);
        $clause = 'scanordernumber IN (' . $qMarks . ')';
        $params = array_merge($orderNumbers, $orderNumbers);

        $sql = 'SELECT    ca.id,
                          ca.scanordernumber AS ordernumber,
                          upc,
                          1 AS handlingUnitQty,
                          "PLT" AS handlingUnitType,
                          SUM(cartonCount) AS packageQty,
                          "CTNS" AS packageType,
                          SUM(weight) AS weight,
                          ca.description AS commondityDescription,
                          ca.nmfc AS commondityNmfc,
                          ca.class AS commondityClass
                FROM (
                    SELECT    ca.id,
                              scanordernumber,
                              ca.orderId,
                              upcId,
                              COUNT(ca.id) AS cartonCount,
                              ROUND(COUNT(ca.id) * b.weight, 2) AS weight,
                              com.description,
                              com.nmfc,
                              com.class
                    FROM      inventory_cartons ca
                    JOIN      inventory_batches b ON b.id = ca.batchId
                    JOIN      neworder n ON n.id = ca.orderId
                    JOIN      shipping_orders so ON so.orderID = n.id
                    JOIN      shipping_info si ON si.bolLabel = so.bolID
                    JOIN      commodity com ON com.id = si.commodity
                    WHERE     so.active AND ' . $clause . '
                    GROUP BY  ca.orderId, b.upcId
                ) ca
                JOIN upcs u ON u.id = ca.upcId
                JOIN (
                    SELECT    scanordernumber,
                              upcId,
                              COUNT(DISTINCT ca.plate) AS plate
                    FROM (
                        SELECT DISTINCT
                                  scanordernumber,
                                  upcId,
                                  locId,
                                  COUNT(DISTINCT ca.plate) AS plate
                        FROM      inventory_cartons ca
                        JOIN      inventory_batches b ON b.id = ca.batchId
                        JOIN      neworder n ON n.id = ca.orderId
                        WHERE     ' . $clause . '
                        GROUP BY  n.scanordernumber, locId, upcId
                    ) AS ca
                    GROUP BY scanordernumber, upcId
                ) ica ON ica.scanordernumber = ca.scanordernumber
                WHERE ica.upcID = ca.upcID
                GROUP BY  ca.scanordernumber, upc';

        $result = $app->queryResults($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function cityStateZIP($city, $state, $zip)
    {
        $stateZip = trim($state . ' ' . $zip);
        $comma = $city && $stateZip ? ', ' : NULL;
        $result = $city . $comma . $stateZip;

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function makeTitleText($data)
    {
        $rowAmount = getDefault($data['rowAmount'], 2);
        $textWidth = getDefault($data['textWidth'], self::LEFT_TEXT_WIDTH);
        $text = getDefault($data['text'], '');
        $textBorder = getDefault($data['textBorder'], 'R');
        $textAlign = getDefault($data['textAlign'], 'L');
        $textBarcode = getDefault($data['textBarcode']);

        self::$pdf->SetFont('helvetica', 'I', self::FONT_SIZE);

        if ($textBarcode && $text) {
            $height = self::ROW_HEIGHT * $rowAmount - 1;

            $style = [
                'stretch' => TRUE,
            ];

            self::$pdf->write1DBarcode($text, 'C128', '', '', $textWidth,
                $height, 0.6, $style, 'T');
        } else {
            self::tableCell([
                'rowAmount' => $rowAmount,
                'width' => $textWidth,
                'text' => $text,
                'border' => $textBorder,
                'align' => $textAlign,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    static function titleText($data = [])
    {
        $rowAmount = getDefault($data['rowAmount'], 2);

        $captionWidth =
            getDefault($data['captionWidth'], self::LEFT_CAPTION_WIDTH);

        $textWidth = getDefault($data['textWidth'], self::LEFT_TEXT_WIDTH);
        $caption = getDefault($data['caption'], '');
        $captionBorder = getDefault($data['captionBorder'], 'L');
        $captionAlign = getDefault($data['captionAlign'], 'L');
        $strenght = getDefault($data['strenght'], '');
        $fontsize = getDefault($data['fontsize'], self::FONT_SIZE);


        self::$pdf->SetFont('helvetica', $strenght, $fontsize);

        self::tableCell([
            'rowAmount' => $rowAmount,
            'width' => $captionWidth,
            'text' => $caption,
            'border' => $captionBorder,
            'align' => $captionAlign,
        ]);

        if ($textWidth) {

            self::makeTitleText($data);

            self::makeExtraTextWidth($data);
        }
    }

    /*
    ****************************************************************************
    */

    static function makeExtraTextWidth($data)
    {
        $rowAmount = getDefault($data['rowAmount'], 2);
        $extraTextWidth = getDefault($data['extraTextWidth'], 0);
        $extraText = getDefault($data['extraText'], '');
        $extraTextBorder = getDefault($data['extraTextBorder'], 'R');
        $extraTextAlign = getDefault($data['extraTextAlign'], 'R');

        if ($extraTextWidth) {
            self::tableCell([
                'rowAmount' => $rowAmount,
                'width' => $extraTextWidth,
                'text' => $extraText,
                'border' => $extraTextBorder,
                'align' => $extraTextAlign,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    static function sectionTitle($data)
    {
        $text = getDefault($data['text'], '');
        $fill = getDefault($data['fill']);
        $border = getDefault($data['border'], 1);
        $align = getDefault($data['align'], 'C');

        if ($fill) {
            self::$pdf->SetFillColor(0, 0, 0);
            self::$pdf->SetTextColor(255, 255, 255);
        }

        self::$pdf->htmlCell([
            'text' => $text,
            'fill' => $fill,
            'border' => $border,
            'align' => $align,
        ]);

        self::$pdf->SetTextColor(0, 0, 0);
    }

    /*
    ****************************************************************************
    */

    static function tableCell($data)
    {
        $width = getDefault($data['width'], self::PAGE_WIDTH / 2);
        $rowAmount = getDefault($data['rowAmount'], 1);
        $text = getDefault($data['text'], '');
        $border = getDefault($data['border'], 1);
        $align = getDefault($data['align'], 'C');
        $fill = getDefault($data['fill']);
        $valign = getDefault($data['valign'], 'M');

        if ($fill) {
            self::$pdf->SetFillColor(85, 85, 85);
        }

        self::$pdf->htmlMultiCell([
            'width' => $width,
            'height' => self::ROW_HEIGHT * $rowAmount,
            'text' => $text,
            'border' => $border,
            'align' => $align,
            'fill' => $fill,
            'reseth' => TRUE,
            'maxh' => self::ROW_HEIGHT * $rowAmount,
            'valign' => $valign,
            'fitcell' => TRUE,
        ]);
    }

    /*
    ****************************************************************************
    */

    static function makeCarrierInfoHeader()
    {
        self::$shippingInfoWidth = self::PAGE_WIDTH - self::CARRIER_ORDER_WIDTH
            - self::CARRIER_PKGS_WIDTH - self::CARRIER_UNIT_WIDTH
            - self::CARRIER_WEIGHT_WIDTH - self::CARRIER_DEPT_WIDTH
            - self::CARRIER_PLTS_WIDTH - self::CARRIER_PICK_TICKET_WIDTH
            - self::CARRIER_REF_WIDTH;

        self::sectionTitle([
            'text' => 'CUSTOMER ORDER INFORMATION',
            'fill' => TRUE,
            'rowAmount' => 2,
        ]);

        self::$pdf->Ln();

        self::tableCell([
            'width' => self::CARRIER_ORDER_WIDTH,
            'text' => 'PO#',
            'border' => 'LRT',
        ]);

        self::tableCell([
            'width' => self::CARRIER_PKGS_WIDTH,
            'text' => '# PKGS',
            'border' => 'LRT',
        ]);

        self::tableCell([
            'width' => self::CARRIER_UNIT_WIDTH,
            'text' => 'UNITS',
            'border' => 'LRT',
        ]);

        self::tableCell([
            'width' => self::CARRIER_WEIGHT_WIDTH,
            'text' => 'WEIGHT',
            'border' => 'LRT',
        ]);
        self::tableCell([
            'width' => self::CARRIER_DEPT_WIDTH,
            'text' => 'DEPT #',
            'border' => 'LRT',
        ]);
        self::tableCell([
            'width' => self::CARRIER_PLTS_WIDTH,
            'text' => 'PLTS',
            'border' => 'LRT',
        ]);
        self::tableCell([
            'width' => self::CARRIER_PICK_TICKET_WIDTH,
            'text' => 'PICK TICKET',
            'border' => 'LRT',
        ]);
        self::tableCell([
            'width' => self::CARRIER_REF_WIDTH,
            'text' => 'TLI REF #',
            'border' => 'LRT',
        ]);
        self::tableCell([
            'width' => self::$shippingInfoWidth,
            'text' => 'ADDITIONAL SHIPPER INFO',
            'border' => 'LRT',
        ]);

        self::$pdf->Ln();
    }

    /*
    ****************************************************************************
    */

    static function makeCarrierInfoBottom($params)
    {
        $totalPkgs = $params['totalPkgs'];
        $totalWeight = $params['totalWeight'];
        $totalUnits = $params['totalUnit'];
        $shippingInfoWidth = $params['shippingInfoWidth'];

        self::tableCell([
            'width' => self::CARRIER_ORDER_WIDTH,
            'text' => 'GRAND TOTAL',
            'align' => 'L',
        ]);

        self::tableCell([
            'width' => self::CARRIER_PKGS_WIDTH,
            'text' => $totalPkgs,
        ]);

        self::tableCell([
            'width' => self::CARRIER_PKGS_WIDTH,
            'text' => $totalUnits,
        ]);

        self::tableCell([
            'width' => self::CARRIER_WEIGHT_WIDTH,
            'text' => number_format($totalWeight, 2, '.', ','),
        ]);

        self::tableCell([
            'width' => self::CARRIER_DEPT_WIDTH
                + self::CARRIER_PICK_TICKET_WIDTH + self::CARRIER_REF_WIDTH
                + self::CARRIER_PLTS_WIDTH + self::$shippingInfoWidth,
            'fill' => TRUE,
        ]);

        self::$pdf->Ln();
    }

    /*
    ****************************************************************************
    */

    static function carrierInfo($carrierResults, $orderNumbers)
    {
        $totalWeight = $totalPkgs = $totalUnit = 0;

        self::makeCarrierInfoHeader();
        foreach ($orderNumbers as $orderNumber) {
            foreach ($carrierResults as $key => $result) {
                if ($key != $orderNumber) {
                    continue;
                }
                $totalPkgs += $result['pkgs'];
                $totalWeight += $result['weight'];
                $totalUnit += $result['cartonUnit'];

                self::makeCarrierInfoBody($result, self::$shippingInfoWidth);
            }
        }
        self::makeCarrierInfoBottom([
            'shippingInfoWidth' => self::$shippingInfoWidth,
            'totalPkgs' => $totalPkgs,
            'totalWeight' => $totalWeight,
            'totalUnit' => $totalUnit,
        ]);

    }

    /*
    ****************************************************************************
    */

    static function makeCarrierInfoBody($result, $shippingInfoWidth)
    {
        self::$pdf->SetFont('helvetica', '', self::FONT_SIZE);
        self::tableCell([
            'width' => self::CARRIER_ORDER_WIDTH,
            'text' => $result['clientordernumber'],
            'align' => 'L',
        ]);

        self::tableCell([
            'width' => self::CARRIER_UNIT_WIDTH,
            'text' => $result['pkgs'],
        ]);

        self::tableCell([
            'width' => self::CARRIER_PKGS_WIDTH,
            'text' => $result['cartonUnit'],
        ]);

        self::tableCell([
            'width' => self::CARRIER_WEIGHT_WIDTH,
            'text' => number_format($result['weight'], 2, '.', ','),
        ]);

        self::tableCell([
            'width' => self::CARRIER_DEPT_WIDTH,
            'text' => $result['deptid'],
            'border' => 'LRT',
        ]);
        self::tableCell([
            'width' => self::CARRIER_PLTS_WIDTH,
            'text' => $result['countPlates'],
            'border' => 'LRT',
        ]);
        self::tableCell([
            'width' => self::CARRIER_PICK_TICKET_WIDTH,
            'text' => $result['clientpickticket'],
            'border' => 'LRT',
        ]);
        self::tableCell([
            'width' => self::CARRIER_REF_WIDTH,
            'text' => $result['customerordernumber'],
            'border' => 'LRT',
        ]);
        self::tableCell([
            'width' => $shippingInfoWidth,
            'text' => $result['additionalshipperinformation'],
            'align'=> 'L',
        ]);
        self::$pdf->SetFont('helvetica', '', self::FONT_SIZE);

        self::$pdf->Ln();
    }

    /*
    ****************************************************************************
    */

    static function makeCustomerInfoHeader($descriptionWidth, $page=1)
    {
        if ($page > 1) {

            self::ladingHeader($page);

            self::$pdf->setStoredAttr('width', self::PAGE_WIDTH);
        }

        self::sectionTitle([
            'text' => 'CARRIER INFORMATION',
            'fill' => TRUE,
        ]);

        self::$pdf->Ln();

        $huWidth = self::CUSTOMER_QUANTITY_WIDTH + self::CUSTOMER_TYPE_WIDTH;

        self::tableCell([
            'width' => $huWidth,
            'text' => 'HANDLING UNIT',
        ]);

        self::tableCell([
            'width' => $huWidth,
            'text' => 'PACKAGE',
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_WEIGHT_WIDTH,
            'text' => 'WEIGHT',
            'border' => 'LRT',
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_HM_WIDTH,
            'text' => 'H.M.',
            'border' => 'LRT',
        ]);

        self::tableCell([
            'width' => $descriptionWidth,
            'text' => 'COMMODITY DESCRIPTION',
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_NMFC_WIDTH + self::CUSTOMER_CLASS_WIDTH,
            'text' => 'LTL ONLY',
        ]);

        self::$pdf->Ln();

        self::$pdf->SetFont('helvetica', '', 9);

        self::tableCell([
            'rowAmount' => 2,
            'width' => self::CUSTOMER_QUANTITY_WIDTH,
            'text' => 'QTY',
        ]);

        self::tableCell([
            'rowAmount' => 2,
            'width' => self::CUSTOMER_TYPE_WIDTH,
            'text' => 'TYPE',
        ]);

        self::tableCell([
            'rowAmount' => 2,
            'width' => self::CUSTOMER_QUANTITY_WIDTH,
            'text' => 'QTY',
        ]);

        self::tableCell([
            'rowAmount' => 2,
            'width' => self::CUSTOMER_TYPE_WIDTH,
            'text' => 'TYPE',
        ]);

        self::tableCell([
            'rowAmount' => 2,
            'width' => self::CUSTOMER_WEIGHT_WIDTH,
            'border' => 'LRB',
        ]);

        self::tableCell([
            'rowAmount' => 2,
            'width' => self::CUSTOMER_HM_WIDTH,
            'text' => '(X)',
            'border' => 'LRB',
        ]);

        self::tableCell([
            'rowAmount' => 2,
            'width' => $descriptionWidth,
            'text' => 'Commodities requiring special or additional care or'
                . 'attention in handling or stowing must be so marked and'
                . 'packages as ensure sage transportation with ordinary'
                . 'care.See Section 2(e) of nmfc Item 360',
            'align' => 'L',
        ]);

        self::tableCell([
            'rowAmount' => 2,
            'width' => self::CUSTOMER_NMFC_WIDTH,
            'text' => 'NMFC #',
        ]);

        self::tableCell([
            'rowAmount' => 2,
            'width' => self::CUSTOMER_CLASS_WIDTH,
            'text' => 'CLASS',
        ]);

        self::$pdf->Ln();

        self::$pdf->SetFont('helvetica', '', 11);
    }

    /*
    ****************************************************************************
    */

    static function customerInfo($orderResult, $customerResults, $orderNumbers)
    {
        $quantityWidth = self::CUSTOMER_QUANTITY_WIDTH;
        $typeWidth = Self::CUSTOMER_TYPE_WIDTH;
        $weightWidth = self::CUSTOMER_WEIGHT_WIDTH;
        $hmWidth = self::CUSTOMER_HM_WIDTH;
        $nmfcWidth = self::CUSTOMER_NMFC_WIDTH;
        $classWidth = self::CUSTOMER_CLASS_WIDTH;
        $row = self::LADING_HEADER_HEIGHT;

        $descriptionWidth = self::PAGE_WIDTH - ($quantityWidth + $typeWidth) * 2
                - $weightWidth - $hmWidth - $nmfcWidth - $classWidth;
        self::makeCustomerInfoHeader($descriptionWidth);
        $totalHandlingUnitQty = $totalPackageQty = $totalWeight = 0;

        $orderResults = [];
        $commodityDescription = $orderResult['commodityDescription'];
        $commondityNmfc = $orderResult['commondityNmfc'];
        $commondityClass = $orderResult['commondityClass'];
        foreach ($orderNumbers as $orderNumber) {
            foreach ($customerResults as $result) {

                if ($result['ordernumber'] != $orderNumber) {
                    continue;
                }

                $totalHandlingUnitQty += $result['handlingUnitQty'];
                $totalPackageQty += $result['packageQty'];
                $totalWeight += $result['weight'];

                $orderResults[] = $result;
            }
        }
        self::makeCustomerInfoBody([
            'commondityDescription' => $commodityDescription,
            'commondityNmfc' => $commondityNmfc,
            'commondityClass' => $commondityClass,
            'totalHandlingUnitQty' => $totalHandlingUnitQty,
            'totalPackageQty' => $totalPackageQty,
            'totalWeight' => $totalWeight,
            'descriptionWidth' => $descriptionWidth,
        ]);

        self::makeCustomerInfoBottom([
            'totalHandlingUnitQty' => $totalHandlingUnitQty,
            'totalPackageQty' => $totalPackageQty,
            'totalWeight' => $totalWeight,
            'descriptionWidth' => $descriptionWidth,
        ]);
    }

    /*
    ****************************************************************************
    */

    static function makeCustomerInfoBody($result)
    {
        self::tableCell([
            'width' => self::CUSTOMER_QUANTITY_WIDTH,
            'text' => $result['totalHandlingUnitQty'],
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_TYPE_WIDTH,
            'text' => 'PLTS',
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_QUANTITY_WIDTH,
            'text' => $result['totalPackageQty'],
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_TYPE_WIDTH,
            'text' => 'CTNS',
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_WEIGHT_WIDTH,
            'text' => number_format($result['totalWeight'], 2, '.', ','),
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_HM_WIDTH,
        ]);

        self::tableCell([
            'width' => $result['descriptionWidth'],
            'text' => $result['commondityDescription'],
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_NMFC_WIDTH,
            'text' => $result['commondityNmfc'],
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_CLASS_WIDTH,
            'text' => $result['commondityClass'],
        ]);

        self::$pdf->Ln();
    }

    /*
    ****************************************************************************
    */

    static function makeCustomerInfoBottom($params)
    {
        $totalHandlingUnitQty = $params['totalHandlingUnitQty'];
        $totalPackageQty = $params['totalPackageQty'];
        $totalWeight = $params['totalWeight'];
        $descriptionWidth = $params['descriptionWidth'];

        self::tableCell([
            'width' => self::CUSTOMER_QUANTITY_WIDTH,
            'text' => $totalHandlingUnitQty,
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_TYPE_WIDTH,
            'fill' => TRUE,
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_QUANTITY_WIDTH,
            'text' => $totalPackageQty,
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_TYPE_WIDTH,
            'fill' => TRUE,
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_WEIGHT_WIDTH,
            'text' => number_format($totalWeight, 2, '.', ','),
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_HM_WIDTH,
            'fill' => TRUE,
        ]);

        self::tableCell([
            'width' => $descriptionWidth,
            'text' => 'GRAND TOTAL',
        ]);

        self::tableCell([
            'width' => self::CUSTOMER_NMFC_WIDTH + self::CUSTOMER_CLASS_WIDTH,
            'fill' => TRUE,
        ]);

        self::$pdf->Ln();
    }

    /*
    ****************************************************************************
    */

    static function ladingHeader($page)
    {
        $userID = \access::getUserID();
        $userInfo = \access::getUserInfo([
            'db' => 'users',
            'search' => 'session',
        ]);
        self::$pdf->setStoredAttr('width', self::PAGE_WIDTH / 3);
        self::$pdf->SetFont('helvetica', '', self::FONT_SIZE);
        self::$pdf->htmlCell([
            'text' => 'Date: ' . date('m/d/Y'),
            'align' => 'L',
            'border' => 'LT',
            'fontsize' => 9
        ]);
        self::$pdf->SetFont('helvetica', 'B', self::FONT_SIZE);
        self::$pdf->htmlCell([
            'text' => 'BILL OF LADING ',
            'align' => 'C',
            'border' => 'TB',
        ]);

        self::$pdf->SetFont('helvetica', '', self::FONT_SIZE);
        self::$pdf->htmlCell([
            'text' => 'Page ' . $page,
            'align' => 'R',
            'border' => 'RTB',
        ]);

        self::$pdf->Ln();
    }

    /*
    ****************************************************************************
    */

    static function selectOptionImage($params)
    {
        $dirImage = $params['image'];

        $width = $params['width'];
        $height = $params['height'];
        self::$pdf->Image($dirImage, $width, $height, '', '3', '', '', '', FALSE, 300, '',
            false, false, 0, false, false, false);
    }

    /*
    ****************************************************************************
    */

    static function getImages($key)
    {
        $appURL = \models\config::getAppURL();

        $imgArray = [
            'cell_image' => $appURL . '/custom/images/cell.png',
            'cell_checked_image' => $appURL . '/custom/images/cell-checked.png',
            'tick_image' => $appURL . '/custom/images/tick.png'
        ];
        $imgDir = NULL;
        switch ($key) {
            case 'cell_checked_image':
                $imgDir = $imgArray['cell_checked_image'];
                break;
            case 'tick_image':
                $imgDir = $imgArray['tick_image'];
                break;
            default:
                $imgDir = $imgArray['cell_image'];
                break;
        }
        return $imgDir;
    }

    /*
    ****************************************************************************
    */

    static function getBolLabel($orderNumbers, $app)
    {
        $qMarks = $app->getQMarkString($orderNumbers);

        $sql = 'SELECT    bolLabel
                FROM      shipping_orders so
                JOIN      neworder n ON n.id = so.orderID
                JOIN      shipping_info si ON si.bolLabel = so.bolID
                WHERE     scanordernumber IN ('. $qMarks .')
                GROUP BY  scanordernumber';

        $result = $app->queryResults($sql, $orderNumbers);

        return array_keys($result);
    }

    /*
    ****************************************************************************
    */

    static function displayLadings($app, $orderNumbers)
    {
        $filteredOrders = array_filter($orderNumbers);
        
        $uniqueOrders = array_values($filteredOrders);

        $bolIDs = self::getBolLabel($uniqueOrders, $app);

        $pdf = new \pdf\creator();

        foreach ($bolIDs as $bolID) {
            $pdf = self::output($app, $pdf, $bolID);
        }

        $pdf->output('pdf', 'I');
    }

    /*
    ****************************************************************************
    */

}
