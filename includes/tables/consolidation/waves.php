<?php   

namespace tables\consolidation;

class waves extends \tables\_default
{    
    
    public $ajaxModel = 'consolidation\waves';
    
    public $primaryKey = 'ca.id';
    
    public $fields = [
        'vendor' => [
            'select' => 'CONCAT(w.shortName, "_", vendorName)',
            'display' => 'Client',
            'searcherDD' => 'vendors',
            'ddField' => 'CONCAT(w.shortName, "_", vendorName)',
        ],
        'location' => [
            'select' => 'l.displayName',
            'display' => 'Location',
        ],
        'upc' => [
            'select' => 'upc',
            'display' => 'UPC',
        ],
        'cartons' => [
            'select' => 'COUNT(ca.id)',
            'display' => 'Cartons',
            'groupedFields' => 'ca.id',
        ],
        'pieces' => [
            'select' => 'SUM(uom)',
            'display' => 'Pieces',
            'groupedFields' => 'uom',
        ],
        'cubicFeet' => [
            'select' => 'ROUND(cubicFeet, 2)',
            'display' => 'Total Volume',
        ],
        'usedVolume' => [
            'select' => 'COUNT(ca.id) * ROUND(
                            CEIL(
                                height * length * width / 1728 * 4
                            ) / 4, 2
                        )',
            'display' => 'Used Volume',
            'groupedFields' => 'ca.id',
        ],
        'availableVolume' => [
            'select' => 'ROUND(cubicFeet, 2) - COUNT(ca.id) * ROUND(
                            CEIL(
                                height * length * width / 1728 * 4
                            ) / 4, 2
                        )',
            'display' => 'Available Volume',
            'groupedFields' => 'ca.id',
        ],
    ];
    
    public $table = 'inventory_cartons ca 
           LEFT JOIN inventory_batches b ON b.id = ca.batchID 
           LEFT JOIN inventory_containers co ON co.recNum = b.recNum 
           LEFT JOIN locations l ON l.id = ca.locID 
           LEFT JOIN statuses s ON s.id = ca.statusID 
           LEFT JOIN vendors v ON v.id = co.vendorID 
           LEFT JOIN warehouses w ON v.warehouseID = w.id
           LEFT JOIN upcs u ON u.id = b.upcId 
           JOIN (
                SELECT    ca.locID
                FROM      inventory_cartons ca 
                LEFT JOIN inventory_batches b ON b.id = ca.batchID 
                LEFT JOIN inventory_containers co ON co.recNum = b.recNum 
                GROUP BY  ca.locID
                HAVING    COUNT(DISTINCT upcID) = 1
           ) AS uu ON uu.locID = ca.locID
           ';
    
    public $where = 's.shortName = "RK" 
                AND NOT isSplit
                AND NOT unSplit';
    
    public $groupBy = 'l.displayName, v.id, u.upc, ca.uom';
    
    /*
    ****************************************************************************
    */

    public $wave = 'one';

    /*
    ****************************************************************************
    */

    static function ajaxWave($mvc)
    {
        $model = new static($mvc);

        $result = $model->getReport([
            'upcs' => $mvc->post['upcs'],
            'setWave' => getDefault($mvc->post['wave']),
            'clientID' => $mvc->post['clientID'],
            'locations' => $mvc->post['locations'],
        ]);

        return $result;
    }
    
    /*
    ****************************************************************************
    */
    
    function logToReport()
    {
        $sql = 'SELECT     upc, 
                           COUNT(w.cartonID) AS cartonCount,
                           pl.displayname AS prevLoc, 
                           nl.displayName AS newLoc
                FROM       consolidation_waves w
                LEFT  JOIN inventory_cartons ca ON w.cartonID = ca.id
                LEFT  JOIN inventory_batches b ON ca.batchID = b.id
                LEFT  JOIN inventory_containers co ON b.recnum = co.recnum
                LEFT  JOIN upcs u ON u.id = upcID
                LEFT  JOIN locations pl ON pl.id = prevLocID
                LEFT  JOIN locations nl ON nl.id = newLocID
                GROUP BY   upc, pl.id
                ORDER BY   upc ASC';
        
        $result = $this->queryRestuls($sql);

        return $result;
    }
    
    /*
    ****************************************************************************
    */
    
    function getReport($params)
    {
        // In phase one the results may be limited by UPC
        $upcs = getDefault($params['upcs']);
        
        // In phase two the results may be limited by report row
        $locations = getDefault($params['locations']);

        $clientID = $params['clientID'];
        $this->wave = getDefault($params['setWave'], $this->wave);
        $used = $this->getLocSpace($clientID, 'usedVolume');
        $avail = $this->getLocSpace($clientID, 'availableVolume');
        
        $movements = [];

        // If UPCs have been passed only use them
        // Wave two must do the intersect later and by locations too
        if ($upcs && $this->wave == 'one') {
            $upcKeys = array_flip($upcs);
            $used = array_intersect_key($used, $upcKeys);
            $avail = array_intersect_key($avail, $upcKeys);
        }
        
        foreach ($used as $upc => $data) {
           
            foreach ($data as $usedLoc => $usedVol) {
                // Take smallest quantity and put into the first smallest avail
                $found = FALSE;
                foreach ($avail[$upc] as $availLoc => $row) {
                    
                    if ($found || $usedLoc == $availLoc) {
                        continue;
                    }
                    
                    if ($data[$usedLoc]['volume'] < $row['volume']) {
                        // Reduce avail by used volume
                        $movingVolume = $data[$usedLoc]['volume'];
                        
                        $avail[$upc][$usedLoc]['volume'] = 0;
                        $data[$usedLoc]['volume'] -= $movingVolume;

                        $data[$availLoc]['volume'] += $movingVolume;
                        $avail[$upc][$availLoc]['volume'] -= $movingVolume;

                        $cartonUse = intval($data[$usedLoc]['cartons']);
                        $cartonOfUpcAtToLoc = 
                                $data[$usedLoc]['upc'] == $row['upc'] ? 
                                intval($row['cartons']) : 0;
                        $totalCartonOfUpc = $cartonUse + $cartonOfUpcAtToLoc;
                        
                        $movements[$upc][] = [
                            'to' => $availLoc,
                            'upc' => $data[$usedLoc]['upc'],
                            'from' => $usedLoc,
                            'cartons' => $data[$usedLoc]['cartons'],
                            'height' => $data[$usedLoc]['height'],
                            'width' => $data[$usedLoc]['width'],
                            'length' => $data[$usedLoc]['length'],
                            'weight' => $data[$usedLoc]['weight'],
                            'sku' => $data[$usedLoc]['sku'],
                            'uom' => $data[$usedLoc]['uom'],
                            'cartonOfUpcAtFromLoc' => $cartonUse,
                            'cartonOfUpcAtToLoc' => $cartonOfUpcAtToLoc,
                            'totalCartonOfUpc' => $totalCartonOfUpc,
                        ];
                        
                        $found = TRUE;
                    }
                }
            }
        }

        // Store each movement using the destination as key
        // If a locations destination has its own destination, update the first
        // locations destination to the final destination
        foreach ($movements as $upc => &$upcMoves) {
            $upcMoves = array_reverse($upcMoves);
            $destinations = [];
            foreach ($upcMoves as &$row) {
                $to = $row['to'];
                $from = $row['from'];

                if (! isset($destinations[$from])) {
                    $destinations[$from] = $to; 
                }

                if (isset($destinations[$to])) {
                    $row['to'] = $destinations[$to];
                }
            }
        }

        if ($movements && $locations && $this->wave == 'two') {
            $locKeys = array_flip($locations);

            foreach ($movements['all'] as $index => $move) {
                $from = $move['from'];
                if (! isset($locKeys[$from])) {
                    unset($movements['all'][$index]);
                }
            }
        }
        
        return $movements;
    }
        
    /*
    ****************************************************************************
    */
    
    static function iterateMoves($params)
    {
        $db = getDefault($params['db']);
        $sql = getDefault($params['sql']);
        $upcIDs = $params['upcIDs'];
        $clientID = $params['clientID'];
        $movements = $params['movements'];
        $isWaveTwo = $params['isWaveTwo'];
        $locationIDs = $params['locationIDs'];
        
        $logParams = $newLocs = [];
        $logCount = 0;
        
        foreach ($movements as $upc => $moves) {
            foreach ($moves as $row) {
                
                $upc = $isWaveTwo ? $row['upc'] : $upc;

                $toLoc = $row['to'];
                $fromLoc = $row['from'];
                

                $upcID = $upcIDs[$upc]['id'];
                $toLocID = $locationIDs[$toLoc]['id'];
                $fromLocID = $locationIDs[$fromLoc]['id'];

                switch ($params['action']) {
                    case 'getLogParams':
                        // Keep track of which cartons are moving to which locs
                        $newLocs[$fromLocID][$clientID][$upcID] = $toLocID;

                        $logParams[] = $fromLocID;
                        $logParams[] = $upcID;
                        $logParams[] = $clientID;

                        $logCount++;
                        break;
                    case 'moveCartons':
                        $db->runQuery($sql, [
                            $toLocID, $toLocID, $fromLocID, $upcID, $clientID
                        ]);
                }
                
            }
        }
        
        return [
            'newLocs' => $newLocs,
            'logCount' => $logCount,
            'logParams' => $logParams,
        ];
    }
        
    /*
    ****************************************************************************
    */
    
    function getLocSpace($clientID, $target)
    {
        $results = $this->search([
            'term' => $clientID,
            'search' => 'vendorID',
            'orderBy' => 'upc ASC, ' . $target . ' ASC',
            'addFields' => [
                'u.sku', 'uom', 'height', 'width', 'length', 'weight'
            ]
        ]);

        $upcs = [];
        foreach ($results as $row) {
            if ($row['availableVolume'] < 0) {
                continue;
            }
            
            $upc = $row['upc'];
            $loc = $row['location'];

            // Wave one needs to separate by UPCs
            switch ($this->wave) {
                case 'one':
                    $upcs[$upc][$loc] = [
                        'upc' => $upc,
                        'volume' => $row[$target],
                        'cartons' => $row['cartons'],
                        'height' => $row['height'],
                        'width' => $row['width'],
                        'length' => $row['length'],
                        'weight' => $row['weight'],
                        'sku' => $row['sku'],
                        'uom' => $row['uom'],
                    ];
                    break;
                case 'two':
                    $upcs['all'][$loc] = [
                        'upc' => $upc,
                        'volume' => $row[$target],
                        'cartons' => $row['cartons'],
                        'height' => $row['height'],
                        'width' => $row['width'],
                        'length' => $row['length'],
                        'weight' => $row['weight'],
                        'sku' => $row['sku'],
                        'uom' => $row['uom'],
                    ];
                    break;
            }
        }

        return $upcs;
    }
        
    /*
    ****************************************************************************
    */
}