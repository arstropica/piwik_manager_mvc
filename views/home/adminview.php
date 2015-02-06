<?php

    /**
    * Project: PiwikManager MVC
    * File: /views/home/adminview.php
    * Purpose: Admin View Class.  Handles Output Logic for home/admin route.
    */
    namespace PiwikManager\Views\Home;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    /**
    * @Inject viewmodel
    * @Inject template
    * @Inject acl
    */
    class AdminView extends \PiwikManager\Views\View
    {

        public function output ()
        {

            $this->viewmodel->resetCriteria();
            
            $pageNo = 1;

            $perPage = 0;

            $startingRecord = 0;

            $filters = isset($_REQUEST['filters']) ? $_REQUEST['filters'] : false;

            if ($filters) {

                foreach ($filters as $filter => $filterParams) {

                    if ($filterParams['criteria']) {

                        $this->viewmodel->setCriteria($filterParams['criteria'], 
                            $filterParams['fields'], $filter);

                        $this->template->assignSection($filterParams, 'filters', 
                            $filter);
                            
                            if ($filter == 'flag') {
                                
                                switch ($filterParams['criteria']) {
                                    
                                    case "include" :
                                    case "null" :
                                    case null : 
                                    default : {
                                        
                                        $this->viewmodel->filterActiveProducts(true);
                                        
                                        break;
                                        
                                    }
                                    
                                    case "exclude" : {
                                        
                                        $this->viewmodel->filterActiveProducts(false);
                                        
                                    }
                                    
                                }
                                
                            }
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
            
            $active_products = $this->viewmodel->active_products;
            
            $this->template->assignSection($active_products, 'active_products');

            $result = $this->viewmodel->find(null, $perPage, $startingRecord, 
                $sort['orderby'], $sort['order']);

            $totalRecords = $this->viewmodel->getTotalResults();

            $totalPages = ceil($totalRecords / 1);

            $navigation = array(
                'totalRecords' => $totalRecords,
                'totalPages' => $totalPages,
                'currentPage' => $pageNo
            );

            $this->template->assignSection($navigation, 'navigation');

            $this->template->assignMeta("Account Admin", "title");

            $this->template->assignMeta(
                "Perform batch account administration here.", 
                "description");

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
