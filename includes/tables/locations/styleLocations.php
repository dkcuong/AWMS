<?php

namespace tables\locations;

class styleLocations extends \tables\_default
{
    public $primaryKey = 'ca.id';

    public $ajaxModel = 'locations\\styleLocations';

    public $fields = [
        'sku' => [
            'select' =>'u.sku',
            'display' => 'Product',
            'noEdit' => TRUE,
            'acTable' => 'upcs u',
        ],
        'plate' => [
            'display' => 'License Plate',
            'noEdit' => TRUE,
            'acDisabled' => TRUE,
        ],
        'locID' => [
            'select' => 'l.displayName',
            'display' => 'Location',
            'noEdit' => TRUE,
            'acTable' => 'locations l',
        ],
        'mLocID' => [
            'select' => 'ml.displayName',
            'display' => 'Manual Location',
            'noEdit' => TRUE,
            'acTable' => 'locations ml',
        ],
        'statusID' => [
            'select' => 's.shortName',
            'display' => 'Status',
            'searcherDD' => 'statuses\\inventory',
            'ddField' => 'shortName',
            'update' => 'ca.statusID',
        ],
        'mStatusID' => [
            'select' => 'ms.shortName',
            'display' => 'Manual Status',
            'searcherDD' => 'statuses\\inventory',
            'ddField' => 'shortName',
            'update' => 'ca.mStatusID',
        ],
        'containerName' => [
            'select' => 'co.name',
            'display' => 'Container',
            'noEdit' => TRUE,
            'acTable' => 'inventory_containers co',
        ],
        'uom' => [
            'select' => 'uom',
            'display' => 'UOM',
            'noEdit' => TRUE,
            'acTable' => 'inventory_cartons',
        ],
        'cartons' => [
            'select' => 'COUNT(ca.id)',
            'display' => 'Cartons',
            'groupedFields' => 'ca.id',
            'noEdit' => TRUE,
            'acTable' => 'inventory_cartons ca
                     JOIN inventory_batches b ON b.id = ca.batchID',
        ],
        'pieces' => [
            'select' => 'SUM(uom)',
            'display' => 'Case Total Pieces',
            'groupedFields' => 'uom',
            'noEdit' => TRUE,
            'acTable' => 'inventory_cartons ca
                     JOIN inventory_batches b ON b.id = ca.batchID',
        ],
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client Name',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
            'noEdit' => TRUE,
        ],
        'upc' => [
            'display' => 'UPC',
            'noEdit' => TRUE,
            'acTable' => 'upcs',
        ],
        'color' => [
            'display' => 'Color',
            'noEdit' => TRUE,
            'acTable' => 'upcs',
        ],
        'size' => [
            'display' => 'Size',
            'noEdit' => TRUE,
            'acTable' => 'upcs',
        ],
        'prefix' => [
            'batchFields' => TRUE,
            'display' => 'Prefix',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'suffix' => [
            'select' => 'suffix',
            'batchFields' => TRUE,
            'display' => 'Suffix',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'height' => [
            'batchFields' => TRUE,
            'display' => 'Height',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'width' => [
            'batchFields' => TRUE,
            'display' => 'Width',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'length' => [
            'batchFields' => TRUE,
            'display' => 'Length',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'weight' => [
            'batchFields' => TRUE,
            'display' => 'Weight',
            'noEdit' => TRUE,
            'acTable' => 'inventory_batches',
        ],
        'setDate' => [
            'display' => 'Set Date',
            'searcherDate' => TRUE,
            'orderBy' => 'ca.id',
            'noEdit' => TRUE,
        ],
    ];

    public $table = 'inventory_containers co
            JOIN      inventory_batches b ON b.recNum = co.recNum
            JOIN      inventory_cartons ca ON ca.batchID = b.id
            JOIN      vendors v ON v.id = co.vendorID
            JOIN      warehouses w ON v.warehouseID = w.id
            JOIN      locations l ON l.id = ca.locID
            JOIN      locations ml ON ml.id = ca.mLocID
            JOIN      statuses s ON ca.statusID = s.id
            JOIN      statuses ms ON ca.mStatusID = ms.id
            JOIN      upcs u ON u.id = b.upcID';

    public $where = 's.shortName = "RK"
            AND s.category = "inventory"
            AND NOT isSplit
            AND NOT unSplit';

    public $displaySingle = 'Style Locations';

    public $mainField = 'ca.id';

    public $groupBy = 'ca.locID, b.upcID, uom, plate';

    /*
    ****************************************************************************
    */

    function controllerData($values, $processType, $vendorID=NULL)
    {
        $ajax = new \datatables\ajax($this->app);

        $ajax->addControllerSearchParams([
            'values' => $values,
            'field' => $processType,
        ]);

        $vendorID ?
            $ajax->addControllerSearchParams([
                'values' => [$vendorID],
                'field' => 'vendorID',
            ]) :
            NULL;

        $ajax->output($this, [
            'bFilter' => FALSE,
            'order' => ['sku' => 'DESC'],
        ]);
    }

    /*
    ****************************************************************************
    */

}