<?php

namespace tables\orders;

use excel\exporter;
use models\config;
use models\directories;

class ShippedDailyReport
{
    protected $emailsReceive = [
        'Heather.ventola@seldatinc.com',
        'christopher.lee@seldatinc.com',
        'edi@golifeworks.com'
    ];

    protected $titleRow = 7;
    protected $columnsReport;

    protected $directories;
    protected $filePath;
    protected $app;
    protected $dataOrder;
    protected $styleTitles = [
        'font'  => [
            'bold'  => true,
            'size'  => 10,
            'name'  => 'ARIAL'
        ],
        'alignment' => [
            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
        ]
    ];

    protected $styleValue = [
        'font'  => [
           'size'  => 10,
        ],
        'alignment' => [
            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
        ]
    ];

    protected $styleBold = [
        'font'  => [
            'size'  => 10,
            'bold' => true,
            'name'  => 'ARIAL'
        ],
        'alignment' => [
            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
        ]
    ];

    protected  $styleReportTitle = [
        'font'  => [
            'size'  => 16,
            'bold' => true,
            'name'  => 'ARIAL'
        ],
        'alignment' => [
            'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        ]
    ];

    /*
    ****************************************************************************
    */

    public function __construct($app)
    {
        $this->columnsReport = [
            [
                'title' => 'PO#',
                'field' => 'poNum',
                'from' => [
                    'col' => 'A',
                    'row' => $this->titleRow
                ],
                'to' => [
                    'col' => 'C',
                    'row' => $this->titleRow
                ]

            ],
            [
                'title' => 'Sales Order #',
                'field' => 'salesOrderNum',
                'from' => [
                    'col' => 'D',
                    'row' => $this->titleRow
                ],
                'to' => [
                    'col' => 'E',
                    'row' => $this->titleRow
                ]
            ],
            [
                'title' => 'Customer ID',
                'field' => 'customer',
                'from' => [
                    'col' => 'F',
                    'row' => $this->titleRow
                ],
                'to' => [
                    'col' => 'I',
                    'row' => $this->titleRow
                ]
            ],
            [
                'title' => 'Carrier',
                'field' => 'carrierName',
                'from' => [
                    'col' => 'J',
                    'row' => $this->titleRow
                ],
                'to' => [
                    'col' => 'L',
                    'row' => $this->titleRow
                ]
            ],
            [
                'title' => 'Tracking # / Pro #',
                'field' => 'bolNumber',
                'from' => [
                    'col' => 'M',
                    'row' => $this->titleRow
                ],
                'to' => [
                    'col' => 'O',
                    'row' => $this->titleRow
                ]
            ],
            [
                'title' => 'Shipped Date',
                'field' => 'shippedDate',
                'from' => [
                    'col' => 'P',
                    'row' => $this->titleRow
                ],
                'to' => [
                    'col' => 'Q',
                    'row' => $this->titleRow
                ]
            ]
        ];

        $this->today = date('Y-m-d');
        $this->directories = directories::getDir('uploads', 'orderReport');
        $this->app = $app;
    }

    /*
    ****************************************************************************
    */

    public function getShippedOrderByTodayForClient($clientCode)
    {
        $result = [];

        if (! $clientCode) {
            return $result;
        }

        $sql = 'SELECT  
                        n.scanordernumber,
                        CONCAT(w.shortName, "-", w.displayName) AS warehouse,
                        CONCAT(w.shortName, "-", vendorName) AS vendor,
                        ob.vendorID,
                        IF (LENGTH(first_name) > 0, 
                              CONCAT(first_name, "-", last_name),
                              last_name
                            ) AS customer,
                        n.clientordernumber AS salesOrderNum,
                        n.customerordernumber AS poNum,
                        si.carrierName,
                        IF (LENGTH(si.trackingNumber) > 0, si.trackingNumber, 
                            IF (LENGTH(si.proNumber) > 0, si.proNumber, si.bolID)
                            ) AS bolNumber,
                        si.bolID,
                        DATE(logTime) AS shippedDate,
                        n.id,
                        n.scanordernumber,
                        n.statusID AS orderStatus
                FROM 	neworder n
                JOIN    order_batches ob ON ob.id = n.order_batch
                JOIN    vendors v ON v.id = ob.vendorID
                JOIN    warehouses w ON w.id = v.warehouseID
                JOIN    logs_values lv ON lv.primeKey = n.id
                JOIN    logs_orders o ON o.id = lv.logID	
                JOIN    logs_fields lf ON lf.id = lv.fieldID
                JOIN    statuses s ON s.id = n.statusID
                JOIN    statuses st ON st.id = lv.toValue
                LEFT JOIN    shipping_orders so on so.orderID = n.ID
                LEFT JOIN    shipping_info si on si.bolLabel = so.bolID
                WHERE   lf.category = \'orders\'
                and     lf.displayName = \'statusID\'
                AND     st.shortName = \'SHCO\'
                AND     s.shortName = \'SHCO\'
                AND     DATE(logTime) = IF(WEEKDAY(CURDATE()) > 0,
                        DATE_SUB(CURDATE(), INTERVAL 1 DAY), 
                        DATE_SUB(CURDATE(), INTERVAL 3 DAY))
                AND     v.clientCode = ?';

        $result = $this->app->queryResults($sql, [$clientCode]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    protected function createShippedOrderReqportFile($dataOrder, $fileName)
    {
        $result = NULL;

        if (! $fileName) {
            return $result;
        }

        $sheetIndex = 0;
        $this->filePath = $this->directories . DIRECTORY_SEPARATOR . $fileName;

        $objPHPExcel = new \PHPExcel();
        $sheetIndex = 0;
        $lengthSheet = count($dataOrder);
        $objPHPExcel->getDefaultStyle()->getNumberFormat()
            ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);

        foreach ($dataOrder as $key => $data) {
            $objPHPExcel->setActiveSheetIndex($sheetIndex);

            $objPHPExcel->getActiveSheet()->mergeCells('A1:Q2')->getCell('A1')
                ->setValue('Vendor Shipping Report');

            $objPHPExcel->getActiveSheet()->getStyle('A1')
                ->applyFromArray($this->styleReportTitle);

            $objPHPExcel->getActiveSheet()->mergeCells('A3:C3')->getCell('A3')
                ->setValue('WHSE:');
            $objPHPExcel->getActiveSheet()->getStyle('A3')->applyFromArray($this->styleBold);

            $objPHPExcel->getActiveSheet()->mergeCells('D3:G3')->getCell('D3')
                ->setValue($key);
            $objPHPExcel->getActiveSheet()->getStyle('D3')->applyFromArray($this->styleBold);

            $objPHPExcel->getActiveSheet()->mergeCells('A4:C4')->getCell('A4')
                ->setValue('VENDOR:');
            $objPHPExcel->getActiveSheet()->getStyle('A4')->applyFromArray($this->styleBold);

            $firstRow = reset($data);
            $objPHPExcel->getActiveSheet()->mergeCells('D4:G4')->getCell('D4')
                ->setValue(isset($firstRow['vendor']) ? $firstRow['vendor'] : '');
            $objPHPExcel->getActiveSheet()->getStyle('D4')->applyFromArray($this->styleBold);

            $objPHPExcel->getActiveSheet()->mergeCells('A5:C5')->getCell('A5')
                ->setValue('DATES:');
            $objPHPExcel->getActiveSheet()->getStyle('A5')->applyFromArray($this->styleBold);

            $dates = date('Y-m-d 00:00') . ' to ' . date('Y-m-d 23:00');
            $objPHPExcel->getActiveSheet()->mergeCells('D5:M5')->getCell('D5')
                ->setValue($dates);
            $objPHPExcel->getActiveSheet()->getStyle('D5')->applyFromArray($this->styleBold);

            //create title report
            foreach ($this->columnsReport as $column) {
                $fromCell = $column['from']['col'].$column['from']['row'];
                $toCell = $column['to']['col'].$column['to']['row'];

                $currentCel = $fromCell . ':' . $toCell;
                $objPHPExcel->getActiveSheet()->mergeCells($currentCel)
                    ->getCell($fromCell)->setValueExplicit(
                        $column['title'], \PHPExcel_Cell_DataType::TYPE_STRING);
            }

            $objPHPExcel->getActiveSheet()->getStyle("A$this->titleRow:Q$this->titleRow")
                ->applyFromArray($this->styleTitles);

            $currentRow = $this->titleRow + 1;

            foreach ($data as $value) {

                foreach ($this->columnsReport as $column) {
                    $fromCell = $column['from']['col'] . $currentRow;
                    $toCell = $column['to']['col'] . $currentRow;

                    $currentCel = $fromCell . ':' . $toCell;
                    $objPHPExcel->getActiveSheet()
                        ->mergeCells($currentCel)
                        ->getCell($fromCell)
                        ->setValue($value[$column['field']])
                        ->getStyle($currentCel)
                        ->applyFromArray($this->styleValue);
                }

                $currentRow++;
            }

            $objPHPExcel->getActiveSheet()->setTitle("$key");

            if ($sheetIndex + 1 < $lengthSheet) {
                $objPHPExcel->createSheet();
                $sheetIndex++;
            }
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($this->filePath);

        return $this->filePath;
    }

    /*
    ****************************************************************************
    */

    protected function sendEmailShippedReport()
    {
        $subject = '['. $this->today .
            '] - Email End of Day Report - Shipped Orders';

        $body = 'There are no data for report';
        $files = [];

        if ($this->dataOrder) {
            $body = 'This is shipped orders daily report at ' .
                $this->today . '.<br/> Please review the attach file.';
            $files = [
                $this->filePath
            ];
        }

        foreach ($this->emailsReceive as $email) {
            \PHPMailer\send::mail( [
                'recipient' => $email,
                'subject' => $subject,
                'body' => $body,
                'files' => $files
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    protected function reformatDataReport($data)
    {
        $result = [];

        if (! $data) {
            return $result;
        }

        foreach ($data as $order) {
            $result[$order['warehouse']][] = $order;
        }

        return $result;
    }

}