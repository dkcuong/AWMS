<?php
/**
 * Created by PhpStorm.
 * User: rober
 * Date: 30/03/2016
 * Time: 17:10
 */

namespace tables\shipments;


class attachBillOfLading extends \tables\_default
{
    public $primaryKey = 'id';

    public $table = '(
                        SELECT "1" AS id,
                               "Yes" AS displayName
                    UNION
                        SELECT "0" AS id,
                               "No" AS displayName
                    )  attachBillOfLading';

    /*
    ****************************************************************************
    */
}