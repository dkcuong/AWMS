<?php

namespace tables\customer;

class customerContact extends  \tables\_default
{
    public $ajaxModel = 'customer\\customerContact';

    public $primaryKey = 'cust_ctc_id';

    public $displaySingle = 'Contact'; 
   
    public $fields = [
        'dlt' => [
            'select' => 'cust_ctc_id',
            'display' => 'DEL',
            'insertDefaultValue' => TRUE,
        ],
        'ctc_dft' => [
            'display' => 'DFT',
            'insertDefaultValue' => TRUE,
        ],
        'ctc_dept' => [
            'select' => 'ctc_dept',
            'display' => 'DEPT',
        ],
        'ctc_nm' => [
            'select' => 'ctc_nm',
            'display' => 'NAME',
        ],
        'ctc_ph' => [
            'select' => 'ctc_ph',
            'display' => 'PHONE',
        ],
        'ctc_eml' => [
            'select' => 'ctc_eml',
            'display' => 'EMAIL',
        ]
    ];

    public $hiddenInsertFields = ['cust_id'];

    public $table = 'customer_ctc cc';
    public $insertTable = 'customer_ctc';
    
    public $where = 'status != "d"';

    /*
    ****************************************************************************
    */

    function insertContact($data)
    {
        $errors = [];

        $fields = array_keys($data);
        $values = array_values($data);

        $mandatoryFields = [
            'cust_id',
            'ctc_dept',
            'ctc_nm',
            'ctc_dft',
            'ctc_ph',
            'ctc_eml',
        ];

        $missingFields = array_diff($mandatoryFields, $fields);

        if ($missingFields) {
            $errors[] = 'Missing fields: ' . implode(', ', $missingFields);
        }

        $extraFields = array_diff($fields, $mandatoryFields);

        if ($extraFields) {
            $errors[] = 'Extra fields: ' . implode(', ', $extraFields);
        }

        if ($errors) {
            return [
                'errors' => $errors,
            ];
        }

        $emptyInput = $this->checkEmptyInput($data, $fields);
        
        if ($emptyInput) {
            return [
                'errors' => $emptyInput,
            ];
        }

        $results = \common\auditing::getData($fields, $values, 'insert');

        $fieldList = $results['fieldList'];
        $valueList = $results['valueList'];

        $sql = 'INSERT INTO customer_ctc
                SET    ' . implode(', ', $fieldList);

        $this->app->runQuery($sql, $valueList);
    }

    /*
    ****************************************************************************
    */

    function updateContact($data, $primeKey)
    {
        $fields = array_keys($data);
        $values = array_values($data);

        $emptyInput = $this->checkEmptyInput($data, $fields);
        
        if ($emptyInput) {
            return [
                'errors' => $emptyInput,
            ];
        }

        $results = \common\auditing::getData($fields, $values, 'update');

        $fieldList = $results['fieldList'];
        $valueList = $results['valueList'];

        $sql = 'UPDATE customer_ctc
                SET    ' . implode(', ', $fieldList) . '
                WHERE  cust_ctc_id = ?';
        
        $valueList[] = $primeKey;
        
        $this->app->runQuery($sql, $valueList);

        return [];
    }

    /*
    ****************************************************************************
    */

    function checkEmptyInput($data, $fields)
    {
        $filtered = array_filter($data);

        $filteredKeys = array_keys($filtered);

        $emptyFields = array_diff($fields, $filteredKeys);

        if ($emptyFields) {
            return 'Empty input: ' . implode(', ', $emptyFields);
        }
    }

    /*
    ****************************************************************************
    */
}
