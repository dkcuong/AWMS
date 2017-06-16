<?php

namespace tables\cycleCount;

use tables\_default;

class cycleCountAudit extends _default
{
    public $displaySingle = 'New';

    public $primaryKey = 'cis.count_item_id';

    public $ajaxModel = 'cycleCount\cycleCountAudit';

    public $fields = [
        'count_item_id' => [
            'display' => 'Select',
            'noEdit' => TRUE
        ],
        'cis.count_item_id' => [
            'display' => 'Item ID',
            'noEdit' => TRUE,
            'canAdd' => TRUE
        ],
        'vendorName' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Customer Name',
            'noEdit' => TRUE
        ],
        'sku' => [
            'display' => 'SKU',
            'noEdit' => TRUE
        ],
        'size' => [
            'display' => 'Size',
            'noEdit' => TRUE
        ],
        'color'  => [
            'display' => 'Color',
            'noEdit' => TRUE
        ],
        'pack_size' => [
            'display' => 'Pack Size',
            'noEdit' => TRUE,
            'canAdd' => TRUE,
            'autocomplete' => TRUE
        ],
        'allocate_qty' => [
            'display' => 'Allocate Qty',
            'noEdit' => TRUE,
        ],
        'sys_qty' => [
            'display' => 'Sys Qty',
            'noEdit' => TRUE
        ],
        'act_qty' => [
            'display' => 'Act Qty',
        ],
        'sysLocation' => [
            'select' => 'sysLoc.displayName',
            'display' => 'Sys Loc',
            'noEdit' => TRUE
        ],
        'actLocation' => [
            'select' => 'actLoc.displayName',
            'display' => 'Act Loc'
        ],
        'status' => [
            'select' => 'CASE WHEN cis.sts = "OP" THEN "Open"
                              WHEN cis.sts = "NA" THEN "Not applicable"
                              WHEN cis.sts = "RC" THEN "Recount"
                              WHEN cis.sts = "AC" THEN "Accepted"
                              WHEN cis.sts = "DL" THEN "Deleted"
                              ELSE "New"
                         END',
            'display' => 'Status',
            'noEdit' => TRUE
        ],
    ];

    public $orderBy = 'cis.count_item_id DESC';

    public $mainField = 'cis.count_item_id';

    public $customInsert = 'cycleCountDetail';
    
    public $table = ' count_items cis
                JOIN    cycle_count cc ON cc.cycle_count_id = cis.cycle_count_id
                JOIN    locations sysLoc ON sysLoc.id = cis.sys_loc
                JOIN    locations actLoc ON actLoc.id = cis.act_loc
                JOIN    vendors v ON v.id = cis.vnd_id
                JOIN    warehouses w ON w.id = v.warehouseID';

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

    function insertTable()
    {
        return $this->table;
    }

    /*
    ****************************************************************************
    */
}