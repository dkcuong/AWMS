<?php

namespace tables\locations;

class adminWarehouse extends \tables\_default
{
    public $ajaxModel = 'locations\\adminWarehouse';
    
    public $primaryKey = 'l.id';

    public $fields = [
        'location' => [
            'select' => 'l.displayName',
            'display' => 'Location',
        ],
        'warehouse' => [
            'select' => 'w.displayName',
            'display' => 'Warehouse',
            'searcherDD' => 'warehouses',
            'ddField' => 'displayName',
        ],
        'cubicFeet' => [
            'display' => 'Cubic Inches',
        ],
        'type' => [
            'select' => 'IF(isShipping, "Shipping", "Receiving")',
            'display' => 'Type',
            'searcherDD' => 'statuses\locationType',
            'ddField' => 'displayName',
        ],
        'mezzanine' => [
            'select' => 'IF(isMezzanine, "Yes", "No")',
            'display' => 'Mezzanine',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
        ],
        'volume' => [
            'select' => 'ROUND(
                            CEIL(
                                SUM(height * length * width) / 1728 * 4
                            ) / 4,
                            2
                         )',
            'groupedFields' => 'height, length, width',
            'display' => 'Used Volume',
            'noEdit' => TRUE,
        ],
        'weight' => [
            'select' => 'ROUND(SUM(weight), 2)',
            'display' => 'Stored Weight',
            'noEdit' => TRUE,
            'groupedFields' => 'weight'
        ],
    ];
    
    public $table = 'locations l
           JOIN      warehouses w ON w.id = warehouseID
           LEFT JOIN inventory_cartons ca ON ca.locID = l.id
           LEFT JOIN inventory_batches b on ca.batchID = b.id
           LEFT JOIN statuses s ON ca.statusID = s.id';

    public $groupBy = 'l.id';
}
