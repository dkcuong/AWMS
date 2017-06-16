<?php

namespace tables\warehouseTransfers;

use \tables\inventory\cartons;

class warehouseTransferPallets extends \tables\_default
{
    public $primaryKey = 'wtp.id';

    public $ajaxModel = 'warehouseTransfers\\warehouseTransferPallets';

    public $fields = [
        'vendor' => [
            'select' => 'vendorName',
            'display' => 'Client NAme',
            'searcherDD' => 'vendors',
            'ddField' => 'vendorName',
        ],
        'warehouseTransfer' => [
            'display' => 'Warehouse Transfer',
            'select' => 'wt.description',
            'searcherDD' => 'warehouseTransfers\\warehouseTransfers',
            'ddField' => 'wt.description',
        ],
        'manifest' => [
            'display' => 'Manifest',
        ],
        'plate' => [
            'display' => 'License Plate',
            'acDisabled' => TRUE,
        ],
        'recNum' => [
            'select' => 'co.recNum',
            'display' => 'Receiving Number',
        ],
        'name' => [
            'display' => 'Container',
        ],
        'sts' => [
            'display' => 'Plate Status',
        ],
        'outTime' => [
            'display' => 'Time Out',
        ],
        'inTime' => [
            'display' => 'Time In',
        ],
        'outUserID' => [
            'select' => 'ou.username',
            'display' => 'Out User',
            'searcherDD' => 'users',
            'ddField' => 'username',
        ],
        'inUserID' => [
            'select' => 'iu.username',
            'display' => 'In User',
            'searcherDD' => 'users',
            'ddField' => 'username',
        ],
    ];

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'warehouse_transfer_pallets wtp
            JOIN      warehouse_transfers wt ON wt.id = wtp.warehouseTransferID
            JOIN      inventory_containers co ON co.recNum = wtp.recNum
            JOIN      vendors v ON v.id = co.vendorID
            LEFT JOIN '.$userDB.'.info iu ON iu.id = wtp.inUserID
            JOIN      '.$userDB.'.info ou ON ou.id = wtp.outUserID
            ';
    }

    /*
    ****************************************************************************
    */

    function create($warehouseTransferID, $manifestPlates)
    {
        $userID = \access::getUserID();

        $sql = 'INSERT INTO warehouse_transfer_pallets (
                    warehouseTransferID,
                    manifest,
                    plate,
                    recNum,
                    outUserID,
                    outVendorID
                ) VALUES (
                    ?, ?, ?,
                    ?, ?, ?
                )';

        $this->app->beginTransaction();

        foreach ($manifestPlates as $manifest => $licensePlates) {
            foreach ($licensePlates as $plate => $plateData) {
                $this->app->runQuery($sql, [
                    $warehouseTransferID,
                    $manifest,
                    $plate,
                    $plateData['recNum'],
                    $userID,
                    $plateData['vendorID'],
                ]);
            }
        }

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function updateTransfer($transferID, $warehouseIDs, $plateLocations)
    {
        $cartons = new cartons($this->app);
        $locations = new \tables\locations($this->app);

        $warehouseID = $warehouseIDs['inWarehouseID'];

        $licensePlates = array_keys($plateLocations);

        $plateVendors = $this->updatePlateVendors($licensePlates, $warehouseIDs);

        $this->updatePlateContainers($plateVendors);

        $transferInventory = $cartons->getWarehouseTransferCartons($licensePlates);

        $locationValues = array_values($plateLocations);

        $uniqueLocations = array_unique($locationValues);

        $locResults = $locations->getByName($uniqueLocations, $warehouseID);

        $transferData = $invIDs = [];

        foreach ($transferInventory as $invID => $values) {

            $plate = $values['plate'];

            $locationName = $plateLocations[$plate];

            if (! isset($transferData[$plate])) {
                $transferData[$plate] = [
                    'count' => 0,
                    'cartonNumbers' => [],
                    'locationInfo' => [
                        'locID' => $locResults[$locationName],
                        'locationName' => $locationName,
                        'warehouseID' => $warehouseID
                    ],
                ];
            }

            $transferData[$plate]['count']++;

            $transferData[$plate]['cartonNumbers'][] = [
                $invID => TRUE,
            ];

            $invIDs[] = $invID;
        }

        $cartons->inventoryTransfer($transferData, $invIDs, [
            'plateVendors' => $plateVendors,
            'transferID' => $transferID,
        ]);
    }

    /*
    ****************************************************************************
    */

    function updateWarehouseTransfer($plateVendors, $transferID)
    {
        $userID = \access::getUserID();
        // already in a transaction
        foreach ($plateVendors as $values) {

            $params = $values['plates'];

            $qMarks = $this->app->getQMarkString($params);

            $sql = 'UPDATE    warehouse_transfer_pallets
                    SET       sts = "' . cartons::STATUS_INACTIVE . '",
                              inUserID = ?,
                              inVendorID = ?
                    WHERE     warehouseTransferID = ?
                    AND       plate IN (' . $qMarks . ')';

            array_unshift($params, $userID, $values['inboundID'], $transferID);

            $this->app->runQuery($sql, $params);
        }
    }

    /*
    ****************************************************************************
    */

    function checkPlates($licensePlates)
    {
        if (! $licensePlates) {
            return [];
        }

        $params = [];

        foreach ($licensePlates as $plateData) {

            $checkPlates = array_keys($plateData);

            $params = array_merge($checkPlates, $params);
        }

        $qMarks = $this->app->getQMarkString($params);

        $sql = 'SELECT    plate
                FROM      warehouse_transfer_pallets
                WHERE     plate IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $params);

        return $results ? array_keys($results) : [];
    }

    /*
    ****************************************************************************
    */

    function checkTransferPlates($manifest, $scannedPlates, $transferID)
    {
        $warehouseTransfers = new warehouseTransfers($this->app);

        $warehouseIDs = $warehouseTransfers->getWarehouseIDs($transferID);

        $errrorWarehouses = $errrorPlates = $licensePlates = $errors = [];

        $plates = new \tables\plates($this->app);

        $results = $plates->getPlateData($scannedPlates);

        $validPlates = array_keys($results);

        $invalidPlates = array_diff($scannedPlates, $validPlates);

        if ($invalidPlates) {
            $errors[] = 'Invalid License Plates or empty:<br>'
                    . implode('<br>', $invalidPlates);
        }

        foreach ($results as $plate => $values) {
            if ($values['warehouseID'] != $warehouseIDs['outWarehouseID']) {

                $errrorWarehouses[] = $plate;

                continue;
            }

            $licensePlates[$manifest][$plate] = [
                'recNum' => $values['recNum'],
                'vendorID' => $values['vendorID'],
            ];
        }

        foreach ($results as $plate => $values) {
            if ($values['shortName'] != cartons::STATUS_RACKED) {

                $errrorPlates[] = $plate;

                continue;
            }

            $licensePlates[$manifest][$plate] = [
                'recNum' => $values['recNum'],
                'vendorID' => $values['vendorID'],
            ];
        }

        if ($errrorWarehouses) {
            $errors[] = 'License Plates that do not belong to the outbound '
                    . 'warehouse:<br>' . implode('<br>', $errrorWarehouses);
        }

        if ($errrorPlates) {
            $errors[] = 'License Plates with cartons that have statuses '
                    . 'different from "' . cartons::STATUS_RACKED . '":<br>'
                    . implode('<br>', $errrorPlates);
        }

        $outboundPlates = $this->checkPlates($licensePlates);

        if ($outboundPlates) {
            $errors[] = 'Duplicate outbound plates:<br>'
                    . implode('<br>', $outboundPlates);
        }

        return [
           'errors' => $errors,
           'plates' => $licensePlates
        ];
    }

    /*
    ****************************************************************************
    */

    function getTransferPlates($licensePlates)
    {
        if (! $licensePlates) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($licensePlates);

        $sql = 'SELECT    id,
                          warehouseTransferID AS transferID,
                          plate,
                          sts
                FROM      warehouse_transfer_pallets
                WHERE     plate IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $licensePlates);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkInboundPlates($transferID, $warehouseID, $scanValues)
    {
        $locationValues = $warehouseTransfer = $inTransfer = $outTransfer =
                $errors = $locErrors = $duplicateLocation = [];

        while (! empty($scanValues)) {
            $scannedPlates[] = array_shift($scanValues);
            $locationValues[] = array_shift($scanValues);
        }

        if (! $scannedPlates) {
            return [];
        }

        $countPlateValues = count($scannedPlates);
        $countLocationValues = count($locationValues);

        //check plate and location count match
        if ($countPlateValues != $countLocationValues) {
            $errors[] = 'Plate count does not match with Location count';
        }

        $uniqueLocations = array_unique($locationValues);

        $checkLocations = array_values($uniqueLocations);

       //check the locations belongs to IN warehouse
        $locations = new \tables\locations($this->app);

        $existingLocResults = $locations->checkWarehouseLocation($checkLocations,
                $warehouseID);

        $existingLocations = array_keys($existingLocResults);

        $missingLocations = array_diff($checkLocations, $existingLocations);

        if ($missingLocations) {
            return [
                'errors' => [
                    'Locations missing in the inbound warehouse:<br>'
                            . implode('<br>', $missingLocations)
                ],
            ];
        }

        $duplicateLocation = array_diff_key($locationValues, $uniqueLocations);

        //check duplicate Location
        if($duplicateLocation) {
            return [
                'errors' => [
                    'Duplicate Locations:<br>'
                            . implode('<br>', $duplicateLocation)
                ],
            ];
        }

        //get both location and plates in an array
        $scanResults = array_combine($scannedPlates, $locationValues);

        $results = $this->getTransferPlates($scannedPlates);

        $platesResults = array_column($results, 'plate');

        $diffPlates = array_diff($scannedPlates, $platesResults);

        if ($diffPlates) {
            return [
                'errors' => [
                    'Missing outbound plates:<br>'
                            . implode('<br>', $diffPlates)
                ]
            ];
        }

        foreach ($results as $row) {

            $plate = $row['plate'];
            $location = $scanResults[$plate];

            if ($row['transferID'] != $transferID) {

                $warehouseTransfer[] = $plate;

                continue;
            }

            if ($row['sts'] == cartons::STATUS_INACTIVE) {

                $inTransfer[] = $plate;

                continue;
            }

            $outTransfer[$plate] = $location;
        }

        if ($warehouseTransfer) {
            $errors[] = 'Other Warehouse Transfer Plates:<br>'
                    . implode('<br>', $warehouseTransfer);
        }

        if ($inTransfer) {
            $errors[] = 'Inbound Transfer Plates:<br>'
                    . implode('<br>', $inTransfer);
        }

        return [
           'errors' => $errors,
           'outTransferPlates' => $outTransfer
        ];
    }

    /*
    ****************************************************************************
    */

    function updatePlateVendors($locationPlates, $warehouseIDs)
    {
        if (! $locationPlates || ! $warehouseIDs) {
            return [];
        }

        $plates = new \tables\plates($this->app);
        $vendors = new \tables\vendors($this->app);

        $plateVendors = $plates->getPlateVendors($locationPlates, $warehouseIDs);

        $outboundVendors = [];

        foreach ($plateVendors as $vendorID => $vendorData) {

            unset($vendorData['plates']);

            $vendorData['vendorID'] = $vendorID;

            $outboundVendors[] = $vendorData;
        }

        $inboundVendors = $vendors->verifyVendors($outboundVendors, $warehouseIDs);

        foreach ($plateVendors as &$vendorData) {

            $vendorName = $vendorData['vendorName'];

            $vendorData['inboundID'] = $inboundVendors[$vendorName];
        }

        return $plateVendors;
    }

    /*
    ****************************************************************************
    */

    function updatePlateContainers($plateVendors)
    {
        if (! $plateVendors) {
            return FALSE;
        }

        foreach ($plateVendors as $values) {

            $params = $values['plates'];

            $qMarks = $this->app->getQMarkString($params);

            $sql = 'UPDATE    inventory_containers co
                    JOIN      inventory_batches b ON b.recNum = co.recNum
                    JOIN      inventory_cartons ca ON ca.batchID = b.id
                    SET       co.vendorID = ?
                    WHERE     plate IN (' . $qMarks . ')';

            array_unshift($params, $values['inboundID']);

            $this->app->runQuery($sql, $params);
        }
    }

    /*
    ****************************************************************************
    */

}