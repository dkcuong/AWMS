<?php

namespace tables;

class upcs extends _default
{
    public $primaryKey = 'u.id';

    public $ajaxModel = 'upcs';

    public $fields = [
        'clientName' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'noEdit' => TRUE,
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'upc' => [
            'select' => 'u.upc',
            'display' => 'UPC',
            'noEdit' => TRUE,
        ],
        'category' => [
            'select' => 'uc.name',
            'display' => 'Category',
            'searcherDD' => 'inventory\\upcsCategories',
            'ddField' => 'uc.name',
            'update' => 'u.catID',
        ],
        'description' => [
            'display' => 'Description',
        ],
        'sku' => [
            'select' => 'u.sku',
            'display' => 'Style',
            'update' => 'u.sku',
            'noEmptyInput' => TRUE,
        ],
        'size1' => [
            'select' => 'size',
            'display' => 'Size',
            'update' => 'size',
            'noEmptyInput' => TRUE,
        ],
        'color1' => [
            'select' => 'color',
            'display' => 'Color',
            'update' => 'color',
            'noEmptyInput' => TRUE,
        ],
        'active' => [
            'select' => 'IF(u.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'u.active',
            'updateOverwrite' => TRUE,
        ],
    ];

    public $table = 'upcs u
           LEFT JOIN upcs_categories uc ON uc.id = u.catID
           JOIN inventory_batches b ON b.upcID = u.id
           JOIN inventory_containers co ON co.recNum = b.recNum
           JOIN vendors v ON v.id = co.vendorID
           JOIN warehouses w ON w.id = v.warehouseID';

    public $groupBy = 'u.sku, u.upc';

    public $mainField = 'u.id';

    public $multiSelect = 'clientName';

    /*
    ****************************************************************************
    */

