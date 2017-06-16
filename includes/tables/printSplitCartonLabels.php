<?php

namespace tables;

class printSplitCartonLabels extends _default
{
    public $ajaxModel = 'printSplitCartonLabels';

    public $primaryKey = 'ip.id';

    public $fields = [
        'splitID' => [
            'select' => 'ip.id',
            'display' => 'Print Barcodes',
        ],
        'parent' => [
            'select' => 'CONCAT(v.id,
                                bap.id,
                                LPAD(cap.uom, 3, 0),
                                LPAD(cap.cartonID, 4, 0)
                        )',
            'display' => 'Original UCC',
            'acDisabled' => TRUE,
        ],
        'child' => [
            'select' => 'CONCAT(v.id,
                                bap.id,
                                LPAD(cac.uom, 3, 0),
                                LPAD(cac.cartonID, 4, 0)
                        )',
            'display' => 'Split UCC',
            'acDisabled' => TRUE,
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", v.vendorName)',
        ],
        'container' => [
            'select' => 'cop.name',
            'display' => 'Container',
        ],
        'batchID' => [
            'select' => 'bap.id',
            'display' => 'Batch Number',
        ],

    ];

    public $where = 'ip.active';

    /*
    ****************************************************************************
    */

    function table()
    {
        return 'inventory_splits ip
               JOIN inventory_cartons cap ON cap.id = ip.parentID
               JOIN inventory_batches bap ON bap.id = cap.batchID
               JOIN inventory_containers cop ON cop.recNum = bap.recNum
               JOIN vendors v ON v.id = cop.vendorID
               JOIN warehouses w ON v.warehouseID = w.id
               JOIN inventory_cartons cac ON cac.id = ip.childID';
    }
}