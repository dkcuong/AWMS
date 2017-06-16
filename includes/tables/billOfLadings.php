<?php

namespace tables;

class billOfLadings extends \tables\_default
{
    public $ajaxModel = 'billOfLadings';

    public $primaryKey = 'si.bolLabel';

    public $fields = [
        'bolLabel' => [
            'select' => 'si.bolLabel',
            'display' => 'BOL Label',
            'noEdit' => TRUE,
        ],
        'scanordernumber' => [
            'display' => 'Order Number',
            'noEdit' => TRUE,
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'noEdit' => TRUE,
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'bolID' => [
            'select' => 'si.bolID',
            'display' => 'BOL Number',
            'noEdit' => TRUE,
        ],
        'shipFromName' => [
            'select' => 'cad.companyName',
            'display' => 'Ship From Name',
            'noEdit' => TRUE,
        ],
        'shipfromaddress' => [
            'select' => 'cad.address',
            'display' => 'Ship From Address',
            'noEdit' => TRUE,
        ],
        'shipfromcity' => [
            'select' => 'CONCAT(cad.city,", ",cad.country, ", ", cad.state, ", ", cad.zip)',
            'display' => 'Ship From City',
            'noEdit' => TRUE,
        ],
        'shiptoname' => [
            'select' => 'si.shiptoname',
            'display' => 'Ship To Name',
        ],
        'shiptoaddress' => [
            'select' => 'si.shiptoaddress',
            'display' => 'Ship To Address',
        ],
        'shiptocity' => [
            'select' => 'si.shiptocity',
            'display' => 'Ship To City',
        ],
        'shiptotel' => [
            'select' => 'si.shiptotel',
            'display' => 'Ship To Tel',
        ],
        'partyname' => [
            'select' => 'si.partyname',
            'display' => '3rdParty Name',
        ],
        'partyaddress' => [
            'select' => 'si.partyaddress',
            'display' => '3rdParty Address',
        ],
        'partycity' => [
            'select' => 'si.partycity',
            'display' => '3rdParty City',
        ],
        'specialinstruction' => [
            'select' => 'si.specialinstruction',
            'display' => 'Special Instruction',
        ],
        'carriername' => [
            'select' => 'si.carriername',
            'display' => 'Carrier Name',
        ],
        'otherdocumentinform' => [
            'select' => 'si.otherdocumentinform',
            'display' => 'Order Documnent',
        ],
        'freightchargetermby' => [
            'select' => 'IF(freightchargetermby = "freightchargetermbycollect",
                            "Collect",
                            IF(freightchargetermby = "freightchargetermbyprepaid",
                                "Prepaid", "3rdParty"))',
            'display' => 'Freight Charge By',
            'searcherDD' => 'shipments\\freightChargeTerms',
            'ddField' => 'displayName',
            'update' => 'si.freightchargetermby',
        ],
        'freightchargeterminfo' => [
            'display' => 'Freight Charge Cost',
        ],
        'carrier' => [
            'select' => 'si.carrier',
            'display' => 'Carrier',
            'searcherDD' => 'shipments\\carrier',
            'ddField' => 'displayName',
            'update' => 'si.carrier'
        ],
        'carriernote' => [
            'select' => 'si.carriernote',
            'display' => 'Carrier Note',
        ],
        'commodityDesc' => [
            'select' => 'com.description',
            'display' => 'Commodity Desc',
            'searcherDD' => 'shipments\\commodity',
            'ddField' => 'description',
            'update' => 'si.commodity',
        ],
        'commodityNmfc' => [
            'select' => 'com.nmfc',
            'display' => 'Commodity NMFC',
            'noEdit' => TRUE,
        ],
        'commodityClass' => [
            'select' => 'com.class',
            'display' => 'Commodity Class',
            'noEdit' => TRUE,
        ],
        'trailernumber' => [
            'display' => 'Trailer Number',
        ],
        'sealnumber' => [
            'display' => 'Seal Number',
        ],
        'scac' => [
            'display' => 'SCAC',
        ],
        'pronumber' => [
            'display' => 'Pro Number',
        ],
        'trackingNumber' => [
            'display' => 'Tracking Number',
        ],
        'shiptype' => [
            'select' => 'si.shiptype',
            'display' => 'Ship Type',
        ],
        'attachbilloflading' => [
            'select' => 'IF(attachbilloflading = "1", "Yes", "No")',
            'display' => 'Attach BOL',
            'searcherDD' => 'shipments\\attachBillOfLading',
            'ddField' => 'displayName',
            'update' => 'si.attachBillOfLading',
        ],
        'acceptablecustomer' => [
            'select' => 'IF(acceptablecustomer = "1", "Yes", "No")',
            'display' => 'Accept Customer',
            'searcherDD' => 'shipments\\acceptTableCustomers',
            'ddField' => 'displayName',
            'update' => 'si.acceptablecustomer',
        ],
        'feetermby' => [
            'select' => 'IF(feetermby = "feetermbycollect", "Collect", "Prepaid")',
            'display' => 'Fee Term By',
            'searcherDD' => 'shipments\\feeTerms',
            'ddField' => 'displayName',
            'update' => 'si.feetermby',
        ],
        'trailerloadby' => [
            'select' => 'IF(trailerloadby = "trailerloadbyshipper", "By Shipper", "By Driver")',
            'display' => 'Fee Term By',
            'searcherDD' => 'shipments\\trailerLoad',
            'ddField' => 'displayName',
            'update' => 'si.trailerloadby',
        ],
        'trailercountedby' => [
            'select' => 'IF(trailercountedby = "trailercountedbyshipper",
                            "By Shipper",
                            IF(trailercountedby = "trailercountedbydriverpallets",
                                "By Drive/Pallets", "By Drive/Pieces"))',
            'display' => 'Trailer Counted By',
            'searcherDD' => 'shipments\\trailerCounted',
            'ddField' => 'displayName',
            'update' => 'si.trailercountedby',
        ],
    ];

    public $groupBy = 'si.bolLabel, n.scanordernumber';
    public $orderBy = 'si.id DESC, b.id';

    /*
    ****************************************************************************
    */

    function table()
    {
        return 'shipping_info si
                JOIN shipping_orders so ON so.bolID = si.bolLabel
                JOIN neworder n ON so.orderID = n.id
                JOIN inventory_cartons ca ON ca.orderID = n.id
                JOIN inventory_batches b ON b.id = ca.batchID
                JOIN inventory_containers co ON co.recNum = b.recNum
                JOIN vendors v ON v.id = co.vendorID
                JOIN warehouses w ON w.id = v.warehouseID
                LEFT JOIN commodity com ON com.id = si.commodity
                LEFT JOIN company_address cad ON cad.id = n.location';
    }

    /*
    ****************************************************************************
    */

    function getCheckInArray($app, $scans)
    {
        $plates = new plates($app);
        $orders = new orders($app);
        $billOfLadings = $passedBillOfLadings = $passedBillOfLading =
        $bolArray = $bolIDs = [];
        $billOfLadingNumber = $errMsg = $error = NULL;
        $platesValues = $bolArray = $platesAsKeys = [];
        if (count($scans) >= 3) {
            foreach ($scans as $scan) {

                if (! $billOfLadingNumber) {
                    // BOLNumber to open
                    $billOfLadingNumber = $scan;
                    $bolIDs[] = $billOfLadingNumber;
                    continue;
                }


                if ($billOfLadingNumber == $scan) {
                    // BOLNumber to close
                    $bolArray[$billOfLadingNumber] = $platesValues;
                    $platesValues = [];
                    $billOfLadingNumber = NULL;

                    continue;
                }
                $platesValues[] = $scan;
                $platesAsKeys[] = $scan;
            }
        }

        return [
            'returnArray' => $bolArray,
            'bolIDs' => $bolIDs,
            'platesAsKeys' => $platesAsKeys,
        ];

    }

    /*
    ****************************************************************************
    */

    function updateBOLInfo($data)
    {
        foreach ($data as $bolID => $value) {
            $data[$bolID]['bolID'] = $bolID;
        }
        foreach ($data as $bolID => $params) {

            $sql = 'UPDATE shipping_info
                    SET    sealnumber = ?,
                           scac = ?,
                           pronumber = ?,
                           shiptype = ?,
                           trailernumber = ?,
                           trackingNumber = ?
                    WHERE  bolLabel = ?';

            $this->app->runQuery($sql, array_values($params));
        }
    }

    /*
    ****************************************************************************
    */

    function getShipFrom($data)
    {
        $vendorID = getDefault($data['vendorID']);
        $scanOrderNumber = getDefault($data['scanordernumber']);
        $createdOrderDay = getDefault($data['createdOrderDay']);

        $results = FALSE;
        $clause = NULL;

        $orders = new statuses\orders($this->app);

        $statusID = $orders->getStatusID(orders::STATUS_PROCESSING_CHECK_OUT);

        $params = [$statusID, $vendorID];

        if ($createdOrderDay) {
            $clause = $createdOrderDay > 1 ?
                'DATE(dateentered) >= DATE_ADD(NOW(), INTERVAL -' .
                    $createdOrderDay . ' DAY)' :
                'DATE(dateentered) >= DATE(NOW())';
        } else if($scanOrderNumber) {
            $clause .= $scanOrderNumber ? 'scanordernumber = ?' : 1;
            $params[] = $scanOrderNumber;
        }

        if ($clause) {
            $sql =  '
                SELECT     c.id,
                           companyName,
                           address,
                           CONCAT_WS(", ", city, country, state, zip) AS city,
                           phone
                FROM       company_address c
                JOIN       neworder n ON n.location = c.id
                JOIN       order_batches b ON b.id = n.order_batch
                WHERE      n.statusID = ?
                AND        b.vendorID = ?
                AND        ' . $clause;

            $results = $this->app->queryResult($sql, $params);
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getOrdersByBOL($params)
    {
        $plateData = $orderData = [];

        $bolLabels = array_keys($params);

        $qMarks = $this->app->getQMarkString($bolLabels);

        $sql = 'SELECT    l.id,
                          si.bolLabel,
                          n.scanordernumber
                FROM      shipping_info si
                JOIN      shipping_orders so ON so.bolID = si.bolLabel
                JOIN      neworder n ON n.id = so.orderID
                JOIN      inventory_cartons ca ON ca.orderID = n.id
                JOIN      licenseplate l ON l.id = ca.plate
                WHERE     bolLabel IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $bolLabels);

        foreach ($results as $plate => $value) {

            $bolLabel = $value['bolLabel'];
            $orderNumber = $value['scanordernumber'];

            $plateData[$bolLabel][] = $plate;
            $orderData[$bolLabel][$orderNumber] = TRUE;
        }

        foreach ($orderData as &$value) {
            // order numbers may happen multiple times
            $value = array_keys($value);
        }

        return [
            'plates' => $plateData,
            'orders' => $orderData,
        ];
    }

    /*
    ****************************************************************************
    */

    function getBOLLabel()
    {
        $field = 'CONCAT(
                    LPAD(userID, 4, 0),
                    assignNumber
                 )';

        $sql = 'SELECT    '.$field.' AS label
                FROM      billofladings
                ORDER BY  assignNumber DESC
                LIMIT 1';

        $result = $this->app->queryResult($sql);

        $bolLabel = $result['label'];

        return $bolLabel;
    }

    /*
    ****************************************************************************
    */

    function addOrderNumberForBOL($orderIDs, $shipFrom)
    {
        $bolLabel = $this->getBOLLabel();

        if (! $bolLabel && ! $orderIDs) {
            return false;
        }

        $params = array_keys($shipFrom);
        $params[] = $bolLabel;

        $this->app->beginTransaction();

        $sql = 'INSERT INTO shipping_info (shipfromid, bolLabel) VALUES (?, ?)';
        $this->app->runQuery($sql, $params);

        foreach ($orderIDs as $orderNumber => $data) {
            $sql = 'INSERT INTO shipping_orders (bolID, orderID) VALUES (?, ?)';
            $this->app->runQuery($sql, [
                $bolLabel,
                $data['orderID']
            ]);
            $orderNumbers[] = $orderNumber;
        }

        $this->app->commit();

        return [
            'bolLabel' => $bolLabel,
            'orderNumbers' => $orderNumbers,
        ];
    }

    /*
    ****************************************************************************
    */

    function getOrderIDs($scanOrderNumbers)
    {
        $qMarks = $this->app->getQMarkString($scanOrderNumbers);

        $sql = 'SELECT  scanOrderNumber,
                        n.id AS orderID,
                        n.location AS shipFrom,
                        n.shipto,
                        n.shiptoaddress,
                        n.shiptocity,
                        so.id AS shippingID,
                        so.bolID
                FROM    neworder n
                LEFT JOIN shipping_orders so ON so.orderID = n.id
                WHERE   n.scanOrderNumber IN (' . $qMarks . ')
                GROUP BY scanordernumber';

        $result = $this->app->queryResults($sql, $scanOrderNumbers);

        return $result;
    }


    /*
    ****************************************************************************
    */

}