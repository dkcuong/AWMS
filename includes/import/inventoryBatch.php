<?php

namespace import;

use \tables;
use \common\seldatContainers;
use \common\receiving;

class inventoryBatch extends seldatContainers
{
    public $data = [];
    public $badSKU = [];
    public $myApp;
    public $myUpcs;
    public $msgError;
    static $fileNameBadUpcs = 'badUPC';
    static $alowColumns = [
        'sku', 'suffix', 'size', 'color', 'uom', 'ctns', 'location'
    ];
    
    static $columnCarton = 'carton';
    /*
    ****************************************************************************
    */

    public function __construct($app, $data = NULL)
    {
        $this->myApp = $app;
        //Remove header data
        if (! $data) {
            return;
        }
        array_shift($data);
        $this->data = $data;

        //Get All UPCs
        $this->myUpcs = $this->getUpcs();
    }

    /*
    ****************************************************************************
    */

    public function getKey($name)
    {
        $header = array_flip($this->headerFormat);

        return isset($header[$name]) ? $header[$name] : 0;
    }

    /*
    ****************************************************************************
    */

    public function checkBadGoodUpcs($batch, &$result)
    {
        $upcs = new \tables\upcs($this->myApp);
        foreach ($this->myUpcs as $val) {
            
            $sku = self::cleanData($val['sku']);
            $color = self::cleanData($val['color']);
            $size = self::cleanData($val['size']);
            
            if ($sku == $batch['sku'] && $color == $batch['color']
                && $size == $batch['size']
            ) {
                $style = $sku . '-' . $size . '-' . $color;

                if (in_array($style, $this->badSKU)) {
                    
                    $duplicateUpcs = $this->getDuplicateUpcs($batch);
                    $duplicateUpcs = array_keys($duplicateUpcs);
                    
                    $batch['notes'] = 'SKU, Size, Color in duplicate upcs: '
                            . implode(', ', $duplicateUpcs);
                    $result['badUpcs'][] = $batch;
                    return FALSE;
                }
                
                $upc = $upcs->getStyleRows($val['upc']);
                unset($upc['suffix']);

                $batch = array_merge($batch, $upc);
                $result['goodUpcs'][] = $batch;
                return TRUE;
            }
        }

        $batch['notes'] = 'Not found SKU, Size, Color';
        $result['badUpcs'][] = $batch;
    }

    /*
    ****************************************************************************
    */

