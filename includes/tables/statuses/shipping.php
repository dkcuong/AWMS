<?php

namespace tables\statuses;

class shipping extends \tables\statuses
{

    public $primaryKey = 'id';

    public $fields = [
        'shortName' => [],
    ];

    public $where = 'category = "shipping"';

    /*
    ****************************************************************************
    */
}