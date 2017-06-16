<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function listAddPlatesView()
    {
        echo $this->labelMakerHTML;
        echo $this->datatablesStructureHTML;
    }
    
    /*
    ****************************************************************************
    */

    function searchPlatesView()
    {
        echo $this->searcherHTML;
        echo $this->datatablesStructureHTML;
        echo $this->searcherExportButton;
        
    }
    
    /*
    ****************************************************************************
    */

    function recordSheetsPlatesView()
    {
        ?>
        <div class="message dontPrint">
        Vendor:
        <select id="vendorSelect">
            <option value="0">Select</option><?php
            foreach ($this->vendorDD as $id => $name) { ?>
                <option value="<?php echo $id; ?>"><?php echo $name; ?></option><?php
            } ?>
        </select>

        Quantity of Pallet Sheets: 
        <input id="palletNumber" type="text">

        <input id="printSheet" type="submit" value="Print Pallet Inventory Sheets">
        </div>

        <div id="sheetPage">
    
        <span class="printDisplay">
            Vendor: <span class="vendorDisplay"></span>
        </span>

        <span class="printDisplay">
            Pallet: <span class="palletNumberDisplay"></span>
        </span>
        <span id="locationDisplay" class="printDisplay">
            Location: 
        </span>

        <table class="palletSheet">
            <tr class="miniTable">
                <th rowspan="4" colspan="13"></th>
                <th>A</th>
                <th>XS</th>
                <th>S</th>
                <th>M</th>
                <th>L</th>
                <th>XL</th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            <tr class="miniTable">
                <th>B</th>
                <th>1X</th>
                <th>2X</th>
                <th>3X</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            <tr class="miniTable">
                <th>C</th>
                <th>38B</th>
                <th>38D</th>
                <th>40C</th>
                <th>40D</th>
                <th>42C</th>
                <th>42D</th>
                <th>44C</th>
                <th>44D</th>
            </tr>
            <tr class="miniTable">
                <th>D</th>
                <th>34B</th>
                <th>34C</th>
                <th>34D</th>
                <th>36A</th>
                <th>36B</th>
                <th>36C</th>
                <th>36D</th>
                <th>38B</th>
            </tr>
            <tr>
                <th class="rowCount">Line</th>
                <th class="widthTwo">UPC</th>
                <th class="widthThree">SKU</th>
                <th class="widthThree">Color</th>
                <th class="widthOne">Client PO</th>
                <th class="widthHalf">Suffix</th>
                <th class="widthHalf">UOM</th>
                <th class="widthHalf">Number of Boxes</th>
                <th class="widthHalf">HEIGHT</th>
                <th class="widthHalf">WIDTH</th>
                <th class="widthHalf">LENGTH</th>
                <th class="widthHalf">WEIGHT</th>
                <th class="widthQuarter">MET<br>/<br>IMP</th>
                <th class="miniTable">E</th>
                <th class="miniTable">32A</th>
                <th class="miniTable">32B</th>
                <th class="miniTable">34A</th>
                <th class="miniTable">34B</th>
                <th class="miniTable">34C</th>
                <th class="miniTable">34D</th>
                <th class="miniTable">36A</th>
                <th class="miniTable">36B</th>
            </tr>
            <?php for ($i=0; $i<20; $i++) { ?>
                <tr><td class="rowCount"><?php echo $i+1; ?></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    </tr>  
            <?php } ?>
            <tr>  
                <td colspan="2">Employee Name</td>
                <td colspan="22">______________</td>
            </tr>  
        </table>
        </div>
        <?php    
    }    

    /*
    ****************************************************************************
    */

}