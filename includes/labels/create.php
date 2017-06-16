<?php

namespace labels;

use \common\pdf;
use \common\tally;
use \tables\locations;
use \tables\inventory\cartons;

class create extends \tcpdf
{
    const SHOW_PDF = 1;

    const BATCH = 0;
    const PO = 1;
    const RA = 2;
    const SKU = 3;
    const CARTONS = 4;
    const PIECES = 5;
    const WAREHOUSE = 6;
    const DATE = 8;
    const USERNAME = 8;

    const NSI_REC_PO = 0;
    const NSI_REC_RA = 1;
    const NSI_REC_PALLET = 3;
    const NSI_REC_DATE = 5;

    const CUSTOMER_CODE = '81235011';
    const AI_CODE = '00';
    const GL_ADDRESS_LINE_1 = 'LIFEWORK C/O CUSTOM GOODS LLC 809 E';
    const GL_ADDRESS_LINE_2 = '236TH STREET CARSON, CA 90745';

    /*
    ****************************************************************************
    */

    static $pdf;
    static $index2;
    static $recNum;
    static $inventory = [];
    static $setByIndex = TRUE;
    static $manualInserts = FALSE;
    static $warehouseID = 0;
    static $masterLabels = [];
    static $piecesInBatches = [];
    static $masterLabelQuery = NULL;
    static $masterLabelParams = [];
    static $splitCarton = FALSE;
    static $unSplitCarton = FALSE;
    static $transferCarton = FALSE;
    static $pickTicketCarton = FALSE;
    static $pickTicketByOrderCarton = FALSE;
    static $pickTicketSingleOrder = FALSE;
    static $skipTayllyOutput = FALSE;
    static $processedCarton = FALSE;
    static $filePath = NULL;
    static $fileName = NULL;

    /*
    ****************************************************************************
    */

    static function byBatches($params)
    {
        $app = $params['app'];
        $batches = $params['batches'];

        $count = count($batches);

        $app->post['andOrs'] = $count == 1 ? [] :
            array_fill(1, $count - 1, 'or');

        array_unshift($app->post['andOrs'], 'and');

        $app->post['searchTypes'] = array_fill(0, $count, 'batchID');
        $app->post['searchValues'] = $batches;

        self::getDTLabels($params);
    }

    /*
    ****************************************************************************
    */

