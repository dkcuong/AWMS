<?php

namespace summary;

use tables\inventory\cartons;

class createRcv extends model
{

    public $fields = [
        'order_nbr' => TRUE,
        'cust_id' => TRUE,
        'dt' => TRUE,
        'val' => TRUE,
    ];

    public $ids = [];

    /*
    ****************************************************************************
    */

    function make()
    {
        $totalTime = self::init()->timer();

        $this
            ->getIDs()
            ->cartons()
            ->volume()
            ->containers()
            ->pieces()
            ->plates();

        $totalTime->timer('Total Make Time');
    }

    /*
    ****************************************************************************
    */

    function getIDs()
    {
        $sql = '(
                    SELECT "statusID",
                           id AS id
                    FROM   logs_fields
                    WHERE  category = "cartons"
                    AND    displayName = "statusID"
                ) UNION (
                    SELECT shortName,
                           id
                    FROM   statuses
                    WHERE  shortName IN ("IN", "RC", "RK")
                    AND    category = "inventory"
                )';

        $ids = $this->db->queryResults($sql);

        $this->clause = '
            WHERE     fieldID = '.$ids['statusID']['id'].'
            AND       fromValue = '.$ids['IN']['id'].'
            AND       toValue IN ('.$ids['RC']['id'].', '.$ids['RK']['id'].')';

