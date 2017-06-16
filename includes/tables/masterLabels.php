<?php

namespace tables;

class masterLabels extends _default
{
    public $primaryKey = 'ca.id';

    public $ajaxModel = 'masterLabels';

    public $fields = [
        'container' => [
            'select' => 'name',
            'display' => 'Container',
        ],
        'batchNumber' => [
            'display' => 'Batch Number',
        ],
        'setDate' => [
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'orderBy' => 'ca.id',
            'noEdit' => TRUE,
        ],
        'barcode' => [
            'display' => 'Master Label',
            'acDisabled' => TRUE,
        ],
        'ucc128' => [
            'select' => 'CONCAT(co.vendorID,
                            b.id,
                            LPAD(uom, 3, 0),
                            LPAD(cartonID, 4, 0)
                        )',
            'customClause' => TRUE,
            'display' => 'UCC128',
            'acDisabled' => TRUE,
        ],
        'uom' => [
            'select' => 'LPAD(UOM, 3, 0)',
            'display' => 'UOM',
        ],
        'cartonID' => [
            'display' => 'Carton ID',
        ]
    ];

    public $where = 'NOT isSplit
        AND       NOT unSplit';

    public $table = 'masterLabel ma
        JOIN      inventory_batches b ON b.id = ma.batchNumber
        JOIN      inventory_cartons ca ON ca.batchID = b.id
        JOIN      inventory_containers co ON co.recNum = b.recNum
        ';

    /*
    ****************************************************************************
    */

}