<?php

namespace tables;

class refs extends _default
{
    public $primaryKey = 'r.id';
    
    public $ajaxModel = 'refs';
    
    public $fields = [
        'ref' => [
            'display' => 'Reference',
        ],
        'work' => [
            'display' => 'Work',
        ],
       'inputName' => [
            'display' => 'Input Name',
            'searcherDate' => TRUE,
        ],  
       'prefix' => [
            'display' => 'Prefix',
        ],  
        'actibe' => [
            'display' => 'Active',
        ],
    ];
         
    public $table = 'refs AS r';
    
    public $groupBy = 'r.id';

    /*
    ****************************************************************************
    */

    function getPrefixes($isAll = FALSE)
    {
        $sql = 'SELECT   prefix,
                         (
                            CASE prefix
                                WHEN "rec"  THEN "Receiving Invoices"
                                WHEN "stor" THEN "Storage Invoicese"
                                WHEN "wo"   THEN "Work Order"
                                ELSE "Order Processing"
                            END
                         ) AS displayName
                FROM     '.$this->table.'
                WHERE    active
                ORDER BY prefix ASC, 
                         sort ASC';
        
        $results = $this->app->queryResults($sql);
        
        if ($isAll && count($results) > 1) {
            $results = array_merge([
                'all' => ['displayName' => 'ALL']
            ], $results);
        }
        return $results;
    }
    
    /*
    ****************************************************************************
    */
   
    function getRefs($client = '0', $prefix = FALSE)
    {
        $param[] = $client;
        $clause = 'r.Active ';

        if ($prefix && strtoupper($prefix) != 'ALL') {
            $clause .= ' AND prefix = ?';
            $param[] = $prefix;
        }
        
        $sql = 'SELECT  r.id, 
                        ref,
                        inputName,
                        work,
                        prefix, 
                        cost 
                FROM    refs r 
                LEFT JOIN (
                    SELECT  c.id,
                            vendorID,
                            refID,
                            cost
                    FROM    costs c
                    WHERE   c.vendorID = ?
                ) c ON c.refID = r.ID 
                WHERE '.$clause.'
                ORDER BY prefix ASC, 
                         sort ASC';

        $results = $this->app->queryResults($sql, $param);

        return $results;
    }
    
    /*
    ****************************************************************************
    */
   
}