<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use tables\cycleCount\cycleCount;
use tables\cycleCount\cycleCountDetail;

class controller extends template
{

    function createCycleCountController()
    {
        $table = new tables\cycleCount\cycleCount($this);
        $vendors = new tables\vendors($this);
        $warehouses = new tables\warehouses($this);
        $users = new tables\users($this);

        $this->warehouse = $warehouses->getWarehouse();
        $this->users = $users->get();
        $this->vendors = $vendors->get();

        foreach ($this->users as $key => $values) {
            if ($values['employer'] != 'Seldat') {
                unset($this->users[$key]);
            }
        }

        $this->setRoleUser();

        $ajax = new datatables\ajax($this);

        $fields = array_keys($table->fields);
        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers'] = [
            'cycleID'      => $fieldKeys['cycle_count_id'],
            'client'       => $fieldKeys['client'],
            'statuses'     => trim($fieldKeys['sts']),
            'viewCycle'    => $fieldKeys['action'],
            'actionDelete' => $fieldKeys['action_delete'],
            'dueDate'      => $fieldKeys['due_dt'],
            'reportName'   => $fieldKeys['name_report']
        ];

        $this->jsVars['currentDate'] = date('Y-m-d');
        $this->jsVars['isCreateCycle'] = TRUE;
        $this->jsVars['isStaffUser'] = $this->isStaffUser;
        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $sortColumn = $fieldKeys['cycle_count_id'];

        $ajax->output($table, [
            'ajaxPost' => TRUE,
            'order'    => [
                $sortColumn => 'DESC'
            ]
        ]);

        new datatables\searcher($table);

        $this->jsVars['urls']['viewCycleDetail'] =
                makeLink('cycleCount', 'view');
        $this->jsVars['urls']['auditCycle'] = makeLink('cycleCount', 'audit');
        $this->jsVars['urls']['getCustomerByWarehouseID'] =
                customJSONLink('appJSON', 'getCustomerByWarehouseID');
        $this->jsVars['urls']['getCustomerNameByWarehouseID'] =
                customJSONLink('appJSON', 'getCustomerNameByWarehouseID');
        $this->jsVars['urls']['createCycleCount'] =
                customJSONLink('appJSON', 'createCycleCount');
        $this->jsVars['urls']['printCyclePDF'] =
                makeLink('cycleCount', 'printCyclePDF');
         $this->jsVars['urls']['deleteCycleCount'] =
                makeLink('appJSON', 'deleteCycleCount');
        $this->jsVars['urls']['processSearchSKU'] =
                customJSONLink('appJSON', 'processSearchSKU');
        $this->jsVars['urls']['searchSKUAutoComplete'] =
                customJSONLink('appJSON', 'searchSKUAutoComplete');

