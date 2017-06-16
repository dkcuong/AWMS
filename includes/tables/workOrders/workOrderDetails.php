<?php

namespace tables\workOrders;

class workOrderDetails extends \tables\_default
{
    public $ajaxModel = 'workOrders\\workOrderDetails';

    public $primaryKey = 'wo_dtl_id';

    public $fields = [
        'vendor' => [
            'select' => 'CONCAT(wa.shortName, "_", vendorName)',
            'display' => 'CLIENT',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'noEdit' => TRUE,
        ],
        'workordernumber' => [
            'display' => 'WO NBR',
            'select' => 'wo_num',
            'noEdit' => TRUE,
        ],
        'ordernumber' => [
            'display' => 'SCAN ORD NBR',
            'select' => 'scanordernumber',
            'noEdit' => TRUE,
        ],
        'shipdate' => [
            'display' => 'SHIP DT',
            'select' => 'ship_dt',
            'searcherDate' => TRUE,
        ],
        'requestdate' => [
            'display' => 'RQST DT',
            'select' => 'rqst_dt',
            'searcherDate' => TRUE,
        ],
        'completedate' => [
            'display' => 'COMP DT',
            'select' => 'comp_dt',
            'searcherDate' => TRUE,
        ],
        'requestby' => [
            'display' => 'RQST By',
             'select' => 'rqst_by',
            'searcherDate' => TRUE,
        ],
        'relatedtocustomer' => [
            'select' => 'IF(rlt_to_cust, "Yes", "No")',
            'display' => 'RELATED CUST',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'rlt_to_cust',
        ],
        'clientWoNbr' => [
            'display' => 'CLIENT W/O NBR',
            'select' => 'client_wo_num',
        ],
        'chargeCode' => [
            'display' => 'CHRG CD',
            'select' => 'chg_cd',
            'noEdit' => TRUE,
        ],
        'qty' => [
            'display' => 'QTY',
            'select' => 'qty',
            'isNum' => 6,
            'limitmin' => 1,
        ],
        'workdetails' => [
            'display' => 'WO NOTES',
            'select' => 'wo_dtl',
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'USER',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'noEdit' => TRUE,
        ],
    ];

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'wo_dtls wd
            JOIN      wo_hdr wh ON wh.wo_id = wd.wo_id
            JOIN      neworder n ON n.scanOrderNumber = wd.scn_ord_num
            JOIN      order_batches b ON b.id = n.order_batch
            JOIN      charge_cd_mstr ch ON ch.chg_cd_id = wd.chg_cd_id
            JOIN      '.$userDB.'.info u ON u.id = wd.create_by
            JOIN      vendors v ON v.id = b.vendorID
            JOIN      warehouses wa ON v.warehouseID = wa.id
            ';
    }

     /*
    ****************************************************************************
    */

    function updateLabor($data)
    {
        if (! getDefault($data['labor'])) {
            // Work Order Check-In may not have labour filled in
            return FALSE;
        }

        $userID = $data['userID'];
        $workOrderNumber = $data['workOrderNumber'];

        $deleteSql = '
            UPDATE    wo_dtls d
            JOIN      wo_hdr h ON h.wo_id = d.wo_id
            SET       d.sts = "d",
                      d.update_by = ?
            WHERE     wo_num = ?';

        $this->app->runQuery($deleteSql, [$userID, $workOrderNumber]);

        $insertSql = '
            INSERT INTO wo_dtls (
                wo_id,
                scn_ord_num,
                chg_cd_id,
                qty,
                create_by
            ) VALUES (
                ?, ?, ?, ?, ?
            ) ON DUPLICATE KEY UPDATE
                chg_cd_id = VALUES(chg_cd_id),
                qty = VALUES(qty),
                update_by = VALUES(create_by),
                sts = "u"';

        foreach ($data['labor'] as $chargeCodeID => $qty) {
            $this->app->runQuery($insertSql, [
                $data['workOrderID'],
                $data['scanOrderNumber'],
                $chargeCodeID,
                $qty,
                $userID,
            ]);
        }
    }

     /*
    ****************************************************************************
    */

}
