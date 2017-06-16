<?php

namespace tables;

class invoicesRec extends _default
{
    public $ajaxModel = 'invoicesRec';

    public $primaryKey = 'i.id';
    
    public $fields = [
        'container' => [
            'display' => 'Container',
            'noEdit' => TRUE,
        ],
        'recNum' => [
           'display' => 'Receiving Number',
            'noEdit' => TRUE,
        ],
        'setDate' => [
            'select' => 'i.setDate',
            'display' => 'Set Date',
            'ignore' => TRUE,                        
            'searcherDate' => TRUE,
        ],
        'vendorID' => [
            'select' => 'v.vendorName',
            'display' => 'Client',
            'ignore' => TRUE,                        
            'searcherDD' => 'vendors',
            'ddField' => 'vendorName',
        ],
        'sku' => [
            'display' => 'SKU',
        ],
        'height' => [
            'display' => 'Height',
            'isNum' => 5,
            'isDecimal' => 2,
            'limitmax' => 60,
            'limitmin' => 4,
        ],
        'width' => [
            'display' => 'Width',
            'isNum' => 5,
            'isDecimal' => 2,
            'limitmax' => 48,
            'limitmin' => 4,
        ],
        'length' => [
            'display' => 'Length',
            'isNum' => 5,
            'isDecimal' => 2,
            'limitmax' => 48,
            'limitmin' => 4,
        ],
        'uom' => [
            'display' => 'UOM',
            'isNum' => 3,
        ],
        'prefix' => [
            'display' => 'Prefix',
        ],
        'suffix' => [
            'display' => 'Suffix',
        ],
        'type' => [
            'display' => 'Type',
            'select' => '"RC"',
        ],
        'totalCartons' => [
            'display' => 'Total Cartons',
            'isNum' => 'unl',
        ],
        'pieces' => [
            'display' => 'Pieces',
            'isNum' => 'unl',
        ],
        'volume' => [
            'select' => 'CAST(
                            CEIL(
                                height * length * width / 1728 * 4
                            ) / 4 AS DECIMAL(10, 2)
                        )',
            'display' => 'Volume',
            'isNum' => 8,
            'isDecimal' => 2,
        ],
        'rush' => [
            'display' => 'Rush',
        ],
        'invoiceNumber' => [
            'display' => 'Invoice Number',
            'isNum' => 7,
        ],
        'recUnits' => [
            'display' => 'Cost Units',
            'isNum' => 'unl',
            'isDecimal' => 2,
        ],
        'recCC' => [
            'display' => 'Cost Cart Close',
            'isNum' => 'unl',
            'isDecimal' => 2,
        ],
        'recCV' => [
            'display' => 'Cost Cart Vol',
            'isNum' => 'unl',
            'isDecimal' => 2,
        ],
        'recRush' => [
            'display' => 'Cost Rush',
            'isDecimal' => 2,
        ],
        'totalUnits' => [
            'display' => 'Total Units',
            'isNum' => 'unl',
        ],
        'totalCC' => [
            'display' => 'Total Cart Close',
            'isNum' => 'unl',
        ],
        'totalCV' => [
            'display' => 'Total Cart Vol',
            'isNum' => 'unl',
        ],
        'totalFreight' => [
            'display' => 'Total Freight',
            'isNum' => 'unl',
        ],
        'totalFP' => [
            'display' => 'Total Freight Pier',
            'isNum' => 'unl',
        ],
        'totalVT' => [
            'display' => 'Total Vat Tax',
            'isNum' => 'unl',
        ],
        'totalDT' => [
            'display' => 'Total Duty Tax',
            'isNum' => 'unl',
        ],
        'totalBF' => [
            'display' => 'Total Broke Fee',
            'isNum' => 'unl',
        ],
        'totalRush' => [
            'display' => 'Total Rush',
            'isNum' => 'unl',
        ],
        'totalSpec' => [
            'display' => 'Total Special',
            'isNum' => 'unl',
        ],
        'total' => [
            'display' => 'Total',
            'isNum' => 'unl',
        ],
        'invoiceStatus' => [
            'display' => 'Invoice Status',
            'searcherDD' => 'statuses\\inventory',
            'ddField' => 'shortName',
        ],
        'status' => [
            'select' => 's.displayName',
            'display' => 'Status',
            'searcherDD' => 'statuses\\invoice',
            'ddField' => 'displayName',
            'ddUpdatePrimary' => 's.id'
        ],
    ];
    
    public $table = 'invoice_receiving i
       LEFT JOIN vendors v ON v.id = i.vendorID
       LEFT JOIN statuses s ON s.id = i.statusID
       ';
    
    /*
    ****************************************************************************
    */
    
    function ajaxSource()
    {        
        return jsonLink('invoices');
    }

    /*
    ****************************************************************************
    */
}