<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 01/03/2017
 * Time: 10:19
 */

namespace tables\receiving;

use models\directories;

class emailWeeklyReport
{
    public $app;

    private $emails = [
        'wesley.cooper@seldatinc.com'
    ];

    private $rangeDateReport  = '1 WEEK';
    private $containerReport;
    private $day = 'SUN';
    private $hour = 20;

    /*
    ****************************************************************************
    */

    public function __construct($app)
    {
        $this->app = $app;
        $this->containerReport = new containerReports($this->app);
    }

    /*
    ****************************************************************************
    */

    public function run()
    {
        $isValidDate = $this->isValidateDateTimeRun();

        if (! $isValidDate) {
            return [];
        }

        $data = $this->getData();

        if (! $data) {
            return [];
        }

        $this->sendMailReport($data);

        return [];
    }

    /*
    ****************************************************************************
    */

    private function getData()
    {
        $selectField = $this->containerReport->getSelectFields();

        $from = $this->containerReport->getTable();

        $where =  $this->containerReport->where .
            ' AND co.setDate >=  DATE(DATE_SUB(NOW(),INTERVAL ' .
            $this->rangeDateReport . ')) ';

        $sql = 'SELECT co.recNum, ' . $selectField .
            ' FROM '. $from .
            ' WHERE '. $where .
            ' GROUP BY co.recNum';

        $result = $this->app->queryResults($sql);

        return $result;
    }

    /*
    ****************************************************************************
    */

    private function sendMailReport($data)
    {
        $date = date('Y-m-d') ;
        $dir = directories::getDir('uploads', 'receivingReport');

        if (! file_exists($dir)) {

            //self::$logs[] = 'Directory '.$dir.' does not exist!';

            return FALSE;
        }

        $receiptEmails = $this->emails;
        $fileReport = '[' . $date . '] - ReceivingWeeklyReport..xls';

        $filePathReport = $dir . DIRECTORY_SEPARATOR . $fileReport;

        $fields = array_column($this->containerReport->fields(), 'display');

        $this->createXlsFileReport($data, $filePathReport, $fields);

        $subject = '['. $date . '] - Email Receiving Weekly Report';
        $body = 'This is receiving weekly report at ' .  $date . '.<br/>
        Pleaese review the attach file.';

        foreach ($receiptEmails as $email) {
            \PHPMailer\send::mail( [
                'recipient' => $email,
                'subject' => $subject,
                'body' => $body,
                'files' =>  [
                    $filePathReport
                ]
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    private function createXlsFileReport($data, $fileReport, $fields)
    {
        $data = $data;
        $fileName = $fileReport;
        $fieldKeys = getDefault($fields, []);

        array_unshift($data, $fieldKeys);

        $phpExcel = new \PHPExcel();
        $phpExcel->getDefaultStyle()
            ->getNumberFormat()
            ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);

        $phpExcel->getActiveSheet()->fromArray($data, NULL, 'A1');

        $objWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel5');

        $objWriter->save($fileName);

    }

    /*
    ****************************************************************************
    */

    private function isValidateDateTimeRun()
    {
        $day = strtoupper(date('D'));
        $hour = date('G');

        return $day == $this->day && $hour == $this->hour;
    }

    /*
    ****************************************************************************
    */
}