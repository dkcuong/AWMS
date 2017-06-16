<?php

namespace tables\statuses;

class locationType extends \tables\_default
{    
    public $primaryKey = 'id';
    
    public $table = '(
                        SELECT 0 AS id,
                               "Receiving" AS displayName
                    UNION
                        SELECT 1 AS id,
                               "Shipping" AS displayName
                    ) locationType';
    
    /*
    ****************************************************************************
    */    
}