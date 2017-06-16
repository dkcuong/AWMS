<?php

namespace common;

class labor
{
    public $db;
    
    const ACTUAL = 'a';
    const ESTIMATED = 'e';
    
    const RUSH = 'r';
    const OVERTIME = 'o';

    /*
    ****************************************************************************
    */

    static function init($db) 
    {
        $labor = new static();
        $labor->db = $db;
        return $labor; 
    }
    
    /*
    ****************************************************************************
    */
    
    function get($passed)
    {
        switch ($passed['cat']) {
            case 'op':
                
                if (! $passed['scanNumbers']) {
                    return;
                }
           
                $cat = self::ACTUAL;
                
                $qMarks = $this->db->getQMarkString($passed['scanNumbers']);

                $params = $passed['scanNumbers'];
                $params[] = $cat;
  
                
                //check if order has actual value
                $sql = 'SELECT   scan_ord_nbr
                        FROM     wrk_hrs_ord_prc 
                        WHERE    scan_ord_nbr IN ('.$qMarks.')
                        AND      cat = ?';

                $orderRes = $this->db->queryResult($sql, $params);

                if ( ! $orderRes ) {
                    $cat = self::ESTIMATED;
                }
                
                $sql = 'SELECT    type,
                                  amount
                        FROM      wrk_hrs_ord_prc s
                        WHERE     scan_ord_nbr IN ('.$qMarks.')
                        AND       cat = ?
                        ORDER BY  id';
          
                
                $selectParams = $passed['scanNumbers'];
                $selectParams[] = $cat;

                $result = $this->db->queryResults($sql, $selectParams);
           
    
                $labor = [
                    'rushAmt'  => getDefault($result[self::RUSH]['amount']),
                    'otAmt'   => getDefault($result[self::OVERTIME]['amount'])
                ];
       
            return array_map('floatval', $labor);
        }
    }

    /*
    ****************************************************************************
    */

}
