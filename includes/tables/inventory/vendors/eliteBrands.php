<?php

namespace tables\inventory\vendors;

class eliteBrands extends \tables\_default
{
    public $ajaxModel = 'inventory\vendors\eliteBrands';
        
    public $primaryKey = 'p.id';

    public $fields = [
        'vendorInvID' => [
            'display' => 'CLient Inv ID',
        ],
        'sku' => [
            'select' => 'p.sku',
            'display' => 'SKU',
        ],
        'location' => [
            'display' => 'Location',
        ],
        'uom' => [
            'display' => 'UOM',
        ],
        'upc' => [
            'display' => 'UPC',
        ],
        'description' => [
            'display' => 'Description',
        ],
        'productQuantity' => [
            'select' => 'p.quantity',
            'display' => 'Product Quantity',
        ],
        'upcQuantity' => [
            'select' => 'u.quantity',
            'display' => 'UPC Quantity',
        ],
        'width' => [
            'display' => 'Width',
        ],
        'length' => [
            'display' => 'Length',
        ],
        'height' => [
            'display' => 'Height',
        ],
    ];
    
    public $table = 'vendor_data.elite_brands_products p 
           LEFT JOIN vendor_data.elite_brands_upc_info u ON p.sku = u.sku 
           ';
    
    /*
    ****************************************************************************
    */
    
}

