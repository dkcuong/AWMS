<?php

class view extends controller
{
    function infoTallySheetView()
    {
        ?> 
        <form  method="POST"  id="infoID"><?php
        $this->getHtml();
        $this->getHtml2();

        if(! isset($this->post['Submit'])){ ?>
            <input id="submit" align="bottom" type="submit" name="Submit" value="Submit"></form>
        <?php } else {  ?> 
            <input id="pdf" type="submit" name="generatePDF" value="Print" action="<?php makeLink('tallySheet', 'pdf')?>">
               <?php }
    } 
    /*
    ****************************************************************************
    */
    function getHtml($isPdf=FALSE) 
    { ?> 
       
        <table class="grid" border="1">
            <tr class="bgcolor">
                <th colspan = "4" class="bigFont">
                    SELDAT DIST. INC
                </th>
                <th colspan="2" class="generateFont">PICKING </th>
            </tr>
            <tr height="100">
                <th class="bgcolor generateFont">
                    START DATE
                </th>
                <td align = "center">
                    <?php if ($isPdf) { 
                        echo getDefault($this->post['startDate']);
                    } else {?>
                        <input type="text" size="10" class="datepicker" name="startDate"
                               value="<?php echo getDefault($this->post['startDate']); ?>">
                    <?php } ?>
                </td>               
                <th class="bgcolor  generateFont">
                    CANCEL DATE
                </th>
                <td><?php 
                        if ($isPdf) { 
                            echo getDefault($this->post['cancelDate']);
                        } else {?>                                  
                                <input type="text" size="10" class="dateUpdate" name="cancelDate" id="dateInput_1"
                        value="<?php echo getDefault($this->post['cancelDate']); ?>">
                        <?php } ?>
                </td>
                <td class="font">
                    COMPLETE 
                </td>
                <td><?php 
                        if ($isPdf) { 
                            echo $this->checks['complete'] ? 'X' : '';
                        } else {?>  
                                <input type="checkbox" name="complete" value="1" <?php echo $this->checks['complete']; ?>>
                          <?php } ?>
                </td>              
            </tr>
            <tr height="50">
                <th class="bgcolor generateFont">
                    CLIENT
                </th>
                <th colspan = "3" height="50">
                    <?php 
                        if ($isPdf) { 
                            echo getDefault($this->post['client']);
                        } else {?> 
                            <input class="input generateFont" name="client" 
                                id="input_1" value="<?php echo getDefault($this->post['client']); ?>">
                        <?php  } ?>
                </th>
                <th class="font">
                    INCOMPLETE
                </th>
                <td><?php
                if($isPdf){
                    echo $this->checks['incomplete'] ? 'X' : '';
                } else { ?> 
                             <input type="checkbox" name="incomplete" value="1" <?php echo $this->checks['incomplete']; ?>>
                    <?php } ?>
                </td>
            </tr>
            <tr height="100">
                <th align = "center" class="bgcolor generateFont">
                    CUSTOMER
                </th>
                <th colspan = "3">
                    <?php 
                    if ($isPdf) { 
                        echo getDefault($this->post['customer']);
                    } else {?> 
                            <input class="input generateFont" name="customer" 
                                   id="input_2" value="<?php echo getDefault($this->post['customer']); ?>">
                      <?php } ?>
                </th>
                <td class="font">
                    FOR WORK ORDER
                </td>
                <td align = "center">
                <?php
                if($isPdf){
                    echo $this->checks['workOrder'] ? 'X' : '';
                } else { ?> 
                            <input type="checkbox" name="workOrder" value="1" <?php echo $this->checks['workOrder']; ?>>
                     <?php } ?>
                </td>
            </tr>
            <tr>
                <th align = "center" rowspan="2" class="bgcolor bigFont">
                    PO#
                </th>
                <th colspan = "3" align = "center" rowspan="2">
                <?php 
                    if ($isPdf) { 
                        echo getDefault($this->post['poNumber']);
                    } else {?> 
                            <input type="text" size="15" class="bigFont input" 
                            name="poNumber" id="input_3" 
                            value="<?php echo getDefault($this->post['poNumber']); ?>">
                    <?php } ?>
                </th>
                <td class="font">
                    FOR PROCESSING
                </td>
                <td>
                <?php
                if($isPdf){
                    echo $this->checks['workOrderProcessing'] ? 'X' : '';
                } else { ?> 
                            <input type="checkbox" name="workOrderProcessing" 
                            value="1" <?php echo $this->checks['workOrderProcessing']; ?>>
                    <?php } ?>
                </td>                             
            </tr>
            <tr>
                <th colspan="2" class="bgcolor font">
                    WORK ORDER
                </th>                          
            </tr>
            <tr>
                <th rowspan="2" class="bgcolor generateFont">
                    CTN'S
                </th>
                <td align = "center">
                    PICKING
                </td>
                <td align = "center">
                    WO
                </td>
                <td align = "center">
                    PROCESSING
                </td>

                <th align = "center" class="font">
                   FOR PROCESSING
                </th>
                <td>
                <?php
                if($isPdf){
                    echo $this->checks['processing'] ? 'X' : '';
                } else { ?> 
                        <input type="checkbox" name="processing" value="1" <?php echo $this->checks['processing']; ?>>
                    <?php } ?>
                </td>                             
            </tr>
            <tr>
                <td>
                <?php 
                    if ($isPdf) { 
                        echo getDefault($this->post['cartonsPick']);
                    } else {?>                         
                            <input type="text" size="3" class="numericCheck font" name="cartonsPick" 
                                   value="<?php echo getDefault($this->post['cartonsPick']); ?>">
                      <?php } ?>
                </td>
                <td>
                <?php 
                    if ($isPdf) { 
                        echo getDefault($this->post['cartonsWork']);
                    } else {?>
                            <input type="text" size="3" class="numericCheck font" name="cartonsWork" 
                            value="<?php echo getDefault($this->post['cartonsWork']); ?>">
                    <?php } ?>
                </td>
                <td><?php
                    if ($isPdf) { 
                            echo getDefault($this->post['cartonsProcess']);
                        } else {?> 
                        <input type="text" size="3" class="numericCheck font" name="cartonsProcess" 
                               value="<?php getDefault($this->post['cartonsProcess']); ?>">
                        <?php } ?>
                </td>
                <th class="font">
                    BACK TO STOCK
                </th>    
                <td><?php 
                    if ($isPdf) { 
                        echo getDefault($this->post['backToStock']);
                    } else {?>  
                        <input type="text" class="datepicker" name="backToStock"
                               value="<?php echo getDefault($this->post['backToStock']); ?>">  
                    <?php } ?>
                </td>   
            </tr>
            <tr>
                <th rowspan="2" align = "center" class="bgcolor generateFont">
                    PALLETS
                </th>
                <td rowspan="2"><?php
                    if($isPdf){
                        echo getDefault($this->post['palletNumber']);
                    } else { ?>
                        <input type="text" name="palletNumber" size="3" class="numericCheck font" 
                               value="<?php echo getDefault($this->post['palletNumber']); ?>">             
                    <?php } ?>
                </td>
                <td rowspan="2" align = "center" class="midFont">
                    OF
                </td>
                <td rowspan="2"><?php
                    if($isPdf){
                        echo getDefault($this->post['palletTotal']);
                    } else { ?><input type="text" size="3" class="numericCheck font" name="palletTotal" 
                           value="<?php echo getDefault($this->post['palletTotal']); ?>">
                    <?php } ?>
                </td> 
                <th colspan="2" class="bgcolor font">
                    PROCESSING
                </th>
            </tr>
            <tr>
                <td class="font">
                    READY TO SHIPPING
                </td>
                <td><?php
                    if($isPdf){
                        echo $this->checks['ready'] ? 'X' : '';
                    } else { ?>                    
                        <input type="checkbox" name="ready" value="1" <?php echo $this->checks['ready']; ?>>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <th class="bgcolor generateFont">
                    DC
                </th>
                <th colspan="3"><?php
                    if($isPdf){
                        echo getDefault($this->post['dc']);
                    } else { ?>   
                        <input type="text" size="20" class="bigFont input" name="dc" id="input_4" value="<?php echo getDefault($this->post['dc']); ?>">
                    <?php } ?>
                </th> 
                <td class="font">
                    BACK TO STOCK
                </td>
                <td><?php
                    if($isPdf){
                        echo getDefault($this->post['backToStockProcess']);
                    } else { ?>  
                        <input type="text"  class="datepicker"  
                            name="backToStockProcess" 
                            value="<?php echo htmlspecialchars(getDefault($this->post['backToStockProcess'])); ?>">                            
                    <?php } ?>
                </td>                     
            </tr>
        </table>
        <br></br><br></br>
        <?php
    }
        
    function getHtml2($isPdf=FALSE) 
    { ?>
    
        <table class="grid" id="pallets" border="1">
            <tr class="bold">
                <th colspan="4" class="bgcolor bigFont">
                    <i>SELDAT DIST.INC - TALLY SHEET</i>
                </th>
                <th colspan="2" align="left">
                    DATE<?php 
                    if($isPdf){
                        echo date('y-m-d'); 
                    } else { ?>
                        <input class="smallFont" name="date" value="<?php echo date('Y-m-d'); ?>">
                    <?php } ?>
                </th>
            </tr>
            <tr>
                <th colspan="2" class="generateFont">
                   CLIENT
                </th>
                <th colspan="4">
                    <div class="display generateFont" name="display_1" id="display_1"></div>
                        <?php echo getDefault($this->post['client']); ?>
                </th>
            </tr>
            <tr>
                <th colspan="2" class="generateFont">
                    CUSTOMER
                </th>
                <td colspan="4"><?php 
                if($isPdf){
                     echo getDefault($this->post['customer']);
                } else {?>
                    <div class="display generateFont" name="customer" id="display_2"></div><?php echo getDefault($this->post['customer']); ?>
                <?php } ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="generateFont">
                    PO#
                </td>
                <td colspan="4"><?php
                if($isPdf){
                     echo getDefault($this->post['poNumber']);
                } else {?>
                    <div class="display generateFont" id="display_3"></div><?php echo getDefault($this->post['poNumber']); ?>
                <?php } ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="generateFont">
                    DC
                </td>
                <td colspan="4">
                    <div class="display generateFont" id="display_4"></div><?php echo getDefault($this->post['dc']); ?>
                </td>
            </tr>
          
                <?php 
                $palletSize = count(getDefault($this->post['pallet']));

                if (isset($this->post['pallet']) && $palletSize > 29) {
                    $loop = $palletSize / 6;
                } else {
                    $loop = 5;
                }
            for ($row=1; $row<=$loop; $row++) { ?>
                <tr><?php 
                    for ($cell=1; $cell<=6; $cell++) {
                        $cellNumber = $cell + 6 * ($row - 1); 
                        $index = $cellNumber - 1;
                            ?>
                    <td  align="left" valign="top">
                                <span class = "smallFont">
                                    <?php echo $cellNumber; ?>
                                </span>
                                    <?php
                            if ($isPdf){ ?>
                            <span class="generateFont"><?php
                                echo isset($this->post['pallet'][$index]) ? $this->post['pallet'][$index] : NULL;     
                                ?>
                            </span><?php
                            } else { ?>
                                <input name="pallet[]" value="<?php echo isset($this->post['pallet'][$index]) ? $this->post['pallet'][$index] : NULL; ?>"> 
                            <?php } ?>
                    </td><?php
                        } ?>
                </tr><?php
            } ?>                    
            <?php if(!$isPdf){ ?>
            <tr id="addRow">
                <td colspan="6">
                    <input type="button" class="addRow" value="Add Row"/>
                </td>
            </tr>
            <?php } ?>
            <tr class="bold">
                <td colspan="2" class="bgcolor generateFont" height="60">
                    TOTAL PALLETS
                </td>
                <td><?php 
                if ($isPdf){
                    echo getDefault($this->post['palletTotal']);
                } else { ?>
                    <input type="text" name="palletTotal" value="<?php echo getDefault($this->post['palletTotal']); ?>">
                <?php } ?>
                </td>
                <td colspan="2" class="bgcolor generateFont">
                    CARTONS
                </td>
                <td><?php 
                if ($isPdf){
                    echo getDefault($this->post['cartons']);
                } else { ?>
                    <input type="text" name="cartons" value="<?php echo getDefault($this->post['cartons']); ?>">
                <?php } ?>
                </td>                    
            </tr>
            <tr class="bold">
                <td colspan="2" rowspan="2" class="bgcolor generateFont" height="80">
                    LOCATION
                </td>
                <td rowspan="2"><?php 
                    if ($isPdf){
                        echo getDefault($this->post['location']);
                    } else { ?>
                        <input type="text" name="location" value="<?php echo getDefault($this->post['location']); ?>">                     
                    <?php } ?>
                </td>
                <td colspan="2" class="font">
                    CANCEL DATE
                </td>
                <td class="font">
                    NAME
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="dateDisplay" id="dateDisplay_1"></div>
                        <?php echo getDefault($this->post['cancelDate']); ?>
                </td>
                <td><?php
                    if($isPdf){
                        echo getDefault($this->post['name']); 
                    } else { ?>
                        <input type="text" name="name" value="<?php echo getDefault($this->post['name']); ?>">
                    <?php } ?>
                </td>
            </tr>
        </table>
    <?php    
    }
}