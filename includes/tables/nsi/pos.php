<?php

namespace tables\nsi;

class pos extends \tables\_default
{
    
    public $primaryKey = 'n.id';
    
    public $ajaxModel = 'nsi\pos';
    
    public $fields = [
        'batch' => [
            'display' => 'Batch',
        ],
        'po' => [
            'display' => 'PO',
            'validation' => 'intval',
            'length' => 6,
        ],
        'ra' => [
            'display' => 'RA',
            'validation' => 'intval',
            'length' => 8,
        ],
        'sku' => [
            'display' => 'SKU',
            'validation' => 'intval',
            'length' => 8,
        ],
        'cartons' => [
            'display' => 'Number of Cartons',
            'validation' => 'intval',
            'length' => 3,
        ],
        'pieces' => [
            'display' => 'Number of Pieces',
            'validation' => 'intval',
            'length' => 5,
        ],
        'warehouse' => [
            'display' => 'Warehouse ID',
            'validation' => 'intval',
            'length' => 3,
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'Username',
            'ignore' => TRUE,
            'validation' => 'intval',
        ],
        'setDate' => [
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'ignore' => TRUE,
            'validation' => 'intval',
        ],
    ];    

    public $mainTable = 'nsi_pos';

    /*
    ****************************************************************************
    */
    
    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'nsi_pos n
            LEFT JOIN '.$userDB.'.info u ON n.userID = u.id';
    }

    /*
    ****************************************************************************
    */
    
}