<?php

/**
 * Project: PiwikManager MVC
 * File: /templates/home/hometemplate.php
 * Purpose: Base Template Class.  Interacts with view to output HTML.
 */
namespace PiwikManager\Templates\Home;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');

/**
 * @Inject acl
 */
class HomeTemplate extends \PiwikManager\Templates\Template
{

    public $defaultOrderBy = 'Accounts_name';

    public $defaultOrder = 'ASC';

    private function _load ()
    {

        self::$data = array(
                'meta' => array(
                        'title' => null,
                        'description' => null
                ),
                'sections' => array(
                        'header' => array(
                                'tabs' => null,
                                'content' => null,
                                'messages' => null
                        ),
                        'footer' => array(
                                'content' => null
                        ),
                        'filters' => null,
                        'search' => null,
                        'sort' => array(
                                'orderby' => $this->defaultOrderBy,
                                'order' => $this->defaultOrder
                        ),
                        'navigation' => null,
                        'records' => null,
                        'record' => null,
                        'action' => strtolower($this->action),
                        'statistics' => array(
                                'active' => 0,
                                'alive' => 0,
                                'total' => 0
                        ),
                        'active_products' => null,
                )
        );
    
    }

    public function render ($view = 'Index', $direct_output = TRUE)
    {

        if (substr($view, - 6) == ".phtml") {
            
            $file = $view;
        } else {
            
            $file = PIWIKAPP . '/templates/' . strtolower($this->controller) .
                     '/' . strtolower($view) . '.phtml';
        }
        
        if (file_exists($file)) {
            
            /**
             * trigger render to include file when this model is destroyed
             * if we render it now, we wouldn't be able to assign variables
             * to the view!
             */
            $this->_template = $file;
        } else {
            
            return $file . ": view file doesn't exist.";
        }
        
        // Turn output buffering on, capturing all output
        if ($direct_output !== TRUE) {
            
            ob_start();
        }
        
        // ACL
        $acl = $this->acl;
        
        // Parse data variables into local variables
        $data = self::$data;
        
        // Get template
        include ($this->_template);
        
        // Get the contents of the buffer and return it
        if ($direct_output !== TRUE) {
            
            return ob_get_clean();
        }
    
    }

}
