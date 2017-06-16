<?php

namespace tables\orders;

class shippingReports extends \tables\_default
{
    public $ajaxModel = 'orders\shippingReports';
        
    public $primaryKey = 'o.scanordernumber';

    public $fields = [
            'vendor' => [
                'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
                'display' => 'Client', 
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            ],
            'first_name' => [
                'display' => 'First Name', 
            ],
            'last_name' => [
                'display' => 'Last Name/Customer Name', 
            ],            
            'clientordernumber' => [
                'display' => 'Client Order Number',         
            ],
            'scanordernumber' => [
                'display' => 'Seldat Order Number', 
            ],
            'bolNumber' => [
                'select' => 'IF (bolNumber, bolNumber, "[No BOL]")',
                'display' => 'BOL',
            ],
            'startshipdate' => [
                'select' => 'IF (startshipdate IS NULL,
                                 DATE(order_date), startshipdate
                            )',
                'display' => 'Start Date', 
                'searcherDate' => TRUE,
            ],
            'canceldate' => [
                'display' => 'Cancel Date', 
                'searcherDate' => TRUE,
            ],
            'numberofcarton' => [
                'select' => 'COUNT(ca.id)',
                'display' => 'Number of Cartons', 
                'groupedFields' => 'product_quantity',
            ],
            'numberofpiece' => [
                'select' => 'SUM(uom)',
                'display' => 'Number of Pieces', 
                'groupedFields' => 'product_quantity, uom',
            ],
            'sku' => [
                'display' => 'SKU', 
            ],
            'color' => [
                'display' => 'Color', 
            ],
            'size' => [
                'display' => 'Size', 
            ],
        ];

    public $table = 'neworder o 
            LEFT JOIN online_orders oo ON oo.SCAN_SELDAT_ORDER_NUMBER = o.scanordernumber
            JOIN      order_batches ob ON ob.id = o.order_batch 
            JOIN      vendors v ON v.id = ob.vendorID
            JOIN      warehouses w ON v.warehouseID = w.id
            JOIN      inventory_cartons ca ON ca.orderID = o.id
            JOIN      inventory_batches b ON b.id = ca.batchID
            JOIN      statuses s ON s.id = ca.statusID
            JOIN      upcs p ON p.id = b.upcID
            ';

    public $where = 'NOT isSplit
            AND       NOT unSplit
            AND       s.shortName = "SH"';

    public $groupBy = 'o.scanordernumber, 
                       p.upc';
    
    /*
    ****************************************************************************
    */
    
}

