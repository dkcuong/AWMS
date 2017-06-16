<?php

namespace tables\invoices;

use PDO;

class processing extends \tables\_default
{
    public $ajaxModel = 'invoices\\processing';

    /*
    ****************************************************************************
    */

    function calculate($data, $model)
    {
        $custID = getDefault($data['custID'], NULL);
        $orderNumbers = $data['orderNumbers'] = $data['details'] ?
            getDefault($data['items']['orderPrc']) : [];

        if ($data['details'] && array_filter($data['items']) && ! $orderNumbers) {
            return [$custID => 0];
        }

        $app = $this->app;

        $fields = new \logs\fields($app);
        $orderStatuses = new \tables\statuses\orders($app);
        $processingHistory = new \invoices\history\orderProcessing($app);

        $entryCheckOutStatusID = \tables\orders::STATUS_ENTRY_CHECK_OUT;

        //get open orders if not select items
        $data['orderNumbers'] = $orderNumbers ? $orderNumbers :
                     $this->getOpenOrders($data, $custID);

        // Set volume rates
        $model->volumeRates('ORD_PROC');

        $sql = 'SELECT    chg_cd,
                          chg_cd_uom
                FROM      charge_cd_mstr
                WHERE     chg_cd_type = "ORD_PROC"
                AND       chg_cd_uom != "UNIT"
                GROUP BY  chg_cd';

        $results = $app->queryResults($sql);

        $uoms = array_column($results, 'chg_cd_uom');
        $uniqueUoms = array_unique($uoms);

        $uomCCs = [];
        foreach ($results as $cc => $row) {
            $uom = $row['chg_cd_uom'];
            $uomCCs[$uom][] = $cc;
        }

        $customerUOMs = $model->customerUOMs('ORD_PROC');

        // Don't pass params like this. Fix later
        $data['processedOrderIDs'] =
            $processingHistory->getBilledOrders($custID, $orderNumbers);

        $data['fieldID'] = $fields->getFieldID('orders');
        $data['statusID'] = $orderStatuses->getStatusID($entryCheckOutStatusID);
        $data['model'] = $model;

        $processedIDs = \summary\createOrd::init($this->app)->getInvoiceProcessedOrder();
        $notProcessedIDs = \summary\createOrd::init($this->app)->getInvoiceNotProcessedOrder();

        $data['statusIDs'] = array_merge($processedIDs, $notProcessedIDs);

        $uomValues = $return = [];
        foreach ($uniqueUoms as $uom) {
            $res = $this->getValues($data, $uom, $customerUOMs);
            if ($res) {
                $uomValues[$uom] = $res;
            }
        }

        $rates = $model->rates();

        $custCounts = [];
        $opRates = getDefault($rates['ORD_PROC'], []);
        foreach ($opRates as $custID => $ccs) {
            foreach ($ccs as $cc => $uom) {
                if ($uom == 'UNIT') {
                    continue;
                }
                $uomValue = getDefault($uomValues[$uom][$custID]);
                if ($uomValue) {
                    $custCounts[$cc][$custID] = $uomValue;
                }
            }
        }

        $woValues = $this->getLabor($data, $customerUOMs, 'UNIT');

        return $woValues ? $custCounts + $woValues : $custCounts;
    }

    /*
    ****************************************************************************
    */

