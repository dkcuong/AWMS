<?php

namespace tables;

class history extends _default
{

    const CHANGE_ACTION_ID = 2;

    public $ajaxModel = 'history';

    public $primaryKey = 'h.id';

    public $fields = [
        'actionTime' => [
            'display' => 'Time',
        ],
        'username' => [
            'select' => 'u.username',
            'display' => 'User',
        ],
        'action' => [
            'select' => 'a.displayName',
            'display' => 'Modified',
        ],
        'model' => [
            'select' => 'm.displayName',
            'display' => 'Field',
        ],
        'target' => [
            'select' => 'CASE m.displayName
                WHEN "inventory\\\\containers" THEN i.name
                WHEN "inventory\\\\batches" THEN i.batchID
                WHEN "inventory\\\\cartons" THEN CONCAT(
                    i.vendorID,
                    i.batchID,
                    LPAD(i.uom, 3, 0),
                    LPAD(i.cartonID, 4, 0)
                )
                WHEN "orders" THEN tno.scanOrderNumber
                WHEN "onlineOrders" THEN oo.SCAN_SELDAT_ORDER_NUMBER
                WHEN "workOrders" THEN wh.wo_num
                WHEN "users" THEN tu.username
                ELSE "Table model is not in hisotry query"
            END',
            'display' => 'Previous Value',
        ],
        'changedField' => [
            'select' => 'f.displayName',
            'display' => 'New Value',
        ],
        'fromValue' => [
            'select' => 'IF(
                fromHistoryValues,
                CASE cfva.displayName
                    WHEN "0" THEN "<i>Unselected</i>"
                    WHEN "" THEN "<i>Empty</i>"
                    ELSE cfva.displayName
                END,
                CASE f.displayName
                    WHEN "level" THEN cfl.displayName
                    WHEN "vendor" THEN cfv.vendorName
                    WHEN "vendorID" THEN cfv.vendorName
                    WHEN "userID" THEN cfu.username
                    WHEN "dealSiteID" THEN cfd.displayName
                    ELSE CONCAT("Missing ", f.displayName, " case for from-values")
                END
            )',
        ],
        'toValue' => [
            'select' => 'IF(
                toHistoryValues,
                IF(ctva.displayName = "0", "Unselected", ctva.displayName),
                CASE f.displayName
                    WHEN "level" THEN ctl.displayName
                   -- WHEN "statuses" THEN cts.displayName
                    WHEN "vendorID" THEN ctv.vendorName
                    WHEN "vendor" THEN ctv.vendorName
                    WHEN "userID" THEN ctu.username
                    WHEN "dealSiteID" THEN ctd.displayName
                    ELSE CONCAT("Missing ", f.displayName, " case for to-values")
                END
            )',
        ],
    ];

    static public $refTables = [
        'modelID' => 'history_models',
        'fieldID' => 'history_fields',
        'toValueID' => 'history_values',
        'fromValueID' => 'history_values',
    ];

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'history h
               LEFT JOIN '.$userDB.'.info u      ON u.id = h.userID
               LEFT JOIN history_actions a       ON h.actionID = a.id
               LEFT JOIN history_models m        ON h.modelID = m.id
               LEFT JOIN history_fields f        ON h.fieldID = f.id

               LEFT JOIN (
                    SELECT    ca.id AS id,
                              co.name,
                              co.vendorID,
                              b.id AS batchID,
                              ca.uom,
                              ca.cartonID
                    FROM      inventory_containers co
                    LEFT JOIN inventory_batches b ON b.recNum = co.recNum
                    LEFT JOIN inventory_cartons ca ON ca.batchID = b.id
               ) AS i                            ON i.id = h.rowID
               LEFT JOIN neworder tno            ON tno.id = h.rowID
               LEFT JOIN online_orders oo        ON oo.id = h.rowID
               LEFT JOIN wo_hdr wh               ON wh.wo_id = h.rowID
               LEFT JOIN '.$userDB.'.info tu     ON tu.id = h.rowID

               LEFT JOIN user_levels cfl         ON h.fromValueID = cfl.id
               LEFT JOIN vendors cfv             ON h.fromValueID = cfv.id
               -- LEFT JOIN statuses cfs          ON cfs.id
               LEFT JOIN history_values cfva     ON h.fromValueID = cfva.id
               LEFT JOIN '.$userDB.'.info cfu                ON h.fromValueID = cfu.id
               LEFT JOIN deal_sites cfd          ON h.fromValueID = cfd.id

               LEFT JOIN user_levels ctl         ON h.toValueID = ctl.id
               LEFT JOIN vendors ctv             ON h.toValueID = ctv.id
               -- LEFT JOIN statuses cts          ON cts.id
               LEFT JOIN history_values ctva     ON h.toValueID = ctva.id
               LEFT JOIN '.$userDB.'.info ctu                ON h.toValueID = ctu.id
               LEFT JOIN deal_sites ctd          ON h.toValueID = ctd.id
            ';
    }

    /*
    ****************************************************************************
    */

    function getJSFields()
    {
        $sql = 'SELECT id,
                       displayName
                FROM   history_models';

        $objects = $this->app->queryResults($sql);

        foreach ($objects as $object) {
            $displayName = $object['displayName'];
            $tablePath = 'tables\\'.$displayName;

            $table = new $tablePath($this->app);

            // Have to remove the app references bc they will break the JSON
            // encoding of the tables
            $table->unsetAppProp();

            $this->app->jsVars['tableFields'][$displayName] = $table;
        }
    }

    /*
    ****************************************************************************
    */

    static function insertHistory($db, $insert)
    {
        $insertFields = array_keys($insert);

        $sql = 'INSERT INTO history (
                    '.implode(', ', $insertFields).'
                ) VALUES (
                    '.$db->getQMarkString($insertFields).'
                )';

        $db->runQuery($sql, array_values($insert));
    }

    /*
    ****************************************************************************
    */

    static function addUpdate($data)
    {
        $model = $data['model'];
        $foreignKeysData = getDefault($data['foreignKeysData']);
        $transaction = getDefault($data['transaction'], TRUE);

        $db = $model->app;

        $foreignKeys = $foreignKeysData ? $foreignKeysData :
                self::getForeignKeysData($data);

        $transaction ? $db->beginTransaction() : FALSE;

        $insertData = self::addMissingKeys($foreignKeys);

        self::insertHistory($db, $insertData);

        $transaction ? $db->commit() : FALSE;
    }

    /*
    ****************************************************************************
    */

    function getIDValue($model, $db, $rowID)
    {
        // Get the main field value
        $sql = 'SELECT  '.$model->mainField.'
                FROM    '.$model->table.'
                WHERE   '.$model->primaryKey.' = ?';

        $result = $db->queryResult($sql, [$rowID]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function checkForeignKeysExist($params)
    {
        $db = $params['db'];
        $refValues = $params['refValues'];
        $model = $params['model'];
        $insert = $params['insert'];
        $fieldKey = $params['fieldKey'];

        // Only dropdown fields will have foreign keys
        $dropdown = getDefault($model->fields[$fieldKey]['searcherDD']);

        $updateField = getDefault($model->fields[$fieldKey]['select'], $fieldKey);

        // Use dropdown model to get the dropdown table
        // If not a dropdown and update field exists use update field as field
        $field = $updateField;
        $table = $model->table;

        if ($dropdown) {
            $foreignModelName = 'tables\\'.$dropdown;
            $foreignModel = new $foreignModelName($db);
            $table = $foreignModel->table;
            $field = $foreignModel->primaryKey;
        }

        $userDB = $db->getDBName('users');

        // Check that all the join values are there
        $sql = 'SELECT (
                    SELECT id
                    FROM   history_models
                    WHERE  displayName = ?
                ) AS modelID, (
                    SELECT id
                    FROM   history_fields
                    WHERE  displayName = ?
                ) AS fieldID, (
                    SELECT '.$field.'
                    FROM   '.$table.'
                    WHERE  '.$field.' = ?
                    LIMIT 1
                ) AS fromValueID, (
                    SELECT '.$field.'
                    FROM   '.$table.'
                    WHERE  '.$field.' = ?
                    LIMIT 1
                ) AS toValueID, (
                    SELECT id
                    FROM   history_values
                    WHERE  displayName = ?
                ) AS fromValue, (
                    SELECT id
                    FROM   history_values
                    WHERE  displayName = ?
                ) AS toValue, (
                    SELECT id
                    FROM   '.$userDB.'.info
                    WHERE  username = ?
                ) AS userID';

        $result = $db->queryResult($sql, array_values($refValues));

        $insert['userID'] = $result['userID'];

        $result['found']['fieldID'] = $result['fieldID'] ? TRUE : FALSE;
        $result['found']['modelID'] = $result['modelID'] ? TRUE : FALSE;

        $insert['fieldID'] = $result['fieldID']
            ? $result['fieldID'] : $insert['fieldID'];
        $insert['modelID'] = $result['modelID']
            ? $result['modelID'] : $insert['modelID'];

        // Store the value in history-values if the it was not found in the
        // related table
        $insert['fromHistoryValues'] = ! $dropdown || ! $result['fromValueID']
            ? 1 : 0;

        $insert['fromValueID'] = $result['fromValue']
            ? $result['fromValue'] : $insert['fromValueID'];

        $insert['fromValueID'] = $result['fromValueID'] && $dropdown
            ? $result['fromValueID'] : $insert['fromValueID'];

        $insert['toHistoryValues'] = ! $dropdown || ! $result['toValueID']
            ? 1 : 0;

        $insert['toValueID'] = $result['toValue']
            ? $result['toValue'] : $insert['toValueID'];

        $insert['toValueID'] = $result['toValueID'] && $dropdown
            ? $result['toValueID'] : $insert['toValueID'];

        return [
            'foreignKeys' => $result,
            'insert' => $insert,
        ];
    }

    /*
    ****************************************************************************
    */

    static function insert($params)
    {
        $sql = 'INSERT INTO '.$params['table'].' (displayName) VALUES (?)';

        $value = $params['value'];
        $params['db']->runQuery($sql, [$value]);
    }

    /*
    ****************************************************************************
    */

    static function addMissingKeys($params)
    {
        $db = $params['db'];
        $refValues = $params['refValues'];
        $insert = $params['insert'];
        $foreignKeys = $params['foreignKeys'];
        $nextIDs = $params['nextIDs'];
        $regularValues = $params['regularValues'];

        foreach ($regularValues as $field => $table) {
            if (! $foreignKeys['found'][$field]) {

                self::insert([
                    'db' => $db,
                    'value' => $refValues[$field],
                    'table' => $table,
                ]);

                $insert[$field] = $nextIDs[$table]++;
            }
        }
        $foreignValues = [
            'toValueID' => [
                'table' => 'history_values',
                'boolean' => 'toHistoryValues',
                'historyValue' => 'toValue',
            ],
            'fromValueID' => [
                'table' => 'history_values',
                'boolean' => 'fromHistoryValues',
                'historyValue' => 'fromValue',
            ],
        ];

        foreach ($foreignValues as $field => $info) {

            $isHistoryValue = $info['boolean'];

            $historyValue = $info['historyValue'];

            // If the value from the history values table but there was not
            // history values index, insert it into the table
            if ($insert[$isHistoryValue] && ! $foreignKeys[$historyValue]) {

                $table = $info['table'];

                // If the value is not a referenceID create a history ref
                // for it
                $insert[$field] = self::insert([
                    'db' => $db,
                    'value' => $refValues[$field],
                    'table' => $table,
                ]);

                $insert[$field] = $nextIDs[$table]++;
            }
        }

        return $insert;
    }

    /*
    ****************************************************************************
    */

    static function getForeignKeysData($params)
    {
        $model = $params['model'];
        $fieldKey = $params['field'];

        $db = $model->app;

        $insert = $refValues = [];

        $insert['userID'] = NULL;
        $insert['actionID'] = self::CHANGE_ACTION_ID;
        $insert['modelID'] = $refValues['modelID'] = $model->ajaxModel;
        $insert['rowID'] = $params['rowID'];
        $insert['fieldID'] = $refValues['fieldID'] = $fieldKey;
        $params['fromValue'] = $params['fromValue'] === NULL ? '' : $params['fromValue'];
        $insert['fromValueID'] = $refValues['fromValueID'] = $params['fromValue'];
        $params['toValue'] = $params['toValue'] === NULL ? '' : $params['toValue'];
        $insert['toValueID'] = $refValues['toValueID'] = $params['toValue'];
        $refValues[] = $params['fromValue'];
        $refValues[] = $params['toValue'];
        $refValues[] = \access::getUserInfoValue('username');

        $foreignKeys = self::checkForeignKeysExist([
            'db' => $db,
            'refValues' => $refValues,
            'model' => $model,
            'insert' => $insert,
            'fieldKey' => $fieldKey,
        ]);

        $foreignKeys['db'] = $db;
        $foreignKeys['refValues'] = $refValues;

        $foreignKeys['regularValues'] = [
            'fieldID' => 'history_fields',
            'modelID' => 'history_models',
        ];

        $foreignKeys['nextIDs'] = [
            'history_values' => $model->getNextID('history_values'),
        ];

        foreach ($foreignKeys['regularValues'] as $table) {
            $foreignKeys['nextIDs'][$table] = $model->getNextID($table);
        }

        return $foreignKeys;
    }

    /*
    ****************************************************************************
    */

}
