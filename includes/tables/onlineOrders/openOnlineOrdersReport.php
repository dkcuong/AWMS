<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 20/12/2016
 * Time: 14:42
 */

namespace tables\onlineOrders;

class openOnlineOrdersReport extends \tables\_default
{
    public $primaryKey = 'v.id';

    public $ajaxModel = 'onlineOrders\\openOnlineOrdersReport';

    public $table = 'vendors v
                    JOIN warehouses w ON w.id = v.warehouseID
                    JOIN order_batches b ON b.vendorID = v.id
                    JOIN neworder n ON n.order_batch = b.id
                    LEFT JOIN statuses s ON s.id = n.statusID';

    public $mainTable = 'vendors';

    public $fields = [
        'scanordernumber' => [
            'select' => 'scanordernumber',
            'display' => 'Scan Order Number',
            'noEdit' => TRUE
        ],
        'clientordernumber' => [
            'select' => 'n.clientordernumber',
            'display' => 'Client Order Number',
            'noEdit' => TRUE,
        ],
        'dateCreated' => [
            'select' => 'DATE(dateCreated)',
            'display' => 'Created',
            'noEdit' => TRUE,
        ],
        'vendorName' => [
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'display' => 'vendor Name',
            'noEdit' => TRUE
        ],
        'warehouse' => [
            'select' => 'w.shortName',
            'display' => 'WHS',
            'ddField' => 'shortName',
            'searcherDD' => 'warehouses',
            'noEdit' => TRUE,
        ],
        'orderStatus' => [
            'select' => 'CASE 
                            WHEN s.displayName IS NULL THEN "Open" 
                            ELSE s.displayName 
                        END',
            'display' => 'Order Status',
            'noEdit' => TRUE
        ],
    ];

    public $where = '(s.shortName != "SHCO" AND s.shortname != "CNCL"
                        OR s.id IS NULL) AND scanordernumber is NOT NULL';

    public $orderBy = ' vendorName,
                        DATE(dateCreated) DESC';

    /*
    ****************************************************************************
    */
}