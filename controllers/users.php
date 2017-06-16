<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use \tables\users\resetPassword;

class controller extends template
{

    function searchUsersController()
    {
        $show = getDefault($this->get['show']);

        $orderColumn = NULL;

        switch ($show) {
            case 'info':
                $table = new tables\users($this);
                $orderColumn = 1;
                break;
            case 'access':
                $table = new tables\users\access($this);
                $orderColumn = 1;
                break;
            case 'clients':
                $table = new tables\users\clients($this);
                $orderColumn = 1;
                break;
            case 'groups':
                $table = new tables\users\groups($this);
                break;
            case 'userGroups':
                $table = new tables\users\userGroups($this);
                break;
            case 'pages':
                $this->addRows = FALSE;
                $table = new tables\users\pages($this);
                break;
            case 'pageParams':
                $this->addRows = FALSE;
                $table = new tables\users\pageParams($this);
                break;
            case 'groupPages':
                $table = new tables\users\groupPages($this);
                break;
            case 'subMenus':
                $this->addRows = FALSE;
                $table = new tables\users\subMenus($this);
                break;
            default:
                die;
        }

        $dtOptions = $orderColumn ? [
            'ajaxPost' => TRUE,
            'order' => [$orderColumn => 'asc'],
        ] : [
            'ajaxPost' => TRUE,
        ];

        // Export Datatable
        $ajax = new datatables\ajax($this);

        $ajax->output($table, $dtOptions);

        new datatables\searcher($table);

        $editable = new datatables\editable($table);

        if (isset($table->customAddRows)) {
            $table->customAddRows();
        } else {
            $editable->canAddRows();
        }
    }

    /*
    ****************************************************************************
    */

    function resetPasswordUsersController()
    {
        $table = new tables\users\resetPassword($this);

        $ajax = new datatables\ajax($this);

        $fields = array_keys($table->fields);
        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers'] = [
            'actionReset' => $fieldKeys['resetPassword'],
            'username' => $fieldKeys['username']
        ];

        $sortColumn = $fieldKeys['username'];

        $ajax->output($table, [
            'ajaxPost' => TRUE,
            'order'    => [
                $sortColumn => 'ASC'
            ]
        ]);

        new datatables\searcher($table);
        new datatables\editable($table);

        $this->isDevelop = $this->jsVars['isDevelop']
            = \access::getLevel() === resetPassword::DEVELOPER_LEVEL_CONSTANT;

        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $this->jsVars['urls']['resetPassword'] =
            makeLink('appJSON', 'resetPassword');
    }

    /*
    ****************************************************************************
    */

}