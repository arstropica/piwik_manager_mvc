<?php

    /**
    * Project: PiwikManager MVC
    * File: /config/bootstrap.php
    * Purpose: bootstrap file containing project-wide definitions
    */
    ini_set('display_errors', 1);
    ini_set('html_errors', 1);
    error_reporting(E_ALL);

    // set a constant that holds the project's "application" folder, like
    // "/var/www/application".
    define('PIWIKAPP', dirname(dirname(__FILE__)));

    define('PIWIKACTION_PROTOCOL', 'http://');

    define('PIWIKACTION_DOMAIN', $_SERVER['HTTP_HOST']);
    
    define('PIWIKDS_URL', PIWIKACTION_PROTOCOL . PIWIKACTION_DOMAIN );

    define('PIWIKACTION_PORTAL', 
        PIWIKACTION_PROTOCOL . PIWIKACTION_DOMAIN . '/portal.php?a=piwikmanager');

    define('PIWIKACTION_PORTAL_PATH', PIWIKACTION_PORTAL . '&path=');

    define('PIWIKACTION_REL_PATH', 
        str_replace($_SERVER['DOCUMENT_ROOT'], '', 
            dirname($_SERVER['SCRIPT_NAME'])));

    /**
    * Configuration for: PIWIKACTION_*
    * Here we auto-detect the applications URL and the potential sub-folder.
    * *
    */
    define('PIWIKACTION_BASE', PIWIKACTION_PORTAL_PATH . PIWIKACTION_REL_PATH);

    /**
    * Configuration for: Database
    * This is the place where you define your database credentials, database type
    * etc.
    */
    define('PIWIKDB_TYPE', 'mysql');

    if ($_SERVER['SERVER_ADDR'] == '172.26.21.48') {

        define('PIWIKDB_HOST', 'localhost');

    } else {

        define('PIWIKDB_HOST', '172.26.66.48');

    }

    define('PIWIKDB_NAME', 'xxxxxxx');

    define('PIWIKDB_USER', 'xxxxxxx');

    define('PIWIKDB_PASS', 'xxxxxxx');

    // Require Helper Files
    require_once PIWIKAPP . '/helpers/functions.php';

    require_once PIWIKAPP . '/helpers/autoloader.php';

    $piwikautoloader = new \PiwikManager\Helpers\Autoloader();

    // set timezone
    date_default_timezone_set('America/New_York');

