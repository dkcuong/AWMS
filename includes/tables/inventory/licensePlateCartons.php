<?php

namespace tables\inventory;

use common\logger;
use tables\_default;
use tables\plates;
use common\seldatContainers;

class licensePlateCartons extends _default
{

    public $primaryKey = 'ca.id';

    public $ajaxModel = 'inventory\licensePlateCartons';

    public $fields = [
        'action' => [
            'select' => 'ca.id',
            'display' => 'Select',
            'ignoreSearch' => TRUE
        ],
        'plate'  => [
            'display' => 'Plate',
            'select' => 'ca.plate',
            'noEdit' => TRUE
        ],
        'batch'  => [
            'display' => 'Batch',
            'select' => 'b.id',
            'noEdit' => TRUE
        ],
        'ucc128' => [
            'display' => 'UCC',
            'select' => 'CONCAT(co.vendorID,
                            b.id,
                            LPAD(ca.uom, 3, 0),
                            LPAD(ca.cartonID, 4, 0)
                        )',
            'noEdit' => TRUE
        ],
        'uom' => [
            'display' => 'UOM',
            'select' => 'ca.uom',
            'noEdit' => TRUE
        ],
        'upc' => [
            'select' => 'u.upc',
            'display' => 'UPC',
            'noEdit' => TRUE
        ],
        'sku' => [
            'display' => 'SKU',
            'select' => 'u.sku',
            'noEdit' => TRUE
        ],
        'color' => [
            'display' => 'Color',
            'select' => 'color',
            'noEdit' => TRUE
        ],
        'size' => [
            'display' => 'Size',
            'select' => 'size',
            'noEdit' => TRUE
        ],
        'locationName' => [
            'display' => 'Location',
            'select' => 'l.displayName',
            'noEdit' => TRUE
        ],
        'status' => [
            'display' => 'Status',
            'select' => 's.shortName',
            'noEdit' => TRUE
        ],
    ];

    public $where = 'NOT isSplit
        AND         NOT unSplit
        AND         NOT isMezzanine
        AND         ca.plate is NOT NULL';

    public $table = '	inventory_cartons ca
                    JOIN inventory_batches b ON b.id = ca.batchID
                    JOIN inventory_containers co ON co.recNum = b.recNum
                    JOIN locations l ON l.id = ca.locID
                    JOIN upcs u ON u.id = b.upcID
                    JOIN statuses s ON s.id = ca.statusID';

    public $mainTable = 'inventory_cartons';

    /*
    ****************************************************************************
    */

    function __construct($app = FALSE)
    {
        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */

    public function processUpdateNewUomCartons()
    {
        $result = [
            'sts' => false
        ];

        $newUom = getDefault($this->app->post['newUom']);
        $invIds = getDefault($this->app->post['invIds']);
        $licensePlate = getDefault($this->app->post['plate']);
        $batch = getDefault($this->app->post['batch']);

        $validate = $this->checkValidateInvIdsOfPlate(
            $invIds, $licensePlate, $newUom, $batch);

        if (! $validate['sts']) {
            $result['msg'] = $validate['msg'];
            return $result;
        }

        $data = $validate['data'];

        $result = $this->updateUomCartonOfPlate($data, $newUom, $licensePlate, $batch);

        return $result;
    }

    /*
    ****************************************************************************
    */

    private function checkValidateInvIdsOfPlate(
        $invIds, $licensePlate, $newUom, $batch)
    {
        $result = [
            'sts' => false
        ];
        $minUom = seldatContainers::$tableCells['uom']['minValue'];
        $maxUom = seldatContainers::$tableCells['uom']['maxValue'];

        $error = [];

        if (! $newUom) {
            $error = 'New Uom is not blank!';
        } elseif (! is_numeric($newUom)) {
            $error[] = 'New Uom is Number!';
        } elseif ($newUom < $minUom || $newUom > $maxUom) {
            $error[] = 'New Uom is invalid!';
        }

        if (! $licensePlate) {
            $error[] = 'License plate is not blank!';
        }

        if (! $batch) {
            $error[] = 'Batch is not blank!';
        }

        if (! $invIds) {
            $error[] = 'InvIds is not blank!';
        }

        if ($error) {
            $result['msg'] = implode('<br>', $error);
            return $result;
        }

        $invIdsValid = plates::getInvIdsOnLicensePlate(
            $this->app, $invIds, $licensePlate, $batch);

        $invIdsInvalid = array_diff($invIds, array_keys($invIdsValid));

        if ($invIdsInvalid) {
            $msgError = implode(',', $invIdsInvalid);
            $result['msg'] = 
                $msgError . ' is not depend of Plate : ' . $licensePlate;
        }

        return [
            'sts' => true,
            'data' => $invIdsValid
        ];
    }

    /*
   ****************************************************************************
   */

    private function updateUomCartonOfPlate($data, $newUom, $licensePlate, $batch)
    {
        $result = [
            'sts' => false
        ];

        $oldUom = array_column($data, 'uom');
        $invIds = array_keys($data);

        if (! ($oldUom && $newUom && $licensePlate && $batch)) {
            $result['msg'] = 'Data is invalid!';
            return $result;
        }

        $this->app->beginTransaction();
        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $qMark = $this->app->getQMarkString($invIds);

        $params = [$newUom, $licensePlate, $batch];
        $params = array_merge($params, $invIds);

        $sql = 'UPDATE  inventory_cartons
                SET     uom = ?
                WHERE   plate = ?
                AND     batchID = ?
                AND     id IN( '.$qMark.')';

        $this->app->runQuery($sql, $params);

        logger::edit([
            'db' => $this->app,
            'primeKeys' => $invIds,
            'fields' => [
                'UOM' => [
                    'fromValues' => $oldUom,
                    'toValues' => $newUom,
                ]
            ],
            'transaction' => FALSE,
        ]);

        $this->app->commit();

        return [
            'sts' => true,
            'msg' => 'Update Successful'
        ];
    }

    /*
    ****************************************************************************
    */
}