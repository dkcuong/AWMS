<?php

namespace tables\inventory;

class control extends \tables\_default
{

    public $primaryKey = 'ic.id';

    public $ajaxModel = 'inventory\\control';

    public $fields = [
        'changeDate' => [
            'searcherDate' => TRUE,
            'display' => 'Date',
            'noEdit' => TRUE,
        ],
        'container' => [
            'select' => 'co.name',
            'display' => 'Container',
            'noEdit' => TRUE,
        ],
        'recNum' => [
            'select' => 'co.recNum',
            'display' => 'Receiving Number',
            'noEdit' => TRUE,
        ],
        'batchID' => [
            'select' => 'ca.batchID',
            'display' => 'Batch Number',
            'noEdit' => TRUE,
        ],
        'setDate' => [
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'orderBy' => 'ca.id',
        ],
        'vendorName' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client',
            'noEdit' => TRUE,
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'sku' => [
            'select' => 'u.sku',
            'batchFields' => TRUE,
            'display' => 'Style Number',
            'noEdit' => TRUE,
        ],
        'measureID' => [
            'select' => 'm.displayName',
            'display' => 'Measurement System',
            'searcherDD' => 'inventory\\measure',
            'ddField' => 'displayName',
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
        'uom' => [
            'display' => 'UOM',
            'noEdit' => TRUE,
        ],
        'cartonID' => [
            'display' => 'Carton',
        ],
        'prefix' => [
            'display' => 'Prefix',
            'noEdit' => TRUE,
        ],
        'suffix' => [
            'display' => 'Suffix',
            'noEdit' => TRUE,
        ],
        'upc' => [
            'display' => 'UPC',
            'noEdit' => TRUE,
        ],
        'size1' => [
            'select' => 'u.size',
            'display' => 'Size',
            'noEdit' => TRUE,
        ],
        'color1' => [
            'select' => 'u.color',
            'display' => 'Color',
            'noEdit' => TRUE,
        ],
        'ucc128' => [
            'select' => 'CONCAT(v.id,
                            b.id,
                            LPAD(uom, 3, 0),
                            LPAD(cartonID, 4, 0)
                        )',
            'display' => 'UCC128',
            'customClause' => TRUE,
            'ignore' => TRUE,
            'acDisabled' => TRUE,
        ],
        'orderNumber' => [
            'select' => 'o.scanordernumber',
            'display' => 'Order Numbers',
            'noEdit' => TRUE,
        ],
        'palletLocation' => [
            'select' => 'l.displayName',
            'display' => 'Location',
        ],
        'licensePlate' => [
            'select' => 'ic.licensePlate',
            'display' => 'License Plate',
            'noEdit' => TRUE,
            'acDisabled' => TRUE,
        ],
        'userID' => [
            'select' => 'us.username',
            'display' => 'Username',
        ],
        'status' => [
            'select' => 'ic.status',
            'display' => 'Status',
            'noEdit' => TRUE,
        ],
        'initialCount' => [
            'display' => 'Total Carton',
            'noEdit' => TRUE,
        ],
        'totalPieces' => [
            'select' => 'uom * initialCount',
            'display' => 'Total Pieces',
            'noEdit' => TRUE,
        ],
    ];

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'inventory_control ic
            LEFT JOIN inventory_cartons ca ON ic.inventoryID = ca.id
            LEFT JOIN inventory_batches b ON b.id = ca.batchID
            LEFT JOIN inventory_containers co ON co.recNum = b.recNum
            LEFT JOIN locations l ON l.id = ca.locID
            LEFT JOIN licenseplate p ON p.id = ca.plate
            LEFT JOIN neworder o ON o.id = ca.orderId
            LEFT JOIN measurement_systems m ON m.id = co.measureID
            LEFT JOIN statuses s ON s.id = ca.statusID
            LEFT JOIN vendors v ON v.id = co.vendorID
            LEFT JOIN warehouses w ON v.warehouseID = w.id
            LEFT JOIN upcs u ON u.id = b.upcId
            LEFT JOIN '.$userDB.'.info us ON us.id = co.userID';
    }

    /*
    ****************************************************************************
    */

}