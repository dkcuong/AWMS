<?php
/**
 * Created by PhpStorm.
 * User: Mr Le
 * Date: 5/3/2017
 * Time: 11:36 AM
 */

namespace tables\statuses;

use tables\_default;

class requestChangeStatus extends _default
{
    public $primaryKey = 'id';

    public $fields = [
        'id' => [
            'display' => 'ID'
        ],
        'displayName' => [
            'display' => 'Status'
        ]

    ];

    public $table = '(
                        SELECT "P" AS id,
                               "Pending" AS displayName
                    UNION
                        SELECT "A" AS id,
                               "Approved" AS displayName
                    UNION
                        SELECT "D" AS id,
                               "Declined" AS displayName
                    ) cycleCount';

    /*
    ****************************************************************************
    */
}