<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function searchContainersController()
    {
        $this->receivedContainer = getDefault($this->get['type']) == 'received';
        
        $this->includeJS['js/datatables/editables.js'] = TRUE;
        
        $orderColumn = NULL;
        
        $model = new tables\containersReceived($this);
        
        $show = getDefault($this->get['show']);

        switch ($show) {
            case 'sku':
                $model = new tables\containersSKUs($this);
                break;
            default:
                $model = new tables\containersReceived($this);
        }

        $dtStructure = $orderColumn ? [
            'ajaxPost' => TRUE,
            'bFilter' => FALSE,
            'order' => [$orderColumn => 'asc'],
        ] : [
            'bFilter' => FALSE,
        ];

        $this->modelName = $this->jsVars['modelName'] = getClass($model);
          
        $keys = array_keys($model->fields);
        
        $this->jsVars['fields'] = ['clientNotes'];
        
        $fields = array_merge($this->jsVars['fields'],['recNum']);
        
        foreach ($fields as $field) {
           $this->jsVars['columnNumbers'][$field] = array_search($field, $keys);
        }
   
        // Export Datatable
        $this->ajax = new \datatables\ajax($this);
        
        $this->jsVars['multiSelect'] = TRUE;
        
        $this->ajax->multiSelectTableController([
            'app' => $this,
            'model' => $model, 
            'dtOptions' => $dtStructure, 
        ]);
       
        $this->jsVars['urls']['addClientNotes']
                    = makeLink('appJSON', 'addClientNotes');
    }

    /*
    ****************************************************************************
    */
       
    function displayContainersController()
    {
        $editContainer = $this->get['recNum'];

        $ajax = new datatables\ajax($this);
        $batches = new tables\inventory\batches($this);

        $keys = array_keys($batches->fields);
        // sort the table by Batch Number
        $orderColumn = array_search('batchID', $keys);
        
        $ajax->addControllerSearchParams([
            'values' => [$editContainer],
            'field' => 'recNum'
        ]);

        $this->includeJS['js/datatables/editables.js'] = TRUE;

        $ajax->output($batches, [
            'bFilter' => FALSE,
            'order' => [$orderColumn => 'asc']
        ]);

	    new datatables\searcher($batches);
        new datatables\editable($batches);
    }

    /*
    ****************************************************************************
    */
    
}
