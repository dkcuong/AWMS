<?php

namespace tables\locations;

class noMezzanine extends \tables\_default
{
    public $ajaxModel = 'locations\\noMezzanine';
    
    public $fields = [
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'noEdit' => TRUE,
        ],
        'sku' => [
            'select' =>'u.sku',
            'display' => 'Style Number',
        ],
        'upc' => [
            'select' =>'u.upc',
            'display' => 'UPC',
        ],
        'color' => [
            'select' =>'u.color',
            'display' => 'Color',
        ],
        'size' => [
            'select' =>'u.size',
            'display' => 'Size',
        ],
        'pieces' => [
            'select' =>'SUM(uom)',
            'display' => 'Pieces Available',
            'groupedFields' => 'uom',
        ],
        'minMax' => [
            'select' =>'IF(li.id IS NULL, "No Min/Max", "Has Min/Max")',
            'display' => 'Min/Max',
            'groupedFields' => 'uom',
        ],
    ];
        
    
    public $table = 'inventory_containers co
           JOIN      inventory_batches b ON b.recNum = co.recNum
           JOIN      upcs u ON u.id = b.upcID
           JOIN      inventory_cartons ca ON ca.batchID = b.id
           JOIN      vendors v ON v.id = co.vendorID
           JOIN      warehouses w ON v.warehouseID = w.id
           JOIN      locations l ON l.id = ca.locID
           JOIN      statuses s ON ca.statusID = s.id
           LEFT JOIN locations_info li ON li.upcID = u.id';
    
    public $where = 's.shortName = "RK"
                 AND s.category = "inventory"
                 AND NOT isSplit
                 AND NOT unSplit';

    public $groupBy = 'u.id';

    public $having = 'NOT sum(isMezzanine)';
    
    public $displaySingle = 'No Mezzanine';

    /*
    ****************************************************************************
    */

    function getNoMezzanineVendors()
    {       
        $sql = 'SELECT    DISTINCT v.id,
                          CONCAT(w.shortName, "_", vendorName) AS vendor
                FROM      '.$this->table.'
                WHERE     '.$this->where.'
                GROUP BY  '.$this->groupBy.'
                HAVING    '.$this->having.'
                ORDER BY  w.shortName ASC,
                          vendorName ASC
                ';
        
        return $this->app->queryResults($sql);
    }
    
    /*
    ****************************************************************************
    */    
    
}