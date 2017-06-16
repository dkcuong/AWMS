<?php

namespace tables\statuses;

class vendors extends \tables\statuses
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