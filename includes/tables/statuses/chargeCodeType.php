<?php

namespace tables\statuses;

class chargeCodeType extends \tables\_default
{    
    public $primaryKey = 'id';
    
    public $fields = [
        'id' => [
            'display' => 'ID'
        ],
        'displayName' => [
            'display' => 'Type'
        ]
        
    ];
    
    public $table = '(
                        SELECT 0 AS id,
                               "RECEIVING" AS displayName
                    UNION
                        SELECT 1 AS id,
                               "STORAGE" AS displayName
                    UNION
                        SELECT 2 AS id,
                               "ORD_PROC" AS displayName      
                    UNION 
                        SELECT 3 AS id,
                               "OTHER_SERV" AS displayName
                    ) chargeType';

    
    /*
    ****************************************************************************
    */
}
