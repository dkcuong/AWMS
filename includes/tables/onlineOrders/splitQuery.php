<?php

namespace tables\onlineOrders;


use tables\inventory\cartons;
use tables\statuses\inventory;

class splitQuery
{
    const DEBUG = FALSE;
    
    public $isImport;
    
    static $nextCartonID;
    static $cartonsIDsByIDs = [];
    static $maxCartons = [];
     
    private $app;
    
    /*
    ****************************************************************************
    */
    
    public function __construct($params)
    {
        $model = $params['model'];
        $this->app = $model->app;
        $this->isImport = $params['isImport'];
        
        if ($params['findBatches']) {
            return;
        }
        
        self::$nextCartonID = self::$nextCartonID ? 
            self::$nextCartonID : $model->getNextID('inventory_cartons');
    }

    /*
    ****************************************************************************
    */
    
    public function getMaxCarton($batchID, $parentID)
    {
        if (! isset(self::$maxCartons[$batchID])) {

            $subSql = 'SELECT MAX(cartonID)
                       FROM inventory_cartons
                       WHERE batchID = ?';

            $sql = '-- getMaxCarton
                    SELECT batchID,
                           plate,
                           locID,
                           mLocID,
                           orderUserID,
                           orderID,
                           statusID,
                           mStatusID,
                           0 AS isSplit,
                           0 AS unSplit,
                           (' . $subSql . ') AS cartonID,
                           vendorCartonID,
                           rackDate
                    FROM   inventory_cartons c
                    JOIN   statuses s ON s.id = c.statusID
                    LEFT JOIN   locations l ON l.id = c.locID
                    WHERE  c.id = ?
                    AND c.statusID = c.mStatusID
                    AND s.shortName = "RK"
                    AND ! isMezzanine';

            $params = [$batchID, $parentID];

            self::$maxCartons[$batchID] = $this->app->queryResult($sql, $params);
        }
        
        return self::$maxCartons[$batchID];
    }

    /*
    ****************************************************************************
    */
    
    public function createChildCarton($carton)
    {
        $caID = self::$nextCartonID++;
        
        $batch = $carton['batchID'];
        $this->storeChildCartonID($batch, $carton['cartonID'], $caID);
        self::$maxCartons[$batch]['cartonID']++;
        self::DEBUG ? varDump(['max' => self::$maxCartons[$batch]]) : NULL;
        
        $fields = array_keys($carton);

        $sql = 'INSERT INTO inventory_cartons ('.implode(',', $fields).') 
                VALUES('.$this->app->getQMarkString($carton).')';
        
        if (! $this->isImport) {
            $this->app->runQuery($sql, array_values($carton));
        }
            
    }

    /*
    ****************************************************************************
    */

    public function updateParentCarton($cartonID)
    {
        $sql = 'UPDATE inventory_cartons
                SET isSplit = 1
                WHERE id = ?';

        if (! $this->isImport)
        {
            $this->app->runQuery($sql, [$cartonID]);
            
            \common\logger::edit([
                'db' => $this->app,
                'primeKeys' => [$cartonID],
                'fields' => [
                    'isSplit' => [
                        'fromValues' => [0],
                        'toValues' => 1
                    ]
                ],
                'transaction' => FALSE
            ]);
        }
       
    }

    /*
    ****************************************************************************
    */
    
    public function getChildCartonID($batchID, $cartonID)
    {
        $cartonsIDsByID = getDefault(self::$cartonsIDsByIDs[$batchID][$cartonID]);
        return $cartonsIDsByID ? $cartonsIDsByID : 
            die('Error: Missing Batch Carton ID: '.$batchID.'-'.$cartonID);
    }
    
    /*
    ****************************************************************************
    */
    
    public function storeChildCartonID($batchID, $cartonID, $caID)
    {
        self::$cartonsIDsByIDs[$batchID][$cartonID] = $caID;
    }
    
    /*
    ****************************************************************************
    */
    
    public function storeChildCartonIDs($batches)
    {
        $statuses = new inventory($this->app);
        $rackID = $statuses->getStatusID(cartons::STATUS_RACKED);

        if (! $batches || self::$cartonsIDsByIDs) {
            return;
        }
        
        $qMarks = $this->app->getQMarkString($batches);

        $sql = 'SELECT c.id,
                       batchID,
                       cartonID,
                       uom
                FROM   inventory_cartons c
                WHERE  batchID IN (' . $qMarks . ')
                AND    statusID = ?
                AND    statusID = mStatusID
                AND    NOT isSplit
                AND    NOT unSplit';

        $params = array_merge($batches, [$rackID]);

        $results = $this->app->queryResults($sql, $params);

        foreach ($results as $caID => $row) {
            $batchID = $row['batchID'];
            $cartonID = $row['cartonID'];
            $this->storeChildCartonID($batchID, $cartonID, $caID);
        }
    }

}