<?php

namespace tables\receiving;

class containerReports extends \tables\_default
{
    public $ajaxModel = 'receiving\\containerReports';

    public $primaryKey = 'co.recNum';

    public $where = '(h.rcv_nbr IS NULL
            OR h.inv_sts
        )
        AND       (i.inv_id IS NULL
            OR i.inv_sts != "c"
        )';

    public $groupBy = 'co.recNum';

    public $xlsExportFileHandle = 'xlsExportFileHandle';

    public $backgroundColors = [
        'darkGreen' => 'A9B7A9',
        'orange' => 'F1D2A7',
        'green' => 'B3DAB3',
        'brown' => 'B3A27F',
        'red' => 'EC9C9C',
    ];

    /*
    ****************************************************************************
    */

    function fields()
    {
        $statuses = new \tables\statuses\inventory($this->app);

        $racked = $statuses->getStatusID(\tables\inventory\cartons::STATUS_RACKED);

        return [
            'warehouse' => [
                'select' => 'w.displayName',
                'display' => 'Warehouse',
                'ddField' => 'displayName',
                'searcherDD' => 'warehouses',
                'backgroundColor' => 'darkGreen',
            ],
            'vendorName' => [
                'select' => 'CONCAT(w.shortName, "_", v.vendorName)',
                'display' => 'Client Name',
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
                'backgroundColor' => 'darkGreen',
            ],
            'name' => [
                'display' => 'Container',
                'backgroundColor' => 'darkGreen',
            ],
            'setDate' => [
                'select' => 't.setDate',
                'display' => 'Container Arrival',
                'searcherDate' => TRUE,
                'backgroundColor' => 'orange',
            ],
            'userID' => [
                'select' => 'u.username',
                'display' => 'Receiver Name',
                'searcherDD' => 'users',
                'ddField' => 'u.username',
                'backgroundColor' => 'orange',
            ],
            'initialCount' => [
                'select' => 'COUNT(tr.cartonCount)',
                'display' => 'Cartons Received',
                'backgroundColor' => 'orange',
            ],
            'numberOfPieces' => [
                'select' => 'SUM(uom)',
                'display' => 'Units Recieved',
                'backgroundColor' => 'orange',
            ],
            'skuCount' => [
                'select' => 'COUNT(DISTINCT sku)',
                'display' => 'SKU per Container',
                'groupedFields' => 'sku',
                'backgroundColor' => 'orange',
            ],
            'putawayDate' => [
                'select' => 't.logTime',
                'display' => 'Receiving Date',
                'searcherDate' => TRUE,
                'backgroundColor' => 'orange',
            ],
            'daysOld' => [
                'select' => 'DATEDIFF(NOW(), DATE(t.setDate))',
                'display' => 'Days Old',
                'backgroundColor' => 'green',
            ],
            'palletCount' => [
                'select' => 'COUNT(DISTINCT plate)',
                'display' => 'Pallets per Container',
                'groupedFields' => 'co.recNum',
                'backgroundColor' => 'orange',
            ],
            'floorPalletCount' => [
                'select' => '
                    COUNT(DISTINCT
                        IF(ca.statusID = ' . $racked . '
                           AND ca.mStatusID = ' . $racked . '
                           AND l.displayName LIKE "%_REC_%",
                           plate, NULL
                        )
                    )',
                'display' => 'Floor Pallets per Container',
                'groupedFields' => 'plate',
                'backgroundColor' => 'orange',
            ],
            'invoiced' => [
                'select' => 'inv_dt',
                'display' => 'Billing',
                'backgroundColor' => 'brown',
            ],
        ];
    }

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'inventory_cartons ca
            JOIN      inventory_batches b ON b.id = ca.batchID
            JOIN      inventory_containers co ON co.recNum = b.recNum
            JOIN      upcs p ON p.id = b.upcID
            JOIN      vendors v ON v.id = co.vendorID
            JOIN      warehouses w ON v.warehouseID = w.id
            JOIN      locations l ON l.id = ca.locID
            LEFT JOIN tally_cartons tc ON tc.invID = ca.id
            LEFT JOIN tally_rows tr ON tr.id = tc.rowID
            LEFT JOIN tallies t ON t.id= tr.tallyID
            LEFT JOIN ' . $userDB . '.info u ON co.userID = u.id
            LEFT JOIN inv_his_rcv h ON h.rcv_nbr = co.recNum
            LEFT JOIN invoice_hdr i ON i.inv_id = h.inv_id
            ';
    }

    /*
    ****************************************************************************
    */

    function xlsExportFileHandle($data)
    {
        \excel\exporter::coloring($this, $data);
    }

    /*
    ****************************************************************************
    */

}