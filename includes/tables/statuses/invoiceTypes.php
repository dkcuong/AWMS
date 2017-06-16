<?php

namespace tables\statuses;

class invoiceTypes extends \tables\statuses
{
    
    public $primaryKey = 'id';
    
    public $fields = [
        'shortName' => [],
    ];
 
    public $where = 'category = "invoiceType"';
    
    /*
    ****************************************************************************
    */
}