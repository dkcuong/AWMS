<?php

namespace tables\inventory;

class pickCartons extends \tables\_default
{
    public $primaryKey = 'pc.id';

    public $ajaxModel = 'inventory\\pickCartons';

    public $fields = [
        'pickID' => [
            'select' => 'pc.pickID',
            'display' => 'Pick #',
            'noEdit' => TRUE,
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'noEdit' => TRUE,
        ],
        'wavePickOrderNumber' => [
            'select' => 'n.scanordernumber',
            'display' => 'Wave Pick Order Number',
            'noEdit' => TRUE,
        ],
        'orderStatusID' => [
            'select' => 'ns.shortName',
            'display' => 'Order Status',
            'searcherDD' => 'statuses\\orders',
            'ddField' => 'shortName',
            'noEdit' => TRUE,
        ],
        'shippingLane' => [
            'select' => 'l.displayName',
            'display' => 'Shipping Lane',
            'noEdit' => TRUE,
        ],
        'ucc128' => [
            'select' => 'CONCAT(co.vendorID,
                            b.id,
                            LPAD(ca.uom, 3, 0),
                            LPAD(ca.cartonID, 4, 0)
                        )',
            'display' => 'UCC128',
            'customClause' => TRUE,
            'noEdit' => TRUE,
            'acDisabled' => TRUE,
        ],
        'name' => [
            'select' => 'co.name',
            'display' => 'Container',
            'noEdit' => TRUE,
        ],
        'containerRecNum' => [
            'select' => 'b.recNum',
            'display' => 'Receiving Number',
            'noEdit' => TRUE,
        ],
        'measureID' => [
            'select' => 'm.displayName',
            'display' => 'Measurement System',
            'searcherDD' => 'inventory\\measure',
            'ddField' => 'displayName',
            'noEdit' => TRUE,
        ],
        'batchID' => [
            'display' => 'Batch Number',
            'noEdit' => TRUE,
        ],
        'uom' => [
            'select' => 'LPAD(UOM, 3, 0)',
            'display' => 'UOM',
            'noEdit' => TRUE,
        ],
        'prefix' => [
            'display' => 'Prefix',
            'noEdit' => TRUE,
        ],
        'suffix' => [
            'display' => 'Suffix',
            'noEdit' => TRUE,
        ],

