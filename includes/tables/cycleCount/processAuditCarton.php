<?php
namespace tables\cycleCount;

use tables\_default;
use tables\cycleCount\cycleCount;
use tables\inventory\cartons;
use tables\statuses\inventory;
use common\logger;

class processAuditCarton extends _default
{
    const STATUS_DISCREPANCY_CLONE = 'CL';
    const STATUS_DISCREPANCY_DELETE = 'DL';
    const STATUS_DISCREPANCY_ADJUSTED = 'AJ';
    const STATUS_DISCREPANCY_RECOUNT = 'RC';
    const STATUS_INVENTORY_CARTON_LOCK = 'LK';
    const STATUS_INVENTORY_CARTON_ADJUSTED = 'AJ';

    const STATUS_COUNT_ITEM_NEW = 'NW';
    const STATUS_COUNT_ITEM_OPEN = 'OP';
    const STATUS_COUNT_ITEM_ACCEPTED = 'AC';
    const STATUS_COUNT_ITEM_NOT_APPLICABLE = 'NA';
    const STATUS_COUNT_ITEM_RECOUNT = 'RC';

    const ADD_CARTON_QTY = 1;
    const INVENTORY_CARTON_TABLE = 'inventory_cartons';

    public $statusLockCarton = 0;
    public $statusAjusted = 0;
    public $statusRacked = 0;
    public $cycleCount;
    public $batchIds = [];
    public $msgError = [];
    public $modelCarton;
    public $inputCountItemIDs;
    public $countItem;
    public $nextIDInventoryCartonTable = 0;

