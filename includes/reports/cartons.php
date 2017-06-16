<?php

namespace reports;

use DateTime;

class cartons 
{    
    static $logs = [];
    
    static $cartonData = [];
    
    static $invData = [];
    
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
    
    function cartonReport() 
    {
        $his = $logs = [];
         
        //get the statusID
        $statusObj = new \tables\statuses\inventory($this->app);
        $stsName = ['IN', 'RC', 'RS', 'PK', 'OP', 'LS', 'DS'];
            
       
        foreach ($stsName as $status) {
            $invResults = $invRes = [];
           
            $stsResult = $statusObj->getStatusIDs($status);
           
            $statusID = array_column($stsResult, 'id');
            
            //get each carton status info from inventory
            $cartonSql = 'SELECT    count(id) AS count
                          FROM      inventory_cartons 
                          WHERE     statusID = ?';
            
            $cartonResult = $this->app->queryResult($cartonSql, $statusID);

            $i = 0;
            
            $cnt = $cartonResult['count'] > 50000 ? 
                                        50000 : $cartonResult['count'];
     
            while ($i < $cartonResult['count']) {
                $limit = $i.' , '.$cnt;
                
                //get each carton status info from inventory
                $results = $this->getCartonStatusInfo($statusID, $limit);
                
                $invResults[] = $results;               

                $i += $cnt;
            }    
            
            foreach ($invResults as $key => $values) {
                foreach ($values as $id => $row) {
                    $invRes[$id] = $row;
                }
            }
 
    
            //get fieldID - category "cartons" 
            $lField = $this->getCartonFieldID();

            $logParam = array_merge($lField, $statusID, $statusID);

            //get logtime for Cartons
            $logResults = $this->logCartons($logParam);

            //get fieldID - from history_fields
            $hField = $this->getHistoryFieldID();

            //get max(logTime, historytime) if exist in logs and history
            $commonParam = array_merge($hField, $lField, $statusID, 
                                            $statusID, $statusID);

            $common = $this->getCommon($commonParam);

            $logKeys = array_keys($logResults);
            $commonKeys = array_keys($common);

            $diffKeys = array_diff($logKeys, $commonKeys);

            foreach ($diffKeys as $key) {
                $logs[$key]['date'] = $logResults[$key]['date'];
            }


            //get historyTime for Cartons
            $hisParam = array_merge($hField, $statusID, $statusID);

            $hisResults = $this->historyCartons($hisParam);

            $hisKeys = array_keys($hisResults);

            $diffHisKeys = array_diff($hisKeys, $commonKeys);

            foreach ($diffHisKeys as $key) {
                $his[$key]['date'] = $hisResults[$key]['date'];
            }

            //get the date
            foreach ($invRes as $key => &$value) {
                if (isset($logs[$key]['date'])) {
                   //logs
                    $value['Date'] = $logs[$key]['date'];
                } elseif (isset($his[$key]['date'])) {
                   //history
                   $value['Date'] = $his[$key]['date']; 
                } elseif (isset($common[$key]['date'])) {
                    //common
                   $value['Date'] = $common[$key]['date'];
                }
                self::$invData[$key] = $value;
            }
        }

        //calcualte the days
        self::calculateDays();

        //csv file
        self::sendCartonReport();

        return self::$logs;
    }
    
    
    /*
    ****************************************************************************
    */
    
    function getCartonStatusInfo($statusID, $limit) 
    {
        //get the cartons info for each status from inventory
            $invSql = 'SELECT   ca.id,
                                co.name AS Container,
                                co.recNum AS ReceivingNum,
                                CONCAT(w.shortName, "_", vendorName) AS Vendor,
                                b.id AS BatchID,
                                upc,
                                sku,
                                size,
                                color,
                                plate,
                                ca.id AS CartonID,
                                CONCAT(co.vendorID,
                                    b.id,
                                    LPAD(ca.uom, 3, 0),
                                    LPAD(ca.cartonID, 4, 0)
                                ) AS UCC128,
                                s.shortName AS Status,
                                setDate AS Date
                        FROM    inventory_cartons ca
                        JOIN    inventory_batches b ON b.id = ca.batchID
                        JOIN    inventory_containers co on co.recNum = b.recNum
                        LEFT JOIN  upcs u ON u.id = b.upcID
                        JOIN    vendors v ON v.id = co.vendorID
                        JOIN    warehouses w ON w.id = v.warehouseID
                        JOIN    statuses s ON s.id = ca.statusID
                        WHERE   statusID = ?
                        ORDER BY  statusID, v.id
                        LIMIT ' . $limit;

            $result = $this->app->queryResults($invSql, $statusID);
            
            return $result;
    }
    
    /*
    ****************************************************************************
    */
    
    function getCartonFieldID()
    {
        $sql = 'SELECT  displayName,
                        id
                FROM    logs_fields
                WHERE   category = "cartons"
                AND     displayName = "statusID"';

        $lResult = $this->app->queryResults($sql);
            
        $id = array_column($lResult, 'id');
        
        return $id;
    }
    
    /*
    ****************************************************************************
    */
    
