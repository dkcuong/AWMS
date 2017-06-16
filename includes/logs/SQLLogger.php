<?php
namespace logs;

class SQLLogger {

    private $logs;

    /*
    ****************************************************************************
    */

    public function __construct() {

        $this->logs = [];

    }

    /*
    ****************************************************************************
    */

    public function pushLog($data) {

        foreach ($data as $value) {

            array_push($this->logs, $value);

        }

    }

    /*
    ****************************************************************************
    */

    public function rightPopLog() {

        array_pop($this->logs);

    }

    /*
    ****************************************************************************
    */

    public function leftPopLog() {

        array_shift($this->logs);

    }

    /*
    ****************************************************************************
    */

    public function commit(){

        $logs = $this->logs;

        $this->app->beginTransaction();

        foreach ($logs as $sql) {

            $this->app->runQuery($sql);

        }

        $this->app->commit();

    }

    /*
    ****************************************************************************
    */

    public function resetLogData() {

        $this->logs = [];

    }
}