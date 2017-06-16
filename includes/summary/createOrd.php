<?php

namespace summary;

class createOrd extends model
{
    
    public $fields = [
        'order_nbr' => TRUE,
        'cust_id' => TRUE, 
        'dt' => TRUE, 
        'val' => TRUE,
        'log_id' => TRUE,
    ];
    
    public $workFields = [
        'order_nbr' => TRUE, 
        'cust_id' => TRUE, 
        'dt' => TRUE, 
        'chg_cd' => TRUE, 
        'labor' => TRUE,
        'log_id' => TRUE,
    ];
    
    
    public $shipFields = [
        'order_nbr' => TRUE,
        'cust_id' => TRUE, 
        'carrierType' => TRUE,
        'dt' => TRUE, 
        'log_id' => TRUE,
        'val' => TRUE,
    ];    
    
    public $ids = [];
    
    public $processedIDs = [];

    public $nonProcessedIDs = [];
    
    public $processedStatus = [];
    
    public $notProcessedStatus = [];
    
    public $orderStatus = [];
    
    public $ordIDs = [];
    
    public $shipStatus = [];
    
    public $cancelStatus = [];
    
    /*
    ****************************************************************************
    */
    
    function make()
    {
        $totalTime = self::init()->timer();
        
        $this
            ->getIDs()
            ->workOrders()
            ->cartons()
            ->volume()
            ->plates()
            ->orders()
            ->labels()
            ->pieces()
            ->shippedOrders()
            ->cancelledOrders()
            ;         
        
        $totalTime->timer('Total Make Time');
    }

    /*
    ****************************************************************************
    */

    function getIDs()
    {
        $sql = 'SELECT    id AS field
                FROM      logs_fields
                WHERE     displayName = "statusID"
                AND       category = "orders"
                ';
        
        $this->ids = $this->db->queryResult($sql);
   
        $this->processedStatus =  $this->getInvoiceProcessedOrder();
        
        $this->notProcessedStatus = $this->getInvoiceNotProcessedOrder();
        
        $statuses = new \tables\statuses\orders($this->db);
        
        $status = \tables\orders::STATUS_SHIPPED_CHECK_OUT;
        $this->shipStatus = $statuses->getOrderStatusID($status);
        
        $status = \tables\orders::STATUS_CANCELED;
        $this->cancelStatus = $statuses->getOrderStatusID($status);

        return $this;
    }

    /*
    ****************************************************************************
    */

    function cartons()
    {
        copyTable::init($this)->standard([
            'sql' => '
                     ' . $this->getCartonQuery('processed') . '
                    UNION
                     ' . $this->getCartonQuery() . '
                    ',
            'csvFile' => 'cartons',
            'truncate' => TRUE,
            'targetTable' => 'ord_sum_ctn',
            'fields' => $this->fields,
        ]);
        
        return $this;
    }

    /*
    ****************************************************************************
    */

    function volume()
    {
        copyTable::init($this)->standard([
            'sql' => '
                     ' . $this->getVolumeQuery('processed') . '
                    UNION
                     ' . $this->getVolumeQuery() . '
                    ',
            'csvFile' => 'volume',
            'truncate' => TRUE,
            'targetTable' => 'ord_sum_vol',
            'fields' => $this->fields,
        ]);
                
        return $this;
    }

    /*
    ****************************************************************************
    */

    function plates()
    {
        copyTable::init($this)->standard([
            'sql' => '
                     ' . $this->getPlatesQuery('processed') . '
                    UNION
                     ' . $this->getPlatesQuery() . '
                    ',
            'csvFile' => 'plates',
            'truncate' => TRUE,
            'targetTable' => 'ord_sum_plt',
            'fields' => $this->fields,
        ]);
        
        return $this;
    }

    /*
    ****************************************************************************
    */
    
