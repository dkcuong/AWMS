<?php

namespace tables;

class warehouses extends _default
{
    public $ajaxModel = 'warehouses';

    public $primaryKey = 'id';

    public $fields = [
        'id' => [
            'select' => 'id',
            'display' => 'Warehouse ID',
            'noEdit' => TRUE,
        ],
        'displayName' => [
            'display' => 'Location',
        ],
    ];

    public $table = 'warehouses';

    /*
    ****************************************************************************
    */

    function getOrderBatchWarehouse($batch)
    {
        $sql = 'SELECT w.id
                FROM   warehouses w
                JOIN   vendors v ON v.warehouseID = w.id
                JOIN   order_batches b ON b.vendorID = v.id
                WHERE  b.id = ?
                ';

        $result = $this->app->queryResult($sql, [$batch]);

        return $result['id'];
    }

    /*
    ****************************************************************************
    */

    function getWarehouseByRecNum($recNum)
    {
        $sql = 'SELECT w.id
                FROM   warehouses w
                JOIN   vendors v ON v.warehouseID = w.id
                JOIN   inventory_containers co ON co.vendorID = v.id
                WHERE  recNum = ?
                ';

        $result = $this->app->queryResult($sql, [$recNum]);

        return $result['id'];
    }

    /*
    ****************************************************************************
    */

    function getByFullName($vendorNames)
	{
        if (! $vendorNames) {
            return [];
        }

		$qMarks = $this->app->getQMarkString($vendorNames);

		$sql = 'SELECT    CONCAT(w.shortName, "_", vendorName),
		        	      v.id AS vendorID,
		        	      w.id AS warehouseID
                FROM      vendors v
                JOIN      warehouses w ON w.id = v.warehouseID
                WHERE     CONCAT(w.shortName, "_", vendorName) IN (' . $qMarks . ')
                ';

		$results = $this->app->queryResults($sql, $vendorNames);

		return $results;
	}

    /*
    ****************************************************************************
    */

    function getWarehouses()
    {
        $sql = 'SELECT   id,
                         shortName
                FROM     ' . $this->table;

        $results = $this->app->queryResults($sql);

        $return = [];

        foreach ($results as $key => $values) {
            $return[$key] = $values['shortName'];
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getWarehouse($id=NULL)
    {
        $clause = $id ? 'id = ?' : 1;
        $params = $id ? [$id] : [];

        $sql = 'SELECT  id,
                        displayName,
                        shortName
                FROM    warehouses
                WHERE   ' . $clause;

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

}
