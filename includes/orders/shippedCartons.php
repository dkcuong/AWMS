<?php

namespace orders;

use common\logger;

class shippedCartons
{

    public $app;
    public $cartons = [];
    
    function __construct($app)
    {
        $this->app = $app;
    }

    /*
    ****************************************************************************
    */
    
    function getCartons($order)
    {
        $cartons = new \tables\inventory\cartons($this->app);
        $vendors = new \tables\vendors($this->app);
        
        $ucc = $cartons->fields['ucc128']['select'];
        $vendor = $vendors->fields['fullVendorName']['select'];
        
        $shippedCartons = $this->getShippedCartons($ucc, $vendor, $order);

        $waveCartons = $this->getWaveCartons($ucc, $vendor, $order);
        
        $all = array_merge($shippedCartons, $waveCartons);

        $pickOnly = array_diff_key($all, $shippedCartons);
        $shippedOnly = array_diff_key($all, $waveCartons);

        $both = array_diff_key($all, $shippedOnly, $pickOnly);
        $subQty = count($shippedOnly);
        
        $types = [
            'pickOnly' => $pickOnly,
            'both' => $both,
            'shippedOnly' => $shippedOnly,
        ];
        
        foreach ($types as $cat => $array) {
            $this->eachArray($cat, $array);
        }
        
        $wave = $this->getWavePick($order);
        
        return [
            'subQty' => abs($subQty),
            'cartons' => $this->cartons,
            'wavePick' => getDefault($wave['pickID']),
        ];
    }

    /*
    ****************************************************************************
    */
    
    function eachArray($cat, $array)
    {
        foreach ($array as $ucc => $row) {

            $row['isPick'] = 'Is In Order Wave Pick';
            $row['isShipped'] = 'Was Shipped With Order';
            switch ($cat) {
                case 'pickOnly':
                    $row['isShipped'] = NULL;
                    break;
                case 'shippedOnly':
                    $row['isPick'] = NULL;
            }

            $row['ucc'] = $ucc;

            $this->cartons[] = $row;
        }
    }

    /*
    ****************************************************************************
    */
    
    static function cartonFields($ucc, $vendor)
    {
        return $ucc.',
                      ca.id AS cartonID,
                      n.scanOrderNumber,
                      cs.shortName AS status,
                      cms.shortName AS mStatus,
                      ca.statusID,
                      ca.mStatusID,
                      '.$vendor.' AS vendor';
    }

    /*
    ****************************************************************************
    */
    
    function getShippedCartons($ucc, $vendor, $order)
    {
        $cartonFields = self::cartonFields($ucc, $vendor);
        
        $sql = 'SELECT '.$cartonFields.'
                FROM   neworder n
                JOIN   statuses ns ON ns.id = n.statusID
                JOIN   inventory_cartons ca ON ca.orderID = n.id
                JOIN   statuses cs ON cs.id = ca.statusID
                JOIN   statuses cms ON cms.id = ca.mStatusID
                JOIN   inventory_batches b ON b.id = ca.batchID
                JOIN   inventory_containers co ON co.recNum = b.recNum
                JOIN   vendors v ON v.id = co.vendorID
                JOIN   warehouses w ON w.id = v.warehouseID
                WHERE  n.scanOrderNumber = ?
                ';

        return $this->app->queryResults($sql, [$order]);    
    }
    
    /*
    ****************************************************************************
    */
    
    function getWavePick($order)
    {
        $sql = 'SELECT p.pickID
                FROM   neworder n
                JOIN   statuses ns ON ns.id = n.statusID
                JOIN   pick_cartons p ON p.orderID = n.id
                WHERE  n.scanOrderNumber = ?
                AND    p.active';
        
        return $this->app->queryResult($sql, [$order]);
    }
    
    /*
    ****************************************************************************
    */
    
    function getWaveCartons($ucc, $vendor, $order)
    {
        $cartonFields = self::cartonFields($ucc, $vendor);
        
        $sql = 'SELECT '.$cartonFields.'
                FROM   neworder n
                JOIN   statuses ns ON ns.id = n.statusID
                JOIN   pick_cartons p ON p.orderID = n.id
                JOIN   inventory_cartons ca ON ca.id = p.cartonID
                JOIN   statuses cs ON cs.id = ca.statusID
                JOIN   statuses cms ON cms.id = ca.mStatusID
                JOIN   inventory_batches b ON b.id = ca.batchID
                JOIN   inventory_containers co ON co.recNum = b.recNum
                JOIN   vendors v ON v.id = co.vendorID
                JOIN   warehouses w ON w.id = v.warehouseID
                WHERE  n.scanOrderNumber = ?
                AND    p.active
                ';

        return $this->app->queryResults($sql, [$order]);
    }
    
