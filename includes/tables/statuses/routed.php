<?php

namespace tables\statuses;

class routed extends \tables\statuses
{
    
    public $primaryKey = 'id';
    
    public $fields = [
        'shortName' => [],
    ];
    
    public $where = 'category = "routing"';
    
    /*
    ****************************************************************************
    */
}