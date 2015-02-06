<?php

    /**
    * Project: PiwikManager MVC
    * File: /views/home/searchview.php
    * Purpose: Search View Class.  Handles Output Logic for home/search route.
    */
    namespace PiwikManager\Views\Home;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    /**
    * @Inject viewmodel
    * @Inject template
    * @Inject acl
    */
    class SearchView extends \PiwikManager\Views\View
    {

        public function output ()
        {

            // $this->templateFile = PIWIKAPP . '/templates/' .
            // strtolower($this->template->controller) . '/' . strtolower("Index") .
            // '.phtml';
            $criteria = isset($_REQUEST['s']) ? $_REQUEST['s'] : false;

            $fields = isset($_REQUEST['fields']) ? $_REQUEST['fields'] : array(
                'Accounts.Accounts_name',
                'Accounts_piwik_details.domain'
            );

            if ($criteria) {

                $this->viewmodel->setCriteria($criteria, $fields, 's');

                $this->template->assignSection($criteria, 'search', 'term');

                $this->template->assignSection($fields, 'search', 'fields');
            }

            $action = ($criteria ? '/home/search/' : '/home/index/');

            $this->template->assignSection($action, 'search', 'action');

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

            $this->template->assignMeta(
                "Search Results for \"" . htmlspecialchars($criteria) . "\".", 
                "description");

            foreach ($result as $record) {

                $this->template->appendSection($record, 'records');
            }

            foreach ($this->viewmodel->getTabData() as $route => $tab) {

                if ($route == "index")
                    $route = "search";

                if ($this->acl->isAllowed($this->request['controller'], $route)) {

                    $this->template->assignTab($route, $tab['title'], $tab['link'], 
                        strtolower($this->template->action) == $route);

                }

            }

            return $this->template->render($this->templateFile);

        }

    }
