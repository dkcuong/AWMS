<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use datatables\searcher;

class controller extends template
{

    /*
    ****************************************************************************
    */

    function componentsLogsController()
    {
        $show = getDefault($this->get['show']);

        switch ($show) {
            case 'cartons':
                $table = new tables\logs\cartons($this);
                break;
            case 'orders':
                $table = new tables\logs\orders($this);
                break;
            case 'workOrders':
                $table = new tables\logs\workOrders($this);
                break;
            case 'scanInput':
                $table = new tables\logs\scanInput($this);
                break;
            default:
                die;
        }

        $this->ajax = new \datatables\ajax($this);

        $table->setMysqlFilters([
            'ajax' => $this->ajax,
            'trigger' => TRUE,
            'searches' => [
                [
                    'selectField' => 'Log Time Starting',
                    'selectValue' => date('Y-m-d', strtotime('-1 DAY')),
                    'clause' => 'logTime > NOW() - INTERVAL 1 DAY',
                ],
            ],
        ]);

        $dtStructure = [
            'bFilter' => TRUE,
        ];

        $this->multiSelect = $show == 'cartons' && \access::isClient($this);

        if ($this->multiSelect) {

            $this->jsVars['multiSelect'] = TRUE;
            
            $this->ajax->multiSelectTableController([
                'app' => $this,
                'model' => $table, 
                'dtOptions' => $dtStructure, 
            ]);
            
        } else {

            $this->ajax->output($table, $dtStructure);

            new searcher($table);
            new datatables\editable($table);
        }
    }

    /*
    ****************************************************************************
    */
}
