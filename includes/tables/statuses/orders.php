<?php

namespace tables\statuses;

class orders extends \tables\statuses
{

    public $primaryKey = 'id';

    public $fields = [
        'shortName' => [],
    ];

    public $where = 'category = "orders"';

    /*
    ****************************************************************************
    */

    function updateStatus($data)
    {
        $orderIDs = $data['orderIDs'];
        $statusID = $data['statusID'];
        $field = getDefault($data['field'], 'statusID');
        $tableClass = $data['tableClass'];

        $statusFields = [
            'statusID',
            'routedStatusID',
            'holdStatusID',
            'shippingStatusID',
            'isError',
        ];

        in_array($field, $statusFields) || die('Invalid Status Field');

        $target = [];

        foreach ($orderIDs as $orderID) {
            $target[] = [
                'target' => $orderID,
            ];
        }

        $fromValues = $this->getOrderStatusIDs($orderIDs, $field);

        $tableClass->updateStatus([
            'target' => $target,
            'field' => $tableClass->primaryKey,
            'status' => $statusID,
            'statusField' => $field,
        ]);

        return [
            'orderIDs' => array_keys($fromValues),
            'fields' => [
                $field => [
                    'fromValues' => array_values($fromValues),
                    'toValues' => $statusID,
                ],
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function getOrderStatusIDs($orderIDs, $field='statusID')
    {
        if (! $orderIDs) {
            return [];
        }

        $sql = 'SELECT   id,
                         ' . $field . '
                FROM     neworder
                WHERE    id IN (' . $this->app->getQMarkString($orderIDs) . ')';

        $results = $this->app->queryResults($sql, $orderIDs);

        $keys = array_keys($results);

        $values = array_column($results, $field);

        return array_combine($keys, $values);
    }

    /*
    ****************************************************************************
    */

    function getOrderStatusID($params)
    {
        $status = is_array($params) ? $params : [$params];

        $clause = $params ?
                'AND shortName IN ('.$this->app->getQMarkString($status).')' : 1;

        $where = $this->where ? $this->where : 1;

        $sql = '
                SELECT    id,
                          shortName
                FROM      statuses
                WHERE     ' . $where . '
                ' . $clause . '
                ';

        $results = $this->app->queryResults($sql, $status);

        return array_keys($results);
    }

    /*
    ****************************************************************************
    */
}
