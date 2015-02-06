<?php

    /**
    * Project: PiwikManager MVC
    * File: /views/home/viewview.php
    * Purpose: View View Class.  Handles Display Logic for home/view route.
    */
    namespace PiwikManager\Views\Home;

    use PiwikManager;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    /**
    * @Inject viewmodel
    * @Inject template
    * @Inject acl
    */
    class ViewView extends \PiwikManager\Views\View
    {

        public function output ()
        {

            $result = null;

            $sherpa_id = isset($this->viewmodel->request['args'][0]) ? $this->viewmodel->request['args'][0] : $this->acl->get_sherpa_id();

            if ($sherpa_id) {

                $this->viewmodel->resetCriteria();
                
                $result = $this->viewmodel->find($sherpa_id, 1, 0);
                
                if ($result && isset($result[$sherpa_id])) {

                    $record = $result[$sherpa_id];

                    $this->template->assignSection($record, 'record');

                    $this->template->assignMeta(
                        "Site: " .
                        (isset($record['domain']) ? ('<a href="' .
                            $record['protocol'] . '://' . $record['domain'] .
                            '" target="_blank" title="' . $record['domain'] .
                            '">' . $record['description'] . '</a>') : $record['description']), 
                        "title");

                    $this->template->assignMeta("View Account Settings.", 
                        "description");
                        
                    if ($record['domain']) {

                        $piwik_site = $this->viewmodel->piwik->getSiteFromDomain($record['domain']);

                        if ($piwik_site) {

                            if ($piwik_site['group'] != $record['sherpa_id']) {

                                $record['duplicate'] = $piwik_site['group'];

                            }

                        }

                        if ($record['duplicate']) {

                            $this->template->addMessage("Duplicate domain found.  See <a href='" . PIWIKACTION_BASE . "home/edit/" . $record['duplicate'] . "'>this record</a> for more information.", 'error');

                        }

                    }

                }
                
            }

            foreach ($this->viewmodel->getTabData() as $route => $tab) {

                if ($route == "index")
                    $route = "view";

                if ($this->acl->isAllowed($this->request['controller'], $route)) {

                    $this->template->assignTab($route, $tab['title'], $tab['link'], 
                        strtolower($this->template->action) == $route);

                }

            }

            return $this->template->render($this->templateFile);

        }

    }
