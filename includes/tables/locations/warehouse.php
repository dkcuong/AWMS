<?php

namespace tables\locations;

class warehouse extends adminWarehouse
{
    public $ajaxModel = 'locations\\warehouse';
    
    public $table = 'locations l
           JOIN      warehouses w ON w.id = warehouseID';
    
    /*
    ****************************************************************************
    */

    public function __construct($app = FALSE) 
    {
        parent::__construct($app);
        
        unset($this->fields['volume']);
        unset($this->fields['weight']);
    }
    
    /*
    ****************************************************************************
    */
}
