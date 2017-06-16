<?php

namespace tables\nsi;

class receiving extends \tables\_default
{
    
    public $primaryKey = 'p.id';
    
    public $ajaxModel = 'nsi\receiving';
    
    public $fields = [
        'po' => [
            'display' => 'PO Number',
            'validation' => 'intval',
            'length' => 6,
        ],
        'ra' => [
            'display' => 'RA Number',
            'validation' => 'intval',
            'length' => 8,
        ],
        'palletNumber' => [
            'display' => 'Pallet Quantity',
            'validation' => 'intval',
            'length' => 4,
        ],
        'pallet' => [
            'select' => 'p.id',
            'display' => 'Pallet Number',
            'ignore' => TRUE,
            'validation' => 'intval',
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'Username',
            'ignore' => TRUE,
            'validation' => 'intval',
        ],
        'setDate' => [
            'display' => 'Set Date',
            'ignore' => TRUE,
            'searcherDate' => TRUE,
            'validation' => 'intval',
            'orderBy' => 'p.id',
        ],
    ];    

    public $mainTable = 'nsi_receiving';
    
    /*
    ****************************************************************************
    */
    
    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'nsi_receiving r
            LEFT JOIN '.$userDB.'.info u ON r.userID = u.id
            LEFT JOIN nsi_receiving_pallets p ON p.receivingID = r.id';
    }
    
    /*
    ****************************************************************************
    */
    
}