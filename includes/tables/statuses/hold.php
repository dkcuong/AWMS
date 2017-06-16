<?php

namespace tables\statuses;

class hold extends \tables\statuses
{
    
    public $primaryKey = 'id';
    
    public $fields = [
        'shortName' => [],
    ];
    
    public $where = 'category = "hold"';
    
    /*
    ****************************************************************************
    */
}