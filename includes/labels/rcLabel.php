<?php

namespace labels;

class rcLabel extends \tcpdf 
{
    static $html;
    
    static $containerInfo = [];
    
    static $inventory;

    /*
    ****************************************************************************
    */
    
    static function getContainerInfo($params) 
    {
        if (self::$containerInfo) {
            return;
        }
        
        $recNum = $params['recNum'];
        self::$inventory = $inventory = $params['inventory'];
        $warehouseID = getDefault($params['warehouseID']);
        
        $recNums = is_array($recNum) ? $recNum : [$recNum];
        
        $param = $warehouseID ? [$warehouseID] : $recNums;
        $clause = $warehouseID ? 'v.warehouseID = ?' : 
            'co.recNum IN ('.$inventory->app->getQMarkString($recNums).')';
        $orderBy = $warehouseID ? 'ORDER BY l.distance ASC' : NULL;
        
        $recNum || $warehouseID or die('Receiving Number Not Found');
        
        $sql = 'SELECT co.recNum,
                       co.name,
                       v.vendorName AS vendor,
                       DATE(setDate) as setDate,
                       w.displayName AS warehouseName,
                       w.id AS warehouseID
                FROM   '.$inventory->table.'             
                WHERE  '.$clause.'
                       '.$orderBy;

        self::$containerInfo = $inventory->app->queryResults($sql, $param);
    }
        
    /*
    ****************************************************************************
    */
    
    static function get($params) 
    {
        $inventory = self::$inventory ? self::$inventory : $params['inventory'];
        $fileName = getDefault($params['fileName']);

        $pdf = new static(); 

        // Can pass recNum throught post var or manually
        $params['recNum'] = $recNum = isset($params['recNum']) ? 
            $params['recNum'] : getDefault($inventory->app->get['recNum']);

        self::getContainerInfo($params);

        if (! self::$containerInfo) {
            echo '<br>No Container Found';
            return;
        }
        
        foreach (self::$containerInfo as $info) {
        
            ob_start(); ?>
            <html>
            <head>
            <style>
                body {
                    text-align: center;        
                }
                td {
                    border: 1px solid black;                                   
                }
            </style>
            </head>
            <body height="100%">
                <table height="100%">
                <tr>
                <td><?php echo $info['vendor'];?></td>
                </tr><tr>
                <td>CONTAINER#</td>
                </tr><tr>
                <td><?php echo $info['name'];?></td>
                </tr><tr>
                <td><?php echo $info['setDate'];?></td>
                </tr>
                </table>
            </body>
            </html>
            <?php 
            self::$html = ob_get_clean();

            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);                            
            $pdf->SetDisplayMode('fullpage'); 
            $pdf->SetFont('Helvetica', 'B', 55, '', 'false');
            $pdf->SetMargins(0, 20, 20, false);
            $pdf->setCellHeightRatio(2.1);
            $pdf->AddPage('L');
            $pdf->WriteHTML(self::$html);
        }
        
        $display = $fileName ? 'F' : 'I';
        $output = $fileName ? $fileName : 'pdf';

        $pdf->Output($output, $display);
    }
    
    /*
    ****************************************************************************
    */
    
    static function updateRCLabel($app, $recNum)
    {
        $sql = 'UPDATE    tallies t
                JOIN      inventory_containers co ON co.recNum = t.recNum
                SET       rcLabelPrinted = 1
                WHERE     co.recNum = ?';

        $app->runQuery($sql, [$recNum]);
    }
    
    /*
    ****************************************************************************
    */
}