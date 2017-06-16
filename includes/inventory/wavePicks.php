<?php

namespace inventory;

use common\scanner;
use labels\create;
use \tables\locations;
use \common\pdf;
use tables\vendors;

class wavePicks
{
    public $pdf = NULL;

    public $lastIndex = NULL;

    public $reportData = [];

    public $pageCount = 0;

    public $submittedOrders = [];

    public $productErrors = [];

    public $purchaseOrders = [];

    public $printByOrder = FALSE;

    public $pickData = [];

    public $upcData = [];

    public $shippingLane = NULL;

    public $sortedUpcsInfo = [];

    public $batch = NULL;

    public $order = NULL;

    public $customerordernumber = NULL;

    public $upc = NULL;

    public $pieces = 0;

    public $info = [];

    public $reqInfo = [];

    public $rowCount = 0;

    public $upcRow = 0;

    public $printType = NULL;

    public $wavePickType = NULL;

    static $sizing;

    static $upcRowAmount = 6;

    static $pageHeaderHeight = 2;

    static $tableHeaderHeight = 2;

    static $upcHeaderHeight = 2;

    static $rowHeight = 6;

    const MESSAGE_ORDER_UCC_FROM_LINGO = 'We can not print this UCC label from 
                                        AWMS and should be print from Lingo';

    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        $this->app = $app;
    }

    /*
    ****************************************************************************
    */

    function createWavePick($classes, $isTruckOrder)
    {
        $wavePicks = $classes['wavePicks'];
        $pickCartons = $classes['pickCartons'];
        $locations = $classes['locations'];

        $quantity = 0;
        $return = $upcLocations = $upcsInfo = $upcList = $wavePickUpcList =
                $processedCartons = $orderProducts = [];

        // get upc for distribution between cartons

        $ordersNumbers = $this->submittedOrders;

        $batchData = $wavePicks->getBatchesUPCs($ordersNumbers, $isTruckOrder);

        $this->productErrors[] = 'No cartons were found to output<br>Go to '
                . 'Order Check Out and create Pick Tickets for orders:<br>'
                . implode('<br>', $ordersNumbers);

        if (! $batchData) {
            return;
        }

        foreach ($batchData as $data) {

            $upc = $data['upc'];

            $upcInfo[$upc] = getDefault($upcInfo[$upc], 0) + $data['quantity'];
        }

        $orderBatch = new \tables\orderBatches($this->app);

        $isWholeSale = $orderBatch->isWholeSale($this->batch);

        $skipResults = $wavePicks->checkSkipProcessing($ordersNumbers);

        $skipProcessing = $skipResults['skipProcessing'];

        $mezzanineClause = $isWholeSale && ! $isTruckOrder ?
                'NOT isMezzanine' : ' isMezzanine';

        // get free cartons to backup (cartons that are not bound to any order)
        $freeCartons = $skipProcessing ? [] :
            $wavePicks->getFreeCartons($upcInfo, $this->batch, $mezzanineClause);

        // get cartons that are already reserved
        $orderIDsList = array_column($batchData, 'orderID');
        $uniqueOrders = array_unique($orderIDsList);
        $orderIDs = array_values($uniqueOrders);

        $allocatedCartons = ! $orderIDs ?
            NULL : $pickCartons->getAllocatedCartons($orderIDs, $mezzanineClause);

        // allocating cartons

        foreach ($batchData as $upcData) {

            // loop through upc
            $upc = $upcData['upc'];
            $quantity = $upcData['quantity'];
            $upcID = $upcData['upcID'];
            $scanOrderNumber = $upcData['scanOrderNumber'];

            $this->purchaseOrders[$scanOrderNumber] = $upcData['customerordernumber'];

            $orderNumber = $this->printByOrder ? $scanOrderNumber : 0;

            $upcListData = [
                'quantity' => 0,
                'upcID' => $upcID,
                'color' => $upcData['color'],
                'size' => $upcData['size'],
                'sku' => $upcData['sku'],
                'customerordernumber' => $upcData['customerordernumber'],
                'edi' => $upcData['edi'],
                'isPrintUccEdi' => $upcData['isPrintUccEdi']
            ];

            if (! isset($upcList[$upc])) {
                $upcList[$upc] = $upcListData;
            }

            $upcList[$upc]['quantity'] += $quantity;

            if (! isset($wavePickUpcList[$orderNumber][$upc])) {
                $wavePickUpcList[$orderNumber][$upc] = $upcListData;
            }

            $wavePickUpcList[$orderNumber][$upc]['quantity'] += $quantity;

            // loop through cartons that are already allocated
            if ($allocatedCartons) {

                $return = $this->addCartonsToPickTicket([
                    'type' => 'allocated',
                    'cartonArray' => $allocatedCartons,
                    'upcData' => $upcData,
                    'quantity' => $quantity,
                    'upcLocations' => $upcLocations,
                    'upcsInfo' => $upcsInfo,
                    'orderProducts' => $orderProducts,
                    'processedCartons' => $processedCartons,
                ]);

                $quantity = $return['quantity'];
                $upcLocations = $return['upcLocations'];
                $upcsInfo = $return['upcsInfo'];
                $processedCartons = $return['processedCartons'];
                $orderProducts = $return['orderProducts'];
            }

            if ($quantity <= 0) {
                continue;
            }

            // loop through cartons that are not allocated

            $return = $this->addCartonsToPickTicket([
                'type' => 'toAllocate',
                'cartonArray' => $freeCartons,
                'upcData' => $upcData,
                'quantity' => $quantity,
                'upcLocations' => $upcLocations,
                'upcsInfo' => $upcsInfo,
                'orderProducts' => $orderProducts,
                'processedCartons' => $processedCartons,
            ]);

            $freeCartons = $return['cartonArray'];
            $quantity = $return['quantity'];
            $upcLocations = $return['upcLocations'];
            $upcsInfo = $return['upcsInfo'];
            $processedCartons = $return['processedCartons'];
            $orderProducts = $return['orderProducts'];
        }

        if ($orderProducts) {

            $wavePicks->processOrderProducts([
                'orderProducts' => $orderProducts,
                'upcList' => $upcList,
                'isTruckOrder' => $isTruckOrder,
            ]);
        }

        if (getDefault($upcsInfo)) {
            $this->pickData[] = $upcsInfo;
            $this->upcData[] = $wavePickUpcList;
            $this->shippingLane = $locations->getBatchShippingLocation($this->batch);
            $this->sortedUpcsInfo[] = $this->getSortedUpcsInfo($upcsInfo,
                $wavePickUpcList, $mezzanineClause);
            $this->productErrors = [];
        }
    }

    /*
    ****************************************************************************
    */

    function addToPickTicket($param)
    {
        $upcData = $param['upcData'];
        $cartonID = $param['cartonID'];
        $carton = $param['carton'];
        $quantity = $param['quantity'];
        $upcLocations = $param['upcLocations'];
        $upcsInfo = $param['upcsInfo'];
        $processedCartons = $param['processedCartons'];
        $orderProducts = $param['orderProducts'];

        $upc = $upcData['upc'];
        $scanOrderNumber = $upcData['scanOrderNumber'];
        $location = $carton['location'];
        $uom = $carton['uom'];

        $upcsInfo[$scanOrderNumber][$upc][$cartonID] = [
            'cartonID' => $cartonID,
            'uom' => $uom,
            'prefix' => $carton['prefix'],
            'suffix' => $carton['suffix'],
            'location' => $location,
            'orderID' => $upcData['orderID'],
        ];

        $processedCartons[$cartonID] = TRUE;

        $quantity -= $uom;
        $orderProducts[$scanOrderNumber][] = $cartonID;
        $upcLocations[$upc][$location] = TRUE;

        // loop exit if quantity is not greater then 0
        $return = $quantity <= 0;

        return [
            'return' => $return,
            'quantity' => $quantity,
            'upcLocations' => $upcLocations,
            'upcsInfo' => $upcsInfo,
            'processedCartons' => $processedCartons,
            'orderProducts' => $orderProducts
        ];
     }

    /*
    ****************************************************************************
    */

    function addCartonsToPickTicket($param)
    {
        $type = $param['type'];
        $cartonArray = $param['cartonArray'];
        $upcData = $param['upcData'];
        $quantity = $param['quantity'];
        $upcLocations = $param['upcLocations'];
        $upcsInfo = $param['upcsInfo'];
        $orderProducts = $param['orderProducts'];
        $processedCartons = $param['processedCartons'];

        foreach ($cartonArray as $cartonID => $carton) {

            if ($upcData['upcID'] != $carton['upcID']
            || $type == 'allocated' && $upcData['orderID'] != $carton['orderID']
            || isset($processedCartons[$cartonID])) {

                continue;
            }
            $return = $this->addToPickTicket([
                'upcData' => $upcData,
                'cartonID' => $cartonID,
                'carton' => $carton,
                'quantity' => $quantity,
                'upcLocations' => $upcLocations,
                'upcsInfo' => $upcsInfo,
                'processedCartons' => $processedCartons,
                'orderProducts' => $orderProducts,
            ]);

            $quantity = $return['quantity'];
            $upcLocations = $return['upcLocations'];
            $upcsInfo = $return['upcsInfo'];
            $processedCartons = $return['processedCartons'];
            $orderProducts = $return['orderProducts'];

            if ($return['return']) {
                break;
            }
        }

        $processedCartonKeys = array_keys($processedCartons);

        foreach ($processedCartonKeys as $processedCarton) {
            unset($cartonArray[$processedCarton]);
        }

        return [
            'cartonArray' => $cartonArray,
            'quantity' => $quantity,
            'upcLocations' => $upcLocations,
            'upcsInfo' => $upcsInfo,
            'processedCartons' => $processedCartons,
            'orderProducts' => $orderProducts,
        ];
    }

    /*
    ****************************************************************************
    */

    function displayWavePick()
    {
        $this->customerordernumber = NULL;

        $wavePickOrders = [];

        foreach ($this->pickData as $values) {

            $warehouseOrders = array_keys($values);

            $wavePickOrders = array_merge($wavePickOrders, $warehouseOrders);
        }

        $orderNumbers = array_unique($wavePickOrders);

        asort($orderNumbers);

        $orderNumber = 0;

        self::$sizing = $this->sizing();

        if ($this->printByOrder) {

            foreach ($orderNumbers as $orderNumber) {

                $this->order = $orderNumber;

                $this->customerordernumber
                        = getDefault($this->purchaseOrders[$orderNumber], NULL);

                $this->displayPickTickets($orderNumber);
            }
        } else {
            $this->displayPickTickets($orderNumber);
        }
    }

    /*
    ****************************************************************************
    */

    function displayPickTickets($orderNumber)
    {
        $warehouseKeys = array_keys($this->sortedUpcsInfo);

        foreach ($warehouseKeys as $count) {
            $this->displayPickTicket($orderNumber, $count);
        }
    }

    /*
    ****************************************************************************
    */

    function getWavePickData($ucpsData)
    {
        $upcsInfo = $ucpsData;

        foreach ($ucpsData as $warehouseCount => $warehouseData) {
            foreach ($warehouseData as $index => $values) {
                $upcsInfo = $this->getOrderData([
                    'upcsInfo' => $upcsInfo,
                    'values' => $values,
                    'warehouseCount' => $warehouseCount,
                    'index' => $index,
                ]);
            }
        }

        return $upcsInfo;
    }

    /*
    ****************************************************************************
    */

    function getOrderData($data)
    {
        $upcsInfo = $data['upcsInfo'];
        $values = $data['values'];
        $warehouseCount = $data['warehouseCount'];
        $index = $data['index'];

        $pieces = 0;

        foreach ($values['cartons'] as $ucc => $values) {
            if (! isset($values['orderNumber'])) {
                continue;
            }

            if ($values['orderNumber'] == $this->order) {
                $pieces += $values['uom'];
            } else {
                unset($upcsInfo[$warehouseCount][$index]['cartons'][$ucc]);
            }
        }

        if ($pieces) {
            $upcsInfo[$warehouseCount][$index]['pieces'] = $pieces;
        } else {
            unset($upcsInfo[$warehouseCount][$index]);
        }

        return $upcsInfo;
    }

    /*
    ****************************************************************************
    */

    function displayPickTicket($orderNumber, $count)
    {
        $this->lastIndex = NULL;
        $this->reportData = [];
        $this->pageCount = 0;

        if (! isset($this->upcData[$count][$orderNumber])) {
            // an order may not have Mixed Items Cartons
            return;
        }

        $param['upcs'] = $this->upcData;
        $param['upcsInfo'] = $this->sortedUpcsInfo;

        $upcData = $this->upcData[$count][$orderNumber];

        $upcsInfo = $this->handleExcessiveCartons($param['upcsInfo'][$count],
                $upcData, $orderNumber);

        foreach ($upcsInfo as $index => $info) {
            $upcsInfo = $this->getReportData([
                'upcsInfo' => $upcsInfo,
                'param' => $param,
                'index' => $index,
                'info' => $info,
            ]);
        }

        $upcTitleHeight = self::$upcRowAmount + self::$upcHeaderHeight;

        $firstUpcData = reset($upcData);

        $this->customerordernumber = $firstUpcData['customerordernumber'];
        $this->createTypeOrder = $firstUpcData['edi'];

        $this->wavePickPageHeader();

        foreach ($upcsInfo as $index => $info) {

            $upc = $info['upc'];

            if ($upcData[$upc]['quantity'] <= 0) {
                continue;
            }

            $this->upc = $upc = $info['upc'];
            $this->pieces = $info['pieces'];
            $this->info = $info['cartons'];
            $this->customerordernumber =
                $param['upcs'][$count][$orderNumber][$upc]['customerordernumber'];

            $upcData[$upc]['prefix'] = $info['prefix'];
            $upcData[$upc]['suffix'] = $info['suffix'];

            $this->reqInfo = $upcData[$upc];

            if ($this->rowCount + $upcTitleHeight > self::$sizing['page']['rowAmount']) {
                $this->wavePickPageHeader();
            }

            $this->upcHeader($count, $orderNumber);

            $this->displayWavePickData($index);

            $upcData[$upc]['quantity'] -= $info['pieces'];

            $this->lastIndex = $index;
        }
    }

    /*
    ****************************************************************************
    */

    function displayWavePickData($index)
    {
        $rowAmount = self::$sizing['page']['rowAmount'];

        $report = $this->reportData[$index];

        $reportData = $report['data'];

        $groupAmount = count($reportData);

        $groupCount = 1;

        $rowHeight = self::$sizing['page']['rowHeight'];
        $width = self::$sizing['width'];

        foreach ($reportData as $key => $value) {

            $groupLocation = FALSE;
            $splitCarton = $piecesCount = $cartonCount = 0;
            $subRowAmount = 1;

            $lastRow = $groupCount == $groupAmount;

            $cartonLocation = isset($value['availableCartons']);

            if ($cartonLocation) {
                $piecesCount = getDefault($value['availablePieces']);
                $cartonCount = getDefault($value['availableCartons']);
                $groupLocation = getDefault($value['location']);
            }

            if ($lastRow) {
                // table data has less rows than rows required for SKU, COLOR, SIZE
                $subRowAmount = max($subRowAmount, self::$upcRowAmount - $this->upcRow);
            }

            if ($this->rowCount + $subRowAmount > $rowAmount) {
                // page break if next row exceeds page length
                $this->wavePickPageHeader();
            }

            for ($subRow = 1; $subRow <= $subRowAmount; $subRow++) {

                $this->upcRow++;

                $border = 'LR';

                $nextKey = $key + 1;

                if (isset($reportData[$nextKey])) {
                    // getting height of the next group of rows to figure out
                    // wherther the next row will fit current page or not
                    $nextGroupRowAmount = isset($reportData[$nextKey]['uccAmount']) ?
                            $reportData[$nextKey]['uccAmount'] : 1;
                } else {
                    $nextGroupRowAmount = 0;
                }

                $pageHeight = $this->rowCount + $nextGroupRowAmount;

                if ($subRow == $subRowAmount
                && ($lastRow || $pageHeight >= $rowAmount)) {
                    // display bottom line for last rows on a page only
                    $border .= 'B';
                }

                pdf::myMultiCell($this->pdf, $width['quantity'], $rowHeight,
                        NULL, $border);
                pdf::myMultiCell($this->pdf, $width['upcID'], $rowHeight, NULL,
                        $border);

                $text = $this->lastIndex == $index ? NULL : $this->upcDescription();

                pdf::myMultiCell($this->pdf, $width['upc'], $rowHeight, $text,
                        $border,'C', 1);

                $dataRow = $cartonLocation;

                $border = 'LR';

                if (($dataRow || $lastRow) &&
                    ($subRow == $subRowAmount || $subRow == 1)) {

                    $border .= 'B';
                }

                if ($dataRow) {
                    $this->displayCartonData([
                        'subRow' => $subRow,
                        'border' => $border,
                        'splitCarton' => $splitCarton,
                        'piecesCount' => $piecesCount,
                        'cartonCount' => $cartonCount,
                        'groupLocation' => $groupLocation,
                        'dataRow' => $dataRow,
                    ]);
                } else {
                    // backup locations
                    $text = $cartonLocation || $groupCount > $groupAmount ?
                            NULL : $value;

                    $border = $cartonLocation ? $border : 'LBR';

                    pdf::myMultiCell($this->pdf, $width['backup'], $rowHeight,
                            $text, $border);

                    if ($this->wavePickType == 'original') {
                        pdf::myMultiCell($this->pdf, $width['locationPicked'],
                                $rowHeight, NULL, 1);
                        pdf::myMultiCell($this->pdf, $width['quantityPicked'],
                                $rowHeight, NULL, 1);
                    }
                }

                $this->newLine();

                if ($subRow == 1) {
                    // incrementing group counter (a group can have up to 3 rows)
                    $groupCount++;
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    function upcDescription()
    {
        switch ($this->upcRow) {
            case 1:
                $text = 'SKU: ' . $this->reqInfo['sku'];
                break;
            case 2:
                $text = 'COLOR: ' . $this->reqInfo['color'];
                break;
            case 3:
                $text = 'SIZE: ' . $this->reqInfo['size'];
                break;
            case 4:
                $text = 'PO # ' . $this->reqInfo['prefix'];
                break;
            case 5:
                $text = 'Suffix # ' . $this->reqInfo['suffix'];
                break;
            case 6:
                //Purchase order for pick ticket for pickwave
                $text = $this->order ? NULL :
                    'Purchase order # ' . $this->customerordernumber;
                break;
            default:
                $text = NULL;
                break;
        }

        return $text;
    }

    /*
    ****************************************************************************
    */

    function displayCartonData($data)
    {
        $subRow = $data['subRow'];
        $border = $data['border'];
        $piecesCount = $data['piecesCount'];
        $cartonCount = $data['cartonCount'];
        $groupLocation = $data['groupLocation'];
        $dataRow = $data['dataRow'];

        $rowHeight = self::$sizing['page']['rowHeight'];
        $width = self::$sizing['width'];

        $location = $text = NULL;
        $firstRow = $dataRow && $subRow == 1;
        $piecesText = $firstRow ? $piecesCount : NULL;
        $cartonText = $firstRow ? $cartonCount : NULL;

        pdf::myMultiCell($this->pdf, $width['pieces'], $rowHeight, $piecesText,
                $border);

        pdf::myMultiCell($this->pdf, $width['cartons'], $rowHeight,
                $cartonText, $border);

        if ($firstRow && isset($groupLocation)) {
            $location = $groupLocation ? $groupLocation : 'Not Racked';
        }

        pdf::myMultiCell($this->pdf, $width['location'], $rowHeight,
                $location, $border, 'C', 1);

        if ($this->wavePickType == 'original') {
            pdf::myMultiCell($this->pdf, $width['locationPicked'], $rowHeight, '');
            pdf::myMultiCell($this->pdf, $width['quantityPicked'], $rowHeight, '');
        }
    }

    /*
    ****************************************************************************
    */

    function wavePickPageHeader()
    {
        $this->pageHeader('Wave Pick');

        $rowHeight = self::$sizing['page']['rowHeight'] * 2;
        $width = self::$sizing['width'];

        $text = $this->wavePickType == 'original' ?
            'PCK' . chr(10) . 'QTY' : 'Picking' . chr(10) . 'Quantity';

        pdf::myMultiCell($this->pdf, $width['quantity'], $rowHeight, $text);
        pdf::myMultiCell($this->pdf, $width['upcID'], $rowHeight,
                'UPC' . chr(10) . 'ID');
        pdf::myMultiCell($this->pdf, $width['upc'], $rowHeight, 'UPC');
        pdf::myMultiCell($this->pdf, $width['locationsTitle'], $rowHeight,
                'Locations');

        $this->newLine(2);
    }

    /*
    ****************************************************************************
    */

    function upcHeader($count, $key)
    {
        $rowHeight = self::$sizing['page']['rowHeight'] * 2;
        $width = self::$sizing['width'];

        $upc = $this->upc;

        $quantity = $this->upcData[$count][$key][$upc]['quantity'];

        $pickingQuantity = min($quantity, $this->pieces);

        pdf::myMultiCell($this->pdf, $width['quantity'], $rowHeight,
                $pickingQuantity, 'LR');
        pdf::myMultiCell($this->pdf, $width['upcID'], $rowHeight,
                $this->reqInfo['upcID'], 'LR');

        $style = [
            'text' => $upc,
            'padding' => 1,
            'fontsize' => self::$sizing['font']['barcode'],
            'label' => $upc,
            'hpadding' => 'auto',
        ];

        $barcodeMargin = 1.5;
        $barcodeWidth = $width['upc'] - $barcodeMargin * 2;

        pdf::myMultiCell($this->pdf, $barcodeMargin, $rowHeight, NULL, 0);

        $this->pdf->write1DBarcode($upc, 'C128', '', '', $barcodeWidth, 8, 0.4,
                $style, 'T', FALSE);

        pdf::myMultiCell($this->pdf, $barcodeMargin, $rowHeight, NULL, 0);

        if ($this->info) {

            $pieceText = $this->wavePickType == 'original' ?
                    'AVL' . chr(10) . 'PCS' : 'Available' . chr(10) . 'Pieces';

            pdf::myMultiCell($this->pdf, $width['pieces'], $rowHeight, $pieceText);

            $cartonText = $this->wavePickType == 'original' ?
                    'AVL' . chr(10) . 'CTN' : 'Available' . chr(10) . 'Cartons';

            pdf::myMultiCell($this->pdf, $width['cartons'], $rowHeight, $cartonText);

            pdf::myMultiCell($this->pdf, $width['location'], $rowHeight,
                    'Locations' );

            if ($this->wavePickType == 'original') {
                pdf::myMultiCell($this->pdf, $width['locationPicked'], $rowHeight,
                        'Locations' . chr(10) . 'Picked');
                pdf::myMultiCell($this->pdf, $width['quantityPicked'], $rowHeight,
                        'QTY' . chr(10) . 'PCK');
            }
        } else {
            pdf::myMultiCell($this->pdf, $width['locationsTitle'], $rowHeight,
                    'Inventory Not Racked');
        }

        $this->newLine(2);

        $this->upcRow = 0;
    }

    /*
    ****************************************************************************
    */

    function addCartonInfo($data)
    {
        $upcsInfo = $data['upcsInfo'];
        $orderData = $data['orderData'];
        $location = $data['location'];
        $orderNumber = $data['orderNumber'];
        $index = $data['index'];

        foreach ($orderData as $upc => $orderCartons) {
            foreach ($orderCartons as $cartonData) {

                $prefix = $cartonData['prefix'];
                $suffix = $cartonData['suffix'];

                if ($cartonData['location'] == $location) {

                    $key = $index . $upc . $prefix . $suffix;

                    $uom = $cartonData['uom'];

                    $upcsInfo[$key]['upc'] = $upc;
                    $upcsInfo[$key]['pieces'] =
                            getDefault($upcsInfo[$key]['pieces'], 0) + $uom;

                    $upcsInfo[$key]['cartons'][] = [
                        'orderNumber' => $orderNumber,
                        'uom' => $uom,
                        'prefix' => $prefix,
                        'suffix' => $suffix,
                        'location' => $cartonData['location'],
                    ];
                }
            }
        }

        return $upcsInfo;
    }

    /*
    ****************************************************************************
    */

    function sortBackupLocations($backupLocations)
    {
        $sortedBackupLocations = [];

        foreach ($backupLocations as $upc => $values) {

            $locationKeys = [];

            $locationNames = array_keys($values);

            foreach ($locationNames as $locationName) {

                $locatioArray = [
                    'location' => $locationName,
                ];

                $isCheck = FALSE;
                $straight = TRUE;

                $locationKeys[$locationName] =
                        locations::getLocationIndex($locatioArray, $isCheck, $straight);
            }

            asort($locationKeys);

            $sortedBackupLocations[$upc] = $locationKeys;
        }

        return $sortedBackupLocations;
    }

    /*
    ****************************************************************************
    */

    function getSortedUpcsInfo($pickData, $pickTicketUpcList, $mezzanineClause)
    {
        $upcsInfo = $sortedLocations = [];

        foreach ($pickData as $orderData) {
            foreach ($orderData as $values) {
                $sortedLocations =
                        locations::sortLocations($values, $sortedLocations);
            }
        }

        asort($sortedLocations);

        foreach ($sortedLocations as $location => $index) {
            foreach ($pickData as $orderNumber => $orderData) {
                $upcsInfo = $this->addCartonInfo([
                    'upcsInfo' => $upcsInfo,
                    'orderData' => $orderData,
                    'location' => $location,
                    'orderNumber' => $orderNumber,
                    'index' => $index,
                ]);
            }
        }

        $upcsInfoSorted = $this->sortUpcsInfoCartons($upcsInfo);

        if ($this->printType == 'wavePick' && $this->wavePickType == 'original') {

            $backupLocations = $this->getBackupLocations($pickTicketUpcList,
                    $mezzanineClause);

            return $this->addBackupLocations($upcsInfoSorted, $backupLocations);
        } else {
            return $upcsInfoSorted;
        }
    }

    /*
    ****************************************************************************
    */

    function sortUpcsInfoCartons($upcsInfo)
    {
        $lastLocation = $lastUpc = $lastPrefix = $lastSuffix = $lastIndex = NULL;

        foreach ($upcsInfo as $index => $info) {

            $upc = $info['upc'];
            $cartons = $info['cartons'];

            krsort($cartons);

            $carton = reset($cartons);

            $location = $carton['location'];
            $prefix = $carton['prefix'];
            $suffix = $carton['suffix'];

            $sameLocation = $location == $lastLocation;
            $sameUPC = $upc == $lastUpc;
            $samePrefix = $prefix == $lastPrefix;
            $sameSuffix = $suffix == $lastSuffix;

            if ($sameLocation && $sameUPC && $samePrefix && $sameSuffix) {

                $upcsInfo[$lastIndex]['cartons'] = array_merge(
                        $upcsInfo[$lastIndex]['cartons'], $cartons
                );

                $upcsInfo[$lastIndex]['pieces'] += $upcsInfo[$index]['pieces'];

                unset($upcsInfo[$index]);
            } else {

                $lastLocation = $location;
                $lastUpc = $upc;
                $lastPrefix = $prefix;
                $lastSuffix = $suffix;
                $lastIndex = $index;

                $upcsInfo[$index]['cartons'] = $cartons;
            }
        }

        foreach ($upcsInfo as $index => $info) {

            $usedLocations = array_column($info['cartons'], 'location');

            $upcsInfo[$index]['locations'] = array_unique($usedLocations);
        }

        return $upcsInfo;
    }

    /*
    ****************************************************************************
    */

    function getBackupLocations($pickTicketUpcList, $mezzanineClause)
    {
        $locations = new \tables\locations($this->app);
        $vendors = new \tables\vendors($this->app);

        $pickTicketUpcKeys = [];

        foreach ($pickTicketUpcList as $values) {

            $orderUpcs = array_keys($values);

            $pickTicketUpcKeys = array_merge($pickTicketUpcKeys, $orderUpcs);
        }

        $pickTicketUpcs = array_unique($pickTicketUpcKeys);

        $vendorID = $vendors->getByBatchNumber($this->batch);

        return $locations->getUPCBackupLocations($pickTicketUpcs,
                $vendorID, $mezzanineClause);
    }

    /*
    ****************************************************************************
    */

    function addBackupLocations($upcsInfo, $backupLocations)
    {
        $sortedBackupLocations = $this->sortBackupLocations($backupLocations);

        foreach ($upcsInfo as $infoKey => $info) {

            $upc = $info['upc'];

            if (! isset($sortedBackupLocations[$upc])) {
                continue;
            }

            $pieces = $info['pieces'];
            $usedLocations = array_flip($info['locations']);

            $carton = reset($info['cartons']);

            $location = $carton['location'];

            $upcBackupLocations = $sortedBackupLocations[$upc];

            $result = $this->selectBackupLocations([
                'location' => $location,
                'upcBackupLocations' => $upcBackupLocations,
                'backupLocations' => $backupLocations[$upc],
                'pieces' => $pieces,
                'infoKey' => $infoKey,
                'usedLocations' => $usedLocations,
            ]);

            if (! $result) {
                continue;
            }

            foreach ($result['selectedLocations'] as $selectedLocations) {

                ksort($selectedLocations);

                $upcsInfo[$infoKey]['cartons'] = array_merge(
                        $upcsInfo[$infoKey]['cartons'],
                        $selectedLocations
                );
            }
        }

        return $upcsInfo;
    }

    /*
    ****************************************************************************
    */

    function selectBackupLocations($data)
    {
        $location = $data['location'];
        $upcBackupLocations = $data['upcBackupLocations'];
        $backupLocations = $data['backupLocations'];
        $infoKey = $data['infoKey'];
        $pieces = $data['pieces'];
        $usedLocations = $data['usedLocations'];

        $count = 0;

        $next = FALSE;

        $nextLocationSet = $prevLocationSet = $selectedLocations = [];

        foreach ($upcBackupLocations as $key => $value) {
            if (isset($usedLocations[$key]) || $location == $key) {

                $next = $location == $key || $next;

                continue;
            }

            if ($next) {
                $nextLocationSet[$value] = $key;
            } else {
                $prevLocationSet[$value] = $key;
            }
        }

        krsort($prevLocationSet);

        $sorderBackupLocations = $nextLocationSet + $prevLocationSet;

        foreach ($sorderBackupLocations as $key => $locationName) {

            $pieces -= $backupLocations[$locationName];
            $count++;

            $selectedLocations[$infoKey][$key] = [
                'extraLocation' => TRUE,
                'location' => $locationName,
            ];

            if ($pieces <= 0 && $count >= self::$sizing['backupLocations']) {
                return [
                    'count'=> $count,
                    'pieces' => $pieces,
                    'selectedLocations' => $selectedLocations,
                ];
            }
        }

        return [
            'count'=> $count,
            'pieces' => $pieces,
            'selectedLocations' => $selectedLocations,
        ];
    }

    /*
    ****************************************************************************
    */

    function verificationList()
    {
        $warehouseKeys = array_keys($this->sortedUpcsInfo);

        self::$sizing = $this->sizing();

        foreach ($warehouseKeys as $count) {

            $orderNumber = 0;

            $upcsData = $this->upcData[$count][$orderNumber];

            $skuSorted = [];

            $firstUpcData = reset($upcsData);
            $this->createTypeOrder = $firstUpcData['edi'];

            $this->verificationListPageHeader();

            // as processing verification never prints by orders per separate
            // sheet $this->upcData array will always have one key equal to zero.
            // On the contary $this->upcData for an order per sheet will have
            // keys equal to order numbers with a list of upcs that belong to
            // the respective order


            // preface array keys with SKU
            foreach ($upcsData as $upc => $upcData) {

                $upcData['upc'] = $upc;

                $sku = $upcData['sku'];

                $key = $sku . $upc;

                $skuSorted[$key] = $upcData;
            }
            // sort array by SKU
            ksort($skuSorted);

            $sortedCartons = $this->sortedUpcsInfo[$count];

            $upcInfo = $this->handleExcessiveCartons($sortedCartons, $upcsData);

            $this->displayVerificationList($skuSorted, $upcInfo);
        }
    }

    /*
    ****************************************************************************
    */

    function displayVerificationList($skuSorted, $upcInfo)
    {
        $upcTitleHeight = self::$upcRowAmount + self::$upcHeaderHeight;

        foreach ($skuSorted as $upcData) {
            if ($this->rowCount + $upcTitleHeight >= self::$sizing['page']['rowAmount']) {
                // page break if next row exceeds page length
                $this->verificationListPageHeader();
            }

            pdf::myMultiCell($this->pdf, 20, self::$rowHeight, $upcData['quantity']);
            pdf::myMultiCell($this->pdf, 15, self::$rowHeight, $upcData['upcID']);
            pdf::myMultiCell($this->pdf, 30, self::$rowHeight, $upcData['upc']);
            pdf::myMultiCell($this->pdf, 36, self::$rowHeight, $upcData['sku'],
                    1, 'C', 1);
            pdf::myMultiCell($this->pdf, 30, self::$rowHeight, $upcData['color'],
                    1, 'C', 1);
            pdf::myMultiCell($this->pdf, 30, self::$rowHeight, $upcData['size'],
                    1, 'C', 1);

            $pieces = $cartons = 0;

            foreach ($upcInfo as $orderData) {
                if ($orderData['upc'] == $upcData['upc']) {
                    $pieces += $orderData['pieces'];
                    $cartons += count($orderData['cartons']);
                }
            }

            pdf::myMultiCell($this->pdf, 17, self::$rowHeight, $pieces);
            pdf::myMultiCell($this->pdf, 17, self::$rowHeight, $cartons);

            $this->newLine();
        }
    }

    /*
    ****************************************************************************
    */

    function verificationListPageHeader()
    {
        $this->pageHeader('Processing Verification');

        $rowHeight = self::$rowHeight * 2;

        pdf::myMultiCell($this->pdf, 20, $rowHeight, 'Pieces' . chr(10) . 'Required');
        pdf::myMultiCell($this->pdf, 15, $rowHeight, 'UPC' . chr(10) . 'ID');
        pdf::myMultiCell($this->pdf, 30, $rowHeight, 'UPC');
        pdf::myMultiCell($this->pdf, 36, $rowHeight, 'SKU');
        pdf::myMultiCell($this->pdf, 30, $rowHeight, 'Color');
        pdf::myMultiCell($this->pdf, 30, $rowHeight, 'Size');

        $prefix = 'Available' . chr(10);

        pdf::myMultiCell($this->pdf, 17, $rowHeight, $prefix . 'Pieces');
        pdf::myMultiCell($this->pdf, 17, $rowHeight, $prefix . 'Cartons');

        $this->newLine(2);
    }

    /*
    ****************************************************************************
    */

    function pageHeader($caption)
    {
        $rowHeight = self::$sizing['page']['rowHeight'];

        $this->pdf->AddPage();

        $this->pdf->SetFont('helvetica', '', self::$sizing['font']['caption']);

        $this->rowCount = 0;

        $text = $caption.' for Batch: ' . $this->batch;

        if ($this->order) {
            $text .= ', Order # ' . $this->order;
        }

        pdf::myMultiCell($this->pdf, 185, $rowHeight, $text, 0, 'L');

        $text = ++$this->pageCount;
        pdf::myMultiCell($this->pdf, 10, $rowHeight, $text, 0, 'R');

        $this->newLine();

        if (getDefault($this->shippingLane['displayName'])) {

            $text = 'Shipping Lane: ' . $this->shippingLane['displayName'];

            if ($this->wavePickType == 'original') {
                pdf::myMultiCell($this->pdf, 195, $rowHeight, $text, 0, 'L');
            } else {
                pdf::myMultiCell($this->pdf, 100, $rowHeight, $text, 0, 'L');
                pdf::myMultiCell($this->pdf, 95, $rowHeight,
                        'Picking Check Out Update', 0, 'C');
            }

            $this->newLine();
        }

        //Purchase order for pick ticket for pick ticket
        if ($this->order && $this->customerordernumber) {

            $text = 'Purchase order # ' . $this->customerordernumber;
            pdf::myMultiCell($this->pdf, 195, $rowHeight, $text, 0, 'L');

            $this->newLine();

            $text = 'Order: ' . ($this->createTypeOrder ? 'EDI' : 'NON-EDI');
            pdf::myMultiCell($this->pdf, 195, $rowHeight, $text, 0, 'L');

            $this->newLine();

        }

        $this->newLine();

        $this->pdf->SetFont('helvetica', '', self::$sizing['font']['text']);
    }

    /*
    ****************************************************************************
    */

    function handleExcessiveCartons($upcsInfo, $upcsData, $orderNumber=0)
    {
        $return = [];

        $upcKeys = array_keys($upcsData);

        foreach ($upcKeys as $upc) {
            $return += $this->removeExcessiveCarton([
                'upcsInfo' => $upcsInfo,
                'orderNumber' => $orderNumber,
                'upc' => $upc,
            ]);
        }

        ksort($return);

        return $return;
    }

    /*
    ****************************************************************************
    */

    function removeExcessiveCarton($data)
    {
        $upcsInfo = $data['upcsInfo'];
        $orderNumber = $data['orderNumber'];
        $upc = $data['upc'];

        $return = [];

        foreach ($upcsInfo as $index => $upcsInfoValue) {

            if (getDefault($upcsInfoValue['upc']) != $upc) {
                continue;
            }

            $results = $this->getOrderPickCartons($upcsInfoValue, $orderNumber);

            if ($results) {
                $upcsInfoValue['pieces'] = $results['pieces'];
                $upcsInfoValue['cartons'] = $results['cartons'];

                $return[$index] = $upcsInfoValue;
            }
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getOrderPickCartons($upcsInfoValue, $orderNumber)
    {
        $pieces = 0;

        foreach ($upcsInfoValue['cartons'] as $ucc128 => $carton) {

            if (! isset($carton['uom'])) {
                // skip backup locations
                continue;
            }

            if ($orderNumber && $orderNumber != $carton['orderNumber']) {
                // remove cartons that are reserved for another orders and skip
                unset($upcsInfoValue['cartons'][$ucc128]);

                continue;
            }

            $pieces += $carton['uom'];
        }

        return $pieces ? [
            'pieces' => $pieces,
            'cartons' => $upcsInfoValue['cartons'],
        ] : NULL;
    }

    /*
    ****************************************************************************
    */

    function newLine($rowAgment=1)
    {
        $this->pdf->Ln();
        $this->rowCount += $rowAgment;
    }

    /*
    ****************************************************************************
    */

    function getReportData($data)
    {
        $upcsInfo = $data['upcsInfo'];
        $param = $data['param'];
        $index = $data['index'];
        $info = $data['info'];

        $prefix = $suffix = NULL;
        $cartonCount = $pieceCount = 0;
        $extraLocation = FALSE;

        foreach ($info['cartons'] as $locInfo) {

            $location = $locInfo['location'];

            $upcsInfo[$index]['prefix'] = isset($locInfo['prefix']) ?
                    $locInfo['prefix'] : $prefix;
            $upcsInfo[$index]['suffix'] = isset($locInfo['suffix']) ?
                    $locInfo['suffix'] : $suffix;

            $prefix = getDefault($locInfo['prefix'], NULL);
            $suffix = getDefault($locInfo['suffix'], NULL);

            $anotherPrefix = getDefault($param['groupPrefix']) != $prefix;
            $anotherSuffix = getDefault($param['groupSuffix']) != $suffix;

            $anotherGroup = $anotherPrefix || $anotherSuffix;

            if ($pieceCount && $anotherGroup) {

                $this->reportData[$index]['data'][] = [
                    'availablePieces' => $pieceCount,
                    'availableCartons' => $cartonCount,
                    'location' => $param['groupLocation']
                ];

                $cartonCount = $pieceCount = 0;
            }

            if (isset($locInfo['extraLocation'])) {
                if (! $extraLocation) {
                    $this->reportData[$index]['data'][] = 'Backup Locations';
                }

                $this->reportData[$index]['data'][] = $locInfo['location'];

                $extraLocation = TRUE;

            } else {

                $cartonCount++;
                $pieceCount += $locInfo['uom'];
                // these variables define a new group of cartons
                $param['groupLocation'] = $location;
                $param['groupPrefix'] = $prefix;
                $param['groupSuffix'] = $suffix;
            }
        }

        if (! $extraLocation) {
            $this->reportData[$index]['data'][] = [
                'availablePieces' => $pieceCount,
                'availableCartons' => $cartonCount,
                'location' => $param['groupLocation']
            ];
        }

        return $upcsInfo;
    }

    /*
    ****************************************************************************
    */

    function createWavePickPDF($order, $batch, $file=NULL)
    {
        ob_clean();
        $orderBatches = new \tables\orderBatches($this->app);
        $orders = new \tables\orders($this->app);

        $this->submittedOrders = $this->pickData = $this->upcData =
                $this->productErrors = $this->purchaseOrders = [];

        $this->batch = $this->order = $this->shippingLane = NULL;

        if ($order) {

            $this->submittedOrders[] = $this->order = $order;

            $this->batch = $orderBatches->getByOrderNumber($this->order);
            $this->customerordernumber =
                $orders->getFieldsValuesBy($this->order, 'customerordernumber');

        } else {

            $this->batch = $batch;

            $this->submittedOrders =
                    $orders->getWavePickOrdersByBatch($this->batch);
        }

        if (! $this->batch) {
            $this->productErrors[] = 'Batch Number was not found';
        } elseif (! $this->submittedOrders) {
            $this->productErrors[] = 'Order Number was not found';
        } elseif ($this->printType == 'uccLabels') {
            $orderInfo = scanner::getNewOrderInfoByID($this->app, $this->order);

            if ($orderInfo['clientCode'] == vendors::VENDOR_CODE_GO_LIVE_WORK
                && $orderInfo['edi']) {
                if (! $orderInfo['isPrintUccEdi']) {
                    create::printUCCLabelEDIFormat($this->app, $this->order);
                } else {
                    die(self::MESSAGE_ORDER_UCC_FROM_LINGO);
                }

            } else {
                create::pickTicketCartonsLabels($this->app,
                    $this->submittedOrders, $this->printByOrder);
            }

            return;
        } else {
            $this->makeWavePicks();
        }

        // Print Wave Pick
        $this->processPrintPDFWavePick($file);
    }

    /*
    ****************************************************************************
    */

    function processPrintPDFWavePick ($file)
    {

        $outputType = $file ? 'F' : 'I';
        $pdfOutput = $file ? $file : 'pdf';

        $this->pdf = new \TCPDF('P', 'mm', 'Letter', TRUE, 'UTF-8', FALSE);
        $this->pdf->setPrintHeader(FALSE);
        $this->pdf->setPrintFooter(FALSE);
        $this->pdf->SetAutoPageBreak(TRUE, 0);
        $this->pdf->SetLeftMargin(10);
        $this->pdf->setCellPaddings(0, 0, 0, 0);

        if ($this->productErrors) {
            $this->displayErrors();
        } else {

            $this->rowCount = $this->pageCount = $this->upcRow = 0;

            $printType = getDefault($this->printType, 'wavePick');

            switch ($printType) {
                case 'wavePick':
                    $this->displayWavePick();
                    break;
                case 'verificationList':
                    $this->verificationList();
                    break;
                default:
                    $this->productErrors[] = 'Invalid Print Type';
                    break;
            }
        }

        $this->pdf->Output($pdfOutput, $outputType);
    }

    /*
    ****************************************************************************
    */

    function makeWavePicks()
    {
        $truckOrderWaves = new \tables\truckOrderWaves($this->app);
        $wavePicks = new \tables\wavePicks($this->app);
        $pickCartons = new \tables\inventory\pickCartons($this->app);
        $locations = new \tables\locations($this->app);

        $classes = [
            'wavePicks' => $wavePicks,
            'pickCartons' => $pickCartons,
            'locations' => $locations,
        ];

        $truckOrders =
                $truckOrderWaves->getExistingTruckOrders($this->submittedOrders);

        if ($truckOrders) {
            // process Master Cartons from the Regular Warehouse
            $this->createWavePick($classes, $isTruckOrder=FALSE);

            // process Mixed Items Cartons from the Mezzanine
            $this->createWavePick($classes, $isTruckOrder=TRUE);
        } else {
            // get inventory from the Regular Warehouse in case of a normal
            // order and from the Mezzanine in case of Online Order
            $this->createWavePick($classes, $isTruckOrder=NULL);
        }
    }

    /*
    ****************************************************************************
    */

    function displayErrors()
    {
        $this->pdf->AddPage();

        foreach ($this->productErrors as $error) {

            $this->pdf->SetFont('helvetica', '', 10);

            $errors = explode('<br>', $error);

            foreach ($errors as $error) {
                \common\pdf::myMultiCell($this->pdf, 180, self::$rowHeight,
                        $error, 0, 'L');

                $this->pdf->Ln();
            }
        }
     }

    /*
    ****************************************************************************
    */

    function sizing()
    {
        return $this->wavePickType == 'original' ?
            [
                'page' => [
                    'rowAmount' => 52,
                    'rowHeight' => 5,
                ],
                'font' => [
                    'caption' => 10,
                    'text' => 10,
                    'barcode' => 8,
                ],
                'width' => [
                    'quantity' => 13,
                    'upcID' => 15,
                    'upc' => 50,
                    'pieces' => 15,
                    'cartons' => 15,
                    'location' => 25,
                    'locationPicked' => 40,
                    'quantityPicked' => 22,
                    // 55 = pieces (15) + cartons (15) + location (25)
                    'backup' => 55,
                    // 117 = backup (55) + locationPicked (40) + quantityPicked (22)
                    'locationsTitle' => 117,
                ],
                'backupLocations' => 5,
            ] : [
                'page' => [
                    'rowAmount' => 42,
                    'rowHeight' => 6,
                ],
                'font' => [
                    'caption' => 12,
                    'text' => 11,
                    'barcode' => 10,
                ],
                'width' => [
                    'quantity' => 18,
                    'upcID' => 15,
                    'upc' => 50,
                    'pieces' => 22,
                    'cartons' => 22,
                    'location' => 66,
                    // 110 = pieces (22) + cartons (22) + location (44)
                    'locationsTitle' => 110,
                ],
                'backupLocations' => 0,
            ];
    }

    /*
    ****************************************************************************
    */

}
