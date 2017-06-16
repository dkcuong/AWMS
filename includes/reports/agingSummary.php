<?php

namespace reports;

use PHPExcel;
use charts\model;
use charts\jpgraph;

use PHPExcel_Style_Fill as fillStyle;
use tables\orders;

class agingSummary
{

    static $logs = [];

    static $invWhData = [];
    static $invClData = [];

    static $ordWhData = [];
    static $ordClData = [];

    static $whDays3 = [];
    static $whDays6 = [];
    static $whDays9 = [];
    static $whDays = [];

    static $clDays3 = [];
    static $clDays6 = [];
    static $clDays9 = [];
    static $clDays = [];

    static $model = NULL;

    public $customerAbbrv;

    public $whName = [];

    public $customers = [];

    /*
    ****************************************************************************
    */

    static function init($app=NULL)
    {
        $self = new static();
        $self->app = $app;
        return $self;
    }

    /*
    ****************************************************************************
    */

    function inventorySummary($warehouse)
    {
        $totalTime = \summary\model::init()->timer();

        $this->customerAbbrv = self::$invWhData = self::$invClData = [];

        //warehouse summary cartons
        $warehouseSql =  '
                ' . $this->cartonSum($warehouse) . '
               UNION
                ' . $this->cartonReservedSum($warehouse) . '
               ORDER BY displayName';

        self::$invWhData =  $this->app->queryResults($warehouseSql);

        //client summary cartons
        $clientSql =  '
                ' . $this->cartonSum($warehouse, 'client') . '
               UNION
                ' . $this->cartonReservedSum($warehouse, 'client') . '
               ORDER BY vendorName';

        self::$invClData =  $this->app->queryResults($clientSql);

        $list = array_column(self::$invClData, 'vendorName');
        $customerNames = array_values(array_unique($list));

        $cust = $this->getName();

        foreach ($customerNames as $value) {
            $name = trim($value);

            $this->customerAbbrv[$name]['name'] = $name;
            $this->customerAbbrv[$name]['abbrv'] = $cust[$name];
        }

        if (! self::$invWhData && ! self::$invClData) {

            return self::$logs[] = 'No carton report emails to be sent'
                                 . ' for ' . $warehouse;
        }

        $this->reportMail($warehouse, 'Carton');

        $totalTime->timer();

        return self::$logs;
    }

    /*
    ****************************************************************************
    */

    function cartonSum($warehouse, $type='warehouse')
    {
       //get the statusID
        $status = new \tables\statuses\inventory($this->app);

        $stsName = [
            \tables\inventory\cartons::STATUS_RECEIVED,
            \tables\inventory\cartons::STATUS_ORDER_PROCESSING,
            \tables\inventory\cartons::STATUS_SHIPPING,
            \tables\inventory\cartons::STATUS_DISCREPANCY
        ];

        $stsResult = $status->getStatusIDs($stsName);
        $statusID = array_column($stsResult, 'id');

        $mstsName = \tables\inventory\cartons::STATUS_RESERVED;

        $mstsResult = $status->getStatusIDs($mstsName);
        $mStatusID = array_column($mstsResult, 'id');

        //get the cartons
        $sql = $this->selectCartonQuery($type);

        $groupBy = $type === 'client' ? 'GROUP BY   cust_id, w.displayName, status_id'
                               : 'GROUP BY  w.displayName, status_id';

        $status = '(' . implode("," , $statusID) . ')';

        $mStatus = implode("," , $mStatusID);

        $invSql = '
             ' . $sql . '
            JOIN         statuses s ON s.id = sc.status_id
            WHERE        status_id IN ' . $status . '
            AND          mStatus_id <> ' . $mStatus . '
            AND          w.displayName = "' . $warehouse . '"
            ' . $groupBy . '
            ';

        return $invSql;
    }

    /*
    ****************************************************************************
    */

