<?php

namespace reports;

use inventory\transfers;
use csv\export as csvExporter;
use excel\exporter as excelExporter;

class inventoryReport
{
    public $db;

    /*
    ****************************************************************************
    */

    static function processDownloadCarton($app, $type)
    {
        $arrayData = [];
        $fileName = 'mezzanine_transferred_inventory_report';
        $colNames = [
            ['title' => 'Warehouse'],
            ['title' => 'Vendor'],
            ['title' => 'Transfer Num'],
            ['title' => 'SKU QTY'],
            ['title' => 'CTN QTY'],
            ['title' => 'PCS QTY'],
            ['title' => 'Created']
        ];

        $data = self::getMezzanineTransferredInventory($app);

        if (! $data) return 'Data be empty!';

        foreach ($data as $row) {
            $arrayData[] = [
                $row['shortName'],
                $row['vendorName'],
                $row['transferID'],
                $row['sumUpc'],
                $row['cartons'],
                $row['pieces'],
                $row['createDate']
            ];
        }

        $params = [
            'fileName' => $fileName,
            'fieldKeys' => $colNames,
            'data' => $arrayData
        ];

        if ($type == 'csv') {
            csvExporter::ArrayToFile($params);
        } else {
            excelExporter::ArrayToExcel($params);
        }

    }

    /*
    ****************************************************************************
    */

    static function getMezzanineTransferredInventory($app)
    {

        $results = self::getWhereClause($app->post);

        $sql = 'SELECT    transferID,
                          w.shortName,
                          createDate,
                          vendorName
                FROM      transfers t
                LEFT JOIN transfer_items ti ON ti.transferID = t.id
                JOIN      vendors v ON v.id = ti.vendorID
                JOIN      warehouses w ON w.id = v.warehouseID
                WHERE     discrepancy = 0' . $results['whereClause'] . '
                GROUP BY  t.id';

        $transferData = $app->queryResults($sql, $results['params']);

        $report = [];

        $sqls = self::getTransferDataQuery();

        foreach ($transferData as $transferID => $row) {

            // Get all carton on this transfer id (uom=1)
            $summaryData = $app->queryResult($sqls['summarySql'], [$transferID]);

            // Get data have split carton
            $haveSplitData = $app->queryResults($sqls['haveSplitSql'], [$transferID]);

            $parentCarton = count($haveSplitData);
            $parentPcs = array_sum(array_column($haveSplitData, 'uom'));

            $row['transferID'] = $transferID;
            $row['pieces'] = $summaryData['sumPcs']; // summary uom
            $row['sumUpc'] = $summaryData['sumUpc']; // count upc
            // Ctn qty = total carton - total uom of carton have split + count
            // carton have split 
            $row['cartons'] = $summaryData['sumPcs'] - $parentPcs + $parentCarton;

            $report[] = $row;
        }

        return $report;
    }

    /*
    ****************************************************************************
    */

    static function getWhereClause($post) {
        $whs = getDefault($post['warehouse']);
        $cus = getDefault($post['customer']);
        $startCtd = getDefault($post['created-starting']);
        $endCtd = getDefault($post['created-ending']);

        $where = '';
        $params = [];

        if ($whs) {
            $where .= ' AND w.id = ?';
            $params[] = $whs;
        }

        if ($cus) {
            $where .= ' AND v.id = ?';
            $params[] = $cus;
        }

        if ($startCtd) {
            $where .= ' AND DATE(createDate) >= ?';
            $params[] = $startCtd;
        }

        if ($endCtd) {
            $where .= ' AND DATE(createDate) <= ?';
            $params[] = $endCtd;
        }

        return [
            'whereClause' => $where,
            'params' => $params
        ];
    }

    /*
    ****************************************************************************
    */

    static function getTransferDataQuery() {


        $summarySql = 'SELECT COUNT(ca.id) AS sumCarton,
                              SUM(ca.uom) AS sumPcs,
                              COUNT(DISTINCT b.upcID) AS sumUpc
                      FROM    transfers tr
                      JOIN    transfer_items i ON tr.id = i.transferID
                      JOIN    transfer_cartons t ON t.transferItemID = i.id
                      JOIN    inventory_cartons ca ON ca.id = t.cartonID
                      JOIN    inventory_batches b ON ca.batchID = b.id
                      WHERE   transferID = ?';

        $haveSplitSql = 'SELECT sp.parentID,
                                ca.uom
                         FROM   transfers tr
                         JOIN   transfer_items i ON tr.id = i.transferID
                         JOIN   transfer_cartons t ON t.transferItemID = i.id
                         JOIN   inventory_splits sp ON sp.childID = t.cartonID
                         JOIN   inventory_cartons ca ON ca.id = sp.parentID
                         WHERE  transferID = ?
                         GROUP BY sp.parentID';

        return [
            'summarySql' => $summarySql,
            'haveSplitSql' => $haveSplitSql
        ];
    }

    /*
    ****************************************************************************
    */
}
