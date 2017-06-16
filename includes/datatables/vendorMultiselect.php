<?php

namespace datatables;

class vendorMultiselect
{

    /*
    ****************************************************************************
    */

    static function vendorMultiselect($data)
    {
        $object = $data['object'];
        $ddHeights = getDefault($data['ddHeights'], NULL);
        $searcher = $data['searcher'];
        $searchField = getDefault($data['searchField'], 'v.id');
        $radio = getDefault($data['radio']);
        $selectRadio = getDefault($data['selectRadio']);
        $radioValue = getDefault($data['radioValue']);

        $object->userClient = \users\groups::commonClientLookUp($object);
        $object->jsVars['isClient'] = \access::isClient($object);

        $vendors = new \tables\vendors($object);

        $object->vendors = $vendors->get();

        $searcher->createMultiSelectTable([
            'size' => $ddHeights,
            'title' => 'Select Clients To View',
            'idName' => 'vendorID',
            'trigger' => TRUE,
            'subject' => $object->vendors,
            'isClient' => $object->jsVars['isClient'],
            'selected' => $object->userClient,
            'fieldName' => 'fullVendorName',
            'searchField' => $searchField,
            'radio' => $radio,
            'selectRadio' => $selectRadio,
            'radioValue' => $radioValue,
        ]);
    }

    /*
    ****************************************************************************
    */

    static function warehouseVendorGroup($model, $warehouseField='displayName',
                                         $whsType=[], $display=[])
    {
        $showWhs = getDefault($display['showWhs'], TRUE);
        $showVendor = getDefault($display['showVendor'], TRUE);
        $showWhsType = getDefault($display['showWhsType'], FALSE);
        $searcher = new \datatables\searcher($model);
        $warehouses = new \tables\warehouses($model->app);

        $warehousesResults = $warehouses->getDropdown($warehouseField);

        $ddHeights = count($warehousesResults) + 1;

        $subject[-1] = ['warehouse' => 'All'];

        $radio = $showVendor && $showWhs ? 'clientView' : FALSE;

        foreach ($warehousesResults as $key => $warehouse) {
            $subject[$key] = ['warehouse' => $warehouse];
        }

        if ($showWhs) {
            $searcher->createMultiSelectTable([
                'size' => $ddHeights,
                'title' => 'Select Warehouses',
                'idName' => 'warehouseID',
                'subject' => $subject,
                'selected' => [TRUE],
                'fieldName' => 'warehouse',
                'searchField' => 'w.id',
                'radio' => $radio,
                'radioValue' => 'warehouseID',
            ]);
        }

        if ($showVendor) {
            self::vendorMultiselect([
                'ddHeights' => $ddHeights,
                'object' => $model->app,
                'searcher' => $searcher,
                'searchField' => 'v.id',
                'fieldName' => 'vendorName',
                'radio' => $radio,
                'selectRadio' => TRUE,
                'radioValue' => 'vendorID',
            ]);
        }

        if ($showWhsType) {
            $searcher->createMultiSelectTable([
                'size' => $ddHeights,
                'title' => 'Warehouse Type',
                'idName' => 'warehouseType',
                'subject' => $whsType,
                'selected' => [TRUE],
                'fieldName' => 'warehouseType',
                'searchField' => 'l.isMezzanine',
            ]);
        }

        return [
            'searcher' => $searcher,
            'ddHeights' => $ddHeights,
        ];
    }

    /*
    ****************************************************************************
    */
}