    public function getBadGoodUpcs()
    {
        $result = [
            'goodUpcs' => [],
            'badUpcs' => []
        ];

        foreach ($this->data as $batch) {
            if (! $batch['sku']) {
                continue;
            }

            $this->checkBadGoodUpcs($batch, $result);
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    public function getUpcs()
    {
        $sku = [];
        
        if (! $this->data) {
            return $sku;
        }

        foreach ($this->data as $batch) {
            $sku[] = $batch['sku'];
        }

        $qMarks = $this->myApp->getQMarkString($sku);
        $sql = 'SELECT  id,
                        upc,
                        TRIM(sku) AS sku,
                        TRIM(size) AS size,
                        TRIM(color) AS color
                FROM    upcs
                WHERE   TRIM(sku) IN (' . $qMarks . ')
                AND     active';

        $result = $this->myApp->queryResults($sql, $sku);
        
        $this->checkBadSKU($result);
        
        return $result;
    }

    /*
    ****************************************************************************
    */
    
    static function importInventoryBatches($app)
    {
        $app->post['editContainer'] = FALSE;
       
        self::$tableCells = self::getTableCells();
        
        self::checkValidateLocation($app);
        
        $result = self::submitContainer($app);
        
        if (! isset($result['recNum'])) {
            return $result;
        }
        
        self::completeRCLogApp($app, $result['recNum']);
        
        return $result;
    }

    /*
    ****************************************************************************
    */

    static function displayField($params)
    {
        $str = 'type="text" ';        
        foreach ($params as $attr => $value) {
            if ($value === NULL) {
                continue;
            }            
            
            $str .= $attr .= '="' . $value . '" ' ;            
        }
        
        $str .='data-post ';
        
        $result = '<td>';        
        $result .= '<input ' . $str .'/>';        
        $result .= '</td>';
        
        return $result;
    }
    
    /*
    ****************************************************************************
    */
    
    static function completeRCLogApp($app, $recNum) 
    {
        $params = self::setParamsForRCLog($recNum);
        //Update Container
        receiving::getContainerInfoForRC($app, $params['container']);
        //Save RC Log
        receiving::updateRCLogPrint($app, $recNum);
        //Save RC Label
        self::updateRcLabelPrinted($app, $recNum);
        //Complete RC Log
        $app->post = $params;
        
        receiving::completeRCLog($app, $recNum);
    }

    /*
    ****************************************************************************
    */
    
    static function updateRcLabelPrinted($app, $recNum) 
    {
        if (! $recNum) {
            return FALSE;
        }
        
        $sql = 'UPDATE    inventory_containers co
               LEFT JOIN tallies t ON t.recNum = co.recNum
               SET       rcLabelPrinted = 1
               WHERE     co.recNum = ?';
        
        $result = $app->runQuery($sql, [$recNum]);
        
        return $result;
    }

    /*
    ****************************************************************************
    */
    
    static function setParamsForRCLog($recNum) 
    {
        $upcs = $styles = $locations = $uoms = $cartons = $batches = [];
        
        foreach (self::$tableData as $count => $rowData) {
           
            $upcs[] = $rowData['upc'];
            $styles[] = $rowData['sku'];
            $locations[] = $rowData['location'];
            $uoms[] = $rowData['uom'];
            $cartons[] = $rowData['carton'];
            $batches[] = self::$batchPerLine[$count];
        }
        
        $params = [
            'recNum' => $recNum,
            'container' => self::$post['container'],
            'rowCount' => 1,
            'styles' => $styles,
            'upcs' => $upcs,
            'batches' => $batches,
            'uoms' => $uoms,
            'locations' => $locations,
            'cartons' => $cartons,
        ];
        
        return $params;
    }
    
    /*
    ****************************************************************************
    */

    static function checkValidateLocation($app)
    {
        if (! getDefault($app->post['tableData'])) {
            return FALSE;
        }
        
        $tableData = $app->post['tableData']; 
        $receivingID = $app->post['receiving'];
        $vendor = new \tables\vendors($app);
        $receiving = new tables\receiving($app);
        $vendorID = $receiving->getClientID($receivingID);

        
        $vendorName = $vendor->getVendorName($vendorID);
        $warehouseOfVendor = $vendor->getVendorWarehouse($vendorID);
        
        if (! $warehouseOfVendor) {
            self::$errors[] = [
                    'field' => 'Client',
                    'error' => 'is invalid!'
                ];
            return FALSE;
        }
        
        $locationsInWareHouse = \tables\locations::getLocationsByWarehouseId(
                $app, $warehouseOfVendor);
        
        if (! $locationsInWareHouse) {
            
            
            self::$errors[] = [
                    'field' => 'Client#',
                    'error' => '"' . $vendorName . '" ' . 
                               'do not have location!'
                ];
            return FALSE;
        }
        
        $locationNamesInWareHouse = array_keys($locationsInWareHouse);
        
        foreach ($tableData as $count => $rowData) {
            $row = $count + 1;
            
            if (! in_array($rowData['location'], $locationNamesInWareHouse)) {
                self::$errors[] = [
                    'field' => 'Location',
                    'error' => 'at row ' . $row . ' "' . $rowData['location'] .
                            '" has not found at ' . $vendorName .' client'
                ];
            } else {
                self::checkLocationInvalidReceiving([
                    'locationsInWareHouse' => $locationsInWareHouse,
                    'locationName' => $rowData['location'],
                    'row' => $row,
                ]);
            }
        }
    }

    /*
    ****************************************************************************
    */
    
    static function buildImportDatatable($data)
    {        
        $tableCells = self::getTableCells();
        
        //build row title
        ?>
        <table id="scanContainerTable" width="80%" border="1"> <?php
        foreach ($tableCells as $cell => $cellInfo) {
            switch ($cell) {
                case 'categoryUPC':
                    break;
                case 'newUPC':
                    break;   
                case 'tableFunc':
                    break;      
                default: 
                    $cellClass = isset($cellInfo['class']) ? 
                        ' class="'.$cellInfo['class'].'"' : NULL; 

                    $class = isset($cellInfo['dimension']) ? 
                        ' class="unitDimensions"' : NULL; 

                    $spanClass = $class && $cellInfo['dimension'] == 'weight' ? 
                        ' class="unitWeight"' : $class;
                    
                    $title = empty($cellInfo['title']) ? NULL :
                        ' title="' . $cellInfo['title'] . '"';
                    $colTitle = isset($cellInfo['colTitle']) ? $cellInfo['colTitle'] :
                        $cellInfo['cellName']; ?>
                <td <?php echo $cellClass . $title; ?>><strong><?php 
                        echo $colTitle; ?></strong><span <?php 
                        echo $spanClass;?>></span> </td><?php
                
                    break;
            }
        } 
        
        //build row body
        $row = 0;
        $num = count($data);
        while ($row < $num) {
            $item = $data[$row];
            $oddRowsClass = $row % 2 ? NULL : 'oddRows'; 
            $fifthRowsClass = ($row + 1) % 5 == 0 ? ' fifthMarked' : NULL;
        ?>
        <tr class="batchRows <?php echo $oddRowsClass . $fifthRowsClass; ?>" 
            id="row-<?php echo $row; ?>"><?php

            foreach ($tableCells as $cell => $cellInfo) {
                switch ($cell) {
                    case 'rowNo': ?>
        <td class="firstCol">
            <span class="idxCtn"><?php echo $row + 1; ?></span>

            <input type="hidden" class="categoryUPC"
                   name="categoryUPC" value="<?php echo $item['categoryUPC'] ?>"
                   data-row-index="<?php echo $row; ?>" data-post>
        </td>
            <?php
                    break;
                case 'categoryUPC':
                    break;
                case 'newUPC':
                    break; 
                case 'tableFunc':
                    break;                
                default:
                    $size = empty($cellInfo['size']) ? NULL : $cellInfo['size'];
                    $rel = empty($cellInfo['inputRel']) ? NULL : 
                        $cellInfo['inputRel'];
                    
                    echo inventoryBatch::displayField([
                        'name' => $cell,
                        'value' => $item[$cell],
                        'data-row-index' => $row,
                        'class' => $cell,
                        'size' => $size,
                        'rel' => $rel
                    ]);
                    break;
                }
            }
        ?>
        </tr>
        <?php
            $row++;
        }
        ?></table>
        <?php
    }
    
    /*
    ****************************************************************************
    */
    
    static function getTableCells()
    {
        $tableCells = self::$tableCells;   
        
        //add column location to tableCells
        $field['location'] = [
            'class' => 'red',
            'cellName' => 'LOCATION',
            'size' => 12,
            'minWidth' => 1,
        ];
        
        self::arrayInsert($tableCells, 10, $field);
        
        return $tableCells;
    }
    
    /*
    ****************************************************************************
    */
    
    static function arrayInsert(&$array, $position, $insertArray)
    { 
        $firstArray = array_splice($array, 0, $position); 
        $array = array_merge($firstArray, $insertArray, $array); 
    } 
   
    /*
    ****************************************************************************
    */
    
    static function processUploadFile(&$app)
    {
        if (empty($app->post['import']) || ! $app->fileSubmitted) {
            return;
        }
        
        $reader = new \excel\importer($app);
        $reader->uploadPath = \models\directories::getDir('uploads', 'imports');
        $rows = $reader->getRowsInventoryBatchFile();

        if (! self::validateFileConlumns($reader, $rows)) {
            return FALSE;
        }
        
        $data = $reader->parseToArray($rows);
        
        $importer = new inventoryBatch($app, $data);
        $upcs = $importer->getBadGoodUpcs();
        $app->data = $upcs['goodUpcs'];
        
        $app->countBadUpcs = 0;
        if (isset($upcs['badUpcs'])) {
            $app->countBadUpcs = count($upcs['badUpcs']);
            //register sesion of bad upc
            $_SESSION['badUpcs'] = serialize($upcs['badUpcs']);                
        }
        
        return TRUE;
    }

    /*
    ****************************************************************************
    */
     
    static function processDownloadBadUpcs(&$app) 
    {
        $downLoadBadUpcs = getDefault($app->get['downLoadBadUpcs'], 0);  
    
        if (! $downLoadBadUpcs || empty($_SESSION['badUpcs'])) {
            return;
        }   
        
        $exporter = new \excel\exporter($app);

        $badUpcs = unserialize($_SESSION['badUpcs']);

        $fieldKeysTmp = array_keys($badUpcs[0]);

        foreach ($fieldKeysTmp as $key) {
            $fieldKeys[] = ['title' => $key];
        }

        // exel output then exist
        $exporter->ArrayToExcel([
            'data' => $badUpcs, 
            'fileName' => self::$fileNameBadUpcs,
            'fieldKeys' => $fieldKeys,
        ]);
        
    }
    
    /*
    ****************************************************************************
    */
    
    static function validateFileConlumns($reader, $rows)
    {
        foreach ($rows as $row) {
            $getRow = $reader->getRow($row);
            $rowData = $getRow['rowData'];
            
            $nHeaderColumns = count(self::$alowColumns) - 1;
            
            for ($i = 0; $i < $nHeaderColumns; $i++) {
                $colValue = strtolower($rowData[$i]);
                
                if (! in_array($colValue, self::$alowColumns)) {     
                    return FALSE;
                }
            }
            
            return TRUE;
        }
    }
    
    /*
    ****************************************************************************
    */
    
    static function formatHeaderColumns($headerColumns)
    {
        $results = [];
        
        foreach ($headerColumns as $key => $val) {
            
            $val = strtolower($val);
            $results[$key] = $val;
            
            if ('ctns' == $val) {
                $results[$key] = self::$columnCarton;
            }
        } 
        
        return $results;
    }
    
    /*
    ****************************************************************************
    */

    static function cleanData($val, $type = 'upperCase')
    {
        $val = trim($val);
        
        return $type == 'upperCase' ? strtoupper($val) : strtolower($val);
    }

    /*
    ****************************************************************************
    */
    
    function checkBadSKU($upcs)
    {
        if (! $upcs) {
            return NULL;
        }

        $tmpSKU = [];
        
        foreach ($upcs as $val) {
            $style = $val['sku'] . '-' . $val['size'] . '-' . $val['color'];
            
            if (in_array($style, $tmpSKU)) {
                $this->badSKU[] = $style;
            } else {
                $tmpSKU[] = $style;
            }
        }
    }

    /*
    ****************************************************************************
    */
    
    function getDuplicateUpcs($batch)
    {
        if (! $batch) {
            return FALSE;
        }
        
        $sql = 'SELECT  upc
                FROM    upcs
                WHERE   TRIM(sku) = ? 
                AND     TRIM(size) = ? 
                AND     TRIM(color) = ?
                AND     active';

        $results = $this->myApp->queryResults($sql, [
            $batch['sku'],
            $batch['size'],
            $batch['color']
        ]);
        
        return $results;
    }
    
    /*
    ****************************************************************************
    */

    static function checkLocationInvalidReceiving($params) 
    {
        $locationsInWareHouse = $params['locationsInWareHouse'];
        $locationName = $params['locationName'];
        $row = $params['row'];
        
        if ($locationsInWareHouse[$locationName]['isShipping']) {
             self::$errors[] = [
                    'field' => 'Location',
                    'error' => 'at row ' . $row . ' "' . $locationName .
                            '" is Shipping Location!'
                ];
        }
        
        if ($locationsInWareHouse[$locationName]['isMezzanine']) {
             self::$errors[] = [
                    'field' => 'Location',
                    'error' => 'at row ' . $row . ' "' . $locationName .
                            '" is Mezzanine Location!'
                ];
        }
    }

    /*
    ****************************************************************************
    */
}
