<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

use \common\order;
use \common\pdf;

class model extends base
{
    const CHECK_IN = 'Check-In';
    const CHECK_OUT = 'Check-Out';

    const REGULAR_ORDER_PRODUCTS_TABLE_CAPTION = 'Order Products';
    const TRUCK_ORDER_PRODUCTS_TABLE_CAPTION = 'Master Cartons';

    /*
    ****************************************************************************
    */

    function __construct() {
       common\orderChecks::init($this);
    }

    /*
    ****************************************************************************
    */

    function processRestoreDBValues($page, $id)
    {
        foreach ($this->inputFields as $field) {
            $this->inputValues[$field][$page] = '';
        }

        $dbValues = $this->dbValues[$id];

        foreach($this->dbFields as $field){
            $this->inputValues[$field][$page] = $dbValues[$field];
        }

        foreach ($this->checkBoxes as $checkBox) {
            //check box info saved in database
            $this->inputValues[$checkBox][$page]
                = $this->inputValues[$checkBox][$page] ? 'checked' : '';
        }

        $checkNFill = $this->checkNFill;

        foreach ($checkNFill as $check) {
            $fillName = $check . 'inform';
            $boxInfo = $this->inputValues[$fillName][$page];
            if ($boxInfo) {
                $this->inputValues[$check][$page] = 'checked';
            }
        }
    }

    /*
    ****************************************************************************
    */

    function restoreDBValues($page, $id)
    {
        $this->processRestoreDBValues($page, $id);
        $dbValues = $this->dbValues[$id];

        //choose from Ecommerce or Regular
        $shipType = $this->inputValues['EcoOrReg'][$page];
        $this->inputValues[$shipType][$page] = 'checked';

        //choose from Standard, Rush or Super-Rush
        $shipType = $this->inputValues['service'][$page];
        $this->inputValues[$shipType][$page] = 'checked';

        //choose label types
        $labelType = $this->inputValues['label'][$page];

        if ($labelType) {
            $this->inputValues[$labelType][$page] = 'checked';
            $labelInfo = $labelType . 'info';
            $this->inputValues[$labelInfo][$page] = $dbValues['labelinfo'];
        }

        $this->inputValues['isVAS'][$page] = $this->inputValues['isVAS'][$page] ?
                'yesVAS' : 'noVAS';

        //choose from No VAS / VAS
        $vasType = $this->inputValues['isVAS'][$page];
        $this->inputValues[$vasType][$page] = 'checked';

        \common\orderChecks::menuLists($this, $page);
        $this->dbOrderProducts[$page] = $dbValues['scanOrderNumber'];
    }

    /*
    ****************************************************************************
    */

    function processRestoreProductDBValues(&$scanOrderNumber, $product, &$count)
    {
        if ($scanOrderNumber == $product['scanOrderNumber']) {

            $page = $this->ordersKeys[$scanOrderNumber];

            $row = $this->closedOrders[$scanOrderNumber] ?
                -1 : $this->checkDuplicateRow($product, $page);

            if ($row != -1) {
                // a row with the same properties exists
                $this->post['uom'][$page][$row][] = $product['uom'];
                $this->post['quantity'][$page][$row] += $product['quantity'];
                $this->post['cartonCount'][$page][$row] += $product['cartonCount'];
                $this->post['weight'][$page][$count] += $product['weight'];
                $this->post['volume'][$page][$count] += $product['volume'];

                return FALSE;
            }

            $count++;
        } else {
            $count = 0;
            $scanOrderNumber = $product['scanOrderNumber'];
            $page = $this->ordersKeys[$scanOrderNumber];
        }

        $this->post['uom'][$page][$count][] = $product['uom'];
        $this->post['cartonCount'][$page][$count] = $product['cartonCount'];
        $this->post['quantity'][$page][$count] = $product['quantity'];
        $this->post['sku'][$page][$count] = $product['sku'];
        $this->post['size'][$page][$count] = $product['size'];
        $this->post['color'][$page][$count] = $product['color'];
        $this->post['upc'][$page][$count] = $product['upc'];
        $this->post['upcID'][$page][$count] = $product['upcID'];
        $this->post['cartonLocation'][$page][$count] = $product['cartonLocation'];
        $this->post['prefix'][$page][$count] = $product['prefix'];
        $this->post['suffix'][$page][$count] = $product['suffix'];
        $this->post['available'][$page][$count] = $product['available'];
        $this->post['weight'][$page][$count] = $product['weight'];
        $this->post['volume'][$page][$count] = $product['volume'];
    }

    /*
    ****************************************************************************
    */

    function restoreProductDBValues($orders)
    {
        $this->ordersKeys = array_flip($this->dbOrderProducts);
        $closedOrders = $notClosedOrders = [];

        foreach ($this->dbOrderProducts as $orderNumber) {
            if ($this->closedOrders[$orderNumber]
             && ! $this->canceledOrders[$orderNumber]) {

                $closedOrders[] = $orderNumber;
            } else {
                $notClosedOrders[] = $orderNumber;
            }
        }

        $notClosedOrdersProducts =
            $orders->getNotClosedOrdersProducts($notClosedOrders);

        $closedOrdersProducts = $orders->getClosedOrdersProducts($closedOrders);

        $products =
            array_merge($notClosedOrdersProducts, $closedOrdersProducts);

        $scanOrderNumber = NULL;
        $count = 0;

        foreach ($products as $product) {
            $this->processRestoreProductDBValues($scanOrderNumber, $product,
                $count);
        }
    }

    /*
    ****************************************************************************
    */

