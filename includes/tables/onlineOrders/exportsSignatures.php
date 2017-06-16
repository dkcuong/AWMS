<?php

namespace tables\onlineOrders;

class exportsSignatures extends \tables\_default
{
    public $displaySingle = 'Online Orders Export Signature';

    public $ajaxModel = 'onlineOrders\\exportsSignatures';
        
    public $primaryKey = 'sg.id';
    
    public $fields = [
        'displayName' => [
            'select' => 'sg.displayName',
            'display' => 'Signature',
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

    public $table = 'online_orders_exports_signatures sg';
    
    public $insertTable = 'online_orders_exports_signatures';
    
    public $dropdownWhere = 'active';
    
    public $mainField = 'sg.id';
    
    /*
    ****************************************************************************
    */

}
