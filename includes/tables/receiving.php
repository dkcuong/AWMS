<?php

namespace tables;

use tables\inventory\cartons;

class receiving extends \tables\_default
{
    public $primaryKey = 'r.id';

    public $ajaxModel = 'receiving';

    public $fields = [
        'id' => [
            'select' => 'r.id',
            'display' => 'Receiving ID',
            'noEdit' => TRUE
        ],
        'ref' => [
            'select' => 'r.ref',
            'display' => 'Ref #',
            'noEdit' => TRUE
        ],
        'clientName' => [
            'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
            'display' => 'Client',
            'noEdit' => TRUE,
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", v.vendorName)'
        ],
        'name' => [
            'display' => 'Container Name',
            'noEdit' => TRUE
        ],
        'date' => [
            'select' => 'r.shipped_at',
            'searcherDate' => TRUE,
            'display' => 'Shipment Date',
            'noEdit' => TRUE
        ],
        'statuses' => [
            'select' => 's.displayName',
            'display' => 'Status',
            'searcherDD' => 'statuses\receiving',
            'ddField' => 'displayName',
            'update' => 'r.status',
            'updateOverwrite' => TRUE
        ],
        'userName' => [
            'select' => 'CONCAT(u.firstName, " ", u.lastName)',
            'display' => 'Create By',
            'noEdit' => TRUE
        ],
        'created_at' => [
            'select' => 'r.created_at',
            'searcherDate' => TRUE,
            'display' => 'Create At',
            'noEdit' => TRUE
        ],
        'description' => [
            'select' => 'r.note',
            'display' => 'Description',
            'noEdit' => TRUE
        ],
        'action' => [
            'select' => 'r.id',
            'display' => 'Action'
        ]
    ];

    public $fileTypes = [
        'xls',
        'xlsx',
        'csv',
        'pdf',
        'txt',
        'doc',
        'docx',
        'ppt',
        'pptx',
        'jpg',
        'png'
    ];

    public $baseTable = 'receivings r
        LEFT JOIN receiving_containers rc ON r.id = rc.receiving_id
        LEFT JOIN inventory_containers ic ON ic.recNum = rc.container_num';

    public $orderBy = 'r.id DESC';

    public $groupBy = 'r.id';

    public $mainField = 'r.id';

    const INVENTORY_CATEGORY = 'inventory';

    const DELETE_STATUS = 'DEL';

    const CANCEL_STATUS = 'CCL';

