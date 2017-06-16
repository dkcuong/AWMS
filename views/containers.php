<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{
    function displayContainersView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
    }

    /*
    ****************************************************************************
    */
    
    function searchContainersView()
    {
        $this->ajax->multiSelectTableView($this, 'vendorID');   
        
        if ($this->receivedContainer) { ?> 
            <div id="commentForm" title="Add Notes">
                <div id="addNote">
                   <textarea id="commentNote" name="comment" rows="2" cols="30">                  
                   </textarea>
                </div>
            </div><?php } ?>
        <?php
    }
    
    /*
    ****************************************************************************
    */

    function addContainersView()
    {
        ?>
        <form method="post">
        <table>
        <?php foreach (array_keys($this->errors) as $error) { ?>
        <tr>
            <td><div class="failedMessage"><?php echo $error; ?></div></td>
        </tr>
        <?php } ?>
        <tr>
        <td>Vendor</td>
        <td>
        <select class="required" name="container[vendorID]" id="vendor"
                data-input-type="vendorName">
                    <option value="0">Select</option><?php
        foreach ($this->vendorsList as $id => $row) { 
            $selected = getDefault($this->post['container']['vendorID']) ==  $id 
            ? ' selected' : NULL; ?>
            <option value="<?php echo $id; ?>" <?php echo $selected; ?>>
                <?php echo $row['vendorName']; ?></option><?php
        } ?>
        </select>
        </td><td>Measurement System:</td>
        <td>
            <select class="required" id="measureID" name="container[measureID]"
                    data-input-type="measureSystem">  
                <option value="<?php 
                    echo tables\inventory\containers::US_IMPERIAL_ID; ?>">
                    US-Imperial</option>
                <!-- option value="Metric">Metric</option-->
            </select>
        </td>

        <td>Date: <input type="text" id="dateTime" value="<?php 
            echo $this->setAutoDate; ?>" readonly></td>
        <tr>

        <tr>
            <td>Container *</td>
            <td><input class="required" id="container" type="text" maxlength="32" 
                name="container[name]" value="<?php echo 
                getDefault($this->post['container']['name']); ?>"
                data-input-type="container"></td> 
            
            <td>Username</td>            
            <td><select class="required" name="container[userID]" id="username"
                        data-input-type="username">
                <option value="0">
                    Select</option><?php
            foreach ($this->users as $id => $row) { 
                $selected = getDefault($this->post['container']['userID']) ==  $id 
                ? ' selected' : NULL;?>
                <option value="<?php echo $id; ?>" <?php echo $selected; ?>>
                    <?php echo $row['username']; ?></option><?php
            } ?>
            </select></td>
        </tr>	 

        </table> 

        <table id="batches" class="display">
        <thead>
        <tr><?php 
        foreach ($this->inventoryFields as $field) { 
            $optional = isset($field['optional']) ? NULL : ' *'; ?>
            <th><?php echo $field['display'].$optional; ?></th><?php
        } ?>
        </tr>
        </thead>
        <tbody>
        <?php 
        $initialRow = 'id="initialRow"'; 
        foreach ($this->inputs as $rowID => $row) { ?>            
            <tr id="<?php echo $initialRow; $initialRow = NULL; ?>"> 
            <?php foreach ($this->fieldNames as $name) { 
                $optional = isset($this->inventoryFields[$name]['optional']) 
                    ? NULL : ' required'; ?>
                <td><input <?php echo $optional; ?>
                           type="text" class="containerInput <?php echo $name.$optional; ?>"
                           name="inputs[<?php echo $rowID; ?>][<?php echo $name; ?>]"
                           value="<?php echo getDefault($row[$name]); ?>"
                           data-input-type="<?php echo $name; ?>"></td>            
            <?php } ?>
            </tr>
        <?php } ?>
        <tbody>

        </table> 
        </form>
        <span class="buttonSpan">
        <input id="addQuantity" type="text">   
        <button id="addButton">Add Rows</button> 
        </span>
        <span class="buttonSpan">
        <input id="removeQuantity" type="text">
        <button id="removeButton">Remove Rows</button> 
        </span>
        <span class="buttonSpan">
        <button id="addContainers">Submit Container</button>
        </span>
        <?php
    }
    
    /*
    ****************************************************************************
    */

    function listContainersView()
    {
        echo datatables\structure::tableHTML('inventory');
        ?>
        <?php    
    }    

}
