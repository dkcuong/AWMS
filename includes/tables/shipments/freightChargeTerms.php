<?php

namespace tables\shipments;

class freightChargeTerms extends \tables\_default
{
    public $primaryKey = 'id';

    public $table = '(
                        SELECT "freightchargetermbycollect" AS id,
                               "Collect" AS displayName
                    UNION
                        SELECT "freightchargetermbyprepaid" AS id,
                               "Prepaid" AS displayName
                    UNION
                        SELECT "freightchargetermby3rdparty" AS id,
                               "3rdParty" AS displayName
                    )  freightChargeTerms';

    /*
    ****************************************************************************
    */
}