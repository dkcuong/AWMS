<?php

/*
********************************************************************************
* DATABASE INFO
********************************************************************************
*/

class dbInfo extends database\model
{
    // The different servers for each environment
    static public $serverEnvs = [

        // Will be determnined by environment class and server location
        'local' => [

            // First server will be chosen by default
            'wms' => [
                'databases' => [
                    // App database will be chosen by default
                    'app' => 'staging_wms',
                    'users' => 'staging_users',
                    'crons' => 'staging_crons',
                    'testRuns' => 'seldat_automations',
                    'tests' => 'seldat_tests',
                ],
                'credentials' => [
                    'host' => 'localhost',
                    'user' => 'root',
                    'pass' => '',
                ]
            ]
        ],
        'development' => [
        
            // First server will be chosen by default
            'wms' => [
                'databases' => [ 
                    // App database will be chosen by default
                    'app' => 'b6_wms',
                    'users' => 'b6_wms_users',
                    'crons' => 'b6_wms_crons',
                ],
                'credentials' => [
                    'host' => 'localhost',
                    'user' => 'wms',
                    'pass' => 'wms102015',
                ]
            ]
        ]
    ];

    /*
    ************************************************************************
    */

    static function getDBInfo($serverAlias)
    {
        $appEnv = appConfig::get('site', 'appEnv');
        return $serverAlias ?
            self::$serverEnvs[$appEnv][$serverAlias] :
            reset(self::$serverEnvs[$appEnv]);
    }

    /*
    ************************************************************************
    */

    static function getDBName($db='app', $serverName=FALSE)
    {
        $server = self::getDBInfo($serverName);

        return $server['databases'][$db];
    }

    /*
    ************************************************************************
    */
}