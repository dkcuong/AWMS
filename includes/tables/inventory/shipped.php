<?php

namespace tables\inventory;

class shipped extends \tables\_default
{
    public $primaryKey = 'ca.id';
    
    public $ajaxModel = 'inventory\\shipped';
    
    public $fields = [
        'orderShipDate' => [
            'display' => 'Ship Date',
            'noEdit' => TRUE,
        ],
        'sku' => [
            'select' =>'u.sku',
            'display' => 'SKU',
            'noEdit' => TRUE,
        ],
        'upc' => [
            'display' => 'UPC',
            'noEdit' => TRUE,
        ],
        'color' => [
            'display' => 'Color',
            'noEdit' => TRUE,
        ],
        'size' => [
            'display' => 'Size',
            'noEdit' => TRUE,
        ],
        'prefix' => [
            'display' => 'Prefix',
            'noEdit' => TRUE,
        ],
        'suffix' => [
            'display' => 'Suffix',
            'noEdit' => TRUE,
        ],
        'totalCartons' => [
            'select' => 'COUNT(ca.id)',
            'display' => 'Total Cartons',
            'groupedFields' => 'ca.id',
            'noEdit' => TRUE,
        ],
        'totalPieces' => [
            'select' => 'SUM(uom)',
            'display' => 'Total Pieces',
            'groupedFields' => 'uom',
            'noEdit' => TRUE,
        ],
    ];
    
    public $where = 's.shortName = "SH"
            AND s.category = "inventory"';
    
    public $mainField = 'u.id';
    
    public $groupBy = 'sku, color, size, orderShipDate';
    
    /*
    ****************************************************************************
    */
    
    function table()
    {
        return 'inventory_batches b
                JOIN      inventory_cartons ca ON ca.batchID = b.id
                JOIN      statuses s ON s.id = ca.statusID
                JOIN      upcs u ON u.id = b.upcID
                LEFT JOIN neworder n ON n.id = ca.orderID
                ';
    }

    /*
    ****************************************************************************
    */
}