<?php
/**
 * Created by PhpStorm.
 * User: rober
 * Date: 15/02/2016
 * Time: 23:19
 */

namespace tables;


class billOfLadingLabels extends _default
{
    static $labelTitle = 'BOL Label';

    static $labelsTitle = 'BOL Labels';

    public $ajaxModel = 'billOfLadingLabels';

    public $fields = [
        'barcode' => [
            'select' => 'LPAD(
                            CONCAT(userID, o.assignNumber),
                            10, 0
                        )',
            'display' => 'BOL Label',
        ],
        'dateEntered' => [
            'select' => 'DATE(dateEntered)',
            'display' => 'Date Entered',
        ],
        'batch' => [
            'display' => 'Label Batch',
        ],
        'username' => [
            'select' => 'u.username',
            'display' => 'Username',
        ],
    ];

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'billofladings o
               LEFT JOIN '.$userDB.'.info u ON u.id = userID';
    }


    /*
    ****************************************************************************
    */

    function insert($userID, $quantity)
    {
        return \common\labelMaker::inserts([
            'model' => $this,
            'userID' => $userID,
            'quantity' => $quantity,
            'labelType' => 'bill',
        ]);
    }
}