    function pieces()
    {
        copyTable::init($this)->standard([
            'sql' => '
                     ' . $this->getPiecesQuery('processed') . '
                    UNION
                     ' . $this->getPiecesQuery() . '
            ',
            'csvFile' => 'pieces',
            'truncate' => TRUE,
            'targetTable' => 'ord_sum_pcs',
            'fields' => $this->fields,
        ]);
                
        return $this;
    }
    
    
    /*
    ****************************************************************************
    */

    function orders()
    {
        copyTable::init($this)->standard([
            'sql' => '
                     ' . $this->getOrdersQuery() . '
                    ',
            'csvFile' => 'orders',
            'truncate' => TRUE,
            'targetTable' => 'ord_sum_ord',
            'fields' => $this->fields,
        ]);
        
        return $this;
    }

    /*
    ****************************************************************************
    */

    function labels()
    {
        copyTable::init($this)->standard([
            'sql' => '
                     ' . $this->getLabelsQuery() . '
                     ',
            'csvFile' => 'labels',
            'truncate' => TRUE,
            'targetTable' => 'ord_sum_lbl',
            'fields' => $this->fields,
        ]);
        
        return $this;
    }

    /*
    ****************************************************************************
    */

    function workOrders()
    {
        copyTable::init($this)->standard([
            'sql' => '
                     ' . $this->getWorkOrdersQuery() . '
                     ',
            'csvFile' => 'workOrders',
            'truncate' => TRUE,
            'targetTable' => 'ord_sum_wo',
            'fields' => [
                'order_nbr' => TRUE, 
                'cust_id' => TRUE, 
                'dt' => TRUE, 
                'chg_cd' => TRUE, 
                'labor' => TRUE,
                'log_id' => TRUE,
            ],
        ]);
        
        return $this;
    }

    /*
    ****************************************************************************
    */

    function shippedOrders()
    {
        copyTable::init($this)->standard([
            'sql' => '
                     ' . $this->getShippedQuery() . '
                    ',
            'csvFile' => 'orders',
            'truncate' => TRUE,
            'targetTable' => 'ord_ship_sum',
            'fields' => $this->shipFields,
        ]);
        
        return $this;
    }
    
    /*
    ****************************************************************************
    */

    function cancelledOrders()
    {
        copyTable::init($this)->standard([
            'sql' => '
                     ' . $this->getCancelledQuery() . '
                    ',
            'csvFile' => 'orders',
            'truncate' => TRUE,
            'targetTable' => 'ord_sum_cncl',
            'fields' => $this->fields,
        ]);
        
        return $this;
    }

    /*
    ****************************************************************************
    */
    
    function invoiceOrderProcessedStatus()
    {
        return [
            \tables\orders::STATUS_PROCESSING_CHECK_OUT,
            \tables\orders::STATUS_BILL_OF_LADING,
            \tables\orders::STATUS_SHIPPING_CHECK_IN,
            \tables\orders::STATUS_SHIPPED_CHECK_OUT,
        ];
    }
    
    
    /*
    ****************************************************************************
    */
    
    function getInvoiceProcessedOrder()
    {
       $statuses = new \tables\statuses\orders($this->db); 
       
       $status = $this->invoiceOrderProcessedStatus();
       
       $processedIds = $statuses->getOrderStatusID($status);
  
       return $processedIds;
    }

    /*
    ****************************************************************************
    */
    
    function invoiceOrderNotProcessedStatus()
    {
        return [
            \tables\orders::STATUS_ENTRY_CHECK_OUT,
            \tables\orders::STATUS_PICKING_CHECK_IN,
            \tables\orders::STATUS_PICKING_CHECK_OUT,
            \tables\orders::STATUS_PROCESSING_CHECK_IN,
        ];
    }
    
    /*
    ****************************************************************************
    */
    
    function getInvoiceNotProcessedOrder()
    {
       $statuses = new \tables\statuses\orders($this->db);
       
       $status = $this->invoiceOrderNotProcessedStatus();
       
       $notProcessedIds = $statuses->getOrderStatusID($status);
  
       return $notProcessedIds;
    }
    
