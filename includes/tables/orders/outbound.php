<?php

namespace tables\orders;

class outbound extends \tables\_default
{
    public $ajaxModel = 'orders\\outbound';

    public $primaryKey = 'n.scanordernumber';

    public $groupBy = 'n.scanordernumber';

    public $xlsExportFileHandle = 'xlsExportFileHandle';

    public $backgroundColors = [
        'orange' => 'F1D2A7',
        'cyan' => '9CC2C3',
        'violet' => 'DCD7E6',
        'green' => 'B3DAB3',
        'darkGreen' => 'A9B7A9',
        'brown' => 'B3A27F',
        'red' => 'EC9C9C',
    ];

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'neworder n
            JOIN      order_batches b ON b.id = n.order_batch
            JOIN      vendors v ON v.id = b.vendorID
            JOIN      warehouses w ON v.warehouseID = w.id
            JOIN      statuses s ON s.id = n.statusID
            LEFT JOIN statuses ss ON ss.id = n.shippingStatusID
            LEFT JOIN ' . $userDB . '.info u ON n.userID = u.id
            LEFT JOIN ' . $userDB . '.info du ON n.dcUserID = du.id
            LEFT JOIN online_orders oo
                ON n.scanOrderNumber = oo.SCAN_SELDAT_ORDER_NUMBER
            LEFT JOIN inv_his_ord_prc ihop ON ihop.ord_id = n.id
            LEFT JOIN outbound_sum os ON os.ord_id = n.id
            LEFT JOIN invoice_hdr ih ON ih.inv_id = ihop.inv_id
            ';
    }

    /*
    ****************************************************************************
    */

    function fields()
    {
        $orderShippedStatus = \tables\orders::STATUS_SHIPPED_CHECK_OUT;
        $orderCancelledStatus = \tables\orders::STATUS_CANCELED;

        return [
            'userID' => [
                'select' => 'u.username',
                'display' => 'CSR Name',
                'searcherDD' => 'users',
                'ddField' => 'u.username',
                'backgroundColor' => 'orange',
            ],
            'warehouse' => [
                'select' => 'w.displayName',
                'display' => 'Warehouse',
                'ddField' => 'displayName',
                'searcherDD' => 'warehouses',
                'backgroundColor' => 'orange',
            ],
            'vendorName' => [
                'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
                'display' => 'Client Name',
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
                'backgroundColor' => 'orange',
            ],
            'customer' => [
                'select' => 'CONCAT_WS(first_name, last_name)',
                'display' => 'First Name',
                'backgroundColor' => 'orange',
            ],
            'customerordernumber' => [
                'display' => 'PO #',
                'backgroundColor' => 'orange',
            ],
            'clientOrderNumber' => [
                'select' => 'clientOrderNumber',
                'display' => 'Client Order Number',
                'backgroundColor' => 'orange',
                'maxLength' => 20,
            ],
            'scanordernumber' => [
                'display' => 'Order #',
                'backgroundColor' => 'orange',
            ],
            'isECOMM' => [
                'select' => 'IF(oo.id IS NULL, "No", "Yes")',
                'display' => 'ECOMM',
                'backgroundColor' => 'orange',
            ],
            'isVAS' => [
                'select' => 'IF(isVAS, "Yes", "No")',
                'display' => 'VAS',
                'backgroundColor' => 'orange',
            ],
            'status' => [
                'select' => 's.shortName',
                'display' => 'Status',
                'searcherDD' => 'statuses\\orders',
                'ddField' => 'shortName',
                'hintField' => 'displayName',
                'backgroundColor' => 'orange',
            ],
            'shippingCondition' => [
                'select' => 'ss.shortName',
                'display' => 'Condition',
                'searcherDD' => 'statuses\\shipping',
                'ddField' => 'shortName',
                'hintField' => 'displayName',
                'backgroundColor' => 'orange',
            ],
            'numberofcarton' => [
                'select' => 'IF (numberofcarton IS NULL,
                                 SUM(product_quantity), numberofcarton
                            )',
                'display' => '# OF CTNS',
                'groupedFields' => 'product_quantity',
                'backgroundColor' => 'orange',
            ],
            'numberofpiece' => [
                'select' => 'IF (numberofpiece IS NULL,
                                 SUM(product_quantity), numberofpiece
                            )',
                'display' => '# OF PCS',
                'groupedFields' => 'product_quantity',
                'backgroundColor' => 'orange',
            ],
            'startshipdate' => [
                'display' => 'Start Ship Date',
                'searcherDate' => TRUE,
                'backgroundColor' => 'orange',
            ],
            'canceldate' => [
                'display' => 'Cancel Date',
                'searcherDate' => TRUE,
                'backgroundColor' => 'orange',
            ],
            'daysLeft' => [
                'select' => '
                    CASE s.shortName
                        WHEN "' . $orderShippedStatus . '" THEN "Completed"
                        WHEN "' . $orderCancelledStatus . '" THEN "Cancelled"
                        ELSE CONCAT_WS (
                            " ",
                            ABS(DATEDIFF(canceldate, DATE(NOW()))),
                            CASE
                                WHEN DATEDIFF(DATE(canceldate), DATE(NOW())) > 0
                                    THEN "remain"
                                WHEN DATEDIFF(DATE(canceldate), DATE(NOW())) < 0
                                    THEN "overdue"
                                ELSE "0"
                            END
                        )
                    END',
                'display' => 'Days Left',
                'searcherDate' => TRUE,
                'backgroundColor' => 'orange',
            ],
            'dateOrderRequested' => [
                'select' => 'DATE(n.create_dt)',
                'display' => 'Dt. Ord. Req.',
                'searcherDate' => TRUE,
                'backgroundColor' => 'orange',
            ],
            'timeOrderRequested' => [
                'select' => 'TIME(n.create_dt)',
                'display' => 'Time Ord. Req.',
                'backgroundColor' => 'orange',
            ],
            'dateOrderCheckOut' => [
                'select' => 'os.chk_out_dt',
                'display' => 'Dt. Ord. Chk. Out',
                'searcherDate' => TRUE,
                'backgroundColor' => 'cyan',
            ],
            'datePickCheckOut' => [
                'select' => 'os.pick_out_dt',
                'display' => 'Dt. Pick Chk. Out',
                'searcherDate' => TRUE,
                'backgroundColor' => 'violet',
            ],
            'dcUserID' => [
                'select' => 'du.username',
                'display' => 'DC Person',
                'searcherDD' => 'users',
                'ddField' => 'username',
                'backgroundColor' => 'violet',
            ],
            'proc_out_dt' => [
                'select' => 'os.proc_out_dt',
                'display' => 'Ord. Prc. Chk. Out',
                'searcherDate' => TRUE,
                'backgroundColor' => 'violet',
            ],
            'dateShipCheckIn' => [
                'select' => 'os.ship_in_dt',
                'display' => 'Dt. Ship Chk. In',
                'searcherDate' => TRUE,
                'backgroundColor' => 'violet',
            ],
            'dateShipCheckOut' => [
                'select' => 'os.ship_out_dt',
                'display' => 'Dt. Ord. Shipped',
                'searcherDate' => TRUE,
                'backgroundColor' => 'violet',
            ],
            'daysOld' => [
                'select' => 'IF(s.shortName = "' . $orderShippedStatus . '",
                                "Completed", DATEDIFF(NOW(), DATE(n.create_dt))
                             )',
                'display' => 'Days Old',
                'backgroundColor' => 'green',
            ],
            'comments' => [
                'select' => 'ordernotes',
                'display' => 'Comments',
                'backgroundColor' => 'darkGreen',
            ],
            'invoiced' => [
                'select' => 'inv_num',
                'display' => 'Billing',
                'backgroundColor' => 'brown',
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function xlsExportFileHandle($data)
    {
        \excel\exporter::coloring($this, $data);
    }

    /*
    ****************************************************************************
    */

}

