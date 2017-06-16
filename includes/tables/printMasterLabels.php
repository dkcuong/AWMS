<?php

namespace tables;

class printMasterLabels extends _default
{
    public $primaryKey = 'ma.barcode';

    public $ajaxModel = 'printMasterLabels';

    public $fields = [
        'batchNumber' => [
            'display' => 'Batch Number',
        ],
        'barcode' => [
            'display' => 'Master Label',
            'acDisabled' => TRUE,
        ],
        'setDate' => [
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'orderBy' => 'ca.id',
            'noEdit' => TRUE,
        ],
        'name' => [
            'display' => 'Container',
            'noEdit' => TRUE,
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'upcID' => [
            'select' => 'p.upc',
            'display' => 'UPC',
        ]
    ];

    public $where = 'NOT isSplit
        AND       NOT unSplit';

    public $groupBy = 'barcode';

    public $table = 'inventory_containers co
        JOIN      inventory_batches b ON co.recNum = b.recNum
        JOIN      inventory_cartons ca ON b.id = ca.batchID
        JOIN      vendors v ON v.id = co.vendorID
        JOIN      warehouses w ON v.warehouseID = w.id
        JOIN      upcs p ON p.id = b.upcID
        JOIN      masterLabel ma ON b.id = ma.batchNumber';

    /*
    ****************************************************************************
    */

}