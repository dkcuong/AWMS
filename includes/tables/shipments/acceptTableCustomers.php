<?php

namespace tables\shipments;

class acceptTableCustomers extends \tables\_default
{
    public $primaryKey = 'id';

    public $table = '(
                        SELECT "1" AS id,
                               "Yes" AS displayName
                    UNION
                        SELECT "0" AS id,
                               "No" AS displayName
                    )  acceptTableCustomers';

    /*
    ****************************************************************************
    */
}