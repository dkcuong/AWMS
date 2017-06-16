<?php

namespace inventory;

use \Exception;
use common\pdf;
use tables\inventory\cartons;

class transfers
{
    public $app;
    public $output;
    public $orders;
    public $cartons;
    public $arrayUPC;
    public $warningMsg;
    static $rowHeight = 5;
    static $pageLength = 220;

    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        $this->app = $app;
        $this->orders = new \tables\orders($app);
        $this->cartons = new \tables\inventory\cartons($app);
    }

    /*
    ****************************************************************************
    */

    public function importToMezzanine($data)
    {
        $this->output = NULL;

        $this->checkValidateData($data);


        $this->getCartonsForTransfer();
        $this->splitAndRelocate();
        $transferID = $this->saveTransfer();

        return $transferID;
    }

    /*
    ****************************************************************************
    */

    function checkValidateData($data)
    {
        //Check input invalid

        if (! $data) {
            throw new Exception('Data is Not Null');
        } elseif(! is_array($data)) {
            throw new Exception('Type is wrong!');
        }

        $app = $this->app;
        $upcs = new \tables\upcs($app);
        $warehouses = new \tables\warehouses($app);

        $vendorKeys = $upcKeys = $vendorNames = $upcData = $locationParams = [];

        foreach ($data as $rowID => $value) {
            if (! is_array($value)) {
                throw new Exception('Input data Invalid!');
            }

            $vendor = $value['client'];
            $upc = $value['upc'];

            $vendorKeys[$vendor] = TRUE;
            $upcKeys[$upc] = TRUE;
        }

        $vendorNames = array_keys($vendorKeys);

        if (count(array_unique($vendorNames)) !== 1) {
            throw new Exception('Input more than one Client data!');
        }

        $upcData = array_keys($upcKeys);

        $clientInfo = $warehouses->getByFullName($vendorNames);
        $upcInfo = $upcs->getUpcs($upcData);

        $vendorID = $warehouseID = 0;

        $errorMsg = '';

        foreach ($data as $rowID => $value) {

            $vendorName = $value['client'];

            if (! isset($clientInfo[$vendorName])) {
                $errorMsg .= 'Invalid warehouse: "' . $vendorName
                        . '" in request: #' . $rowID . '<br>';

                continue;
            }

            $warehouseID = $clientInfo[$vendorName]['warehouseID'];
            $vendorID = $clientInfo[$vendorName]['vendorID'];

            // check UPC
            $upc = $value['upc'];

            if (! isset($upcInfo[$upc])) {
                $errorMsg .= 'Invalid UPC: "' . $upc . '" in request: #'
                    . $rowID . '<br>';
                continue;
            }

            if (! $upcInfo[$upc]['active']) {
                $errorMsg .= 'UPC: "' . $upc . '" is Inactive #'
                    . $rowID . '<br>';
            }

            // check SKU
            $sku = $value['sku'];
            if ($upcInfo[$upc]['sku'] != $sku) {
                $errorMsg .= 'UPC: "' . $upc . '" do not match with SKU: "'.
                        $sku . '" in request: #' . $rowID . '<br>';
            }

            //check upc existed on warehouse
            $isUpcExistedOnWarehouse = $this->isUpcExistedOnWarehouse(
                $upcInfo[$upc]['id'],
                $vendorID
            );

            if (! $isUpcExistedOnWarehouse) {
                $errorMsg .= 'Client ' . $vendorName . ' have not upc: "' .
                    $upc . '" in request: #' . $rowID . '<br>';
            }

            //check duplicate UPCs in the same location of Mezzannine
            $upcID = $upcInfo[$upc]['id'];
            $locationsMezzanine = $this->getLocationsMezzanineHaveUPC($upcID);

            if (! $locationsMezzanine) {
                 $errorMsg .= 'UPC: "' . $upc . '" is not in Locations MinMax'
                         . ' in request: #' . $rowID . '<br>';
            }

            //check Duplicate UPC in more than 1 location Mezzanine
            $this->checkDuplicateUPCLocationMezzanine([
                'locationsMezzanine' => $locationsMezzanine,
                'upc' => $upc,
                'rowID' => $rowID
            ]);

        }

        if ($errorMsg) {
            $errorMsg = 'Errors:<br>' . $errorMsg . '<br>';
            throw new Exception ($errorMsg);
        }

        foreach ($data as $key => $value) {

            $vendorName = $value['client'];
            $upc = $value['upc'];

            $upcID = $upcInfo[$upc]['id'];
            $this->output[] = [
                'input' => [
                    'vendorID' => $vendorID,
                    'client' => $vendorName,
                    'upcID' => $upcID,
                    'upc' => $upc,
                    'mezzanineID' => $key,
                ],
                'output' => []
            ];
        }
    }

    /*
    ****************************************************************************
    */

    function getClientID()
    {
        return reset($this->output)['input']['vendorID'];
    }

    /*
    ****************************************************************************
    */

    private function getCartonsForTransfer()
    {
        $pieceTotals = $transferData = [];

        foreach ($this->output as $request) {

            $locID = $request['input']['mezzanineID'];
            $upcID = $request['input']['upcID'];
            $vendorID = $request['input']['vendorID'];
            $pieces = $request['input']['pieces'];

            $pieceTotals[$vendorID][$upcID] =
                    getDefault($pieceTotals[$vendorID][$upcID], 0) + $pieces;

            $transferData[$vendorID][$upcID][$locID] =
                    getDefault($transferData[$vendorID][$upcID][$locID], 0) + $pieces;

            $vendorData[$vendorID] = $request['input']['client'];
            $upcData[$upcID] = $request['input']['upc'];
        }

        $ignoreCartons = [];

        $this->getTransferInventory([
            'pieceTotals' => $pieceTotals,
            'ignoreCartons' => $ignoreCartons,
            'vendorData' => $vendorData,
            'upcData' => $upcData,
            'transferData' => $transferData
        ]);
    }

    /*
    ****************************************************************************
    */

    private function splitCartonForMezzanine()
    {
        $params = [];
        $requestCount = 1;

        foreach ($this->output as &$request) {

            $mezzanineID = $request['input']['mezzanineID'];

            $params[$mezzanineID] = getDefault($params[$mezzanineID], []);

            foreach ($request['output'] as &$row) {

                $ucc = $row['carton']['ucc128'];

                $uoms = array_fill(0, $row['carton']['uom'], 1);

                $result = $this->cartons->split([$ucc => $uoms]);

                if ($result['error']) {
                    throw new Exception(
                        'Error split carton for request: #' . $requestCount);
                }

                $children = $this->cartons->getChildrenData($row['carton']['id']);

                $params[$mezzanineID] += $children;
                $this->setChildren($children, $row);

            }
            $requestCount++;
        }

        return $params;
    }

    /*
    ****************************************************************************
    */

    private function setChildren($children, &$row)
    {
        foreach ($children as $childID => $child) {

            $childUOM = cartons::customStrPad([
                'input' => $child['uom'],
                'padLength' => 3,
                'padString' => '0',
                'padType' => STR_PAD_LEFT
            ]);

            $childCartonID = cartons::customStrPad([
                'input' => $child['cartonID'],
                'padLength' => 4,
                'padString' => '0',
                'padType' => STR_PAD_LEFT
            ]);

            $ucc128 = $row['carton']['vendorID'] .
                $row['carton']['batchID'] . $childUOM .
                $childCartonID;

            $row['splitCartons'][$childID] = $ucc128;
        }
    }

    /*
    ****************************************************************************
    */

    private function splitAndRelocate()
    {
        $params = $this->splitCartonForMezzanine();

        $this->app->beginTransaction();
        foreach ($params as $mezzanineID => $children) {
            // update location for children
            $childrenID = array_keys($children);
            $this->cartons->updateLocations($childrenID, $mezzanineID);
        }
        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    private function saveTransfer()
    {
        $transfers = new \tables\transfers($this->app);

        // save transferItem
        $requestCountItemSql = 'INSERT INTO transfer_items (
                        transferID,
                        vendorID,
                        upcID,
                        pieces,
                        locationID
                    ) VALUE (?, ?, ?, ?, ?)';

        // save Location Info
        $locInfoSql = 'INSERT IGNORE locations_info (
                          vendorID,
                          locID,
                          upcID
                       ) VALUE (?, ?, ?)';

        // save transferCarton
        $cartonSql = 'INSERT INTO transfer_cartons (
                          transferItemID,
                          cartonID
                      ) VALUE (?, ?)';

        // save transfer
        $sql = 'INSERT INTO transfers (
                    userID,
                    barcode
                ) VALUE (
                    ?, LEFT(MD5(?), 20)
                )';

        $transferID = $transfers->getNextID('transfers');
        $transferItemID = $transfers->getNextID('transfer_items');

        $this->app->beginTransaction();

        $this->app->runQuery($sql, [\access::getUserID(), $transferID]);

        foreach ($this->output as $request) {

            $this->app->runQuery($requestCountItemSql, [
                $transferID,
                $request['input']['vendorID'],
                $request['input']['upcID'],
                $request['input']['pieces'],
                $request['input']['mezzanineID']
            ]);

            $this->app->runQuery($locInfoSql, [
                $request['input']['vendorID'],
                $request['input']['mezzanineID'],
                $request['input']['upcID']
            ]);

            foreach ($request['output'] as $row) {
                foreach ($row['splitCartons'] as $cartonID => $ucc128) {

                    $this->app->runQuery($cartonSql, [
                        $transferItemID,
                        $cartonID
                    ]);
                }
            }

            $transferItemID++;
        }

        $this->app->commit();

        return $transferID;
    }

    /*
    ****************************************************************************
    */

    function getTransferInventory($data)
    {
        $upcData = $data['upcData'];
        $vendorData = $data['vendorData'];
        $pieceTotals = $data['pieceTotals'];
        $transferData = $data['transferData'];
        $ignoreCartons = $data['ignoreCartons'];
        $allocatedCartons = getDefault($data['allocatedCartons']);

        $results = $this->cartons->getMezzanineTransferInventory($pieceTotals,
                $ignoreCartons);

        if (! $results) {
            throw new Exception('Not enough inventory to complete transfer');
        }

        foreach ($results as $result) {

            $upcID = $result['upcID'];
            $vendorID = $result['vendorID'];

            $pieceTotals[$vendorID][$upcID] -= $result['uom'];
        }

        $errors = NULL;

        foreach ($pieceTotals as $vendorID => $values) {
            foreach ($values as $upcID => $values) {

                $vendor = $vendorData[$vendorID];
                $upc = $upcData[$upcID] ;
                $pieces = $values['limit'];

                if ($pieces > 0) {
                    $errors .= $vendor . ' does not have enough inventory in '
                            . 'the regular warehouse to transfer UPC ' . $upc
                            . ', shortage is ' . $pieces . ' piece(s)<br>';
                }
            }
        }

        if ($errors) {
            throw new Exception($errors);
        }

        $allocatedChildren = $childrenData = $cartonsToSplit = [];

        foreach ($transferData as $vendorID => $vendorValues) {
            foreach ($vendorValues as $upcID => $locationValues) {
                foreach ($locationValues as $locID => $quantity) {
                    foreach ($results as $invID => $carton) {
                        if ($vendorID != $carton['vendorID'] || $upcID != $carton['upcID']) {
                            continue;
                        }

                        $upc = $carton['upc'];
                        $batchID = $carton['batchID'];
                        $uom = $carton['uom'];

                        $transferData[$vendorID][$upcID][$locID] =
                                getDefault($transferData[$vendorID][$upcID][$locID], 0);

                        if ($transferData[$vendorID][$upcID][$locID] <= 0) {
                            // sufficient inventory was allocated
                            continue;
                        }

                        if ($transferData[$vendorID][$upcID][$locID] < $uom) {

                            $uomA = $transferData[$vendorID][$upcID][$locID];
                            $uomB = $uom - $uomA;

                            $cartonUOM = cartons::customStrPad([
                                'input' => $uom,
                                'padLength' => 3,
                                'padString' => '0',
                                'padType' => STR_PAD_LEFT
                                ]);

                            $cartonID = cartons::customStrPad([
                                'input' => $carton['cartonID'],
                                'padLength' => 4,
                                'padString' => '0',
                                'padType' => STR_PAD_LEFT
                            ]);

                            $ucc128 = $vendorID . $batchID . $cartonUOM . $cartonID;

                            $cartonsToSplit[$ucc128] = [$uomA, $uomB];

                            $allocatedChildren[$invID] = $uomA;
                            $childrenData[$invID] = [
                                'vendorID' => $vendorID,
                                'upcID' => $upcID,
                                'locID' => $locID
                            ];
                        } else {
                            $ignoreCartons[] = $invID;
                            $allocatedCartons[$vendorID][$upcID][$locID][] = [
                                'ucc128' => $carton['ucc128'],
                            ];
                        }

                        unset($results[$invID]);

                        $transferData[$vendorID][$upcID][$locID] -= $uom;
                    }
                }
            }
        }

        foreach ($transferData as $vendorID => $vendorValues) {
            foreach ($vendorValues as $upcID => $locationValues) {
                foreach ($locationValues as $locID => $quantity) {
                    if ($quantity <= 0) {
                        unset($transferData[$vendorID][$upcID][$locID]);
                    }
                }
            }
        }

        if ($cartonsToSplit) {

            $splitResult = $this->cartons->split($cartonsToSplit);

            if ($splitResult['error']) {
                throw new Exception('Error splitting cartons');
            }
        }

        if ($allocatedChildren) {
             $newCarton = $this->cartons->getChildrenUCCs($allocatedChildren);

            foreach ($newCarton as $invID => $values) {
                $ignoreCartons[] = $values['childID'];

                $vendorID = $childrenData[$invID]['vendorID'];
                $upcID = $childrenData[$invID]['upcID'];
                $locID = $childrenData[$invID]['locID'];

                $allocatedCartons[$vendorID][$upcID][$locID][] = $values['ucc128'];
            }
        }

        $upcPieceTotals = [];

        foreach ($transferData as $vendorID => $values) {
            foreach ($values as $upcID => $locationData) {
                foreach ($locationData as $locID => $pieces) {

                    $upcPieceTotals[$vendorID][$upcID] =
                            getDefault($upcPieceTotals[$vendorID][$upcID], 0) + $pieces;
                }
            }
        }

        $result = [
            'pieceTotals' => $upcPieceTotals,
            'ignoreCartons' => $ignoreCartons,
            'vendorData' => $vendorData,
            'upcData' => $upcData,
            'transferData' => $transferData,
            'allocatedCartons' => $allocatedCartons
        ];

        return $upcPieceTotals ? $this->getTransferInventory($result) : $result;
    }

    /*
    ****************************************************************************
    */

    static function getTransferReportData($app, $transferID)
    {
        $parentSql = '
            SELECT    CONCAT_WS("_", upc, l.id, lp.id, uom) AS primaryKey,
                      l.id AS targetLocID,
                      lp.id AS sourceLocID,
                      tr.id AS transfersID,
                      tr.userID,
                      barcode,
                      createDate,
                      CONCAT(w.shortName, "_", vendorName) AS vendor,
                      COUNT(DISTINCT ca.id) AS cartonCount,
                      uom,
                      COUNT(DISTINCT ca.id) * uom AS pieces,
                      upc,
                      sku,
                      color,
                      size,
                      l.displayName AS mezzanineName,
                      lp.displayName AS locationName
            FROM      transfer_items i
            JOIN      transfer_cartons t ON t.transferItemID = i.id
            LEFT JOIN inventory_splits sp ON sp.childID = t.cartonID
            JOIN      inventory_cartons ca ON ca.id = t.cartonID
            JOIN      inventory_batches b ON ca.batchID = b.id
            JOIN      inventory_containers co ON b.recNum = co.recNum
            JOIN      vendors v ON i.vendorID = v.id
            JOIN      upcs u ON i.upcID = u.id
            JOIN      locations l ON i.locationID = l.id
            JOIN      warehouses w ON l.warehouseID = w.id
            LEFT JOIN locations lp ON lp.id = t.fromLocID
            JOIN      transfers tr ON tr.id = i.transferID
            WHERE     transferID = ?
            GROUP BY  u.upc,
                      uom,
                      l.id,
                      lp.id
            ORDER BY  lp.displayName ASC';

        $parentResults = $app->queryResults($parentSql, [$transferID]);

        if (! $parentResults) {
            throw new Exception('Could not find: #' . $transferID);
        }

        $results = [];

        foreach ($parentResults as $values) {

            $values['locationName'] = getDefault($values['locationName'], 'NA');

            $key = $values['targetLocID'] . '_' . $values['upc'] . '_'
                    . $values['uom'];

            $values['locationName'] .= ' - ' . $values['pieces'] . ' pc';

            if (isset($results[$key])) {

                $results[$key]['cartonCount'] += $values['cartonCount'];
                $results[$key]['pieces'] += $values['pieces'];
                $results[$key]['locationName'] .= ',' . $values['locationName'];
            } else {
                $results[$key] = $values;
            }
        }

        $result = reset($results);

        return [
            'transfer' => [
                'id' => $result['transfersID'],
                'userID' => $result['userID'],
                'createDate' => $result['createDate'],
                'barcode' => $result['barcode']
            ],
            'upc' => $results
        ];
    }

    /*
    ****************************************************************************
    */

    static function pdfOutput($params)
    {
        $app = $params['app'];
        $transferID = $params['transferID'];
        $file = getDefault($params['file'], NULL) ;
        $includeJS = getDefault($params['includeJS'], NULL);

        $data = self::getTransferReportData($app, $transferID);

        $outputType = $file ? 'F' : 'I';
        $pdfOutput = $file ? $file : 'pdf';

        $rowHeight = self::$rowHeight * 3;

        $pdf = new \TCPDF('P', 'mm', 'Letter', TRUE, 'UTF-8', FALSE);

        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);
        $pdf->SetAutoPageBreak(TRUE, 0);
        $pdf->SetLeftMargin(10);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->SetFont('helvetica', '', 11);

        $pdf->AddPage();

        if ($includeJS) {
            $pdf->IncludeJS($includeJS);
        }

        $text = 'TransferID: #' . $data['transfer']['id'] . ' - '
              . 'Created: ' . $data['transfer']['createDate'];

        pdf::myMultiCell($pdf, 185, self::$rowHeight, $text, 0, 'L');
        pdf::myMultiCell($pdf, 15, self::$rowHeight, '1', 0, 'R');

        $pdf->Ln();

        $barcode = $data['transfer']['barcode'];

        $style = [
            'text' => $barcode,
            'padding' => 1,
            'fontsize' => 10,
            'label' => $barcode,
        ];

        $pdf->write1DBarcode($barcode, 'C128', '', '', 70, $rowHeight - 5,
                0.4, $style, 'T', FALSE);

        pdf::myMultiCell($pdf, 50, $rowHeight, NULL, 0);

        $pdf->Ln();

        pdf::myMultiCell($pdf, 65, $rowHeight, 'Client' . chr(10)
            . 'UPC' . chr(10) . 'Mezzanine Location');
        pdf::myMultiCell($pdf, 15, $rowHeight, chr(10) . 'Cartons');
        pdf::myMultiCell($pdf, 15, $rowHeight, chr(10) . 'UOM');
        pdf::myMultiCell($pdf, 15, $rowHeight, chr(10) . 'Pieces');
        pdf::myMultiCell($pdf, 50, $rowHeight, 'SKU' . chr(10) . 'Color' . chr(10) . 'Size');
        pdf::myMultiCell($pdf, 40, $rowHeight, chr(10) . 'Locations');

        $pdf->Ln();

        $rowCount = $pageHeigth = 0;
        $pageCount = 1;
        $totalPieces = $totalCartons = 0;

        $pdf->SetFillColor(224, 224, 224);

        foreach ($data['upc'] as $row) {

            $sourceLocations = explode(',', $row['locationName']);

            $lineCount = count($sourceLocations);

            $rowHeight = self::$rowHeight * max(3, $lineCount);

            $pageHeigth += $rowHeight;

            if ($pageHeigth >= self::$pageLength) {

                $pageHeigth = $rowCount = 0;

                $pdf->AddPage();

                pdf::myMultiCell($pdf, 200, self::$rowHeight, ++$pageCount, 0, 'R');

                $pdf->Ln();
            }

            $fill = $rowCount % 2;

            $text = $row['vendor']
                 . chr(10) . $row['upc']
                 . chr(10) . $row['mezzanineName'];

            pdf::myMultiCell($pdf, 65, $rowHeight, $text, 1, 'C', 0, $fill);

            pdf::myMultiCell($pdf, 15, $rowHeight, chr(10) . $row['cartonCount'],
                    1, 'C', 0, $fill);
            pdf::myMultiCell($pdf, 15, $rowHeight, chr(10) . $row['uom'], 1,
                    'C', 0, $fill);
            pdf::myMultiCell($pdf, 15, $rowHeight, chr(10) . $row['pieces'], 1,
                    'C', 0, $fill);

            $text = $row['sku']
                  . chr(10) . $row['color']
                  . chr(10) . $row['size'] ;

            pdf::myMultiCell($pdf, 50, $rowHeight, $text, 1, 'C', 0, $fill);

            $x = $pdf->GetX();
            $y = $pdf->GetY() - self::$rowHeight;

            for ($count=0; $count<max(3, $lineCount); $count++) {

                $y += self::$rowHeight;

                $pdf->SetXY($x, $y);

                $sourceLocation = getDefault($sourceLocations[$count], NULL);

                pdf::myMultiCell($pdf, 40, self::$rowHeight, $sourceLocation, 1,
                        'L', 1, $fill);
            }

            $pdf->SetXY($x, $y);

            $totalPieces += $row['pieces'];
            $totalCartons += $row['cartonCount'];

            $rowCount++;

            $pdf->Ln();
        }

        $fill = $rowCount % 2;

        pdf::myMultiCell($pdf, 65, self::$rowHeight, 'Total', 1, 'C', 0, $fill);
        pdf::myMultiCell($pdf, 15, self::$rowHeight, $totalCartons, 1, 'C', 0, $fill);
        pdf::myMultiCell($pdf, 15, self::$rowHeight, NULL, 1, 'C', 0, $fill);
        pdf::myMultiCell($pdf, 15, self::$rowHeight, $totalPieces, 1, 'C', 0, $fill);
        pdf::myMultiCell($pdf, 90, self::$rowHeight, NULL, 1, 'C', 0, $fill);

        $pdf->Output($pdfOutput, $outputType);
    }


    /*
    ****************************************************************************
    */

    function isUpcExistedOnWarehouse($upcID, $vendorID)
    {
        $sql = 'SELECT  count(*) AS number
                FROM    inventory_batches b
                JOIN    inventory_containers i ON b.recNum = i.recNum
                JOIN    vendors v ON v.id = i.vendorID
                WHERE   b.upcID = ?
                AND     v.id = ?';

        $result = $this->app->queryResult($sql, [
            $upcID,
            $vendorID
        ]);

        return $result['number'];
    }

    /*
    ****************************************************************************
    */

    function getLocationsMezzanineHaveUPC($upcID)
    {
        $sql = 'SELECT  l.displayName
                FROM    min_max mm
                JOIN    locations l ON l.id = mm.locID
                WHERE   upcID = ?
                AND     mm.active';

        $result = $this->app->queryResults($sql, [$upcID]);

        return $result ? array_keys($result) : NULL;
    }

    /*
    ****************************************************************************
    */

    function checkDuplicateUPCLocationMezzanine($params)
    {
        $locationsMezzanine = $params['locationsMezzanine'];
        $upc = $params['upc'];
        $rowID = $params['rowID'];

        if (count($locationsMezzanine) == 1) {
            return TRUE;
        }

        //This warning will be show before render pdf
        $this->warningMsg .=
                '\\nUPC: ' . $upc . ' in Locations Mezzanine:\\n';
        if (is_array($locationsMezzanine)) {
          $this->warningMsg .= implode(', \\n', $locationsMezzanine);
        }
        else {
          $this->warningMsg .= $locationsMezzanine;
        }
        $this->warningMsg .= '\\n in request: #' . $rowID . '\\n';
    }

    /*
    ****************************************************************************
    */

}
