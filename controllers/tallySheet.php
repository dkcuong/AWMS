<?php

class controller extends template
{   
   
    
    function infoTallySheetController()
    {

        foreach ($this->checkboxes as $name) {    
            $this->checks[$name] = isset($this->post[$name]) ? ' checked ' : NULL;
        }
        
        if (isset($this->post['Submit'])) {
                    
            $nextID = $this->getNextTallyID();

            $startDate = $this->post['startDate'];
            $cancelDate = $this->post['cancelDate'];
            $complete = getDefault($this->post['complete']);
            $client = $this->post['client'];
            $incomplete = getDefault($this->post['incomplete']);
            $customer = $this->post['customer'];
            $workOrder = getDefault($this->post['workOrder']);
            $poNumber = $this->post['poNumber'];
            $workOrderProcessing = getDefault($this->post['workOrderProcessing']);
            $processing = getDefault($this->post['processing']);
            $cartonsPick = $this->post['cartonsPick'];
            $cartonsWork = $this->post['cartonsWork'];
            $cartonsProcess = $this->post['cartonsProcess'];
            $backToStock =$this->post['backToStock'];
            $palletNumber = $this->post['palletNumber'];
            $palletTotal = $this->post['palletTotal'];
            $ready = getDefault($this->post['ready']);
            $dc = $this->post['dc'];
            $backToStockProcess = $this->post['backToStockProcess'];
            $currentDate = $this->post['date'];     
            $cartons = $this->post['cartons'];
            $location = $this->post['location'];
            $name = $this->post['name'];
           
            $sql = 'INSERT INTO tally_sheets(
                                            id,
                                            startDate,
                                            cancelDate,
                                            client,
                                            customer,
                                            poNumber,
                                            cartonsPick,
                                            cartonsWork,
                                            cartonsProcess,
                                            palletNumber,
                                            palletTotal,
                                            dc,
                                            complete,
                                            incomplete,
                                            workOrder,
                                            processing,
                                            forProcessing,
                                            backToStock,
                                            readyToShip,
                                            backToStockProcess,
                                            cartons,
                                            location,
                                            currentDate,
                                            name
                                            )
                    VALUES      (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                                ?, ?, ?, ?)';  

            $this->runQuery($sql, [  
                            $nextID,
                            $startDate, 
                            $cancelDate,                                              
                            $client,
                            $customer,
                            $poNumber,                
                            intval($cartonsPick), 
                            intval($cartonsWork), 
                            intval($cartonsProcess),
                            intval($palletNumber), 
                            intval($palletTotal), 
                            $dc, 
                            intval($complete),
                            intval($incomplete),                                             
                            intval($workOrder),
                            intval($workOrderProcessing),
                            intval($processing),                                                 
                            $backToStock, 
                            $ready,                                             
                            $backToStockProcess,
                            $cartons,
                            $location,
                            $currentDate,
                            $name
                            ]); 

            $sql = 'UPDATE tally_sheet_pallets 
                    SET    active = 0
                    WHERE  tallyID = ?';

            $this->runQuery($sql, [$nextID]); 

            foreach ($this->post['pallet'] as $index => $pallet) {

                if (! intval($pallet)) {
                    continue;                    
                }

                $sql = 'INSERT INTO tally_sheet_pallets (
                            tallyID,
                            noteCellID,
                            notes,
                            active
                        ) VALUES (?, ?, ?, 1)
                        ON DUPLICATE KEY UPDATE
                            notes = VALUES(notes),
                            active = 1';  

                $this->runQuery($sql, [ 
                    $nextID,
                    $index,
                    intval($pallet)
                ]); 
            }

        }
        
        if(isset($this->post['generatePDF'])){
            
            $isPdf = isset($this->post['generatePDF']);
        
            $appURL = appConfig::getAppURL();

            ob_start();
            $this->getHtml($isPdf);
            $html = ob_get_clean();
            $html .= '<style>'.file_get_contents($appURL.
                    '/custom/css/includes/tallySheet.css').                  
                    'td {
                    border: 2px solid black;
                        }
                        '.'</style>';
          
            ob_start();
            $this->getHtml2($isPdf);
            $html2 = ob_get_clean();
            $html2 .= '<style>'.file_get_contents($appURL.
                        '/custom/css/includes/tallySheet.css').                  
                        'td {
                        border: 2px solid black;
                            }
                            '.'</style>';

            $pdf = new TCPDF('L', 'px', '', true, 'UTF-8', false); 

            $pdf->SetDisplayMode('fullpage'); 

            $pdf->list_indent_first_level = 0; 

            $pdf->AddPage('L');
            $pdf->WriteHTML($html, true, false, false, false, '');
            $pdf->AddPage('P');
            $pdf->WriteHTML($html2, true, false, false, false, '');
            
            echo 0 ? $html : $pdf->Output('tally.pdf','I');
      
        }
    }   
  
}
