<?php
/**
 * Created by PhpStorm.
 * User: rober
 * Date: 30/03/2016
 * Time: 17:00
 */

namespace tables\shipments;


class commodity extends \tables\_default
{
    public $primaryKey = 'id';

    public $fields = [
        'description' => [],
    ];

    public $table = 'commodity';

    /*
    ****************************************************************************
    */
}