    function getUPCInfo($upc)
    {
        $qMarks = $this->app->getQMarkString($upc);

        $sql = 'SELECT  upc,
                        sku,
                        size,
                        color,
                        id
                FROM    upcs
                WHERE   upc IN (' . $qMarks . ')
                AND     active
                ';

        $result = is_array($upc) ? $this->app->queryResults($sql, $upc) :
            $this->app->queryResult($sql, [$upc]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getUpcDescr($skuSizeColor, $params)
    {
        $condition = implode(' OR ', $skuSizeColor);
        $sql = 'SELECT      upc,
                            sku,
                            size,
                            color
                FROM        upcs
                WHERE       ' . $condition . '
                GROUP BY    sku,
                            size,
                            color
                ';

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getUpcs($upcs=[])
    {
        $clause = $upcs ? 'upc IN ('.$this->app->getQMarkString($upcs).')' : 1;

        $sql = 'SELECT  upc,
                        id,
                        sku,
                        active
                FROM    upcs
                WHERE   '.$clause;

        $results = $this->app->queryResults($sql, $upcs);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getOriginalUpcIDs($passedUPCs)
    {
        $qMarks = $this->app->getQMarkString($passedUPCs);

        $sql = 'SELECT    o.id
                FROM      upcs_originals o
                LEFT JOIN upcs u ON u.upc = o.upc
                WHERE     o.upc IN (' . $qMarks . ')
                AND       (u.id IS NULL
                    OR NOT u.active)';

        $result = $this->app->queryResults($sql, $passedUPCs);

        $data = array_keys($result);

        return $data;
    }

    /*
    ****************************************************************************
    */

    function getStyleRows($upc)
    {
        $sql = 'SELECT      upc,
                            u.sku,
                            prefix,
                            suffix,
                            height,
                            width,
                            length,
                            weight,
                            eachHeight,
                            eachWidth,
                            eachLength,
                            eachWeight,
                            size AS size1,
                            color AS color1,
                            catID AS categoryUPC
                FROM        upcs u
                LEFT JOIN   inventory_batches b ON b.upcID = u.id
                WHERE       upc = ?
                ORDER BY    b.id DESC
                LIMIT       1';

        $results = $this->app->queryResult($sql, [$upc]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getAutocomplete($target, $value)
    {
        if (! $value || ! in_array($target, ['upc', 'sku'])) {
            return FALSE;
        }

        // need to return string result for SKU. Use CONCAT() rather than CAST()
        $field = $target == 'sku' ? 'DISTINCT CONCAT(sku, " ")' : $target;

        $sql = 'SELECT  ' . $field . '
                FROM    upcs
                WHERE   active = ?
                AND     ' . $target . ' LIKE ?
                LIMIT   10';

        $active = \models\config::getStatus('active');

        $results = $this->app->queryResults($sql, [$active, $value . '%']);

        $upcs = [];
        foreach (array_keys($results) as $row) {
            $upcs[] = [
                'value' => $row,
            ];
        }

        return $upcs;
    }

    /*
    ****************************************************************************
    */

    function getSkuAutocomplete($text)
    {
        if (! $text) {
            return FALSE;
        }

        $sql = 'SELECT    id,
                          sku,
                          size,
                          color,
                          upc
                FROM      upcs
                WHERE     sku LIKE ?
                AND       active
                LIMIT 10';

        $results = $this->app->queryResults($sql, ['%' . $text . '%']);

        $rows = [];

        foreach ($results as $row => $value) {
            $rows[] = [
                'value' => $row,
                'sku' => $value['sku'],
                'size' => $value['size'],
                'color' => $value['color'],
                'upc' => $value['upc']
            ];
        }

        return $rows;
    }

     /*
    ****************************************************************************
    */

    function getBySkuColorSize($sku, $color, $size)
    {
        $sql = 'SELECT    upc,
                          id
                FROM      upcs
                WHERE     sku = ?
                AND       color = ?
                AND       size = ?
                ';

        $result = $this->app->queryResult($sql, [$sku, $color, $size]);

        return $result['id'];
    }

    /*
    ****************************************************************************
    */

    function getUpcsWarehouses($upcs)
    {
        $qMarks = $this->app->getQMarkString($upcs);

        $sql = 'SELECT    upc,
                          warehouseID
                FROM      upcs u
                JOIN      inventory_batches b ON b.upcID = u.id
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      vendors v ON v.id = co.vendorID
                WHERE     upc IN (' . $qMarks . ')
                ';

        $results = $this->app->queryResults($sql, $upcs);

        $return = [];

        foreach ($results as $upc => $values) {
            $return[$upc] = $values['warehouseID'];
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function seldatUPC()
    {
        // Select the next available UPC that is not checked out
        $sql = 'SELECT    o.id,
                          o.upc
                FROM      upcs_originals o
                LEFT JOIN upcs_checkout c ON c.upcID = o.id
                LEFT JOIN upcs u ON u.upc = o.upc
                -- Use check out times to make sure no one has tried to use this
                -- UCP recently
                WHERE   ( c.checkedOut IS NULL
                          OR NOW() - INTERVAL 1 DAY > c.checkedOut )
                AND      u.id IS NULL
                ORDER BY  o.upc ASC
                LIMIT     1
                ';

        $result = $this->app->queryResult($sql);

        if ($result) {
            // Note that this has been checked out
            $sql = 'INSERT INTO upcs_checkout(
                        upcID,
                        checkedOut
                    ) VALUE (
                        ?,
                        NOW()
                    )
                    ON DUPLICATE KEY UPDATE
                        checkedOut = NOW()';

            $this->app->runQuery($sql, [
                $result['id']
            ]);
        }

        return $result ? $result['upc'] : FALSE;
    }

    /*
    ****************************************************************************
    */

    function getVendorUPCMismatch($upcs, $vendorID)
    {
        $qMarks = $this->app->getQMarkString($upcs);

        $sql = 'SELECT    upc
                FROM      upcs u
                JOIN      inventory_batches b ON b.upcID = u.id
	        JOIN      inventory_cartons ca ON ca.batchID = b.id
                JOIN      inventory_containers co ON co.recNum = b.recNum
                WHERE     upc IN (' . $qMarks . ')
                AND       vendorID != ?
                GROUP BY  u.id
                HAVING    SUM(IF(ca.statusID != 4, 1, 0))';

        $params = $upcs;

        $params[] = $vendorID;

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function searchSKUAutoComplete($sku)
    {
        $results = [];

        $sql = 'SELECT   sku
                FROM     upcs
                WHERE    sku LIKE ?
                AND      active
                GROUP BY sku
                LIMIT    10';

        $skus = $this->app->queryResults($sql, [$sku . '%']);

        if (! $skus) {
            return $results;
        }

        $keySKUs = array_keys($skus);

        foreach ($keySKUs as $row) {
            $results[] = ['value' => $row];
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function handleImportedUPCs($importedUPCs, $upcsToAdd)
    {
        $insertSql = '
            INSERT INTO upcs (upc, sku, color, size) VALUES (
                ?, ?, ?, ?
            )';

        $updateSql = '
            UPDATE upcs
            SET    active = 1
            WHERE  upc IN (' . $this->app->getQMarkString($importedUPCs) . ')';

        $this->app->beginTransaction();

        foreach ($upcsToAdd as $upc => $values) {
            $this->app->runQuery($insertSql, [
                $upc,
                strtoupper($values['sku']),
                strtoupper($values['color']),
                strtoupper($values['size']),
            ]);
        }

        $this->app->runQuery($updateSql, $importedUPCs);

        $this->app->commit();
    }

    /*
    ****************************************************************************
    */

    function getCheckDuplicates($data)
    {
        $clause = 'sku = ? AND color = ? AND size = ?';

        $clauseArray = array_fill(0, count($data), $clause);

        $clauses = implode(' OR ', $clauseArray);

        $params = $return = [];

        foreach ($data as $values) {

            $upcData = array_values($values);

            $params = array_merge($params, $upcData);
        }

        $sql = 'SELECT    CONCAT_WS("-", sku, color, size),
                          upc
                FROM      upcs
                WHERE     ' . $clauses;

        $results = $this->app->queryResults($sql, $params);

        foreach ($data as $upc => $values) {

            $key = $values['sku'] . '-' . $values['color'] . '-' . $values['size'];

            if (isset($results[$key]) && $results[$key]['upc'] != $upc) {
                $return[] = $results[$key]['upc'];
            }
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

}