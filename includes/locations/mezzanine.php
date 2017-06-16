<?php

namespace locations;

use labels\create;
use models\directories;

class mezzanine extends \tables\_default
{
    public $db;

    public $info = [];

    static $dir;

    static $logs = [];

    const STATUS_TRANSFER = 'METR';

    const STATUS_USER_WAVE_PICK = 'METU';

    /*
    ****************************************************************************
    */

    function transferMezzanine($params=[])
    {
        $isOnlineOrderWavePicks = getDefault($params['isOnlineOrderWavePicks']);

        $app = $this->app;
        $toolTransfer = new \inventory\transfers($app);
        $onlineOrders = new \tables\onlineOrders($app);

        self::$dir = directories::getDir('uploads', 'transfers');

        $batchData = $ordersRequests = $transferIDs = [];

        if ($isOnlineOrderWavePicks) {
            // get inventory that is needed for Online Orders Wave Picks to reserve
    	    $batchData = $this->getTransferBatches();

            $batchIDs = array_keys($batchData);

            $ordersRequests = $onlineOrders->getRequestedInventory($batchIDs);
        }

        $dataTransfers = $this->getSupplements($ordersRequests);

        if (! $dataTransfers) {
            // there is no need in Mezzanine Transfer
            return self::$logs;
        }

        foreach ($dataTransfers as $batchID => $data) {
            $transferIDs[$batchID] = $toolTransfer->importToMezzanine($data);
        }

        if ($batchData) {
            $emailedReports = $this->createOrderWavePicks($batchData, $onlineOrders);

            report::updateReportData($app, $emailedReports);
        }

        $this->reportTransfers($transferIDs, $toolTransfer);

        return self::$logs;
    }

    /*
    ****************************************************************************
    */

    function getRecipientInfo()
    {
        $userDB = $this->app->getDBName('users');

        $sql = 'SELECT  u.id,
                        u.email
                FROM    '.$userDB.'.info u
                JOIN    statuses s ON s.id = u.employer
                JOIN    user_groups ug ON ug.userID = u.id
                JOIN 	groups g ON g.id = ug.groupID
                WHERE   u.active
                AND     ug.active
                AND     g.hiddenName = "mezzanineAdmin"';

        $result = $this->app->queryResults($sql);

        return $result;
    }

    /*
    ****************************************************************************
    */

    function getuserWavePick()
    {
        $userWavePick = [];
        $userDB = $this->app->getDBName('users');

        $sql = 'SELECT	rd.reportID,
                        u.id AS userID,
                        u.email
                FROM	'.$userDB.'.info u
                JOIN	reports_data rd ON rd.primeKey = u.id
                JOIN    reports r ON r.id = rd.reportID
                JOIN	statuses st ON rd.statusID = st.id
                WHERE	st.shortName = "' . self::STATUS_USER_WAVE_PICK . '"
                AND     NOT isSent';

        $results = $this->app->queryResults($sql);

        foreach ($results as $reportID => $value) {
            $userID = $value['userID'];
            $userWavePick[$reportID][$userID]['email'] = $value['email'];
        }

        return $userWavePick;

    }

    /*
    ****************************************************************************
    */

