<?php

namespace tables\warehouseTransfers;

class warehouseTransfers extends \tables\_default
{
    public $displaySingle = 'Warehouse Transfers';

    public $primaryKey = 'wt.id';

    public $ajaxModel = 'warehouseTransfers\\warehouseTransfers';

    public $fields = [
        'transferDate' => [
            'display' => 'Transfer Date',
            'searcherDate' => TRUE,
        ],
        'description' => [
            'display' => 'Description',
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'update' => 'wt.userID',
            'noEdit' => TRUE,
        ],
        'outWarehouseID' => [
            'select' => 'ow.displayName',
            'display' => 'Out Warehouse',
            'searcherDD' => 'warehouses',
            'ddField' => 'displayName',
            'update' => 'wt.outWarehouseID',
        ],
        'inWarehouseID' => [
            'select' => 'iw.displayName',
            'display' => 'In Warehouse',
            'searcherDD' => 'warehouses',
            'ddField' => 'displayName',
            'update' => 'wt.inWarehouseID',
        ],
    ];

    public $customAddRows = TRUE;

    public $customInsert = 'warehouseTransfers\\warehouseTransfers';

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'warehouse_transfers wt
            JOIN      '.$userDB.'.info u ON u.id = wt.userID
            JOIN      warehouses ow ON ow.id = wt.outWarehouseID
            JOIN      warehouses iw ON iw.id = wt.inWarehouseID
            ';
    }

    /*
    ****************************************************************************
    */

    function customInsert($post)
    {
        if ($post['outWarehouseID'] == $post['inWarehouseID']) {
            die('Warehouses cannot match');
        } elseif($post['transferDate']) {

            $date = \DateTime::createFromFormat('Y-m-d', $post['transferDate']);

            if (! $date || $date->format('Y-m-d') != $post['transferDate']) {
                die('Warehouses Transfer date is invalid');
            }
        }

        $sql = 'INSERT INTO warehouse_transfers (
                    transferDate,
                    description,
                    userID,
                    outWarehouseID,
                    inWarehouseID
                ) VALUES (
                    ?, ?, ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    userID = VALUES(userID)';

        $ajaxRequest = TRUE;

        $param = [
            $post['transferDate'],
            $post['description'],
            \access::getUserID(),
            $post['outWarehouseID'],
            $post['inWarehouseID'],
        ];

        $this->app->runQuery($sql, $param, $ajaxRequest);
    }

    /*
    ****************************************************************************
    */

    function getWarehouseIDs($warehouseTransferID)
    {
        if (! $warehouseTransferID) {
            return NULL;
        }

        $sql = 'SELECT    outWarehouseID,
                          inWarehouseID
                FROM      warehouse_transfers
                WHERE     id = ?';

        $result = $this->app->queryResult($sql, [$warehouseTransferID]);

        return $result;
    }

    /*
    ****************************************************************************
    */

}