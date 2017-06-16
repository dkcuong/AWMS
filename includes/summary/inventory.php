<?php

namespace summary;

class inventory extends model
{

    function getDefaultRecDates()
    {
        $sql = 'UPDATE ctn_sum s
                JOIN   inventory_batches b ON s.batch_id = b.batchID
                JOIN   inventory_containers c ON c.recNum = b.recNum
                SET    s.rcv_dt = c.setDate
                WHERE  rcv_dt IS NULL';

        $this->runQuery($sql);
    }

    /*
    ****************************************************************************
    */

    function defaultRDs($fieldInfo, $fieldIDs)
    {
        $this->export('defaultRDs.sql', '
                SELECT primeKey,
                       logTime
                FROM   logs_values lv
                JOIN   logs_cartons c ON c.id = lv.logID
                WHERE  fieldID = '.$fieldIDs['statusID']['id'].'
                AND    fromValue = '.$fieldInfo['statusIDs']['IN'].'
                AND   ( toValue = '.$fieldInfo['statusIDs']['RC'].'
                OR     toValue = '.$fieldInfo['statusIDs']['RK'].' )
                ORDER BY lv.id DESC
        ', 'drdIter');
    }
    
    /*
    ****************************************************************************
    */

    function shippedCartonsIter($row)
    {
        $this->batch[] = '
            INSERT INTO ctn_sum (
                carton_id, batch_id, cust_id, last_active, vol
            ) VALUES (
                '.$row['cartonID'].',
                '.$row['batchID'].',
                '.$row['vendorID'].',
                "'.$row['lastActive'].'",
                "'.$row['vol'].'"
            );
        ';
    }    
    
    /*
    ****************************************************************************
    */

    function missingRCVDTs()
    {
        $sql = 'UPDATE ctn_sum s
                JOIN   inventory_batches b ON s.batch_id = b.id
                JOIN   inventory_containers c ON c.recNum = b.recNum
                SET    s.rcv_dt = c.setDate
                WHERE  rcv_dt IS NULL';
        
        $this->runQuery($sql);
    }
    
    /*
    ****************************************************************************
    */

    function dailyChanges($logRange)
    {
        $sql = 'SELECT v.id,
                       logID,
                       COUNT(primeKey) AS count,
                       primeKey,
                       toValue,
                       fromValue,
                       vendorID,
                       fieldID
                FROM   logs_values v
                JOIN   inventory_cartons ca ON ca.id = primeKey
                JOIN   inventory_batches b ON b.id = ca.batchID
                JOIN   inventory_containers co ON co.recNum = b.recNum
                WHERE  1
                AND    logID <= "'.intVal($logRange['maxID']).'"
                AND    logID >= "'.intVal($logRange['minID']).'"
                GROUP BY logID
                ORDER BY v.id DESC
                ';

        return $this->queryResults($sql);
    }
   
    /*
    ****************************************************************************
    */

}
