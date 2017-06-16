<?php
namespace tables\cycleCount;

use tables\_default;
use tables\cycleCount\cycleCount;
use tables\inventory\cartons;
use tables\statuses\inventory;


class processDiscrepancyByCountItem extends _default
{
    public $byUomCarton = 'carton';
    public $statusLockCarton = 0;
    public $statusInactive = 0;
    public $cycleCount;

    const STATUS_CLONE = 'CL';
    const STATUS_DELETE = 'DL';
    const STATUS_ADJUSTED = 'AJ';
    const DISCY_QTY_CLONE = 1;
    const DISCY_QTY_DELETE = -1;

    /*
    ****************************************************************************
    */

    function __construct($app)
    {
        $statuses = new inventory($app);

        $this->statusLockCarton = $statuses->getStatusID(
                processAuditCarton::STATUS_INVENTORY_CARTON_LOCK);
        $this->statusInactive =
                $statuses->getStatusID(cartons::STATUS_INACTIVE);

        parent::__construct($app);
    }

    /*
    ****************************************************************************
    */

    public function processSaveCycleItemData($data)
    {
        $invalidCountItemMsg = '';
        $cycleID = getDefault($data['cycleID']);
        $cycle = new cycleCount($this->app);

        $this->cycleCount = $cycle->getCycleCountInfoById($cycleID);
        $cycleBy = trim(strtolower($this->cycleCount['cycle_count_by_uom']));

        $countItemDiscres = $this->getCountItemByCycleID($cycleID, TRUE);
        $countItemNoDiscres = $this->getCountItemByCycleID($cycleID);

        $processModel = $cycleBy == $this->byUomCarton ?
            new processDiscrepancyCarton($this->app) :
            new processDiscrepanciesEach($this->app);

        $discrepancies = $processModel->getDiscrepancyData($countItemDiscres);

        $this->app->beginTransaction();

        $this->processCountItemNoDiscrepancy($countItemNoDiscres);
        $invalidCountItem = $this->processCountItemDiscrepancy($processModel,
            $countItemDiscres, $discrepancies);

        if ($invalidCountItem) {
            $this->updateStatusCountItems($invalidCountItem,
                cycleCountDetail::STATUS_DELETED);
            $invalidCountItemMsg .= 'Count items: '
                . implode(', ', $invalidCountItem)
                . ' not have data. Will update to Deleted.';
        }

        if ($this->cycleCount['sts'] == cycleCount::STATUS_ASSIGNED) {
            $this->setDefaultActValueCountItem($cycleID);
        }

        $cycle->updateCycleCountStatus($cycleID, cycleCount::STATUS_CYCLE);

        $this->app->commit();

        return [
            'cycleCountID' => cycleCount::STATUS_CYCLE,
            'invalidCountItem' => $invalidCountItemMsg
        ];
    }

    /*
    ****************************************************************************
    */

    public function processCountItemDiscrepancy(
        $model, $countItemDiscres, $discrepancies
    ) {
        $invalidCountItem = [];
        if (! $countItemDiscres) {
            return FALSE;
        }

        foreach ($discrepancies as $countItemID => $row) {
            if (! $row['cartonID']) {
                $invalidCountItem[] = $countItemID;
                unset($discrepancies[$countItemID]);
            }
        }
        
        if ($discrepancies) {
            $model->insertDiscrepancies($discrepancies);
        }

        $countItemIDs = array_keys($countItemDiscres);

        $this->updateStatusCountItems(
            $countItemIDs,
            processAuditCarton::STATUS_COUNT_ITEM_OPEN
        );

        return $invalidCountItem;
    }

    /*
    ****************************************************************************
    */

    public function processCountItemNoDiscrepancy($countItemNoDiscres)
    {
        if (! $countItemNoDiscres) {
            return;
        }

        $countItemIds = array_keys($countItemNoDiscres);

        $this->updateStatusCountItems(
                $countItemIds,
                processAuditCarton::STATUS_COUNT_ITEM_NOT_APPLICABLE
        );
    }

    /*
    ****************************************************************************
    */

