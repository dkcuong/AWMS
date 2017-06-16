<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use datatables\searcher;

class controller extends template
{

    function displayDashboardsController()
    {
        $this->jsVars['urls']['type'] = $this->get['type'];
        $this->jsVars['urls']['sendDashboardEmail']
            = customJSONLink('appJSON', 'sendDashboardEmail');
        $this->jsVars['urls']['orderFilter'] = '';

        $isReceiving = getDefault($this->get['type']) == 'receiving';

        $model = $isReceiving ?
            new tables\dashboards\receiving($this) :
            new tables\dashboards\shipping($this);

        $model->fields();

        $ajax = new datatables\ajax($this);
        $ajax->output($model, [
            'bFilter' => FALSE
        ], TRUE);

        $ddHeights = $isReceiving ? count($this->filters['Receiving']) :
            count($this->filters['Shipping']);

        $searcher = new searcher($model);

        \datatables\vendorMultiselect::vendorMultiselect([
            'ddHeights' => $ddHeights,
            'object' => $this,
            'searcher' => $searcher,
        ]);

        $subject = [
            'Complete' => ['type' => 'Complete'],
            'Incomplete' => ['type' => 'Incomplete'],
        ];

        $selected = [TRUE, TRUE];

        $searcher->createMultiSelectTable([
            'size' => $ddHeights,
            'title' => 'Select Statuses',
            'idName' => 'completion',
            'subject' => $subject,
            'selected' => $selected,
            'fieldName' => 'type',
            'searchField' => FALSE,
        ]);

        $statusFieldIDs = [];
        $arrayKeys = array_keys($model->fields);
        foreach ($arrayKeys as $key => $name) {
            if (isset($model->fields[$name]['colorStatus'])) {
                $statusFieldIDs[] = $key;
            }
        }

        $this->jsVars['fields'] = array_keys($model->fields);
        $this->jsVars['statusFieldIDs'] = $statusFieldIDs;
    }

    /*
    ****************************************************************************
    */

    function smartTVDashboardsController()
    {
        foreach ($this->filters as $title => $filters) {
            if (isset($this->post[$title])) {
                $_SESSION['search'] = [
                    'title' => $title,
                    'filterTitle' => TRUE,
                ];

                unset($_SESSION['search']['status']);

                break;
            }

            foreach ($filters as $filterTitle => $filter) {
                if (isset($this->post[$filter])) {

                    $_SESSION['search'] = [
                        'title' => $title,
                        'filterTitle' => $filterTitle,
                        'status' => $this->post[$filter.'status'],
                    ];

                    break 2;
                }
            }
        }

        if (isset($this->post['updateTime'])) {

            $_SESSION['tableRowAmount'] = getDefault($this->post['tableRowAmount']);
            $_SESSION['updateTime']= getDefault($this->post['updateTime']);
            $_SESSION['blinkTime'] = getDefault($this->post['blinkTime']);
            $_SESSION['loop'] = $_SESSION['elapsedTime'] = 0;
            $_SESSION['fontSize'] = getDefault($this->post['fontSize']);

            unset($_SESSION['blinkDashboard']);
            unset($_SESSION['results']);
        } else {
            $_SESSION['tableRowAmount'] = getDefault($_SESSION['tableRowAmount']);
            $_SESSION['updateTime'] = getDefault($_SESSION['updateTime']);
            $_SESSION['blinkTime'] = getDefault($_SESSION['blinkTime']);
            $_SESSION['loop'] = getDefault($_SESSION['loop'], 0);
            $_SESSION['fontSize'] = getDefault($_SESSION['fontSize']);
        }

        $this->loop = $_SESSION['loop'];
        $this->fontSize = min(40, max(4, $_SESSION['fontSize']));
        $this->tableRowAmount = min(100, max(2, $_SESSION['tableRowAmount']));
        $this->results = getDefault($_SESSION['results'], []);
        $this->title = $_SESSION['search']['title'];

        if ($_SESSION['search']['title'] != 'Shipping') {
            $_SESSION['blinkTime'] = $_SESSION['updateTime'];
        }

        $updateTime = min(100, max(2, $_SESSION['updateTime']));
        $this->metaRefresh = min($_SESSION['blinkTime'], $updateTime);

        $elapsedTime = $_SESSION['elapsedTime'];

        if ($elapsedTime + $this->metaRefresh >= $updateTime) {

            $_SESSION['elapsedTime'] = 0;

            $this->loop = $_SESSION['loop']
                    = ($this->loop + 1)*$this->tableRowAmount < count($this->results)
                    ? $this->loop + 1: 0;
        } else {
            $_SESSION['elapsedTime'] += min($_SESSION['blinkTime'], $updateTime);
        }

        switch ($this->title) {
            case 'Shipping':
                $this->table = new tables\dashboards\shipping($this);
                break;
            case 'Receiving':
                $this->table = new tables\dashboards\receiving($this);
                break;
            default:
                return;
        }
    }

    /*
    ****************************************************************************
    */

    function selectDashboardsController()
    {
        $this->displayLink = makeLink('dashboards', 'smartTV');
    }

    /*
    ****************************************************************************
    */
}