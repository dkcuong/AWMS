<?php

namespace summary;

use pdo;

class model
{
    const USE_REMOTE_DB = TRUE;

    public $db;
    public $path;
    public $file;
    public $batch = [];
    public $startTime;
    public $fieldInfo = [];
    public $getFieldIDs = [];

    const INSERT_BATCH = 5000;

    /*
    ****************************************************************************
    */

    static function init($app=NULL)
    {
        $self = new static();
        $self->app = $app;
        $self->db = $app;
        $self->path = \models\directories::getDir('uploads', 'invSums');
        return $self;
    }

    /*
    ****************************************************************************
    */

    function getProp($prop)
    {
        switch ($prop) {
            case 'db':
                return $this->db;
            case 'path':
                return $this->path;
        }
    }

    /*
    ****************************************************************************
    */

    function fieldInfo()
    {
        if ($this->fieldInfo) {
            return $this->fieldInfo;
        }

        $sql = 'SELECT  id,
                        shortName
                FROM    statuses
                WHERE   category = "inventory"
                AND     shortName IN (
                    "RC", "OP", "RK", "LS", "RS", "PK", "IN", "SH", "DS"
                )';

        $statuses = [];
        foreach ($this->db->query($sql) as $row) {
            $id = $row['id'];
            $statuses[$id] = $row;
        }

        $ids = array_keys($statuses);
        $shortNames = array_column($statuses, 'shortName');
        $statusIDs = array_combine($shortNames, $ids);


        $inactiveIDs = array_intersect_key($statusIDs, [
            'DS' => TRUE,
            'SH' => TRUE,
            'IN' => TRUE,
        ]);

        $inactiveIDs[] = 0;

        $activeIDs = array_diff_key($statusIDs, $inactiveIDs);

        $this->fieldInfo = [
            'statuses' => $statuses,
            'statusIDs' => $statusIDs,
            'activeStr' => implode(',', $activeIDs),
            'inactiveStr' => implode(',', $inactiveIDs),
        ];

        return $this->fieldInfo;
    }

    /*
    ****************************************************************************
    */

    function getFieldIDs()
    {
        if ($this->getFieldIDs) {
            return $this->getFieldIDs;
        }

        $sql = 'SELECT  displayName,
                        id
                FROM    logs_fields
                WHERE   category = "cartons"
                AND     displayName IN (
                    "isSplit", "statusID", "unSplit", "plate"
                )';

        foreach ($this->db->query($sql) as $row) {
            $display = $row['displayName'];
            $this->getFieldIDs[$display] = $row;
        }

        return $this->getFieldIDs;
    }

    /*
    ****************************************************************************
    */

    function logRange($targetDate)
    {
        $sql = 'SELECT id,
                        MIN(id) AS minID,
                        MAX(id) AS maxID
                FROM  logs_cartons
                WHERE DATE(logTIME) = ?';

        return $this->db->queryResult($sql, [$targetDate]);
    }

    /*
    ****************************************************************************
    */

    function export($params)
    {
        file_put_contents($params['file'], NULL);

        $bathcSize = getDefault($params['batchSize'], self::INSERT_BATCH);

        $this->batch = [];
        foreach ($this->query($params['sql'], pdo::FETCH_ASSOC) as $row) {
            $callback = $params['callback'];
            $this->$callback($row);

            if (count($this->batch) > $bathcSize) {
                self::writeQuery($params);
                $this->batch = [];
            }
        }

        self::writeQuery($params);
    }

    /*
    ****************************************************************************
    */

    function loopWrite($table, $sql, $file, $fields)
    {
        file_put_contents($file, NULL);

        foreach ($this->query($sql, pdo::FETCH_ASSOC) as $row) {

            $batch[] = '('.implode(', ', $row).')';

            if (count($batch) > self::INSERT_BATCH) {

                self::writeInsert($table, $fields, $batch, $file);

                $batch = [];
            }
        }

        self::writeInsert($table, $fields, $batch, $file);
    }

    /*
    ****************************************************************************
    */

    function writeQuery($params)
    {
        $string = $params['start'].' '.implode(', '.PHP_EOL, $this->batch) .
            ';'.PHP_EOL;
        file_put_contents($params['file'], $string, FILE_APPEND);
    }

    /*
    ****************************************************************************
    */

    static function writeInsert($table, $fields, $batch, $file)
    {
        $sql = 'INSERT INTO '.$table.' ('.implode(', ', $fields).')
                VALUES '.implode(', ', $batch).';';

        file_put_contents($file, $sql, FILE_APPEND);
    }

    /*
    ****************************************************************************
    */

    function quoteRow($row)
    {
        $quoted = array_map([$this->db, 'quote'], $row);
        $this->batch[] = '('.implode(', ', $quoted).')';
    }

    /*
    ****************************************************************************
    */

    function timer($message=FALSE)
    {
        if ($this->startTime) {
            $period = timeThis($this->startTime);
            $message ? varDump([
                'subject' => $message,
                'time' => $period,
            ]) : varDump($period);
            $this->startTime = FALSE;
        } else {
            $this->startTime = timeThis();
        }

        return $this;
    }

    /*
    ****************************************************************************
    */

    function getHistoryFieldID($field='statusID')
    {
        $params = is_array($field) ? $field : [$field];

        $qMarks = $this->app->getQMarkString($params);

        $sql = '
            SELECT  id,
                    displayName
            FROM    history_fields
            WHERE   displayName IN (' . $qMarks . ')
            ';

        $results = $this->app->queryResults($sql, $params);

        if (is_array($field)) {

            $keys = array_keys($results);

            $values = array_column($results, 'displayName');

            return array_combine($values, $keys);
        } else {
            return key($results);
        }
    }

    /*
    ****************************************************************************
    */

    function getLogIDs($summaryTable, $logTable, $field='log_id')
    {
        $maxSql = 'SELECT    MAX(' . $field . ') AS maxLogID
                   FROM      ' . $summaryTable;

        $maxLogResult = $this->app->queryResult($maxSql);

        $lastLogID = intVal($maxLogResult['maxLogID']);

        $logSql = 'SELECT    id
                   FROM      ' . $logTable . '
                   WHERE     id > ?
                   LIMIT 10000';

        $results = $this->app->queryResults($logSql, [$lastLogID]);

        return array_keys($results);
    }

    /*
    ****************************************************************************
    */
}
