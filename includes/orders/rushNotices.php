<?php

namespace orders;

class rushNotices
{
    const DAY_DELAY = 2;
    
    const ROW_AMOUNT = 42;
    
    const ROW_HEIGHT = 6;    

    public $db;
    
    public $title;

    public $logs = [];

    public $files = [];
    
    public $pdf;

    public $pageCount = 1;
    
    public $rowCount = 0;
    
    public $clientName;
    
    /*
    ****************************************************************************
    */
    
    function __construct($db)
    {
        $this->db = $db;
    }
        
    /*
    ****************************************************************************
    */
    
    static function send($db)
    {
        $self = new static($db);
        $self->main();
        return $self->logs;
    }
    
    

    /*
    ****************************************************************************
    */
    
    function main()
    {
        $emailString = $this->getManagerEmails();

        if (! $emailString) {
            return $this->logs[] = 'No Warehouse Managers Addresses Found';
        }
        
        $shipping = new \tables\dashboards\shipping($this->db);

        $results = $shipping->getPendingOrdersData(self::DAY_DELAY);

        if (! $results) {
            return $this->logs[] = 'No Rush Orders';
        }
            
        $ordersByClient = [];
        
        foreach ($results as $row) {
            $vendorID = $row['vendorID'];
            $ordersByClient[$vendorID][] = $row;
        }
        
        $this->title = 'Orders that have less than 48 hours to be shipped by '
                .date('l, m/d/Y h:i:s a');

        foreach ($ordersByClient as $vendorID => $orders) {
            $this->clientOrders($vendorID, $orders);
        }
        
        \PHPMailer\send::mail([
            'recipient' => $emailString,
            'subject' => $this->title,
            'body' => 'Rush orders:',
            'files' => $this->files,
        ]); 
    }
    
    

    /*
    ****************************************************************************
    */
    
    function clientOrders($vendorID, $orders)
    {
        $this->pdf = new \pdf\creator();

        $this->pdf->setStoredAttr('length', self::ROW_HEIGHT);
        $this->pdf->setStoredAttr('stretch', 1);

        // remove horizontal line at the top of the page.
        $this->pdf->setPrintHeader(FALSE);

        $this->pageCount = 1;
        $this->rowCount = 0;

        foreach ($orders as $row) {
            $this->addOrder($row);
        }

        $this->files[] = $this->attachment($this->pdf, $vendorID, $this->clientName);    
    }

    /*
    ****************************************************************************
    */
    
    function addOrder($row)
    {
        $this->clientName = trim($row['clientName']);
        
        $this->logs[] = 'Sending notice for Client: '.$this->clientName
            . ' Order: '.$row['scanordernumber'];

        $newPage = $this->rowCount % self::ROW_AMOUNT == 0;

        if ($newPage) {

            $this->rowCount = 0;

            $this->pdf->AddPage();

            $this->pdf->Ln();

            $this->pdf->SetFont('helvetica', '', 11);

            $this->pdf->setStoredAttr('border', 0);

            $this->pdf->htmlCell([
                'width' => 180,
                'text' => $this->clientName,
                'align' => 'L',
            ]);

            $this->pdf->htmlCell([
                'width' => 10,
                'text' => $this->pageCount++,
                'align' => 'R',
            ]);

            $this->pdf->Ln();

            $this->pdf->htmlCell([
                'width' => 190,
                'text' => $this->title,
                'align' => 'C',
            ]);

            $this->pdf->Ln();

            $this->pdf->SetFont('helvetica', '', 12);

            $this->pdf->Ln();

            $this->pdf->setStoredAttr('border', 1);

            $this->pdf->htmlCell([
                'width' => 125,
                'text' => 'Customer',
                'align' => 'C',
            ]);

            $this->pdf->htmlCell([
                'width' => 35,
                'text' => 'Order Number',
                'align' => 'C',
            ]);

            $this->pdf->htmlCell([
                'width' => 30,
                'text' => 'Cancel Date',
                'align' => 'C',
            ]);

            $this->pdf->Ln();

            $this->rowCount += 4;
        }

        $this->pdf->setStoredAttr('border', 1);

        $this->pdf->htmlCell([
            'width' => 125,
            'text' => trim($row['customerName']),
            'align' => 'L',
        ]);

        $this->pdf->htmlCell([
            'width' => 35,
            'text' => $row['scanordernumber'],
            'align' => 'C',
        ]);

        $this->pdf->htmlCell([
            'width' => 30,
            'text' => $row['cancelDate'],
            'align' => 'C',
        ]);

        $this->pdf->Ln();

        $this->rowCount++;
    }

    /*
    ****************************************************************************
    */
    
    function getManagerEmails()
    {
        $userDB = $this->db->getDBName('users');
        
        // This needs to go in its own class later
        $sql = 'SELECT u.email
                FROM   user_groups ug
                JOIN   '.$userDB.'.info u ON u.id = ug.userID
                JOIN   groups g ON g.id = ug.groupID
                WHERE  g.groupName = "Warehouse Managers"';
        
        $results = $this->db->queryResults($sql);
        
        $emails = array_keys($results);
        
        $emailString = implode(';', $emails);
        
        return $emailString;
    }
    
    /*
    ****************************************************************************
    */

    function attachment($pdf, $vendorID, $clientName)
    {
        $clientNameNoSpace = str_replace(' ', '_', $clientName);

        $uploadDir = \models\directories::getDir('uploads', 'shippingDashboard');
        
        $file = $uploadDir . '/Client_' . $vendorID . '_' . $clientNameNoSpace 
                . '_Orders_Pending_' . date('Ymd_his_a') . '.pdf';
       
        $pdf->output($file, 'F');
        
        return $file;
    }

    /*
    ****************************************************************************
    */
}