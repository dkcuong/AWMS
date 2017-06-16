<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{
    function searchUpcsController()
    {
	    $table = NULL;
	    switch (getDefault($this->get['show'])) {
		    case 'upcs':
			    $table = new tables\upcs($this);
			    break;
		    case 'client':
			    $table = new tables\upcsClient($this);
                            break;
                    default:
                    die;
	    }

        $this->ajax = new datatables\ajax($this);

	    $this->ajax->output($table, [
		    'ajaxPost' => TRUE,
	    ]);

	    $this->multiSelect = isset($table->multiSelect) ? $table->multiSelect : NULL;

	    $searcher = new datatables\searcher($table);

		if ($this->multiSelect) {
			\datatables\vendorMultiselect::vendorMultiselect([
				'object' => $this,
				'searcher' => $searcher,
			]);
		}

        new datatables\editable($table);
    }

    /*
    ****************************************************************************
    */

    function editCategoriesUpcsController()
    {
        $table = new tables\inventory\upcsCategories($this);

        $ajax = new datatables\ajax($this);

        $ajax->output($table, [
            'ajaxPost' => TRUE,
        ]);

        new datatables\searcher($table);

        $editable = new datatables\editable($table);

        $this->addRows = isset($table->customInsert) ? $table->customInsert : NULL;

        if (isset($table->customAddRows)) {
            $table->customAddRows();
        } else if (isset($table->customInsert)){
            $editable->canAddRows();
        }

    }

    /*
    ****************************************************************************
    */

    function editUpcsController()
    {

        $this->jsVars['urls']['updateUpcInfo'] =
            customJSONLink('appJSON', 'updateUpcInfo');
        $this->jsVars['urls']['getAutocompleteUpc'] =
            customJSONLink('appJSON', 'getAutocompleteUpc');

        $table = new tables\upcs($this);

        $keys = array_keys($table->fields);
        // sort the table by Order Number
        $this->jsVars['checkBoxColumn'] = array_search('upc', $keys);

        $ajax = new datatables\ajax($this);

        $ajax->output($table, [
            'ajaxPost' => TRUE,
            'order' => [5 => 'desc']
        ]);

        new datatables\searcher($table);

        new datatables\editable($table);

    }

    /*
    ****************************************************************************
    */

}