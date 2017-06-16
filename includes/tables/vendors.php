<?php

namespace tables;

class vendors extends _default
{
    public $primaryKey = 'v.id';

    public $ajaxModel = 'vendors';

    public $fields = [
        'id' => [
            'select' => 'v.id',
            'display' => 'Client ID',
            'noEdit' => TRUE,
        ],
        'fullVendorName' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Full Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'noEdit' => TRUE,
            'ignore' => TRUE,
        ],
        'warehouseID' => [
            'select' => 'w.displayName',
            'display' => 'Warehouse',
            'searcherDD' => 'warehouses',
            'ddField' => 'displayName',
            'update' => 'v.warehouseID',
        ],
        'vendorName' => [
            'display' => 'Client Name',
            'update' => 'v.vendorName',
        ],
        'cust_type' => [
            'display' => 'Customer Type',
        ],
        'bill_to_add1' => [
            'display' => 'Bill Address Line 1',
        ],
        'bill_to_add2' => [
            'display' => 'Bill Address Line 2',
        ],
        'bill_to_state' => [
            'display' => 'Bill to State',
        ],
        'bill_to_city' => [
            'display' => 'Bill to City',
        ],
        'bill_to_cnty' => [
            'display' => 'Bill to Country',
        ],
        'bill_to_zip' => [
            'display' => 'Bill to Zipcode',
        ],
        'bill_to_contact' => [
            'display' => 'Bill to Contact',
        ],
        'ship_to_add1' => [
            'display' => 'Ship Address Line 1',
        ],
        'ship_to_add2' => [
            'display' => 'Ship Address Line 2',
        ],
        'ship_to_state' => [
            'display' => 'Ship to State',
        ],
        'ship_to_city' => [
            'display' => 'Ship to City',
        ],
        'ship_to_cnty' => [
            'display' => 'Ship to Country',
        ],
        'ship_to_zip' => [
            'display' => 'Ship to Zipcode',
        ],
        'net_terms' => [
            'display' => 'Net Terms',
        ],
        'active' => [
            'insertDefaultValue' => TRUE,
            'select' => 'IF(v.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'v.active',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $table = 'vendors v
        JOIN      warehouses w ON v.warehouseID = w.id
        JOIN      customer_mstr c ON c.cust_id = v.id';

    public $insertTable = 'vendors';

    public $displaySingle = 'Client';

    public $orderBy = 'shortName ASC,
                       vendorName ASC';

    public $customInsert = 'vendors';

    const VENDOR_CODE_GO_LIVE_WORK = 'GL';

    /*
    ****************************************************************************
    */

    function __construct($app = FALSE)
    {
        \common\vendor::addConditionByVendor($app, $this);

        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */

    function getByName($name)
    {
        $sql = 'SELECT '.$this->primaryKey.'
                FROM   '.$this->table.'
                WHERE  vendorName = ?
                AND    v.active';

        $results = $this->app->queryResult($sql, [$name]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getAlphabetizedNames($warehouse=NULL)
    {
        $whereClause = ' WHERE v.active';
        $whereClause .= $warehouse ?  ' AND warehouseID = ' . $warehouse : NULL;

        $sql = 'SELECT v.id,
                       v.id AS vendorID,
                       w.id AS warehouseID,
                       CONCAT(w.shortName, "_", vendorName) AS fullVendorName
                FROM   '.$this->table.'
                '.$whereClause . '
                ORDER BY CONCAT(w.shortName, "_", vendorName) ASC';

        $results = $this->app->queryResults($sql);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getIDByUCCs($ucc, $field='co.vendorID')
    {
        $uccs = is_array($ucc) ? $ucc : [$ucc];

        $joinVendors = NULL;

        $selectField = NULL;
        switch ($field) {
            case 'co.vendorID':
                $selectField = $field;
                break;
            case 'fullVendorName':
                $joinVendors = 'JOIN vendors v ON v.id = co.vendorID
                                JOIN warehouses w ON w.id = v.warehouseID';
                $selectField = $this->fields['fullVendorName']['select'];
                break;
            default:
                die;
        }

        $sql = 'SELECT    '.$selectField.'
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                '.$joinVendors.'
                WHERE     CONCAT(
                            co.vendorID,
                            b.id,
                            LPAD(uom, 3, 0),
                            LPAD(cartonID, 4, 0)
                          ) IN ('.$this->app->getQMarkString($uccs).')';

        $resutls = $this->app->queryResults($sql, $uccs);

        $vendorIDs = array_keys($resutls);

        return $vendorIDs;
    }

    /*
    ****************************************************************************
    */

    function makeFullName($params)
    {
        $warehouses = new warehouses($this->app);
        $warehouse = $warehouses->search([
            'selectField' => 'shortName',
            'search' => 'id',
            'term' => $params['warehouseID'],
            'oneResult' => TRUE,
        ]);

        return $warehouse['shortName'].'_'.$params['vendorName'];
    }

    /*
    ****************************************************************************
    */

    function update($columnID, &$value, $rowID, $ajaxRequest=FALSE)
    {
        $result = $this->search([
            'search' => $this->primaryKey,
            'addFields' => 'warehouseID',
            'term' => $rowID,
            'oneResult' => TRUE,
        ]);

        $keys = array_keys($this->fields);

        $columnKey = $keys[$columnID];
        switch ($columnKey) {
            case 'vendorName':
            case 'warehouseID':
                $result[$columnKey] = $value;
        }

        $newName = $this->makeFullName($result);

        $sql = 'UPDATE customer_mstr
                SET    cust_nm = ?
                WHERE  cust_id = ?';

        $this->app->runQuery($sql, [$newName, $rowID]);

        return parent::update($columnID, $value, $rowID, $ajaxRequest);
    }

    /*
    ****************************************************************************
    */

    function addRowQuery($post, $unbound=FALSE)
    {
        $results = parent::addRowQuery($post, $unbound);

        $results['sql'] .= 'INSERT INTO customer_mstr
                            SET         cust_id = ?,
                                        cust_nm = ?;';

        $nextID = $this->getNextID('vendors');

        $results['params'][] = $nextID;
        $results['params'][] = $this->makeFullName($post);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getVendorName($vendorID)
    {
        $isArray = is_array($vendorID);

        $params = $isArray ? $vendorID : [$vendorID];

        $sql = 'SELECT  v.id,
                        CONCAT(w.shortName, "_", vendorName) AS fullVendorName
                FROM    '.$this->table.'
                WHERE   v.id IN (' . $this->app->getQMarkString($params) . ')';

        $results = $this->app->queryResults($sql, $params);

        $data = $isArray ? $results :
            getDefault($results[$vendorID]['fullVendorName'], NULL);

        return $data;
    }

    /*
    ****************************************************************************
    */

    function getByBatchNumber($batch)
    {
        if (is_array($batch)) {

            $clauses = array_fill(0, count($batch), 'b.id = ?');

            $sql = 'SELECT b.id,
                           v.id
                    FROM   vendors v
                    JOIN   order_batches b ON b.vendorID = v.id
                    WHERE  '.implode(' OR ', $clauses);

            $results = $this->app->queryResults($sql, $batch);

            $return = [];

            foreach ($results as $batch => $vendor) {
                $return[$batch] = $vendor['id'];
            }

            return $return;
        } else {
            $sql = 'SELECT v.id
                    FROM   vendors v
                    JOIN   order_batches b ON b.vendorID = v.id
                    WHERE  b.id = ?
                    ';

            $result = $this->app->queryResult($sql, [$batch]);

            return $result['id'];
        }
    }

    /*
    ****************************************************************************
    */

    function getByScanOrderNumber($orderNumber)
    {
        if (is_array($orderNumber)) {

            $clauses = array_fill(0, count($orderNumber), 'scanordernumber = ?');

            $sql = 'SELECT scanordernumber,
                           v.id
                    FROM   vendors v
                    JOIN   order_batches b ON b.vendorID = v.id
                    JOIN   neworder n ON n.order_batch = b.id
                    WHERE  '.implode(' OR ', $clauses);

            $results = $this->app->queryResults($sql, $orderNumber);

            $return = [];

            foreach ($results as $orderNumber => $vendor) {
                $return[$orderNumber] = $vendor['id'];
            }

            return $return;

        } else {
            $sql = 'SELECT v.id
                    FROM   vendors v
                    JOIN   order_batches b ON b.vendorID = v.id
                    JOIN   neworder n ON n.order_batch = b.id
                    WHERE  n.scanordernumber = ?
                    ';

            $result = $this->app->queryResult($sql, [$orderNumber]);

            return $result['id'];
        }
    }

    /*
    ****************************************************************************
    */

    function getVendorWarehouse($vendorID)
    {
        $sql = 'SELECT  warehouseID
                FROM    vendors
                WHERE   id = ?';

        $result = $this->app->queryResult($sql, [$vendorID]);

        return $result['warehouseID'];
    }

    /*
    ****************************************************************************
    */

    function getWarehouseByVendorIDs($vendorIDs)
    {
        $sql = 'SELECT    id,
                          warehouseID
                FROM      vendors
                WHERE     id IN (' . $this->app->getQMarkString($vendorIDs) . ')';

        $results = $this->app->queryResults($sql, $vendorIDs);

        $keys = array_keys($results);
        $values = array_column($results, 'warehouseID');

        return array_combine($keys, $values);
    }

    /*
    ****************************************************************************
    */

    function getVendorData($vendorID)
    {
        $sql = 'SELECT    v.id,
                          vendorName,
                          warehouseID,
                          shortName
                FROM      '.$this->table.'
                WHERE     v.id = ?';

        $results = $this->app->queryResult($sql, [$vendorID]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getVendorDataByID($vendorID)
    {
        $sql = 'SELECT    vendorName AS vendor,
                          w.id AS warehouseID,
                          w.shortName AS warehouse
                FROM      '.$this->table.'
                WHERE     v.id = ?
                AND       active
                ';

        $result = $this->app->queryResult($sql, [$vendorID]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getVendorDropdown($warehouse=FALSE)
    {
        $vendorNames = $this->getAlphabetizedNames($warehouse);

        $label = $warehouse !== FALSE ? '<label>Customer: </label>' : 'Client';
        $id = $warehouse !== FALSE ? 'customer-input' : 'vendor';

        ob_start();

        echo $label?>

         <select id="<?php echo $id ?>" class="vendor" name="customer">
             <option value="0" selected>Select Customer</option>

        <?php

        foreach ($vendorNames as $vendorID => $vendorData) {?>

            <option value="<?php echo $vendorID; ?>"
                    data-warehouse-id="<?php echo $vendorData['warehouseID']; ?>"><?php
                echo $vendorData['fullVendorName']; ?></option>

        <?php } ?>

        </select>

        <?php

        return ob_get_clean();
    }

    /*
    ****************************************************************************
    */

    function customInsert($post)
    {
        $ajaxRequest = TRUE;

        $vendordSql = '
            INSERT INTO vendors (
                vendorName, warehouseID
            ) VALUES (
                ?, ?
            ) ON DUPLICATE KEY UPDATE
                active = 1';

        $vendorsParams = [
            $post['vendorName'],
            $post['warehouseID'],
        ];

        $this->app->runQuery($vendordSql, $vendorsParams, $ajaxRequest);

        $vendor = $this->getByNameAndWarehouseID($vendorsParams);

        $customerMasterSql = '
            INSERT IGNORE INTO customer_mstr (
                cust_id,
                cust_nm,
                cust_type,
                bill_to_add1,
                bill_to_add2,
                bill_to_state,
                bill_to_city,
                bill_to_cnty,
                bill_to_zip,
                bill_to_contact,
                ship_to_add1,
                ship_to_add2,
                ship_to_state,
                ship_to_city,
                ship_to_cnty,
                ship_to_zip,
                net_terms,
                create_by
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?
            ) ON DUPLICATE KEY UPDATE
                cust_type = ?,
                bill_to_add1 = ?,
                bill_to_add2 = ?,
                bill_to_state = ?,
                bill_to_city = ?,
                bill_to_cnty = ?,
                bill_to_zip = ?,
                bill_to_contact = ?,
                ship_to_add1 = ?,
                ship_to_add2 = ?,
                ship_to_state = ?,
                ship_to_city = ?,
                ship_to_cnty = ?,
                ship_to_zip = ?,
                net_terms = ?,
                update_by = ?
            ';

        $masterParams = [
            $post['cust_type'],
            $post['bill_to_add1'],
            $post['bill_to_add2'],
            $post['bill_to_state'],
            $post['bill_to_city'],
            $post['bill_to_cnty'],
            $post['bill_to_zip'],
            $post['bill_to_contact'],
            $post['ship_to_add1'],
            $post['ship_to_add2'],
            $post['ship_to_state'],
            $post['ship_to_city'],
            $post['ship_to_cnty'],
            $post['ship_to_zip'],
            $post['net_terms'],
            \access::getUserID(),
        ];

        $params = [
            $vendor['id'],
            $vendor['fullVendorName'],
        ];

        $params = array_merge($params, $masterParams, $masterParams);

        $this->app->runQuery($customerMasterSql, $params, $ajaxRequest);
    }

    /*
    ****************************************************************************
    */

    function getByNameAndWarehouseID($params)
    {
        $sql = 'SELECT    ' . $this->primaryKey . ' AS id,
                          CONCAT(w.shortName, "_", vendorName) AS fullVendorName
                FROM      vendors v
                JOIN      warehouses w ON w.id = v.warehouseID
                WHERE     vendorName = ?
                AND       warehouseID = ?
                AND       v.active';

        $result = $this->app->queryResult($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getLocationIDByWarehouse($locationName, $warehouseID)
    {
        $sql = 'SELECT  id
                FROM    locations
                WHERE   displayName = ?
                AND     warehouseID = ?';

        $results = $this->app->queryResult($sql, [$locationName, $warehouseID]);

        return $results ? $results['id'] : FALSE;
    }

    /*
    ****************************************************************************
    */

    function getVendorNames()
    {
        $sql = 'SELECT    vendorName,
                          clientCode
                FROM      vendors
                ORDER BY  vendorName';

        $results = $this->app->queryResults($sql);

        return $results;
    }


    /*
    ****************************************************************************
    */

    function verifyVendors($vendorData, $warehouseIDs)
    {
        if (! $vendorData || ! $warehouseIDs) {
            return [];
        }

        $warehouses = new warehouses($this->app);

        $checkVendors = $vendorKeys = $vendorNames = [];

        $warehouseID = $warehouseIDs['inWarehouseID'];

        foreach ($vendorData as $vendor) {

            $vendorKey = $warehouseID . '-' . $vendor['vendorName'];

            $checkVendors[$vendorKey] = [
                'vendorName' => $vendor['vendorName'],
                'warehouseID' => $warehouseID,
            ];
        }

        $availableVendors = $this->getByNamesAndWarehouseIDs($checkVendors);

        $missingVendors = array_diff_key($checkVendors, $availableVendors);

        $warehouseData = $warehouses->getWarehouse($warehouseID);

        $nextID = $this->getNextID('vendors');

        $this->app->beginTransaction();

        foreach ($vendorData as $vendor) {

            $vendorName = $vendor['vendorName'];

            if ($missingVendors) {

                $result = $this->addMissingVendor([
                    'missingVendors' => $missingVendors,
                    'vendor' => $vendor,
                    'warehouseID' => $warehouseID,
                    'warehouseShortName' => $warehouseData[$warehouseID]['shortName'],
                    'nextID' => $nextID,
                ]);

                if ($result) {
                    // new vendor created
                    $vendorNames[$vendorName] = $nextID;

                    $nextID = $result;
                } else {
                    // vendor exists
                    $this->reactivateVendor($vendor['vendorID']);

                    $key = $warehouseID . '-' . $vendorName;

                    $vendorNames[$vendorName] = $availableVendors[$key]['vendorID'];
                }
            } else {
                foreach ($vendorData as $vendor) {

                    $this->reactivateVendor($vendor['vendorID']);

                    $key = $warehouseID . '-' . $vendorName;

                    $vendorNames[$vendorName] = $availableVendors[$key]['vendorID'];
                }
            }
        }

        $this->app->commit();

        return $vendorNames;
    }

    /*
    ****************************************************************************
    */

    function addMissingVendor($data)
    {
        $missingVendors = $data['missingVendors'];
        $vendor = $data['vendor'];
        $warehouseID = $data['warehouseID'];
        $warehouseShortName = $data['warehouseShortName'];
        $nextID = $data['nextID'];

        $vendorsSql = 'INSERT INTO vendors (
                           vendorName, clientCode, email, warehouseID
                       ) VALUES (
                           ?, ?, ?, ?
                       )';

        $customerMasterSql = 'INSERT INTO customer_mstr (
                                  cust_id, cust_nm
                              ) VALUES (
                                  ?, ?
                              )';

        foreach ($missingVendors as $missingVendor) {
            if ($vendor['vendorName'] == $missingVendor['vendorName']) {

                $this->app->runQuery($vendorsSql, [
                    $vendor['vendorName'],
                    $vendor['clientCode'],
                    $vendor['email'],
                    $warehouseID,
                ]);

                $this->app->runQuery($customerMasterSql, [
                    $nextID,
                    $warehouseShortName . '_' . $vendor['vendorName'],
                ]);

                return $nextID + 1;
            }
        }

        return 0;
    }

    /*
    ****************************************************************************
    */

    function reactivateVendor($vendorID)
    {
        $sql = 'UPDATE    vendors
                SET       active = 1
                WHERE     id = ?';

        $this->app->runQuery($sql, [$vendorID]);
    }

    /*
    ****************************************************************************
    */

    function getByNamesAndWarehouseIDs($values)
    {
        foreach ($values as $value) {
            $params[] = $value['vendorName'];
            $params[] = $value['warehouseID'];
        }

        $clauses = array_fill(0, count($values),
                'vendorName = ? AND warehouseID = ?');

        $sql = 'SELECT    CONCAT_WS("-", w.id, vendorName) AS vendorKey,
                          v.id AS vendorID,
                          vendorName,
                          w.shortName,
                          warehouseID
                FROM      vendors v
                JOIN      warehouses w ON w.id = v.warehouseID
                WHERE     ' . implode(' OR ', $clauses);

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

}
