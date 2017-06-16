<?php

namespace tables\inventory;

class recevingReport extends \tables\_default
{
    public $ajaxModel = 'inventory\\recevingReport';
    public $primaryKey = 'co.recNum';

    // do not display orders that are shipped more than 1 business day ago
    public $where = 'v.active
        AND NOT ca.isSplit
        AND NOT ca.unSplit
        ';

    public $groupBy = 'co.recNum';

    /*
    ****************************************************************************
    */

    function fields()
    {
        $fields = [
            'cust' => [
                'select' => 'CONCAT(w.shortName,"_",v.vendorName)',
                'display' => 'Cust',
                'searcherDD' => 'vendors',
                'ddField' => 'CONCAT(w.shortName,"_",v.vendorName)',
                'noEdit' => TRUE
            ],
            'name' => [
                'display' => 'Container Name',
                'noEdit' => TRUE
            ],
            'wh' => [
                'select' => 'w.displayName',
                'display' => 'WH',
                'noEdit' => TRUE
            ],
            'cntr' => [
                'select' => 'co.name',
                'display' => 'Cntr',
                'noEdit' => TRUE
            ],
            'meas' => [
                'select' => 'ms.displayName',
                'display' => 'Meas',
                'noEdit' => TRUE
            ],
            'receiving_id' => [
                'select' => 'rco.receiving_id',
                'display' => 'RCV NBR',
                'noEdit' => TRUE
            ],
            'asnUser' => [
                'select' => 'u.username',
                'display' => 'ASN User',
                'noEdit' => TRUE
            ],
            'asnDt' => [
                'select' => 'co.setDate',
                'display' => 'ASN DT',
                'searcherDate' => TRUE,
                'noEdit' => TRUE
            ],
            'ttlAsnSku' => [
                'select' => 'COUNT(DISTINCT p.sku)',
                'display' => 'TTL ASN SKU',
                'groupedFields' => 'co.recNum',
                'noEdit' => TRUE
            ],
            'ttlAsnCtn' => [
                'select' => 'SUM(IF(ca.cartonID = 1, b.initialCount, 0))',
                'display' => 'TTL ASN CTN',
                'groupedFields' => 'co.recNum',
                'noEdit' => TRUE
            ],
            'ttlAsnPc' => [
                'select' => 'SUM(
                    IF(ca.cartonID = 1, ca.uom * b.initialCount, 0))',
                'display' => 'TTL ASN PC',
                'groupedFields' => 'co.recNum',
                'noEdit' => TRUE
            ],
            'rcDt' => [
                'select' => 't.logTime',
                'display' => 'RC DT',
                'searcherDate' => TRUE,
                'noEdit' => TRUE
            ],
            'rcUser' => [
                'select' => 'uu.username',
                'display' => 'RC User',
                'noEdit' => TRUE
            ],
            'ttlSku' => [
                'select' => 'COUNT(DISTINCT pp.sku)',
                'display' => 'TTL SKU',
                'groupedFields' => 'co.recNum',
                'noEdit' => TRUE
            ],
            'ttlCtn' => [
                'select' => 'COUNT(ico.inventoryID)',
                'display' => 'TTL CTN',
                'groupedFields' => 'co.recNum',
                'noEdit' => TRUE
            ],
            'ttlPc' => [
                'select' => 'SUM(caa.uom)',
                'display' => 'TTL PC',
                'groupedFields' => 'co.recNum',
                'noEdit' => TRUE
            ],
        ];

        return $fields;
    }

    /*
    ****************************************************************************
    */

    function table()
    {
        $userDB = $this->app->getDBName('users');

        return 'inventory_containers co 
                JOIN inventory_batches b on b.recNum = co.recNum
                JOIN inventory_cartons ca on ca.batchID = b.id
                LEFT JOIN inventory_control ico on ico.inventoryID = ca.id
                LEFT JOIN inventory_cartons caa on caa.id = ico.inventoryID
                LEFT JOIN inventory_batches bb on bb.id = caa.batchID
                LEFT JOIN upcs pp on pp.id = bb.upcID
                LEFT JOIN licenseplate li on li.ID = ico.licenseplate
                LEFT JOIN  ' . $userDB . '.info uu ON li.userID = uu.id
                JOIN measurement_systems ms ON ms.id = co.measureID
                JOIN receiving_containers rco ON rco.container_num = co.recNum
                JOIN upcs p ON p.id = b.upcID
                JOIN vendors v ON v.id = co.vendorID
                JOIN warehouses w ON v.warehouseID = w.id
                JOIN ' . $userDB . '.info u ON co.userID = u.id
                LEFT JOIN tallies t on t.recNum = co.recNum';
    }

    /*
   ****************************************************************************
   */
}