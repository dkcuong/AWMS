<?php

namespace tables\shipments;

class feeTerms extends \tables\_default
{
    public $primaryKey = 'id';

    public $table = '(
                        SELECT "feetermbycollect" AS id,
                               "Collect" AS displayName
                    UNION
                        SELECT "feetermbyprepaid" AS id,
                               "Prepaid" AS displayName
                    )  feeTerms';

    /*
    ****************************************************************************
    */
}