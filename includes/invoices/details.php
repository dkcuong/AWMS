<?php

namespace invoices;

class details
{

    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        $this->app = $app;
    }

    /*
    ****************************************************************************
    */

    function get($invoiceNo)
    {
        $sql = 'SELECT   inv_id,
                         chg_cd_desc,
                         chg_cd_qty,
                         d.chg_cd_uom,
                         chg_cd_price,
                         chg_cd_cur,
                         chg_cd_amt,
                         chg_cd_type
                FROM     invoice_dtls d
                JOIN     charge_cd_mstr ch ON ch.chg_cd_id = d.chg_cd_id
                WHERE    inv_num = ?';

        $results = $this->app->queryResults($sql, [$invoiceNo]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getCancellingData($invoiceNo)
    {
        $sql = 'SELECT   inv_id,
                         wh_id,
                         cust_id,
                         chg_cd_id,
                         chg_cd_desc,
                         chg_cd_qty,
                         chg_cd_uom,
                         chg_cd_price,
                         chg_cd_cur,
                         -1 * chg_cd_amt AS chg_cd_amt
                FROM     invoice_dtls
                WHERE    inv_num = ?';

        $results = $this->app->queryResults($sql, [$invoiceNo]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkIfExists($invoiceNo)
    {
        $sql = 'SELECT   inv_id
                FROM     invoice_dtls
                WHERE    inv_num = ?
                LIMIT 1';

        $result = $this->app->queryResult($sql, [$invoiceNo]);

        return $result != [];
    }

    /*
    ****************************************************************************
    */

}
