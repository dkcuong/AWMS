<?php

namespace tables;

class upcsClient extends _default
{
	public $primaryKey = 'u.id';

	public $ajaxModel = 'upcsClient';

	public $fields = [
		'finalStatusID' => [
			'select' => 'IF(statusID = 2, 4, statusID)',
			'display' => 'Final Status',
			'noEdit' => TRUE,
		],
		'vendorName' => [
			'select' => 'CONCAT(w.shortName, "_", vendorName)',
			'display' => 'Client Name',
			'noEdit' => TRUE,
			'searcherDD' => 'vendors',
			'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
		],
		'upc' => [
			'display' => 'UPC',
			'noEdit' => TRUE,
		],
		'sku' => [
			'display' => 'Style',
			'update' => 'u.sku',
		],
		'size' => [
			'display' => 'Size',
			'update' => 'size',
		],
		'color' => [
			'display' => 'Color',
			'update' => 'color',
		],
		'statusID' => [
			'select' => 's.displayName',
			'display' => 'Status',
		],
		'totalCartons' => [
			'select' => 'COUNT(ca.id)',
			'display' => 'Total Cartons',
                        'groupedFields' => 'ca.id',
               	],
		'totalPieces' => [
			'select' => 'SUM(ca.uom)',
			'display' => 'Total Pieces',
                        'groupedFields' => 'ca.uom',
		],
	];

	public $table = 'inventory_containers co
					JOIN inventory_batches b ON b.recNum = co.recNum
					JOIN inventory_cartons ca ON b.id = ca.batchID
					JOIN vendors v ON v.id = co.vendorID
					JOIN warehouses w ON w.id = v.warehouseID
					JOIN statuses s ON s.id = ca.statusID
					JOIN upcs u ON u.id = b.upcID';

	public $groupBy = 'v.id, u.upc, finalStatusID';

	public $where = 'NOT isSplit
                     AND NOT unSplit';

	public $multiSelect = 'vendorName';

	/*
	****************************************************************************
	*/

}