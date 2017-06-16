<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

class model extends base
{
    static $rowHeight = 6;

    static $rowAmount = 13;

    static $importColumns = [
        'upc' => 'UPC',
        'sku' => 'SKU',
        'client' => 'Client',
        'pieces' => 'Pieces',
    ];

    public $sizeHeight = 10;

    public $ajax = NULL;

    /*
    ****************************************************************************
    */

    function downloadTemplate()
    {
        $phpExcel = new PHPExcel();

        $phpExcel->setActiveSheetIndex(0);

        $phpExcel->getDefaultStyle()
            ->getNumberFormat()
            ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

        $sheet = $phpExcel->getActiveSheet();

        $count = 0;

        foreach (self::$importColumns as $fieldTitle) {
            $sheet->setCellValueByColumnAndRow($count++, 1, $fieldTitle);
        }

        $sheet->setCellValueByColumnAndRow(0, 2, '8731521943056');
        $sheet->setCellValueByColumnAndRow(1, 2, 'SYLETEST001');
        $sheet->setCellValueByColumnAndRow(2, 2, 'LA_ACCUTIME WATCH');
        $sheet->setCellValueByColumnAndRow(3, 2, '7');

        $sheet->setCellValueByColumnAndRow(0, 3, '5731321043086');
        $sheet->setCellValueByColumnAndRow(1, 3, 'SYLETEST002');
        $sheet->setCellValueByColumnAndRow(2, 3, 'TO_C-Life');
        $sheet->setCellValueByColumnAndRow(3, 3, '10');

        $sheet->getStyle(1)->getFont()->setBold(TRUE);
        $sheet->getStyle(1)->applyFromArray([
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $downloadFile = 'template_import_transfer_mezzanine.xlsx';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $downloadFile . '"');
        header('Cache-Control: max-age=0');

        $objWriter = new PHPExcel_Writer_Excel2007($phpExcel);
        $objWriter->save('php://output');
    }

    /*
    ****************************************************************************
    */

    function getRequestFromExcel()
    {
        // Check file uploaded
        $this->fileSubmitted = getDefault($_FILES) && ! $_FILES['file']['error'];

        $importer = new excel\importer($this, NULL);

        $importer->uploadPath = \models\directories::getDir('uploads',
                    'transfers');

        if (! $this->fileSubmitted) {
            throw new Exception('Please select Transfer Files');
        } elseif (! $importer->loadFile()) {
            throw new Exception('There was an error while uploading');
        } elseif (empty($importer->objPHPExcel)) {
            throw new Exception('No file loaded');
        }

        $objPHPExcel = $importer->objPHPExcel;

        if (! $objPHPExcel->getSheetCount()) {
            throw new Exception('File load failed');
        }

        // read excel
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
            //process first sheet only
            return $this->getSheetData($worksheet, $importer);
        }
    }

    /*
    ****************************************************************************
    */

    function getSheetData($worksheet, $importer)
    {
        $flip = array_flip(self::$importColumns);
        $results = $columnsSubmitted = [];
        $columnCount = count(self::$importColumns);

        foreach ($worksheet->getRowIterator() as $rowKey => $row) {
            $getRow = $importer->getRow($row);

            if ($rowKey == 1) {
                $columnsSubmitted = array_filter($getRow['rowData']);
                continue;
            }

            // No blank rows
            $rowData = array_filter($getRow['rowData']);

            if (count($rowData) != $columnCount) {
                echo 'Missing Field(s)<br>';
                echo 'Fields Submitted: '.implode(', ', $columnsSubmitted).'<br>';
                echo 'Fields Expected: '.implode(', ', self::$importColumns).'<br>';;
                echo 'Row: ' . $rowKey;
                die;
            }

            foreach ($columnsSubmitted as $id => $display) {

                $field = $flip[$display];
                $results[$rowKey - 1][$field] = $getRow['rowData'][$id];
            }
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

}