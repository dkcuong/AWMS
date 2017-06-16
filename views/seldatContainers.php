<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

use import\inventoryBatch;

class view extends controller 
{
    function scanSeldatContainersView()
    { 
        if ($this->editContainer && ! $this->containerValues) { 

            $message = 'No data related to Container # ' . $this->editContainer
                        . ' was found'; ?>

            <div id="resultMessage" class="failedMessage"><?php echo $message; ?></div> 
            
            <?php
            return;
        } ?>

        <a class="message" href="<?php echo makeLink('inventory', 'components', [
            'show' => 'modifyContainers',
            'editable' => 'containers',
        ]); ?>">Modify Containers</a>
        <a class="message" href="<?php echo makeLink('inventory', 'components', [
            'show' => 'containers',
            'modify' => 'addBatches',
        ]); ?>">Add Batches to Containers</a>
        <a class="message" href="<?php echo makeLink('inventory', 'components', [
            'show' => 'batches',
            'modify' => 'addCartons',
        ]); ?>">Add Cartons to Container Batches</a>        
        <a class="message" href="<?php echo makeLink('inventory', 'search', 'reprint'); 
            ?>">Reprint Carton Bar Codes</a>
        <a class="message" href="<?php echo makeLink('seldatContainers', 'import', 'import'); 
            ?>">Import Batch of Inventory</a><br><br>
        <div id="resultMessage" style="display: none;">
        </div> 
        <div>
            <input type="button" id="generateBarcode" style="display: none;"
                   value="Print Carton Labels For New Container">
        </div>
        
        <form method="POST" name="userenterform" id="scanContainerForm">
            <?php echo $this->generalContainerInfo()?>
            <div id="alertMessages"></div>
            <?php if ($this->editContainer && ! $this->modify) {
                $containerID = 1; ?>
                <h3>Container <?php echo $this->prevStyle[$containerID]; ?></h3><?php
                echo $this->datatablesStructuresHTML['batches']; ?>
                <br><hr><br>
                <h3>Add Batches to Container <?php echo $this->prevStyle[$containerID];
                ?>:</h3>
            <?php } ?>

            <table id="scanContainerTable" width="80%" border="1"
                   modifyRows="<?php echo $this->modifyRows; ?>">
                <tr><?php
                    foreach ($this->tableCells as $cell => $cellInfo) {
                        switch ($cell) {
                            case 'categoryUPC':
                                break;
                            case 'newUPC': ?>

                            <td>
                                <span id="newUPCHeader">
                                    NEW UPC<span id="infoIcon"></span>
                                </span>
                            </td><?php
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
                                $colTitle = isset($cellInfo['colTitle'])
                                    ? $cellInfo['colTitle'] : $cellInfo['cellName']; ?>
                                <td <?php echo $cellClass . $title; ?>><strong><?php
                                        echo $colTitle; ?></strong><span <?php
                                        echo $spanClass;?>></span>
                                </td><?php
                                break;
                        }
                    } ?>
                    </tr><?php
                $row = 0;
                while ($row <= $this->setrow) {

                    $oddRowsClass = $row % 2 ? NULL : 'oddRows';
                    $fifthRowsClass = ($row + 1) % 5 == 0 ? ' fifthMarked' : NULL;
                    ?>
                    <tr class="batchRows <?php echo $oddRowsClass . $fifthRowsClass; ?>"
                        id="row-<?php echo $row; ?>"><?php

                    $this->displayTableData($row); ?>

                    </tr><?php
                    $row++;
                } ?>
            </table>
        <br><br>
        <a class="message" href="#" id="addRow">Add</a>
        <input size="3" type="text" id="addRowAmount"> #row 
        <br><br>
        <a class="message" href="#" id="removeRow">Remove</a>
        <input size="5" type="text" id="removeRowAmount"> #row 
        <br><br>
        <a class="message" href="#" id="submitForm">Submit</a>
        <a class="message" href="#" id="clearContainer">Clear Container</a>
        <br><br>
        </form>
        <div id="dialog-form" title="New UPC">
            <div id="createUPC">
            <div class="newUPCMessage">
                If you can not find the UPC for your style,selectCategory
                request an original Seldat UPC:</div>
                <div id="selectCategory">
                    <select name="categoryUPC[]" id="categoryUPC">
                        <option value="-1">-- Select a category --</option>
                        
                        <option value="0">Un-category</option>
                        <?php foreach ($this->listCategoryUPCs as $key => $upc) {
                            $name = $this->listCategoryUPCs[$key]['name']; ?>
                            <option value="<?php echo $name; ?>">
                                <?php echo $name; ?>
                            </option>
                        <?php } ?>

                    </select>      
                    Request Original Seldat UPC by pick a Category
                </div>
            </div>
            
            <div id="lookUpUPC">
                <div class="newUPCMessage">Search for the Style UPC below:</div>
                <?php echo $this->datatablesStructuresHTML['upcs']; ?>
            </div>
            <div id="selectCategory"></div>
        </div><?php
    }

    /*
    ****************************************************************************
    */
    
    function importSeldatContainersView()
    {?>
        <?php if (! $this->fileSubmitted || isset($this->errorFile)) { ?>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file"/>
            <input type="submit" value="Upload CSV/Excel file" name="import" />
            <input type="submit" value="Download template" name="template"/>
        </form>
        <?php 
        if (isset($this->errorFile)) {?>
            <div class="alert red">
                <?php echo $this->errorFile;?>
            </div><?php
        }
        return FALSE; ?> 
        <?php } ?>
        <form method="POST" name="userenterform" id="scanContainerForm">
            <?php echo $this->generalContainerInfo()?>
            <div id="alertMessages"></div>  
            <?php inventoryBatch::buildImportDatatable($this->data); ?>
            <br>
            <a class="message" href="#" id="submitForm">Submit</a>
            <?php if ($this->countBadUpcs) { ?>
            <a class="message" href="#" id="downloadBadUpcs">
            Download Bad Upcs <span class="red">
            (<?php echo $this->countBadUpcs; ?>)</span></a>
            <?php } ?>
            <a class="message" href="#" id="clearContainer">Clear Container</a>
            <br><br>
        </form>
        <div id="resultMessage" style="display: none;"></div><?php
    }
    
    /*
    ****************************************************************************
    */
    
    

}