    function cartonReservedSum($warehouse, $type='warehouse')
    {
        //get the statusID
        $status = new \tables\statuses\inventory($this->app);

        $mstsName = \tables\inventory\cartons::STATUS_RESERVED;

        $mstsResult = $status->getStatusIDs($mstsName);

        $mStatusID = array_column($mstsResult, 'id');

        //get the cartons
        $sql = $this->selectCartonQuery($type);

        $groupBy = $type === 'client' ? 'GROUP BY   cust_id, w.displayName, mStatus_id'
                               : 'GROUP BY w.displayName, mStatus_id';

        $mStatus = implode("," , $mStatusID);

        $invSql = '
             ' . $sql . '
            JOIN        statuses s ON s.id = sc.mStatus_id
            WHERE       mStatus_id = ' . $mStatus . '
            AND         w.displayName =  "' . $warehouse . '"
            ' . $groupBy . '
            ';

        return $invSql;
    }

    /*
    ****************************************************************************
    */

    function selectCartonQuery($type)
    {
        $select = $type === 'warehouse' ?
                         'CONCAT(w.displayName, "_", s.shortName) AS displayName'
                        : 'CONCAT(cust_id, "_", s.shortName) AS displayName,
                           vendorName';
        $sql = '
            SELECT
                   ' . $select . ',
                    w.displayName AS warehouse,
                    s.displayName AS status,
                    SUM(IF(DATEDIFF(CURDATE(), last_update_time) <=30, 1, 0)) AS 30Days,
                    SUM(IF(DATEDIFF(CURDATE(), last_update_time) > 30 AND
                            DATEDIFF(CURDATE(), last_update_time) <=60, 1, 0)) AS 60Days,
                    SUM(IF(DATEDIFF(CURDATE(), last_update_time) > 60 AND
                            DATEDIFF(CURDATE(), last_update_time) <=90, 1, 0)) AS 90Days,
                    SUM(IF(DATEDIFF(CURDATE(), last_update_time) > 90, 1, 0)) AS Over90Days
            FROM    sum_last_ctn_sts sc
            JOIN    vendors v ON v.id = sc.cust_id
            JOIN    warehouses w ON w.id = v.warehouseID
            ';

        return $sql;
    }

    /*
    ****************************************************************************
    */


    function orderSummary($warehouse)
    {
        $totalTime = \summary\model::init()->timer();

        $this->customerAbbrv = self::$ordWhData = self::$ordClData = [];

        //get the statusID
        $statusObj = new \tables\statuses\orders($this->app);

        $stsName = orders::getProcessedOrdersStatuses('orderSummary');

        $stsResult = $statusObj->getStatusIDs($stsName);

        $statusID = array_column($stsResult, 'id');

        //get fieldID - category "orders"
        $logs = new \logs\fields($this->app);
        $lFieldID = $logs->getFieldID('orders');

        //get history field
        $hFieldID = \summary\model::init($this->app)->getHistoryFieldID();

        $params = [
            'statusID' => $statusID,
            'lFieldID' => $lFieldID,
            'hFieldID' => $hFieldID
        ];

        //warehouse summary orders
        $warehouseSql =  '
                    ' . $this->getOrder($warehouse, $params) . '
                    ORDER BY displayName';

        self::$ordWhData =  $this->app->queryResults($warehouseSql);

        //client summary orders
        $clientSql =  '
                ' . $this->getOrder($warehouse, $params, 'client') . '
                ORDER BY displayName';

        self::$ordClData =  $this->app->queryResults($clientSql);

        $list = array_column(self::$ordClData, 'vendorName');
        $customerNames = array_values(array_unique($list));

        $cust = $this->getName();

        foreach ($customerNames as $value) {
            $name = trim($value);

            $this->customerAbbrv[$name]['name'] = $name;
            $this->customerAbbrv[$name]['abbrv'] = $cust[$name];
        }


        if (! self::$ordWhData && ! self::$ordClData) {
            return self::$logs[] = 'No order report emails to be sent'
                                 . ' for ' . $warehouse;
        }

        $this->reportMail($warehouse, 'Order');

        $totalTime->timer();

        return self::$logs;
    }

    /*
    ****************************************************************************
    */

