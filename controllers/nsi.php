<?php 

/*
********************************************************************************
* CLASS CONTROLLER METHODS                                                     *
********************************************************************************
*/

class controller extends template
{

    function listNSIController()
    {
        $model = getDefault($this->get['receiving']) ? new tables\nsi\receiving($this) : 
            new tables\nsi\pos($this);

        $ajax = new datatables\ajax($this);        
        
        $this->jsVars['batch'] = $batch = getDefault($this->get['batch']);
        $recID = getDefault($this->get['receiving']);
        $this->jsVars['receiving'] = $recID = intval($recID);
        
        $customDT = [];
        
        if ($batch) {
            $this->post['andOrs'][] = 'and';
            $this->post['searchTypes'][] = 'batch';
            $this->post['searchValues'][] = $batch;
        } 
        
        if (isset($this->get['receiving'])) {
            $customDT = [
                'order' => [3 => 'desc'],
            ];
        }

        if ($recID) {

            $rec = new tables\nsi\receiving($this);
            
            // Get all the recNSIs with the same time as the recID sent
            $sql = 'SELECT '.$rec->primaryKey.',
                           po,
                           ra
                    FROM   '.$rec->table.'
                    WHERE  setDate = (
                        SELECT setDate
                        FROM   '.$rec->table.'
                        WHERE  receivingID = ?
                        LIMIT  1
                    )
                    GROUP BY po, ra';
            
            $results = $this->queryResults($sql, [$recID]);

            $this->post['andOrs'][] = 'and';

            $firstSearch = array_pop($results);
            $this->post['andOrs'][] = 'and';
            $this->post['searchTypes'] = ['po', 'ra'];
            $this->post['searchValues'] = [
                $firstSearch['po'], 
                $firstSearch['ra'], 
            ];
            
            foreach ($results as $row) {
                $this->post['andOrs'][] = 'or';
                $this->post['searchTypes'][] = 'po';
                $this->post['searchValues'][] = $row['po'];
                $this->post['andOrs'][] = 'and';
                $this->post['searchTypes'][] = 'ra';
                $this->post['searchValues'][] = $row['ra'];
            }
            
        }
        
        $output = $ajax->output($model, $customDT);

        $cartonCount = count($output->params['data']);

        $cartonLabels = isset($this->get['cartonLabels']) ? TRUE : FALSE;
        
        $this->failedReprint = $cartonCount > 900 && $cartonLabels
            ? 'You may only print 900 labels at a time. '
                . 'You have tried to print '.$cartonCount.' labels'
            : NULL;

        if ($cartonLabels && ! $this->failedReprint) {
           
            $receiving = getDefault($this->get['receiving']);
            labels\create::forNSIPOs($output->params['data'], $receiving);
            
        } else if ($this->failedReprint) {
            // If the reprint fails, display the regular inventory
            unset($this->post);
            $output = $ajax->output($model, [
                'order' => ['setDate' => 'desc']
            ]);
        }        
        
        new datatables\searcher($model);
        
        $this->jsVars['receiving'] = getDefault($this->get['receiving'], 0);
    }
    
    /*
    ********************************************************************************
    */

    function shippingNSIController()
    {
        if (isset($this->get['batch'])) {
            $labels = new labels\licensePlates();
            
            $labels->addLicensePlate([
                'db' => $this,
                'nsi' => TRUE,
                'term' => $this->get['batch'],
                'search' => 'batch',
                'fileName' => 'NSI_Shipping_Labels_Batch_'.$this->get['batch'],
            ]);

            return;
        }
        
        $sql = 'SELECT   storeNumber AS id, 
                         storeNumber 
                FROM     stores
                ORDER BY storeNumber ASC';
        
        $this->stores = $this->queryResults($sql);

        
        if ($this->post) {
            
            // Get the next batch
            $sql = 'SELECT id,
                           id
                    FROM   nsi_shipping_batches
                    ORDER BY id DESC
                    LIMIT 1';
            
            $result = $this->queryResult($sql);
            
            $nextBatch = $result['id'] + 1;

            $storeNumber = getDefault($this->post['storeNumber']);

            $storeNumbers = strtolower($storeNumber) == 'all' ? 
                $this->stores : [$storeNumber];            
                
            $this->beginTransaction();
            
            $sql = 'INSERT INTO nsi_shipping_batches () VALUES ()';
            
            $this->runQuery($sql);

            $sql = 'INSERT INTO nsi_shipping (batch, storeNumber)
                    VALUES (?, ?)';

            foreach ($storeNumbers as $storeNumber) { 

                $storeNumber = isset($storeNumber['storeNumber']) ?
                    $storeNumber['storeNumber'] : $storeNumber;
                
                $storeNumber = preg_replace('/[^0-9]+/', NULL, $storeNumber);
                
                for ($i=0; $i<$this->post['quantity']; $i++) {
                    $this->runQuery($sql, [$nextBatch, $storeNumber]);
                }
            }
            
            $this->commit();
            
            $link = makeLink('nsi', 'shipping', [
                'batch' => $nextBatch
            ]);
            
            redirect($link);
        }

    }
    
    /*
    ********************************************************************************
    */

    function addNSIController()
    {
        $this->errors = [];
        
        $users = new tables\users($this);
        $this->users = $users->get();

        $nsi = isset($this->get['receiving']) ? new tables\nsi\receiving($this) 
            : new tables\nsi\pos($this);
        
        // For adding rows
        $this->jsVars['allFields'] = $nsi->fields;
        
        // Pass all fields to JS for alert references
        unset(
            $nsi->fields['setDate'], 
            $nsi->fields['userID'], 
            $nsi->fields['batch'],
            $nsi->fields['pallet']
        );

        // For check field validity
        $this->jsVars['fieldNames'] = $this->fieldNames 
                = array_keys($nsi->fields);      

        
        // For displaying fields in the table
        $this->nsiFields = $nsi->fields;
        
        $post = getDefault($this->post, []);

        if ($post) {
            $this->submit($post, $nsi);
        }
        
        $this->inputs = getDefault($post['inputs'], [[]]);

        $this->jsVars['rowCounter'] = isset($post['inputs']) 
            ? count($post['inputs']) : 1;
        
        
    }

    /*
    ****************************************************************************
    */
}