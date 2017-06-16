<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

class model extends base
{
    public $datableFilters = [
        'warehouseID',
        'vendorID',
    ];

    public $sizeHeight = 5;

    public $reportDates = [];

    /*
    ****************************************************************************
    */

    function multiSelectCustomDiv()
    { ?>
        <button class="reportDate" data-date="all">Whole week</button>

        <?php foreach ($this->reportDates as $date) { ?>

        <button class="reportDate"
                data-date="<?php echo $date; ?>"><?php echo $date; ?></button>

        <?php }
    }

    /*
    ****************************************************************************
    */

}
