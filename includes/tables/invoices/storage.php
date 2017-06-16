<?php

namespace tables\invoices;

use models\config;

class storage extends \tables\_default
{
    static $today;

    static $monthDays;

    const DEBUG = FALSE;

    public $ajaxModel = 'invoices\\storage';

    public $primaryKey = 'ca.id';

    public $fields = [
        'ucc128' => [
            'select' => 'CONCAT(co.vendorID,
                            b.id,
                            LPAD(ca.uom, 3, 0),
                            LPAD(ca.cartonID, 4, 0)
                        )',
            'display' => 'UCC128',
            'acDisabled' => TRUE,
        ],
        'name' => [
            'select' => 'co.name',
            'display' => 'CNTR NM',
         ],
        'containerRecNum' => [
            'select' => 'b.recNum',
            'display' => 'CNTR NBR',
        ],
        'batchID' => [
            'display' => 'BATCH NBR',
        ],
        'sku' => [
            'select' => 'p.sku',
            'display' => 'SKU',
        ],
        'uom' => [
            'select' => 'LPAD(UOM, 3, 0)',
            'display' => 'UOM',
        ],
        'volume' => [
            'select' => 'CAST(
                              CEIL( (height *length * width /1728 ) * 4 ) / 4
                               AS DECIMAL(10,2))',
            'display' => 'VOL',
        ],
        'upcID' => [
            'select' => 'p.upc',
            'display' => 'UPC',
        ],
        'size' => [
            'select' => 'size',
            'display' => 'SIZE',
        ],
        'color' => [
            'select' => 'color',
            'display' => 'COLOR',
         ],
        'rackDate' => [
            'select' => 'ca.rackDate',
            'display' => 'DATE',
            'searcherDate' => TRUE,
        ],
        'orderID' => [
            'select' => 'n.scanordernumber',
            'display' => 'ORD NBR',
            'isNum' => 10,
            'allowNull' => TRUE,
            'update' => 'orderID',
            'updateOverwrite' => TRUE,
            'updateTable' => 'neworder',
            'updateField' => 'scanordernumber',
        ],
        'plate' => [
            'display' => 'LIC PLATE',
            'isNum' => 8,
            'allowNull' => TRUE,
            'acDisabled' => TRUE,
        ],
        'locID' => [
            'select' => 'l.displayName',
            'display' => 'LOC',
            'update' => 'locID',
            'updateOverwrite' => TRUE,
            'updateTable' => 'locations',
            'updateField' => 'displayName',
        ],
        'statusID' => [
            'select' => 's.shortName',
            'display' => 'STS',
            'searcherDD' => 'statuses\\inventory',
            'ddField' => 'shortName',
            'hintField' => 'displayName',
            'update' => 'ca.statusID',
        ],
        'isSplit' => [
            'select' => 'isSplit',
            'display' => 'isSplit',
        ],
        'unSplit' => [
            'select' => 'unSplit',
            'display' => 'unSplit',
        ],
    ];

    public $table = 'inventory_containers co
                JOIN      inventory_batches b ON co.recNum = b.recNum
                JOIN      inventory_cartons ca ON b.id = ca.batchID
                JOIN      vendors v ON v.id = co.vendorID
                JOIN      warehouses w ON v.warehouseID = w.id
                LEFT JOIN locations l ON l.id = ca.locID
                LEFT JOIN locations lm ON lm.id = ca.mLocID
                LEFT JOIN neworder n ON n.id = ca.orderID
                JOIN      statuses s ON ca.statusID = s.id
                JOIN      statuses sm ON ca.mStatusID = sm.id
                LEFT JOIN statuses os ON os.id = n.statusID
                JOIN      upcs p ON p.id = b.upcID
                ';

    public $groupBy = 'ca.id';

    static public $rackedStatuses = [
        \tables\inventory\cartons::STATUS_RECEIVED,
        \tables\inventory\cartons::STATUS_RACKED,
        \tables\inventory\cartons::STATUS_ORDER_PROCESSING,
        \tables\inventory\cartons::STATUS_SHIPPING,
    ];

    /*
    ****************************************************************************
    */

    static function init($app)
    {
        return new static($app);
    }

    /*
    ****************************************************************************
    */

    function getDB()
    {
        return $this->app;
    }

    /*
    ****************************************************************************
    */

    function getStorageCosts($data)
    {
//        $data = [
//            'fromDate' => '2016-04-03',
//            'toDate' => '2016-04-30',
//            'vendorID' => 11195,
//            'chargeCode' => 'STOR-PALLET',
//        ];

        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $vendorID = $data['vendorID'];
        $chargeCode = $data['chargeCode'];

        $toDatePlus = $this->getNextDate($toDate);

        $customerRates = new \tables\customer\customerRate($this->app);

        $rates = $customerRates->getCosts($chargeCode, $vendorID);

        $orderByPrefix = $chargeCode == 'STOR-PALLET' ? 'plate' : NULL;

        $params = [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'toDatePlus' => $toDatePlus,
            'sth' => $this->getInventory([
                'vendorID' => $vendorID,
                'toDate' => $toDate,
                'fieldList' => 'vendorID,
                                ca.batchID,
                                ca.uom,
                                ca.cartonID,
                                ca.plate,
                                ca.locID,',
                'orderByPrefix' => $orderByPrefix,
            ]),
        ];

        switch ($chargeCode) {
            case 'STOR-PALLET':
                // by pallets (plates)
                return [
                    'days' => $this->getByPallet($params),
                    'rates' => $rates,
                ];
            case 'STOR-CART':
                // by cartons
                return [
                    'days' => $this->getByCarton($params),
                    'rates' => $rates,
                ];
            default:
                break;
        }
    }

    /*
    ****************************************************************************
    */

    function getByPallet($data)
    {
        $sth = $data['sth'];

        $processed = $inventory = $platesData = [];
        $dayCount = 0;
        $plate = $invID = NULL;

        while ($values = $sth->fetch(\database\myPDO::FETCH_ASSOC)) {

            $currentPlate = $values['plate'];

            if (isset($processed[$currentPlate])) {
                // current plate was processed

                $plate = $values['plate'];
                $invID = $values['invID'];

                continue;
            }

            if ($values['plate'] != $plate || $values['invID'] != $invID) {
                if ($plate) {

                    $data['inventory'] = $inventory;
                    // store days that a carton ($invID) was on a given pallet ($plate)
                    $platesData[$plate][$invID] = $this->getCartonInvoiceData($data);

                    if ($platesData[$plate][$invID]['min'] == $data['fromDate']
                     && $platesData[$plate][$invID]['max'] == $data['toDate']) {

                        $processed[$plate] = TRUE;
                    }
                }

                $inventory = [];
            }

            $inventory[] = $values;

            $plate = $values['plate'];
            $invID = $values['invID'];
        }

        $data['inventory'] = $inventory;

        $platesData[$plate][$invID] = $this->getCartonInvoiceData($data);

        $plateDays = [];

        foreach ($platesData as $plate => $cartonData) {
            foreach ($cartonData as $cartonDays) {
                if (! $cartonDays['min'] || ! $cartonDays['max']) {
                    continue;
                }

                $dayCount = getDefault($plateDays[$plate], 0);

                $days = $this->addDays($cartonDays['min'], $cartonDays['max']) + 1;

                $plateDays[$plate] = max($dayCount, $days);
            }
        }

        return array_sum($plateDays);
    }

    /*
    ****************************************************************************
    */

    function getByCarton($data)
    {
        $fromDate = $data['fromDate'];
        $toDatePlus = $data['toDatePlus'];
        $sth = $data['sth'];

        $processed = $current = [];
        $dayCount = 0;

        while ($values = $sth->fetch(\database\myPDO::FETCH_ASSOC)) {

            $invID = $values['invID'];
            $logDate = $values['logDate'];
            $racked = in_array($values['status'], self::$rackedStatuses);

            if (isset($processed[$invID]) || $current
             && $current['invID'] == $invID && $current['logDate'] == $logDate) {
                // current InvID was processed or duplicate dates (only the lates date matters)
                continue;
            }

            if ($values['invID'] != getDefault($current['invID'])) {
                // starting process a new InvID
                $current = $values;

                if ($racked) {

                    $startDate = max($fromDate, $logDate);

                    $dayCount += $this->addDays($startDate, $toDatePlus);
                }

                if ($fromDate > $logDate) {
                    $processed[$invID] = TRUE;
                }

                continue;
            }

            $currentDate = $current['logDate'];

            if ($logDate < $fromDate) {
                // end of processing of the current InvID
                if ($racked && $currentDate != $fromDate) {

                    $endDate = $currentDate < $fromDate ? $toDatePlus : $currentDate;

                    $dayCount += $this->addDays($fromDate, $endDate);
                }

                $processed[$invID] = TRUE;

                continue;
            }

            if ($racked && $currentDate != $logDate) {
                $dayCount += $this->addDays($values['logDate'], $currentDate);
            }

            $current = $values;
        }

        return $dayCount;
    }

    /*
    ****************************************************************************
    */

    function getCartonInvoiceData($data)
    {
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $inventory = $data['inventory'];

        $processed = $current = [];

        $rackedStatuses = \tables\invoices\storage::$rackedStatuses;
        // get the smallest and the biggest date a carton was on a plate within
        // a given period of time
        $days = [
            'max' => NULL,
            'min' => NULL,
        ];

        foreach ($inventory as $values) {

            $invID = $values['invID'];
            $logDate = $values['logDate'];
            $racked = in_array($values['status'], self::$rackedStatuses);

            if (isset($processed[$invID]) || $current
             && $current['invID'] == $invID && $current['logDate'] == $logDate) {
                // current InvID was processed or duplicate dates (only the lates date matters)
                continue;
            }

            if ($values['invID'] != getDefault($current['invID'])) {
                // starting process a new InvID
                $current = $values;

                if ($racked) {

                    $startDate = max($fromDate, $values['logDate']);

                    $days = [
                        'min' => $days['min'] ?
                            min($days['min'], $startDate) : $startDate,
                        'max' => $days['max'] ?
                            $days['max'] : max($days['max'], $toDate),
                    ];
                }

                if ($fromDate > $logDate) {
                    $processed[$invID] = TRUE;
                }

                continue;
            }

            $currentDate = $current['logDate'];

            if ($logDate < $fromDate) {
                // end of processing of the current InvID
                if ($racked && $currentDate != $fromDate) {

                    $endDate = $currentDate < $fromDate ? $toDate : $currentDate;

                    $days = [
                        'min' => $days['min'] ?
                            min($days['min'], $fromDate) : $fromDate,
                        'max' => $days['max'] ?
                            $days['max'] : max($days['max'], $endDate),
                    ];
                }

                $processed[$invID] = TRUE;

                continue;
            }

            if ($racked && $currentDate != $logDate) {
                $days = [
                    'min' => $days['min'] ?
                        min($days['min'], $logDate) : $logDate,
                    'max' => $days['max'] ?
                        $days['max'] : max($days['max'], $currentDate),
                ];
            }

            $current = $values;
        }

        return $days;
    }

    /*
    ****************************************************************************
    */

    function getInventory($data)
    {
        $vendorID = getDefault($data['vendorID']);
        $toDate = getDefault($data['toDate']);
        $fieldList = getDefault($data['fieldList'], NULL);
        $orderByPrefix = getDefault($data['orderByPrefix'], NULL);

        $orderBy = 'invID,
                    logTime DESC';

        if ($orderByPrefix) {
            $orderBy = $orderByPrefix . ', ' . $orderBy;
        }

        $vendorIDs = $vendorID;

        if ($vendorID) {
            $vendorIDs = is_array($vendorID) ? $vendorID : [$vendorID];
        }

        $toClause = $toDate ? 'AND DATE(logTime) <= ?' : NULL;
        $vendorClause = ! $vendorID ? NULL :
                'AND vendorID IN (' . $this->app->getQMarkString($vendorIDs) . ')';

        $param = $vendorIDs ? $vendorIDs : [];

        $toDate && $param[] = $toDate;

        $whereClause = $vendorClause . ' ' . $toClause;

        $inactiveStatus = \tables\inventory\cartons::STATUS_INACTIVE;

        $sql = 'SELECT    invID,
                          DATE(logTime) AS logDate,
                          ' . $fieldList . '
                          shortName AS status
                FROM (
                    (
                    -- cartons that were never split and are not slit children
						SELECT    ca.id AS invID,
                                  logTime,
                                  ' . $fieldList . '
                                  s.shortName
                        FROM      inventory_cartons ca
                        JOIN      inventory_batches b ON b.id = ca.batchID
                        JOIN      inventory_containers co ON co.recNum = b.recNum
                        JOIN      logs_values lv ON lv.primeKey = ca.id
                        JOIN      logs_cartons lc ON lc.id = lv.logID
                        JOIN      logs_fields lf ON lf.id = lv.fieldID
                        JOIN      statuses s ON s.id = lv.toValue
                        WHERE     lf.category = "cartons"
                        AND       lf.displayName = "statusID"
                        AND       NOT ca.isSplit
                        AND       NOT ca.unSplit
                        ' . $whereClause . '
					) UNION (
                    -- split children
						SELECT    ca.id AS invID,
                                  logTime,
                                  ' . $fieldList . '
                                  s.shortName
                        FROM      inventory_cartons cap
                        JOIN      inventory_batches b ON b.id = cap.batchID
                        JOIN      inventory_containers co ON co.recNum = b.recNum
                        JOIN      logs_values lv ON lv.primeKey = cap.id
                        JOIN      logs_cartons lc ON lc.id = lv.logID
                        JOIN      logs_fields lf ON lf.id = lv.fieldID
                        JOIN      inventory_splits sp ON sp.parentID = cap.id
                        JOIN      inventory_cartons ca ON ca.id = sp.childID
                        JOIN      statuses s ON s.id = cap.statusID
                        WHERE     lf.category = "cartons"
                        AND       lf.displayName = "isSplit"
                        AND       cap.isSplit
                        AND       NOT ca.isSplit
                        AND       NOT ca.unSplit
                        ' . $whereClause . '
                    ) UNION (
                    -- split parents
						SELECT    ca.id AS invID,
                                  logTime,
                                  ' . $fieldList . '
                                  s.shortName
                        FROM      inventory_cartons ca
                        JOIN      inventory_batches b ON b.id = ca.batchID
                        JOIN      inventory_containers co ON co.recNum = b.recNum
                        JOIN      logs_values lv ON lv.primeKey = ca.id
                        JOIN      logs_cartons lc ON lc.id = lv.logID
                        JOIN      logs_fields lf ON lf.id = lv.fieldID
                        JOIN      statuses s ON s.id = lv.toValue
                        WHERE     lf.category = "cartons"
                        AND       lf.displayName = "statusID"
                        AND       ca.isSplit
                        ' . $whereClause . '
					) UNION (
                    -- make split parent inactive on the date the split happened
						SELECT    ca.id AS invID,
                                  logTime,
                                  ' . $fieldList . '
                                  "' . $inactiveStatus . '" AS shortName
                        FROM      inventory_cartons ca
                        JOIN      inventory_batches b ON b.id = ca.batchID
                        JOIN      inventory_containers co ON co.recNum = b.recNum
                        JOIN      logs_values lv ON lv.primeKey = ca.id
                        JOIN      logs_cartons lc ON lc.id = lv.logID
                        JOIN      logs_fields lf ON lf.id = lv.fieldID
                        WHERE     lf.category = "cartons"
                        AND       lf.displayName = "isSplit"
                        AND       ca.isSplit
                        ' . $whereClause . '
					)
				) ca
                ORDER BY  ' . $orderBy;

        $pdo = $this->app->getHoldersPDO();

        $sth = $pdo->prepare($sql);

        $params = array_merge($param, $param, $param, $param);

        $sth->execute($params);

        return $sth;
    }

    /*
    ****************************************************************************
    */

    function addDays($startDate, $endDate)
    {
        $diff = strtotime($endDate) - strtotime($startDate);

        return floor($diff / 3600 / 24);
    }

    /*
    ****************************************************************************
    */

    function getNextDate($date)
    {
        $time = strtotime($date);

        $timePlus = strtotime('+1 day', $time);

        return date('Y-m-d', $timePlus);
    }

    /*
    ****************************************************************************
    */

    static function getParams($custs, $params, $current=FALSE)
    {
        $startName = $current ? 'endDate' : 'startDate';
        array_push($custs, $params[$startName], $params['endDate']);
        return $custs;
    }

    /*
    ****************************************************************************
    */

    function calculate($params, $model)
    {
        $custID = getDefault($params['custID']);
        $passedEnd = $params['endDate'];

        $storage = $params['details'] ?
            getDefault($params['items']['Storage']) : [];
        if ($params['details'] && array_filter($params['items']) && ! $storage) {
            return [$custID => 0];
        }

        self::$monthDays = date('t', strtotime($passedEnd));
        self::$today = config::getDateTime('date');
        self::$today = $passedEnd;

        $totals = [];

        $uomCusts = $model->customerUOMs('STORAGE');

        $custUOMs = $model->custUOMs('STORAGE');

        $allUOMs = array_keys($uomCusts);
        $allCusts = array_keys($custUOMs);

        // Don't run if there are no customers with charge codes
        if (! $allCusts) {
            return [];
        }

        $billed = \invoices\model::init($this)->getBillableDts([
            'cat' => 's',
            'period' => 'daily',
            'dates' => $params,
            'custs' => $allCusts,
            'details' => TRUE,
            'getBilled' => TRUE,
        ]);

        $qMarks = $this->app->getQMarkString($allCusts);

        $sql = 'SELECT carton_id,
                       rcv_dt,
                       cust_id,
                       last_active,
                       vol,
                       uom
                FROM   ctn_sum
                WHERE  cust_id IN ('.$qMarks.')
                AND    last_active >= ?
                AND    rcv_dt <= ?';

        $queryParams = self::getParams($allCusts, $params);
        $results = $this->app->queryResults($sql, $queryParams);

        $model->volumeRates('STORAGE');

        foreach ($results as $row) {
            $custID = $row['cust_id'];
            $days = self::dayCount($row, $params, $billed);

            foreach ($allUOMs as $uom) {
                $custHasUOM = getDefault($custUOMs[$custID][$uom]);
                if (! $custHasUOM) {
                    continue;
                }

                $days = self::dayCount($row, $params, $billed);

                $uomRes = self::uomCases([
                    'uom' => $uom,
                    'row' => $row,
                    'days' => $days,
                    'model' => $model,
                ]);

                $finalUOM = getDefault($uomRes['overrideUOM'], $uom);

                $totals[$custID][$finalUOM] =
                    isset($totals[$custID][$finalUOM]) ?
                    $totals[$custID][$finalUOM] : 0;

                $totals[$custID][$finalUOM] += $uomRes['value'];
            }
        }

        $palletCusts = getDefault($uomCusts['PALLET_CURRENT']);

        if ($palletCusts) {

            $qMarks = $this->app->getQMarkString($palletCusts);
            $sql = 'SELECT cust_id,
                           COUNT(DISTINCT plate) AS plateCount
                    FROM   ctn_sum
                    JOIN   inventory_cartons ca ON carton_id = ca.id
                    WHERE  cust_id IN ('.$qMarks.')
                    AND    last_active >= ?
                    AND    rcv_dt <= ?
                    GROUP BY cust_id';

            $queryParams = self::getParams($palletCusts, $params, 'current');
            $results = $this->app->queryResults($sql, $queryParams);

            foreach ($results as $custID => $row) {
                // The totals[cust][PALLET_CURRENT] will have a count of active
                // cartons. dont charge if there are no active cartons
                $totals[$custID]['PALLET_CURRENT'] =
                    $totals[$custID]['PALLET_CURRENT']? $row['plateCount'] : 0;
            }

        }

        $monthlyPalletCusts = getDefault($uomCusts['MONTHLY_PALLET']);

        if ($monthlyPalletCusts) {

            $qMarks = $this->app->getQMarkString($monthlyPalletCusts);

            $sql = 'SELECT  plate,
                            cust_id,
                            rcv_dt,
                            IF(last_active, last_active, CURDATE()) AS last_active
                    FROM    stor_sum_plt
                    WHERE   cust_id IN ('.$qMarks.')
                    AND     IF(last_active, last_active, CURDATE()) >= ?
                    AND     rcv_dt <= ?
                    ';

            $queryParams = self::getParams($monthlyPalletCusts, $params);
            $results = $this->app->queryResults($sql, $queryParams);

            foreach ($results as $row) {
                $custID = $row['cust_id'];

                $days = self::dayCount($row, $params, $billed);

                $totals[$custID]['MONTHLY_PALLET'] =
                    isset($totals[$custID]['MONTHLY_PALLET']) ?
                    $totals[$custID]['MONTHLY_PALLET'] : 0;

                $value = $days / self::$monthDays;

                $totals[$custID]['MONTHLY_PALLET'] += $value;
            }

        }


    return $totals;
    }

    /*
    ****************************************************************************
    */

    static function uomCases($params)
    {
        if (! $params['days']) {
            return ['value' => 0];
        }

        $value = 0;

        switch ($params['uom']) {
            case 'VOLUME':
                $value = $params['days'] * $params['row']['vol'];
                break;
            case 'MONTHLY_VOLUME':
                $value = $params['days'] * $params['row']['vol'] / self::$monthDays;
                break;
            case 'VOLUME_CURRENT':
                $row = $params['row'];
                $vol = $params['row']['vol'];
                $value = self::ifDateActive($row, self::$today, $vol);
                break;
            case 'CARTON':
                $value = $params['days'];
                break;
            case 'PIECES':
                $value = $params['days'] * $params['row']['uom'];
                break;
            case 'CARTON_CURRENT':
            case 'PALLET_CURRENT':
                $row = $params['row'];
                $value = self::ifDateActive($row, self::$today, 1);
                break;
            case 'MONTHLY_SMALL_CARTON':
            case 'MONTHLY_MEDIUM_CARTON':
            case 'MONTHLY_LARGE_CARTON':
            case 'MONTHLY_XL_CARTON':
            case 'MONTHLY_XXL_CARTON':
                $volRes = self::volRangeValue($params['model'], $params['row']);

                return $volRes['rate'] ? [
                    'overrideUOM' => $volRes['rangeUOM'],
                    'value' => $params['days'] / self::$monthDays,
                ] : ['value' => 0];
        }

        return ['value' => $value];
    }

    /*
    ****************************************************************************
    */

    static function volRangeValue($model, $row)
    {
        $custID = $row['cust_id'];
        $custVolRate = $model->custVolumeRates('STORAGE', $custID);

        foreach ([
            'MONTHLY_SMALL_CARTON',
            'MONTHLY_MEDIUM_CARTON',
            'MONTHLY_LARGE_CARTON',
            'MONTHLY_XL_CARTON',
            'MONTHLY_XXL_CARTON',
        ] as $size) {

            if ($row['vol'] > getDefault($custVolRate[$size]['min_vol'])
                &&  (
                    // If less than max or there is no max
                    $row['vol'] <= getDefault($custVolRate[$size]['max_vol'])
                    || ! getDefault($custVolRate[$size]['max_vol'])
                )
            ) {
                return [
                    'rangeUOM' => $size,
                    'rate' => getDefault($custVolRate[$size]['chg_cd_price']),
                ];
            }
        }

        return ['rate' => 0];
    }

    /*
    ****************************************************************************
    */

    static function ifDateActive($row, $date, $value)
    {
        return $row['rcv_dt'] <= $date && $row['last_active'] >= $date ?
            $value : 0;
    }

    /*
    ****************************************************************************
    */

    static function dayCount($row, $params, $billed)
    {
        $custID = $row['cust_id'];
        $custBill = getDefault($billed[$custID], []);

        $rangeStartsFirst = $row['rcv_dt'] >= $params['startDate'];
        $cartonStartsFirst = $row['rcv_dt'] <= $params['startDate'];
        $rangeEndsFirst = $row['last_active'] >= $params['endDate'];
        $cartonEndsFirst = $row['last_active'] <= $params['endDate'];

        if ($cartonStartsFirst && $rangeEndsFirst) {
            return self::dateSub($params['endDate'], $params['startDate'], $custBill) + 1;
        } else if ($rangeStartsFirst && $cartonEndsFirst) {
            return self::dateSub($row['last_active'], $row['rcv_dt'], $custBill) + 1;
        } else if ($rangeStartsFirst && $rangeEndsFirst) {
            return self::dateSub($params['endDate'], $row['rcv_dt'], $custBill) + 1;
        } else if ($cartonStartsFirst && $cartonEndsFirst) {
            return self::dateSub($row['last_active'], $params['startDate'], $custBill) + 1;
        }

        return 0;
    }

    /*
    ****************************************************************************
    */

    static function dateSub($end, $start, $billed)
    {
        $datediff = strtotime($end) - strtotime($start);

        foreach ($billed as $dt) {
            if ($dt >= $start && $dt <= $end) {
                $datediff -= 60*60*24;
            }
        }

        return floor($datediff/(60*60*24));
    }

    /*
    ****************************************************************************
    */

    function cycleChanges(&$row, &$currentStatus, &$lastChangeDay)
    {
        foreach ($row['changes'] as $day => $dayChanges) {

            // Take the first action and set begining value
            $toStatus = TRUE;
            $fromStatus = FALSE;

            $first = reset($dayChanges);
            switch ($first) {
                case 'inactive':
                case 'isSplit':
                    $fromStatus = TRUE;
            }

            $last = end($dayChanges);
            switch ($last) {
                case 'inactive':
                case 'isSplit':
                    $toStatus = FALSE;
            }

           self::DEBUG ? vardump([
                '$toStatus' => $toStatus,
                '$fromStatus' => $fromStatus,
                '$currentStatus' => $currentStatus,
                '$lastChangeDay' => $lastChangeDay,
                '$row[stored]' => $row['stored'],
            ]) : NULL;

            if ($currentStatus && ! $toStatus) {
                $row['stored'] += $day - $lastChangeDay + 1;
                $lastChangeDay = $day;
                $currentStatus = $toStatus;
            } else if (! $currentStatus && $toStatus) {
                $lastChangeDay = $day;
                $currentStatus = $toStatus;
            }

            self::DEBUG ? vardump($lastChangeDay) : NULL;
        }
    }

    /*
    ****************************************************************************
    */

}
