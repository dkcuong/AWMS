<?php

namespace tables\workOrders;

class workOrderHeaders extends \tables\_default
{
    public $ajaxModel = 'workOrders\\workOrderHeaders';

    public $primaryKey = 'wh.wo_id';

    public $fields = [
        'vendor' => [
            'select' => 'CONCAT(wa.shortName, "_", vendorName)',
            'display' => 'CLIENT',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'update' => 'b.vendorID',
        ],
        'workordernumber' => [
            'display' => 'WO NBR',
            'select' => 'wo_num',
            'isNum' => 10,
        ],
        'ordernumber' => [
            'display' => 'SCAN ORD NBR',
            'select' => 'scanordernumber',
            'isNum' => 10,
        ],
        'shipdate' => [
            'display' => 'SHIP DT',
            'select' => 'ship_dt',
            'searcherDate' => TRUE,
        ],
        'requestdate' => [
            'display' => 'RQST DT',
            'select' => 'rqst_dt',
            'searcherDate' => TRUE,
        ],
        'completedate' => [
            'display' => 'COMP DT',
            'select' => 'comp_dt',
            'searcherDate' => TRUE,
        ],
        'userID' => [
            'select' => 'u.username',
            'display' => 'USER',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'update' => 'wh.create_by',
        ],
        'companyName' => [
            'select' => 'companyName',
            'display' => 'LOC',
            'searcherDD' => 'orders\companyAddresses',
            'ddField' => 'companyName',
            'update' => 'n.location',
        ],
        'requestby' => [
            'display' => 'RQST By',
             'select' => 'rqst_by',
            'searcherDate' => TRUE,
        ],
        'relatedtocustomer' => [
            'select' => 'IF(rlt_to_cust, "Yes", "No")',
            'display' => 'RELATED CUST',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'rlt_to_cust',
        ]
    ];


    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'wo_hdr wh
            JOIN      neworder n ON n.scanOrderNumber = wh.scn_ord_num
            JOIN      order_batches b ON b.id = n.order_batch
            JOIN      ' . $userDB . '.info u ON u.id = wh.create_by
            JOIN      vendors v ON v.id = b.vendorID
            JOIN      warehouses wa ON v.warehouseID = wa.id
            JOIN      company_address a ON a.id = n.location
            ';
    }

    /*
    ****************************************************************************
    */

    function getByWorkOrderNumber($workOrderNumbers, $target='wo_id')
    {
        $isArray = is_array($workOrderNumbers);

        if (! $workOrderNumbers) {
            return $isArray ? [] : NULL;
        }

        $params = $isArray ? $workOrderNumbers : [$workOrderNumbers];

        $qMarks = $this->app->getQMarkString($params);

        $sql = 'SELECT    wo_num,
                          ' . $target . '
                FROM      wo_hdr wh
                JOIN      neworder n ON n.scanOrderNumber = wh.scn_ord_num
                WHERE     wo_num IN (' . $qMarks . ')';

        $results = $isArray ? $this->app->queryResults($sql, $params) :
            $this->app->queryResult($sql, $params);

        return $isArray ? $results : $results[$target];
    }

    /*
    ****************************************************************************
    */

    function getByOrderNumber($orderNumbers, $target='wo_num')
    {
        $isArray = is_array($orderNumbers);

        if (! $orderNumbers) {
            return $isArray ? [] : NULL;
        }

        $params = $isArray ? $orderNumbers : [$orderNumbers];

        $qMarks = $this->app->getQMarkString($params);

        $sql = 'SELECT    scn_ord_num,
                          ' . $target . '
                FROM      wo_hdr
                WHERE     scn_ord_num IN (' . $qMarks . ')';

        $results = $isArray ? $this->app->queryResults($sql, $params) :
            $this->app->queryResult($sql, $params);

        return $isArray ? $results : $results[$target];
    }

    /*
    ****************************************************************************
    */

    function getByScanOrderNumbers($scanOrderNumbers)
    {
        if (! $scanOrderNumbers) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($scanOrderNumbers);

        $sql = 'SELECT    scn_ord_num,
                          wo_id,
                          wo_num
                FROM      wo_hdr
                WHERE     scn_ord_num IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $scanOrderNumbers);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getWorkOrderHeader($workOrderNumbers)
    {
        if (! $workOrderNumbers) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($workOrderNumbers);

        $sql = 'SELECT    wo_num,
                          wo_id,
                          scn_ord_num,
                          rqst_dt,
                          comp_dt,
                          client_wo_num,
                          rlt_to_cust,
                          ship_dt,
                          rqst_by,
                          wo_dtl
                FROM      wo_hdr
                WHERE     wo_num IN (' . $qMarks . ')';

        $results = $this->app->queryResults($sql, $workOrderNumbers);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getOrderData($workOrderNumbers)
    {
        if (! $workOrderNumbers) {
            return [];
        }

        $qMarks = $this->app->getQMarkString($workOrderNumbers);

        $userDB = $this->app->getDBName('users');

        $sql = 'SELECT    scanOrderNumber,
                          wo_num AS workOrderNumber,
                          scanOrderNumber,
                          vendorID,
                          CONCAT(wa.shortName, "_", vendorName) AS vendor,
                          u.userName,
                          companyName AS location,
                          startShipDate AS shipDate
                FROM      wo_hdr wh
                JOIN      neworder n ON n.scanOrderNumber = wh.scn_ord_num
                JOIN      order_batches b ON b.id = n.order_batch
                JOIN      ' . $userDB . '.info u ON u.id = wh.create_by
                JOIN      vendors v ON v.id = b.vendorID
                JOIN      warehouses wa ON v.warehouseID = wa.id
                JOIN      company_address a ON a.id = n.location
                WHERE     wo_num IN (' . $qMarks . ')
                AND       wh.sts != "d"';

        $results = $this->app->queryResults($sql, $workOrderNumbers);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function updateHeader($data)
    {
        if (! $data) {
            return FALSE;
        }

        $workOrderNumber = $data['workOrderNumber'];
        $param = [
            $data['scanOrderNumber'],
            $data['requestDate'],
            $data['completeDate'],
            $data['clientWorkOrderNumber'],
            $data['relatedToCustomer'],
            $data['shipDate'],
            $data['requestBy'],
            $data['workOrderDetails'],
            $data['userID'],
        ];

        $sql = 'INSERT INTO wo_hdr (
                    wo_num,
                    scn_ord_num,
                    rqst_dt,
                    comp_dt,
                    client_wo_num,
                    rlt_to_cust,
                    ship_dt,
                    rqst_by,
                    wo_dtl,
                    create_by
                ) VALUES (
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    scn_ord_num = ?,
                    rqst_dt = ?,
                    comp_dt = ?,
                    client_wo_num = ?,
                    rlt_to_cust = ?,
                    ship_dt = ?,
                    rqst_by = ?,
                    wo_dtl = ?,
                    update_by = ?,
                    sts = "u"';

        $params = array_merge([$workOrderNumber], $param, $param);

        $this->app->runQuery($sql, $params);
    }

    /*
    ****************************************************************************
    */

    function getCheckInArray($scans)
    {
        $results = [
            'workOrders' => [],
            'passedWorkOrders' => [],
            'passedOrders' => [],
            'workOrderNumber' => NULL,
            'errors' => [],
        ];

        if (count($scans) % 3 == 0) {
            foreach ($scans as $scan) {

                $results['scan'] = $scan;
                $results['continue'] = FALSE;

                $results = $this->getCheckInScannerInput($results);

                if ($results['continue']) {
                    continue;
                }
            }

            if ($results['workOrderNumber']) {
              $results['errors'][] = 'Sequence Error';
              $results['workOrders'] = [];
            }

            if ($results['errors']) {
                return $results;
            }

            $results['type'] = 'checkIn';
            $results['passedOrders'] = array_keys($results['passedOrders']);
            $results['passedWorkOrders'] =
                    array_keys($results['passedWorkOrders']);

            $results['errors'] = $this->verifyScannerInput($results);

        } else {
            $results['errors'][] = 'Work Order Number quantity does not macth '
                    . 'Order Number quantity';
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getCheckOutArray($scans)
    {
        $results = [
            'workOrders' => [],
            'errors' => [],
        ];

        $results['type'] = 'checkOut';
        $results['passedWorkOrders'] = $results['workOrders'] =
                array_values($scans);

        $results['errors'] = $this->verifyScannerInput($results);
        $results['workOrders'] = array_flip($results['workOrders']);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getCheckInScannerInput($data)
    {
        $workOrderNumber = $data['workOrderNumber'];
        $scan = $data['scan'];

        if (! $workOrderNumber || $workOrderNumber == $scan) {
            // opening workOrderNumber tag || closing workOrderNumber tag
            $data['workOrderNumber'] = $workOrderNumber ? NULL : $data['scan'];

            $data['continue'] = TRUE;

            return $data;
        }

        $errors = [];

        if (isset($data['passedWorkOrders'][$workOrderNumber])) {
            $errors[] = 'Work Order Number ' . $workOrderNumber. ' has been '
                    . 'passed twice. No duplicate values are allowed.';
        } else {
            $data['passedWorkOrders'][$workOrderNumber] = FALSE;
        }

        if (isset($data['passedOrders'][$scan])) {
            $errors[] = 'Order Number ' . $scan . ' has been passed twice. '
                    . 'No duplicate values are allowed.';
        } else {
            $data['passedOrders'][$scan] = FALSE;
        }

        if ($errors) {

            $data['errors'] = array_merge($data['errors'], $errors);
            $data['continue'] = TRUE;

            return $data;
        }

        $data['workOrders'][$workOrderNumber] = $scan;

        return $data;
    }

    /*
    ****************************************************************************
    */

    function verifyScannerInput($data)
    {
        $data['labelsClass'] = new \tables\workOrderLabels($this->app);

        $workOrdersCheckResults = $this->verifyScannerOrders($data);

        $ordersCheckResults = [];

        if (isset($data['passedOrders'])) {
            // Work Order Check-Out scanner does not have orders scanned
            $data['labelsClass'] = new \tables\orderLabels($this->app);

            $ordersCheckResults = $this->verifyScannerOrders($data);
        }

        return array_merge($workOrdersCheckResults, $ordersCheckResults);
    }

    /*
    ****************************************************************************
    */

    function verifyScannerOrders($data)
    {
        $labelsClass = $data['labelsClass'];
        $type = $data['type'];

        $errors = [];

        $isWorkOrders = $labelsClass->ajaxModel == 'workOrderLabels';

        $values = $isWorkOrders ? $data['passedWorkOrders'] :
            $data['passedOrders'];
        $model = $isWorkOrders ? $this : new \tables\orders($this->app);
        $caption = $isWorkOrders ? 'Work Order Number ' : 'Order Number ';

        $result = $labelsClass->valid(
                $values,
                $labelsClass->fields['barcode']['select'],
                'assignNumber'
        );

        $existing = array_column($result['perRow'], 'target');

        $missing = array_diff($values, $existing);

        foreach ($missing as $orderNumber) {
            $errors[] = $caption . $orderNumber . ' does not exist';
        }

        $results = $isWorkOrders ? $model->getByWorkOrderNumber($values, 'sts') :
            $model->getOrderProcessed($values);

        if ($isWorkOrders) {
            $errors = $this->getWorkOrderErrors([
                'results' => $results,
                'type' => $type,
                'values' => $values,
                'caption' => $caption,
                'errors' => $errors,
            ]);
        } else {
            $errors = $this->getScanOrderErrors([
                'results' => $results,
                'workOrders' => $data['workOrders'],
                'caption' => $caption,
                'errors' => $errors,
            ]);
        }

        return array_filter($errors);
    }

    /*
    ****************************************************************************
    */

    function getWorkOrderErrors($data)
    {
        $results = $data['results'];
        $type = $data['type'];
        $values = $data['values'];
        $caption = $data['caption'];
        $errors = $data['errors'];

        if (! $results && $type == 'checkOut') {
            foreach ($values as $orderNumber) {
                $errors[] = $caption . $orderNumber . ' was not created. '
                        . 'Do Work Order Check In first';
            }
        }

        foreach ($results as $orderNumber => $values) {
            if ($type == 'checkIn') {
                $errors[] = $caption . $orderNumber . ' has already been '
                        . 'Checked-In. Use Work Order Check-Out instead';
            } else {
                $errors[] = $values['sts'] == 'd' ? ' been deleted' : NULL;
            }
        }

        return $errors;
    }

    /*
    ****************************************************************************
    */

    function getScanOrderErrors($data)
    {
        $results = $data['results'];
        $caption = $data['caption'];
        $errors = $data['errors'];
        $workOrders = $data['workOrders'];

        $orderNumbers = array_keys($results);

        foreach ($results as $orderNumber => $values) {
            if (! $values['isClosed']) {
                continue;
            }

            $text = $values['shortName'] == \tables\orders::STATUS_CANCELED ?
                    ' been cancelled' : ' already been processed';

            $errors[] = $caption . $orderNumber . ' has ' . $text;
        }

        if (! $orderNumbers) {
            return $errors;
        }

        $reservedOrders = $this->getByOrderNumber($orderNumbers);

        $submittedOrders = array_flip($workOrders);

        foreach ($reservedOrders as $orderNumber => $values) {
            if ($submittedOrders[$orderNumber] != $values['wo_num']) {
                $errors[] = $caption . $orderNumber . ' has already been '
                        . 'reserved for Work Order Number ' . $values['wo_num'];
            }
        }

        return $errors;
    }

    /*
    ****************************************************************************
    */

}
