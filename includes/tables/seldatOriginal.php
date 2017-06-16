<?php

namespace tables;

class seldatOriginal extends \tables\_default
{
    static $upcsExist;

    static $allowColumns = ['upc'];

    public $primaryKey = 'id';

    public $ajaxModel = 'seldatOriginal';

    public $fields = [
        'upc' => [
            'display' => 'UPC Original'
        ],
        'date' => [
            'display' => 'Date',
            'searcherDate' => TRUE
        ]
    ];

    public $table = 'upcs_originals';

    public $groupBy = 'id';

    public $mainField = 'id';

    /*
    ****************************************************************************
    */

    function getInputScan(&$data)
    {
        if ($data) {

            $data = preg_replace('/\s+/u',' ',$data );
            $data = str_replace(" ", "\n", $data);
            $upcArray = explode("\n", $data);
            $trimmedScans = array_map('trim', $upcArray);
            $noBlanks = array_filter($trimmedScans);
            $data = array_values($noBlanks);

            return $data;
        }

        return FALSE;
    }

    /*
    ****************************************************************************
    */

    function getExistUpcs($data)
    {
        $qMark = $this->app->getQMarkString($data);
        $clause = $data ? 'upc IN (' . $qMark . ')' : 0;

        $sql = 'SELECT  upc
                FROM    upcs_originals
                WHERE   ' . $clause;

        $results = $this->app->queryResults($sql, $data);

        return $results ? array_keys($results) : FALSE;

    }

    /*
    ****************************************************************************
    */

    function removeExistUPCs(&$data)
    {
        self::$upcsExist = $this->getExistUpcs($data);

        $keys = self::$upcsExist ? array_values(self::$upcsExist) : FALSE;

        if ($keys) {
            foreach ($keys as $upc) {
                if(($key = array_search($upc, $data)) !== false) {
                    unset($data[$key]);
                }
            }
        };
    }

    /*
    ****************************************************************************
    */

    function addNewUPCsOriginal($data)
    {
        $this->app->beginTransaction();

        foreach ($data as $upc) {

            $sql = 'INSERT INTO upcs_originals (
                        upc,
                        date
                    ) VALUES (?, NOW())';

            $this->app->runQuery($sql, [$upc]);
        }

        $this->app->commit();

    }

    /*
    ****************************************************************************
    */

    function checkDuplicateUPC(&$data)
    {
        $oldData = count($data);

        $data = array_unique($data, SORT_STRING );

        $data = array_values($data);

        $newData = count($data);

        $duplicateNumber = $oldData - $newData;

        return $duplicateNumber ? 'Removed ' . $duplicateNumber
            . ' UPC duplicate input.' : FALSE;

    }

    /*
    ****************************************************************************
    */

    function processUploadUpcOriginal(&$app)
    {
        $errors = [];
        $uploadPath = \models\directories::getDir('uploads', 'upcOriginal');

        if (empty($app->post['import'])) {
            return;
        }

        $pathInfo = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

        if (! in_array($pathInfo, ['xls', 'xlsx', 'txt'])) {
            $errors['format'] = 'Not allow .'.$pathInfo.' format file';
            return $results = [
                'errors' => $errors,
                'success' => FALSE
            ];
        } elseif (in_array($pathInfo, ['xls', 'xlsx'])) {
            $reader = new \excel\importer($app);
            $reader->uploadPath = $uploadPath;
            $reader->loadFile();
            $rows = $reader->objPHPExcel->getSheet(0)->getRowIterator();

            $validateFileColumns = $reader->validateFileColumns($reader, $rows,
                self::$allowColumns);
            if (! $validateFileColumns) {
                $errors['format'] = 'Wrong format column';
                return $results = [
                    'errors' => $errors,
                    'success' => FALSE
                ];
            }

            $data = $reader->parseToArray($rows);

            $this->getDataRow($data);

        } else {
            $dataInput = file_get_contents($_FILES['file']['tmp_name']);
            move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath . '/'
                . $_FILES['file']['name']);
            $data = $this->getInputUpcs($dataInput);
        }

        $data = array_filter($data);

        $badUPC = $this->getExistUpcs($data);

        $badUPC ? $errors['badUPC'] = $badUPC : NULL;

        $duplicate = $this->checkDuplicateUPC($data);

        $duplicate ? $errors['duplicate'] = $duplicate : NULL;

        $upcInvalid = $this->getUPCsInvalid($data);

        $upcInvalid ? $errors['upcInvalid'] = $upcInvalid : NULL;

        $this->removeExistUPCs($data);

        $upcSuccess = count($data) ? count($data) : FALSE;

        $results = [
            'errors' => $errors,
            'success' => $upcSuccess
        ];

        if ($data) {
            $this->addNewUPCsOriginal($data);
        }

        return $results ? $results : FALSE;
    }

    /*
    ****************************************************************************
    */

    function getDataRow(&$data)
    {
        foreach ($data as $rowIndex => $rowData) {

            if ($rowIndex == 1) {
                unset($data[$rowIndex]);
                continue;
            }
            $data[$rowIndex] = $rowData;
            foreach ($rowData as $key => $value) {
                $data[$rowIndex] = $value;
            }

        }
        $data = array_values($data);
    }

    /*
    ****************************************************************************
    */

    function getUPCsInvalid(&$data)
    {
        $upcInvalid = [];
        foreach ($data as $key => $value) {
            if (! is_numeric($value) || ! preg_match('/^\d{13}$/', $value)) {
                $upcInvalid[$key] = $data[$key];
                unset($data[$key]);
            }
        }
        $data = array_values($data);
        $upcInvalid = array_values($upcInvalid);

        return $upcInvalid ? $upcInvalid : FALSE;
    }
}