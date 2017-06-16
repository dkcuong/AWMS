<?php

namespace tables\users;

class access extends \tables\users
{
    public $displaySingle = 'Access';
    
    public $ajaxModel = 'users\\access';
    
    public $primaryKey = 'u.id';
    
    public $fields = [
        'username' => [
            'select' => 'u.username',
            'display' => 'Username',
            'searcherDD' => 'users',
            'ddField' => 'username',
            'noEdit' => TRUE
        ],
        'level' => [
            'update' => 'a.levelID',
            'select' => 'l.displayName',
            'display' => 'User Level',
            'searcherDD' => 'users\\levels',
            'ddField' => 'l.displayName',
            'ignore' => TRUE,
        ],
    ];
        
    public $mainField = 'username';
    
    public $customAddRows = TRUE;
    
    public $customInsert = 'users\\access';
    
    /*
    ****************************************************************************
    */
    
    function table()
    {
        $userDB = $this->app->getDBName('users');
        
        return $userDB.'.info u
               JOIN users_access a ON a.userID = u.id
               JOIN user_levels l ON l.id = a.levelID';
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

        $this->app->jsVars['editables']['access']['sAddURL'] = $addURL;
        
        $single = $this->displaySingle;

        $levels = new levels($this->app);

        $userLevels = $levels->getLevels();
        $userNames = $this->getUsersMissingStatus();
        
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
                'caption' => 'User Level',
                'rel' => $rel,
                'source' => $userLevels,
                'name' => 'level',
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
        $levelID = $post['level'];
        
        $sql = 'INSERT INTO users_access (
                    userID, levelID
                ) VALUES (
                    ?, ?
                ) ON DUPLICATE KEY UPDATE
                    levelID = ?';

        $ajaxRequest = TRUE;

        $this->app->runQuery($sql, [$userID, $levelID, $levelID], $ajaxRequest);
    }
    
    
    /*
    ****************************************************************************
    */    
 
    function getUsersMissingStatus()
    {
        $userDB = $this->app->getDBName('users');
        
        $sql = 'SELECT    u.id,
                          username
                FROM      '.$userDB.'.info u
                LEFT      JOIN users_access a ON a.userID = u.id
                WHERE     active = 1
                AND       a.userID IS NULL
                ORDER BY  username ASC';
        
        $results = $this->app->queryResults($sql);

        return $results;
    }
    
    /*
    ****************************************************************************
    */
}