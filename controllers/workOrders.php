<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{
    function checkinoutWorkOrdersController()
    {
        $this->isCheckOut = $this->jsVars['isCheckOut'] =
                $this->get['type'] == 'checkOut';

        if (! isset($this->post['workOrders'])) {
            return FALSE;
        }

        $this->jsVars['workOrderNumbers'] =
                explode(',', $this->post['workOrders']);

        $this->jsVars['scanOrderNumbers'] = $this->jsVars['isCheckOut'] ? NULL :
                explode(',', $this->post['orderNumbers']);

        $this->jsVars['urls']['getClientLabor']
            = customJSONLink('appJSON', 'getClientLabor');

        $this->jsVars['urls']['submitWorkOrder']
            = customJSONLink('appJSON', 'submitWorkOrder');

        $this->jsVars['urls']['getOrderDataByWorkOrderNumber']
            = customJSONLink('appJSON', 'getOrderDataByWorkOrderNumber');

        $this->includeJS['custom/js/common/workOrders.js'] = TRUE;
        $this->includeJS['js/jQuery/blocker.js'] = TRUE;
    }

    /*
    ****************************************************************************
    */

    function addLabelsWorkOrdersController()
    {
        $labels = new tables\workOrderLabels($this);

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

    function searchWorkOrdersController()
    {
        $model = new tables\workOrders\workOrderDetails($this);

        // Export Datatalbe
        $ajax = new datatables\ajax($this);

        $ajax->output($model, [
            'ajaxPost' => TRUE,
            'order' => [2 => 'desc'],
        ]);

        new datatables\searcher($model);

        new datatables\editable($model);
    }

    /*
    ****************************************************************************
    */

}