    /*
    ****************************************************************************
    */
    
    function getCartonQuery($processed='notProcessed')
    {
        $clause = $this->orderClauses($processed);

        return '
                SELECT  n.scanOrderNumber,
                        vendorID,
                        DATE(logTime),
                        COUNT(ca.id) AS valueCount,
                        maxLogID
                FROM  (
                        SELECT     primeKey,
                                   MAX(logID) AS maxLogID
                        FROM       logs_values
                        JOIN       neworder o ON o.statusID = toValue
                        WHERE      fieldID = ' . $this->ids['field'] . '
                        AND        toValue IN ' . $clause['status'] . '
                        GROUP BY   primeKey
                ) lv
                JOIN      logs_orders c ON c.id = lv.maxLogID
                JOIN      neworder n ON n.id = lv.primeKey
                JOIN      order_batches ob ON ob.id = n.order_batch
                 ' . $clause['join'] . '
                JOIN      statuses s ON s.id = n.statusID
                WHERE     ' . $clause['where'] . '
                AND       s.id  IN ' . $clause['status'] . '
                GROUP by  vendorID,
                          n.id
                ';
    }    
    
    /*
    ****************************************************************************
    */
    
    function getVolumeQuery($processed='notProcessed')
    {
        $clause = $this->orderClauses($processed);
        
        return '
                SELECT  n.scanOrderNumber,
                        vendorID,
                        DATE(logTime),
                        SUM(b.height * b.length * b.width / 1728) AS valueCount,
                        maxLogID
                FROM  (
                        SELECT     primeKey,
                                   MAX(logID) AS maxLogID
                        FROM       logs_values
                        JOIN       neworder o ON o.statusID = toValue
                        WHERE      fieldID = ' . $this->ids['field'] . '
                        AND        toValue IN ' . $clause['status'] . '
                        GROUP BY   primeKey
                ) lv
                JOIN      logs_orders c ON c.id = lv.maxLogID
                JOIN      neworder n ON n.id = lv.primeKey
                JOIN      order_batches ob ON ob.id = n.order_batch
                 ' . $clause['join'] . '
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      statuses s ON s.id = n.statusID
                WHERE     ' . $clause['where'] . '
                AND       s.id  IN ' . $clause['status'] . '
                GROUP by  vendorID,
                          n.id
                ';
        
    }    
    
    /*
    ****************************************************************************
    */
    
    function getPlatesQuery($processed='notProcessed')
    {
        $clause = $this->orderClauses($processed);
        
        return '
                SELECT  n.scanOrderNumber,
                        vendorID,
                        DATE(logTime),
                        COUNT(DISTINCT ca.plate) AS valueCount,
                        maxLogID
                FROM  (
                        SELECT     primeKey,
                                   MAX(logID) AS maxLogID
                        FROM       logs_values
                        JOIN       neworder o ON o.statusID = toValue
                        WHERE      fieldID = ' . $this->ids['field'] . '
                        AND        toValue IN ' . $clause['status'] . '
                        GROUP BY   primeKey
                ) lv
                JOIN      logs_orders c ON c.id = lv.maxLogID
                JOIN      neworder n ON n.id = lv.primeKey
                JOIN      order_batches ob ON ob.id = n.order_batch
                 ' . $clause['join'] . '
                JOIN      statuses s ON s.id = n.statusID
                WHERE     ' . $clause['where'] . '
                AND       s.id  IN ' . $clause['status'] . '
                GROUP by  vendorID,
                          n.id
                ';
    }    
    
    /*
    ****************************************************************************
    */
   
