<?php

namespace tables\onlineOrders;

class onlineOrderQuery
{
    public $app;

    public $obj;
    public $newOrders = [];

    public function __construct($app, $obj)
    {
        $this->app = $app;
        $this->obj = $obj;
    }

    /*
    ****************************************************************************
    */

    public function makeNewOrder($params)
    {
        $clientOrder = $params['clientOrder'];

        if (isset($this->newOrders[$clientOrder])) {
            return ;
        }

        $this->newOrders[$clientOrder] = TRUE;

        $sql = '
            INSERT INTO neworder (
                clientordernumber,
                scanordernumber,
                first_name,
                last_name,
                order_batch,
                carrier,
                location,
                isError,
                statusID
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?
            ) ON DUPLICATE KEY UPDATE
                first_name = ?,
                last_name = ?';

        $this->app->runQuery($sql, [
            $params['clientOrder'],
            $params['orderNumber'],
            $params['first_name'],
            $params['last_name'],
            $params['batchNumber'],
            $params['carrier'],
            $params['location'],
            $params['isErr'],
            $params['orderStatusID'],
            $params['first_name'],
            $params['last_name'],
        ]);
    }

    /*
    ****************************************************************************
    */

    public function makeOnlineOrder($rowData)
    {
        unset($rowData['clientordernumber']);
        unset($rowData['last_name']);
        unset($rowData['first_name']);
        unset($rowData['carrier']);
        $qMarkRow =$this->app->getQMarkString($rowData);
        $fields = implode(',', array_keys($rowData));

        $sql = '
            INSERT INTO online_orders (
                ' . $fields . '
            ) VALUES (
                ' . $qMarkRow . '
            )';

        $this->app->runQuery($sql, array_values($rowData));
    }

    /*
    ****************************************************************************
    */
}