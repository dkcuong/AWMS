<?php

namespace tables\logs;

class cartons extends \tables\_default
{
    public $primaryKey = 'lv.id';

    public $ajaxModel = 'logs\\cartons';

    public $where = 'lf.category = "cartons"
            AND tableName != ""
            ';

    public $mainTable = 'logs_cartons';

    public $mainField = 'lv.id';

    /*
    ****************************************************************************
    */

    function fields()
    {
        $fields = [
            'logTime' => [
                'display' => 'Log Time',
                'searcherDate' => TRUE,
                'noEdit' => TRUE,
            ],
        ];

        $fields += \access::isClient($this->app) ? [
            'vendor' => [
                'select' => 'CONCAT(w.shortName, "_", vendorName)',
                'display' => 'Client Name',
                'noEdit' => TRUE,
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            ],
        ] : [
            'userID' => [
                'select' => 'u.username',
                'display' => 'Username',
                'searcherDD' => 'users',
                'ddField' => 'u.username',
                'noEdit' => TRUE,
            ],
        ];

        $fields += [
            'ucc128' => [
                'select' => 'CONCAT(co.vendorID,
                                b.id,
                                LPAD(ca.uom, 3, 0),
                                LPAD(ca.cartonID, 4, 0)
                            )',
                'display' => 'UCC128',
                'customClause' => TRUE,
                'noEdit' => TRUE,
                'acDisabled' => TRUE,
            ],
            'vendor' => [
                'display' => 'Client',
                'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
                'noEdit' => TRUE,
            ],
            'upc' => [
                'display' => 'UPC',
                'noEdit' => TRUE,
            ],
            'sku' => [
                'select' => 'p.sku',
                'display' => 'SKU',
                'noEdit' => TRUE,
            ],
            'color' => [
                'display' => 'Color',
                'noEdit' => TRUE,
            ],
            'size' => [
                'display' => 'Size',
                'noEdit' => TRUE,
            ],
            'target' => [
                'select' => 'CASE lf.displayName
                                WHEN "locID" THEN "Location Name"
                                WHEN "plate" THEN "License Plate"
                                WHEN "orderID" THEN "Order Number"
                                WHEN "statusID" THEN "Status"
                                WHEN "mStatusID" THEN "Manual Status"
                            END',
                'display' => 'Target',
            ],
            'fromValue' => [
                'select' => 'CASE lf.displayName
                                WHEN "locID" THEN fl.displayName
                                WHEN "plate" THEN fromValue
                                WHEN "orderID" THEN fn.scanordernumber
                                WHEN "statusID" THEN fs.shortName
                                WHEN "mStatusID" THEN fms.shortName
                            END',
                'display' => 'Initial Value',
            ],
            'toValue' => [
                'select' => 'CASE lf.displayName
                                WHEN "locID" THEN tl.displayName
                                WHEN "plate" THEN toValue
                                WHEN "orderID" THEN tn.scanordernumber
                                WHEN "statusID" THEN ts.shortName
                                WHEN "mStatusID" THEN tms.shortName
                            END',
                'display' => 'New Value',
            ]
        ];

        return $fields;
    }

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'logs_values lv
                JOIN      logs_cartons lc ON lc.id = lv.logID
                LEFT JOIN '.$userDB.'.info u ON u.id = lc.userID
                JOIN      logs_fields lf ON lf.id = lv.fieldID
                JOIN      inventory_cartons ca ON ca.id = lv.primeKey
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      upcs p ON p.id = b.upcID
                JOIN      vendors v ON v.id = co.vendorID
                JOIN      warehouses w ON w.id = v.warehouseID
                LEFT JOIN locations fl ON fl.id = lv.fromValue
                LEFT JOIN locations tl ON tl.id = lv.toValue
                LEFT JOIN neworder fn ON fn.id = lv.fromValue
                LEFT JOIN neworder tn ON tn.id = lv.toValue
                LEFT JOIN statuses fs ON fs.id = lv.fromValue
                LEFT JOIN statuses ts ON ts.id = lv.toValue
                LEFT JOIN statuses fms ON fms.id = lv.fromValue
                LEFT JOIN statuses tms ON tms.id = lv.toValue
                ';
    }

    /*
    ****************************************************************************
    */

}