    function logCartons($logParam)
    {
        $logSql = 'SELECT    ca.id, 
                             logTime AS date
                    FROM     inventory_cartons ca
                    JOIN     logs_values v ON primeKey = ca.id
                    JOIN     logs_cartons c ON c.id = v.logID
                    WHERE    fieldID = ?
                    AND      toValue = ?
                    AND      statusID = ?
                    ';
            
        $logResults = $this->app->queryResults($logSql, $logParam);
        
        return $logResults;
    }
  
    /*
    ****************************************************************************
    */
    
    function getHistoryFieldID()
    {
        $sql = 'SELECT  displayName,
                        id
                FROM    history_fields
                WHERE   displayName = "statusID"';

        $hResult = $this->app->queryResults($sql);

        $id = array_column($hResult, 'id');
        
        return $id;
    }
    
    /*
    ****************************************************************************
    */
    
    function getCommon($commonParam)
    {
        $commonSql = 'SELECT    ca.id, 
                                if(logTime > actionTime, logTime, actionTime) AS date
                      FROM      inventory_cartons ca
                      JOIN      history h ON rowID = ca.id
                      JOIN      logs_values v ON primeKey = h.rowID
                      JOIN      logs_cartons c ON c.id = v.logID
                      WHERE     h.fieldID = ?
                      AND       v.fieldID = ?
                      AND       toValue = ?
                      AND       toValueID = ?
                      AND       statusID = ?
                   ';

        $common = $this->app->queryResults($commonSql, $commonParam);
        
        return $common;
    }
    
    /*
    ****************************************************************************
    */
    
    function historyCartons($hisParam) 
    {
        $hisSql = 'SELECT   ca.id, 
                            actionTime AS date
                    FROM    inventory_cartons ca
                    JOIN    history h ON rowID = ca.id
                    WHERE   fieldID = ?
                    AND     toValueID = ?
                    AND     statusID = ?
                    ';

        $hisResults = $this->app->queryResults($hisSql, $hisParam);
        
        return $hisResults;
    }
    
    /*
    ****************************************************************************
    */
        
    static function calculateDays() 
    {
        $cartons = [];

        $curDate = \models\config::getDateTime('date');
        $curDtObj = new DateTime($curDate);
        $curDateObj = $curDtObj->modify( '+1 day' ); 

        foreach (self::$invData as  $key => $row) {
            $dtObj = new DateTime($row['Date']);
            $interval = $dtObj->diff($curDateObj);
            $days = $interval->format('%a days');

            $row['After 30 days'] = $row['After 60 days'] = 
                    $row['After 30 days'] = NULL;

            $row['UCC128'] = "'".$row['UCC128'];

            if ($days >= 30 && $days <= 59) {
                $row['After 30 days'] = $days;
            } elseif ($days >=60 && $days <= 89) {
                $row['After 60 days'] = $days;
            } elseif ($days >= 90) {
                $row['After 90 days'] = $days;
            } else {
                continue;
            }

            $cartons[$key] = $row;
        }

        foreach ($cartons as $values) {
            self::$cartonData[]  = array_values($values);
        }

        return self::$cartonData;
    }
    
    
    /*
    ****************************************************************************
    */
    
    
    static function sendCartonReport()
    {
       if (! self::$cartonData) {
            return self::$logs = 'No carton report emails to be sent';
        }
        
        $csv = new \csv\export($this);
                       
        $path = \models\directories::getDir('uploads', 'cartonReport');

        $titles = ['CONTAINER', 'RECNUM', 'CLIENT', 'BATCHID', 'UPC', 'SKU', 
                       'SIZE', 'COLOR', 'PLATE', 'CARTONID', 'UCC128', 
                       'STATUS', 'DATE', 'AFTER 30 DAYS', 'AFTER 60 DAYS',
                       'AFTER 90 DAYS'];

        array_unshift(self::$cartonData, $titles);

        $csv::write(self::$cartonData, 'cartonReport', 'cartonReport.csv');

        $file = $path . '/cartonReport.csv';

        $subject = 'Carton Report';
        $text = 'Carton Report';

        $receipient = [
            'raji.velou@seldatinc.com',
            'jonathan.sapp@seldatinc.com'
        ];
     
        \PHPMailer\send::mail([
            'recipient' => array_values($receipient),
            'subject' => $subject, 
            'body' => $text,
            'files' => $file,
        ]);      

         
        // excel output
//        $exporter = new \excel\exporter($this);
//        
//        $exporter->ArrayToExcel([
//            'data' => $data,
//            'fileName' => 'cartonReport',
//            'fieldKeys' => [
//                ['title' => 'CONTAINER'],
//                ['title' => 'RECNUM'],
//                ['title' => 'CLIENT'],
//                ['title' => 'BATCHID'],
//                ['title' => 'UPC'],
//                ['title' => 'SKU'],
//                ['title' => 'SIZE'],
//                ['title' => 'COLOR'],
//                ['title' => 'PLATE'],
//                ['title' => 'CARTONID'],
//                ['title' => 'UCC128'],
//                ['title' => 'STATUS'],
//                ['title' => 'DATE'],
//                ['title' => 'AFTER 30 DAYS'],
//                ['title' => 'AFTER 60 DAYS'],
//                ['title' => 'AFTER 90 DAYS'],
//            ],
//        ]);

    }
    
    /*
    ****************************************************************************
    */
}

