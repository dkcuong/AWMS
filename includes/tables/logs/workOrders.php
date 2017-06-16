<?php

namespace tables\logs;

class workOrders extends \tables\_default
{
    public $primaryKey = 'wh.wo_id';

    public $ajaxModel = 'logs\\workOrders';

    public $fields = [
        'logTime' => [
            'display' => 'Log Time',
            'searcherDate' => TRUE,
            'noEdit' => TRUE,
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'noEdit' => TRUE,
        ],
        'wo_num' => [
            'display' => 'Work Order #',
            'noEdit' => TRUE,
        ],
        'vendor' => [
            'display' => 'Client',
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'noEdit' => TRUE,
        ],
        'scanordernumber' => [
            'display' => 'Scan Order #',
            'noEdit' => TRUE,
        ],
        'rqst_dt' => [
            'display' => 'Request Date',
            'searcherDate' => TRUE,
            'noEdit' => TRUE,
        ],
        'comp_dt' => [
            'display' => 'Complete Date',
            'searcherDate' => TRUE,
            'noEdit' => TRUE,
        ],
        'target' => [
            'select' => '"Status"',
            'display' => 'Target',
        ],
        'fromValue' => [
            'display' => 'Initial Value',
        ],
        'toValue' => [
            'display' => 'New Value',
        ]
    ];

    public $mainField = 'wh.wo_id';

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'wo_hdr AS wh
            JOIN (
                SELECT    wo_id,
                          NULL AS fromValue,
                          "' . \common\workOrders::STATUS_CHECK_IN . '" AS toValue,
                          create_by AS userID,
                          create_dt AS logTime
                FROM      wo_hdr wh
                WHERE     sts != "d"
            UNION
                SELECT    wo_id,
                          "' . \common\workOrders::STATUS_CHECK_IN . '" AS toValue,
                          "' . \common\workOrders::STATUS_CHECK_OUT . '" AS toValue,
                          update_by AS userID,
                          update_dt AS logTime
                FROM      wo_hdr wh
                WHERE     sts = "u"
            ) AS w ON w.wo_id = wh.wo_id
            JOIN      neworder n ON n.scanordernumber = wh.scn_ord_num
            JOIN      order_batches b ON b.id = n.order_batch
            LEFT JOIN ' . $userDB . '.info u ON u.id = w.userID
            JOIN      vendors v ON v.id = b.vendorID
            JOIN      warehouses w ON w.id = v.warehouseID
            ';
    }

    /*
    ****************************************************************************
    */

}
