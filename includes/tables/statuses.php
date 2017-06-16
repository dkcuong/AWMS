<?php

namespace tables;

class statuses extends _default
{
    public $primaryKey = 'id';

    public $fields = [
        'id' => [],
        'category' => [],
        'displayName' => [],
        'shortName' => [],
    ];

    public $table = 'statuses';

    /*
    ****************************************************************************
    */

    function getStatusID($description, $returnQuery=FALSE)
    {
        $sql = 'SELECT id
                FROM   ' . $this->table . '
                WHERE  (' . getDefault($this->where, 1) . ')
                AND    shortName = ?';

        if ($returnQuery) {
            return $sql;
        }

        $result = $this->app->queryResult($sql, [$description]);

        return $result['id'];
    }

    /*
    ****************************************************************************
    */

    function getStatusIDs($descriptions=[], $returnQuery=FALSE, $field=NULL)
    {
        $clause = $descriptions ?
            'shortName IN ('.$this->app->getQMarkString($descriptions).')' : 1;

        $fieldClause = $field ? $field . ',' : NULL;

        $sql = 'SELECT shortName,
                       ' . $fieldClause . '
                       id
                FROM   '.$this->table.'
                WHERE  (' . getDefault($this->where, 1) . ')
                AND    (' . $clause . ')';

        $params = is_array($descriptions) ? $descriptions : [$descriptions];

        $data = $this->app->queryResults($sql, $params);

        return $returnQuery ? $sql : $data;
    }

    /*
    ****************************************************************************
    */

    function getStatusName($description, $returnQuery=FALSE)
    {
        $sql = 'SELECT shortName
                FROM   '.$this->table.'
                WHERE  id = ?';

        if ($returnQuery) {
            return $sql;
        }

        $result = $this->app->queryResult($sql, [$description]);

        return $result['shortName'];


    }

    /*
    ****************************************************************************
    */

    function checkStatus($target, $field, $status, $statusField='status')
    {
        $targets = is_array($target) ? array_values($target) : [$target];
        $statusIDs = is_array($status) ? array_values($status) : [$status];

        $ordersQMarks = $this->app->getQMarkString($targets);
        $statusesQMarks = $this->app->getQMarkString($statusIDs);

        $sql = 'SELECT    ' . $field . ',
                          ' . $statusField . '
                FROM      neworder o
                JOIN      statuses s ON s.id = o.' . $statusField . '
                WHERE     ' . $field . ' IN (' . $ordersQMarks . ')
                AND       ' . $statusField . ' IN (' . $statusesQMarks . ')';

        $params = array_merge($targets, $statusIDs);

        $results = $this->app->queryResults($sql, $params);

        $keys = array_keys($results);

        $values = array_column($results, $statusField);

        return array_combine($keys, $values);
    }

    /*
    ****************************************************************************
    */

}