<?php
/**
 * Created by PhpStorm.
 * User: rober
 * Date: 30/03/2016
 * Time: 17:16
 */

namespace tables\shipments;


class carrier extends \tables\_default
{
    public $primaryKey = 'id';

    public $table = '(
                        SELECT "fedex" AS id,
                               "FedEx" AS displayName
                    UNION
                        SELECT "UPS" AS id,
                               "UPS" AS displayName
                    UNION
                        SELECT "LTL" AS id,
                               "LTL" AS displayName
                    UNION
                        SELECT "routing" AS id,
                               "Routing Required" AS displayName
                    UNION
                        SELECT "willcall" AS id,
                               "Will Call/Client Arranged" AS displayName
                    UNION
                        SELECT "specificcarrier" AS id,
                               "Specific Carrier" AS displayName
                    UNION
                        SELECT "shiptolabels" AS id,
                               "Ship To Labels" AS displayName
                    UNION
                        SELECT "ediasn" AS id,
                               "EDI/ASN/UCC128" AS displayName
                    )  acceptTableCustomers';

    /*
    ****************************************************************************
    */
}