<?php

namespace tables\customer;


class chargeCodeMaster extends \tables\_default{

    public $ajaxModel = 'customer\\chargeCodeMaster';
    
    public $displaySingle = 'Charge Code';

    public $primaryKey = 'chm.chg_cd_id';

    public $fields = [
       'chg_cd' => [
            'select' => 'chg_cd',
            'display' => 'CODE',
        ],
        'chg_cd_des' => [
            'select' => 'chg_cd_des',
            'display' => 'DESC',
        ],
        'chg_cd_type' => [
            'select' => 'chg_cd_type',
            'display' => 'TYPE',
            'searcherDD' => 'statuses\\chargeCodeType',
            'ddField' => 'displayName',
            'update' => 'chg_cd_type',
            'updateType' => 'TRUE'
        ],
        'chg_cd_uom' => [
            'select' => 'chg_cd_uom',
            'display' => 'UOM',
            'searcherDD' => 'statuses\\chargeUOM',
            'ddField' => 'displayName',
            'update' => 'chg_cd_uom',
            'updateUOM' => 'TRUE'
        ]
    ];

    public $where = 'chm.status <> "d"';
    
    public $table = 'charge_cd_mstr chm';

    public $insertTable = 'charge_cd_mstr';

    public $customInsert = 'customer\\chargeCodeMaster';
  
    /*
    ****************************************************************************
    */

