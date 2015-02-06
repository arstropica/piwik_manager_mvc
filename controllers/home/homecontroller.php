<?php

/**
 * Project: PiwikManager MVC
 * File: /controllers/home/homecontroller.php
 * Purpose: controller for the home of the app.
 */
namespace PiwikManager\Controllers\Home;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');

/**
 * @Inject viewmodel
 * @Inject template
 * @Inject view
 * @Inject acl
 */
class HomeController extends \PiwikManager\Controllers\Controller
{

    /**
     * Default method
     */
    public function index ()
    {

        echo $this->view->output();
    
    }

    /**
     * Edit Record
     */
    public function edit ()
    {

        echo $this->view->output();
    
    }

    /**
     * View Record
     */
    public function view ()
    {

        echo $this->view->output();
    
    }

    /**
     * Search Records
     */
    public function search ()
    {

        echo $this->view->output();
    
    }

    /**
     * Update Record
     */
    public function update ()
    {

        $this->view->update();
    
    }

    /**
     * Ping Record
     */
    public function ping ()
    {

        $type = isset($_GET['type']) ? $_GET['type'] : 'send';
        
        switch ($type) {
            
            case 'send':
            
            default:
                
                echo $this->view->send();
                
                break;
            
            case 'listen':
                
                echo $this->view->listen();
                
                break;
        }
        
        return;
    
    }

    /**
     * Output Settings
     */
    public function settings ()
    {

        echo $this->view->output();
    
    }

    /**
     * Admin Tab
     */
    public function admin ()
    {

        echo $this->view->output();
    
    }

}
