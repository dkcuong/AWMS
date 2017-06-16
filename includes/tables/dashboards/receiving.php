<?php

namespace tables\dashboards;

use tables\statuses\inventory;
use tables\inventory\cartons;

class receiving extends \tables\_default
{
    public $ajaxModel = 'dashboards\receiving';

    public $primaryKey = 'b.id';

    public $fields = [];

    public $smartVTFields = [];

    public $groupBy = '
        batchID,
        IF (startshipdate IS NULL, order_date, startshipdate)
    ';

    /*
    ****************************************************************************
    */

    function __construct($app = FALSE)
    {
        \common\vendor::addConditionByVendor($app, $this);

        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */

    function table()
    {
        $statuses = new inventory($this->app);

        $statusNames = [
            cartons::STATUS_INACTIVE,
            cartons::STATUS_RECEIVED,
            cartons::STATUS_RACKED,
            cartons::STATUS_PICKED,
            cartons::STATUS_ORDER_PROCESSING,
            cartons::STATUS_SHIPPING,
            cartons::STATUS_SHIPPED,
        ];

        $statusIDs = $statuses->getStatusIDs($statusNames);

        foreach ($statusNames as $statusName) {
            $cartonStatuses[$statusName] = $statusIDs[$statusName]['id'];
        }

        $reportStatuses = [
            'iin' => cartons::STATUS_INACTIVE,
            'rc' => cartons::STATUS_RECEIVED,
            'rk' => cartons::STATUS_RACKED,
        ];

        // Get each required status for target status to be completed
        $statusSelects = [];
        foreach ($reportStatuses as $field => $reportStatus) {

            $statusSelects[$reportStatus] = '
                IF (
                    cl.status_id IN (' . implode(',', $cartonStatuses) . '),
                    "Complete", "Incomplete"
                ) AS ' .  $field;

            array_shift($cartonStatuses);
        }

        return '(
			SELECT    batch_id AS batchID,
    				  order_id,
                      COUNT(carton_id) AS cartonCount,
                      SUM(uom) AS totalPieces,
                      ' . implode(', ', $statusSelects) . '
            FROM      ctn_log_sum cl
	        WHERE     cl.status_id != "' . $statusIDs[cartons::STATUS_RACKED]['id'] . '"
            OR        last_active IS NULL
            -- do not display orders that are shipped more than 1 business day ago
            OR        CURDATE() < CASE WEEKDAY(last_active)
                          WHEN 3 THEN DATE_ADD(last_active, INTERVAL +3 DAY)
                          WHEN 4 THEN DATE_ADD(last_active, INTERVAL +3 DAY)
                          WHEN 5 THEN DATE_ADD(last_active, INTERVAL +2 DAY)
                          ELSE DATE_ADD(last_active, INTERVAL +1 DAY)
                      END
            GROUP BY  batch_id,
    				  order_id,
                      cl.status_id
        ) ca
        JOIN      inventory_batches b ON b.id = ca.batchID
        JOIN      inventory_containers co ON co.recNum = b.recNum
        JOIN      vendors v ON v.id = co.vendorID
        JOIN      warehouses w ON v.warehouseID = w.id
        JOIN      upcs u ON u.id = b.upcID
        LEFT JOIN neworder o ON ca.order_id = o.id
        LEFT JOIN online_orders oo ON oo.SCAN_SELDAT_ORDER_NUMBER = o.scanordernumber
        ';
    }

    /*
    ****************************************************************************
    */

    function fields()
    {
        $this->fields = [
            'vendor' => [
                'select' => 'CONCAT(w.shortName, "_", vendorName)',
                'display' => 'Client',
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            ],
            'recNum' => [
                'select' => 'b.recNum',
                'display' => 'Receiving Number',
            ],
            'name' => [
                'display' => 'Container',
            ],
            'batchID' => [
                'display' => 'Batch ID',
            ],
            'sku' => [
                'display' => 'Style Number',
            ],
            'startshipdate' => [
                'select' => '
                    IF (startshipdate IS NULL, order_date, startshipdate)
                ',
                'display' => 'Shipping Date',
                'searcherDate' => TRUE,
            ],
            'setDate' => [
                'select' => 'DATE(setDate)',
                'display' => 'Date',
                'searcherDate' => TRUE,
           ],
            'initialCount' => [
                'display' => 'Initial Cartons',
            ],
            'totalPieces' => [
                'display' => 'Total Pieces',
            ],
            'volume' => [
                'select' => 'CAST(
                                CEIL(
                                    (height * length * width) / 1728 * 4
                                ) / 4 AS DECIMAL(4, 2)
                            )',
                'display' => 'Volume',
            ],
            'cartonCount' => [
                'display' => 'Total Cartons',
            ],
            'iin' => [
                'display' => 'In Transit',
                'colorStatus' => TRUE,
            ],
            'rc' => [
                'display' => 'Received',
                'colorStatus' => TRUE,
            ],
            'rk' => [
                'display' => 'Racked',
                'colorStatus' => TRUE,
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function smartTVFields()
    {
        $this->fields();

        $this->smartTVFields = $this->fields;
    }

    /*
    ****************************************************************************
    */

}