    function getPiecesQuery($processed='notProcessed')
    {
        $clause = $this->orderClauses($processed);
        
        return '
                SELECT  n.scanOrderNumber,
                        vendorID,
                        DATE(logTime),
                        SUM(ca.uom) AS valueCount,
                        maxLogID
                FROM  (
                        SELECT     primeKey,
                                   MAX(logID) AS maxLogID
                        FROM       logs_values
                        JOIN       neworder o ON o.statusID = toValue
                        WHERE      fieldID = ' . $this->ids['field'] . '
                        AND        toValue IN ' . $clause['status'] . '
                        GROUP BY   primeKey
                ) lv
                JOIN      logs_orders c ON c.id = lv.maxLogID
                JOIN      neworder n ON n.id = lv.primeKey
                JOIN      order_batches ob ON ob.id = n.order_batch
                 ' . $clause['join'] . '
                JOIN      statuses s ON s.id = n.statusID
                WHERE     ' . $clause['where'] . '
                AND       s.id  IN ' . $clause['status'] . '
                GROUP by  vendorID,
                          n.id
                ';
    }    
        
    /*
    ****************************************************************************
    */
    
    function getOrdersQuery()
    {
        $param = array_merge($this->processedStatus, $this->notProcessedStatus);
   
        $status = '(' . implode("," , $param) . ')';
        
        return '
                SELECT  n.scanOrderNumber,
                        vendorID,
                        DATE(logTime),
                        COUNT(n.id) AS valueCount,
                        maxLogID
                FROM  (
                        SELECT     primeKey,
                                   MAX(logID) AS maxLogID
                        FROM       logs_values
                        JOIN       neworder o ON o.statusID = toValue
                        WHERE      fieldID = ' . $this->ids['field'] . '
                        AND        toValue IN ' . $status . '
                        GROUP BY   primeKey
                ) lv
                JOIN      logs_orders c ON c.id = lv.maxLogID
                JOIN      neworder n ON n.id = lv.primeKey
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      statuses s ON s.id = n.statusID
                WHERE     s.id  IN ' . $status . '
                GROUP by  vendorID,
                          n.id
                ';
    }    
    
    /*
    ****************************************************************************
    */
    
    function getLabelsQuery()
    {
        $param = array_merge($this->processedStatus, $this->notProcessedStatus);
   
        $status = '(' . implode("," , $param) . ')';
        
        return '
                SELECT  n.scanOrderNumber,
                        vendorID,
                        DATE(logTime),
                        COUNT(DISTINCT oo.shipment_tracking_id) AS valueCount,
                        maxLogID
                FROM  (
                        SELECT     primeKey,
                                   MAX(logID) AS maxLogID
                        FROM       logs_values
                        JOIN       neworder o ON o.statusID = toValue
                        WHERE      fieldID = ' . $this->ids['field'] . '
                        AND        toValue IN ' . $status . '
                        GROUP BY   primeKey
                ) lv
                JOIN      logs_orders c ON c.id = lv.maxLogID
                JOIN      neworder n ON n.id = lv.primeKey
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      online_orders oo
                        ON oo.SCAN_SELDAT_ORDER_NUMBER = n.scanOrderNumber
                JOIN      statuses s ON s.id = n.statusID
                WHERE     s.id  IN ' . $status . '
                GROUP by  vendorID,
                          n.id
                ';
    }    
    
    /*
    ****************************************************************************
    */
    
