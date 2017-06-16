<?php

namespace reports;

use DateTime;

class orders
{    
    static $logs = [];
    
    static $orderData = [];
    
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
    
    function orderReport() 
    {
        $his = $logs = [];
         
        //get the statusID
        $statusObj = new \tables\statuses\orders($this->app);
        $stsName = ['WMCI', 'WMCO', 'PKCI', 'PKCO', 'OPCI', 'OPCO', 'LSCI', 'BOL'];
     
        
        foreach ($stsName as $status) {
            $invResults = $invRes = [];
           
            $stsResult = $statusObj->getStatusIDs($status);
           
            $statusID = array_column($stsResult, 'id');
           
            //get each carton status info from inventory
            $orderSql = 'SELECT    count(id) AS count
                         FROM      neworder
                         WHERE     statusID = ?';
            
            $orderResult = $this->app->queryResult($orderSql, $statusID);

            $i = 0;
            
            $cnt = $orderResult['count'] > 50000 ? 
                                        50000 : $orderResult['count'];
     
            while ($i < $orderResult['count']) {
                $limit = $i.' , '.$cnt;
                
                //get each carton status info from inventory
                $results = $this->getOrderStatusInfo($statusID, $limit);
                
                $invResults[] = $results;               

                $i += $cnt;
            }    
            
            foreach ($invResults as $key => $values) {
                foreach ($values as $id => $row) {
                    $invRes[$id] = $row;
                }
            }

            //get fieldID - category "cartons" 
            $lField = $this->getOrderFieldID();



            $logParam = array_merge($lField, $statusID, $statusID);

            //get logtime for Orders
            $logResults = $this->logOrders($logParam);


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

            $hisResults = $this->historyOrders($hisParam);

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
        self::sendOrderReport();

        return self::$logs;
    }
    
    /*
    ****************************************************************************
    */
    
    function getOrderStatusInfo($statusID, $limit) 
    {
        //get the orders info for each status 
            $invSql = 'SELECT   n.id,
                                scanordernumber AS OrderNumber,
                                clientordernumber,
                                customerordernumber,
                                CONCAT(w.shortName, "_", vendorName) AS Vendor,
                                n.id AS OrderID,
                                dateentered AS Date,
                                startshipdate AS ShipDate,
                                canceldate AS CancelDate,
                                s.shortName AS OrderStatus,
                                hsts.shortName AS HoldStatus,
                                ests.shortName AS ErrorStatus
                        FROM    neworder n
                        JOIN    order_batches b ON b.id = n.order_batch
                        JOIN    vendors v ON v.id = b.vendorID
                        JOIN    warehouses w ON w.id = v.warehouseID
                        JOIN    statuses s ON s.id = n.statusID
                        LEFT JOIN statuses hsts ON n.holdStatusID = hsts.id
                        JOIN statuses ests ON n.isError = ests.id
                        WHERE   statusID = ?
                        ORDER BY  statusID, v.id
                        LIMIT ' . $limit;

            $result = $this->app->queryResults($invSql, $statusID);
            
            return $result;
    }
    
    /*
    ****************************************************************************
    */
    
    function getOrderFieldID()
    {
        $sql = 'SELECT  displayName,
                        id
                FROM    logs_fields
                WHERE   category = "orders"
                AND     displayName = "statusID"';

        $lResult = $this->app->queryResults($sql);
            
        $id = array_column($lResult, 'id');
        
        return $id;
    }
    
    /*
    ****************************************************************************
    */
    
    function logOrders($logParam)
    {
        $logSql = 'SELECT   n.id, 
                            logTime AS date
                    FROM    neworder n
                    JOIN    logs_values v ON primeKey = n.id
                    JOIN    logs_orders o ON o.id = v.logID
                    WHERE   fieldID = ?
                    AND     toValue = ?
                    AND     statusID = ?
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
        $commonSql = 'SELECT    n.id, 
                                if(logTime > actionTime, logTime, actionTime) AS date
                      FROM      neworder n
                      JOIN      history h ON rowID = n.id
                      JOIN      logs_values v ON primeKey = h.rowID
                      JOIN      logs_orders o ON o.id = v.logID
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
    
    function historyOrders($hisParam) 
    {
        $hisSql = 'SELECT   n.id, 
                            actionTime AS date
                    FROM    neworder n
                    JOIN    history h ON rowID = n.id
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
        $orders = [];

        $curDate = \models\config::getDateTime('date');
        $curDtObj = new DateTime($curDate);
        $curDateObj = $curDtObj->modify( '+1 day' ); 

        foreach (self::$invData as  $key => $row) {
            $dtObj = new DateTime($row['Date']);
            $interval = $dtObj->diff($curDateObj);
            $days = $interval->format('%a days');

            $row['After 30 days'] = $row['After 60 days'] = 
                    $row['After 30 days'] = NULL;

            if ($days >= 30 && $days <= 59) {
                $row['After 30 days'] = $days;
            } elseif ($days >=60 && $days <= 89) {
                $row['After 60 days'] = $days;
            } elseif ($days >= 90) {
                $row['After 90 days'] = $days;
            } else {
                continue;
            }

            $orders[$key] = $row;
        }

        foreach ($orders as $values) {
            self::$orderData[]  = array_values($values);
        }

        return self::$orderData;
    }
    
    
    /*
    ****************************************************************************
    */
    
    static function sendOrderReport()
    {
        if (! self::$orderData) {
            return self::$logs = 'No order report emails to be sent';
        }
       
        $csv = new \csv\export($this);
                       
        $path = \models\directories::getDir('uploads', 'orderReport');
                
        $titles = ['ORDERNUM', 'CLIENTORDNUM', 'CUSTORDNUM', 'CLIENT', 'ORDERID', 
                    'DATE', 'SHIPDATE', 'CNCLDATE', 'ORDSTS',
                    'HOLDSTS', 'ERRSTS', 'AFTER 30 DAYS', 'AFTER 60 DAYS',
                       'AFTER 90 DAYS'];

        array_unshift(self::$orderData, $titles);

        $csv::write(self::$orderData, 'orderReport', 'orderReport.csv');

        $file = $path . '/orderReport.csv';

        $subject = 'Order Report';
        $text = 'Order Report';
        
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

