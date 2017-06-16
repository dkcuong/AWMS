<?php

namespace tables\old;

class inventory extends \tables\_default
{
    public $fields = [
        'id',
        'displayName',
    ];
    
    public $table = 'inventory';
    
    /*
    ****************************************************************************
    */
    

    function create()
    {
        $sql = 'INSERT INTO order_batches () VALUES ()';
        $this->app->runQuery($sql);
        return $this->app->lastInsertId();
    }
    
    /*
    ****************************************************************************
    */
}