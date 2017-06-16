<?php

namespace tables\users;

class userGroups extends \tables\users
{
    public $displaySingle = 'User Groups';

    public $ajaxModel = 'users\\userGroups';

    public $primaryKey = 'ug.id';

    public $fields = [
        'username' => [
            'select' => 'u.username',
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'username',
            'noEdit' => TRUE
        ],
        'userGroup' => [
            'update' => 'ug.groupID',
            'select' => 'groupName',
            'display' => 'User Group',
            'searcherDD' => 'users\\groups',
            'ddField' => 'groupName',
            'ignore' => TRUE,
        ],
        'active' => [
            'update' => 'ug.active',
            'select' => 'activeStatus.displayName',
            'display' => 'Status',
            'searcherDD' => 'statuses\activeStatus',
            'ddField' => 'displayName',
        ],
    ];

    public $mainField = 'username';

    public $customAddRows = TRUE;

    public $customInsert = 'users\\userGroups';

    /*
    ****************************************************************************
    */

    function table()
    {
        $activeStatus = new \tables\statuses\activeStatus($this);

        $userDB = $this->app->getDBName('users');

        return 'user_groups ug
               JOIN '.$userDB.'.info u ON u.id = ug.userID
               JOIN groups g ON g.id = ug.groupID
               LEFT JOIN '.$activeStatus->table.' ON activeStatus.id = ug.active
               ';
    }

    /*
    ****************************************************************************
    */

    function customAddRows()
    {
        $this->app->includeCSS['css/datatables/editable.css'] = TRUE;

        $addURL = jsonLink('dtEditableAdd', [
            'modelName' => $this->ajaxModel
        ]);

        $this->app->jsVars['editables']['userGroups']['sAddURL'] = $addURL;

        $single = $this->displaySingle;

        $groups = new groups($this->app);
        $activeStatus = new \tables\statuses\activeStatus($this->app);

        $groupResults = $groups->getDropDown('groupName');

        $userGroups = [];

        foreach ($groupResults as $userGroup) {
            $userGroups[] = ['groupName' => $userGroup];
        }

        $userNames = $this->getUsers();

        $activeStatuses = $activeStatus->getStatuses();

        $rel = 0;
        ob_start(); ?>
        <form id="formAddNewRow" action="#" title="Add New <?php echo $single; ?>">
        <div id="addRowNotice"></div>
        <table><?php
            $rel = $this->getInsertDropDown([
                'caption' => 'User Name',
                'rel' => $rel,
                'source' => $userNames,
                'name' => 'username',
            ]);
            $rel = $this->getInsertDropDown([
                'caption' => 'User Group',
                'rel' => $rel,
                'source' => $userGroups,
                'name' => 'groupname',
                'field' => 'groupName',
            ]);
            $rel = $this->getInsertDropDown([
                'caption' => 'Status',
                'rel' => $rel,
                'source' => $activeStatuses,
                'name' => 'activeStatus',
                'field' => 'displayName',
            ]); ?>
        </table>
        </form><?php
        $this->app->searcherAddRowFormHTML = ob_get_clean();

        ob_start(); ?>
        <button id="btnAddNewRow" class="add_row">Add <?php echo $single; ?>
            </button><?php
        $this->app->searcherAddRowButton = ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function customInsert($post)
    {
        $userID = $post['username'];
        $groupID = $post['groupname'];
        $statusID = $post['activeStatus'];

        $sql = 'INSERT INTO user_groups (
                    userID, groupID, active
                ) VALUES (
                    ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    active = ?';

        $ajaxRequest = TRUE;

        $param = [$userID, $groupID, $statusID, $statusID];

        $this->app->runQuery($sql, $param, $ajaxRequest);
    }


    /*
    ****************************************************************************
    */

    function getUsers()
    {
        $userDB = $this->app->getDBName('users');

        $sql = 'SELECT    u.id,
                          username
                FROM      '.$userDB.'.info u
                ORDER BY  username ASC';

        $results = $this->app->queryResults($sql);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getUserGroups($userID)
    {
        $sql = 'SELECT    ug.id,
                          groupID
                FROM      user_groups ug
                JOIN      groups g ON g.id = ug.groupID
                WHERE     userID = ?
                AND       ug.active
                AND       g.active';

        $results = $this->app->queryResults($sql, $userID);

        return array_column($results, 'groupID');
    }

    /*
    ****************************************************************************
    */

}