<?php

namespace tables;

class containersReceived extends \tables\_default
{
    public $ajaxModel = 'containersReceived';

    public $primaryKey = 'ca.id';

    public $where = 'NOT isSplit
                    AND NOT unSplit';
    
    public $groupBy = 'b.id';
    
    public $displaySingle = 'ContainerReceived';
      
    /*
    ****************************************************************************
    */
    
    function fields()
    {
        $fields = [
            'vendor' => [
                'select' => 'CONCAT(w.shortName, "_", vendorName)',
                'display' => 'Client Name',
                'noEdit' => TRUE,
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            ],
            'setDate' => [
                'display' => 'Set Date', 
                'searcherDate' => TRUE,
                'orderBy' => 'ca.id',
                'noEdit' => TRUE,
            ],
            'container' => [
                'select' => 'name', 
                'display' => 'Container Name',
                'noEdit' => TRUE,
            ],
            'recNum' => [
                'select' => 'co.recNum',
                'display' => 'Receiving Number',
                'noEdit' => TRUE,
            ],
            'batchnumber' => [
                'select' => 'b.id', 
                'display' => 'Batch Number',
                'noEdit' => TRUE,
            ],
            'upc' => [
                'select' => 'u.upc', 
                'display' => 'UPC',
                'noEdit' => TRUE,
            ],
            'SKU' => [
                'select' => 'u.sku',
                'display' => 'SKU',
                'noEdit' => TRUE,
            ],
            'prefix' => [
                'display' => 'Prefix',
                'noEdit' => TRUE,
            ],
            'sufix' => [
                'select' => 'suffix',
                'display' => 'Suffix',
                'noEdit' => TRUE,
            ],
            'expectedCartons' => [
                'select' => 'initialCount',
                'display' => 'Expected Cartons',
                'noEdit' => TRUE,
            ],
            'actualCartons' => [
                'select' => 'SUM(IF(s.shortName != "IN", 1, 0))',
                'display' => 'Actual Cartons',
                'noEdit' => TRUE,
                'groupedFields' => 's.shortName',
            ],
            'expectedPieces' => [
                'select' => 'initialCount * UOM',
                'display' => 'Expected Pieces',
                'noEdit' => TRUE,
            ],
            'actualPieces' => [
                'select' => 'SUM(IF(s.shortName != "IN", uom, 0))',
                'display' => 'Actual Pieces',
                'noEdit' => TRUE,
                'groupedFields' => 's.shortName',
            ],
            'delta' => [
                'select' => 'initialCount * UOM / SUM(
                            IF(s.shortName != "IN", uom, 0)
                         )',
                'display' => 'Pieces Delta, %',
                'noEdit' => TRUE,
                'groupedFields' => 's.shortName, uom',
            ],
            'errStatus' => [
                'select' => 'IF(
                            ABS(
                               initialCount * UOM / SUM(
                                    IF(s.shortName != "IN", uom, 0)
                               )
                            ) >= 1,
                            "Alert", "OK"
                        )',
                'display' => 'Status',
                'noEdit' => TRUE,
                'groupedFields' => 's.shortName, uom',
            ],
            'clientNotes' => [
                'select' => 'clientNotes',
                'display' => 'Client Note',
            ]
        ];
        
        return $fields;
   }
   
   /*
    ****************************************************************************
   */

    function table()
    {
        return 'inventory_cartons ca
                JOIN inventory_batches b ON b.id = ca.batchID
                JOIN inventory_containers co ON co.recNum = b.recNum
                JOIN upcs u ON u.id = b.upcID
                JOIN statuses s ON s.id = ca.statusID
                JOIN vendors v ON v.id = co.vendorID
                JOIN warehouses w ON v.warehouseID = w.id
                LEFT JOIN container_notes cn ON cn.recNum = co.recNum';
    } 
    
   /*
    ****************************************************************************
   */
    
    function containerClientNotes($data) 
    {
        //get the post data       
        $recNum = $data['recNum'];
        $comment = $data['notes'];
        
        //select the container from container_notes table
        $sql = 'SELECT  recNum 
                FROM container_notes
                WHERE   recNum = ?';

        $notesResults = $this->app->queryResults($sql, [$recNum]);
        
        if ($notesResults) {
            //Update client notes to the container_notes table
            $sql = 'UPDATE  container_notes
                    SET    clientNotes = ?
                    WHERE  recNum = ?';
                     
             $this->app->runQuery($sql, [$comment, $recNum]);
        } else {
            //Insert into container_notes table
            $sql = 'INSERT INTO container_notes (
                        recNum,
                        clientNotes
                    ) VALUES (
                        ?, ?
                    )';
            $this->app->runQuery($sql, [$recNum, $comment]);
        }
        

        return TRUE;
            
    }      
}   