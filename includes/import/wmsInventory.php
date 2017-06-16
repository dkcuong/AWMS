<?php

namespace import;

use \tables;

class wmsInventory 
{
    public $invFields = [
        'container',
        'receivingNumber',
        'batchNumber',
        'vendor',
        'sku',
        'height',
        'width',
        'length',
        'uom',
        'carton',
        'upcID',
        'upc',
        'ucc128',
        'palletLocation',
        'status',
        'totalcarton',
        'vendorInvID',
    ];
    
    public $containers = [];

    
    /*
    ****************************************************************************
    */

    function __construct($params)
    {
        $app = $this->pdo = $params['app'];

        if (empty($app->post)) {
            return;
        }
        
        $this->vendors = new tables\vendors($app);

        $vendor = $params['vendor'];
        $vendorInfo = $app->importInterface[$vendor];
        
        $this->vendorInitials = $app->importInterface[$vendor]['initials'] or 
            die('Vendor Initials not set');
        
        $this->locations = new tables\locations($app);
        $this->inventory = new tables\inventory\cartons($app);
        $vendorModelPath = 'tables\inventory\vendors\\'.$vendorInfo['model'];
        $vendorInv = new $vendorModelPath($app);

                
        $upcs = $this->inventory->getUPCIDs();
        $this->vendorProducts = $vendorInv->get();
        
        $maxUPCID = $this->inventory->getMaxID('upcID', 1);

        foreach ($upcs as $upc => $upcID) {
            $upcs[$upc] = $upcID['upcID'];
        }
        
        $this->setUPCIDs($upcs, $maxUPCID);

        $app->beginTransaction();
        $this->makeInsertions($vendorInfo['display'], $upcs);
        $app->commit();                
    }
    
    
    /*
    ****************************************************************************
    */

    function setUPCIDs(&$upcs, &$maxUPCID)
    {       
        $containerCount = 0;
        $recievingNumber = $this->inventory->getMaxID('receivingNumber', 10000001);

        foreach ($this->vendorProducts as $product) {
            // make one container per location
            $location = $product['location'];
            
            if (! isset($this->containers[$location])) {
                $this->containers[$location] = [
                    'container' => 'Import'.$this->vendorInitials.'_'.$containerCount++,
                    'receivingNumber' => $recievingNumber++
                ];
            }
            
            // If upc isn't in upc array add it with new upcID
            $upc = $product['upc'];
            if (! isset($upcs[$upc])) {
                $upcs[$upc] = ++$maxUPCID;
            }
        }
    }
    
    /*
    ****************************************************************************
    */

    function makeInsertions($vendorName, $upcs)
    {
        $sql = 'INSERT IGNORE inventory (
                    '.implode(',', $this->invFields).'
                ) VALUES (
                    '.$this->pdo->getQMarkString($this->invFields).'
                )';

        $batchNumber = $this->inventory->getMaxID('batchNumber', 10000001);
        $vendorInfo = $this->vendors->getByName($vendorName) 
            or die('Class import\\wmsInvenory could not find vendor.');
        
        $shippingLocations = $this->locations->getShippingLocationsByName();

        foreach ($this->vendorProducts as $vendorInvID => $product) {
            $location = $product['location'];
            $upc = $product['upc'];
            $quantity = $product['productQuantity'];

            $status = isset($shippingLocations[$location]) ? 'LS' : 'RK';

            $newBatch = $batchNumber++;
            for ($piece = 1; $piece <= $quantity; $piece++) {
                $carton = sprintf('%04d', $piece);
                $uom = sprintf('%03d', $product['uom']);
                $ucc = $vendorInfo['id'].$newBatch.$uom.$carton;
                $params = [
                    $this->containers[$location]['container'],
                    $this->containers[$location]['receivingNumber'],
                    $newBatch,
                    $vendorInfo['id'],
                    $product['sku'],
                    $product['height'],
                    $product['width'],
                    $product['length'],
                    $uom,
                    $carton,
                    $upcs[$upc],
                    $upc,
                    $ucc,
                    $location,
                    $status,
                    $quantity,
                    $vendorInvID,
                ];

                $this->pdo->runQuery($sql, $params);
            }
        }
    }

}
