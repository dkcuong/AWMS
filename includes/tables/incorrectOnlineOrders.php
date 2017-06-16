<?php

namespace tables;

class incorrectOnlineOrders extends _default
{
    public $primaryKey = 'SCAN_SELDAT_ORDER_NUMBER';
    
    public $ajaxModel = 'incorrectOnlineOrders';
        
    
    public $fields = [
        'SCAN_SELDAT_ORDER_NUMBER' => [
            'display' => 'SCAN_SELDAT_ORDER_NUMBER',
        ],
        'reference_id' => [
            'display' => 'Reference ID',
            'required' => TRUE,
        ],
        'upc' => [
            'select' => 'o.UPC',
            'display' => 'UPC',
        ],                  
        'order_date' => [
            'display' => 'Order Date',
            'required' => TRUE,
        ],
        'difference' => [
            'select' => 'DATEDIFF(now(), STR_TO_DATE(order_date, "%m/%d/%Y"))',
            'display' => 'Days From Order Date',
        ],
    ];

    public $where = 'u.UPC IS NULL 
            AND DATEDIFF(now(), STR_TO_DATE(order_date, "%m/%d/%Y")) <= 3';
    
    public $mainField = 'scanOrderNumber';
    
    /*
    ****************************************************************************
    */
    
    function table() 
    {
        return 'online_orders o
            LEFT JOIN upcs u ON u.upc = o.UPC';
    }
  
}
