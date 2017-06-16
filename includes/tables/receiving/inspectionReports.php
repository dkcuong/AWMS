<?php

namespace tables\receiving;

class inspectionReports extends \tables\_default
{
    public $ajaxModel = 'receiving\\inspectionReports';

    public $primaryKey = 'b.id';

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
            'select' => 'DATE(setDate)',
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
        'numberofpieces' => [
            'select' => 'uom * initialCount',
            'display' => 'Units Recieved',
        ],
    ];

    public $table = 'inventory_cartons ca
        JOIN      inventory_batches b ON b.id = ca.batchID
        JOIN      inventory_containers co ON co.recNum = b.recNum
        JOIN      upcs u ON u.id = b.upcID
        JOIN      vendors v ON v.id = co.vendorID
        JOIN      warehouses w ON v.warehouseID = w.id
        JOIN      statuses s ON s.id = ca.statusID
        ';

     public $where = 'cartonID = 1
        AND       s.shortName != "IN"';

    /*
    ****************************************************************************
    */

}