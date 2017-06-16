<?php

namespace tables\inventory;

use tables\inventory\cartons;

class available extends \tables\_default
{

    public $primaryKey = 'co.recNum';

    public $ajaxModel = 'inventory\\available';

    public $fields = [
        'vendorName' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client',
            'noEdit' => TRUE,
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
         'containerName' => [
            'select' => 'co.name',
            'display' => 'Container',
            'noEdit' => TRUE,
        ],
        'warehouseType' => [
            'select' => 'IF (l.isMezzanine, "Mezzanine", "Regular")',
            'display' => 'Warehouse Type',
            'noEdit' => TRUE,
        ],
        'setDate' => [
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'orderBy' => 'ca.id',
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
        'height' => [
            'display' => 'Height',
            'noEdit' => TRUE,
        ],
        'width' => [
            'display' => 'Width',
            'noEdit' => TRUE,
        ],
        'length' => [
            'display' => 'Length',
            'noEdit' => TRUE,
        ],
        'weight' => [
            'display' => 'Weight',
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
        'size1' => [
            'select' => 'p.size',
            'display' => 'Size',
            'noEdit' => TRUE,
        ],
        'color1' => [
            'select' => 'p.color',
            'display' => 'Color',
            'noEdit' => TRUE,
        ],
        'quantity' => [
            'select' => 'COUNT(co.recNum)',
            'display' => 'Actual Cartons',
            'noEdit' => TRUE,
            'groupedFields' => 'co.recNum',
        ],
        'totalPieces' => [
            'select' => 'SUM(uom)',
            'display' => 'Total Pieces',
            'noEdit' => TRUE,
            'groupedFields' => 'uom'
        ],
    ];

    public $where = 'NOT c.isSplit
        AND      NOT c.unSplit
        AND      s.shortName = "' . cartons::STATUS_RACKED . '"
        AND      c.statusID = c.mStatusID';

    public $groupBy = 'vendorID, co.recNum, b.id, upcID, l.isMezzanine';

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'inventory_containers co
            JOIN      inventory_batches b ON co.recNum = b.recNum
            JOIN      inventory_cartons c ON b.id = c.batchID
            JOIN      statuses s ON c.statusID = s.id
            LEFT JOIN locations l ON l.id = c.locID
            LEFT JOIN locations lm ON lm.id = c.mLocID
            JOIN      vendors v ON v.id = vendorID
            JOIN      warehouses w ON v.warehouseID = w.id
            JOIN      measurement_systems m ON co.measureID = m.id
            JOIN      upcs p ON b.upcID = p.id
            JOIN      ' . $userDB . '.info u ON u.id = userID';
    }

    /*
    ****************************************************************************
    */

}