    function getWorkOrdersQuery()
    {
        $param = array_merge($this->processedStatus, $this->notProcessedStatus);
   
        $status = '(' . implode("," , $param) . ')';
        
        return '
                SELECT  CONCAT(n.scanOrderNumber, ch.chg_cd_id),
                        n.scanOrderNumber
                        vendorID,
                        DATE(logTime),
                        chg_cd,
                        SUM(qty) AS labor,
                        maxLogID
                FROM  (
                        SELECT     primeKey,
                                   MAX(logID) AS maxLogID
                        FROM       logs_values
                        JOIN       neworder o ON o.statusID = toValue
                        WHERE      fieldID = ' . $this->ids['field'] . '
                        AND        toValue IN ' . $status . '
                        GROUP BY   primeKey
                ) lv
                JOIN      logs_orders c ON c.id = lv.maxLogID
                JOIN      neworder n ON n.id = lv.primeKey
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      wo_dtls wd ON wd.scn_ord_num = n.scanordernumber
                JOIN      charge_cd_mstr ch ON ch.chg_cd_id = wd.chg_cd_id
                JOIN      statuses s ON s.id = n.statusID
                WHERE     s.id  IN ' . $status . '
                AND       wd.sts != "d"
                GROUP by  vendorID,
                          wd.chg_cd_id,
                          n.id
                ';
    }    
    
    
    /*
    ****************************************************************************
    */
    
    function getShippedQuery()
    {
        return '
                SELECT  n.scanOrderNumber,
                        vendorID,
                        si.carrier,
                        DATE(logTime),
                        maxLogID,
                        COUNT(n.id) AS valueCount
                FROM  (
                        SELECT     primeKey,
                                   MAX(logID) AS maxLogID
                        FROM       logs_values
                        JOIN       neworder o ON o.statusID = toValue
                        WHERE      fieldID = ' . $this->ids['field'] . '
                        AND        toValue = ' . implode(",", $this->shipStatus) . '
                        GROUP BY   primeKey
                ) lv
                JOIN      logs_orders c ON c.id = lv.maxLogID
                JOIN      neworder n ON n.id = lv.primeKey
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      shipping_orders so ON so.orderID = n.id
                JOIN      shipping_info si ON so.bolID = si.bolLabel
                JOIN      statuses s ON s.id = n.statusID
                WHERE     s.id  = ' . implode(",", $this->shipStatus) . '
                GROUP by  vendorID,
                          n.id,
                          si.carrier
                ';
    }

    
    /*
    ****************************************************************************
    */
    
    function getCancelledQuery()
    {
        return '
                SELECT  n.scanOrderNumber,
                        vendorID,
                        DATE(logTime),
                        COUNT(n.id) AS valueCount,
                        maxLogID
                FROM  (
                        SELECT     primeKey,
                                   MAX(logID) AS maxLogID
                        FROM       logs_values
                        JOIN       neworder o ON o.statusID = toValue
                        WHERE      fieldID = ' . $this->ids['field'] . '
                        AND        toValue = ' . implode(",", $this->cancelStatus) . '
                        GROUP BY   primeKey
                ) lv
                JOIN      logs_orders c ON c.id = lv.maxLogID
                JOIN      neworder n ON n.id = lv.primeKey
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      statuses s ON s.id = n.statusID
                WHERE     s.id  = ' . implode(",", $this->cancelStatus) . '
                GROUP by  vendorID,
                          n.id
                ';
    }

    /*
    ****************************************************************************
    */
    
    function getOrderProcessingSummary()
    {
        $totalTime = self::init()->timer();
        
        //get the max log_id from ctn_log_sum
        $logIDs = $this->getLogIDs('ord_sum_ctn', 'logs_orders');
    
        if ( ! $logIDs) {
            return [];
        }
        
        $logs = new \logs\fields($this->db);

        $lFieldID = $logs->getFieldID('orders');
       
        //get the order status
        $statuses = new \tables\statuses\orders($this->db);
        
        $ship = \tables\orders::STATUS_SHIPPED_CHECK_OUT;
        $this->shipStatus = $statuses->getOrderStatusID($ship);
        
        $cancel = \tables\orders::STATUS_CANCELED;
        $this->cancelStatus = $statuses->getOrderStatusID($cancel);
  
        $this->processedStatus =  $this->getInvoiceProcessedOrder();
        
        $this->notProcessedStatus = $this->getInvoiceNotProcessedOrder();
        
        $this->orderStatus = array_merge($this->processedStatus, $this->notProcessedStatus);
        
        $toStatus = array_merge($this->orderStatus, $this->cancelStatus);
    

        $logValuesSql = '
            SELECT  primeKey,
                    maxLogID,
                    DATE(logTime) AS logDate
            FROM  
            (
                SELECT     primeKey,
                           MAX(logID) AS maxLogID
                FROM       logs_values lv
                WHERE      logID IN (' . $this->db->getQMarkString($logIDs) . ')
                AND        fieldID = ' . $lFieldID . '
                AND        toValue IN (' . implode(',' , $toStatus) . ')
                GROUP BY   primeKey
            ) lv
            JOIN   logs_orders lo ON lo.id = lv.maxLogID';

        $logValuesRes = $this->db->queryResults($logValuesSql, $logIDs);

        $ordIDs = array_keys($logValuesRes);
      
        if ( ! $ordIDs) {
            return [];
        }
        
        $this->ordIDs = '(' . implode(',' , $ordIDs) . ')';
    
        $this->insertOrderSummary($logValuesRes);
        
        $totalTime->timer();

        return [];
    }
        
