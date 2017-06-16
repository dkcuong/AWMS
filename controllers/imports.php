<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use import\vendorData;
use \csv\export as csvExporter;

class controller extends template
{
    
    function inventoryImportsController()
    {
        $this->warning = NULL;
        $vendors = new tables\vendors($this);
        
        $vendorSelect = 'CONCAT(w.shortName, "_", vendorName)';
        $this->vendorsHTML = $vendors->getDropdown($vendorSelect);

        foreach ($this->ajaxURLs as &$row) {
            $row['url'] = makeLink('appJSON', $row['input']);
        }

        $this->jsVars['urls']['createUCCLabels'] = 
            makeLink('appJSON', 'createUCCLabels');
        
        $this->jsVars['ajaxURLs'] = $this->ajaxURLs;
        
        $palletSheetDisplayFields = vendorData::$palletSheetFields;
        $this->imports['Inventory Pallet Sheet']['fields'] = 
            array_keys($palletSheetDisplayFields);

        $this->jsVars['payload'] = [];

        if (isset($this->post['palletSheetTemplate'])) {
            
            $template = array_keys(vendorData::$palletSheetFields);
           
            csvExporter::exportArray($template, 'import_inventory_template');
            
            die;
        }
                
        // Check for template request
        $post = getDefault($this->post);
        foreach ($this->imports as $display => $import) {
            $inputName = $import['input'];
            $templateName = $import['input'] . 'Template';
            
            if (isset($_FILES[$inputName])) {
                switch ($inputName) {
                    case 'palletSheet':
                        $this->jsVars['payload'] = $result = 
                            vendorData::importPalletSheet($this);

                        $this->warning = isset($result['warning']) ? $result['warning'] :
                            FALSE;
                        $this->errors = getDefault($result['errors']);

                        return;
                }
                
                return $this->template($display);
            }

            if (isset($post[$templateName])) {
                $this->getTemplate = TRUE;
                return $this->template($display);
            }
        }

        $importer = new vendorData($this);
        
        $importer->insertFiles();
        
        new import\wmsInventory([
            'app' => $this,
            'formName' => 'importForm',
        ]);
    }
         
    /*
    ****************************************************************************
    */

    function updateImportsController()
    {

        $this->vendor = $this->checkVendor();

        $import = new vendorData([
            'app' => $this,
            'formName' => 'importForm',
        ]);
        
        if ($import->insertMade()) {
            $inventory = $this->modelGetRackedInventory();
         
            $newFile = reset($import->uploadPaths);

            $newInventory = files\import::toArray($newFile);
            
            array_shift($newInventory);
            
            if (! array_filter($newInventory)) {
                return;
            }
        
            $this->modelCombineInventory($newInventory, $inventory);
        
            $this->modelUpdateStyleStatues($inventory);
        }
    }
    
    /*
    ****************************************************************************
    */
}