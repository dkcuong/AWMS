<?php

namespace tables;

class clientEmails extends _default
{
    public $displaySingle = 'Client Emails';

    public $ajaxModel = 'clientEmails';
        
    public $primaryKey = 'ce.id';
    
    public $fields = [
        'vendorName' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'update' => 'ce.vendorID',
            'updateOverwrite' => TRUE,
        ],
        'receivingConfirmation' => [
            'select' => 'IF(receivingConfirmation, "Yes", "No")',
            'display' => 'Receiving Confirmation',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'updateOverwrite' => TRUE,
        ],
        'bolConfirmation' => [
            'select' => 'IF(bolConfirmation, "Yes", "No")',
            'display' => 'Bill of Lading Confirmation',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'updateOverwrite' => TRUE,
        ],        
        'email' => [
            'select' => 'ce.email',
            'display' => 'Email',
        ],
        'bolEmail' => [
            'select' => 'IF(bolEmail, "Yes", "No")',
            'display' => 'Is BOL Email',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'updateOverwrite' => TRUE,
        ],
        'active' => [
            'select' => 'IF(ce.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'ce.active',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $table = 'client_emails ce
        JOIN      vendors v ON v.id = ce.vendorID
        JOIN      warehouses w ON v.warehouseID = w.id
        ';
        
    public $insertTable = 'client_emails';
    
    public $dropdownWhere = 'active';
    
    public $mainField = 'ce.id';

    public $customInsert = 'clientEmails';
    
    /*
    ****************************************************************************
    */

    function customInsert($post)
    {
        $vendorID = $post['vendorName'];
        $receivingConfirmation = $post['receivingConfirmation'];
        $bolConfirmation = $post['bolConfirmation'];
        $email = $post['email'];
        $bolEmail = $post['bolEmail'];
        $active = $post['active'];
        
        $sql = 'INSERT INTO client_emails (
                    vendorID, 
                    receivingConfirmation, 
                    bolConfirmation, 
                    email, 
                    bolEmail, 
                    active
                ) VALUES (
                    ?, ?, ?,
                    ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    receivingConfirmation = ?, 
                    bolConfirmation = ?, 
                    bolEmail = ?, 
                    active = ?
                ';

        $ajaxRequest = TRUE;

        $param = [
            $vendorID, 
            $receivingConfirmation, 
            $bolConfirmation, 
            $email, 
            $bolEmail,
            $active,
            $receivingConfirmation, 
            $bolConfirmation, 
            $email, 
            $active,
        ];
        
        $this->app->runQuery($sql, $param, $ajaxRequest);
    }
    
    /*
    ****************************************************************************
    */  
}