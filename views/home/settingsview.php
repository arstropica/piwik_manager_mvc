<?php

    /**
    * Project: PiwikManager MVC
    * File: /views/home/settingsview.php
    * Purpose: Settings View Class.  Handles Display Logic for home/settings route.
    */
    namespace PiwikManager\Views\Home;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    /**
    * @Inject viewmodel
    * @Inject template
    * @Inject acl
    */
    class SettingsView extends \PiwikManager\Views\View
    {

        public function output ()
        {

            $defaults = array(
                array(
                    'option_name' => 'piwik_scheme',
                    'option_value' => '',
                    'option_desc' => 'HTTP(s)'
                ),
                array(
                    'option_name' => 'piwik_url',
                    'option_value' => '',
                    'option_desc' => 'Piwik URL'
                ),
                array(
                    'option_name' => 'piwik_api_key',
                    'option_value' => '',
                    'option_desc' => 'Piwik API Key'
                ),
                array(
                    'option_name' => 'wordpress_api_key',
                    'option_value' => '',
                    'option_desc' => 'WordPress Plugin API Key'
                ),
                array(
                    'option_name' => 'accounts_products',
                    'option_value' => $this->viewmodel->getAccountProducts(),
                    'option_desc' => 'Active Client Packages'
                ),
            );

            foreach ($defaults as $default) {

                $setting = $this->viewmodel->getSettings($default['option_name']);

                if ($setting)
                    $this->template->assignSection($setting[0], 'settings', 
                        $setting[0]['option_name']);
                else
                    $this->template->assignSection($default, 'settings', 
                        $default['option_name']);
            }
            
            $account_products = $this->viewmodel->getAccountProducts() ?: array();
            
            $this->template->assignSection($account_products, 'account_products');

            $this->template->assignMeta("Settings", "title");

            $this->template->assignMeta("Edit Global Settings.", "description");

            foreach ($this->viewmodel->getTabData() as $route => $tab) {

                if ($this->acl->isAllowed($this->request['controller'], $route)) {

                    $this->template->assignTab($route, $tab['title'], $tab['link'], 
                        strtolower($this->template->action) == $route);

                }

            }

            return $this->template->render($this->templateFile);

        }

    }
