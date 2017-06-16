<?php

namespace tables\invoices;

class issueInvoice extends \tables\_default
{
    public $ajaxModel = 'invoices\\issueInvoice';

    public $primaryKey = 'id.cust_id';

    public $fields = [
        'chg_cd' => [
            'select' => 'chg_cd',
            'display' => 'ITEM',
        ],
        'chg_cd_des' => [
            'select' => 'id.chg_cd_desc',
            'display' => 'DESC',
        ],
        'qty' => [
            'select' => 'chg_cd_qty',
            'display' => 'QTY',
        ],
        'chg_cd_uom' => [
            'select' => 'id.chg_cd_uom',
            'display' => 'UOM',
        ],
        'chg_cd_price' => [
            'select' => 'id.chg_cd_price',
            'display' => 'PRICE',
        ],
        'amt' => [
            'select' => 'CONCAT(id.chg_cd_cur," ",chg_cd_amt)',
            'display' => 'AMT',
        ]
    ];

    public $table = 'invoice_dtls id
            JOIN charge_cd_mstr chm ON chm.chg_cd_id = id.chg_cd_id
            ';

    public $where = 'id.sts != "d"';

    public $groupBy = 'id.cust_id,
                       id.chg_cd_id';

    /*
    ****************************************************************************
    */

}
