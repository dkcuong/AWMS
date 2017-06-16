<?php

namespace tables\statuses;

class cartons extends \tables\statuses
{
    
    public $primaryKey = 'id';
    
    public $fields = [
        'displayName' => [],
    ];
    
    public $where = 'category = "cartons"';
    
    /*
    ****************************************************************************
    */
}