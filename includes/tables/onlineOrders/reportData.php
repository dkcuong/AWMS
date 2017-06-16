<?php
/**
 * Created by PhpStorm.
 * User: vuong
 * Date: 08-Dec-15
 * Time: 3:24 AM
 */

namespace tables\onlineOrders;


class reportData {

    protected $app;

    public $logs = [];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function transferMezzanine()
    {
        $mezzanine = new \tables\onlineOrders($this->app);
        $importInfo = new importsInfo();
        $importQuery = new mezzanineImportQuery($this->app);
        $reportIDs = [];

        $reports = \common\report::getCronData($this->app, 'METR');

        if (! $reports) {
            $this->logs[] = 'No more Cron to RUN';
            return $this->logs;
        }

        foreach ($reports as $report) {

            $batchInfo = $this->processBatchInfo($report);

            $orderInfo = reset($batchInfo);

            $mezzanine->importOrTransfer([
                'app' => $this->app,
                'info' => $importInfo,
                'upcItems' => $batchInfo,
                'vendorID' => $orderInfo['vendorID'],
                'query' => $importQuery,
                'method' => 'notimport'
            ]);

            $reportIDs[] = $report['reportID'];
        }

        \common\report::updateReportData($this->app, $reportIDs);

        return $this->logs;
    }

    /*
    ****************************************************************************
    */

    public function processBatchInfo($report)
    {
        $orderBatch = new \tables\orderBatches($this->app);
        $batchInfo = $orderBatch->getBatchInfo($report['primeKey']);
        $data = [];

        $transferUpcs = json_decode($report['data'], true);

        foreach ($transferUpcs as $upc) {
            if (isset($batchInfo[$upc])) {
                $data[$upc] = $batchInfo[$upc];
            }
        }

        return $data;
    }

    /*
    ****************************************************************************
    */
}