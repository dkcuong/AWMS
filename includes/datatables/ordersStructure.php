<?php

namespace datatables;

class ordersStructure extends structure
{
    
    /*
    ****************************************************************************
    */

    static function exportCustomTable($dtInfo, $multiRows, $displayFirst=FALSE)
    {
        $columns = $dtInfo['columns'];
        if (! $displayFirst) {
            array_shift($columns);
        }
        ?>
        <table cellspacing="0" width="100%">
            <thead>
                <tr>
                <?php foreach ($columns as $field) { ?>
                    <th><?php echo $field['title']; ?></th>
                <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($dtInfo['data'] as $rowID => $row) { 
                    // If exporting use html to color multi row orders
                    $multiRow = $multiRows[$rowID];
                    $multiHTML = $multiRow ? ' bgcolor="#ffffbb"' : NULL; ?>
                    <tr>
                    <?php foreach ($row as $cellID => $cell) { 
                        if ($cellID || $displayFirst) { ?>
                            <td<?php echo $multiHTML; ?>><?php echo $cell; ?></td>
                        <?php } ?>
                    <?php } ?>
                    </tr><?php 
                } 
                ?>
            </tbody>
        </table>
        <?php        
    }    

}