    static function getDTLabels($params)
    {
        $app = $params['app'];
        $bFilter = getDefault($params['bFilter'], FALSE);

        $download = getDefault($params['download']);

        $multiSelectTrigger = getDefault($params['multiSelect']);

        $cartonLabels = isset($app->get['cartonLabels']) ? TRUE : FALSE;

        if ($download) {
            $bFilter = FALSE;
            $cartonLabels = TRUE;
        }

        $ajax = isset($params['ajax']) ?
            $params['ajax'] : new \datatables\ajax($app);

        $model = isset($params['model']) ? $params['model'] :
            new cartons($app);

        $dtOptions = [
            'order' => ['containerRecNum' => 'desc', ],
            'bFilter' => $bFilter,
        ];

        // Reprints need assoc results
        if ($cartonLabels) {
            $dtOptions['queryString'] = TRUE;
        }

        // Use "=" instead of "LIKE" to compare strings for carton reprints
        if (! $bFilter) {
            $dtOptions['compareOperator'] = 'exact';
        }

        $queryInfo = $output =
            $ajax->output($model, $dtOptions, $multiSelectTrigger);

        $model->where = getDefault($model->where, 1);
        $model->where .= ' AND s.shortName NOT IN ("IN", "SH")';

        $initialCount = $cartonLabels ? NULL : count($output->params['data']);

        $app->failedReprint = $initialCount > 900 && $cartonLabels
            ? 'You may only print 900 labels at a time. '
                . 'You have tried to print ' . $initialCount . ' labels' : NULL;

        if ($cartonLabels && ! $app->failedReprint) {

            // No limits on download
            $limit = isset($queryInfo['limit']) && ! $download ?
                $queryInfo['limit'] : NULL;

                $ucc128 = $model->fields['ucc128']['select'];

                $sql = 'SELECT  ca.id,
                                ' . $ucc128 . ' AS ucc128,
                                CONCAT(w.shortName, "_", vendorName) AS vendor,
                                p.sku,
                                prefix,
                                upc,
                                color,
                                size,
                                batchID,
                                uom,
                                vendorID,
                                cartonID,
                                b.upcID,
                                locID,
                                plate,
                                l.displayName AS location,
                                co.name AS container,
                                co.recNum AS containerRecNum
                        FROM    inventory_containers co
                        JOIN    inventory_batches b ON co.recNum = b.recNum
                        JOIN    inventory_cartons ca ON b.id = ca.batchID
                        JOIN    vendors v ON v.id = co.vendorID
                        JOIN    warehouses w ON v.warehouseID = w.id
                        JOIN    locations l ON l.id = ca.locID
                        JOIN    upcs p ON p.id = b.upcID
                                ' . $queryInfo['clause'] . '
                                ' . $limit;

            $uccs = $app->queryResults($sql, $queryInfo['params']);

            $commaPos = strrpos($limit, ',');
            $spacePos = strrpos($limit, ' ');

            $pos = max(0, $commaPos, $spacePos);

            $outputLimit = $pos ? (int) substr($limit, $pos + 1) : 0;

            if ($uccs && $outputLimit > 0) {
                $batches = array_column($uccs, 'batchID');
                $uniqueBatches = array_unique($batches);

                $sql = 'SELECT   batchID,
                                 COUNT(id) AS cartonCount
                        FROM     inventory_cartons
                        WHERE    batchID IN (' .
                                    $app->getQMarkString($uniqueBatches) . ')
                        AND      isSplit
                        AND      unSplit
                        GROUP BY batchID
                        ORDER BY batchID ASC';

                $results = $app->queryResults($sql,
                        array_values($uniqueBatches));

                $cartonAmount = 0;

                foreach ($results as $batchID => $value) {
                    if ($cartonAmount + $value['cartonCount'] > $outputLimit) {

                        $skipBatch[$batchID] = NULL;
                    }
                    $cartonAmount += $value['cartonCount'];
                }

                if (isset($skipBatch)) {
                    foreach ($uccs as $key => $value) {
                        $batchID = $value['batchID'];
                        if (isset($skipBatch[$batchID])) {
                            unset($uccs[$key]);
                        }
                    }
                }
            }

            $params['labels'] = $uccs;
            $params['checkLocation'] = TRUE;

            if (count($queryInfo['params']) > 1) {
                // do not use tally sheet if more than one paramater was passed
                // to the searcher form
                self::$skipTayllyOutput = TRUE;
            }

            self::forCartons($params);

            $dtOptions['filePath'] = self::$filePath;
            $dtOptions['fileName'] = self::$fileName;

        } else if ($app->failedReprint) {
            // If the reprint fails, display the regular inventory
            unset($app->post);
            $output = $ajax->output($model, [
                'order' => ['setDate' => 'desc']
            ]);
        }

        return $dtOptions;
    }

    /*
    ****************************************************************************
    */

    static function forCartons($params)
    {
        $app = isset($params['db']) ? $params['db'] : $params['app'];
        $uccs = $params['labels'];
        $isDownload = isset($params['isDownload']) ? TRUE : FALSE;

        $fileName = NULL;

        if (! $uccs) {
            die('No Cartons Found');
        }

        if (self::$splitCarton
         || self::$unSplitCarton
         || self::$pickTicketCarton
         || self::$processedCarton) {

            self::commonLabelMaking($params);

            return;
        }

        // Check which cartons are splits and get their parents
        $sql = 'SELECT    childID,
                          ca.batchID,
                          CONCAT(
                            v.id, b.id, LPAD(uom, 3, 0), LPAD(cartonID, 4, 0)
                          ) AS ucc
                FROM      inventory_splits s
                LEFT JOIN inventory_cartons ca ON ca.id = s.parentID
                LEFT JOIN inventory_batches b ON b.id = ca.batchID
                LEFT JOIN inventory_containers co ON co.recNum = b.recNum
                LEFT JOIN vendors v ON v.id = co.vendorID
                WHERE     childID IN ('.$app->getQMarkString($uccs).')
                AND       s.active';

        $children = $app->queryResults($sql, array_keys($uccs));

        $params['labels'] = $batches = self::cartonsToBatches($uccs, $children);

        if (! $isDownload) {

            $batchesTable = new \tables\inventory\batches($app);

            $badBatches = $batchesTable->checkBadBatches($batches);

            // batches over 2000 cartons shall by downloaded but not displayed

            if ($badBatches) {

                self::$filePath = \models\directories::getDir('uploads', 'uccLabels');

                if (! is_dir(self::$filePath)) {
                    die('Create ' . self::$filePath . ' folder');
                }

                $batches = array_keys($batches);

                $firstBatch = reset($batches);
                $lastBatch = end($batches);

                $params['batches'] = $batches;

                self::$fileName = isset($app->post['uccLabelFile']) ?
                        $app->post['uccLabelFile'] :
                        pdf::getUCCLabelsDownloadName($firstBatch, $lastBatch);

                $params['save'] = TRUE;
                $params['files'] = self::$filePath . '/' . self::$fileName;

                $params['isDownload'] = TRUE;
            }
        }

        self::commonLabelMaking($params);
    }

    /*
    ****************************************************************************
    */

    static function getTCPDF()
    {
        return new \TCPDF('P', 'mm', 'Letter', true, 'UTF-8', false);
    }

    /*
    ****************************************************************************
    */

    static function cartonsToBatches($uccs, $children=[], $byIndex=FALSE)
    {
        $batches = [];

        foreach ($uccs as $id => $carton) {
            $parent = getDefault($children[$id]['ucc'], 'all');

            // If these are warehouse labels by location indexs, use 'all' as
            // the batches index so they don't get out of order
            $batch = $byIndex && self::$setByIndex ? $carton['location'] : $carton['batchID'];
            $parent = $byIndex && self::$setByIndex ? $carton['batchID'] : $parent;
            $batches[$batch][$parent][$id] = $carton;

        }

        return $batches;
    }

    /*
    ****************************************************************************
    */

    static function getInventory($params)
    {
        if (self::$inventory) {
            return;
        }

        $db = $params['db'];
        $recNum = getDefault($params['recNum']);
        $batches = getDefault($params['batches']);
        $recNums = getDefault($params['recNums']);
        $container = getDefault($params['container']);
        $warehouseID = getDefault($params['warehouseID']);

        $params = [];
        $clauses = [];

        if ($recNum) {
            $params[] = $recNum;
            $clauses[] = 'co.recNum = ?';
        }

        if ($recNums) {
            $params = $recNums;
            $clauses[] = 'co.recNum IN ('.$db->getQMarkString($recNums).')';
        }

        if ($batches) {
            $params = $batches;
            $clauses[] = 'b.id IN ('.$db->getQMarkString($batches).')';
        }

        if ($container) {
            $params[] = $container;
            $clauses[] = 'co.name = ?';
        }

        if ($warehouseID) {
            $params[] = $warehouseID;
            $clauses[] = 'v.warehouseID = ?';
        }

        $sql = 'SELECT    ca.id,
                          ca.id,
                          co.name AS container,
                          l.displayName AS location,
                          CONCAT(
                                v.id,
                                b.id,
                                LPAD(uom, 3, 0),
                                LPAD(cartonID, 4, 0)
                          ) AS ucc,
                          u.sku,
                          prefix,
                          upc,
                          color,
                          size,
                          ca.batchID,
                          LPAD(uom, 3, 0) AS uom,
                          vendorID,
                          cartonID,
                          b.upcID,
                          b.recNum
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                LEFT JOIN locations l ON l.id = ca.locID
                LEFT      vendors v ON v.id = co.vendorID
                LEFT      upcs u ON u.id = b.upcID
                WHERE     '.implode(' AND ', $clauses).'
                ORDER BY  b.id ASC,
                          ca.id ASC';

        self::$inventory = $db->queryResults($sql, $params);
    }

    /*
    ****************************************************************************
    */

    static function forContainer($params)
    {
        self::$recNum = getDefault($params['recNum']);
        self::$setByIndex = getDefault($params['byIndex']);
        self::$manualInserts = getDefault($params['manualInserts']);

        $warehouseID = getDefault($params['warehouseID']);

        self::getInventory($params);

        if (! self::$inventory) {
            echo '<br>No Conainer Found For UCC Labels';
            return;
        }

        $batches = self::cartonsToBatches(self::$inventory, [], $warehouseID);

        $params['labels'] = $batches;

        self::commonLabelMaking($params);
    }

    /*
    ****************************************************************************
    */
    static function piecesInBatches($params)
    {
        if (self::$piecesInBatches) {
            return;
        }

        $db = $params['db'];
        $terms = $params['terms'];
        $clause = NULL;

        switch ($params['search']) {
            case 'batches':
                $clause = 'b.id IN ('.$db->getQMarkString($terms).')';
                break;
            case 'recNums':
                $clause = 'b.recNum IN ('.$db->getQMarkString($terms).')';
                break;
            default:
                die('Invalid Type for PiecesInBatch Method');
        }

        $sql = 'SELECT    ca.batchID,
                          SUM(uom) AS totalPiece,
                          COUNT(uom) AS totalCarton
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                WHERE     '.$clause.'
                AND NOT   isSplit
                AND NOT   unSplit
                GROUP BY  ca.batchID';

        self::$piecesInBatches = $db->queryResults($sql, $terms);
    }

    /*
    ****************************************************************************
    */

    static function getNewTallyLabelsCartons($app, $uccs)
    {
        $containers = new \tables\inventory\containers($app);

        // Get inventory of the tally labels
        $paritalLabels = [];

        $masterLabels = [];

        foreach ($uccs as $row) {

            $vendorBatch = substr($row, 0, 13);

            $paritalLabels[$vendorBatch] = TRUE;
        }

        $params = array_keys($paritalLabels);

        $uccContainers = $containers->getUccContainers($params);

        if (! $uccContainers) {
            return [
                'uccs' => $uccs,
                'masterLabels' => $masterLabels,
            ];
        }

        $containerNames = array_keys($uccContainers);
        // Must get all batches from the container so the labels line up
        $results = $containers->getContainerBatches($containerNames);

        if (! $results) {
            return [
                'uccs' => $uccs,
                'masterLabels' => $masterLabels,
            ];
        }

        // Separate batches by container/tally
        $containerBatches = $containerInv = [];

        foreach ($results as $row) {

            $container = $row['container'];
            $batch = $row['batchID'];
            $id = $row['id'];

            $containerBatches[$container][$batch] = TRUE;
            $containerInv[$container][$id] = $row;
        }

        // Get each containers tally
        $tallyBatches = [];

        foreach ($containerBatches as $container => $batches) {

            $tallied = $batchID = 0;
            $cartonPlates = 1;

            $storedTally = getDefault(tally::$getTally[$container]);

            $tally = $storedTally ? $storedTally : tally::get($app, [
                'batches' => array_keys($batches),
            ], true);

            foreach ($tally as $row) {
                for ($plate=1; $plate<=$row['plateCount']; $plate++) {
                    $inventory = array_slice($containerInv[$container], $tallied,
                        $row['cartonCount'], $preserveKeys = TRUE);
                    $firstCarton = reset($inventory);

                    if ($batchID != $firstCarton['batchID']) {
                        // reset a plate counter if a new batch is being processed
                        $cartonPlates = 1;
                    }

                    $batchID = $firstCarton['batchID'];

                    // Use the first carton as the tally label
                    $masterUOM = $row['cartonCount'] * $firstCarton['uom'];
                    $underThousand = $masterUOM > cartons::MAX_CARTON_ID ?
                        cartons::MAX_CARTON_ID : $masterUOM;
                    $tallyLabel = $firstCarton['vendorID'].$firstCarton['batchID']
                        . sprintf('%03d', $underThousand)
                        . sprintf('%04d', $cartonPlates);

                    $tallied += $row['cartonCount'];
                    foreach ($inventory as &$carton) {
                        $carton = $carton['ucc'];
                    }
                    $tallyBatches[$tallyLabel] = $inventory;
                    $cartonPlates++;
                }
            }
        }

        $index = 0;

        foreach ($uccs as $id => $ucc) {

            $currentUCC = getDefault($tallyBatches[$ucc]);

            if ($currentUCC) {

                unset($uccs[$index]);

                array_splice($uccs, $index, 0, $currentUCC);

                $index += count($currentUCC) - 1;

                $masterLabels[$ucc] = $currentUCC;
            }

            $index++;
        }

        foreach ($uccs as &$value) {
            if (isset($results[$value])) {

                $invID = $results[$value]['id'];

                $value = [
                    $invID => $value
                ];
            }
        }

        return [
            'uccs' => $uccs,
            'masterLabels' => $masterLabels,
        ];
    }

    /*
    ****************************************************************************
    */

    static function getBatchCartons()
    {
        $results = [];
        foreach (self::$inventory as $cartonID => $row) {
            if (self::$recNum == $row['recNum']) {
                $results[$cartonID] = $row;
            }
        }
        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getNewTallyInfo($app, $batches)
    {
        // Get inventory of the batches
        $qMarkBatches = $app->getQMarkString($batches);

        $sql = 'SELECT  ca.id,
                        ca.id,
                        cartonID,
                        u.upc AS upc,
                        CONCAT(
                            v.id,
                            b.id,
                            LPAD(uom,3, 0),
                            LPAD(cartonID, 4, 0)
                        ) AS ucc,
                        u.sku,
                        color,
                        prefix,
                        size,
                        vendorID,
                        LPAD(uom, 3, 0) AS uom,
                        cartonID,
                        b.id AS batchID,
                        l.displayName AS location,
                        b.upcID,
                        name AS container
                FROM    inventory_cartons ca
                JOIN    inventory_batches b ON b.id = ca.batchID
                JOIN    inventory_containers co ON co.recNum = b.recNum
                JOIN    statuses s ON s.id = ca.statusID
                JOIN    vendors v ON v.id = co.vendorID
                LEFT JOIN upcs u ON u.id = b.upcID
                LEFT JOIN locations l ON l.id = ca.locID
                WHERE    b.id IN (' . $qMarkBatches . ')
                AND      s.shortName NOT IN ("IN", "SH")
                AND      NOT isSplit
                AND      NOT unSplit
                ORDER BY b.id ASC,
                         locID,
                         ca.id ASC';

        $results = self::$inventory ? self::$inventory :
            $app->queryResults($sql, array_keys($batches));

        if (! $results) {
            return $batches;
        }

        // Separate batches by container/tally
        $containers = [];
        foreach ($results as $id => $row) {
            $container = $row['container'];
            $containers[$container][] = $row['batchID'];
        }

        // Get each containers tally

        $tallyBatches = [];

        foreach ($containers as $container => $batches) {

            $storedTally = getDefault(tally::$getTally[$container]);

            $tally = $storedTally ? $storedTally : tally::get($app, [
                'batches' => $batches,
            ]);

            $tallied = 0;
            $cartonPlates = 1;
            $usedBatches = [];

            foreach ($tally as $row) {

                $tallyUPC = $row['upc'];

                for ($plate=1; $plate<=$row['plateCount']; $plate++) {

                    $cartons = array_slice($results, $tallied,
                        $row['cartonCount'], $preserveKeys = TRUE);

                    $firstCarton = reset($cartons);

                    if ($tallyUPC != $firstCarton['upc']) {
                            continue;
                    }

                    $id = $firstCarton['id'];
                    $batch = $firstCarton['batchID'];
                    // Use the first carton as the tally label

                    $masterUOM = 0;

                    foreach ($cartons as $carton) {
                        // calculate total quantity of pieces per Master Label
                        $masterUOM += $carton['uom'];
                    }

                    $firstCarton['uom'] = $masterUOM > cartons::MAX_CARTON_ID ?
                        cartons::MAX_CARTON_ID : $masterUOM;

                    // Increase tally label ID
                    $tallyID = getDefault($usedBatches[$batch], 0);
                    $tallyID++;
                    $usedBatches[$batch] = $tallyID;

                    self::$piecesInBatches[$batch]['tallyTotals'][] = [
                        'cartonID' => $tallyID,
                        'totalPiece' => $firstCarton['uom'],
                    ];

                    $firstCarton['cartonID'] = $cartonPlates;
                    $firstCarton['labelType'] = 'Master Label';
                    $cartons['tallyLabel'] = $firstCarton;

                    $tallyBatches[$batch][$cartonPlates] = $cartons;
                    $cartonPlates++;
                    $tallied += $row['cartonCount'];
                }
            }
        }

        return $tallyBatches;
    }

    /*
    ****************************************************************************
    */

    static function getNewTallyByInvID($app, $batches)
    {
        $qMarkBatches = $app->getQMarkString($batches);

        $sql = 'SELECT    r.tallyID,
                          vendorID,
                          b.id AS batchID,
                          cartonCount,
                          color,
                          size,
                          u.sku,
                          upc
                FROM      tally_rows r
                LEFT JOIN tally_cartons c ON c.rowID = r.id
                LEFT JOIN inventory_cartons ca ON c.invID = ca.id
                LEFT JOIN inventory_batches b ON b.id = ca.batchID
                LEFT JOIN inventory_containers co ON co.recNum = b.recNum
                LEFT JOIN vendors v ON v.id = co.vendorID
                LEFT JOIN upcs u ON u.id = b.upcID

                WHERE     b.id IN (' . $qMarkBatches . ')
                AND       r.active
                AND       c.active';

        $tallyLabels = $app->queryResults($sql, array_keys($batches));

        $count = 1;
        foreach ($tallyLabels as &$row) {
            $row['cartonID'] = $count++;
        }

        return $tallyLabels;
    }

    /*
    ****************************************************************************
    */

    static function batchLabels($app, $batches)
    {
        // Get batches with tallied inventory

        if (self::$splitCarton || self::$unSplitCarton) {

            $count = 0;
            $parentUCC = NULL;
            $batchTallies = [];

            foreach ($batches as $carton) {
                if ($parentUCC != $carton['parentUCC']) {
                    $count++;
                    $parentUCC = $carton['parentUCC'];
                }

                $batchTallies[$parentUCC][$count][] = $carton;
            }

            $batches = $batchTallies;

            $batchTallies = [];
        } else if (self::$transferCarton
                || self::$processedCarton
                || self::$pickTicketCarton) {
            // no tallies are needed
            $batchTallies = [];
        } else {
            $batchTallies = self::$skipTayllyOutput
                    ? self::skipTayllyOutput($batches)
                    : self::getNewTallyInfo($app, $batches);

            foreach (array_keys($batches) as $batch) {
                if (isset($batchTallies[$batch])) {
                    $batches[$batch] = $batchTallies[$batch];
                }
            }
        }

        $finalBatch = [];

        foreach ($batches as $batch => $rows) {
            foreach ($rows as $parentUCC => $inventoryRows) {

                // If these are labels for a warehouse, all batch indexes will
                // be locations. Need to reference label data to get real batch
                if (! is_int($batch)) {
                    $temp = $parentUCC;
                    $parentUCC = $batch;
                    $batch = $temp;
                }

                // This needs to be changed when I have time
                $isTally = isset($batchTallies[$batch]);
                $tallyLabel = NULL;

                if ($isTally) {

                    if (! isset($inventoryRows['tallyLabel'])) {
                        continue;
                        die ('missing') ;
                    }

                    $tallyLabel = getDefault($inventoryRows['tallyLabel']);
                    unset($inventoryRows['tallyLabel']);
                }

                if (! self::$pickTicketCarton && ! self::$processedCarton) {
                    $inventoryRows = array_reverse($inventoryRows);
                }

                // paper width: 215.9 mm
                // label width: 66.675 mm

                // Get first elemtent
                $firstLabel = reset($inventoryRows);

                if ($isTally) {
                    $parentTitle = 'UPC';
                    $parentValue = $firstLabel['upc'];
                    $batchKey = $parentUCC;
                } else if ($parentUCC == 'all') {
                    $parentTitle = 'UPC';
                    $parentValue = $firstLabel['upc'];
                    $batchKey = $batch;
                } else if (self::$transferCarton) {
                    $parentTitle = 'UPC';
                    $parentValue = $firstLabel['upc'];
                    if (isset($batchKey)) {
                        $batchKey += 1;
                    } else {
                        $batchKey = 0;
                    }
                } elseif (self::$pickTicketCarton) {

                    $orderNumber = $firstLabel['container'];

                    $keyPrefix = self::$pickTicketByOrderCarton ?
                            $orderNumber . '-' : NULL;

                    $upc = $firstLabel['upc'];
                    $location = $firstLabel['sortLocation'];
                    $prefix = $firstLabel['prefix'];
                    $suffix = $firstLabel['suffix'];

                    $parentTitle = 'Pick Ticket';
                    $parentValue = $upc;
                    $batchKey = $keyPrefix . $location . '-' . $upc
                              . '-' . $prefix . '-' . $suffix;
                } else if (self::$processedCarton) {

                    $orderNumber = $firstLabel['container'];
                    $upc = $firstLabel['upc'];
                    $location = $firstLabel['sortLocation'];

                    $parentTitle = 'Processed Carton';
                    $parentValue = $upc;
                    $batchKey = $orderNumber . '-' . $location . '-' . $upc;
                } else {
                    $parentTitle = 'UCC128';
                    $parentValue = $parentUCC;
                    $batchKey = $parentUCC;
                }

                $total = count($inventoryRows);

                $isMasterLabel = ! self::$splitCarton
                              && ! self::$transferCarton
                              && ! self::$unSplitCarton
                              && ! self::$pickTicketCarton
                              && ! self::$processedCarton;

                $labelIncrement = $isMasterLabel ? 4 : 3;

                $actualLabels = $total + $labelIncrement;

                $pagesPerStyle = ceil($actualLabels / 30);
                $labelPerStyle = $pagesPerStyle * 30;
                $blankLabelCount = $labelPerStyle - $actualLabels;

                // split carton labels page does not have master label
                if (! $isMasterLabel) {
                    $blankLabelCount = $total % 3 ? 3 - ($total % 3) : 0;
                }

                array_unshift($inventoryRows, [
                    'labelType' => 'Page Count',
                    'value' => $pagesPerStyle,
                    'cartons' => $total,
                ]);

                array_unshift($inventoryRows, [
                    'labelType' => $parentTitle,
                    'value' => $parentValue,
                ]);

                array_unshift($inventoryRows, [
                    'labelType' => 'Container',
                    'value' => $firstLabel['container'],
                    'location' => $firstLabel['location'],
                    'sku' => $firstLabel['sku'],
                ]);

                for ($i=0; $i<$blankLabelCount; $i++) {
                    array_push($inventoryRows, [
                        'labelType' => 'Blank Label',
                    ]);
                }

                if ($isMasterLabel) {
                    // split carton labels page do not need master label
                    if ($isTally) {
                        // If tally, use tally label instead of master label
                        array_push($inventoryRows, $tallyLabel);
                    } else if($parentTitle == 'UCC128') {
                        array_push($inventoryRows, [
                            'labelType' => 'Parent Label',
                            'value' => $parentValue,
                        ]);
                    } else {
                        array_push($inventoryRows, [
                            'labelType' => 'Master Label',
                        ]);
                    }
                }

                $finalBatch[$batchKey] = $inventoryRows;
            }
        }

        return $finalBatch;
    }

    /*
    ****************************************************************************
    */

    static function insertMasterLabels($app)
    {
        $app->beginTransaction();
        foreach (self::$masterLabelParams as $params) {
            $app->runQuery(self::$masterLabelQuery, $params);
        }
        $app->commit();

        self::$masterLabelQuery = [];
    }

    /*
    ****************************************************************************
    */

    static function commonLabelMaking($params)
    {
        ob_clean();

        $app = isset($params['db']) ? $params['db'] : $params['app'];
        $save = getDefault($params['save']);
        $labels = $params['labels'];
        $concat = getDefault($params['concat']);
        $fileName = getDefault($params['fileName']);
        $warehouses = getDefault($params['warehouses']);
        $warehouseID = getDefault($params['warehouseID']);
        $checkLocation = getDefault($params['checkLocation']);
        $files = getDefault($params['files']);

        self::piecesInBatches([
            'db' => $app,
            'terms' => array_keys($labels),
            'search' => 'batches',
        ]);

        $finalBatch = self::batchLabels($app, $labels);

        $pdf = self::getTCPDF();

        $style = self::getPDF($pdf);

        $barcodesForSql = self::outputLabels([
            'pdf' => $pdf,
            'finalBatch' => $finalBatch,
            'style' => $style,
            'checkLocation' => $checkLocation,
        ]);

        if ($barcodesForSql) {

            $barcodesInDBKeys = self::$masterLabels;

            if (! $barcodesInDBKeys) {

                $sqlBarcodes = array_values($barcodesForSql);
                $qMarkString = $app->getQMarkString($sqlBarcodes);

                $checkDB = 'SELECT  barcode
                            FROM    masterlabel
                            WHERE   barcode IN (' . $qMarkString . ')';

                $barcodesInDBKeys = $app->queryResults($checkDB, $sqlBarcodes);
            }

            $barcodesInDB = array_keys($barcodesInDBKeys);

            self::$masterLabelQuery = $masterLabelSql =
                'INSERT INTO masterlabel (barcode, batchnumber) VALUES (?,?)';

            self::$masterLabels ? NULL : $app->beginTransaction();

            foreach ($barcodesForSql as $batch => $barcode) {
                if (! in_array($barcode, $barcodesInDB)) {
                    self::$masterLabelParams[] = self::$manualInserts ?
                        [$barcode, $batch] :
                        $app->runQuery($masterLabelSql, [$barcode, $batch]);
                }
            }

            self::$masterLabels ? NULL : $app->commit();
        }

        // Dont print or save when save is set but not file name
        if (! $concat) {
            $display = $save ? 'F' : 'I';

            if ($files) {
                $save = $files;
            } else {
                $warehouseState = getDefault($warehouses[$warehouseID]);

                $save = $fileName && $warehouses ?
                    $save.$warehouseState.'/'.$fileName : $save;

                $save = $save ? $save : 'container.pdf';
            }

            $pdf->Output($save, $display);

            return $pdf;
        }
    }

    /*
    ****************************************************************************
    */

    static function getMasterLabels($params)
    {
        $db = $params['db'];
        $recNums = $params['recNums'];
        $qMarkString = $db->getQMarkString($recNums);

        $sql = 'SELECT barcode
                FROM   masterLabel m
                JOIN   inventory_batches b ON m.batchNumber = b.id
                WHERE  b.recNum IN (' . $qMarkString . ')';

        self::$masterLabels = $db->queryResults($sql, $recNums);
    }

    /*
    ********************************************************************************
    */

    static function sortBatchesByLoc(&$labels, $checkLocation)
    {
        $newArray = [];

        foreach ($labels as $tally) {
            $labelInfo = reset($tally);

            $indexLoc = locations::getLocationIndex($labelInfo, $checkLocation);

            $newArray[$indexLoc][] = $tally;
        }

        ksort($newArray);

        $labels = [];

        foreach ($newArray as $array) {
            foreach ($array as $label) {
                $labels[] = $label;
            }
        }
    }

    /*
    ********************************************************************************
    */

    static function outputLabels($params)
    {
        $pdf = $params['pdf'];
        $finalBatch = $params['finalBatch'];
        $style = $params['style'];
        $checkLocation = $params['checkLocation'];

        $barcodesForSql = [];
        $pageAmount = $splitRowCount = $splitRowAmount = 0;
        $pageCount = 1;

        foreach ($finalBatch as $keyBatch => $inventoryRows1) {
            $pageAmount += ceil(count($inventoryRows1) / 30);
            foreach ($inventoryRows1 as $row) {
                $splitRowAmount++;
            }

            if (self::$transferCarton && count($inventoryRows1) % 30 > 0) {
                //Add more Blank Label to Full page
                $numBlankLabel = 30 - count($inventoryRows1) % 30;
                for ($i = 0; $i < $numBlankLabel; $i++) {
                    $finalBatch[$keyBatch][] = ['labelType' => 'Blank Label'];
                }
            }
        }

        // do not need to sort Pick Ticket UCC Labels by location names
        self::$pickTicketCarton || self::$processedCarton ||
            self::sortBatchesByLoc($finalBatch, $checkLocation);

        $previousOrder = $prevUPC = NULL;
        $rowCount = 0;

        foreach ($finalBatch as $key => $inventoryRows1) {

            if (self::$pickTicketCarton || self::$processedCarton) {

                $batchKey = explode('-', $key);

                $anotherOrder = $previousOrder && $previousOrder != $batchKey[0];
                $anotherUPC = $prevUPC && $prevUPC != $batchKey[1];

                if (($anotherOrder && self::$pickTicketByOrderCarton
                  || $anotherUPC && $rowCount > 8) && $rowCount) {
                    // new page on another order if labels are printed by orders
                    // or if there is a new upc and only one row remaining at
                    // the current page
                    $pdf->AddPage();
                    $rowCount = 0;
                }

                $previousOrder = $batchKey[0];
                $prevUPC = $batchKey[1];
            }

            $uccCount = 0;
            $firstLabel = NULL;

            foreach ($inventoryRows1 as $row) {
                $splitRowCount++;

                // Get only regular labels
                $firstLabel = isset($row['labelType']) ? $firstLabel : $row;

                $labelType = getDefault($row['labelType']);
                $txt = NULL;

                switch ($labelType) {
                    case 'Container':
                        if (self::$pickTicketCarton || self::$processedCarton) {

                            $title = self::$pickTicketByOrderCarton
                                  || self::$pickTicketSingleOrder
                                  || self::$processedCarton ?
                                    'Scan Order Number' : 'Location Name';

                            $location = self::$pickTicketByOrderCarton
                                     || self::$pickTicketSingleOrder
                                     || self::$processedCarton ?
                                    $row['value'] . "\n" . $row['location'] :
                                    $row['location'];
                        } else {
                            $location = isset($row['location']) ?
                                    $row['location'] : $row['sku'];

                            $title = self::$transferCarton
                                ? 'Mezzanine Location' : $row['value'];
                        }

                        $pdf->SetFont('helvetica', '', 12);
                        $txt = $title."\n".$location;
                        $pdf->MultiCell(68, 25.5, $txt, 0, 'C', 0, 0, '', '',
                                true, 0, false, true, 0);
                        break;
                    case 'Pick Ticket':

                        $pdf->SetFont('helvetica', '', 12);

                        $txt = 'UPC' . "\n" . $row['value'];

                        $pdf->MultiCell(68, 25.5, $txt, 0, 'C', 0, 0, '', '',
                                true, 0, false, true, 0);
                        break;
                    case 'Processed Carton':
                        $pdf->SetFont('helvetica', '', 12);

                        $txt = 'UPC' . "\n" . $row['value'];

                        $pdf->MultiCell(68, 25.5, $txt, 0, 'C', 0, 0, '', '',
                                true, 0, false, true, 0);
                        break;
                    case 'UPC':
                    case 'UCC128':
                        $pdf->SetFont('helvetica', '', 12);
                        $txt = $labelType."\n".$row['value'];
                        $pdf->MultiCell(68, 25.5, $txt, 0, 'C', 0, 0, '', '',
                                true, 0, false, true, 0);
                        break;
                    case 'Page Count':
                        $pdf->SetFont('helvetica', '', 12);

                        if (self::$splitCarton) {
                            $title = 'Split into # cartons';
                        } elseif (self::$unSplitCarton) {
                            $title = 'UnSplit into # cartons';
                        } elseif (self::$transferCarton) {
                            $title = 'Transfer Cartons';
                        } elseif (self::$pickTicketCarton) {
                            $title = 'Pick Ticket Cartons';
                        } elseif (self::$processedCarton) {
                            $title = 'Processed Cartons';
                        } else {
                            $title = $row['value'];
                        }

                        $prefix = self::$splitCarton || self::$transferCarton
                            || self::$unSplitCarton || self::$pickTicketCarton
                            || self::$processedCarton ? NULL : 'Cartons: ';

                        $txt = $title."\n".$prefix.$row['cartons'];
                        $pdf->MultiCell(68, 25.5, $txt, 0, 'C', 0, 0, '', '',
                                true, 0, false, true, 0);
                        break;
                    case 'Blank Label':
                        $pdf->MultiCell(68, 25.5, '', 0, 'C', 0, 0, '', '',
                                true, 0, false, true, 0);
                        break;
                    case 'Parent Label':
                        $currentBatch = $firstLabel['batchID'];
                        $parentUCC = $row['value'];

                        self::parentLabel($parentUCC, $firstLabel, $pdf, $style);
                        break;
                    case 'Master Label':
                        if (self::$skipTayllyOutput) {
                            $labelInfo = $row;
                            $batchID = $row['batchID'];
                        } else {
                            $labelInfo = $firstLabel;
                            $batchID = $firstLabel['batchID'];
                            $labelInfo['cartonID'] = '0001';

                            $totalPieces = getDefault(self::$piecesInBatches[$batchID]['tallyTotals']);

                            if (is_array($totalPieces)) {
                                $nextInfo = array_shift(self::$piecesInBatches[$batchID]['tallyTotals']);
                                $labelInfo['cartonID']
                                        = str_pad($nextInfo['cartonID'],
                                            4, 0, STR_PAD_LEFT);
                                $labelInfo['uom']
                                        = str_pad($nextInfo['totalPiece'],
                                            3, 0, STR_PAD_LEFT);

                            } else {
                                $labelInfo['uom']
                                    = str_pad($totalPieces, 3, 0, STR_PAD_LEFT);
                            }
                        }

                        $labelInfo['uom'] =
                            $labelInfo['uom'] > cartons::MAX_CARTON_ID ?
                            cartons::MAX_CARTON_ID : $labelInfo['uom'];

                        $barcodesForSql[$batchID]
                            = $labelInfo['vendorID']
                            . $labelInfo['batchID']
                            . $labelInfo['uom']
                            . $labelInfo['cartonID'];

                        self::label($pdf, $labelInfo, $style);
                        break;
                    default:
                        self::label($pdf, $row, $style);
                }

                if ($uccCount++ % 3 == 2) {

                    $pdf->Ln();

                    if (self::$splitCarton || self::$unSplitCarton) {
                        if ($splitRowCount % 30 == 0
                            && $splitRowCount < $splitRowAmount) {

                            $pdf->AddPage();
                        }
                    } elseif (self::$pickTicketCarton || self::$processedCarton) {
                        $rowCount++;
                        if ($rowCount > 9) {
                            $pdf->AddPage();
                            $rowCount = 0;
                        }
                    } else {
                        if ($uccCount % 30 == 0) {
                            if ($pageCount < $pageAmount) {
                                $pdf->AddPage();
                            }
                            $pageCount++;
                        }
                    }
                }
            }
        }

        return $barcodesForSql;
    }

    /*
    ****************************************************************************
    */

    static function forNSIPOs($data, $receiving=FALSE)
    {
        // paper width: 215.9 mm
        // label width: 66.675 mm

        $multiplied = [];

        foreach ($data as $row) {
            $multiplied[] = $row;
            $multiplied[] = $row;
            $multiplied[] = $row;
            if (! $receiving) {
                $multiplied[] = $row;
            }
        }
        $uccCount = 0;

        $pdf = self::getTCPDF();

        $style = self::getPDF($pdf);

        // Make sure extra labels are positioned correctly
        $total = count($multiplied);
        $oneThird = $total / 3;
        $newTotal = ceil($oneThird) * 3;
        for ($i = 0; $i < $newTotal - $total; $i++) {
            $multiplied[] = [];
        }

        $pageAmount = ceil(count($multiplied) / 30);

        $pageCount = 1;

        foreach ($multiplied as $row) {

            if (isset($row[self::BATCH])) {
                $barCode = $row[self::BATCH].$row[self::PO].$row[self::SKU];
                $barCodeDisplay = $row[self::BATCH].'-'.$row[self::PO].'-'.$row[self::SKU];
                $showBorder = $row[self::WAREHOUSE] == 990
                    ? ' style="border-top: 1px solid #000; border-bottom: 1px solid #000;"' : NULL;

                if ($receiving) {
                    $txt = $showBorder."PO: ".$row[self::NSI_REC_PO]."   Pallet: "
                        .$row[self::NSI_REC_PALLET]."\nRA: "
                        ."   DATE: ".$row[self::NSI_REC_DATE].
                        "\n\n".$barCodeDisplay;
                } else {
                    $txt = $showBorder."PO: ".$row[self::PO]."SKU: ".$row[self::SKU].
                        "   DATE:".$row[self::DATE]."\nRA: ".$row[self::RA].
                        "\nCartons: ".$row[self::CARTONS]."Pieces: ".$row[self::PIECES].
                        "\n".$barCodeDisplay;
                }
                $font = 6.5;
                $xPos = 8;
                self::writeBarcodes($pdf, $barCode, $txt, $style, $font, $xPos);
             }

            if ($uccCount++ % 3 == 2) {
                    $pdf->Ln();
                if($uccCount % 30 == 0 && $pageCount < $pageAmount){
                    $pdf->AddPage();
                    $pageCount++;
                }
            }
        }
        $pdf->Output('pdf','I');
        return $pdf;
    }

    /*
    ****************************************************************************
    */

    static function label($pdf, $labelInfo, $style)
    {

        $vednorID = getDefault($labelInfo['vendorID']);
        $batchID = getDefault($labelInfo['batchID']);
        $uom = sprintf('%03d', getDefault($labelInfo['uom']));
        $cartonID = sprintf('%04d', getDefault($labelInfo['cartonID']));
        $barcode1 = $vednorID.$batchID.$uom.$cartonID;
        $clientPO = '';

        $barcodedisplay = $labelInfo['vendorID'].'-'.$labelInfo['batchID']
                       .'-'.$uom.'-'.$cartonID;

        if (isset($labelInfo['prefix'])) {
            $clientPO = "Client PO: " . $labelInfo['prefix'] . "\n";
        }

        $txt = NULL;

        if ($labelInfo) {
            $txt = $clientPO . "SKU: " . $labelInfo['sku']
                . "  upcID: " . $labelInfo['upcID']
                . "\nCOLOR=" . $labelInfo['color']
                . "   QTY. " . $labelInfo['uom']
                . "\nSIZE = " . $labelInfo['size'];

            $txt .= strlen($labelInfo['size']) > 20 ? "\n" : ' ';

            $txt .= "UPC = " . $labelInfo['upc'] . "\n"
                . $barcodedisplay;
        }

        $font = $labelInfo && strlen($labelInfo['size']) > 20 ? 5.5 : 6.5;

        $xPos = 8;

        self::writeBarcodes($pdf, $barcode1, $txt, $style, $font, $xPos);

        return [
            $vednorID,
            $batchID,
            $uom,
            $cartonID
        ];
    }

    /*
    ****************************************************************************
    */

    static function parentLabel($parentUCC, $labelInfo, $pdf, $style)
    {

        $uom = substr($parentUCC, 13, 3);
        $cartonID = substr($parentUCC, 16, 4);
        $barcode1 = $labelInfo['vendorID'].$labelInfo['batchID']
                       .$uom.$cartonID;
        $barcodedisplay = $labelInfo['vendorID'].'-'.$labelInfo['batchID']
               .'-'.$uom.'-'.$cartonID;

        $txt = "SKU: " .$labelInfo['sku']."  upcID: ".$labelInfo['upcID']
                    ."\nCOLOR="
                    .$labelInfo['color']." QTY.".$uom
                    ."\nSIZE = ".$labelInfo['size']
                    ." UPC = ".$labelInfo['upc']
                    ."\n".$barcodedisplay;

        $font = 6.5;
        $xPos = 8;

        self::writeBarcodes($pdf, $barcode1, $txt, $style, $font, $xPos);
    }

    /*
    ****************************************************************************
    */

    static function getPDF ($pdf)
    {
        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);
        $pdf->SetAutoPageBreak(TRUE, 0);
        $pdf->setTopMargin(12);
        $pdf->SetLeftMargin(5);
        $pdf->setCellPaddings(0, 3, 0, 0 );
        $pdf->AddPage();

        $style = [
            'position' => '',
            'align' => '',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => false,
            'hpadding' => 0,
            'vpadding' => 0,
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255),
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 7,
        ];

        $pdf->SetFillColor(255, 255, 255);

        return $style;
    }

