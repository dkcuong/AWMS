<?php

namespace tables\statuses;

class enoughInventory extends \tables\statuses
{
    public $primaryKey = 'id';
    
    public $where = 'category = "orderErrors"';
   
    /*
    ****************************************************************************
    */
}