    const NEW_STATUS = 'NEW';

    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        $status = new \tables\statuses\receiving($app);
        $clause = $status->getStatusID(self::DELETE_STATUS);
        $this->where = 'r.statusID != ' . $clause;

        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'receivings r
                LEFT JOIN receiving_containers rc ON r.id = rc.receiving_id
                LEFT JOIN inventory_containers ic ON ic.recNum = rc.container_num
                LEFT JOIN vendors v ON r.client_id = v.id
                LEFT JOIN warehouses w ON r.warehouse_id = w.id
                LEFT JOIN statuses s ON s.id = r.statusID
                LEFT JOIN ' . $userDB . '.info u ON u.id = r.created_by';
    }

    /*
    ****************************************************************************
    */

    function getReceivingNumber($data)
    {
        $return = [];
        $term = '%' . $data['term'] . '%';

        if (! $term) {
            return FALSE;
        }

        $statuses = new statuses($this->app);

        $statusID = $statuses->getStatusID(self::NEW_STATUS);

        $sql = 'SELECT  CONCAT_WS("_",
                            r.id,
                            ref,
                            note
                        ) AS primaryKey,
                        r.id,
                        ref,
                        note,
                        CONCAT(w.shortName,
                            "_",
                            v.vendorName
                        ) AS vendorFullName
                FROM    receivings r
                JOIN    vendors v ON v.id = r.client_id
                JOIN    warehouses w ON w.id = v.warehouseID
                WHERE   statusID = ?
                AND     (
                            r.id LIKE ?
                            OR ref LIKE ?
                            OR note LIKE ?
                        )
                LIMIT   10';

        $params = [
            $statusID,
            $term,
            $term,
            $term
        ];

        $results = $this->app->queryResults($sql, $params);

        foreach ($results as $value) {
            $return[] = [
                'receivingID' => $value['id'],
                'ref' => $value['ref'],
                'description' => $value['note'],
                'vendorName' => $value['vendorFullName']
            ];
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getReceivingData($receivingID)
    {
        if (! $receivingID) {
            return [];
        }

        $sql = 'SELECT    r.id,
                          CONCAT(w.shortName, "_", v.vendorName) AS vendorName,
                          client_id,
                          ref,
                          shipped_at,
                          note,
                          statusID
                FROM      receivings r
                JOIN      vendors v ON r.client_id = v.id
                JOIN      warehouses w ON r.warehouse_id = w.id
                WHERE     r.id = ?';

        $result = $this->app->queryResult($sql, [$receivingID]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getClientID($receivingID)
    {
        $sql = 'SELECT  client_id
                FROM    receivings
                WHERE   id = ?';

        $result = $this->app->queryResult($sql, [$receivingID]);

        return $result ? $result['client_id'] : FALSE;
    }

    /*
    ****************************************************************************
    */

    function addNewReceiving($data)
    {
        $statuses = new \tables\statuses\receiving($this->app);
        $warehouseID = getDefault($data['warehouseID']);
        $ref = getDefault($data['ref']);
        $vendorID = getDefault($data['vendorID']);
        $note = getDefault($data['note']);
        $userID = getDefault($data['userID']);
        $statusID = $statuses->getStatusID(\tables\receiving::NEW_STATUS);

        $sql = 'INSERT INTO receivings (
                    warehouse_id,
                    client_id,
                    ref,
                    note,
                    statusID,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?)';

        $this->app->runQuery($sql, [
            $warehouseID,
            $vendorID,
            $ref,
            $note,
            $statusID,
            $userID
        ]);

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    function updateReceiving($receivingID, $status=FALSE)
    {
        $statuses = new statuses($this->app);
        if ($status === FALSE) {
            $status = $statuses->getStatusID(self::DELETE_STATUS);
        }

        $sql = 'UPDATE receivings
                SET    statusID = ?
                WHERE  id = ?';

        $this->app->runQuery($sql, [
            $status,
            $receivingID
        ]);
    }

    /*
    ****************************************************************************
    */

    function checkReceivedContainer($receivingID, $option)
    {
        $status = new statuses\inventory($this->app);
        $statusID = $status->getStatusID(cartons::STATUS_INACTIVE);

        // Get all container
        $sql = 'SELECT  COUNT(DISTINCT ic.recNum) AS containerNum
                FROM    inventory_containers ic
                JOIN    receiving_containers rc ON ic.recNum = rc.container_num
                JOIN    receivings r ON rc.receiving_id = r.id
                JOIN    inventory_batches ib ON ib.recNum = ic.recNum
                JOIN    inventory_cartons ca ON ca.batchID = ib.id
                WHERE   r.id = ?';

        $summary = $this->app->queryResult($sql, [$receivingID]);

        switch ($option) {
            case 'receipt':
                $results = $summary['containerNum'] ? FALSE : TRUE;
                break;
            case 'checkRCLog':
                // Get container RC log
                $sql .= 'AND     ca.statusID <> ?';

                $RCLog = $this->app->queryResult($sql, [
                    $receivingID,
                    $statusID
                ]);

                // Quantity container missing
                $quantity = $summary['containerNum'] - $RCLog['containerNum'];

                if ($quantity) {
                    $results = [
                        'status' => TRUE,
                        'missingContainer' => $quantity,
                    ];
                } else {
                    $results = [
                        'status' => FALSE,
                        'missingContainer' => FALSE
                    ];
                }

                if (! $RCLog['containerNum']){
                    $results['notRCLog'] = TRUE;
                }
                break;
            default: die();
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    function getReceivingContainerRCLog($receivingID)
    {

        $sql = 'SELECT  COUNT(id) AS containerNum
                FROM    receiving_containers
                WHERE   id = ?';

        $result = $this->app->runQuery($sql, [$receivingID]);

        return $result ? $result : FALSE;

    }

    /*
    ****************************************************************************
    */

    function getStatuses($statusID)
    {
        $statuses = new statuses($this->app);
        $statusShortName = $statuses->getStatusName($statusID);

        switch ($statusShortName) {
            case 'NEW':
                $status = ['NEW', 'RCT'];
                break;
            case 'RCT':
                $status = ['RCT', 'FNS'];
                break;
            default:
                $status = [$statusShortName];
                break;
        }

        $qMark = $this->app->getQMarkString($status);

        $sql = 'SELECT  id AS statusID,
                        shortName,
                        displayName
                FROM    statuses
                WHERE   category = "receivings"
                AND     shortName IN (' . $qMark . ')';

        $statusArray = $this->app->queryResults($sql, $status);

        return $statusArray;

    }

    /*
    ****************************************************************************
    */

    function checkReceivingContainer($receivingID)
    {
        $sql = 'SELECT   ic.recNum
                FROM     receivings r
                JOIN     receiving_containers rc ON r.id = rc.receiving_id
                JOIN     inventory_containers ic ON ic.recNum = rc.container_num
                WHERE    r.id = ?
                GROUP BY ic.recNum';

        $result = $this->app->queryResults($sql, [$receivingID]);

        return $result ? TRUE : FALSE;

    }

    /*
    ****************************************************************************
    */

    function uploadAttachFiles($app)
    {
        $error = $data = [];
        $prefix = 'Receiving_' . $app->receivingID . '_';
        $uploadPath = \models\directories::getDir('uploads', 'receiving');

        if (! isset($_FILES['files']['name'])) {
            return [];
        }

        if (! $uploadPath) {
            return $error[] = 'Not found upload directory';
        }

        foreach ($_FILES['files']['name'] as $id => $fileName) {
            $pathInfo = pathinfo($fileName, PATHINFO_EXTENSION);

            if (! in_array($pathInfo, $this->fileTypes)) {
                $error[] = 'Not allow .' . $pathInfo . ' format file';
                return $error;
            } else {
                move_uploaded_file($_FILES['files']['tmp_name'][$id],
                    $uploadPath . '/' . $prefix . $fileName);

                $data[$id]['fileName'] = $fileName;
                $data[$id]['url'] = $data[$id]['url'] = $prefix . $fileName;;
                $data[$id]['userID'] = getDefault($app->post['userID']);
            }
        }

        if ($data) {
            $this->insertFiles($app, $data);
        }

        return $error;
    }

    /*
    ****************************************************************************
    */

    function insertFiles($app, $data)
    {
        $receivingID = $this->getNextID('receivings');
        $maxID = $this->getNextID('files');

        $app->beginTransaction();

        foreach ($data as $rowData) {
            $fileName = getDefault($rowData['fileName']);
            $fileUrl = getDefault($rowData['url']);
            $userID = getDefault($rowData['userID']);

            $sql = 'INSERT INTO files (filename, url, created_at, created_by)
                        VALUES(?, ?, NOW(), ?);
                    INSERT INTO receiving_attachment (file_id, receiving_id)
                        VALUES(?, ?);';

            $app->runQuery($sql, [
                $fileName,
                $fileUrl,
                $userID,
                $maxID,
                $receivingID
            ]);

            $maxID++;
        }

        $app->commit();
    }

    /*
    ****************************************************************************
    */

    function getFileList($receivingID)
    {
        $sql = 'SELECT f.id,
                       f.filename,
                       url
                FROM   receivings r
                JOIN   receiving_attachment ra ON ra.receiving_id = r.id
                JOIN   files f ON f.id = ra.file_id
                WHERE  r.id = ?';

        $result = $this->app->queryResults($sql, [$receivingID]);

        return $result ? $result : FALSE;

    }

    /*
    ****************************************************************************
    */

    function prepareReceivingID(&$data)
    {
        $receivingID = $this->getNextID('receivings');

        foreach ($data as $key => $row) {
            $data[$key]['receivingID'] = $receivingID;
            $receivingID++;
        }

        return $data;

    }

    /*
    ****************************************************************************
    */

    function createMissingContainerReceiving($data)
    {
        $statuses = new statuses($this->app);
        $status = $statuses->getStatusID(self::NEW_STATUS);

        $sqlInsertReceiving = 'INSERT INTO receivings (
                    warehouse_id,
                    client_id,
                    created_by,
                    shipped_at,
                    created_at,
                    statusID
                ) VALUES (?, ?, ?, ? , ?, ?)';

        $sqlInsertRelationship = 'INSERT INTO receiving_containers (
                    receiving_id,
                    container_num,
                    created_at
                ) VALUES (?, ?, ?)';

        $this->app->beginTransaction();

        foreach ($data as $containerNum => $row) {

            // Create receiving for each container
            $params = [
                $row['warehouseID'],
                $row['vendorID'],
                $row['userID'],
                $row['setDate'],
                $row['setDate'],
                $status
            ];
            $this->app->runQuery($sqlInsertReceiving, $params);

            // Add relationship for container and receiving
            $params = [
                $row['receivingID'],
                $containerNum,
                $row['setDate']
            ];
            $this->app->runQuery($sqlInsertRelationship, $params);
        }

        $this->app->commit();

        return count($data);
    }

    /*
    ****************************************************************************
    */

    function checkDataInput($data)
    {
        $result = NULL;
        $ref = $data['ref'];


        if ($ref == "") {
            $result = 'Please input ref #!';
        } else {
            $sql = 'SELECT    id
                    FROM      receivings
                    WHERE     ref = ?';
            $results = $this->app->queryResults($sql, [$ref]);

            $results ? $result = 'Reference ID <b>"'. $ref
                . '"</b> has been already exists!' : FALSE;
        }

        return $result;
    }

    /*
    ****************************************************************************
    */
}