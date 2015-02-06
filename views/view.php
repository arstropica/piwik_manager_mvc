<?php

/**
 * Project: PiwikManager MVC
 * File: /views/view.php
 * Purpose: Abstract View Class extends other views.  Handles Display Logic. Interacts with viewmodel and template to output HTML.
 */
namespace PiwikManager\Views;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');

/**
 * @Inject viewmodel
 * @Inject template
 * @Inject acl
 */
abstract class View
{

    public $viewModel;

    public $template;

    public $request;

    public $acl;

    protected $templateFile;

    public function __construct (Array $request)
    {

        $this->request = $request;
        
        $this->templateFile = PIWIKAPP . '/templates/' .
                 strtolower($request['controller']) . '/' .
                 strtolower($request['action']) . '.phtml';
    
    }

    public function _handle_di ()
    {

        if (\PiwikManager\Helpers\get_http_response_code() == '412') {
            
            $this->template->addMessage("Operation Unsuccessful.", "error");
        } elseif (\PiwikManager\Helpers\get_http_response_code() == "302") {
            
            $this->template->addMessage("Operation Successful.", "updated");
        }
        
        // Add Script(s) to template footer
        $this->template->appendSection(
                '<script type="text/javascript" src="/_js/jquery-date-range-picker/moment.min.js"></script>', 
                'footer', 'content');
        $this->template->appendSection(
                '<script type="text/javascript" src="/_js/piwikmanager.js"></script>', 
                'footer', 'content');
    
    }

    public function output ()
    {

        $this->template->render();
    
    }

}
