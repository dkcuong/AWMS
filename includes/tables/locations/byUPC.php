<?php

namespace tables\locations;

class byUPC extends \tables\_default
{    
    
    public $ajaxModel = 'locations\byUPC';
    
    public $primaryKey = 'ca.id';
    
    public $fields = [
        'vendor' => [
            'select' => 'v.vendorName',
            'display' => 'Client',
            'searcherDD' => 'vendors',
            'ddField' => 'vendorName',
        ],
        'locID' => [
            'select' => 'l.displayName',
            'display' => 'Location',
        ],
        'upc' => [
            'display' => 'UPC',
        ],
        'cartons' => [
            'select' => 'COUNT(ca.id)',
            'display' => 'Cartons',
            'groupedFields' => 'ca.id',
        ],
        'pieces' => [
            'select' => 'SUM(uom)',
            'display' => 'Pieces',
            'groupedFields' => 'uom'
        ],
        'usedVolume' => [
            'select' => 'cast(ceil((height*length*width)/1728*4)/4 as decimal(10,2))',
            'display' => 'Used Volume',
        ],
        'availableVolume' => [
            'select' => '115200 - ROUND(
                            SUM(height * width* length), 1
                         )',
            'display' => 'Available Volume',
            'groupedFields' => 'height, width, length',
        ],
        'totalVolume' => [
            'select' => '"115200"',
            'display' => 'Total Volume',
        ],
    ];
    
    public $table = 'inventory_containers co
           LEFT JOIN inventory_batches b ON b.recNum = co.recNum
           LEFT JOIN inventory_cartons ca ON ca.batchID = b.id
           LEFT JOIN vendors v ON v.id = co.vendorID
           LEFT JOIN statuses s ON s.id = ca.statusID
           LEFT JOIN locations l ON l.id = ca.locID
           LEFT JOIN upcs u ON u.id = b.upcID
           ';
    
    public $where = 's.shortName = "RK"';
    
    public $groupBy = 'ca.locID, co.vendorID, b.upcID';

    /*
    ****************************************************************************
    */

    
    function emptyMethod()
    {
    }
        
    /*
    ****************************************************************************
    */
}