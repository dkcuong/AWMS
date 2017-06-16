<?php
/**
 * Created by PhpStorm.
 * User: Mr Le
 * Date: 5/3/2017
 * Time: 10:01 AM
 */

namespace tables\inventory;

use common\logger;
use tables\_default;

class requestChangeStatus extends _default
{
    public $ajaxModel = 'inventory\\requestChangeStatus';

    protected $emails = [
        'wesley.cooper@seldatinc.com'
    ];

    const STATUS_REQUEST_PENDING = 'P';
    const STATUS_REQUEST_APPROVED = 'A';
    const STATUS_REQUEST_DECLINED = 'D';

    const NAME_STATUS_REQUEST_PENDING = 'Pending';
    const NAME_STATUS_REQUEST_APPROVED = 'Approved';
    const NAME_STATUS_REQUEST_DECLINED = 'Declined';

    public $fields = [
        'req_dtl_id' => [
            'display' => 'Select',
            'ignoreExport' => TRUE,
            'noEdit' => TRUE
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'noEdit' => TRUE,
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'name' => [
            'select' => 'co.name',
            'display' => 'Container',
            'noEdit' => TRUE,
        ],
        'measureID' => [
            'select' => 'm.displayName',
            'display' => 'Measurement System',
            'searcherDD' => 'inventory\\measure',
            'ddField' => 'displayName',
            'noEdit' => TRUE,
        ],
        'setDate' => [
            'select' => 'co.setDate',
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'orderBy' => 'ca.id',
            'noEdit' => TRUE,
        ],
        'batchID' => [
            'display' => 'Batch Number',
            'noEdit' => TRUE,
            'acTable' => 'inventory_cartons',
        ],
        'sku' => [
            'select' => 'p.sku',
            'display' => 'Style Number',
            'noEdit' => TRUE,
            'acTable' => 'upcs p',
        ],
        'uom' => [
            'select' => 'LPAD(UOM, 3, 0)',
            'display' => 'UOM',
            'update' => 'UOM',
            'acTable' => 'inventory_cartons',
        ],
        'prefix' => [
            'display' => 'Prefix',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'suffix' => [
            'select' => 'suffix',
            'display' => 'Suffix',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'height' => [
            'display' => 'Height',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'width' => [
            'display' => 'Width',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'length' => [
            'display' => 'Length',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'weight' => [
            'display' => 'Weight',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'upc' => [
            'display' => 'UPC',
            'noEdit' => TRUE,
            'acTable' => 'upcs p',
        ],
        'size' => [
            'select' => 'size',
            'display' => 'Size',
            'noEdit' => TRUE,
            'acTable' => 'upcs',
        ],
        'color' => [
            'select' => 'color',
            'display' => 'Color',
            'noEdit' => TRUE,
            'acTable' => 'upcs',
        ],
        'cartonID' => [
            'display' => 'Carton ID',
            'noEdit' => TRUE,
            'acTable' => 'inventory_cartons c',
        ],

        'ucc128' => [
            'select' => 'CONCAT(co.vendorID,
                            b.id,
                            LPAD(ca.uom, 3, 0),
                            LPAD(ca.cartonID, 4, 0)
                        )',
            'customClause' => TRUE,
            'display' => 'UCC128',
            'noEdit' => TRUE,
            'acDisabled' => TRUE,
        ],
        'plate' => [
            'display' => 'License Plate',
            'isNum' => 8,
            'allowNull' => TRUE,
            'noEdit' => TRUE,
            'acTable' => 'inventory_cartons c',
        ],
        'locID' => [
            'select' => 'l.displayName',
            'display' => 'Location',
            'update' => 'locID',
            'updateOverwrite' => TRUE,
            'updateTable' => 'locations',
            'updateField' => 'displayName',
            'acTable' => 'locations l',
        ],
        'mLocID' => [
            'select' => 'lm.displayName',
            'display' => 'Manual Location',
            'update' => 'mLocID',
            'updateOverwrite' => TRUE,
            'updateTable' => 'locations',
            'updateField' => 'displayName',
            'acTable' => 'locations lm',
        ],
        'statusID' => [
            'select' => 'os.shortName',
            'display' => 'Status',
            'searcherDD' => 'statuses\\inventory',
            'ddField' => 'shortName',
            'noEdit' => TRUE
        ],
        'mStatusID' => [
            'select' => 'oms.shortName',
            'display' => 'mStatus',
            'searcherDD' => 'statuses\\inventory',
            'ddField' => 'shortName',
            'noEdit' => TRUE
        ],
        'to_sts' => [
            'select' => 'ts.shortName',
            'display' => 'Status To',
            'searcherDD' => 'statuses\\inventory',
            'ddField' => 'shortName',
            'noEdit' => TRUE
        ],
        'to_mSts' => [
            'select' => 'tms.shortName',
            'display' => 'mStatus To',
            'searcherDD' => 'statuses\\inventory',
            'ddField' => 'shortName',
            'noEdit' => TRUE
        ],
        'userID' => [
            'select' => 'CONCAT(u.firstName, " ", u.lastName)',
            'display' => 'Created By',
            'searcherDD' => 'users',
            'ddField' => 'u.username',
            'noEdit' => TRUE,
        ],
        'req_sts' => [
            'select' =>
                'CASE WHEN rd.sts = "' . self::STATUS_REQUEST_PENDING . '"
                       THEN "' . self::NAME_STATUS_REQUEST_PENDING . '"
                      WHEN rd.sts = "' . self::STATUS_REQUEST_APPROVED . '"
                       THEN "' . self::NAME_STATUS_REQUEST_APPROVED . '"
                      ELSE "' . self::NAME_STATUS_REQUEST_DECLINED . '"
                 END',
            'searcherDD' => 'statuses\requestChangeStatus',
            'ddField' => 'displayName',
            'display' => 'Request Status'
        ],
    ];

    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */


    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'ctn_sts_req r
                JOIN      ctn_sts_req_dtl rd ON rd.req_id = r.req_id
                JOIN      inventory_cartons ca ON ca.id = rd.ctn_id
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      vendors v ON v.id = rd.vnd_id
                JOIN      warehouses w ON rd.whs_id = w.id
                JOIN      ' . $userDB . '.info u ON u.id = r.req_by
                LEFT JOIN locations l ON l.id = ca.locID
                LEFT JOIN locations lm ON lm.id = ca.mLocID
                JOIN      statuses os ON rd.from_sts_req = os.id
                JOIN      statuses oms ON rd.from_msts_req = oms.id
                JOIN      statuses ts ON r.to_sts_req = ts.id
                JOIN      statuses tms ON r.to_msts_req = tms.id
                JOIN      upcs p ON p.id = b.upcID
                JOIN      measurement_systems m ON m.id = co.measureID';
    }

    /*
    ****************************************************************************
    */

    function processSendRequest($data)
    {
        $sts = getDefault($data['sts']);
        $mSts = getDefault($data['mSts']);
        $ivtIDs = getDefault($data['ivtIDs']);
        $reqID = $this->getNextID('ctn_sts_req');

        $this->app->beginTransaction();

        // Create request
        $this->createRequest($sts, $mSts);

        // Insert request detail
        $this->insertRequestDetail($reqID, $ivtIDs);

        $this->app->commit();

        // Process send email
        return $this->processSendEmail($reqID);
    }

    /*
    ****************************************************************************
    */

    function createRequest($sts, $mSts)
    {

        $sql = 'INSERT INTO ctn_sts_req (
                    req_date,
                    to_sts_req,
                    to_msts_req,
                    req_by
                ) VALUES (NOW(), ?, ?, ?)';

        $userID = \access::getUserID();

        $this->app->runQuery($sql, [
            $sts,
            $mSts,
            $userID
        ]);
    }

    /*
    ****************************************************************************
    */

    function insertRequestDetail($reqID, $ivtData)
    {

        $sql = 'INSERT INTO ctn_sts_req_dtl (
                    req_id,
                    vnd_id,
                    whs_id,
                    ctn_id,
                    from_sts_req,
                    from_msts_req,
                    sts
                )
                VALUES
                    (?, ?, ?, ?, ?, ?, ?)';

        foreach ($ivtData as $ivtID => $values) {
            $params = [
                $reqID,
                $values['vendorID'],
                $values['warehouseID'],
                $ivtID,
                $values['statusID'],
                $values['mStatusID'],
                self::STATUS_REQUEST_PENDING
            ];

            $this->app->runQuery($sql, $params);
        }
    }

    /*
    ****************************************************************************
    */

    function processSendEmail($reqID)
    {
        $subject = 'New request change inventory cartons status - ' . $reqID;
        $body = 'We have new request change inventory carton status. <br>
            Please click link bellow for more detail:  <a href="'
            . $this->app->jsVars['appURL'] . '#'
            . makeLink('inventory', 'changeStatus') . '/req/' . $reqID . '">
            Request ' . $reqID . '</a>';


        $params = [
            'recipient' => $this->emails,
            'subject' => $subject,
            'body' => $body
        ];

        return \PHPMailer\send::mail($params);
    }

    /*
    ****************************************************************************
    */

    public static function getRequestStatus()
    {
        return [
            self::STATUS_REQUEST_PENDING => self::NAME_STATUS_REQUEST_PENDING,
            self::STATUS_REQUEST_APPROVED => self::NAME_STATUS_REQUEST_APPROVED,
            self::STATUS_REQUEST_DECLINED => self::NAME_STATUS_REQUEST_DECLINED
        ];
    }

    /*
    ****************************************************************************
    */

    function processRequest($type, $reqDtlIDs)
    {
        return $type === 'approve' ? $this->approveRequest($reqDtlIDs)
            : $this->declineRequest($reqDtlIDs);
    }

    /*
    ****************************************************************************
    */

    function approveRequest($reqDtlIDs)
    {
        // Update inventory cartons status
        $results = $this->getCartonInfoByRequestID($reqDtlIDs);

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        $return = $this->updateInventoryStatus($results);

        // Update requests status
        $this->updateRequestDtlStatus($reqDtlIDs, self::STATUS_REQUEST_APPROVED);

        // logging cartons status
        logger::edit([
            'db' => $this->app,
            'primeKeys' => array_keys($results),
            'fields' => [
                'statusID' => [
                    'fromValues' => $return['sts'],
                    'toValues' => $return['fromSts']
                ],
                'mStatusID' => [
                    'fromValues' => $return['mSts'],
                    'toValues' => $return['fromMSts']
                ]
            ],
            'transaction' => FALSE
        ]);

        $this->app->commit();

        return 'Approve Successful!';
    }

    /*
    ****************************************************************************
    */

    function declineRequest($reqDtlIDs)
    {
        $this->updateRequestDtlStatus($reqDtlIDs, self::STATUS_REQUEST_DECLINED);

        return 'Decline Successful!';
    }

    /*
    ****************************************************************************
    */

    function updateRequestDtlStatus($reqDtlIDs, $sts) {
        if (! ($reqDtlIDs)) {
            return false;
        }

        $qMark = $this->app->getQMarkString($reqDtlIDs);

        $sql = 'UPDATE  ctn_sts_req_dtl
                SET     sts = ?,
                        update_by = ?,
                        update_dt = NOW()
                WHERE   req_dtl_id IN (' . $qMark . ')';

        $userID = \access::getUserID();

        $params = array_merge([$sts, $userID], $reqDtlIDs);

        $this->app->runQuery($sql, $params);

    }

    /*
    ****************************************************************************
    */

    function getCartonInfoByRequestID($reqDtlID)
    {
        $reqDtlIDs = is_array($reqDtlID) ? $reqDtlID : [$reqDtlID];

        $qMark = $this->app->getQMarkString($reqDtlIDs);

        $sql = 'SELECT  ctn_id,
                        from_sts_req,
                        from_msts_req,
                        to_sts_req,
                        to_msts_req
                FROM    ctn_sts_req r
                JOIN    ctn_sts_req_dtl rd ON rd.req_id = r.req_id
                WHERE   req_dtl_id IN (' . $qMark . ')';

        $results = $this->app->queryResults($sql, $reqDtlIDs);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function updateInventoryStatus($data)
    {
        $stsArray = $mStsArray = [];

        $sql = 'UPDATE  inventory_cartons
                SET     statusID = ?,
                        mStatusID = ?
                WHERE   id = ?';

        foreach ($data as $ivtID => $values) {
            $stsArray[] = $values['from_sts_req'];
            $mStsArray[] = $values['from_msts_req'];
            $sts = $values['to_sts_req'];
            $mSts = $values['to_msts_req'];

            $this->app->runQuery($sql, [
                $sts,
                $mSts,
                $ivtID
            ]);
        }

        return [
            'sts' => $stsArray,
            'mSts' => $mStsArray,
            'fromSts' => $sts,
            'fromMSts' => $mSts
        ];

    }

    /*
    ****************************************************************************
    */

    function checkScanInput($data)
    {
        $invalids = $ivtData = $ivtIDs = [];
        $sts = getDefault($data['sts']);
        $mSts = getDefault($data['mSts']);
        $uccData = getDefault($data['uccData']);

        foreach ($uccData as $value) {
            $ivtIDs = array_merge($ivtIDs, array_keys($value));
        }

        // Check cartons have request change status in other request
        $result = $this->checkCartonHaveBeenRequest($ivtIDs);

        $results = $this->getCartonInfoByUCC($ivtIDs);

        if ($result) {
            $errors = [];
            foreach ($result as $ivtID => $reqID) {
                if (isset($results[$ivtID])) {
                    $errors[] = 'UCC ' . $results[$ivtID]['ucc128']
                        . ' ready exist on request ' . $reqID['req_id'];
                }
            }

            return [
                'errors' => $errors
            ];
        }

        foreach ($results as $ivtID => $values) {
            if($values['statusID'] == $sts && $values['mStatusID'] == $mSts) {
                $invalids[] = $values['ucc128'];
                continue;
            }

            $ivtData[$ivtID] = $values;
        }

        return [
            'errors' => $invalids ?
                ['UCCs do not need change status: ' . implode(', ', $invalids)]
                : FALSE,
            'data' => [
                'sts' => $sts,
                'mSts' => $mSts,
                'ivtIDs' => $ivtData,
            ]
        ];
    }

    /*
    ****************************************************************************
    */

    function getCartonInfoByUCC($ivtID)
    {
        $ivtIDs = is_array($ivtID) ? $ivtID : [$ivtID];

        $qMark = $this->app->getQMarkString($ivtIDs);

        $sql = 'SELECT  ca.id,
                        statusID,
                        mStatusID,
                        vendorID,
                        warehouseID,
                        CONCAT(
                            co.vendorID,
                            b.id,
                            LPAD(ca.uom, 3, 0),
                            LPAD(ca.cartonID, 4, 0)
                        ) AS ucc128
                FROM    inventory_cartons ca
                JOIN    inventory_batches b ON b.id = ca.batchID
                JOIN    inventory_containers co ON co.recNum = b.recNum
                JOIN    vendors v ON v.id = co.vendorID
                WHERE   ca.id IN (' . $qMark . ')';

        $results = $this->app->queryResults($sql, $ivtIDs);

        return $results;
    }

    /*
    ****************************************************************************
    */

    function checkCartonHaveBeenRequest($ivtIDs)
    {
        $qMark = $this->app->getQMarkString($ivtIDs);

        $sql = 'SELECT  ctn_id,
                        req_id
                FROM    ctn_sts_req_dtl
                WHERE   sts = ?
                AND     ctn_id IN (' . $qMark . ')';

        $params = array_merge([self::STATUS_REQUEST_PENDING], $ivtIDs);

        $results = $this->app->queryResults($sql, $params);

        return $results;
    }

    /*
    ****************************************************************************
    */

}