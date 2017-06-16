<?php

namespace common;

use access;
use tables\receiving;

class seldatContainers
{
    static $post = NULL;
    static $measurement = NULL;
    static $tableData = [];
    static $containers = NULL;
    static $cartons = NULL;
    static $upcs = NULL;
    static $receiving = NULL;
    static $statuses = NULL;
    static $upcsCategories = NULL;
    static $containerUpcData = [];
    static $changedBathes = [];
    static $batchPerLine = [];
    static $errors = [];
    static $rejectUOM = [];

    const UPC_ACTIVE = 1;
    const UPC_INACTIVE = 0;

    static $headerCells = [
        'measurementSystem' => [
            'cellName' => 'Measurement System',
            'minWidth' => 1,
        ],
        'container' => [
            'cellName' => 'Container #',
            'text' => TRUE,
            'minWidth' => 1,
        ],
        'receiving' => [
            'cellName' => 'Receiving'
        ]
    ];

    static $tableCells = [
        'rowNo' => [
            'class' => 'firstCol',
            'cellName' => NULL,
        ],
        'tableFunc' => [
            'cellName' => '#',
        ],
        'categoryUPC' => [
            'cellName' => NULL,
        ],
        'upc' => [
            'class' => 'red',
            'cellName' => 'UPC',
            'size' => 12,
            'maxValue' => 999999999999,
            'minWidth' => 8,
            'maxWidth' => 13,
        ],
        'newUPC' => [
            'cellName' => 'NEW UPC',
        ],
        'sku' => [
            'class' => 'red',
            'cellName' => 'STYLE NO',
            'size' => 7,
            'minWidth' => 1,
            'maxWidth' => 45,
            'text' => TRUE,
        ],
        'suffix' => [
            'cellName' => 'SUFFIX',
            'size' => 7,
            'text' => TRUE,
        ],
        'size1' => [
            'class' => 'red',
            'cellName' => 'SIZE',
            'size' => 7,
            'minWidth' => 1,
            'maxWidth' => 45,
            'text' => TRUE,
        ],
        'color1' => [
            'class' => 'red',
            'cellName' => 'COLOR',
            'size' => 7,
            'minWidth' => 1,
            'maxWidth' => 45,
            'text' => TRUE,
        ],
        'uom' => [
            'class' => 'red',
            'cellName' => 'UOM',
            'size' => 5,
            'minValue' => 1,
            'maxValue' => 999,
            'minWidth' => 1,
            'inputRel' => 'short'
        ],
        'carton' => [
            'class' => 'red',
            'cellName' => '# CARTON',
            'size' => 8,
            'minValue' => 1,
            'maxValue' => 9999,
            'inputRel' => 'short'
        ],
        'prefix' => [
            'class' => 'red',
            'cellName' => 'CLIENT PO',
            'size' => 7,
            'minWidth' => 1,
            'maxWidth' => 45,
            'text' => TRUE,
        ],
        'length' => [
            'class' => 'red',
            'cellName' => 'LENGTH',
            'dimension' => 'length',
            'size' => 7,
            'decimals' => 1,
            'title' => 'LENGTH',
            'colTitle' => 'L',
            'inputRel' => 'short'
        ],
        'width' => [
            'class' => 'red',
            'cellName' => 'WIDTH',
            'dimension' => 'width',
            'size' => 7,
            'decimals' => 1,
            'title' => 'WIDTH',
            'colTitle' => 'W',
            'inputRel' => 'short'
        ],
        'height' => [
            'class' => 'red',
            'cellName' => 'HEIGHT',
            'dimension' => 'height',
            'size' => 7,
            'decimals' => 1,
            'title' => 'HEIGHT',
            'colTitle' => 'H',
            'inputRel' => 'short'
        ],
        'weight' => [
            'class' => 'red',
            'cellName' => 'WEIGHT',
            'dimension' => 'weight',
            'size' => 7,
            'decimals' => 1,
            'title' => 'WEIGHT',
            'colTitle' => 'We',
            'inputRel' => 'short'
        ],
        'eachLength' => [
            'each' => TRUE,
            'cellName' => 'Each-Length',
            'dimension' => 'length',
            'size' => 7,
            'decimals' => 1,
            'title' => 'Each Length',
            'colTitle' => 'E-L',
            'inputRel' => 'short'
        ],
        'eachWidth' => [
            'each' => TRUE,
            'cellName' => 'Each-Width',
            'dimension' => 'width',
            'size' => 7,
            'decimals' => 1,
            'title' => 'Each Width',
            'colTitle' => 'E-W',
            'inputRel' => 'short'
        ],
        'eachHeight' => [
            'each' => TRUE,
            'cellName' => 'Each-Height',
            'dimension' => 'height',
            'size' => 7,
            'decimals' => 1,
            'title' => 'Each Height',
            'colTitle' => 'E-H',
            'inputRel' => 'short'
        ],
        'eachWeight' => [
            'each' => TRUE,
            'cellName' => 'Each-Weight',
            'dimension' => 'weight',
            'size' => 7,
            'decimals' => 1,
            'title' => 'Each Weight',
            'colTitle' => 'E-We',
            'inputRel' => 'short'
        ],
    ];

