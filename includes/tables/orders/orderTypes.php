<?php

namespace tables\orders;

class orderTypes extends \tables\_default
{
    public $primaryKey = 'id';
   
    public $ajaxModel = 'orderTypes';
    
    public $fields = [
        'id' => [],
        'typeName' =>[]
    ];
    
    public $table = 'order_types';
    
    
    /*
    ****************************************************************************
    */
    
}