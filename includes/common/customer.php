<?php

namespace common;


class customer 
{
    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        $this->app = $app;
    }

    /*
    ****************************************************************************
    */
  
    function insertCustomerMaster($data)
    {
        $errors = [];

        $fields = array_keys($data);
        $values = array_values($data);
        
        $custFields = [
            'cust_cd', 'cust_type', 'cust_nm', 
            'bill_to_add1', 'bill_to_state', 'bill_to_city', 
            'bill_to_cnty', 'bill_to_zip', 'bill_to_contact',
            'ship_to_add1', 'ship_to_state',
            'ship_to_city', 'ship_to_zip', 'ship_to_cnty',
            'net_terms'
        ];
        
        $missingFields = array_diff($custFields, $fields);

        if ($missingFields) {
            $errors[] = 'Missing fields: ' . implode(', ', $missingFields);
        }

        $extraFields = array_diff($fields, $custFields);

        if ($extraFields) {
            $errors[] = 'Extra fields: ' . implode(', ', $extraFields);
        }

        if ($errors) {
            return [
                'errors' => $errors,
            ];
        }

        $obj = new \tables\customer\customerContact($this);
        
        $emptyInput = $obj->checkEmptyInput($data, $fields);
        
        if ($emptyInput) {
            return [
                'errors' => $emptyInput,
            ];
        }

        $results = \common\auditing::getData($fields, $values, 'insert');

        $fieldList = $results['fieldList'];
        $valueList = $results['valueList'];

        
        //insert into customer_mstr table
        
        $sql = 'INSERT INTO customer_mstr
                SET    ' . implode(', ', $fieldList);
        
        $this->app->runQuery($sql, $valueList);
    }
    
    /*
    ****************************************************************************
    */
    
    
    function updateCustomerMaster($params, $vendorID, $displayTo)
    {
        $errors = [];

        
        $billTo = $shipTo = $data = [];
        
        parse_str($params['billTo'], $billTo);
        parse_str($params['shipTo'], $shipTo);
        
        $data = array_merge($billTo, $shipTo); 
       
        $custFields = [
            'cust_cd', 'cust_type', 'cust_nm', 
            'bill_to_add1', 'bill_to_state', 'bill_to_city', 
            'bill_to_cnty', 'bill_to_zip', 'net_terms',
            'ship_to_add1', 'ship_to_state',
            'ship_to_city', 'ship_to_zip', 'ship_to_cnty'
        ];
    
        if ($displayTo== 'invoice') {
            $custFields = [
                'cust_nm', 'bill_to_add1', 'bill_to_state', 'bill_to_city', 
                'bill_to_cnty', 'bill_to_zip', 
                'ship_to_add1', 'ship_to_state',
                'ship_to_city', 'ship_to_zip', 'ship_to_cnty'
            ];
        }

        $fields = array_keys($data);
        $values = array_values($data);  
        

        $missingFields = array_diff($custFields, $fields);

        if ($missingFields) {
            $errors[] = 'Missing fields: ' . implode(', ', $missingFields);
        }

        $extraFields = array_diff($fields, $custFields);

        if ($extraFields) {
            $errors[] = 'Extra fields: ' . implode(', ', $extraFields);
        }

        if ($errors) {
            return [
                'errors' => $errors,
            ];
        }
        
        $obj = new \tables\customer\customerContact($this);
        
        $emptyInput = $obj->checkEmptyInput($data, $fields);
       
        if ($emptyInput) {
            return [
                'errors' => $emptyInput,
            ];
        }

        $results = \common\auditing::getData($fields, $values, 'update');

        $fieldList = $results['fieldList'];
        $valueList = $results['valueList'];
        
        $valueList[] = $vendorID;

        //update to customer_mstr table
        $sql = 'UPDATE  customer_mstr
                SET    ' . implode(', ', $fieldList) . '
                WHERE   cust_id = ?';

        $this->app->runQuery($sql, $valueList);
        
        return TRUE;
    }
    
    
    /*
    ****************************************************************************
    */
    
    function getBillTo($vendor) 
    {
       $sql = 'SELECT 
                        cm.cust_id,
                        cust_cd,
                        cust_type,
                        vendorName,
                        IF(bill_to_add2, CONCAT(bill_to_add1," ",bill_to_add2), bill_to_add1)
                        AS bill_to_add,
                        bill_to_city,
                        bill_to_state,
                        bill_to_cnty,
                        bill_to_zip,
                        net_terms,
                        cust_ctc_id,
                        ctc_ph,
                        ctc_nm
                FROM    customer_mstr cm 
                LEFT JOIN   (
                            SELECT 
                                    cc.cust_id,
                                    cust_ctc_id,
                                    ctc_ph,
                                    ctc_nm
                            FROM    customer_ctc cc 
                            WHERE   ctc_dft 
                            AND     cc.status != "d"    
                )t  ON   t.cust_id = cm.cust_id
                JOIN    vendors v ON v.id = cm.cust_id
                WHERE   cm.status != "d"
                AND     cm.cust_id = ?';
        
        $billResults = $this->app->queryResult($sql,[$vendor]);
   
        return $billResults ? $billResults : [];
    }
    
    /*
    ****************************************************************************
    */
    
    function getShipTo($vendor) 
    {
        $sql = 'SELECT 
                        cm.cust_id,
                        vendorName,
                        IF(ship_to_add2, CONCAT(ship_to_add1," ",ship_to_add2), ship_to_add1)
                        AS ship_to_add,
                        ship_to_city,
                        ship_to_state,
                        ship_to_cnty,
                        ship_to_zip
                FROM    customer_mstr cm 
                JOIN    vendors v ON v.id = cm.cust_id
                WHERE   cm.cust_id = ?';
        
        $shipResults = $this->app->queryResult($sql,[$vendor]);
        
        return $shipResults ? $shipResults : [];
    }
    
    /*
    ****************************************************************************
    */
    
}
