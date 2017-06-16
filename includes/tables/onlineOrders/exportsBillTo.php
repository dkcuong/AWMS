<?php

namespace tables\onlineOrders;

class exportsBillTo extends \tables\_default
{
    public $displaySingle = 'Online Orders Export Bill To';

    public $ajaxModel = 'onlineOrders\\exportsBillTo';
        
    public $primaryKey = 'bl.id';
    
    public $fields = [
        'displayName' => [
            'select' => 'bl.displayName',
            'display' => 'Bill To',
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

    public $table = 'online_orders_exports_bill_to bl';
    
    public $insertTable = 'online_orders_exports_bill_to';
    
    public $dropdownWhere = 'active';
    
    public $mainField = 'bl.id';
    
    /*
    ****************************************************************************
    */

}