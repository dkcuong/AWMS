<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function addNSIView()
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
            <td>Username</td>            
            <td><select class="required" name="userID" id="userID"
                        data-input-type="userID">
                <option value="0">
                    Select</option><?php
            foreach ($this->users as $id => $row) { 
                $selected = getDefault($this->post['userID']) ==  $id 
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
        foreach ($this->nsiFields as $field) { 
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

    function shippingNSIView()
    { 
        ?>
        <CENTER><font size=6>Gernerate NSI Label</font></center>
        <form method="post">
        <CENTER><BR><BR>
        <Table border=2>
        <B><td>Store Number: &nbsp; &nbsp; &nbsp;</td><td>
        <select name='storeNumber' id="storeNumber">
        <option value='All'>All</option>
        <?php
        foreach ($this->stores  as $row) { ?>
            <option><?php echo $row['storeNumber']; ?></option><?php
        } ?>
        </select>
        </td><TR><td>
        Quantity: </td><td> <input type="text" name="quantity">

        <input type=submit name=Submit value=Submit><BR>
        </td></TR>
        </form>
        </table>
        <?php
    }
    
    /*
    ****************************************************************************
    */

    function listNSIView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
        
        // This needs to move to controller
        $dataTable = reset($this->jsVars['dataTables']);
        $records = $dataTable['recordsFiltered'];

        $limit = 900;
        $end = $start = 0;
        
        if (isset($this->get['reprint']) || $this->failedReprint) { 
            ?><div id="reprintButtons"><?php
            while ($records > $start) { 
                $end += $limit;
                $end = $end > $records ? $records : $end;
                ?><br><button data-start="<?php echo $start; ?>" 
                              data-end="<?php echo $end; ?>"
                              data-receiving="<?php echo getDefault($this->get['receiving'], 0); ?>"
                              class="reprintLabels">Reprint Labels <?php echo $start+1; ?>
                to <?php echo $end; ?></button><?php
                $start += $limit;
            } 
            ?></div><?php
        }

    }    

}