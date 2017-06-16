<?php

namespace tables\inventory;

class batches extends \tables\_default
{
    public $primaryKey = 'b.id';

    public $ajaxModel = 'inventory\\batches';

    public $fields = [
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client',
            'noEdit' => TRUE,
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'container' => [
            'select' => 'i.name',
            'display' => 'Container',
            'noEdit' => TRUE,
        ],
        'recNum' => [
            'select' => 'b.recNum',
            'display' => 'Receiving Number',
            'noEdit' => TRUE,
        ],
        'measureID' => [
            'select' => 'm.displayName',
            'display' => 'Measurement System',
            'searcherDD' => 'inventory\\measure',
            'ddField' => 'displayName',
            'noEdit' => TRUE,
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'noEdit' => TRUE,
        ],
        'setDate' => [
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'orderBy' => 'b.id',
            'noEdit' => TRUE,
        ],
        'batchID' => [
            'select' => 'b.id',
            'display' => 'Batch Number',
            'noEdit' => TRUE,
        ],
        'plate' => [
            'select' => '
                CASE COUNT(DISTINCT(c.plate))
                    WHEN 0 THEN "Not Received"
                    WHEN 1 AND COUNT(c.id) = COUNT(c.plate) THEN c.plate
                    ELSE "Multiple"
                END',
            'display' => 'Plate',
            'noEdit' => TRUE,
            'acDisabled' => TRUE,
            'groupedFields' => 'c.id, c.plate',
        ],
        'sku' => [
            'select' => 'p.sku',
            'batchFields' => TRUE,
            'display' => 'Style Number',
            'noEdit' => TRUE,
        ],
        'uom' => [
            'batchFields' => TRUE,
            'select' => 'LPAD(UOM, 3, 0)',
            'display' => 'UOM',
            'isNum' => 3,
            'noEdit' => TRUE,
        ],
        'prefix' => [
            'batchFields' => TRUE,
            'display' => 'Prefix',
        ],
        'suffix' => [
            'select' => 'suffix',
            'batchFields' => TRUE,
            'display' => 'Suffix',
        ],
        'height' => [
            'batchFields' => TRUE,
            'display' => 'Height',
            'isNum' => 5,
            'isDecimal' => 2,
            'limitmax' => 60,
            'limitmin' => 2,
        ],
        'width' => [
            'batchFields' => TRUE,
            'display' => 'Width',
            'isNum' => 5,
            'isDecimal' => 2,
            'limitmax' => 48,
            'limitmin' => 2,
        ],
        'length' => [
            'batchFields' => TRUE,
            'display' => 'Length',
            'isNum' => 5,
            'isDecimal' => 2,
            'limitmax' => 48,
            'limitmin' => 2,
        ],
        'weight' => [
            'batchFields' => TRUE,
            'display' => 'Weight',
            'isNum' => 'unl',
        ],
        'eachHeight' => [
            'batchFields' => TRUE,
            'display' => 'Each-Height',
            'isNum' => 5,
            'isDecimal' => 2,
            'limitmax' => 60,
            'limitmin' => 0,
        ],
        'eachWidth' => [
            'batchFields' => TRUE,
            'display' => 'Each-Width',
            'isNum' => 5,
            'isDecimal' => 2,
            'limitmax' => 48,
            'limitmin' => 0,
        ],
        'eachLength' => [
            'batchFields' => TRUE,
            'display' => 'Each-Length',
            'isNum' => 5,
            'isDecimal' => 2,
            'limitmax' => 48,
            'limitmin' => 0,
        ],
        'eachWeight' => [
            'batchFields' => TRUE,
            'display' => 'Each-Weight',
            'isNum' => 'unl',
        ],
        'upc' => [
            'select' => 'p.upc',
            'batchFields' => TRUE,
            'display' => 'UPC',
            'noEdit' => TRUE,
        ],
        'size1' => [
            'select' => 'p.size',
            'batchFields' => TRUE,
            'display' => 'Size',
            'noEdit' => TRUE,
        ],
        'color1' => [
            'select' => 'p.color',
            'batchFields' => TRUE,
            'display' => 'Color',
            'noEdit' => TRUE,
        ],
        'initialCount' => [
            'batchFields' => TRUE,
            'display' => 'Original Carton Count',
            'isNum' => 'unl',
        ],
        'actualCartons' => [
            'select' => 'COUNT(c.cartonID)',
            'display' => 'Current Carton Count',
            'noEdit' => TRUE,
            'groupedFields' => 'c.cartonID',
            'isNum' => 'unl',
        ],
    ];
    public $where = 'NOT c.isSplit
        AND       NOT c.unSplit';

    public $groupBy = 'b.id';

    public $displaySingle = 'Batch';
    public $mainField = 'b.id';

