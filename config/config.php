<?php

/**
 * Project: PiwikManager MVC
 * File: /config/config.php
 * Purpose: Class handling project-wide configuration
 */
namespace PiwikManager\Config;

use \PiwikManager\Helpers;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');

class Config
{

    /**
     *
     * @var string The DBO class FQN
     */
    static $db;

    /**
     *
     * @var array Class Map
     */
    public $map;

    public function __construct (Array $dbOptions = array(), Array $caps = array(), 
            Array $routes = array())
    {

        self::$db = Helpers\Db::get(false, $dbOptions);
        
        Helpers\DI::mapValue("db", self::$db);
        
        Helpers\Logger::$error_file = $_SERVER['DOCUMENT_ROOT'] .
                 '/piwik/log/error_log.html';
        
        set_exception_handler('PiwikManager\Helpers\Logger::exception_handler');
        
        set_error_handler('PiwikManager\Helpers\Logger::error_handler');
        
        $this->loadMap($routes);
        
        if ($caps && is_array($caps)) {
            
            foreach ($caps as $route => $cap) {
                
                self::setCaps($route, $cap);
            }
        }
    
    }

    private function loadMap ($routes)
    {

        $this->map = array(
                
                "common" => array(
                        
                        "model" => array(
                                
                                "type" => "instance",
                                
                                "path" => "[app_ns]\\Models\\[controller]\\[controller]Model",
                                
                                "args" => null
                        ),
                        
                        "controller" => array(
                                
                                "type" => "instance",
                                
                                "path" => "[app_ns]\\Controllers\\[controller]\\[controller]Controller",
                                
                                "args" => array(
                                        "[request]"
                                )
                        ),
                        
                        "view" => array(
                                
                                "type" => "instance",
                                
                                "path" => "[app_ns]\\Views\\[controller]\\[action]View",
                                
                                "args" => array(
                                        "[request]"
                                )
                        ),
                        
                        "viewmodel" => array(
                                
                                "type" => "instance",
                                
                                "path" => "[app_ns]\\Models\\[controller]\\[controller]ViewModel",
                                
                                "args" => array(
                                        "[request]"
                                )
                        ),
                        
                        "template" => array(
                                
                                "type" => "instance",
                                
                                "path" => "[app_ns]\\Templates\\[controller]\\[controller]Template",
                                
                                "args" => array(
                                        "[request]"
                                )
                        )
                )
                
        )
        ;
        
        if ($routes) {
            
            $this->map = array_merge_recursive($this->map, $routes);
        }
    
    }

    public function setCap (String $route, Int $level, String $csv)
    {

        Helpers\Security\Acl::setCap($route, $level, $csv);
    
    }

    public function setCaps (String $route, Array $values)
    {

        Helpers\Security\Acl::setCaps($route, $values);
    
    }

    public function routeToMap (String $route, Array $routes)
    {

        if (isset($this->map[$route]))
            
            $this->map[$route] = array_merge($this->map[$route], $routes);
        
        else
            
            $this->map[$route] = $routes;
    
    }

    public function classToMap (String $route, String $key, Array $clsOpts)
    {

        $this->map[$route][$key] = $clsOpts;
    
    }

    /**
     * Get Settings Page Values
     *
     * @param string|null $option_name            
     *
     * @return array
     */
    public function getSettings ($option_name = null)
    {

        $params = array();
        
        $output = false;
        
        $sql = "SELECT * FROM Accounts_piwik_settings";
        
        if ($option_name) {
            
            $sql .= " WHERE option_name LIKE :option_name;";
            
            $params = array(
                    ':option_name' => $option_name
            );
        }
        
        $query = self::$db->prepare($sql);
        
        $query->execute($params);
        
        $results = $query->fetchAll(\PDO::FETCH_ASSOC);
        
        if ($results) {
            
            $output = array();
            
            foreach ($results as $row) {
                
                $output[$row['option_name']] = $row["option_value"];
            }
        }
        
        return $output;
    
    }

}
