<?php

namespace tables;

use common\logger;

class unsplitCartons extends _default
{
    static $unSplitSql = [];

    static $cartonData = [];

    static $newUOMs = [];

    static $child = [];

    public $ajaxModel = 'unsplitCartons';

    public $primaryKey = 'ip.id';

    public $fields = [
        'parent' => [
            'select' => 'CONCAT(v.id,
                                bap.id,
                                LPAD(cap.uom, 3, 0),
                                LPAD(cap.cartonID, 4, 0)
                        )',
            'display' => 'Original UCC128',
            'acDisabled' => TRUE,
        ],
        'childID' => [
            'select' => 'COUNT(cac.id)',
            'display' => 'Merge Carton Count',
            'groupedFields' => 'cac.id',
        ],
        'childUom' => [
            'select' => 'SUM(cac.uom)',
            'display' => 'Merge UOM Count',
            'groupedFields' => 'cac.uom',
        ],
        'childLocation' => [
            'select' => 'l.displayName',
            'display' => 'Location',
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", v.vendorName)',
        ],
        'container' => [
            'select' => 'cop.name',
            'display' => 'Container',
        ],
        'batchID' => [
            'select' => 'bap.id',
            'display' => 'Batch Number',
        ],

    ];

    public $where = 'ip.active
                AND NOT cac.isSplit
                AND NOT cac.unSplit
                AND s.shortName = "RK"
                AND cac.statusID = cac.mstatusID';

    public $groupBy  = 'cap.id, l.id';

    public $having = 'COUNT(cac.id) > 1';

    /*
    ****************************************************************************
    */

    function table()
    {
        return 'inventory_splits ip
               JOIN inventory_cartons cap ON cap.id = ip.parentID
               JOIN inventory_batches bap ON bap.id = cap.batchID
               JOIN inventory_containers cop ON cop.recNum = bap.recNum
               JOIN vendors v ON v.id = cop.vendorID
               JOIN warehouses w ON v.warehouseID = w.id
               JOIN inventory_cartons cac ON cac.id = ip.childID
               JOIN locations l ON l.id = cac.locID
               JOIN statuses s ON s.id = cac.statusID';
    }

    /*
    ****************************************************************************
    */

    function getCartonInfo($cartonIDs)
    {
        $cartonIDQMarks = $this->app->getQMarkString($cartonIDs);

        $sql = 'SELECT    ca.id,
                          ca.batchID,
                          ca.cartonID,
                          uom,
                          plate,
                          locID,
                          mLocID,
                          statusID,
                          mStatusID,
                          vendorCartonID,
                          co.vendorID
                FROM      inventory_containers co
                JOIN      inventory_batches b ON co.recNum = b.recNum
                JOIN      inventory_cartons ca ON b.id = ca.batchID
                JOIN      statuses s ON s.id = ca.statusID
                WHERE     ca.id  IN (' . $cartonIDQMarks . ')
                AND       s.shortName = "RK"
                AND       ca.statusID = ca.mStatusID
                AND       ca.locID = ca.mLocID
                GROUP BY ca.id';

        self::$cartonData = $this->app->queryResults($sql, $cartonIDs);

        return self::$cartonData;
    }

    /*
    ****************************************************************************
    */

    function getNextCartonIDs()
    {
        $batchIDs = [];
        foreach (self::$cartonData as $cartonID => $data)
        {
            $batchID = $data['batchID'];
            $batchIDs[$cartonID] = $this->getNextCartonID($batchID);
        }
        return $batchIDs;
    }

    /*
    ****************************************************************************
    */

    function getNextCartonID($batchID)
    {
        $sql = 'SELECT  MAX(ca.cartonID) + 1 AS largestCartonID
                FROM    inventory_cartons ca
                JOIN    inventory_batches b ON b.id = ca.batchID
                WHERE   b.id = ?';

        $largestCartonID = $this->app->queryResult($sql, [$batchID]);

        return $largestCartonID;
    }

    /*
    ****************************************************************************
    */

    function insertUnSplitRel($parent, $child)
    {
        $sql = 'INSERT INTO inventory_unsplits (
                    parentID,
                    childID,
                    userID
                ) VALUES (
                    ?, ?, ?
                )';

        $userID = \access::getUserID();

        $this->app->runQuery($sql, [$parent, $child, $userID]);
    }

    /*
    ****************************************************************************
    */

    function getNextInventoryID()
    {
        $sql = 'SELECT  AUTO_INCREMENT AS nextID
                FROM    information_schema.tables
                WHERE   table_name = "inventory_cartons"';

        $result = $this->app->queryResult($sql);

        return $result['nextID'];
    }

    /*
    ****************************************************************************
    */

    function getSplitData ($inventoryCartons, $parents=[])
    {
        $this->getUnSplitSql();

        $clauses = $inventoryCartons->getByUCCSelectClauses($parents);

        $where = $clauses['where'];
        $params = $clauses['params'];

        $where .= ' AND s.shortName = ? ';

        $params[] = inventory\cartons::STATUS_RACKED;

        $ucc128 = $inventoryCartons->fields['ucc128']['select'];

        $sql = 'SELECT  ip.id,
                        ip.childID,
                        ip.parentID,
                        b.id AS batchID,
                        ' . $ucc128 . ' AS ucc128,
                        cac.locID
                FROM    inventory_cartons ca
                JOIN    inventory_batches b ON b.id = ca.batchID
                JOIN    inventory_containers co ON co.recNum = b.recNum
                JOIN    inventory_splits ip ON ip.parentID = ca.id
                JOIN    inventory_cartons cac ON ip.childID = cac.id
                JOIN    statuses s ON s.id = cac.statusID
                WHERE   ' . $where . '
                AND     ip.active
                AND     NOT cac.isSplit
                AND     NOT cac.unSplit
                AND     cac.statusID = cac.mstatusID';

        $splitData = $this->app->queryResults($sql, $params);

        if (! $splitData) {
            return [
                'parentIDs' => [],
                'errors' => ['No child cartons found'],
            ];
        }

        $childLocations = $errors = $parentIDs = [];

        foreach ($splitData as $value) {

            $parentID = $value['parentID'];
            $childID = $value['childID'];
            $childLocation = $value['locID'];

            if (isset($childLocations[$parentID])
             && $childLocations[$parentID] != $childLocation) {

                $ucc128 = $value['ucc128'];

                $errors[] = 'Carton ' . $ucc128 . ' has children cartons at '
                    . 'different locations';
            }

            $childLocations[$parentID] = $childLocation;

            $childrenIDs[] = $childID;
            $parentIDs[] = $parentID;

            self::$child[$parentID][] = $childID;
        }

        $this->getUomsByLocation($parentIDs);

        $childrenIDs = array_unique($childrenIDs);
        $parentIDs = array_unique($parentIDs);
        $cartonIDs = array_merge($parentIDs, $childrenIDs);

        $this->getCartonInfo($cartonIDs);

        return [
            'parentIDs' => $parentIDs,
            'errors' => $errors,
        ];
    }

    /*
    ****************************************************************************
    */

    function getUnSplitSql()
    {
        $insertSql = 'INSERT INTO inventory_cartons (
                        batchID,
                        cartonID,
                        uom,
                        plate,
                        locID,
                        mLocID,
                        statusID,
                        mStatusID,
                        isSplit,
                        vendorCartonID
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, 0, ?
                    )';

        $updateChildSql = 'UPDATE    inventory_cartons ca
                           LEFT JOIN inventory_splits sp ON sp.childID =  ca.id
                           SET       unSplit = 1,
                                     sp.active = 0
                           WHERE     ca.id = ?';

        $revertParentSql = 'UPDATE    inventory_cartons ca
                            SET       unSplit = 0,
                                      isSplit = 0,
                                      locID = ?,
                                      mlocID =?
                            WHERE     ca.id = ?';

        self::$unSplitSql  = [
            'insertCarton'  => $insertSql,
            'updateChild'   => $updateChildSql,
            'revertParent'  => $revertParentSql
        ];
        return self::$unSplitSql;
    }

    /*
    ****************************************************************************
    */

    function updateChildCartons($child, $newInventoryID)
    {
        foreach ($child as $childID) {
            $this->insertUnSplitRel($childID, $newInventoryID);
            $this->app->runQuery(self::$unSplitSql['updateChild'], [$childID]);
            logger::edit([
                'db' => $this->app,
                'primeKeys' => $childID,
                'fields' => [
                    'unSplit' => [
                        'fromValues' => 0,
                        'toValues' => 1,
                    ],
                ],
                'transaction' => FALSE,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function mergeCartons($parentIDs)
    {
        $newCartons = [];
        $nextCartonIDs = $this->getNextCartonIDs();
        $newInventoryID = $this->getNextInventoryID();

        $revertParentSQL =  self::$unSplitSql['revertParent'];
        $insertCarton = self::$unSplitSql['insertCarton'];

        $this->app->beginTransaction();

        foreach ($parentIDs as $parentID) {
            $cartonData = self::$cartonData[$parentID];
            foreach(self::$newUOMs[$parentID] as $locID => $data) {
                $uom = sprintf('%03d', $data['uom']);
                if ($cartonData['uom'] == $uom) {
                    $params = [$locID, $locID, $parentID];
                    $cartonID = sprintf('%04d', $cartonData['cartonID']);
                    $this->app->runQuery($revertParentSQL, $params);

                    $this->updateChildCartons(self::$child[$parentID], $parentID);

                    logger::edit([
                        'db' => $this->app,
                        'primeKeys' => $parentID,
                        'fields' => [
                            'locID' => [
                                'fromValues' => $cartonData['locID'],
                                'toValues' => $locID,
                            ],
                        ],
                        'transaction' => FALSE,
                    ]);

                } else {
                    $nextCartonID = $nextCartonIDs[$parentID]['largestCartonID'];
                    $cartonID = sprintf('%04d', $nextCartonID);
                    $insertData = [
                        $cartonData['batchID'],
                        $cartonID,
                        $uom,
                        $cartonData['plate'],
                        $locID,
                        $locID,
                        $cartonData['statusID'],
                        $cartonData['mStatusID'],
                        $cartonData['vendorCartonID']
                    ];

                    $this->app->runQuery($insertCarton, $insertData);
                    $this->updateChildCartons(self::$child[$parentID], $newInventoryID);
                    $newInventoryID++;
                    $nextCartonIDs[$parentID]['largestCartonID']++;
                }
                $newCartons[] = $cartonData['vendorID'] .
                    $cartonData['batchID'] . $uom . $cartonID;
            }
        }

        $this->app->commit();

        return $newCartons;

    }

    /*
    ****************************************************************************
    */

    function getUomsByLocation($parentIDs)
    {
        $cartonIDQMarks = $this->app->getQMarkString($parentIDs);

        $sql = 'SELECT  CONCAT(cap.id, "_", l.id) AS locCartonID,
                        l.id AS locID,
                        cap.id AS cartonID,
                        l.displayName AS location,
                        COUNT(cac.id) AS countChild,
                        SUM(cac.uom) AS countUOM
                FROM    inventory_splits ip
                JOIN    inventory_cartons cap ON cap.id = ip.parentID
                JOIN    inventory_batches bap ON bap.id = cap.batchID
                JOIN    inventory_containers cop ON cop.recNum = bap.recNum
                JOIN    inventory_cartons cac ON cac.id = ip.childID
                JOIN    locations l ON l.id = cac.locID
                JOIN    statuses s ON s.id = cac.statusID
                WHERE   ip.active
                AND     cap.id IN (' . $cartonIDQMarks . ')
                AND     NOT isMezzanine
                AND     NOT cac.isSplit
                AND     NOT cac.unSplit
                AND     s.shortName = "RK"
                AND     cac.statusID = cac.mstatusID
                GROUP BY cap.id,
                        l.id';
        $results = $this->app->queryResults($sql, $parentIDs);

        foreach ($results as $locCartonID => $value){
            $locID = $value['locID'];
            $cartonID = $value['cartonID'];
            self::$newUOMs[$cartonID][$locID]['carton'] = $value['countChild'];
            self::$newUOMs[$cartonID][$locID]['uom'] = $value['countUOM'];
        }
    }

    /*
    ****************************************************************************
    */

}