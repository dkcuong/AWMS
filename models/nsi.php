<?php

class model extends base 
{
    public $stores = [];
    
    /*
    ****************************************************************************
    */

    function submit($post, $nsi) 
    {
        foreach ($post['inputs'] as $row) {
            // Check that all required fields are there
            foreach ($this->nsiFields as $field => $info) {
                if (! $row[$field] && ! isset($info['optional'])) {
                    $this->errors['Required field is missing'] = TRUE; 
                }
                
                if (isset($info['validation'])) {
                    $validation = $info['validation'];
                    $isValid = call_user_func($validation, $row[$field]);
                    if (! $isValid) {
                        $this->errors['Non-numeric value passed'] = TRUE; 
                    }
                }
            }
        }

        if ($this->errors) {
            return;
        }
        
        // Otherwise make insertion

        $nextBatch = $nsi->getNextID('nsi_po_batches');
        
        $firstRow = reset($post['inputs']);
        
        $nextRecID = NULL;
        
        if (isset($this->get['receiving'])) {
            // Get the next receiving ID if necessary
            $sql = 'SELECT   id
                    FROM     ' . $nsi->mainTable . '
                    ORDER BY id DESC
                    LIMIT    1';
            
            $result = $this->queryResult($sql);
            $nextRecID = $result['id'];
        } else {
            $firstRow['batch'] = $nextBatch;
        }

        $this->beginTransaction();
        
        $firstRow['userID'] = $post['userID'];        

        $arrayKeys = array_keys($firstRow);
        
        $sql = 'INSERT INTO ' . $nsi->mainTable . ' (' . implode(', ', $arrayKeys) . ')
                VALUES      (' . $this->getQMarkString($arrayKeys) . ')';

        $innerInsert = 'INSERT INTO nsi_receiving_pallets (receivingID)
                        VALUES (?)';
        
        foreach ($post['inputs'] as $values) {
            
            if (! isset($this->get['receiving'])) {
                $values['batch'] = $nextBatch;
            }
            $values['userID'] = $post['userID'];
            $arrayValues = array_values($values);
            $this->runQuery($sql, $arrayValues);

            $nextRecID++;
            for ($i = 0; $i < $values['palletNumber']; $i++) {

                $this->runQuery($innerInsert, [$nextRecID]);
            }
        }
        
        $addBatch = 'INSERT INTO nsi_po_batches () VALUES ()';
        
        $this->runQuery($addBatch);
        
        $this->commit();
        
        $getParams = isset($this->get['receiving']) ? [
            'reprint' => TRUE,
            'receiving' => $nextRecID
        ] : [
            'reprint' => TRUE,
            'batch' => $nextBatch
        ];

        $link = makeLink('nsi', 'list', $getParams);
        
        redirect($link);
        
    }

    /*
    ****************************************************************************
    */
}