    function checkDuplicateRow($product, $page)
    {
        $post = $this->post;
        $rowAmount = count($post['uom'][$page]);

        for ($row = 0; $row < $rowAmount; $row++) {
            if ($post['sku'][$page][$row] == $product['sku']
                && $post['size'][$page][$row] == $product['size']
                && $post['color'][$page][$row] == $product['color']
                && $post['upc'][$page][$row] == $product['upc']
                && $post['upcID'][$page][$row] == $product['upcID']
                && $post['cartonLocation'][$page][$row] ==
                $product['cartonLocation']
                && $post['prefix'][$page][$row] == $product['prefix']
                && $post['suffix'][$page][$row] == $product['suffix']
                && $post['available'][$page][$row] == $product['available']
            ) {
                return $row;
            }
        }

        return -1;
    }

    /*
    ****************************************************************************
    */

    function pagePrint($pageData)
    {
        $pdf = $this->pdf;
        $rowHeight = $this->rowHeight;
        $results = $this->results;
        $tableData = $this->tableData;

        $userID = $pageData['userid'];
        $vendorID = $pageData['vendor'];
        $typeID = $pageData['type'];
        $locationID = $pageData['location'];

        $user = getDefault($results['user'][$userID]['username'], NULL);
        $vendor = getDefault($results['vendor'][$vendorID]['fullVendorName'], NULL);
        $orderType = getDefault($results['orderType'][$typeID]['typeName'], NULL);
        $location = getDefault($results['location'][$locationID]['companyName'], NULL);

        $pageData['EcoOrReg'] = getDefault($pageData['EcoOrReg']);
        $pageData['service'] = getDefault($pageData['service']);
        $pageData['label'] = getDefault($pageData['label']);
        $pageData['isVAS'] = getDefault($pageData['isVAS']);

        $pdf->AddPage('P');
        $pdf->SetFont('helvetica', '', 10);

        $tableData[1]['data'][1]['text'] = $user;
        $tableData[2]['data'][1]['selected'] = $pageData['EcoOrReg'] == 'ecommerce';
        $tableData[3]['data'][1]['caption'] = $pageData['samples'];

        $tableData[1]['data'][2]['text'] = $pageData['last_name'];
        $tableData[2]['data'][2]['selected'] = $pageData['EcoOrReg'] == 'regular';
        $tableData[3]['data'][2]['caption'] = $pageData['pickpack'];

        $tableData[1]['data'][3]['text'] = $vendor;
        $tableData[2]['data'][3]['selected'] = $pageData['service'] == 'standard';
        $tableData[3]['data'][3]['text'] = $pageData['shiptolabelinfo'];
        $tableData[3]['data'][3]['selected'] = $pageData['label'] == 'shiptolabel';

        $tableData[1]['data'][4]['text'] = $pageData['clientordernumber'];
        $tableData[2]['data'][4]['selected'] = $pageData['service'] == 'rush';
        $tableData[3]['data'][4]['text'] = $pageData['eriinfo'];
        $tableData[3]['data'][4]['selected'] = $pageData['label'] == 'eri';

        $tableData[1]['data'][5]['text'] = $pageData['customerordernumber'];
        $tableData[2]['data'][5]['selected'] = $pageData['service'] == 'superrush';
        $tableData[3]['data'][5]['text'] = $pageData['UFlabelsinfo'];
        $tableData[3]['data'][5]['selected'] = $pageData['label'] == 'UFlabels';

        $tableData[1]['data'][6]['text'] = $pageData['scanOrderNumber'] ;
        $tableData[2]['data'][6]['caption'] = $pageData['numberofcarton'] ?
                $pageData['numberofcarton'] : 'Not Estimated';
        $tableData[3]['data'][6]['caption'] = $pageData['NOpallets'];

        $tableData[1]['data'][7]['text'] = $pageData['deptid'];
        $tableData[2]['data'][7]['caption'] = $pageData['numberofpiece'] ?
                $pageData['numberofpiece'] : 'Not Stated';
        $tableData[3]['data'][7]['caption'] = $pageData['physicalhours'];

        $tableData[1]['data'][8]['text'] = $pageData['clientpickticket'];
        $tableData[2]['data'][8]['caption'] = $pageData['totalVolume'] ?
                $pageData['totalVolume'] : 'Not Estimated';
        $tableData[3]['data'][8]['caption'] = $pageData['overtimehours'];
        $tableData[3]['data'][9]['text'] = $pageData['additionalshipperinformation'];

        $tableData[1]['data'][9]['text'] = $pageData['startshipdate'];
        $tableData[1]['data'][10]['text'] = $pageData['canceldate'];

        $tableData[1]['data'][11]['text'] = $orderType;
        $tableData[2]['data'][9]['caption'] = $pageData['totalWeight'] ?
                $pageData['totalWeight'] : 'Not Estimated';

        $tableData[2]['data'][10]['text'] = $pageData['pickid'] ?
                $pageData['pickid'] : 'Not Created';
        $tableData[3]['data'][10]['selected'] = $pageData['isVAS'] == 'noVAS';

        $tableData[2]['data'][11]['text'] = $location;
        $tableData[3]['data'][11]['selected'] = $pageData['isVAS'] == 'yesVAS';

        $tableData[2]['data'][12]['selected'] = isset($pageData['picklist']);

        $tableData[2]['data'][13]['selected'] =
                isset($pageData['packinglist']);
        $tableData[2]['data'][14]['selected'] = isset($pageData['prebol']);

        $tableData[2]['data'][15]['selected'] = isset($pageData['commercialinvoice']);

        $tableData[2]['data'][16]['selected'] = isset($pageData['cartoncontent']);

        $tableData[2]['data'][17]['selected'] = isset($pageData['otherlabel']);

        $tableData[2]['data'][17]['caption'] =
            'Other Label: ' . $pageData['otherlabelinform'];

        for ($rowIndex=1; $rowIndex<=17; $rowIndex++) {
            $this->tableGroupOneRowDisplay($tableData, $pageData, $rowIndex);
        }

        $pdf->Ln();

        pdf::myMultiCell($pdf, 95, $rowHeight, 'Order Processing Notes:');
        pdf::myMultiCell($pdf, 5, $rowHeight, NULL, 0);

        $pdf->Ln();

        $ordernotes = trim($pageData['ordernotes']);

        pdf::myMultiCell($pdf, 95, $rowHeight * 8, $ordernotes, 1, 'L');
        pdf::myMultiCell($pdf, 5, $rowHeight, NULL, 0);

        $pdf->SetFont('helvetica', '', 9);

        $pageData['tableData'] = getDefault($pageData['tableData'], []);

        $row = 1;

        foreach ($pageData['tableData'] as $key => $rowData) {
            $pageData['tableData'][$key]['location'] = $rowData['cartonLocation'];
        }

        $sortedLocations = tables\locations::sortLocations($pageData['tableData']);

        asort($sortedLocations);

        $sortedLocationKeys = array_keys($sortedLocations);

        foreach ($sortedLocationKeys as $sortedLocation) {
            foreach ($pageData['tableData'] as $rowData) {
                    if ($rowData['cartonLocation'] != $sortedLocation) {
                        continue;
                    }

                $uomCount = count($rowData['uom']);

                if ($row == 1 || $this->rowCount + $uomCount > $this->productRowCount) {
                    $this->printPageTableHeader($pageData, $row);
                }

                $this->printPageTableRow($rowData, $row++);
            }
        }

        if (! getDefault($pageData['truckOrder'])) {
            return;
        }

        $truckOrderTitles = $pageData['truckOrder']['caption'];

        $this->truckTableColumnAmount = count($truckOrderTitles);

        $this->truckTableColumnWidth =
                floor($this->landscapePageWidth / $this->truckTableColumnAmount);

        $this->truckTableLastColumnWidth = $this->landscapePageWidth -
                $this->truckTableColumnWidth * ($this->truckTableColumnAmount - 1);

        $truckRow = 1;

        $titleKeys = array_keys($truckOrderTitles);

        foreach ($pageData['truckOrder']['data'] as $rowData) {
            if ($truckRow == 1 || $this->rowCount > $truckRow) {
                $this->printPageTruckTableHeader($truckOrderTitles);
            }

            $this->printPageTruckTableRow($rowData, $titleKeys);

            $truckRow++;
        }
    }

