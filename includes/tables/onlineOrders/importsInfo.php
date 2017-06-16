<?php

namespace tables\onlineOrders;

class importsInfo
{
    public $inserting = FALSE;
    
    public $values = [
        'upcsInfo' => [],
        'uomsInfo' => [],
        'transferID' => 0,
    ];
    
    /*
    ****************************************************************************
    */

    function insertMode()
    {
        $this->inserting = TRUE;
    }
    
    /*
    ****************************************************************************
    */

    function get($name, $callback=FALSE, $params=[])
    {
        $error = 'Undeclared order info value name: '.$name;
        isset($this->values[$name]) or die($error);

        if ($this->values[$name] && $callback) {
            die('Trying to reset a value');
            backtrace();
        }
        
        // Once insertions begin ther should be no more values stored
        if (! $this->inserting && ! $this->values[$name]) {
            $this->values[$name] = call_user_func_array($callback, $params);
        }
        
        return $this->values[$name];      
    }
    
}