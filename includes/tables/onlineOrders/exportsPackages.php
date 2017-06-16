<?php

namespace tables\onlineOrders;

class exportsPackages extends \tables\_default
{
    public $displaySingle = 'Online Orders Export Packages';

    public $ajaxModel = 'onlineOrders\\exportsPackages';
        
    public $primaryKey = 'pc.id';
    
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
            'select' => 'pc.shortName',
            'display' => 'Package Code',
        ],
        'displayName' => [
            'select' => 'pc.displayName',
            'display' => 'Package Name',
        ],
        'active' => [
            'select' => 'IF(pc.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'pc.active',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $table = 'online_orders_exports_packages pc
        LEFT JOIN online_orders_exports_providers pr ON pr.id = pc.providerID
        ';
    
    public $insertTable = 'online_orders_exports_packages';
    
    public $dropdownWhere = 'pc.active';
    
    public $mainField = 'pc.id';
    
    /*
    ****************************************************************************
    */
    
}