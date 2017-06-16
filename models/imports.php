<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

use excel\exporter;

class model extends base
{
    public $getTemplate;

    public $changes = [];

    public $files = ['upc_info', 'products'];

    public $imports = [
        'Inventory Pallet Sheet' => [
            'input' => 'palletSheet',
        ]
    ];

    public $importInterface = [
        'fields' => [
            // Input File
            'upc_info' => [
                // Fields of File
                'input' => [
                    'sku',
                    'description',
                    'upc',
                    'quantity',
                    'dimensions',
                    'cube'
                ],
                // Fields of DB Table
                'output' => [
                    'sku',
                    'description',
                    'upc',
                    'quantity',
                    'width',
                    'length',
                    'height',
                    'cube'
                ]
            ],
            'products' => [
                'input' => [
                    'inv_key',
                    'sku',
                    'color',
                    'size',
                    'custom',
                    'lot_code',
                    'il_key',
                    'zone',
                    'bin',
                    'uom',
                    'current_quantity',
                    'adjustment_quantity',
                    'comment'
                ],
                'output' => [
                    'vendorInvID',
                    'sku',
                    'color',
                    'size',
                    'custom',
                    'lotCode',
                    'ilKey',
                    'zone',
                    'location',
                    'uom',
                    'quantity',
                    'adjustment_quantity',
                    'comment'
                ]
            ]
        ]
    ];

    public $ajaxURLs = [
        [
            'input' => 'createRCLabels',
            'title' => 'Creating RC Labels'
        ], [
            'input' => 'createPlates',
            'title' => 'Creating Plates'
        ],
    ];

    public $vendorsHTML = NULL;

    public $errors = [];

    public $vendor = NULL;

    /*
    ****************************************************************************
    */

    function numbersOnly(&$string)
    {
        $string = preg_replace('/[^0-9]/', NULL, $string);
    }

    /*
    ****************************************************************************
    */

    function template($importTitle)
    {
        $templateName = $importTitle . '_Import_Template';

        exporter::header($templateName);

        $headerRow = $this->imports[$importTitle]['fields'];

        exporter::arrayToTable([$headerRow]);
    }

    /*
    ****************************************************************************
    */

    function customizeRow(&$oneRow, &$flag, $params)
    {
        $table = $params['table'];
        $key = $params['key'];
        $cellValue = $params['cellValue'];

        switch ($table) {
            case 'elite_brands_upc_info':
                switch ($key) {
                    case 'upc':
                        $upc = str_replace('-', NULL, $cellValue);
                        // Non numeric UPCs are changed to 0
                        $oneRow[] = is_numeric($upc) ? $upc : 0;
                        break;
                    case 'dimensions':
                        $dimensions = explode('x', $cellValue);
                        $trimmed = array_map('trim', $dimensions);
                        $oneRow = array_merge($oneRow, $trimmed);
                        break;
                    case 'quantity':
                        // If quantity cell that is not numeric, create flag
                        $flag = intval($cellValue) ? $flag : TRUE;
                    default:
                        $oneRow[] = $cellValue;
                }
                break;
            case 'elite_brands_products':
                switch ($key) {
                    case 'inv_key':
                        // If quantity cell that is not numeric, create flag
                        $flag = intval($cellValue) ? $flag : TRUE;
                    case 'uom':
                        $this->numbersOnly($cellValue);
                    default:
                        $oneRow[] = $cellValue;
                }
                break;
            case 'genius_pack_upc_info':
                switch ($key) {
                    case 'upc':
                        $upc = str_replace('-', NULL, $cellValue);
                        // Non numeric UPCs are changed to 0
                        $oneRow[] = is_numeric($upc) ? $upc : 0;
                        break;
                    case 'quantity':
                        // If quantity cell that is not numeric, create flag
                        $flag = intval($cellValue) ? $flag : TRUE;
                    default:
                        $oneRow[] = $cellValue;
                }
                break;
            case 'genius_pack_products':
                switch ($key) {
                    case 'inv_key':
                        // If quantity cell that is not numeric, create flag
                        $flag = intval($cellValue) ? $flag : TRUE;
                    case 'uom':
                        $this->numbersOnly($cellValue);
                    default:
                        $oneRow[] = $cellValue;
                }
                break;
            default:
                die('Table ' . $table . ' not found');
        }
    }

