<?php

namespace tables\onlineOrders;
use models\directories;


class emailCron
{    
    private $app;
    private $query;
    
    /*
    ****************************************************************************
    */
    
    function __construct($app)
    {
        $this->app = $app;
        $this->query = new emailCronQuery($this->app);
    }
    
    /*
    ****************************************************************************
    */
    
    public function run($transferInfo = [])
    {
        if (! $transferInfo) {
            return;
        }

        $this->processDataTransfer($transferInfo);
    }
    
    /*
    ****************************************************************************
    */

    public function processDataTransfer($transfer)
    {
        $data = $transfer;
       
        if (! $data) {
            return;
        }
        
        $transferID = $data['transferID'];
        $vendorID = $data['vendorID'];
        $transferCartons = $data['transferCartons'];

        if (! $transferCartons) {
            return;
        }
        
        $emails = $this->query->getMezzanineAdminEmail($vendorID);
        
        $clientName = $this->query->getClienName($vendorID);
        
        $transfer = $this->getTransferInfo($transferID);

        $pdfData = [
            'rows' => [],
            'transfer' => $transfer,
        ];
        
        $total = 0;
        $labelPDFs = [];

        foreach ($transferCartons as $upc => $targetCartons) {
            $upcData = $this->processUPC($upc, $targetCartons, $clientName);
            $total += $upcData['total'];
            $pdfData['rows'] = array_merge($pdfData['rows'], $upcData['rows']);
            //create labelPDF for each upc
            $labelPDFs[$upc] = $upcData['rows'];
        }
        
        $pdfData['total'] = $total;

        $pdfData['labelPDFs'] = $labelPDFs;

        $this->sendEmail($emails, $pdfData);
        
        return $pdfData;
    }

    /*
    ****************************************************************************
    */

    public function processUPC($upc, $targetCartons, $clientName)
    {
        $transferRows = [];
        $total = 0;
        foreach ($targetCartons as $targetID => $target) {
            $info = $target['info'];
            $uCC128Children = 
                    $this->processUCC128Children($target['children']);
            
            $oldLocationName = 
                    $this->query->getLocationName($info['oldLocID']);
            $newLocationName = 
                    $this->query->getLocationName($info['newLocID']);
            
            $transferredRow = [
                'oldLoc' => $oldLocationName,
                'newLoc' => $newLocationName,
                'targetUCC128' => $this->query->createUCC128($info['targetID']),
                'targetID' => $info['targetID'],
                'pieces' => $info['pieces'],
                'uCC128Children' => $uCC128Children,
                'upc' => $info['upc'],
                'sku' => $info['sku'],
                'color' => $info['color'],
                'clientName' => $clientName
            ];
            
            $transferRows[] = $transferredRow;
            $total += $info['pieces'];
        }
        
        $results =  [
            'total' => $total,
            'rows' => $transferRows
        ];
        
        return $results;
    }
    
    /*
    ****************************************************************************
    */

    public function processUCC128Children($childCartonIDs)
    {
        $results = [];
        
        foreach ($childCartonIDs as $cartonID) {
            $results[$cartonID] = $this->query->createUCC128($cartonID);
        }
        
        return $results;
    }
    
    /*
    ****************************************************************************
    */

    public function getTransferInfo($transferID)
    {
        return $this->query->getTransferByID($transferID);
    }
    
    /*
    ****************************************************************************
    */

    public function sendEmail($emails, $pdfData)
    {
        $dir = directories::getDir('uploads', 'transfers');

        if (! file_exists($dir)) {

            //self::$logs[] = 'Directory '.$dir.' does not exist!';

            return FALSE;
        }

        if (! $emails) {
            return FALSE;
        }

        $filePDF = $dir . '/printedWavePicks' .
                $pdfData['transfer']['id'] . '_' . date('Y-m-d-H-i-s') . '.pdf';
        
        $transferPDF = new transferPDF($this->app);
        $transferPDF->createTransferPDF($pdfData, $filePDF);

        $attachFile = $this->createLabelPDF($pdfData, $dir);
        $attachFile[] = $filePDF;

        $subject = '[Transfer Mezzanine Online Order] BatchID: TransferID: ' .
            $pdfData['transfer']['id'];

        foreach ($emails as $email) {
            \PHPMailer\send::mail( [
                'recipient' => $email,
                'subject' => $subject,
                'body' => $subject,
                'files' => $attachFile
            ]);
        }
    }

    /*
    ****************************************************************************
    */
    
    public function createLabelPDF($pdfData, $dir)
    {
        $transferID = $pdfData['transfer']['id'];
        $transferPDF = new transferPDF($this->app);
        $successFiles = [];

        foreach ($pdfData['labelPDFs'] as $upc => $row) {

            $file = $dir . '/transferUCCLabels_' . $upc . '_'
                . $transferID . '_' . date('Y-m-d-H-i-s') . '.pdf';

            $reMainingFile = $dir . '/remainingUCCLabels_' . $upc . '_'
                . $transferID . '_' . date('Y-m-d-H-i-s') . '.pdf';


            $successFiles[] = $file;
            $successFiles[] = $reMainingFile;

            $transferPDF->transferCartonsLabels($row, $file);

            $transferPDF->makeRemainingCartonLabels($row, $reMainingFile);
        }

        return $successFiles;
    }

    /*
    ****************************************************************************
    */
}