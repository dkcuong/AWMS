<?php

namespace tables\locations;

class utilization extends \tables\_default
{
    public $primaryKey = 'ca.id';

    public $ajaxModel = 'locations\\utilization';

    public $fields = [
        'warehouse' => [
            'select' =>'w.displayName',
            'display' => 'Warehouse',
            'noEdit' => TRUE
        ],
        'location' => [
            'select' => 'l.displayName',
            'display' => 'Location',
            'noEdit' => TRUE
        ],
        'warehouseType' => [
            'select' => 'IF (l.isMezzanine, "Mezzanine", "Regular")',
            'display' => 'Type',
            'noEdit' => TRUE,
        ],
        'customer' => [
            'select' => 'GROUP_CONCAT(DISTINCT v.vendorName)',
            'display' => 'Customers',
            'groupedFields' => 'l.id',
            'noEdit' => TRUE,
        ],
        'amountVendor' => [
            'select' => 'COUNT(DISTINCT v.id)',
            'display' => 'Total Customer',
            'groupedFields' => 'l.id',
            'noEdit' => TRUE,
        ],
        'plate' => [
            'select' => 'GROUP_CONCAT(DISTINCT ca.plate)',
            'display' => 'LP',
            'groupedFields' => 'l.id',
            'noEdit' => TRUE,
        ],
        'cartons' => [
            'select' => 'COUNT(ca.id)',
            'display' => 'Cartons',
            'groupedFields' => 'l.id',
            'noEdit' => TRUE
        ],
        'pieces' => [
            'select' => 'SUM(ca.uom)',
            'display' => 'Pieces',
            'groupedFields' => 'l.id',
            'noEdit' => TRUE
        ]
    ];

    public $table = 'locations l
                     JOIN   warehouses w ON w.id = l.warehouseID
                     JOIN   inventory_cartons ca ON ca.locID = l.id
                     JOIN   statuses s ON s.id = ca.statusID
                     JOIN   inventory_batches b ON b.id = ca.batchID
                     JOIN   inventory_containers co ON co.recNum = b.recNum
                     JOIN   vendors v ON v.id = co.vendorID';

    public $where = 'NOT    isShipping 
                     AND    NOT ca.isSplit
                     AND    NOT ca.unSplit
                     AND    s.shortName = "RK"';

    public $displaySingle = 'Search Locations Utilization';

    public $mainField = 'l.id';

    public $groupBy = 'l.id';
}