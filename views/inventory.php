<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    /*
    ****************************************************************************
    */

    function importPalletSheetsInventoryView()
    { ?>
        <form method="post">
            <input type="text" name="directoryPath">
            <input type="submit" name="submit" value="Sumbit">
        </form><?php
    }

    /*
    ****************************************************************************
    */

    function createTallyInventoryView()
    { ?>
        <div class="dontPrint" id="dontPrint">
        <div class="message forScreen">
        Enter Container: <input id="container" type="text" placeholder="(autocomplete)">
        <input id="updateContainer" type="submit" value="Submit">
        <input class="containerButtons printTallySheet" type="submit" value="Print Blank Tally Sheet">
        <input class="containerButtons" id="updateTallySheet" type="submit" value="Update Tally Sheet">
        <input class="containerButtons" id="printLabels" type="submit" value="Print Labels">
        <button id="print" class="button containerButtons" media="print">Print Page</button>
        </div>
        <div id="pagePrintContainerInfo"></div>
        <table id="tallyTable">
            <tr>
                <td>Style</td>
                <td>UPC</td>
                <td>Pallet Count</td>
                <td>Cartons Per Pallet</td>
            </tr>
            <?php for ($i=0; $i<$this->jsVars['tallyRows']; $i++) { ?>
            <tr>
                <td>
                    <input data-row="<?php echo $i; ?>" class="styles forScreen"
                           data-column="style" type="text">
                    <span class="forPagePrint"></span>
                </td>
                <td><input data-row="<?php echo $i; ?>" class="upcs" data-column="upc" type="text"></td>
                <td><input data-row="<?php echo $i; ?>" class="plateCounts" data-column="palletCount" type="text"></td>
                <td><input data-row="<?php echo $i; ?>" class="cartonCounts" data-column="palletCartons" type="text"></td>
            </tr>
            <?php } ?>
        </table>
        <br>

        </div>

        <table id="printTable" class="doPrint">
            <tr><td colspan="4">Container Tally Sheet:
                    <span id="showContainerName"></span></td></tr>
            <tr><td>Style</td>
                <td>UPC</td>
                <td>Pallet Count</td>
                <td>Cartons Per Pallet</td></tr>
        </table>
        <form id="searchForm" class="containerButtons" name="searcher" method="post">
            <input type="hidden" name="andOrs[]" value="and">
            <input type="hidden" name="searchTypes[]" value="name">
            <input id="containerInput" type="hidden" name="searchValues[]">
        </form>
        <?php
    }

    /*
    ****************************************************************************
    */

    function splitAllInventoryView()
    {
        echo $this->listOfSplitPages; ?>

        <h3 class="dontPrint">Split All Cartons of Container to Pieces</h3>
        <div class="dontPrint message splitterBox">
        <span>Enter the name of a container to split all of its cartons to pieces:</span>
        <form method="post">
        <input name="container" type="text">
        <input name="submit" type="submit" value="Submit">
        </form>
        <br><br>

        <span>Enter the name of a container that has already been split to view
              its splits without making additional splits:</span>
        <form method="post">
        <input name="getContainer" type="text">
        <input name="submit" type="submit" value="Submit">
        </form>
        </div>
        <br>

        <?php

        $this->displaySplitResults();
    }

    /*
    ****************************************************************************
    */

    function componentsInventoryView()
    {
        if ($this->batchNumber) { ?>
            <h3 id="addCartonsTitle">Add Cartons to Batch <?php echo $this->batchNumber; ?></h3>

            <form method="post">
                Add Cartons <input size="4" name="addCartons" type="text"
                    placeholder="Quantity" id ="createCartonsCount" value="<?php echo $this->addCartons; ?>">
                <?php if ($this->posiblePlates) { ?>
                <select name="posiblePlates" id="licencePlate">
                    <option value="unselected">Select License Plate</option>
                    <?php foreach ($this->posiblePlates as $info) {
                        $plateDisplay = getDefault($info['plate'], 'Unassigned'); ?>
                        to <option value="<?php echo $info['plate']; ?>"><?php echo $plateDisplay; ?></option>
                    <?php } ?>
                </select>
                <?php } ?>
                <input name="submit" type="button" id="createCartons" value="Create Cartons">
            </form>
            <?php
        }

        if ($this->get['show'] == 'locBatches') {

            echo $this->searcherHTML; ?>

            <div id="searcher">
                <form method="post" onsubmit="searchMultiLocation(this); return false;">
                    <input id="multiLocation" type="text"
                           placeholder="Input location list (separating items with whitespace)"
                           style="width:90%" />
                    <input name="submit" type="submit" value="Search" />
                </form>
            </div>
        <?php

            echo $this->datatablesStructureHTML;
        ?>
            <button onclick="fnCheckAll(this)" value="0">Uncheck/Check All</button>
            <button onclick="fnSetInActive()">Set Inactive Row Selected</button> &nbsp;&nbsp;
        <?php
            echo $this->searcherExportButton;

        } else {
            $this->ajax->multiSelectTableView($this);
        }
    }


    /*
    ****************************************************************************
    */

    function availableInventoryView()
    {
        $this->ajax->warehouseVendorMultiSelectTableView($this, 'availableInventory');
    }

    /*
    ****************************************************************************
    */

    function searchInventoryView()
    {
        if ($this->failedReprint) { ?>
            <div class="failedMessage"><?php echo $this->failedReprint; ?></div><?php
        }

        $this->ajax->multiSelectTableView($this);

        if ($this->isReprint || $this->failedReprint) {
            ?><div id="reprintButtons">
                <br>
                <button id="reprintLabels" class="reprintLabels">Reprint Labels</button>
                &nbsp&nbspfrom&nbsp&nbsp
                <input type="text" id="printFrom" size="4" value="0" />
                &nbsp&nbspto&nbsp&nbsp
                <input type="text" id="printTo" size="4" value="0" maxValue="0" />
            </div><?php
        } ?>

        <div id="splitterForm" title="Split Carton">
            <p id="oneUOM">This carton only has one unit of measure and can not be split.</p>
            <p>This carton has <span id="showUOM"></span> pieces.</p>
            <p>Enter the number of pieces you want each new carton to have:
            <input id="newUOM" type="text"> <button id="calculate">Calculate</button></p>

            <p class="calculated">After this split you will have:</p>
            <p class="calculated"><span id="cartonCount"></span>
                carton<span id="pluralCartons">s</span>
            with <span id="pieceCount"></span>  piece<span id="pluralPieces">s</span></p>

            <p class="calculated remainderMessage" id="andRemainder">and</p>

            <p class="calculated remainderMessage">1 carton with
                <span id="pieceRemainder"></span>
                piece<span id="remainderPlural">s</span></p>
            </p>

        </div><?php
    }

    /*
    ****************************************************************************
    */

    function splitterInventoryView()
    {
        echo $this->listOfSplitPages; ?>

        <h2>Split one ucc128 to two</h2><BR>

        <?php if (getDefault($this->results['error'])) { ?>
            <div class="failedMessage">
            <?php echo implode('<br>', $this->results['error']); ?>
            </div>
        <?php }

        if (getDefault($this->results['combined'])) { ?>
            <div class="successMessage" style="display: inline-block">
            <?php

            foreach ($this->results['combined'] as $ucc => $values) {

                $children = array_keys($values); ?>
                Carton <?php echo $ucc; ?> has been split to
                <?php echo implode(' and ', $children); ?></br>
            <?php } ?>
            </div>
        <?php } ?>

        <form id="splitForm" method="POST" action="splitter" name="form">

        <table id="tbl1" class="gridtable" border="1">

            <?php
            for ($i = 1; $i <= $this->loop; $i++) {
                $index = $i - 1;
            ?>
            <tr>
        <?php   foreach ($this->rowInputs as $fieldName => $name) {
                    $value = NULL;
                    $cell = $name.'_'.$index;
                    if ($this->post['UCC']) {
                        $value = isset($this->post[$name][$index])
                            ? $this->post[$name][$index] : NULL;
                    } elseif (isset($this->get[$cell])) {
                        $value = $this->get[$cell];
                    } ?>
                <td>
                <?php
                    echo $fieldName;
                ?>
                    <input class="<?php echo $name; ?>"
                        type="text" name="<?php echo $name; ?>[]"
                        value="<?php echo $value; ?>">
                </td>
            <?php
                }
            ?>
            </tr>
        <?php
            }
        ?>
        </table>
            <input type="submit" name="Submit" value="Submit">
                <?php
                foreach ($this->uccs as $ucc) {
                ?>
                <input type="hidden" name="uccs[]" value="<?php echo $ucc; ?>">
            <?php } ?>
        </form>

        <?php
        if (getDefault($this->results['combined'])) { ?>
            <form method="POST" action="<?php echo makeLink('inventory', 'barcode'); ?>">
            <?php foreach ($this->results['combined'] as $ucc => $children) {
                foreach ($children as $newUCC => $child) { ?>
                    <input type="hidden" name="uccs[]" value="<?php echo $newUCC; ?>">
                <?php }
            } ?>
                <input type="submit" name="GenerateBarCode" value="Generate Barcode">
            </form>
  <?php } ?>
        </html>
<?php
    }

    /*
    ****************************************************************************
    */

    function splitBatchesInventoryView()
    {
        $this->displaySplitResults();
    }

    /*
    ****************************************************************************
    */

    function listSplitCartonsInventoryView()
    {
        echo $this->searcherHTML;
        $modelName = $this->modelName;
        if ($this->unsplit) { ?>
            <button id="mergeCarton" data-status="off" onclick="mergeCartons(this)">Merge Carton</button>
            <br>
            <br><?php
        }
        echo datatables\structure::tableHTML($modelName);
        echo $this->searcherExportButton;
        if (! $this->unsplit) { ?>

            <button id="selectAll">Select All</button>
            <button id="deselectAll">Deselect All</button>
            <button id="printSplitLabels">Print selected labels</button>

            <form id="split" target="_blank" method="POST" style="display:none;"
                  action="<?php echo makeLink('inventory', $this->method); ?>">

                <input id="splitCartonLabels" name="uccs" type="hidden" />
            </form>

            <?php
        }
    }

    /*
    ****************************************************************************
    */

    function pickCartonsInventoryView()
    {
        $this->ajax->multiSelectTableView($this);
    }

    /*
    ****************************************************************************
    */

    function pickErrorsInventoryView()
    {
        $this->ajax->multiSelectTableView($this);
    }

    /*
    ****************************************************************************
    */

    function summaryReportInventoryView()
    {
        $this->ajax->multiSelectTableView($this, 'vendorID'); ?>

        <form id="rcLog" method="POST" target="_blank"
            action="<?php echo makeLink('receiving', 'recordTallySheets'); ?>">
            <div style="display: none;">
                <input id="name" name="name">
            </div>
        </form>

        <form id="styleLocations" method="POST" target="_blank"
            action="<?php echo makeLink('inventory', 'styleLocations'); ?>">
            <div style="display: none;">
                <input id="value" name="value">
                <input id="field" name="field">
                <input id="vendorID" name="vendorID">
            </div>
        </form>

        <form id="cartonsTable" method="POST" target="_blank"
            action="<?php echo makeLink('inventory', 'summaryCartons'); ?>">
            <div style="display: none;">
                <input id="name" name="name">
                <input id="upc" name="upc">
                <input id="prefix" name="prefix">
                <input id="suffix" name="suffix">
                <input id="uom" name="uom">
                <input id="status" name="status">
                <input id="manualStatus" name="manualStatus">
            </div>
        </form>

        <?php
    }

    /*
    ****************************************************************************
    */

    function displaySplitResults()
    {
        if (getDefault($this->results['error'])) { ?>
            <div class="failedMessage dontPrint">
            <?php echo implode('<br>', $this->results['error']); ?>
            </div><br>
        <?php }

        if (getDefault($this->results['combined'])) { ?>
            <div class="successMessage dontPrint" style="display: inline-block">
                Cartons have been split successfully</div><br>
        <?php }

        if (! empty($this->maps)) { ?>
            <button id="dddd" class="dontPrint" onClick="window.print()">Print</button><br>
        <?php }

        foreach ($this->maps as $parent => $children) {

            $style = $this->styles[$parent]; ?>

            <span style="border: 1px grey solid;
                  display: inline-block;
                  padding: 10px; margin: 10px 0;">
                <b>Original UCC: <?php echo $parent; ?>
                <br> Style: <?php echo $style; ?></b><br><br><?php
                echo implode('<br>', $children); ?>
            </span><br><?php
        }
    }

    /*
    ****************************************************************************
    */

    function summaryCartonsInventoryView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }

    /*
    ****************************************************************************
    */

    function styleLocationsInventoryView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }

    /*
    ****************************************************************************
    */

    function styleHistoryInventoryView()
    {
        $this->ajax->warehouseVendorMultiSelectTableView($this, 'styleHistoryReport');
    }

    /*
    ****************************************************************************
    */

    function sccInventoryView()
    {
        ?>
        <div id="sccButtons">
        <span id="addCatButton" class="links message">
            Create Item Category</span>    
        <span id="addItemButton" class="links message">
            Create Item</span>    
        </div>
        <div id="hideSearcher"><?php echo $this->searcherHTML; ?></div>
        <?php echo $this->datatablesStructureHTML; ?>
        <?php echo $this->searcherExportButton; ?>
        <div id="addCatDialog" title="Add Category">
        <form id="catForm">
            <div id="catExists" class="failedMessage sccErrors">
                Category already exists.
            </div>
            <input type="hidden" name="inputType" value="cat">
            <table>
                <tr><td>Category Type&nbsp;</td><td>
                    <input id="cat_type" class="popCatType" name="type">
                </td></tr>
                <tr><td>Category Name&nbsp;</td><td>
                    <input id="cat_name" name="name">
                </td></tr>
            </table>
        </form>
        </div>

        <div id="addItemDialog" title="Add Item">
        <form id="itemForm">
            <input type="hidden" name="inputType" value="item">
            <div id="catNotFound" class="failedMessage sccErrors">
                Category not found.
            </div>
            <div id="itemExists" class="failedMessage sccErrors">
                Item already exists.
            </div>
            <div id="noSeldatUPC" class="failedMessage sccErrors">
                There are no new Seldat UPCs to assign to items.
            </div>
            <table>
                <tr><td>Category&nbsp;</td><td>
                    <input class="popCat" id="cat_name" name="cat_name">
                </td></tr>
                <tr><td>Item</td>
                    <td><input name="sku"></td></tr>
                <tr><td>Size</td>
                    <td><input name="size"></td></tr>
                <tr><td>Color</td>
                    <td><input name="color"></td></tr>
                <tr><td>Description</td>
                    <td><input name="description"></td></tr>
                <tr><td>Supplier</td>
                    <td><input name="supplier"></td></tr>
                <tr><td>Quantity</td>
                    <td><input name="qty"></td></tr>
            </table>
        </form>
        </div>
        
        <div id="changeQty" title="Change Stock Quantity">
        <form>
            <input type="hidden" name="inputType" value="qty">
            <input type="hidden" id="itemID" name="itemID">
            <input type="hidden" id="testQty" name="testQty">
            
            <table>
                <tr id="changeRadios"><td colspan="2">
                    <span id="addButtonSpan">
                        <input id="addButton" type="radio" name="action" 
                               value="add"> Add
                    </span>
                    <input type="radio" name="action" 
                           value="subtract"> Subtract
                    </td></tr>
                <tr id="testOrder"><td colspan="2">
                    <input id="orderButton" type="radio" name="testOrder" 
                           value="order"> Order
                    <input type="radio" name="testOrder" 
                           value="test"> Test
                    </td></tr>
                <tr><td>Current Quantity</td>
                    <td><input id="current" readonly></td></tr>
                <tr id="changeRow"><td>
                        <span id="add">Add</span> 
                        <span id="subtract">Subtract</span> 
                        Quantity</td>
                    <td><input id="changeInput"></td></tr>
                <tr><td>Resulting Quantity</td>
                    <td><input id="new" name="resultingQty" readonly></td></tr>
                <tr><td>Item#</td>
                    <td id="displayItem"><b></b></td></tr>
                <tr class="testOnly" id="styleRow"><td>Style#</td>
                    <td><input class="alignLeft" name="style" maxlength="25"></td></tr>
                <tr id="supplierRow"><td>Supplier</td>
                    <td><input class="alignLeft" id="supplier" 
                               name="supplier" maxlength="25"></td></tr>
                <tr id="requestByRow" class="testOnly"><td>Requested By</td>
                    <td><input class="alignLeft" id="supplier" 
                               name="requestedBy" maxlength="25"></td></tr>
                <tr id="reasonTitles"><td>
                    <span id="test">Reference#</span>
                    <span id="order">Order#</span></td>
                    <td><textarea id="tranID" name="tranID"></textarea></td></tr>
            </table>
            
            
        </form>
        </div>

        <div id="historyDialog" title="Stock History">
            <table id="historyTable"></table>
        </div>

        <div id="stsDialog" title="Change Item Status">
        <form>
            <input type="hidden" id="itemID" name="itemID">
            <table>
                <tr><td>Change Item Status</td>
                    <td></td></tr>
                <tr><td><b></b></td>
                    <td><select>
                        <option>Active</option>
                        <option>Inactive</option>
                        <option>Discontinued</option>
                    </select></td></tr>
            </table>
        </form>
        </div>
        <?php 
    }

    /*
    ****************************************************************************
    */

    function receivingReportInventoryView()
    {
        $this->ajax->multiSelectTableView($this);
    }

    /*
    ****************************************************************************
    */

    function mezzanineTransferredInventoryView()
    {
        ?>
        <form action="<?php echo $this->jsVars['requestURI'];?>" method="post">
            <?php
                $this->customSearchForm();
                if ($this->message)
                    echo '<div id="alertMessages" class="alert alert-warning">' . $this->message . '</div>';
            ?>
            <button class="downloadMezzanineTransferred" name="download"
                    value="csv" type="submit">
                Download Mezzanine Transferred to CSV
            </button>
            <button class="downloadMezzanineTransferred" name="download"
                    value="excel" type="submit">
                Download Mezzanine Transferred to Excel
            </button>
        </form>
        <?php
    }

    /*
    ****************************************************************************
    */

    public function getCartonsByPlateInventoryView()
    {
        ?>

        <div class="list-button">
            <a href="<?php echo makeLink('inventory', 'getCartonsEditUomByPlates') ?>" >Back</a>
        </div>

        <div id="form-change-uom">
            <label><b>Enter new UOM :</b></label>
            <input type="number" name="new_uom" id="new_uom">
            <input type="hidden" name="plate" value="<?php echo $this->licensePlate ?>" id="plate">
            <input type="hidden" name="batch" value="<?php echo $this->batch ?>" id="batch">
            <input type="hidden" name="invIds" id="invIds">
            <input type="submit" value="Submit" id="changeUom">
        </div>

        <div class="list-button">
            <a href="#" class="button-action selectAll">Select All</a>
            <a href="#" class="button-action  deselectAll">Deselect All</a>
        </div>

        <?php

        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;

        ?>

        <div class="list-button">
            <a href="<?php echo makeLink('inventory', 'getCartonsEditUomByPlates') ?>" >Back</a>
        </div>

        <?php
    }

    /*
    ****************************************************************************
    */

    public function getCartonsEditUomByPlatesInventoryView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }

    /*
    ****************************************************************************
    */

    function changeStatusInventoryView()
    {
        echo $this->searcherHTML;
        ?>
        <div class="action-button">
            <button id="approve" value="approve" onclick="processData(event)">Approve</button>
            <button id="decline" value="decline" onclick="processData(event)">Decline</button>
        </div>
        <?php
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }

    /*
    ****************************************************************************
    */
}
