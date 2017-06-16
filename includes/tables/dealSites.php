<?php

namespace tables;

class dealSites extends _default
{
    public $primaryKey = 'id';
    
    public $fields = [
        'id' => [],
        'displayName' => [],
        'imageName' => [],
    ];
    
    public $table = 'deal_sites';
    
    /*
    ****************************************************************************
    */

    function getWholesaleID()
    {
        $sql = 'SELECT    id
                FROM      deal_sites
                WHERE     displayName = "Wholesale"';
        
        $result = $this->app->queryResult($sql);
        
        return $result['id'];
    }

    /*
    ****************************************************************************
    */

}

