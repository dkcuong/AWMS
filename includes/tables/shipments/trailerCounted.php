<?php

namespace tables\shipments;

class trailerCounted extends \tables\_default
{
    public $primaryKey = 'id';

    public $table = '(
                        SELECT "trailercountedbyshipper" AS id,
                               "By Shipper" AS displayName
                    UNION
                        SELECT "trailercountedbydriverpallets" AS id,
                               "By Driver/Pallets" AS displayName
                    UNION
                        SELECT "trailercountedbydriverpieces" AS id,
                               "By Driver/Pieces" AS displayName
                    )  trailerCounted';

    /*
    ****************************************************************************
    */
}