    function getValues($data, $type, $customerUOMs)
    {
        $custClause = $whereSelect = $join = NULL;
        $clauses = [];

        $volField = 'vol';
        $custField = 'o.cust_id';
        $orderField = 'order_nbr';

        $model = $data['model'];

        $custs = getDefault($customerUOMs[$type]) ?
                     array_unique($customerUOMs[$type]) : NULL;

        if (! $custs) {
           return FALSE;
        }

        $qMarks = $this->app->getQMarkString($custs);
        $custClause = $custs ? 'AND o.cust_id IN (' . $qMarks . ')' : NULL;

        $statusIDs = $data['statusIDs'];

        $statuses = new \tables\statuses\orders($this->app);

        switch ($type) {
            case 'CARTON':
                $fields = 'cust_id, SUM(val) AS valueCount';
                $table = 'ord_sum_ctn o';
                break;
            case 'PIECES':
                $fields = 'cust_id, SUM(val) AS valueCount';
                $table = 'ord_sum_pcs o';
                break;
            case 'VOLUME':
                $fields = 'cust_id, SUM(val) AS valueCount';
                $table = 'ord_sum_vol o';
                break;
            case 'MONTHLY_SMALL_CARTON':
            case 'MONTHLY_MEDIUM_CARTON':
            case 'MONTHLY_LARGE_CARTON':
                $volField = 'width * height * length / 1728';
                $fields = 'r.cust_id, COUNT(ca.id) AS valueCount';
                $table = 'ord_sum_ctn o
                    JOIN     neworder n ON n.scanordernumber = o.order_nbr
                    JOIN     inventory_cartons ca ON ca.orderID = n.id
                    JOIN     inventory_batches b ON b.id = ca.batchID
                    JOIN     inv_vol_rates r ON r.cust_id = o.cust_id
                    JOIN     statuses s ON s.id = n.statusID';

                $custClause = 'AND  r.uom = "' . $type . '"
                               AND r.category = "ORD_PROC"';

                $custClause .= $custs ? 'AND r.cust_id IN (' . $qMarks . ')' :
                                NULL;

                $custField = 'r.cust_id';
                break;
            case 'PALLET':
                $fields = 'cust_id, SUM(val) AS valueCount';
                $table = 'ord_sum_plt o';
                break;
            case 'ORDER':
                $fields = 'cust_id, SUM(val) AS valueCount';
                $table = 'ord_sum_ord o';
                break;
            case 'LABEL':
                $fields = 'cust_id, SUM(val) AS valueCount';
                $table = 'ord_sum_lbl o';
                break;
            case 'FEDEX_CARTON':
                $whereSelect = 'UPPER(carrierType) = "FEDEX"';
                break;
            case 'UPS_CARTON':
                $whereSelect = 'UPPER(carrierType) = "UPS"';
                break;
            case 'ORDER_CANCEL':
                $fields = 'cust_id, SUM(val) AS valueCount';
                $table = 'ord_sum_cncl o';

                $status = \tables\orders::STATUS_CANCELED;
                $statusIDs = $statuses->getOrderStatusID($status);
                break;
            default:
                return [];
        }

        //status
        $statusClause = $statusIDs ?
                'AND s.id IN (' . implode(",", $statusIDs) . ')' : NULL;


        //monthly charges
        $charges = [
            'MONTHLY_SMALL_CARTON',
            'MONTHLY_MEDIUM_CARTON',
            'MONTHLY_LARGE_CARTON',
        ];

        if (! in_array($type, $charges)) {
            $join = 'JOIN  neworder n ON n.scanordernumber = o.order_nbr
                     JOIN  statuses s ON s.id = n.statusID';
        }

        //carrier
        $carrier = [
            'UPS_CARTON',
            'FEDEX_CARTON'
        ];

        if (in_array($type, $carrier)) {
            $fields = 'o.cust_id, SUM(o.val) AS valueCount';

            $table = 'ord_sum_ctn o
                      JOIN  ord_ship_sum ss ON ss.order_nbr = o.order_nbr';

            $whereSelect .= ' AND ss.dt >= ? AND ss.dt <= ?';

            $orderField = 'ss.order_nbr';
        }

        //$clauses
        $clauses = $this->getClauses($data, $orderField);

        //volume rates
        $volParams = [
            'uom' => $type,
            'custs' => $custs
        ];

        $volClause = $model->volClause('ORD_PROC', $volParams, $volField, $custField);


        //dt field
        $whereSelect = $whereSelect ? $whereSelect  : 'dt >= ?  AND  dt <= ?';

        $sql = 'SELECT ' . $fields . '
                FROM   ' . $table . '
                ' . $join . '
                WHERE  ' . $whereSelect . '
                ' . $clauses['where'] . '
                ' . $custClause . '
                ' . $volClause . '
                ' . $statusClause . '
                GROUP BY ' .  $custField . '
                ';

        $allParams = $custs ? array_merge($clauses['params'], $custs) :
            $clauses['params'];

        $results = $this->app->queryResults($sql, $allParams);

        $vendorIDs = array_keys($results);
        $cartonCounts = array_column($results, 'valueCount');

        return array_combine($vendorIDs, $cartonCounts);
    }

