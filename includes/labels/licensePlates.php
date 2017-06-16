<?php

namespace labels;

class licensePlates
{
    public $html;

    static $plateInfo = [];

    static $cartonInfo = [];

    static $firstPage = TRUE;

    static $pageCounter = 0;

    static $font = 20;

    static $barHeight = 50;

    static $txtHeight = 50;

    /*
    ****************************************************************************
    */

    function writePDFPage($pdf, $font, $txt, $barcode, $style, $counter = FALSE, $container = FALSE, $location = FALSE, $upcInfo=[])
    {
        $info = FALSE;

        if ($upcInfo) {
            $info = implode('', array_values($upcInfo));
        }

        self::$firstPage ? NULL : $pdf->addPage('L');
        self::$firstPage = FALSE;

        $pdf->SetFont('helvetica', '', $font);
        $pdf->MultiCell(270, self::$txtHeight, $txt, 0, 'C', 0, 0, '', '', true, 0, false, true, 0);
        $pdf->Ln();
        $pdf->write1DBarcode($barcode, 'C128', '', '', 200, self::$barHeight, 20, $style, 'N', $showCode = FALSE);


        $pdf->SetFont('helvetica', '', self::$font);
        $pdf->MultiCell(250, 10, $counter.$container.$location.$info, 0, 'C', 0, 0, '', '', true, 0, false, true, 0);

        self::$pageCounter++;
    }

    /*
    ****************************************************************************
    */

    function getPlateData($params)
    {
        if (self::$plateInfo) {
            return;
        }

        $db = getDefault($params['db']);
        $nsi = getDefault($params['nsi']);
        $term = getDefault($params['term']);
        $search = getDefault($params['search']);
        $locOrder = getDefault($params['locOrder']);

        $clause = $orderBy = NULL;
        switch ($search) {
            case 'batch':
                $clause = $search;
                break;
            case 'dateEntered':
                $clause = 'DATE(dateEntered)';
                break;
            case 'location':
                $clause = 'l.displayName';
                break;
            case 'warehouseID':
                $clause = 'v.warehouseID';
                $orderBy = 'ORDER BY l.distance ASC';
                break;
            case 'container':
                $clause = 'co.name';
                break;
            case 'recNum':
            case 'recNums':
                $clause = 'co.recNum';
                break;
            case 'plates':
                $clause = 'p.id';
                break;
            default:
                die('Invalid Search: '.$search);
        }

        $sql = NULL;

        $finalTerm = is_array($term) ? $term : [$term];
        $finalClause = is_array($term) ?
            $clause . ' IN (' . $db->getQMarkString($term) . ')' :
            $clause . ' = ?';

        if ($nsi) {
            $sql = 'SELECT    s.id,
                              DATE(setDate) AS setDate,
                              storeNumber
                    FROM      nsi_shipping s
                    LEFT JOIN nsi_shipping_batches b ON b.id = s.batch
                    WHERE     ' . $finalClause;

        } else {

            $vendors = new \tables\vendors($db);
            $plates = new \tables\plates($db);

            $vendorSelect = $vendors->fields['fullVendorName']['select'];

            $join = getDefault($params['level']) == 'order' ?
                    'LEFT JOIN neworder n ON n.id = ca.orderID' : NULL;

            $select = getDefault($params['level']) == 'order' ? ',
                    n.scanordernumber' : NULL;

            $sql = 'SELECT    p.id,
                              DATE(p.dateEntered) AS dateEntered,
                              l.displayName AS location,
                              co.name AS container,
                              u.username,
                              ' . $vendorSelect . ' AS vendorName,
                              w.id AS warehouseID,
                              p.id AS plate,
                              b.prefix,
                              upc,
                              sku,
                              size,
                              color
                              ' . $select . '
                    FROM      ' . $plates->table . '
                    -- LEFT JOINs are necessary when printing Labels for License
                    -- Plates that are not present in inventory_cartons table
                    LEFT JOIN inventory_cartons ca ON ca.plate = p.ID
                    LEFT JOIN inventory_batches b ON ca.batchID = b.id
                    LEFT JOIN inventory_containers co ON co.recNum = b.recNum
                    LEFT JOIN locations l ON l.id = ca.locID
                    LEFT JOIN upcs up ON up.id = b.upcID
                    LEFT JOIN vendors v ON v.id = co.vendorID
                    LEFT JOIN warehouses w ON v.warehouseID = w.id
                    ' . $join . '
                    WHERE     ' . $finalClause . '
                              ' . $orderBy;
        }

        $tmp = $db->queryResults($sql, $finalTerm);
        self::$plateInfo = $tmp ;

        if (! self::$plateInfo) {
            return;
        }

        //get the carton count and uom count for each LP
        $plateKeys = array_keys(self::$plateInfo);

        $cartonSql = '
            SELECT    CONCAT(plate, "-", uom),
                      plate,
                      count(ca.id) AS carton,
                      uom
            FROM      inventory_cartons ca
            WHERE     NOT isSplit
            AND       plate IN (' . $db->getQMarkString($plateKeys) . ')
            GROUP BY  plate,
                      batchID,
                      uom';

        $cartonResults = $db->queryResults($cartonSql, $plateKeys);

        foreach ($cartonResults as $key => $carton) {

            $plate = strstr($key, '-' , TRUE);

            self::$cartonInfo[$plate] = getDefault(self::$cartonInfo[$plate], NULL);
            self::$cartonInfo[$plate] .= $carton['carton'] . 'x'
                    . $carton['uom'] . '/';
        }

        if ($locOrder) {

            $indexLoc = \tables\locations::getLocationIndex($plate);

            foreach (self::$plateInfo as $plate) {
                // add License Plate number to index because different
                // License Plates can have the same Location Name
                $indexLoc .= $plate['plate'];

                $newArray[$indexLoc] = $plate;
            }

            ksort($newArray);

            self::$plateInfo = $newArray;
        }

        return self::$plateInfo;
    }

