<?php

namespace tables\inventory\adjustments;

class logs extends \tables\_default
{
    public $primaryKey = 'al.id';

    public $ajaxModel = 'inventory\\adjustments\\logs';

    public $table = 'adjustment_logs al
                JOIN      inventory_cartons ca ON al.cartonID = ca.id
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      statuses ns ON newStatusID = ns.id
                JOIN      statuses os ON os.id = oldStatusID
                LEFT JOIN locations l ON l.id = oldLocID
                LEFT JOIN locations nl ON nl.id = newLocID';

    /*
    ****************************************************************************
    */

    function fields()
    {
        $cartons = new \tables\inventory\cartons($this->app);

        $ucc128 = $cartons->fields['ucc128']['select'];

        $plateLength = \tables\plates::PLATE_LENGTH;

        return [
            'name' => [
                'select' => 'co.name',
                'display' => 'Container',
            ],
            'containerRecNum' => [
                'select' => 'b.recNum',
                'display' => 'Receiving Number',
            ],
            'batchID' => [
                'display' => 'Batch Number',
            ],
            'cartonID' => [
                'select' => 'al.cartonID',
                'display' => 'Carton ID',
            ],
            'ucc128' => [
                'select' => $ucc128,
                'display' => 'UCC128',
                'customClause' => TRUE,
                'acDisabled' => TRUE,
            ],
            'oldPlate' => [
                'display' => 'Old License Plate',
                'isNum' => $plateLength,
                'acDisabled' => TRUE,
            ],
            'newPlate' => [
                'display' => 'New License Plate',
                'isNum' => $plateLength,
                'acDisabled' => TRUE,
            ],
            'oldlocID' => [
                'select' => 'l.displayName',
                'display' => 'Old Location',
                'update' => 'oldlocID',
            ],
            'newLocID' => [
                'select' => 'nl.displayName',
                'display' => 'New Location',
                'update' => 'newlocID',
            ],
            'oldStatusID' => [
                'select' => 'os.shortName',
                'display' => 'Old Status',
                'searcherDD' => 'statuses\\inventory',
                'ddField' => 'shortName',
                'update' => 'oldStatusID',
            ],
            'newStatusID' => [
                'select' => 'ns.shortName',
                'display' => 'New Status',
                'searcherDD' => 'statuses\\inventory',
                'ddField' => 'shortName',
                'update' => 'newStatusID',
            ],
            'dateAdjusted' => [
                'display' => 'Date Adjusted',
            ],
        ];
    }

    /*
    ****************************************************************************
    */
}