        $this->jsVars['cycleStatus'] = cycleCount::getStatusCycleCount();
    }

    /*
    ****************************************************************************
    */

    function viewCycleCountController()
    {
        $this->isEdit = isset($this->get['editable']);

        $this->cycleID = $this->jsVars['cycleID'] =
                getDefault($this->get['cycleID']);

        $cycleCount = new tables\cycleCount\cycleCount($this);

        $info = $cycleCount->getCycleStatus($this->cycleID);

        $this->data = $cycleCount->getCycleCountInfoById($this->cycleID);
        $this->vendor = new \tables\vendors($this);

        $status = getDefault($this->data['sts']);
        $status = trim(strtoupper($status));

        $this->canUpdate = ($status == cycleCount::STATUS_ASSIGNED
                || $status == cycleCount::STATUS_RECOUNT);

        $table = $info['hasSizeColor'] ?
            new tables\cycleCount\cycleCountDetail($this) :
            new tables\cycleCount\cycleCountDetailNonSizeColor($this);

        $userId = access::getUserID();
        $inCycleGroup = $cycleCount->checkUserInCycleGroup($userId);

        $ajax = new datatables\ajax($this);

        $this->includeJS['js/datatables/editables.js'] = TRUE;

        $ajax->addControllerSearchParams([
            'values' => [$this->cycleID],
            'field'  => 'cc.cycle_count_id'
        ]);

        $fields = array_keys($table->fields);
        $fieldKeys = array_flip($fields);

        $this->jsVars['columnNumbers'] = [
            'systemQty'     => $fieldKeys['sys_qty'],
            'actualQty'     => $fieldKeys['act_qty'],
            'systemLoc'     => $fieldKeys['sys_loc'],
            'actualLoc'     => $fieldKeys['act_loc'],
            'totalPiece'    => $fieldKeys['total_piece'],
            'cycleStatus'   => $fieldKeys['sts']
        ];

        $sortColumn = $fieldKeys['sts'];

        $ajax->output($table, [
            'iDisplayLength' => $this->defaultShowData,
            'ajaxPost' => TRUE,
            'order' => [
                $sortColumn => 'DESC'
            ]
        ]);

        if (($userId != $this->data['asgd_id']) && $this->isEdit
        && ! $inCycleGroup ) {

            $link = makeLink('cycleCount', 'view', [
                'cycleID' => $this->cycleID
            ]);

            redirect($link);
        }

        $this->jsVars['isEdit'] = $this->isEdit;
        $this->jsVars['cycleStatus'] = $info['cycleStatus'];
        $this->jsVars['warehouseID'] = $this->data['whs_id'];
        $this->jsVars['uomByCarton'] = strtoupper($info['byUOM']) == 'CARTON';
        $this->jsVars['cycleStatusArray'] = cycleCount::getStatusCycleCount();
        $this->jsVars['countItemStatus'] = cycleCountDetail::getStatusCountItem();

        new datatables\searcher($table);

        if ($this->isEdit) {
            new datatables\editable($table);

            $this->jsVars['editables'][$table->dtName]['sAddURL'] =
                customJSONLink('appJSON', 'addCustomRow');

            $this->customAddRows($table);

        }

        $this->addClickEventOnDatatable($table);
        $this->setDefaultForAutocompleteLinkCycle($this, $table);

        $this->printPdfUrl = makeLink('cycleCount', 'printCyclePDF', [
            'cycleID' => $this->cycleID
        ]);
        $this->jsVars['viewCycleCount'] = makeLink('cycleCount', 'view');
        $this->jsVars['urls']['printCyclePDF'] =
                customJSONLink('appJSON', 'printCyclePDF');
        $this->jsVars['urls']['saveCycle'] =
                customJSONLink('appJSON', 'saveCycle');
        $this->saveCycleDataLink = $this->jsVars['urls']['saveCycleItem'] =
                customJSONLink('appJSON', 'saveCycleItem');
        $this->jsVars['urls']['getCustomerNameByWarehouseID'] =
                customJSONLink('appJSON', 'getCustomerNameByWarehouseID');
        $this->jsVars['urls']['addCustomRow'] =
                customJSONLink('appJSON', 'addCustomRow');
        $this->jsVars['urls']['auditCycle'] = makeLink('cycleCount', 'audit', [
            'cycleID' => $this->cycleID
        ]);
        $this->jsVars['urls']['getSKUByClientID'] =
                customJSONLink('appJSON', 'getSKUByClientID');
        $this->jsVars['urls']['loadUPCInfoFromAjax'] =
                customJSONLink('appJSON', 'loadUPCInfoFromAjax');
        $this->jsVars['urls']['checkLocationOnWarehouse'] =
                customJSONLink('appJSON', 'checkLocationOnWarehouse');
        $this->jsVars['urls']['getSKUByWarehouseID'] =
                customJSONLink('appJSON', 'getSKUByWarehouseID');

    }

    /*
    ****************************************************************************
    */

    function auditCycleCountController()
    {
        $this->cycleID = getDefault($this->get['cycleID']);

        $cycleCount = new tables\cycleCount\cycleCount($this);
        $info = $cycleCount->getCycleStatus($this->cycleID);

        $table = $info['hasSizeColor'] ?
            new tables\cycleCount\cycleCountAudit($this) :
            new tables\cycleCount\cycleCountAuditNonSizeColor($this);

        $this->data = $cycleCount->getCycleCountInfoById($this->cycleID);

        $status = strtoupper(trim($this->data['sts']));
        $this->isCycleComplete =
                $status == tables\cycleCount\cycleCount::STATUS_COMPLETE;

        $fields = array_keys($table->fields);
        $fieldKeys = array_flip($fields);
        $ajax = new datatables\ajax($this);

        $ajax->addControllerSearchParams([
            'values' => [$this->cycleID],
            'field'  => 'cis.cycle_count_id'
        ]);

        $this->includeJS['js/datatables/editables.js'] = TRUE;
        $this->includeJS['js/jQuery/blocker.js'] = TRUE;

        $this->jsVars['columnNumbers'] = [
            'systemQty' => $fieldKeys['sys_qty'],
            'actualQty' => $fieldKeys['act_qty'],
            'systemLoc' => $fieldKeys['sysLocation'],
            'actualLoc' => $fieldKeys['actLocation'],
            'status'    => $fieldKeys['status'],
            'id'        => $fieldKeys['count_item_id']
        ];

        $ajax->output($table, [
            'iDisplayLength' => $this->defaultShowData,
            'ajaxPost' => TRUE,
            'bFilter'  => TRUE
        ]);

        new datatables\editable($table);
        new datatables\searcher($table);

        $this->jsVars['urls']['viewCycleDetail'] =
                makeLink('cycleCount', 'view');
        $this->jsVars['urls']['auditCycle'] = makeLink('cycleCount', 'audit', [
            'cycleID' => $this->cycleID
        ]);
        $this->jsVars['urls']['acceptCountItems'] =
                makeLink('appJSON', 'acceptCountItems');
        $this->jsVars['urls']['recountCountItems'] =
                makeLink('appJSON', 'recountCountItems');
        $this->jsVars['urls']['filterDatatable'] =
                customJSONLink('appJSON', 'filterDatatable');
        $this->printPdfUrl = makeLink('cycleCount', 'printCyclePDF', [
            'cycleID' => $this->cycleID
        ]);
        $this->jsVars['cycleStatus'] = cycleCount::getStatusCycleCount();
        $this->jsVars['countItemStatus'] = cycleCountDetail::getStatusCountItem();
    }

    /*
    ****************************************************************************
    */

    public function printCyclePDFCycleCountController()
    {
        $cycle = new tables\cycleCount\cycleCount($this);
        $cycleCount = new tables\cycleCount\cycleCountDetail($this);
        $cycleID = getDefault($this->get['cycleID']);
        $params = $cycle->getCycleStatus($cycleID);

        $cycleCount->printPDFCycleCount($cycleID, $params);
    }

    /*
    ****************************************************************************
    */

}
