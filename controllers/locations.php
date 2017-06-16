<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function searchLocationsController()
    {
        
        $orderColumn = NULL;

        switch (getDefault($this->get['display'])) {
            case 'unconsolidated':
                $table = new tables\consolidation\waveOne($this);
                $orderColumn = 'upc';
                break;
            case 'byUPC':
                $table = new tables\locations\byUPC($this);
                $orderColumn = 'upc';
                break;
            case 'locationinfo':
                
                $table = new tables\locations\locationInfo($this);
                $this->importer = new excel\importer($this, $table);

                if (getDefault($_FILES) && $_FILES['file']['error']) {
                    die('Error Submitting a File');
                }

                if (isset($this->post['template'])) {
                    
                    \locations\minMax::importTemplate($table->fields);

                    die;
                }

                $this->fileSubmitted = getDefault($_FILES);

                if ($this->fileSubmitted) {

                    $this->importer->uploadPath = 
                            \models\directories::getDir(
                                'uploads',
                                'minMaxImportsFiles'
                            );

                    $this->importer->insertFile();
                }

                $this->includeCSS['custom/css/includes/minMax.css'] = TRUE;
                
                break;
            default:
                $table = new tables\locations\warehouse($this);
        }

        $dtOptions = $orderColumn ? [
            'bFilter' => FALSE,
            'order' => [$orderColumn => 'asc'],
        ] : [
            'bFilter' => FALSE,
        ];

        $ajax = new datatables\ajax($this);

        $ajax->output($table, $dtOptions);

        new datatables\searcher($table);

        $editable = new datatables\editable($table);

        $this->jsVars['locationNameColumnNo'] = 1;
        
        $this->jsVars['urls']['checkMezzanineLocation'] =
            customJsonLink('appJSON', 'checkMezzanineLocation');

        $this->addRows = isset($table->customInsert) ? 
                $table->customInsert : NULL;

        if (isset($table->customAddRows)) {
            $table->customAddRows();
        } else if (isset($table->customInsert)) {
            $editable->canAddRows();
        }

    }

    /*
    ****************************************************************************
    */

    function waveLocationsController()
    {
        $table = new tables\consolidation\waveOne($this);
        
        $inventory = $table->getByID(FALSE, 'cartons DESC');
        
        $byUPC = [];
        foreach ($inventory as $info) {
            
            $upc = $info['upc'];
            
            $byUPC[$upc][] = [
                'location' => $info['palletLocation'],
                'cartons' => $info['cartons'],
                'maxCartons' => $info['maxUPC'],
            ];
            
        }

        $collect = inventory\consolidation::getMulti($byUPC);

        inventory\waves::consolidationCollect($collect);
        
    }

    /*
    ****************************************************************************
    */
    
    function adminSearchLocationsController()
    {
        $table = new tables\locations\adminWarehouse($this);
        
        $dtOptions = [
            'bFilter' => FALSE,
        ];

        $ajax = new datatables\ajax($this);
        
        $ajax->output($table, $dtOptions);

        new datatables\searcher($table);

        new datatables\editable($table);

    }
    
    /*
    ****************************************************************************
    */

    function searchLocationsUtilizationLocationsController()
    {
        $this->datableFilters = [
            'warehouseID'
        ];

        $table = new tables\locations\utilization($this);
        $this->ajax = new datatables\ajax($this);

        $this->ajax->warehouseVendorMultiSelectTableController([
            'app' => $this,
            'model' => $table,
            'display' => [
                'showWhsType' => TRUE,
                'warehouseType' => FALSE,
                'showVendor' => FALSE
            ]
        ]);

        $fields = array_keys($table->fields);
        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers'] = [
            'customers'      => $fieldKeys['customer'],
            'plates'       => $fieldKeys['plate'],
        ];

        $this->jsVars['multiselect'] = TRUE;
    }

    /*
    ****************************************************************************
    */
}