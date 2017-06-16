<?php

namespace reports;

use PHPExcel;
use charts\model;
use charts\jpgraph;

class charts extends model
{
    function example()
    {
        $this->objPHPExcel = new PHPExcel();
        
        $this->excel([
            'excelFile' => 'Example_Report',
            'imageDir' => 'reportImages',
            'chartImages' => [[
                'filename' => 'pie',
                'type' => 'pie',
                'xPos' => 'A',
                'yPos' => 1,
                'excelWidth' => 750,
                'excelHeight' => 420,
                
                'width' => 600,
                'height' => 500,
                'theme' => 'vividTheme',
                'groupField' => 'qty',
                'title' => 
                    'Customers with invetnory >1% of total warehouse inventory',
                
                'makeImage' => [
                    'method' => [$this, 'invSumChartImage'],
                ],
            ], [
                'filename' => 'line',
                'type' => 'line',
                'xPos' => 'I',
                'yPos' => 1,
                'excelWidth' => 600,
                'excelHeight' => 450,

                'width' => 700,
                'height' => 500,
                'yField' => 'qty',
                'xField' => 'dt',
                'groupField' => 'wh',
                'title' => 'Warehouse Daily Containers Received',
                'theme' => 'universalTheme',

                'makeImage' => [
                    'method' => [$this, 'rcvSumChartImage'],
                    'params' => [
                        'startDate' => '2016-06-25',
                        'endDate' => '2016-07-01',
                    ],
                ],
            ], [
                'filename' => 'bar',
                'type' => 'bar',
                'xPos' => 'S',
                'yPos' => 1,
                'excelWidth' => 900,
                'excelHeight' => 420,
                

                'width' => 1200,
                'height' => 500,
                'yField' => 'qty',
                'xField' => 'startShipDate',
                'groupField' => 'displayName',
                'theme' => 'universalTheme',
                'offSetHieght' => -40,
                'title' => 'Order By Start Ship Dates and Order Status',
                'xTitle' => 'Start Ship Date',
                'yTitle' => 'Total Orders By Status',
                'auto' => TRUE,

                'makeImage' => [
                    'method' => [$this, 'opSumChartImage'],
                    'params' => [
                        'startDate' => '2016-07-04',
                        'endDate' => '2016-07-11',
                    ],
                ],
            ]],
        ]);
    }
    
    //**************************************************************************
    
    function rcvSumChartImage($params)
    {
        $sql = 'SELECT recNum,
                       w.displayName AS wh,
                       DATE(setDate) AS dt,
                       COUNT(recNum) AS qty
                FROM   inventory_containers c
                JOIN   vendors v ON c.vendorID = v.id
                JOIN   warehouses w ON w.id = v.warehouseID
                WHERE  setDate BETWEEN ? AND ?
                GROUP BY w.id,
                         DATE(setDate)
                ORDER BY setDate 
                DESC';

        $results = $this->db->queryResults($sql, [
            $params['makeImage']['params']['startDate'],
            $params['makeImage']['params']['endDate'],
        ]);

        jpgraph::setData($this, $results)->myChart($params);
    }
    
    //**************************************************************************

    function opSumChartImage($params)
    {
        $sql = 'SELECT n.id, 
                       startShipDate,
                       s.displayName,
                       COUNT(n.id) AS qty
                FROM   neworder n
                JOIN   statuses s ON s.id = n.statusID
                WHERE  1
                AND    startShipDate BETWEEN ? AND ?
                AND   startShipDate
                GROUP BY statusID,
                         startShipDate';
        
        $results = $this->db->queryResults($sql, [
            $params['makeImage']['params']['startDate'],
            $params['makeImage']['params']['endDate'],
        ]);

        jpgraph::setData($this, $results)->myChart($params);
    }
    
    //**************************************************************************

    function invSumChartImage($params)
    {
        $sql = 'SELECT  vendorName,
                        COUNT(c.id) AS qty
                FROM   inventory_cartons c
                JOIN   statuses s ON s.id = c.statusID
                JOIN   inventory_batches b ON b.id = batchID
                JOIN   inventory_containers co ON co.recnum = b.recnum
                JOIN   vendors v ON v.id = co.vendorID
                WHERE  shortName NOT IN ("IN")
                AND    setDate > DATE_SUB(NOW(), INTERVAL 15 DAY)
                GROUP BY vendorID';
        
        $results = $this->db->queryResults($sql);
        
        $custQuantities = array_column($results, 'qty');
        
        $sum = array_sum($custQuantities);

        // Exclude customers with less than 1%
        foreach ($results as $cust => $row) {
            if ($row['qty'] / $sum <= 0.01 ) {
                unset($results[$cust]);
            }   
        }

        jpgraph::setData($this, $results)->myChart($params);
    }
}
