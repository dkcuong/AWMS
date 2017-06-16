<?php

namespace tables\statuses;

class active extends \tables\statuses
{
    
    public $primaryKey = 'id';
    
    public $fields = [
        'displayName' => [],
    ];
    
    public $where = 'category = "vendors"';
    
    /*
    ****************************************************************************
    */
}