    function getSupplements($ordersRequests=[])
    {
        $locationInfo = new \tables\locations\locationInfo($this->app);

        $this->locationsToReplenish = $locationInfo->getLocationsToReplenish();

        $this->upcIDParams = $return = [];

        // combine Min/Max replenish with Mezzanine transfers for Online Orders Wave Picks
        foreach ($this->locationsToReplenish as &$minMaxRequest) {
            foreach ($ordersRequests as $key => $ordersRequest) {
                if ($ordersRequest['vendorID'] == $minMaxRequest['vendorID']
                 && $ordersRequest['upcID'] == $minMaxRequest['upcID']) {

                    $minMaxRequest['supplement'] += $ordersRequest['supplement'];

                    unset($ordersRequests[$key]);
                }
            }
        }

        $this->locationsToReplenish = array_merge($this->locationsToReplenish,
                $ordersRequests);

        if (! $this->locationsToReplenish) {
            return [];
        }

        foreach ($this->locationsToReplenish as $replenishLocation) {
            if (! isset($replenishLocation['locID'])) {

                $vendorID = $replenishLocation['vendorID'];
                $upcID = $replenishLocation['upcID'];

                $this->upcIDParams[$vendorID][] = $upcID;
            }
        }

        $minMaxLocations = $locationInfo->getByVendorUpcIDs($this->upcIDParams);
        // set Mezzanne locations for online order transfers for vendors that
        // are stated in locations_info table
        foreach ($minMaxLocations as $vendorID => $upcIDs) {
            $this->addLocations($vendorID, $upcIDs);
        }

        foreach ($this->locationsToReplenish as $key => $values) {
            if (! isset($values['locID'])) {
                unset($this->locationsToReplenish[$key]);
            }
        }

        $replenishInventory = $this->getReplenishInventory();

        foreach ($this->locationsToReplenish as $replenishLocation) {

            $vendorID = $replenishLocation['vendorID'];
            $upc = $replenishLocation['upc'];

            if (getDefault($replenishInventory[$vendorID][$upc])) {

                $location = $replenishLocation['location'];
                $requested = $replenishLocation['supplement'];
                $available = $replenishInventory[$vendorID][$upc];

                $quantity = min($requested, $available);

                $replenishInventory[$vendorID][$upc] -= $quantity;

                $return[$vendorID][] = [
                    'client' => $replenishLocation['vendor'],
                    'upc' => $upc,
                    'mezzanineLocation' => $location,
                    'pieces' => $quantity
                ];
            }
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    function getReplenishInventory()
    {
        $cartons = new \tables\inventory\cartons($this->app);

        $upcParams = [];

        foreach ($this->locationsToReplenish as $replenishLocation) {

            $vendorID = $replenishLocation['vendorID'];
            $upc = $replenishLocation['upc'];

            $upcParams[$vendorID][$upc] = TRUE;
        }

        foreach ($upcParams as $key => $values) {
            $upcParams[$key] = array_keys($values);
        }

        // only NOT Mezzanine inventory will be selected
        $isMezzanine = FALSE;

        return $cartons->getUPCQuantity($upcParams, $isMezzanine);
    }

    /*
    ****************************************************************************
    */

    function addLocations($vendorID, $upcIDs)
    {
        foreach ($upcIDs as $upcID => $values) {
            foreach ($this->locationsToReplenish as &$replenishLocation) {
                if ($replenishLocation['vendorID'] == $vendorID
                 && $replenishLocation['upcID'] == $upcID) {

                    $replenishLocation['locID'] = $values['locID'];
                    $replenishLocation['location'] = $values['location'];

                    $this->removeLocatedKeys($vendorID, $upcID);

                    continue 2;
                }
            }
        }
    }

    /*
    ****************************************************************************
    */

    function removeLocatedKeys($vendorID, $upcID)
    {
        foreach ($this->upcIDParams[$vendorID] as $key => $value) {
            if ($value != $upcID) {
                continue;
            }
            // remove upcID which location was found
            unset($this->upcIDParams[$vendorID][$key]);

            if (! $this->upcIDParams[$vendorID]) {
                // remove vendor if no upcIDs are left
                unset($this->upcIDParams[$vendorID]);
            }

            return;
        }
    }

    /*
    ****************************************************************************
    */

    function reportTransfers($transferIDs, $toolTransfer)
    {
        $getRecipient = $this->getRecipientInfo();

        foreach ($transferIDs as $batchID => $transferID) {

            $printedWavePicks = self::$dir . '/transferWavePicks_'
                . $transferID . '_' . date('Y-m-d-H-i-s') . '.pdf';

            $printedUCCLabels = $files = self::$dir . '/transferUCCLabels_'
                . $transferID . '_' . date('Y-m-d-H-i-s') . '.pdf';

            $toolTransfer->pdfOutput($transferID, $printedWavePicks);

            create::transferCartonsLabels($this->app, $transferID, $files);

            $subject = '[Transfer Mezzanine Online Order] BatchID: '
                . $batchID  . ' TransferID: ' ;

            $text = '[Transfer Mezzanine Online Order] BatchID: ' . $batchID
                . ' TransferID: ';

            foreach ($getRecipient as $value) {

                \PHPMailer\send::mail( [
                    'recipient' => $value['email'],
                    'subject' => $subject,
                    'body' => $text,
                    'files' => [$printedWavePicks, $printedUCCLabels]
                ]);

                self::$logs[] = '[Transfer Mezzanine Online Order] BatchID: '
                    . $batchID . ' ' . $value['email'];
            }
        }
    }

    /*
    ****************************************************************************
    */

    function createOrderWavePicks($batchData, $onlineOrders)
    {
        $inventoryWavePicks = new \inventory\wavePicks($this->app);

        $emailedReports = [];

        $userWavePick = $this->getUserWavePick();

        $batchIDs = array_keys($batchData);
        $dataErrors = $onlineOrders->checkWavePick($batchIDs);

        foreach ($batchData as $batchID => $reportID) {

            $userInfo = getDefault($userWavePick[$reportID]);
            $dataError = getDefault($dataErrors[$batchID]);

            if (! $userInfo) {
                self::$logs[] = 'Email regarding Online Order Wave Pick for '
                        . 'Batch # ' . $batchID . ' was not sent due to '
                        . 'missing email address';
                continue;
            }

            $fiels = NULL;

            if (! $dataError) {
                $fiels[] = $batchWavePick = self::$dir.'/BatchWavePicks_'
                        . $batchID . '_' . date('Y-m-d-H-i-s') . '.pdf';

                $inventoryWavePicks->createWavePickPDF(NULL, $batchID, $batchWavePick);
            }

            $text = $fiels ? 'Wave Pick is available' :
                    'Wave Pick was not created due to lack of inventory';

            foreach ($userInfo as $value) {

                \PHPMailer\send::mail([
                    'recipient' => $value['email'],
                    'subject' => 'Wave Pick for Batch # ' . $batchID,
                    'body' => $text,
                    'files' => $fiels
                ]);

                self::$logs[] = 'Emailing Online Order Wave Pick for Batch # '
                        . $batchID . ' to ' . $value['email'];
            }

            $emailedReports[] = $reportID;
        }

        return $emailedReports;
    }

    /*
    ****************************************************************************
    */

    function getTransferBatches()
    {
        $sql = 'SELECT    primeKey,
                          reportID
                FROM      reports_data rd
                JOIN      reports r ON r.id = rd.reportID
                JOIN      statuses s ON rd.statusID = s.id
                WHERE     s.shortName = "' . self::STATUS_TRANSFER . '"
                AND       category = "reports"
                AND       NOT isSent
                ';

        $results = $this->app->queryResults($sql);

        $return = [];

        foreach ($results as $key => $value) {
            $return[$key] = $value['reportID'];
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

}
