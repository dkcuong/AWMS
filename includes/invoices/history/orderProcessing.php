<?php

namespace invoices\history;

class orderProcessing
{

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

    function getBilledOrders($custID, $orderNumbers=[])
    {
        $params = $clauses = [];

        if ($custID) {
            $clauses[] = 'his.cust_id = ?';
            $params[] = $custID;
        }

        if ($orderNumbers) {

            $qMarks = $this->app->getQMarkString($orderNumbers);

            $clauses[] = 'n.scanOrderNumber IN (' . $qMarks . ')';
            $params = array_merge($params, $orderNumbers);
        }

        $whereClause = $clauses ? implode(' AND ', $clauses) : 1;
        
        $sql = 'SELECT    ord_num
                FROM      inv_his_ord_prc his
                JOIN      neworder n ON n.id = his.ord_id
                WHERE     his.inv_sts
                AND       ' . $whereClause;

        $results = $this->app->queryResults($sql, $params);

        return array_keys($results);
    }

    /*
    ****************************************************************************
    */

}
