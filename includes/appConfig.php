<?php

/*
********************************************************************************
* APPCONFIG.PHP                                                                *
********************************************************************************
*/

class appConfig extends models\config
{
    const JWT_AUTH_SECRET = 'My JWT Local Secret';
    const JWT_TEST_MODE = TRUE;
    const SHORIFY_LISTENER = '';


    static $settings = [

        /*
        ************************************************************************
        * APPLICATION DEBUG SETTINGS
        ************************************************************************
        */

        'debug' => [
            // Control whether instant redirect if debug mode is active
            // use: set to see debug message before redirecting
            'crawlMode' => 0,
            // Check classes included during loading
            'loadChecks' => 0,
            'dumpJSVars' => 0,
            // This allows crons to be called from a browser and not just from
            // cron script
            'debugCrons' => 1,
            // Track query times for performance purposes
            'queryTimes' => 1,
            // Time the entire request
            'timeRequest' => 0,
            // Use find mislink for bad connections sent to runQuery
            'findMislinks' => 1,
            // Check if queries will break PDO transactions
            'debugTransactions' => 0,
            // Check if too many queries are being used outside of transactions
            'debugMissingTransactions' => 0,
        ],

        /*
        ************************************************************************
        * PAGE AFTER LOGIN
        ************************************************************************
        */

        'defaultPage' => [
            'requestClass' => 'main',
            'requestMethod' => 'menu',
        ],

        /*
        ************************************************************************
        * USER ACCESS
        ************************************************************************
        */

        'accessLevels' => [
            'developer' => 1,
            'owner'     => 2,
            'admin'     => 3,
            'user'      => 4,
            'inactive'  => 5,
            'none'      => 0,
        ],

        'durations' => [
            // Ten Minutes
            'lock' => 600,
            // Half Hour
            'session' => 1800,
            // Eigth Hours
            'gunSession' => 28800,
        ],

        /*
        ************************************************************************
        * ALLOWED DIRECTORIES
        ************************************************************************
        */

        'logDirs' => [
            'customerPRs' => 'customer/portalRequests',
            'queryErrors' => 'logger/queryErrors',
        ],

        'uploadDirs' => [
            'reportImages' => 'reports/charts/images',
            'invSums' => 'summaries/inventory',
            'imports' => 'imports',
            'uccLabels' => 'appJSON/uccLabels',
            'licensePlates' => 'appJSON/licensePlates',
            'faInventoryImportsFiles' => 'imports/inventory/FA/files',
            'faInventoryImportsPlates' => 'imports/inventory/FA/labels/plates',
            'faInventoryImportsRCLabels' => 'imports/inventory/FA/labels/RC_Labels',
            'faInventoryImportsUCCLabels' => 'imports/inventory/FA/labels/UCC_Labels',
            'laInventoryImportsFiles' => 'imports/inventory/LA/files',
            'laInventoryImportsPlates' => 'imports/inventory/LA/labels/plates',
            'laInventoryImportsRCLabels' => 'imports/inventory/LA/labels/RC_Labels',
            'laInventoryImportsUCCLabels' => 'imports/inventory/LA/labels/UCC_Labels',
            'njInventoryImportsFiles' => 'imports/inventory/NJ/files',
            'njInventoryImportsPlates' => 'imports/inventory/NJ/labels/plates',
            'njInventoryImportsRCLabels' => 'imports/inventory/NJ/labels/RC_Labels',
            'njInventoryImportsUCCLabels' => 'imports/inventory/NJ/labels/UCC_Labels',
            'toInventoryImportsFiles' => 'imports/inventory/TO/files',
            'toInventoryImportsPlates' => 'imports/inventory/TO/labels/plates',
            'toInventoryImportsRCLabels' => 'imports/inventory/TO/labels/RC_Labels',
            'toInventoryImportsUCCLabels' => 'imports/inventory/TO/labels/UCC_Labels',
            'onlineOrdersImportsFiles' => 'imports/onlineOrders/files',
            'onlineOrdersImportsUPSLabels' => 'imports/onlineOrders/UPS_Labels',
            'minMaxImportsFiles' => 'imports/minMax/files',
            'scanOrders' => 'scanners/orders',
            'billoflading' => 'scanners/billoflading',
            'originalImports' => 'imports/originals',
            'shippingDashboard' => 'dashboards/shipping',
            'transfers' => 'transfers',
            'receivingReport' => 'reports/receiving',
            'receiving' => 'imports/receiving',
            'upcOriginal' => 'imports/upcOriginal',
            'orderFiles' => 'imports/orders/files',
            'truckOrderFiles' => 'imports/truckOrders/files',
            'orderReport' => 'reports/orders',
            'cartonReport' => 'reports/cartons',
            'LACartonReport' => 'reports/agingReports/LA/cartons',
            'NJCartonReport' => 'reports/agingReports/NJ/cartons',
            'TOCartonReport' => 'reports/agingReports/TO/cartons',
            'FACartonReport' => 'reports/agingReports/FA/cartons',
            'LAOrderReport' => 'reports/agingReports/LA/orders',
            'NJOrderReport' => 'reports/agingReports/NJ/orders',
            'TOOrderReport' => 'reports/agingReports/TO/orders',
            'FAOrderReport' => 'reports/agingReports/FA/orders',
            'resetPassword' => 'reports/resetPasswords',
            'receivingContainerReport' => 'reports/receivingContainer',
            'reportIssuesFiles' => 'reportIssues/files',
            'reportIssuesScreenshots' => 'reportIssues/screenshots',
        ],

        /*
        ************************************************************************
        * ENVIRONMENTAL DEBUG SETTINGS
        ************************************************************************
        */

        'testingDebug' => [
            // Ok to display errors in dev
            'displayErrors' => 'On',
            // Write MySQL errors to log
            'logErrors' => 0,
            // Errors cause a die
            'errorsKill' => 1,
        ],

        'productionDebug' => [
            'displayErrors' => 'Off',
            'logErrors' => 1,
            'errorsKill' => 1,
        ],

        /*
        ************************************************************************
        * ALTERNATIVE ACCESS / SECURITY
        ************************************************************************
        */

        // Active Directory Credentials
        'activeDirectory' => [
            'host'              => NULL,
            'domain'            => NULL,
            'user'              => NULL,
            'password'          => NULL,
            'errorMessage'      => NULL,
            'distinguishedName' => NULL,
        ],


        // Encription Settings
        'encryption' => [
            'privateKey' => NULL,
            'certificate' => NULL,
        ],

        /*
        ************************************************************************
        * MISCELLANEOUS
        ************************************************************************
        */

        'misc' => [
            // Redirect Site: Leave blank if not forwarding the site to another
            // place
            'redirectSite' => FALSE,
            'developerEmail' => NULL,
        ],

    ];

    static $mailConfig;

    /*
    ****************************************************************************
    */

    static function getMailConfig()
    {
        if (! self::$mailConfig) {
            self::$mailConfig = require 'mailConfig.php';
        }

        return self::$mailConfig;
    }

}
