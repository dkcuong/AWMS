<?php

/*
********************************************************************************
* CLASS VIEW METHODS                                                           *
********************************************************************************
*/

class view extends controller
{

    function clientsCostsView()
    { ?>
    <form method="post" id="clientCosts">
        <table id="charge" width="100%" style="border-collapse: collapse;">
            <tr class="filter">
                <td colspan="2">
                    <select name='vendors' id='vendors'>
  <?php foreach ($this->vendorNames as $vendorID => $vendor) {
            $vendorID = $vendor['vendorID'];
            $selected = $this->vendorID == $vendorID ? 'selected' : NULL; ?>
                        <option value="<?php echo $vendorID; ?>" 
                                <?php echo $selected; ?>>
                            <?php echo $vendor['fullVendorName']; ?> 
                        </option>
  <?php } ?>
                    </select>
                    <br>&nbsp
                </td>
                <td colspan="2" align="right">
                    <select name='prefix' id='prefix' class='prefix'>
  <?php foreach ($this->chargeTypes as $prefix => $display) { 
            $selected = $this->prefix == $prefix ? 'selected' : NULL; ?>
                        <option value="<?php echo $prefix; ?>" 
                                <?php echo $selected; ?>>
                                <?php echo $display['displayName']; ?> 
                        </option>
  <?php } ?>
                    </select>
                    <br>&nbsp
                </td>
            </tr>
  <?php foreach ($this->vendorCosts as $key => $value) { 
            $costs = $value['chg_cd_price'] !== 0  ? $value['chg_cd_price'] : NULL; ?>
            <tr>
                <td style="border: 1px solid black;">
                    <?php echo $value['chg_cd']; ?> 
                </td>
                <td style="border: 1px solid black;">
                    <?php echo $value['chg_cd_des']; ?>
                </td>
                <td width="4%" style="border: 1px solid black;">
                     <?php echo $value['chg_cd_uom']; ?>
                </td>
                <td width="1%" style="border: 1px solid black;">USD</td>
                <td width="5%" style="border: 1px solid black;">
                   <input class="costs"
                                type="text" size="8"
                                data-ref-id="<?php echo $key; ?>"
                                value="<?php echo $costs; ?>">
                </td>
                <td width="3%" style="border: 1px solid black;" align="center">
                    <button class="update" 
                            data-ref-cat="<?php echo $value['chg_cd_type']; ?>"
                            data-ref-uom="<?php echo $value['chg_cd_uom']; ?>"
                            value="<?php echo $key; ?>">Update
                    </button>
                </td>
                <td width="3%" style="border: 1px solid black;" align="center">
                    <button class="delete"
                            value="<?php echo $key; ?>">Delete
                    </button>
                </td>
            </tr>    
  <?php } ?>
        </table>
    </form> <?php
    }    

    /*
    ****************************************************************************
    */

}