    function getChargeCodeType($isAll = FALSE)
    {
        $sql = 'SELECT   chg_cd_type,
                         (
                            CASE chg_cd_type
                                WHEN "RECEIVING"  THEN "Receiving Charge Codes"
                                WHEN "STORAGE" THEN "Storage Charge Codes"
                                WHEN "ORD_PROC"   THEN "Order Processing Charge Codes"
                            END
                         ) AS displayName
                FROM     '.$this->table.'
                WHERE   status != "d"
                AND     chg_cd_type != "OTHER_SERV"        
                ORDER BY chg_cd_type DESC,
                         disp_ord ASC';

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

    function getClientCharges($client = '0', $prefix = FALSE)
    {
        $param[] = $client;
        $clause = 'chg_cd_sts = "active" 
                   AND ch.status <> "d"';

        if ($prefix && strtoupper($prefix) != 'ALL') {
            $clause .= ' AND chg_cd_type = ?';
            $param[] = $prefix;
        }

        $sql = 'SELECT  ch.chg_cd_id,
                        chg_cd,
                        chg_cd_des,
                        chg_cd_uom,
                        chg_cd_type,
                        c.chg_cd_cur,
                        c.chg_cd_price
                FROM    charge_cd_mstr ch
                LEFT JOIN (
                    SELECT  c.inv_cost_id,
                            cust_id,
                            c.chg_cd_id,
                            chg_cd_cur,
                            chg_cd_price
                    FROM    invoice_cost c
                    WHERE   status != "d" AND
                            c.cust_id = ?
                ) c ON c.chg_cd_id = ch.chg_cd_id
                WHERE '.$clause.'
                ORDER BY chg_cd_type ASC,
                         disp_ord ASC';

        $results = $this->app->queryResults($sql, $param);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getChargIDsByCodes($chargeCodes)
    {
        if (! $chargeCodes) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($chargeCodes);

        $sql = 'SELECT    chg_cd,
                          chg_cd_id
                FROM      charge_cd_mstr
                WHERE     chg_cd IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $chargeCodes);

        return $results;
    }
    
    /*
    ****************************************************************************
    */

     function getChargeType($chgID)
    {
        $invoiceType = [
          'RECEIVING',
          'STORAGE',
          'ORD_PROC',
          'OTHER_SERV'  
        ];    
 
        return $invoiceType[$chgID];
    }
    
    /*
    ****************************************************************************
    */

     function getChargeUOM($uom)
    {
        $invoiceUOM = [
                  'CARTON',
                  'VOLUME',
                  'MONTH',
                  'PALLET',
                  'UNIT',
                  'ORDER',
                  'TBD',
                  'LABEL',
                  'CONTAINER',
                  'MONTHLY_LARGE_CARTON',
                  'MONTHLY_MEDIUM_CARTON',
                  'MONTHLY_SMALL_CARTON',
                  'CARTON_CURRENT',
                  'PALLET_CURRENT',
                  'VOLUME_CURRENT',
                  'VOLUME_RAN',
                  'ORDER_CANCEL',
                  'LABOR',
                  'MONTHLY_VOLUME',
                  'MONTHLY_XL_CARTON',
                  'MONTHLY_XXL_CARTON',
                  'PIECES',
                  'MONTHLY_PALLET'
                ];    
        
        return $invoiceUOM[$uom];
    }
    
    /*
    ****************************************************************************
    */

    function update($columnID, &$value, $rowID, $ajaxRequest=FALSE)
    {
        $fieldIDs = array_keys($this->fields);
        $field = getDefault($fieldIDs[$columnID]);
        $fieldInfo = $this->fields[$field];
      
        //Type - UOM
        if ( getDefault($this->fields[$field]['updateType']) ) {
              $typeValues = $this->getChargeType($value);
        }  else if ( getDefault($this->fields[$field]['updateUOM']) ) {
              $typeValues = $this->getChargeUOM($value);
        } else {
              $typeValues = $value;
        }
        
 
        // Get custom ajax error for field if available
        $fieldUpdateError = getDefault($this->fields[$field]['updateError']);
        $updateError = $fieldUpdateError ? $fieldUpdateError : $ajaxRequest;

        $isValidate = $this->validateDataInputUpdate([
            'fieldInfo' => $fieldInfo,
            'value' => $typeValues,
        ]);

        if (! $isValidate) {
            echo $this->errorMsg;
            return FALSE;
        }

        if (getDefault($fieldInfo['isDecimal'])) {
            $newValue = ceil($typeValues * 4) / 4;
        }

        $updateOverwrite = getDefault($fieldInfo['updateOverwrite']);

        $updateField = $overwriteField = getDefault(
                $fieldInfo['update'],
                $field
        );

        $updateFieldSelect = isset($this->fields[$updateField]['select']) ?
                $this->fields[$updateField]['select'] : NULL;

        $updateField = isset($updateFieldSelect) ?
                $updateFieldSelect : $updateField;

        if (! $updateField) {
            return 'Field not found';
        }

        $previous = NULL;
        $queryValue = isset($newValue) ? $newValue : $value;

        $whereClause = $this->primaryKey.' = ?';
       
        $params = [$typeValues,$rowID];
  
        $updateField = $updateOverwrite ? $overwriteField : $updateField;        
             
        $previous = $this->getPreviousValueUpdateHaveGroupBy([
                'updateField' => $updateField,
                'whereClause' => $whereClause,
                'sqlParams' => [$rowID],
            ]);

        if ($previous != $value) {
            $sql = 'UPDATE  ' . $this->table . '
                    SET     ' . $updateField . ' = ?
                    WHERE   ' . $whereClause;

            $this->app->runQuery($sql, $params, $updateError);
        }
   
        if ($previous != $queryValue) {
            \tables\history::addUpdate([
                'model' => $this,
                'field' => $field,
                'rowID' => $rowID,
                'toValue' => $typeValues,
                'fromValue' => $previous,
            ]);
        }

        return TRUE;
    }
    
        
    /*
    ************************************************************************
    */
    
    function customInsert($post)
    {
        $ajaxRequest = TRUE;

        $chg_cd = $post['chg_cd'];
        $chg_cd_des = $post['chg_cd_des'];
        $chg_cd_uom = $post['chg_cd_uom'];
        $chg_cd_type = $post['chg_cd_type'];

        $chargeUOM = new \tables\statuses\chargeUOM($this);
        $chargeCat = new \tables\statuses\chargeCodeType($this);

        $uomSql = '
            SELECT    displayName
            FROM      ' . $chargeUOM->table . '
            WHERE     id = ?';

        $uomResult = $this->app->queryResult($uomSql, [$chg_cd_uom]);

        $catSql = '
            SELECT    displayName
            FROM      ' . $chargeCat->table . ' 
            WHERE     id = ?';

        $catResult = $this->app->queryResult($catSql, [$chg_cd_type]);

        $insertSql = '
            INSERT INTO charge_cd_mstr (
                chg_cd,
                chg_cd_des,
                chg_cd_type,
                chg_cd_uom
            ) VALUES (
                ?, ?, ?, ?
            ) ON DUPLICATE KEY UPDATE
                chg_cd = VALUES(chg_cd),
                chg_cd_uom = VALUES(chg_cd_uom)';

        $param = [
            $chg_cd,
            $chg_cd_des,
            $catResult['displayName'],
            $uomResult['displayName'],

        ];

        $this->app->runQuery($insertSql, $param, $ajaxRequest);
    }

    /*
    ****************************************************************************
    */
    
    function checkVolumeRates($data) 
    {
        $custID = $data['vendorID'];
        $chgID = $data['chgID'];
        $cat = $data['cat'];
        $uom = $data['uom'];
       
        $sql = 'SELECT  cust_id,
                        min_vol,
                        max_vol
                FROM    inv_vol_rates i
                JOIN    charge_cd_mstr ch ON ch.chg_cd_uom = i.uom
                WHERE   chg_cd_id = ? 
                AND     cust_id = ?
                AND     category = ?
                AND     uom = ?
                ';

        $params = [$chgID, $custID, $cat, $uom];
        
        $result = $this->app->queryResults($sql, $params);

        return $result;
    }
    
    /*
    ****************************************************************************
    */
}
