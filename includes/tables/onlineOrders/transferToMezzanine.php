<?php

namespace tables\onlineOrders;

use inventory\splits;
use common\logger;
use tables\inventory\cartons;
use tables\statuses\inventory;

class transferToMezzanine
{
    static $RACKED_STATUS_ID;

    private $app;
    private $upcItems = [];
    private $remainingTransferQuantity = 0;
    private $vendorID;
    private $query;
    private $manualTransfer;


    public $splitQuery;
    public $findBatches = FALSE;
    public $foundBatches = [];
    public $isImport;

    public $info;

    /*
    ****************************************************************************
    */

    public function __construct($params)
    {
        $this->app = $params['app'];
        $this->info = $params['info'];
        $this->upcItems = $params['upcItems'];
        $this->vendorID = $params['vendorID'];
        $this->isImport = $params['isImport'];
        $this->manualTransfer = getDefault($params['manualTransfer']);
        $this->query = new transferQuery($params);
        
        self::$RACKED_STATUS_ID = cartons::getRackedStatusID($this->app);
    }

    /*
    ****************************************************************************
    */

    public function transfer($findBatches)
    {
        $this->transferItemID = $this->query->getNextID('transfer_items');
        $emailTool = new emailCron($this->app);

        $this->findBatches = $findBatches;

        if (! $findBatches)  {
            $transferID = $this->info->get('transferID', [$this, 'createTransfer']);
        }

        $this->splitQuery = new splitQuery([
            'model' => $this->query,
            'isImport' => $this->isImport,
            'findBatches' => $findBatches,
        ]);

        logger::getFieldIDs('cartons', $this->app);

        logger::getLogID();

        $transferCartons = [];

        foreach ($this->upcItems as $upc => $item) {

            $item['upc'] = $upc;
            $cartons = $this->getCartons($upc, $item);

            $this->makeSplitCartons($cartons);

            $splitPieces = $this->splitPieces($cartons);

            if ($findBatches) {
                continue;
            }

            $transferCartons[$upc] =
                $this->transferToMezzanine($splitPieces, $item);

            $params = [
                'vendorID' => $this->vendorID,
                'upcID' => $item['upcID'],
                'pieces' => $item['transferQuantity'],
                'locationID' => $item['minMaxLocID'],
                'sourceLocID' => $item['locID'],
                'transferID' => $transferID
            ];

            $this->app->beginTransaction();
            $this->query->insertTransferItems($params);

            $this->createTransferCartons($this->transferItemID++, $splitPieces);
            $this->app->commit();

        }

        if (! $this->manualTransfer) {

            //send email
            $emailTool->run([
                'transferID' => $transferID,
                'vendorID' => $this->vendorID,
                'transferCartons' => $transferCartons
            ]);
        }

        //Insert transferIf into report data
        return $findBatches ? $this->foundBatches : $transferCartons;
    }

    /*
    ****************************************************************************
    */

    function createTransfer()
    {
        $userID = \access::getUserID();
        $nextID = $this->query->getNextID('transfers');

        $this->query->insertTransfer($userID, $nextID);

        return $nextID;
    }

    /*
    ****************************************************************************
    */

