<?php

namespace tables\shipments;

class trailerLoad extends \tables\_default
{
    public $primaryKey = 'id';

    public $table = '(
                        SELECT "trailerloadbyshipper" AS id,
                               "By Shipper" AS displayName
                    UNION
                        SELECT "trailerloadbydriver" AS id,
                               "By Driver" AS displayName
                    )  trailerLoad';

    /*
    ****************************************************************************
    */
}