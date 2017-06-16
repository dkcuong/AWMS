<?php

namespace tables\statuses;

class receiving extends \tables\statuses
{
    public $primaryKey = 'id';

    public $fields = [
        'displayName' => [],
    ];

    public $where = 'category = "receivings"';
    
    /*
    ****************************************************************************
    */    
}