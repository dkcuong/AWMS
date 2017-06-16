<?php

namespace reports;
use tables\inventory\cartons;
use tables\locations;
use tables\statuses\inventory;

class receiving extends \tables\containersReceived
{
    public $db;

    public $info = [];

    public $logs = [];

    protected $emails = [
        'wesley.cooper@seldatinc.com'
    ];

    protected $hour = 23;
    protected $amoutDays = 2;
    protected $txtNoData = 'No Data';
    protected $userDB;
    protected $statusIds;
    protected $cartonModel;

    /*
    ****************************************************************************
    */

    public function __construct($app)
    {
        parent::__construct($app);

        $statuses = new inventory($this->app);

        $this->statusIds = $statuses->getStatusIDs([
            cartons::STATUS_RACKED,
            cartons::STATUS_RECEIVED
        ]);

        $this->userDB = $this->app->getDBName('users');
        $this->cartonModel = new cartons($app);
    }

    /*
    ****************************************************************************
    */

    static function processCreateReceivingReport($app)
    {
        $self = new static($app);

        $isValidateDateTime = $self->isValidateDateTimeRun();

        if (! $isValidateDateTime) {
            return [];
        }

        $dataReport = $self->processGetDataReport();

        $self->processSendingMail($dataReport);

        return [];
    }

    /*
    ****************************************************************************
    */

    function processGetDataReport() {

        $pendingContainer = $this->getPendingContainerData();
        $receivingDocsLoc = $this->getReceivingDocsLocData();
        $backToStockLoc = $this->getBackToStockLocData();
        $haveData = $pendingContainer || $receivingDocsLoc || $backToStockLoc;

        return [
            'haveData' => $haveData,
            'data' => [
                'pendingContainer' => $pendingContainer,
                'receivingDocsLoc' => $receivingDocsLoc,
                'backToStockLoc' => $backToStockLoc
            ]
        ];
    }

    /*
    ****************************************************************************
    */

    function processSendingMail($data) {

        $subject = '['. date('Y-m-d') . '] - Receiving Container Daily Report';
        $body = $data['haveData'] ? 'Daily receiving container report.' :
            'No data for report';

        $params = [
            'recipient' => $this->emails,
            'subject' => $subject,
            'body' => $body
        ];

        if (! $data['haveData']) {
            return \PHPMailer\send::mail($params);
        }

        $fileReport = $this->createFileReport($data['data']);

        $params['files'] = [$fileReport];

        return \PHPMailer\send::mail($params);
    }

    /*
    ****************************************************************************
    */

