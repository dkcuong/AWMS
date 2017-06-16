<?php
namespace tables\logs;

class scanInputQuery
{
    private $app = '';
    private $table = 'logs_scan_input';


    public function __construct($app)
    {
        $this->app = $app;
    }
    
    /*
    ****************************************************************************
    */
    
    public function insertScanInput($params) 
    {
        $sql = 'INSERT INTO ' . $this->table . ' (
                    userID, 
                    pageRequest, 
                    scanInput,
                    InputOption
                ) 
                VALUES (?, ?, ?, ?)';
        
        $result = $this->app->runQuery($sql, [
            $params['userID'], 
            $params['pageRequest'], 
            $params['scanInput'],
            $params['inputOption'],
        ]);
        
        return $result;
    }
    
    /*
    ****************************************************************************
    */
}
