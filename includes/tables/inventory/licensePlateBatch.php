<?php

namespace tables\inventory;

use tables\_default;
use tables\plates;

class licensePlateBatch extends _default
{
    public $primaryKey = 'ca.id';

    public $ajaxModel = 'inventory\licensePlateBatch';

    public $fields = [
        'plate' => [
            'display' => 'LP',
            'select' => 'ca.plate',
            'noEdit' => TRUE
        ],
        'customer' => [
            'display' => 'Customer',
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'noEdit' => TRUE
        ],
        'container' => [
            'select' => 'co.name',
            'display' => 'container',
            'noEdit' => TRUE
        ],
        'rcvnumber' => [
            'display' => 'RcvNumber',
            'select' => 'rcv.id',
            'noEdit' => TRUE
        ],
        'date' => [
            'display' => 'Setdate',
            'select' => 'co.setDate',
            'noEdit' => TRUE
        ],
        'batches'  => [
            'display' => 'Batches',
            'select' => 'IF(COUNT(DISTINCT caa.batchID) > 1, "mulitple", ca.batchID)',
            'noEdit' => TRUE
        ],
        'action' => [
            'select' => 'ca.batchID',
            'display' => 'Action',
            'ignoreSearch' => TRUE
        ]
    ];

    public $where = 'NOT ca.isSplit
        AND         NOT ca.unSplit
        AND         ca.plate is NOT NULL';

    public $groupBy = 'ca.plate, ca.batchID';

    public $table = '	inventory_containers co
                    JOIN receiving_containers rc ON rc.container_num = co.recNum
                    JOIN receivings rcv ON rcv.id = rc.receiving_id
                    JOIN inventory_batches b ON co.recNum = b.recNum
                    JOIN inventory_cartons ca ON b.id = ca.batchID
                    JOIN vendors v ON v.id = co.vendorID
                    JOIN warehouses w ON w.id = v.warehouseID
                    JOIN inventory_cartons caa ON caa.plate = ca.plate';

    public $mainTable = 'inventory_cartons';

    /*
    ****************************************************************************
    */

    function __construct($app = FALSE)
    {
        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */

    function checkPlatesInvalid($plates)
    {
        $result = [
            'sts' => false
        ];

        if (! $plates) {
            return $result;
        }

        $plateModel = new plates($this->app);

        $fields = 'id, batch';
        $platesValid = $plateModel->getAllLicensePlates($plates, $fields);
        $platesInvalid = array_diff($plates, array_keys($platesValid));

        return $platesInvalid;
    }

    /*
    ****************************************************************************
    */
}