    function getOrder($warehouse, $params, $type='warehouse')
    {
        $select = $type === 'warehouse' ?
                'CONCAT(w.displayName, "_", s.shortName) AS displayName' :
                'CONCAT(vendorID, "_", s.shortName) AS displayName, vendorName';

        $groupBy = $type === 'client' ?
                ' vendorID, w.displayName, statusID' : ' w.displayName, statusID';

        $status = '(' . implode("," , $params['statusID']) . ')';


        //get the order data
        $orderSql = '
                SELECT
                            ' . $select . ',
                            w.displayName AS warehouse,
                            s.displayName AS status,
                            ' . self::orderSumDiff() . '
                FROM        neworder n
                LEFT JOIN   (
                            SELECT   primeKey,
                                     MAX(logID) AS logID
                            FROM 	 logs_values
                            WHERE	 fieldID = ' . $params['lFieldID'] . '
                            AND      toValue IN ' . $status . '
                            GROUP BY primeKey DESC
                    ) lv ON lv.primeKey = n.id
		LEFT JOIN (
                            SELECT   rowID,
                                     MAX(id) AS hisID,
                                     actionTime
                            FROM     history
                            WHERE    fieldID = ' . $params['hFieldID'] . '
                            AND      toValueID IN ' . $status . '
                            GROUP BY rowID DESC
                )h ON h.rowID = n.id
                LEFT JOIN   logs_orders lo ON lo.id = lv.logID
                JOIN        order_batches b ON b.id = n.order_batch
                JOIN        vendors v ON v.id = b.vendorID
                JOIN        warehouses w ON w.id = v.warehouseID
                JOIN        statuses s ON s.id = n.statusID
                WHERE       statusID IN ' . $status . '
                AND         w.displayName =  "' . $warehouse . '"
                GROUP BY    ' . $groupBy . '
                ';

        return $orderSql;
    }

    /*
    ****************************************************************************
    */

    static function orderSumDiff()
    {
        return '
            SUM(
                IF(DATEDIFF(CURDATE(),
                            IF(IFNULL(logTime,0) >  IFNULL(actionTime,0), logTime, actionTime)
                           ) <=30, 1, 0
                )
            ) AS 30Days,

            SUM(
                IF(DATEDIFF(CURDATE(),
                            IF(IFNULL(logTime,0) > IFNULL(actionTime,0), logTime, actionTime)
                            ) > 30 AND
                    DATEDIFF(CURDATE(),
                            IF(IFNULL(logTime,0) >= IFNULL(actionTime,0), logTime, actionTime)
                            ) <=60, 1, 0
                )
            ) AS 60Days,

            SUM(
                IF(DATEDIFF(CURDATE(),
                            IF(IFNULL(logTime,0) > IFNULL(actionTime,0), logTime, actionTime)
                            ) > 60 AND
                    DATEDIFF(CURDATE(),
                            IF(IFNULL(logTime,0) > IFNULL(actionTime,0), logTime, actionTime)
                            ) <=90, 1, 0
                )
            ) AS 90Days,

            SUM(
                IF(DATEDIFF(CURDATE(),
                            IF(IFNULL(logTime,0) > IFNULL(actionTime,0), logTime, actionTime)
                            ) > 90, 1, 0
                )
            ) AS Over90Days
        ';
    }


    /*
    ****************************************************************************
    */

    function sendReport($warehouse, $type)
    {
        $warehouseObj = new \tables\warehouses($this->app);
        $results = $warehouseObj->getWarehouse();

        $displayName = array_column($results, 'displayName');
        $shortName = array_column($results, 'shortName');

        $this->whName = array_combine($displayName, $shortName);

        $name = $this->whName[$warehouse] . $type . 'Report';

        $warehouseData = $type == 'Carton' ? self::$invWhData :
                self::$ordWhData;

        $clientData = $type == 'Carton' ? self::$invClData :
                self::$ordClData;

        if (! $warehouseData && ! $clientData) {
            return NULL;
        }

        $fieldKeys = [
                ['title' => 'WAREHOUSE'],
                ['title' => $type .' STATUS'],
                ['title' => '30 DAYS'],
                ['title' => '60 DAYS'],
                ['title' => '90 DAYS'],
                ['title' => 'OVER 90 DAYS'],
        ];

        $warehouseTitles = array_column($fieldKeys, 'title');

        array_unshift($fieldKeys, ['title' => 'CUSTOMER']);

        $clientTitles = array_column($fieldKeys, 'title');

        $abFieldKeys = [
                ['title' => 'Customer'],
                ['title' => 'Abbreviation'],
        ];

        $abbrvTitles = array_column($abFieldKeys, 'title');

        //excel
        $model = new model($this);

        $model->objPHPExcel = new PHPExcel();

        $model->objPHPExcel->getDefaultStyle()
                ->getNumberFormat()
                ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);

