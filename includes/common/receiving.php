<?php
namespace common;

use common\tally;
use import\vendorData;
use \tables\cycleCount\cycleCount;

class receiving
{
    static function updateRCLogPrint(&$app, $recNum)
    {
        if (! $recNum) {
            return FALSE;
        }
        $sql = 'UPDATE    tallies t
                LEFT JOIN inventory_containers co ON t.recNum = co.recNum
                SET       rcLogPrinted = 1
                WHERE     t.recNum = ?';

        $results = $app->runQuery($sql, [$recNum]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function readyToComplete(&$app, $recNum)
    {
        if (! $recNum) {
            return [
                'errors' => FALSE,
                'ready' => FALSE,
            ];
        }

        $locked = self::checkIfLocked($app, $recNum);

        if ($locked) {
            // a RC Log was created to the container
            return [
                'errors' => FALSE,
                'ready' => TRUE,
            ];
        }

        $clientCycleCount = self::getClientCycleCount($app, $recNum);
        $styleCycleCount = self::getStyleCycleCount($app, $recNum);

        $errors = [];

        if ($clientCycleCount) {

            $errors[] = 'Cycle Count(s) by Client Name created by <strong>'
                    . $clientCycleCount . '</strong>';

            if ($styleCycleCount) {
                $errors[] = NULL;
            }
        }

        foreach ($styleCycleCount as $values) {
            $errors[] = 'Cycle Count by Style # (SKU) <strong>' . $values['sku']
                    . '</strong> created by <strong>' . $values['userName']
                    . '</strong>';
        }

        if ($errors) {
            array_unshift($errors, NULL);
            array_unshift($errors, 'RC Log cannot be created due to active '
                    . 'Cycle Count(s):');
        }

        return [
            'errors' => $errors ? $errors : FALSE,
            'ready' => ! $errors && self::checkIfReady($app, $recNum),
        ];
    }

    /*
    ****************************************************************************
    */

    static function completeRCLog($app, $recNum)
    {
        $params = [
            'app' => $app,
            'rcLogForm' => TRUE,
            'recNums' => [$recNum],
        ];

        tally::prepareForSubmit($params);

        $app->beginTransaction();

        $completeResult = tally::submitRCLog($params);

        $app->commit();

        $sql = 'SELECT v.id,
                       email
                FROM   inventory_containers co
                JOIN   vendors v ON v.id = co.vendorID
                WHERE  recNum = ?';

        $result = $app->queryResult($sql, [$recNum]);

        $clientID = $result['id'];

        return $completeResult;
    }

    /*
    ****************************************************************************
    */

    static function getContainerInfoForRC($app, $container)
    {
        if (! $container) {
            return FALSE;
        }

        $batchIDs = $metaData = [];

        $sql = 'SELECT recNum
                FROM   inventory_containers
                WHERE  name = ?';

        $return = $app->queryResult($sql, [$container]);

        $recNum = $return['recNum'];

        $sql = 'SELECT    ca.id,
                          u.upc,
                          batchID,
                          ca.uom,
                          COUNT(ca.id) AS cartonCount,
                          l.displayName AS location,
                          name
                FROM      inventory_containers co
                LEFT JOIN inventory_batches b ON b.recNum = co.recNum
                LEFT JOIN tallies t ON t.recNum = co.recNum
                LEFT JOIN inventory_cartons ca ON b.id = ca.batchID
                LEFT JOIN upcs u ON b.upcID = u.id
                LEFT JOIN tally_cartons tc ON tc.invID = ca.id
                LEFT JOIN locations l ON l.id = ca.locID
                WHERE     co.recNum = ?
                AND       locked
                AND       NOT isSplit
                AND       NOT unSplit
                AND       locID
                GROUP BY  locID,
                          ca.plate,
                          u.upc,
                          batchID';

        $return['tally'] = $app->queryResults($sql, [$recNum]);

        $sql = 'SELECT b.id,
                       co.recNum,
                       b.id AS batchID,
                       name,
                       vendorName,
                       DATE(co.setDate) AS setDate,
                       u.sku,
                       upc,
                       suffix,
                       prefix,
                       color,
                       size,
                       ca.uom,
                       l.displayName,
                       ic.initialCount,
                       length,
                       width,
                       height,
                       weight,
                       t.id AS tallyID,
                       locked
                FROM   inventory_cartons ca
                LEFT JOIN inventory_batches b ON b.id = ca.batchID
                LEFT JOIN inventory_containers co ON co.recNum = b.recNum
                LEFT JOIN vendors v ON v.id = co.vendorID
                LEFT JOIN upcs u ON u.id = b.upcID
                LEFT JOIN locations l ON l.id = ca.locID
                LEFT JOIN tallies t ON t.recNum = co.recNum
                JOIN      (
                    SELECT   batchID,
                             COUNT(batchID) AS initialCount
                    FROM     inventory_containers co
                    JOIN     inventory_batches b ON b.recNum = co.recNum
                    JOIN     inventory_cartons ca ON ca.batchID = b.id
                    WHERE    co.recNum = ?
                    AND      NOT isSplit
                    AND      NOT unSplit
                    GROUP BY batchID
                ) AS ic ON ic.batchID = b.id
                WHERE  co.recNum = ?
                GROUP BY b.id
               ';

        $results = $app->queryResults($sql, [$recNum, $recNum]);

        // Create a tally if the container doesn't have one
        if ($results) {
            $firstResult = reset($results);
            if (! isset($firstResult['tallyID'])) {
                $sql = 'INSERT INTO tallies (
                            recNum,
                            rowCount
                        ) VALUES (?, 0)';

                $recNum = $firstResult['recNum'];
                $app->runQuery($sql, [$recNum]);
            }

            $batchIDs = array_keys($results);
            $qMarkString = $app->getQMarkString($batchIDs);

            $sql = 'SELECT  bm.id,
                            b.id AS batchID,
                            ag.short_name,
                            description,
                            value
                FROM        inventory_batches b
                LEFT JOIN   batches_meta bm ON bm.batch_id = b.id
                LEFT JOIN   attribute_group ag ON ag.id = bm.attribute_group_id
                WHERE       ag.category = "batch"
                AND         b.id IN (' . $qMarkString . ')
                GROUP BY    b.id, ag.id
                ORDER BY b.id';

            $batchMeta = $app->runQuery($sql, $batchIDs);

            foreach ($batchMeta as $meta) {
                $batchID = $meta['batchID'];
                $shortName = $meta['short_name'];
                $metaData[$batchID]['crossDock'] = FALSE;
                $metaData[$batchID][$shortName] = $meta['value'];
                if($shortName == 'description' && strpos($meta['value'], "CROSS DOCK") !== FALSE) {
                    $metaData[$batchID]['crossDock'] = TRUE;
                }
            }
            foreach ($results as $batchID => $batchData) {
                if (isset($metaData[$batchID])) {
                    $results[$batchID] = array_merge(
                        $metaData[$batchID],
                        $results[$batchID]
                    );
                }
            }
        }

        $return['batches'] = $results ? array_values($results) : FALSE;

        return $return;
    }

    /*
    ****************************************************************************
    */

    static function getFileName($app, $fileID)
    {
        $sql = 'SELECT url
                FROM files
                WHERE id = ?';

        $result = $app->queryResult($sql, [$fileID]);

        return $result ? $result['url'] : FALSE;

    }

    /*
    ****************************************************************************
    */

    static function downloadFile($file, $name, $type='')
    {
        //Check the file permission
        if (! is_readable($file)) die('File not found or inaccessible!');

        $size = filesize($file);
        $name = rawurldecode($name);

        /* Figure out the MIME type | Check in array */
        $mimeTypes = array(
            "pdf" => "application/pdf",
            "txt" => "text/plain",
            "doc" => "application/msword",
            "xls" => "application/vnd.ms-excel",
            "ppt" => "application/vnd.ms-powerpoint",
        );

        if ($type == '') {
            $fileExtension = strtolower(substr(strrchr($file, "."), 1));
            if(array_key_exists($fileExtension, $mimeTypes)){
                $type = $mimeTypes[$fileExtension];
            } else {
                $type = "application/force-download";
            };
        };

        //turn off output buffering to decrease cpu usage
        @ob_end_clean();

        // required for IE, otherwise Content-Disposition may be ignored
        if (ini_get('zlib.output_compression'))
            ini_set('zlib.output_compression', 'Off');

        header('Content-Type: ' . $type);
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header("Content-Transfer-Encoding: binary");
        header('Accept-Ranges: bytes');


        header("Cache-control: private");
        header('Pragma: private');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");


        if (isset($_SERVER['HTTP_RANGE'])) {

            list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
            list($range) = explode("," ,$range, 2);
            list($range, $rangeEnd) = explode("-", $range);
            $range = intval($range);
            if(! $rangeEnd) {
                $rangeEnd = $size - 1;
            } else {
                $rangeEnd = intval($rangeEnd);
            }

            $newLength = $rangeEnd - $range + 1;
            header("HTTP/1.1 206 Partial Content");
            header("Content-Length: $newLength");
            header("Content-Range: bytes $range-$rangeEnd/$size");
        } else {
            $newLength = $size;
            header("Content-Length: " . $size);
        }

        $chunkSize = 1 * (1024 * 1024);
        $bytesSend = 0;
        if ($file = fopen($file, 'r')) {
            if (isset($_SERVER['HTTP_RANGE']))
                fseek($file, $range);

            while (! feof($file) && (! connection_aborted()) &&
                ($bytesSend < $newLength) )
            {
                $buffer = fread($file, $chunkSize);
                print($buffer);
                flush();
                $bytesSend += strlen($buffer);
            }
            fclose($file);
        } else
            die('Error - can not open file.');
        die();
    }

    /*
    ****************************************************************************
    */

    static function checkAndCompleteRCLog($app)
    {
        $locations = new \tables\locations($app);

        $invalidLocations = [];

        $post = $app->post;

        $recNum = $post['recNum'];

        $submittedLocations = json_decode($post['locations']);

        $locationNames = array_filter($submittedLocations);

        if (! $locationNames) {
            return [
                'errors' => [
                    'message' => 'Locations is not blank!'
                ]
            ];
        }

        $results = $locations->checkRCLogLocations($locationNames, $recNum);

        $validLocationKeys = $results['validLocations'];

        foreach ($submittedLocations as &$submittedLocation) {
            if (! $submittedLocation) {
                // skip empty rows
                continue;
            } elseif (isset($validLocationKeys[$submittedLocation])) {
                // skip valid locations
                continue;
            } elseif (isset($validLocationKeys['0' . $submittedLocation])) {
                // add leading zeros to location names if necessary
                $submittedLocation = '0' . $submittedLocation;
            } else {
                $invalidLocations[] = $submittedLocation;
            }
        }

        if ($invalidLocations || $results['wrongWarehouse']) {
            return [
                'errors' => [
                    'invalidLocations' => $invalidLocations,
                    'wrongWarehouse' => $results['wrongWarehouse'],
                ]
            ];
        } else {

            $app->post['locations'] = json_encode($submittedLocations);

            $result = self::completeRCLog($app, $post['recNum']);

            return $result;
        }
    }

    /*
    ****************************************************************************
    */

    static function checkIfReady($app, $recNum)
    {
        $sql = 'SELECT    co.recNum
                FROM      tallies t
                LEFT JOIN inventory_containers co ON co.recNum = t.recNum
                WHERE     co.recNum = ?
                AND       rcLabelPrinted
                AND       rcLogPrinted';

        $result = $app->queryResult($sql, [$recNum]);

        return getDefault($result['recNum']);
    }

    /*
    ****************************************************************************
    */

    static function checkIfLocked($app, $recNum)
    {
        $sql = 'SELECT    locked
                FROM      tallies
                WHERE     recNum = ?';

        $result = $app->queryResult($sql, [$recNum]);

        return getDefault($result['locked']);
    }

    /*
    ****************************************************************************
    */

    static function getClientCycleCount($app, $recNum)
    {
        $clauses = self::cycleCountClauses($app, cycleCount::TYPE_CUSTOMER);

        $sql = 'SELECT    CONCAT_WS(" ", firstName, lastName) AS userName
                FROM      inventory_containers co
                JOIN      count_items ci ON ci.vnd_id = co.vendorID
                ' . $clauses['join'] . '
                WHERE     ' . $clauses['where'] . '
                GROUP BY  created_by
                ORDER BY  userName ASC';

        $results = $app->queryResults($sql, [$recNum]);

        $userNames = array_keys($results);

        return implode(', ', $userNames);
    }

    /*
    ****************************************************************************
    */

    static function getStyleCycleCount($app, $recNum)
    {
        $clauses = self::cycleCountClauses($app, cycleCount::TYPE_SKU);

        $sql = 'SELECT    CONCAT_WS("-", ci.sku, u.id),
                          ci.sku,
                          CONCAT_WS(" ", firstName, lastName) AS userName
                FROM      upcs p
                JOIN      inventory_batches b ON b.upcID = p.id
                JOIN      inventory_containers co ON co.recNum = b.recNum
                JOIN      count_items ci ON ci.sku = p.sku
                ' . $clauses['join'] . '
                WHERE     ' . $clauses['where'] . '
                GROUP BY  sku,
                          userName';

        $results = $app->queryResults($sql, [$recNum]);

        return $results;
    }

    /*
    ****************************************************************************
    */

    static function getLocationCycleCount($app, $recNum, $params)
    {
        if (! $params) {
            return [];
        }

        $clauses = self::cycleCountClauses($app, cycleCount::TYPE_LOCATION);

        $sql = 'SELECT    CONCAT_WS("-", ci.sys_loc, u.id),
                          l.displayName,
                          CONCAT_WS(" ", firstName, lastName) AS userName
                FROM      locations l
                -- inventory_containers does not need ON caluse cause it is used
                -- for the sake of location warehouse verification only
                JOIN      inventory_containers co
                JOIN      count_items ci ON ci.sys_loc = l.id
                ' . $clauses['join'] . '
                WHERE     ' . $clauses['where'] . '
                AND       displayName IN (' . $app->getQMarkString($params) . ')
                GROUP BY  displayName,
                          userName';

        array_unshift($params, $recNum);

        $results = $app->queryResults($sql, $params);

        $return = [];

        foreach ($results as $values) {

            $location = $values['displayName'];

            $return[$location][] = $values['userName'];
        }

        return $return;
    }

    /*
    ****************************************************************************
    */

    static function cycleCountClauses($app, $type)
    {
        $validTypes = [
            cycleCount::TYPE_CUSTOMER,
            cycleCount::TYPE_SKU,
            cycleCount::TYPE_LOCATION,
        ];

        if (! in_array($type, $validTypes)) {
            die('Invalid Cycle Count type');
        }

        $userDB = $app->getDBName('users');

        $join = '
            JOIN      cycle_count cc ON cc.cycle_count_id = ci.cycle_count_id
            JOIN      ' . $userDB . '.info u ON u.id = cc.created_by
            ';

        $where = 'co.recNum = ?
            AND       cc.sts IN ('
                        . '"' . cycleCount::STATUS_CYCLE . '", '
                        . '"' . cycleCount::STATUS_ASSIGNED . '"
                      )
            ';

        $where .= $type == cycleCount::TYPE_LOCATION ? NULL :
                'AND       type = "' . $type . '"';

        if ($type != cycleCount::TYPE_CUSTOMER) {

            $where .= 'AND       v.warehouseID = cv.warehouseID';

            $join .= '
                JOIN      vendors v ON v.id = co.vendorID
                JOIN      vendors cv ON cv.id = ci.vnd_id
                ';
        }

        return [
            'join' => $join,
            'where' => $where,
        ];
    }

    /*
    ****************************************************************************
    */

}
