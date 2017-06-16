<?php

namespace tables\plates;

class tallies extends \tables\_default
{
    public $ajaxModel = 'plates\\tallies';
    
    public $primaryKey = 'b.id';

    public $fields = [
        'id' => [
            'select' => 'b.id',
            'display' => 'Pallet Sheet',
        ],
        'username' => [
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'username',
        ],
        'vendorName' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'warehouse' => [
            'select' => 'w.displayName',
            'display' => 'Warehouse',
            'searcherDD' => 'warehouses',
            'ddField' => 'displayName',
        ],
        'datePrinted' => [
            'display' => 'Time Printed',
            'searcherDate' => TRUE,
        ],
    ];
    
    /*
    ****************************************************************************
    */
    
    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'pallet_sheets s
            LEFT JOIN    pallet_sheet_batches b ON b.palletSheetID = s.id
            LEFT JOIN    '.$userDB.'.info u ON s.userID = u.id            
            LEFT JOIN    vendors v ON v.id = s.vendorID
            LEFT JOIN    warehouses w ON v.warehouseID = w.id
            ';
    }
    
}
