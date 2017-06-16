<?php

namespace tables\inventory;

class styleHistory extends \tables\_default
{
    public $ajaxModel = 'inventory\\styleHistory';

    public $primaryKey = 'shs.id';

    public $groupBy = 'shs.id';

    public $table = 'style_his_sum shs
        JOIN      vendors v ON v.id = shs.cust_id
        JOIN      warehouses w ON v.warehouseID = w.id
        ';

    /*
    ****************************************************************************
    */

    function fields()
    {
        return [
            'warehouse' => [
                'select' => 'w.displayName',
                'display' => 'Warehouse',
                'ddField' => 'displayName',
                'searcherDD' => 'warehouses',
                'backgroundColor' => 'orange',
            ],
            'vendorName' => [
                'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
                'display' => 'Client Name',
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
                'backgroundColor' => 'orange',
            ],
            'sku' => [
                'display' => 'Style Number',
            ],
            'ucc128' => [
                'display' => 'UCC128',
            ],
            'name' => [
                'display' => 'Container',
            ],
            'rcv_dt' => [
                'display' => 'Receiving Date',
                'searcherDate' => TRUE,
            ],
            'rack_dt' => [
                'display' => 'Rack Date',
                'searcherDate' => TRUE,
            ],
            'alloc_dt' => [
                'display' => 'Allocation Date',
                'searcherDate' => TRUE,
            ],
            'alloc_ord' => [
                'display' => 'Allocation Order',
            ],
            'ship_dt' => [
                'display' => 'Ship Date',
                'searcherDate' => TRUE,
            ],
            'ship_ord' => [
                'display' => 'Ship Order',
            ],
        ];
    }

    public $where = 'alloc_ord';

    /*
    ****************************************************************************
    */

}

