<?php

namespace import;

use \labels\rcLabel;
use \labels\create as uccLabels;
use \PHPExcel_IOFactory as factory;
use \common\tally;
use tables\inventory\cartons;

class vendorData
{
    static $reset = FALSE;

    public $errors = [];
    public $badRows = [];
    public $badRefIDs = [];
    public $missingReqs;
    public $fieldKeys = [];
    public $app = NULL;

    static $files = [];
    static $vendorName = NULL;
    static $userID = NULL;
    static $client = NULL;
    static $clientID = NULL;
    static $warehouse = NULL;
    static $warehouseID = NULL;
    static $measurement = NULL;
    static $measurementSystems = NULL;
    static $locationNames = [];
    static $containerMeasureIDs = [];

    static $clientUsed = [];
    static $warehouseUsed = [];
    static $containerUsed = [];
    static $measureUsed = [];
    static $locationUsed = [];

    static $batch = 10000001;
    static $recNum = 10000001;
    static $uploadFile = NULL;
    static $uploadPaths = [];

    static $palletSheetFields = [
        'Container' => 'container',
        'Warehouse' => 'warehouse',
        'Client' => 'client',
        'Location' => 'location',
        'SKU' => 'sku',
        'Color' => 'color',
        'Client PO' => 'prefix',
        'Suffix' => 'suffix',
        'UOM' => 'uom',
        'Initial Count' => 'initialCount',
        'Height' => 'height',
        'Width' => 'width',
        'Length' => 'length',
        'Weight' => 'weight',
        'Measurement System' => 'measure',
        'Size' => 'size',
    ];

    static $measures = [
        'IMP' => [
            'dbName' => 'US-Imperial',
            'referenceName' => 'imperial'
        ],
        'MET' => [
            'dbName' => 'Metric',
            'referenceName' => 'metric'
        ],
    ];

    static $errorMsg = NULL;

    static $warning = NULL;

    const NUMBER_PRECISION_BATCHES = 2;

    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        $this->app = $app;

        $this->importerFormElements();
        $this->importerFormEnd();
        $this->importerFormButton();

        self::$files = getDefault($_FILES, []);

        $vendor = self::$vendorName = $this->checkVendor();

        $this->interface = getDefault($this->app->importInterface['fields']);

        ! $vendor || $this->interface or die('Vendor not found.');

