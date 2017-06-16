<?php

namespace tables\inventory;

class upcs extends \tables\_default
{
    public $primaryKey = 'u.id';
    
    public $ajaxModel = 'inventory\\upcs';
    
    public $fields = [
        'upc' => [
            'select' => 'u.upc',
            'display' => 'UPC',
            'exactSearch' => TRUE,
        ],
        'category' => [
            'select' => 'uc.name',
            'display' => 'Category',
            'searcherDD' => 'inventory\\upcsCategories',
            'ddField' => 'uc.name',
            'update' => 'u.catID',
        ],
        'sku' => [
            'select' => 'u.sku',
            'display' => 'Style',
        ],
        'size1' => [
            'select' => 'size',
            'display' => 'Size',
        ],
        'color1' => [
            'select' => 'color',
            'display' => 'Color',
        ],
        'totalBatches' => [
            'select' => 'COUNT(b.id)',
            'display' => 'Inventory Batches',
            'groupedFields' => 'b.id',
        ],
    ];

    public $table = 'upcs u
           LEFT JOIN inventory_batches b ON b.upcID = u.id
           LEFT JOIN upcs_categories uc ON uc.id = u.catID';

    public $where = 'u.active';

    public $groupBy = 'u.sku, u.upc';
    
    public $mainField = 'u.id';

    /*
    ****************************************************************************
    */
    
    function getUPCStatuses($upcs)
    {
        $newUPCs = $oldUPCs = [];
        
        $sql = 'SELECT id
                FROM   upcs
                WHERE  upc = ?';
        
        foreach ($upcs as $rowID => $upc) {
            $found = $this->app->queryResult($sql, [$upc]);

            if ($found) {
                $oldUPCs[$rowID] = $found['id'];
            } else {
                // Return arrays of rows by upc needed
                $newUPCs[$upc][] = $rowID;
            }
        }
        
        $results = [
            'newUPCs' => $newUPCs,
            'oldUPCs' => $oldUPCs,
        ];
        
        return $results;
    }

    /*
    ****************************************************************************
    */
            
    function insertNewUPCs($upcInfo)
    {
        $sql = 'INSERT INTO upcs (upc) 
                VALUES (?)';
        
        foreach ($upcInfo['newUPCs'] as $newUPC => $rows) {
            
            if (! $newUPC) {
                continue;
            }
            
            $this->app->runQuery($sql, [$newUPC]);
            $insertID = $this->app->lastInsertId();
            
            if (! $insertID) {
                continue;
            }
            
            foreach ($rows as $row) {
                $upcInfo['oldUPCs'][$row] = $insertID;
            }
            
        }
        
        return $upcInfo['oldUPCs'];
    }
                
    /*
    ****************************************************************************
    */
    
    function getUPCID($upc)
    {
        $sql = 'SELECT    id
                FROM      upcs
                WHERE     upc = ?';        

        $result = $this->app->queryResult($sql, [$upc]);
        
        return $result ? $result['id'] : FALSE;  
    }
                
    /*
    ****************************************************************************
    */
    
    function getUPCs($values=NULL, $target='upc')
    {
        $allowTarget = ['upc', 'sku'];
        
        if (! in_array($target, $allowTarget)) {
            return [];
        }
        
        $qMark = $values ? $this->app->getQMarkString($values) : NULL;

        $clause = $values ? $target . ' IN ( ' . $qMark . ')' : 1;
        
        $sql = 'SELECT    ' . $target . ',
                          id
                FROM      upcs
                WHERE     ' . $clause;

        $results = $values ? $this->app->queryResults($sql, $values) :
            $this->app->queryResults($sql);
        
        return $results;
    }

    /*
    *****************************************************************************
    */

    function listCategoryUPC()
    {
        $sql = 'SELECT  id,
                        name
                FROM    upcs_categories
                WHERE   active';

        $results = $this->app->queryResults($sql);
        
        return $results;
    }

    /*
    *****************************************************************************
    */
}