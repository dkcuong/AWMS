<?php

namespace tables\statuses;

class boolean extends \tables\_default
{    
    public $primaryKey = 'id';
    
    public $fields = [
        'id' => [
            'display' => 'ID'
        ],
        'displayName' => [
            'display' => 'Status'
        ]
        
    ];
    
    public $table = '(
                        SELECT 0 AS id,
                               "No" AS displayName
                    UNION
                        SELECT 1 AS id,
                               "Yes" AS displayName
                    ) boolean';
    
    /*
    ****************************************************************************
    */    
    
    function getKey($display) 
    {
        $results = $this->get();
        $displays = array_column($results, 'displayName');
        $key = array_search($display, $displays);
        
        if ($key === FALSE) {
            echo 'Failed Boolean Search';
            backtrace();
            die;
        }
        
        return $key;
    }
    /*
    ****************************************************************************
    */    
}