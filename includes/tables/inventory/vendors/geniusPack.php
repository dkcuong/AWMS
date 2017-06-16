<?php

namespace tables\inventory\vendors;

class geniusPack extends \tables\_default
{
    public $ajaxModel = 'inventory\vendors\geniusPack';
        
    public $primaryKey = 'p.id';

    public $fields = [
        'vendorInvID' => [
            'display' => 'Client Inv ID',
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
    
    public $table = 'vendor_data.genius_pack_products p 
           LEFT JOIN vendor_data.genius_pack_upc_info u ON p.sku = u.sku 
           ';
    
    /*
    ****************************************************************************
    */
    
}

