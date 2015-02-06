<?php

    /**
    * Project: PiwikManager MVC
    * File: /views/home/indexview.php
    * Purpose: Index View Class.  Handles Display Logic for home/index route.
    */
    namespace PiwikManager\Views\Home;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    /**
    * @Inject viewmodel
    * @Inject template
    * @Inject acl
    */
    class IndexView extends \PiwikManager\Views\View
    {

        public function output ()
        {

            // echo "<pre>" . print_r($this, true) . "</pre>";
            $pageNo = isset($this->viewmodel->request['args'][0]) ? $this->viewmodel->request['args'][0] : 1;

            $perPage = $this->viewmodel->getRecordsPerPage();

            $startingRecord = $perPage * ($pageNo - 1);

            $filters = isset($_REQUEST['filters']) ? $_REQUEST['filters'] : false;

            if ($filters) {

                foreach ($filters as $filter => $filterParams) {

                    if ($filterParams['criteria']) {

                        $this->viewmodel->setCriteria($filterParams['criteria'], 
                            $filterParams['fields'], $filter);

                        $this->template->assignSection($filterParams, 'filters', 
                            $filter);
                    }
                }

                // echo "<pre>" . print_r(array($this->viewmodel->searchTerm,
                // $this->viewmodel->fieldNames), true) . "</pre>";
            }

            $sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : array(
                'orderby' => 'Accounts_name',
                'order' => 'ASC'
            );

            if ($sort) {

                $this->template->assignSection($sort, 'sort');
            }

            $statistics = $this->viewmodel->getAccountStats();

            $this->template->assignSection($statistics, 'statistics');

            $result = $this->viewmodel->find(null, $perPage, $startingRecord, 
                $sort['orderby'], $sort['order']);

            $totalRecords = $this->viewmodel->getTotalResults();

            $totalPages = ceil($totalRecords / $perPage);

            $navigation = array(
                'totalRecords' => $totalRecords,
                'totalPages' => $totalPages,
                'currentPage' => $pageNo
            );

            $this->template->assignSection($navigation, 'navigation');

            $this->template->assignMeta("Dashboard Accounts", "title");

            $this->template->assignMeta("Manage Piwik Accounts.", "description");

            foreach ($result as $record) {

                $this->template->appendSection($record, 'records');
            }

            foreach ($this->viewmodel->getTabData() as $route => $tab) {

                if ($this->acl->isAllowed($this->request['controller'], $route)) {

                    $this->template->assignTab($route, $tab['title'], $tab['link'], 
                        strtolower($this->template->action) == $route);

                }

            }

            return $this->template->render($this->templateFile);

        }

    }
