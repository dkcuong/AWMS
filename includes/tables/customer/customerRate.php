<?php

namespace tables\customer;

class customerRate extends \tables\_default{

    public $ajaxModel = 'customer\\customerRate';

    public $primaryKey = 'ic.inv_cost_id';

    public $fields = [
        'chg_cd_type' => [
            'select' => 'chg_cd_type',
            'display' => 'CHRG TYPE',
        ],
        'chg_cd_cur' => [
            'select' => 'chg_cd_cur',
            'display' => 'CUR',
        ],
        'chg_cd_price' => [
            'select' => 'chg_cd_price',
            'display' => 'PRICE',
        ],
        'chg_cd' => [
            'select' => 'chg_cd',
            'display' => 'ITEM',
        ],
        'chg_cd_des' => [
            'select' => 'chg_cd_des',
            'display' => 'DESC',
        ]
    ];

    public $table = 'invoice_cost ic
            JOIN      charge_cd_mstr chm ON chm.chg_cd_id = ic.chg_cd_id
            ';

    /*
    ****************************************************************************
    */

    function getCosts($chargeCode, $vendorID)
    {
        $sql = 'SELECT    chg_cd_price
                FROM      ' . $this->table . '
                WHERE     chg_cd = ?
                AND       cust_id = ?
                AND       chg_cd_sts = "active"
                ';

        $result = $this->app->queryResult($sql, [$chargeCode, $vendorID]);

        return $result['chg_cd_price'];
    }

    /*
    ****************************************************************************
    */

}
