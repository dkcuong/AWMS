<?php

namespace tables\logs;

class scanInput extends \tables\_default
{
    public $primaryKey = 'l.id';
    
    public $ajaxModel = 'logs\\scanInput';
    
    public $fields = [
        'logTime' => [
            'display' => 'Log Time', 
            'searcherDate' => TRUE,
            'noEdit' => TRUE,
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'noEdit' => TRUE,
        ],        
        'page' => [
            'select' => 'l.pageRequest',
            'display' => 'Page Request',
        ],        
        'data' => [
            'select' => 'CONCAT("<pre>", l.scanInput, "</pre>")',
            'display' => 'Scan Input',
        ],  
        'inputOption' => [
            'select' => 'l.inputOption',
            'display' => 'Type Scan Input',
        ], 
    ];

    public $mainTable = 'logs_scan_input';
    
    public $mainField = 'l.id';
    
    /*
    ****************************************************************************
    */
    
    function table()
    {
        $userDB = $this->app->getDBName('users');
       
        return 'logs_scan_input l
                LEFT JOIN ' . $userDB . '.info u ON u.id = l.userID
            ';
    }
    
    /*
    ****************************************************************************
    */
    
}
