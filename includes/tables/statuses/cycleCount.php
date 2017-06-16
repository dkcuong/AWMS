<?php

namespace tables\statuses;

class cycleCount extends \tables\_default
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
                        SELECT "CC" AS id,
                               "Cycled" AS displayName
                    UNION
                        SELECT "OP" AS id,
                               "Open" AS displayName
                    UNION
                        SELECT "AS" AS id,
                               "Assigned" AS displayName
                    UNION
                        SELECT "RC" AS id,
                               "Recount" AS displayName
                    UNION
                        SELECT "CP" AS id,
                               "Completed" AS displayName
                    ) cycleCount';
    
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