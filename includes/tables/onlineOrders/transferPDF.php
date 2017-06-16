<?php

namespace tables\onlineOrders;
use common\pdf;
use labels\create;

class transferPDF 
{
    private $app;

    private $rowHeight = 5;

    private $pageLength = 12;

    /*
    ****************************************************************************
    */
    
    function __construct($app)
    {
        $this->app = $app;
    }

    /*
    ****************************************************************************
    */
    
    function createTransferPDF($data, $file)
    {
        $outputType = $file ? 'F' : 'I';
        $pdfOutput = $file ? $file : 'pdf';
        
        $rowHeight = $this->rowHeight * 3;
        
        $pdf = new \TCPDF('P', 'mm', 'Letter', TRUE, 'UTF-8', FALSE);

        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);
        $pdf->SetAutoPageBreak(TRUE, 0);
        $pdf->SetLeftMargin(10);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        
        $pdf->AddPage();

        $text = 'TransferID: #' . $data['transfer']['id'] . ' - ' 
              . 'Created: ' . $data['transfer']['createDate'];

        pdf::myMultiCell($pdf, 185, $this->rowHeight, $text, 0, 'L');
        pdf::myMultiCell($pdf, 15, $this->rowHeight, '1', 0, 'R');
                
        $pdf->Ln();

        $barcode = $data['transfer']['barcode'];
        
        $style = [
            'text' => $barcode,
            'padding' => 1,
            'fontsize' => 10,
            'label' => $barcode,
        ];

        $pdf->write1DBarcode($barcode, 'C128', '', '', 70, $rowHeight - 5, 
                0.4, $style, 'T', FALSE);

        pdf::myMultiCell($pdf, 50, $rowHeight, NULL, 0);

        $pdf->Ln();

        pdf::myMultiCell($pdf, 55, $rowHeight, 'Client' . "\n"
            . 'UPC (Pieces)' . "\n" . 'Mezzanine Location');
        pdf::myMultiCell($pdf, 50, $rowHeight, "\n" . 'UCC128 (New)');
        pdf::myMultiCell($pdf, 15, $rowHeight, "\n" . 'Pieces');
        pdf::myMultiCell($pdf, 50, $rowHeight, 'Cartons (Target)' . "\n"
            . 'SKU' . "\n" . 'Color');
        pdf::myMultiCell($pdf, 30, $rowHeight, "\n" . 'Locations');

        $pdf->Ln();
        // 2 = barcode + table header
        $rowCount = 2;
        $pageCount = 1;

        foreach ($data['rows'] as $targetCarton => $targetInfo) {

            $splitCartons = $targetInfo['uCC128Children'];

            if ($rowCount == $this->pageLength) {

                $rowCount = 0;

                $pdf->AddPage();

                pdf::myMultiCell($pdf, 200, $this->rowHeight, ++$pageCount, 0, 'R');

                $pdf->Ln();            
            }

            $text = $targetInfo['clientName'] .  "\n" . $targetInfo['upc'] . ' ('
                . $targetInfo['pieces'] . ')' . "\n" . $targetInfo['newLoc'];

            pdf::myMultiCell($pdf, 55, $rowHeight, $text);

            $text = count($splitCartons) == 1 ? current($splitCartons) : 
                current($splitCartons) . "\n\n" . end($splitCartons);

            pdf::myMultiCell($pdf, 50, $rowHeight, $text);

            if (count($splitCartons) > 1) {

                $arrowTop = ($rowCount + 1)*$rowHeight + $this->rowHeight;

                $pdf->Arrow(90, $arrowTop, 90, $arrowTop + 5, 2, 2, 15);
            }

            pdf::myMultiCell($pdf, 15, $rowHeight, $targetInfo['pieces']);

            $text = $targetInfo['targetUCC128'] . "\n" . $targetInfo['sku']
                . "\n" . $targetInfo['color'];

            pdf::myMultiCell($pdf, 50, $rowHeight, $text);
            pdf::myMultiCell($pdf, 30, $rowHeight, $targetInfo['oldLoc']);


            $rowCount++;

            $pdf->Ln();
        }