    /*
    ****************************************************************************
    */
    
    function insertOrderSummary($logValuesRes)
    {
        $commonTable = [
                'carton'  =>  'ord_sum_ctn',
                'piece'   =>  'ord_sum_pcs',
                'volume'  =>  'ord_sum_vol',
                'plate' =>    'ord_sum_plt',
        ];

        
        //cartons,pieces,plates,volume
        $commonQuery = '
                    ' . $this->commonQuery('processed') . '
                    UNION
                    ' .  $this->commonQuery()  . '
                    ';
        
        $commonResult =  $this->db->queryResults($commonQuery);
    
        
        //orders,labels,workOrders,shipped Orders,cancelled orders
        $orderResult =  $this->orderQuery();
        $labelResult =  $this->labelQuery();
        $workResult =  $this->workQuery();
        $shipResult =  $this->shipOrderQuery();
        $cancelResult =  $this->cancelledQuery();

        $this->app->beginTransaction();
        
        //ord_sum_ctn,ord_sum_pcs,ord_sum_vol,ord_sum_plt
        foreach ($commonResult as $ordNum => $row) {
            
            foreach ($commonTable as $key => $table) {

                $sql = $this->insertOrderSql($table, $this->fields);
                
                $ordID = $row['id'];
                
                $dt = $logValuesRes[$ordID]['logDate'];
        
                $logID = $logValuesRes[$ordID]['maxLogID'];
             
                $this->app->runQuery($sql, [
                        $ordNum,
                        $row['vendorID'],
                        $dt,
                        $row[$key],
                        $logID
                ]);
            }
        }
        
        //ord_sum_ord 
        foreach ($orderResult as $ordNum => $row) {
            
            $sql = $this->insertOrderSql('ord_sum_ord', $this->fields);
                
            $ordID = $row['id'];
            
            $dt = $logValuesRes[$ordID]['logDate'];
        
            $logID = $logValuesRes[$ordID]['maxLogID'];

            $this->app->runQuery($sql, [
                    $ordNum,
                    $row['vendorID'],
                    $dt,
                    $row['valueCount'],
                    $logID
            ]);
        }
        
        //ord_sum_lbl
        foreach ($labelResult as $ordNum => $row) {
            
            $sql = $this->insertOrderSql('ord_sum_lbl', $this->fields);
                
            $ordID = $row['id'];

            $dt = $logValuesRes[$ordID]['logDate'];

            $logID = $logValuesRes[$ordID]['maxLogID'];

            $this->app->runQuery($sql, [
                    $ordNum,
                    $row['vendorID'],
                    $dt,
                    $row['valueCount'],
                    $logID,
            ]);
        }

        //ord_sum_wo
        foreach ($workResult as $row) {
            
            $sql = $this->insertOrderSql('ord_sum_wo', $this->workFields);
            
            $ordNum = $row['scanOrderNumber'];
                
            $ordID = $row['id'];
            
            $dt = $logValuesRes[$ordID]['logDate'];
        
            $logID = $logValuesRes[$ordID]['maxLogID'];

            $this->app->runQuery($sql, [
                    $ordNum,
                    $row['vendorID'],
                    $dt,
                    $row['chg_cd'],
                    $row['labor'],
                    $logID,
            ]);
        }

        //ord_ship_sum
        foreach ($shipResult as $ordNum => $row) {
            
            $sql = $this->insertOrderSql('ord_ship_sum', $this->shipFields);
                
            $ordID = $row['id'];
            
            $dt = $logValuesRes[$ordID]['logDate'];
        
            $logID = $logValuesRes[$ordID]['maxLogID'];

            $this->app->runQuery($sql, [
                    $ordNum,
                    $row['vendorID'],
                    $row['carrier'],
                    $dt,
                    $logID,
                    $row['valueCount']
            ]);
        }
        
        //ord_sum_cncl 
        foreach ($cancelResult as $ordNum => $row) {
            
            $sql = $this->insertOrderSql('ord_sum_cncl', $this->fields);
                
            $ordID = $row['id'];
            
            $dt = $logValuesRes[$ordID]['logDate'];
        
            $logID = $logValuesRes[$ordID]['maxLogID'];

            $this->app->runQuery($sql, [
                    $ordNum,
                    $row['vendorID'],
                    $dt,
                    $row['valueCount'],
                    $logID
            ]);
        }
    
        $this->app->commit();   
    }
    
    
    /*
    ****************************************************************************
    */
    
    
    function commonQuery($processed='notProcessed')
    {
        $clause = $this->orderClauses($processed);
        
        return '
                SELECT    n.scanOrderNumber,
                          n.id,  
                          vendorID,
                          COUNT(ca.id) AS carton,
                          SUM(ca.uom) AS piece,
                          COUNT(DISTINCT ca.plate) AS plate,
                          SUM(b.height * b.length * b.width / 1728) AS volume
                FROM      neworder n 
                JOIN      order_batches ob ON ob.id = n.order_batch
                 ' . $clause['join'] . '
                JOIN      inventory_batches b ON b.id = ca.batchID                     
                JOIN      statuses s ON s.id = n.statusID
                WHERE     ' . $clause['where'] . '
                AND       s.id  IN  ' . $clause['status'] . '
                AND       n.id IN  ' . $this->ordIDs . ' 
                GROUP by  vendorID,
                          n.id
                ';
    }    
    
   
    /*
    ****************************************************************************
    */
    
    
    function orderQuery()
    {
        $sql =  '
                SELECT    n.scanOrderNumber,
                          n.id,
                          vendorID,
                          COUNT(n.id) AS valueCount
                FROM      neworder n 
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      statuses s ON s.id = n.statusID
                WHERE     s.id  IN (' . implode(',' , $this->orderStatus) . ')
                AND       n.id IN  ' . $this->ordIDs . ' 
                GROUP by  vendorID,
                          n.id
                ';
   
        $result =  $this->db->queryResults($sql);
        
        return $result;
    }    
    
