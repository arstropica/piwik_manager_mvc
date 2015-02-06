<?php

/**
 * Project: PiwikManager MVC
 * File: /helpers/interfaces/injectable.php
 * Purpose: Interface for Dependency Injection
 */
namespace PiwikManager\Helpers\Interfaces;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');

Interface Injectable
{
    
    // Returns Instance of the class
    static function getInstanceOf ($className, $arguments = null);
    
    // Generates a fixed value
    static function mapValue ($key, $value);
    
    // Generates and uses a new Instance of the class
    static function mapClassAsFactory ($key, $value, $arguments = null);
    
    // Generates and uses a single Instance of the class
    static function mapClassAsSingleton ($key, $value, $arguments = null);

}
