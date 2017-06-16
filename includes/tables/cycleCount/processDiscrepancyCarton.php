<?php
namespace tables\cycleCount;

class processDiscrepancyCarton extends processDiscrepancyByCountItem
{

    /*
    ****************************************************************************
    */

    public function getDiscrepancyData($countItems)
    {
        $result = [];

        foreach ($countItems as $key => $item) {

            $item['count_item_id'] = $key;
            $item['act_qty'] = getDefault($item['act_qty'], $item['sys_qty']);

            $discrepancyQty = $item['act_qty'] - $item['sys_qty'];

            if (! $discrepancyQty) {
                continue;
            }

            $data = [
                'countItemID' => $key,
                'diffQty' => abs($discrepancyQty)
            ];

            //clone carton
            if ($discrepancyQty > 0) {

                //case add new SKU
                $data['cartonID'] = $item['sys_qty'] ?
                        $this->getCartonFromLockedCarton($item, 1) :
                        $this->getCartonByUPC($item);

                $data['status'] = self::STATUS_CLONE;

            } else {
                //disable carton (delete)
                $data['cartonID'] = $this->getCartonFromLockedCarton($item,
                        $data['diffQty']);
                $data['status'] = self::STATUS_DELETE;
            }

            $result[$key] = $data;
        }

        return $result;
    }

    /*
    ****************************************************************************
    */

    public function insertDiscrepancies($discrepancies)
    {
        $sql = 'INSERT INTO discrepancy_cartons (
                    count_item_id,
                    invt_ctn_id,
                    dicpy_qty,
                    sts
                ) VALUES (
                    ?, ?, ?, ?
                )';

        foreach ($discrepancies as $discrepancy) {
            if (! getDefault($discrepancy['status'])) {
                continue;
            }

            if ($discrepancy['status'] == self::STATUS_CLONE) {

                $this->app->runQuery($sql, [
                    $discrepancy['countItemID'],
                    reset($discrepancy['cartonID']),
                    $discrepancy['diffQty'],
                    $discrepancy['status'],
                ]);

                continue;
            }

            foreach ($discrepancy['cartonID'] as $cartonID) {
                $this->app->runQuery($sql, [
                    $discrepancy['countItemID'],
                    $cartonID,
                    self::DISCY_QTY_DELETE,
                    $discrepancy['status'],
                ]);
            }
        }
    }

    /*
    ****************************************************************************
    */
}