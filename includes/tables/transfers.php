<?php

namespace tables;

use \common\logger;

class transfers extends _default
{
    public $ajaxModel = 'transfers';
    public $primaryKey = 't.id';

    static $allowColumns = [
        'ucc',
        'location'
    ];

    public $fields = [
        'id' => [
            'select' => 't.id',
            'display' => 'ID',
        ],
        'vendorName' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)'
        ],
        'printLabels' => [
            'select' => 't.id',
            'display' => 'Reprint Labels',
        ],
        'createDate' => [
            'select' => 'createDate',
            'display' => 'Create Date',
            'searcherDate' => TRUE
        ],
        'username' => [
            'select' => 'u.username',
            'display' => 'User Name',
        ],
        'confirmation' => [
            'select' => 'IF(discrepancy IS NULL, "Pending",
                            IF(discrepancy = 0, "Confirmed",
                               CONCAT(
                                   "Discrepant",
                                   IF(discrepancy > 0, " + ", " - "),
                                   ABS(discrepancy)
                               )
                            )
                         )',
            'display' => 'Mezzanine Arrival',
        ],
    ];

    public $groupBy = 't.id';

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'transfers t
            LEFT JOIN transfer_items ti ON ti.transferID = t.id
            JOIN      vendors v ON v.id = ti.vendorID
            JOIN      warehouses w ON w.id = v.warehouseID
            LEFT JOIN ' . $userDB . '.info u ON u.id = t.userID';
    }

    /*
    ****************************************************************************
    */

    function checkBarcodes($transfersValues)
    {
        $processed = $invalidNumbers = $duplicateNumbers = $errMsg = [];

        $result = $this->valid($transfersValues, 'barcode', 'barcode');

        $barcodes = array_column($result['perRow'], 'target');

        $validBarcodes = array_flip($barcodes);

        foreach ($transfersValues as $transferNo) {
            if (isset($processed[$transferNo])) {
                $duplicateNumbers[$transferNo] = TRUE;
            }

            if (! isset($validBarcodes[$transferNo])) {
                $invalidNumbers[$transferNo] = TRUE;
            }

            $processed[$transferNo] = TRUE;
        }

        if ($invalidNumbers) {

            $values = array_keys($invalidNumbers);

            $errMsg[] = 'Invalid Transfer Number(s):<br>'
                    . implode('<br>', $values);
        }

        if ($duplicateNumbers) {

            $values = array_keys($duplicateNumbers);

            $errMsg[] = 'Duplicate Transfer Number(s):<br>'
                    . implode('<br>', $values);
        }

        return $errMsg;
    }

    /*
    ****************************************************************************
    */

    function getBarcodePieces($barcodes)
    {
        $qMarks = $this->app->getQMarkString($barcodes);

        $sql = 'SELECT    barcode,
                          SUM(pieces) AS pieces
                FROM      transfers t
                JOIN      transfer_items ti ON ti.transferID = t.id
                WHERE     barcode IN (' . $qMarks . ')
                GROUP BY  barcode';

        $results = $this->app->queryResults($sql, $barcodes);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function updateDiscrepancy($barcode, $discrepancy)
    {
        $sql = 'UPDATE    transfers
                SET       discrepancy = ?
                WHERE     barcode = ?';

        $result = $this->app->runQuery($sql, [$discrepancy, $barcode]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function processTransfer($dataInput, $cartons)
    {
        $locKeys = $locIDs = $invIDs = $fromValues = $fromManualValues =
                $cartonsToSplit = $parents = [];

        foreach ($dataInput as $newLocID => $locationCartons) {

            $locKeys[$newLocID] = TRUE;

            foreach ($locationCartons as $ucc => $carton) {

                $locID = $carton['locID'];
                $mLocID = $carton['mLocID'];

                if ($carton['uom'] > 1) {

                    $cartonsToSplit[$ucc] = array_fill(0, $carton['uom'], 1);

                    $parents[$ucc] = [
                        'newLocID' => $newLocID,
                        'locID' => $locID,
                        'mLocID' => $mLocID,
                    ];

                } else {
                    $invIDs[$newLocID][] = $carton['id'];
                    $fromValues[$newLocID][] = $locID;
                    $fromManualValues[$newLocID][] = $mLocID;
                }
            }
        }

        if ($cartonsToSplit) {

            $results = $cartons->split($cartonsToSplit);

            if ($results['error']) {
                return [
                    'errors' => $results['error']
                ];
            }

            foreach ($results['combined'] as $parent => $child) {

                $locID = $parents[$parent]['newLocID'];

                foreach ($child as $carton) {
                    $invIDs[$locID][] = $carton['invID'];
                    $fromValues[$locID][] = $parents[$parent]['locID'];
                    $fromManualValues[$locID][] = $parents[$parent]['mLocID'];
                }
            }
        }

        $locIDs = array_keys($locKeys);

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        foreach ($locIDs as $locID) {

            $cartons->updateLocations($invIDs[$locID], $locID);

            logger::edit([
                'db' => $this->app,
                'primeKeys' => $invIDs[$locID],
                'fields' => [
                    'locID' => [
                        'fromValues' => $fromValues[$locID],
                        'toValues' => $locID,
                    ],
                    'mLocID' => [
                        'fromValues' => $fromManualValues[$locID],
                        'toValues' => $locID,
                    ],
                ],
                'transaction' => FALSE,
            ]);
        }

        $this->app->commit();

        return FALSE;
    }

    /*
    ****************************************************************************
    */

    function processImportTransfer($app)
    {
        $errors = [];
        $uploadPath = \models\directories::getDir('uploads', 'transfers');

        if (empty($app->post['import'])) {
            return;
        }

        $pathInfo = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

        if (! in_array($pathInfo, ['xls', 'xlsx'])) {
            $errors[] = 'Not allow .' . $pathInfo . ' format file';
            return [
                'errors' => $errors
            ];
        } else {
            $reader = new \excel\importer($app);
            $reader->uploadPath = $uploadPath;
            $reader->loadFile();
            $rows = $reader->objPHPExcel->getSheet(0)->getRowIterator();

            $validateFileColumns = $reader->validateFileColumns($reader, $rows,
                self::$allowColumns);
            if (! $validateFileColumns) {
                $errors[] = 'Wrong format column';
                return [
                    'errors' => $errors
                ];
            }

            $data = $reader->parseToArray($rows);

            $results = $this->restructureData($data);

            return $results;
        }
    }

    /*
    ****************************************************************************
    */

    function restructureData($dataInput)
    {
        $data = [];
        $dataStructure = [];

        foreach ($dataInput as $rowIndex => $rowData) {

            if ($rowIndex == 1) {
                unset($dataInput[$rowIndex]);
                continue;
            }

            $data[$rowData['location']][] = $rowData['ucc'];
        }

        foreach ($data as $location => $row) {
            $dataStructure[] = $location;
            foreach ($row as $ucc) {
                $dataStructure[] = $ucc;
            }
            $dataStructure[] = $location;
        }

        return  array_filter($dataStructure);
    }

    /*
    ***************************************************************************
    */
}