    /*
    ****************************************************************************
    */
    
    function update($order, $target)
    {
        $cartonIDs = $statusIDs = $mStatusIDs = [];
        
        switch ($target) {
            case 'order':
                $cartons = $this->getCartons($order);
                $cartonIDs = array_column($cartons['cartons'], 'cartonID');
                $statusIDs = array_column($cartons['cartons'], 'statusID');
                $mStatusIDs = array_column($cartons['cartons'], 'mStatusID');
                break;
            case 'cartons':

                $cartons = $this->app->postVar('cartons', 'getDef', []);
                $passedCartonIDs = array_keys($cartons);
                
                if (! $passedCartonIDs) {
                    return $order;
                }

                $results = $this->getStatuses($passedCartonIDs);                

                $cartonIDs = array_keys($results);
                $statusIDs = array_column($results, 'statusID');
                $mStatusIDs = array_column($results, 'mStatusID');

        }
        
        logger::getFieldIDs('cartons', $this->app);
        
        $orderInfo = $this->getOPLogID($order);

        // Use the log ID of the OPing if it is found use start ship date
        $overrideTime = $orderInfo ? NULL : $this->getOrderLogDate($order);
        $logID = $orderInfo ? logger::getLogID($orderInfo['id']) : 
            logger::getLogID();

        $shippedStatus = $this->shippedStatus();
        
        logger::edit([
            'db' => $this->app,
            'overrideTime' => $overrideTime,
            'transaction' => FALSE,
            'primeKeys' => $cartonIDs,
            'fields' => [
                'statusID' => [
                    'fromValues' => $statusIDs,
                    'toValues' => $shippedStatus['id'],
                ],
                'mStatusID' => [
                    'fromValues' => $mStatusIDs,
                    'toValues' => $shippedStatus['id'],
                ],
            ],
            'transaction' => TRUE,
        ]);
        
        // Add log for quick lookup
        $this->log($logID, $order);

        $sql = 'UPDATE  inventory_cartons
                SET     statusID = '.$shippedStatus['id'].',
                        mStatusID = '.$shippedStatus['id'].'
                WHERE   id IN ('.$this->app->getQMarkString($cartonIDs).')';

        $this->app->runQuery($sql, $cartonIDs);
        
        return $order;
    
    }
    
    /*
    ****************************************************************************
    */
    
    function getStatuses($passedCartonIDs)
    {
        $sql = 'SELECT id, 
                       statusID,
                       mStatusID
                FROM   inventory_cartons
                WHERE  id IN ('.$this->app->getQMarkString($passedCartonIDs).')';

        return $this->app->queryResults($sql, $passedCartonIDs);
    }
    
    /*
    ****************************************************************************
    */
    
    function shippedStatus()
    {
        $sql = 'SELECT id
                FROM   statuses 
                WHERE  category = "inventory"
                AND    shortName = "SH"';
        return $this->app->queryResult($sql);        
    }

    /*
    ****************************************************************************
    */
    
    function log($logID, $order)
    {
        $sql = 'INSERT INTO ship_ctn_mod_logs (logID, orderID) 
                VALUES (
                    ?, 
                    (
                        SELECT id
                        FROM   neworder
                        WHERE  scanOrderNumber = ?
                    )
                )';
        $this->app->runQuery($sql, [$logID, $order]);
    }
    
    /*
    ****************************************************************************
    */
    
    function getOPLogID($order)
    {
        $sql = 'SELECT   lo.id
                FROM     neworder n
                JOIN     logs_values lv ON lv.primeKey = n.id
                JOIN     logs_orders lo ON lo.id = lv.logID
                JOIN     logs_fields f ON f.id = lv.fieldID
                JOIN     statuses s ON s.id = lv.toValue
                WHERE    scanOrderNumber = ?
                AND      f.displayName = "statusID"
                AND      s.shortName = "SHCO"
                ORDER BY lo.id DESC
                LIMIT    1';
        
        return $this->app->queryResult($sql, [$order]);
    }
    
    /*
    ****************************************************************************
    */
    
    function getOrderLogDate($order)
    {
        $sql = 'SELECT    IF (
                            startshipdate is null, 
                            order_date, startShipDate
                          ) AS dt
                FROM      neworder
                LEFT JOIN online_orders oo 
                    ON scanordernumber = oo.scan_seldat_order_number
                WHERE scanordernumber = ?';
        $results = $this->app->queryResult($sql, [$order]);

        return $results['dt'];
    }
    
}
