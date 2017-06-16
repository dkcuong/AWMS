<?php
namespace tables\inventory\scc;
use pdo;
use models\vars;
use models\history;
use tables\upcs;
class items extends \tables\_default
{
    const AC_LIMIT = 3;
    const CAT_NAME = 'SCC';
    const ITEM_TYPE = 'SCI';
    
    
    public $upcs;
    public $vars;
    public $catID;
    public $error;
    public $containers;
    public $primaryKey = 'u.id';
    public $ajaxModel = 'inventory\\scc\\items';
    
    public $excludeExportFields = ['changeQty', 'changeTestQty', 'history'];
    public $fields = [
        'type' => [
            'select' => 'c.type',
            'display' => 'Category Type',
        ],
        'name' => [
            'select' => 'c.name',
            'display' => 'Category Name',
        ],
        'sku' => [
            'select' => 'u.sku',
            'display' => 'Item#',
        ],
        'description' => [
            'display' => 'Description',
        ],
        'qty' => [
            'display' => 'Qty',
        ],
        'changeQty' => [
            'select' => 'u.id',
            'display' => 'Change',
        ],
        'history' => [
            'select' => 'u.id',
            'display' => 'Stock History',
        ],
        'active' => [
            'select' => 'i.active',
            'display' => 'Status',
        ],
    ];
    public $table = 'upcs u
        JOIN upcs_categories uc ON uc.id = u.catID
        JOIN scc_items i ON i.upc_id = u.id
        JOIN scc_ctgrs c ON c.id = i.category_id
    ';
    public $groupBy = 'u.sku, u.upc';
    public $mainField = 'u.id';
    public $multiSelect = 'clientName';
    
    public $where = 'uc.name = "'.self::CAT_NAME.'"';
    
    /*
    ****************************************************************************
    */
    static function init($app, $vars=FALSE)
    {
        $self = new static($app);
        
        $self->vars = $vars ? $vars : new vars();
        $self->vars->set('db', $app);
        $self->upcs = new upcs($app);
        
        $result = $self->getUPCCat();
        
        $self->catID = $result ? $result['id'] : $self->createCat();
        return $self;
    }
    
    /*
    ****************************************************************************
    */
    function varGet($name)
    {
        return $this->vars->get($name);
    }
    
    /*
    ****************************************************************************
    */
    function getUPCCat()
    {
        $clauses = ['name = ?'];
        $qParams = [self::CAT_NAME];
        
        $sql = 'SELECT   id,
                         name
                FROM     upcs_categories
                WHERE    '.implode(' AND ', $clauses);
        
        $results = $this->app->queryResult($sql, $qParams);
        
        return $results;
    }
    
    /*
    ****************************************************************************
    */
    function getItemCats($params=[])
    {
        $name = getDefault($params['name']);
        $limit = isset($params['limit']) ? 'LIMIT '.$params['limit'] : NULL;
        $groupBy = isset($params['groupBy']) ? 
            'GROUP BY '.$params['groupBy'] : NULL;
        
        $clauses = [1];
        $qParams = [];
        
        $term = getDefault($params['term']);
        if ($term) {
            $clauses[] = 'name LIKE ?';
            $qParams[] = $term.'%';
        }
        
        if ($name) {
            $clauses[] = 'name = ?';
            $qParams[] = $name;
        }
        $sql = 'SELECT   id,
                         name,
                         type
                FROM     scc_ctgrs
                WHERE    '.implode(' AND ', $clauses).'
                '.$groupBy.'
                ORDER BY name
                '.$limit;
        
        $results =  $limit ? 
            $this->app->queryResults($sql, $qParams) : 
            $this->app->queryResult($sql, $qParams);
        
        return $results;
    }
    
    /*
    ****************************************************************************
    */
    function searchItems($params)
    {
        $field = $params['search'] == 'sku' ? 'sku' : 'upc';
        
        $sql = 'SELECT id
                FROM   upcs
                WHERE  '.$field.' = ?';
        
        $term = $params['term'];
        return $this->app->queryResult($sql, [$term]);
    }
    