    /*
    ****************************************************************************
    */

    function pagePrintTableRow($data)
    {
        $tableIndex = $data['tableIndex'];
        $rawCaption = getDefault($data['caption'], NULL);
        $rawText = getDefault($data['text'], NULL);
        $rawContinue = getDefault($data['textContinue'], NULL);
        $border = getDefault($data['border'], 1);
        $selected = getDefault($data['selected'], NULL);
        $type = getDefault($data['type'], NULL);
        $fillColor = getDefault($data['fillColor']);
        $tableData = $data['tableData'];

        $pdf = $this->pdf;
        $rowHeight = $this->rowHeight;
        $table = $tableData[$tableIndex];

        $text = $rawText ? trim($rawText) : $rawText;
        $caption = $rawCaption ? trim($rawCaption) : $rawCaption;
        $textContinue = $rawContinue ? trim($rawContinue) : $rawContinue;

        $fill = FALSE;

        if ($fillColor) {
            $pdf->SetFillColor($fillColor);
            $fill = TRUE;
        }

        $columnWidth = $table['columnWidth'];

        $margin = getDefault($table['margin']);

        if (is_bool($selected)) {

            $type == 'checkbox' ? pdf::checkbox($pdf, $rowHeight, $selected) :
                pdf::radio($pdf, $rowHeight, $selected);

            $columnWidth[1] -= $rowHeight;
        }

        $columnWidth[1] = $text !== NULL ? $columnWidth[1] :
                $columnWidth[1] + $columnWidth[2];

        $columnWidth[2] = $textContinue !== NULL ? $columnWidth[2] :
                $columnWidth[2];

        pdf::myMultiCell($pdf, $columnWidth[1], $rowHeight, $caption, $border,
                'L', 1, $fill);

        if ($text !== NULL) {
            pdf::myMultiCell($pdf, $columnWidth[2], $rowHeight, $text, $border,
                    'L', 1, $fill);
        }

        if ($margin) {
            pdf::myMultiCell($pdf, $margin, $rowHeight, NULL, 0);
        }
    }

    /*
    ****************************************************************************
    */

    function printPageTableHeader($pageData, $row)
    {
        $skipTotal = $row > 1;

        $pdf = $this->pdf;
        $rowHeight = $this->rowHeight;

        $tableWidth = $descriptionWidth = 0;

        foreach ($this->productTableData AS $column => $columnData) {

            $tableWidth += $columnData['width'];
            $descriptionWidth += $column > 3 && $column < 8 ?
                    $columnData['width'] : 0;

            switch ($column) {
                case 3:
                    $totalField = 'cartonCount';
                    break;
                case 9:
                    $totalField = 'quantity';
                    break;
                case 14:
                    $totalField = 'volume';
                    break;
                case 15:
                    $totalField = 'weight';
                    break;
                default:
                    $totalField = NULL;
                    break;
            }

            if (! $totalField || $skipTotal) {
                // $skipTotal - count column totals for the 1-st page only
                continue;
            }

            foreach ($pageData['tableData'] as $rowData) {
                // get column total
                $this->productTableData[$column]['header'][3] +=
                        $rowData[$totalField];
            }
        }

        $this->rowCount = 0;

        $columnAmount = count($this->productTableData);

        $this->pdf->AddPage('L');

        pdf::myMultiCell($pdf, $tableWidth, $rowHeight, 'Order Products');

        $this->pdf->Ln();

        for ($row=1; $row<=3; $row++) {
            for ($column=1; $column<=$columnAmount; $column++) {

                if ($row != 2 && $column > 4 && $column < 8) {
                    continue;
                }

                $columnData = $this->productTableData[$column];

                $text = getDefault($columnData['header'][$row], NULL);
                $border = $row == 3 && in_array($column, [3, 4, 9, 14, 15])
                       || $row == 1 && $column == 4 ? 1 : 'LR';

                $columnWidth = $row != 2 && $column == 4 ? $descriptionWidth :
                        $columnData['width'];

                if ($row == 3 && in_array($column, $this->totalColumns)) {
                    // make column totals in bold
                    $pdf->SetFont('helvetica', 'B', 9);
                }

                pdf::myMultiCell($pdf, $columnWidth, $rowHeight, $text, $border);

                $pdf->SetFont('helvetica', '', 9);
            }

            $this->pdf->Ln();
        }
    }

