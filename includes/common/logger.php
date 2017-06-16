<?php

namespace common;

class logger
{
    static $fieldIDs = [];

    static $nextID;

    static $logAddSQL;

    static $target = NULL;

    static $model = NULL;

    static $tableName = NULL;

    static $logID;

    /*
    ****************************************************************************
    */

    static function setLogID($value)
    {
        self::$logID = $value;
    }

    /*
    ****************************************************************************
    */

    static function setFieldIDs($value)
    {
        self::$fieldIDs = $value;
    }

    /*
    ****************************************************************************
    */

    static function getLogID($oldLogID=FALSE)
    {
        switch (self::$target) {
            case 'orders':
                self::$tableName = 'logs_orders';
                break;
            case 'workorders':
                self::$tableName = 'logs_workorders';
                break;
            case 'cartons':
                self::$tableName = 'logs_cartons';
                break;
            default:
                die('Invalid Log Target');
        }

        self::$logID = $oldLogID ? $oldLogID :
            self::$model->getNextID(self::$tableName);

        return self::$logID;
    }

    /*
    ****************************************************************************
    */

    static function getFieldIDs($category, $db)
    {
        self::$target = $category;
        self::$model = new \tables\_default($db);

        $sql = 'SELECT id,
                       displayName
                FROM   logs_fields
                WHERE  category = ?';

        $results = $db->queryResults($sql, [$category]);

        foreach ($results as $id => $row) {
            $display = $row['displayName'];
            self::$fieldIDs[$display] = $id;
        }

        return self::$fieldIDs;
    }

    /*
    ****************************************************************************
    */

    static function edit($params)
    {
        $db = getDefault($params['db']);

        $overrideTime = getDefault($params['overrideTime'], NULL);

        // Use getFieldID only if the field IDs have not been retrieved
        self::$fieldIDs ? self::$fieldIDs : self::getFieldIDs(self::$target, $db);

        // Create new transaction by default
        $transaction = getDefault($params['transaction'], TRUE);

        $primeKey = $params['primeKeys'];
        $primeKeys = is_array($primeKey) ? $primeKey : [$primeKey];

        $field = $params['fields'];
        $fields = is_array($field) ? $field : [$field];

        $transaction ? $db->beginTransaction() : FALSE;

        $sql = 'INSERT INTO '.self::$tableName.' (
                    userID,
                    logTime
                ) VALUES (?, ?)';

        $userID = \access::getUserID();

        $db->runQuery($sql, [$userID, $overrideTime]);

        $sql = 'INSERT INTO logs_values (
                    primekey,
                    logID,
                    fieldID,
                    fromValue,
                    toValue
                ) VALUES (?, ?, ?, ?, ?)';

        foreach ($fields as $field => $values) {

            $alert = 'Field not found in logger fields: '.$field;
            isset(self::$fieldIDs[$field]) or die($alert);

            $fieldID = self::$fieldIDs[$field];

            $toValue = $values['toValues'];
            $toValues = is_array($toValue) ? $toValue : [$toValue];

            $fromValue = $values['fromValues'];
            $fromValues = is_array($fromValue) ? $fromValue : [$fromValue];

            // If this is an update and there are multiple prev values but one new
            // value, make an array of the new value
            $fromCount = count($fromValues);

            if ($fromCount > 1 && count($toValues) == 1) {
                $toValue = reset($toValues);
                $toValues = array_fill(0, $fromCount, $toValue);
            }

            foreach ($toValues as $index => $toValue) {
                $fromValue = getDefault($fromValues[$index], 0);
                
                if ($toValue == $fromValue) {
                    continue;
                }
                
                $db->runQuery($sql, [
                    $primeKeys[$index],
                    self::$logID,
                    $fieldID,
                    $fromValue,
                    $toValue,
                ]);
            }
        }

        self::$logID++;

        $transaction ? $db->commit() : FALSE;
    }

    /*
    ****************************************************************************
    */

    static function startAdd($table)
    {
        // This to set the query and get the next ID before the transaction starts
        self::$logAddSQL = 'INSERT INTO logs_adds (primeKey) VALUES (?)';

        self::$nextID = $table->getNextID($table->mainTable);
    }

    /*
    ****************************************************************************
    */

    static function add($db)
    {
        $db->runQuery(self::$logAddSQL, [self::$nextID++]);
    }

    /*
    ****************************************************************************
    */

    static function createLogScanInput($params)
    {
        $app = $params['app'];
        $currentUserID = \access::getUserID();
        $currentPageRequest = \models\config::get('site', 'requestURI');

        $userID = getDefault($params['userID'], $currentUserID);
        $pageRequest = getDefault($params['pageRequest'], $currentPageRequest);
        $scanInput = getDefault($params['scanInput']);
        $inputOption = getDefault($params['inputOption']);

        $scanInputQuery = new \tables\logs\scanInputQuery($app);

        $result = $scanInputQuery->insertScanInput([
            'userID' => $userID,
            'pageRequest' => $pageRequest,
            'scanInput' => $scanInput,
            'inputOption' => $inputOption
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

}
