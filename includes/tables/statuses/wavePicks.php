<?php

namespace tables\statuses;

class wavePicks extends \tables\statuses
{
    
    public $primaryKey = 'id';
    
    public $fields = [
        'shortName' => [],
    ];
    
    public $where = 'category = "wavePicks"';
    
    /*
    ****************************************************************************
    */
}