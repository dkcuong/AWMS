<?php

namespace tables\statuses;

class inventory extends \tables\statuses
{
    
    public $primaryKey = 'id';
    
    public $fields = [
        'shortName' => [],
    ];
    
    public $where = 'category = "inventory"';
    
    /*
    ****************************************************************************
    */
}