<?php

namespace tables\logs;

class orders extends \tables\_default
{
    public $primaryKey = 'lv.id';
    
    public $ajaxModel = 'logs\\orders';
    
    public $fields = [
        'logTime' => [
            'display' => 'Log Time', 
            'searcherDate' => TRUE,
            'noEdit' => TRUE,
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'noEdit' => TRUE,
        ],        
        'scanordernumber' => [
            'display' => 'Scan Order #',
            'noEdit' => TRUE,
        ],
        'vendor' => [
            'display' => 'Client',
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'noEdit' => TRUE,
        ],
        'clientordernumber' => [
            'display' => 'Client Order #',
            'noEdit' => TRUE,
        ],
        'startShipDate' => [
            'display' => 'Start Ship Date',
            'searcherDate' => TRUE,
            'noEdit' => TRUE,
        ],
        'cancelDate' => [
            'display' => 'Cancel Date',
            'searcherDate' => TRUE,
            'noEdit' => TRUE,
        ],
        'target' => [
            'select' => 'IF(lf.displayName = "statusID",
                            "Status", "Routed Status")',
            'display' => 'Target',
        ],        
        'fromValue' => [
            'select' => 'IF(lf.displayName = "statusID",
                            fs.shortName, frs.shortName)',
            'display' => 'Initial Value',
        ],
        'toValue' => [
            'select' => 'IF(lf.displayName = "statusID",
                            ts.shortName, trs.shortName)',
            'display' => 'New Value',
        ]
    ];

    public $where = 'lf.category = "orders"';
    
    public $mainTable = 'logs_orders';
    
    public $mainField = 'lv.id';
    
    /*
    ****************************************************************************
    */
    
    function table()
    {
        $userDB = $this->app->getDBName('users');
       
        return 'logs_values lv
                JOIN      logs_orders lo ON lo.id = lv.logID
                LEFT JOIN '.$userDB.'.info u ON u.id = lo.userID
                JOIN      neworder n ON n.id = lv.primeKey
                JOIN      logs_fields lf ON lf.id = lv.fieldID
                JOIN      order_batches b ON b.id = n.order_batch 
                JOIN      vendors v ON v.id = b.vendorID
                JOIN      warehouses w ON w.id = v.warehouseID
                LEFT JOIN statuses fs ON fs.id = lv.fromValue
                LEFT JOIN statuses ts ON ts.id = lv.toValue
                LEFT JOIN statuses frs ON frs.id = lv.fromValue
                LEFT JOIN statuses trs ON trs.id = lv.toValue
                ';
    }
    
    /*
    ****************************************************************************
    */
    
}