    /*
    ****************************************************************************
    */

    function modelGetRackedInventory()
    {
        $sql = 'SELECT    ca.id,
                          u.sku,
                          COUNT(ca.id) AS uccCount,
                          ca.uom,
                          l.displayName AS location
                FROM      inventory_containers co
                JOIN      inventory_batches b ON co.recNum = b.recNum
                JOIN      inventory_cartons ca ON b.id = ca.batchID
                JOIN      locations l ON l.id = ca.locID
                JOIN      statuses s ON s.id = ca.statusID
                JOIN      upcs u ON u.id = b.upcID
                WHERE     s.shortName = "RK"
                GROUP BY  u.sku,
                          ca.uom,
                          l.displayName';

        $results = $this->queryResults($sql);

        $inventory = [];
        foreach ($results as $row) {
            $sku = $row['sku'];
            $uom = $row['uom'];
            $location = $row['location'];
            $inventory[$sku][$uom][$location]['uccCount'] = $row['uccCount'];
        }

        return $inventory;
    }

    /*
    ****************************************************************************
    */

    function modelCombineInventory($newInventory, &$inventory)
    {
        $styleID = 0;
        $locationID = 2;
        $uomID = 3;
        $quantityID = 4;

        foreach ($newInventory as $row) {

            $style = $row[$styleID];

            $location = $row[$locationID];

            $originalUOM = $row[$uomID];

            $this->numbersOnly($originalUOM);

            $uom = sprintf('%03d', $originalUOM);

            $quantity = $row[$quantityID];

            $inventory[$style][$uom][$location]['newCount'] =
                getDefault($inventory[$style][$uom][$location]['newCount'], 0);

            $inventory[$style][$uom][$location]['newCount'] += $quantity;
        }
    }

    /*
    ****************************************************************************
    */

    function modelUpdateStyleStatues($inventory)
    {
        $statuses = new tables\statuses\inventory($this);

        $statusIDs = $statuses->getStatusIDs([
            \tables\inventory\cartons::STATUS_RACKED,
            \tables\inventory\cartons::STATUS_SHIPPED,
            \tables\inventory\cartons::STATUS_RECEIVED
        ]);

        $this->beginTransaction();

        foreach ($inventory as $style => $uoms) {
            $this->modelUpdateUOMStatus($statusIDs, $style, $uoms);
        }

        $this->commit();
    }

    /*
    ****************************************************************************
    */

    function modelUpdateUOMStatus($statusIDs, $style, $uoms)
    {
        foreach ($uoms as $uom => $locations) {
            foreach ($locations as $location => $row) {
                $newCount = getDefault($row['newCount'], 0);
                $uccCount = getDefault($row['uccCount'], 0);
                if ($uccCount > $newCount) {

                    $limit = $uccCount - $newCount;

                    $sql = 'UPDATE    inventory_batches b
                            JOIN      inventory_cartons ca ON b.id = ca.batchID
                            JOIN      locations l ON l.id = ca.locID
                            JOIN      upcs u ON u.id = b.upcID
                            SET       ca.statusID = ?,
                                      ca.mStatusID = ?
                            WHERE     l.displayName = ?
                            AND       ca.uom = ?
                            AND       u.sku = ?
                            AND       ca.statusID IN (?, ?)
                            LIMIT     ' . $limit;

                    $params = [
                        $statusIDs['SH']['id'],
                        $statusIDs['SH']['id'],
                        $location,
                        $uom,
                        $style,
                        $statusIDs['RC']['id'],
                        $statusIDs['RK']['id']
                    ];

                    $this->runQuery($sql, $params);

                    $this->changes[$style][$uom][$location] =
                        $uccCount - $newCount;
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

}

