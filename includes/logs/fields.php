<?php
namespace logs;

class fields {

    private $app;

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

    function getFieldID($category, $field='statusID')
    {
        $params = is_array($field) ? $field : [$field];

        $qMarks = $this->app->getQMarkString($params);

        $sql = 'SELECT  id,
                        displayName
                FROM    logs_fields
                WHERE   displayName IN (' . $qMarks . ')
                AND     category = ?';

        $params[] = $category;

        $results = $this->app->queryResults($sql, $params);

        if (is_array($field)) {

            $keys = array_keys($results);

            $values = array_column($results, 'displayName');

            return array_combine($values, $keys);
        } else {
            return key($results);
        }
    }

    /*
    ****************************************************************************
    */
}