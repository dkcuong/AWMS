<?php

namespace tables;


class truckOrderWaves extends _default
{
    public $ajaxModel = 'truckOrderWaves';

    public $primaryKey = 'tow.id';

    public $fields = [
        'quantity' => [
            'display' => 'Quantity',
            'validation' => 'intval',
            'required' => TRUE,
            'isNum' => TRUE,
            'isPositive' => TRUE,
        ],
        'upc' => [
            'display' => 'UPC',
            'required' => TRUE,
        ],
        'sku' => [
            'display' => 'SKU',
            'required' => TRUE,
        ],
        'color' => [
            'display' => 'Color',
            'required' => TRUE,
        ],
        'size' => [
            'display' => 'Size',
            'required' => TRUE,
        ],
    ];

    public $table = 'truck_orders t
        JOIN      truck_order_waves tow ON tow.truckOrderID = t.id
        JOIN      upcs u ON u.id = tow.upcID';

    public $where = 'tow.active
        AND       u.active';

    public $upcKey = 0;

    public $quantityKey = 0;

    public $skuKey = 0;

    public $colorKey = 0;

    public $sizeKey = 0;

    public $feildKeys = [];

    public $errors = [];

    public $checkFields = [];

    public $orderNumber = 'LPAD(CONCAT(userID, assignNumber), 10, "0")';

    /*
    ****************************************************************************
    */

    function insertFile()
    {
        $upcRows = $quantities = [];
        // only the first page can have Truck Orders. It shall be the only page
        $vendorID = $this->app->post['vendor'][0];
        $scanOrderNumber = $this->app->post['scanOrderNumber'][0];

        $this->checkFields = ['sku', 'color', 'size'];

        foreach ($this->importData as $rowIndex => $rowData) {
            if ($rowIndex == 1) {

                $this->handleColumnTitles($rowData);

                \excel\importer::checkTableErrors($this);

                if ($this->errors) {
                    return;
                }

                $this->getColumnKeys($rowData);

                continue;
            }

            // No blank rows
            if (! \array_filter($rowData)) {
                continue;
            }

            $upcRows = $this->checkInput($upcRows, $rowIndex, $rowData);
        }

        foreach ($upcRows as $upc => $upcData) {
            $quantities[$upc]['quantity'] = $upcData['quantity'];
        }

        $upcRows && $this->checkUPCs([
            'quantities' => $quantities,
            'vendorID' => $vendorID,
        ]);

        unset($this->importData[1]);

        foreach ($this->importData as $rowIndex => $rowData) {

            \excel\importer::checkCellErrors([
                'model' => $this,
                'rowData' => $rowData,
                'rowIndex' => $rowIndex,
            ]);
        }

        if (! isset($rowData)) {
            // the file has proper extension, but actually is not an Excel file
            return $this->errors['wrongType'] = TRUE;
        }

        if ($upcRows) {
            $upcRows = $this->checkDiscrepantUPCs($upcRows);
        }

        if (! $this->errors) {

            $results = $this->getTruckOrderID([$scanOrderNumber]);

            $truckID = getDefault($results[$scanOrderNumber]['truckID'], NULL);

            $upcResults = $truckID ? $this->getTruckOrderUpcIDs($truckID) : [];

            $truckNextID = $truckID ? $truckID : $this->getNextID('truck_orders');

            $this->app->beginTransaction();

            $sqlData = $this->emptyTruckOrder([$scanOrderNumber]);

            $insertTruckSql = '
                INSERT truck_orders (
                    userID,
                    assignNumber
                ) VALUES (
                    ?, ?
                )';

            $insertWaveSql = '
                INSERT truck_order_waves (
                    quantity,
                    truckOrderID,
                    upcID
                ) VALUES (
                    ?, ?, ?
                )';

            $updateTruckSql = '
                UPDATE truck_orders
                SET    submitted = 0,
                       importTime = NOW()
                WHERE  ' . $sqlData['clause'];

            $updateWaveSql = '
                UPDATE truck_order_waves
                SET    quantity = ?,
                       active = 1
                WHERE  truckOrderID = ?
                AND    upcID = ?
                ';

            $sql = $truckID ? $updateTruckSql : $insertTruckSql;

            $this->app->runQuery($sql, $sqlData['params']);

            $updateNeworderSql = '
                UPDATE neworder
                SET    numberofcarton = NULL,
                       numberofpiece = NULL,
                       totalVolume = NULL,
                       totalWeight = NULL,
                       pickID = NULL
                WHERE  scanordernumber = ?
                ';

            $this->app->runQuery($updateNeworderSql, [$scanOrderNumber]);

            foreach ($upcRows as $upcData) {

                $upcID = $upcData['upcID'];
                $quantity = $upcData['quantity'];

                $sql = isset($upcResults[$upcID]) ? $updateWaveSql : $insertWaveSql;

                $this->app->runQuery($sql, [$quantity, $truckNextID, $upcID]);
            }

            $this->app->commit();
        }
    }

