<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

class model extends base
{

    public $container;

    public $printLabelsLink = NULL;

    public $palletRows = 0;

    public $labor = 0;

    public $otLabor = 0;

    public $datableFilters = [
        'warehouseID',
        'vendorID',
    ];

    /*
    ****************************************************************************
    */

    function receivingReportMysqlFilter()
    {
        return [
            'trigger' => TRUE,
            'searches' => [
                [
                    'selectField' => 'Receiving Date Starting',
                    'selectValue' => date('Y-m-d', strtotime('-1 DAY')),
                    'clause' => 'logTime > NOW() - INTERVAL 1 DAY',
                ],
            ],
        ];
    }

    /*
    ****************************************************************************
    */

}