    /*
    ****************************************************************************
    */

    static function writeBarcodes($pdf, $barcode, $txt, $style, $font, $xPos)
    {
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->write1DBarcode($barcode, 'C128', $x+$xPos, $y+18, 50, 13, 0.4, $style, 'N', $showCode=FALSE);

        //Reset X,Y so wrapping cell wraps around the barcode's cell.
        $pdf->SetXY($x,$y);
        $pdf->SetFont('helvetica', '', $font);

        // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1,
        // $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
        $pdf->MultiCell(68, 25.5, $txt, 0, 'C', 0, 0, '', '', true, 0, false, true, 0);
    }

    /*
    ****************************************************************************
    */

    static function badBatchesOutput($badBatches)
    {
        $pdf = self::getTCPDF();

        ob_start(); ?>
        <h2>You have requested labels for containers that have over 2000 cartons:</h2>
        <table>
            <thead>
                <tr>
                    <th>Batch #</th>
                    <th>Receiving #</th>
                    <th>Container Name</th>
                    <th>Cartons Amount</th>
                    <th>Client</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5">
                    </td>
                </tr>
                <?php foreach ($badBatches as $batchNumber => $batch) { ?>
                    <tr>
                        <td><?php echo $batchNumber; ?></td>
                        <td><?php echo $batch['recNum']; ?></td>
                        <td><?php echo $batch['name']; ?></td>
                        <td><?php echo $batch['cartonCount']; ?></td>
                        <td><?php echo $batch['vendorName']; ?></td>
                     </tr>
                <?php } ?>
            </tbody>
        </table> <?php

        $html = ob_get_clean();

        $pdf->AddPage();

        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, TRUE, TRUE, TRUE);

