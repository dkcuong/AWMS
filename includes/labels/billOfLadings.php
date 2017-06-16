<?php

namespace labels;

class billOfLadings extends \tcpdf
{

    /*
    ****************************************************************************
    */

    function writePDFPage($pdf)
    {
        $pdf->setPrintHeader(FALSE);
        $pdf->setPrintFooter(FALSE);  
        $pdf->SetAutoPageBreak(TRUE, 0);     
        $pdf->setTopMargin(12);
        $pdf->SetLeftMargin(5);
        $pdf->setCellPaddings(0, 3, 0, 0 );        
        $pdf->AddPage();
        
        return $pdf;
    }
        
    /*
    ****************************************************************************
    */
    
    function addBillOfLadingLabels($params)
    {
        $db = getDefault($params['db']);        
        $term = getDefault($params['term']);
        $search = getDefault($params['search']);
        $orderBy = NULL;
        switch ($search){
            case 'billOfLadingLabels':
                $clause = ' LPAD(
                                 CONCAT(userID, assignNumber),
                                 10, 0
                             )';
                break;
            case 'dateEntered':
                $clause = 'DATE(dateEntered)';
                break;
            case 'batch':
                $clause = $search;;
                break;
            default:

                die('Invalid Search');
        }

        $finalTerm = is_array($term) ? $term : [$term];
        $finalClause = is_array($term) ?
        $clause . ' IN (' . $db->getQMarkString($term) . ')' : $clause . ' = ?';

        $table = 'billOfLadings';

        $title = 'Bill Of Ladings';
        $sql = 'SELECT  LPAD(
                         CONCAT(userID, assignNumber),
                         10, 0
                     ) AS barcode,
                     DATE(dateEntered) AS date
             FROM    ' . $table . '
             WHERE   ' . $finalClause;

        $results = $db->queryResults($sql, $finalTerm);

        if (! $results) {
            die('Do not have data.');
        }
      
        $pdf = new \TCPDF('P', 'mm', 'Letter', true, 'UTF-8', false);
        
        $this->writePDFPage($pdf);
        
        $count = 0;
        
        $total = count($results);
     
        $pageAmount = $total / 10;
        
        $pageCount = 1;

        foreach ($results as $barcode => $row) {
          
            $date = $row['date'];
            
            for ($i = 0; $i <= 2; $i++) {   
                $txt = $title."\n".$date."\n".$barcode;
              
                $this->writeBarcodes($pdf, $barcode, $txt);               
            }
            $count++;
            
            if ($count % 10 == 0) {            
                if ($pageCount < $pageAmount) {

                    $pdf->AddPage();                            
                }
                
                $pageCount++;
                
            } else {
                $pdf->Ln();
            }
        }
        
        $pdf->Output($title . '_' . $barcode . 'pdf','I');
        
        return $pdf;
    }
    /*
    ****************************************************************************
    */   
    
    function pdfStyle ()
    {
        $style = [
            'position' => '',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => FALSE,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255),
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 7,
        ];
        
        return $style;
        
    }
    
    /*
    ****************************************************************************
    */
    
    function writeBarcodes ($pdf, $barcode, $txt)
    {
        $x = $pdf->GetX();
        $y = $pdf->GetY();            
        $pdf->write1DBarcode($barcode, 'C128', $x+13, $y+14, 40, 16, 0.4, $this->pdfStyle(), 'N', $showCode=FALSE);
        //reset X Y for Cell
        $pdf->SetXY($x,$y);  

        /*MultiCell ($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='',
            $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)*/ 
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(68, 25.5, $txt, 0, 'C', 0, 0, '', '', true, 0, false, true, 0);
    }
}