    /*
    ****************************************************************************
    */

    function printPageTableRow($rowData, $row)
    {
        $pdf = $this->pdf;

        $rawAvailable = getDefault($rowData['available'], NULL);
        $rawVolume = getDefault($rowData['volume'], NULL);
        $rawWeight = getDefault($rowData['weight'], NULL);

        // avoid displying zero
        $available = $rawAvailable ? $rawAvailable : NULL;
        $volume = $rawVolume ? $rawVolume : NULL;
        $weight = $rawWeight ? $rawWeight : NULL;

        $rowUOM = $rowData['uom'];

        $rowData['uom'] = is_array($rowUOM) ? $rowUOM : [$rowUOM];

        $rowHeight = $this->rowHeight * count($rowData['uom']);

        $uomCount = max(1, count($rowData['uom']));

        if ($rowData['uom']) {

            $uom = NULL;

            foreach ($rowData['uom'] as $value) {
                $break = $uom ? "\n" : NULL;
                $uom .= $break . $value;
            }
        }

        $this->rowCount += $uomCount;

        foreach ($this->productTableData as $column => $columnData) {
            $width[$column] = $columnData['width'];
        }

        pdf::myMultiCell($pdf, $width[1], $rowHeight, $row, 1, 'C', 1);
        pdf::myMultiCell($pdf, $width[2], $rowHeight, $rowData['upcID'],
                1, 'C', 1);
        pdf::myMultiCell($pdf, $width[3], $rowHeight, $rowData['cartonCount'],
                1, 'C', 1);
        pdf::myMultiCell($pdf, $width[4], $rowHeight, $rowData['sku'],
                1, 'C', 1);
        pdf::myMultiCell($pdf, $width[5], $rowHeight, $rowData['size'],
                1, 'C', 1);
        pdf::myMultiCell($pdf, $width[6], $rowHeight, $rowData['color'],
                1, 'C', 1);
        pdf::myMultiCell($pdf, $width[7], $rowHeight, $rowData['upc'],
                1, 'C', 1);
        pdf::myMultiCell($pdf, $width[8], $rowHeight, $uom, 1, 'C');
        pdf::myMultiCell($pdf, $width[9], $rowHeight, $rowData['quantity'],
                1, 'C', 1);
        pdf::myMultiCell($pdf, $width[10], $rowHeight, $rowData['cartonLocation'],
                1, 'C', 1);
        pdf::myMultiCell($pdf, $width[11], $rowHeight, $rowData['prefix'],
                1, 'C', 1);
        pdf::myMultiCell($pdf, $width[12], $rowHeight, $rowData['suffix'],
                1, 'C', 1);
        pdf::myMultiCell($pdf, $width[13], $rowHeight, $available, 1, 'C', 1);
        pdf::myMultiCell($pdf, $width[14], $rowHeight, $volume, 1, 'C', 1);
        pdf::myMultiCell($pdf, $width[15], $rowHeight, $weight, 1, 'C', 1);

        $this->pdf->Ln();
    }

    /*
    ****************************************************************************
    */

    function printPageTruckTableHeader($titles)
    {
        $pdf = $this->pdf;
        $rowHeight = $this->rowHeight;

        $this->rowCount = 0;

        $pdf->AddPage('L');

        $count = 0;

        foreach ($titles as $title) {

            $columnWidth = $count + 1 < $this->truckTableColumnAmount ?
                    $this->truckTableColumnWidth : $this->truckTableLastColumnWidth;

            pdf::myMultiCell($pdf, $columnWidth, $rowHeight, $title);
        }

        $pdf->Ln();
    }

    /*
    ****************************************************************************
    */

    function printPageTruckTableRow($rowData, $titles)
    {
        $pdf = $this->pdf;
        $rowHeight = $this->rowHeight;

        $count = 0;

        foreach ($titles as $key) {

            $columnWidth = $count + 1 < $this->truckTableColumnAmount ?
                    $this->truckTableColumnWidth : $this->truckTableLastColumnWidth;

            pdf::myMultiCell($pdf, $columnWidth, $rowHeight, $rowData[$key]);
        }

        $pdf->Ln();
    }

    /*
    ****************************************************************************
    */

    function tableGroupOneRowDisplay($tableData, $pageData, $row)
    {
        $pdf = $this->pdf;

        for ($table=1; $table<=3; $table++) {
            if ($table == 1 && $row == 11) {

                $value = $pageData['scanOrderNumber'];

                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $style = [
                    'text' => $value,
                    'padding' => 1,
                    'fontsize' => 10,
                    'label' => $value,
                ];

                $columnWidth = $tableData[1]['columnWidth'];

                $width = $columnWidth[1] + $columnWidth[2];

                $barcodeWidth = 30;
                // place barcode in the middle of the cell
                $left = $this->leftMargin + round(($width - $barcodeWidth) / 2);
                $height = count($tableData[1]['data']) * $this->rowHeight + $this->leftMargin;

                $pdf->write1DBarcode($value, 'C128', $left, $height, $barcodeWidth,
                        $this->rowHeight * 3, 0.4, $style, 'T');

                $pdf->SetXY($x, $y);
                // draw a cell frame around the barcode
                pdf::myMultiCell($this->pdf, $width, $this->rowHeight * 5);

                $pdf->SetXY($x, $y);
            }

            $rowData = isset($tableData[$table]['data'][$row]) ?
                    $tableData[$table]['data'][$row] : ['border' => 0];

            $rowData['tableIndex'] = $table;

            if ($table == 2 && isset($rowData['selected'])) {
                $rowData['type'] = $row < 6 ? 'radio' : 'checkbox';
            }

            $rowData['tableData'] = $tableData;

            $this->pagePrintTableRow($rowData);
        }

        $pdf->Ln();
    }

