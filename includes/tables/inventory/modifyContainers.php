<?php

namespace tables\inventory;

class modifyContainers extends \tables\_default
{
    public $primaryKey = 'i.recNum';
    
    public $ajaxModel = 'inventory\\modifyContainers';
    
    public $fields = [
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'name' => [
            'display' => 'Container',
        ],
        'modify' => [
            'select' => 'name',
            'display' => 'Modify Container',
            'noEdit' => TRUE,
        ],
        'recNum' => [
            'display' => 'Receiving Number',
            'noEdit' => TRUE,
        ],
        'measureID' => [
            'select' => 'm.displayName',
            'display' => 'Measurement System',
            'searcherDD' => 'inventory\\measure',
            'ddField' => 'displayName',
            'noEdit' => TRUE,
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'update' => 'userID',
            'updateOverwrite' => TRUE,
        ],
        'setDate' => [
            'display' => 'Set Date', 
            'searcherDate' => TRUE,
            'orderBy' => 'i.recNum',
            'noEdit' => TRUE,
        ],
    ];

    public $groupBy = 'recNum';
    
    public $displaySingle = 'Container';

    public $mainField = 'recNUm';
    
    /*
    ****************************************************************************
    */
    
    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'inventory_containers i
            JOIN vendors v ON v.id = vendorID
            JOIN warehouses w ON v.warehouseID = w.id
            JOIN measurement_systems m ON i.measureID = m.id
            JOIN '.$userDB.'.info u ON u.id = userID';
    }

    /*
    ****************************************************************************
    */
    
}


