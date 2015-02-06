<?php

/**
 * Project: PiwikManager MVC
 * File: /helpers/interfaces/pageable.php
 * Purpose: Interface for Result Pagination
 */
namespace PiwikManager\Helpers\Interfaces;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');

Interface Pageable
{
    
    // The number of records which will be shown on each page
    public function getRecordsPerPage ();
    
    // The page currently being shown
    public function getCurrentPage ();
    
    // The total number of records being paged through.
    // Used to calculate the total number of pages.
    public function getTotalResults ();

}