    /*
    ****************************************************************************
    */

    function tableGroupTwoRowDisplay($tableData, $row)
    {
        $pdf = $this->pdf;

        for ($table=4; $table<=5; $table++) {

            $rowData = isset($tableData[$table]['data'][$row]) ?
                    $tableData[$table]['data'][$row] :
                    ['border' => 0];

            $rowData['tableIndex'] = $table;

            if ($table == 4 && $row != 1) {

                $oddRow = $row % 2;

                $rowData['border'] = $row < 8 && $oddRow || $row > 8 && ! $oddRow ?
                        'LRB' : 'LTR';
            }

            $rowData['tableData'] = $tableData;

            $this->pagePrintTableRow($rowData);
        }

        $pdf->Ln();
    }

    /*
    ****************************************************************************
    */

    function formDuplicate()
    {
        $truckOrderWaves = new \tables\truckOrderWaves($this);

        $truckOrderWaves->emptyTruckOrder($this->inputValues['scanOrderNumber']);

        for ($page = 1; $page <= $this->post['duplicate']; $page++) {
            foreach ($this->inputFields as $field) {
                $this->inputValues[$field][$page] = $this->inputValues[$field][0];
            }

            $this->inputValues['scanOrderNumber'][$page]
                = $this->inputValues['clientordernumber'][$page]
                = $this->inputValues['numberofcarton'][$page]
                = $this->inputValues['numberofpiece'][$page]
                = $this->inputValues['pickid'][$page] = NULL;
        }
    }

    /*
    ****************************************************************************
    */

    function getTDClass($key, $type=NULL)
    {
        if (isset($this->missingMandatoryValues[$key])
         || $type == 'missingValues' && isset($this->missingValues[$key])
         || $type == 'integerOnly' && isset($this->integerOnly[$key])) {

            return 'missField';
        }

        return NULL;
    }

    /*
    ****************************************************************************
    */

    function getMenuParam($param)
    {
        $page = $param['page'];

        if ($this->dbValues) {
            // Order Check-Out
            $param['preset'] = $this->dbValues[$page]['vendor'];
            $param['disabled'] = TRUE;
        } elseif (getDefault($this->vendorsArray)) {
            // Order Check-In (display order batches table)
            $submittedOrder = $this->post['scanOrderNumber'][$page];

            $vendorKeys = array_keys($this->vendorsArray);

            foreach ($vendorKeys as $vendorID) {
                if (isset($this->orderKeys[$submittedOrder])) {

                    $param['preset'] = $vendorID;
                    $param['disabled'] = TRUE;

                    return $param;
                }
            }
        }

        return $param;
    }

    /*
    ****************************************************************************
    */

    function batchOrderTable()
    {
        foreach ($this->vendorsArray as $vendor => $orderNumbers) {

            $rows = count($orderNumbers); ?>

        <tr>
            <td rowspan="<?php echo $rows; ?>"><?php echo $vendor; ?></td>

            <?php foreach ($orderNumbers as $orderNumber) { ?>

            <td class="orderNumberBatchDisplay"><?php echo $orderNumber; ?></td>
            <td>
                <span class="batchNumber" vendor="<?php echo $vendor; ?>"
                    initialValue="<?php echo $this->nextBatchID; ?>">
                    <?php echo $this->nextBatchID; ?>
                </span>
            </td>
                <input type="hidden" name="order_batch[]" class="orderBatch"
                       value="<?php echo $this->nextBatchID; ?>"  data-post>
                <input type="hidden" name="batch_vendor[]"
                       value="<?php echo $vendor; ?>" data-post>
                <input type="hidden" name="batch_orderNumber[]"
                       class="orderNumberBatchInput"
                       value="<?php echo $orderNumber; ?>" data-post>
            <td>
                <button class="addBatch">Make an Additional Batch</button>
            </td>
        </tr>

            <?php } ?>

        </tr>

            <?php ++$this->nextBatchID;
        }
    }

    /*
    ****************************************************************************
    */

    function modelGetTruckOrderInfo($scanOrderNumber)
    {
        $ajax = new datatables\ajax($this);
        $table = new tables\truckOrderWaves($this);

        $searchParams = [
            'userID' => (int)substr($scanOrderNumber, 0, 4),
            'assignNumber' => (int)substr($scanOrderNumber, 4),
        ];

        foreach ($searchParams as $field => $value) {
            $ajax->addControllerSearchParams([
                'values' => [$value],
                'field' => $field,
            ]);
        }

        $ajax->output($table, [
            'bFilter' => TRUE
        ]);

        return $ajax->searchParams;
    }

    /*
    ****************************************************************************
    */

    function getTruckOrderInfo($orderNumbers, $truckOrderWavesClass=NULL)
    {
        $truckOrderWaves = $truckOrderWavesClass ? $truckOrderWavesClass :
                new tables\truckOrderWaves($this);

        $orderNumber = reset($orderNumbers);

        $this->truckOrders =
                $truckOrderWaves->getExistingTruckOrders([$orderNumber]);

        $truckOrder = $this->truckOrders ? key($this->truckOrders) : $orderNumber;

        $this->modelGetTruckOrderInfo($truckOrder);
    }

