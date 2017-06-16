<?php

namespace summary;

class cartonLog extends model
{

    /*
    ****************************************************************************
    */

    static function init($app=NULL)
    {
        $self = new static();
        $self->app = $app;
        return $self;
    }

    /*
    ****************************************************************************
    */

    function cartonLogSum()
    {
        $totalTime = self::init()->timer();

        //get the max log_id from ctn_log_sum
        $ctnSql = '
            SELECT   MAX(log_id) AS log
            FROM     ctn_log_sum';

        $logRes = $this->app->queryResult($ctnSql);

        $fieldSql = '
            SELECT   id
            FROM     logs_fields
            WHERE    displayName = "statusID"
            AND      category = "cartons"';

        $fieldRes = $this->app->queryResult($fieldSql);

        $fieldID = $fieldRes['id'];

        $maxLog = getDefault($logRes['log'], 0);

        $locCartonResults = $this->getLogData($maxLog, 'logs_cartons', TRUE);

        if (! $locCartonResults) {

            $totalTime->timer();

            return [];
        }

        $logIDs = array_keys($locCartonResults);

        $logValuesSql = '
            SELECT    primeKey,
                      logID
            FROM      logs_values
            WHERE     fieldID = ?
            AND       logID IN (' . $this->app->getQMarkString($logIDs) . ')';

        array_unshift($logIDs, $fieldID);

        $logValuesResults = $this->app->queryResults($logValuesSql, $logIDs);

        $logData = array_keys($logValuesResults);

        $this->cartonSumExecute($logData, $logValuesResults, $locCartonResults);

        $totalTime->timer();

        return [];
    }

    /*
    ****************************************************************************
    */

    function cartonSumExecute($invIDs, $logValuesResults, $locCartonResults)
    {
        if (! $invIDs) {
            return;
        }

        $params = array_splice($invIDs, 0, 1000);

        $cartonSql = '
            SELECT    id,
                      batchID,
                      statusID,
                      orderID,
                      uom
            FROM      inventory_cartons ca
            WHERE     NOT isSplit
            AND       NOT unSplit
            AND       id IN (' . $this->app->getQMarkString($params) . ')';

        $ctnResults = $this->app->queryResults($cartonSql, $params);

        $sql = 'INSERT INTO ctn_log_sum (
                    carton_id,
                    batch_id,
                    status_id,
                    order_id,
                    uom,
                    log_id,
                    last_active
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                   batch_id = VALUES(batch_id),
                   status_id = VALUES(status_id),
                   order_id = VALUES(order_id),
                   uom = VALUES(uom),
                   log_id = VALUES(log_id),
                   last_active = VALUES(last_active)
                ';

        $this->app->beginTransaction();

        foreach ($ctnResults as $key => $row) {

            $logID = $logValuesResults[$key]['logID'];

            $logDate = $locCartonResults[$logID]['date'];

            $this->app->runQuery($sql, [
                $key,
                $row['batchID'],
                $row['statusID'],
                $row['orderID'],
                $row['uom'],
                $logID,
                $logDate
            ]);
        }

        $this->app->commit();

        $this->cartonSumExecute($invIDs, $logValuesResults, $locCartonResults);
    }

    /*
    ****************************************************************************
    */

    function lastCartonSumStatus()
    {
        $totalTime = self::init()->timer();

        $logs = new \logs\fields($this->app);

        $lFields = $logs->getFieldID('cartons', ['statusID', 'mStatusID']);

        $hFields = $this->getHistoryFieldID(['statusID', 'mStatusID']);

        //get the max log_id / his_id from sum_last_ctn_sts table
        $sql = 'SELECT  MAX(last_log_id) AS logID,
                        MAX(last_his_id) AS hisID
                FROM    sum_last_ctn_sts';

        $logRes = $this->app->queryResult($sql);

        $maxLog = getDefault($logRes['logID'], 0);

        $this->insertLastStatus([
            'table' => 'logs_values',
            'logsRes' => $this->getLogData($maxLog, 'logs_cartons'),
            'lFieldIDs' => array_values($lFields),
        ]);

        $maxHis = getDefault($logRes['hisID'], 0);
        //get cartons history
        $this->insertLastStatus([
            'table' => 'history',
            'logsRes' => $this->getLogData($maxHis, 'history'),
            'lFieldIDs' => array_values($hFields),
        ]);

        $totalTime->timer();

        return [];
    }

