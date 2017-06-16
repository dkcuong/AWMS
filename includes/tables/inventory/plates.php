<?php

namespace tables\inventory;

class plates extends \tables\_default
{
    public $primaryKey = 'co.recNum';

    public $ajaxModel = 'inventory\\plates';

    public $fields = [
        'plate' => [
            'display' => 'License Plate',
            'noEdit' => TRUE,
            'acDisabled' => TRUE,
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'batchID' => [
            'display' => 'Batch Number',
            'noEdit' => TRUE,
        ],
         'upc' => [
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
        'uom' => [
            'select' => 'uom * 1',
            'display' => 'UOM',
            'noEdit' => TRUE,
        ],
        'initialCount' => [
            'select' => 'COUNT(c.id)',
            'display' => 'Total Cartons',
            'noEdit' => TRUE,
            'groupedFields' => 'c.id'
        ],
        'totalPieces' => [
            'select' => 'SUM(uom)',
            'display' => 'Total Pieces',
            'noEdit' => TRUE,
            'groupedFields' => 'uom'
        ],
        'statusID' => [
            'select' => 's.shortName',
            'display' => 'Status',
            'noEdit' => TRUE,
        ],
        'setDate' => [
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'orderBy' => 'b.id',
            'noEdit' => TRUE,
        ],
    ];

    public $where = 'plate';

    public $groupBy = 'plate, upcID, uom, statusID';

    public $displaySingle = 'Plate';

    public $mainField = 'plate';

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'inventory_cartons c
            JOIN inventory_batches b ON b.id = c.batchID
            JOIN inventory_containers co ON b.recNum = co.recNum
            LEFT JOIN upcs p ON b.upcID = p.id
            LEFT JOIN statuses s ON c.statusID = s.id
            JOIN vendors v ON v.id = vendorID
            JOIN warehouses w ON v.warehouseID = w.id
            LEFT JOIN '.$userDB.'.info u ON u.id = userID';
    }

    /*
    ****************************************************************************
    */

}


