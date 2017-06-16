<?php

namespace tables\statuses;

class workOrders extends \tables\statuses
{
    
    public $primaryKey = 'id';
    
    public $fields = [
        'displayName' => [],
    ];
    
    public $where = 'category = "workOrders"';
    
    /*
    ****************************************************************************
    */
}