    public function getCountItemByCycleID($cycleID, $discrepancy=FALSE)
    {
        $hasDiscre = $discrepancy ? 'NOT' : NULL;

        $sql = 'SELECT  count_item_id,
                        cycle_count_id,
                        vnd_id,
                        pcs,
                        sku,
                        sys_qty,
                        sys_loc,
                        act_qty,
                        act_loc,
                        upc_id,
                        pack_size
                FROM    count_items
                WHERE   cycle_count_id = ?
                AND     sts IN (?, ?)
                AND     ' . $hasDiscre . ' (
                            (sys_qty = act_qty  AND sys_loc = act_loc)
                            OR (act_loc IS NULL AND act_qty IS NULL)
                            OR (act_loc IS NULL AND act_qty = sys_qty)
                            OR (act_qty IS NULL AND act_loc = sys_loc)
                        )';

        $results = $this->app->queryResults($sql, [
            $cycleID,
            processAuditCarton::STATUS_COUNT_ITEM_NEW,
            processAuditCarton::STATUS_COUNT_ITEM_RECOUNT
        ]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    public function setDefaultActValueCountItem($cycleID)
    {
        $sql = 'UPDATE  count_items
                SET     act_loc =  IF (act_loc IS NULL, sys_loc, act_loc),
                        act_qty = IF (act_qty IS NULL, sys_qty, act_qty)
                WHERE   cycle_count_id = ?';

        $this->app->runQuery($sql, [$cycleID]);
    }

    /*
    ****************************************************************************
    */

    public function updateStatusCountItems($countItemIds, $status)
    {
        $qMarks = $this->app->getQMarkString($countItemIds);

        $sql = 'UPDATE  count_items
                SET     sts = ?
                WHERE   count_item_id IN (' . $qMarks . ')';

        $params = $countItemIds;

        array_unshift($params, $status);

        $this->app->runQuery($sql, $params);
    }

    /*
    ****************************************************************************
    */

    public function getCartonByUPC($countItem)
    {
        $sql = 'SELECT  ic.id
                FROM    inventory_cartons ic
                JOIN    inventory_batches ib ON ib.id = ic.batchID
                JOIN    inventory_containers ico ON ico.recNum = ib.recNum
                WHERE   ib.upcID = ?
                AND     ico.vendorID = ?
                AND     ic.statusID != ?';

        $params = [
            $countItem['upc_id'],
            $countItem['vnd_id'],
            $this->statusInactive
        ];

        $result = $this->app->queryResult($sql, $params);

        return $result ? array_values($result) : [];
    }

    /*
    ****************************************************************************
    */

    public function getCartonFromLockedCarton($countItem, $limit=NULL)
    {
        $result = $this->getCartonLockExistCountItem($countItem, $limit);

        if (! $result) {
            $result = $this->getCartonLockNonExistCountItem($countItem, $limit);
        }

        return array_keys($result);
    }

    /*
    ****************************************************************************
    */

    public function getCartonLockExistCountItem($countItem, $limit)
    {
        // Inventory cartons have 2 status RK/RK & DS/any in locked_cartons,
        // sort sts and get RK/RK with greater priority

        $statuses = new inventory($this->app);

        $statusIDs = $statuses->getStatusIDs([
            cartons::STATUS_RACKED,
            cartons::STATUS_DISCREPANCY
        ]);

        $rackID = $statusIDs[cartons::STATUS_RACKED]['id'];
        $discrepancyID = $statusIDs[cartons::STATUS_DISCREPANCY]['id'];

        $orderBy = $rackID < $discrepancyID ? 'ASC' : 'DESC';

        $sql = 'SELECT   invt_ctn_id
                FROM     locked_cartons
                WHERE    count_item_id = ?
                ORDER BY sts ' . $orderBy;

        $sql .= $limit ? ' LIMIT ' . $limit : NULL;

        $result = $this->app->queryResults($sql, [
            $countItem['count_item_id']
        ]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    public function getCartonLockNonExistCountItem($countItem, $limit)
    {
        $sql = 'SELECT  lc.invt_ctn_id
                FROM    inventory_cartons ic
                JOIN    inventory_batches ib ON ib.id = ic.batchID
                JOIN    locked_cartons lc ON lc.invt_ctn_id = ic.id
                WHERE   ib.upcID = ?
                AND     ic.uom = ?
                AND     lc.vnd_id = ?
                AND     ic.locID = ?
                AND     NOT ic.isSplit
                AND     NOT ic.unSplit
                AND     ic.statusID = ?';

        $sql .= $limit ? ' LIMIT ' . $limit : NULL;

        $params = [
            $countItem['upc_id'],
            $countItem['pack_size'],
            $countItem['vnd_id'],
            $countItem['sys_loc'],
            $this->statusLockCarton
        ];

        $result = $this->app->queryResults($sql, $params);

        return $result;
    }

    /*
    ****************************************************************************
    */
}