    /*
    ****************************************************************************
    */

    function displayResults()
    {
        foreach ($this->success as $orderNumber => $success) {
            if (getDefault($this->closedOrders[$orderNumber])) {

                $message = 'Order # '.$orderNumber.' can not be modified<br>'
                        . 'This order has already been Order Processed';  ?>

        <br>
        <font size="5" color="#ffa500"><?php echo $message; ?></font>
        <br>

                <?php

                continue;
            }

            if ($this->isOrderImport) {
                $action = 'import';
            } else {
                $action = $this->checkType == 'Check-In' ? 'creat' : 'updat';
            }

            $color = $success ? '#090' : '#f00';
            $prefix = $success ? '' : 'Error '.$action.'ing ';
            $suffix = $success ? ' was successfully ' . $action . 'ed' : '';
            $message = $prefix . 'Order # ' . $orderNumber . $suffix; ?>

        <font size="5" color="<?php echo $color; ?>"><?php echo $message; ?></font>

            <?php if (isset($this->shortageProducts[$orderNumber])) {

                $message = '<br>This order has been saved as an Error Order '
                        . 'due to lack of inventory'; ?>

        <br>
        <font size="5" color="#ffa500"><?php echo $message; ?></font>
        <br>

            <?php } ?>

        <br>

        <?php }
    }

    /*
    ****************************************************************************
    */

