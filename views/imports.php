<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function inventoryImportsView()
    {
        if ($this->getTemplate) {
            return;
        }
        
        if ($this->jsVars['payload'] && ! $this->errors) {
            foreach ($this->ajaxURLs as $row) { ?>
                <div><span id="<?php echo $row['input']; ?>">Processing...</span> 
                    <?php echo $row['title']; ?></div><?php
            }
        } ?>
        
        <div id="createUCCLabels"></div><?php
        
        foreach ($this->imports as $display => $row) {
            $import = $row['input']; ?>
            <div id="importInventory" class="message importForms">
                <span id="title">Import <?php echo $display; ?></span><br><?php 
                echo $this->importerFormStart[$import]; 
                echo $this->templateInputs[$import]; ?><br>
                <div class="message">
                    <input type="radio" value="0" name="deactivateInventory" checked>
                    Do not deactivate inventory<br>
                    <input type="radio" value="1" name="deactivateInventory">
                    Deactivate inventory
                </div>
                <br>
                Client: 
                <select name="clientID">
                <option>Select</option>
                <?php foreach ($this->vendorsHTML as $vendorID => $name) { ?>
                    <option value="<?php echo $vendorID; ?>"><?php echo $name; ?></option>
                <?php } ?>
                </select><br><?php 
                echo $this->importerInputs[$import]; ?><br><?php 
                echo $this->importerFormButton;
                echo $this->importerFormEnd;
                ?>
            </div>
        <?php }

        if (getDefault($this->warning)) {
            $message = '<br><span id="warningMessage">';
            foreach ($this->warning as $warning) {
                $message .= $warning;
            }
            $message .= '</span>';
            echo $message;
        }

        $this->importErrors();
    }
    
    /*
    ****************************************************************************
    */

    function updateImportsView()
    {
        ?>
        <div id="title"><?php echo $this->vendorDisplay; ?> Imports:</div>
        <div class="message">
        <?php echo $this->importerFormStart; ?>
        <span>Import Product Information</span><br>
        <?php echo $this->importerInputs['products']; ?><br>
        <?php
        echo $this->importerFormButton;
        echo $this->importerFormEnd;
        ?>
        </div>
        <?php
    }    

    /*
    ****************************************************************************
    */

    function method2EmptyView()
    {
        ?>
        <?php    
    }    
    
    /*
    ****************************************************************************
    */

    function importErrors()
    {

        if (! getDefault($this->errors)) {
            return;
        }
        
        $errors = $this->errors;

        if (isset($errors['generalError'])) {
            $this->displayErrors([
                'errorArray' => $errors['generalError'],
            ]);
        } else {
            if (isset($errors['unexpectedColumns'])) {
                $this->displayErrors([
                    'captionSuffix' => 'with unexpected columns:',
                    'errorArray' => $errors['unexpectedColumns'],
                ]);
            }
            if (isset($errors['missingColumns'])) {
                $this->displayErrors([
                    'captionSuffix' => 'with missing columns:',
                    'errorArray' => $errors['missingColumns'],
                ]);
            }
        }

        if (isset($errors['wrongContainers'])) {
            $this->displayErrors([
                'captionSuffix' => 'with used Container Names:',
                'errorArray' => $errors['wrongContainers'],
            ]);
        }
        
        if (isset($errors['wrongWarehouses'])) {
            $this->displayErrors([
                'captionSuffix' => 'with wrong Warehouse Names:',
                'errorArray' => $errors['wrongWarehouses'],
            ]);
        }

        if (isset($errors['vendorMismatch'])) {
            $this->displayErrors([
                'captionSuffix' => 'with different Client Names:',
                'errorArray' => $errors['vendorMismatch'],
            ]);
        }       
        
        if (isset($errors['unexpectedMeasure'])) {
            $this->displayErrors([
                'captionSuffix' => 'with unexpected Measurement System:',
                'errorArray' => $errors['unexpectedMeasure'],
            ]);
        }
        
        if (isset($errors['multipleContainerMeasures'])) {
            $this->displayErrors([
                'captionSuffix' => 'with multiple Measurement Systems per Container:',
                'errorArray' => $errors['multipleContainerMeasures'],
            ]);
        }
        
        if (isset($errors['missingLocations'])) {
            $this->displayErrors([
                'captionSuffix' => 'with missing locations:',
                'errorArray' => $errors['missingLocations'],
            ]);
        }
        
        if (isset($errors['insert'])) {
            $this->displayErrors([
                'captionSuffix' => 'that requires the following SQL stements to be run:',
                'errorArray' => $errors['insert'],
            ]);
        }
        
        if (isset($errors['dimensions'])) {
            $this->displayErrors([
                'captionSuffix' => 'with invalid dimensions:',
                'errorArray' => $errors['dimensions'],
            ]);
        }        
    }
    
    /*
    ****************************************************************************
    */

    function displayErrors($descriptions)
    { ?>
        <div class="failedMessage blockDisplay">
        <?php if (is_array($descriptions)) { 
            if (getDefault($descriptions['captionSuffix'])) { ?>
            You have submitted a file <?php echo $descriptions['captionSuffix']; ?>
            <br> 
            <?php }            
            if (getDefault($descriptions['errorArray'])) {
                echo implode('<br>', $descriptions['errorArray']);
            }                
        } ?>
        </div><?php
    }
}