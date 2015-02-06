<?php

/**
 * Project: PiwikManager MVC
 * File: /core/application.php
 * Purpose: class that routes URL requests to Controller object creation
 */
namespace PiwikManager\Core;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');

class Application
{

    /**
     *
     * @var Config Object
     */
    static $config;

    /**
     *
     * @var User Info Object
     */
    static $row_current_session;

    /**
     *
     * @var array request parameters
     */
    static $args;

    /**
     *
     * @var array route parameters
     */
    static $request;

    /**
     *
     * @var array class map structure
     */
    static $map;

    /**
     *
     * @var array FQN class map
     */
    static $routes;

    /**
     * Constructor
     * "Start" the application:
     * Constuct a Class Map from URL path
     * Inject Dependencies for Required Components *
     */
    public function __construct (\PiwikManager\Config\Config $config)
    {
        
        //
        self::$config = $config;
        
        $path = empty($_GET['path']) ? null : $_GET['path'];
        
        self::$request = $this->parser($path);
        
        self::$map = $this->mapper($config->map, self::$request);
        
        $this->router(self::$map);
    
    }

    /**
     * Get Arguments from URL variable 'path'
     */
    private function parser ($path = null)
    {

        $request = array();
        
        if (isset($path)) {
            
            // split URL
            $url = trim($path, '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            
            // Put URL parts into according properties
            // By the way, the syntax here is just a short form of if/else,
            // called "Ternary Operators"
            // @see
            // http://davidwalsh.name/php-shorthand-if-else-ternary-operators
            
            $request['controller'] = isset($url[0]) ? ucwords($url[0]) : "Home";
            
            $request['action'] = isset($url[1]) ? ucwords($url[1]) : "Index";
            
            // Remove controller and action from the split URL
            unset($url[0], $url[1]);
            
            // Rebase array keys and store the URL params
            $request['args'] = array_values($url);
        } else {
            
            $request['controller'] = "Home";
            
            $request['action'] = "Index";
            
            $request['args'] = array();
        }
        
        return $request;
    
    }

    private function mapper (Array $map, Array $request)
    {

        $output = array();
        
        $placeholders = array(
                
                "app_ns" => "PiwikManager",
                
                "controller" => $request['controller'],
                
                "action" => $request['action'],
                
                "request" => $request
        );
        
        if ($map) {
            
            foreach ($map as $route => $routes) {
                
                if (is_array($routes)) {
                    
                    foreach ($routes as $key => $_config) {
                        
                        $mapped = $_config;
                        
                        foreach (array(
                                "path" => $_config["path"],
                                "args" => $_config["args"]
                        ) as $type => $value) {
                            
                            if (is_string($value)) {
                                
                                foreach ($placeholders as $placeholder => $replacement) {
                                    
                                    $value = str_replace("[{$placeholder}]", 
                                            $replacement, $value);
                                }
                            } elseif (is_array($value)) {
                                
                                foreach ($value as &$val) {
                                    
                                    if (is_string($val)) {
                                        
                                        foreach ($placeholders as $placeholder => $replacement) {
                                            
                                            if ($val == "[{$placeholder}]")
                                                
                                                $val = $replacement;
                                            
                                            else
                                                
                                                $val = str_replace(
                                                        "[{$placeholder}]", 
                                                        $replacement, $val);
                                        }
                                    }
                                }
                            }
                            
                            $mapped[$type] = $value;
                        }
                        
                        $output[$key] = $mapped;
                    }
                }
            }
            
            return $output;
        }
    
    }

    private function router ($map)
    {

        if ($map) {
            
            foreach ($map as $alias => $details) {
                
                if (is_array($details)) {
                    
                    if (isset($details["type"])) {
                        
                        switch ($details["type"]) {
                            
                            case "instance":
                                
                                if (class_exists($details["path"])) {
                                    
                                    \PiwikManager\Helpers\DI::mapClassAsSingleton(
                                            $alias, $details["path"], 
                                            $details["args"]);
                                } else {
                                    
                                    throw new \Exception(
                                            ucwords($alias) . " @ " .
                                                     $details["path"] .
                                                     " could not be found.");
                                }
                                
                                break;
                            
                            case "factory":
                                
                                if (class_exists($details["path"])) {
                                    
                                    \PiwikManager\Helpers\DI::mapClassAsFactory(
                                            $alias, $details["path"], 
                                            $details["args"]);
                                } else {
                                    
                                    throw new \Exception(
                                            ucwords($alias) . " @ " .
                                                     $details["path"] .
                                                     " could not be found.");
                                }
                                
                                break;
                            
                            case "value":
                                
                                \PiwikManager\Helpers\DI::mapValue($alias, 
                                        $details["args"]);
                                
                                break;
                        }
                    } else {
                        
                        throw new \Exception(
                                ucwords($alias) . " does not have valid mapping.");
                    }
                } else {
                    
                    throw new \Exception(
                            ucwords($alias) . " @ " . print_r($details, true) .
                                     " could not be found.");
                }
            }
        } else {
            
            throw new \Exception(
                    "The required mapping and routing could not be performed for this action.");
        }
    
    }

    /* executes the requested method */
    public function _dispatch ()
    {

        $controller = \PiwikManager\Helpers\DI::getInstanceOf(
                self::$map["controller"]["path"]);
        
        return $controller->{self::$request["action"]}();
    
    }

}

?>
