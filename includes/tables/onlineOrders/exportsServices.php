<?php

namespace tables\onlineOrders;

class exportsServices extends \tables\_default
{
    public $displaySingle = 'Online Orders Export Services';

    public $ajaxModel = 'onlineOrders\\exportsServices';
        
    public $primaryKey = 'sr.id';
    
    public $fields = [
        'providerID' => [
            'select' => 'pr.displayName',
            'display' => 'Provider',
            'searcherDD' => 'onlineOrders\exportsProviders',
            'ddField' => 'pr.displayName',
            'update' => 'providerID',
            'updateOverwrite' => TRUE,
        ],
        'shortName' => [
            'select' => 'sr.shortName',
            'display' => 'Service Code',
        ],
        'displayName' => [
            'select' => 'sr.displayName',
            'display' => 'Service Name',
        ],
        'active' => [
            'select' => 'IF(sr.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'sr.active',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $table = 'online_orders_exports_services sr
        LEFT JOIN online_orders_exports_providers pr ON pr.id = sr.providerID
        ';
    
    public $insertTable = 'online_orders_exports_services';
    
    public $dropdownWhere = 'sr.active';

    public $mainField = 'sr.id';
    
    /*
    ****************************************************************************
    */
    
}