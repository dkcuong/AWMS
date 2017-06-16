<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function listAddOrderLabelsController()
    {
        $labels = new tables\orderLabels($this);
        
        $ajax = new datatables\ajax($this);
        
        $ajax->output($labels, [
            'order' => ['assignNumber' => 'desc']
        ]);
        
        $users = new tables\users($this);
  
        new common\labelMaker($this, $labels, $users);
    }

    /*
    ****************************************************************************
    */

    function displayOrderLabelsController()
    {    
        $getTerm = getDefault($this->get['term']);
        
        $getType = getDefault($this->get['type']) == 'work'
                ? 'work'
                : 'order';

        $term = getDefault($this->post['term'], $getTerm);

        $getSearch = getDefault($this->get['search']);
        
        $search = getDefault($this->post['search'], $getSearch);
      
        if (! $search || ! $term) {
            return FALSE;
        }
        $labels = new labels\orderLabels();
       
        $displayTerm = is_array($term) ? 'Mulitiple' : $term;
       
        $labels->addOrderLabels([
            'db' => $this, 
            'type' => $getType,
            'term' => $term,
            'search' => $search, 
            'fileName' => 'Order_'.$search.'_'.$displayTerm,
        ]);    
    }

    /*
    ****************************************************************************
    */

    function printUCCOrderLabelsController()
    {
        $table = new tables\orders\PickedCheckoutUcclabels($this);

        $ajax = new datatables\ajax($this);

        $fields = array_keys($table->fields);

        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers'] = [
            'ordernum' => $fieldKeys['ordernum']
        ];

        $ajax->output($table);

        new datatables\searcher($table);

        $this->jsVars['urls']['printUCCLabels'] = makeLink('inventory',
            'printUccLabels');

    }

    /*
    ****************************************************************************
    */
}