<?php

class workOrders extends \tables\_default
{
    public $ajaxModel = 'invoices\\workorders';

    public $primaryKey = 'wh.wo_id';

    public $fields = [
        'workNbr' => [
            'display' => 'W/O NBR',
            'select' => 'wo_num',
        ],
        'clientWoNbr' => [
            'display' => 'CLIENT W/O NBR',
            'select' => 'client_wo_num',
        ],
        'ordernumber' => [
            'display' => 'SCAN ORD NBR',
            'select' => 'scanordernumber',
        ],
        'shipDate' => [
            'display' => 'SHIP DT',
            'select' => 'ship_dt',
            'searcherDate' => TRUE,
        ],
        'chargeCode' => [
            'display' => 'CHARGE CODE',
            'select' => 'chg_cd',
        ],
        'qty' => [
            'display' => 'QTY',
            'select' => 'qty',
        ],
        'workdetails' => [
            'display' => 'WORK NOTES',
            'select' => 'wo_dtl',
        ]
    ];

     public $table = 'wo_hdr wh
        JOIN      wo_dtls wd ON wd.wo_id = wh.wo_id
        JOIN      neworder n ON n.scanordernumber = wd.scn_ord_num
        JOIN      order_batches b ON b.id = n.order_batch
        JOIN      charge_cd_mstr ch ON ch.chg_cd_id = wd.chg_cd_id
        JOIN      vendors v ON v.id = b.vendorID
        JOIN      warehouses w ON v.warehouseID = w.id
        ';

    public $groupBy = 'wo_dtl_id';

    /*
    ****************************************************************************
    */

}
