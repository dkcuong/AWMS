<?php

namespace tables\orders;

class companyAddresses extends \tables\_default
{
    public $primaryKey = 'id';
    
    public $ajaxModel = 'companyAddresses';
    
    public $table = 'company_address';
    
    public $fields = [
        'id' => [],
        'companyName' =>[]
    ];
    
    public $where = 'companyName != "SELDAT NJ Thatcher"';
    
    /*
    ****************************************************************************
    */
    
}