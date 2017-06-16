<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function ordersOutboundController()
    {
        $model = new \tables\orders\outbound($this);
        $statuses = new \tables\statuses\orders($this);
        $this->ajax = new \datatables\ajax($this);

        $fields = array_keys($model->fields);

        $this->jsVars['fieldKeys'] = $fieldKeys = array_flip($fields);

        foreach ($model->fields as $key => $values) {

            $background = getDefault($values['backgroundColor']);

            $this->jsVars['columnColors'][$background][] = $fieldKeys[$key];
        }

        $this->jsVars['backgroundColors'] = $model->backgroundColors;

        $this->jsVars['clientView'] = [
            'warehouseID',
            'vendorID',
        ];

        $this->ajax->output($model, [
            'bFilter' => FALSE
        ], TRUE);

        $statusesResults = $statuses->getDropdown('shortName');

        $results = \datatables\vendorMultiselect::warehouseVendorGroup($model);

        $ddHeights = $results['ddHeights'];
        $searcher = $results['searcher'];

        $subject[-1]['status'] = 'All';

        foreach ($statusesResults as $key => $status) {
            $subject[$key] = ['status' => $status];
        }

        $searcher->createMultiSelectTable([
            'size' => $ddHeights,
            'title' => 'Select Statuses',
            'idName' => 'statusID',
            'subject' => $subject,
            'selected' => [TRUE],
            'fieldName' => 'status',
            'searchField' => 's.id',
        ]);

        $titles = array_column($model->fields, 'display');

        $this->jsVars['titles'] = array_flip($titles);

        $this->includeJS['custom/js/common/multiSelectFilter.js'] = TRUE;
        $this->includeJS['custom/js/common/datatableColoring.js'] = TRUE;

        $this->includeCSS['custom/css/common/multiSelectFilter.css'] = TRUE;
    }

    /*
    ****************************************************************************
    */
}