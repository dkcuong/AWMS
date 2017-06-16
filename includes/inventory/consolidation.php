<?php

namespace inventory;

class consolidation 
{
    /*
    ****************************************************************************
    */

    static function getMulti($byUPC)
    {
        $collect = [];
        foreach ($byUPC as $upc => $locations) {
            if (count($locations) != 1) {
                foreach ($locations as $row) {
                    $location = $row['location'];
                    $collect[$upc][$location] = $row['cartons'];
                }
            }
        }
        
        return $collect;
    }

    /*
    ****************************************************************************
    */

    static function get($byUPC)
    {
        $movements = [];
        foreach ($byUPC as $upc => $locations) {
            if (count($locations) != 1) {
                $movements[$upc] = self::getMoves($locations);
            }
        }
        return $movements;
    }
    
    /*
    ****************************************************************************
    */

    static function getMoves($data)
    {
        foreach ($data as &$createDiffs) {
            $createDiffs['needed'] = $createDiffs['maxCartons'] - $createDiffs['cartons'];
        }

        $haventCollectEnough = TRUE;
        $collect = [];
        $cartons = 0;
        $lastCartons = 0;
        while ($haventCollectEnough) {
            $smallestGroup = array_pop($data);
            $cartons += $smallestGroup['cartons'];
            $collect[] = $smallestGroup;
            
            $required = 0;
            foreach ($data as $info) {
                $required += $info['needed'];
            }

            // Check if collected amount is greater than amount required to fill
            // in the other locs
            if ($cartons >= $required) {
                if ($lastCartons > $required) {
                    $data[] = $smallestGroup;
                    array_pop($collect);
                }

                $haventCollectEnough = FALSE;
            }
            
            $lastCartons = $cartons;
        }
        
        $movements = [];

        foreach ($collect as $info) {
            
            // If the collected upcs fill up the first one add it to the next
            $finished = $usedUp = FALSE;
            $needed = 0;
            while (! $usedUp && ! $finished) {
                $firstLoc = array_shift($data);
                $needed = $firstLoc['needed'];

                if (! $needed) {
                    $finished = TRUE;
                }
                
                $moving = NULL;
                if ($needed >= $info['cartons']) {
                    
                    $moving = $info['cartons'];
                    
                    $firstLoc['needed'] -= $info['cartons'];

                    array_unshift($data, $firstLoc);
                    
                    $usedUp = TRUE;
                }
                
                if ($needed < $info['cartons']) {
                    
                    $moving = $needed;

                    // Remove cartons that were moved
                    $info['cartons'] -= $needed;
                    
                }

                if (! $finished) {
                    $movements[] = [
                        'quantity' => $moving,
                        'fromLoc' => $info['location'],
                        'toLoc' => $firstLoc['location'],
                    ];
                }
            }
        }
        
        return $movements;
    }

    /*
    ****************************************************************************
    */

    function direct()
    {
        // Need an array of ucc128s by location

        $sql = 'SELECT      ca.id, 
                            u.upc, 
                            CONCAT(v.id, b.id, LPAD(uom, 3, 0), LPAD(cartonID, 4, 0)) AS ucc128, 
                            l.displayName AS location 
                FROM        inventory_cartons ca 
                LEFT JOIN   inventory_batches b 
                ON          b.id = ca.batchID 
                LEFT JOIN   inventory_containers co 
                ON          co.recNum = b.recNum 
                LEFT JOIN   vendors v 
                ON v.id = co.vendorID 
                LEFT JOIN locations l 
                ON locID = l.id 
                LEFT JOIN upcs u 
                ON b.upcID = u.id 
                WHERE upc IN  ('.implode(', ', array_keys($movements)).')';
        
        $results = $this->queryResults($sql);
        
        $upcsByLoc = [];
        foreach ($results as $info) {
            $upc = $info['upc'];
            $location = $info['location'];
            $upcsByLoc[$upc][$location][] = $info['ucc128'];
        }
            
        inventory\waves::directDisplay($movements);        
    }

    /*
    ****************************************************************************
    */

}
