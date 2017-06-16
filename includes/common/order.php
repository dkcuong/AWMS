<?php

namespace common;

class order
{
    static $orderIDs;

    static function updateAndLogStatus($data)
    {
        $orderIDs = getDefault($data['orderIDs']);
        $statusID = $data['statusID'];
        $field = getDefault($data['field'], 'statusID');
        $tableClass = $data['tableClass'];

        $db = $tableClass->app;

        $orderKeys = $orderIDs ? $orderIDs : getDefault(self::$orderIDs);

        $orderStatuses = new \tables\statuses\orders($db);

        $result = $orderStatuses->updateStatus([
            'orderIDs' => $orderKeys,
            'statusID' => $statusID,
            'field' => $field,
            'tableClass' => $tableClass,
        ]);

        logger::getFieldIDs('orders', $db);

        logger::getLogID();

        logger::edit([
            'db' => $db,
            'primeKeys' => $result['orderIDs'],
            'fields' => $result['fields'],
        ]);
    }

    /*
    ****************************************************************************
    */

    static function getIDs($app, $orderNumbers)
    {
        $params = is_array($orderNumbers) ? $orderNumbers : [$orderNumbers];

        $qMarks = $app->getQMarkString($params);

        $sql = 'SELECT scanOrderNumber,
                       id
                FROM   neworder
                WHERE  scanOrderNumber IN (' . $qMarks . ')';

        $results = $app->queryResults($sql, $params);

        $orderKeys = array_keys($results);

        self::$orderIDs = array_column($results, 'id');

        return array_combine($orderKeys, self::$orderIDs);
    }

    /*
    ****************************************************************************
    */

}