    /*
    ************************************************************************
    */

    function getColumnKeys($rowData)
    {
        $fieldKeys = array_flip($rowData);

        $this->feildKeys = [
            'sku' => $fieldKeys['sku'],
            'color' => $fieldKeys['color'],
            'size' => $fieldKeys['size'],
        ];

        $this->upcKey = $fieldKeys['upc'];
        $this->quantityKey = $fieldKeys['quantity'];
        $this->skuKey = $this->feildKeys['sku'];
        $this->colorKey = $this->feildKeys['color'];
        $this->sizeKey = $this->feildKeys['size'];
    }

    /*
    ************************************************************************
    */

    function checkInput($upcRows, $rowIndex, $rowData)
    {
        $upc = trim($rowData[$this->upcKey]);
        $quantity = $rowData[$this->quantityKey];

        if (isset($upcRows[$upc])) {

            $upcData = $upcRows[$upc];

            $this->checkUPCdescription($upcData, $rowData, $rowIndex);

            $upcRows[$upc]['quantity'] += $quantity;

        } else {
            $upcRows[$upc] = [
                'row' => $rowIndex,
                'sku' => trim($rowData[$this->skuKey]),
                'color' => trim($rowData[$this->colorKey]),
                'size' => trim($rowData[$this->sizeKey]),
                'quantity' => $quantity,
            ];
        }

        return $upcRows;
    }

    /*
    ************************************************************************
    */

    function checkUPCdescription($upcData, $rowData, $rowIndex)
    {
        foreach ($this->checkFields as $field) {

            $key = $this->feildKeys[$field];

            if ($upcData[$field] != trim($rowData[$key])) {

                $this->errors['mismatchUPCData'][$rowIndex][] =
                        $rowData[$this->upcKey] . ' at row ' . $upcData['row'];

                return;
            }
        }
    }

    /*
    ************************************************************************
    */

    function checkDiscrepantUPCs($upcRows)
    {
        $upcs = new upcs($this->app);

        $upcKeys = array_keys($upcRows);

        $upcInfo = $upcs->getUPCInfo($upcKeys);

        foreach ($upcRows as $upc => &$upcData) {

            if (! isset($upcInfo[$upc])) {
                continue;
            }

            foreach ($this->checkFields as $field) {
                if ($upcData[$field] == $upcInfo[$upc][$field]) {
                    $upcData['upcID'] = $upcInfo[$upc]['id'];
                } else {

                    $this->errors['discrepantUPCs'][$upc] = TRUE;

                    continue 2;
                }
            }
        }

        return $upcRows;
    }

    /*
    ************************************************************************
    */

    function getTruckOrderUpcIDs($truckOrderID)
    {
        $sql = 'SELECT    upcID
                FROM      truck_order_waves
                WHERE     truckOrderID = ?';

        $results = $this->app->queryResults($sql, [$truckOrderID]);

        return $results;
    }

    /*
    ************************************************************************
    */