        $this->uploadFiles($app);
    }

    /*
    ****************************************************************************
    */

    function checkVendor()
    {
        $app = $this->app;

        $vendor = getDefault($app->get['vendor']);

        if (! $vendor) {
            return;
        }

        isset($app->importInterface[$vendor]) || die('Vender Interface Not Found');

        $this->interface = $app->importInterface[$vendor];

        $app->vendorDisplay = $app->importInterface[$vendor]['display'];

        return $vendor;
    }

    /*
    ****************************************************************************
    */

    static function uploadFiles($app)
    {
        self::$vendorName = self::$vendorName ? self::$vendorName :
            isset($app->post['clientID']) ? (int)$app->post['clientID'] : NULL;

        if (! self::$vendorName) {
            return;
        }

        self::getVendorData($app);

        $warehouseDir = strtolower(self::$warehouse) . 'InventoryImportsFiles';

        $fileUploadPath = \models\directories::getDir('uploads', $warehouseDir);

        file_exists($fileUploadPath) || die('Foler "' . $fileUploadPath
                . '" does not exist!');

        foreach (self::$files as $fileInfo) {

            self::$uploadFile = self::$uploadPaths[] = $fileUploadPath . '/'
                    . $fileInfo['name'];

            move_uploaded_file($fileInfo['tmp_name'], self::$uploadFile);
        }
    }

    /*
    ****************************************************************************
    */

    function loadFile($fileName)
    {
        $pathInfo = pathinfo($_FILES[$fileName]['name'], PATHINFO_EXTENSION);

        if (! in_array($pathInfo, ['xls', 'xlsx'])) {
            die('Excel Files Only');
        }

        die('Can not use dynamic values as directories.');
    }

    /*
    ****************************************************************************
    */

    function insertFiles()
    {
        if (empty($_FILES)) {
            return;
        }

        foreach ($this->inputNames as $inputName) {
            $this->insertFile($inputName);
        }
    }

    /*
    ****************************************************************************
    */

    function insertMade()
    {
        return $this::$files;
    }

    /*
    ****************************************************************************
    */

    function insertFile($inputName)
    {
        // Files must have been posted
        if (! $this::$files) {
            return FALSE;
        }

        $fields = $this->fields = $this->interface[$inputName]
            or die('This request object does not have an import interface.');

        $this->setInsertionQuery($fields, $inputName);

        $this->table = [];
        if (! $this->loadFile($inputName)) {
            return $this->errors['wrongType'] = TRUE;
        }

        $phpExcel = $this->objPHPExcel or die('No file loaded..');

        $phpExcel->getSheetCount() < 2 or die('One sheet please');

        $this->app->beginTransaction();

        foreach ($phpExcel->getWorksheetIterator() as $this->worksheet) {
            $this->insertFileIterateRows();
        }

        unset($this->worksheet, $this->objPHPExcel);

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function setInsertionQuery($fields, $inputName)
    {
        $table = $this->tableName = $this::$vendorName . '_' . $inputName;

        $fields = implode(',', $fields['output']);
        $qMarks = $this->app->getQMarkString($fields['output']);

        $this->sql = 'INSERT IGNORE vendor_data.' . $table . ' (
                    ' . $fields . '
                    ) VALUES (' . $qMarks . ')';
    }

    /*
    ****************************************************************************
    */

    function processCellIterator(&$oneRow, &$flag, $cellIterator)
    {
        foreach ($cellIterator as $cellID => $cell) {
            if (! isset($this->fields['input'][$cellID])) {
                continue;
            }

            $this->app->customizeRow($oneRow, $flag, [
                'table' => $this->tableName,
                'key' => $this->fields['input'][$cellID],
                'cellValue' => $cell->getFormattedValue()
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function insertFileIterateRows()
    {
        foreach ($this->worksheet->getRowIterator() as $row) {

            $cellIterator = $row->getCellIterator();

            // Loop all cells, even if it is not set
            $cellIterator->setIterateOnlyExistingCells(false);

            $oneRow = [];
            $flag = FALSE;

            $this->processCellIterator($oneRow, $flag, $cellIterator);

            if (! $flag) {
                $this->table[] = $oneRow;
                $this->app->runQuery($this->sql, $oneRow);
            }
        }
    }

    /*
    ****************************************************************************
    */

    function importerFormElements()
    {
        foreach ($this->app->imports as $row) {
            $name = $row['input'];
            $formID = $name ? ' id="' . $name . 'Form"' : NULL;
            $formName = $name ? ' name="' . $name . 'Form"' : NULL;

            ob_start(); ?>
            <input id="<?php echo $name; ?>" name="<?php echo $name; ?>"
                   type="file"><?php
            $this->app->importerInputs[$name] = ob_get_clean();

            ob_start(); ?>
            <input name="<?php echo $name; ?>Template" type="submit"
                   value="Download Template"><?php
            $this->app->templateInputs[$name] = ob_get_clean();

            ob_start(); ?>
            <form <?php echo $formID.$formName; ?>
                method="post" enctype="multipart/form-data"><?php
            $this->app->importerFormStart[$name] = ob_get_clean();
        }
    }

    /*
    ****************************************************************************
    */

    function importerFormEnd()
    {
        ob_start(); ?>
        </form><?php
        $this->app->importerFormEnd = ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function importerFormButton()
    {
        ob_start(); ?>
        <input type="submit" name="submit" value="Submit"><?php
        $this->app->importerFormButton = ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    static function importPalletSheet($app)
    {

        if (! isset($_FILES['palletSheet'])) {
            return FALSE;
        }

        $payload = self::insertAndRack($app);

        if (isset($payload['errors'])) {
            return $payload;
        } else {
            return self::createLabels($payload);
        }
    }

    /*
    ****************************************************************************
    */

    static function resetInventory($app)
    {
        $sql = 'TRUNCATE upcs;
                TRUNCATE tallies;
                TRUNCATE tally_rows;
                TRUNCATE cartons_logs;
                TRUNCATE licenseplate;
                TRUNCATE tally_cartons;
                TRUNCATE upcs_assigned;
                TRUNCATE invoices_storage;
                TRUNCATE inventory_batches;
                TRUNCATE inventory_cartons;
                TRUNCATE cartons_logs_adds;
                TRUNCATE invoices_receiving;
                TRUNCATE cartons_logs_values;
                TRUNCATE inventory_containers;
                TRUNCATE invoices_receiving_batches;
                ALTER TABLE licenseplate auto_increment = 10000001;
                ALTER TABLE inventory_batches auto_increment = ' . self::$batch . ';
                ALTER TABLE inventory_containers auto_increment = ' . self::$recNum .';
                ALTER TABLE invoices_receiving_batches auto_increment = 1000001;
                ';

        $app->runQuery($sql);
    }

    /*
    ****************************************************************************
    */

    static function updateInventory($app, $inactiveStatus)
    {
        $sql = 'UPDATE inventory_cartons c
                JOIN   inventory_batches b ON b.id = c.batchID
                JOIN   inventory_containers co ON co.recNum = b.recNum
                SET    c.statusID = ?,
                       c.mStatusID = ?
                WHERE  co.vendorID = ?';

        $params = [$inactiveStatus, $inactiveStatus, self::$clientID];

        $app->runQuery($sql, $params);
    }

    /*
    ****************************************************************************
    */

    static function dontResetInventory($app, $deactivate)
    {
        // Set all of the clients inventory to inactive
        $statuses = new \tables\statuses\inventory($app);

        $inactiveStatus = $statuses->getStatusID(cartons::STATUS_INACTIVE);

        if ($deactivate) {
            self::updateInventory($app, $inactiveStatus);
        }

        self::$batch = $statuses->getNextID('inventory_batches');
        self::$recNum = $statuses->getNextID('inventory_containers');
    }

    /*
    ****************************************************************************
    */

    static function makeInsertUpc($data, &$upcs)
    {
        $app = $data['app'];
        $newUPCs = $data['newUPCs'];
        $seldatUPCs = $data['seldatUPCs'];
        $maxUPCID = $data['maxUPCID'];

        $app->beginTransaction();

        foreach ($newUPCs as $upcIndex => $row) {

            $upcInfo = array_shift($seldatUPCs);
            $upcs[$upcIndex]['upc'] = $upcInfo['upc'];

            self::insertUpc($app, $upcInfo, $row);
            self::insertUpcsAssigned($app, $maxUPCID);
            $maxUPCID++;
        }

       $app->commit();
    }

    /*
    ****************************************************************************
    */

    static function insertUpc($app, $upcInfo, $row)
    {
        $sql = 'INSERT IGNORE upcs (
                        upc,
                        sku,
                        color,
                        size
                    ) VALUES (?, ?, ?, ?)';

        $app->runQuery($sql, [
            $upcInfo['upc'],
            $row['sku'],
            $row['color'],
            $row['size'],
        ]);
    }

    /*
    ****************************************************************************
    */

    static function insertUpcsAssigned($app, $upcID)
    {
        $sql = 'INSERT INTO upcs_checkout(
                        upcID,
                        checkedOut
                    ) VALUE (
                        ?,
                        NOW()
                    )
                    ON DUPLICATE KEY UPDATE
                        checkedOut = NOW()';

        $app->runQuery($sql, [$upcID]);

        $sql = 'INSERT INTO upcs_assigned (
                        upcID,
                        userID
                    ) VALUES (?, ?)';

        $app->runQuery($sql, [$upcID, self::$userID]);
    }

    /*
    ****************************************************************************
    */

    static function locationInsertBuilder()
    {
        foreach (self::$errorMsg['missingLocations'] as $missingLocation) {

            $query = 'INSERT INTO locations (
                          displayName,
                          isShipping,
                          warehouseID,
                          cubicFeet,
                          distance
                      ) VALUES (
                          "' . $missingLocation . '",
                          0,
                          ' . self::$warehouseID . ',
                          64.44,
                          0
                      );';

            self::$errorMsg['insert'][] = $query;
        }
    }

    /*
    ****************************************************************************
    */

    static function insertInvContainer($app, $containers)
    {
        $sql = 'INSERT INTO inventory_containers (
                    name,
                    userID,
                    vendorID,
                    measureID
                ) VALUES (?, ?, ?, ?)';

        foreach ($containers as $row) {

            $name = reset($row);

            $row[] = self::$containerMeasureIDs[$name];

            $app->runQuery($sql, $row);
        }
    }

    /*
    ****************************************************************************
    */

    static function insertInvBatch($app, $batches)
    {
        $sql = 'INSERT INTO inventory_batches (
                    recNum,
                    upcID,
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
                    initialCount
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?
                )';

        foreach ($batches as $row) {
            $app->runQuery($sql, $row);
        }
    }

    /*
    ****************************************************************************
    */

    static function insertInvCarton($app, $cartons, $inactiveStatus)
    {
        $sql = 'INSERT INTO inventory_cartons (
                    batchID,
                    uom,
                    statusID,
                    mStatusID,
                    cartonID
                ) VALUES (?, ?, ?, ?, ?)';

        foreach ($cartons as $row) {

            $cartonCountCol = $inactiveStatus;
            $cartonIDCol = $inactiveStatus;
            $initialCount = $row[$cartonCountCol];

            unset($row[$cartonCountCol]);

            for ($i = 0; $i < $initialCount; $i++) {
                $row[$cartonIDCol] = $i + 1;
                $app->runQuery($sql, $row);
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function callRCLog($data)
    {
        $app = $data['app'];
        $tallies = $data['tallies'];
        $recNums = $data['recNums'];
        $containerBatches = $data['containerBatches'];

        foreach ($tallies as $container => $tally) {

            $recNum = $recNums[$container];

            $app->post = $tally;
            $app->post['recNum'] = $recNum;
            $app->post['container'] = $container;
            $app->post['batches'] = $containerBatches[$recNum];

            tally::submitRCLog([
                'app' => $app,
                'checkProducts' => FALSE,
                'importInventory' => TRUE,
            ]);
            tally::updateTallyPrinted($app, $recNum);

        }
    }

    /*
    ****************************************************************************
    */

    static function insertAndRack($app)
    {
        if (! isset($app->post)) {
            return FALSE;
        }

        self::$clientID = (int)$app->post['clientID'];
        $fileName = getDefault($_FILES['palletSheet']['name']);

        new static($app);

        $importFileName = self::validateInsertAndRack($app, $fileName);

        if (self::$errorMsg) {
            return self::$errorMsg;
        }

        self::checkDataUpload($app, $importFileName);

        if (self::$errorMsg) {
            return ['errors' => self::$errorMsg];
        }

        $users = new \tables\users($app);
        $statuses = new \tables\statuses\inventory($app);

        $upcs = self::getUPCs($app);

        $deactivateInventory = $app->post['deactivateInventory'];

        // Get the previous client carton info
        self::$reset ? self::resetInventory($app) :
            self::dontResetInventory($app, $deactivateInventory);

        // Get User
        self::$userID = self::getUserIByUsername($users, 'jsapp');

        //process data input from CSV file
        $data = self::processDataUpload($app, $upcs, $importFileName);

        if (self::$errorMsg) {
            return ['errors' => self::$errorMsg];
        }

        $containerBatches = $data['containerBatches'];
        $inventoryContainers = $data['inventoryContainers'];
        $tallies = $data['tallies'];
        $neededLocations = $data['neededLocations'];
        $newUPCs = $data['newUPCs'];

        if ($newUPCs) {
            self::$warning[] = count($newUPCs) . ' new SKU(s) had been requested
                create new:';
            foreach ($newUPCs as $key => $row) {
                self::$warning[] = '<br>'.$row['sku'];
            }
            //get seldat UPCs
            $seldatUPCs = self::getNewSeldatUPCs($app, $newUPCs);

            if (self::$errorMsg) {
                return ['errors' => self::$errorMsg];
            }

            $maxUPCID = $users->getNextID('upcs');

            self::makeInsertUpc([
                'app' => $app,
                'newUPCs' => $newUPCs,
                'seldatUPCs' => $seldatUPCs,
                'maxUPCID' => $maxUPCID,
            ], $upcs);

            self::updateUPCIDToInventoryContainerData([
                'inventoryContainers' => &$inventoryContainers,
                'newUPCs' => $newUPCs,
                'maxUPCID' => $maxUPCID,
            ]);
        }

        isset(self::$errorMsg['missingLocations']) && self::processMissingLocations();

        if (self::$errorMsg) {
            return ['errors' => self::$errorMsg];
        }

        $inactiveStatus = $statuses->getStatusID(cartons::STATUS_INACTIVE);

        // Put in the real UPCs
        self::putInRealUpcs($tallies, $upcs);

        $infoInventory = self::getDataForInsertInventoryContainer([
            'inventoryContainers' => $inventoryContainers,
            'containerBatches' => &$containerBatches,
            'inactiveStatus' => $inactiveStatus,
        ]);

        $recNums = $infoInventory['recNums'];
        $containers = $infoInventory['containers'];
        $batches = $infoInventory['batches'];
        $cartons = $infoInventory['cartons'];

        tally::$nextCartonID = $users->getNextID('inventory_cartons');

        $app->beginTransaction();

        self::insertInvContainer($app, $containers);
        self::insertInvBatch($app, $batches);
        self::insertInvCarton($app, $cartons, $inactiveStatus);

        $app->commit();

        // Get all the select-queries out of the way
        $queryRecNums = array_values($recNums);

        $result = tally::prepareForSubmit([
            'app' => $app,
            'rcLogForm' => FALSE,
            'recNums' => $queryRecNums,
            'neededLocations' => $neededLocations,
        ]);

        $nextID = $users->getNextID('plate_batches');
        $tallyUserID = $result['userID'];
        $nextPlate = $result['nextPlate'];

        $app->beginTransaction();

        // Call RC Log Functions
        self::callRCLog([
            'app' => $app,
            'tallies' => $tallies,
            'recNums' => $recNums,
            'containerBatches' => $containerBatches,
        ]);

        $platesCreated = tally::$nextPlate - $nextPlate;

        // Get the count of pallets then make
        \common\labelMaker::inserts([
            'pdo' => $app,
            'model' => $users,
            'userID' => $tallyUserID,
            'quantity' => $platesCreated,
            'labelType' => 'plate',
            'firstBatchID' => $nextID,
            'makeTransaction' => FALSE,
        ]);

        $app->commit();

        self::unifyContainerPlates([
            'app' => $app,
            'recNums' => $queryRecNums,
            'makeTransaction' => TRUE,
        ]);

        $allBatches = self::getAllContainerBatches($containerBatches);

        return [
            'recNums' => $queryRecNums,
            'vendorIDs' => [self::$clientID],
            'client' => self::$client,
            'warehouse' => self::$warehouse,
            'containerBatches' => array_values($allBatches),
            'warning' => self::$warning
        ];
    }

    /*
    ****************************************************************************
    */

    static function createLabels($payload)
    {
        $labelDir = self::getLableDirPath('UCCLabels');

        if (! file_exists($labelDir)) {
            die('Folder "' . $labelDir . '" does not exist!');
        }

        return $payload;
    }

    /*
    ****************************************************************************
    */

    static function createUCCLabels($payload)
    {
        $app = $payload['app'];
        $batches = $payload['batches'];
        $client = strtr($payload['client'], ' ', '_');

        self::$warehouse = $payload['warehouse'];

        $labelDir = self::getLableDirPath('UCCLabels');

        $fileName = $labelDir . '/' . $client . '_' . date('Y-m-d-H-i-s') . '.pdf';

        uccLabels::forContainer([
            'db' => $app,
            'save' => $labelDir,
            'batches' => $batches,
            'byIndex' => FALSE,
            'fileName' => $fileName,
            'manualInserts' => TRUE,
        ]);

        uccLabels::insertMasterLabels($app);

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    static function createRCLabels($payload)
    {
        $app = $payload['app'];
        $recNums = $payload['recNums'];
        $client = strtr($payload['client'], ' ', '_');

        self::$warehouse = $payload['warehouse'];

        $labelDir = self::getLableDirPath('RCLabels');

        $fileName = $labelDir . '/' . $client . '_' . date('Y-m-d-H-i-s') . '.pdf';

        $inventory = new \tables\inventory\cartons($app);

        rcLabel::getContainerInfo([
            'recNum' => $recNums,
            'inventory' => $inventory,
        ]);

        $app->beginTransaction();

        rcLabel::get([
            'fileName' => $fileName,
            'inventory' => $inventory,
        ]);

        $app->commit();

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    static function createPlates($payload)
    {
        $app = $payload['app'];
        $recNums = $payload['recNums'];
        $client = strtr($payload['client'], ' ', '_');

        self::$warehouse = $payload['warehouse'];

        $labelDir = self::getLableDirPath('Plates');

        $fileName = $labelDir . '/' . $client . '_' . date('Y-m-d-H-i-s') . '.pdf';

        $labels = new \labels\licensePlates();

        $plateInfo = $labels->getPlateData([
            'db' => $app,
            'term' => $recNums,
            'search' => 'recNums',
            'locOrder' => TRUE,
        ]);

        $app->beginTransaction();

        $labels->addLicensePlate([
            'db' => $app,
            'save' => TRUE,
            'search' => 'recNums',
            'fileName' => $fileName,
            'plateInfo' => $plateInfo,
        ]);

        $app->commit();

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    static function unifyContainerPlates($data)
    {
        $app = $data['app'];
        $statusReceived = \tables\inventory\cartons::STATUS_RECEIVED;

        $containers = new \tables\inventory\containers($app);
        $inventoryCartons = new \tables\inventory\cartons($app);

        $containerLocs = isset($data['containerLocs']) ? $data['containerLocs'] :
            $containers->getContainerLocations($data['recNums']);

        $makeTransaction = getDefault($data['makeTransaction'], TRUE);

        $sql = 'UPDATE inventory_cartons
                SET    plate = ?
                WHERE  id = ?';

        $insertInventoryControlSql = 'INSERT INTO inventory_control (
                                      licenseplate,
                                      status,
                                      inventoryID
                                     ) VALUES (
                                     ?, "' . $statusReceived . '", ?
                                    )';

        $makeTransaction ? $app->beginTransaction() : NULL;

        foreach ($containerLocs as $locations) {
            foreach ($locations as $location => $plates) {
                foreach ($plates as $plate => $cartons) {
                    foreach ($cartons as $invID) {
                        $app->runQuery($sql, [$plate, $invID]);
                        $app->runQuery($insertInventoryControlSql, [$plate, $invID]);
                    }
                }
            }
        }

        $makeTransaction ? $app->commit() : NULL;
    }

    /*
    ****************************************************************************
    */

    static function checkHeaderErrors($columns, $data)
    {
        $errors = [];
        $missingColumns = $columns;
        $columnKeys = array_keys($columns);

        $unexpectedColumns = array_diff($data, $columnKeys);

        if ($unexpectedColumns) {
            foreach ($unexpectedColumns as $key => $value) {
                if (! $value) {
                    $unexpectedColumns[$key] = 'Column Empty Header';
                }
            }

            $errors['unexpectedColumns'] = $unexpectedColumns;
        }

        foreach ($data as $value) {
            unset($missingColumns[$value]);
        }

        if ($missingColumns) {
            $errors['missingColumns'] = array_keys($missingColumns);
        }

        return $errors;
    }

    /*
    ****************************************************************************
    */

    static function getUPCs($app)
    {
        $sql = 'SELECT  CONCAT(sku, color, size),
                        upc,
                        id,
                        sku,
                        color,
                        size
                FROM    upcs';

        $result = $app->queryResults($sql);

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function getNewSeldatUPCs($app, $newUPCs)
    {
        // Get the new seldat UPCs
        $limit = count($newUPCs);
        $sql = 'SELECT    o.id,
                          o.upc
                FROM      upcs_originals o
                LEFT JOIN upcs_checkout c ON c.upcID = o.id
                LEFT JOIN upcs u ON u.upc = o.upc
                -- Use check out times to make sure no one has tried to use this
                -- UCP recently
                WHERE   ( c.checkedOut IS NULL
                          OR NOW() - INTERVAL 1 DAY > c.checkedOut )
                AND      u.id IS NULL
                ORDER BY  o.upc ASC
                LIMIT     0, ' . $limit;

        $seldatUPCs = $app->queryResults($sql);

        if (count($seldatUPCs) < $limit) {
            self::$errorMsg['generalError'][] = 'Import was stopped
            due to lack of original upcs.';
        }

        return $seldatUPCs;
    }

    /*
    ****************************************************************************
    */

    static function checkDimension($param, $checkField)
    {
        $values = $param['values'];
        $cartons = $param['cartonsClass'];

        $measurementSystem = $values['measure'];

        $measurement = self::$measures[$measurementSystem]['referenceName'];

        $error = $cartons->checkCartonDimension([
            'value' => $values[$checkField],
            'dimension' => $checkField,
            'measurement' => $measurement
        ]);

        return $error;
    }

    /*
    ****************************************************************************
    */

    static function processCheckBatch($param, $checkField)
    {
        $values = $param['values'];
        $cartons = $param['cartonsClass'];
        $cartonCount = $values['initialCount'];

        switch ($checkField) {
            case 'height':
            case 'width':
            case 'length':
            case 'weight':
                $error = self::checkDimension($param, $checkField);
                break;
            case 'sku':
                $error = $cartons->checkCartonSKU($values['sku']);
                break;
            case 'uom':
                $error = $cartons->checkCartonUOM($values['uom']);
                break;
            case 'initialCount':
                $error = $cartons->checkCartonInitialCount($cartonCount, 'CARTONS');
                break;
            case 'color':
                $error = $cartons->checkCartonColor($values['color']);
                break;
            case 'size':
                $error = $cartons->checkCartonSize($values['size']);
                break;
            case 'prefix':
                $error = $cartons->checkCartonPrefix($values['prefix']);
                break;
            case 'suffix':
                $error = $cartons->checkCartonSuffix($values['suffix']);
                break;
            default:
                break;
        }

        return getDefault($error, []);
    }

    /*
    ****************************************************************************
    */

    static function checkBatch($param)
    {
        $values = $param['values'];
        $row = $param['row'] + 1;
        $cartonsClass = $param['cartonsClass'];

        $errors = [];

        foreach (self::$palletSheetFields as $checkField) {

            $batchErrors = self::processCheckBatch($param, $checkField);

            foreach ($batchErrors as $batchError) {
                $errors[] = $batchError['field'] . ' at row ' . $row . ' ' .
                        $batchError['error'];
            }
        }

        $cubeError = $cartonsClass->checkCubicValue([
            'height' => $values['height'],
            'width' => $values['width'],
            'length' => $values['length'],
            'returnArray' => TRUE,
        ]);

        if ($cubeError) {
            foreach ($cubeError as $error) {
                $errors[] = 'row ' . $row . ': ' . $error['error'];
            }
        }

        return $errors;
    }

    /*
    ****************************************************************************
    */

    static function validateInsertAndRack($app, $fileName)
    {
        if (! self::$clientID) {
            self::$errorMsg['errors']['generalError'][] = 'Select a Client';
        }

        if (! $fileName) {
            self::$errorMsg['errors']['generalError'][] = 'Select a File';
        }

        $file = NULL;

        if ($fileName && self::$client) {

            $file = self::$uploadFile;

            $ext = pathinfo($file, PATHINFO_EXTENSION);

            if (! in_array($ext, ['csv', 'xls', 'xlsx'])) {
                self::$errorMsg['errors']['generalError'][]
                        = 'A file you have submitted has unexpected extension';

                return;
            } else if (fopen($file, 'r') === FALSE) {
                self::$errorMsg['errors']['generalError'][]
                        = 'A file you have submitted can not be read';

                return;
            }

            if ($ext && $ext != 'csv') {

                $importer = new \excel\importer($app);

                if (! $importer->getCheckFileType($file)) {
                    self::$errorMsg['errors']['generalError'][]
                            = 'Invalid file type';

                    return;
                }

                $type = $ext == 'xls' ? 'Excel5' : 'Excel2007';

                $reader = factory::createReader($type);

                $reader->setReadDataOnly(TRUE);

                $excel = $reader->load($file);

                $writer = factory::createWriter($excel, 'CSV');

                $pos = strrpos($file, '.');

                $file = substr($file, 0, $pos + 1) . 'csv';

                $writer->save($file);
            }
        }

        return $file;
    }

    /*
    ****************************************************************************
    */

    static function getUserIByUsername($model, $username)
    {
        $userInfo = $model->lookUp($username);

        if (! $userInfo) {
            return;
        }

        return $userInfo['id'];
    }

    /*
    ****************************************************************************
    */

    static function checkDataUpload($app, $fileName)
    {
        $containers = new \tables\inventory\containers($app);
        $measure = new \tables\inventory\measure($app);
        $locations = new \tables\locations($app);

        $columns = self::$palletSheetFields;
        $colCount = count($columns);

        $handle = fopen($fileName, 'r');

        $row = 0;

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {

            $trimmed = array_map('trim', $data);

            if (! $row) {

                self::$errorMsg = self::checkHeaderErrors($columns, $trimmed);

                if (self::$errorMsg) {
                    return ['errors' => self::$errorMsg];
                }

                $row++;

                continue;
            }

            $padded = array_pad($trimmed, $colCount, NULL);
            $values = array_combine($columns, $padded);

            $container = $values['container'];
            $measurement = $values['measure'];
            $client = $values['client'];
            $warehouse = $values['warehouse'];
            $location = strtoupper($values['location']);

            self::$containerUsed[$container] = self::$measureUsed[$measurement] =
            self::$clientUsed[$client] = self::$warehouseUsed[$warehouse] =
            self::$containerMeasureIDs[$container][$measurement] =
            self::$locationUsed[$location] = TRUE;
        }

        $containerUsed = array_keys(self::$containerUsed);
        $measureUsed = array_keys(self::$measureUsed);
        $clientUsed = array_keys(self::$clientUsed);
        $warehouseUsed = array_keys(self::$warehouseUsed);
        $locationUsed = array_keys(self::$locationUsed);

        // check for duplicate containers
        $duplicateContainers = $containers->getContainers($containerUsed);

        if ($duplicateContainers) {
            self::$errorMsg['wrongContainers'] = array_keys($duplicateContainers);
        }

        // check for measurement systems
        $unexpectedMeasures = array_diff_key(self::$measureUsed, self::$measures);

        if ($unexpectedMeasures) {
            self::$errorMsg['unexpectedMeasure'] = array_keys($unexpectedMeasures);
        }

        self::$measurementSystems = $measure->getMesaurements();

        // check for different client names
        $unexpectedClients = array_diff($clientUsed, [self::$client]);

        if ($unexpectedClients) {
            self::$errorMsg['vendorMismatch'] = $unexpectedClients;
        }

        // check for different warehouses
        $unexpectedWarehouses = array_diff($warehouseUsed, [self::$warehouse]);

        if ($unexpectedClients) {
            self::$errorMsg['wrongWarehouses'] = $unexpectedWarehouses;
        }

        // check for missing locations
        self::$locationNames = $locations->checkWarehouseLocation($locationUsed,
                self::$warehouseID);

        $missingLocations = array_diff_key(self::$locationUsed,
                self::$locationNames);

        if ($missingLocations) {

            self::$errorMsg['missingLocations'] = array_keys($missingLocations);

            self::locationInsertBuilder();
        }

        // check for multiple Measurement Sysytems per Container Name
        foreach (self::$containerMeasureIDs as $container => $measurements) {
            if (count($measurements) > 1) {
                self::$errorMsg['multipleContainerMeasures'][] = $container;
            } else {

                $measurementSystem = key($measurements);

                $dbName = self::$measures[$measurementSystem]['dbName'];

                $measureID = self::$measurementSystems[$dbName];

                self::$containerMeasureIDs[$container] = $measureID;
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function processDataUpload($app, &$upcs, $fileName)
    {
        $containerBatches = $inventoryContainers = $tallies = $newUPCs =
                $neededLocations = [];

        $cartonsClass = new \tables\inventory\cartons($app);

        $columns = self::$palletSheetFields;

        $colCount = count($columns);

        $handle = fopen($fileName, 'r');

        $row = 0;

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {

            if (! $row) {

                $row++;

                continue;
            }

            $trimmed = array_map('trim', $data);

            $padded = array_pad($trimmed, $colCount, NULL);
            $values = array_combine($columns, $padded);

            $upcIndex = NULL;

            self::setUpcIDFromUploadData([
                'values' => &$values,
                'upcs' => &$upcs,
                'newUPCs' => &$newUPCs,
                'upcIndex' => &$upcIndex,
            ]);

            $location = strtoupper($values['location']);

            $dimensionErrors = self::checkMeasureFromUploadData([
                'values' => &$values,
                'cartonsClass' => $cartonsClass,
                'row' => $row,
            ]);

            $row++;

            if ($dimensionErrors) {
                continue;
            }

            $locID = self::$locationNames[$location];

            // Group the inventory into batches by UPC/SKU/UOM/location
            self::groupInventoryIntoBatches([
                'values' => &$values,
                'inventoryContainers' => &$inventoryContainers,
                'tallies' => &$tallies,
                'neededLocations' => &$neededLocations,
                'locID' => $locID,
                'upcIndex' => $upcIndex
            ]);
        }

        $results = [
            'containerBatches' => $containerBatches,
            'inventoryContainers' => $inventoryContainers,
            'tallies' => $tallies,
            'neededLocations' => $neededLocations,
            'newUPCs' => $newUPCs
        ];

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function processMissingLocations()
    {
        self::$errorMsg['missingLocations'] =
                array_keys(self::$errorMsg['missingLocations']);

        foreach (self::$errorMsg['missingLocations'] as $missingLoc) {

            $insert = self::locationInsertBuilder($missingLoc);
            self::$errorMsg['insert'][] = $insert;
        }
    }

    /*
    ****************************************************************************
    */

    static function putInRealUpcs(&$tallies, $upcs)
    {
        foreach ($tallies as $container => $row) {
            foreach ($row['upcs'] as $batchIndex => $upcIndex) {
                $tallies[$container]['upcs'][$batchIndex] =
                    $upcs[$upcIndex]['upc'];
            }
        }

        foreach ($tallies as $container => $tally) {
            foreach (array_keys($tally) as $value) {
                if ($value == 'warehouse') {
                    continue;
                }

                $tallies[$container][$value] = array_values($tally[$value]);
            }
        }
    }

    /*
    ****************************************************************************
    */

    static function getDataForInsertInventoryContainer($params)
    {
        $inventoryContainers = $params['inventoryContainers'];
        $containerBatches = &$params['containerBatches'];
        $inactiveStatus = $params['inactiveStatus'];

        $recNums = $containers = $batches = $cartons = [];

        foreach ($inventoryContainers as $containerName => $container) {

            $recNums[$containerName] = self::$recNum;

            $values = reset($container);

            $containers[$containerName] = [
                $values['container'],
                self::$userID,
                self::$clientID
            ];

            self::getBatchesCartonsFromContainer([
                'container' => $container,
                'batches' => &$batches,
                'cartons' => &$cartons,
                'containerBatches' => &$containerBatches,
                'inactiveStatus' => $inactiveStatus
            ]);

            self::$recNum++;
        }

        $results = [
            'recNums' => $recNums,
            'containers' => $containers,
            'batches' => $batches,
            'cartons' => $cartons,
        ];

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getBatchesCartonsFromContainer($params)
    {
        $container = $params['container'];
        $batches = &$params['batches'];
        $cartons = &$params['cartons'];
        $containerBatches = &$params['containerBatches'];
        $inactiveStatus = $params['inactiveStatus'];

        if (! $container) {
            return FALSE;
        }

        foreach ($container as $values) {
            // Get batches by container
            $containerBatches[self::$recNum][] = self::$batch;

            $uom = $values['uom'];

            if ($uom < 1) {
                continue;
            }

            $values = self::setEachDimensions($values, $uom);

            $batches[self::$batch] = [
                self::$recNum,
                $values['upcID'],
                $values['prefix'],
                $values['suffix'],
                $values['height'],
                $values['width'],
                $values['length'],
                $values['weight'],
                $values['eachHeight'],
                $values['eachWidth'],
                $values['eachLength'],
                $values['eachWeight'],
                $values['initialCount'],
            ];

            $cartons[] = [
                self::$batch,
                $values['uom'],
                $inactiveStatus,
                $inactiveStatus,
                $values['initialCount'],
            ];

            self::$batch++;
        }
    }

    /*
    ****************************************************************************
    */

    static function getAllContainerBatches($containerBatches)
    {
        $allBatches = [];

        if (! $containerBatches) {
            return $allBatches;
        }

        $index = 0;

        foreach ($containerBatches as $batches) {

            $counter = 1;

            foreach ($batches as $batch) {
                $allBatches[$index][] = $batch;
                $index += $counter++ % 100 ? 0 : 1;
            }

            $index++;
        }

        return $allBatches;
    }

    /*
    ****************************************************************************
    */

    static function setUpcIDFromUploadData($params)
    {
        $values = &$params['values'];
        $upcs = &$params['upcs'];
        $newUPCs = &$params['newUPCs'];
        $upcIndex = &$params['upcIndex'];

        $sku = $values['sku'] = trim(strtoupper($values['sku']));
        $color = $values['color'] = trim(strtoupper($values['color']));
        $size = $values['size'] = trim(strtoupper($values['size']));

        $upcIndex = $sku . $color . $size;

        if (isset($upcs[$upcIndex])) {
            $values['upcID'] = $upcs[$upcIndex]['id'];
        } else {
            $newUPCs[$upcIndex]['sku'] = $sku;
            $newUPCs[$upcIndex]['color'] = $color;
            $newUPCs[$upcIndex]['size'] = $size;
        }
    }

    /*
    ****************************************************************************
    */

    static function checkMeasureFromUploadData($params)
    {
        $values = &$params['values'];
        $cartons = $params['cartonsClass'];
        $row = $params['row'];

        $dimensionErrors = self::checkBatch([
            'cartonsClass' => $cartons,
            'values' => $values,
            'row' => $row,
        ]);

        if ($dimensionErrors) {
            self::$errorMsg['dimensions'] = array_merge(
                    getDefault(self::$errorMsg['dimensions'], []),
                    $dimensionErrors
            );
        }

        return $dimensionErrors;
    }

    /*
    ****************************************************************************
    */

    static function groupInventoryIntoBatches($params)
    {
        $values = &$params['values'];
        $inventoryContainers = &$params['inventoryContainers'];
        $tallies = &$params['tallies'];
        $neededLocations = &$params['neededLocations'];
        $locID = $params['locID'];
        $upcIndex = $params['upcIndex'];
        $initialCount = $values['initialCount'];
        $container = $values['container'];
        $locationName = $values['location'];
        $sku = $values['sku'];
        $color = $values['color'];
        $size = $values['size'];
        $uom = $values['uom'];
        $length = $values['length'];
        $width = $values['width'];
        $height = $values['height'];
        $weight = $values['weight'];

        $batchIndex = $sku . '-' . $color . '-' . $size . '-' . $height . '-'
                . $width . '-' . $length . '-' . $weight . '-' . $uom . '-'
                . $locID;

        if (isset($inventoryContainers[$container][$batchIndex])) {

            $inventoryContainers[$container][$batchIndex]['initialCount']
                += $initialCount;

            $currentIndex =
                count($tallies[$container]['cartons'][$batchIndex]) - 1;

            $tallies[$container]['cartons'][$batchIndex][$currentIndex] +=
                $initialCount;

        } else {
            // Only add tally if this is a new tally row
            // This is just a place holder until I can query the real UPC
            $tallies[$container]['uoms'][$batchIndex] = $uom;
            $tallies[$container]['upcs'][$batchIndex] = $upcIndex;
            $tallies[$container]['styles'][$batchIndex] = $sku;
            $tallies[$container]['cartons'][$batchIndex][] = $initialCount;
            $neededLocations[] = $tallies[$container]['locations'][$batchIndex][] =
                    $locationName;
            $tallies[$container]['warehouse'] = self::$warehouse;

            unset($values['warehouse'], $values['client'], $values['index'],
                $values['palletSheetID'], $values['measure']);

            $inventoryContainers[$container][$batchIndex] = $values;
        }
    }

    /*
    ****************************************************************************
    */

    static function getLableDirPath($outputDir, $warehouse=NULL)
    {
        $warehouse = $warehouse ? $warehouse : self::$warehouse;

        if (in_array($outputDir, ['Plates', 'UCCLabels', 'RCLabels'])) {

            $warehousePrefix = strtolower($warehouse);

            $dir = $warehousePrefix . 'InventoryImports' . $outputDir;

            return \models\directories::getDir('uploads', $dir);
        } else {
            die('Invalid directory was requested');
        }
    }

    /*
    ****************************************************************************
    */

    static function setEachDimensions($values, $uom)
    {
        $values['eachLength'] = getDefault($values['eachLength'], $values['length']);
        $values['eachWidth'] = getDefault($values['eachWidth'], $values['width']);

        $values['eachHeight'] = isset($values['eachHeight']) ?
                $values['eachHeight'] :
                round($values['height'] / $uom, self::NUMBER_PRECISION_BATCHES);

        $values['eachWeight'] = isset($values['eachWeight']) ?
                $values['eachWeight'] :
                round($values['weight'] / $uom, self::NUMBER_PRECISION_BATCHES);
        // dimensions can not be zero
        $values['eachHeight'] = max($values['eachHeight'], 0.01);
        $values['eachWeight'] = max($values['eachWeight'], 0.01);

        return $values;
    }

    /*
    ****************************************************************************
    */

    static function getVendorData($app)
    {
        $vendors = new \tables\vendors($app);

        $vendorData = $vendors->getVendorDataByID(self::$clientID);

        self::$client = $vendorData['vendor'];
        self::$warehouse = $vendorData['warehouse'];
        self::$warehouseID = $vendorData['warehouseID'];

        if (! self::$client) {
            die('Vendor was not found');
        }

        if (! self::$warehouse) {
            die('Warehouse was not found');
        }
    }

    /*
    ****************************************************************************
    */

    static function updateUPCIDToInventoryContainerData($params)
    {
        $inventoryContainers = &$params['inventoryContainers'];
        $newUPCs = $params['newUPCs'];
        $startUPCID = $params['maxUPCID'];

        foreach ($newUPCs as $upc) {
            foreach ($inventoryContainers as &$container) {

                self::updateUPCIDBatches([
                    'container' => &$container,
                    'startUPCID' => $startUPCID,
                    'upc' => $upc,
                ]);
            }

            $startUPCID++;
        }

    }

    /*
    ****************************************************************************
    */

    static function updateUPCIDBatches($params)
    {
        $container = &$params['container'];
        $startUPCID = $params['startUPCID'];
        $upc = $params['upc'];

        foreach ($container as &$batches) {
            if ($upc['sku'] == $batches['sku']
                && $upc['color'] == $batches['color']
                && $upc['size'] == $batches['size']
            ) {
                   $batches['upcID'] = $startUPCID;
               }
        }
    }

    /*
    ****************************************************************************
    */

}