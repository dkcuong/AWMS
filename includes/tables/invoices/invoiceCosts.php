<?php

namespace tables\invoices;

class invoiceCosts extends \tables\_default
{
    public $ajaxModel = 'invoices\\invoiceCosts';

    public $primaryKey = 'ic.inv_cost_id';

    public $fields = [
        'chg_cd_type' => [
            'display' => 'CHRG TYPE',
          ],
        'chg_cd' => [
            'display' => 'CHRG CODE',
        ],
        'chg_cd_cur' => [
            'display' => 'CUR',
        ],
        'chg_cd_price' => [
            'select' => 'chg_cd_price',
            'display' => 'PRICE',
        ],
    ];

    public $table = 'invoice_cost ic
        JOIN charge_cd_mstr chm ON chm.chg_cd_id = ic.chg_cd_id
        ';

    /*
    ****************************************************************************
    */

    function getCosts($vendorIDs=[], $uoms=FALSE)
    {
        $clause = $vendorIDs ? 
                'WHERE  cust_id IN ('.$this->app->getQMarkString($vendorIDs).')' 
                   : NULL;

        $sql = 'SELECT inv_cost_id,
                       cust_id,
                       chg_cd,
                       chg_cd_des,
                       chg_cd_price,
                       chg_cd_uom,
                       chg_cd_type
                FROM   invoice_cost ic
                JOIN   vendors v ON v.id = cust_id
                JOIN   charge_cd_mstr cc ON cc.chg_cd_id = ic.chg_cd_id
                '.$clause.'
                AND    chg_cd_sts = "active"
                AND    ic.status != "d"';

        $results = $this->app->queryResults($sql, $vendorIDs);

        $return = [];

        foreach ($results as $value) {

            $vendorID = $value['cust_id'];
            $type = $value['chg_cd_type'];
            $chargeCode = $value['chg_cd'];

            $return['rates'][$type][$vendorID][$chargeCode] = $uoms ?
                $value['chg_cd_uom'] : $value['chg_cd_price'];
            $return['info'][$type][$chargeCode] = $value;
        }

        return $return;
    }

    /*
    ****************************************************************************
    */
    
    function checkRecvClientUOM($custIDs, $uom=NULL) 
    {
        if (! $custIDs) {
            return NULL;
        }
        
        $custClause = $custIDs ?  'AND cust_id IN ('.implode(',', $custIDs).')' : NULL;
        
        $uomClause = $uom ?  'AND chg_cd_uom = ' . $this->app->quote($uom) : NULL;
        
        $sql = 'SELECT    cust_id,
                          chg_cd_uom AS uom,
                          chg_cd_price AS price 
                FROM      invoice_cost c        
                JOIN      charge_cd_mstr ch ON ch.chg_cd_id = c.chg_cd_id
                WHERE     chg_cd_sts = "active"
                '.$custClause.'
                '.$uomClause.'
                AND       chg_cd_type = "RECEIVING"      
                AND       c.status != "d"
                ';
        
        $result = $this->app->queryResults($sql);
       
        return $result ? $result : [];
    }
    
    /*
    ****************************************************************************
    */ 
    
    function getReceivingUOM($vendorIDs) 
    {
        $clause = $vendorIDs ? 'AND cust_id = ' . $vendorIDs : NULL;
        
        $sql = 'SELECT    inv_cost_id,
                          chg_cd,
                          chg_cd_uom AS uom,
                          chg_cd_price AS price,
                          cust_id
                FROM      invoice_cost c        
                JOIN      charge_cd_mstr ch ON ch.chg_cd_id = c.chg_cd_id
                WHERE     chg_cd_sts = "active"
                '.$clause.'
                AND       chg_cd_type = "RECEIVING"      
                AND       c.status != "d"
                ';

        return $this->app->queryResults($sql);
    }
    
    /*
    ****************************************************************************
    */ 
    
    function getLaborCharge()
    {
    
        $sql = 'SELECT  chg_cd_id,
                        chg_cd,
                        chg_cd_des,
                        chg_cd_uom 
                FROM    charge_cd_mstr cc 
                WHERE   chg_cd_uom = "LABOR"
                AND     chg_cd_sts = "active"';

        $results = $this->app->queryResults($sql);
        
        $return = [];

        foreach ($results as $value) {
            $chargeCode = $value['chg_cd'];

            $return['info'][$chargeCode] = $value;
        }

        return $return;
    }
        
    
    /*
    ****************************************************************************
    */
}
