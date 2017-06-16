<?php

namespace summary;

use tables\orders;
use tables\inventory\cartons;

class create extends model
{
   static $locCartonResults = [];

   /*
    ****************************************************************************
   */

    function makeSummaryTable()
    {
        $totalTime = self::init()->timer();

        $this
            ->activeCartons()
            ->getShippedCartons()
            ->copyBatches()
            ->getMKVols()
            ->copyContainers()
            ->getMKCusts()
            ->combineMKSHP()
            ->recDates()
            ->buRecDates()
            ->swapTables()
            ->updateCtnLog();

        $totalTime->timer('Total Make Time');
    }

    /*
    ****************************************************************************
    */

    function activeCartons()
    {
        $fieldInfo = $this->fieldInfo();
        copyTable::init($this)->standard([
            'sql' => '
                SELECT id,
                       batchID,
                       CURDATE(),
                       uom
                FROM   inventory_cartons
                WHERE  1
                AND    statusID IN ('.$fieldInfo['activeStr'].')
                AND    NOT isSplit
                AND    NOT unsplit
            ',
            'csvFile' => 'activeCartons',
            'truncate' => TRUE,
            'targetTable' => 'ctn_sum_mk',
            'timeMessage' => 'Start Summary Make-Table',
        ]);

        return $this;
    }

    /*
    ****************************************************************************
    */

    function getShippedCartons()
    {
        $fieldInfo = $this->fieldInfo();
        $fieldIDs = $this->getFieldIDs();

        $startBillingDate = $this->app->getVar('startDate');

        $sql = 'SELECT MIN(id) AS id
                FROM   logs_cartons
                WHERE  logTime >= '.$this->db->quote($startBillingDate);

        $firstLog = $this->db->queryResult($sql);

        copyTable::init($this)->standard([
            'sql' => '
                SELECT  ca.id,
                        DATE(logTime) AS logTime,
                        b.id AS batchID,
                        vendorID,
                        width * height * length / 1728 AS vol,
                        uom
                FROM  (
                        SELECT primeKey,
                               MAX(logID) AS maxLogID
                        FROM   logs_values lv
                        WHERE  fieldID = '.$fieldIDs['statusID']['id'].'
                        AND    fromValue != '.$fieldInfo['statusIDs']['SH'].'
                        AND    toValue = '.$fieldInfo['statusIDs']['SH'].'
                        AND    logID >= '.$firstLog['id'].'
                        GROUP BY primeKey
                    ) lv
                JOIN   inventory_cartons ca ON ca.id = lv.primeKey
                JOIN   inventory_batches b ON b.id = ca.batchID
                JOIN   inventory_containers co ON co.recNum = b.recNum
                JOIN   logs_cartons c ON c.id = lv.maxLogID
            ',
            'csvFile' => 'getShippedCartons',
            'truncate' => TRUE,
            'targetTable' => 'ctn_sum_shp',
            'timeMessage' => 'Get cartons shipped since start date',
        ]);

        return $this;
    }

    /*
    ****************************************************************************
    */

    function copyBatches()
    {
        $fields = copyTable::getTemplate('ctn_sum_batch');
        copyTable::init($this)->standard([
            'sql' => '
                SELECT '.implode(', ', $fields).'
                FROM   inventory_batches
            ',
            'csvFile' => 'copyBatches',
            'truncate' => TRUE,
            'targetTable' => 'ctn_sum_batch',
            'timeMessage' => 'Make a copy of the batch table',
        ]);

        return $this;
    }

    /*
    ****************************************************************************
    */

    function getMKVols()
    {
        $this->timer();
        $sql = 'UPDATE ctn_sum_mk s
                JOIN   ctn_sum_batch b ON b.id = s.batch_id
                SET    vol = width * height * length / 1728';

        $this->db->runQuery($sql);

        $this->timer('Get the carton volumes');

        return $this;
    }

    /*
    ****************************************************************************
    */

    function copyContainers()
    {
        copyTable::init($this)->standard([
            'sql' => '
                SELECT recNum,
                       vendorID
                FROM   inventory_containers
            ',
            'csvFile' => 'copyContainers',
            'truncate' => TRUE,
            'targetTable' => 'ctn_sum_cntr_custs',
            'timeMessage' => 'Copy container/customer relationships',
        ]);

        return $this;
    }

    /*
    ****************************************************************************
    */

    function getMKCusts()
    {
        $this->timer();
        $sql = 'UPDATE ctn_sum_mk c
                JOIN   ctn_sum_batch b ON b.id = c.batch_id
                JOIN   ctn_sum_cntr_custs co ON co.rcv_nbr = b.recNum
                SET    c.cust_id = co.cust_id';

        $this->db->runQuery($sql);

        $this->timer('Get the customers for Make-Table');

        return $this;
    }