    /*
    ****************************************************************************
    */

    function insertLastStatus($data)
    {
        $table = $data['table'];
        $logsRes = $data['logsRes'];
        $lFieldIDs = $data['lFieldIDs'];

        if (! $logsRes) {
            return;
        }

        $logIDs = array_keys($logsRes);

        $logField = $table == 'history' ? 'id' : 'logID';
        $groupField = $table == 'history' ? 'rowID' : 'primeKey';

        $qMarks = $this->app->getQMarkString($logIDs);

        $sql = 'SELECT    ' . $groupField . ',
                          ' . $groupField . ' AS invID,
                          MAX(' . $logField . ') AS lastID
                FROM      ' . $table . '
                WHERE     ' . $logField . ' IN (' . $qMarks . ')
                AND       fieldID IN (?, ?)
                GROUP BY  ' . $groupField . ' DESC';

        $param = array_merge($logIDs, $lFieldIDs);

        $logData = $this->app->queryResults($sql, $param);

        $this->logLastCartonStatus($logData, $logsRes, $table);
    }

    /*
    ****************************************************************************
    */

    function getLogData($maxValue, $table, $isDate=FALSE)
    {
        $field = $table == 'history' ? 'actionTime' : 'logTime';

        $logDate = $isDate ? 'DATE(' . $field . ')' : $field;

        $sql = 'SELECT  id,
                        ' . $logDate . ' AS date
                FROM    ' . $table . '
                WHERE   id > ?
                LIMIT 10000';

        $results = $this->app->queryResults($sql, [$maxValue]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function logLastCartonStatus($valuesRes, $logsRes, $table)
    {
        if (! $valuesRes) {
            return;
        }

        $params = array_splice($valuesRes, 0, 1000);

        $lastID = $table == 'history' ? 'last_his_id' : 'last_log_id';

        $invIDs = array_column($params, 'invID');
        $lastIDs = array_column($params, 'lastID');

        $lastValues = array_combine($invIDs, $lastIDs);

        $cartonSql = '
            SELECT    ca.id,
                      batchID,
                      statusID,
                      mStatusID,
                      vendorID
            FROM      inventory_cartons ca
            JOIN      inventory_batches  b ON b.id = ca.batchID
            JOIN      inventory_containers co ON co.recNum = b.recNum
            WHERE     NOT isSplit
            AND       NOT unSplit
            AND       ca.id IN (' . $this->app->getQMarkString($invIDs) . ')';

        $ctnRes = $this->app->queryResults($cartonSql, $invIDs);

        $sql = 'INSERT INTO sum_last_ctn_sts (
                        carton_id,
                        batch_id,
                        cust_id,
                        status_id,
                        mStatus_id,
                        ' . $lastID . ',
                        last_update_time
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                       batch_id = VALUES(batch_id),
                       status_id = VALUES(status_id),
                       mStatus_id = VALUES(mStatus_id),
                       ' . $lastID . ' = VALUES(' . $lastID . '),
                       last_update_time = VALUES(last_update_time)
                    ';

        $this->app->beginTransaction();

        foreach ($ctnRes as $invID => $row) {

            $lastID = $lastValues[$invID];

            $logDate = getDefault($logsRes[$lastID]['date']);

            $this->app->runQuery($sql, [
                $invID,
                $row['batchID'],
                $row['vendorID'],
                $row['statusID'],
                $row['mStatusID'],
                $lastID,
                $logDate
            ]);
        }

        $this->app->commit();

        $this->logLastCartonStatus($valuesRes, $logsRes, $table);
    }

    /*
    ****************************************************************************
    */

}