    /*
    ****************************************************************************
    */
    function checkItemExists()
    {
        return $this->searchItems([
            'search' => 'sku',
            'term' => $this->app->postVar('sku'),
        ]) ? ['error' => 'itemExists'] : [];
    }
    
    /*
    ****************************************************************************
    */
    function createItemCat()
    {
        $sql = 'INSERT INTO scc_ctgrs
                SET         name = ?,
                            type = ?';
        $params = $this->vars->getParams(['name', 'type']);
        
        $this->app->runQuery($sql, $params);
        
        return $this->app->lastInsertID();
    }
    
    /*
    ****************************************************************************
    */
    function createCat()
    {
        $catName = $this->vars->get('cat_name', 'getDef', self::CAT_NAME);
        
        $sql = 'INSERT INTO upcs_categories
                SET         name = ?';
        
        $this->app->runQuery($sql, [$catName]);
        
        return $this->app->lastInsertID();
    }
    
    /*
    ****************************************************************************
    */
    function setRecVars($names)
    {
        $post = $this->app->getArray('post');
        $this->vars->setArray($post, $names);
        
        return $post;
    }
    
    /*
    ****************************************************************************
    */
    function setError($type='missing')
    {
        $this->error[$type][] = $this->vars->getName();
        return $this;
    }
    
    /*
    ****************************************************************************
    */
    function getHistory()
    {
        $userDB = $this->app->getDBName('users');
        
        $sql = 'SELECT   dt,
                         toVal,
                         fromVal,
                         ui.username,
                         u.sku AS item,
                         supplier,
                         toVal - fromVal AS diff,
                         IF(isTest = "Y", "Test", "Order") AS reason,
                         tranID,
                         style,
                         requestedBy
                FROM     scc_logs l
                JOIN     upcs u ON u.id = targetID
                JOIN     '.$userDB.'.info ui ON ui.id = userID
                WHERE    type = "'.self::ITEM_TYPE.'"
                AND      targetID = ?
                ORDER BY dt DESC';
        $id = $this->app->getVar('id');
        $results = $this->app->queryResults($sql, [$id], pdo::FETCH_ASSOC);
        return array_values($results);
    }
    
    /*
    ****************************************************************************
    */
    function jsonGet()
    {
        $stockHistory = $this->app->getVar('stock', 'getDef') == 'history';
        
        if ($stockHistory) {
            return $this->getHistory();
        }
        $acRes = [];
        $get = $this->app->getArray('get');
        
        $typeSearch = getDefault($get['acSearch']) == 'catTypes'; 
        
        $results = $this->getItemCats([
            'limit' => self::AC_LIMIT,
            'getAll' => TRUE,
            'term' => $get['term'],
            'groupBy' => $typeSearch ? 'type' : NULL,
        ]);
        
        $field = $typeSearch ? 'type' : 'name';
        foreach ($results as $id => $row) {
            $acRes[] = [
                'label' => $row[$field],
                'id' => $id,
            ];
        }
        
        return $acRes;
    }
    
    /*
    ****************************************************************************
    */
    function jsonCreateItem()
    {
        $newUPC = $this->upcs->seldatUPC();
        if (! $newUPC) {
            return ['error' => 'noSeldatUPC'];
        }
        $this->vars->get('containers')->handleUPCs([
            'app' => $this->app,
            'category' => self::CAT_NAME,
            'upcData' => [
                [
                    'upc' => $newUPC,
                    'category' => self::CAT_NAME,
                    'data' => [
                        'sku' => $this->vars->get('sku'),
                        'size' => $this->vars->get('size'),
                        'color' => $this->vars->get('color'),
                    ]
                ]
            ],
        ]);
        
        $upc = $this->searchItems([
            'term' => $newUPC,
            'search' => 'upc',
        ]);
        $this->vars->set('upc_id', $upc['id']);
        history::init(['tableName' => 'scc_logs'])->addFields([
            'supplier', 'isTest',
        ])->varDB($this)->add([
            'type' => self::ITEM_TYPE, 
            'targetID' => $this->vars->get('upc_id'),
            'field' => 'qty', 
            'toVal' => $this->vars->get('qty'), 
            'fromVal' => 0, 
            'supplier' => $this->vars->get('supplier'),
            'isTest' => 'N',
        ]);
        $sql = 'INSERT INTO scc_items
                SET         upc_id = ?,
                            category_id = ?,
                            description = ?,
                            qty = ?';
        
        $qParams = $this->vars->getParams([
            'upc_id', 'category_id', 'description', 'qty'
        ]);
        
        $this->app->runQuery($sql, $qParams);
        
        return ['success' => 'itemCreated'];
    }
    