    /*
    ****************************************************************************
    */

    function combineMKSHP()
    {
        copyTable::init($this)->standard([
            'sql' => '
                SELECT  carton_id,
                        last_active,
                        batch_id,
                        cust_id,
                        vol,
                        uom
                FROM ctn_sum_shp
            ',
            'replace' => TRUE,
            'csvFile' => 'combineMKSHP',
            'targetTable' => 'ctn_sum_mk',
            'timeMessage' => 'Add the shipped cartons to the Make-Table',
            'otherTemplate' => 'ctn_sum_shp',
        ]);

        return $this;
    }

    /*
    ****************************************************************************
    */

    function recDates()
    {
        // Add shp to mk before running this

        $fieldInfo = $this->fieldInfo();
        $fieldIDs = $this->getFieldIDs();

        copyTable::init($this)->standard([
            'sql' => '
                SELECT primeKey,
                       DATE(logTime) AS logTime
                FROM  (
                    SELECT primeKey,
                           MAX(logID) AS maxLogID
                    FROM   logs_values
                    WHERE  fieldID = '.$fieldIDs['statusID']['id'].'
                    AND    fromValue = '.$fieldInfo['statusIDs']['IN'].'
                    AND   ( toValue = '.$fieldInfo['statusIDs']['RC'].'
                    OR     toValue = '.$fieldInfo['statusIDs']['RK'].' )
                    GROUP BY primeKey
                ) lv
                JOIN   logs_cartons c ON c.id = lv.maxLogID
            ',
            'csvFile' => 'recHist',
            'truncate' => TRUE,
            'targetTable' => 'ctn_sum_rec_dt',
            'timeMessage' => 'Copy receiving dates from log tables',
        ]);

        $this->timer();
        $mkSql = 'UPDATE ctn_sum_mk s
                  JOIN   ctn_sum_rec_dt r ON s.carton_id = r.carton_id
                  SET    s.rcv_dt = r.rcv_dt';

        $this->db->runQuery($mkSql);

        $shSql = 'UPDATE ctn_sum_shp cs
                  JOIN   ctn_sum_rec_dt s ON s.carton_id = cs.carton_id
                  SET    cs.rcv_dt = s.rcv_dt';

        $this->db->runQuery($shSql);
        $this->timer('Update the receiving dates found');

        return $this;
    }

    /*
    ****************************************************************************
    */

    function buRecDates()
    {
        $this->timer();

        $mkSql = 'UPDATE ctn_sum_mk s
                JOIN   inventory_batches b ON s.batch_id = b.id
                JOIN   inventory_containers c ON c.recNum = b.recNum
                SET    s.rcv_dt = c.setDate
                WHERE  rcv_dt IS NULL';

        $this->db->runQuery($mkSql);

        $shipSql = 'UPDATE ctn_sum_shp s
                JOIN   inventory_batches b ON s.batch_id = b.id
                JOIN   inventory_containers c ON c.recNum = b.recNum
                SET    s.rcv_dt = c.setDate
                WHERE  rcv_dt IS NULL';

        $this->db->runQuery($shipSql);

        $this->timer('Use container entry date if receiving date wasn\'t found');

        return $this;
    }

    /*
    ****************************************************************************
    */

    function swapTables()
    {
        $this->timer();

        $sql = 'TRUNCATE ctn_sum;
                RENAME TABLE ctn_sum TO ctn_sum_old;
                RENAME TABLE ctn_sum_mk TO ctn_sum;
                RENAME TABLE ctn_sum_old TO ctn_sum_mk';

        $this->db->runQuery($sql);

        $this->timer('Swap in new table');

        return $this;
    }

    /*
    ****************************************************************************
    */

    function updateCtnLog()
    {
        $fieldInfo = $this->fieldInfo();
        $fieldIDs = $this->getFieldIDs();

        $updateStatus = $fieldInfo['activeStr'] . ',' . $fieldInfo['statusIDs']['SH'];

        $this->timer();

        $sql = '
                UPDATE  ctn_sum cs
                JOIN  (
                    SELECT    primeKey,
                              MAX(logID) AS maxLogID,
                              toValue
                    FROM      logs_values
                    WHERE     fieldID = '.$fieldIDs['statusID']['id'].'
                    AND	      toValue IN ('.$updateStatus.')
                    GROUP BY  primeKey, toValue
                ) lv ON lv.primeKey = cs.carton_id
                SET  cs.last_ctn_log_id = lv.maxLogID';

        $this->db->runQuery($sql);

        $this->timer('Update carton last log id');

        return $this;
    }

