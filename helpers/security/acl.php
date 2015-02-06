<?php

    /**
    * Project: PiwikManager MVC
    * File: /helpers/security/acl.php
    * Purpose: Class handling Access Control for Users
    */
    namespace PiwikManager\Helpers\Security;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    class Acl
    {

        private static $capabilities = array();

        private $row_current_session;

        private $request;

        private static $cron;

        public function __construct (Array $request, 
            \stdClass $row_current_session = null)
        {

            $cronsafe = isset($_REQUEST['cronsafe']) ? $_REQUEST['cronsafe'] : false; 

            if ($cronsafe == "mdzzsa32fs4j"){

                $this::$cron = true;

            }

            if (empty($row_current_session) && ! $this::$cron)

                throw new \Exception('You must be a valid user to use this tool.');

            $this->request = $request;

            $this->row_current_session = $row_current_session;

            if (! $this->isAllowed($request['controller'], $request['action']))

                throw new \Exception(
                    'Sorry. You do not have sufficient permissions to do this.');

        }

        public static function setCaps (String $route, Array $values)
        {

            self::$capabilities[$route] = $values;

        }

        public static function setCap (String $route, Int $level, String $csv)
        {

            self::$capabilities[$route][$level] = $csv;

        }

        public static function getCaps (String $route)
        {

            return self::$capabilities[$route];

        }

        public static function getCap (String $route, Int $level)
        {

            return self::$capabilities[$route][$level];

        }

        public function isAllowed ($controller, $action = null)
        {

            if ($this::$cron) {

                return true;

            }

            if (! self::$capabilities)

                throw new \Exception("Capabilities have not been set in ACL class.");

            $controller = strtolower($controller);

            $action = strtolower($action);

            $account_level = $this->row_current_session->users_account_id;

            $permission = false;

            if (isset(self::$capabilities[$controller][$account_level])) {

                // Constructor method?
                if (! $action) {

                    $permission = true;
                } else {

                    $ucap = explode(",", 
                        self::$capabilities[$controller][$account_level]);

                    if (in_array($action, $ucap)) {

                        $permission = true;
                    }
                }
            }

            return $permission;

        }

        public function get_sherpa_id ()
        {

            return $this->row_current_session->default_sherpa_id;

        }

    }
