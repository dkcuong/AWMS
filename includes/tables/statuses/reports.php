<?php

namespace tables\statuses;

class reports extends \tables\statuses
{
    
    public $primaryKey = 'id';
    
    public $fields = [
        'shortName' => [],
    ];
    
    public $where = 'category = "reports"';
    
    /*
    ****************************************************************************
    */
    
    function getMezzanineTransfersStauses()
    {
        $sql = 'SELECT    shortName,
                          id
                FROM      statuses
                WHERE     ' . $this->where . '
                AND       shortName IN ("METR", "METU")';

        $results = $this->app->queryResults($sql);

        $return = [];
        
        foreach ($results as $key => $value) {
            $return[$key] = $value['id'];
        }
        
        return $return;
    }
    
    /*
    ****************************************************************************
    */
}