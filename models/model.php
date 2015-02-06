<?php

/**
 * Project: PiwikManager MVC
 * File: /models/model.php
 * Purpose: Abstract Base Class handling the Data Access layer
 */
namespace PiwikManager\Models;

use \PDO;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');

/**
 * @Inject db
 */
abstract class Model
{

    public $db;
    
    protected $comparison;
    
    static $lastQuery;

    public function __construct ()
    {
        
        // Common FieldNames & Comparisons
        $this->comparison = array(
        
            "Accounts.Accounts_name" => "RLIKE",
                    
            "Accounts_name" => "RLIKE",
            
            "Accounts_piwik_details.description" => "RLIKE",
                    
            "decription" => "RLIKE",
            
            "Accounts_credentials.DesignSherpaURL" => "RLIKE",
                    
            "DesignSherpaURL" => "RLIKE",
            
            "Accounts_credentials.DomainMappingURL" => "RLIKE",
                    
            "DomainMappingURL" => "RLIKE",
            
            "Accounts_credentials.blogurl" => "RLIKE",
                    
            "blogurl" => "RLIKE",
            
            "Accounts_piwik_details.last_checked" => "<=",
                    
            "last_checked" => "<=",
            
            "Accounts_piwik.last_modified" => "<=",
                    
            "last_modified" => "<=",
            
        );

    }

}
