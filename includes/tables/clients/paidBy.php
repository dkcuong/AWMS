<?php

namespace tables\clients;

class paidBy extends \tables\_default
{
    public $primaryKey = 'name';
    
    public $ajaxModel = 'clients\\paidBy';
    
    public $fields = [
        'id' => [            
            'display' => 'ID',
            'noEdit' => TRUE,
        ],
        'name' => [            
            'display' => 'Paid By Name',
            'noEdit' => TRUE,
        ],
    ];
    
    public $table = 'paid_by';
}