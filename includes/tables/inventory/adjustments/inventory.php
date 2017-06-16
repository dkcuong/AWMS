<?php

namespace tables\inventory\adjustments;

use \common\logger;

class inventory extends \tables\_default
{
    public $primaryKey = 'ca.id';

    public $ajaxModel = 'inventory\adjustments\inventory';

    public $cartonsInfo = [];

    public $where = '(
                        (sn.shortName IS NULL
                            OR sn.shortName = "SHCO"
                        )
                        AND (
                            ca.statusID != ca.mStatusID
                            OR locID != mLocID
                        )
                        OR s.shortName = "DS"
                        OR s.shortName = "RS"
                        OR locID != mLocID
                    )
                    AND (lf.category IS NULL
                        OR lf.category = "cartons"
                    )';

    public $groupBy = 'ca.id';

    /*
    ****************************************************************************
    */

    function fields()
    {
        $cartons = new \tables\inventory\cartons($this->app);

        $ucc128 = $cartons->fields['ucc128']['select'];

        $plateLength = \tables\plates::PLATE_LENGTH;

        return $fields = [
            'ucc128' => [
                'select' => $ucc128,
                'display' => 'UCC128',
                'customClause' => TRUE,
                'noEdit' => TRUE,
                'acDisabled' => TRUE,
            ],
            'logTime' => [
                'display' => 'Discrepancy Time',
                'searcherDate' => TRUE,
                'noEdit' => TRUE,
            ],
            'customerOrderNumber' => [
                'display' => 'Customer Order Number',
                'noEdit' => TRUE,
            ],
            'statusID' => [
                'select' => 's.shortName',
                'display' => 'Status',
                'searcherDD' => 'statuses\\inventory',
                'ddField' => 'shortName',
                'update' => 'ca.statusID',
            ],
            'mStatusID' => [
                'select' => 'sm.shortName',
                'display' => 'Manual Status',
                'searcherDD' => 'statuses\\inventory',
                'ddField' => 'shortName',
                'update' => 'ca.mStatusID',
            ],
            'locID' => [
                'select' => 'l.displayName',
                'display' => 'Location',
            ],
            'mLocID' => [
                'select' => 'lm.displayName',
                'display' => 'Manual Location',
            ],
            'warehouse' => [
                'select' => 'w.displayName',
                'display' => 'Warehouse',
            ],
            'vendor' => [
                'select' => 'CONCAT(w.shortName, "_", vendorName)',
                'display' => 'Client Name',
                'noEdit' => TRUE,
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            ],
            'name' => [
                'select' => 'co.name',
                'display' => 'Container',
                'noEdit' => TRUE,
            ],
            'containerRecNum' => [
                'select' => 'b.recNum',
                'display' => 'Receiving Number',
                'noEdit' => TRUE,
            ],
            'batchID' => [
                'display' => 'Batch Number',
                'noEdit' => TRUE,
            ],
            'sku' => [
                'select' => 'p.sku',
                'batchFields' => TRUE,
                'display' => 'Style Number',
                'noEdit' => TRUE,
            ],
            'uom' => [
                'batchFields' => TRUE,
                'select' => 'LPAD(UOM, 3, 0)',
                'display' => 'UOM',
                'noEdit' => TRUE,
            ],
            'prefix' => [
                'batchFields' => TRUE,
                'display' => 'Prefix',
                'noEdit' => TRUE,
            ],
            'suffix' => [
                'select' => 'suffix',
                'batchFields' => TRUE,
                'display' => 'Suffix',
                'noEdit' => TRUE,
            ],
            'height' => [
                'batchFields' => TRUE,
                'display' => 'Height',
                'noEdit' => TRUE,
            ],
            'width' => [
                'batchFields' => TRUE,
                'display' => 'Width',
                'noEdit' => TRUE,
            ],
            'length' => [
                'batchFields' => TRUE,
                'display' => 'Length',
                'noEdit' => TRUE,
            ],
            'weight' => [
                'batchFields' => TRUE,
                'display' => 'Weight',
                'noEdit' => TRUE,
            ],
            'upcID' => [
                'select' => 'p.upc',
                'batchFields' => TRUE,
                'display' => 'UPC',
                'noEdit' => TRUE,
            ],
            'cartonID' => [
                'select' => 'ca.cartonID',
                'display' => 'Carton ID',
                'noEdit' => TRUE,
            ],
            'plate' => [
                'display' => 'License Plate',
                'isNum' => $plateLength,
                'allowNull' => TRUE,
                'acDisabled' => TRUE,
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function table()
    {
        return 'inventory_cartons ca
            JOIN      inventory_batches b ON b.id = ca.batchID
            JOIN      inventory_containers co ON co.recNum = b.recNum
            JOIN      statuses s ON ca.statusID = s.id
            JOIN      statuses sm ON ca.mStatusID = sm.id
            JOIN      vendors v ON v.id = co.vendorID
            JOIN      warehouses w ON w.id = v.warehouseID
            LEFT JOIN locations l ON l.id = ca.locID
            LEFT JOIN locations lm ON lm.id = ca.mLocID
            JOIN      upcs p ON p.id = b.upcID
            LEFT JOIN pick_cartons pc ON pc.cartonID = ca.id
            LEFT JOIN neworder n ON n.id = pc.orderID
            LEFT JOIN statuses sn ON n.statusID = sn.id
            LEFT JOIN logs_values lv ON lv.primeKey = ca.id
            LEFT JOIN logs_cartons lc ON lc.id = lv.logID
            LEFT JOIN logs_fields lf ON lf.id = lv.fieldID
            ';
    }

    /*
    ****************************************************************************
    */

    function getAdjustCartonsInfo($invIDs)
    {
        if (! $invIDs) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($invIDs);

        $sql = 'SELECT    id,
                          plate,
                          locID,
                          statusID
                FROM      inventory_cartons ca
                WHERE     id IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $invIDs);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function logAdjustments($cartonInfo)
    {
        $sql = 'INSERT INTO adjustment_logs (
                    cartonID,
                    oldPlate,
                    newPlate,
                    oldLocID,
                    newLocID,
                    oldStatusID,
                    newStatusID,
                    dateAdjusted
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, NOW()
                )';

        foreach ($cartonInfo as $invID => $info) {

            $oldPlate = $info['plate'];
            $oldLocID = $info['locID'];
            $oldStatusID = $info['statusID'];
            $newStatusID = $info['newStatusID'];

            $newPlate = getDefault($info['newPlate'], $oldPlate);
            $newLocID = getDefault($info['newLocID'], $oldLocID);

            $this->app->runQuery($sql, [
                $invID,
                $oldPlate,
                $newPlate,
                $oldLocID,
                $newLocID,
                $oldStatusID,
                $newStatusID,
            ]);

            $logData = [];

            if ($oldPlate != $newPlate) {
                $logData['plate'] = [
                    'fromValues' => $oldPlate,
                    'toValues' => $newPlate,
                ];
            }

            if ($oldLocID != $newLocID) {
                $logData['locID'] = [
                    'fromValues' => $oldLocID,
                    'toValues' => $newLocID,
                ];
            }

            if ($oldStatusID != $newStatusID) {
                $logData['statusID'] = [
                    'fromValues' => $oldStatusID,
                    'toValues' => $newStatusID,
                ];
            }

            logger::edit([
                'db' => $this->app,
                'primeKeys' => $invID,
                'fields' => $logData,
                'transaction' => FALSE,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function process($dataPassed)
    {
        $rackedStatus = \tables\inventory\cartons::STATUS_RACKED;
        $discrepantStatus = \tables\inventory\cartons::STATUS_DISCREPANCY;

        $statuses = new \tables\statuses\inventory($this->app);

        $statusIDs = $statuses->getStatusIDs([$rackedStatus, $discrepantStatus]);

        $discrepantStatusID = $statusIDs[$discrepantStatus]['id'];
        $rackedStatusID = $statusIDs[$rackedStatus]['id'];

        $locationData = $dataPassed['locationData'];
        $passedCartons = $dataPassed['cartons'];
        $masterLabels = $dataPassed['masterLabels'];

        $locationsPasses = array_column($locationData, 'locID');

        $inventoryInSystem = $this->cartonsInSystem($locationsPasses, $rackedStatusID);

        $cartonsInSystem = array_keys($inventoryInSystem);

        $return['extraCartons'] = array_diff($passedCartons, $cartonsInSystem);
        $return['missedCartons'] = $missedCartons =
                array_diff($cartonsInSystem, $passedCartons);

        $cartonsToProcess = array_merge($passedCartons, $missedCartons);

        $inventoryToProcess = $inventory = [];

        foreach ($cartonsToProcess as $ucc) {
            $inventoryToProcess[$ucc] = isset($inventoryInSystem[$ucc]) ?
                    $inventoryInSystem[$ucc] : $dataPassed['inventory'][$ucc];
        }

        $invIDs = array_values($inventoryToProcess);

        $cartonInfo = $this->getAdjustCartonsInfo($invIDs);

        $discrepantCartons = [];

        foreach ($missedCartons as $ucc) {

            $invID = $discrepantCartons[] = $inventoryToProcess[$ucc];

            $missedCartonInfo[$invID] = $cartonInfo[$invID];
        }

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        $updatedCartonInfo = ! $dataPassed['structured'] ? $cartonInfo :
                $this->updateAllCartons([
                    'passedInfo' => $dataPassed['structured'],
                    'locationData' => $locationData,
                    'statusID' => $rackedStatusID,
                    'cartonInfo' => $cartonInfo,
                    'inventoryToProcess' => $inventoryToProcess,
                    'masterLabels' => $masterLabels,
                ]);

        $this->discrepantCartons($discrepantCartons, $discrepantStatusID);

        foreach ($cartonsToProcess as $ucc) {

            $invID = $inventoryToProcess[$ucc];

            if (isset($missedCartonInfo[$invID])) {
                $updatedCartonInfo[$invID]['newStatusID'] = $discrepantStatusID;
            }
        }

        $this->logAdjustments($updatedCartonInfo);

        $this->app->commit();

        return $return;
    }

    /*
    ****************************************************************************
    */

    function cartonsInSystem($locIDs, $rackedStatusID)
    {
        $params = $locIDs;

        $params[] = $rackedStatusID;

        $ucc128 = $this->fields['ucc128']['select'];

        $qMarks = $this->app->getQMarkString($locIDs);

        $sql = 'SELECT    ' . $ucc128 . ' AS ucc128,
                          ca.id
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                WHERE     locID IN (' . $qMarks . ')
                AND       statusID = ?
                ';

        $results = $this->app->queryResults($sql, $params);

        $return = [];

        foreach ($results as $ucc => $values) {
            $return[$ucc] = $values['id'];
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function updateExtraCartons($data)
    {
        $invIDs = $data['invIDs'];
        $locID = $data['locID'];
        $plate = $data['plate'];
        $statusID = $data['statusID'];

        $plateUpdate = NULL;

        $setParams = [$locID, $locID, $statusID, $statusID];

        if ($plate != 'blankPlate') {

            $setParams[] = $plate;

            $plateUpdate = ', plate = ?';
        }

        $params = array_merge($setParams, $invIDs);

        $qMarks = $this->app->getQMarkString($invIDs);

        $sql = 'UPDATE inventory_cartons
                SET    locID = ?,
                       mLocID = ?,
                       statusID = ?,
                       mStatusID = ?
                       ' . $plateUpdate . '
                WHERE  id IN (' . $qMarks . ')
                ';

        $this->app->runQuery($sql, $params);

        $wavePicks = new \tables\wavePicks($this->app);

        $wavePicks->deactivateByCartonID($invIDs);
    }

    /*
    ****************************************************************************
    */

    function discrepantCartons($invIDs, $statusID)
    {
        if (! $invIDs) {
            return;
        }

        $qMarks = $this->app->getQMarkString($invIDs);

        $sql = 'UPDATE inventory_cartons
                SET    statusID = ?
                WHERE  id IN (' . $qMarks . ')
                ';

        $params = $invIDs;

        array_unshift($params, $statusID);

        $this->app->runQuery($sql, $params);

        $wavePicks = new \tables\wavePicks($this->app);

        $wavePicks->deactivateByCartonID($invIDs);
    }

    /*
    ****************************************************************************
    */

    function updateAllCartons($data)
    {
        $passedInfo = $data['passedInfo'];
        $statusID = $data['statusID'];

        foreach ($passedInfo as $location => $plates) {
            foreach ($plates as $plate => $extraCartons) {

                $data['invIDs'] = [];
                $data['extraCartons'] = $extraCartons;
                $data['location'] = $location;
                $data['plate'] = $plate;

                $data = $this->updateAdjustedCartons($data);

                $this->updateExtraCartons([
                    'invIDs' => getDefault($data['invIDs'], []),
                    'locID' => getDefault($data['locID'], NULL),
                    'plate' => $plate,
                    'statusID' => $statusID
                ]);
            }
        }

        return $data['cartonInfo'];
    }

    /*
    ****************************************************************************
    */

    function updateAdjustedCartons($data)
    {
        $extraCartons = $data['extraCartons'];
        $masterLabels = $data['masterLabels'];

        foreach ($extraCartons as $ucc) {
            if (! isset($masterLabels[$ucc])) {
                // regular UCC (not a Master Label)
                $data = $this->updateAdjustedCartonExecute($data, $ucc);

                continue;
            }
            // Master Label UCC
            foreach ($masterLabels[$ucc] as $ucc) {
                $data = $this->updateAdjustedCartonExecute($data, $ucc);
            }
        }

        return $data;
    }

    /*
    ****************************************************************************
    */

    function updateAdjustedCartonExecute($data, $ucc)
    {
        $inventoryToProcess = $data['inventoryToProcess'];
        $locationData = $data['locationData'];
        $location = $data['location'];
        $plate = $data['plate'];
        $statusID = $data['statusID'];

        $invID = $inventoryToProcess[$ucc];

        $data['invIDs'][] = $invID;
        $data['cartonInfo'][$invID]['newPlate'] = $plate;
        $data['cartonInfo'][$invID]['newStatusID'] = $statusID;
        $data['cartonInfo'][$invID]['newLocID'] = $data['locID'] =
                $locationData[$location]['locID'];

        return $data;
    }

    /*
    ****************************************************************************
    */

    function getAdjustInventory($post)
    {
        $skipCartons = getDefault($post['skipCartons'], []);

        $searcherData = [];

        parse_str($post['searchData'], $searcherData);

        $ajax = new \datatables\ajax($this->app);

        foreach ($searcherData as $key => $value) {
            $ajax->app->post[$key] = $value;
        }

        $dtOptions['queryString'] = TRUE;

        $queryInfo = $ajax->output($this, $dtOptions);

        $ucc128 = $this->fields['ucc128']['select'];

        $sql = 'SELECT    ' . $ucc128 . ' AS ucc128,
                          l.displayName AS locID,
                          s.shortName AS statusID,
                          lm.displayName AS mLocID,
                          sm.shortName AS mStatusID,
                          batchID,
                          ca.cartonID
                FROM      ' . $this->table
                . $queryInfo['clause'];

        $params = getDefault($queryInfo['params'], []);

        $results = $this->app->queryResults($sql, $params);

        $skipKeys = array_flip($skipCartons);

        return array_diff_key($results, $skipKeys);
    }

    /*
    ****************************************************************************
    */

    function checkAdjustScannerInput($scans, $classes)
    {
        $plates = $classes['plates'];
        $locations = $classes['locations'];
        $cartons = $classes['cartons'];
        $scanner = $classes['scanner'];

        $invIDs = $scanAdjust['inventory'] = $errors = [];

        $results = $this->addMezzaninePlate($locations, $scans);

        $isMezzanine = $results['isMezzanine'];

        $scanAdjust =
                $scanner->getTargetCartonsArray($results['scans'], 'location');

        $missingLocations =
                $locations->getMissingLocations($scanAdjust['locations']);

        if ($missingLocations) {
            $errors[] = 'Locations not found:<br>'
                    . implode('<br>', $missingLocations);
        }

        $plateValue = array_keys($scanAdjust['plates']);

        if (! $plates->validPlates($plateValue) && ! $isMezzanine) {
            $errors[] = 'License Plate not found';
        }

        // adding cartons derived from Master Labels
        $scannedCartons = $cartons->masterLabelToCarton($scanAdjust['cartons']);

        $invalidUCCs = $scannedCartons['invalidUCCs'];

        if ($invalidUCCs) {
            $errors[] = 'Cartons not found:<br>' . implode('<br>', $invalidUCCs);
        }

        foreach ($scannedCartons['returnScanArray'] as $uccData) {
            if (! is_array($uccData)) {
                // missing closing Location Name and/or License Plate tag(s)
                continue;
            }

            $invID = key($uccData);
            $ucc = $uccData[$invID];

            $scanAdjust['inventory'][$ucc] = $invIDs[] = $invID;
        }

        $allowMezzanine = TRUE;

        $scanAdjust['locationData'] = $locations->getLocationWarehouses(
                $scanAdjust['locations'], $allowMezzanine
        );

        $masterLabels = $scannedCartons['masterLabels'];

        $checkData = [
            'invalidUCCKeys' => array_flip($invalidUCCs),
            'cartonWarehouse' => $cartons->getCartonWarehouse($invIDs),
            'locationData' => $scanAdjust['locationData'],
            'masterLabels' => $masterLabels,
        ];

        foreach ($scanAdjust['structured'] as $location => $locationData) {
            foreach ($locationData as $plate => $uccs) {

                $checkData['plate'] = $plate;
                $checkData['location'] = $location;
                $checkData['uccs'] = $uccs;

                $checkData = $this->adjustScannerCartonCheck($checkData);
            }
        }

        $structureError =
                getDefault($checkData['checkErrors']['structureError']);
        $duplicateCartonsError =
                getDefault($checkData['checkErrors']['duplicateCartonsError']);
        $warehouseMismatchError =
                getDefault($checkData['checkErrors']['warehouseMismatchError']);

        if ($duplicateCartonsError) {

            array_unshift($duplicateCartonsError, 'Cartons that have been '
                    . 'scanned multiple times:');

            $errors[] = $duplicateCartonsError;
        }

        if ($warehouseMismatchError) {

            array_unshift($warehouseMismatchError, 'Warehouses mismatch:');

            $errors[] = $warehouseMismatchError;
        }

        if ($structureError) {
            $errors[] = $structureError;
        }

        $scanAdjust['cartons'] = $scannedCartons['validUCCData'];
        $scanAdjust['masterLabels'] = $masterLabels;

        return [
            'scanAdjust' => $scanAdjust,
            'errors' => $errors,
        ];
    }

    /*
    ****************************************************************************
    */

    function adjustScannerCartonCheck($data)
    {
        $uccs = $data['uccs'];
        $masterLabels = $data['masterLabels'];

        $data['plateWarehouse'] = NULL;
        $data['warehouseMismatch'] = FALSE;

        unset($data['uccs']);

        foreach ($uccs as $ucc) {

            $masterLabel = getDefault($masterLabels[$ucc]);

            if (! $masterLabel) {

                $data['ucc'] = $ucc;

                $data = $this->adjustScannerCartonCheckExecute($data);

                continue;
            }

            foreach ($masterLabel as $ucc) {

                $data['ucc'] = $ucc;

                $data = $this->adjustScannerCartonCheckExecute($data);
            }
        }

        return $data;
    }

    /*
    ****************************************************************************
    */

    function adjustScannerCartonCheckExecute($data)
    {
        $cartonWarehouse = $data['cartonWarehouse'];
        $locationData = $data['locationData'];
        $plateWarehouse = $data['plateWarehouse'];
        $ucc = $data['ucc'];
        $location = $data['location'];
        $plate = $data['plate'];
        $warehouseMismatch = $data['warehouseMismatch'];
        $checkErrors = getDefault($data['checkErrors'], []);
        $invalidUCCKeys = $data['invalidUCCKeys'];
        $checkedCartons = getDefault($data['checkedCartons'], []);

        $invalidUCC = isset($invalidUCCKeys[$ucc]);
        $checkedUCC = isset($checkedCartons[$ucc]);
        $locationError = ! isset($cartonWarehouse[$ucc])
                || ! isset($locationData[$location]);

        if ($locationError || $invalidUCC || $checkedUCC) {
            if ($locationError && ! isset($checkErrors['structureError'])) {
                $checkErrors['structureError'][] = 'Input scanned has missing '
                        . 'closing Location Name and/or License Plate tag(s)';
            } elseif ($checkedUCC) {
                // UCC was scanned multiple times within different locations/plates
                $checkErrors['duplicateCartonsError'][] = $ucc;
            }

            $data['checkErrors'] = $checkErrors;

            return $data;
        }

        $checkedCartons[$ucc] = TRUE;

        $uccWarehouse = $cartonWarehouse[$ucc];

        $differentWarehouse = $plateWarehouse != $uccWarehouse;

        if ($plateWarehouse && ! $warehouseMismatch && $differentWarehouse) {

            $checkErrors['warehouseMismatchError'][] = 'License Plate '
                    . $plate . ' at Location "' . $location
                    . '" has cartons that belong to different warehouses';

            $warehouseMismatch = TRUE;
        }

        $finalPlateWarehouse = $plateWarehouse ? $plateWarehouse : $uccWarehouse;

        $warehouseKeys = array_flip($locationData[$location]['warehouses']);

        if (! isset($warehouseKeys[$uccWarehouse])) {
            $checkErrors['warehouseMismatchError'][] = 'UCC ' . $ucc
                    . ' warehouse does not match location "' . $location
                    . '" warehouse';
        }

        $data['plateWarehouse'] = $finalPlateWarehouse;
        $data['warehouseMismatch'] = $warehouseMismatch;
        $data['checkErrors'] = $checkErrors;
        $data['checkedCartons'] = $checkedCartons;

        return $data;
    }

    /*
    ****************************************************************************
    */

    function addMezzaninePlate($locations, $scans)
    {
        $isMezzanine = FALSE;

        $first = reset($scans);

        $result = $locations->search([
            'term' => $first,
            'search' => 'displayName',
            'oneResult' => TRUE,
            'addFields' => ['isMezzanine']
        ]);

        if ($result['isMezzanine']) {
            $isMezzanine = TRUE;
            $scanCount = count($scans);
            array_splice($scans, 1, 0, ['blankPlate']);
            array_splice($scans, $scanCount, 0, ['blankPlate']);
        }

        return [
            'scans' => $scans,
            'isMezzanine' => $isMezzanine
        ];
    }

    /*
    ****************************************************************************
    */
}