        'height' => [
            'display' => 'Height',
            'noEdit' => TRUE,
        ],
        'width' => [
            'display' => 'Width',
            'noEdit' => TRUE,
        ],
        'length' => [
            'display' => 'Length',
            'noEdit' => TRUE,
        ],
        'weight' => [
            'display' => 'Weight',
            'noEdit' => TRUE,
        ],
        'upc' => [
            'select' => 'p.upc',
            'display' => 'UPC',
            'noEdit' => TRUE,
        ],
        'sku' => [
            'display' => 'Style Number',
            'noEdit' => TRUE,
        ],
        'size1' => [
            'select' => 'p.size',
            'display' => 'Size',
            'noEdit' => TRUE,
        ],
        'color1' => [
            'select' => 'p.color',
            'display' => 'Color',
            'noEdit' => TRUE,
        ],
        'initialCount' => [
            'display' => 'Original Carton Count',
            'noEdit' => TRUE,
        ],
        'cartonStatusID' => [
            'select' => 'cs.shortName',
            'display' => 'Carton Status',
            'searcherDD' => 'statuses\\inventory',
            'ddField' => 'shortName',
            'noEdit' => TRUE,
        ],
        'location' => [
            'select' => 'lc.displayName',
            'display' => 'Location',
            'noEdit' => TRUE,
        ],
        'plate' => [
            'display' => 'Plate',
            'noEdit' => TRUE,
            'acDisabled' => TRUE,
        ],
        'shippedOrderNumber' => [
            'select' => 'nc.scanordernumber',
            'display' => 'Shipped Order Number',
            'noEdit' => TRUE,
        ],
    ];

    public $where = 'pc.active';

    public $displaySingle = 'Pick Cartons';

    public $mainField = 'pc.id';

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'pick_cartons pc
            JOIN      pick_waves pw ON pw.id = pc.pickID
            JOIN      inventory_cartons ca ON ca.id = pc.cartonID
            JOIN      inventory_batches b ON b.id = ca.batchID
            JOIN      inventory_containers co ON co.recNum = b.recNum
            JOIN      locations l ON l.id = pw.locID
            JOIN      upcs p ON p.id = b.upcID
            JOIN      neworder n ON n.id = pc.orderID
            LEFT JOIN '.$userDB.'.info u ON n.userID = u.id
            JOIN      vendors v ON v.id = co.vendorID
            JOIN      warehouses w ON w.id = v.warehouseID
            JOIN      statuses cs ON cs.id = ca.statusID
            JOIN      statuses ns ON ns.id = n.statusID
            LEFT JOIN neworder nc ON nc.id = ca.orderID
            JOIN      locations lc ON lc.id = ca.locID
            JOIN      measurement_systems m ON m.id = co.measureID
            ';
    }

    /*
    ****************************************************************************
    */

    public function getByOrderNumber($orderNumbers, $fields)
    {
        $params = is_array($orderNumbers) ? $orderNumbers : [$orderNumbers];
        $select = is_array($fields) ? implode(', ', $fields) : $fields;

        $qMarks = $this->app->getQMarkString($params);

        $sql = 'SELECT    ca.id,
                          scanOrderNumber,
                          ' . $select . '
                FROM 	  pick_cartons pc
                JOIN      neworder n ON n.id = pc.orderID
                JOIN      inventory_cartons ca ON ca.id = pc.cartonID
                JOIN      statuses s ON s.id = ca.statusID
                WHERE     scanOrderNumber IN (' . $qMarks . ')
                AND       s.shortName = "' . cartons::STATUS_RACKED . '"
                AND       category = "inventory"
                AND       active';

        $results = $this->app->queryResults($sql, $params);

        $return = [];

        foreach ($results as $invID => $values) {

            $orderNumber = $values['scanOrderNumber'];

            unset($values['scanOrderNumber']);

            $return[$orderNumber][$invID] = $values;
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getReservedByBatch($batcIDs)
    {
        $qMarks = $this->app->getQMarkString($batcIDs);

        $sql = 'SELECT    pc.id,
                          order_batch,
                          upc,
                          SUM(uom) AS uom
                FROM      pick_cartons pc
                JOIN      neworder n ON n.id = pc.orderID
                JOIN      order_batches ob ON ob.id = n.order_batch
                JOIN      inventory_cartons ca ON ca.id = pc.cartonID
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      locations l ON l.id = ca.locID
                JOIN      upcs u ON u.id = b.upcID
                WHERE     order_batch IN (' . $qMarks . ')
                AND       pc.active
                AND       u.active
                AND       NOT isSplit
                AND       NOT unSplit
                AND       isMezzanine
                GROUP BY  order_batch,
                          upc';

        $results = $this->app->queryResults($sql, $batcIDs);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getAllocatedCartons($orderIDs, $mezzanineClause)
    {
        $qMarks = $this->app->getQMarkString($orderIDs);

        $sql = 'SELECT    pc.cartonID,
                          pc.orderID,
                          pickID,
                          uom,
                          upcID,
                          prefix,
                          suffix,
                          l.displayName AS location,
                          isMezzanine
                FROM      pick_cartons pc
                JOIN      inventory_cartons ca ON ca.id = pc.cartonID
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      upcs p ON p.id = b.upcID
                JOIN      locations l ON ca.locID = l.id
                LEFT JOIN inventory_splits sp ON sp.childID = ca.id
                WHERE     pc.orderID IN (' . $qMarks . ')
                AND       ' . $mezzanineClause . '
                AND       l.displayName NOT IN ('
                     . '"' . \tables\locations::NAME_LOCATION_STAGING . '", '
                     . '"' . \tables\locations::NAME_LOCATION_BACK_TO_STOCK . '"'
                . ')
                AND       pc.active
                AND       p.active
                ORDER BY  upc ASC,
                          isMezzanine DESC,
                          uom DESC,
                          l.displayName ASC,
                          ca.id ASC';

        $results = $this->app->queryResults($sql, $orderIDs);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getPickCartonData($param)
    {
        $orderNumber = $param['orderNumber'];
        $orders = $param['orders'];
        $processed = $param['processed'] === 'true';

        $rackedStatusID = cartons::STATUS_RACKED;

        $sql = 'SELECT    pc.id,
                          batchID,
                          upcID,
                          COUNT(ca.id) AS cartonCount,
                          u.sku,
                          size,
                          color,
                          u.upc,
                          uom,
                          SUM(ca.uom) AS quantity,
                          l.displayName AS cartonLocation,
                          b.prefix,
                          b.suffix,
                          0 AS available,
                          isMezzanine
                FROM      pick_cartons pc
                JOIN 	  neworder n ON n.id = pc.orderID
                JOIN      inventory_cartons ca ON ca.id = pc.cartonID
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      locations l ON l.id = ca.locID
                JOIN      upcs u ON u.id = b.upcID
                JOIN      statuses s ON s.id = ca.statusID
                WHERE     scanOrderNumber = ?
                AND       n.pickID
                AND       pc.active
                AND       u.active
                AND       s.shortName = "' . cartons::STATUS_RACKED . '"
                AND       category = "inventory"
                AND       NOT isSplit
                AND       NOT unSplit
                GROUP BY  upcID,
                          uom,
                          l.displayName,
                          prefix,
                          suffix';

        $dbProducts = $this->app->queryResults($sql, $orderNumber);

        $batches = new batches($this->app);

        $results = $batches->addUnitDimensions($dbProducts);

        $return = $orders->multiUOMResult($results, $processed);

        return $return;
    }

    /*
    ****************************************************************************
    */

}
