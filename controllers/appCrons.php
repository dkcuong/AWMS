<?php

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

use reports\agingSummary;
use summary\createRcv;
use summary\createOrd;

class controller extends template
{

    function containerReportsAppCronsController()
    {
        $this->logs = reports\containers::sendEmails($this);
    }

    /*
    ****************************************************************************
    */

    function csvTestAppCronsController()
    {

        $sql = 'SELECT *
                FROM   inventory_containers';

        $results = $this->queryResults($sql);

        $file = csv\export::write($results, 'test', 'test.csv');

        $this->logs[] = phpMailer\send::mail([
            'recipient' => 'jonathan.sapp@seldatinc.com',
            'subject' => 'test attachment',
            'body' => 'test file attachment',
            'files' => $file,
        ]);
    }

    /*
    ****************************************************************************
    */

    function rushOrdersNoticesAppCronsController()
    {
        $this->logs = orders\rushNotices::send($this);
    }

    /*
    ****************************************************************************
    */

    function emailProcessedLadingAppCronsController()
    {
        $this->logs = orders\ladingNotices::sendEmails($this, 'processed');
    }

    /*
    ****************************************************************************
    */

    function emailShippedLadingAppCronsController()
    {
        $this->logs = orders\ladingNotices::sendEmails($this, 'shipped');
    }

    /*
    ****************************************************************************
    */

    function emailInvalidPickTicketAppCronsController()
    {
        $this->logs = orders\pickTicketNotices::sendEmails($this);
    }

    /*
    ****************************************************************************
    */

    function emailMinMaxMezzanineInfoAppCronsController()
    {
        $mezzanine = new locations\mezzanine($this);
        $this->logs = $mezzanine->transferMezzanine();
    }

    /*
    ****************************************************************************
    */

    function transferMezzanineOnlineOrderAppCronsController()
    {
        $onlineOrder = new \tables\onlineOrders\reportData($this);
        $this->logs = $onlineOrder->transferMezzanine();
    }

    /*
    ****************************************************************************
    */

    function emailTransferMezzanineAppCronsController()
    {
        $emailCron = new tables\onlineOrders\emailCron($this);
        $this->logs = $emailCron->run();
    }

    /*
    ****************************************************************************
    */

    function invoiceSummaryAppCronsController()
    {
        $method = $this->getVar('callMethod', 'getDef');
        $action = $method ? 'method' : $this->getVar('action');

        switch ($action) {
            case 'make':
                summary\create::init($this)->makeSummaryTable();
                break;
            case 'update':
                summary\inventory::init($this);
                break;
            case 'makeOrders':
                createOrd::init($this)->make();
                break;
            case 'makeRcvs':
                createRcv::init($this)->make();
                break;
            default:
                break;
        }

        $this->logs = [];
    }

    /*
    ****************************************************************************
    */

    function cartonLogSummaryAppCronsController()
    {
        $this->logs = summary\cartonLog::init($this)->cartonLogSum();
    }

    /*
    ****************************************************************************
    */

    function palletLogSummaryAppCronsController()
    {
        $this->logs = summary\create::init($this)->getPallets();
    }

    /*
    ****************************************************************************
    */

    function outboundOrdersSummaryAppCronsController()
    {
        $this->logs = summary\create::init($this)->getOutboundOrders();
    }

    /*
    ****************************************************************************
    */
    function styleHistorySummaryAppCronsController()
    {
        $this->logs = summary\create::init($this)->getStyleHistory();
    }

    /*
    ****************************************************************************
    */

    function lastCartonLogsAppCronsController()
    {
        $this->logs = summary\cartonLog::init($this)->lastCartonSumStatus();
    }

    /*
    ****************************************************************************
    */


    function agingLACartonSummaryAppCronsController()
    {
        $this->logs = agingSummary::init($this)->inventorySummary('Los Angeles');
    }

    /*
    ****************************************************************************
    */

    function agingNJCartonSummaryAppCronsController()
    {
        $this->logs = agingSummary::init($this)->inventorySummary('New Jersey');
    }

    /*
    ****************************************************************************
    */

    function agingTOCartonSummaryAppCronsController()
    {
        $this->logs = agingSummary::init($this)->inventorySummary('Toronto');
    }

    /*
    ****************************************************************************
    */

    function agingFACartonSummaryAppCronsController()
    {
        $this->logs = agingSummary::init($this)->inventorySummary('Fontana');
    }

    /*
    ****************************************************************************
    */

    function agingLAOrderSummaryAppCronsController()
    {
        $this->logs = agingSummary::init($this)->orderSummary('Los Angeles');
    }

    /*
    ****************************************************************************
    */

    function agingNJOrderSummaryAppCronsController()
    {
        $this->logs = agingSummary::init($this)->orderSummary('New Jersey');
    }

    /*
    ****************************************************************************
    */

    function agingTOOrderSummaryAppCronsController()
    {
        $this->logs = agingSummary::init($this)->orderSummary('Toronto');
    }

    /*
    ****************************************************************************
    */

    function agingFAOrderSummaryAppCronsController()
    {
        $this->logs = agingSummary::init($this)->orderSummary('Fontana');
    }

    /*
    ****************************************************************************
    */

    function invoiceReceivingSummaryAppCronsController()
    {
        $this->logs = summary\createRcv::init($this)->getReceivingSummary();
    }

    /*
    ****************************************************************************
    */

    function invoiceOrderProcessingSummaryAppCronsController()
    {
        $this->logs = createOrd::init($this)->getOrderProcessingSummary();
    }

    /*
    ****************************************************************************
    */

    function invoiceStorageSummaryAppCronsController()
    {
        $this->logs = summary\create::init($this)->getStorageSummary();
    }

    /*
    ****************************************************************************
    */

    function invoiceStorageUpdateCartonAppCronsController()
    {
        $this->logs = summary\create::init($this)->updateCartonDate();
    }

    /*
    ****************************************************************************
    */

    function dailyReceivingContainerReportAppCronsController()
    {
        $this->logs = \reports\receiving::processCreateReceivingReport($this);
    }

    /*
    ****************************************************************************
    */

    function emailReceivingWeeklyReportAppCronsController()
    {
        $model = new \tables\receiving\emailWeeklyReport($this);
        $this->logs = $model->run();
    }

    /*
    ****************************************************************************
    */

    function shippedOrderDailyReportAppCronsController()
    {
        $model = new \tables\orders\ShippedDailyReportToVendor($this);
        $this->logs = $model->run('GL');
    }

    /*
    ****************************************************************************
    */
}
