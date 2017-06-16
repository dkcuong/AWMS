<?php

namespace orders;

use orders\lading;

class ladingNotices
{
    static $statuses = NULL;

    static $reportStatus = NULL;
    
    static $message = NULL;
    
    static $reportOrders = [];
    
    static $emailedOrders = [];
    
    static $logs = [];
    
    /*
    ****************************************************************************
    */
 
    static function sendEmails($app, $type)
    {
        switch ($type) {
            case 'processed':
                self::$statuses = '"OPCO", "LSCI", "SHCO"';
                self::$reportStatus = 'PBOL';
                self::$message = 'Processed';
                break;
            case 'shipped':
                self::$statuses = '"SHCO"';
                self::$reportStatus = 'SBOL';
                self::$message = 'Shipped';
                break;
            default:
                self::$logs[] = 'Invalid type of orders to be processed';
                return self::$logs;
        }
        
        self::getUnreported($app);
        
        if (! self::$emailedOrders) {
            self::$logs[] = 'No '.strtolower(self::$message).' BOL notice emails '
                    .'to be sent';
            return self::$logs;
        }

        self::forEachOrder($app);
               
        \common\report::recordReportsSent($app, self::$emailedOrders, self::$reportStatus);
        
        return self::$logs;
    }
    
    /*
    ****************************************************************************
    */
 
    static function getUnreported($app)
    {
        // Get a list of orders that have not been reported yet

        $sql = 'SELECT    CONCAT_WS("-", n.id, ce.id) AS id,
                          SUM(
                            IF(rs.shortName = "'.self::$reportStatus.'" AND rs.category = "reports", 1, 0)
                          ) AS reported,
                          ce.vendorID,
                          vendorName,
                          scanordernumber,
                          n.id AS orderID,
                          ce.email
                FROM      neworder n
                JOIN      order_batches b ON b.id = n.order_batch
                JOIN      vendors v ON v.id = b.vendorID
                JOIN      client_emails ce ON ce.vendorID = b.vendorID
                JOIN      statuses s ON s.id = n.statusID
                LEFT JOIN reports_data rd ON rd.primeKey = n.id
                LEFT JOIN statuses rs ON rs.id = rd.statusID
                WHERE     ce.email IS NOT NULL
                AND       ce.active
                AND       s.category = "orders"
                AND       s.shortName IN ('.self::$statuses.')
                AND       bolConfirmation
                GROUP BY  n.id
                HAVING    reported = 0
                ';

        $results = $app->queryResults($sql);

        $orderIDs = [];
        
        foreach ($results as $result) {
            
            $vendorID = $result['vendorID'];
            $vendorName = $result['vendorName'];
            $scanordernumber = $result['scanordernumber'];
            $email = $result['email'];
            
            if (! isset(self::$reportOrders[$vendorID]['vendorName'])) {
                self::$reportOrders[$vendorID]['vendorName'] = $vendorName;
            }
            
            self::$reportOrders[$vendorID]['orders'][$scanordernumber] = TRUE;
            self::$reportOrders[$vendorID]['emails'][$email] = TRUE;
            
            $orderID = $result['orderID'];
            
            if (! isset($orderIDs[$orderID])) {
                
                $orderIDs[$orderID] = TRUE;

                self::$logs[] = 'Sending '.strtolower(self::$message).' BOL notice '
                        .'for Order#  '.$scanordernumber.', '
                        .'Client Name: '.$vendorName;
            }
        }

        self::$emailedOrders = array_keys($orderIDs);
    }
    
    /*
    ****************************************************************************
    */
    
    static function forEachOrder($app)
    {
        $time = date('Y-m-d H:i:s');
        
        foreach (self::$reportOrders as $reportOrders) {
            foreach ($reportOrders['orders'] as $order => $orderData) {

                $uploadDir = \models\directories::getDir('uploads', 'billoflading');

                $file = $uploadDir . '/BillOfLading_' . $order. '_' 
                        . date('Y-m-d-H-i-s') . '.pdf';

                lading::output($app, [$order], $file);

                $files[] = $file;
            }
            
            $subject = self::$message.' Bills of Lading by '.$time;
            $text = $reportOrders['vendorName'].'. Bills of Lading for orders '
                    .'that have been '.strtolower(self::$message).' by '.$time;
                
            \PHPMailer\send::mail([
                'recipient' => array_keys($reportOrders['emails']),
                'subject' => $subject, 
                'body' => $text,
                'files' => $files,
            ]);            
        }
    }
    
    /*
    ****************************************************************************
    */ 
}