    /*
    ****************************************************************************
    */

    function getPallets()
    {
        $totalTime = self::init()->timer();

        //get the max start_log_id / last_log_id from stor_sum_plt
        $maxSql = '
            SELECT   MAX(start_log_id) AS startLog,
                     MAX(last_log_id) AS lastLog
            FROM     stor_sum_plt';

        $res = $this->app->queryResult($maxSql);

        $startLog = getDefault($res['startLog'], 0);
        $lastLog = getDefault($res['lastLog'], 0);

        $maxLog = max($startLog, $lastLog);

        $logs = new \logs\fields($this->db);

        $fieldID = $logs->getFieldID('cartons', 'plate');

        $cartonFieldID = $logs->getFieldID('cartons');

        $status = new \tables\statuses\inventory($this->app);

        $stsName = [
            cartons::STATUS_RACKED,
            cartons::STATUS_SHIPPING,
            cartons::STATUS_SHIPPED,
        ];

        $stsResult = $status->getStatusIDs($stsName);

        $statusID = array_column($stsResult, 'id');

        $sql = 'SELECT  id,
                        DATE(logTime) AS logDate
                FROM    logs_cartons
                WHERE   id > ?
                LIMIT 10000';

        self::$locCartonResults = $this->app->queryResults($sql, [$maxLog]);

        if (! self::$locCartonResults) {

            $totalTime->timer();

            return [];
        }

        $logIDs = array_keys(self::$locCartonResults);

        $this->logPallets([
            'logIDs' => $logIDs,
            'fieldID' => $fieldID,
            'statusID' => $statusID,
            'cartonFieldID' => $cartonFieldID,
        ]);

        $totalTime->timer();

        return [];
    }

    /*
    ****************************************************************************
    */

    function logPallets($data)
    {
        $logIDs = $data['logIDs'];
        $fieldID = $data['fieldID'];
        $statusID = $data['statusID'];
        $cartonFieldID = $data['cartonFieldID'];

        if (! $logIDs) {
            return;
        }

        $logParam = $processIDs = array_splice($logIDs, 0, 500);

        $data['logIDs'] = $logIDs;

        array_unshift($logParam, $fieldID);

        //1 - GETTING plate start Log ID - fieldID = 2
        $this->getStartLogPlate($processIDs, $logParam);

        //2 - GETTING plate change Log ID
        $this->getChangeLogPlate($processIDs, $logParam);

        //3 - GETTING plate for cartons shipped - Online order
        array_unshift($statusID, $cartonFieldID);

        $params = array_merge($statusID, $processIDs);

        $this->getOnlineLogPlate($processIDs, $params);

        //4 - Update plates start date with container setDate if it's NULL
        $this->pltStartDates();

        $this->logPallets($data);
    }

    /*
    ****************************************************************************
    */

    function pltStartDates()
    {
        $sql = '
            UPDATE stor_sum_plt s
            JOIN   inventory_cartons ca ON ca.plate = s.plate
            JOIN   inventory_batches b ON b.id = ca.batchID
            JOIN   inventory_containers c ON c.recNum = b.recNum
            SET    s.rcv_dt = c.setDate
            WHERE  rcv_dt IS NULL';

        $this->db->runQuery($sql);

        return $this;
    }

    /*
    ****************************************************************************
    */

