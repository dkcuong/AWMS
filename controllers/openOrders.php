<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function reportOpenOrdersController()
    {
        $model = new \tables\orders\openOrdersReport($this);

        $this->ajax = new \datatables\ajax($this);

        $localtime = time();

        $this->jsVars['openOrders']['fromDate'] = $this->reportDates = $fromDate
                = date('Y-m-d', $localtime);

        $this->jsVars['openOrders']['daysAdd'] = $daysAdd =
                \tables\orders\openOrdersReport::DAYS_ADD;

        for ($count=0; $count<$daysAdd; $count++) {

            $key = ! $count ? $fromDate :
                    date('Y-m-d', strtotime($fromDate . '+' . $count . ' days'));

            $this->jsVars['openOrders']['daysColors'][$key] = 'day' . $count;
        }

        $this->reportDates = array_keys($this->jsVars['openOrders']['daysColors']);

        $fields = array_keys($model->fields);

        $fieldKeys = array_flip($fields);

        $this->jsVars['openOrders']['dateColumnNo'] = $fieldKeys['cancelDate'];

        $this->ajax->warehouseVendorMultiSelectTableController([
            'app' => $this,
            'model' => $model,
            'dtOptions' => [
                'bFilter' => FALSE,
                'order' => [
                    'cancelDate' => 'ASC',
                    'skuCount' => 'DESC',
                    'cartonCount' => 'DESC',
                    'palletCount' => 'DESC',
                    'pieceCount' => 'DESC',
                    'vendorName' => 'ASC',
                ],
            ],
            'warehouseField' => 'shortName',
            'vendorFieldName' => 'vendorName',
            'searcher' => FALSE,
        ]);

        new datatables\editable($model);
    }

    /*
    ****************************************************************************
    */

}