    function __construct($app)
    {
        $statuses = new inventory($app);

        $statusIDs = $statuses->getStatusIDs([
            self::STATUS_DISCREPANCY_ADJUSTED,
            self::STATUS_INVENTORY_CARTON_LOCK,
            cartons::STATUS_RACKED,
            cartons::STATUS_DISCREPANCY
        ]);

        $this->statusAjusted =
                $statusIDs[self::STATUS_DISCREPANCY_ADJUSTED]['id'];
        $this->statusLockCarton =
                $statusIDs[self::STATUS_INVENTORY_CARTON_LOCK]['id'];
        $this->statusRacked = $statusIDs[cartons::STATUS_RACKED]['id'];
        $this->statusDiscrepancy =
                $statusIDs[cartons::STATUS_DISCREPANCY]['id'];

        $this->modelCarton = new cartons($app);

        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */

    public function checkInputData()
    {
        if (! $this->inputCountItemIDs) {
            $this->msgError[] = 'Please input count items!';

            return FALSE;
        }

        if (! $this->hasPermission($this->inputCountItemIDs)) {
            $this->msgError[] = 'Permission denies!';

            return FALSE;
        }

        if (! $this->cycleCount) {
            $this->msgError[] = 'Not found the cycle count ID';

            return FALSE;
        }

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    public function hasPermission($countItems)
    {
        $cycleCounts = new cycleCount($this->app);
        $countItem = reset($countItems);
        $userID = \access::getUserID();

        $cycleCount = $this->getCycleCountByCountItemId($countItem);
        $inCycleGroup = $cycleCounts->checkUserInCycleGroup();


        if (! $cycleCount) {
            return FALSE;
        }

        if ($inCycleGroup) {
            $this->cycleCount = $cycleCount;
            return TRUE;
        }

        if ($cycleCount['created_by'] != $userID) {
            return FALSE;
        }

        $this->cycleCount = $cycleCount;

        return TRUE;
    }

    /*
    ****************************************************************************
    */

    public function getCycleCountByCountItemId($id)
    {
        $sql = 'SELECT  cc.cycle_count_id,
                        whs_id,
                        created_by,
                        asgd_id
                FROM    cycle_count cc
                JOIN    count_items ci ON ci.cycle_count_id = cc.cycle_count_id
                WHERE   ci.count_item_id = ?';

        $result = $this->app->queryResult($sql, [$id]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    public function acceptCountItems($countItemIDs)
    {
        $result = [
            'status' => FALSE,
        ];

        $this->inputCountItemIDs = $countItemIDs;

        if (! $this->checkInputData()) {

            $result['msg'] = $this->msgError;

            return $result;
        }

        $cycleCountID = $this->cycleCount['cycle_count_id'];

        $this->cycleCount['countItems'] =
                $this->getDataOfCycleCountID($cycleCountID);

        $this->getDataOfCountItems();

        $this->nextIDInventoryCartonTable =
                $this->getNextID(self::INVENTORY_CARTON_TABLE);

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $this->app->beginTransaction();

        foreach ($this->cycleCount['inputCountItems'] as $countItem) {

            $this->countItem = $countItem;

            $this->acceptCountItem($this->countItem);
        }

        $this->unLockedCartons();

        $this->updateStatusToCountItem($this->inputCountItemIDs,
                self::STATUS_COUNT_ITEM_ACCEPTED);

        $this->updateStatusCycleCount();

        $this->app->commit();

        return [
            'status' => TRUE,
            'msg' => ['Successful'],
        ];
    }

    /*
    ****************************************************************************
    */

    private function getDataOfCycleCountID($cycleCountID)
    {
        $sql = '
            SELECT    cis.count_item_id,
                      cis.count_item_id,
                      cis.cycle_count_id,
                      vnd_id,
                      upc_id,
                      pack_size,
                      sku,
                      size,
                      color,
                      sys_qty,
                      sys_loc,
                      act_qty,
                      act_loc,
                      cis.sts
            FROM      count_items cis
            JOIN      cycle_count cc ON cc.cycle_count_id = cis.cycle_count_id
            WHERE     cc.cycle_count_id = ?';

        $results = $this->app->queryResults($sql, [$cycleCountID]);

        if (! $results) {
            return [];
        }

        $hasItemsResults = $this->getLockedCartonHasCountItems($results);

        $noItems = array_diff_key($results, $hasItemsResults);

        $noItemResults = $noItems ?
                $this->getLockedCartonNoCountItem($noItems) : [];

        return $hasItemsResults + $noItemResults;
    }

    /*
    ****************************************************************************
    */

    private function getDataOfCountItems()
    {
        $upcID = [];
        $inputCountItems = [];
        $cartonClones = [];

        $discrepanciesItems =
            $this->getDicrepanciesCartonByCountItemID($this->inputCountItemIDs);

        foreach ($this->inputCountItemIDs as $countItemID) {
            $countItem = $this->cycleCount['countItems'][$countItemID];

            if (isset($discrepanciesItems[$countItemID])) {
                $discrepancy = $discrepanciesItems[$countItemID];

                $data =
                    $this->filterDataDiscrepancies($discrepancy, $cartonClones);

                $countItem['discrepancy'] = $data;
            }

            if ($this->isUnlockedCartonOfUpc($countItem['upc_id'])) {
                $upcID[] = $countItem['upc_id'];
            }

            $inputCountItems[$countItemID] = $countItem;
        }

        $uniqueCartons = array_unique($cartonClones);

        $invIDs = array_values($uniqueCartons);

        $this->cycleCount['dataClone'] = $this->getCartonClones($invIDs);
        $this->cycleCount['inputCountItems'] = $inputCountItems;
        $this->cycleCount['upcUnLock'] = $upcID;
    }

    /*
    ****************************************************************************
    */

    public function getCartonClones($invIDs)
    {
        $batchIDs = [];

        if (! $invIDs) {
            return [];
        }

        $results = $this->modelCarton->getCartonCloneByID($invIDs);

        if ($results) {

            $clonedBatches = array_column($results, 'batchID');

            $clonedBatchIds = array_unique($clonedBatches);

            $params = array_values($clonedBatchIds);

            $batchIDs = $this->modelCarton->getMaxCartonIDByBatch($params);
        }

        return [
            'cartons' => $results,
            'batchIDs' => $batchIDs
        ];
    }

    /*
    ****************************************************************************
    */

    private function isUnlockedCartonOfUpc($upcID)
    {
        $amount = $this->getAmountUpcOfCycleCountNoComplete($upcID);

        return ! $amount ? TRUE : FALSE;
    }

    /*
    ****************************************************************************
    */

    public function recountCountItems($countItems)
    {
        $result = [
            'status' => FALSE,
        ];

        $this->inputCountItemIDs = $countItems;

        if (! $this->checkInputData()) {
            $result['msg'] = $this->msgError;

            return $result;
        }

        $this->app->beginTransaction();

        $this->updateStatusToCountItem($this->inputCountItemIDs,
                self::STATUS_COUNT_ITEM_RECOUNT);

        $this->updateStatusDiscrepanciesOfCountItem($this->inputCountItemIDs,
            self::STATUS_DISCREPANCY_RECOUNT);

        $this->changeStatusCycleCountByStatus(cycleCount::STATUS_RECOUNT);

        $this->app->commit();

        return [
            'status' => TRUE,
            'msg'    => ['Successfull']
        ];
    }

    /*
    ****************************************************************************
    */

    public function acceptCountItem($countItem)
    {
        if (! $countItem) {
            return;
        }

        if (isset($countItem['discrepancy'])) {
            //process accept count items
            $this->processData($countItem['discrepancy'], $countItem);
        }

        if ($countItem['act_loc'] != $countItem['sys_loc']) {
            $this->updateNewLocationOfCountItem($countItem);
        }
    }

    /*
    ****************************************************************************
    */

    public function updateNewLocationOfCountItem($countItem)
    {
        $invCartons = $countItem['cartonLocked'];

        if (! $invCartons) {
            return;
        }

        $invIDs = array_keys($invCartons);

        $this->updateNewLocationCartons($invIDs, $countItem['act_loc']);

        $mLocIDs = array_column($invCartons, 'mn_loc_id');
        $oldLocIDs = array_fill(0, count($invIDs), $countItem['sys_loc']);

        logger::edit([
            'db' => $this->app,
            'primeKeys' => $invIDs,
            'fields' => [
                'locID' => [
                    'fromValues' => $oldLocIDs,
                    'toValues' => $countItem['act_loc']
                ],
                'mLocID' => [
                    'fromValues' => $mLocIDs,
                    'toValues' => $countItem['act_loc']
                ],
            ],
            'transaction' => FALSE
        ]);
    }

    /*
    ****************************************************************************
    */

    public function updateNewLocationCartons($invIDs, $location)
    {
        $sql = 'UPDATE  inventory_cartons
                SET     locID = ?,
                        mLocID = ?
                WHERE   id IN (' . $this->app->getQMarkString($invIDs) . ')';

        $params = $invIDs;

        array_unshift($params, $location, $location);

        $this->app->runQuery($sql, $params);
    }

    /*
    ****************************************************************************
    */

    public function getDicrepanciesCartonByCountItemID($countItemID)
    {
        $result = [];
        $params = is_array($countItemID) ? $countItemID : [$countItemID];
        $qMark = $this->app->getQMarkString($params);

        $sql = 'SELECT  dicpy_ctn_id,
                        dicpy_ctn_id,
                        count_item_id,
                        invt_ctn_id,
                        dicpy_qty,
                        sts
                FROM    discrepancy_cartons
                WHERE   count_item_id IN (' . $qMark . ')
                AND     sts != ?';

        $params[] = self::STATUS_DISCREPANCY_RECOUNT;

        $data = $this->app->queryResults($sql, $params);

        if (! $data) {
            return [];
        }

        foreach ($data as $discrepancy) {
            $itemID = $discrepancy['count_item_id'];
            $result[$itemID][] = $discrepancy;
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    public function cloneNewCartonByCarton($carton, $qtyCarton, $uom)
    {
        $countItemID = $this->countItem['count_item_id'];

        $newCartonIDs = $this->processCloneCarton([
            'carton' => $carton,
            'qtyCarton' => $qtyCarton,
            'uom' => $uom,
            'locationID' => $this->countItem['act_loc']
        ]);

        //insert New carton into lock_cartons table
        $this->insertLockedCartonForNews($newCartonIDs);

        //add array locked carton of count item
        $this->addCartonLockedIntoCountItem($countItemID, $newCartonIDs);

        $this->insertNewCartonToDiscrepancyTable($countItemID, $newCartonIDs);

        return $qtyCarton;
    }

    /*
    ****************************************************************************
    */

    private function addCartonLockedIntoCountItem($countItemID, $inventory)
    {
        $invIDs = is_array($inventory) ? $inventory : [$inventory];

        $cartonMerge = [];

        foreach ($invIDs as $invID){
            $cartonMerge[$invID] = [
                'count_item_id' => $countItemID,
                'mStatusID' => $this->statusRacked,
                'sts' => $this->statusRacked
            ];
        }

        $this->cycleCount['countItems'][$countItemID]['cartonLocked'] +=
                $cartonMerge;
    }

    /*
    ****************************************************************************
    */

    public function insertLockedCartonForNews($inventory)
    {
        $invIDs = is_array($inventory) ? $inventory : [$inventory];

        $sql = 'INSERT INTO locked_cartons (
                        whs_id,
                        vnd_id,
                        invt_ctn_id,
                        count_item_id,
                        sts
                    ) VALUES (
                        ?, ?, ?, ?, ?
                    )';

        foreach ($invIDs as $invID) {
            $this->app->runQuery($sql, [
                $this->cycleCount['whs_id'],
                $this->countItem['vnd_id'],
                $invID,
                $this->countItem['count_item_id'],
                $this->statusRacked,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    private function processData($data, $countItem)
    {
        if (isset($data[self::STATUS_DISCREPANCY_DELETE])) {
            $this->processDeleteCartonForCountItem(
                $data[self::STATUS_DISCREPANCY_DELETE]);
        }

        if (isset($data[self::STATUS_DISCREPANCY_CLONE])) {
            $this->processCloneCartonForCountItem(
                $data[self::STATUS_DISCREPANCY_CLONE], $countItem);
        }

        if (isset($data[self::STATUS_DISCREPANCY_ADJUSTED])) {
            $this->processAdjustCartonForCountItem(
                $data[self::STATUS_DISCREPANCY_ADJUSTED]);
        }
    }

    /*
    ****************************************************************************
    */

    public function filterDataDiscrepancies($discrepancies, &$cartonClones)
    {
        $result = [];

        if (! $discrepancies) {
            return $result;
        }

        foreach ($discrepancies as $discrepancyID => $discrepancy) {

            $discrepancy['discrepancyID'] = $discrepancyID;
            $statusID = $discrepancy['sts'];

            $result[$statusID][] = $discrepancy;

            if ($discrepancy['sts'] != self::STATUS_DISCREPANCY_DELETE) {
                $cartonClones[] = $discrepancy['invt_ctn_id'];
            }
        }
        return $result;
    }

    /*
    ****************************************************************************
    */

    public function processAdjustCartonForCountItem($discrepancies)
    {
        $cartonsClone = $this->cycleCount['dataClone']['cartons'];

        foreach ($discrepancies as $discrepancy){
            $invtID = $discrepancy['invt_ctn_id'];
            $carton = $cartonsClone[$invtID];

            if (! $carton) {
                continue;
            }

            $this->processAjustCarton($discrepancy, $carton);
        }
    }

    /*
    ****************************************************************************
    */

    public function processCloneCartonForCountItem($discrepancies, $countItem)
    {
        $discrepancy = reset($discrepancies);

        $firstCartonID = $discrepancy['invt_ctn_id'];

        $invID = $this->cycleCount['dataClone']['cartons'][$firstCartonID];

        foreach ($discrepancies as $discrepancy) {
            $this->cloneNewCartonByCarton($invID, $discrepancy['dicpy_qty'],
                    $countItem['pack_size']);
        }
    }

    /*
    ****************************************************************************
    */

    private function processDeleteCartonForCountItem($discrepancies)
    {
        $arrCartonIDs = array_column($discrepancies, 'invt_ctn_id');

        $this->updateStatusForCarton($arrCartonIDs, $this->statusAjusted);

        // Remove invtIDs have been deleted out off this count item
        foreach ($this->cycleCount['countItems'] as &$data) {
            foreach (array_keys($data['cartonLocked']) as $ivtID) {
                if (in_array($ivtID, $arrCartonIDs)) {
                    unset($data['cartonLocked'][$ivtID]);
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    private function processCloneCarton($params)
    {
        $carton = $params['carton'];
        $qtyCarton = $params['qtyCarton'];
        $uom = $params['uom'];
        $locationID = $params['locationID'];
        $batchID = $carton['batchID'];
        $batchIDs = &$this->cycleCount['dataClone']['batchIDs'];
        $cartonID = $batchIDs[$batchID]['nextCartonID'];
        $created = date('Y-m-d H:i:s');
        $newCartonIDs = [];

        $sql = 'INSERT INTO inventory_cartons (
                   batchID,
                   cartonID,
                   uom,
                   plate,
                   locID,
                   mLocID,
                   statusID,
                   mStatusID,
                   created_at
                ) VALUES (
                    ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?
                )';

        for ($i = 0; $i < $qtyCarton; $i++) {

            $newCartonIDs[] = $this->nextIDInventoryCartonTable++;

            $this->app->runQuery($sql, [
                $carton['batchID'],
                $cartonID++,
                $uom,
                $carton['plate'],
                $locationID,
                $locationID,
                $this->statusLockCarton,
                $this->statusRacked,
                $created,
            ]);
        }

        $this->addLogForCartonCloned($this->app, $newCartonIDs, $carton['plate'],
                $locationID);

        $batchIDs[$batchID]['nextCartonID'] = $cartonID;

        return $newCartonIDs;
    }

    /*
    ****************************************************************************
    */

    private function addLogForCartonCloned($app, $invIDs, $plate, $locID)
    {
        $statusIDs = $mStatusIDs = $plates = $locIDs = $mLocIDs =
                array_fill(0, count($invIDs), 0);

        //add log
        logger::edit([
            'db' => $app,
            'primeKeys' => $invIDs,
            'fields' => [
                'plate' => [
                    'fromValues' => $plates,
                    'toValues' => $plate
                ],
                'locID' => [
                    'fromValues' => $locIDs,
                    'toValues' => $locID
                ],
                'mLocID' => [
                    'fromValues' => $mLocIDs,
                    'toValues' => $locID
                ],
                'statusID' => [
                    'fromValues' => $statusIDs,
                    'toValues' => $this->statusLockCarton
                ],
                'mStatusID' => [
                    'fromValues' => $mStatusIDs,
                    'toValues' => $this->statusRacked
                ]
            ],
            'transaction' => FALSE
        ]);
    }

    /*
    ****************************************************************************
    */

    public function processAjustCarton($discrepancyCarton, $carton)
    {
        if (! $discrepancyCarton) {
            return FALSE;
        }

        $qty = $discrepancyCarton['dicpy_qty'];

        if ($discrepancyCarton['dicpy_qty'] < 0) {

            $this->updateStatusForCarton($carton['id'], $this->statusAjusted);

            $qty = $discrepancyCarton['dicpy_qty'] + $carton['uom'];
        }

        $this->cloneNewCartonByCarton($carton, self::ADD_CARTON_QTY, $qty);
    }

    /*
    ****************************************************************************
    */

    public function updateStatusForCarton($inventory, $statusID)
    {
        $invIDs = is_array($inventory) ? $inventory : [$inventory];

        $sql = 'UPDATE  inventory_cartons
                SET     statusID = ?
                WHERE   statusID != ?
                AND     id IN (' . $this->app->getQMarkString($invIDs) . ')';

        $params = $invIDs;

        array_unshift($params, $statusID, $this->statusAjusted);

        $this->app->runQuery($sql, $params);

        $statusLocks = array_fill(0, count($invIDs), $this->statusLockCarton);
        //add log
        logger::edit([
            'db' => $this->app,
            'primeKeys' => $invIDs,
            'fields' => [
                'statusID' => [
                    'fromValues' => $statusLocks,
                    'toValues' => $statusID
                ]
            ],
            'transaction' => FALSE
        ]);
    }

    /*
    ****************************************************************************
    */

    public function updateStatusForCartonDS($inventory, $statusID)
    {
        if (! $inventory) {
            return;
        }

        $invIDs = array_keys($inventory);

        $qMarks = $this->app->getQMarkString($invIDs);
        $mStatusIDs = array_column($inventory, 'mn_sts_id');

        $sql = 'UPDATE  inventory_cartons
                SET     statusID = ?,
                        mStatusID = ?
                WHERE   statusID != ?
                AND     id IN (' . $qMarks . ')';

        $params = $invIDs;

        array_unshift($params, $statusID, $statusID, $this->statusAjusted);

        $this->app->runQuery($sql, $params);

        $statusLocks = array_fill(0, count($invIDs), $this->statusLockCarton);

        //add log
        logger::edit([
            'db' => $this->app,
            'primeKeys' => $invIDs,
            'fields' => [
                'statusID' => [
                    'fromValues' => $statusLocks,
                    'toValues' => $statusID
                ],
                'mStatusID' => [
                    'fromValues' => $mStatusIDs,
                    'toValues' => $statusID
                ]
            ],
            'transaction' => FALSE
        ]);
    }

    /*
    ****************************************************************************
    */

    private function getLockedCartonNoCountItem($countItems)
    {
        $clauses = $params = $accepted = [];

        foreach ($countItems as $countItemID => $countItem) {

            $claus = 'upcID = ?
                AND       vendorID = ?
                AND       statusID = ?
                ';

            $params[] = $countItem['upc_id'];
            $params[] = $countItem['vnd_id'];
            $params[] = $this->statusLockCarton;

            if ($countItem['sts'] != self::STATUS_COUNT_ITEM_ACCEPTED) {

                $accepted[$countItemID] = TRUE;

                $claus .= '
                    AND       locID = ?
                    AND       uom = ?
                    ';

                $params[] = $countItem['sys_loc'];
                $params[] = $countItem['pack_size'];
            }

            $clauses[] = $claus;
        }

        $sql = 'SELECT    ca.id,
                          upcID,
                          vendorID,
                          locID,
                          mLocID AS mn_loc_id,
                          mStatusID AS mn_sts_id,
                          la.sts,
                          uom
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      locked_cartons la ON la.invt_ctn_id = ca.id
                WHERE     ' . implode(' OR ', $clauses);

        $results = $this->app->queryResults($sql, $params);

        foreach ($countItems as $countItemID => &$countItem) {
            foreach ($results as $invID => $cartonData) {
                // including check for location and uom
                $acceptedClause = ! isset($accepted[$countItemID]) ||
                        $countItem['sys_loc'] == $cartonData['locID'] &&
                        $countItem['pack_size'] == $cartonData['uom'];

                if ($countItem['upc_id'] == $cartonData['upcID'] &&
                    $countItem['vnd_id'] == $cartonData['vendorID'] &&
                    $acceptedClause) {

                    $countItem['cartonLocked'][$invID] = $cartonData;
                }
            }

            $countItem['cartonLocked'] =
                    getDefault($countItem['cartonLocked'], []);
        }

        return $countItems;
    }

    /*
    ****************************************************************************
    */

    private function getLockedCartonHasCountItems($countItems)
    {
        $countItemIDs = array_keys($countItems);

        $qMarks = $this->app->getQMarkString($countItemIDs);

        $sql = 'SELECT  invt_ctn_id,
                        count_item_id,
                        sts,
                        mn_sts_id,
                        mn_loc_id
                FROM    locked_cartons la
                WHERE   count_item_id IN (' . $qMarks . ')';

        $result = $this->app->queryResults($sql, $countItemIDs);

        foreach ($result as $invID => $values) {

            $countItemID = $values['count_item_id'];

            $countItems[$countItemID]['cartonLocked'][$invID] = $values;
        }

        $countItemKeys = array_keys($countItems);

        foreach ($countItemKeys as $countItemID) {
            if (! isset($countItems[$countItemID]['cartonLocked'])) {
                unset($countItems[$countItemID]);
            }
        }

        return $countItems;
    }

    /*
    ****************************************************************************
    */

    public function insertNewCartonToDiscrepancyTable($countItemID, $cartonID)
    {
        $cartonIDs = is_array($cartonID) ? $cartonID : [$cartonID];

        $sql = 'INSERT INTO discrepancy_cartons (
                    count_item_id,
                    invt_ctn_id,
                    dicpy_qty,
                    sts,
                    created_at,
                    updated_at
                ) VALUES (
                    ?, ?,
                    "' . self::ADD_CARTON_QTY . '",
                    "' . self::STATUS_INVENTORY_CARTON_ADJUSTED . '",
                    NOW(), NOW()
                )';

        foreach ($cartonIDs as $cartonID) {
            $this->app->runQuery($sql, [
                $countItemID,
                $cartonID,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    public function unLockedCartons()
    {
        $data = $this->getCartonLockedCanUnlock();

        if (! $data) {
            return FALSE;
        }

        $invIDs = array_keys($data);

        $this->deleteLockedCarton($invIDs);

        $cartonsLocked = $this->filterCartonLockedByStatus($data);

        if (isset($cartonsLocked[$this->statusRacked])) {
            $invIDsStatusRacked =
                    array_keys($cartonsLocked[$this->statusRacked]);

            $this->updateStatusForCarton($invIDsStatusRacked,
                    $this->statusRacked);
        }

        if (isset($cartonsLocked[$this->statusDiscrepancy])) {
            $this->updateStatusForCartonDS(
                $cartonsLocked[$this->statusDiscrepancy], $this->statusRacked);
        }
    }

    /*
    ****************************************************************************
    */

    function filterCartonLockedByStatus($data)
    {
        $result = [];

        foreach ($data as $invID => $value) {
            $statusID = $value['sts'];
            $result[$statusID][$invID] = $value;
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    public function deleteLockedCarton($invIDs)
    {
        $qMark = $this->app->getQMarkString($invIDs);

        $sql = 'DELETE
                FROM      locked_cartons
                WHERE     invt_ctn_id IN (' . $qMark . ')';

        $this->app->runQuery($sql, $invIDs);
    }

    /*
    ****************************************************************************
    */

    private function getCartonLockedCanUnlock()
    {
        if (! $this->cycleCount['upcUnLock']) {
            return FALSE;
        }

        $result = [];

        $upcIDs = $this->cycleCount['upcUnLock'];
        $countItems = $this->cycleCount['countItems'];

        foreach ($upcIDs as $upcID) {
            foreach ($countItems as $countItem) {
                if ($upcID != $countItem['upc_id']) {
                    continue;
                }

                $result = $result + $countItem['cartonLocked'];
            }
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    private function getAmountUpcOfCycleCountNoComplete($upcID)
    {
        $qMarks = $this->app->getQMarkString($this->inputCountItemIDs);

        $sql = 'SELECT  COUNT(count_item_id) AS amount
                FROM    count_items
                WHERE   sts IN (?, ?, ?)
                AND     upc_id = ?
                AND     cycle_count_id = ?
                AND     count_item_id NOT IN (' . $qMarks . ')';

        $params = [
            self::STATUS_COUNT_ITEM_NOT_APPLICABLE,
            self::STATUS_COUNT_ITEM_RECOUNT,
            self::STATUS_COUNT_ITEM_OPEN,
            $upcID,
            $this->cycleCount['cycle_count_id']
        ];

        $params = array_merge($params, $this->inputCountItemIDs);

        $result = $this->app->queryResult($sql, $params);

        return $result['amount'];
    }

    /*
    ****************************************************************************
    */

    private function updateStatusToCountItem($countItemIDs, $status)
    {
        if (! $countItemIDs) {
            return;
        }

        $params = is_array($countItemIDs) ? $countItemIDs : [$countItemIDs];

        $qMarks = $this->app->getQMarkString($params);

        $sql = 'UPDATE    count_items
                SET       sts = ?
                WHERE     count_item_id IN (' . $qMarks . ')';

        array_unshift($params, $status);

        $this->app->runQuery($sql, $params);
    }

    /*
    ****************************************************************************
    */

    public function changeStatusCycleCountByStatus($status)
    {
        $prefix = 'UPDATE    cycle_count
                   SET       sts = ?';

        if ($status == cycleCount::STATUS_COMPLETE) {
            $prefix .= ', completed_dt = NOW()';
        }

        $sql = $prefix . ' WHERE cycle_count_id = ?';

        $result = $this->app->runQuery($sql, [
            $status,
            $this->cycleCount['cycle_count_id']
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    public function getStatusCycleByCountItems()
    {
       $countItems = $this->cycleCount['countItems'];

        foreach ($countItems as $countItemId => $countItem) {
            if (in_array($countItemId, $this->inputCountItemIDs)) {
                continue;
            }

            $status = $countItem['sts'];

            if ($status == self::STATUS_COUNT_ITEM_NEW) {
                return cycleCount::STATUS_ASSIGNED;
            } elseif ($status == self::STATUS_COUNT_ITEM_RECOUNT) {
                return cycleCount::STATUS_RECOUNT;
            } elseif ($status == self::STATUS_COUNT_ITEM_NOT_APPLICABLE
                   || $status == self::STATUS_COUNT_ITEM_OPEN) {

                return cycleCount::STATUS_CYCLE;
            }
        }

        return cycleCount::STATUS_COMPLETE;
    }

    /*
    ****************************************************************************
    */

    public function updateStatusCycleCount()
    {
        $statusCycle = $this->getStatusCycleByCountItems();

        $this->changeStatusCycleCountByStatus($statusCycle);
    }

    /*
    ****************************************************************************
    */

    private function updateStatusDiscrepanciesOfCountItem($countItemID, $status)
    {
        if (! $countItemID) {
            return;
        }

        $params = is_array($countItemID) ? $countItemID : [$countItemID];

        $qMark = $this->app->getQMarkString($params);

        $sql = 'UPDATE    discrepancy_cartons
                SET       sts = ?
                WHERE     count_item_id IN (' . $qMark . ')';

        array_unshift($params, $status);

        $this->app->runQuery($sql, $params);
    }

    /*
    ****************************************************************************
    */

    public function restoreInventoryStatus($inventory)
    {
        $statuses = new inventory($this->app);
        $rackedID = $statuses->getStatusID(cartons::STATUS_RACKED);
        $lockedID = $statuses->getStatusID(cartons::STATUS_LOCKED);

        $invIDs = array_keys($inventory);
        $statusIDs = array_pad([], count($inventory), $lockedID);

        $sql = 'UPDATE  inventory_cartons
                SET     statusID = ?,
                        mStatusID = ?
                WHERE   id = ?';

        foreach ($inventory as $invID => $row) {
            $this->app->runQuery($sql, [
                $row['sts'],
                $row['mn_sts_id'],
                $invID
            ]);
        }

        // logging cartons status
        logger::edit([
            'db' => $this->app,
            'primeKeys' => $invIDs,
            'fields' => [
                'statusID' => [
                    'fromValues' => $statusIDs,
                    'toValues' => $rackedID
                ]
            ],
            'transaction' => FALSE
        ]);

        return TRUE;
    }

    /*
    ****************************************************************************
    */

}