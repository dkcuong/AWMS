<?php

namespace inventory;

class splits
{

    public $parents = [];
    public $isImport;

    /*
    ****************************************************************************
    */

    function __construct($app, $isImport=TRUE)
    {
        $this->app = $app;
        $this->isImport = $isImport;
    }

    /*
    ****************************************************************************
    */

    function createChildren($params)
    {
        isset($this->app->nextID) or die('Next inenvtory ID not set');

        $splitData = [];

        $ucc = $params['ucc'];

        $insertData = $params['insertData'];

        $batchID = $insertData['batchID'];
        $vendorID = $insertData['vendorID'];

        $sql = 'INSERT INTO inventory_cartons (
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
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    0, ?
                )';

        foreach ($params['newUOMs'] as $newUOM) {

            $uom = sprintf('%03d', $newUOM);

            // Increment Carton ID
            $this->app->maxCartons[$batchID] += 1;
            $cartonID = sprintf('%04d', $this->app->maxCartons[$batchID]);

            $insertParams = [
                $batchID,
                $cartonID,
                $uom,
                $insertData['plate'],
                $insertData['locID'],
                $insertData['mLocID'],
                $insertData['statusID'],
                $insertData['mStatusID'],
                $insertData['vendorCartonID'],
            ];

            $this->app->runQuery($sql, $insertParams);

            $parent = $this->parents[$ucc];

            $child = $this->app->nextID++;

            $this->app->newUCCs[] = $newUCC =
                    $vendorID . $batchID . $uom . $cartonID;

            $splitData[$ucc][$newUCC] = [
                'invID' => $child,
                'uom' => (int)$uom
            ];

            $this->insertSplitRel($parent, $child);
        }

        return $splitData;
    }

    /*
    ****************************************************************************
    */

    function batchesByUCC($inventoryCartons, $uccs)
    {
        $trimUCCs = array_map('trim', $uccs);

        $ucc128 = $inventoryCartons->fields['ucc128']['select'];
        $qMarkString =$this->app->getQMarkString($trimUCCs);

        $sql = 'SELECT    ca.id,
                          ca.batchID,
                          ' . $ucc128 . ' AS ucc,
                          isSplit
                FROM      inventory_containers co
                JOIN      inventory_batches b ON b.recNum = co.recNum
                JOIN      inventory_cartons ca ON b.id = ca.batchID
                WHERE     ' .$ucc128. ' IN (' . $qMarkString . ')';

        $results = $this->app->queryResults($sql, $trimUCCs);

        $batches = [];
        foreach ($results as $rowID => $row){
            $ucc = $row['ucc'];
            $batches[$ucc] = $row['batchID'];
            $this->parents[$ucc] = $rowID;
            $this->app->isSplit[$ucc] = $row['isSplit'];
        }

        return $batches;
    }

    /*
    ****************************************************************************
    */

    function insertSplitRel($parent, $child)
    {
        $sql = 'INSERT INTO inventory_splits (
                    parentID,
                    childID,
                    userID
                ) VALUES (
                    ?, ?, ?
                )';

        $userID = \access::getUserID();

        $this->isImport ? NULL :
            $this->app->runQuery($sql, [$parent, $child, $userID]);
    }

    /*
    ****************************************************************************
    */

    function getBatchesMaxes($batches)
    {
        if (! $batches) {
            return [];
        }
        $qMarkString =$this->app->getQMarkString($batches);

        $sql = 'SELECT   ca.batchID,
                         co.vendorID,
                         MAX(ca.cartonID) AS maxCarton
                FROM     inventory_cartons ca
                JOIN     inventory_batches b ON ca.batchID = b.id
                JOIN     inventory_containers co ON co.recNum = b.recNum
                WHERE    batchID IN (' . $qMarkString . ')
                GROUP BY batchID';

        return $batchMaxes = $this->app->queryResults($sql, array_values($batches));
    }

    /*
    ****************************************************************************
    */

    function splitCarton($invIDs)
    {
        $primeKeys = is_array($invIDs) ? $invIDs : [$invIDs];

        $qMarks = $this->app->getQMarkString($primeKeys);

        $sql = 'UPDATE inventory_cartons AS ca
                SET    isSplit = 1
                WHERE  ca.id IN (' . $qMarks . ')';

        $this->app->runQuery($sql, $primeKeys);

        $toValues = array_fill(0, count($primeKeys), 1);

        \common\logger::edit([
            'db' => $this->app,
            'primeKeys' => $primeKeys,
            'fields' => [
                'isSplit' => [
                    'fromValues' => 0,
                    'toValues' => $toValues,
                ],
            ],
            'transaction' => FALSE,
        ]);
    }

    /*
    ****************************************************************************
    */

    function getSplitCartons($field, $number)
    {
        switch ($field) {
            case 'order':
                $target = 'scanordernumber';
                break;
            case 'batch':
                $target = 'order_batch';
                break;
            default:
                return [];
        }

        $sql = 'SELECT DISTINCT
                         CONCAT(co.vendorID,
                            ca.batchID,
                            LPAD(uom, 3, 0),
                            LPAD(ca.cartonID, 4, 0)
                         ) AS ucc
                FROM     inventory_splits sp
                JOIN     pick_cartons pc ON pc.cartonID = sp.childID
                JOIN     neworder n ON n.id = pc.orderID
                JOIN     inventory_cartons ca ON ca.id = sp.parentID
                JOIN     inventory_batches b ON ca.batchID = b.id
                JOIN     inventory_containers co ON co.recNum = b.recNum
                WHERE    '.$target.' = ?
                AND       pc.active
                ';

        $result = $this->app->queryResults($sql, [$number]);

        return $result;
    }

    /*
    ****************************************************************************
    */
}
