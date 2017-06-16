<?php

namespace tables\inventory;

class pallets extends \tables\_default
{
    public $primaryKey = 'co.recNum';

    public $ajaxModel = 'inventory\\pallets';

    public $fields = [
        'setDate' => [
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'orderBy' => 'ca.id',
            'noEdit' => TRUE,
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'locIDShow' => [
            'select' => 'l.displayName',
            'display' => 'Pallet',
            'noEdit' => TRUE,
        ],
        'plate' => [
            'display' => 'License Plate',
            'noEdit' => TRUE,
            'acDisabled' => TRUE,
        ],
        'id' => [
            'select' => 'b.id',
            'display' => 'Batch Number',
            'noEdit' => TRUE,
        ],
        'upcID' => [
            'select' => 'p.upc',
            'display' => 'UPC',
            'noEdit' => TRUE,
        ],
        'sku' => [
            'select' => 'p.sku',
            'display' => 'SKU',
            'noEdit' => TRUE,
        ],
        'prefix' => [
            'display' => 'Prefix',
            'noEdit' => TRUE,
        ],
        'suffix' => [
            'display' => 'Suffix',
            'noEdit' => TRUE,
        ],
        'volume' => [
            'select' => 'CAST(
                            CEIL(
                                (height * length * width) / 1728 * 4
                            ) / 4 AS DECIMAL(4,2)
                        )',
            'display' => 'Volume',
            'noEdit' => TRUE,
        ],
        'weight' => [
            'select' => 'ROUND(COUNT(co.recNum) * weight, 2)',
            'display' => 'Weight',
            'noEdit' => TRUE,
            'groupedFields' => 'co.recNum',
        ],
        'actualCartons' => [
            'select' => 'COUNT(co.recNum)',
            'display' => 'Total Cartons',
            'noEdit' => TRUE,
            'groupedFields' => 'co.recNum'
        ],
        'totalPieces' => [
            'select' => 'SUM(uom)',
            'display' => 'Total Pieces',
            'noEdit' => TRUE,
            'groupedFields' => 'uom'
        ],
    ];

    public $table = 'inventory_containers co
        JOIN inventory_batches b ON b.recNum = co.recNum
        JOIN inventory_cartons c ON c.batchID = b.id
        JOIN statuses s ON c.statusID = s.id
        JOIN upcs p ON b.upcID = p.id
        JOIN locations l ON c.locID = l.id
        JOIN vendors v ON v.id = vendorID
        JOIN warehouses w ON v.warehouseID = w.id';

    public $where = 's.shortName = "RK"';

    public $groupBy = 'vendorID, locID, plate, upcID';

    public $displaySingle = 'Pallet';


    /*
    ****************************************************************************
    */

}


