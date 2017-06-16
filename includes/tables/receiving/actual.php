<?php

namespace tables\receiving;

class actual extends \tables\_default
{
    public $ajaxModel = 'receiving\\actual';

    public $primaryKey = 'b.id';

    public $groupBy = 'b.id';

    public $fields = [
        'warehouse' => [
            'select' => 'w.displayName',
            'display' => 'Warehouse',
            'ddField' => 'displayName',
            'searcherDD' => 'warehouses',
        ],
        'vendorName' => [
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'setDate' => [
            'select' => 'DATE(co.setDate)',
            'display' => 'Receiving Date',
            'searcherDate' => TRUE,
        ],
        'name' => [
            'display' => 'Container',
        ],
        'prefix' => [
            'display' => 'PO #',
        ],
        'sku' => [
            'display' => 'Style #',
        ],
        'color' => [
            'display' => 'Color',
        ],
        'size' => [
            'select' => 'u.size',
            'display' => 'Size',
        ],
        'uom' => [
            'display' => 'Units per Pack',
        ],
        'initialCount' => [
            'display' => '# OF CTNS',
        ],
        'actualUom' => [
            'select' => 'uom',
            'display' => 'Actual Units per pack',
        ],
        'actualCarton' => [
            'select' => 'COUNT(tr.cartonCount)',
            'display' => 'Actual Cartons',
        ],
        'length' => [
            'display' => 'Length',
        ],
        'width' => [
            'display' => 'Width',
        ],
        'height' => [
            'display' => 'Height',
        ],
        'CuFt' => [
            'display' => 'CuFt',
            'select' => 'ROUND(
                COUNT(tr.cartonCount)*length*width*height/1728,2)'
        ]

    ];

    public $table = 'inventory_cartons ca
        JOIN      inventory_batches b ON b.id = ca.batchID
        JOIN      inventory_containers co ON co.recNum = b.recNum
        JOIN      upcs u ON u.id = b.upcID
        JOIN      vendors v ON v.id = co.vendorID
        JOIN      warehouses w ON v.warehouseID = w.id
        JOIN      statuses s ON s.id = ca.statusID
        JOIN      tally_cartons tc on tc.invID = ca.id
        JOIN      tally_rows tr on tr.id = tc.rowID
        JOIN      tallies t on t.id = tr.tallyID
        ';

    /*
    ****************************************************************************
    */

}