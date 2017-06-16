<?php

namespace tables\dashboards;

use common\workOrders;
use \tables\orders;

class shipping extends \tables\_default
{
    public $ajaxModel = 'dashboards\shipping';

    public $primaryKey = 'o.scanordernumber';

    public $fields = [];

    public $smartTVFields = [];

    public $groupBy = 'o.scanordernumber';

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
        $logsOrders = '
            FROM      logs_values lv
            JOIN      logs_orders lo ON lo.id = lv.logID
            ';

        $workOrders = '
            FROM      wo_hdr wh
            JOIN      neworder n ON n.scanordernumber = wh.scn_ord_num
            WHERE     sts != "d"
            ';

        $fieldSql = '
            SELECT    id
            FROM      logs_fields
            WHERE     displayName IN ("statusID", "routedStatusID")
            AND       category = "orders"';

        $fieldResults = $this->app->queryResults($fieldSql);

        $statusFields = array_keys($fieldResults);

        $orderStatuses = '
                SELECT    primeKey,
                          shortName,
                          logTime
                ' . $logsOrders . '
                JOIN      statuses s ON s.id = lv.toValue
                WHERE     lv.fieldID IN (' . implode(', ', $statusFields) . ')
            UNION
                -- wo_hdr table does not have statusID field
                SELECT    n.id AS primeKey,
                          IF(sts = "i",
                             "' . workOrders::STATUS_CHECK_IN . '",
                             "' . workOrders::STATUS_CHECK_OUT . '"
                          ) AS shortName,
                          update_dt AS logTime
                ' . $workOrders;

        $latestLogs = '
            SELECT    primeKey,
                      MAX(logTime) AS logTime
            FROM (
                    SELECT    primeKey,
                              logTime
                    ' . $logsOrders . '
                    WHERE     lv.fieldID IN (' . implode(', ', $statusFields) . ')
                UNION
                    SELECT    n.id AS primeKey,
                              update_dt AS logTime
                    ' . $workOrders . '
            ) lo
            GROUP BY  primeKey
            ';

