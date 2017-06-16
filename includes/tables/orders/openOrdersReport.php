<?php

namespace tables\orders;

class openOrdersReport extends \tables\_default
{
    public $ajaxModel = 'orders\\openOrdersReport';

    public $primaryKey = 'n.id';

    public $fields = [
        'warehouse' => [
            'select' => 'w.shortName',
            'display' => 'WHS',
            'ddField' => 'shortName',
            'searcherDD' => 'warehouses',
            'noEdit' => TRUE,
        ],
        'vendorName' => [
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'display' => 'CLNT NM',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'noEdit' => TRUE,
        ],
        'scanOrderNumber' => [
            'display' => 'ORD',
            'noEdit' => TRUE,
        ],
        'customerOrderNumber' => [
            'display' => 'CUST ORD',
            'noEdit' => TRUE,
        ],
        'status_id' => [
            'select' => 's.displayName',
            'display' => 'STS',
            'searcherDD' => 'statuses\\openOrders',
            'ddField' => 'displayName',
            'update' => 'oos.status_id',
            'customUpdate' => 'openStatusID',
        ],
        'updated' => [
            'display' => 'UPD DATE',
            'noEdit' => TRUE,
        ],
        'updateUserID' => [
            'select' => 'uu.username',
            'display' => 'UPD CSR',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'noEdit' => TRUE,
        ],
        'typeName' => [
            'display' => 'ORD TYPE',
            'noEdit' => TRUE,
        ],
        'userName' => [
            'select' => 'u.userName',
            'display' => 'CSR Name',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'noEdit' => TRUE,
        ],
        'createDate' => [
            'display' => 'Create DT',
            'noEdit' => TRUE,
            'searcherDate' => TRUE,
        ],
        'skuCount' => [
            'display' => 'SKU',
            'noEdit' => TRUE,
        ],
        'palletCount' => [
            'display' => 'PLT',
            'noEdit' => TRUE,
        ],
        'cartonCount' => [
            'display' => 'CTN',
            'noEdit' => TRUE,
        ],
        'pieceCount' => [
            'display' => 'PCS',
            'noEdit' => TRUE,
        ],
        'cancelDate' => [
            'display' => 'Cancel DT',
            'noEdit' => TRUE,
            'searcherDate' => TRUE,
        ],
    ];

    public $where = '(s.shortName IS NULL
        OR s.shortName = "OP")';

    public $orderBy = '
        cancelDate ASC,
        skuCount DESC,
        cartonCount DESC,
        palletCount DESC,
        pieceCount DESC,
        vendorName ASC
        ';

    const FROM_DATE = '2016-06-29';

    const DAYS_ADD = 7;

    const EXCLUDE_VENDORS = [
        'Elite Brands',
    ];

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        $unionFields = '
            n.id,
            DATE(create_dt) AS createDate,
            COUNT(DISTINCT sku) AS skuCount,
            COUNT(DISTINCT plate) AS palletCount,
            COUNT(DISTINCT ca.id) AS cartonCount,
            SUM(UOM) AS pieceCount
            ';

        $unionFrom = 'neworder n
            JOIN      order_batches ba ON ba.id = n.order_batch
            JOIN      vendors v ON v.id = ba.vendorID
            JOIN      statuses s ON s.id = n.statusID
            ';

        $unionJoin = '
            JOIN      inventory_batches b ON b.id = ca.batchID
            JOIN      upcs u ON u.id = b.upcID
            ';

        $exclude = self::EXCLUDE_VENDORS ? '
            AND       v.id NOT IN ("' . implode('", "', self::EXCLUDE_VENDORS) . '")
            ' : NULL;

        $localtime = time();

        $currentDate = date('Y-m-d', $localtime);

        $dateAdded = strtotime('+' . (self::DAYS_ADD - 1) . ' day',
                strtotime($currentDate));

        $toDate = date('Y-m-d', $dateAdded);

        $dateClause = self::FROM_DATE ?
            'BETWEEN "' . self::FROM_DATE . '" AND "' . $currentDate . '"' :
            'IS NULL OR DATE(create_dt) <= "' . $currentDate . '"';

        $unionWhere = '
            AND       (DATE(create_dt) ' . $dateClause . ')
            AND       cancelDate <= "' . $toDate . '"
            AND       NOT isSplit
            AND       NOT unSplit
            ' . $exclude;

        $unionGroupBy = '
            n.id
            ';

        $notProcessed =
                \tables\orders::getProcessedOrdersStatuses('orderNotProcessed');
        $processed = \tables\orders::getProcessedOrdersStatuses();

        $statusList = [
            'processed' => '"' . implode('", "', $processed) . '"',
            'notProcessed' => '"' . implode('", "', $notProcessed) . '"',
        ];

        return 'neworder n
            JOIN      order_batches b ON b.id = n.order_batch
            JOIN      vendors v ON v.id = b.vendorID
            JOIN      warehouses w ON v.warehouseID = w.id
            JOIN      order_types ot ON ot.id = n.type
            JOIN      ' . $userDB . '.info u ON u.id = n.userID
            LEFT JOIN open_orders_statuses oos ON oos.ord_id = n.id
            LEFT JOIN statuses s ON s.id = oos.status_id
            LEFT JOIN ' . $userDB . '.info uu ON uu.id = oos.user_id
            JOIN (
                    SELECT    ' . $unionFields . '
                    FROM      ' . $unionFrom . '
                    JOIN      (
                        SELECT    cartonID,
                                  orderID
                        FROM      pick_cartons
                        WHERE     active
                        GROUP BY  cartonID
                    ) pc ON pc.orderID = n.ID
                    JOIN      inventory_cartons ca ON ca.id = pc.cartonID
                    ' . $unionJoin . '
                    WHERE     s.shortName IN (' . $statusList['notProcessed'] . ')
                    ' . $unionWhere . '
                    GROUP BY  ' . $unionGroupBy . '
                UNION
                    SELECT    ' . $unionFields . '
                    FROM      ' . $unionFrom . '
                    JOIN      inventory_cartons ca ON ca.orderID = n.id
                    ' . $unionJoin . '
                    WHERE     s.shortName IN (' . $statusList['processed'] . ')
                    ' . $unionWhere . '
                    GROUP BY  ' . $unionGroupBy . '
            ) AS a ON a.id = n.id
            ';
    }

    /*
    ****************************************************************************
    */

    function customUpdate($data)
    {
        $queryValue = $data['queryValue'];
        $rowID = $data['rowID'];

        $userID = \access::getUserID();

        $sql = 'INSERT INTO open_orders_statuses (
                    ord_id,
                    status_id,
                    user_id
                ) VALUES (
                    ?, ?, ?
                )
                ON DUPLICATE KEY UPDATE
                    status_id = ?,
                    user_id = ?';

        $this->app->runQuery($sql, [
            $rowID,
            $queryValue,
            $userID,
            $queryValue,
            $userID,
        ]);
    }

    /*
    ****************************************************************************
    */

}