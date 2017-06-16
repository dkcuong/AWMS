<?php

namespace tables\statuses;

class activeStatus extends \tables\_default
{    
    public $primaryKey = 'id';
    
    public $fields = [
        'inactive' => [
            'select' => 0,
            'display' => 'Inctive',
        ],
        'active' => [
            'select' => 1,
            'display' => 'Active',
        ],
    ];

    public $table = '(
                        SELECT 0 AS id,
                               "Inactive" AS displayName
                    UNION
                        SELECT 1 AS id,
                               "Active" AS displayName
                    ) activeStatus';
    
    
    /*
    ****************************************************************************
    */
    
    function getStatuses()
    {
        $sql = 'SELECT    id,
                          displayName
                FROM      '.$this->table.'
                ORDER BY  displayName ASC';
        
        return $this->app->queryResults($sql);        
    }
    
    /*
    ****************************************************************************
    */
}