<?php

namespace carriers;

class carriers
{
    static function getCosts($app, $ordersWeights)
    {
        $upcs = array_unique(array_column($ordersWeights, 'upc'));
        $carriers = array_unique(array_column($ordersWeights, 'carrier'));
        
        $sql = 'SELECT    upc,
                          MAX(CAST(weight AS DECIMAL(12,6))) AS weight
                FROM      inventory_batches b
                LEFT JOIN upcs u
                ON        b.upcID = u.id
                WHERE     upc IN (' . $app->getQMarkString($upcs) . ')
                GROUP BY upc';
        
        $upcWeight = $app->queryResults($sql, $upcs);

        $sql = 'SELECT    id,
                          carrierID,
                          ounces,
                          price
                FROM      carriers.shipping_prices
                WHERE     carrierID IN (' . $app->getQMarkString($carriers) . ')';
        $carrierPrices = $app->queryResults($sql, array_values($carriers));
        
        foreach($carrierPrices as $result) {
            $carrierId = $result['carrierID'];
            $ounces = $result['ounces'];
            $price = $result['price'];
            $prices[$carrierId][$ounces] = $price;
        }

        $orders = [];
        foreach ($ordersWeights as $key => $ordersWeight) {
            $order = $ordersWeight['order'];
            $carrier = $ordersWeight['carrier'];
            $upc = $ordersWeight['upc'];
            $quantity = $ordersWeight['quantity'];
            $weight = $quantity*$upcWeight[$upc]['weight'];
            
            $orders[$order][$carrier] = 
                    (isset($orders[$order][$carrier]) ? 
                     $orders[$order][$carrier] : 0) + $weight;
        }

        foreach ($orders as $order => $carriers) {
            $costs['orders'][$order] = 0;
            foreach ($carriers as $carrier => $weight) {
                $weight = self::getMailingWeight($weight);
                $upcPrice = 0;
                if ($weight > 0) {
                    foreach ($prices[$carrier] as $ounces => $price) {
                        if ($ounces <= $weight || $upcPrice == 0) {
                            $upcPrice = $price;
                        } else {
                            break;
                        }
                    }
                }
                $cargoPrice = $weight*$upcPrice;
                
                $costs['orders'][$order] += $cargoPrice;
                $costs['carriers'][$carrier] = 
                        (isset($costs['carriers'][$carrier]) ? 
                         $costs['carriers'][$carrier] : 0) + $cargoPrice;
            }
        }
        return $costs;
    }
    
    /*
    ****************************************************************************
    */

    static function getMailingWeight($weight)
    {
        $weight *= 1.05;
        if ($weight < 0) {
            return FALSE;
        } else if ($weight < 1) {
            return self::customFloor($weight, 0.0625);
        } else if ($weight < 10) {
            return self::customFloor($weight, 1);
        } else {
            return self::customFloor($weight, 10);            
        }
    }
    
    /*
    ****************************************************************************
    */

    static function customFloor($value, $incs)
    {
        $smaller = $value / $incs;
        $floor = floor($smaller);
        $final = $floor * $incs;
        return $final;
    }

    /*
    ****************************************************************************
    */
}
