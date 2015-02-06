<?php

    /**
    * Project: PiwikManager MVC
    * File: /index.php
    * Purpose: root handler for all requests
    */
    require_once "vendor/bootstrap.inc";

    global $row_current_session;

    // Set DB Options and create Config
    $dbOptions = array(
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING
    );

    // Define ACL Permissions
    $caps = array(

        'home' => array(
            // Admins
            1 => 'index,admin,edit,activate,deactivate,exclude,include,view,search,update,ping,settings',
            // Employees
            2 => 'index,search,view,ping',
            // Clients
            3 => 'view,ping',
            // Limited Employees
            4 => 'index,view,ping',
            // PMCs
            5 => 'index,view,search,ping',
            // Limited PMCs
            6 => 'index,view,search,ping'
        )
    );

    // Init Config Class
    $config = new \PiwikManager\Config\Config($dbOptions, $caps);

    // Get API Settings
    $settings = $config->getSettings();

    // Define FQN Class Maps
    $routes = array(

        "common" => array(

            "acl" => array(

                "type" => "factory",

                "path" => "[app_ns]\\Helpers\\Security\\Acl",

                "args" => array(
                    "[request]",
                    $row_current_session
                )
            )
        )
        ,

        "home" => array(

            "piwik" => array(

                "type" => "instance",

                "path" => "[app_ns]\\Models\\Home\\PiwikAPIModel",

                "args" => array(
                    $settings["piwik_scheme"],
                    $settings["piwik_url"],
                    $settings["piwik_api_key"]
                )
            ),

            "wp" => array(

                "type" => "instance",

                "path" => "[app_ns]\\Models\\Home\\WordPressModel",

                "args" => array(
                    $settings["wordpress_api_key"],
                    // Proxy Settings
                    array(
                        "domain" => "poxy_host",
                        "port" => "proxy_port",
                        "user" => "proxy_user",
                        "pass" => "proxy_pass",
                    )
                )
            ),
        )

    )
    ;

    // Add Class Maps to Config
    foreach ($routes as $route => $classes) {

        $config->routeToMap($route, $classes);
    }

    // Start the application
    $application = \PiwikManager\Helpers\DI::getInstanceOf(
        "\\PiwikManager\\Core\\Application", array(
            $config
    ));

    // Execute Application
    $application->_dispatch();

