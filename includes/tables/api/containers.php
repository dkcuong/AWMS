<?php

namespace tables\api;

use dateTime;

class containers extends \tables\_default
{
    public $ajaxModel = 'api\\containers';

    public $primaryKey = 'co.recNum';

    public $fields = [
        'wh' => [
            'select' => 'w.displayName',
        ],
        'cust' => [
            'select' => 'v.vendorName',
            'openEnd' => TRUE,
        ],
        'wh_cust' => [
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'openEnd' => TRUE,
        ],
        'pre_dt' => [
            'select' => 'co.setDate',
        ],
        'rcv_dt' => [
            'select' => 't.setDate',
        ],
        'cntr' => [
            'select' => 'name',
            'openEnd' => TRUE,
        ],
        'rcv_nbr' => [
            'select' => 'co.recNum',
        ],
        'meas_sys' => [
            'select' => 'IF(measureID=1, "IMP", "MET")',
        ],
        'rcv_id' => [
            'select' => 'r.id',
        ],
        'rcv_created_at' => [
            'select' => 'r.created_at',
        ],
    ];

    public $table = 'inventory_containers co
        LEFT JOIN   receiving_containers rc ON rc.container_num = co.recNum
        LEFT JOIN   receivings r ON r.id = rc.receiving_id
        JOIN        vendors v ON v.id = co.vendorID
        JOIN        warehouses w ON v.warehouseID = w.id
        JOIN        tallies t ON t.recNum = co.recNum
        ';

    //**************************************************************************

    static function init($app)
    {
        return new self($app);
    }

    //**************************************************************************

    function filter()
    {
        $params = $this->app->getArray('post');

        // Default clause is 1 = 1
        $clauses = $terms = [];

        foreach ($this->fields as $alias => $row) {

            $value = getDefault($params[$alias]);
            if (! $value) {
                continue;
            }

            $param = isset($row['openEnd']) ? $value.'%' : $value;
            array_unshift($terms, $param);
            $exact = isset($row['openEnd']) ? 'LIKE' : '=';
            array_unshift($clauses, $row['select'].' '.$exact.' ?');
        }

        $dateType = getDefault($params['dt_type']);

        // Dates have to be searched last because they might need a having
        // clause
        if ($dateType) {
            foreach ([[
                'name' => 'start_dt',
                'compare' => '>=',
            ], [
                'name' => 'end_dt',
                'compare' => '<=',
            ]] as $row) {
                $name = $row['name'];
                $date = getDefault($params[$name]);
                echo $dateType;
                $dbName = getDefault($this->fields[$dateType]['select']);
                if (! $date || ! $dbName) {
                    continue;
                }

                try {
                    $dtObj = new dateTime($date);
                    $terms[] = $dtObj->format('Y-m-d');
                    $clauses[] = 'DATE('.$dbName.') '.$row['compare'].' ?';
                } catch (exception $e) {

                }
            }
        }

        // Default length
        $length = isset($params['rows']) && $params['rows'] ? $params['rows'] : 10;
        $maxLimit = $length > 1000 ? 1000 : $length;

        $start = getDefault($params['start'], 0);
        $limit = $start * $maxLimit.', '.$maxLimit;

        $cntrs = $this->search([
            'term' => $terms,
            'clause' => $clauses,
            'limit' => $limit,
        ]);


        $sql = 'SELECT FOUND_ROWS() AS result';
        $filterCount = $this->app->queryResult($sql);

        if (! $cntrs) {
            return [
                'data' => [],
                'recordsFiltered' => $filterCount['result'],
                'recordsTotal' => $this->containerCount(),
                'deferLoading' => 0,
            ];
        }

        $qMarks = $this->app->getQMarkString($cntrs);
        $recNums = array_keys($cntrs);

        $actInfo = $this->actual($recNums, $qMarks);
        $expInfo = $this->expected($recNums, $qMarks);

        foreach ($cntrs as $cntrID => &$row) {
            foreach (['po', 'uom', 'sku_qty', 'exp_ctn_qty'] as $field) {
                $row[$field] = getDefault($expInfo[$cntrID][$field]);
            }

            $row['act_ctn_qty'] = getDefault($actInfo[$cntrID]['act_ctn_qty']);
            $uom = $expInfo[$cntrID]['uom'];
            $row['exp_pcs_qty'] = $row['exp_ctn_qty'] * $uom;
            $row['act_pcs_qty'] = $row['act_ctn_qty'] * $uom;
        }

        return [
            'data' => array_values($cntrs),
            // Says filtered from this value
            'recordsFiltered' => $filterCount['result'],
            'recordsTotal' => $this->containerCount(),
            'deferLoading' => 0,
        ];
    }

    //**************************************************************************

    function actual($recNums, $qMarks)
    {
        $sql = 'SELECT recNum, COUNT(tr.cartonCount) AS act_ctn_qty
                FROM   tallies t
                JOIN   tally_rows tr ON tr.tallyID = t.id
                JOIN   tally_cartons tc ON tr.id = tc.rowID
                WHERE  recNum IN ('.$qMarks.')
                GROUP BY recNum';

        return $this->app->queryResults($sql, $recNums);
    }

    //**************************************************************************

    function expected($recNums, $qMarks)
    {
        $sql = 'SELECT recNum, COUNT(ca.id) AS exp_ctn_qty, uom,
                       COUNT(DISTINCT sku) AS sku_qty, b.prefix AS po
                FROM   inventory_batches b 
                JOIN   inventory_cartons ca ON ca.batchID = b.id
                JOIN   upcs u ON u.id = b.upcID
                JOIN   statuses s ON s.id = ca.statusID
                WHERE  recNum IN ('.$qMarks.')
                AND    shortName != "IN"
                GROUP BY recNum';

        return $this->app->queryResults($sql, $recNums);
    }

     //**************************************************************************

    function containerCount()
    {
        $sql = 'SELECT COUNT(recNum) AS listTotal
                FROM   inventory_containers';

        $result = $this->app->queryResult($sql);
        return $result['listTotal'];
    }

   //**************************************************************************

    function addHavingClause($clause)
    {
        $this->having .= ' AND '.$clause;
    }
}