    /*
    ****************************************************************************
    */
    function updateItem($post)
    {
        $field = json_decode($post['testQty']) ? 'test_qty' : 'qty';
        $sql = 'UPDATE scc_items
                SET    '.$field.' = ?
                WHERE  upc_id = ?';
        $params = $this->vars->getParams(['resultingQty', 'itemID']);
        $this->app->runQuery($sql, $params);
    }
    
    /*
    ****************************************************************************
    */
    function jsonUpdate()
    {
        $post = $this->app->getArray('post');
        $this->vars->setArray($post);
        if (isset($post['updateStatus'])) {
            $sql = 'SELECT active
                    FROM   scc_items
                    WHERE  upc_id = ?';
            
            $id = $post['id'];
            $row = $this->app->queryResult($sql, [$id]);
            
            history::init(['tableName' => 'scc_logs'])->varDB($this)->add([
                'type' => self::ITEM_TYPE, 
                'targetID' => $this->vars->get('id'),
                'field' => 'active', 
                'toVal' => $this->vars->get('newStatus'), 
                'fromVal' => $row['active'], 
            ]);
            
            $params = $this->vars->getParams(['newStatus', 'id']);
            $this->app->runQuery('
                UPDATE scc_items
                SET    active = ?
                WHERE  upc_id = ?
            ', $params);
            
            return TRUE;
        }
        $requires = [];
        
        switch ($post['inputType']) {
            case 'cat': 
                $requires = ['name', 'type'];
                break;
            case 'item': 
                $requires = [
                    'cat_name', 'sku', 'size', 'color', 'description', 'qty'
                ];
                break;
            case 'qty': 
                
                
                $field = json_decode($post['testQty']) ? 'test_qty' : 'qty';
                
                $sql = 'SELECT '.$field.'
                        FROM   scc_items
                        WHERE  upc_id = ?';
                $params = $this->vars->getParams(['itemID']);
                $result = $this->app->queryResult($sql, $params);
                $fromVal = getDefault($result[$field]);
                
                history::init(['tableName' => 'scc_logs'])->addFields([
                    'style', 'supplier', 'isTest', 'requestedBy', 'tranID'
                ])->varDB($this)->add([
                    'type' => self::ITEM_TYPE, 
                    'targetID' => $this->vars->get('itemID'),
                    'field' => $field, 
                    'toVal' => $this->vars->get('resultingQty'), 
                    'fromVal' => $fromVal, 
                    'tranID' => $this->vars->get('tranID', 'getDef'),
                    'isTest' => $this->vars->get('testOrder', 'getDef') == 'test' ?
                        'Y' : 'N',
                    'style' => $this->vars->get('style', 'getDef'),
                    'supplier' => $this->vars->get('supplier', 'getDef'),
                    'requestedBy' => $this->vars->get('requestedBy', 'getDef'),
                ]);
                
                $this->updateItem($post);
                
                return TRUE;
        }
        $this->vars->required($requires, [$this, 'setError']);
        if ($this->error) {
            return $this->error;
        }
        
        switch ($post['inputType']) {
            case 'cat': 
                $name = $this->app->postVar('name');
                $found = $this->getItemCats(['name' => $name]);
                
                if ($found) {
                    return ['error' => 'catExists'];
                }
                
                $this->createItemCat();
                
                return ['success' => 'catCreated'];
            case 'item': 
                
                // Category must exist already
                $name = $this->app->postVar('cat_name');
                $catFound = $this->getItemCats(['name' => $name]);
                $this->vars->set('category_id', $catFound['id']);
                if (! $catFound) {
                    return ['error' => 'catNotFound'];
                }
                
                $error = $this->checkItemExists();
                
                return $error ? $error : $this->jsonCreateItem();
        }
    }
    
    /*
    ****************************************************************************
    */
}