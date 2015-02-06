<?php

/**
 * Project: PiwikManager MVC
 * File: /helpers/interfaces/searchable.php
 * Purpose: Interface for Result Searching
 */
namespace PiwikManager\Helpers;

use \PiwikManager\Helpers\Interfaces;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');

class DI implements Interfaces\Injectable
{

    private static $map;

    private static function addToMap ($key, $obj)
    {

        if (self::$map === null) {
            self::$map = (object) array();
        }
        self::$map->$key = $obj;
    
    }

    public static function mapValue ($key, $value)
    {

        self::addToMap($key, 
                (object) array(
                        "value" => $value,
                        "type" => "value"
                ));
    
    }

    public static function mapClassAsFactory ($key, $value, $arguments = null)
    {

        self::addToMap($key, 
                (object) array(
                        "value" => $value,
                        "type" => "class",
                        "arguments" => $arguments
                ));
    
    }

    public static function mapClassAsSingleton ($key, $value, $arguments = null)
    {

        self::addToMap($key, 
                (object) array(
                        "value" => $value,
                        "type" => "classSingleton",
                        "instance" => null,
                        "arguments" => $arguments
                ));
    
    }

    public static function getInstanceOf ($className, $arguments = null)
    {
        
        // checking if the class exists
        if (! class_exists($className)) {
            throw new \Exception("DI: missing class '" . $className . "'.");
        }
        
        // initialized the ReflectionClass
        $reflection = new \ReflectionClass($className);
        
        // creating an instance of the class
        if ($arguments === null || count($arguments) == 0) {
            if ($reflection->isInstantiable())
                $obj = new $className();
            elseif (method_exists($className, 'getInstance'))
                $obj = $className::getInstance();
            else
                throw new \Exception("Could not instantiate {$className}.");
        } else {
            if (! is_array($arguments)) {
                $arguments = array(
                        $arguments
                );
            }
            $obj = $reflection->newInstanceArgs($arguments);
        }
        
        // injecting
        if ($doc = $reflection->getDocComment()) {
            $inject = false;
            $lines = explode("\n", $doc);
            foreach ($lines as $line) {
                if (count($parts = explode("@Inject", $line)) > 1) {
                    $parts = explode(" ", $parts[1]);
                    if (count($parts) > 1) {
                        $key = $parts[1];
                        $key = str_replace("\n", "", $key);
                        $key = str_replace("\r", "", $key);
                        if (isset(self::$map->$key)) {
                            $inject = true;
                            switch (self::$map->$key->type) {
                                case "value":
                                    $obj->$key = self::$map->$key->value;
                                    break;
                                case "class":
                                    $obj->$key = self::getInstanceOf(
                                            self::$map->$key->value, 
                                            self::$map->$key->arguments);
                                    break;
                                case "classSingleton":
                                    if (self::$map->$key->instance === null) {
                                        $obj->$key = self::$map->$key->instance = self::getInstanceOf(
                                                self::$map->$key->value, 
                                                self::$map->$key->arguments);
                                    } else {
                                        $obj->$key = self::$map->$key->instance;
                                    }
                                    break;
                            }
                        }
                    }
                }
            }
            
            if ($inject && $reflection->hasMethod("_handle_di")) {
                
                $_handle_di = $reflection->getMethod("_handle_di");
                
                $_handle_di->invoke($obj);
            }
        }
        
        // return the created instance
        return $obj;
    
    }

}