    static function fromPlatesSql()
    {
        return '
              INSERT INTO stor_sum_plt (
                        plate,
                        cust_id,
                        last_log_id,
                        last_active
                    ) VALUES (
                        ?, ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                       cust_id = VALUES(cust_id),
                       last_log_id = VALUES(last_log_id),
                       last_active = VALUES(last_active)
            ';
    }

    /*
    ****************************************************************************
    */

    static function toPlatesql()
    {
        return '
              INSERT INTO stor_sum_plt (
                        plate,
                        cust_id,
                        start_log_id,
                        rcv_dt
                    ) VALUES (
                        ?, ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                       cust_id = VALUES(cust_id),
                       start_log_id = VALUES(start_log_id),
                       rcv_dt = VALUES(rcv_dt)
            ';
    }

    /*
    ****************************************************************************
    */

    static function selectCartonSql()
    {
        return '
                SELECT    ca.id,
                          co.vendorID AS custID
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                WHERE     ca.id IN
            ';
    }

    /*
    ****************************************************************************
    */

    function getStartLogPlate($logIDs, $logParam)
    {
        $startLogValuesSql = '
            SELECT  primeKey,
                    toValue AS plate,
                    logID
            FROM    logs_values
            WHERE   NOT fromValue
            AND     fieldID = ?
            AND     logID IN (' . $this->app->getQMarkString($logIDs) . ')';

        $startLogValRes = $this->app->queryResults($startLogValuesSql, $logParam);

        if (! $startLogValRes) {
            return;
        }

        $invIDs = array_keys($startLogValRes);

        $qMarks = $this->app->getQMarkString($invIDs);

        $cartonSql = self::selectCartonSql() . '(' . $qMarks . ')';

        $ctnResults = $this->app->queryResults($cartonSql, $invIDs);

        //$pltLogs
        $plts = array_column($startLogValRes, 'plate');
        $logs = array_column($startLogValRes, 'logID');
        $pltLogs = array_combine($plts, $logs);

        foreach ($startLogValRes as $key => $row) {
            if (! isset($ctnResults[$key])) {
                // prime key may be missing in the logs_values file for the plate
                continue;
            }

            $plate = $row['plate'];
            $pltCust[$plate] = $ctnResults[$key]['custID'];
        }

        //insert into stor_sum_plt
        $this->app->beginTransaction();

        foreach ($pltLogs as $key => $logID) {
            if (! isset($pltCust[$key])) {
                // prime key may be missing in the logs_values file for the plate
                continue;
            }

            $logDate = getDefault(self::$locCartonResults[$logID]['logDate']);
            $custID = $pltCust[$key];

            $this->app->runQuery(self::toPlatesql(), [
                $key,
                $custID,
                $logID,
                $logDate
            ]);
        }

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function getChangeLogPlate($logIDs, $logParam)
    {
        $changePlateLogSql = '
            SELECT    primeKey,
                      logID,
                      fromValue,
                      toValue
            FROM      logs_values
            WHERE     fromValue
            AND       fieldID = ?
            AND       logID IN (' . $this->app->getQMarkString($logIDs) . ')';


        $changePlateLogRes = $this->app->queryResults($changePlateLogSql, $logParam);

        if (! $changePlateLogRes) {
            return;
        }

        $invIDs = array_keys($changePlateLogRes);

        $qMarks = $this->app->getQMarkString($invIDs);

        $cartonSql = self::selectCartonSql() . '(' . $qMarks . ')';

        $ctnResults = $this->app->queryResults($cartonSql, $invIDs);

        //fromPltLogs and toPltLogs
        $toPlts = array_column($changePlateLogRes, 'toValue');
        $logs = array_column($changePlateLogRes, 'logID');
        $toPltLogs = array_combine($toPlts, $logs);

        $fromPlts = array_column($changePlateLogRes, 'fromValue');
        $fromPltLogs = array_combine($fromPlts, $logs);

        //check fromValue plates had any cartons

        $uniquePlates = array_unique($fromPlts);

        $pallets = array_values($uniquePlates);

        $fromPlateSql = '
            SELECT    plate
            FROM      inventory_cartons ca
            JOIN      statuses s ON s.id = ca.statusID
            WHERE     s.shortName = "' . cartons::STATUS_RACKED . '"
            AND       NOT isSplit
            AND       NOT unSplit
            AND       plate IN (' . $this->app->getQMarkString($pallets) . ')
            GROUP BY  plate';

        $fromPlateRes = $this->app->queryResults($fromPlateSql, $pallets);

        foreach ($changePlateLogRes as $key => $row) {

            $fromPlate = $row['fromValue'];
            $toPlate = $row['toValue'];

            $fromPltCust[$fromPlate] = $toPltCust[$toPlate] =
                       $ctnResults[$key]['custID'];
        }

        //insert into stor_sum_plt
        $this->app->beginTransaction();

        foreach ($fromPltLogs as $key => $logID) {

            $custID = $fromPltCust[$key];

            $logID = array_key_exists($key, $fromPlateRes) ? NULL : $logID;

            $logDate = array_key_exists($key, $fromPlateRes) ? NULL :
                    getDefault(self::$locCartonResults[$logID]['logDate']);

            $this->app->runQuery(self::fromPlatesSql(), [
                $key,
                $custID,
                $logID,
                $logDate
            ]);
        }

        foreach ($toPltLogs as $key => $logID) {

            $logDate = getDefault(self::$locCartonResults[$logID]['logDate']);

            $custID = $toPltCust[$key];

            $this->app->runQuery(self::toPlatesql(), [
                $key,
                $custID,
                $logID,
                $logDate
            ]);
        }

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function getOnlineLogPlate($logIDs, $params)
    {
        $shipPlts = [];

        $shipPlateLogSql = '
            SELECT    primeKey,
                      logID
            FROM      logs_values
            WHERE     fieldID = ?
            AND       fromValue IN (?, ?)
            AND       toValue = ?
            AND       logID IN (' . $this->app->getQMarkString($logIDs) . ')';

        $shipPlateLogRes = $this->app->queryResults($shipPlateLogSql, $params);

        if (! $shipPlateLogRes) {
            return;
        }

        $invIDs = array_keys($shipPlateLogRes);

        $cartonPlateSql = '
            SELECT    ca.id,
                      plate,
                      co.vendorID AS custID
            FROM      inventory_cartons ca
            JOIN      inventory_batches b ON b.id = ca.batchID
            JOIN      inventory_containers co ON co.recNum = b.recNum
            JOIN      statuses s ON s.id = ca.statusID
            WHERE     s.shortName = "' . cartons::STATUS_SHIPPED . '"
            AND       NOT isSplit
            AND       NOT unSplit
            AND       ca.id IN (' . $this->app->getQMarkString($invIDs) . ')
            ';

        $cartonPlateRes = $this->app->queryResults($cartonPlateSql, $invIDs);

        if (! $cartonPlateRes) {
            return;
        }

        $shipPlates = array_column($cartonPlateRes, 'plate');

        //check shipped carton plates had any cartons
        $checkPlateSql = '
            SELECT    plate
            FROM      inventory_cartons ca
            JOIN      statuses s ON s.id = ca.statusID
            WHERE     s.shortName = "' . cartons::STATUS_RACKED . '"
            AND       NOT isSplit
            AND       NOT unSplit
            AND       plate IN (' . $this->app->getQMarkString($shipPlates) . ')
            GROUP BY  plate';

        $checkPlateRes = $this->app->queryResults($checkPlateSql, $shipPlates);

        foreach ($cartonPlateRes as $key => $row) {
            $plate = $row['plate'];

            $shipPlts[$plate]['logID'] = $shipPlateLogRes[$key]['logID'];
            $shipPlts[$plate]['custID'] = $row['custID'];
        }

        //insert into stor_sum_plt
        $this->app->beginTransaction();

        foreach ($shipPlts as $key => $row) {

            $custID = $row['custID'];

            $logID = array_key_exists($key, $checkPlateRes) ? NULL
                         : $row['logID'];

            $logDate = array_key_exists($key, $checkPlateRes) ? NULL
                     : getDefault(self::$locCartonResults[$logID]['logDate']);

            $this->app->runQuery(self::fromPlatesSql(), [
                $key,
                $custID,
                $logID,
                $logDate
            ]);
        }

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function getOutboundOrders()
    {
        $totalTime = self::init()->timer();

        $logIDs = $this->getLogIDs('outbound_sum', 'logs_orders');

        if (! $logIDs) {

            $totalTime->timer();

            return [];
        }

        $statuses = new \tables\statuses\orders($this->app);

        $fields = [
            'chk_in_dt' => orders::STATUS_ENTRY_CHECK_IN,
            'chk_out_dt' => orders::STATUS_ENTRY_CHECK_OUT,
            'proc_out_dt' => orders::STATUS_PROCESSING_CHECK_OUT,
            'pick_out_dt' => orders::STATUS_PICKING_CHECK_OUT,
            'proc_out_dt' => orders::STATUS_PROCESSING_CHECK_OUT,
            'ship_in_dt' => orders::STATUS_SHIPPING_CHECK_IN,
            'ship_out_dt' => orders::STATUS_SHIPPED_CHECK_OUT,
        ];

        $dateFields = array_flip($fields);

        $statusParams = array_keys($dateFields);

        $statusIDs = $statuses->getStatusIDs($statusParams);

        $statusKeys = array_keys($statusIDs);

        $statusValues = array_column($statusIDs, 'id');

        $statusCodes = array_combine($statusValues, $statusKeys);

        $outboundData = $this->getLogData([
            'reportFields' => [
                'statusID' => [
                    'fields' => $fields,
                ],
            ],
            'logIDs' => $logIDs,
            'category' => 'orders',
            'statusIDs' => $statusIDs,
        ]);

        $this->app->beginTransaction();

        foreach ($outboundData as $values) {

            $statusID = $values['statusID'];

            $statusCode = $statusCodes[$statusID];

            $field = $dateFields[$statusCode];

            $sql = 'INSERT INTO outbound_sum (
                        log_id,
                        ord_id,
                        ' . $field . '
                    ) VALUES (
                        ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                       log_id = VALUES(log_id),
                       ord_id = VALUES(ord_id),
                       ' . $field . ' = VALUES(' . $field . ')
                    ';

            $this->app->runQuery($sql, [
                $values['logID'],
                $values['primeKey'],
                $values['logDate'],
            ]);
        }

        $this->app->commit();

        $totalTime->timer();

        return [];
    }

    /*
    ****************************************************************************
    */

    function getStyleHistory()
    {
        $totalTime = self::init()->timer();

        $logIDs = $this->getLogIDs('style_his_sum', 'logs_cartons');

        if (! $logIDs) {

            $totalTime->timer();

            return [];
        }

        $cartons = new cartons($this->app);
        $statuses = new \tables\statuses\inventory($this->app);

        $dateFields = [
            cartons::STATUS_RACKED => 'rack_dt',
            cartons::STATUS_SHIPPED => 'ship_dt',
            cartons::STATUS_RESERVED => 'alloc_dt',
        ];

        $statusParams = array_keys($dateFields);

        $statusIDs = $statuses->getStatusIDs($statusParams);

        $this->logStyleHistory([
            'logIDs' => $logIDs,
            'cartons' => $cartons,
            'statusIDs' => $statusIDs,
            'dateFields' => $dateFields,
        ]);

        $totalTime->timer();

        return [];
    }

    /*
    ****************************************************************************
    */

    function logStyleHistory($data)
    {
        $logIDs = $data['logIDs'];
        $cartons = $data['cartons'];
        $statusIDs = $data['statusIDs'];
        $dateFields = $data['dateFields'];

        if (! $logIDs) {
            return;
        }

        $processIDs = array_splice($logIDs, 0, 500);

        $data['logIDs'] = $logIDs;

        $logData = $this->getLogData([
            'reportFields' => [
                'statusID' => [
                    'fields' => [
                        'rack_dt' => cartons::STATUS_RACKED,
                        'ship_dt' => cartons::STATUS_SHIPPED,
                    ],
                ],
                'mStatusID' => [
                    'fields' => [
                        'alloc_dt_rs' => cartons::STATUS_RESERVED,
                        'alloc_dt_rk' => cartons::STATUS_RACKED,
                    ],
                    'subGroupBy' => 'primeKey,
                                     logID DESC',
                    'groupBy' => 'primeKey',
                ],
            ],
            'logIDs' => $processIDs,
            'category' => 'cartons',
            'statusIDs' => $statusIDs,
        ]);

        if (! $logData) {
            return [];
        }

        $statusKeys = array_keys($statusIDs);

        $statusValues = array_column($statusIDs, 'id');

        $statusCodes = array_combine($statusValues, $statusKeys);

        $this->logStyleHistoryExecute([
            'logData' => $logData,
            'ucc128' => $cartons->fields['ucc128']['select'],
            'statusCodes' => $statusCodes,
            'statusIDs' => $statusIDs,
            'dateFields' => $dateFields,
        ]);

        $this->logStyleHistory($data);
    }

    /*
    ****************************************************************************
    */

    function logStyleHistoryExecute($data)
    {
        $logData = $data['logData'];
        $ucc128 = $data['ucc128'];
        $statusCodes = $data['statusCodes'];
        $statusIDs = $data['statusIDs'];
        $dateFields = $data['dateFields'];

        if (! $logData) {
            return;
        }

        $processIDs = array_splice($logData, 0, 500);

        $data['logData'] = $logData;

        $invIDs = array_column($processIDs, 'primeKey');

        $sql = 'SELECT    ca.id,
                          sku,
                          vendorID,
                          DATE(setDate) AS rcv_dt,
                          `name`,
                          ' . $ucc128 . ' AS ucc,
                          na.scanordernumber AS alloc_ord,
                          ns.scanordernumber AS ship_ord
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      pick_cartons pc ON pc.cartonID = ca.id
                JOIN      upcs u ON u.id = b.upcID
                JOIN      neworder na ON na.id = pc.orderID
                LEFT JOIN neworder ns ON ns.id = ca.orderID
                WHERE     ca.id IN (' . $this->app->getQMarkString($invIDs) . ')
                ';

        $results = $this->app->queryResults($sql, $invIDs);

        $this->app->beginTransaction();

        foreach ($processIDs as $values) {

            $invID = $values['primeKey'];

            if (! isset($results[$invID])) {
                continue;
            }

            $statusID = $values['statusID'];

            $statusCode = $statusCodes[$statusID];

            $field = $values['keyField'] == 'mStatusID' ? 'alloc_dt' :
                    $dateFields[$statusCode];

            $sql = 'INSERT INTO style_his_sum (
                        log_id,
                        carton_id,
                        `name`,
                        cust_id,
                        sku,
                        ucc128,
                        alloc_ord,
                        ship_ord,
                        rcv_dt,
                        ' . $field . '
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                        log_id = ?,
                        ' . $field . ' = IF(' . $field . ' IS NULL,
                                            ?, VALUES(' . $field . ')
                                         ),
                        alloc_ord = IF(alloc_ord IS NULL, ?, VALUES(alloc_ord)),
                        ship_ord = IF(ship_ord IS NULL, ?, VALUES(ship_ord))
                    ';

            $cartonData = $results[$invID];

            $logDate = $values['logDate'];

            if ($values['keyField'] == 'mStatusID'
             && $values['statusID'] == $statusIDs[cartons::STATUS_RACKED]['id']) {

                $logDate = $cartonData['alloc_ord'] = NULL;
            }

            $this->app->runQuery($sql, [
                $values['logID'],
                $invID,
                $cartonData['name'],
                $cartonData['vendorID'],
                $cartonData['sku'],
                $cartonData['ucc'],
                $cartonData['alloc_ord'],
                $cartonData['ship_ord'],
                $cartonData['rcv_dt'],
                $logDate,
                $values['logID'],
                $logDate,
                $cartonData['alloc_ord'],
                $cartonData['ship_ord'],
            ]);
        }

        $this->app->commit();

        $this->logStyleHistoryExecute($data);
    }

    /*
    ****************************************************************************
    */

    function getLogData($data)
    {
        $reportFields = $data['reportFields'];
        $logIDs = $data['logIDs'];
        $category = $data['category'];
        $statusIDs = $data['statusIDs'];

        $fields = new \logs\fields($this->app);

        $statusKeys = $dateFields = $statusParams = $unions = $params = [];

        $fieldNames = array_keys($reportFields);

        $fieldResults = $fields->getFieldID($category, $fieldNames);

        foreach ($reportFields as $fieldName => $values) {

            $statusCodes = array_values($values['fields']);

            $statusParams = array_merge($statusParams, $statusCodes);
        }

        $logQMarks = $this->app->getQMarkString($logIDs);

        foreach ($reportFields as $fieldName => $values) {

            $statusKeys = [];

            foreach ($values['fields'] as $status) {
                $statusKeys[] = $statusIDs[$status]['id'];
            }

            $statusQMarks = $this->app->getQMarkString($statusKeys);

            $fieldID = $fieldResults[$fieldName];

            $subGroupBy = getDefault($values['subGroupBy'], 'primeKey,
                                                             statusID,
                                                             logID ASC');

            $groupBy = getDefault($values['groupBy'], 'primeKey,
                                                       statusID');

            $unions[] = '
                SELECT *
                FROM (
                    SELECT    lv.id,
                              "' . $fieldName . '" AS keyField,
                              logID,
                              primeKey,
                              toValue AS statusID,
                              DATE(logTime) AS logDate
                    FROM      logs_' . $category . ' l
                    JOIN      logs_values lv ON lv.logID = l.id
                    WHERE     toValue IN (' . $statusQMarks . ')
                    AND       fieldID = ?
                    AND       logID IN (' . $logQMarks . ')
                    AND       fromValue != toValue
                    GROUP BY  ' . $subGroupBy . '
                ) lv
                GROUP BY  ' . $groupBy;

            $params = array_merge($params, $statusKeys, [$fieldID], $logIDs);
        }

        $sql = implode(' UNION ', $unions);

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getStorageSummary()
    {
        $totalTime = self::init()->timer();

        $logs = new \logs\fields($this->db);

        $fieldID = $logs->getFieldID('cartons');

        //get the max log_id from ctn_sum
        $logIDs = $this->getLogIDs('ctn_sum', 'logs_cartons', 'last_ctn_log_id');

        if (! $logIDs) {
            return [];
        }

        //new cartons
        $status = new \tables\statuses\inventory($this->db);
        //New cartons
        $stsName = [
            cartons::STATUS_INACTIVE,
            cartons::STATUS_LOCKED,
            cartons::STATUS_RECEIVED,
            cartons::STATUS_RACKED,
        ];

        $stsResult = $status->getStatusIDs($stsName);

        $newStatusID = array_column($stsResult, 'id');

        $sql = 'SELECT     primeKey,
                           logID,
                           DATE(logTime) AS logDate
                FROM       logs_values lv
                JOIN       logs_cartons lc ON lc.id = lv.logID
                WHERE      logID IN (' . $this->db->getQMarkString($logIDs) . ')
                AND        fieldID = ?
                AND        fromValue IN (?, ?)
                AND        toValue IN (?, ?)
                ';

        $logParam = array_merge($logIDs, [$fieldID], $newStatusID);

        $logValues = $this->db->queryResults($sql, $logParam);

        $this->logStorageSummary([
            'logData' => array_keys($logValues),
            'fieldID' => $fieldID,
            'logIDs' => $logIDs,
            'logValues' => $logValues,
        ]);

        $totalTime->timer();

        return [];
    }

    /*
    ****************************************************************************
    */

    function logStorageSummary($data)
    {
        $logData = $data['logData'];
        $fieldID = $data['fieldID'];
        $logIDs = $data['logIDs'];
        $logValues = $data['logValues'];

        if (! $logData) {
            return;
        }

        $invIDs = array_splice($logData, 0, 1000);

        $data['logData'] = $logData;

        $sql = 'SELECT  ca.id,
                        vendorID,
                        batchID,
                        uom,
                        width * height * length / 1728 AS vol
                FROM    inventory_cartons ca
                JOIN    inventory_batches b ON b.id = ca.batchID
                JOIN    inventory_containers c ON c.recNum = b.recNum
                WHERE   ca.id IN (' . $this->db->getQMarkString($invIDs) . ')
                AND     NOT isSplit
                AND     NOT unsplit
                ';

        $results = $this->db->queryResults($sql, $invIDs);

        //ship cartons
        $update = $this->updateLogCarton($fieldID, $logIDs);

        $this->app->beginTransaction();
        //insert new carton to ctn_sum table
        foreach ($results as $invID => $row) {

            $sql = 'INSERT INTO ctn_sum (
                        carton_id,
                        batch_id,
                        cust_id,
                        rcv_dt,
                        last_ctn_log_id,
                        last_active,
                        vol,
                        uom
                    ) VALUES (
                        ?, ?, ?, ?,
                        ?, ?, ?, ?
                    ) ON DUPLICATE KEY UPDATE
                        last_ctn_log_id = VALUES(last_ctn_log_id),
                        last_active = VALUES(last_active)
                    ';

            $this->app->runQuery($sql, [
                    $invID,
                    $row['batchID'],
                    $row['vendorID'],
                    $logValues[$invID]['logDate'],
                    $logValues[$invID]['logID'],
                    $logValues[$invID]['logDate'],
                    $row['vol'],
                    $row['uom'],
            ]);
        }

        //update  cartons in ctn_sum table
        $sql = 'UPDATE    ctn_sum
                SET       last_ctn_log_id = ?,
                          last_active = ?
                WHERE     carton_id = ?';

        foreach ($update as $invID => $row) {
            $this->db->runQuery($sql, [
                $row['logID'],
                $row['logDate'],
                $invID,
            ]);
        }

        $this->app->commit();

        $this->logStorageSummary($data);
    }

    /*
    ****************************************************************************
    */

    function updateLogCarton($fieldID, $logIDs)
    {
        $status = new \tables\statuses\inventory($this->db);

        $stsName = [
            cartons::STATUS_RACKED,
            cartons::STATUS_PICKED,
            cartons::STATUS_ORDER_PROCESSING,
            cartons::STATUS_SHIPPING,
            cartons::STATUS_SHIPPED
        ];

        $stsResult = $status->getStatusIDs($stsName);

        $statusID = array_column($stsResult, 'id');

        $qMarks = $this->db->getQMarkString($statusID);

        $logValuesSql = '
            SELECT  primeKey,
                    maxLogID AS logID,
                    DATE(logTime) AS logDate
            FROM
            (
                SELECT     primeKey,
                           MAX(logID) AS maxLogID
                FROM       logs_values lv
                WHERE      logID IN (' . $this->db->getQMarkString($logIDs) . ')
                AND        fieldID = ?
                AND        toValue IN (' . $qMarks . ')
                GROUP BY   primeKey
            ) lv
            JOIN   logs_cartons c ON c.id = lv.maxLogID
            ';

        $logParam = array_merge($logIDs, [$fieldID], $statusID);

        $updateLogValues = $this->db->queryResults($logValuesSql, $logParam);

        return $updateLogValues;
    }

    /*
    ****************************************************************************
    */

    function updateCartonDate()
    {
        $totalTime = self::init()->timer();

        $status = new \tables\statuses\inventory($this->db);

        //New cartons
        $stsName = [
            cartons::STATUS_RECEIVED,
            cartons::STATUS_RACKED,
            cartons::STATUS_RESERVED,
            cartons::STATUS_PICKED,
            cartons::STATUS_ORDER_PROCESSING,
            cartons::STATUS_SHIPPING,
            cartons::STATUS_LOCKED
        ];

        $stsResult = $status->getStatusIDs($stsName);

        $existStatusID = array_column($stsResult, 'id');

        $qMarks = $this->db->getQMarkString($existStatusID);

        $sql = '
            UPDATE  ctn_sum cs
            JOIN    inventory_cartons c ON c.id = cs.carton_id
            SET     cs.last_active = CURDATE()
            WHERE   statusID IN (' . $qMarks . ')';

        $this->db->runQuery($sql, $existStatusID);

        $totalTime->timer();

        return [];
    }

    /*
    ****************************************************************************
    */

}