        pdf::myMultiCell($pdf, 105, $this->rowHeight, 'Total Pieces');        
        pdf::myMultiCell($pdf, 15, $this->rowHeight, $data['total']);
        pdf::myMultiCell($pdf, 80, $this->rowHeight, NULL);
        
        $pdf->Output($pdfOutput, $outputType);
        
        
    }

    /*
    ****************************************************************************
    */
    
    public function createUCCLabels($pdfData,$transfer, $file)
    {   
        $this->transferCartonsLabels($pdfData, $transfer,$file);
    }

    /*
    ****************************************************************************
    */

    function transferCartonsLabels($pdfData, $files = NULL)
    {
        $cartonIDs = [];
        foreach ($pdfData as $targetCarton) {
            $cartonIDs += $targetCarton['uCC128Children'];
        }
       
        $results = $this->getCartonsInformation($cartonIDs);

        $this->generateOutputPdf($results, $files);
    }

    /*
    ****************************************************************************
    */

    function makeRemainingCartonLabels($pdfData, $files = NULL)
    {
        $cartonIDs = [];
        foreach ($pdfData as $targetCarton) {
            $cartonIDs[] = $targetCarton['targetID'];
        }

        $qMarks = $this->app->getQMarkString($cartonIDs);
        $sql = 'SELECT ssp.childID
                FROM inventory_splits sp
                JOIN inventory_splits ssp ON ssp.parentID = sp.parentID
                JOIN inventory_cartons ca ON ca.id = ssp.childID
                JOIN locations l ON l.id = ca.locID
                WHERE sp.childID IN (' . $qMarks . ')
                AND ! ca.isSplit
                AND ! l.isMezzanine
                AND ssp.active';

        $remainingIDs = $this->app->queryResults($sql, $cartonIDs);

        if ($remainingIDs) {
            $results = $this->getCartonsInformation($remainingIDs);
            $this->generateOutputPdf($results, $files);
        }
    }

    /*
    ****************************************************************************
    */

    function generateOutputPdf($results, $files)
    {
        $save = $files ? TRUE : FALSE;

        $concat = $files ? FALSE : TRUE;
        create::$transferCarton = TRUE;
        create::forCartons([
            'db' => $this->app,
            'labels' => $results,
            'splitCarton' => FALSE,
            'save' => $save,
            'concat' => $concat,
            'files' => $files,
            'isDownload' => FALSE
        ]);
    }

    /*
    ****************************************************************************
    */

    function getCartonsInformation($cartonIDs)
    {
        $cartonIDs = array_keys($cartonIDs);
        $qMarks = $this->app->getQMarkString($cartonIDs);
        $sql = 'SELECT    ca.id,
                          ca.cartonID,
                          u.upc AS upc,
                          CONCAT(
                            co.vendorID,
                            b.id,
                            LPAD(ca.uom,3, 0),
                            LPAD(ca.cartonID, 4, 0)
                          ) AS ucc,
                          u.sku,
                          color,
                          size,
                          co.vendorID,
                          LPAD(ca.uom, 3, 0) AS uom,
                          b.id AS batchID,
                          l.displayName AS location,
                          b.upcID,
                          co.name AS container
                FROM      inventory_cartons ca
                JOIN      inventory_batches b ON b.id = ca.batchID
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      statuses s ON s.id = ca.statusID
                JOIN      upcs u ON u.id = b.upcID
                JOIN      locations l ON l.id = ca.locID
                WHERE     ca.id IN ('.$qMarks.')
                ORDER BY  b.id ASC,
                          ca.id ASC
                ';

        $results = $this->app->queryResults($sql, $cartonIDs);

        return $results;
    }

    /*
    ****************************************************************************
    */
}
    

