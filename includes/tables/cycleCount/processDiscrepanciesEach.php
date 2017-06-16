<?php
namespace tables\cycleCount;

class processDiscrepanciesEach extends processDiscrepancyByCountItem
{

    /*
    ****************************************************************************
    */

    public function getDiscrepancyData($countItemIds)
    {
        $discrepancyCartons = [];

        foreach ($countItemIds as $countItemId => $countItem) {

            $countItem['count_item_id'] = $countItemId;

            $data = $this->getDiscrepancyCarton($countItem);

            $discrepancyCartons = array_merge($discrepancyCartons, $data);
        }

        return $discrepancyCartons;
    }

    /*
    ****************************************************************************
    */

    private function getDiscrepancyCarton($countItem)
    {
        if (! $countItem) {
            return [];
        }

        $actQty = $countItem['act_qty'] != NULL ? $countItem['act_qty'] :
            $countItem['sys_qty'];
        // between warehouse with system
        $discrepancy = $actQty - $countItem['sys_qty'];

        $lockedCarton = $countItem['sys_qty'] ?
                $this->getCartonFromLockedCarton($countItem) :
                $this->getCartonByUPC($countItem);

        // ATTENTION: getCartonsRemove() shall be applied if $discrepancy is
        // less or EQUAL to zero !!!
        $discrepancyCartons = $discrepancy > 0 ?
            $this->getCartonsToAdd($countItem, $lockedCarton, $discrepancy) :
            $this->getCartonsRemove($countItem, $lockedCarton, $discrepancy);

        return $discrepancyCartons;
    }

    /*
    ****************************************************************************
    */

    private function getCartonsRemove($countItem, $lockedCarton, $discrepancy)
    {
        $absDiscrepancy = abs($discrepancy);

        $packSize = $countItem['pack_size'];
        
        $numberDelete = ceil($absDiscrepancy / $packSize);
        $ajustCarton = $absDiscrepancy % $packSize;

        $discrepancies = $this->getCartonsDelete($lockedCarton, $numberDelete, 
                $countItem);
        
        if ($ajustCarton) {

            $discrepancies[] = [
                'uom' => $packSize,
                'cartonID' => reset($lockedCarton),
                'countItemId' => $countItem['count_item_id'],
                'status' => processAuditCarton::STATUS_DISCREPANCY_ADJUSTED,
                'qty' => $packSize - $ajustCarton
            ];
        }

        return $discrepancies;
    }

    /*
    ****************************************************************************
    */
    
    function getCartonsDelete($lockedCarton, $number, $countItem)
    {
        $discrepancies = [];

        for ($i = 0; $i < $number; $i++) {
            
            $discrepancies[] = [
                'uom' => $countItem['pack_size'],
                'cartonID' => $lockedCarton[$i],
                'countItemId' => $countItem['count_item_id'],
                'status' => processAuditCarton::STATUS_DISCREPANCY_DELETE,
                'qty' => -1 * $countItem['pack_size']
            ];
        }

        return $discrepancies;
    }

    /*
    ****************************************************************************
    */

    private function getCartonsToAdd($countItem, $lockedCarton, $discrepancy)
    {
        $results = [];

        $cartonID = current($lockedCarton);

        $packSize = $countItem['pack_size'];

        $numberCartonClone = intval($discrepancy / $packSize);
        $numberPiecesAdjust = $discrepancy % $packSize;

        if ($numberCartonClone) {
            $results[] = [
                'status' => processAuditCarton::STATUS_DISCREPANCY_CLONE,
                'qty' => $numberCartonClone,
                'uom' => $packSize,
                'cartonID' => $cartonID,
                'countItemId' => $countItem['count_item_id']
            ];
        }

        if ($numberPiecesAdjust) {
            $results[] = [
                'status' => processAuditCarton::STATUS_DISCREPANCY_ADJUSTED,
                'qty' => $numberPiecesAdjust,
                'uom' => $packSize,
                'cartonID' => $cartonID,
                'countItemId' => $countItem['count_item_id']
            ];
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    public function insertDiscrepancies($discrepancyCartons)
    {
        $sql = 'INSERT INTO discrepancy_cartons (
                    count_item_id,
                    invt_ctn_id,
                    dicpy_qty,
                    sts
                ) VALUES (
                    ?, ?, ?, ?
                )';

        foreach ($discrepancyCartons as $discrepancyCarton) {
            $this->app->runQuery($sql, [
                $discrepancyCarton['countItemId'],
                $discrepancyCarton['cartonID'],
                $discrepancyCarton['qty'],
                $discrepancyCarton['status'],
            ]);
        }
    }

    /*
    ****************************************************************************
    */
}