<?php

/**
 * Project: PiwikManager MVC
 * File: /controllers/controller.php
 * Purpose: abstract class from which controllers extend
 */
namespace PiwikManager\Controllers;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');

/**
 * @Inject viewmodel
 * @Inject template
 * @Inject view
 * @Inject acl
 */
abstract class Controller
{

    public $request;

    public $viewmodel;

    public $template;

    public $view;

    public $acl;

    public function __construct (Array $request = array())
    {

        $this->request = $request;
    
    }

}

?>
