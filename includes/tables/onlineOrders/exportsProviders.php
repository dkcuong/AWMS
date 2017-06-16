<?php

namespace tables\onlineOrders;

class exportsProviders extends \tables\_default
{
    public $displaySingle = 'Online Orders Export Providers';

    public $ajaxModel = 'onlineOrders\\exportsProviders';
        
    public $primaryKey = 'pr.id';
    
    public $fields = [
        'displayName' => [
            'select' => 'pr.displayName',
            'display' => 'Provider',
        ],
        'active' => [
            'select' => 'IF(active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'active',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $table = 'online_orders_exports_providers pr';
    
    public $insertTable = 'online_orders_exports_providers';
    
    public $dropdownWhere = 'active';
    
    public $mainField = 'pr.id';
    
    /*
    ****************************************************************************
    */
    
}