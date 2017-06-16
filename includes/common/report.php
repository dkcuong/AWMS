<?php

namespace common;

class report
{

    /*
    ****************************************************************************
    */
    
    static function recordReportsSent($app, $primeKeys, $status, $data = [])
    {
        // Record that the email was sent
        
        $statuses = new \tables\statuses\reports($app);

        $statusID = $statuses->getStatusID($status);
        
        $reportID = self::createReportLog($app);

        $data = json_encode($data);

        $sql = 'INSERT INTO reports_data (
                    reportID, 
                    primeKey,
                    statusID,
                    data
                ) VALUES (
                    ?, ?, ?, ?
                )';
        
        $app->beginTransaction();
        
        foreach ($primeKeys as $primeKey) {
            $app->runQuery($sql, [$reportID, $primeKey, $statusID, $data]);
        }
        
        $app->commit();        
    }
    
    /*
    ****************************************************************************
    */
   
    static function createReportLog($app)
    {
        $sql = 'INSERT INTO reports () VALUES ()';
        
        $app->runQuery($sql);
        
        return $app->lastInsertID();
    }
    
    /*
    ****************************************************************************
    */

    static function getByPrimeKey($app, $primeKey, $status)
    {        
        $sql = 'SELECT  reportID
                FROM 	reports_data rd
                JOIN 	statuses s ON s.id = rd.statusID
                WHERE 	primeKey = ?
                AND 	shortName = ?
                AND 	s.category = "reports"';

        $result = $app->queryResult($sql, [$primeKey, $status]);

        return $result ? $result['reportID'] : $result;
    }
    
    /*
    ****************************************************************************
    */

    static function getCronData($app, $status, $limit = 1)
    {
        $sql = 'SELECT  rd.*
                FROM 	reports_data rd
                JOIN    reports r ON r.id = rd.reportID
                JOIN 	statuses s ON s.id = rd.statusID
                WHERE   shortName = ?
                AND 	category = "reports"
                AND     ! r.isSent
                LIMIT ' . $limit;

        $result = $app->queryResults($sql, [$status]);

        return $result;
    }

    /*
    ****************************************************************************
    */

    static function isLogged($app, $primeKey, $status)
    {
        $result = self::getByPrimeKey($app, $primeKey, $status);
        return $result ? TRUE : FALSE;
    }

    /*
    ****************************************************************************
    */

    static function updateMezzanineTransfer($data)
    {
        $app = $data['app'];
        $reportID = $data['reportID'];
        $userID = $data['userID'];
        $batches = $data['batches'];
        $transferStauses = $data['transferStauses'];

        $batchStatus = $transferStauses['METR'];
        $userStatus = $transferStauses['METU'];
        
        $sql = 'INSERT INTO reports (
                    id
                ) VALUES (
                    ?
                ) ON DUPLICATE KEY UPDATE
                    isSent = 0
                ';

        $app->runQuery($sql, [$reportID]);        

        $sql = 'INSERT IGNORE INTO reports_data (
                    reportID, primeKey, statusID
                ) VALUES (
                    ?, ?, ?
                ) ';

        foreach ($batches as $batch) {
            $app->runQuery($sql, [$reportID, $batch, $batchStatus]);
            $app->runQuery($sql, [$reportID, $userID, $userStatus]);
        }
    }
    
    /*
    ****************************************************************************
    */

    static function updateReportData($app, $reportIDs)
    {
        $qMarks = $app->getQMarkString($reportIDs);
        
        $sql = 'UPDATE 	reports
                SET 	isSent = 1
                WHERE 	id IN (' . $qMarks . ')';

        $app->runQuery($sql, $reportIDs);
    }

    /*
    ****************************************************************************
    */

}