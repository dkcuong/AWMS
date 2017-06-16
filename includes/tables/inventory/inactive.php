<?php

namespace tables\inventory;

class inactive extends \tables\inventory\cartons
{    
    public $ajaxModel = 'inventory\\inactive';
    
    public $where = 'NOT isSplit
                     AND NOT unSplit
                     AND s.category = "inventory"
                     AND s.shortName = "IN"
                     AND (locID IS NULL
                        OR NOT l.isShipping
                    )';
    
    public $displaySingle = 'Inactive Carton';

    /*
    ****************************************************************************
    */
    
}
