<?php

namespace tables\orders;

class commodity extends \tables\_default
{
    public $primaryKey = 'id';
   
    public $ajaxModel = 'commodity';
    
    public $fields = [
        'id' => [],
        'description' =>[]
    ];
    
    public $table = 'commodity';
    
    
    /*
    ****************************************************************************
    */
    
}