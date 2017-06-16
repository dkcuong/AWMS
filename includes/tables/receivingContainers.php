<?php

namespace tables;

class receivingContainers extends \tables\_default
{

    public $primaryKey = 'r.id';

    public $ajaxModel = 'receivingContainers';

    public $fields = [
        'recNum' => [
            'select' => 'ic.recNum',
            'display' => 'Rec Num',
            'noEdit' => TRUE,
        ],
        'containerName' => [
            'select' => 'ic.name',
            'display' => 'Container'
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'noEdit' => TRUE,
        ],
        'status' => [
            'select' => 'IF(tallyStatus, "Received", IF(cartonStatus, "IN", "Received"))',
            'display' => 'Status',
        ]
    ];

    public $groupBy = 'ic.name';

    public $orderBy = 'ic.name DESC';

    public $mainField = 'r.id';

    public $where = '';


    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */


    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'receivings r
                LEFT JOIN receiving_containers rc ON r.id = rc.receiving_id
                LEFT JOIN inventory_containers ic ON ic.recNum = rc.container_num
                LEFT JOIN inventory_batches ib ON ib.recNum = ic.recNum
                LEFT JOIN inventory_cartons ca ON ca.batchID = ib.id
                LEFT JOIN ' . $userDB . '.info u ON u.id = ic.userID
                LEFT JOIN 
                (
                    SELECT  rc.container_num, 
                            rc.receiving_id, 
                            (SELECT     IF(ca.statusID,1,0) 
                             FROM       inventory_batches ib
                             JOIN       inventory_cartons ca ON ca.batchID = ib.id
                             JOIN       statuses  s ON s.id = ca.statusID
                             WHERE      ib.recNum = rc.container_num  
                             AND        category = "inventory"
                             AND        shortName = "IN"
                             GROUP BY   ib.recNum
                            ) AS cartonStatus,
                            (SELECT     locked 
                            FROM        inventory_batches ib
                            JOIN        tallies t ON t.recNum = ib.recNum
                            WHERE       ib.recNum = rc.container_num
                            GROUP BY    ib.recNum
                            ) AS tallyStatus
                    FROM receiving_containers rc
                )AS rco ON  rco.container_num = rc.container_num';
    }

    /*
    ****************************************************************************
    */
}