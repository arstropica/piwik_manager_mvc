<?php

    /**
    * Project: PiwikManager MVC
    * File: /templates//template.php
    * Purpose: Base Template Class.  Interacts with view to output HTML.
    */
    namespace PiwikManager\Templates;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    /**
    * @Inject acl
    */
    abstract class Template
    {

        protected static $data;

        protected $_template;

        public $request;

        public $acl;

        public $controller;

        public $action;

        public function __construct (Array $request)
        {

            $this->request = $request;

            $this->controller = $request['controller'];

            $this->action = $request['action'];

            $this->_load();

        }

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
                    'navigation' => null,
                    'records' => null,
                    'record' => null,
                    'action' => strtolower($this->action)
                )
            );

        }

        public function assign ($value, $field = '')
        {

            if ($field == '')
                self::$data = $value;
            else
                self::$data[$field] = $value;

        }

        public function assignSection ($value, $section, $field = '')
        {

            if ($field == '')
                self::$data['sections'][$section] = $value;
            else
                self::$data['sections'][$section][$field] = $value;

        }

        public function assignMeta ($value, $section = false)
        {

            if ($section)
                self::$data['meta'][$section] = $value;
            else
                self::$data['meta'] = $value;

        }

        public function appendSection ($value, $section, $field = '')
        {

            if ($field == '')
                self::$data['sections'][$section][] = $value;
            else
                self::$data['sections'][$section][$field][] = $value;

        }

        public function assignTab ($route, $title, $link, $current = false)
        {

            if ($title && $link && $route) {

                if (! isset(self::$data['sections']['header']['tabs'])) {

                    self::$data['sections']['header']['tabs'] = array();
                }

                self::$data['sections']['header']['tabs'][strtolower($route)] = array(
                    'title' => $title,
                    'link' => $link,
                    'current' => $current
                );
            }

        }

        public function addMessage ($message, $type = "updated")
        {

            if (! isset(self::$data['sections']['header']['messages']))

                self::$data['sections']['header']['messages'] = array();

            self::$data['sections']['header']['messages'][] = "<div class=\"message {$type}\"><p>{$message}</p></div>";

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

        public function nldecode($string) { 
            $string = str_replace(array("\r\n", "\r", "\n", "\\r\\n", "\\r", "\\n"), "\n", utf8_encode($string)); 
            return $string; 
        } 

    }
