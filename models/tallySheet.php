<?php

class model extends base 
{   
    public $checkboxes = [
        'complete',
        'incomplete',
        'workOrder',
        'workOrderProcessing',
        'processing',
        'ready'
    ];
    
    public $checks = [];
    
    /*
    ****************************************************************************
    */
        function getNextTallyID()
    {
        $sql = 'SELECT  AUTO_INCREMENT as nextID
                FROM    information_schema.tables
                WHERE   table_name = "tally_sheets"';

        $result = $this->queryResult($sql);

        return $result['nextID'];        
    }
    
}