    private function createTransferCartons($transferItemID, $cartons)
    {
        foreach ($cartons as $carton) {
            
            $this->query->insertTransferCartons([
                'transferItemID' => $transferItemID, 
                'cartonID' => $carton['id'],
                'locID' => $carton['locID']
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    private function makeSplitCartons(&$cartons)
    {
        if ($cartons && $this->remainingTransferQuantity) {
            $lastCarton = array_pop($cartons);
            $remainingCarton = $this->getRemainingCarton($lastCarton);

            array_push($cartons, $remainingCarton);
        }
    }

    /*
    ****************************************************************************
    */

    private function splitPieces($cartons)
    {
        $pieces = [];

        foreach ($cartons as $carton) {
            if ($this->isOnePiece($carton)) {

                $pieces[] = $this->formatCartonData($carton, $carton);
                continue;
            } else {
                $splitPiece = $this->doSplitPieces($carton);

                if ($this->findBatches) {
                    continue;
                }

                foreach ($splitPiece as $piece) {
                    $pieces[] = $this->formatCartonData($piece, $carton);
                }
            }
        }

        return $pieces;
    }

    /*
    ****************************************************************************
    */

    private function isOnePiece($carton)
    {
        return $carton['uom'] == 1;
    }

    /*
    ****************************************************************************
    */

    private function transferToMezzanine($cartons, $upcInfo)
    {
        $cartonInfo = [];
        $this->app->beginTransaction();

        $info = $upcInfo;
        $info['pieces'] = 0;
        $info['newLocID'] = $upcInfo['minMaxLocID'];

        $invIDs = $oldLocIDs = $oldMLocIDs = [];
        
        foreach ($cartons as $carton) {
            $this->query->transferCarton($upcInfo['minMaxLocID'], $carton['id']);

            $invIDs[] = $carton['id'];
            $oldLocIDs[] = $carton['locID'];
            $oldMLocIDs[] = $carton['mLocID'];

            $targetID = $info['targetID'] = $carton['parentID'];
            $info['oldLocID'] = $carton['locID'];

            if (! isset($cartonInfo[$targetID]['info'])) {
                $cartonInfo[$targetID]['info'] = $info;
            }

            $cartonInfo[$targetID]['info']['pieces'] += $carton['uom'];
            $cartonInfo[$targetID]['children'][] = $carton['id'];
        }

        if (! $this->isImport) {
            logger::edit([
                'db' => $this->app,
                'primeKeys' => $invIDs,
                'fields' => [
                    'locID' => [
                        'fromValues' => $oldLocIDs,
                        'toValues' => $info['newLocID']
                    ],
                    'mLocID' => [
                        'fromValues' => $oldMLocIDs,
                        'toValues' => $info['newLocID']
                    ]
                ],
                'transaction' => FALSE
            ]);
        }

        $this->app->commit();

        return $cartonInfo;
    }

    /*
    ****************************************************************************
    */

    public function doSplitCarton($parentCarton, $firstChildUom)
    {
        $query = $this->splitQuery;
        $parentID = $parentCarton['id'];

        $carton = $query->getMaxCarton($parentCarton['batchID'], $parentID);

        if ($this->findBatches) {
            if ($carton['batchID']) {
                $this->foundBatches[] = $carton['batchID'];
            }
            return;
        }

        $targetPieces = $remaining = $carton;
        $targetPieces['uom'] = $firstChildUom;
        $targetPieces['cartonID'] += 1;

        $remaining['uom'] = $parentCarton['uom'] - $firstChildUom;
        $remaining['cartonID'] = $targetPieces['cartonID'] + 1;

        $this->app->beginTransaction();
        $this->findBatches ? NULL : $query->createChildCarton($targetPieces);
        $this->findBatches ? NULL : $query->createChildCarton($remaining);
        $this->app->commit();

        $targetPieces['id'] =
            $query->getChildCartonID($targetPieces['batchID'], $targetPieces['cartonID']);

        $remaining['id'] =
            $query->getChildCartonID($remaining['batchID'], $remaining['cartonID']);

        $children = [$targetPieces, $remaining];

        if (! $this->findBatches && ! $this->isImport){
            $this->addLogForNewInventoryCartons($children, TRUE);
        }

        $isUpdated = $this->updateSplitCartons([
            'query' => $query,
            'parentID' => $parentCarton['id'],
            'children' => $children,
        ]);

        return $isUpdated ? $children : FALSE;
    }

    /*
    ****************************************************************************
    */

    private function addLogForNewInventoryCartons($cartons, $transaction=FALSE)
    {
        if (! $cartons) {
            return;
        }

        $params = [
            'db' => $this->app,
            'primeKeys' => array_column($cartons, 'id'),
            'transaction' => $transaction,
        ];

        $logFields = ['statusID', 'mStatusID', 'locID', 'mLocID', 'plate'];

        $fromValues = array_fill(0, count($params['primeKeys']), 0);

        foreach ($logFields as $logField) {
            $params['fields'][$logField] = [
                'fromValues' => $fromValues,
                'toValues' => in_array($logField, ['statusID', 'mStatusID']) ?
                        10 : array_column($cartons, $logField),
            ];
        }

        logger::edit($params);
    }

    /*
    ****************************************************************************
    */

    public function doSplitPieces($parentCarton, $firstChildUom = 1)
    {
        $query = $this->splitQuery;
        $parentID = $parentCarton['id'];

        $carton = $query->getMaxCarton($parentCarton['batchID'], $parentID);

        $maxCarton = $carton['cartonID'];
        $children = [];

        $this->app->beginTransaction();

        for ($i = 0; $i < $parentCarton['uom']; $i += $firstChildUom ) {
            $child = $carton;
            $child['uom'] = $firstChildUom;
            $maxCarton++;
            $child['cartonID'] = $maxCarton;
            $this->findBatches ? NULL : $query->createChildCarton($child);
            $child['id'] = splitQuery::$nextCartonID;
            $children[] = $child;
        }

        if (! $this->findBatches && ! $this->isImport) {
            $this->addLogForNewInventoryCartons($children);
        }

        $this->app->commit();

        $this->setSplitCartonIDs($query, $children, $carton['batchID']);

        if ($this->findBatches) {
            return;
        }

        $isUpdated = $this->updateSplitCartons([
            'query' => $query,
            'parentID' => $parentCarton['id'],
            'children' => $children,
        ]);

        $results = $isUpdated ? $children : FALSE;

        if (! $results) {
            echo 'Do Split Pieces Method found carton';
            backtrace();die;
        }

        return $results;
    }

    /*
    ****************************************************************************
    */

    public function setSplitCartonIDs($query, &$children, $batchID)
    {
        if ($this->findBatches) {
            return $this->foundBatches[] = $batchID;
        }

        foreach ($children as $key => $child) {
            $id = $query->getChildCartonID($batchID, $child['cartonID']);
            $children[$key]['id'] = $id;
        }
    }

    /*
    ****************************************************************************
    */

    public function updateSplitCartons($params)
    {
        $query = $params['query'];
        $parentID = $params['parentID'];
        $children = $params['children'];

        $split = new splits($this->app, $this->isImport);

        $this->app->beginTransaction();

        try {
            $query->updateParentCarton($parentID);

            foreach ($children as $child) {
                $split->insertSplitRel($parentID, $child['id']);
            }
            $this->app->commit();
            return TRUE;
        } catch (PDOException $e) {
            return FALSE;
        }
    }

    /*
    ****************************************************************************
    */

    private function getRemainingCarton($lastCarton)
    {
        $splitCartons =
            $this->doSplitCarton($lastCarton, $this->remainingTransferQuantity);

        if ($this->findBatches) {
            return;
        }

        if (is_array($splitCartons)) {
            $remainingCarton = array_shift($splitCartons);
            return $this->formatCartonData($remainingCarton, $lastCarton);
        }

        echo 'No split cartons found';
        backtrace();die;
    }

    /*
    ****************************************************************************
    */

    private function formatCartonData($carton, $parentCarton)
    {
        return [
            'id' => $carton['id'],
            'uom' => $carton['uom'],
            'upc' => $parentCarton['upc'],
            'locID' => $carton['locID'],
            'mLocID' => $carton['mLocID'],
            'batchID' => $carton['batchID'],
            'parentID' => $parentCarton['id'],
        ];
    }

    /*
    ****************************************************************************
    */

    private function getCartons($upc, $upcInfo)
    {
        $uomsInfo = $this->info->get('uomsInfo');

        $transferQuantity = $upcInfo['transferQuantity'];

        $cartonList = [];

        // Check each UOM from smallest to biggest until enough pieces are found
        if (! isset($uomsInfo[$upc])) {
            return $cartonList;
        }

        foreach ($uomsInfo[$upc] as $uom => $uomInfo) {

            if (($transferQuantity % $uom != 0)
            || ($uomInfo['cartonCount'] * $uom < $transferQuantity)) {
                continue;
            }

            $this->remainingTransferQuantity = $transferQuantity % $uom;
            $cartonQuantity = $transferQuantity / $uom;
            
            $cartons = $cartonQuantity <= $uomInfo['cartonCount'] ?
                    array_slice($uomInfo['cartons'], 0, $cartonQuantity) :
                    $uomInfo['cartons'];
            
            $cartonList = array_merge($cartonList, $cartons);
            $transferQuantity -= $cartonQuantity * $uom;

            break;
        }

        if (! $transferQuantity) {
            return $cartonList;
        }

        foreach ($uomsInfo[$upc] as $uom => $uomInfo) {
            $cartonQuantity = ceil($transferQuantity / $uom);
            
            $cartons = $cartonQuantity <= $uomInfo['cartonCount'] ?
                    array_slice($uomInfo['cartons'], 0, $cartonQuantity) :
                    $uomInfo['cartons'];
            
            $cartonList = array_merge($cartonList, $cartons);

            if ($uomInfo['cartonCount'] * $uom >= $transferQuantity) {
                $this->remainingTransferQuantity = $transferQuantity % $uom;
                break;
            }

            $transferQuantity = $this->reCalculateTransferQuantity($cartons,
                $uom, $transferQuantity);

        }

        return $cartonList;
    }

    /*
    ****************************************************************************
    */

    private function reCalculateTransferQuantity($cartons, $uom, $quantity)
    {
        $foundQuantity = count($cartons) * $uom;
        $remainingQuantity = $quantity - $foundQuantity;

        return $remainingQuantity;
    }

    /*
    ****************************************************************************
    */
}