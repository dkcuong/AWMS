<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

class model extends base 
{
    public $filters = [
        'Receiving' => [
            'All' => TRUE,
            'In Transit' => 'iin',
            'Received' => 'rc',
            'Racked' => 'rk'
        ],
        'Shipping' => [
            'All Orders' => TRUE,
            'Checked In' => 'wmco',
            'Routing' => 'rtco',
            'Picking' => 'pkco',
            'Order Processing' => 'opco',
            'Work Orders' => 'woco',
            'Shipping' => 'shco'
        ]
    ];

    public $showDashboard = FALSE;

    public $displayLink = NULL;    

    public $loop = 0;

    public $fontSize = 0;

    public $tableRowAmount = 0;

    public $results = [];

    public $title = NULL;

    public $metaRefresh = 0;

    /*
    ****************************************************************************
    */

    function getData($fields, $havingClause, $param)
    {
        $table = $this->table;
        
        array_unshift($fields, $table->primaryKey);

        $sql = 'SELECT      ' . implode(',', $fields) . '
                FROM        ' . $table->table . '
                WHERE       ' . $table->where . '
                GROUP BY    ' . $table->groupBy . '
                ' . $havingClause;

        $_SESSION['results'] = $this->queryResults($sql, $param);

        return $_SESSION['results'];
    }
}