        //Add client chart to excel sheet
        $this->calcDays($type);
        $clParams = $this->clientParams($model, $name, $type, $warehouse);
        $model->chartToExcel($clParams, $warehouse . ' CUSTOMER CHART');

        //Add client abbreviation
        array_unshift($this->customerAbbrv, $abbrvTitles);
        $model->objPHPExcel->createSheet()->setTitle('ABBREVIATION')
                ->fromArray($this->customerAbbrv, NULL, 'A1');


        //Add warehouse carton report
        array_unshift($warehouseData, $warehouseTitles);
        $model->objPHPExcel->createSheet()
                ->setTitle($warehouse . ' WAREHOUSE SUMMARY')
                ->fromArray($warehouseData, NULL, 'A1');

         //Add client carton report
        array_unshift($clientData , $clientTitles);
        $model->objPHPExcel->createSheet()
                ->setTitle($warehouse . ' CUSTOMER DETAIL')
                ->fromArray($clientData, NULL, 'A1');

        //formatting
        $sheetsFormatting  = [
                1 => [
                    'freezePane' => 'B2',
                    'getStyle' => 'A1:B1',
                    'fill' => [
                        'type' => fillStyle::FILL_SOLID,
                        'color' => ['rgb' => 'B0E0E6']
                    ]
                ],
                2 => [
                    'freezePane' => 'F2',
                    'getStyle' => 'A1:F1',
                    'fill' => [
                        'type' => fillStyle::FILL_SOLID,
                        'color' => ['rgb' => 'FFDEAD']
                     ]
                ],
                3 => [
                    'freezePane' => 'G2',
                    'getStyle' => 'A1:G1',
                    'fill' => [
                        'type' => fillStyle::FILL_SOLID,
                        'color' => ['rgb' => '98FB98']
                     ]
                ],
            ];

         foreach ($sheetsFormatting as $key => $row) {
            $model->objPHPExcel->getSheet($key)
                    ->freezePane($row['freezePane'])
                    ->getStyle($row['getStyle'])
                    ->applyFromArray([
                          'fill' => $row['fill']
                      ])
                    ->getFont()
                    ->setSize(15)
                    ->setBold(true);
         }

        //set default font size and AutoSize
        for($i=1;$i <= count($sheetsFormatting);$i++) {
            $range = $i == 1 ? range('A', 'B') : range('A', 'G');

            $model->objPHPExcel->getSheet($i)
                    ->getDefaultStyle()
                    ->getFont()
                    ->setSize(15);

            foreach($range as $columnID) {
             $model->objPHPExcel->getSheet($i)
                    ->getColumnDimension($columnID)
                    ->setAutoSize(true);
            }
        }

        //save to local
        $objWriter = \PHPExcel_IOFactory::createWriter($model->objPHPExcel, 'Excel2007');
        $path = \models\directories::getDir('uploads', $name);

        $file = $path . '/' .  $name . '.xlsx';
        $objWriter->save($file);