    function getPendingContainerData()
    {
        $sql = 'SELECT 	co.recNum,
                        w.displayName AS warehouse,
                        CONCAT(w.shortName,"_",v.vendorName) AS vendorName,
                        co.`name`,
                        co.setDate AS setDate,
                        u.username AS userID,
                        COUNT(DISTINCT sku) AS skuCount,
                        DATEDIFF(NOW(), DATE(co.setDate)) AS daysOld
                FROM    inventory_batches ib
                JOIN    inventory_containers co ON co.recNum = ib.recNum
                JOIN    vendors v ON v.id = co.vendorID
                JOIN    warehouses w ON w.id = v.warehouseID
                JOIN    upcs p ON p.id = ib.upcID
                LEFT JOIN tallies t ON t.recNum = co.recNum
                LEFT JOIN ' . $this->userDB . '.info u ON u.id = co.userID
                WHERE   t.recNum IS NULL
                AND	    DATEDIFF(NOW(), DATE(co.setDate)) > ?
                GROUP BY co.recNum
        ';

        $results = $this->app->queryResults($sql, [
            $this->amoutDays
        ]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getReceivingDocsLocData()
    {
        $racked = $this->statusIds[cartons::STATUS_RACKED]['id'];

        $sql = 'SELECT 	  co.recNum,
                          w.displayName AS warehouse,
                          CONCAT(w.shortName,"_",v.vendorName) AS vendorName,
                          co.`name`,
                          t.setDate AS setDate,
                          u.username AS userID,
                          COUNT(DISTINCT sku) AS skuCount,
                          DATEDIFF(NOW(), DATE(t.setDate)) AS daysOld
                FROM      inventory_cartons ca
                JOIN      inventory_batches ib ON ca.batchID = ib.id
                JOIN      inventory_containers co ON co.recNum = ib.recNum
                JOIN      locations l ON  l.id = ca.locID
                JOIN      vendors v ON v.id = co.vendorID
                JOIN      warehouses w ON w.id = v.warehouseID
                JOIN      upcs p ON p.id = ib.upcID
                LEFT JOIN tallies t ON t.recNum = co.recNum
                LEFT JOIN ' . $this->userDB . '.info u ON u.id = co.userID
                WHERE     DATEDIFF(NOW(), DATE(t.setDate)) > ?
                AND       ca.statusID = ca.mStatusID
                AND       ca.statusID = ' . $racked . '
                AND       l.displayName LIKE "%_REC_%"
                GROUP BY co.recNum
                ORDER BY co.`name`
        ';

        $results = $this->app->queryResults($sql, [
            $this->amoutDays
        ]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    private function getBackToStockLocData()
    {
        //add space for export number to cell
        $this->cartonModel->fields['ucc128']['select'] =
            'CONCAT(" ",
                co.vendorID,
                b.id,
                LPAD(ca.uom, 3, 0),
                LPAD(ca.cartonID, 4, 0)
            )';

        $selectField = $this->cartonModel->getSelectFields();

        $from = $this->cartonModel->getTable();

        $where =  'NOT isSplit AND NOT unSplit AND l.displayName = ?';

        $sql = 'SELECT ca.id,' . $selectField .
            ' FROM '. $from .
            ' WHERE '. $where;

        $result = $this->app->queryResults($sql, [
            locations::NAME_LOCATION_BACK_TO_STOCK
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    private function createFileReport($data)
    {
        $sheetIndex = 0;
        $objPHPExcel = new \PHPExcel();
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

        foreach ($data as $key => $values) {

            $objPHPExcel->setActiveSheetIndex($sheetIndex);
            $sheetTitle = $this->getSheetTitle($key);
            $this->setDataForExcelFileByType([
                'sheetTitle' => $sheetTitle,
                'data' => $values,
                'type' => $key,
                'objPHPExcel' => &$objPHPExcel,
                'objWriter' => &$objWriter
            ]);

            $objPHPExcel->createSheet();
            $sheetIndex++;
        }

        $uploadPath =
            \models\directories::getDir('uploads', 'receivingContainerReport');
        $fileName = '[' . date('Y-m-d') .'] - Daily Receiving Container Report';

        $file = $uploadPath . DIRECTORY_SEPARATOR . $fileName . '.xlsx';

        $objWriter->save($file);

        return $file;
    }

    /*
    ****************************************************************************
    */

    private function getSheetTitle($key)
    {
        $result = '';
        switch ($key) {
            case 'pendingContainer':
                $result = 'Pending Container';
                break;
            case 'receivingDocsLoc':
                $result = 'Receiving Docs Location';
                break;
            case 'backToStockLoc':
                $result = 'Back To Stock Location';
                break;
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    private function setDataForExcelFileByType($params)
    {
        $sheetTitle = &$params['sheetTitle'];
        $data = $params['data'];
        $type = $params['type'];
        $objPHPExcel = &$params['objPHPExcel'];

        if ($data) {

            $fieldKeys = $type == 'backToStockLoc' ?
                    array_column($this->cartonModel->fields, 'display') :
                    [
                        'Warehouse',
                        'Client Name',
                        'Container',
                        'Container Arrival',
                        'Receiver Name',
                        'SKU per Container',
                        'Days Old',
                    ];

            array_unshift($data, $fieldKeys);

            $objPHPExcel->getDefaultStyle()
                ->getNumberFormat()
                ->setFormatCode(\PHPExcel_Cell_DataType::TYPE_STRING);

            $objPHPExcel->getActiveSheet()->fromArray($data, NULL, 'A1');
        } else {
            $objPHPExcel->getActiveSheet()->SetCellValue('A1', $this->txtNoData);
        }

        $objPHPExcel->getActiveSheet()->setTitle($sheetTitle);
    }

    /*
    ****************************************************************************
    */

    private function isValidateDateTimeRun()
    {
        $hour = date('G');
        return $hour == $this->hour;
    }

    /*
    ****************************************************************************
    */
}
