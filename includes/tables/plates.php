<?php

namespace tables;

use \common\logger;
use common\pdf;

class plates extends _default
{
    const SHIPPED_CHECK_OUT = 'SHCO';

    static $rowHeight = 6;

    static $plateNum = 6;

    static $pageIndex = 1;

    const PLATE_LENGTH = 8;

    static $labelTitle = 'License Plate';

    static $labelsTitle = 'License Plates';

    public $primaryKey = 'p.id';

    public $ajaxModel = 'plates';

    public $fields = [
        'id' => [
            'select' => 'p.id',
            'display' => 'License Plate',
            'acDisabled' => TRUE,
        ],
        'dateEntered' => [
            'select' => 'DATE(dateEntered)',
            'display' => 'Date Entered',
        ],
        'batch' => [
            'display' => 'Label Batch',
        ],
        'userID' => [
            'select' => 'CONCAT(firstName, " ", lastName)',
            'display' => 'Creator',
        ],
        'username' => [
            'select' => 'u.username',
            'display' => 'Username',
        ],
    ];

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'licenseplate p
            LEFT JOIN '.$userDB.'.info u ON p.userID = u.id';
    }

    /*
    ****************************************************************************
    */

    function insert($userID, $quantity, $makeTransaction=TRUE)
    {
         return \common\labelMaker::inserts([
            'model' => $this,
            'userID' => $userID,
            'quantity' => $quantity,
            'labelType' => 'plate',
            'makeTransaction' => $makeTransaction,
        ]);
    }

    /*
    ****************************************************************************
    */

    function getPlateInfo(&$plate)
    {
        $sql = 'SELECT  '.$this->getSelectFields().'
                FROM     '.$this->table.'
                WHERE    p.id = ?';

        $plate = $this->app->queryResult($sql, [$plate]);
    }

    /*
    ****************************************************************************
    */

    function getPlateIDs($plates)
    {
        if (! $plates) {
            return $plates;
        }

        $sql = 'SELECT   p.id
                FROM     '.$this->table.'
                WHERE    p.id IN ('.$this->app->getQMarkString($plates).')';

        $foundPlates = $this->app->queryResults($sql, $plates);

        return $foundPlates;
    }

    /*
    ****************************************************************************
    */

    function validPlates($plates)
    {
        if (! $plates) {
            return TRUE;
        }

        $foundPlates = $this->getPlateIDs($plates);

        // If any of the results were empty then return false
        return count($plates) == count($foundPlates) ? TRUE : FALSE;
    }

    /*
    ****************************************************************************
    */

    function validPlatesTellWhich($plates)
    {
        if (! $plates) {
            return NULL;
        }

        $foundPlates = $this->getPlateIDs($plates);

        $unknowPlates = [];
        foreach ($plates as $plate) {
            if (! isset($foundPlates[$plate])) {
                $unknowPlates[] = $plate;
            }
        }
        $result = implode(', ', $unknowPlates);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function platesArray($filteredValues)
    {
        $licensePlates = $plateErrors = [];
        $currentPlate = $errors = NULL;

        foreach($filteredValues as $value) {
            if ($currentPlate == $value) {
                // closing plate tag
                $currentPlate = NULL;
                continue;
            }

            $isArray = is_array($value);

            if ($currentPlate && ! $isArray || ! $currentPlate && $isArray) {
                // carton is an array where invID is a key and ucc is a value,
                // plate number should not be an array.
                $plateErrors[] = 'Missing opening/closing plate or duplicate '
                        . 'adjacent plates occured';

                continue;
            }

            if ($currentPlate) {

                $licensePlates['mapped'][$currentPlate]['cartonNumbers'][]
                        = $value;
                // Store in cartons only array
                $licensePlates['cartons'][] = $value;
            } else {
                // opening plate tag
                $licensePlates['mapped'][$value]['count'] = 0;
                $currentPlate = $value;

                // Store in Plates only array
                $licensePlates['plates'][$value] = TRUE;
            }
        }

        foreach ($licensePlates['mapped'] as $plateNo => &$plateData) {
            if (! getDefault($plateData['cartonNumbers'])) {

                $plateErrors[] = 'No valid cartons for License Plate # '
                        . $plateNo . ' were found';

                continue;
            }

            $plateData['count'] = count($plateData['cartonNumbers']);
        }

        $errors = $plateErrors ? implode('<br>', $plateErrors) : NULL;

        return [
            'errors' => $errors ? [$errors] : NULL,
            'licensePlates' => $licensePlates
        ];
    }

    /*
    ****************************************************************************
    */

    function platesLocationsArray($filteredValues)
    {
        $licensePlates = $plateErrors = [];
        $currentPlate = $currentLocation = $errors = NULL;

        foreach($filteredValues as $value) {
            if ($currentPlate == $value && ! $currentLocation) {
                // closing plate tag
                $currentPlate = NULL;
                continue;
            }

            if ($currentLocation == $value) {
                // closing location tag
                $currentLocation = NULL;
                continue;
            }

            $isArray = is_array($value);

            if ( ($currentPlate && $currentLocation && ! $isArray )
                    || ! ($currentPlate && $currentLocation) && $isArray) {
                // carton is an array where invID is a key and ucc is a value,
                // plate and location should not be an array.
                $plateErrors[] = 'Missing opening/closing plate/location or '
                        . 'duplicate adjacent plates occured';

                continue;
            }

            if (! $currentPlate) {
                $currentPlate = $value;
                $licensePlates['plates'][$value] = TRUE;
                $licensePlates['mapped'][$value]['count'] = 0;
                continue;
            } elseif (! $currentLocation) {
                $currentLocation = $value;
                $licensePlates['locations'][] = $value;
                continue;
            } else {
                $licensePlates['mapped'][$currentPlate]['cartonNumbers'][]
                        = $value;
                // Store in cartons only array
                $licensePlates['cartons'][] = $value;
                $licensePlates['mapped'][$currentPlate]['count']++;
            }
        }

        foreach ($licensePlates['mapped'] as $plateNo => &$plateData) {
            if (! getDefault($plateData['cartonNumbers'])) {

                $plateErrors[] = 'No valid cartons for License Plate # '
                        . $plateNo . ' were found';

                continue;
            }

            $plateData['count'] = count($plateData['cartonNumbers']);
        }

        $errors = $plateErrors ? implode('<br>', $plateErrors) : NULL;

        return [
            'errors' => $errors ? [$errors] : NULL,
            'licensePlates' => $licensePlates
        ];
    }

    /*
    ****************************************************************************
    */

    function check($licensePlates)
    {
        if (! $licensePlates) {
            return ['No License Plates Submitted'];
        }

        $missing = [];

        $plates = array_keys($licensePlates);

        $missing = $this->checkExisted($plates);

        $used = $this->checkPlatesIsUsed($plates);

        $missing = array_merge($missing, $used);

        return $missing;
    }

    /*
    ****************************************************************************
    */

    function getAllLicensePlates($plates, $fields = '')
    {
        if (! $plates) {
            return [];
        }

        $selected =  $fields ? $fields : 'id';
        $qMarkString = $this->app->getQMarkString($plates);

        $sql = 'SELECT  ' . $selected . '
                FROM    licenseplate
                WHERE   id in (' . $qMarkString . ')';

        $results = $this->app->queryResults($sql, $plates);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkExisted($plates)
    {
        if (! $plates) {
            return;
        }
        $results = [];

        $found = $this->getAllLicensePlates($plates);

        foreach ($plates as $plate) {
            if (! isset($found[$plate])) {
                $results[$plate] = 'License plate '. $plate
                    . ' was not found';
            }
        }
        return $results;
    }

    /*
    ****************************************************************************
    */

    function getPlatesUsed($plates)
    {
        if (! $plates) {
            return;
        }

        $qMarkString = $this->app->getQMarkString($plates);

        $sql = 'SELECT  plate
                FROM    inventory_cartons
                WHERE   plate in (' . $qMarkString . ')';


        $results = $this->app->queryResults($sql, $plates);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkPlatesIsUsed($plates)
    {
        if (! $plates) {
            return;
        }

        $result = [];

        $platesUsed = $this->getPlatesUsed($plates);

        foreach ($plates as $plate) {
            if (isset($platesUsed[$plate])) {
                $result[$plate] = 'License plate ' . $plate . ' has been used.';
            }
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    function updateLocationsByPlates($updates, $noChangeStatus = FALSE)
    {
        $locations = new locations($this->app);
        $statuses = new statuses\inventory($this->app);

        $newValues = $logValues = [];

        $statusID = $statuses->getStatusID('RK');

        // Get location IDs
        $locIDs = $locations->getLocIDs($updates);

        // Get cartons' old values
        $qMarks = $this->app->getQMarkString($updates);

        $sql = 'SELECT id,
                       plate,
                       locID,
                       mLocID,
                       statusID,
                       mStatusID
                FROM   inventory_cartons
                WHERE  plate IN (' . $qMarks . ')';

        $plates = array_keys($updates);
        $oldCartonValues = $this->app->queryResults($sql, $plates);

        $sql = $noChangeStatus ?
                'UPDATE  inventory_cartons
                SET     locID = ?,
                        mLocID = ?
                WHERE   plate = ?' :

                'UPDATE  inventory_cartons
                SET     locID = ?,
                        mLocID = ?,
                        statusID = ?,
                        mStatusID = ?
                WHERE   plate = ?';

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        foreach ($updates as $plate => $row) {

            $warehouseID = $row['warehouseID'];
            $displayName = $row['locationName'];

            $locID = $locIDs[$warehouseID][$displayName];

            $params = $noChangeStatus ?
                    [$locID, $locID, $plate] :
                    [$locID, $locID, $statusID, $statusID, $plate];

            // Store new locations and statuses by plate
            $newValues[$plate] = $noChangeStatus ?
                    [
                        'locID' => $locID
                    ] :
                    [
                        'locID' => $locID,
                        'statusID' => $statusID
                    ];

            $this->app->runQuery($sql, $params);
        }

        foreach ($oldCartonValues as $invID => $row) {
            $plate = $row['plate'];
            $logValues['invIDs'][] = $invID;
            $logValues['oldLocID'][] = $row['locID'];
            $logValues['oldMLocID'][] = $row['mLocID'];
            $logValues['newLocID'][] = $newValues[$plate]['locID'];

            if (! $noChangeStatus) {
                $logValues['oldStatusID'][] = $row['statusID'];
                $logValues['oldMStatusID'][] = $row['mStatusID'];
                $logValues['newStatusID'][] = $newValues[$plate]['statusID'];
            }
        }

        $fieldLogs = [
            'locID' => [
                'fromValues' => $logValues['oldLocID'],
                'toValues' => $logValues['newLocID']
            ],
            'mLocID' => [
                'fromValues' => $logValues['oldMLocID'],
                'toValues' => $logValues['newLocID']
            ]
        ];

        if (! $noChangeStatus) {
            $fieldLogs['statusID'] = [
                'fromValues' => $logValues['oldStatusID'],
                'toValues' => $logValues['newStatusID']
            ];

            $fieldLogs['mStatusID'] = [
                'fromValues' => $logValues['oldMStatusID'],
                'toValues' => $logValues['newStatusID']
            ];
        }

        logger::edit([
            'db' => $this->app,
            'primeKeys' => $logValues['invIDs'],
            'fields' => $fieldLogs,
            'transaction' => FALSE,
        ]);

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function validPlatesInCartons($plates)
    {
        if (! $plates) {
            return TRUE;
        }

        $sql = 'SELECT   p.id
                FROM     '.$this->table.'
                RIGHT JOIN inventory_cartons ca ON ca.plate = p.id
                WHERE    p.id IN ('.$this->app->getQMarkString($plates).')';

        $foundPlates = $this->app->queryResults($sql, $plates);

        // If any of the results were empty then return false
        return count($plates) == count($foundPlates) ? TRUE : FALSE;

    }

    /*
    ****************************************************************************
    */

    function checkLocationValuesToTransfer($plateLocations)
    {
        $plates = [];
        $this->errors = NULL;
        $scanPlates = array_keys($plateLocations);

        $warehouses = $this->getWarehouseOfPlates($scanPlates);

        $locations = array_values($plateLocations);
        $locations = array_unique($locations);
        $locations = array_values($locations);

        $warehouseToLocations = $this->getWarehouseByLocations($locations);

        foreach ($plateLocations as $licensePlate => $locationName) {

            $locationDiplayName = $locID = $warehouseID = NULL;

            foreach ($warehouseToLocations as $value) {

                $ucLoc = strtoupper($locationName);

                $casePlate  = FALSE;

                if (! isset($warehouses[$licensePlate])) {
                    $casePlate = TRUE;
                } elseif ( $warehouses[$licensePlate]['warehouseID']
                        == $value['warehouseID']) {
                    $casePlate = TRUE;
                }

                if ($ucLoc == $value['displayName'] && $casePlate) {

                    $locationDiplayName = $value['displayName'];
                    $warehouseID = $value['warehouseID'];
                    $locID = $value['locID'];

                    break;
                }
            }

            if ($warehouseID) {
                $plates[$licensePlate]['locID'] = $locID;
                $plates[$licensePlate]['locationName'] = $locationDiplayName;
                $plates[$licensePlate]['warehouseID'] = $warehouseID;
            } else {
                $this->errors .= 'Location '.$locationName.' and License Plate '
                    . $licensePlate. ' are in Different Warehouses <br>';
            }
        }

        return $plates;
    }

    /*
    ****************************************************************************
    */

    function checkLocationValues($updates)
    {
        $plates = [];
        $this->errors = NULL;
        $scanPlates = array_keys($updates);

        $warehouses = $this->getWarehouseOfPlates($scanPlates);

        $locations = array_values($updates);
        $locations = array_unique($locations);
        $locations = array_values($locations);

        $warehouseToLocations = $this->getWarehouseByLocations($locations);

        foreach ($updates as $licensePlate => $locationName) {

            $locationDiplayName = $locID = $warehouseID = NULL;

            foreach ($warehouseToLocations as $value) {

                $ucLoc = strtoupper($locationName);

                if ($ucLoc == $value['displayName']
                    && $warehouses[$licensePlate]['warehouseID']
                    == $value['warehouseID']) {

                    $locationDiplayName = $value['displayName'];
                    $warehouseID = $value['warehouseID'];
                    $locID = $value['locID'];

                    break;
                }
            }

            if ($warehouseID) {
                $plates[$licensePlate]['locID'] = $locID;
                $plates[$licensePlate]['locationName'] = $locationDiplayName;
                $plates[$licensePlate]['warehouseID'] = $warehouseID;
            } else {
                $this->errors .= 'Location '.$locationName.' and License Plate '
                    . $licensePlate. ' are in Different Warehouses <br>';
            }
        }

        return $plates;
    }

    /*
    ****************************************************************************
    */

    function getWarehouseByLocations($locations)
    {
        if (! $locations) {
            return;
        }

        $qMarkString = $this->app->getQMarkString($locations);

        $sql = 'SELECT   id,
                         UCASE(displayName) AS displayName,
                         warehouseID,
                         id as locID
                 FROM    locations l
                 WHERE   displayName IN (' . $qMarkString . ')';

        $results = $this->app->queryResults($sql, $locations);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getWarehouseOfPlates($plates)
    {
        if (! $plates) {
            return;
        }

        $qMarks = $this->app->getQMarkString($plates);
        // join vendors table to get License Plate warehouseID cause can not
        // join locations table on ca.locID since a carton may not have a
        // corresponding location a corresponding location
        $sql = 'SELECT    DISTINCT ca.plate,
                          warehouseID
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      vendors v ON v.ID = co.vendorID
                WHERE     ca.plate IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $plates);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getOrdersFromPlate($plate)
    {
        $qMarks = $this->app->getQMarkString($plate);
        //$plate received is array.
        $sql = 'SELECT       scanordernumber
                FROM         inventory_cartons ca
                LEFT JOIN    neworder o
                ON           ca.orderID = o.id
                WHERE        plate IN (' . $qMarks . ')';

        $orderNumbers = array_keys($this->app->queryResults($sql, $plate));

        return $orderNumbers;
    }

    /*
    ****************************************************************************
    */

    function getInventoryPlates($plates)
    {
        $qMarks = $this->app->getQMarkString($plates);

        $sql = 'SELECT  plate
                FROM    inventory_cartons
                WHERE   plate IN (' . $qMarks . ')
                AND     plate IS NOT NULL
                GROUP BY plate
                ';

        return $this->app->queryResults($sql, $plates);
    }

    /*
    ****************************************************************************
    */

    function getOrderLicensePlate ($orderID)
    {
        $sql = 'SELECT    id,
                          plate
                FROM      inventory_cartons
                WHERE     orderID = ?
                GROUP BY  plate';

        $results = $this->app->queryResults($sql, [$orderID]);

        return count($results);
    }

    /*
    ****************************************************************************
    */

    function getPlateEachLocation($dataInput)
    {
        $data = [];

        $sql = 'SELECT 	  ic.plate
                FROM      inventory_cartons ic
                LEFT JOIN locations l ON l.id = ic.locID
                LEFT JOIN licenseplate lp ON lp.ID = ic.plate
                LEFT JOIN neworder o ON o.ID = ic.orderID
                LEFT JOIN statuses s ON s.id = o.statusID
                WHERE     l.displayName = ?
                AND       ic.orderID
                AND       s.shortName = ?
                GROUP BY  ic.plate
                ORDER BY  ic.plate';

        foreach ($dataInput as $key => $location) {

            $results = $this->app->queryResults($sql, [
                $location,
                self::SHIPPED_CHECK_OUT
            ]);

            $data[$location] = $results ? array_keys($results) : FALSE;

        }

        return $data;
    }

    /*
    ****************************************************************************
    */

    function printPDFLicensePlate($data, $file=NULL)
    {

        $outputType = $file ? 'F' : 'I';
        $pdfOutput = $file ? $file : 'pdf';

        $this->pdf = new \TCPDF('P', 'mm', 'Letter', TRUE, 'UTF-8', FALSE);
        $this->pdf->setPrintHeader(FALSE);
        $this->pdf->setPrintFooter(FALSE);
        $this->pdf->SetAutoPageBreak(TRUE, 0);
        $this->pdf->SetLeftMargin(10);
        $this->pdf->setCellPaddings(0, 0, 0, 0);

        $this->pdf->AddPage();

        $this->platePageHeader();

        $this->platePageContent($data);

        unset($_SESSION['licensePlate']);

        $this->pdf->Output($pdfOutput, $outputType);
    }

    /*
    ****************************************************************************
    */

    function platePageHeader()
    {

        $this->pdf->SetFont('helvetica', 'B', 13);

        $text = 'LICENSE PLATE AT EACH LOCATIONS';

        pdf::myMultiCell($this->pdf, 185, self::$rowHeight, $text, 0, 'C');

        $page = self::$pageIndex;

        pdf::myMultiCell($this->pdf, 10, self::$rowHeight, $page, 0, 'R');

        $this->pdf->Ln(15);

        $this->pdf->SetFont('helvetica', 'B', 11);

        pdf::myMultiCell($this->pdf, 15, self::$rowHeight, 'No');
        pdf::myMultiCell($this->pdf, 50, self::$rowHeight, 'Location');
        pdf::myMultiCell($this->pdf, 130, self::$rowHeight, 'License Plate');

        $this->pdf->Ln();
    }

    /*
    ****************************************************************************
    */

    function platePageContent($data)
    {
        $index = 1;
        $pageHeight = 0;

        foreach ($data as $location => $plateArray) {

            if (is_array($plateArray)) {

                $rowHeight = ceil(count($plateArray) / self::$plateNum)
                    * self::$rowHeight;
                $plateText = '';

                foreach ($plateArray as $key => $plate) {

                    if ($plate === end($plateArray)) {
                        $plateText .= $plate;
                    } else {
                        $plateText .= $plate . ', ';
                    }
                }

                $this->pdf->SetFont('helvetica', '', 11);

                pdf::myMultiCell($this->pdf, 15, $rowHeight, $index);

                pdf::myMultiCell($this->pdf, 50, $rowHeight, $location);

                pdf::myMultiCell($this->pdf, 130, $rowHeight, $plateText);

                $this->pdf->Ln();

                $index++;

                $pageHeight += $rowHeight;

                if ($pageHeight >= 220) {
                    $pageHeight = 0;
                    self::$pageIndex++;
                    $this->pdf->AddPage();
                    $this->platePageHeader();
                }

            }

        }
    }

    /*
    ****************************************************************************
    */

    function getPlateVendors($locationPlates, $warehouseIDs)
    {
        if (! $locationPlates || ! $warehouseIDs) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($locationPlates);

        $sql = 'SELECT 	  plate,
                          co.vendorID,
                          vendorName,
                          clientCode,
                          email
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      vendors v ON v.id = co.vendorID
                WHERE     plate IN (' . $qMarks . ')
                GROUP BY  plate';

        $results = $this->app->queryResults($sql, $locationPlates);

        $return = [];

        foreach ($results as $plate => $vendorData) {

            $vendorID = $vendorData['vendorID'];

            if (! isset($return[$vendorID])) {

                unset($vendorData['vendorID']);

                $return[$vendorID] = $vendorData;
            }

            $return[$vendorID]['plates'][] = $plate;
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getPlateData($licensePlates)
    {
        $qMarks = $this->app->getQMarkString($licensePlates);

        $sql = 'SELECT DISTINCT plate,
                          shortName,
                          warehouseID,
                          co.recNum,
                          vendorID
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      statuses s ON s.id = ca.statusID
                JOIN      locations l ON l.id = ca.locID
                WHERE     plate IN (' . $qMarks . ')
                ';

        $results = $this->app->queryResults($sql, $licensePlates);

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getInvIdsOnLicensePlate($app, $invIds, $licensePlate, $batch)
    {
        if (! ($invIds && $licensePlate)) {
            return false;
        }

        $qMarks = $app->getQMarkString($invIds);

        $sql = 'SELECT    id,
                          uom
                FROM      inventory_cartons
                WHERE     plate = ?     
                AND       batchID = ?
                AND       id IN (' . $qMarks . ')
                ';

        $params = [$licensePlate, $batch];
        $params = array_merge($params, $invIds);

        $results = $app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */
}