<?php

namespace tables\cycleCount;


use tables\statuses;

class cycleCountStatus extends statuses
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
                        SELECT "OP" AS id,
                               "Open" AS displayName
                    UNION
                        SELECT "AS" AS id,
                               "Assigned" AS displayName
                    )
                     UNION
                        SELECT "CC" AS id,
                               "Cycled" AS displayName
                    )
                    UNION
                        SELECT "RC" AS id,
                               "Recount" AS displayName
                    )
                    UNION
                        SELECT "LC" AS id,
                               "Completed" AS displayName
                    ) cycleCountStatus';

    /*
    ****************************************************************************
    */
}