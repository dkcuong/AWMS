<?php

namespace tables\orders;

class shippingInfo extends \tables\_default
{
    public $ajaxModel = 'orders\\shippingInfo';

    public $primaryKey = 'osi.id';

    public $fields = [
        'vendor' => [
            'display' => 'Client',
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'noEdit' => TRUE,
        ],
        'scanOrderNumber' => [
            'display' => 'Order Number',
            'noEdit' => TRUE,
        ],
        'transMC' => [
            'length' => 1,
            'format' => 'Letter',
            'display' => 'Transportation Method Code'
        ],
        'scac' => [
            'length' => 4,
            'format' => 'Letter',
            'display' => 'SCAC'
        ],
        'proNumber' => [
            'length' => 8,
            'format' => 'Numeric',
            'display' => 'Pro Number',
            'isNum' => 8,
        ],
        'shipType' => [
            'length' => 2,
            'format' => 'Letter',
            'display' => 'Shipment Type'
        ],
        'trailerNumber' => [
            'length' => 8,
            'format' => 'Alpha-Numeric',
            'display' => 'Trailer Number'
        ]
    ];

    public $table = 'orders_shipping_info osi
        JOIN      neworder n ON n.id = osi.newOrderID
        JOIN      order_batches b ON b.id = n.order_batch 
        JOIN      vendors v ON v.id = b.vendorID
        JOIN      warehouses w ON v.warehouseID = w.id
        ';

    /*
    ****************************************************************************
    */

}