    function getTruckOrderID($orderNumbers)
    {
        $sqlData = $this->getMixedCartonsClause($orderNumbers);

        $sql = 'SELECT    ' . $this->orderNumber. ' AS orderNumber,
                          id AS truckID
                FROM      truck_orders
                WHERE     ' . $sqlData['clause'];

        $results = $this->app->queryResults($sql, $sqlData['params']);

        return $results;
    }

    /*
    ************************************************************************
    */

    function getTruckProducts($orderNumbers)
    {
        $sqlData = $this->getMixedCartonsClause($orderNumbers);

        $sql = 'SELECT    tow.id,
                          ' . $this->orderNumber. ' AS scanOrderNumber,
                          upc,
                          quantity
                FROM      truck_orders t
                JOIN      truck_order_waves tow ON tow.truckOrderID = t.id
                JOIN      upcs u ON u.id = tow.upcID
                WHERE     ' . $sqlData['clause'] . '
                AND       u.active
                AND       tow.active';

        $results = $this->app->queryResults($sql, $sqlData['params']);

        $return = [];

        foreach ($results as $values) {

            $upc = $values['upc'];
            $orderNumber = $values['scanOrderNumber'];

            $return[$orderNumber][$upc][] = [
                'quantity' => $values['quantity'],
            ];
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getExistingTruckOrders($orderNumbers)
    {
        $sqlData = $this->getMixedCartonsClause($orderNumbers);

        $sql = 'SELECT    ' . $this->orderNumber. ' AS scanOrderNumber
                FROM      truck_orders t
                JOIN      truck_order_waves tow ON tow.truckOrderID = t.id
                WHERE     ' . $sqlData['clause'] . '
                AND       active';

        $results = $this->app->queryResults($sql, $sqlData['params']);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function submitMixedCartons($orderNumbers)
    {
        $sqlData = $this->getMixedCartonsClause($orderNumbers);

        $sql = 'UPDATE    truck_orders
                SET       submitted = 1
                WHERE     ' . $sqlData['clause'];

        $this->app->runQuery($sql, $sqlData['params']);
    }

    /*
    ****************************************************************************
    */

    function getMixedCartonsClause($orderNumbers)
    {
        $clauses = $params = [];

        foreach ($orderNumbers as $scanOrderNumber) {

            $clauses[] = 'userID = ? AND assignNumber = ?';

            $params[] = (int)substr($scanOrderNumber, 0, 4);
            $params[] = (int)substr($scanOrderNumber, 4);
        }

        $clause = $clauses ? '(' . implode(' OR ', $clauses) . ')' : 0;

        return [
            'clause' => $clause,
            'params' => $params,
        ];
    }

    /*
    ****************************************************************************
    */

    function emptyTruckOrder($orderNumbers)
    {
        $sqlData = $this->getMixedCartonsClause($orderNumbers);

        $sql = 'UPDATE    truck_orders t
                LEFT JOIN truck_order_waves tow ON tow.truckOrderID = t.id
                SET       active = 0,
                          submitted = 0
                WHERE     ' . $sqlData['clause'];

        $this->app->runQuery($sql, $sqlData['params']);

        return $sqlData;
    }

    /*
    ****************************************************************************
    */

    function getOutput($orderNumbers)
    {
        $sqlData = $this->getMixedCartonsClause($orderNumbers);

        $fields = array_keys($this->fields);

        $sql = 'SELECT    ' . $this->primaryKey . ',
                          ' . $this->orderNumber. ' AS scanOrderNumber,
                          ' . implode(',', $fields) . '
                FROM      ' . $this->table . '
                WHERE     ' . $this->where . '
                AND       ' . $sqlData['clause'];

        $results = $this->app->queryResults($sql, $sqlData['params']);

        $return = [];

        foreach ($results as $values) {

            $orderNumber = $values['scanOrderNumber'];

            unset($values['scanOrderNumber']);

            $return[$orderNumber][] = $values;
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

}