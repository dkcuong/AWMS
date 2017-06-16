<?php

namespace tables\statuses;

class chargeUOM extends \tables\_default
{    
    public $primaryKey = 'id';
    
    public $fields = [
        'id' => [
            'display' => 'ID'
        ],
        'displayName' => [
            'display' => 'UOM'
        ]
        
    ];
    
    public $table = '(
                    SELECT 0 AS id,
                               "CARTON" AS displayName
                    UNION
                        SELECT 1 AS id,
                               "VOLUME" AS displayName
                    UNION
                        SELECT 2 AS id,
                               "MONTH" AS displayName          
                    UNION
                        SELECT 3 AS id,
                               "PALLET" AS displayName 
                    UNION
                        SELECT 4 AS id,
                               "UNIT" AS displayName 
                    UNION
                        SELECT 5 AS id,
                               "ORDER" AS displayName 
                    UNION
                        SELECT 6 AS id,
                               "TBD" AS displayName 
                    UNION
                        SELECT 7 AS id,
                               "LABEL" AS displayName  
                    UNION
                        SELECT 8 AS id,
                               "CONTAINER" AS displayName  
                    UNION
                        SELECT 9 AS id,
                               "MONTHLY_LARGE_CARTON" AS displayName
                    UNION
                        SELECT 10 AS id,
                               "MONTHLY_MEDIUM_CARTON" AS displayName
                    UNION
                        SELECT 11 AS id,
                               "MONTHLY_SMALL_CARTON" AS displayName
                    UNION
                        SELECT 12 AS id,
                               "CARTON_CURRENT" AS displayName
                    UNION
                        SELECT 13 AS id,
                               "PALLET_CURRENT" AS displayName
                    UNION
                        SELECT 14 AS id,
                               "VOLUME_CURRENT" AS displayName
                    UNION
                        SELECT 15 AS id,
                               "VOLUME_RAN" AS displayName
                    UNION
                        SELECT 16 AS id,
                               "ORDER_CANCEL" AS displayName
                    UNION
                        SELECT 17 AS id,
                               "LABOR" AS displayName   
                    UNION
                        SELECT 18 AS id,
                               "MONTHLY_VOLUME" AS displayName 
                    UNION
                        SELECT 19 AS id,
                               "MONTHLY_XL_CARTON" AS displayName
                    UNION
                        SELECT 20 AS id,
                               "MONTHLY_XXL_CARTON" AS displayName
                    UNION
                        SELECT 21 AS id,
                               "PIECES" AS displayName
                    UNION
                        SELECT 22 AS id,
                               "MONTHLY_PALLET" AS displayName
                    ) AS chargeUOM';

    
    /*
    ****************************************************************************
    */
    
}