<?php

namespace tables\inventory;

class measure extends \tables\_default
{
    public $primaryKey = 'id';
    
    public $ajaxModel = 'inventory\\measure';
    
    public $fields = [
        'id' => TRUE,
        'displayName' => TRUE,
    ];

    public $table = 'measurement_systems';
        
    /*
    ****************************************************************************
    */
    
    function getMesaurements()
    {
        $return = [];
        
        $sql = 'SELECT   displayName,
                         ' . $this->primaryKey . '                        
                FROM     ' . $this->table;
        
        $results = $this->app->queryResults($sql);
        
        foreach ($results as $key => $values) {
            $return[$key] = $values['id'];
        }

        return $return;
    }
    
    /*
    ****************************************************************************
    */
    
}