    /*
    ****************************************************************************
    */

    function addLicensePlate($params)
    {
        $upc = $po = $sku = $size = $color = $cartons = $counter = $orderNum = NULL;

        $nsi = getDefault($params['nsi']);
        $concat = getDefault($params['concat']);

        $displayLevel = getDefault($params['level']);

        self::$plateInfo = getDefault($params['plateInfo'], NULL);

        $pdf = new \TCPDF('L', 'mm', 'Letter', true, 'UTF-8', false);

        $pdf = $concat ? $concat : $pdf;

        $fileName = getDefault($params['fileName']);
        $save = getDefault($params['save']) ? 'F' : 'I';

        self::getPlateData($params);

        if (! self::$plateInfo) {
            echo '<br>No License Plates Found';
            return;
        }

        $cnt = 1;
        $totalPlates = count(self::$plateInfo);

        $style = create::getPDF($pdf);

        $container = getDefault($container) ? $container : '';
        $location = getDefault($location) ? $location : '';

        foreach (self::$plateInfo as $row) {

            $barcode = $row['plate'];

            if ($nsi) {
                $storeNumber = getDefault($row['storeNumber']);
                $numbersOnly = preg_replace('/[^0-9]+/', NULL, $storeNumber);
                $barcode = sprintf('%04d', $numbersOnly) . $barcode;
            }

            if ($nsi) {
                $txt = 'Store Number: ' . $row['storeNumber'].
                        chr(10) . 'Date: ' . $row['setDate'] . chr(10) . $barcode;
            } else if ($displayLevel === 'order') {
                self::$font = 25;
                self::$barHeight = 70;
                self::$txtHeight = 70;

                $vendorName = getDefault($row['vendorName'])
                    ? chr(10) . 'Client Name: ' . $row['vendorName']
                    : NULL;

                if (getDefault($row['scanordernumber'])) {
                    $orderNum = chr(10) . 'Order #: ' . $row['scanordernumber'];
                }

                $txt = 'Administrator: ' . $row['username'].
                        $vendorName.
                        chr(10) . 'Date Created: ' . $row['dateEntered'].
                        $orderNum .
                        chr(10) . 'License Plate: ' . $barcode;

                $counter = $totalPlates > 1 ? $cnt . '/' . $totalPlates : NULL;
                $cnt++;
            } else {
                $vendorName = isset($row['vendorName'])
                    ? chr(10) . 'Client Name: ' . $row['vendorName']
                    : NULL;

                $txt = 'Administrator: ' . $row['username'].
                        $vendorName.
                        chr(10) . 'Date Created: ' . $row['dateEntered'].
                        chr(10) . 'License Plate: ' . $barcode;

                $carton = getDefault(self::$cartonInfo[$barcode]) ?
                        rtrim(self::$cartonInfo[$barcode], '/') : NULL;


                if (getDefault($row['container'])) {
                    $container = 'Container #: ' . $row['container'];
                }
                if (getDefault($row['location'])) {
                    $location = chr(10) . 'Location #: ' . $row['location'];
                }
                if (getDefault($row['upc'])) {
                    $upc = chr(10) . 'UPC #: ' . $row['upc'];
                }
                if (getDefault($row['prefix'])) {
                    $po = chr(10) . 'PO #: ' . $row['prefix'];
                }
                if (getDefault($row['sku'])) {
                    $sku = chr(10) . 'SKU #: ' . $row['sku'];
                }
                if (getDefault($row['size'])) {
                    $size = chr(10) . 'Size #: ' . $row['size'];
                }
                if (getDefault($row['color'])) {
                    $color = chr(10) . 'Color #: ' . $row['color'];
                }

                if ($carton) {
                    $cartons = chr(10) . 'Cartons : ' . $carton;
                }
            }

            $style = [
                'position' => 'C',
                'align' => 'C',
                'stretch' => false,
                'fitwidth' => true,
                'cellfitalign' => 'C',
                'border' => false,
                'hpadding' => 0,
                'vpadding' => 0,
                'fgcolor' => array(0,0,0),
                'bgcolor' => false, //array(255,255,255),
                'text' => true,
                'font' => 'helvetica',
                'fontsize' => 12,
            ];

            $upcInfo = [
                'upc' => $upc,
                'po' =>  $po,
                'sku' => $sku,
                'size' => $size,
                'color' => $color,
                'carton' => $cartons
            ];

            if ($nsi){
                $this->writePDFPage($pdf, self::$font, $txt, $barcode, $style, $counter);
            } else if($displayLevel === 'order') {
                $this->writePDFPage($pdf, self::$font, $txt, $barcode, $style, $counter);
            } else {
                $this->writePDFPage($pdf, self::$font, $txt, $barcode, $style, $counter, $container,
                        $location, $upcInfo);

                for ($i=0; $i<0; $i++) {
                    $pdf->copyPage(self::$pageCounter);
                }

                self::$pageCounter += 3;
            }
        }

        if (! $concat) {
            $pdf->output($fileName, $save);
        }

        return $pdf;
    }

    /*
    ****************************************************************************
    */

}
