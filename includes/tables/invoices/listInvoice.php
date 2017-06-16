<?php

namespace tables\invoices;

class listInvoice extends \tables\_default 
{
    public $ajaxModel = 'invoices\\listInvoice';

    public $primaryKey = 'ind.inv_id';
    
    public $fields = [
        'status' => [
            'select' => 's.displayName',
            'display' => 'INV STS',
            'searcherDD' => 'statuses\\invoice',
            'ddField' => 'displayName',
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'CLIENT',
            'ignore' => TRUE,
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'update' => 'co.vendorID',
        ],
        'inv_num' => [
            'select' => 'inh.inv_num',
            'display' => 'INV NBR',
        ],
        'create_dt' => [
            'select' => 'inh.create_dt',
            'display' => 'INV DT',
            'ignore' => TRUE,
            'searcherDate' => TRUE,
        ],
        'total_amt' => [
            'select' =>  'CONCAT(chg_cd_cur," ",CAST(
                            ins.inv_net_ord - ins.inv_dis 
                            + ins.inv_freight + ins.inv_tax
                            AS DECIMAL(10,2)
                         ))',
            'display' => 'TOTAL',
         ],
        'paidReceived' => [
           'select' => 'inh.inv_paid_dt',
           'display' => 'PMNT RCV DT',
           'ignore' => TRUE,
           'searcherDate' => TRUE,
        ],
        'chequeNo' => [
            'select' => 'ins.check_num',
            'display' => 'CHECK NBR',
        ]
    ];

    public $table = 'invoice_dtls ind
           JOIN invoice_sum ins ON ins.inv_num = ind.inv_num
           JOIN invoice_hdr inh ON inh.inv_num = ind.inv_num
           JOIN vendors v ON v.id = ind.cust_id
           JOIN warehouses w ON v.warehouseID = ind.wh_id
           JOIN statuses s ON s.id = inh.inv_sts
       ';
        
    public $groupBy = 'ind.inv_num,ind.cust_id';
}
