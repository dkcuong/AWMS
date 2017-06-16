<?php

namespace tables\statuses;

class openOrders extends \tables\statuses
{

    public $primaryKey = 'id';

    public $fields = [
        'shortName' => [],
    ];

    public $where = 'category = "openOrders"';

    /*
    ****************************************************************************
    */
}