        $pdf->Output('pdf', 'I');
    }

    /*
    ****************************************************************************
    */

    static function skipTayllyOutput($batches)
    {
        $output = [];
        $count = 0;

        foreach ($batches as $batch => $values) {
            foreach ($values as $cartons) {

                $plate = $location = $firstCarton = NULL;
                $cartonPlates = $uom = 0;

                foreach ($cartons as $caID => $carton) {
                    if ($carton['plate'] != $plate
                        || $carton['location'] != $location) {

                        if ($cartonPlates > 0) {

                            $output[$batch][$count]['tallyLabel'] = $firstCarton;

                            $output[$batch][$count]['tallyLabel']['uom'] = $uom;
                            $output[$batch][$count]['tallyLabel']['cartonID']
                                    = $cartonPlates;
                            $output[$batch][$count]['tallyLabel']['labelType']
                                    = 'Master Label';
                            $uom = 0;
                        }

                        $plate = $carton['plate'];
                        $location = $carton['location'];
                        $firstCarton = $carton;
                        $count++;
                        $cartonPlates++;
                    }

                    $output[$batch][$count][$caID] = $carton;
                    $uom += $carton['uom'];
                }

                $output[$batch][$count]['tallyLabel'] = $firstCarton;

                $output[$batch][$count]['tallyLabel']['uom'] = $uom;
                $output[$batch][$count]['tallyLabel']['cartonID'] = $cartonPlates;
                $output[$batch][$count]['tallyLabel']['labelType'] = 'Master Label';
            }
        }

        return $output;
    }

    /*
    ****************************************************************************
    */

    static function splitCartonsLabels($app, $uccs)
    {
        self::$splitCarton = TRUE;

        $parentUCC = 'CONCAT(
                    cos.vendorID,
                    bs.id,
                    LPAD(cas.uom, 3, 0),
                    LPAD(cas.cartonID, 4, 0)
                )';

        $ucc = 'CONCAT(
                    co.vendorID,
                    b.id,
                    LPAD(ca.uom,3, 0),
                    LPAD(ca.cartonID, 4, 0)
                )';

        $qMarkString = $app->getQMarkString($uccs);

        $sql = 'SELECT    ca.id,
                          ca.id,
                          cas.cartonID AS parentCartonID,
                          ca.cartonID,
                          u.upc AS upc,
                          ' . $parentUCC . ' AS parentUCC,
                          ' . $ucc . ' AS ucc,
                          u.sku,
                          color,
                          size,
                          co.vendorID,
                          LPAD(ca.uom, 3, 0) AS uom,
                          ca.cartonID,
                          b.id AS batchID,
                          l.displayName AS location,
                          b.upcID,
                          co.name AS container,
                          co.recNum AS containerRecNum
                FROM      inventory_cartons cas
                JOIN      inventory_batches bs ON bs.id = cas.batchID
                JOIN      inventory_containers cos ON cos.recNum = bs.recNum
                JOIN      inventory_splits sp ON sp.parentID = cas.id
                JOIN      inventory_cartons ca ON ca.id = sp.childID
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      statuses s ON s.id = ca.statusID
                JOIN      upcs u ON u.id = b.upcID
                JOIN      locations l ON l.id = ca.locID
                WHERE     ' . $parentUCC . ' IN (' . $qMarkString . ')
                AND       s.shortName != "IN"
                ORDER BY  cos.vendorID ASC,
                          bs.id ASC,
                          cas.uom ASC,
                          cas.cartonID ASC,
                          co.vendorID ASC,
                          b.id ASC,
                          ca.uom ASC,
                          ca.cartonID ASC
                ';

        $results = $app->queryResults($sql, $uccs);

        self::forCartons([
            'db' => $app,
            'labels' => $results,
            'splitCarton' => TRUE,
        ]);
    }

    /*
    ****************************************************************************
    */

    static function transferCartonsLabels($app, $transferID, $files=NULL)
    {
        self::$transferCarton = TRUE;

        $sql = 'SELECT    ca.id,
                          ca.cartonID,
                          u.upc AS upc,
                          CONCAT(
                            co.vendorID,
                            b.id,
                            LPAD(ca.uom,3, 0),
                            LPAD(ca.cartonID, 4, 0)
                          ) AS ucc,
                          u.sku,
                          color,
                          size,
                          co.vendorID,
                          LPAD(ca.uom, 3, 0) AS uom,
                          b.id AS batchID,
                          l.displayName AS location,
                          b.upcID,
                          co.name AS container
                FROM      transfer_cartons t
                JOIN      transfer_items ti ON ti.id = t.transferItemID
                JOIN      inventory_cartons ca ON ca.id = t.cartonID
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      statuses s ON s.id = ca.statusID
                JOIN      upcs u ON u.id = b.upcID
                JOIN      locations l ON l.id = ca.locID
                WHERE     ti.transferID = ?
                ORDER BY  b.id ASC,
                          ca.id ASC
                ';

        $results = $app->queryResults($sql, [$transferID]);

        self::forCartons([
            'db' => $app,
            'save' => $files,
            'files' => $files,
            'labels' => $results,
            'isDownload' => FALSE,
            'splitCarton' => TRUE,
        ]);
    }

    /*
    ****************************************************************************
    */

    static function unsplitCartonsLabels($app, $uccs)
    {
        self::$unSplitCarton = TRUE;

        $ucc = 'CONCAT(
                    cos.vendorID,
                    bs.id,
                    LPAD(cas.uom, 3, 0),
                    LPAD(cas.cartonID, 4, 0)
                )';
        $qMarkString = $app->getQMarkString($uccs);

        $sql = 'SELECT  ca.id,
                        ca.id,
                        cas.cartonID AS parentCartonID,
                        ca.cartonID,
                        u.upc AS upc,
                        ' . $ucc . ' AS parentUCC,
                        CONCAT(
                            co.vendorID,
                            b.id,
                            LPAD(ca.uom,3, 0),
                            LPAD(ca.cartonID, 4, 0)
                        ) AS ucc,
                        u.sku,
                        color,
                        size,
                        cos.vendorID,
                        LPAD(ca.uom, 3, 0) AS uom,
                        ca.cartonID,
                        bs.id AS batchID,
                        l.displayName AS location,
                        bs.upcID,
                        cos.name AS container,
                        cos.recNum AS containerRecNum
                        FROM      inventory_cartons cas
                        JOIN      inventory_batches bs ON bs.id = cas.batchID
                        JOIN      inventory_containers cos ON cos.recNum = bs.recNum
                        JOIN      inventory_unsplits sp ON sp.childID = cas.id
                        JOIN      inventory_cartons ca ON ca.id = sp.parentID
                        JOIN      inventory_batches b ON b.id = ca.batchID
                        JOIN      inventory_containers co ON co.recNum = b.recNum
                        JOIN      statuses s ON s.id = cas.statusID
                        JOIN      upcs u ON u.id = b.upcID
                        JOIN      locations l ON l.id = ca.locID
                        WHERE     ' . $ucc . ' IN (' . $qMarkString . ')
                        AND       s.shortName != "IN"
                        ORDER BY  cos.vendorID ASC,
                        bs.id ASC,
                        cas.uom ASC,
                        cas.cartonID ASC,
                        cos.vendorID ASC,
                        bs.id ASC,
                        ca.uom ASC,
                        ca.cartonID ASC';

        $results = $app->queryResults($sql, $uccs);

        self::forCartons([
            'db' => $app,
            'labels' => $results,
            'splitCarton' => TRUE,
        ]);
    }

    /*
    ****************************************************************************
    */

    static function pickTicketCartonsLabels($app, $targetValues, $printByOrder)
    {
        self::$pickTicketCarton = TRUE;
        self::$pickTicketByOrderCarton = $printByOrder;
        self::$pickTicketSingleOrder = count($targetValues) == 1;

        $orderClause = self::$pickTicketByOrderCarton || self::$pickTicketSingleOrder ?
                'scanOrderNumber ASC,' : NULL;

        $qMarkString = $app->getQMarkString($targetValues);

        $queryClauses = self::getCartonsLabelClauses($app);

        $sql = 'SELECT
                ' . $queryClauses['fields'] . ',
                          prefix,
                          suffix
                ' . $queryClauses['from'] . '
                JOIN      pick_cartons pc ON pc.cartonID = ca.id
                JOIN      neworder n ON n.id = pc.orderID
                WHERE     scanOrderNumber IN (' . $qMarkString . ')
                AND       pc.active
                ' . $queryClauses['where'] . '
                ORDER BY  ' . $orderClause . '
                          sortLocation ASC,
                          upc ASC,
                          prefix ASC,
                          suffix ASC,
                          vendorID DESC,
                          batchID DESC,
                          uom DESC,
                          cartonID DESC
                ';

        $results = $app->queryResults($sql, $targetValues);

        $isPickTicket = TRUE;

        self::forCartons([
            'db' => $app,
            'labels' => self::getLabelLocations($results, $isPickTicket),
            'pickTicketCarton' => TRUE,
        ]);
    }

    /*
    ****************************************************************************
    */

    static function pickedCartonsLabels($app, $uccs)
    {
        self::$processedCarton = TRUE;

        $cartons = new \tables\inventory\cartons($app);

        $clauses = $cartons->getByUCCSelectClauses($uccs);

        $queryClauses = self::getCartonsLabelClauses($app);

        $sql = 'SELECT
                ' . $queryClauses['fields'] . '
                ' . $queryClauses['from'] . '
                JOIN      pick_cartons pc ON pc.cartonID = ca.id
                JOIN      neworder n ON n.id = pc.orderID
                WHERE     ' . $clauses['where'] . '
                AND       pc.active
                ' . $queryClauses['where'] . '
                ' . $queryClauses['orderBy'];

        $results = $app->queryResults($sql, $clauses['params']);

        self::forCartons([
            'db' => $app,
            'labels' => self::getLabelLocations($results),
            'processedCarton' => TRUE,
        ]);
    }

    /*
    ****************************************************************************
    */

    static function processedCartonsLabels($app, $orderNumber)
    {
        self::$processedCarton = TRUE;

        $queryClauses = self::getCartonsLabelClauses($app);

        $sql = 'SELECT
                ' . $queryClauses['fields'] . '
                ' . $queryClauses['from'] . '
                JOIN      neworder n ON n.id = ca.orderID
                WHERE     scanOrderNumber = ?
                ' . $queryClauses['where'] . '
                ' . $queryClauses['orderBy'];

        $results = $app->queryResults($sql, [$orderNumber]);

        self::forCartons([
            'db' => $app,
            'labels' => self::getLabelLocations($results),
            'processedCarton' => TRUE,
        ]);
    }

    /*
    ****************************************************************************
    */

    static function getCartonsLabelClauses($app)
    {
        $cartons = new \tables\inventory\cartons($app);

        $ucc128 = $cartons->fields['ucc128']['select'];

        return [
            'fields' => '
                ca.id,
                ca.id,
                ca.cartonID,
                upc,
                ' . $ucc128 . ' AS ucc,
                sku,
                color,
                size,
                vendorID,
                LPAD(ca.uom, 3, 0) AS uom,
                batchID,
                displayName AS location,
                upcID,
                scanOrderNumber AS container,
                CONCAT(
                    IF(isMezzanine, "2",
                        IF(
                            LEFT(l.displayName, 1) REGEXP "^[0-9]+$", "1", "0"
                        )
                    ), l.displayName
                ) AS sortLocation',
            'from' => '
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      upcs u ON u.id = b.upcID
                JOIN      locations l ON l.id = ca.locID',
            'where' => '
                AND       NOT isSplit
                AND       NOT unSplit
                ',
            'orderBy' => '
                ORDER BY  scanOrderNumber ASC,
                          isMezzanine ASC,
                          upc ASC,
                          displayName ASC,
                          ca.id ASC
                ',
        ];
    }

    /*
    ****************************************************************************
    */

    static function getLabelLocations($results, $isPickTicket=FALSE)
    {
        $return = [];

        foreach ($results as $values) {

            $location = $values['sortLocation'];

            $sortField = ! $isPickTicket
                      || self::$pickTicketByOrderCarton
                      || self::$pickTicketSingleOrder ?
                    $values['container'] . '-' . $location : $location;

            $upc = $values['upc'];

            $return[$sortField][$upc][] = $values;
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    static function printUCCLabelEDIFormat($app, $orderNumber)
    {
        $data = self::getUCCLabelEDIData($app, $orderNumber);

        // Process print ucc label
        self::processPrintLabel( $data);

    }

    /*
    ****************************************************************************
    */

    static function getUCCLabelEDIData ($app, $orderNumber) {
        $sql = 'SELECT  ca.id,
                        n.shipto,
                        n.customerordernumber AS PO,
                        n.clientPickTicket AS storeNumber,
                        n.deptID AS dept,
                        CONCAT(
                            oo.shipping_address_street,
                            ", ", oo.shipping_city,
                            ", ", oo.shipping_state,
                            " ", oo.shipping_postal_code
                        ) AS shippingTo,
                        oo.shipping_postal_code,
                        oo.product_description AS custItem,
                        si.carrierName AS carrierName,
                        si.proNumber,
                        si.bolID AS bolNumber,
                        u.sku,
                        ca.uom,
                        its.des,
                        1 AS sumCarton,
                        1 AS innerPack
                FROM    neworder n
                LEFT JOIN    online_orders oo
                    ON oo.SCAN_SELDAT_ORDER_NUMBER = n.scanordernumber
                JOIN    order_batches ob ON ob.id = n.order_batch
                JOIN    pick_cartons pc ON pc.orderID = n.id
                JOIN    inventory_cartons ca ON ca.id = pc.cartonID
                JOIN    inventory_batches b ON b.id = ca.batchID
                JOIN    upcs u ON u.id = b.upcID
                LEFT JOIN (
                    SELECT  it.item_id,
                            it.`value` AS des
                    FROM    item_meta it
                    JOIN    attribute_group ag ON ag.id = it.attribute_group_id
                ) AS its ON its.item_id = u.id
                LEFT JOIN shipping_orders so ON so.orderID = n.id
                LEFT JOIN shipping_info si ON si.bolLabel = so.bolID
                WHERE     n.scanordernumber = ?
                GROUP BY  ca.id';

        $results = $app->queryResults($sql, [$orderNumber]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function generateUCC128($companyPrefix, $cartonID)
    {
        $cartonID = str_pad($cartonID, 9, 0, STR_PAD_LEFT);
        $txEan20 = $companyPrefix . $cartonID;

        $sum = ($txEan20[0] + $txEan20[2] + $txEan20[4] + $txEan20[6]
                + $txEan20[8] + $txEan20[10] + $txEan20[12] + $txEan20[14]
                + $txEan20[16]) * 3 + ($txEan20[1] + $txEan20[3] + $txEan20[5]
                + $txEan20[7] + $txEan20[9] + $txEan20[11] + $txEan20[13]
                + $txEan20[15]) * 1;

        $digit = $sum % 10 != 0 ? 10 - $sum % 10 : 0;

        return $txEan20 . $digit;
    }

    /*
    ****************************************************************************
    */

    static function processPrintLabel($data)
    {
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT,
            true, 'UTF-8', false);
        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);
        $pdf->SetAutoPageBreak(TRUE, 0);
        $pdf->SetLeftMargin(15);
        $pdf->setTopMargin(15);

        $style = [
            'position' => 'L',
            'align' => 'L',
            'stretch' => FALSE,
            'fitwidth' => FALSE,
            'cellfitalign' => '',
            'border' => false,
            'hpadding' => 0,
            'vpadding' => 0,
            'fgcolor' => array(0,0,0),
            'bgcolor' => false,
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 11,
        ];

        $pdf->SetFont('helvetica', 'B', 13);

        if (! $data) {
            $pdf->AddPage();
            pdf::myMultiCell($pdf, 100, 6, 'No have data.', 0, 'L');
        } else {
            foreach ($data as $cartonID => $row) {
                $pdf->AddPage();
                $pdf->Line($pdf->w / 2 + 5, $pdf->y, $pdf->w / 2 + 5, $pdf->y + 54);
                pdf::myMultiCell($pdf, 100, 6, 'FROM: ', 0, 'L');
                pdf::myMultiCell($pdf, 100, 6, 'CUSTOMER: ', 0, 'L');

                $pdf->Ln(7);
                $pdf->SetFont('helvetica', 'B', 11);
                pdf::myMultiCell($pdf, 100, 6, self::GL_ADDRESS_LINE_1, 0, 'L',
                    1, 0, '', '', true, 0, true, true, 5, 'M');
                pdf::myMultiCell($pdf, 100, 6, $row['shipto'], 0, 'L', 1, 0, '',
                    '', true, 0, true, true, 5, 'M');

                $pdf->Ln(5);
                pdf::myMultiCell($pdf, 100, 6, self::GL_ADDRESS_LINE_2, 0, 'L',
                    1, 0, '', '', true, 0, true, true, 5, 'M');
                $pdf->MultiCell(87, 6, $row['shippingTo'], 0, 'L', 0, 0, '',
                    '', true, 0, true, true, 5, 'M');

                $pdf->Ln(10);
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Line(15, $pdf->y, $pdf->w - 15, $pdf->y);
                $pdf->Ln(3);
                pdf::myMultiCell($pdf, 100, 6, 'SHIPPING TO POSTAL CODE: '
                    . $row['shipping_postal_code'], 0, 'L');
                pdf::myMultiCell($pdf, 100, 6, 'CARRIER: ' . $row['carrierName'],
                    0, 'L');

                $pdf->Ln(7);
                $style['position'] = 'L';
                $style['hpadding'] = 5;
                $style['xpadding'] = 5;
                $pdf->write1DBarcode($row['shipping_postal_code'], 'C128', '',
                    '', 100, 20, 0.4, $style, 'T');
                pdf::myMultiCell($pdf, 210, 6, 'PRO: ' . $row['proNumber'], 0, 'L');

                $pdf->Ln(7);
                pdf::myMultiCell($pdf, 100, 6, '', 0, 'L');
                pdf::myMultiCell($pdf, 100, 6, 'B/L: ' . $row['bolNumber'], 0, 'L');

                $pdf->Ln(15);
                $pdf->SetFont('helvetica', 'B', 13);
                $pdf->Line(15, $pdf->y, $pdf->w - 15, $pdf->y);
                $pdf->Ln(5);
                pdf::myMultiCell($pdf, 100, 6, 'ITEM INFORMATION: ', 0, 'L');
                $pdf->Ln(7);
                $pdf->SetFont('helvetica', 'B', 11);
                pdf::myMultiCell($pdf, 100, 6, 'PO: ' . $row['PO'], 0, 'L');

                $pdf->Ln(5);
                pdf::myMultiCell($pdf, 100, 6, 'DEPT: ' . $row['dept'], 0, 'L');

                $pdf->Ln(5);
                pdf::myMultiCell($pdf, 100, 6, 'Cust Item: ' . $row['custItem'],
                    0, 'L');

                $pdf->Ln(5);
                pdf::myMultiCell($pdf, 100, 6, 'Item: ' . $row['sku'], 0, 'L');

                $pdf->Ln(5);
                pdf::myMultiCell($pdf, 100, 6, 'Desc: ' . $row['des'], 0, 'L');

                $pdf->Ln(5);
                pdf::myMultiCell($pdf, 100, 6, 'Master Pack Qty: ' . $row['uom'],
                    0, 'L');

                $pdf->Ln(5);
                pdf::myMultiCell($pdf, 100, 6, 'Inner Pack Qty: '
                    . $row['innerPack'], 0, 'L');

                $pdf->Ln(5);
                pdf::myMultiCell($pdf, 100, 6, 'Qty: ' . $row['uom'], 0, 'L');

                $pdf->Ln(5);
                pdf::myMultiCell($pdf, 100, 6, 'Carton #: ' . $row['sumCarton']
                    . ' of ' . $row['sumCarton'], 0, 'L');

                $pdf->Ln(10);

                $pdf->Line($pdf->w / 2 + 5, $pdf->y, $pdf->w / 2 + 5, $pdf->y + 35);
                $pdf->Line(15, $pdf->y, $pdf->w - 15, $pdf->y);
                $pdf->SetFont('helvetica', 'B', 13);
                $pdf->Ln(3);
                pdf::myMultiCell($pdf, 100, 6, 'FOR: ' . $row['storeNumber'],
                    0, 'L');
                pdf::myMultiCell($pdf, 100, 6, 'CUSTOMER', 0, 'l');

                $pdf->Ln(7);
                $pdf->write1DBarcode($row['storeNumber'], 'C128', '', '', 100,
                    20, 0.4, $style, 'T');
                $pdf->SetFont('helvetica', 'B', 11);
                pdf::myMultiCell($pdf, 165, 6, 'STORE: ' . $row['storeNumber'],
                    0, 'L');

                $pdf->Ln(25);
                $pdf->Line(15, $pdf->y, $pdf->w - 15, $pdf->y);
                $pdf->Ln(5);
                $pdf->SetFont('helvetica', 'B', 13);
                pdf::myMultiCell($pdf, 100, 6, 'SSCC-18', 0, 'L');

                $pdf->Ln(7);
                $style['align'] = 'C';
                $barCode = self::AI_CODE
                    . self::generateUCC128(self::CUSTOMER_CODE, $cartonID);
                $pdf->write1DBarcode($barCode, 'C128', '', '', 180, 30, 1,
                    $style, 'N');
            }
        }

        $pdf->Output('UCCs_Label.pdf', 'I');
    }

    /*
    ****************************************************************************
    */


}
