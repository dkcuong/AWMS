<?php

namespace tables\inventory;

class styleUOMs extends \tables\_default
{
    public $primaryKey = 'u.id';
    
    public $ajaxModel = 'inventory\\styleUOMs';
    
    public $fields = [
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
        'cartomUOM' => [
            'select' => 'GROUP_CONCAT(DISTINCT UOM ORDER BY UOM SEPARATOR ", ")',
            'display' => 'Carton UOM',
            'groupedFields' => 'uom',
            'noEdit' => TRUE,
        ],
    ];
    
    public $where = 's.shortName = "RK"
            AND s.category = "inventory"
            AND NOT isSplit
            AND NOT unSplit';
    
    public $displaySingle = 'Style UOMs';

    public $mainField = 'u.id';
    
    public $groupBy = 'sku, color, size';
    
    /*
    ****************************************************************************
    */
    
    function table()
    {
        return 'inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      statuses s ON ca.statusID = s.id
                JOIN      upcs u ON u.id = b.upcID';
    }

    /*
    ****************************************************************************
    */
}