    /*
    ****************************************************************************
    */

    static function init($params)
    {
        $self = new static();
        $self::$upcs = $params['upcs'];
        $self::$post['tableData'] = [];
        $self::$upcsCategories = $params['upcCats'];
        return $self;
    }

    /*
    ****************************************************************************
    */

    static function submitContainer($app)
    {
        self::$measurement = $app->post['measurementSystem'] == 'Metric' ?
                'metric' : 'imperial';

        self::$containers = new \tables\inventory\containers($app);
        self::$cartons = new \tables\inventory\cartons($app);
        self::$upcs = new \tables\upcs($app);
        self::$upcsCategories = new \tables\inventory\upcsCategories($app);
        self::$statuses = new \tables\statuses\inventory($app);
        self::$receiving = new receiving($app);
        self::$tableData = getDefault($app->post['tableData'], []);
        self::$post = $app->post;

        $receivingID = self::$post['receiving'];

        if (! $receivingID) {
            return [
                'errors' => [
                    [
                        'field' => 'Receiving',
                        'error' => 'must not be empty'
                    ]
                ],
            ];
        }

        $results = self::$receiving->getReceivingData($receivingID);

        if (! $results) {
            return [
                'errors' => [
                    [
                        'field' => 'Receiving input "' . $receivingID . '"',
                        'error' => 'is invalid.'
                    ]
                ],
            ];
        }

        $statuses = new \tables\statuses\receiving($app);

        $newStatusID = $statuses->getStatusID(receiving::NEW_STATUS);

        if ($results['statusID'] != $newStatusID) {
            return [
                'errors' => [
                    [
                        'field' => 'Receiving "' . $receivingID . '"',
                        'error' => 'can not received. Please choose other Receiving.'
                    ]
                ],
            ];
        }

        self::$post['editContainer'] = json_decode(self::$post['editContainer']);

        self::checkContainer($app);
        self::getRowBatches($app);

        if (! self::$tableData) {
            self::$errors[] = [
                'field' => 'Data input',
                'error' => 'must not be empty'
            ];
        }

        if (self::$errors) {
            return [
                'errors' => self::$errors,
            ];
        }

        $recNum = self::saveContainer($app, $receivingID, $results['client_id']);

        return $recNum ? [
            'recNum' => $recNum,
            'rejectUOM' => self::$rejectUOM,
            'submitErrors' => self::$errors,
        ] : [
            'errors' => self::$errors,
        ];
    }

    /*
    ****************************************************************************
    */

