<?php

namespace tables\invoices;

use DateTime;

class receiving extends \tables\_default
{
    public $ajaxModel = 'invoices\\receiving';

    public $primaryKey = 'co.recNum';

    public $fields = [
        'recNum' => [
            'select' => 'co.recNum',
            'display' => 'RCV NBR',
        ],
        'container' => [
            'select' => 'co.name',
            'display' => 'CNTR NM',
        ],
        'logTime' => [
           'display' => 'RCV DT',
           'ignore' => TRUE,
           'searcherDate' => TRUE,
        ],
        'totalCartons' => [
            'select' => 'COUNT(ca.id)',
            'display' => 'TTL CART',
        ],
         'totalPieces' => [
            'select' => 'SUM(ca.uom)',
            'display' => 'TTL PCS',
        ],
        'totalVol' => [
            'select' => 'CAST(
                        SUM(
                           (b.height * b.length * b.width / 1728 * b.initialCount)
                        )
                        AS DECIMAL(10, 2))',
            'display' => 'TTL VOL',
        ],
   ];

    public $table = 'inventory_cartons ca
                JOIN inventory_batches b ON b.id = ca.BatchID
                JOIN inventory_containers co ON co.recNum = b.recNum
                JOIN logs_values lv ON lv.primekey = ca.id
                JOIN logs_cartons lc ON lc.id = lv.logID
                JOIN logs_fields lf ON lf.id = lv.fieldID
                JOIN statuses s ON s.id = lv.toValue
                ';

    public $where = 'lf.category = "cartons"
                     AND lf.displayName = "statusID"
                     AND s.shortName = "RK"
                    ';

    public $groupBy = 'co.recNum';

    /*
    ****************************************************************************
    */

    function calculate($params, $model)
    {
        $custID = getDefault($params['custID'], NULL);
        $recNums = $params['details'] ?
            getDefault($params['items']['Receiving']) : [];

        if ($params['details'] && array_filter($params['items']) && ! $recNums) {
            return [$custID => 0];
        }

        // Get receiving history from inv_his_rcv table
        $hisCartonIDs = $this->getReceivingHisCarton($params, $recNums, 'returnRecs');

        // Calculate based on UOM
        $invoiceCosts = new \tables\invoices\invoiceCosts($this->app);

        $uomCusts = $model->customerUOMs('RECEIVING');

        $custUOMs = $model->custUOMs('RECEIVING');

        $allUOMs = array_keys($uomCusts);
        $allCusts = array_keys($custUOMs);

        // Don't run if there are no customers with charge codes
        if (! $allCusts) {
            return [];
        }

        // Set volume rates before iterating
        $model->volumeRates('RECEIVING');

        $chargeCodeValues = [];

        foreach ($allUOMs as $uom) {
            $custs = getDefault($uomCusts[$uom]);

            if (! $custs) {
                continue;
            }

            $results = $this->rcvQuery([
                'uom' => $uom,
                'custs' => $custs,
                'model' => $model,
                'params' => $params,
                'recNums' => $recNums,
                'hisCartonIDs' => $hisCartonIDs,
            ]);

            $vendorIDs = array_keys($results);
            $cartonCounts = array_column($results, 'rcvCount');

            $chargeCodeValues[$uom] = array_combine($vendorIDs, $cartonCounts);
        }

        //get vendors/qty for each charge code of Receiving
        $resultCosts = $invoiceCosts->getReceivingUOM($custID);

        $chargeDtls = [];
        foreach ($resultCosts as $row) {
            $cc = $row['chg_cd'];
            $uom = $row['uom'];
            $custID = $row['cust_id'];
            $count = getDefault($chargeCodeValues[$uom][$custID]);
            if ($count) {
                $chargeDtls[$cc][$custID] = $count;
            }
        }

        return $chargeDtls;
    }

    /*
    ****************************************************************************
    */

    function rcvQuery($passed)
    {
        $custs = getDefault($passed['custs']);

        if ($passed['uom'] == 'MONTH') {
            return $this->getReceivingMonth($passed['params'], $custs);
        }

        $volField = 'vol';
        $recField = 'rcv_nbr';
        $dateField = 'dt';
        $custField = 'cust_id';

        $custClause = NULL;

        $qMarks = $this->app->getQMarkString($custs);


        switch ($passed['uom']) {
            case 'CONTAINER':
                $fields = 'cust_id, SUM(val) AS rcvCount';
                $table = 'rcv_sum_cntr';
                break;
            case 'MONTHLY_SMALL_CARTON':
            case 'MONTHLY_MEDIUM_CARTON':
            case 'MONTHLY_LARGE_CARTON':
                $volField = 'width * height * length / 1728';
                $fields = 'vendorID, COUNT(ca.id) AS rcvCount';
                $table = 'inventory_containers co
                     JOIN inventory_batches b ON b.recNum = co.recNum
                     JOIN inventory_cartons ca ON ca.batchID = b.id
                     JOIN ctn_sum cs ON cs.carton_id = ca.id
                     JOIN inv_vol_rates r ON r.cust_id = vendorID';

                $custClause = 'AND  r.uom = "' . $passed['uom'] . '"
                               AND category = "RECEIVING"';

                $custClause .= $custs ? 'AND r.cust_id IN (' . $qMarks . ')' :
                    NULL;

                $recField = 'co.recNum';
                $dateField = 'setDate';
                $custField = 'vendorID';
                break;
            case 'VOLUME':
                $fields = 'cust_id, SUM(vol) AS rcvCount';
                $table = 'rcv_sum_vol';
                break;
            case 'PIECES':
                $fields = 'cust_id, SUM(val) AS rcvCount';
                $table = 'rcv_sum_pcs';
                break;
            case 'PALLET':
                $fields = 'cust_id, SUM(val) AS rcvCount';
                $table = 'rcv_sum_plt';
                break;
            case 'CARTON':
            default:
                $fields = 'cust_id, SUM(val) AS rcvCount';
                $table = 'rcv_sum_ctn';
                break;
        }

        $monthlyCharges = [
            'MONTHLY_SMALL_CARTON',
            'MONTHLY_MEDIUM_CARTON',
            'MONTHLY_LARGE_CARTON'
        ];

        if (! in_array($passed['uom'], $monthlyCharges)) {
            $custClause = $custs ? 'AND '.$custField.' IN (' . $qMarks . ')' : NULL;
        }

        $containerClause = $passed['recNums'] ?
            'AND '.$recField.' IN ('.implode(',', $passed['recNums']).')' : NULL;

        $hisClause = $passed['hisCartonIDs'] ?
            'AND '.$recField.' NOT IN ('.implode(',', $passed['hisCartonIDs']).')' : NULL;

        $volClause = $passed['model']->volClause('RECEIVING', $passed, $volField, $custField);

        $sql = 'SELECT '.$fields.'
                FROM   '.$table.'
                WHERE  '.$dateField.' >= ?
                AND    '.$dateField.' <= ?
                '.$containerClause.'
                '.$hisClause.'
                '.$custClause.'
                '.$volClause.'
                GROUP BY  '.$custField;

        $params = [
            $passed['params']['startDate'],
            $passed['params']['endDate'],
        ];

        $allParams = $custs ? array_merge($params, $custs) : $params;

        $results = $this->app->queryResults($sql, $allParams);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getReceivedCartonsID($recNums)
    {
        if (! $recNums )  {
            return [];
        }

        $recParams = is_array($recNums) ? $recNums : [$recNums];

        $qMarks = $this->app->getQMarkString($recParams);


        $statuses = new \tables\statuses\inventory($this->app);

        $statusIDs = $statuses->getStatusIDs(['IN', 'RC', 'RK']);

        $param = array_column($statusIDs, 'id');

        $sql = 'SELECT  id
                FROM    logs_fields
                WHERE   category = "cartons"
                AND     displayName = "statusID"';

        $result = $this->app->queryResult($sql);

        array_unshift($param, $result['id']);


        $invSql = '
            SELECT    ca.id AS cartonID
            FROM      logs_values v
            JOIN      logs_cartons c ON c.id = v.logID
            JOIN      inventory_cartons ca ON ca.id = primeKey
            JOIN      inventory_batches b ON b.id = ca.batchID
            WHERE     fieldID = ?
            AND       fromValue = ?
            AND       toValue IN (?, ?)
            AND       recNum IN (' . $qMarks . ')';

        $selectParams = array_merge($param, $recNums);


        $invCartons = $this->app->queryResults($invSql, $selectParams);

        //get the cartons from inv_his_ctn table
        $hisSql = '
            SELECT    ctn_id AS cartonID
            FROM      inv_his_ctn his
            JOIN      inv_his_rcv rcv ON rcv.inv_id = his.inv_id
            WHERE     cat = "RECEIVING"
            AND       rcv_nbr IN (' . $qMarks . ')
            AND       his.inv_sts
            AND       rcv.inv_sts';

        $hisCartons = $this->app->queryResults($hisSql, $recNums);

        $cartonIDs = NULL;

        if ( $invCartons ) {
            $cartonIDs = $hisCartons ?
                    array_diff_key($invCartons, $hisCartons) : $invCartons;
        }

        return $cartonIDs ? array_keys($cartonIDs) : [];
    }

    /*
    ****************************************************************************
    */

    function getReceivingHisCarton($params, $recNums=[], $returnRecs=FALSE)
    {
        $custID = getDefault($params['custs']);

        $histCustIDClause = $custID ? 'AND cust_id = ' . intVal($custID) : NULL;

        $recClause = $recNums ?
            'AND rcv_nbr IN ('.implode(',', $recNums).')' : NULL;

        $sql = 'SELECT   rcv_nbr
                FROM     inv_his_rcv
                WHERE    inv_sts
                AND      recv_dt >= ?
                AND      recv_dt <= ?
                ' . $histCustIDClause . '
                ' . $recClause;

        $selectParams = [
          $params['startDate'],
          $params['endDate']
        ];

        $hisResults = $this->app->queryResults($sql, $selectParams);

        $hisCartonIDs = $hisResults ? array_keys($hisResults) : [];

        if ($returnRecs) {
            return $hisCartonIDs;
        }

        return $hisCartonIDs ?
            'AND rcv_nbr NOT IN ('.implode(',', $hisCartonIDs).')' : NULL;
    }

    /*
    ****************************************************************************
    */

    function getReceivingMonth($dates, $custs, $details=FALSE)
    {
        if (! $custs) {
            return [];
        }

        $curDate = \models\config::getDateTime('date');

        $curDtObj = new DateTime($curDate);
        $endDtObj = new DateTime($dates['endDate']);

        $daterange = new \DatePeriod(
            new DateTime($dates['startDate']),
            new \DateInterval('P1M'),
            $endDtObj
        );

        $sums = $dts = $billedCustDts = $months = [];

        foreach($daterange as $date){
            $months[] = $date->format('Y-m');
        }

        // Incase end date is the first of a month
        $months[] = $endDtObj->format('Y-m');

        $curDTFormatted = $curDtObj->format('Y-m');
        $diff = array_diff($months, [$curDTFormatted]);

        $unique = array_unique($diff);

        if (! $unique) {
            return [];
        }

        $custQMarks = $this->app->getQMarkString($custs);
        $dateQMarks = $this->app->getQMarkString($unique);

        $sql = 'SELECT cust_id,
                       DATE_FORMAT(inv_date, "%Y-%m") AS dt
                FROM   inv_his_month
                WHERE  inv_sts
                AND    cust_id IN ('.$custQMarks.')
                AND    DATE_FORMAT(inv_date, "%Y-%m") IN ('.$dateQMarks.')';

        $params = array_merge($custs, $unique);

        $monthResults = $this->app->queryResults($sql, $params, \PDO::FETCH_ASSOC);

        foreach ($monthResults as $row) {
            $custID = $row['cust_id'];
            $billedCustDts[$custID][] = $row['dt'];
        }

        foreach ($custs as $custID) {

            $custBilled = getDefault($billedCustDts[$custID], []);

            $found = array_diff($unique, $custBilled);

            $sums[$custID]['rcvCount'] = 0;

            foreach ($found as $dt) {
                $dt = $dt.'-01';
                $dts[$custID][$dt] = 1;
                $sums[$custID]['rcvCount'] += 1;
            }
        }

        return $details ? $dts : $sums;
    }

    /*
    ****************************************************************************
    */

}
