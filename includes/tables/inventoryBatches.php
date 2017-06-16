<?php

namespace tables;

class inventoryBatches extends _default
{
    public $fields = [
        'id' => [
            'display' => '',
            'ignore' => TRUE,
        ],
        'containerID' => ['display' => '',],
        'receivingNumber' => ['display' => '',],
        'sku' => ['display' => '',],
        'uom' => ['display' => '',],
        'prefix' => ['display' => '',],
        'suffix' => ['display' => '',],
        'height' => ['display' => '',],
        'width' => ['display' => '',],
        'length' => ['display' => '',],
        'weight' => ['display' => '',],
        'upcID' => ['display' => '',],
        'totalCarton' => ['display' => '',],
        'orderLabel' => [
            'display' => '',
            'ignore' => TRUE,
        ]
    ];
    
    public $table = 'inventory_batches';
    
    /*
    ****************************************************************************
    */
    
    function createBatches($cartons, $containerID, $batches)
    {
        // Next receiving number
        $receivingNumber = $this->getNextID('receiving_numbers');
        
        $this->app->beginTransaction();
        
        $fields = $this->getNonIgnoredFields([], TRUE);
        
        $sql = 'INSERT INTO '.$this->table.' ('.implode(',', $fields).') 
                VALUES ('.$this->app->getQMarkString($fields).')';   

        $recNumSQL = 'INSERT INTO receiving_numbers () VALUES ()';
        
        $batchQuantites = [];
        foreach ($batches as $batchCount => $batch) {
            $totalCarton = $batch['totalCarton'];
            $batch = array_values($batch);
            $currentRecNumber = $receivingNumber + $batchCount;
            array_unshift($batch, $containerID, $currentRecNumber);
        
            $this->app->runQuery($sql, $batch);
            // Increment receiving numbers
            $this->app->runQuery($recNumSQL);
            
            $batchID = $this->app->lastInsertID();
            $batchQuantites[$batchID] = $totalCarton;
        }

        foreach ($batchQuantites as $batchID => $totalCarton) {
            $cartons->add($batchID, $totalCarton);
        }
        
        $this->app->commit();
    }    
    
    /*
    ****************************************************************************
    */
}