    static function checkContainer($app)
    {
        $cartons = new \tables\inventory\cartons($app);

        foreach (self::$headerCells as $cell => $cellInfo) {

            $value = self::$post[$cell];

            self::checkValue([
                'value' => $value,
                'cellInfo' => $cellInfo,
                'cartonsClass' => $cartons
            ]);

            if (self::$post['editContainer']) {
                continue;
            }

            if ($cell == 'container'
             && self::$containers->valueExists('name', $value)) {

                self::$errors[] = [
                    'field' => self::$headerCells[$cell]['cellName'],
                    'error' => '"'.$value.'" has already been used'
                ];
            }
        }

        $whereClause = $containerUpcs = $upcData = [];

        foreach (self::$tableData as $count => &$rowData) {
            // loop through table rows
            $row = $count + 1;
            foreach ($rowData as $cell => &$value) {
                // trimming input (removing leading/trailing spaces and tabulations)
                $value = trim($value);
                // loop through table columns
                switch ($cell) {
                    case 'rowNo':
                    case 'categoryUPC':
                    case 'newUPC':
                        // Skip columns with no inputs. Continue statement
                        // applies to switch and acts similar to break
                        continue 2;
                    case 'upc':

                        $whereClause[] = 'sku = ? AND size = ? AND color = ?';

                        $sku = strtoupper($rowData['sku']);
                        $size = strtoupper($rowData['size1']);
                        $color = strtoupper($rowData['color1']);

                        $upcData[] = $sku;
                        $upcData[] = $size;
                        $upcData[] = $color;

                        $containerUpcs[$value] = FALSE;

                        self::$containerUpcData[$count] = [
                            'upc' => $value,
                            'data' => [
                                'sku' => $sku,
                                'size' => $size,
                                'color' => $color
                            ],
                            'category' => $rowData['categoryUPC']
                        ];

                        break;
                    default:
                        break;
                }

                $cellInfo = self::$tableCells[$cell];

                self::checkValue([
                    'value' => $value,
                    'cellInfo' => $cellInfo,
                    'row' => $row,
                    'cartonsClass' => $cartons
                ]);

                $dimension = getDefault($cellInfo['dimension']);
                $isEach = getDefault($cellInfo['each']);

                if ($dimension && self::$measurement == 'metric') {
                    // convert Metric to US-Imperial AFTER cell checking
                    $dimensionLimits = self::$cartons->measurements[$dimension];

                    $limits = $dimensionLimits['imperial'];

                    $minValue = $isEach ? 0.1 : $limits['min'];
                    $maxValue = $limits['max'];

                    $convertValue = $value * $dimensionLimits['convert'];

                    $estimateValue = $cell == 'weight' ? $convertValue :
                        ceil($convertValue * 4) / 4;

                    $roundedValue = round($estimateValue, 1);

                    self::$tableData[$count][$cell] =
                            max($minValue, min($maxValue, $roundedValue));
                }
            }

            $errors = self::$cartons->checkCubicValue([
                'height' => $rowData['height'],
                'width' => $rowData['width'],
                'length' => $rowData['length'],
                'returnArray' => TRUE
            ]);

            $prefix = 'at row ' . $row . ' - ';

            self::addCellErrorMessage($errors, $prefix);
        }

        if ($upcData) {

            self::checkUPCDuplicateProperties($containerUpcs);

            $upcDescription = self::$upcs->getUpcDescr($whereClause, $upcData);
            // removing upcs that are in current table
            $diffUPC = array_diff_key($upcDescription, $containerUpcs);

            foreach (self::$containerUpcData as $key => $upcData) {
                self::checkDuplicateUPCs($diffUPC, $key, $upcData);
                self::checkInvalidUPCs($key, $upcData);
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function checkValue($data)
    {
        $value = $data['value'];
        $cellInfo = $data['cellInfo'];
        $row = getDefault($data['row'], 0);
        $cartons = getDefault(self::$cartons, $data['cartonsClass']);

        $measurement = isset(self::$measurement) ? self::$measurement :
                $data['measurement'];

        $cellInfo['cellValue'] = $value;
        $cellInfo['measurement'] = $measurement;
        $cellInfo['returnArray'] = TRUE;

        $isEach = getDefault($cellInfo['each']);

        if (isset($cellInfo['dimension']) && $isEach) {

            $dimension = $cellInfo['dimension'];
            // eaches have no min limits
            $cellInfo['minValue'] = .1;
            // eaches max limits are the same as cartons' max limits
            $cellInfo['maxValue'] =
                    $cartons->measurements[$dimension][$measurement]['max'];
        }

        $errors = $cartons->checkCellValue($cellInfo);
        $prefix = $row ? 'at row '.$row.' ' : NULL;

        self::addCellErrorMessage($errors, $prefix);

        return self::$errors;
    }

    /*
    ****************************************************************************
    */

    static function addCellErrorMessage($errors, $prefix)
    {
        if (! $errors) {
            return;
        }

        foreach ($errors as $error) {
            self::$errors[] = [
                'field' => $error['field'],
                'error' => $prefix . $error['error'],
            ];
        }

        return self::$errors;
    }

    /*
    ****************************************************************************
    */

    static function getRowBatches($app)
    {
        $batchNumber = 0;

        for ($count = 0; $count < count(self::$tableData); $count++) {
            if (self::$post['modifyRows'] > $count) {
                // modified rows should keep their original batch numbers
                self::$changedBathes[] = $batchNumber =
                        self::$post['modifyBatches'][$count];
            } else {
                // get next batch number for the first row when creating a
                // container or for the first added row when modifying a
                // container. Apply batch number augment otherwise.
                $batchNumber = ! $batchNumber || self::$post['modifyRows'] == $count
                    ? self::getFieldNextValue('id', 'inventory_batches', $app)
                    : $batchNumber + 1;
            }

            self::$batchPerLine[$count] = $batchNumber;
        }
    }

    /*
    ****************************************************************************
    */

    static function checkDuplicateUPCs($diffUPC, $key, $upcData)
    {
        foreach ($diffUPC as $upc => $data) {
            if ($upcData['data'] == $data && $upcData['upc'] != $upc) {

                $row = $key + 1;

                self::$errors[] = [
                    'field' => 'Current combination of SKU, SIZE and COLOR',
                    'error' => 'at row '.$row.' has already been reserved for '
                              .'UPC: ' . $upc,
                ];
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function checkInvalidUPCs($key, $upcData)
    {
        for ($count = 0; $count < count(self::$tableData); $count++) {
            if ($key <= $count) {
                // avoid error messages with the same content
                return;
            }

            $rowData = self::$tableData[$count];

            $prevUPC = $rowData['upc'];
            $upc = $upcData['upc'];

            $firstUPCRow = $key + 1;
            $secondUPCRow = $count + 1;

            foreach ($upcData['data'] as $column => $value) {
                // loop through SKU, SIZE, COLOR
                $field = $column == 'sku' ? $column : $column.'1';

                $prevValue = strtoupper(trim($rowData[$field]));

                if ($prevValue != $value && $prevUPC == $upc) {
                    self::$errors[] = [
                        'field' => self::$tableCells[$field]['cellName'],
                        'error' => 'table value "'.$prevValue.'" at row '
                                  .$firstUPCRow.' does not match table value "'
                                  .$value.'" at row '.$secondUPCRow.' but have '
                                  .'the same UPC '.$upc,
                    ];
                }
            }

            $sku = strtoupper($rowData['sku']);
            $size = strtoupper($rowData['size1']);
            $color = strtoupper($rowData['color1']);

            if ($sku == $upcData['data']['sku']
             && $size == $upcData['data']['size']
             && $color == $upcData['data']['color']
             && $prevUPC != $upc) {

                self::$errors[] = [
                    'field' => 'Identical combination of SKU, SIZE and COLOR',
                    'error' => 'is used for different UPCs: '.$upc.' at row '
                              .$firstUPCRow.' and '.$prevUPC.' at row '
                              .$secondUPCRow,
                ];
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function checkUPCDuplicateProperties($containerUpcs)
    {
        $upcValues = array_keys($containerUpcs);

        $upcInfo = self::$upcs->getUpcInfo($upcValues);

        foreach (self::$tableData as $values) {

            $upc = $values['upc'];
            $sku = strtoupper($values['sku']);
            $size = strtoupper($values['size1']);
            $color = strtoupper($values['color1']);

            if (! isset($upcInfo[$upc])) {
                continue;
            }

            $existingUPC = $upcInfo[$upc];

            if (strtoupper($existingUPC['sku']) != $sku
             || strtoupper($existingUPC['size']) != $size
             || strtoupper($existingUPC['color']) != $color) {

                self::$errors[] = [
                    'field' => 'UPC '.$upc,
                    'error' => 'alteration of SKU, COLOR SIZE is not allowed',
                ];
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function saveContainer($app, $receivingID, $vendorID)
    {
        $submittedUpcs = self::handleUPCs([
            'app' => $app,
        ]);

        $updateBatchSql = 'UPDATE inventory_batches
                           SET    upcId = ?,
                                  prefix = ?,
                                  suffix = ?,
                                  height = ?,
                                  width = ?,
                                  length = ?,
                                  weight = ?,
                                  eachHeight = ?,
                                  eachWidth = ?,
                                  eachLength = ?,
                                  eachWeight = ?
                           WHERE  id = ?';

        $updateCartonSql = 'UPDATE inventory_cartons
                            SET    uom = ?
                            WHERE  batchID = ?
                            AND    NOT isSplit
                            AND    NOT unSplit';

        $insertContainerSql = 'INSERT INTO inventory_containers (
                                    recNum,
                                    name,
                                    userID,
                                    vendorID,
                                    measureID
                                ) VALUES (
                                    ?, ?, ?, ?, ?
                                )';

        $insertBatchSql = 'INSERT INTO inventory_batches (
                               upcId,
                               prefix,
                               suffix,
                               height,
                               width,
                               length,
                               weight,
                               eachHeight,
                               eachWidth,
                               eachLength,
                               eachWeight,
                               id,
                               recNum,
                               initialCount
                           ) VALUES (
                               ?, ?, ?, ?, ?, ?, ?,
                               ?, ?, ?, ?, ?, ?, ?
                           )';

        $insertCartonSql = 'INSERT INTO inventory_cartons (
                                batchID,
                                uom,
                                cartonID,
                                statusID,
                                mStatusID
                            ) VALUES (
                                ?, ?, ?, ?, ?
                            )';

        $insertReceivingContainerSql = 'INSERT INTO receiving_containers (
                                receiving_id,
                                container_num,
                                created_at
                            ) VALUES (
                                ?, ?, NOW()
                                )';

        $statusID = self::$statuses->getStatusID('IN');
        $processedBatches = self::$cartons->getProcessedBatches(self::$changedBathes);
        $upcIDs = self::$upcs->getUpcs($submittedUpcs);

        foreach ($submittedUpcs as &$value) {
            // removing leading zeros
            $value = (string)(float)$value;
        }

        $upcsInvalid = array_diff($submittedUpcs, array_keys($upcIDs));

        if ($upcsInvalid) {

            self::$errors[] = [
                'field' => 'UPC(s): ' . implode (', ', $upcsInvalid),
                'error' => 'is invalid.',
            ];

            return FALSE;
        }

        $recNum = self::$post['editContainer'] ?
                self::$post['editContainer'] :
                self::getFieldNextValue('recNum', 'inventory_containers', $app);

        $app->beginTransaction();

        for ($count = 0; $count < count(self::$tableData); $count++) {
            $batchNumber = self::$batchPerLine[$count];

            $rowData = self::$tableData[$count];

            for ($cartonCount = 1; $cartonCount <= $rowData['carton']; $cartonCount++) {

                $upc = (string)(float)$rowData['upc'];
                $uom = $rowData['uom'];

                $params = [
                    $upcIDs[$upc]['id'],
                    $rowData['prefix'],
                    $rowData['suffix'],
                    $rowData['height'],
                    $rowData['width'],
                    $rowData['length'],
                    $rowData['weight'],
                    $rowData['eachHeight'],
                    $rowData['eachWidth'],
                    $rowData['eachLength'],
                    $rowData['eachWeight'],
                    $batchNumber,
                ];

                if (self::$post['modifyRows'] > $count) {
                    // modifying a container
                    $app->runQuery($updateBatchSql, $params);

                    if (isset($processedBatches[$batchNumber])) {
                        if ($cartonCount == 1
                         && $processedBatches[$batchNumber] != $uom) {
                            // batch cartons were split or processed
                            self::$rejectUOM[] = 'UOM for UPC '.$upc.' in row '
                                    .($count + 1).' was not changed to '.$uom
                                    .' due to split or processed cartons';
                        }
                    } else {
                        $app->runQuery($updateCartonSql, [$uom, $batchNumber]);
                    }

                } else {
                    // creating a new container
                    if ($cartonCount == 1) {
                        // first carton in a batch
                        if ($count == 0 && ! self::$post['editContainer']) {

                            // first container row
                            $app->runQuery($insertContainerSql, [
                                $recNum,
                                self::$post['container'],
                                self::$post['userID'],
                                $vendorID,
                                self::$post['measureID'],
                            ]);

                            // insert into receiving_container table
                            $app->runQuery($insertReceivingContainerSql, [
                                $receivingID,
                                $recNum
                            ]);
                        }

                        $params[] = $recNum;
                        $params[] = $rowData['carton'];

                        $app->runQuery($insertBatchSql, $params);
                    }

                    $cartonID = sprintf('%04d', $cartonCount);

                    $app->runQuery($insertCartonSql, [
                        $batchNumber,
                        $uom,
                        $cartonID,
                        $statusID,
                        $statusID,
                    ]);
                }
            }
        }

        $app->commit();

        return $recNum;
    }

    /*
    ****************************************************************************
    */

    static function handleUPCs($params=[])
    {
        $app = $params['app'];
        self::$containerUpcData = isset($params['upcData']) ?
            $params['upcData'] : self::$containerUpcData;

        foreach (self::$containerUpcData as $values) {

            $upc = $values['upc'];

            // getting rid of duplicate upcs
            $containerUpcData[$upc]['category'] = $values['category'];
            $containerUpcData[$upc] += $values['data'];
        }

        $upcCategories = isset($params['category']) ?
            $params['category'] : self::upcCategories();

        $categories = is_array($upcCategories) ?
            array_keys($upcCategories) : [$upcCategories];

        $upcCategoryIDs = self::$upcsCategories->getByName($categories);

        $submittedUpcs = array_keys($containerUpcData);
        $originalUpc = self::$upcs->getOriginalUpcIDs($submittedUpcs);

        $app->beginTransaction();

        self::updateUpcs($containerUpcData, $upcCategoryIDs, $app);
        self::insertOriginalUpcs($originalUpc, $app);

        $app->commit();

        return $submittedUpcs;
    }

    /*
    ****************************************************************************
    */

    static function upcCategories()
    {
        $upcCategories = [];
        foreach (self::$post['tableData'] as $values) {
            if ($values['categoryUPC']) {
                // getting unique UPC categories
                $value = $values['categoryUPC'];
                $upcCategories[$value] = TRUE;
            }
        }
        return $upcCategories;
    }

    /*
    ****************************************************************************
    */

    static function getFieldNextValue($field, $table, $app=NULL)
    {
        $sql = 'SELECT     ' . $field . '
                FROM       ' . $table . '
                ORDER BY   ' . $field . ' DESC
                LIMIT 1';

        $result = $app->queryResult($sql);

        $minValue = 10000001;

        if ($result[$field] == 0) {
            return $result[$field] = $minValue;
        } else {
            if ($result[$field] > 0 && $result[$field] < $minValue) {

                $fieldName = $table == 'inventory_batches' ? 'Batch' : 'Container';

                self::$errors[] = [
                    'field' => NULL,
                    'error' => 'Invalid '.$fieldName.' Number',
                ];
            }

            return ++$result[$field];
        }
    }

    /*
    ****************************************************************************
    */

    static function updateUpcs($submittedUpcs, $upcCategoryIDs, $app)
    {
        $sql = 'INSERT IGNORE INTO upcs (
                    upc,
                    catID,
                    sku,
                    size,
                    color
                ) VALUES (
                    ?, ?, ?, ?, ?
                )
                ON DUPLICATE KEY UPDATE
                	active = ' . self::UPC_ACTIVE;

        foreach ($submittedUpcs as $upc => $description) {

            $category = $description['category'];

            $description['category'] = getDefault($upcCategoryIDs[$category], NULL);

            $params = array_values($description);

            array_unshift($params, $upc);

            $app->runQuery($sql, $params);
        }
    }

    /*
    ****************************************************************************
    */

    static function insertOriginalUpcs($originalUpc, $app)
    {
        $sql = 'INSERT IGNORE upcs_assigned (
                    upcID,
                    userID
                ) VALUES (
                    ?, ?
                )';

        foreach ($originalUpc as $upcID) {

            $userID = access::getUserID();

            $app->runQuery($sql, [$upcID, $userID]);
        }
    }

    /*
    ****************************************************************************
    */

}
