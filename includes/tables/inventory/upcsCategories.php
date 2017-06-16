<?php

namespace tables\inventory;

class upcsCategories extends \tables\_default
{
    public $displaySingle = 'Categories';

    public $primaryKey = 'uc.id';

    public $ajaxModel = 'inventory\\upcsCategories';

    public $fields = [
        'displayName' => [
            'select' => 'uc.name',
            'display' => 'Name',
        ],
        'active' => [
            'select' => 'IF(active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'active',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $table = 'upcs_categories uc';

    public $groupBy = 'uc.id';

    public $mainField = 'uc.id';

    public $customInsert = 'inventory\\upcsCategories';

    /*
    ****************************************************************************
    */

    function insertTable()
    {
        return $this->table;
    }

    /*
    ****************************************************************************
    */

    function customInsert($post)
    {
        $displayName = $post['displayName'];
        $active = $post['active'];

        $sql = 'INSERT INTO upcs_categories (
                    name, active
                ) VALUES (
                    ?, ?
                ) ON DUPLICATE KEY UPDATE
                    active = ?';

        $ajaxRequest = TRUE;

        $param = [$displayName, $active, $active];

        $this->app->runQuery($sql, $param, $ajaxRequest);
    }

    /*
    ****************************************************************************
    */

    function getByName($params)
    {
        if (! $params) {
            return [];
        }
        $markString = $this->app->getQMarkString($params);
        
        $sql = 'SELECT    name,
                          id
                FROM      upcs_categories
                WHERE     name IN (' . $markString . ')';

        $results = $this->app->queryResults($sql, $params);
        
        $return = [];
        
        foreach ($results as $categoryName => $categoryID) {            
            $return[$categoryName] = $categoryID['id'];
        }
        
        return $return;
    }

    /*
    ****************************************************************************
    */ 

}