    /*
    ****************************************************************************
    */
    
    
    function labelQuery()
    {
        $sql = '  
                SELECT    n.scanOrderNumber,
                          n.id,
                          vendorID,
                          COUNT(DISTINCT oo.shipment_tracking_id) AS valueCount
                FROM      neworder n
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      online_orders oo
                        ON oo.SCAN_SELDAT_ORDER_NUMBER = n.scanOrderNumber
                JOIN      statuses s ON s.id = n.statusID
                WHERE     s.id  IN (' . implode(',' , $this->orderStatus) . ')
                AND       n.id IN  ' . $this->ordIDs . '
                GROUP by  vendorID,
                          n.id
                ';
   
        $result =  $this->db->queryResults($sql);
        
        return $result;
    }    
    
    /*
    ****************************************************************************
    */
    
    function workQuery()
    {
        $sql =  '
                SELECT    CONCAT(n.scanOrderNumber, "-", ch.chg_cd_id),
                          n.scanOrderNumber,
                          n.id,
                          vendorID,
                          chg_cd,
                          SUM(qty) AS labor
                FROM      neworder n 
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      wo_dtls wd ON wd.scn_ord_num = n.scanordernumber
                JOIN      charge_cd_mstr ch ON ch.chg_cd_id = wd.chg_cd_id
                JOIN      statuses s ON s.id = n.statusID
                WHERE     s.id  IN (' . implode(',' , $this->orderStatus) . ')
                AND       wd.sts != "d"
                AND       n.id IN  ' . $this->ordIDs . '
                GROUP by  vendorID,
                          wd.chg_cd_id,
                          n.id
                ';

        $result =  $this->db->queryResults($sql);
        
        return $result;
    }    
    
