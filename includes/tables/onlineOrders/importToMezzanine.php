<?php

namespace tables\onlineOrders;

use \Exception;

class importToMezzanine 
{
    const DEBUG = FALSE;

    public $importData = [];
    public $app = [];
    public $upc = [];
    public $info;
    public $badUpcs = [];
    public $vendorID;
    public $isImport;
    public $dealSiteID;
    public $clientOrder;
    public $onlineOrder;
    public $manualTransfer;
    public $noTransferUpcs = [];
    public $tableOnlineOrder;
    public $transferUpcItems = [];

    public function __construct($params)
    {
        $this->app = $params['app'];
        $this->info = $params['info'];
        $this->upcItems = $params['upcItems'];
        $this->vendorID = $params['vendorID'];
        $this->isImport = $params['method'] == 'import';
        $this->manualTransfer = getDefault($params['manualTransfer']);
    }

    /*
    ****************************************************************************
    */

    public function findBatches()
    {
        return $this->process('findBatches');
    }

    /*
    ****************************************************************************
    */

    public function process($findBatches=FALSE)
    {
        $valid = $this->validateEnoughInventory();

        self::DEBUG ? varDump($valid) : NULL;
        
        if ($valid) {

            try {
                self::DEBUG ? vardump($this->transferUpcItems) : NULL;

                if ($this->transferUpcItems) {
                    $transferTool = new transferToMezzanine([
                        'app' => $this->app,
                        'info' => $this->info,
                        'isImport' => $this->isImport,
                        'vendorID' => $this->vendorID,
                        'upcItems' => $this->transferUpcItems,
                        'manualTransfer' => $this->manualTransfer,
                    ]);

                    $foundBatches = $transferTool->transfer($findBatches);

                    if ($findBatches) {
                        return [
                            'foundBatches' => $foundBatches,
                            'transferTool' => $transferTool,
                        ];
                    }
                }

            } catch (Exception $e) {
                die ('Caught exception: '.  $e->getMessage(). "\n");
            }

        }
       
    }

    /*
    ****************************************************************************
    */

    public function validateEnoughInventory()
    {
        $upcsInfo = $this->info->get('upcsInfo');
         
        $exceptMessage = '';
        
        foreach ($this->upcItems as $upc => $item) {
            $upcInfo = $upcsInfo[$upc];

            if (! $upcInfo['hasMinMaxSetting']) {
                $this->badUpcs[] = 'UPC# '. $upc . ': Not set for Min Max location';
                return FALSE;
            }

            $isTransfer = $this->isTransferToMezzanine($item, $upcInfo);

            if ($isTransfer) {
                $upcInfo['orderQuantity'] = $item['quantity'];
                $result = $this->processTransferQuantity($upcInfo);

                if ($result) {
                    continue;
                }

            }
        }

        if (isset($this->badUpcs) && $this->badUpcs) {
            echo 'Transfer Import Error Data:';
            foreach ($this->badUpcs as $except) {
               $exceptMessage .= $except . '<br/>';
            }
            throw new Exception ($exceptMessage);
        }

        return count($this->badUpcs) == 0;
    }

    /*
    ****************************************************************************
    */

    public function isTransferToMezzanine($importUpc, $info)
    {
        self::DEBUG ? vardump($importUpc) : NULL;
        self::DEBUG ? vardump($info) : NULL;

        $upc = $info['upc'];

        if ($this->manualTransfer) {
            if ($info['warehouseQuantity'] < $importUpc['quantity']) {
                $this->badUpcs[] = 'UPC# '. $upc .': Requested trasfer quantity is '
                            . $importUpc['quantity'] .' and Warehouse Quantity'
                            . ' is '. $info['warehouseQuantity'];
                return FALSE;
            }

            return TRUE;
        }
        
        $transferRequired = $info['actualQuantity'] < $importUpc['quantity'];

        $warehouseQuantity = $info['warehouseQuantity'];
        $totalPieces = $warehouseQuantity + $info['actualQuantity'];
        if ($transferRequired && $totalPieces < $importUpc['quantity']) {
            $this->badUpcs[] = 'Not enough quantity in warehouse required to '
                            . 'transfer ' . $warehouseQuantity . ' item';
            return FALSE;                
        }

        return $info['max'] - $info['actualQuantity'] +  $importUpc['quantity'];
    }

    /*
    ****************************************************************************
    */

    public function calculateTransferQuantity($params)
    {
        if ($this->manualTransfer) {
            return $params['orderQuantity'];
        }
        
        return $params['max'] ?
            $params['max'] - $params['actualQuantity'] + $params['orderQuantity'] :
            $params['orderQuantity'];
    }

    /*
    ****************************************************************************
    */

    public function processTransferQuantity($params)
    {
        $upc = $params['upc'];
        $warehouseQuantity = $params['warehouseQuantity'];

        $params['transferQuantity'] = $this->calculateTransferQuantity($params);

        if ($params['transferQuantity'] <= $params['warehouseQuantity']) {
            $this->transferUpcItems[$upc] = $params;
            return TRUE;
        }
        
        $params['transferQuantity'] = $warehouseQuantity;
        $this->transferUpcItems[$upc] = $params;

        $quantityDiff = $params['actualQuantity'] - $params['orderQuantity'];
        
        $total = $params['actualQuantity'] + $params['warehouseQuantity'];

        if (// Check order quantity in warehouse
            $params['orderQuantity'] <= $params['warehouseQuantity']
        ||  // Check after fulfill order in warehouse
            $quantityDiff <= $params['warehouseQuantity']
        ||  // Check order quantity merged inventories
            $total >= $params['orderQuantity']
        ) {
            return TRUE;
        }

        $this->badUpcs[] = 'Not have enough quantity in inventory to transfer';
        
        return FALSE;

     }
    
    /*
    ****************************************************************************
    */
}