    public $baseTable = 'inventory_batches b
        JOIN      inventory_containers i ON b.recNum = i.recNum
        JOIN      inventory_cartons c ON b.id = c.batchID';

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'inventory_batches b
            JOIN inventory_containers i ON b.recNum = i.recNum
            JOIN measurement_systems m ON i.measureID = m.id
            JOIN vendors v ON v.id = i.vendorID
            JOIN warehouses w ON v.warehouseID = w.id
            JOIN inventory_cartons c ON b.id = c.batchID
            JOIN upcs p ON b.upcID = p.id
            JOIN '.$userDB.'.info u ON u.id = userID';
    }

    /*
    ****************************************************************************
    */

    function createBatches($cartons, $containerID, $batches)
    {
        $this->app->beginTransaction();

        $firstBatch = reset($batches);
        unset($firstBatch['uom'], $firstBatch['upc']);

        $fields = array_keys($firstBatch);

        $sql = 'INSERT INTO inventory_batches (recNum, '.implode(',', $fields).')
                VALUES (?, '.$this->app->getQMarkString($fields).')';

        $batchQuantites = [];
        foreach ($batches as $batch) {
            $uom = $batch['uom'];
            unset($batch['uom'], $batch['upc']);
            $totalCarton = $batch['initialCount'];
            $batch = array_values($batch);

            array_unshift($batch, $containerID);
            $this->app->runQuery($sql, $batch);
            // Increment receiving numbers
            $batchID = $this->app->lastInsertID();
            $batchQuantites[$batchID] = $totalCarton;
        }

        foreach ($batchQuantites as $batchID => $totalCarton) {
            $cartons->add($batchID, $totalCarton, $uom);
        }

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function insertFields()
    {
        return $fields = [
            'id' => [
                'display' => '',
                'ignore' => TRUE,
            ],
            'recNum' => ['display' => '',],
            'sku' => ['display' => '',],
            'prefix' => ['display' => '',],
            'suffix' => ['display' => '',],
            'height' => ['display' => '',],
            'width' => ['display' => '',],
            'length' => ['display' => '',],
            'weight' => ['display' => '',],
            'size1' => ['display' => '',],
            'color1' => ['display' => '',],
            'initialCount' => ['display' => '',],
            'upcID' => ['display' => '',],
        ];
    }

    /*
    ****************************************************************************
    */

    function getByRecNum($recNum, $firstLast=FALSE)
    {
        $sql = 'SELECT    id,
                          id AS batch
                FROM      inventory_batches
                WHERE     recNum = ?
                ';

        $result = $this->app->queryResults($sql, $recNum);

        if ($firstLast) {
            if ($result) {
                $firstBatch = reset($result);
                $lastBatch = end($result);

                return [
                    'firstBatch' => $firstBatch['batch'],
                    'lastBatch' => $lastBatch['batch'],
                ];
            } else {
                return FALSE;
            }
        } else {
            return array_values($result);
        }
    }

    /*
    ****************************************************************************
    */

    function checkBadBatches($batches)
    {
        $qMakString = $this->app->getQMarkString($batches);

        $sql = 'SELECT    ca.batchID,
                          co.recNum,
                          co.name,
                          vendorName,
                          b.upcID,
                          COUNT(ca.id) AS cartonCount
                FROM      inventory_batches b
                JOIN      inventory_cartons ca ON ca.batchID = b.id
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      vendors v ON v.id = co.vendorID
                WHERE     b.id IN (' . $qMakString . ')
                GROUP BY  b.id
                HAVING cartonCount > 2000
                ';
        $key = array_keys($batches);
        $result = $this->app->queryResults($sql, $key);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function addUnitDimensions($dbProducts)
    {
        if (! $dbProducts) {
            return [];
        }

        $batchKeys = [];

        foreach ($dbProducts as $dbProduct) {

            $batchID = $dbProduct['batchID'];

            $batchKeys[$batchID] = TRUE;
        }

        $batchIDs = array_keys($batchKeys);

        $dimensions = $this->getUnitDimensions($batchIDs);

        foreach ($dbProducts as &$dbProduct) {

            $batchID = $dbProduct['batchID'];

            $weight = getDefault($dimensions[$batchID]['weight'], 0);
            $volume = getDefault($dimensions[$batchID]['volume'], 0);

            $dbProduct['volume'] = round($dbProduct['quantity'] * $volume / 1728, 1);
            $dbProduct['weight'] = round($dbProduct['quantity'] * $weight, 1);

            unset($dbProduct['batchID']);
        }

        return $dbProducts;
    }

    /*
    ****************************************************************************
    */

    function getUnitDimensions($batchIDs)
    {
        // get volume and weight per piece
        $sql = 'SELECT    b.id,
                          upcID,
                          IF(eachLength * eachWidth * eachHeight,
                             eachLength * eachWidth * eachHeight,
                             height * width * length / uom
                          ) AS volume,
                          IF(eachWeight, eachWeight, weight / uom) AS weight
                FROM      inventory_batches b
                JOIN      inventory_cartons ca ON ca.batchID = b.id
                WHERE     b.id = ?
                ORDER BY  ca.id ASC
                ';

        $subqueryCount = count($batchIDs);

        return $this->app->queryUnionResults([
            'limits' => array_fill(0, $subqueryCount, 1),
            'subqueries' => array_fill(0, $subqueryCount, $sql),
            'mysqlParams' => $batchIDs,
            'subqueryCount' => $subqueryCount
        ]);
    }

    /*
    ****************************************************************************
    */

    function getRecNumByID($batchID)
    {
        $sql = 'SELECT   recNum
                FROM     inventory_batches
                WHERE    id = ?';

        $result = $this->app->queryResult($sql, [$batchID]);

        return $result['recNum'];
    }

    /*
    ****************************************************************************
    */
}

