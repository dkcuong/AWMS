<?php

namespace tables\inventory;

class containers extends \tables\_default
{
    public $primaryKey = 'co.recNum';

    public $ajaxModel = 'inventory\\containers';

    public $fields = [
        'vendorID' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'update' => 'co.vendorID',
        ],
        'name' => [
            'display' => 'Container',
            'noEmptyInput' => TRUE,
        ],
        'recNum' => [
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
            'update' => 'co.userID',
        ],
        'setDate' => [
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'orderBy' => 'co.recNum',
            'noEdit' => TRUE,
        ],
    ];

    public $displaySingle = 'Container';

    public $mainField = 'recNum';

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'inventory_containers co
            JOIN vendors v ON v.id = co.vendorID
            JOIN warehouses w ON v.warehouseID = w.id
            JOIN measurement_systems m ON co.measureID = m.id
            JOIN ' . $userDB . '.info u ON u.id = userID';
    }

    /*
    ****************************************************************************
    */

    function insert($row)
    {
        $sql = 'INSERT IGNORE INTO inventory_containers (
                    vendorID,
                    measureID,
                    name,
                    userID
                ) VALUES (?, ?, ?, ?)';

        $this->app->runQuery($sql, $row);

        $lastID = $this->app->lastInsertID();

        return $lastID;
    }

    /*
    ****************************************************************************
    */

    function getVendorID($recNum)
    {
        $sql = 'SELECT  vendorID
                FROM    inventory_containers co
                WHERE   co.recNum = ?';

        $result = $this->app->queryResult($sql, [
            $recNum
        ]);

        return $result['vendorID'];
    }

    /*
    ****************************************************************************
    */

    function getContainer($container)
    {
        $sql = 'SELECT  name
                FROM    inventory_containers
                WHERE   name = ?
                LIMIT 1';

        $result = $this->app->queryResult($sql, [$container]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getReceivingNumberData($receivingNumber)
    {
        $userDB = $this->app->getDBName('users');

        $sql = 'SELECT    vendorID,
                          co.recNum AS receivingNumber,
                          userID,
                          setDate AS dateAdded,
                          name AS container,
                          CONCAT_WS("_", w.shortName, vendorName) AS vendorName,
                          userName,
                          CONCAT(lastName, ", ", firstName) AS lastFirst,
                          setDate,
                          m.id AS measureID,
                          m.displayName AS measurementSystem,
                          r.id AS receiving,
                          ref
                FROM      inventory_containers co
                LEFT JOIN receiving_containers rc ON rc.container_num = co.recNum
                LEFT JOIN receivings r ON r.id = rc.receiving_id
                JOIN      measurement_systems m ON m.id = co.measureID
                JOIN      vendors v ON v.id = co.vendorID
                JOIN      warehouses w ON w.id = v.warehouseID
                JOIN      ' . $userDB . '.info us ON us.id = co.userID
                WHERE     co.recNum = ?';

        $result = $this->app->queryResult($sql, [$receivingNumber]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getContainers($names)
    {
        $qMarks = $this->app->getQMarkString($names);

        $sql = 'SELECT  name
                FROM    inventory_containers
                WHERE   name IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $names);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getContainerLocations($recNums)
    {
        if (! $recNums) {
            return FALSE;
        }

        $qMarks = $this->app->getQMarkString($recNums);

        $sql = 'SELECT   c.id,
                         recNum,
                         plate,
                         locID
                FROM     inventory_batches b
                JOIN     inventory_cartons c ON c.batchID = b.id
                WHERE    recNum IN (' . $qMarks . ')
                ORDER BY plate DESC';

        $results = $this->app->queryResults($sql, $recNums);

        $containerLocs = [];

        foreach ($results as $invID => $row) {

            $recNum = $row['recNum'];
            $locID = $row['locID'];
            $plate = $row['plate'];

            $containerLocs[$recNum][$locID][$plate][] = $invID;
        }

        return $containerLocs;
    }

    /*
    ****************************************************************************
    */

    function getUccContainers($params)
    {
        $qMarks = $this->app->getQMarkString($params);

        $sql = 'SELECT    name AS container
                FROM      tally_rows r
                LEFT JOIN tally_cartons c ON c.rowID = r.id
                LEFT JOIN inventory_cartons ca ON c.invID = ca.id
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                WHERE     r.active
                AND       c.active
                AND       NOT isSplit
                AND       NOT unSplit
                AND       CONCAT(vendorID, b.id) IN (' . $qMarks . ')
                GROUP BY  container';

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getContainerBatches($uccContainers)
    {
        $cartons = new cartons($this->app);

        $ucc128 = $cartons->fields['ucc128']['select'];

        $qMarkUccContainers = $this->app->getQMarkString($uccContainers);

        $sql = 'SELECT  ' . $ucc128 . ',
                        ca.id,
                        cartonID,
                        u.upc AS upc,
                        ' . $ucc128 . ' AS ucc,
                        u.sku,
                        color,
                        size,
                        vendorID,
                        LPAD(uom, 3, 0) AS uom,
                        cartonID,
                        b.id AS batchID,
                        b.upcID,
                        name AS container
                FROM    inventory_cartons ca
                JOIN    inventory_batches b ON b.id = ca.batchID
                JOIN    inventory_containers co ON co.recNum = b.recNum
                JOIN    upcs u ON u.id = b.upcID
                WHERE   name IN (' . $qMarkUccContainers . ')
                ORDER BY ca.id ASC';

        $results = $this->app->queryResults($sql, $uccContainers);

        return $results;
    }

    /*
    ****************************************************************************
    */
    function getInfoContainerMissingReceiving()
    {
        $sql = 'SELECT    co.recNum,
                          co.`name`,
                          co.setDate,
                          co.userID,
                          co.vendorID,
                          v.warehouseID
                FROM      inventory_containers co
                LEFT JOIN receiving_containers rc ON rc.container_num = co.recNum
                LEFT JOIN receivings r ON r.id = rc.receiving_id
                LEFT JOIN vendors v ON v.id = co.vendorID
                WHERE     rc.receiving_id IS NULL';

        $results = $this->app->queryResults($sql);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getContainerName($recNum)
    {
        $sql = 'SELECT  name
                FROM    inventory_containers co
                WHERE   co.recNum = ?';

        $result = $this->app->queryResult($sql, $recNum);

        return $result['name'];
    }

    /*
    ****************************************************************************
    */

    function getContainerReceivedDate($recNums)
    {
        $model = new \logs\fields($this->app);
        $fieldID = $model->getFieldID('cartons');

        $statusObj = new \tables\statuses\inventory($this->app);
        $stsName = ['IN', 'RC', 'RK'];
        $stsResult = $statusObj->getStatusIDs($stsName);
        $statusID = array_column($stsResult, 'id');

        $sql = '
                SELECT    co.recNum,
                          vendorName,
                          co.recNum AS recNum,
                          name,
                          measureID,
                          DATE(logTime) AS date
                FROM      logs_values lv
                JOIN      logs_cartons c ON c.id = lv.logID
                JOIN      inventory_cartons ca ON ca.id = primeKey
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      vendors v ON v.id = co.vendorID
                WHERE     fieldID = ?
                AND       fromValue = ?
                AND       toValue IN (?, ?)
                AND       co.recNum IN (' . $this->app->getQMarkString($recNums) . ')
                GROUP BY  co.recNum
               ';

        array_unshift($statusID, $fieldID);

        $param = array_merge($statusID, $recNums);

        $result = $this->app->queryResults($sql, $param);

        return $result;

    }

     /*
    ****************************************************************************
    */
}
