<?php

namespace tables\statuses;

class employer extends \tables\statuses
{
    
    public $primaryKey = 'id';
    
    public $fields = [
        'displayName' => [],
    ];
    
    public $where = 'category = "employers"';
    
    /*
    ****************************************************************************
    */
}