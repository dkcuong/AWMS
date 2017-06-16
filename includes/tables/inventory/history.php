<?php

namespace tables\inventory;

class history extends \tables\_default
{
    public $primaryKey = 'co.recNum';

    public $ajaxModel = 'inventory\\history';

    public $fields = [
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'container' => [
            'select' => 'IF(ca.orderID && s.shortName = "SH",NULL,co.name)',
            'display' => 'Container Name',
        ],
        'scanordernumber' => [
            'select' => 'IF(s.shortName = "SH",n.scanordernumber,NULL)',
            'display' => 'Order Number',
            'noEdit' => TRUE,
        ],
        'logTime' => [
            'display' => 'Log Time',
            'searcherDate' => TRUE,
            'ignore' => TRUE,
        ],
        'actualCartons' => [
            'select' => 'COUNT(ca.id)',
            'display' => 'BOX',
        ],
        'quantity' => [
            'select' => 'SUM(ca.uom)',
            'display' => 'Quantity',
        ],
    ];

    public $table = 'logs_cartons lc
        JOIN logs_values lv ON lc.id = lv.logID
        JOIN logs_fields lf ON lv.fieldID = lf.id
        JOIN statuses s ON s.id = lv.toValue
        LEFT JOIN inventory_cartons ca ON lv.primeKey = ca.id
        LEFT JOIN inventory_batches b ON b.id = ca.batchID
        LEFT JOIN inventory_containers co ON co.recNum = b.recNum
        LEFT JOIN vendors v ON co.vendorID = v.id
        LEFT JOIN warehouses w ON w.id = v.warehouseID
        LEFT JOIN neworder n ON n.id = ca.orderID
    ';

    public $where = 'lf.displayName = "statusID"
                    AND s.shortName IN ("RK", "SH")
                    AND lf.category = "cartons"';

    public $groupBy = 'lv.toValue, co.recNum';

    /*
    ****************************************************************************
    */

    function ajaxSource()
    {
        return jsonLink('inventory');
    }

    /*
    ****************************************************************************
    */
}


