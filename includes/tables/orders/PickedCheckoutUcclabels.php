<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 11/04/2017
 * Time: 14:48
 */

namespace tables\orders;

class PickedCheckoutUcclabels extends \tables\_default
{
    static $labelTitle = 'UCC Label';
    static $labelsTitle = 'UCC Labels';
    public $ajaxModel = 'orders\\PickedCheckoutUcclabels';
    public $where = 'os.shortName IN (
                                        "WMCO",
                                        "PKCI",
                                        "PKCO",
                                        "OPCI",
                                        "OPCO",
                                        "LSCI",
                                        "SHCO"
                                       )';
    public $groupBy = 'o.ID';

    public $fields = [
        'ordernum' => [
            'display' => 'Order Number',
            'select' => 'scanordernumber',
            'required' => TRUE,
        ],
        'vendor' => [
            'display' => 'Client',
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'update' => 'b.vendorID',
        ],
        'po' => [
            'display' => 'PO #',
            'select' => 'customerordernumber',
            'required' => TRUE,
        ],
        'statusID' => [
            'select' => 'os.shortName',
            'display' => 'Status',
            'noEdit' => TRUE
        ],
        'username' => [
            'select' => 'u.username',
            'display' => 'Username',
        ],
        'edi' => [
            'display' => 'Created Type',
            'select' => 'IF(o.edi, "EDI", "User")',
            'isNum' => 20,
            'noEdit' => TRUE,
        ],
        'isPrintUccEdi' => [
            'display' => 'UCC Print Type',
            'select' => 'IF(o.edi AND o.isPrintUccEdi, "LINGO", "AWMS")',
            'isNum' => 20,
            'noEdit' => TRUE,
        ],
    ];

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'neworder o
            JOIN      order_batches b ON b.id = o.order_batch
            JOIN      vendors v ON v.id = b.vendorID
            LEFT JOIN ' . $userDB . '.info u ON o.userID = u.id
            LEFT JOIN company_address a ON o.location = a.id
            JOIN      warehouses w ON v.warehouseID = w.id
            LEFT JOIN online_orders oo
                ON   o.scanOrderNumber = oo.SCAN_SELDAT_ORDER_NUMBER
            LEFT JOIN statuses os ON o.statusID = os.id
            ';
    }
}