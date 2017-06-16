<?php

namespace common;

class pdf
{
    static function download($filePath, $fileName)
    {
        $file = $filePath . '/' . $fileName;

        $fsize = filesize($file);
        
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . $fsize);

        readfile($file);        
    }
    
    /*
    ****************************************************************************
    */
    
    static function myMultiCell($pdf, $width, $height, $text=NULL, $border = 1,
        $align = 'C', $stretch = 0, $fill=FALSE)
    {
        if ($stretch) {
            // use cell function to avoid word wrap
            $pdf->Cell($width, $height, $text, $border, 0, $align, $fill, '',
                $stretch, FALSE, 'T', 'M');
        } else {
            $pdf->MultiCell($width, $height, $text, $border, $align, $fill, 0, 
                '', '', TRUE, 0, FALSE, FALSE, $height, 'T', FALSE);
        }
    }
    
   /*
   ****************************************************************************
   */
    
    static function radio($pdf, $width, $selected=FALSE, $border=1)
    {
        //Get current write position.
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->RadioButton('radio', $width, [], [], NULL, $selected);
        
        if ($border) {
            //Reset X,Y so wrapping cell wraps around the barcode's cell.
            $pdf->SetXY($x,$y);

            self::myMultiCell($pdf, $width, $width);            
        }   
    }
    
   /*
   ****************************************************************************
   */
    
    static function checkbox($pdf, $width, $selected=FALSE, $border=1)
    {
        //Get current write position.
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->CheckBox('radio', $width, $selected);
        
        if ($border) {
            //Reset X,Y so wrapping cell wraps around the barcode's cell.
            $pdf->SetXY($x,$y);

            self::myMultiCell($pdf, $width, $width);            
        }   
    }
    
   /*
   ****************************************************************************
   */
    
    static function getUCCLabelsDownloadName($firstBatch, $lastBatch=NULL)
    {
        if (is_array($firstBatch)) {
            $lastBatch = $firstBatch['lastBatch'];
            $firstBatch = $firstBatch['firstBatch'];
        }

        return 'Batch_' . $firstBatch . '_To_' . $lastBatch . '_UCC_Labels_'
                . date('Y-m-d-H-i-s') . '.pdf';
    }
    
    /*
    ****************************************************************************
    */
    
}
