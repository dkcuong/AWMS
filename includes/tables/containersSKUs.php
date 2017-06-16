<?php

namespace tables;

class containersSKUs extends \tables\_default
{
    public $ajaxModel = 'containersSKUs';

    public $primaryKey = 'ca.id';

    public $table = 'inventory_cartons ca
        JOIN      inventory_batches b ON b.id = ca.batchID
        JOIN      inventory_containers co ON co.recNum = b.recNum
        JOIN      upcs p ON p.id = b.upcID
        JOIN      statuses s ON s.id = ca.statusID
        JOIN      vendors v ON v.id = co.vendorID
        JOIN      warehouses w ON w.id = v.warehouseID';

    public $where = 'NOT isSplit
                    AND NOT unSplit';

    public $groupBy = 'co.recNum,
                       p.sku';

    /*
    ****************************************************************************
    */

    function fields()
    {
        $inactive = inventory\cartons::STATUS_INACTIVE;
        $shipped = inventory\cartons::STATUS_SHIPPED;

        $remaining = implode(',', [$inactive, $shipped]);
        
        return [
            'setDate' => [
                'display' => 'Set Date',
                'searcherDate' => TRUE,
                'orderBy' => 'ca.id',
                'noEdit' => TRUE,
            ],
            'vendor' => [
                'select' => 'CONCAT(w.shortName, "_", vendorName)',
                'display' => 'Client Name',
                'noEdit' => TRUE,
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            ],
            'container' => [
                'select' => 'name',
                'display' => 'Container',
                'noEdit' => TRUE,
            ],
            'recNum' => [
                'select' => 'co.recNum',
                'display' => 'Receiving Number',
                'noEdit' => TRUE,
            ],
            'upc' => [
                'display' => 'UPC',
                'noEdit' => TRUE,
            ],
            'sku' => [
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
            'received' => [
                'select' => 'SUM(IF(s.shortName = "' . $inactive . '", 0, uom))',
                'display' => 'Received',
                'noEdit' => TRUE,
                'groupedFields' => 's.shortName, uom',
            ],
            'shipped' => [
                'select' => 'SUM(IF(s.shortName = "' . $shipped . '", uom, 0))',
                'display' => 'Shipped',
                'noEdit' => TRUE,
                'groupedFields' => 's.shortName, uom',
            ],
            'remaining' => [
                'select' => 'SUM(
                                IF(FIND_IN_SET(s.shortName, "' . $remaining . '"), 0, uom)
                            )',
                'display' => 'Remaining',
                'noEdit' => TRUE,
                'groupedFields' => 's.shortName, uom',
            ],
        ];
    }

    /*
    ****************************************************************************
    */
}