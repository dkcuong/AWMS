<?php

namespace inventory;

class waves 
{
    static function create()
    {
        
    }
    
    /*
    ****************************************************************************
    */

    static function consolidationCollect($upcs)
    {
        ?>
        <style>
            table {
                width: 100%;
            }
            table td {
                border: 1px solid #ccc;;
                text-align: center;
                vertical-align: top;
                padding: 3px;
            }
            table td.ids {
                padding-top: 1em;
            }


        @media print { #printButton { display: none; } }
        
        </style>
        <button id="printButton" onclick="window.print()">Print</button>
        <h3>Inventory Consolidation Wave Pick</h3>    
        <table>
        <?php 
        
        foreach ($upcs as $upc => $locations) { 

            ?>
            <tr><td>UPC</td>
                <td>Location</td>
                <td>Carton Quantity</td>
            </tr>
            <?php
            
            $first = TRUE;
            foreach ($locations as $location => $quantity) {
                $fromLocBar = '/wms/classes/barcodephp/html/image.php?filetype=PNG&dpi=72'
                    .'&scale=1&rotation=0&font_family=Arial.ttf&font_size=10&text='
                    .$location.'&thickness=30&checksum=&code=BCGcode39';
                $upcBar = '/wms/classes/barcodephp/html/image.php?filetype=PNG&dpi=72'
                    .'&scale=1&rotation=0&font_family=Arial.ttf&font_size=10&text='
                    .$upc.'&thickness=30&checksum=&code=BCGcode39';
                ?>
                <tr>
                    <?php if ($first) { ?>
                        <td rowspan="<?php echo count($locations); ?>">
                            <img src="<?php echo $upcBar; ?>"></td>
                    <?php } ?>
                    <td><img src="<?php echo $fromLocBar; ?>"></td>
                    <td><?php echo $quantity; ?></td>
                </tr>
                <?php
                $first = FALSE;
            }
        } ?>
        </table>
        <?php        
    }

    /*
    ****************************************************************************
    */

    static function display($upcs)
    {
        ?>
        <style>
            table {
                width: 100%;
            }
            table td {
                border: 1px solid #ccc;;
                text-align: center;
                vertical-align: top;
                padding: 3px;
            }
            table td.ids {
                padding-top: 1em;
            }


        @media print { #printButton { display: none; } }
        
        </style>
        <button id="printButton" onclick="window.print()">Print</button>
        <h3>Inventory Consolidation Wave Pick</h3>    
        <table>
        <?php 
        
        foreach ($upcs as $upc => $locations) { 

            ?>
            <tr><td>UPC</td>
                <td>From Location</td>
                <td>To Location</td>
                <td>Carton Quantity</td>
            </tr>
            <?php
            
            $first = TRUE;
            foreach ($locations as $info) {
                $toLocBar = '/wms/classes/barcodephp/html/image.php?filetype=PNG&dpi=72'
                    .'&scale=1&rotation=0&font_family=Arial.ttf&font_size=10&text='
                    .$info['toLoc'].'&thickness=30&checksum=&code=BCGcode39';
                $fromLocBar = '/wms/classes/barcodephp/html/image.php?filetype=PNG&dpi=72'
                    .'&scale=1&rotation=0&font_family=Arial.ttf&font_size=10&text='
                    .$info['fromLoc'].'&thickness=30&checksum=&code=BCGcode39';
                $upcBar = '/wms/classes/barcodephp/html/image.php?filetype=PNG&dpi=72'
                    .'&scale=1&rotation=0&font_family=Arial.ttf&font_size=10&text='
                    .$upc.'&thickness=30&checksum=&code=BCGcode39';
                ?>
                <tr>
                    <?php if ($first) { ?>
                        <td rowspan="<?php echo count($locations); ?>">
                            <img src="<?php echo $upcBar; ?>"></td>
                    <?php } ?>
                    <td><img src="<?php echo $fromLocBar; ?>"></td>
                    <td><img src="<?php echo $toLocBar; ?>"></td>
                    <td><?php echo $info['quantity']; ?></td>
                </tr>
                <?php
                $first = FALSE;
            }
        } ?>
        </table>
        <?php        
    }

    /*
    ****************************************************************************
    */
}