        return $this;
    }

    /*
    ****************************************************************************
    */

    function containers()
    {
        copyTable::init($this)->standard([
            'sql' => '
                SELECT    vendorID,
                          co.recNum AS recNum,
                          logID,
                          DATE(logTime),
                          1
                FROM      logs_values v
                JOIN      logs_cartons c ON c.id = v.logID
                JOIN      inventory_cartons ca ON ca.id = primeKey
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                '.$this->clause.'
                GROUP BY  co.recNum
            ',
            'csvFile' => 'containersRcv',
            'truncate' => TRUE,
            'targetTable' => 'rcv_sum_cntr',
            'fields' => [
                'cust_id' => TRUE,
                'rcv_nbr' => TRUE,
                'log_id' => TRUE,
                'dt' => TRUE,
                'val' => TRUE,
            ],
            'timeMessage' => 'Containers Summary',
        ]);

        return $this;
    }

    /*
    ****************************************************************************
    */

    function volume()
    {
        copyTable::init($this)->standard([
            'sql' => '
                SELECT    co.recNum,
                          vendorID,
                          logID,
                          DATE(logTime),
                          SUM(b.height * b.length * b.width / 1728) AS vol
                FROM      logs_values v
                JOIN      logs_cartons c ON c.id = v.logID
                JOIN      inventory_cartons ca ON ca.id = primeKey
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                '.$this->clause.'
                GROUP BY  co.recNum
            ',
            'csvFile' => 'volumeRcv',
            'truncate' => TRUE,
            'targetTable' => 'rcv_sum_vol',
            'fields' => [
                'rcv_nbr' => TRUE,
                'cust_id' => TRUE,
                'log_id' => TRUE,
                'dt' => TRUE,
                'vol' => TRUE,
            ],
            'timeMessage' => 'Volume Summary',
        ]);

        return $this;
    }

    /*
    ****************************************************************************
    */

    function cartons()
    {
        copyTable::init($this)->standard([
            'sql' => '
                SELECT    co.recNum,
                          vendorID,
                          logID,
                          DATE(logTime),
                          COUNT(DISTINCT ca.id)
                FROM      logs_values v
                JOIN      logs_cartons c ON c.id = v.logID
                JOIN      inventory_cartons ca ON ca.id = primeKey
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                '.$this->clause.'
                GROUP BY  co.recNum
            ',
            'csvFile' => 'cartonsRcv',
            'truncate' => TRUE,
            'targetTable' => 'rcv_sum_ctn',
            'fields' => [
                'rcv_nbr' => TRUE,
                'cust_id' => TRUE,
                'log_id' => TRUE,
                'dt' => TRUE,
                'val' => TRUE,
            ],
            'timeMessage' => 'Cartons Summary',
        ]);

        return $this;
    }

    /*
    ****************************************************************************
    */

    function pieces()
    {
        copyTable::init($this)->standard([
            'sql' => '
                SELECT    co.recNum,
                          vendorID,
                          logID,
                          DATE(logTime),
                          SUM(ca.uom)
                FROM      logs_values v
                JOIN      logs_cartons c ON c.id = v.logID
                JOIN      inventory_cartons ca ON ca.id = primeKey
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                '.$this->clause.'
                GROUP BY  co.recNum',
            'csvFile' => 'piecesRcv',
            'truncate' => TRUE,
            'targetTable' => 'rcv_sum_pcs',
            'fields' => [
                'rcv_nbr' => TRUE,
                'cust_id' => TRUE,
                'log_id' => TRUE,
                'dt' => TRUE,
                'val' => TRUE,
            ],
            'timeMessage' => 'Pieces Summary',
        ]);

        return $this;
    }

    /*
    ****************************************************************************
    */

    function plates()
    {
        copyTable::init($this)->standard([
            'sql' => '
                SELECT    co.recNum,
                          vendorID,
                          logID,
                          DATE(logTime),
                          COUNT(DISTINCT ca.plate)
                FROM      logs_values v
                JOIN      logs_cartons c ON c.id = v.logID
                JOIN      inventory_cartons ca ON ca.id = primeKey
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                '.$this->clause.'
                GROUP BY  co.recNum',
            'csvFile' => 'platesRcv',
            'truncate' => TRUE,
            'targetTable' => 'rcv_sum_plt',
            'fields' => [
                'rcv_nbr' => TRUE,
                'cust_id' => TRUE,
                'log_id' => TRUE,
                'dt' => TRUE,
                'val' => TRUE,
            ],
            'timeMessage' => 'Plates Summary',
        ]);

        return $this;
    }

    /*
    ****************************************************************************
    */

    function getReceivingSummary()
    {
        $totalTime = self::init()->timer();

        //get the max log_id from ctn_log_sum
        $logIDs = $this->getLogIDs('rcv_sum_ctn', 'logs_cartons');

        if ( ! $logIDs) {
            return [];
        }

        $logs = new \logs\fields($this->db);

        $lFieldID = $logs->getFieldID('cartons');

        $status = new \tables\statuses\inventory($this->db);

        $stsName = [
            cartons::STATUS_INACTIVE,
            cartons::STATUS_RECEIVED,
            cartons::STATUS_RACKED
        ];

        $stsResult = $status->getStatusIDs($stsName);

        $statusID = array_column($stsResult, 'id');

        $this->logReceivingSummary($logIDs, $lFieldID, $statusID);

        $totalTime->timer();

        return [];
    }

    /*
    ****************************************************************************
    */

    function logReceivingSummary($logIDs, $lFieldID, $statusID)
    {
        if (! $logIDs) {
            return;
        }

        $processData = array_splice($logIDs, 0, 500);

        $qMarks = $this->db->getQMarkString($processData);

        $sql = 'SELECT     primeKey,
                           logID,
                           DATE(logTime) AS logDate
                FROM       logs_values lv
                JOIN       logs_cartons lc ON lc.id = lv.logID
                WHERE      logID IN (' . $qMarks . ')
                AND        fieldID = ?
                AND        fromValue = ?
                AND        toValue IN (?, ?)
                ';

        $logParam = array_merge($processData, [$lFieldID], $statusID);

        $logValuesRes = $this->db->queryResults($sql, $logParam);

        $logData = array_keys($logValuesRes);

        if (! $logData) {
            return [];
        }

        $this->logReceivingSummaryExecute($logData, $logValuesRes);

        $this->logReceivingSummary($logIDs, $lFieldID, $statusID);
    }

    /*
    ****************************************************************************
    */

    function logReceivingSummaryExecute($invIDs, $logValuesRes)
    {
        if (! $invIDs) {
            return;
        }

        $processData = array_splice($invIDs, 0, 1000);

        $sumTable = [
            'carton'  =>  'rcv_sum_ctn',
            'piece'   =>  'rcv_sum_pcs',
            'volume'  =>  'rcv_sum_vol',
            'plate' =>   'rcv_sum_plt',
            'container'  => 'rcv_sum_cntr'
        ];

        $qMarks = $this->app->getQMarkString($processData);

        $sql = 'SELECT      co.recNum,
                            vendorID,
                            MIN(ca.id) AS id,
                            COUNT(DISTINCT ca.id) AS carton,
                            SUM(b.height * b.length * b.width / 1728) AS volume,
                            SUM(ca.uom) AS piece,
                            COUNT(DISTINCT ca.plate) AS plate,
                            1 AS container
                FROM        inventory_cartons ca
                JOIN        inventory_batches b ON b.id = ca.batchID
                JOIN        inventory_containers co ON co.recNum = b.recNum
                WHERE       ca.id IN (' . $qMarks . ')
                GROUP BY    co.recNum';

        $results = $this->app->queryResults($sql, $processData);

        $this->app->beginTransaction();

        foreach ($results as $recNum => $row) {

            $firstCartonID = $row['id'];

            $dt = $logValuesRes[$firstCartonID]['logDate'];

            $logID = $logValuesRes[$firstCartonID]['logID'];

            foreach ($sumTable as $key => $table) {

                $field = $table === 'rcv_sum_vol' ? 'vol' : 'val';

                $sql = 'INSERT IGNORE INTO ' . $table . '
                        (
                            rcv_nbr,
                            cust_id,
                            log_id,
                            dt,
                            ' . $field . '
                        ) VALUES (
                            ?, ?, ?, ?, ?
                        )';

                $this->app->runQuery($sql, [
                        $recNum,
                        $row['vendorID'],
                        $logID,
                        $dt,
                        $row[$key]
                ]);
            }
        }

        $this->app->commit();

        $this->logReceivingSummaryExecute($invIDs, $logValuesRes);
    }

    /*
    ****************************************************************************
    */

}
