<?php

/*
********************************************************************************
* MODEL                                                                        *
********************************************************************************
*/

class model extends base 
{
    
    public $ajax = NULL;

    /*
    ****************************************************************************
    */

    function modelSubmitBatches($params)
    {
        $post = $params['post'];
        $upcs = $params['upcs'];
        $cartons = $params['cartons'];
        $batches = $params['batches'];
        $containers = $params['containers'];
        
        $upcInputs = [];
        foreach ($post['inputs'] as $rowID => $row) {
            // Check that all required fields are there
            foreach ($this->inventoryFields as $field => $info) {
                if (! $row[$field] && ! isset($info['optional'])) {
                    $this->errors['Required field is missing'] = TRUE; 
                }
            }

            // Store UPCs to check if new
            $upcInputs[$rowID] = $row['upc'];

        }

        if ($this->errors) {
            return;
        }

        // Convert upcs to upcIDs and create new if necessary
        $upcInfo = $upcs->getUPCStatuses($upcInputs);

        $oldUPCs = $upcs->insertNewUPCs($upcInfo);

        foreach ($oldUPCs as $rowID => $upcID) {
            $post['inputs'][$rowID]['upcID'] = $upcID;
        }

        $reordered = array_values($post['container']);

        // Create container
        $containerID = $containers->insert($reordered);

        if (! $containerID) {
            return $this->errors['Container already exists'] = TRUE; 
        }
        
        // Create new inv batches
        $batches->createBatches($cartons, $containerID, $post['inputs']);  
        
        $containerName = getDefault($post['container']['name']);
        
        $link = makeLink('containers', 'list', ['container' => $containerName]);
        redirect($link);
    }

}
