<?php

namespace tables\packingSlip;

class packingSlip extends \tables\_default
{
    public $ajaxModel = 'packingSlip\\packingSlip';

    public $primaryKey = 'oo.id';

    public $fields = [
        'batch' => [
            'select' => 'b.id',
            'display' => 'Batch Order',
            'noEdit' => TRUE,
        ],
        'SCAN_SELDAT_ORDER_NUMBER' => [
            'display' => 'Order Number',
            'noEdit' => TRUE,
        ],
        'reference_id' => [
            'select' => 'TRIM(
                            IF(INSTR(reference_id, "-") > 0,
                               LEFT(reference_id, INSTR(reference_id, "-") - 1),
                               reference_id
                            )
                        )',
            'display' => 'Order ID',
            'noEdit' => TRUE,
        ],
        'fullName' => [
            'select' => 'CONCAT_WS(" ", first_name,
                            last_name)',
            'display' => 'Name',
            'noEdit' => TRUE,
        ],
        'address' => [
            'select' => 'CONCAT_WS(" ", shipping_address_street,
                            shipping_address_street_cont, shipping_city,
                            shipping_state, shipping_postal_code, shipping_country_name
                        )',
            'display' => 'Address',
            'noEdit' => TRUE,
        ],
        'customer_phone_number' => [
            'display' => 'Phone',
            'noEdit' => TRUE,
        ],
        'product_sku' => [
            'select' => 'IF(u.id, u.sku, "Not Found")',
            'display' => 'SKU',
            'noEdit' => TRUE,
        ],
        'upcID' => [
            'select' => 'IF(u.id, u.id, "Not Found")',
            'display' => 'UPC ID',
            'noEdit' => TRUE,
        ],
        'upc' => [
            'select' => 'IF(u.id, u.upc, "Not Found")',
            'display' => 'UPC',
        ],
        'product_description' => [
            'select' => 'CONCAT_WS(" ", product_name, product_description)',
            'display' => 'Description',
        ],
        'product_quantity' => [
            'select' => 'SUM(product_quantity)',
            'display' => 'Quantity',
            'groupedFields' => 'product_quantity',
        ],
    ];

    public $table = 'order_batches b
        JOIN      neworder n ON n.order_batch = b.id
        JOIN      online_orders oo
            ON oo.SCAN_SELDAT_ORDER_NUMBER = n.scanordernumber
        LEFT JOIN upcs u ON u.upc = oo.upc';

    public $where = 'b.id = n.order_batch';

    public $groupBy = 'SCAN_SELDAT_ORDER_NUMBER,
                       oo.upc';

    /*
    ****************************************************************************
    */

}