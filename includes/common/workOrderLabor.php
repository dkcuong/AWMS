<?php

namespace common;

class workOrderLabor
{

    /*
    ****************************************************************************
    */

    static function getWorkOrderLaborTables($app, $vendorIDs=[])
    {
        $joinClause = $field = NULL;
        $whereClause =  1;

        if ($vendorIDs) {

            $field = 'CONCAT_WS("-", cust_id, labor_cd_id),
                      cust_id,';

            $joinClause = '
                JOIN      invoice_cost ic ON ic.chg_cd_id = chm.chg_cd_id';

            $whereClause = 'cust_id IN (' . $app->getQMarkString($vendorIDs) . ')';
        }

        $sql = 'SELECT    ' . $field . '
                          labor_cd_id,
                          chm.chg_cd_id,
                          labor_cd_des,
                          chg_cd,
                          chg_cd_des
                FROM      wo_labor wl
                JOIN      charge_cd_mstr chm ON chm.chg_cd_id = wl.chg_cd_id
                ' . $joinClause . '
                WHERE     ' . $whereClause . '
                AND       wl.sts != "d"
                AND       chg_cd_sts = "active"';

        $results = $app->queryResults($sql, $vendorIDs);

        $return = [];

        foreach ($results as $values) {

            $labor = $values['labor_cd_des'];
            $chargeCode = $values['chg_cd'];

            $data = [
                'id' => $values['chg_cd_id'],
                'text' => $values['chg_cd_des'],
            ];

            if ($vendorIDs) {

                $vendorID = $values['cust_id'];

                $return[$vendorID][$labor][$chargeCode] = $data;
            } else {
                $return[$labor][$chargeCode] = $data;
            }
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    static function getWorkOrderLaborValues($app, $workOrderIDs)
    {
        if (! $workOrderIDs) {
            return [];
        }

        $sql = 'SELECT    wo_dtl_id,
                          wo_num,
                          chg_cd_id,
                          qty
                FROM      wo_dtls wd
                JOIN      wo_hdr wh ON wh.wo_id = wd.wo_id
                WHERE     wh.wo_id IN (' . $app->getQMarkString($workOrderIDs) . ')
                AND       wh.sts != "d"
                AND       wd.sts != "d"';

        $results = $app->queryResults($sql, $workOrderIDs);

        $return = [];

        foreach ($results as $values) {

            $workOrderNumber = $values['wo_num'];
            $chargeCode = $values['chg_cd_id'];

            $return[$workOrderNumber][$chargeCode] = $values['qty'];
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

}
