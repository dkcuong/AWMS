<?php

namespace tables\inventory;

class summaryReport extends \tables\_default
{
    public $ajaxModel = 'inventory\\summaryReport';

    public $primaryKey = 'ca.id';

    public $table = 'inventory_cartons ca
        JOIN      inventory_batches b ON b.id = ca.batchID
        JOIN      inventory_containers co ON co.recNum = b.recNum
        JOIN      vendors v ON v.id = co.vendorID
        JOIN      warehouses w ON v.warehouseID = w.id
        JOIN      statuses s ON s.id = ca.statusID
        JOIN      statuses ms ON ms.id = ca.mStatusID
        JOIN      upcs u ON u.id = b.upcID
        ';

    // do not display orders that are shipped more than 1 business day ago
    public $where = 'v.active
        AND NOT isSplit
        AND NOT unSplit
        ';

    public $groupBy = 'name,
                       upc,
                       prefix,
                       suffix,
                       uom';

    /*
    ****************************************************************************
    */

    function fields()
    {
        $fields = [
            'vendor' => [
                'select' => 'CONCAT(w.shortName, "_", vendorName)',
                'display' => 'Client Name',
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
                'noEdit' => TRUE,
            ],
            'name' => [
                'display' => 'Container Name',
                'noEdit' => TRUE,
            ],
            'recNum' => [
                'select' => 'co.recNum',
                'display' => 'Receiving Number',
                'noEdit' => TRUE,
            ],
            'vendorID' => [
                'display' => 'RC Log',
                'noEdit' => TRUE,
            ],
            'setDate' => [
                'select' => 'setDate',
                'display' => 'Date Rec',
                'searcherDate' => TRUE,
                'orderBy' => 'co.recNum',
                'noEdit' => TRUE,
            ],
            'upc' => [
                'display' => 'UPC',
                'noEdit' => TRUE,
            ],
            'sku' => [
                'select' =>'u.sku',
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
            'prefix' => [
                'display' => 'Prefix',
                'noEdit' => TRUE,
            ],
            'suffix' => [
                'display' => 'Suffix',
                'noEdit' => TRUE,
            ],
            'uom' => [
                'display' => 'UOM',
                'noEdit' => TRUE,
            ],
            'totalCartons' => [
                'select' => 'COUNT(ca.id)',
                'display' => 'Total Cartons',
                'groupedFields' => 'ca.id',
                'noEdit' => TRUE,
            ],
            'totalPieces' => [
                'select' => 'SUM(uom)',
                'display' => 'Total Pieces',
                'groupedFields' => 'uom',
                'noEdit' => TRUE,
            ],
            'inactive' => [
                'select' => 'SUM(IF(s.shortName = "IN", uom, 0))',
                'display' => 'Inactive',
                'groupedFields' => 'uom',
                'noEdit' => TRUE,
            ],
            'received' => [
                'select' => 'SUM(IF(s.shortName = "RC", uom, 0))',
                'display' => 'Received',
                'groupedFields' => 'uom',
                'noEdit' => TRUE,
            ],
            'picked' => [
                'select' => 'SUM(IF(s.shortName = "PK", uom, 0))',
                'display' => 'Picked',
                'groupedFields' => 'uom',
                'noEdit' => TRUE,
            ],
            'processed' => [
                'select' => 'SUM(IF(s.shortName = "OP", uom, 0))',
                'display' => 'Processed',
                'groupedFields' => 'uom',
                'noEdit' => TRUE,
            ],
            'shipping' => [
                'select' => 'SUM(IF(s.shortName = "LS", uom, 0))',
                'display' => 'Shiping Check In',
                'groupedFields' => 'uom',
                'noEdit' => TRUE,
            ],
            'shipped' => [
                'select' => 'SUM(IF(s.shortName = "SH", uom, 0))',
                'display' => 'Shipped',
                'groupedFields' => 'uom',
                'noEdit' => TRUE,
            ],
            'racked' => [
                'select' => 'SUM(IF(s.shortName = "RK" AND ms.shortName != "RS", uom, 0))',
                'display' => 'Pieces Racked',
                'groupedFields' => 'uom',
                'noEdit' => TRUE,
            ],
            'rackedCartons' => [
                'select' => 'SUM(IF(s.shortName = "RK" AND ms.shortName != "RS", 1, 0))',
                'display' => 'Cartons Racked',
                'groupedFields' => 's.shortName',
                'noEdit' => TRUE,
            ],
            'reserved' => [
                'select' => 'SUM(IF(s.shortName = "RK" AND ms.shortName = "RS", uom, 0))',
                'display' => 'Reserved',
                'groupedFields' => 'uom',
                'noEdit' => TRUE,
            ],
            'discrepant' => [
                'select' => 'SUM(IF(s.shortName = "DS", uom, 0))',
                'display' => 'Discrepant',
                'groupedFields' => 'uom',
                'noEdit' => TRUE,
            ],
        ];

        $isClient = \access::isClient($this);

        if ($isClient) {

            $clientFields = [
                'vendor',
                'name',
                'recNum',
                'setDate',
                'upc',
                'sku',
                'color',
                'size',
                'prefix',
                'suffix',
                'uom',
                'racked',
                'rackedCartons',
                'reserved'
            ];

            $clientFieldsKeys = array_flip($clientFields);

            return array_intersect_key($fields, $clientFieldsKeys);
        } else {
            return $fields;
        }
    }

    /*
    ****************************************************************************
    */

}
