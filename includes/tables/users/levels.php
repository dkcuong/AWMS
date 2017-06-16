<?php

namespace tables\users;

class levels extends \tables\users
{
    public $ajaxModel = 'users\\levels';
    
    public $primaryKey = 'l.id';
    
    public $fields = [
        'id' => [],
        'displayName' => [],
        'level' => [],
    ];
    
    /*
    ****************************************************************************
    */

    function table()
    {
        return 'user_levels l';
    }
    
    /*
    ****************************************************************************
    */
    
    function getLevels()
    {
        $sql = 'SELECT    id,
                          displayName
                FROM      user_levels
                ORDER BY  level DESC';
        
        $results = $this->app->queryResults($sql);

        return $results;
    }    
}