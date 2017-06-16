<?php

namespace tables;
use access;

class updatesList extends \tables\_default
{
    static $lastVersion;

    public $displaySingle = 'New Feature';

    public $primaryKey = 'anf.id';

    public $ajaxModel = 'updatesList';

    public $fields = [
        'versionName' => [
            'select' => 'rv.versionName',
            'display' => 'Version Name',
            'searcherDD' => 'releaseVersion',
            'ddField' => 'rv.versionName',
            'update' => 'anf.versionID'
        ],
        'featureName' => [
            'select' => 'anf.featureName',
            'display' => 'Feature Name'
        ],
        'featureDescription' => [
            'select' => 'anf.featureDescription',
            'display' => 'Description'
        ],
        'date' => [
            'select' => 'anf.date',
            'searcherDate' => TRUE,
            'display' => 'Date',
            'noEdit' => TRUE
        ],
        'active' => [
            'select' => 'IF(anf.active, "Yes", "No")',
            'display' => 'Active',
            'searcherDD' => 'statuses\boolean',
            'ddField' => 'displayName',
            'update' => 'anf.active',
            'updateOverwrite' => TRUE
        ]
    ];

    public $table = 'release_versions rv
                     JOIN awms_new_features anf ON rv.id = anf.versionID';

    public $groupBy = 'anf.id';

    public $mainField = 'rv.id';

    public $customInsert = 'updatesList';

    /*
    ****************************************************************************
    */

    function insertTable()
    {
        return $this->table;
    }

    /*
    ****************************************************************************
    */

    function customInsert($post)
    {
        $versionID = $post['versionName'];
        $featureName = $post['featureName'];
        $featureDescription = $post['featureDescription'];
        $active = $post['active'];

        $sql = 'INSERT INTO awms_new_features (
                    versionID,
                    featureName,
                    featureDescription,
                    active
                ) VALUES (
                    ?, ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    featureDescription = ?,
                    active = ?
                ';

        $ajaxRequest = TRUE;

        $param = [
            $versionID, 
            $featureName, 
            $featureDescription, 
            $active, 
            $featureDescription, 
            $active
        ];

        $this->app->runQuery($sql, $param, $ajaxRequest);
    }

    /*
    ****************************************************************************
    */

    function listNewFeatures()
    {
        $versionID = $this->getLastVersionHaveFeature();

        if ($versionID) {
            $sql = 'SELECT 	  anf.id,
                              anf.featureName,
                              anf.featureDescription
                    FROM      awms_new_features anf
                    JOIN      release_versions rv ON anf.versionID = rv.id
                    LEFT JOIN version_info vi ON vi.versionID = rv.id
                    WHERE     rv.id = ?
                    AND       anf.active
                    GROUP BY  rv.id, anf.id';

            $result = $this->app->queryResults($sql, [$versionID]);

            return $result;
        }

        return FALSE;
    }

    /*
    ****************************************************************************
    */

    function checkIsShowFeature()
    {
        $userID = access::getUserID();
        self::$lastVersion = $this->getLastVersion();
        
        $isShow = $currentVersionID = FALSE;

        $sql = 'SELECT  id,
                        versionID,
                        isShow
                FROM    version_info
                WHERE   userID = ?';

        $result = $this->app->queryResult($sql,[$userID]);

        if ($result) {
            $isShow = $result['isShow'];
            $currentVersionID = $result['versionID'];
        }

        // Update status isShow when new Release Version updated.
        $updateIsShow = $this->updateStatusIsShow(self::$lastVersion,
            $currentVersionID, $userID);

        // Check last Release Version have Features.
        $checkDataFeature = $this->checkDataFeatureTable(self::$lastVersion);

        if ($checkDataFeature) {
            // Insert new user with last Release Version or update last Release
            // Version for exits user in table version_info
            $this->updateVersionInfo(self::$lastVersion, $userID);
        }

        return $isShow || $updateIsShow && $checkDataFeature ? TRUE : FALSE;
    }

    /*
    ****************************************************************************
    */

    function getLastVersion()
    {
        $sql = 'SELECT    rv.id AS lastVersionID
                FROM      release_versions rv
                ORDER BY  rv.id DESC
                LIMIT     1';

        $result = $this->app->queryResult($sql);

        $versionID = getDefault($result['lastVersionID']);

        return $versionID;
    }

    /*
    ****************************************************************************
    */

    function getLastVersionHaveFeature()
    {
        $sql = 'SELECT    rv.id AS versionID
                FROM      release_versions rv
                JOIN 	  awms_new_features anf ON rv.id = anf.versionID
                WHERE 	  rv.id = anf.versionID
                AND       anf.active
                ORDER BY  rv.id DESC
                LIMIT     1';

        $result = $this->app->queryResult($sql);

        $versionID = getDefault($result['versionID']);

        return $versionID;
    }

    /*
    ****************************************************************************
    */

    function updateStatusIsShow($lastVersionID, $versionID, $userID)
    {
        if ($lastVersionID && $versionID && $lastVersionID > $versionID) {
            $sql = 'UPDATE  version_info
                    SET     isShow = 1
                    WHERE   userID = ?';

            $this->app->runQuery($sql, [$userID]);

            return TRUE;
        }
        return FALSE;
    }

    /*
    ****************************************************************************
    */

    function updateVersionInfo($lastVersionID,  $userID)
    {
        if ($lastVersionID) {
            
            $sql = 'INSERT INTO version_info (
                        userID,
                        versionID
                    ) VALUES (
                        ?, ?
                    ) ON DUPLICATE KEY UPDATE
                        versionID = ?';

            $this->app->runQuery($sql, [
                $userID,
                $lastVersionID,
                $lastVersionID
            ]);
        }
    }

    /*
    ****************************************************************************
    */

    function checkDataFeatureTable($lastVersion)
    {
        $sql = 'SELECT    a.id AS featureID
                FROM      awms_new_features a
                JOIN      release_versions r ON a.versionID = r.id
                WHERE     a.versionID = ?
                AND       a.active';

        $result = $this->app->queryResult($sql,[$lastVersion]);

        return $result['featureID'] ? TRUE : FALSE;
    }

    /*
    ****************************************************************************
    */

}