    function checkImportFileType($type)
    {
        switch ($type) {
            case 'orderFiles':
                $table = new tables\orders($this);
                break;
            case 'truckOrderFiles':
                $table = new tables\truckOrderWaves($this);
                break;
            default:
                $this->errors['importError'] = 'Invalid Import Type';
                return FALSE;
        }

        if (getDefault($_FILES) && $_FILES[$type]['error']) {

            $this->errors['importError'] = 'Error submitting a file';

            return FALSE;
        }

        $pathInfo = pathinfo($_FILES[$type]['name'], PATHINFO_EXTENSION);

        if (! in_array($pathInfo, ['csv', 'xls', 'xlsx'])) {

            $this->errors['importError'] = 'Unexpected extension. '
                    . 'Require CSV or Excel file only';

            return FALSE;
        }

        $this->importer = $pathInfo == 'csv' ? new csv\importer($this, $table) :
                new excel\importer($this, $table);

        $this->importer->uploadPath =
                \models\directories::getDir('uploads', $type);

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function import($type)
    {
        $result = $this->checkImportFileType($type);

        if ($result) {

            $importResult = $this->importer->insertFile($type);

            $this->errors = $this->importer->errors;

            if ($type == 'orderFiles') {
                $this->success = $importResult;
            }
        }
    }

    /*
    ****************************************************************************
    */

    function importError()
    {
        $errors = $this->importer->errors;

        if (isset($errors['multipleSheets'])) {
            $this->errorFile([
                'captionSuffix' => 'with multiple sheets',
            ]);
        } else if (isset($errors['wrongType'])) {
            $this->errorFile([
                'captionSuffix' => 'that is not a valid Excel file',
            ]);
        } else {

            // table columns' errors section
            if (isset($errors['missingColumns'])) {
                $this->errorFile([
                    'captionSuffix' => 'with missing columns:',
                    'errorArray' => $errors['missingColumns'],
                ]);
            }

            if (isset($errors['duplicateColumns'])) {
                $this->errorFile([
                    'captionSuffix' => 'with duplicate columns:',
                    'errorArray' => $errors['duplicateColumns'],
                ]);
            }

            if (isset($errors['emptyCaptions'])) {
                $this->errorFile([
                    'captionSuffix' => 'with empty captions for the following'
                                      .' columns:',
                    'errorArray' => $errors['emptyCaptions'],
                ]);
            }

            if (isset($errors['invalidColumns'])) {
                $this->errorFile([
                    'captionSuffix' => 'with invalid columns:',
                    'errorArray' => $errors['invalidColumns'],
                ]);
            }

            // table cells' errors section
            if (isset($errors['missingReqs'])) {
                $this->errorDescription([
                    'errorArray' => $errors['missingReqs'],
                    'captionSuffix' => 'are missing required values:',
                ]);
            }

            if (isset($errors['extraColumn'])) {
                $this->errorDescription([
                    'errorArray' => $errors['extraColumn'],
                    'captionSuffix' => 'have empty column caption(s):',
                ]);
            }

            if (isset($errors['nonUTFReqs'])) {
                $this->errorDescription([
                    'errorArray' => $errors['nonUTFReqs'],
                    'captionSuffix' => 'have non UTF character(s):',
                ]);
            }

            if (isset($errors['invalidUPCs'])) {
                $this->errorFile([
                    'captionSuffix' => 'with invalid UPCs:',
                    'errorArray' => $errors['invalidUPCs'],
                ]);
            }

            if (isset($errors['discrepantUPCs'])) {
                $this->errorFile([
                    'captionSuffix' => 'with UPCs which sku, color or size '
                            . 'does not match database values:',
                    'errorArray' => $errors['discrepantUPCs'],
                ]);
            }

            if (isset($errors['mismatchUPCData'])) {
                $this->errorDescription([
                    'captionSuffix' => 'which sku, color or size values do not '
                            . 'match its previous values for the particular UPC:',
                    'errorArray' => $errors['mismatchUPCData'],
                ]);
            }

            if (isset($errors['wrongUPCs'])) {
                $this->errorFile([
                    'captionSuffix' => 'with UPCs that belong to another Client(s):',
                    'errorArray' => $errors['wrongUPCs'],
                ]);
            }

            if (isset($errors['nonPositiveReqs'])) {
                $this->errorDescription([
                    'errorArray' => $errors['nonPositiveReqs'],
                    'captionSuffix' => 'have nonpositive values:',
                    'rowSuffix' => 'value must be positive:',
                ]);
            }

            if (isset($errors['mismatchOrderData'])) {
                $this->errorDescription([
                    'captionSuffix' => 'which order details do not match '
                            . 'its previous values for the particular Order:',
                    'errorArray' => $errors['mismatchOrderData'],
                ]);
            }

            if (isset($errors['existingClientOrderNumbers'])) {
                $this->errorFile([
                    'captionSuffix' => 'with existing Client Order Numbers:',
                    'errorArray' => $errors['existingClientOrderNumbers'],
                ]);
            }
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function errorDescription($data)
    {
        $captionDescr = $data['captionSuffix'];

        $delimiter = isset($data['delimiter']) ? $data['delimiter'] : ', ';

        $descriptionCaption = getDefault($data['descriptionCaption'], NULL);
        $descriptionValues = getDefault($data['descriptionValues'], []);

        if (isset($data['rowSuffix'])) {
            $rowDescr = $data['rowSuffix'];
        } elseif (substr($captionDescr, 0, 4) == 'are ') {
            $rowDescr = 'is' . substr($captionDescr, 3);
        } elseif (substr($captionDescr, 0, 5) == 'have ') {
            $rowDescr = 'has' . substr($captionDescr, 4);
        } else {
            $rowDescr = $captionDescr;
        }

        $caption = getDefault($data['caption'], 'row'); ?>

        <div class="failedMessage blockDisplay">
            <strong>The following import <?php echo $caption; ?>s <?php echo
                $captionDescr; ?></strong><br>

            <?php

            $count = 0;

            foreach ($data['errorArray'] as $key => $req) {

                echo ! $count || $delimiter == ', ' ? NULL : $delimiter;

                $rowDescr = $rowDescr ? ' ' . $rowDescr : ':'; ?>

                Spreadsheet <?php echo $caption . ' ' . $key . $rowDescr . ' '
                        . implode($delimiter, $req);

                if (getDefault($descriptionValues[$key])) { ?>

                <br><?php echo $descriptionCaption
                        . implode(',', $descriptionValues[$key]);

                } ?>

                <br> <?php

                $count++;
            } ?>

        </div><?php

        $this->importer->errors[] = TRUE;
    }

    /*
    ****************************************************************************
    */

    function errorFile($descriptions)
    { ?>

        <div class="failedMessage blockDisplay">
            You have submitted a file <?php echo $descriptions['captionSuffix']; ?>

            <br>

            <?php if (isset($descriptions['errorArray'])) {
                echo implode('<br>', array_keys($descriptions['errorArray']));
            } ?>

        </div><?php

        $this->importer->errors[] = TRUE;
    }

    /*
    ****************************************************************************
    */

    function displayImportResults($type)
    {
        $orderImport = $type == 'orderImport' && $this->isOrderImport;
        $truckOrderImport = $type == 'truckOrderImport' && $this->isTruckOrderImport;

        if (! $orderImport && ! $truckOrderImport) {
            return;
        }

        if (key_exists('importError', $this->errors)) { ?>

        <div class="failedMessage blockDisplay">
            <?php echo $this->errors['importError'];?>
        </div>

        <?php } elseif ($this->errors || getDefault($this->importer->errors)) {
            $this->importError();
        } else { ?>

        <div class="showsuccessMessage">
            Your file has been imported successfully!
            <br>

                <?php if ($this->checkType == 'Check-Out') { ?>

            <span  style="font-weight: bold;color: green">
                    <?php echo 'Create a Pick Ticket'; ?>
            </span>

                <?php } ?>

        </div>

        <?php }
    }

    /*
    ****************************************************************************
    */

    function displayPickingTables($orderNumber, $tableCount)
    {
        if (! isset($this->products[$orderNumber])) {
            return;
        }

        $warehouseCount = count($this->products[$orderNumber]);

        foreach ($this->products[$orderNumber] as $warehouseType => $products) {

            $tableType = $this->closedOrders[$orderNumber] ? 'closed' : 'active'; ?>

        <br>
        <table class="pickingTable" border="1" width="100%"
               data-order-number="<?php echo $orderNumber; ?>"
               data-vendor="<?php echo $this->orderVendors[$orderNumber]; ?>"
               data-active="<?php echo $tableType; ?>"
               data-warehouse-type="<?php echo $warehouseType; ?>">

            <?php if ($warehouseCount > 1) { ?>

            <caption><?php echo $warehouseType == 'regular' ? 'Master Cartons' :
                    'Mixed Items Cartons'; ?><caption>

            <?php } ?>

            <tr>
                <th class="pickingAddRemoveCell"></th>
                <th class="pickingSKUCell">SKU</th>
                <th class="pickingSizeCell">Size</th>
                <th class="pickingColorCell">Color</th>
                <th class="pickingUPCCell">UPC</th>

            <?php if (! $this->closedOrders[$orderNumber]) { ?>

                <th class="pickingPieceQuantityCell">Piece Quantity</th>
                <th class="pickingPrimeLocationCell">Prime Location</th>

            <?php } ?>

                <th class="pickingPiecesPickedCell">Pieces Picked</th>
                <th class="pickingActualLocationCell">Actual Location</th>

            <?php if (! $this->closedOrders[$orderNumber]) { ?>

                <th class="cycleCountAssignedToCell">Cycle Count<br>Assigned To</th>
                <th class="cycleCountReportNameCell">Cycle Count<br>Report Name</th>
                <th class="cycleCountDueDateCell">Cycle Count<br>Due Date</th>

            <?php } ?>

            </tr>

            <?php $this->addPickingTable([
                'products' => $products,
                'orderNumber' => $orderNumber,
                'tableCount' => $tableCount++,
                'warehouseType' => $warehouseType,
            ]); ?>

        </table>

        <?php }

        return $tableCount;
    }

    /*
    ****************************************************************************
    */

    function addPickingTable($data)
    {
        $products = $data['products'];
        $orderNumber = $data['orderNumber'];
        $tableCount = $data['tableCount'];
        $warehouseType = $data['warehouseType'];

        $row = 0;

        $closedOrder = $this->closedOrders[$orderNumber];

        foreach ($products as $product) {

            $oddRowsClass = $row++ % 2 ? NULL : 'oddRows';

            if ($product['scanOrderNumber'] != $orderNumber) {
                continue;
            }

            $upcID = $product['upcID'];
            $location = $product['cartonLocation'];
            $quantity = $product['quantity']; ?>

        <tr class="<?php echo $oddRowsClass; ?>">
            <td class="pickingAddRemoveCell">

            <?php if (! $closedOrder) { ?>

                <button class="addRemoveSkuLocation"
                        data-table-index="<?php echo $tableCount; ?>">-</button>

            <?php } ?>

            </td>
            <td class="pickingSKUCell">

                <?php echo $product['sku'];

                if (! $closedOrder) {  ?>

                <input type="hidden" class="pickingSKU" data-post
                       name="sku[]" value="<?php echo $product['sku']; ?>">

                <?php } ?>

            </td>
            <td class="pickingSizeCell">

                <?php echo $product['size'];

                if (! $closedOrder) {  ?>

                <input type="hidden" class="pickingSize"
                       name="size[]" value="<?php echo $product['size']; ?>">

                <?php } ?>

            </td>
            <td class="pickingColorCell">

                <?php echo $product['color'];

                if (! $closedOrder) {  ?>

                <input type="hidden" class="pickingColor"
                       name="color[]" value="<?php echo $product['color']; ?>">

                <?php } ?>

            </td>
            <td class="pickingUPCCell">

                <?php echo $product['upc'];

                if (! $closedOrder) {  ?>

                <input type="hidden" class="pickingUPC" name="upc[]"
                       data-post value="<?php echo $product['upc']; ?>">
                <input type="hidden" class="pickingUPCID" name="upcID[]"
                       data-post value="<?php echo $product['upcID']; ?>">

                <?php } ?>

            </td>

            <?php if (! $closedOrder) { ?>

            <td class="pickingPieceQuantityCell">
                <input type="hidden" class="pickingPieceQuantity" data-post
                       name="pickingPieceQuantity[]" value="<?php echo $quantity; ?>">
                <?php echo $quantity; ?>
            </td>
            <td class="pickingPrimeLocationCell">
                <input type="hidden" class="pickingPrimeLocation" data-post
                       name="pickingPrimeLocation[]" value="<?php echo $location; ?>">
                <?php echo $location; ?>
            </td>

            <?php } ?>

            <td class="pickingPiecesPickedCell">

            <?php if ($closedOrder) {
                echo $quantity;
            } else { ?>

                <input class="pickingPiecesPicked" type="number" min="1"
                       max="99999999" name="quantity[]" data-post
                       value="<?php echo $quantity; ?>">

            <?php } ?>

            </td>
            <td class="pickingActualLocationCell">

            <?php if ($closedOrder) {
                echo $location;
            } else { ?>

                <select class="pickingActualLocation" data-post
                        name="cartonLocation[]">

                <?php $this->addPickingLocations(
                    $this->pickingLocations[$orderNumber][$warehouseType][$upcID],
                    $location
                ); ?>

                </select>

            <?php }

            if (! $this->closedOrders[$orderNumber]) { ?>

            <td class="cycleCountAssignedToCell">
                <select class="cycleCountAssignedTo" data-post
                        name="cycleCountAssignedTo[]">

                <?php

                $selected = 'selected';

                foreach ($this->user as $id => $values) { ?>

                    <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php
                        echo $values['username']; ?></option>

                <?php

                    $selected = NULL;
                } ?>

                </select>
            </td>
            <td class="cycleCountReportNameCell">
                <input type="text" class="cycleCountReportName"
                       name="cycleCountReportName[]" data-post value="">
            </td>
            <td class="cycleCountDueDateCell">
                <input type="text" class="cycleCountDueDate datepicker"
                       name="cycleCountDueDate[]" data-post value="">
            </td>

            <?php } ?>

        </tr>

        <?php }

        $oddRowsClass = $row++ % 2 ? NULL : 'oddRows'; ?>

        <?php if (! $closedOrder) { ?>

        <tr class="<?php echo $oddRowsClass; ?>">
            <td>
                <button class="addRemoveSkuLocation"
                        data-table-index="<?php echo $tableCount; ?>">+</button>
            </td>

            <?php

            $productColumnCount = 8;
            $cycleCountColumns = $closedOrder ? 0 : 3;

            $columnCount = $productColumnCount + $cycleCountColumns;

            for ($count = 0; $count < $columnCount; $count++) {

                $class = NULL;

                $cycleCountColumn = $count - $productColumnCount;

                switch ($cycleCountColumn) {
                    case 0:
                        $class = 'class="cycleCountAssignedToCell"';
                        break;
                    case 1:
                        $class = 'class="cycleCountReportNameCell"';
                        break;
                    case 2:
                        $class = 'class="cycleCountDueDateCell"';
                        break;
                    default:
                        $class = NULL;
                        break;
                } ?>

            <td <?php echo $class; ?>></td>

            <?php } ?>

        </tr>

        <?php }
    }

    /*
    ****************************************************************************
    */

    function addPickingLocations($pickingLocations, $location)
    {
        foreach ($pickingLocations as $key => $available) {

            $selected = $key == $location ? 'selected' : NULL; ?>

            <option data-available="<?php echo $available; ?>"
                    <?php echo $selected; ?>><?php echo $key; ?></option>

        <?php }
    }

    /*
    ****************************************************************************
    */

}