    /*
    ****************************************************************************
    */
    
    function shipOrderQuery()
    {
        $sql =  '
                SELECT    n.scanOrderNumber,
                          n.id,
                          vendorID,
                          si.carrier,
                          COUNT(n.id) AS valueCount
                FROM      neworder n 
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      shipping_orders so ON so.orderID = n.id
                JOIN      shipping_info si ON so.bolID = si.bolLabel
                JOIN      statuses s ON s.id = n.statusID
                WHERE     s.id  IN (' . implode(',' , $this->shipStatus) . ')
                AND       n.id IN  ' . $this->ordIDs . ' 
                GROUP by  vendorID,
                          n.id,
                          si.carrier
                ';
   
        $result =  $this->db->queryResults($sql);
    
        return $result;
    }    
    
        
    /*
    ****************************************************************************
    */
    
    function cancelledQuery()
    {
        $sql =  '
                SELECT    n.scanOrderNumber,
                          n.id,
                          vendorID,
                          COUNT(n.id) AS valueCount
                FROM      neworder n 
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      statuses s ON s.id = n.statusID
                WHERE     s.id  IN (' . implode(',' , $this->cancelStatus) . ')
                AND       n.id IN  ' . $this->ordIDs . ' 
                GROUP by  vendorID,
                          n.id
                ';
   
        $result =  $this->db->queryResults($sql);
        
        return $result;
    } 
    
        
    /*
    ****************************************************************************
    */
    
     function orderClauses($processed)
    {
        $join = $processed == 'processed' ? 
                'JOIN  inventory_cartons ca ON ca.orderID = n.id' :
                'JOIN  pick_cartons pc ON pc.orderID = n.id
                 JOIN  inventory_cartons ca ON ca.id = pc.cartonID
                ';
          
        $where = $processed == 'processed' ? ' 1 ' : 'pc.active';
        
        $status = $processed == 'processed' ? 
                        '(' . implode("," , $this->processedStatus) . ')' :
                        '(' . implode("," , $this->notProcessedStatus) . ')';
        
        return [
            'join'  => $join,
            'where' => $where,
            'status' => $status
        ];
        
    }
     
    /*
    ****************************************************************************
    */

    
    function insertOrderSql($table, $fieldName) 
    {
        $fields = array_keys($fieldName);
        
        $update = $table == 'ord_sum_wo' ? 'labor = VALUES(labor)' : 
                                    'val = VALUES(val)';

        $sql = 'INSERT  INTO ' . $table . ' 
                (' 
                    .  implode(',', $fields)  . 
                ') 
                VALUES
                (' 
                    .  $this->db->getQMarkString($fields)  . 
                ') ON DUPLICATE KEY UPDATE
                       log_id = VALUES(log_id),
                       dt = VALUES(dt),
                       ' . $update . ' 
                ';              

        return $sql;
    }
    
        
    /*
    ****************************************************************************
    */
}
