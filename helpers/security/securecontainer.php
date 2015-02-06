<?php

/**
 * Project: PiwikManager MVC
 * File: /helpers/security/securecontainer.php
 * Purpose: Decorator Class handling Access Control for Users
 */
namespace PiwikManager\Helpers\Security;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');

class SecureContainer
{

    protected $className;

    protected $qualifiedClassName;

    protected $classInst;

    protected $acl;

    public function __construct (\PiwikManager\Acl $acl, String $className, 
            Array $arguments = array())
    {

        $this->acl = $acl;
        
        $this->className = $className;
        
        $this->qualifiedClassName = get_qualified_name($className);
        
        $this->classInst = $this->_init($arguments);
    
    }

    private function _init ($arguments)
    {

        if (class_exists($this->qualifiedClassName) &&
                 $this->acl->isAllowed(
                        get_short_name($this->qualifiedClassName))) {
            
            $rclassInst = new \ReflectionClass($this->qualifiedClassName);
            
            return $rclassInst->newInstanceArgs($arguments);
        } else {
            
            throw new \Exception(
                    'Sorry. You do not have sufficient permissions to do this.');
        }
    
    }

    public function __call ($method, $arguments)
    {

        if (method_exists($this->classInst, $method) &&
                 $this->acl->isAllowed(get_short_name($this->classInst), 
                        $method)) {
            
            return call_user_func_array(
                    array(
                            $this->classInst,
                            $method
                    ), $arguments);
        } else {
            
            throw new \Exception(
                    'Sorry. You do not have sufficient permissions to do this.');
        }
    
    }

}
