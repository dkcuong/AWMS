<?php

namespace common;

use tables\invoices\storage;

class invoicing
{
    public $db = NULL;

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

    public $vendorList = [];

    /*
    ****************************************************************************
    */

    static function init($db)
    {
        $self = new static();
        $self->db = $db;
        return $self;
    }

    /*
    ****************************************************************************
    */

    function getDB()
    {
        return $this->db;
    }

    /*
    ****************************************************************************
    */

    function get($model, $data=[], $receiving=NULL, $processing=NULL)
    {
        // EXAMPLE
        //$data = [
        //    'categories' => [
        //        'receiving',
        //        'storage',
        //        'workOrders',
        //        'processing',
        //    ],
        //    'vednorID' => 11195,
        //    'fromDate' => '2016-03-01',
        //    'toDate' => '2016-03-30',
        //];
        // none of these array elements are mandatory

        $categories = getDefault($data['categories'], NULL);

        $results = [];

        $rcvUOMs = $model->customerUOMs('RECEIVING');
        if ($rcvUOMs && (! $categories || in_array('receiving', $categories))) {
            $catResults = $this->getReceivingInvoices($data, $receiving);
            $results = array_merge($results, $catResults);
        }

        $storUOMs = $model->customerUOMs('STORAGE');
        if ($storUOMs && (! $categories || in_array('storage', $categories))) {
            $catResults = $this->getStorageInvoices($data);
            $results = array_merge($results, $catResults);
        }

        $opUOMs = $model->customerUOMs('ORD_PROC');
        if ($opUOMs && (! $categories || in_array('processing', $categories))) {
            $catResults = $this->getProcessingInvoices($data, $processing);
            $results = array_merge($results, $catResults);
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getReceivingInvoices($data, $receiving)
    {
        $hisClause = $receiving->getReceivingHisCarton($data);

        $custClause = $data['custID'] ?
            'AND cust_id = '.intVal($data['custID']) : NULL;

        $sql = 'SELECT  recNum,
                        CONCAT(w.shortName, "_", vendorName) AS clientName,
                        name AS container,
                        ms.displayName AS measure,
                        DATE(setDate) AS setDate
                FROM    rcv_sum_cntr s
                JOIN    inventory_containers co ON rcv_nbr = co.recNum
                JOIN    measurement_systems ms ON ms.id = co.measureID
                JOIN    vendors v ON v.id = co.vendorID
                JOIN    warehouses w ON v.warehouseID = w.id
                WHERE   dt >= ?
                AND     dt <= ?
                '.$hisClause.'
                '.$custClause.'
                ORDER BY recNum';

        $params = [
            $data['startDate'],
            $data['endDate'],
        ];

        $results = $this->db->queryResults($sql, $params);

        if ($results) {
            $recNums = array_keys($results);

            $qMarks = $this->db->getQMarkString($recNums);

            //get the recNums from inv_his_rcv table
            $hisSql = '
                SELECT    rcv_nbr AS recNum
                FROM      inv_his_rcv
                WHERE     rcv_nbr IN (' . $qMarks . ')
                AND       inv_sts';

            $hisRecResults = $this->db->queryResults($hisSql, $recNums);

            $hisRecNums = array_keys($hisRecResults);
        }

        $return = [];
        foreach ($results as $recNum => $values) {
           if (! in_array($recNum, $hisRecNums) ) {
            $return[] = [
                'id' => $recNum,
                'cat' => 'Receiving',
                'cust' => $values['clientName'],
                'ext' => [
                    'name' => $values['container'],
                    'meas' => $values['measure'],
                ],
                'dt' => $values['setDate'],
            ];
           }
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getStorageInvoices($data)
    {
        $vendorID = getDefault($data['vendorID']);
        $fromDate = getDefault($data['fromDate'], '0000-00-00 00:00:00');
        $toDate = getDefault($data['toDate'], '9999-12-31 23:59:59');

        $storage = new storage($this);

        $sth = $storage->getInventory([
            'vendorID' => $vendorID,
            'toDate' => $toDate,
            'fieldList' => 'vendorID,
                            ca.batchID,
                            ca.uom,
                            ca.cartonID,
                            ca.plate,
                            ca.locID,',
        ]);

        $processed = $current = [];

        $rackedStatuses = storage::$rackedStatuses;

        while ($values = $sth->fetch(\database\myPDO::FETCH_ASSOC)) {

            $invID = $values['invID'];
            $logData = $values['logDate'];
            $racked = in_array($values['status'], $rackedStatuses);

            if (isset($processed[$invID]) || $current
             && $current['invID'] == $invID && $current['logDate'] == $logData) {
                // current InvID was processed or duplicate dates (only the lates date matters)
                continue;
            }

            if ($values['invID'] != getDefault($current['invID'])) {
                // starting process a new InvID
                $current = $values;

                if ($racked) {
                    $return[] = $this->getStorageCarton($values);
                }

                if ($fromDate && $fromDate > $values['logDate']) {
                    $processed[$invID] = TRUE;
                }

                continue;
            }

            $currentDate = $current['logDate'];

            $differentPlate = $current['plate'] != $values['plate'];
            $differentLocation = $current['locID'] != $values['locID'];
            $differentDate = $current['logDate'] != $values['logDate'];

            if ($values['logDate'] < $fromDate) {
                // end of processing of the current InvID
                if ($racked && $fromDate && $currentDate != $fromDate
                 && ($differentPlate || $differentLocation)) {

                    $return[] = $this->getStorageCarton($values);
                }

                $processed[$invID] = TRUE;

                continue;
            }

            if ($racked && $currentDate != $values['logDate']
             && ($differentPlate || $differentLocation || $differentDate)) {

                $return[] = $this->getStorageCarton($values);
            }

            $current = $values;
        }

        $locIDs = $vendorIDs = [];

        foreach ($return as $value) {

            $vendorID = $value['cust'];
            $locID = $value['ext']['loc'];

            $vendorIDs[$vendorID] = TRUE;
            $locIDs[$locID] = TRUE;
        }

        $vendors = new tables\vendors($this);
        $locations = new tables\locations($this);

        $vendorKeys = array_keys($vendorIDs);
        $locationKeys = array_keys($locIDs);

        $vendorNames = $vendors->getVendorName($vendorKeys);
        $locationNames = $locations->getLocationsDisplayNameByClause(
                $locationKeys, array_fill(0, count($locationKeys), 'id = ?')
        );

        $locationValues = [];

        foreach ($locationNames as $locationName => $values) {

            $locID = $values['id'];

            $locationValues[$locID] = $locationName;
        }

        foreach ($return as &$value) {

            $vendorID = $value['cust'];
            $locID = $value['ext']['loc'];

            $value['cust'] = getDefault($vendorNames[$vendorID]['fullVendorName'], NULL);
            $value['ext']['loc'] = getDefault($locationValues[$locID], NULL);
        }

        return [];
    }

    /*
    ****************************************************************************
    */

    function getProcessingInvoices($data, $processing)
    {
        $return = [];

        $processingHistory = new \invoices\history\orderProcessing($this->db);

        $processedOrderIDs = $processingHistory->getBilledOrders($data['custID']);

        $clauses = $processing->getClauses([
            'endDate' => $data['endDate'],
            'startDate' => $data['startDate'],
            'orderNumbers' => [],
            'processedOrderIDs' => $processedOrderIDs,
        ]);

        $custClause = $data['custID'] ?
            'AND cust_id = '.intVal($data['custID']) : NULL;

        $params = [
            'clauses' => $clauses,
            'custClauses' => $custClause
        ];

        $sql = '
                ' .  $this->processingQuery($params, 'processed') . '
                UNION
                ' .  $this->processingQuery($params, 'notProcessed') . '
                UNION
                ' .  $this->processingQuery($params, 'cancelled') . '
               ';

        $selectParams =  array_merge($clauses['params'], $clauses['params'],
                                $clauses['params']);

        $results = $this->db->queryResults($sql, $selectParams);

        if ($results) {

            $orderNums = array_keys($results);

            $qMarks = $this->db->getQMarkString($orderNums);

            //get the recNums from inv_his_ord_prc table
            $hisSql = 'SELECT    scanOrderNumber
                       FROM      inv_his_ord_prc prc
                       JOIN      neworder n ON n.id = prc.ord_id
                       WHERE     scanOrderNumber IN (' . $qMarks . ')
                       AND       inv_sts';

            $hisOrderResults = $this->db->queryResults($hisSql, $orderNums);

            $hisOrderNums = array_keys($hisOrderResults);
        }

        foreach ($results as $scanOrderNumber => $values) {
            if (! in_array($scanOrderNumber, $hisOrderNums) ) {
                $return[] = [
                    'id' => $scanOrderNumber,
                    'cat' => 'Order Processing',
                    'cust' => $values['clientName'],
                    'ext' => [
                        'name' => $values['name'],
                        'custNum' => $values['customerOrderNumber'],
                        'clientOrdNum' => $values['clientordernumber'],
                        'cq' => $values['cartonQuantity'],
                        'pq' => $values['pieceQuantity'],
                    ],
                    'dt' => $values['dt'],
                    'order' => $values['type'],
                ];
           }
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function processingQuery($params, $processed)
    {
        $table = 'ord_sum_ord o';

        $select = 'COUNT(ca.id) AS cartonQuantity, SUM(uom) AS pieceQuantity';

        $type = 'CONCAT("", "Open", "") AS type';

        $join = NULL;
        $active = 1;
        $status = [];

        $statuses = new \tables\statuses\orders($this->db);

        switch ($processed) {
            case 'processed':
                $join = 'JOIN  inventory_cartons ca ON ca.orderID = n.id';
                $status = \summary\createOrd::init($this->db)->invoiceOrderProcessedStatus();
                break;
            case 'notProcessed':
                $join = 'JOIN  pick_cartons pc ON pc.orderID = n.id
                         JOIN  inventory_cartons ca ON ca.id = pc.cartonID';
                $active = 'pc.active';
                $status = \summary\createOrd::init($this->db)->invoiceOrderNotProcessedStatus();
                break;
            case 'cancelled':
                $table = 'ord_sum_cncl o';
                $select = 'CONCAT("", "NA", "") AS cartonQuantity,
                           CONCAT("", "NA", "") AS pieceQuantity';
                $type = 'CONCAT("", "Cancel", "") AS type';

                $status = \tables\orders::STATUS_CANCELED;
        }

        $statusIds = $statuses->getOrderStatusID($status);

        $sql = 'SELECT    scanOrderNumber,
                          CONCAT(w.shortName, "_", vendorName) AS clientName,
                          '  . $type . ',
                          TRIM(
                            CONCAT_WS(" ", first_name, last_name)
                          ) AS name,
                          customerOrderNumber,
                          clientordernumber,
                          dt,
                          ' . $select . '
                FROM      ' . $table . '
                JOIN      neworder n ON n.scanOrderNumber = o.order_nbr
                JOIN      order_batches b ON b.id = n.order_batch
                JOIN      vendors v ON v.id = b.vendorID
                JOIN      warehouses w ON w.id = v.warehouseID
                JOIN      statuses s ON s.id = n.statusID
                ' . $join . '
                WHERE     dt >= ?
                AND       dt <= ?
                AND       ' . $active . '
                AND       s.id IN (' . implode(",", $statusIds) . ')
                '.$params['clauses']['where'].'
                '.$params['custClauses'].'
                GROUP BY  n.id';

        return $sql;
    }

    /*
    ****************************************************************************
    */


    function getClauses($data, $dateField, $vendorField='vendorID')
    {
        $fromDate = getDefault($data['startDate']);
        $toDate = getDefault($data['endDate']);
        $vendorID = $vendorIDs = getDefault($data['custID']);

        if ($vendorID) {
            $vendorIDs = is_array($vendorID) ? $vendorID : [$vendorID];
        }

        $qMarks = $this->db->getQMarkString($vendorIDs);

        $fromClause = $fromDate ? 'AND ' . $dateField . ' >= ?' : NULL;
        $toClause = $toDate ? 'AND ' . $dateField . ' <= ?' : NULL;
        $vendorClause = ! $vendorID ? NULL :
                'AND ' . $vendorField . ' IN (' . $qMarks . ')';

        $params = $vendorIDs ? $vendorIDs : [];

        $fromDate && $params[] = $fromDate;
        $toDate && $params[] = $toDate;

        return [
            'whereClause' => $vendorClause . ' ' . $fromClause . ' ' . $toClause,
            'params' => $params,
        ];
    }

    /*
    ****************************************************************************
    */

    function getStorageCarton($values)
    {
        $ucc = $values['vendorID']
             . $values['batchID']
             . str_pad($values['uom'], 3, '0', STR_PAD_LEFT)
             . str_pad($values['cartonID'], 4, '0', STR_PAD_LEFT);

        return [
            'id' => $ucc,
            'cat' => 'Storage',
            'cust' => $values['vendorID'],
            'ext' => [
                'uom' => $values['uom'],
                'plate' => $values['plate'],
                'loc' => $values['locID'],
            ],
            'dt' => $values['logDate'],
        ];
    }

    /*
    ****************************************************************************
    */

    static function getInvoiceProcessingCosts(&$results, $data)
    {
        if (! getDefault($data['costRes'])) {
            return [];
        }

        foreach ($data['array'] as $chargeCode => $categoryValues) {

            if (! $categoryValues) {
                continue;
            }

            foreach ($categoryValues as $vendorID => $value) {

                $data['chargeCode'] = $chargeCode;
                $data['vendorID'] = $vendorID;
                $data['value'] = $value;

                // updating $results inside getProcessingCosts() function
                $continue = self::getProcessingCosts($results, $data);

                if ($continue) {
                    continue;
                }
            }
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getProcessingCosts(&$results, $data)
    {
        $rates = $data['costRes']['rates'];
        $details = $data['details'];
        $category = $data['category'];
        $costInfo = $data['costRes']['info'];

        $vendorID = $data['vendorID'];
        $cc = $data['chargeCode'];
        $value = $data['value'];

        if (! isset($rates[$category][$vendorID][$cc]) || ! $value) {
            return TRUE;
        }

        $cost = $rates[$category][$vendorID][$cc];

        $results['sums'][$vendorID] = getDefault($results['sums'][$vendorID], 0);
        $results['sums'][$vendorID] += $value * $cost;

        if (! $details) {
            return TRUE;
        }

        $quantity = getDefault($results['details'][$vendorID][$cc]['quantity'], 0);
        $ccTotal = getDefault($results['details'][$vendorID][$cc]['ccTotal'], 0);

        $results['details'][$vendorID][$cc] = $costInfo[$category][$cc];
        $results['details'][$vendorID][$cc]['rate'] = $cost;
        $results['details'][$vendorID][$cc]['quantity'] = $quantity + $value;
        $results['details'][$vendorID][$cc]['ccTotal'] = $ccTotal + $cost * $value;

        return FALSE;
    }

    /*
    ****************************************************************************
    */

    function getReceivingInvoiceCosts(&$results, $data)
    {
        if (!getDefault($data['costRes'])) {
            return [];
        }

        $array = $data['array'];
        $rates = $data['costRes']['rates'];
        $details = $data['details'];
        $category = $data['category'];
        $costInfo = $data['costRes']['info'];

        foreach ($array as $chargeCode => $chgCodeQtys) {
            if (! $chgCodeQtys) {
                continue;
            }

            foreach ($chgCodeQtys as $vendorID => $value) {
                //sums
                $results['sums'][$vendorID] =
                    getDefault($results['sums'][$vendorID], 0);

                $cost = isset($rates[$category][$vendorID][$chargeCode]) ?
                        $rates[$category][$vendorID][$chargeCode] : 0;

                $results['sums'][$vendorID] +=  $value * $cost;

                 if (! $details) {
                    continue;
                }

                //details
                $results['details'][$vendorID][$chargeCode] = $costInfo[$category][$chargeCode];
                $results['details'][$vendorID][$chargeCode]['rate'] = $cost;
                $results['details'][$vendorID][$chargeCode]['quantity'] = $value;

                $results['details'][$vendorID][$chargeCode]['ccTotal'] =
                    $cost * $value;
            }
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getLaborCharges($data)
    {
        $invoiceCriteria = $data['invoiceCriteria'];

        $recResult = $rushAmt = $ordResult = $woResult = $otAmt = $laborAmt = [];

        $vendorIDs = $data['custIDs'] ? $data['custIDs'] : [];

        $clause = $vendorIDs ?
                    'AND vendorID IN ('.implode(',', $vendorIDs).')' : NULL;

        $recNums = $data['getVars']['details'] ?
            getDefault($data['getVars']['items']['Receiving']) : [];

        $containerClause = $recNums ?
            'AND rcv_num IN ('.implode(',', $recNums).')' : NULL;


        $orderNums = $data['getVars']['details'] ?
            getDefault($data['getVars']['items']['orderPrc']) : [];

        $orderClause = $orderNums ?
            'AND scanordernumber IN ('.implode(',', $orderNums).')' : NULL;

        $rushClause = 'AND w.type = "r"';
        $otClause = 'AND w.type = "o"';

        if ($invoiceCriteria['calculateAll'] || $invoiceCriteria['receiving']) {
            //get receiving labor history from wrk_hrs_rcv table
            $hisRcvClause = $this->getReceivingHisLabor($data, $recNums);

            //get Receiving labor rush cost and overtime cost
            $recResult['RUSH-LABOR'] =  $this->getRecLaborQuery($data, [
                    'clause' => $clause,
                    'containerClause' =>  $containerClause,
                    'laborClause'  =>  $rushClause,
                    'hisRcvClause'  => $hisRcvClause,
                    'chgCode' => 'RUSH-LABOR'
            ]);

            $recResult['OVERTIME-LABOR'] = $this->getRecLaborQuery($data, [
                    'clause' => $clause,
                    'containerClause' =>  $containerClause,
                    'laborClause'  =>  $otClause,
                    'hisRcvClause'  => $hisRcvClause,
                    'chgCode' => 'OVERTIME-LABOR'
            ]);
        }

        if ($invoiceCriteria['calculateAll'] || $invoiceCriteria['processing']) {
            //get OP labor and WO labor from wrk_hrs_ord_prc , wrk_hrs_wo table
            $hisOrdClause = $this->getOrderProcHisLabor($data, $orderNums);

            //get Ord_Proc labor rush cost and overtime cost
            $ordResult['RUSH-LABOR'] = $this->getOrdLaborQuery($data, [
                    'clause' =>   $clause,
                    'orderClause' =>  $orderClause,
                    'laborClause'  =>  $rushClause,
                    'hisOrdClause'  => $hisOrdClause,
                    'chgCode' => 'RUSH-LABOR'
            ]);

            $ordResult['OVERTIME-LABOR'] = $this->getOrdLaborQuery($data, [
                    'clause' =>   $clause,
                    'orderClause' =>  $orderClause,
                    'laborClause'  =>  $otClause,
                    'hisOrdClause'  => $hisOrdClause,
                    'chgCode' => 'OVERTIME-LABOR'
            ]);


            //get WO labor rush cost and overtime cost
            $woResult['RUSH-LABOR'] = $this->getWoLaborQuery($data, [
                    'clause' =>   $clause,
                    'orderClause' =>  $orderClause,
                    'laborClause'  =>  $rushClause,
                    'hisOrdClause'  => $hisOrdClause,
                    'chgCode' => 'RUSH-LABOR'
            ]);

            $woResult['OVERTIME-LABOR'] = $this->getWoLaborQuery($data, [
                    'clause' =>   $clause,
                    'orderClause' =>  $orderClause,
                    'laborClause'  =>  $otClause,
                    'hisOrdClause'  => $hisOrdClause,
                    'chgCode' => 'OVERTIME-LABOR'
            ]);
       }


        $uniqueCustIDs = array_unique($this->vendorList);

        $laborInfo = getDefault($data['laborCharges']['info'], []);
        $laborCodes = array_keys($laborInfo);


        //calculate total rush cost and total overtime cost for each category
        foreach ($uniqueCustIDs as $custID) {
           foreach ($laborCodes as $chgCd) {
                $laborAmt[$custID][$chgCd] =
                        array_sum(getDefault($recResult[$chgCd][$custID], [])) +
                        array_sum(getDefault($ordResult[$chgCd][$custID], [])) +
                        array_sum(getDefault($woResult[$chgCd][$custID], []));
            }
        }

       return $laborAmt ? $laborAmt : [];
    }

    /*
    ****************************************************************************
    */

    static function laborChargeInvoiceCosts(&$results, $data)
    {
        //calculate sums and details
        foreach ($data['laborAmt'] as $custID => $value) {
            foreach ($value as $cc => $cost) {
                //sums
                $results['sums'][$custID] =
                        getDefault($results['sums'][$custID], 0);

                $results['sums'][$custID] += $cost;


                if (! $data['details']) {
                    continue;
                }

                //details
                $results['details'][$custID][$cc] = $data['laborCharges']['info'][$cc];

                $results['details'][$custID][$cc]['rate'] = $cost;
                $results['details'][$custID][$cc]['quantity'] = '-';

                $results['details'][$custID][$cc]['ccTotal'] = $cost;
            }
        }
    }


    /*
    ****************************************************************************
    */

    function getRecLaborQuery($data, $select)
    {
        $sql = 'SELECT    vendorID,
                          type,
                          amount
                FROM      (
			    SELECT    vendorID,
                	  	      rcv_num,
                		      dt,
                                      type,
                                      amount
                            FROM      wrk_hrs_rcv w
                            JOIN      inventory_containers co ON co.recNum = w.rcv_num
                            WHERE     cat = "e"
                            AND       inv_sts
                             '.$select['clause'].'
                             '.$select['laborClause'].'
                             '.$select['containerClause'].'
                             '.$select['hisRcvClause'].'
                            AND       DATE(dt) >= ?
                            AND       DATE(dt) <= ?
                            GROUP BY  rcv_num,
                                      type,
                                      dt DESC
                ) a
                GROUP BY rcv_num, type
                ';


        $params = [
           $data['startDate'],
           $data['endDate']
        ];


        $result = $this->db->queryResults($sql, $params,  \PDO::FETCH_ASSOC);

        $list = [];

        foreach ($result as $value) {
            $vendorID = $value['vendorID'];
            $amount = $value['amount'];
            $list[$vendorID][] = $amount;
        }


        $custID = array_column($result, 'vendorID');

        $this->vendorList = array_merge($this->vendorList, array_unique($custID));

        return $list ? $list : [];

    }

    /*
    ****************************************************************************
    */

    function getWoLaborQuery($data, $select)
    {
        $cancelStatus = \tables\orders::STATUS_CANCELED;

        $sql = 'SELECT    vendorID,
                          type,
                          amount
                FROM      (
			    SELECT    vendorID,
                	  	      scn_ord_num,
                		      dt,
                                      w.type,
                                      amount
                            FROM      wrk_hrs_wo w
                            JOIN      wo_hdr wh ON wh.wo_id = w.wo_id
                            JOIN      neworder n ON n.scanordernumber = wh.scn_ord_num
                            JOIN      order_batches ob ON ob.id = n.order_batch
                            JOIN      statuses s ON s.id = n.statusID
                            WHERE     cat = "e"
                            AND       inv_sts
                             '.$select['clause'].'
                             '.$select['laborClause'].'
                             '.$select['orderClause'].'
                             '.$select['hisOrdClause'].'
                            AND       DATE(dt) >= ?
                            AND       DATE(dt) <= ?
                            AND       s.shortName != ?
                            GROUP BY  scn_ord_num,
                                      type,
                                      dt DESC
                ) a
                GROUP BY scn_ord_num, type
                ';

        $params = [
           $data['startDate'],
           $data['endDate'],
           $cancelStatus
        ];

        $result = $this->db->queryResults($sql, $params, \PDO::FETCH_ASSOC);

        $list = [];

        foreach ($result as $value) {
            $vendorID = $value['vendorID'];
            $amount = $value['amount'];
            $list[$vendorID][] = $amount;
        }


        $custID = array_column($result, 'vendorID');

        $this->vendorList = array_merge($this->vendorList, array_unique($custID));

        return $list ? $list : [];

    }

    /*
    ****************************************************************************
    */

    function getOrdLaborQuery($data, $select)
    {
        $cancelStatus = \tables\orders::STATUS_CANCELED;

        $sql = 'SELECT    vendorID,
                          type,
                          amount
                FROM      (
			    SELECT    vendorID,
                	  	      scan_ord_nbr,
                		      dt,
                                      w.type,
                                      amount
                            FROM      wrk_hrs_ord_prc w
                            JOIN      neworder n ON n.scanordernumber = w.scan_ord_nbr
                            JOIN      statuses s ON s.id = n.statusID
                            JOIN      order_batches ob ON ob.id = n.order_batch
                            WHERE     cat = "e"
                            AND       inv_sts
                             '.$select['clause'].'
                             '.$select['laborClause'].'
                             '.$select['orderClause'].'
                             '.$select['hisOrdClause'].'
                            AND       DATE(dt) >= ?
                            AND       DATE(dt) <= ?
                            AND       s.shortName != ?
                            GROUP BY  scan_ord_nbr,
                                      type,
                                      dt DESC
                ) a
                GROUP BY scan_ord_nbr, type
              ';

        $params = [
           $data['startDate'],
           $data['endDate'],
           $cancelStatus
        ];

        $result = $this->db->queryResults($sql, $params, \PDO::FETCH_ASSOC);


        $list = [];

        foreach ($result as $value) {
            $vendorID = $value['vendorID'];
            $amount = $value['amount'];
            $list[$vendorID][] = $amount;
        }

        $custID = array_column($result, 'vendorID');

        $this->vendorList = array_merge($this->vendorList, array_unique($custID));

        return $list ? $list : [];

    }

    /*
    ****************************************************************************
    */


    function updateInvoTables($params=[])
    {
        $openItems = $getItems = [];

        $getVars = $params ? $params : $this->db->getArray('get');

        $open = $this->getBillableData($getVars);

        $return = [];

        if (getDefault($open['sums'])) {
            foreach ($open['sums'] as $vendorID => $sum) {
                $return[] = [
                    'sts' => 'Open',
                    'total' => $sum,
                    'custID' => $vendorID,
                ];
            }
        }

        $results = $this->getInvoicedeData($getVars);

        foreach ($results['invoiced'] as $key => $values) {

            $cancelInvoice = getDefault($results['canceled'][$key], []);

            $cancelInvoiceSum = $cancelInvoiceDt = NULL;
            // $cancelInvoiceNo default value should be '' (not NULL)
            $cancelInvoiceNo = '';

            if ($cancelInvoice) {
                $cancelInvoiceNo = $cancelInvoice['inv_num'];
                $cancelInvoiceDt = $cancelInvoice['inv_dt'];
                $cancelInvoiceSum = $cancelInvoice['inv_amt'];
            }

            $return[] = [
                'sts' => $values['status'],
                'invNbr' => $values['inv_num'],
                'invDT' => $values['inv_dt'],
                'cnclNbr' => $cancelInvoiceNo,
                'cnclDT' => $cancelInvoiceDt,
                'currency' => $values['inv_cur'],
                'total' => $values['inv_amt'],
                'pmntDT' => $values['inv_paid_dt'] ? $values['inv_paid_dt'] : '',
                'check' => $values['checkNbr'],
                'custID' => $values['cust_id'],
                'type' => $cancelInvoice ? 'C' : 'O',
                ''
            ];

            if ($cancelInvoice) {
                $return[] = [
                    'sts' => 'Invoiced',
                    'invNbr' => $cancelInvoiceNo,
                    'invDT' => $cancelInvoiceDt,
                    'cnclNbr' => '',
                    'currency' => $values['inv_cur'],
                    'total' => $cancelInvoiceSum,
                    'custID' => $values['cust_id'],
                    'type' => 'C',
                ];
            }
        }


        //Open Items
        foreach ($open['items'] as $key => $value) {
            $cat = $value['cat'];
            if ($cat == "Storage") {
                continue;
            }
            $openItems[$cat][] = $value['id'];
        }

        //getVars['item']
        if (getDefault($getVars['items'])) {
            foreach ($getVars['items'] as $key => $value) {
                if ($key == "Storage") {
                   continue;
                }
                $cat = $key == 'orderPrc' ? 'Order Processing' : $key;
                $getItems[$cat] = array_keys($value);
            }
        }

        $invItems = is_array($getVars['items']) ? $getItems : $openItems;

        return [
            'invoices' => $return,
            'sums' => $open['sums'],
            'items' => $open['items'],
            'details' => getDefault($open['details'], []),
            'invItemsIDs' => $invItems
        ];
    }

    /*
    ****************************************************************************
    */

    function getInvoicedeData($params=[])
    {
        $filterParams['startDate'] = getDefault($params['startDate']);
        $filterParams['endDate'] = getDefault($params['endDate']);
        $filterParams['custID'] = getDefault($params['custID']);

        $clauses = $this->getClauses($filterParams, 'inv_dt', 'cust_id');

        $invoicedSql = '
                SELECT    inv_id,
                          cust_id,
                          inv_num,
                          inv_dt,
                          inv_cur,
                          inv_amt,
                          IF(inv_paid_dt, inv_paid_dt, "") AS inv_paid_dt,
                          IF(inv_sts = "i", "Invoiced",
                              IF(inv_sts = "p", "Paid", "Canceled")
                          ) AS status,
                          CONCAT_WS(" ", inv_paid_typ, inv_paid_ref) AS checkNbr
                FROM      invoice_hdr
                WHERE     inv_typ = "o"
                AND       inv_sts IN ("i", "p", "c")
                AND       inv_amt IS NOT NULL
                AND       sts != "d"
                ' . $clauses['whereClause'];

        $canceledSql = '
                SELECT    inv_org_id,
                          inv_num,
                          inv_dt,
                          inv_amt
                FROM      invoice_hdr
                WHERE     inv_typ = "c"
                AND       inv_org_id
                AND       sts != "d"
                ' . $clauses['whereClause'];

        return [
            'invoiced' => $this->db->queryResults($invoicedSql, $clauses['params']),
            'canceled' => $this->db->queryResults($canceledSql, $clauses['params']),
        ];
    }

    /*
    ****************************************************************************
    */

    function getBillableData($params=[])
    {
        $getVars = $params ? $params : $this->db->getArray('get');

        // Check if this is a details item search
        $items = isset($getVars['items']) ?
            array_filter($getVars['items']) : [];

        if ($items) {

            foreach ($items as &$catItems) {
                $catItems = array_keys($catItems);
            }

            $getVars['items'] = $items;
        } else {
            $getVars['items'] = [];
        }
        // Needs to be change to pass params by key
        // BAD -> $getVars['var'] = value

        // If not an items search defer to details value passed
        $details = $items || getDefault($getVars['details']);

        $dateRange = getDefault($getVars['startDate']) &&
            getDefault($getVars['endDate']);

        if (! $dateRange && ! $details) {
            return $this->db->results = ['error' => 'dateMissing'];
        }

        $custID = getDefault($getVars['custID']);

        if (! $custID) {
            $getVars['sums'] = TRUE;
        } else if ($custID && ! $details) {
            $getVars['items'] = TRUE;
        } else if ($custID && $details) {
            $getVars['details'] = TRUE;
        } else {
            return $this->db->results = ['error' => 'invalidParams'];
        }

        $receiving = new \tables\invoices\receiving($this->db);
        $storage = new \tables\invoices\storage($this->db);
        $processing = new \tables\invoices\processing($this->db);
        $invoiceCosts = new \tables\invoices\invoiceCosts($this->db);

        $custSearch = $custID ? [$custID] : [];
        $uomRes = $getVars['uomRes'] =
            $invoiceCosts->getCosts($custSearch, 'uom');

        $model = \invoices\model::init($this->db)
            ->setRates($uomRes)
            ->customerUOMs();

        $rcvRes = $receiving->calculate($getVars, $model);
        $strRes = $storage->calculate($getVars, $model);
        $opRes = $processing->calculate($getVars, $model);

        $all = $strRes + $rcvRes;

        if (array_filter($opRes)) {
            foreach ($opRes as $row) {

                $custIDs = array_keys($row);

                foreach ($custIDs as $custID) {
                    $all[$custID] = TRUE;
                }
            }
        }

        $costRes = $getVars['costRes'] = $invoiceCosts->getCosts($custSearch);

        // storage -  updating $results inside getProcessingCosts() function
        $array = $strRes;
        $rates = getDefault($costRes['rates'], []);
        $costInfo = getDefault($costRes['info'], []);

        $storageTotals = [];

        $sql = 'SELECT *
                FROM   charge_cd_mstr
                WHERE  chg_cd_type = "Storage"';
        $res = $this->db->queryResults($sql);

        $ccUOMs = [];
        foreach ($res as $row) {
            $uom = $row['chg_cd_uom'];
            $ccUOMs[$uom] = $chgCd = $row['chg_cd'];
        }

        //get the criteria to show each category
        $invoiceCriteria = $this->getInvoiceCriteria($getVars);

        if ($invoiceCriteria['calculateAll'] || $invoiceCriteria['storage']) {
            foreach ($array as $vendorID => $ccs) {
                self::getStorageDetails($storageTotals, [
                    'ccs' => $ccs,
                    'rates' => $rates,
                    'category' => 'STORAGE',
                    'vendorID' => $vendorID,
                    'costInfo' => $costInfo,
                ]);
            }
        }

        $results = [];

        foreach (getDefault($storageTotals['details'], []) as $custID => $ccs) {
            $results['sums'][$custID] = getDefault($results['sums'][$custID], 0);
            $ccTotals = array_column($ccs, 'ccTotal');
            $results['sums'][$custID] += array_sum($ccTotals);
        }

        if ($invoiceCriteria['calculateAll'] || $invoiceCriteria['receiving']) {
            // receiving -  updating $results inside getProcessingCosts() function
            $this->getReceivingInvoiceCosts($results, [
                'array' => $rcvRes,
                'costRes' => $costRes,
                'category' => 'RECEIVING',
                'details' => $getVars['details'],
            ]);
        }

        //get the Labor charge code from charge code mstr
        $laborCharges = $invoiceCosts->getLaborCharge();

        // Labor charge Code - Receiving , Ord_Proc, Work order
        $labors = $this->getLaborCharges([
            'getVars'  => $getVars,
            'laborCharges' => $laborCharges,
            'startDate' => $getVars['startDate'],
            'endDate' => $getVars['endDate'],
            'custIDs' => $custSearch,
            'invoiceCriteria' => $invoiceCriteria,
        ]);

        self::laborChargeInvoiceCosts($results, [
            'laborCharges' => $laborCharges,
            'laborAmt' => $labors,
            'details' => $getVars['details']
        ]);

        if ($invoiceCriteria['calculateAll'] || $invoiceCriteria['processing']) {
            // order processing + work orders
            // updating $results inside getProcessingCosts() function
            self::getInvoiceProcessingCosts($results, [
                'array' => $opRes,
                'costRes' => $costRes,
                'category' => 'ORD_PROC',
                'details' => $getVars['details'],
            ]);
        }

        $model::formatDtsQty($results['details']);

        if (isset($getVars['items'])) {

            $getVars['categories'] = [
                'receiving',
                'processing',
            ];

            $results['items'] = $this->get($model, $getVars, $receiving, $processing);

            if (isset($strRes[$custID]) && isset($storageTotals['details'][$custID])) {
                foreach ($storageTotals['details'][$custID] as $cc => $row) {
                    $results['items'][] = [
                        'id' => $row['chg_cd_des'],
                        'cat' => 'Storage',
                        'qty' => $row['quantity'],
                        'uom' => $row['chg_cd_uom'],
                        'dt' => $getVars['startDate'].' to '.$getVars['endDate'],
                    ];

                    $results['details'][$custID][$cc] = $row;
                }
            }
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function money($amount, $noCurreny=FALSE)
    {
        $currency = $noCurreny ? NULL : 'USD';
        if (! is_array($amount)) {
            return number_format($amount, 2).' '.$currency;
        }

        foreach ($amount as &$each) {
            $each = number_format($each, 2).' '.$currency;
        }

        return $amount;
    }

    /*
    ****************************************************************************
    */

    function updateInvoiceProcessing($post)
    {
        $billTo = $shipTo = $chargeCodes = $uoms = $recMonths = $storDays = [];

        $invoiceNo = $post['invoiceNo'];
        $vendorID = $post['vendorID'];

        $invoiceDetails = new \invoices\details($this->db);

        $invoiceExists = $invoiceDetails->checkIfExists($invoiceNo);

        if ($invoiceExists) {
            return FALSE;
        }

        parse_str($post['billTo'], $billTo);
        parse_str($post['shipTo'], $shipTo);

        $details = json_decode($post['details']['data']);

        $vendors = new \tables\vendors($this->db);
        $chargeMaster = new \tables\customer\chargeCodeMaster($this->db);
        $headers = new \invoices\headers($this->db);

        $status = $headers->getStatusByNumber($invoiceNo);

        if (in_array($status, ['p', 'c'])) {
            // "paid" or "canceled" Invoice can not be invoiced
            return FALSE;
        }

        $amount = 0;

        $warehouseID = $vendors->getVendorWarehouse($vendorID);

        $userID = \access::getUserID();

        $titles = $post['details']['titles'];

        $columnNumbers = array_flip($titles);

        $chargeCodeColumnNo = $columnNumbers['ITEM'];
        $descriptionColumnNo = $columnNumbers['DESC'];
        $uomColumnNo = $columnNumbers['UOM'];
        $priceColumnNo = $columnNumbers['PRICE'];
        $amountColumnNo = $columnNumbers['AMT'];

        foreach ($details as $values) {

            $chargeCode = $values[$chargeCodeColumnNo];

            $chargeCodes[$chargeCode] = TRUE;

            $uom = $values[$uomColumnNo];

            $uoms[$uom] = TRUE;
        }

        $uomKeys = array_keys($uoms);

        $chargeKeys = array_keys($chargeCodes);

        $chargeIDs = $chargeMaster->getChargIDsByCodes($chargeKeys);

        $headerSql = '
            UPDATE invoice_hdr
            SET     wh_id = ?,
                    cust_id = ?,
                    inv_sts = "i",
                    inv_dt = ?,
                    inv_amt = ?,
                    cust_ref = ?,
                    net_terms = ?,
                    bill_to_add1 = ?,
                    bill_to_state = ?,
                    bill_to_city = ?,
                    bill_to_cnty = ?,
                    bill_to_zip = ?,
                    update_by = ?,
                    sts = "u"
            WHERE   inv_num = ?';

        $detailsInsertSql = '
            INSERT INTO invoice_dtls (
                    inv_num,
                    wh_id,
                    cust_id,
                    chg_cd_id,
                    chg_cd_desc,
                    chg_cd_qty,
                    chg_cd_uom,
                    chg_cd_price,
                    chg_cd_amt,
                    create_by
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?
                )';


        $orders = getDefault($post['items']['Order Processing']) ?
                    getDefault($post['items']['Order Processing']) :
                         getDefault($post['items']['orderPrc']);

        $billableOrders = [];

        if ($orders) {
            $qMark = $this->db->getQMarkString($orders);
            $sql = 'SELECT id,
                           scanOrderNumber AS ord_num
                    FROM   neworder
                    WHERE  scanOrderNumber IN ('.$qMark.')';

            $results = $this->db->queryResults($sql, $orders);

            $opObj = new \tables\invoices\processing($this->db);
            $billableOrders = ! $results ? [] :
                $opObj->billableOrders([
                    'orderNums' => $results
                ]);
        }

        $recNums = getDefault($post['items']['Receiving']);

        //get the container received date
        $containerInfo = $recNums ? $this->getContainerInfo($recNums) : [];

        //get the receiving latest expected - type = "e"
        $recLabor = $recNums ?
                $this->getRcvLaborHrs($recNums, $post['dateRange']) : [];

        $ordLabor = $orders ?
                $this->getOrdLaborHrs($orders, $post['dateRange']) : [];

        $woLabor = $orders ?
                $this->getWoLaborHrs($orders, $post['dateRange']) : [];


       if (getDefault($post['items']['Receiving']) &&
                                    in_array('MONTH', $uomKeys)) {
            $recMonths = \invoices\model::init($this)->getBillableDts([
                'cat' => 'r',
                'period' => 'monthly',
                'dates' => $post['dateRange'],
                'custs' => [$vendorID],
                'details' => TRUE,
             ]);
        }


        if (getDefault($post['items']['Storage'])) {
            $storDays = \invoices\model::init($this)->getBillableDts([
                'cat' => 's',
                'period' => 'daily',
                'dates' => $post['dateRange'],
                'custs' => [$vendorID],
                'details' => TRUE,
            ]);
        }

        $invoiceID = $headers->getByInvoiceNumber($invoiceNo);

        $nextID = $invoiceID ? $invoiceID : $vendors->getNextID('invoice_hdr');

        $this->db->beginTransaction();

        foreach ($details as $values) {

            $chargeCode = $values[$chargeCodeColumnNo];

            $detailsParams = [
                $invoiceNo,
                $warehouseID,
                $vendorID,
                $chargeIDs[$chargeCode]['chg_cd_id'],
                $values[$descriptionColumnNo],
                $values[$amountColumnNo] / $values[$priceColumnNo],
                $values[$uomColumnNo],
                $values[$priceColumnNo],
                $values[$amountColumnNo],
                $userID,
            ];

            $this->db->runQuery($detailsInsertSql, $detailsParams);

            $amount += $values[$amountColumnNo];
        }

        $this->db->runQuery($headerSql, [
            $warehouseID,
            $vendorID,
            $post['header']['invDt'],
            $amount,
            $post['header']['custRef'],
            $post['header']['terms'],
            $billTo['bill_to_add1'],
            $billTo['bill_to_city'],
            $billTo['bill_to_state'],
            $billTo['bill_to_cnty'],
            $billTo['bill_to_zip'],
            $userID,
            $invoiceNo,
        ]);

        $this->updateHistory([
            'invID' => $nextID,
            'target' => $post['items'],
            'custID' => $vendorID,
            'containerValues' => $containerInfo,
            'tables' => [
                'Receiving' => [
                    'fields' => ['inv_id', 'cust_id', 'rcv_nbr', 'recv_dt'],
                    'table' => 'inv_his_rcv',
                ],
            ],
        ]);

        $this->updateOrdProcHistory([
            'values' => $billableOrders,
            'custID' => $vendorID,
            'invID' => $nextID,
        ]);

        $this->updateMonthHistory([
            'custID' => $vendorID,
            'invID' => $nextID,
            'invNum' => $invoiceNo,
            'cats' => [
                'r' => $recMonths,
                's' =>  $storDays,
            ],
        ]);

        $this->updateRcvLaborHistory([
            'invID' => $nextID,
            'invNum' => $invoiceNo,
            'recLabor' => $recLabor,
        ]);

        $this->updateOrdLaborHistory([
            'invID' => $nextID,
            'invNum' => $invoiceNo,
            'ordLabor' => $ordLabor,
        ]);

        $this->updateWoLaborHistory([
            'invID' => $nextID,
            'invNum' => $invoiceNo,
            'woLabor' => $woLabor,
        ]);

        $this->db->commit();

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function updateHistory($params)
    {
        $custID = $params['custID'];
        $invoiceID = $params['invID'];
        $containerValues = $params['containerValues'];


        foreach ($params['tables'] as $cat => $row) {
            $values = getDefault($params['target'][$cat]);

            if (! $values) {
                continue;
            }

            $this->rmInvHist($row['table'], $params);

            $sql = 'INSERT INTO '.$row['table'].' (
                        '.implode(',', $row['fields']).'
                    ) VALUES (
                        ?, ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                        inv_id = VALUES(inv_id),
                        inv_sts = 1';

            foreach ($containerValues as $value) {
                $this->db->runQuery($sql, [
                    $invoiceID,
                    $custID,
                    $value['recNum'],
                    $value['setDate']
                ]);
            }
        }
    }

    /*
    ****************************************************************************
    */

    function rmInvHist($table, $params)
    {
        $invID = $params['invID'];

        $sql = 'UPDATE    '.$table.'
                SET       inv_sts = 0
                WHERE     inv_id = ?
                ';

        $this->db->runQuery($sql, [$invID]);
    }

    /*
    ****************************************************************************
    */

    function updateOrdProcHistory($params)
    {
        if (! $params['values']) {
            return FALSE;
        }

        $this->rmInvHist('inv_his_ord_prc', $params);

        $sql = 'INSERT INTO inv_his_ord_prc (
                    inv_id,
                    cust_id,
                    ord_id,
                    ord_num
                ) VALUES (
                    ?, ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    inv_id = VALUES(inv_id),
                    inv_sts = 1';

        foreach ($params['values'] as $ordID => $row) {
            $this->db->runQuery($sql, [
                $params['invID'],
                $params['custID'],
                $ordID,
                $row['ord_num'],
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function updateMonthHistory($params)
    {
        $invID = $params['invID'];
        $invNum = $params['invNum'];
        $custID = $params['custID'];

        $this->rmInvHist('inv_his_month', ['invID' => $invID]);

        foreach ($params['cats'] as $cat => $dates) {
            if (! getDefault($dates[$custID])) {
                continue;
            }

            $sql = 'INSERT INTO inv_his_month (
                        inv_id,
                        inv_num,
                        cust_id,
                        inv_date,
                        type
                    ) VALUES (
                        ?, ?, ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                        inv_id = VALUES(inv_id),
                        inv_num = VALUES(inv_num),
                        inv_sts = 1';

            foreach (array_keys($dates[$custID]) as $date) {
               $this->db->runQuery($sql, [$invID, $invNum, $custID, $date, $cat]);
            }

        }
    }

    /*
    ****************************************************************************
    */

    static function getStorageDetails(&$storageTotals, $params)
    {
        $ccs = $params['ccs'];
        $rates = $params['rates'];
        $category = $params['category'];
        $vendorID = $params['vendorID'];
        $costInfo = $params['costInfo'];

        if (! $ccs) {
            return;
        }

        $uomCCs = [];
        foreach ($costInfo[$category] as $cc => $row) {
            $uom = $row['chg_cd_uom'];
            $uomCCs[$uom] = $cc;
        }

        foreach ($ccs as $uom => $quantity) {

            // Get charge code from UOM
            $cc = $uomCCs[$uom];

            if (! isset($rates[$category][$vendorID][$cc]) || ! $quantity) {
                continue;
            }

            $rate = $rates[$category][$vendorID][$cc];
            $row = $costInfo[$category][$cc];
            $row['rate'] = $rates[$category][$vendorID][$cc];
            $row['quantity'] = round($quantity, 2);

            $row['ccTotal'] = $rate * $quantity;
            $storageTotals['details'][$vendorID][$cc] = $row;
        }
    }

    /*
    ****************************************************************************
    */

    function cancelInvoice($invoiceNo)
    {
        if (! $invoiceNo) {
            return FALSE;
        }

        $historyTables = [
            'inv_his_ctn',
            'inv_his_ctn_dt',
            'inv_his_month',
            'inv_his_ord_prc',
            'inv_his_plt',
            'inv_his_plt_dt',
            'inv_his_rcv',
            'inv_his_wo',
        ];

        $details = new \invoices\details($this->db);
        $headers = new \invoices\headers($this->db);

        $headerUpdateSql = '
            UPDATE    invoice_hdr
            SET       inv_sts = "c",
                      update_by = ?,
                      sts = "u"
            WHERE     inv_id = ?';

        $headerInsertSql = '
            INSERT INTO invoice_hdr (
                    inv_num,
                    inv_dt,
                    inv_org_id,
                    inv_org,
                    inv_typ,
                    wh_id,
                    cust_id,
                    inv_sts,
                    inv_cur,
                    inv_amt,
                    inv_tax,
                    cust_ref,
                    net_terms,
                    bill_to_add1,
                    bill_to_add2,
                    bill_to_state,
                    bill_to_city,
                    bill_to_cnty,
                    bill_to_zip,
                    bill_to_contact,
                    create_by
            ) VALUES (
                ?, CURDATE(), ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?
            )';

        $detailsSql = '
            INSERT INTO invoice_dtls (
                    inv_num,
                    wh_id,
                    cust_id,
                    chg_cd_id,
                    chg_cd_desc,
                    chg_cd_qty,
                    chg_cd_uom,
                    chg_cd_price,
                    chg_cd_cur,
                    chg_cd_amt,
                    create_by
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?
            )';

        $cancellingHeaders = $headers->getCancellingData($invoiceNo);

        if ($cancellingHeaders['inv_sts'] != 'i') {

            $status = NULL;

            switch ($cancellingHeaders['inv_sts']) {
                case 'o':

                    $status = '"Open"';

                    break;
                case 'c':

                    $status = '"Canceled"';

                    break;
                case 'p':

                    $status = '"Paid"';

                    break;
                default:
                    break;
            }

            // only an Invoice with invoiced status can be cancelled
            return [
                'errors' => [$status . ' Invoice can not be cancled'],
            ];
        }

        $userID = \access::getUserID();

        $cancellingDetails = $details->getCancellingData($invoiceNo);

        $invoiceID = $headers->getByInvoiceNumber($invoiceNo);

        $nextInvoiceNumber = $headers->getNextInvoiceNumber();

        $this->db->beginTransaction();

        foreach ($historyTables as $table) {

            $historySql = '
                UPDATE    ' . $table . '
                SET       inv_sts = 0
                WHERE     inv_id = ?';

            $this->db->runQuery($historySql, [$invoiceID]);
        }

        $this->db->runQuery($headerUpdateSql, [$userID, $invoiceID]);

        $headerParams = array_values($cancellingHeaders);

        array_unshift($headerParams, 'c');
        array_unshift($headerParams, $invoiceNo);
        array_unshift($headerParams, $invoiceID);
        array_unshift($headerParams, $nextInvoiceNumber);

        $headerParams[] = $userID;

        $this->db->runQuery($headerInsertSql, $headerParams);

        foreach ($cancellingDetails as $values) {

            $detailsParams = array_values($values);

            array_unshift($detailsParams, $nextInvoiceNumber);

            $detailsParams[] = $userID;

            $this->db->runQuery($detailsSql, $detailsParams);
        }

        $this->db->commit();

        return [
            'errors' => NULL,
        ];
    }

    /*
    ****************************************************************************
    */

    function getContainerInfo($values)
    {
        $qMarks = $this->db->getQMarkString($values);

        $sql = 'SELECT  name,
                        recNum,
                        setDate
                FROM    inventory_containers
                WHERE   recNum IN (' . $qMarks . ')';

        $results = $this->db->queryResults($sql, $values);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getRcvLaborHrs($values, $dateRange)
    {
        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];

        $qMarks = $this->db->getQMarkString($values);

        $sql = 'SELECT *
                FROM    wrk_hrs_rcv w
                WHERE   inv_sts
                AND     rcv_num IN (' . $qMarks . ')
                AND     DATE(dt) >= ?
                AND     DATE(dt) <= ?
                ';

        $params = array_merge($values, [$startDate], [$endDate]);

        $results = $this->db->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function updateRcvLaborHistory($params)
    {
        $invNum = $params['invNum'];
        $invoiceID = $params['invID'];
        $recLabor = $params['recLabor'];

        $this->rmInvHist('wrk_hrs_rcv', ['invID' => $invoiceID]);

        $sql = 'UPDATE  wrk_hrs_rcv
                SET     inv_id = ?,
                        inv_num = ?
                WHERE   id = ?
                AND     inv_sts';

        foreach ($recLabor as $key => $value) {
            $this->db->runQuery($sql, [
                    $invoiceID,
                    $invNum,
                    $key
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function getOrdLaborHrs($values, $dateRange)
    {
        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];

        $qMarks = $this->db->getQMarkString($values);

        $sql = 'SELECT *
                FROM      wrk_hrs_ord_prc w
                WHERE     inv_sts
                AND       scan_ord_nbr IN (' . $qMarks . ')
                AND       DATE(dt) >= ?
                AND       DATE(dt) <= ?
               ';

        $params = array_merge($values, [$startDate], [$endDate]);

        $results = $this->db->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function  updateOrdLaborHistory($params)
    {
        $invNum = $params['invNum'];
        $invoiceID = $params['invID'];
        $ordLabor = $params['ordLabor'];

        $this->rmInvHist('wrk_hrs_ord_prc', ['invID' => $invoiceID]);

        $sql = 'UPDATE  wrk_hrs_ord_prc
                SET     inv_id = ?,
                        inv_num = ?
                WHERE   id = ?
                AND     inv_sts';

        foreach ($ordLabor as $key => $value) {
            if (! isset($value)) {
                continue;
            }

            $this->db->runQuery($sql, [
                    $invoiceID,
                    $invNum,
                    $key
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function getWoLaborHrs($values, $dateRange)
    {
        $startDate = $dateRange['startDate'];
        $endDate = $dateRange['endDate'];

        $qMarks = $this->db->getQMarkString($values);

        $sql = 'SELECT *
                FROM      wrk_hrs_wo w
                JOIN      wo_hdr wh ON wh.wo_id = w.wo_id
                WHERE     inv_sts
                AND       scn_ord_num IN (' . $qMarks . ')
                AND       DATE(dt) >= ?
                AND       DATE(dt) <= ?
               ';

        $params = array_merge($values, [$startDate], [$endDate]);

        $results = $this->db->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function  updateWoLaborHistory($params)
    {
        $invNum = $params['invNum'];
        $invoiceID = $params['invID'];
        $woLabor = $params['woLabor'];

        $this->rmInvHist('wrk_hrs_wo', ['invID' => $invoiceID]);

        $sql = 'UPDATE  wrk_hrs_wo
                SET     inv_id = ?,
                        inv_num = ?
                WHERE   id = ?
                AND     inv_sts';

        foreach ($woLabor as $key => $value) {
            if (! isset($value)) {
                continue;
            }

            $this->db->runQuery($sql, [
                    $invoiceID,
                    $invNum,
                    $key
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function getReceivingHisLabor($data, $recNums=[])
    {
        $recClause = $recNums ?
            'AND rcv_num IN ('.implode(',', $recNums).')' : NULL;

        $sql = 'SELECT   rcv_num
                FROM     wrk_hrs_rcv w
                JOIN     invoice_hdr h ON h.inv_id = w.inv_id
                WHERE    w.inv_sts
                AND      inv_typ = "o"
                AND      h.inv_sts IN ("p", "i")
                AND      dt >= ?
                AND      dt <= ?
               ' . $recClause;

        $selectParams = [
          $data['startDate'],
          $data['endDate']
        ];

        $hisResults = $this->db->queryResults($sql, $selectParams);

        $hisRcvIDs = $hisResults ? array_keys($hisResults) : [];


        return $hisRcvIDs ?
            'AND rcv_num NOT IN ('.implode(',', $hisRcvIDs).')' : NULL;
    }


    /*
    ****************************************************************************
    */

    function getOrderProcHisLabor($data, $orderNums=[])
    {
        $ordClause = $orderNums ?
            'AND scan_ord_nbr IN ('.implode(',', $orderNums).')' : NULL;

        $sql = 'SELECT   scan_ord_nbr
                FROM     wrk_hrs_ord_prc w
                JOIN     invoice_hdr h ON h.inv_id = w.inv_id
                WHERE    w.inv_sts
                AND      inv_typ = "o"
                AND      h.inv_sts IN ("p", "i")
                AND      dt >= ?
                AND      dt <= ?
               ' . $ordClause;

        $selectParams = [
          $data['startDate'],
          $data['endDate']
        ];

        $hisResults = $this->db->queryResults($sql, $selectParams);

        $hisOrdIDs = $hisResults ? array_keys($hisResults) : [];


        return $hisOrdIDs ?
            'AND scanordernumber NOT IN ('.implode(',', $hisOrdIDs).')' : NULL;
    }

    /*
    ****************************************************************************
    */

    function getInvoiceCriteria($getVars)
    {
        $rcvGlobalCheck = getDefault($getVars['receivingChecked']) == 'on';
        $strGlobalCheck = getDefault($getVars['storageChecked']) == 'on';
        $ordGlobalCheck = getDefault($getVars['processingChecked']) == 'on';

        $rcvItemCheck = is_array($getVars['items'])
                 && getDefault($getVars['items']['Receiving']);

        $storItemCheck = is_array($getVars['items'])
                 && getDefault($getVars['items']['Storage']);

        $ordItemCheck = is_array($getVars['items'])
                 && getDefault($getVars['items']['orderPrc']);

        return [
            'receiving' => $rcvGlobalCheck && $rcvItemCheck,
            'storage' => $strGlobalCheck && $storItemCheck,
            'processing' => $ordGlobalCheck && $ordItemCheck,
            'calculateAll' => ! $rcvGlobalCheck && ! $strGlobalCheck && ! $ordGlobalCheck
        ];
    }

    /*
    ****************************************************************************
    */

    function checkSummaryDate($params)
    {
        $custID = $params['vendorID'] ? $params['vendorID']
                         : $params['openCust'];
        $endDate = $params['toDate'];

        $sql = 'SELECT  last_active
                FROM    ctn_sum
                WHERE   cust_id = ?
                AND     DATE(last_active) >= ?
                ';

        $results = $this->db->queryResult($sql, [$custID, $endDate]);

        return $results;
    }

    /*
    ****************************************************************************
    */
}
