<?php

namespace tables\onlineOrders;

use tables\inventory\cartons;
use tables\minMaxRanges;
use tables\statuses\inventory;

class mezzanineImportQuery
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /*
    ****************************************************************************
    */

    public function oneQuery($vendorID, $upcs, $batch=NULL)
    {
        $rackedStatusID = cartons::getRackedStatusID($this->app);
        
        $qMarks = $this->app->getQMarkString($upcs);

        $validCarton = 'l.isMezzanine
                        AND ca.statusID = ?
                        AND ca.statusID = ca.mStatusID
                        AND co.vendorID = ?';

        $fields = ' upc,
                    b.upcID,
                    li.locID AS minMaxLocID,
                    mml.id AS minMaxLocID,
                    ca.locID,
                    IF(li.id IS NOT NULL, TRUE, FALSE) AS hasMinMaxSetting,
                    minCount AS min,
                    maxCount AS max,
                    upc,
                    sku,
                    color,
                    size';

        $join = '
            JOIN      inventory_batches b ON b.id = ca.batchID
            JOIN      inventory_containers co ON co.recNum = b.recNum
            JOIN      upcs p ON p.id = b.upcID
            JOIN      locations l ON l.id = ca.locID
            LEFT JOIN min_max li ON li.upcID = p.id
            LEFT JOIN locations mml ON mml.id = li.locID';

        $where = '
            AND       NOT isSplit
            AND       NOT unSplit
            AND       p.active
            AND       (li.active IS NULL
                OR li.active
            )';

        $sql = '
            SELECT    ' . $fields . ',
                      SUM(IF(' . $validCarton . ', uom, 0)) AS actualQuantity,
                      SUM(IF(NOT ' . $validCarton . ', uom, 0)) AS warehouseQuantity
            FROM      inventory_cartons ca
            ' . $join . '
            WHERE     co.vendorID = ?
            AND       upc IN (' . $qMarks . ')
            ' . $where . '
            GROUP BY  b.upcID';

        $params = array_merge([
            $rackedStatusID,
            $vendorID,
            $rackedStatusID,
            $vendorID,
            $vendorID
        ], array_keys($upcs));

        $freeCartons = $this->app->queryResults($sql, $params);

        if ($batch) {
            // adding of inventory already reserved for a batch is required
            // when checking available inventiry when a Pick Ticket for Online
            // Orders is created
            $sql = 'SELECT    ' . $fields . ',
                              SUM(uom) AS actualQuantity,
                              0 AS warehouseQuantity
                    FROM      pick_cartons pc
                    JOIN      neworder n ON n.id = pc.orderID
                    JOIN      inventory_cartons ca ON ca.id = pc.cartonID
                    ' . $join . '
                    WHERE     order_batch = ?
                    AND       pc.active
                    AND       l.isMezzanine
                    ' . $where . '
                    GROUP BY  b.upcID';

            $reservedCartons = $this->app->queryResults($sql, [$batch]);

            foreach ($reservedCartons as $upc => $values) {
                if (isset($freeCartons[$upc])) {
                    $freeCartons[$upc]['actualQuantity'] 
                            += $values['actualQuantity'];
                } else {
                    $freeCartons[$upc] = $reservedCartons[$upc];
                }
            }
        }

        return $freeCartons;
    }

    /*
    ****************************************************************************
    */

    public function scanFreeLocation($vendorID)
    {
        $minMax  = new minMaxRanges();
        $minMax->getFreeLocation($vendorID);
    }

    /*
    ****************************************************************************
    */
}
