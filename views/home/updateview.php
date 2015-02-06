<?php

    /**
    * Project: PiwikManager MVC
    * File: /views/home/updateview.php
    * Purpose: Update View Class.  Handles Display Logic for home/update route.
    */
    namespace PiwikManager\Views\Home;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    /**
    * @Inject viewmodel
    * @Inject template
    * @Inject acl
    */
    class UpdateView extends \PiwikManager\Views\View
    {

        public function update ()
        {

            $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'accounts';

            $result = false;

            $output = false;

            switch ($type) {

                case 'accounts':

                default: {

                    if (isset($_REQUEST['accounts'])) {

                        $accounts = $_REQUEST['accounts'];

                        $scope = isset($_REQUEST['scope']) ? $_REQUEST['scope'] : 'single';

                        switch ($scope) {

                            case 'single':

                            default:

                                $output = $this->viewmodel->updateRecords($accounts);

                                break;

                            case 'bulk':

                                $selected = array_filter($accounts, 
                                    function  ($account)
                                    {
                                        return isset($account['sel']) &&
                                        ($account['sel'] == 1);
                                });

                                $output = $this->viewmodel->updateRecords($selected);

                                break;
                        }
                    }

                    $result = $output ? (array_filter($output, 
                        function  ($result)
                        {
                            return $result['outcome'] == 0;
                        }) === array()) : false;

                    break;

                }

                case 'cron': {

                    ini_set('max_execution_time', 0);

                    $job_id = uniqid('job-');

                    $output = isset($_REQUEST['output']) ? $_REQUEST['output'] : 'echo'; 

                    $cronsafe = isset($_REQUEST['cronsafe']) ? $_REQUEST['cronsafe'] : false; 

                    $sherpa_id = isset($_REQUEST['sherpa_id']) ? $_REQUEST['sherpa_id'] : null; 

                    $operation = isset($_REQUEST['operation']) ? $_REQUEST['operation'] : 'ping'; 

                    $period = isset($_REQUEST['period']) ? rawurldecode($_REQUEST['period']) : '-12 Hours';
                    
                    $filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : false;

                    if ($filter) {

                        foreach ($filter as $filtername => $filterval) {

                                $this->viewmodel->setCriteria($filterval, 
                                    $filtername, $filtername);

                        }

                    }

                    insert_debugging_log("CRON Piwik {$operation} Attempt");

                    if ( $_SERVER['HTTP_X_FORWARDED_FOR'] == '172.26.61.102' && (! $this->viewmodel->cronReady(120, $job_id, $operation))) {

                        insert_debugging_log("CRON Piwik {$operation} Update: CRON Already running.");

                        exit;

                    }

                    $data = array();

                    if($cronsafe != "mdzzsa32fs4j"){

                        insert_debugging_log("CRON Piwik Update: Cheatin' ?");

                        if ($output == 'echo') {

                            echo("CRON Piwik Update: Cheatin' ?<br>\n");

                        }

                        exit; // this will prevent users from running this script as a hack

                    } else {

                        insert_debugging_log("CRON Piwik {$operation} Update: Start Job");

                    }

                    switch ($operation) {

                        case 'deactivate' : {

                            $accounts = $this->viewmodel->findDead(true);

                            break;
                        }
                        case 'activate' : {

                            if ($period) {

                                $criteria = date('Y-m-d H:i:s', strtotime($period));

                                $fields = array('Accounts_piwik_details.last_checked');

                                $this->viewmodel->setCriteria($criteria, $fields, "activate");

                            }

                            $accounts = $this->viewmodel->find($sherpa_id, 0, 0);

                            break;

                        } 
                        case 'ping' : {

                            if ($period) {

                                $criteria = date('Y-m-d H:i:s', strtotime($period));

                                $fields = array('Accounts_piwik_details.last_checked');

                                $this->viewmodel->setCriteria($criteria, $fields, "ping");

                            }

                            $criteria = "not null";

                            $fields = array('Accounts_piwik.site_id');

                            $this->viewmodel->setCriteria($criteria, $fields, "site_id");

                            $criteria = "not null";

                            $fields = array('Accounts_piwik.active');

                            $this->viewmodel->setCriteria($criteria, $fields, "active");

                            $accounts = $this->viewmodel->find($sherpa_id, 0, 0);

                            break;

                        }
                        case 'export' : {

                            $this->viewmodel->resetCriteria();

                            $accounts = $this->viewmodel->find($sherpa_id, 0, 0);

                            $this->viewmodel->outputCSV($accounts, true);

                            exit;

                            break;

                        }
                        default : {

                            $accounts = $this->viewmodel->find($sherpa_id, 0, 0);

                            break;

                        } 


                    }

                    $counters = array('total' => 0, 'success' => 0, 'active' => 0, 'alive' => 0);

                    if ($accounts) {

                        foreach($accounts as $acc_sherpa_id => $account) {

                            if ( $_SERVER['HTTP_X_FORWARDED_FOR'] == '172.26.61.102') {

                                if (! $this->viewmodel->cronReady(120, $job_id, $operation)) {

                                    insert_debugging_log("CRON Piwik {$operation} Update: CRON Already running.");

                                    exit;

                                }

                            }

                            $counters['total'] ++;

                            $success = false;

                            switch($operation) {

                                case 'ping' : {

                                    $success = $this->viewmodel->liveCheck($account['sherpa_id']);

                                    break;

                                }

                                case 'activate' : {

                                    if (empty($account['domain']) === false) {

                                        $account['activate'] = 1;

                                        $result = $this->viewmodel->updateRecords(array($acc_sherpa_id => $account));

                                        $success = $result ? (array_filter($result, 
                                            function  ($_result)
                                            {
                                                return $_result['outcome'] == 0;
                                            }) === array()) : false;

                                    }

                                    break;

                                }

                                case 'deactivate' : {

                                    if ($account['active']) {

                                        $account['activate'] = 0;

                                        $result = $this->viewmodel->updateRecords(array($acc_sherpa_id => $account));

                                        $success = $result ? (array_filter($result, 
                                            function  ($result)
                                            {
                                                return $result['outcome'] == 0;
                                            }) === array()) : false;

                                    }

                                    break;

                                }

                            }

                            $this->viewmodel->resetCriteria();

                            // Get updated record
                            $records = $this->viewmodel->find($acc_sherpa_id, 0, 0, null, "ASC", false);

                            if ($records && isset($records[$acc_sherpa_id])) {

                                $record = $records[$acc_sherpa_id];

                            } else {

                                $record = $account;

                            }

                            $data[$acc_sherpa_id] = array(

                                "sherpa_id" => $record['sherpa_id'],

                                "description" => $record['description'],

                                "active" => $record['active'],

                                "alive" => $record['alive'],

                            );

                            $counters['active'] += $record['active'] ? 1 : 0;

                            $counters['alive'] += $record['alive'] ? 1 : 0;

                            if ($success) {

                                $counters['success'] ++;

                            }

                        }

                    }

                    if ($output == 'echo') {

                        $content = "<table border='1'>";

                        $content .= "<tr><th>Account Name</th><th>Sherpa ID</th><th>Activated</th><th>Online</th><tr>";

                    }

                    foreach ($data as $acc_sherpa_id => $result) {

                        insert_debugging_log(rawurldecode($result['description']) . " <{$result['sherpa_id']}> : Active: " . ($result['active'] ? "Yes" : "No") . ", Online: " . ($result['alive'] ? "Yes" : "No"));

                        if ($output == 'echo') {

                            $content .= ("<tr><td>" . rawurldecode($result['description']) . "</td><td>{$result['sherpa_id']}</td><td>" . ($result['active'] ? "Yes" : "No") . "</td><td>" . ($result['alive'] ? "Yes" : "No") . "</td></tr>");

                        }

                    }

                    insert_debugging_log("Total Accounts: {$counters['total']}, Active Accounts: {$counters['active']}, Online Accounts: {$counters['alive']}");

                    insert_debugging_log("Accounts affected: {$counters['success']}");

                    if ($output == 'echo') {

                        $content .= "</table>";

                        echo $content;

                    }

                    insert_debugging_log("CRON Piwik {$action} Update: End Job");

                    if ($output == 'json') {

                        echo json_encode(array("data" => $data, "counts" => $counters));

                    }

                    exit;

                    break;

                }

                case 'settings': {

                    if (isset($_REQUEST['settings'])) {

                        $settings = $_REQUEST['settings'];

                        $result = true;

                        foreach ($settings as $option_name => $option) {

                            $option_value = isset($option['option_value']) ? $option['option_value'] : '';

                            $option_desc = isset($option['option_desc']) ? $option['option_desc'] : '';

                            $result = $this->viewmodel->updateSettings($option_name, 
                                $option_value, $option_desc) ? $result : false;
                        }
                    }

                    break;

                }
            }

            $this->viewmodel->redirect($result);

        }

        public function output ()
        {

            return false;

        }

    }
