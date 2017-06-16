<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

use common\logger;
use tables\inventory\cartons;

class model extends base
{

    public $rowInputs = [
        'UCC128' => 'UCC',
        'UOM_A' => 'uomA',
        'UOM_B' => 'uomB'
    ];

    public $alreadySplit = [];

    public $newUCCs = [];

    public $uccs = [];

    public $loop = 20;

    public $parents = [];

    public $maxCartons = [];

    public $isSplit = [];

    public $notReprinting = FALSE;

    public $styles = FALSE;

    public $maps = [];

    public $addCartons = NULL;

    public $batchNumber = 0;

    public $posiblePlates = NULL;

    public $userClient = NULL;

    public $vendors = NULL;

    public $isReprint = FALSE;

    public $nextID = 0;

    public $unsplit = FALSE;

    public $modelName = NULL;

    public $ajax = NULL;

    public $datableFilters = [
        'warehouseID',
        'warehouseType'
    ];

    public $warehouseType = [
        -1 => 'All',
        0 => 'Regular',
        1 => 'Mezzanine'
    ];

    public $sizeHeight = 5;
    
    public $defaultShowData = 100;

    /*
    ****************************************************************************
    */

    function modelSetListOfSplitPages()
    {
        ob_start(); ?>

        <span class="message dontPrint">
            <a href="<?php echo makeLink('inventory', 'splitAll'); ?>">
                Split All Cartons</a>
        </span>
        <span class="message dontPrint">
            <a href="<?php echo makeLink('inventory', 'search', [
                'split' => 'cartons'
            ]); ?>">
                Split Cartons by Piece Count</a>
        </span>
        <span class="message dontPrint">
            <a href="<?php echo makeLink('inventory', 'splitter'); ?>">
                Split Cartons into Two Cartons</a>
        </span>
        <span class="message dontPrint">
            <a href="<?php echo makeLink('inventory', 'components', [
                'show' => 'batches',
                'modify' => 'splitBatches'
            ]); ?>">
                Split Cartons by Batch</a>
        </span>

        <?php

        $this->listOfSplitPages = ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function split($target, $value, $cartons)
    {
        in_array($target, ['name', 'batchID']) || die('Invalid split target');

        $ucc128 = $cartons->fields['ucc128']['select'];

        $sql = 'SELECT    ' . $ucc128 . ',
                          uom
                FROM      inventory_containers co
                JOIN      inventory_batches b ON co.recNum = b.recNum
                JOIN      inventory_cartons ca ON b.id = ca.batchID
                WHERE     ' . $target . ' = ?
                AND       NOT isSplit
                AND       NOT unSplit
                AND       uom > 1';

        $results = $this->queryResults($sql, [$value]);

        if (! $results) {

            $targetName = $target == 'name' ? 'Container Name' : 'Batch Number';

            return [
                'error' => [$targetName . ' is not found or no cartons to split']
            ];
        }

        $splitData = [];

        foreach ($results as $ucc => $values) {
            $splitData[$ucc] = array_fill(0, $values['uom'], 1);
        }

        return $cartons->split($splitData);
    }

    /*
    ****************************************************************************
    */

    function getNextInventoryID()
    {
        $sql = 'SELECT  AUTO_INCREMENT AS nextID
                FROM    information_schema.tables
                WHERE   table_name = "inventory_cartons"';

        $result = $this->queryResult($sql);

        return $result['nextID'];
    }

    /*
    ****************************************************************************
    */

    function modelGetNextCartonID($batch)
    {
        $cartons = new cartons($this);

        // Get largest carton ID in the batch
        $sql = 'SELECT  MAX(ca.cartonID) AS largestCartonID
                FROM    ' . $cartons->table . '
                WHERE   b.id = ?';

        $result = $this->queryResult($sql, [$batch]);

        return $largestCartonID = $result['largestCartonID'] + 1;
    }

    /*
    ****************************************************************************
    */

    function mapStyles($target, $value, $cartons)
    {
        in_array($target, ['name', 'batchID']) || die('Invalid split target');

        $ucc128 = $cartons->fields['ucc128']['select'];

        $table = 'inventory_containers co
            JOIN      inventory_batches b ON co.recNum = b.recNum
            JOIN      inventory_cartons ca ON b.id = ca.batchID';

        $sql = 'SELECT    c.id,
                          ' . $ucc128 . ' AS parent,
                          sku,
                          c.ucc AS child
                FROM      ' . $table . '
                JOIN      inventory_splits sp ON ca.id = sp.parentID
                JOIN      upcs u ON u.id = b.upcID
                JOIN  (
                    SELECT    ca.id,
                              ' . $ucc128 . ' AS ucc
                    FROM      ' . $table . '
                    WHERE     ' . $target .' = ?
                ) AS c ON c.id = sp.childID
                ORDER BY ' . $ucc128 . ' ASC,
                         c.ucc ASC';

        $results = $this->queryResults($sql, [$value]);

        foreach ($results as $row) {

            $parent = $row['parent'];

            $this->styles[$parent] = $row['sku'];
            $this->maps[$parent][] = $row['child'];
        }
    }

    /*
    ****************************************************************************
    */

    function styleHistoryReportMysqlFilter()
    {
        return [
            'trigger' => TRUE,
            'searches' => [
                [
                    'selectField' => 'Receiving Date Starting',
                    'selectValue' => date('Y-m-d', strtotime('-1 DAY')),
                    'clause' => 'logTime > NOW() - INTERVAL 1 DAY',
                ],
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function customSearchForm() {
        ?>
        <div id="searcher">
            <table>
                <tbody>
                <tr>
                    <td class="clauses">
                        <label>Warehouse Name: </label>
                        <select class="warehouse" id="warehouse-input" name="warehouse" data-post>
                            <option value="0">Select a Warehouse</option>
                            <?php foreach ($this->warehouse as $id => $row) {?>
                                <option value="<?php echo $id; ?>">
                                    <?php echo $row['displayName']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                    <td class="vendor-name">
                        <label>Customer: </label>
                        <select id="customer-input" name="customer" data-post>
                            <option value="0">Select Customer</option>
                            <?php foreach ($this->vendors as $id => $row) {?>
                                <option value="<?php echo $id; ?>">
                                    <?php echo $row['fullVendorName']; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </td>
                    <td class="clauses">
                        <label>Created Starting: </label>
                        <input type="text" name="created-starting" class="created-date">
                    </td>
                    <td class="clauses">
                        <label>Created Ending: </label>
                        <input type="text" name="created-ending" class="created-date">
                    </td>
                </tr>
                </tbody>
            </table>
        </div>

        <?php
    }

}
