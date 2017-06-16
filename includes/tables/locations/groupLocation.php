<?php

namespace tables\locations;

class groupLocation extends \tables\_default
{
    public $primaryKey = 'ca.id';
    
    public $ajaxModel = 'locations\\groupLocation';
    
    public $fields = [
        'location' => [
            'select' => 'l.displayName',
            'display' => 'Location',
            'noEdit' => TRUE,
        ],
        'sku' => [
            'select' =>'p.sku',
            'display' => 'SKU',
            'noEdit' => TRUE,
        ],
        'uom' => [
            'display' => 'UOM',
            'noEdit' => TRUE,
        ],
        'cartons' => [
            'select' => 'COUNT(ca.id)',
            'display' => 'Cartons',
            'groupedFields' => 'ca.id',
            'noEdit' => TRUE,
        ],
        'pieces' => [
            'select' => 'SUM(uom)',
            'display' => 'Case Total Pieces',
            'groupedFields' => 'uom',
            'noEdit' => TRUE,
        ],
        'color' => [
            'display' => 'Color',
            'noEdit' => TRUE,
        ],
        'size' => [
            'display' => 'Size',
            'noEdit' => TRUE,
        ],
        'height' => [
            'batchFields' => TRUE,
            'display' => 'Height',
            'noEdit' => TRUE,
        ],
        'width' => [
            'batchFields' => TRUE,
            'display' => 'Width',
            'noEdit' => TRUE,
        ],
        'length' => [
            'batchFields' => TRUE,
            'display' => 'Length',
            'noEdit' => TRUE,
        ],
        'weight' => [
            'batchFields' => TRUE,
            'display' => 'Weight',
            'noEdit' => TRUE,
        ],
    ];
    
    public $where = 's.shortName = "RK"
            AND s.category = "inventory"
            AND NOT isSplit
            AND NOT unSplit';
    
    public $displaySingle = 'Locations Scannder';

    public $mainField = 'ca.id';
    
    public $groupBy = 'location, sku, uom, color, size';
    
    /*
    ****************************************************************************
    */
    
    function table()
    {
        return 'inventory_containers co
                JOIN      inventory_batches b ON b.recNum = co.recNum
                JOIN      inventory_cartons ca ON ca.batchID = b.id
                JOIN      vendors v ON v.id = co.vendorID
                JOIN      warehouses w ON v.warehouseID = w.id
                JOIN      locations l ON l.id = ca.locID
                JOIN      statuses s ON ca.statusID = s.id
                JOIN      upcs p ON p.id = b.upcID';
    }

    /*
    ****************************************************************************
    */
    
    function __construct($app=FALSE)
    {
        $this->addConditionInGroupLocaion();
        
        parent::__construct($app);
    }


   /*
    ****************************************************************************
    */
    
    function addConditionInGroupLocaion() 
    {
        if (isset($_SESSION['locationSearch'])) {
            $listLocation = $_SESSION['locationSearch'];
            
            $arrLocation = formatStringForArray($listLocation);
            
            if ($arrLocation) {
                if (trim($this->where)) {
                    $this->where .= ' AND l.displayName IN ('
                        . implode(',', $arrLocation) . ')';
                } else {
                    $this->where .= 'l.displayName IN (' 
                        . implode(',', $arrLocation);
                }
            }
        }
    }

    /*
    ****************************************************************************
    */
}