    /*
    ****************************************************************************
    */

    function getLabor($data, $customerUOMs)
    {
        $custs = getDefault($customerUOMs['UNIT']);
        if (! $custs) {
            return FALSE;
        }

        $custID = array_unique($custs);

        $custClause = $custs ?
            'AND cust_id IN ('.$this->app->getQMarkString($custID).')' : NULL;

        $statusIDs = $data['statusIDs'];

        $statusClause = $statusIDs ?
                'AND s.id IN (' . implode(', ', $statusIDs) . ')' : NULL;

        $clauses = $this->getClauses($data);

        $sql = 'SELECT   cust_id,
                         chg_cd,
                         SUM(labor) AS labor
                FROM     ord_sum_wo w
                JOIN     neworder n ON n.scanordernumber = w.order_nbr
                JOIN     statuses s ON s.id = n.statusID
                WHERE    dt >= ?
                AND      dt <= ?
                ' . $clauses['where'] . '
                ' . $custClause . '
                ' . $statusClause . '
                GROUP BY cust_id,
                         chg_cd
                ';

        $allParams = $custs ?
            array_merge($clauses['params'], $custID) : $clauses['params'];

        $results = $this->app->queryResults($sql, $allParams, PDO::FETCH_ASSOC);

        $return = [];
        foreach ($results as $values) {

            $chargeCodes = $values['chg_cd'];
            $vendorID = $values['cust_id'];

            $return[$chargeCodes][$vendorID] = $values['labor'];
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getClauses($data, $orderField='order_nbr')
    {
        $orderNumbers = $data['orderNumbers'];
        $processedOrderIDs = $data['processedOrderIDs'];

        $clauses = [];
        $params = [
            $data['startDate'],
            $data['endDate'],
        ];

        if ($orderNumbers) {

            $qMarks = $this->app->getQMarkString($orderNumbers);

            $clauses[] = ' AND ' . $orderField . ' IN (' . $qMarks . ')';
            $params = array_merge($params, $orderNumbers);
        }

        if ($processedOrderIDs) {

            $qMarks = $this->app->getQMarkString($processedOrderIDs);

            $clauses[] = ' AND ' . $orderField . ' NOT IN (' . $qMarks . ')';
            $params = array_merge($params, $processedOrderIDs);
        }

        return [
            'where' => $clauses ? implode($clauses) : NULL,
            'params' => $params,
        ];
    }

    /*
    ****************************************************************************
    */

    function billableOrders($params)
    {
        $orderNums = getDefault($params['orderNums']);
        $retBilled = getDefault($params['retBilled']);

        $qMarks = $this->app->getQMarkString($orderNums);

        $sql = 'SELECT ord_id,
                       LPAD(ord_id, 10, 0) AS ord_num
                FROM   inv_his_ord_prc
                WHERE  ord_id IN ('.$qMarks.')
                AND    inv_sts';

        $ids = array_keys($orderNums);

        $billed = $this->app->queryResults($sql, $ids);

        if ($retBilled) {
            return $billed;
        }

        $billable = array_diff_key($orderNums, $billed);

        return $billable;
    }

    /*
    ****************************************************************************
    */

    function getOpenOrders($params, $custs)
    {
        if (! $custs) {
            return FALSE;
        }

        $custClause = $custs ?
            'AND cust_id IN ('.$this->app->getQMarkString($custs).')' : NULL;

        $params = [
            $params['startDate'],
            $params['endDate'],
            $custs
        ];

        $sql = 'SELECT   order_nbr,
                         cust_id
                FROM     ord_sum_ord
                WHERE    dt >= ?
                AND      dt <= ?
                ' . $custClause . '
                GROUP BY cust_id,
                         order_nbr
                ';

        $results = $this->app->queryResults($sql, $params);

        return array_keys($results);
    }

    /*
    ****************************************************************************
    */
}