        return 'neworder o
            LEFT JOIN online_orders oo ON oo.SCAN_SELDAT_ORDER_NUMBER = o.scanordernumber
            JOIN      order_batches b ON b.id = o.order_batch
            JOIN      vendors v ON v.id = b.vendorID
            JOIN      warehouses wa ON v.warehouseID = wa.id
            LEFT JOIN wo_hdr wh ON wh.scn_ord_num = o.scanordernumber
            JOIN      statuses os ON os.id = o.statusID
            LEFT JOIN statuses osr ON osr.id = routedStatusID
            LEFT JOIN inventory_cartons ca ON ca.orderID = o.id
            LEFT JOIN (
                SELECT lo.primeKey,
                       shortName,
                       lo.logTime
                FROM (
                ' . $orderStatuses . '
                ) lo
                JOIN (
                ' . $latestLogs . '
                ) lt ON lt.primeKey = lo.primeKey
                AND       lt.logTime = lo.logTime
            ) lo ON lo.primeKey = o.id
            ';
    }

    /*
    ****************************************************************************
    */

    function fields()
    {
        $this->fields = [
            'vendor' => [
                'select' => 'CONCAT(wa.shortName, "_", v.vendorName)',
                'display' => 'Client',
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            ],
            'first_name' => [
                'display' => 'First Name',
            ],
            'last_name' => [
                'display' => 'Last Name/Customer Name',
            ],
            'clientordernumber' => [
                'display' => 'Client Order Number',
            ],
            'scanordernumber' => [
                'display' => 'Seldat Order Number',
            ],
            'bolNumber' => [
                'select' => 'IF (bolNumber, bolNumber, "[No BOL]")',
                'display' => 'BOL',
            ],
            'startshipdate' => [
                'select' => 'IF (startshipdate IS NULL,
                                 DATE(order_date), startshipdate
                            )',
                'display' => 'Start Date',
                'searcherDate' => TRUE,
            ],
            'canceldate' => [
                'display' => 'Cancel Date',
                'searcherDate' => TRUE,
            ],
            'numberofcarton' => [
                'select' => 'IF (numberofcarton IS NULL,
                                 SUM(product_quantity), numberofcarton
                            )',
                'display' => 'Number of Cartons',
                'groupedFields' => 'product_quantity',
            ],
            'numberofpiece' => [
                'select' => 'IF (numberofpiece IS NULL,
                                 SUM(product_quantity * uom), numberofpiece
                            )',
                'display' => 'Number of Pieces',
                'groupedFields' => 'product_quantity, uom',
            ],
            'NOrushhours' => [
                'select' => 'IF(os.shortName = "' . orders::STATUS_SHIPPED_CHECK_OUT . '",
                                "Complete", IF(NOrushhours > 0, "Rush", "")
                            )',
                'display' => 'Rush',
                'colorStatus' => TRUE,
            ],
            'canceled' => [
                'select' => 'IF(os.shortName = "' . orders::STATUS_CANCELED . '",
                                "Cancelled", "Active"
                            )',
                'display' => 'Cacnelled',
                'colorStatus' => TRUE,
            ],
            'wmco' => [
                'select' => 'IF(os.shortName = "' . orders::STATUS_ENTRY_CHECK_IN . '",
                                "Incomplete", "Complete"
                            )',
                'display' => 'Checked In',
                'colorStatus' => TRUE,
            ],
            'rtco' => [
                'select' => 'IF(osr.id IS NULL OR osr.shortName = "' . orders::STATUS_ROUTING_CHECK_IN . '",
                                "Incomplete", "Complete"
                            )',
                'display' => 'Routing',
                'colorStatus' => TRUE,
            ],
            'pkco' => [
                'select' => 'IF(FIND_IN_SET(os.shortName, "'
                                    . orders::STATUS_ENTRY_CHECK_IN . ','
                                    . orders::STATUS_ENTRY_CHECK_OUT . ','
                                    . orders::STATUS_PICKING_CHECK_IN . '"
                                ),
                                "Incomplete", "Complete"
                            )',
                'display' => 'Picking',
                'colorStatus' => TRUE,
            ],
            'woco' => [
                'select' => 'IF(wh.wo_id IS NULL OR wh.sts = "i",
                                "Incomplete", "Complete"
                            )',
                'display' => 'Work Orders',
                'colorStatus' => TRUE,
            ],
            'opco' => [
                'select' => 'IF(FIND_IN_SET(os.shortName, "'
                                    . orders::STATUS_ENTRY_CHECK_IN . ','
                                    . orders::STATUS_ENTRY_CHECK_OUT . ','
                                    . orders::STATUS_PICKING_CHECK_IN . ','
                                    . orders::STATUS_PICKING_CHECK_OUT . ','
                                    . orders::STATUS_PROCESSING_CHECK_IN . '"
                                ),
                                "Incomplete", "Complete"
                            )',
                'display' => 'Order Processing',
                'colorStatus' => TRUE,
            ],
            'shco' => [
                'select' => 'IF(os.shortName != "' . orders::STATUS_SHIPPED_CHECK_OUT . '",
                                "Incomplete", "Complete"
                            )',
                'display' => 'Shipping',
                'colorStatus' => TRUE,
            ],
            'logDate' => [
                'select' => 'DATE(logTime)',
                'ignoreSearch' => TRUE,
            ],
            'lastStatus' => [
                'select' => 'lo.shortName',
                'ignoreSearch' => TRUE,
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function where()
    {
        $sql = '
            SELECT    n.id
            FROM      neworder n
            JOIN      statuses os ON os.id = n.statusID
            LEFT JOIN statuses hs ON hs.id = n.holdStatusID
            WHERE (
                os.shortName IS NULL
                OR os.shortName != "' . orders::STATUS_SHIPPED_CHECK_OUT . '"
                OR NOT orderShipDate
                OR orderShipDate IS NULL
                -- do not display orders that are shipped more than 1 business day ago
                OR CURDATE() < CASE WEEKDAY(orderShipDate)
                      WHEN 3 THEN DATE_ADD(orderShipDate, INTERVAL +3 DAY)
                      WHEN 4 THEN DATE_ADD(orderShipDate, INTERVAL +3 DAY)
                      WHEN 5 THEN DATE_ADD(orderShipDate, INTERVAL +2 DAY)
                      ELSE DATE_ADD(orderShipDate, INTERVAL +1 DAY)
                END
            )
            AND (hs.shortName IS NULL
                OR hs.shortName != "' . orders::STATUS_ON_HOLD . '"
            )';

        $results = $this->app->queryResults($sql);

        $orderIDs = array_keys($results);

        return '
            o.id IN (' . implode(', ', $orderIDs) . ')
            AND (wh.wo_id IS NULL
                OR wh.sts != "d"
            )';
    }

    /*
    ****************************************************************************
    */

    function smartTVFields()
    {
        $this->fields();

        $this->smartTVFields = $this->fields;

        // precede clientordernumber with order status in order to distinguish
        // whether order date is Check In or Check Out (to be used in smartTVDashboardsView)

        $this->smartTVFields['clientordernumber'] = [
            'select' => 'CONCAT_WS(
                            " ",
                            IF(lo.shortName IS NULL, os.shortName,lo.shortName),
                            clientordernumber
                        )',
            'display' => 'Client Order Number',
        ];

        // precede scanordernumber with order status in order to know whether
        // order date is stated (to be used in smartTVDashboardsView)

        $this->smartTVFields['scanordernumber'] = [
            'select' => 'CONCAT_WS(
                            " ",
                            IF(logTime IS NULL, "XXXX-XX-XX", DATE(logTime)),
                            scanordernumber
                        )',
            'display' => 'Seldat Order Number',
        ];
    }

    /*
    ****************************************************************************
    */

    function getPendingOrdersData($dayDelay)
    {
        $sql = 'SELECT    o.id,
                          v.id AS vendorID,
                          CONCAT_WS(" ", first_name, last_name) AS customerName,
                          v.vendorName AS clientName,
                          scanordernumber,
                          cancelDate
                FROM      '.$this->table.'
                WHERE     os.shortName != "SHCO"
                AND       cancelDate IS NOT NULL
                AND       cancelDate <= NOW() + INTERVAL '.intVal($dayDelay).' DAY
                GROUP BY '.$this->groupBy.'
                ORDER BY  v.id ASC,
                          cancelDate ASC,
                          scanordernumber ASC
                ';

        $result = $this->app->queryResults($sql);

        return $result;
    }

    /*
    ****************************************************************************
    */

}