        return $file;
    }

    /*
    ****************************************************************************
    */

    function clientSumChartImage($params)
    {
        $report = $params['report'];

        if ($report === '30Days' && self::$clDays3) {
            jpgraph::setData($params['model'], self::$clDays3)->myChart($params);
        } elseif ($report === '60Days' && self::$clDays6) {
            jpgraph::setData($params['model'], self::$clDays6)->myChart($params);
        } elseif ($report === '90Days' && self::$clDays9) {
            jpgraph::setData($params['model'], self::$clDays9)->myChart($params);
        } elseif ($report === 'Over90Days' && self::$clDays) {
            jpgraph::setData($params['model'], self::$clDays)->myChart($params);
        }
    }

    /*
    ****************************************************************************
    */

    function calcDays($column)
    {
        $days3 = $days6 = $days9 = $days = [];

        $clData = $column == 'Carton' ? self::$invClData : self::$ordClData;

        //30 days, 60 days, 90 days, over 90 days
        foreach ($clData as $key => $row)  {

            $name = trim($row['vendorName']);

            if ($row['30Days']) {
                $days3[$key]['name'] = $this->customers[$name];
                $days3[$key]['status'] = $row['status'];
                $days3[$key]['qty'] = $row['30Days'];
            }

            if ($row['60Days']) {
                $days6[$key]['name'] = $this->customers[$name];
                $days6[$key]['status'] = $row['status'];
                $days6[$key]['qty'] = $row['60Days'];
            }

            if ($row['90Days']) {
                $days9[$key]['name'] = $this->customers[$name];
                $days9[$key]['status'] = $row['status'];
                $days9[$key]['qty'] = $row['90Days'];
            }

            if ($row['Over90Days'])  {
                $days[$key]['name'] = $this->customers[$name];
                $days[$key]['status'] = $row['status'];
                $days[$key]['qty'] = $row['Over90Days'];
            }
        }

            self::$clDays3 = $days3;
            self::$clDays6 = $days6;
            self::$clDays9 = $days9;
            self::$clDays = $days;
    }

    /*
    ****************************************************************************
    */

    function clientParams($model, $name, $col, $warehouse)
    {
        $params = [];

        $days30 = $days60 = $days90 = FALSE;

        $column = $col === 'BILLING' ? 'NOT BILLING' : $col;


        if (self::$clDays3) {

            $days30 = TRUE;

            $xPos = 'A';

            $value = $this->getGraphMeasure(self::$clDays3);

            $selectParams = [
                'model' => $model,
                'name' => $name,
                'column' => $column,
                'warehouse' => $warehouse,
                'report' => '30Days',
                'title' => ' Status Shows In  0  TO  30 Days',
                'height'  => $value['height'],
                'width'  => $value['width'],
                'excelHeight' => $value['excelHeight'],
                'xPos' => $xPos,
                'yPos' => 1
            ];

            $params[] = $this->getDaysParam($selectParams);
        }

        if (self::$clDays6) {

            $days60 = TRUE;

            $xPos = $days30 ? 'K' : 'A';

            $value = $this->getGraphMeasure(self::$clDays6);

            $selectParams = [
                'model' => $model,
                'name' => $name,
                'column' => $column,
                'warehouse' => $warehouse,
                'report' => '60Days',
                'title' => ' Status Shows In  30  TO  60 Days',
                'height'  => $value['height'],
                'width'  => $value['width'],
                'excelHeight' => $value['excelHeight'],
                'xPos' => $xPos,
                'yPos' => 1
            ];

            $params[] = $this->getDaysParam($selectParams);
        }

        if (self::$clDays9) {

            $days90 = TRUE;
            $yPos = 1;

            $value = $this->getGraphMeasure(self::$clDays9);

            if ($days30 && $days60) {
                $xPos = 'A';
                $yPos = 40;
            } elseif ($days30 && ! $days60
                        || ! $days30 && $days60) {
                $xPos = 'K';
            } elseif (! $days30 && ! $days60) {
                $xPos = 'A';
            }

            $selectParams = [
                'model' => $model,
                'name' => $name,
                'column' => $column,
                'report' => '90Days',
                'warehouse' => $warehouse,
                'title' => ' Status Shows In  60  TO  90 Days',
                'height'  => $value['height'],
                'width'  => $value['width'],
                'excelHeight' => $value['excelHeight'],
                'xPos' => $xPos,
                'yPos' => $yPos
            ];

            $params[] = $this->getDaysParam($selectParams);
        }

        if (self::$clDays) {

            $yPos = 1;

            $value = $this->getGraphMeasure(self::$clDays);

            if ($days30 && $days60 && $days90) {
                $yPos = 40;
                $xPos = 'K';
            } elseif ($days30 && $days60 && ! $days90
                        || $days30 && ! $days60 && $days90
                        || ! $days30 && $days60 && $days90) {
                $yPos = 40;
                $xPos = 'A';
            } elseif (! $days30 && $days60 && ! $days90
                         || $days30 && ! $days60 && ! $days90
                         || ! $days30 && ! $days60 && $days90) {
                $xPos = 'K';
            } elseif (! $days30 && ! $days60 && ! $days90) {
                $xPos = 'A';
            }

            $selectParams = [
                'model' => $model,
                'name' => $name,
                'column' => $column,
                'warehouse' => $warehouse,
                'report' => 'Over90Days',
                'title' => ' Status Shows In  Over  90  Days',
                'height'  => $value['height'],
                'width'  => $value['width'],
                'excelHeight' => $value['excelHeight'],
                'xPos' => $xPos,
                'yPos' => $yPos
            ];

            $params[] = $this->getDaysParam($selectParams);
        }

        return [
            'excelFile' => 'Aging_Report',
            'imageDir' => 'reportImages',
            'chartImages' => $params
        ];
    }

    /*
    ****************************************************************************
    */

    function reportMail($warehouse, $name)
    {
        $file = $this->sendReport($warehouse, $name);

        if (! $file) {
            return;
        }

        $text  = $warehouse . ' ' . $name . ' Aging Report';

        //send the report to email
        $userDB = \dbInfo::getDBName('users');

        $param = $this->whName[$warehouse] . 'GroupAdmin';

        $sql = 'SELECT  email
                FROM    groups g
                JOIN    user_groups ug ON ug.groupID = g.id
                JOIN    ' . $userDB . '.info u ON u.id = ug.userID
                WHERE   hiddenName = ?
                AND     ug.active
                AND     g.active
                AND     u.active';

        $result = $this->app->queryResults($sql, [$param]);

        $receipients = array_keys($result);

        \PHPMailer\send::mail([
            'recipient' => $receipients,
            'subject' => $text,
            'body' => $text,
            'files' => [$file],
        ]);
    }

    /*
    ****************************************************************************
    */

    function getDaysParam($params)
    {
        return [
            'filename' => 'verticalbar',
            'type' => 'verticalbar',
            'xPos' => $params['xPos'],
            'yPos' => $params['yPos'],
            'excelWidth' => 600,
            'excelHeight' => $params['excelHeight'],

            'uploads'  => $params['name'],
            'report'  => $params['report'],
            'model'  => $params['model'],

            'width' => $params['width'],
            'height' => $params['height'],
            'yField' => 'qty',
            'xField' => 'name',
            'groupField' => 'status',
            'theme' => 'default',
            'offSetHieght' => -40,
            'title' => $params['warehouse'] . ' -  ' . $params['column'] .
                        $params['title'],
            'xTitle' => 'Customer',
            'yTitle' => 'Carton Count',
            'auto' => TRUE,

            'makeImage' => [
                'method' => [$this, 'clientSumChartImage'],
                'params' => [
                  'type' => $params['column']
              ],
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function getGraphMeasure($data)
    {
        $list = array_column($data, 'name');
        $uniqueClients = array_values(array_unique($list));

        //graph width
        if (count($uniqueClients) <= 10) {
            $height = 500;
            $width = 600;
            $excelHeight = 600;
        } elseif (count($uniqueClients) > 10 &&
                    count($uniqueClients) <= 17) {
            $height = 600;
            $width = 650;
            $excelHeight = 600;
        } else {
            $height = 700;
            $width = 750;
            $excelHeight = 750;
        }

        return [
            'height' => $height,
            'width' => $width,
            'excelHeight' => $excelHeight
        ];
    }

    /*
    ****************************************************************************
    */


    function getName()
    {
        $vendorObj = new \tables\vendors($this->app);
        $results = $vendorObj->getVendorNames();

        foreach ($results as $key => $value) {
            $name = trim($key);
            $this->customers[$name] = $value['clientCode'];
        }

        return $this->customers;
    }

    /*
    ****************************************************************************
    */
}
