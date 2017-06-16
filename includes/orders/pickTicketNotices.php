<?php

namespace orders;

class pickTicketNotices
{
    const ROW_AMOUNT = 48;

    const ROW_HEIGHT = 6;

    const COLUMN_AMOUNT = 4;

    const PAGE_WIDTH = 190;
    static $pdf = NULL;


    static $columsWidth = 0;

    static $pageCount = 1;

    static $rowCount = 0;

    static $mid = 0;

    static $reportOrders = [];

    static $emailedOrders = [];

    static $dir = NULL;

    static $files = [];

    static $logs = [];

    /*
    ****************************************************************************
    */

    static function sendEmails($app)
    {
        self::$dir = \models\directories::getDir('uploads', 'scanOrders');

        if (! file_exists(self::$dir)) {
            self::$logs[] = 'Directory '.self::$dir.' does not exist!';
            return self::$logs;
        }

        self::getUnreported($app);

        if (! self::$emailedOrders) {
            self::$logs[] = 'No invalid Pick Ticket notices were found';
            return self::$logs;
        }

        self::forEachOrder();

        self::updateSentStatus($app);

        \common\report::recordReportsSent($app, self::$emailedOrders, 'INPT');

        return self::$logs;
    }

    /*
    ****************************************************************************
    */

    static function updateSentStatus($app)
    {
        $qMark = $app->getQMarkString(self::$emailedOrders);
        $sql = 'UPDATE neworder
                SET    reprintPickTicket = 0
                WHERE  id IN (' . $qMark . ')';

        $app->runQuery($sql, self::$emailedOrders);
    }

    /*
    ****************************************************************************
    */

    static function getUnreported($app)
    {
        // Get a list of orders that have not been reported yet

        $userDB = $app->getDBName('users');

        $sql = 'SELECT    CONCAT_WS("-", u.id, n.id) AS id,
                          scanordernumber,
                          u.id AS userID,
                          u.username,
                          n.id AS orderID,
                          v.id AS vendorID,
                          vendorName,
                          u.email
                FROM      neworder n
                JOIN      order_batches b ON b.id = n.order_batch
                JOIN      vendors v ON v.id = b.vendorID
                JOIN      warehouses w ON v.warehouseID = w.id
                JOIN      client_users cu ON cu.vendorID = v.id
                JOIN      '.$userDB.'.info u ON u.id = cu.userID
                JOIN      statuses s ON s.id = u.employer
                JOIN      users_access a ON u.id = a.userID
                JOIN      user_levels l ON l.id = a.levelID
                WHERE     u.email IS NOT NULL
                AND       u.active
                AND       cu.active
                AND       reprintPickTicket
                AND       category = "employers"
                AND       s.displayName = "Seldat"
                ORDER BY  vendorName ASC,
                          scanordernumber ASC
                ';

        $results = $app->queryResults($sql);

        $orderIDs = [];

        foreach ($results as $scanordernumber => $result) {

            $vendorName = $result['vendorName'];
            $scanordernumber = $result['scanordernumber'];
            $email = $result['email'];

            self::$reportOrders[$email]['orders'][$vendorName][] = $scanordernumber;
            self::$reportOrders[$email]['user'] = [
                'userID' => $result['userID'],
                'username' => $result['username'],
            ];

            $orderID = $result['orderID'];

            $orderIDs[$orderID] = TRUE;

            self::$logs[] = 'Invalid Pick Tickets for Client: '.$vendorName.', '
                .'Order # '.$scanordernumber.' sent to '.$email;
        }

        self::$emailedOrders = array_keys($orderIDs);
    }

    /*
    ****************************************************************************
    */

    static function forEachOrder()
    {
        $time = date('l, m/d/Y h:i:s a');

        foreach (self::$reportOrders as $email => $reportOrders) {

            self::userOrders($reportOrders);

            \PHPMailer\send::mail([
                'recipient' => [$email],
                'subject' => 'Orders with Pick Ticket discrepancies by '.$time,
                'body' => 'The following orders have invalid Pick Tickets',
                'files' => self::$files,
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    static function userOrders($reportOrders)
    {
        self::$pdf = new \pdf\creator();

        $userID = $reportOrders['user']['userID'];
        $userName = $reportOrders['user']['username'];

        self::$pdf->SetFont('helvetica', '', 12);

        self::$pdf->setStoredAttr('border', 0);
        self::$pdf->setStoredAttr('length', self::ROW_HEIGHT);
        self::$pdf->setStoredAttr('stretch', 1);

        // remove horizontal line at the top of the page.
        self::$pdf->setPrintHeader(FALSE);

        self::$columsWidth = intval(self::PAGE_WIDTH / self::COLUMN_AMOUNT);

        foreach ($reportOrders['orders'] as $vendorName => $vendorOrders) {

            self::$pageCount = 1;
            self::$rowCount = 0;

            $amount = count($vendorOrders);

            self::$mid = ceil($amount / self::COLUMN_AMOUNT);
            $orderRowAmount = ceil($amount / self::COLUMN_AMOUNT);

            for ($count = 0; $count < $orderRowAmount; $count++) {
                self::addOrder($vendorName, $vendorOrders, $count);
            }
        }

        self::attachment($userID, $userName);
    }

    /*
    ****************************************************************************
    */

    static function addOrder($vendorName, $vendorOrders, $count)
    {
        $newPage = self::$rowCount % self::ROW_AMOUNT == 0;

        if ($newPage) {

            self::$rowCount = 0;

            self::$pdf->AddPage();

            self::$pdf->htmlCell([
                'width' => self::PAGE_WIDTH - 10,
                'text' => $vendorName,
                'align' => 'L',
            ]);

            self::$pdf->htmlCell([
                'width' => 10,
                'text' => self::$pageCount++,
                'align' => 'R',
            ]);

            self::$pdf->Ln();

            self::$pdf->htmlCell([
                'width' => self::PAGE_WIDTH,
                'text' => 'Orders with invalid Pick Tickets by '.date('l, m/d/Y h:i:s a'),
                'align' => 'L',
            ]);

            self::$pdf->Ln();

            self::$pdf->Ln();

            self::$rowCount += 3;
        }

        for ($index = 0; $index < self::COLUMN_AMOUNT; $index++) {

            $key = self::$mid * $index + $count;

            if (isset($vendorOrders[$key])) {

                $orderNumber = $vendorOrders[$key];

                self::$pdf->htmlCell([
                    'width' => self::$columsWidth,
                    'text' => $orderNumber,
                    'align' => 'L',
                ]);
            }
        }

        self::$pdf->Ln();

        self::$rowCount++;
    }

    /*
    ****************************************************************************
    */

    static function attachment($userID, $userName)
    {
        $file = self::$dir.'/User_'.$userID.'_'.$userName.'_Invalid_Pick_Tickets'
                .date('Y-m-d-H-i-s').'.pdf';

        self::$pdf->output($file, 'F');

        self::$files = [$file];
    }

    /*
    ****************************************************************************
    */
}
