<?php

namespace tables\onlineOrders;

class transferQuery extends \tables\_default
{

    static $storeCartons = [];

    public $app;
    public $isImport;

    private $vendorID;

    /*
    ****************************************************************************
    */

    public function __construct($params)
    {
        $this->app = $params['app'];
        $this->vendorID = $params['vendorID'];
        $this->isImport = $params['isImport'];
    }

    /*
    ****************************************************************************
    */

    public function getCartonList($upc, $uom, $limit, $rackID)
    {
        if (! isset(self::$storeCartons[$upc][$uom][$limit])) {
            
            
            $sql = '-- getCartonList
                    SELECT  ca.id AS cartonID,
                            ca.id,
                            uom,
                            locID,
                            mLocID,
                            upc,
                            batchID
                    FROM inventory_cartons ca
                    JOIN inventory_batches b ON ca.batchID = b.id
                    JOIN inventory_containers co ON co.recNum = b.recNum
                    JOIN upcs u ON b.upcID = u.id
                    JOIN locations l ON l.id = locID
                    WHERE NOT isMezzanine
                    AND statusID = ?
                    AND statusID = mStatusID
                    AND upc = ?
                    AND uom = ?
                    AND co.vendorID = ?
                    AND NOT isSplit
                    AND NOT unSplit
                    LIMIT ' . intVal($limit);

            $result = $this->app->queryResults($sql, [
                $rackID,
                $upc,
                $uom,
                $this->vendorID
            ]);

            self::$storeCartons[$upc][$uom][$limit] = $result;
        }

        return self::$storeCartons[$upc][$uom][$limit];
    }

    /*
    ****************************************************************************
    */

    public function transferCarton($locID, $id)
    {
        if ($this->isImport) {
            return;
        }

        $sql = 'UPDATE inventory_cartons
                SET    locID = ?,
                       mLocID = ?
                WHERE  id = ?';

        $this->app->runQuery($sql, [$locID, $locID, $id]);
    }

    /*
    ****************************************************************************
    */

    public function insertTransfer($userID, $nextID)
    {
        if ($this->isImport) {
            return;
        }

        $sql = 'INSERT INTO transfers (
                  userID,
                  barcode
                ) VALUE (
                  ?, LEFT(MD5(?), 20)
                )';

        $this->app->runQuery($sql, [$userID, $nextID]);

        return $this->getNextID('transfers');
    }

    /*
    ****************************************************************************
    */

    public function insertTransferItems($params)
    {
        if ($this->isImport) {
            return;
        }

        // save transferItem
        $sql = 'INSERT INTO transfer_items (
                        transferID,
                        vendorID,
                        upcID,
                        pieces,
                        locationID,
                        source_loc_id
                    ) VALUE (?, ?, ?, ?, ?, ?)';

        $this->app->runQuery($sql, [
            $params['transferID'],
            $params['vendorID'],
            $params['upcID'],
            $params['pieces'],
            $params['locationID'],
            $params['sourceLocID']
        ]);
    }

    /*
    ****************************************************************************
    */

    public function insertTransferCartons($params)
    {
        if ($this->isImport) {
            return;
        }

        $sql = 'INSERT INTO transfer_cartons (
                          transferItemID,
                          cartonID,
                          fromLocID
                      ) VALUE (?, ?, ?)';

        $this->app->runQuery($sql, [
            $params['transferItemID'],
            $params['cartonID'],
            $params['locID']
        ]);
    }

    /*
    ****************************************************************************
    */
}