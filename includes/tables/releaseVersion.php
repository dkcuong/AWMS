<?php

namespace tables;

class releaseVersion extends \tables\_default
{
    static $lastVersion;

    public $displaySingle = 'Release Version';

    public $primaryKey = 'rv.id';

    public $ajaxModel = 'releaseVersion';

    public $fields = [
        'id' => [
            'display' => 'Version ID',
            'noEdit' => TRUE,
        ],
        'versionName' => [
            'display' => 'Version'
        ],
        'date' => [
            'searcherDate' => TRUE,
            'display' => 'Date',
            'noEdit' => TRUE
        ]
    ];

    public $table = 'release_versions rv';

    public $groupBy = 'rv.versionName';

    public $orderBy = 'rv.id DESC';

    public $mainField = 'rv.id';

    public $customInsert = 'releaseVersion';

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

        $sql = 'INSERT IGNORE release_versions (
                    versionName
                ) VALUES (
                    ?
                )';

        $this->app->runQuery($sql, [$versionID]);
    }

    /*
    ****************************************************************************
    */
}