<?php

/**
 * Project: PiwikManager MVC
 * File: /helpers/interfaces/searchable.php
 * Purpose: Interface for Result Searching
 */
namespace PiwikManager\Helpers\Interfaces;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');

Interface Searchable
{
    
    // Get the records using limit and offset
    public function find ($sherpa_id = null, $pagination = 20, $offset = 0, 
            $sort = null, $order = "ASC");

    public function setCriteria ($criteria, $field, $key = 0);

}
