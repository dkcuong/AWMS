<?php

namespace tables\inventory;

class pickErrors extends \tables\_default
{
    public $primaryKey = 'pe.id';
    
    public $ajaxModel = 'inventory\\pickErrors';
    
    public $fields = [
        'scanordernumber' => [
            'display' => 'Order Number',
            'noEdit' => TRUE,
        ],
        'isError' => [
            'select' => 'es.displayName',
            'display' => 'Error Status',
            'searcherDD' => 'statuses\\enoughInventory',
            'ddField' => 'displayName',
            'noEdit' => TRUE,
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'noEdit' => TRUE,
        ],
         'upc' => [
            'display' => 'UPC',
            'noEdit' => TRUE,
        ],
        'sku' => [
            'select' =>'p.sku',
            'display' => 'SKU',
            'noEdit' => TRUE,
        ],
        'color' => [
            'display' => 'Color',
            'noEdit' => TRUE,
        ],
        'size' => [
            'display' => 'Size',
            'noEdit' => TRUE,
        ],
        'quantity' => [
            'display' => 'Quantity',
            'noEdit' => TRUE,
        ],
        'totalPieces' => [
            'select' => 'SUM(IF(s.shortName = "RK" 
                                AND ca.statusID = ca.mStatusID
                                AND ca.locID = ca.mLocID, uom, 0))',
            'display' => 'Available Pieces',
            'groupedFields' => 'uom',
            'noEdit' => TRUE,
        ],
        'totalCartons' => [
            'select' => 'SUM(IF(s.shortName = "RK" 
                                AND ca.statusID = ca.mStatusID
                                AND ca.locID = ca.mLocID, 1, 0))',
            'display' => 'Available Cartons',
            'groupedFields' => 'uom',
            'noEdit' => TRUE,
        ],
    ];
    
    public $where = 'pe.active
            AND (ca.id IS NULL
            OR  co.vendorID = ob.vendorID
            AND s.category = "inventory"
            AND NOT isSplit
            AND NOT unSplit)';
    
    public $displaySingle = 'Error Cartons';

    public $mainField = 'pe.id';
    
    public $groupBy = 'pe.id';
    
    /*
    ****************************************************************************
    */
    
    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'pick_errors pe
            JOIN      upcs p ON p.id = pe.upcID
            JOIN      neworder n ON n.id = pe.orderID
            JOIN      statuses es ON es.id = n.isError
            JOIN      order_batches ob ON ob.id = n.order_batch
            LEFT JOIN '.$userDB.'.info u ON n.userID = u.id 
            LEFT JOIN inventory_batches b ON b.upcID = pe.upcID
            LEFT JOIN inventory_cartons ca ON ca.batchID = b.id
            LEFT JOIN inventory_containers co ON co.recNum = b.recNum
            LEFT JOIN statuses s ON s.id = ca.statusID
            JOIN      vendors v ON v.id = ob.vendorID
            JOIN      warehouses w ON w.id = v.warehouseID';
    }

    /*
    ****************************************************************************
    */
    
}


