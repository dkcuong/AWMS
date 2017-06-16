<?php

namespace tables\inventory;

class vendorPallets extends \tables\_default
{
    
    public $primaryKey = 'ca.id';
    
    public $ajaxModel = 'inventory\\vendorPallets';
    
    public $fields = [
        'vendorName' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client',
            'noEdit' => TRUE,
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'totalPallets' => [
            'select' => 'COUNT(DISTINCT plate)',
            'display' => 'Total Pallets',
            'noEdit' => TRUE,
            'groupedFields' => 'plate',
        ],
    ];
    
    public $table = 'inventory_cartons ca
            JOIN      inventory_batches b ON b.id = ca.batchID 
            JOIN      inventory_containers co ON co.recNum = b.recNum 
            JOIN      vendors v ON v.id = co.vendorID
            JOIN      warehouses w ON v.warehouseID = w.id
            JOIN      statuses s ON s.id = ca.statusID ';
    
    public $where = 's.shortName = "RK"';
    
    public $groupBy = 'v.id';
    
    /*
    ****************************************************************************
    */
}