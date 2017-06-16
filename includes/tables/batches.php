<?php

namespace tables;

class batches extends _default
{
    public $fields = [
        'id',
        'displayName',
    ];
    
    /*
    ****************************************************************************
    */
    

    function create($vendorID=0, $dealSiteID=0)
    {
        $sql = 'INSERT INTO order_batches (
                    vendorID, 
                    dealSiteID
                ) VALUES (?, ?)';
        $this->app->runQuery($sql, [$vendorID, $dealSiteID]);
        return $this->app->lastInsertId();
    }
    
    /*
    ****************************************************************************
    */
}