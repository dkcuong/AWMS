<?php

namespace tables;

class minMax extends _default
{
    public $primaryKey = 'mm.id';

    public $ajaxModel = 'minMax';

    public $fields = [
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'update' => 'mm.vendorID',
            'noEdit' => TRUE,
        ],
        'location_name' => [
            'select' => 'l.displayName',
            'display' => 'Location Name',
            'noEdit' => TRUE,
            'required' => TRUE,
        ],
        'upc' => [
            'select' => 'u.upc',
            'display' => 'UPC',
            'noEdit' => TRUE,
            'required' => TRUE,
        ],
        'sku' => [
            'select' => 'u.sku',
            'display' => 'SKU',
            'noEdit' => TRUE,
        ],
        'min_count' => [
            'select' => 'minCount',
            'display' => 'Min Count',
            'isNum' => 'unl',
            'min' => 1,
            'max' => 99999999,
            'required' => TRUE,
            'lengthLimit' => 8,
            'isPositive' => TRUE,
        ],
        'max_count' => [
            'select' => 'maxCount',
            'display' => 'Max Count',
            'isNum' => 'unl',
            'min' => 1,
            'max' => 99999999,
            'required' => TRUE,
            'lengthLimit' => 8,
            'isPositive' => TRUE,
        ],
        'active' => [
            'select' => 'IF(mm.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'mm.active',
            'updateOverwrite' => TRUE,
        ]
    ];

    public $table = 'min_max mm
        JOIN      inventory_batches b ON b.upcID = mm.upcID
        JOIN      inventory_containers co ON co.recNum = b.recNum
        JOIN      vendors v ON v.id = co.vendorID
        JOIN      warehouses w ON v.warehouseID = w.id
        JOIN      locations l ON l.id = mm.locID
        JOIN      upcs u ON u.id = mm.upcID';

    public $where = 'v.active
                     AND  u.active and l.warehouseID = w.id';

    public $groupBy = 'u.id, l.id';

    public $mainField = 'mm.id';

    public $errorDescription = [
        'invalidLocations' => [
            'captionSuffix' => 'with invalid Location Names:'
        ],
        'duplicateLocationUpc' => [
            'captionSuffix' => 'with duplicate UPC / Location Name:'
        ],
    ];

    public $errorFile = [
        'invalidUPCs' => [
            'captionSuffix' => 'with invalid UPCs:'
        ],
        'warehouseMismatch' => [
            'captionSuffix' => 'with UPC / Location Names warehouses mismatch at rows:'
        ],
        'minMaxMismatch' => [
            'captionSuffix' => 'with quantity mismatch at rows:'
        ],
        'unknownError' => [
            'captionSuffix' => 'with unknown error at rows:'
        ]
    ];

    /*
    ****************************************************************************
    */

    function checkMinMaxInput($data)
    {
        $vendorID = $data['vendorID'];
        $minCount = $data['minCount'];
        $maxCount = $data['maxCount'];

        $return = [];

        $this->checkMinMaxValues($return, $minCount, $maxCount);

        if (! isset($data['location'])) {
            // check for minCount / maxCount input values only
            return $return;
        }

        $location = $data['location'];
        $category = $data['category'];
        $categoryValue = $data['value'];
        $color = $data['color'];
        $size = $data['size'];

        $locations = new locations($this->app);
        $upcs = new upcs($this->app);
        $cartons = new inventory\cartons($this->app);

        if (! $location) {
            $return['errors'][] = 'Missing Location Name';
        } else {

            $result = $locations->checkMinMaxLocation($location, $vendorID);

            if (! $result) {
                $return['errors'][] = 'Invalid Location Name';
            } else {

                $this->checkAmbiguousVendors($return, [
                    'cartons' => $cartons,
                    'location' => $location,
                    'vendorID' => $vendorID
                ]);

                $this->checkAmbiguousRange($return, $location, $vendorID);
            }
        }

        if (! in_array($category, ['UPC', 'SKU'])) {
            $return['errors'][] = 'Invalid category "' . $category . '". '
                    . 'Should be UPC or SKU';
        } elseif (! $categoryValue) {
            $return['errors'][] = 'Missing ' . $category;
        } else {

            $primary = [
                'assoc' => 'id',
                'field' => $upcs->primaryKey
            ];

            $results = $upcs->valid($categoryValue, $category, $primary);

            if (! $results['valid']) {
                $return['errors'][] = 'Invalid ' . $category;
            } else {

                $params = [
                    'field' => $category,
                    'values' => [
                        $categoryValue,
                    ],
                    'joinClause' => 'JOIN      upcs u ON u.id = b.upcID',
                    'vendorID' => $vendorID,
                ];

                if ($category == 'SKU') {
                    $params['whereClause'] = 'color = ? AND size = ?';
                    $params['whereParams'] = [$color, $size];
                }

                $results = $cartons->getAmbiguousVendors([$params]);

                $text = $category;

                $text .= $category == 'SKU' ? ' / COLOR / SIZE' : NULL;

                foreach ($results as $value) {
                    $return['errors'][] = 'Selected ' . $text . ' is '
                            . 'associated with ' . $value['fullVendorName'];
                }
            }
        }

        $results = $this->checkIfExists($data);

        if ($results) {
            if ($results['active']) {
                $return['warning'][] = 'Input combination of Location Name and '
                        . $category . ' exists and will be updated. Proceed?';
            } else {
                $return['warning'][] = 'Input combination of Location Name and '
                        . $category . ' is marked as inactive. It will be set '
                        . 'back to active and updated. Proceed?';
            }
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function checkMinMaxValues(&$return, $minCount, $maxCount)
    {
        $minField = $this->fields['min_count'];
        $maxField = $this->fields['max_count'];

        if ($minCount < $minField['min'] || $minCount > $minField['max']) {
            $return['errors'][] = 'Invalid Min Count';
        }

        if ($maxCount < $maxField['min'] || $maxCount > $maxField['max']) {
            $return['errors'][] = 'Invalid Max Count';
        }

        if ($maxCount < $minCount) {
            $return['errors'][] = 'Min Count can not be greater than Max Count';
        }
    }

    /*
    ****************************************************************************
    */

    function checkIfExists($data)
    {
        $location = $data['location'];
        $category = $data['category'];
        $value = $data['value'];

        $sql = 'SELECT    mm.id,
                          mm.active
                FROM      min_max mm
                JOIN      locations l ON l.id = mm.locID
                JOIN      upcs u ON u.id = mm.upcID
                WHERE     l.displayName = ?
                AND       ' . $category . ' = ?
                ';

        $result = $this->app->queryResult($sql, [$location, $value]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function updateMinMax($data)
    {
        $location = $data['location'];
        $vendorID = $data['vendorID'];
        $category = $data['category'];
        $categoryValue = $data['value'];
        $color = $data['color'];
        $size = $data['size'];
        $minCount = $data['minCount'];
        $maxCount = $data['maxCount'];

        $locations = new locations($this->app);
        $upcs = new upcs($this->app);

        $locationNames = $locations->getLocationDisplayName([
            'locs' => [$location],
            'vendorID' => $vendorID,
            'param' => [],
            'locsNoHyphens' => [$location],
            'clause' => 1
        ]);

        $locationName = reset($locationNames);

        $locID = $locationName['id'];

        if ($category == 'UPC') {

            $upcData = $upcs->getUpcs([$categoryValue]);

            $locationName = reset($upcData);

            $upcID = $locationName['id'];
        } else {
            $upcID = $upcs->getBySkuColorSize($categoryValue, $color, $size);
        }

        $sql = 'INSERT INTO min_max (
                    locID,
                    upcID,
                    minCount,
                    maxCount
                ) VALUES (
                    ?, ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    minCount = ?,
                    maxCount = ?,
                    active = 1
                ';

        $params = [$locID, $upcID, $minCount, $maxCount, $minCount, $maxCount];

        $this->app->runQuery($sql, $params);

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function updateClientMinMax($data)
    {
        $vendorID = $data['vendorID'];
        $minCount = $data['minCount'];
        $maxCount = $data['maxCount'];

        $minMaxSql = '
                UPDATE    min_max mm
                JOIN      inventory_batches b ON b.upcID = mm.upcID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                SET       minCount = ?,
                          maxCount = ?
                WHERE     co.vendorID = ?
                ';

        // do NOT restore min_max_ranges active field as it can influence ranges
        $rangeSql = '
                UPDATE    min_max_ranges
                SET       minCount = ?,
                          maxCount = ?
                WHERE     vendorID = ?';

        $params = [$minCount, $maxCount, $vendorID];

        $this->app->beginTransaction();

        $this->app->runQuery($minMaxSql, $params);
        $this->app->runQuery($rangeSql, $params);

        $this->app->commit();

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function getMinMaxInputs()
    {
        $fields = $this->fields;

        ob_start(); ?>

        Min Count <input type="number" id="locationMin"
                         min="<?php echo $fields['min_count']['min']; ?>"
                         max="<?php echo $fields['min_count']['max']; ?>">
        Max Count <input type="number" id="locationMax"
                         min="<?php echo $fields['max_count']['min']; ?>"
                         max="<?php echo $fields['max_count']['max']; ?>">

        <?php

        return ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function getAmbiguousLocations($vendorID, $startLocation, $endLocation)
    {
        $sql = 'SELECT    l.displayName,
                          CONCAT(w.shortName, "_", vendorName) AS fullVendorName
                FROM      locations l
                JOIN      inventory_cartons ca ON ca.locID = l.id
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                WHERE     co.vendorID != ?
                AND       l.displayName BETWEEN ? AND ?';

        $params = [$vendorID, $startLocation, $endLocation];

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkAmbiguousVendors(&$return, $data)
    {
        $cartons = $data['cartons'];
        $location = $data['location'];
        $vendorID = $data['vendorID'];

        $params = [
            'field' => 'l.displayName',
            'values' => [
                $location,
            ],         
            'joinClause' => 'JOIN      locations l ON l.id = ca.locID',
            'vendorID' => $vendorID,
        ];
        
        $results = $cartons->getAmbiguousVendors([$params]);

        foreach ($results as $value) {
            $return['errors'][] = 'Selected Location Name is associated '
                    . 'with ' . $value['fullVendorName'];
        }
    }

    /*
    ****************************************************************************
    */

    function checkAmbiguousRange(&$return, $location, $vendorID)
    {
        $minMaxRanges = new minMaxRanges($this->app);

        $results = $minMaxRanges->getAmbiguousRange($vendorID, $location);

        foreach ($results as $result) {
            $return['errors'][] = 'Input Location intersects a range "'
                    . $result['range'] . '" associated with '
                    . $result['fullVendorName'];
        }
    }

    /*
    ****************************************************************************
    */

    function insertFile()
    {
        $this->errors = $upcRows = $locationRows = $submittedUpcs =
                $submittedLocations = $upcLocation = $insertValues = [];

        // Loop through each row of the worksheet in turn
        foreach ($this->importData as $rowIndex => $rowData) {
            if ($rowIndex == 1) {

                $this->handleColumnTitles($rowData);

                \excel\importer::checkTableErrors($this);

                if ($this->errors) {
                    return;
                }

                $this->getColumnKeys($rowData);

                continue;
            }

            // No blank rows
            if (! \array_filter($rowData)) {
                continue;
            }

            $upc = $rowData[$this->upcKey];
            $location = $rowData[$this->locationKey];

            $upcRows[$upc][] = $rowIndex;
            $locationRows[$location][] = $rowIndex;

            $key = $upc . ' / ' . $location;

            $upcLocation[$key][] = TRUE;
        }

        unset($this->importData[1]);

        $submittedUpcs = array_keys($upcRows);
        $submittedLocations = array_keys($locationRows);

        $ucps = new \tables\upcs($this->app);
        $locations = new \tables\locations($this->app);

        $mezzanineLocations =
                $locations->getMezzanineLocationsData($submittedLocations);
        $upcWarehouses = $ucps->getUpcsWarehouses($submittedUpcs);

        $locationsWarehouses = [];

        foreach ($mezzanineLocations as $locID => $values) {

            $location = $values['displayName'];
            $warehouseID = $values['warehouseID'];

            $locationsWarehouses[$location][] = $warehouseID;
        }

        foreach ($this->importData as $rowIndex => $rowData) {

            \excel\importer::checkCellErrors([
                'model' => $this,
                'rowData' => $rowData,
                'rowIndex' => $rowIndex,
            ]);

            $upc = $rowData[$this->upcKey];
            $location = $rowData[$this->locationKey];
            $minCount = $rowData[$this->minCountKey];
            $maxCount = $rowData[$this->maxCountKey];

            if ($minCount > $maxCount) {
                $this->errors['minMaxMismatch'][$rowIndex][] = TRUE;
            }

            $key = $upc . ' / ' . $location;

            if (! isset($upcLocation[$key])) {
                $this->errors['duplicateLocationUpc'][$rowIndex][] = $key;
            }

            unset($upcLocation[$key]);

            $locationWarehouses = getDefault($locationsWarehouses[$location]);

            if ($location && ! $locationWarehouses) {
                $this->errors['invalidLocations'][$rowIndex][] = $location;
            }

            $upcWarehouse = getDefault($upcWarehouses[$upc]);

            if (! $upcWarehouse) {
                $this->errors['invalidUPCs'][$upc][] = TRUE;
            }

            if (! $locationWarehouses || ! $upcWarehouse) {
                continue;
            }

            $this->errors['warehouseMismatch'][$rowIndex][] = TRUE;

            foreach ($locationWarehouses as $warehouseID) {
                if ($warehouseID == $upcWarehouse) {

                    unset($this->errors['warehouseMismatch'][$rowIndex]);

                    break;
                }
            }

            $insertValues[$key] = [
                'upc' => $upc,
                'location' => $location
            ];
        }

        if (! isset($rowData)) {
            // the file has proper extension, but actually is not an Excel file
            return $this->errors['wrongType'] = TRUE;
        }

        if (! getDefault($this->errors['warehouseMismatch'])) {
            unset($this->errors['warehouseMismatch']);
        }

        if ($insertValues && ! $this->errors) {

            $results = $this->getUpcLocationVendor($insertValues);

            $this->checkRowConsistency($results, $insertValues);
        }

        if (! $this->errors) {

            $sql = 'INSERT INTO min_max (
                    locID,
                    upcID,
                    minCount,
                    maxCount
                ) VALUES (
                    ?, ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    minCount = ?,
                    maxCount = ?,
                    active = 1
                ';

            $this->app->beginTransaction();

            foreach ($this->importData as $rowIndex => $rowData) {

                $upc = $rowData[$this->upcKey];
                $location = $rowData[$this->locationKey];
                $minCount = $rowData[$this->minCountKey];
                $maxCount = $rowData[$this->maxCountKey];

                $key = $upc . ' / ' . $location;

                $this->app->runQuery($sql, [
                    $results[$key]['locID'],
                    $results[$key]['upcID'],
                    $minCount,
                    $maxCount,
                    $minCount,
                    $maxCount
                ]);
            }

            $this->app->commit();
        }
    }

    /*
    ************************************************************************
    */

    function getColumnKeys($rowData)
    {
        $fieldKeys = array_flip($rowData);

        $this->upcKey = $fieldKeys['upc'];
        $this->locationKey = $fieldKeys['location_name'];
        $this->minCountKey = $fieldKeys['min_count'];
        $this->maxCountKey = $fieldKeys['max_count'];
    }

    /*
    ************************************************************************
    */

    function getUpcLocationVendor($data)
    {
        if (! $data) {
            return [];
        }

        foreach ($data as $values) {

            $params[] = $values['upc'];
            $params[] = $values['location'];
        }

        $clauses = array_fill(0, count($data), 'upc = ? AND l.displayName = ?');

        $sql = 'SELECT    CONCAT(upc, " / ", l.displayName),
                          l.id AS locID,
                          upcID
                FROM      upcs u
                JOIN      inventory_batches b ON b.upcID = u.id
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      vendors v ON v.id = co.vendorID
                JOIN      locations l ON l.warehouseID = v.warehouseID
                WHERE     ' . implode(' OR ', $clauses);

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ************************************************************************
    */

    function checkRowConsistency($results, $insertValues)
    {
        if (count($results) != count($insertValues)) {

            $keys = array_keys($insertValues);

            $count = 0;

            foreach ($keys as $key) {

                $count++;

                if (! isset($results[$key])) {
                    $this->errors['unknownError'][$count][] = TRUE;
                }
            }
        }
    }

    /*
    ************************************************************************
    */

    function importSuccess()
    {
        \locations\minMax::importSuccessHTML();
    }

    /*
    ************************************************************************
    */

    function update($columnID, &$value, $rowID, $ajaxRequest=FALSE)
    {
        $fieldIDs = array_keys($this->fields);
        $field = getDefault($fieldIDs[$columnID]);
        $fieldInfo = $this->fields[$field];
        
        // Get custom ajax error for field if available
        $fieldUpdateError = getDefault($this->fields[$field]['updateError']);
        $updateError = $fieldUpdateError ? $fieldUpdateError : $ajaxRequest;

        $isValidate = $this->validateDataInputUpdate([
            'fieldInfo' => $fieldInfo,
            'value' => $value,
        ]);

        if (! $isValidate) {
            echo $this->errorMsg;
            return FALSE;
        }

        if (getDefault($fieldInfo['isDecimal'])) {
            $newValue = ceil($value * 4) / 4;
        }

        $updateOverwrite = getDefault($fieldInfo['updateOverwrite']);

        $updateField = $overwriteField = getDefault(
                $fieldInfo['update'],
                $field
        );

        $updateFieldSelect = isset($this->fields[$updateField]['select']) ?
                $this->fields[$updateField]['select'] : NULL;

        $updateField = isset($updateFieldSelect) ?
                $updateFieldSelect : $updateField;

        if (! $updateField) {
            return 'Field not found';
        }

        $previous = NULL;
        $queryValue = isset($newValue) ? $newValue : $value;
                
        $whereClause = $this->primaryKey.' = ?';
        $params = [$value,$rowID];
  
        $updateField = $updateOverwrite ? $overwriteField : $updateField;        
             
        $previous = $this->getPreviousValueUpdateHaveGroupBy([
                'updateField' => $updateField,
                'whereClause' => $whereClause,
                'sqlParams' => [$rowID],
            ]);
   
        if ($previous != $value) {
            $sql = 'UPDATE  ' . $this->table . '
                    SET     ' . $updateField . ' = ?
                    WHERE   ' . $whereClause;

        
            $this->app->runQuery($sql, $params, $updateError);
        }
   
        if ($previous != $queryValue) {
            history::addUpdate([
                'model' => $this,
                'field' => $field,
                'rowID' => $rowID,
                'toValue' => $value,
                'fromValue' => $previous,
            ]);
        }

        return TRUE;
    }

    /*
    ************************************************************************
    */

}