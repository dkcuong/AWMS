<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 10/04/2017
 * Time: 10:55
 */

namespace tables\orders;

class ShippedDailyReportToVendor extends ShippedDailyReport
{
    public $clientIds;
    public $clientCode;

    //timezone of system : America/New_York
    //this cron run att 11pm Pacific Daylight Time
    private $timeRun = 2;

    public function run($clientCode)
    {
        $result = false;

        $this->clientCode = $clientCode;

        if (! $clientCode) {
            return $result;
        }

        $validateTime = $this->checkValidTimeRun();

        if (! $validateTime) {
            return $result;
        }

        $data = $this->getShippedOrderByTodayForClient($this->clientCode);

        $this->dataOrder = $data;

        $data = $this->reformatDataReport($data);

        $fileReport = 'END OF DAY - ' . $this->today. '.xls';

        $this->createShippedOrderReqportFile($data, $fileReport);
        
        $result = $this->sendEmailShippedReport();

        return $result;
    }

    /*
    ****************************************************************************
    */

    private function checkValidTimeRun()
    {
        $result = date('G') == $this->timeRun;
        return $result;
    }
}