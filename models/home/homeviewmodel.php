<?php

    /**
    * Project: PiwikManager MVC
    * File: /models/home/homeviewmodel.php
    * Purpose: Base MVC model.  Handles Application's Business logic. Interacts with view and domain model.
    */
    namespace PiwikManager\Models\Home;

    use \PiwikManager\Helpers\Interfaces;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    /**
    * @Inject model
    * @Inject wp
    * @Inject piwik
    * @Inject acl
    */
    class HomeViewModel implements Interfaces\Pageable, Interfaces\Searchable
    {

        public $request;

        public $acl;

        public $model;

        public $piwik;

        public $wp;

        public $searchTerm;

        public $fieldNames;

        public $numberOfAccounts;
        
        public $active_products;

        protected $records;

        protected $accounts;

        protected $urls;

        protected $usermodel;

        protected $page = 1;

        /**
        * @Inject model
        * @Inject acl
        */
        public $_newrecord = null;

        public function __construct (Array $request = array())
        {

            $this->request = $request;

            $this->_init();

        }

        private function _init ()
        {

            $this->_newrecord = array(
                'id' => null,
                'saved' => 0,
                'update' => 0,
                'match' => 0,
                'site_id' => null,
                'alive' => null,
                'active' => null,
                'last_checked' => null,
                'last_checked_elapsed' => 'Never',
                'last_modified' => null,
                'last_modified_elapsed' => 'Never',
                'sherpa_id' => null,
                'description' => null,
                'domain' => null,
                'domains' => null,
                'protocol' => 'http',
                'type' => 'dashboard',
                'piwikurl' => null,
                'brand' => null,
                'team' => null,
                'products' => null,
                'parent' => null,
                'sugar_id' => null,
                'flag' => null,
                'notes' => null,
                'duplicate' => 0,
            );

            $this->records = array();

            $this->accounts = array();

            $this->urls = array();

            $this->searchTerm = array();

            $this->fieldNames = array();

        }

        public function _handle_di()
        {

            $this->filterActiveProducts();

            $this->filterUnflaggedAccounts();

        }

        public function filterActiveProducts($include = true)
        {

            $active_products = $this->getSettings('accounts_products');

            if (! $active_products || (isset($active_products[0]) && array() === array_filter($active_products[0]['option_value'])))
            {

                \PiwikManager\Templates\Home\HomeTemplate::addMessage('No active products have been selected.', 'updated');

            } else {

                $this->setCriteria($active_products[0]['option_value'], 'Accounts_products.Accounts_products_name', ($include ? '' : '-') . 'accounts_products');
                
                $this->active_products = $active_products[0]['option_value'];

            }

        }

        public function filterUnflaggedAccounts()
        {

            $this->setCriteria("null", 'Accounts_piwik_details.flag', 'flag');

        }

        public function setCriteria ($criteria, $fields, $key = 0)
        {

            $this->searchTerm[$key] = $criteria;

            if (! is_array($fields)) {

                $fields = array(
                    $fields
                );
            }

            $this->fieldNames[$key] = $fields;

        }

        public function resetCriteria()
        {

            $this->searchTerm = array();

            $this->fieldNames = array();

        }

        /**
        * Search for Accounts
        *
        * @param mixed $sherpa_id            
        * @param int $pagination            
        * @param int $offset            
        * @param mixed $sort            
        * @param string $order            
        * @param bool $cache            
        */
        public function find ($sherpa_id = null, $pagination = 20, $offset = 0, 
            $sort = null, $order = "ASC", $cache = true)
        {

            // Output
            $records = array();

            // Filter by Sherpa ID (if any)
            if ($sherpa_id)
                $this->setCriteria($sherpa_id, 
                    array(
                        'Accounts.Accounts_sherpa_id'
                    ), "sherpa_id");

            // Get filter criteria (if any)
            $criteria = $this->searchTerm;

            // Get filter fields (if any)
            $fields = $this->fieldNames;

            // echo "<pre>" . print_r($fields, true) . "</pre>";
            // Get Accounts Record
            $accounts = $this->model->findAccounts($criteria, $fields, $pagination, 
                $offset, $sort, $order, true);

            // Get Accounts URLs
            $account_urls = $this->model->getAccountURLs($sherpa_id);

            // Set Accounts URLs in Class
            $this->urls = $account_urls;

            // Add Accounts URLs to Account Record
            if ($accounts['results']) {

                // Count Accounts Records
                $this->numberOfAccounts = $accounts['count'];

                foreach ($accounts['results'] as $idx => $account) {

                    // error_log(var_export($account, true), 3, $_SERVER['DOCUMENT_ROOT'] . '/piwik/log/ping.log');

                    $account_sherpa_id = $account['Accounts_sherpa_id'];

                    if (isset($account_urls[$account_sherpa_id])) {

                        $accounts['results'][$idx] = array_merge(
                            $account_urls[$account_sherpa_id], $account);
                    }
                }

                // Set Accounts Record in Class
                $this->accounts = $accounts['results'];

                // Parse Account Records for display
                foreach ($this->accounts as $account) {

                    $account_sherpa_id = $account['Accounts_sherpa_id'];

                    $records[$account_sherpa_id] = $this->parseAccount($account);
                }
            }

            // Set Accounts Records for display
            return ($cache) ? $this->records = $records : $records;

        }

        public function findDead($cache = false)
        {

            $records = array();

            $accounts = $this->model->findDeadAccounts();

            // Get Accounts URLs
            $account_urls = $this->model->getAccountURLs();

            // Set Accounts URLs in Class
            $this->urls = $account_urls;

            // Add Accounts URLs to Account Record
            if ($accounts['results']) {

                foreach ($accounts['results'] as $idx => $account) {

                    // error_log(var_export($account, true), 3, $_SERVER['DOCUMENT_ROOT'] . '/piwik/log/ping.log');

                    $account_sherpa_id = $account['Accounts_sherpa_id'];

                    if (isset($account_urls[$account_sherpa_id])) {

                        $accounts['results'][$idx] = array_merge(
                            $account_urls[$account_sherpa_id], $account);
                    }
                }

                // Set Accounts Record in Class
                $this->accounts = $accounts['results'];

                // Count Accounts Records
                $this->numberOfAccounts = $accounts['count'];

                // Parse Account Records for display
                foreach ($this->accounts as $account) {

                    $account_sherpa_id = $account['Accounts_sherpa_id'];

                    $records[$account_sherpa_id] = $this->parseAccount($account);
                }
            }

            // Set Accounts Records for display
            return ($cache) ? $this->records = $records : $records;

        }

        /**
        * Prepare Account array for display
        *
        * @param array $account            
        */
        public function parseAccount ($account)
        {

            $output = $this->_newrecord;

            $output['id'] = $account['id'];

            $output['saved'] = (int) ($account['status'] == 'local');

            $output['match'] = isset($account['site_id']) ? 1 : 0;

            $output['site_id'] = isset($account['site_id']) ? $account['site_id'] : null;

            $output['alive'] = isset($account['alive']) ? $account['alive'] : null;

            $output['active'] = isset($account['active']) ? $account['active'] : null;

            $output['update'] = (int) (isset($account['last_modified'], 
                $account['Accounts_credentials_datetime']) &&
                (strtotime($account['Accounts_credentials_datetime']) >
                    strtotime($account['last_modified'])));

            $output['last_checked'] = isset($account['last_checked']) ? $account['last_checked'] : null;

            $output['last_checked_elapsed'] = $account['last_checked'] ? $this->time_elapsed_string(
                strtotime($account['last_checked'])) : 'Never';

            $output['last_modified'] = isset($account['last_modified']) ? $account['last_modified'] : null;

            $output['last_modified_elapsed'] = $account['last_modified'] ? $this->time_elapsed_string(
                strtotime($account['last_modified'])) : 'Never';

            $output['sherpa_id'] = $account['Accounts_sherpa_id'];

            $output['description'] = rawurldecode($account['Accounts_name']);

            $output['domains'] = array_map('rawurldecode', 
                array_filter(
                    array_intersect_key($account, 
                        array(
                            'DomainMappingURL' => null,
                            'DesignSherpaURL' => null,
                            'blogurl' => null
            ))));

            $output['domain'] = isset($account['domain']) ? $account['domain'] : null;

            $output['protocol'] = empty($account['protocol']) ? 'http' : $account['protocol'];

            $output['type'] = isset($account['type']) ? $account['type'] : 'dashboard';

            $output['piwikurl'] = $account['site_id'] ? $this->piwik->getDashboardURL(
                $account['site_id']) : null;

            $output['brand'] = isset($account['Accounts_sherpa_brand']) ? $account['Accounts_sherpa_brand'] : null;

            $output['team'] = (isset($account['Team_name']) && ! in_array($account['Team_name'], array('Global', 'NULL'))) ? $account['Team_name'] : null;

            $output['products'] = isset($account['products']) ? $account['products'] : null;

            $output['parent'] = isset($account['Accounts_parent_name']) ? rawurldecode($account['Accounts_parent_name']) : null;

            $output['sugar_id'] = isset($account['Accounts_sugar_id']) ? $account['Accounts_sugar_id'] : null;

            $output['flag'] = isset($account['flag']) ? $account['flag'] : null;

            $output['notes'] = isset($account['notes']) ? rawurldecode($account['notes']) : null;

            if ($account['status'] == 'imported' || ! isset($output['domain'])) {

                $siteurls = array_map('rawurldecode', 
                    array_filter(
                        array_intersect_key($account, 
                            array(
                                'DomainMappingURL' => null,
                                'DesignSherpaURL' => null,
                                'blogurl' => null
                ))));

                foreach ($siteurls as $urltype => $siteurl) {

                    $domain = parse_url($this->addhttp(rawurldecode($siteurl)), 
                        PHP_URL_HOST);

                    $output['domain'] = $domain;

                    $piwik_site = $this->piwik->getSiteFromDomain($domain);

                    if ($piwik_site) {

                        if ($piwik_site['group'] == $account['Accounts_sherpa_id']) {

                            $output['update'] = 1;

                            $output['site_id'] = $piwik_site['idsite'];

                            $output['match'] = 1;

                            $output['piwikurl'] = $this->piwik->getDashboardURL(
                                $output['site_id']);

                            break;

                        } else {

                            $output['duplicate'] = $piwik_site['group'];

                        }

                    }
                }

                if (! $output['match']) {

                    if (isset($siteurls['DomainMappingURL'])) {

                        $output['domain'] = parse_url(
                            $this->addhttp(
                                rawurldecode($siteurls['DomainMappingURL'])), 
                            PHP_URL_HOST);
                    } elseif ($siteurls) {

                        reset($siteurls);

                        $output['domain'] = parse_url(
                            $this->addhttp(rawurldecode(current($siteurls))), 
                            PHP_URL_HOST);
                    }
                }
            }

            return $output;

        }

        public function outputCSV($accounts, $download = true, $filename = null)
        {
            if ($accounts && is_array($accounts)) {

                $active_products = $this->getSettings('accounts_products');

                if ($download) {

                    $filename = $filename ?: 'Piwik-Accounts (' . date('Y-m-d') . ')';

                    header('Content-Type: application/csv');

                    header('Content-Disposition: attachment; filename=' . $filename . '.csv');

                    header('Pragma: no-cache');

                }

                ob_start();

                $df = fopen("php://output", 'w');

                $heading = array_keys(reset($accounts));

                $heading[] = 'active product';

                $domains_p = array_search('domains', $heading, true);

                $domains_k = array('DesignSherpaURL', 'DomainMappingURL', 'blogurl');

                array_splice($heading, $domains_p, 1, $domains_k);

                $products_p = array_search('products', $heading, true);

                $products_k = array('product 1', 'product 2', 'product 3', 'product 4', 'product 5');

                array_splice($heading, $products_p, 1, $products_k);

                $heading = array_map('ucwords', $heading);

                fputcsv($df, $heading);

                foreach ($accounts as $account) {

                    foreach (array('domains', 'products') as $multiv) {

                        switch ($multiv) {

                            case 'products' : {

                                if (is_array($account[$multiv])) {

                                    ${$multiv} = array_pad($account[$multiv], 5, '');

                                } elseif (isset($account[$multiv])) {

                                    ${$multiv} = array_pad(array_map('trim', explode(",", $account[$multiv], 5)), 5, '');

                                } else {

                                    ${$multiv} = array_pad(array(), 5, '');

                                }

                                $active_product = empty($active_products[0]['option_value']) ? true : array_intersect($active_products[0]['option_value'], array_filter(array_map('trim', explode(",", $account[$multiv]))));

                                $account['active product'] = $active_product ? 'True' : 'False';

                                ${$multiv} = array_combine($products_k, ${$multiv});

                                break;

                            }

                            case 'domains' : {

                                if (is_array($account[$multiv])) {

                                    ${$multiv} = array_pad($account[$multiv], 3, '');

                                } else {

                                    ${$multiv} = array_pad(array_map('trim', explode(",", $account[$multiv])), 3, '');

                                }

                                ${$multiv} = array_combine($domains_k, ${$multiv});

                                break;

                            }

                        }

                        ${$multiv} = array_map('rawurldecode', ${$multiv});

                        $this->_array_splice($account, ${$multiv . "_p"}, 1, ${$multiv});

                    }

                    $account['sugar_id'] = "https://digitalsherpa.sugarondemand.com/index.php?module=Accounts&offset=1&action=DetailView&record={$account['sugar_id']}";

                    fputcsv($df, $account);

                }

                fclose($df);

                if ($download) {

                    echo ob_get_clean();

                    exit;

                } else {

                    return ob_get_clean();

                }

            }

            return false;

        }

        public function updateRecords ($records = null)
        {

            $result = array();

            if (isset($records)) {

                foreach ($records as $sherpa_id => $record) {

                    $filtered = array();

                    foreach ($record as $key => $value) {

                        $filtered[$key] = $this->sanitize($value);
                        
                        if ($filtered[$key] === 0 || $filtered[$key] === '0') {
                            
                            $filtered[$key] = null;
                            
                        }
                    }

                    $filtered['last_modified'] = date('Y-m-d H:i:s');

                    $filtered['active'] = $filtered["activate"] ?  : null;

                    $filtered['flag'] = $filtered["flag"] ?  : null;

                    $filtered['notes'] = $filtered["notes"] ?  : null;

                    $filtered['alive'] = $filtered['active'] ? $filtered['alive'] : null;

                    // Check Piwik Account

                    $idsite = $this->hasPiwikAccount($filtered);

                    // If site already exists
                    if ($idsite) {

                        // If site id is different
                        if ($filtered['site_id'] != $idsite) {

                            $duplicates = $this->model->findAccounts(array('duplicates' => $idsite, 'activefilter' => 1), array('duplicates' => array('site_id'), 'activefilter' => array('active')));

                            // If there are active records with this site id and a different sherpa id
                            if ($duplicates['count'] > 0 && ($duplicates['results'] && $duplicates['results'][0]['sherpa_id'] != $filtered['sherpa_id'])) {

                                // Matching Piwik Account Not Found
                                $filtered['match'] = 0;

                            } else {

                                // Matching Piwik Account Found
                                $filtered['match'] = 1;

                            }

                        } else {

                            // Matching Piwik Account Found
                            $filtered['match'] = 1;

                        }

                        // If matching Piwik Account found
                        if ($filtered['match']) {

                            // Assign new or existing site id
                            $filtered['site_id'] = $idsite;

                            // If updating Piwik Account
                            if ($filtered['force']) {

                                // TO DO - Add Error Handling
                                $result[$sherpa_id]['piwik'] = $this->updatePiwikAccount(
                                    $filtered);

                            } else {

                                $this->joinPiwikMetaSite($filtered['site_id'], $filtered);

                            }

                            // TO DO - Add Error Handling
                            $result[$sherpa_id]['wordpress'] = $this->updateWordPressSite(
                                $filtered);

                            $alive = $this->liveCheck($filtered['sherpa_id']);

                            $filtered['alive'] = $alive ? 1 : null;

                            $filtered['last_checked'] = date('Y-m-d H:i:s');

                        }

                    } elseif($filtered["activate"]) {

                        // TO DO - Add Error Handling
                        $filtered['site_id'] = $this->addPiwikAccount($filtered);

                        if (isset($filtered['site_id']) && $filtered['site_id']) {

                            $result[$sherpa_id]['piwik'] = $filtered['site_id'];

                            // TO DO - Add Error Handling
                            $result[$sherpa_id]['wordpress'] = $this->updateWordPressSite(
                                $filtered);

                            $alive = $this->liveCheck($filtered['sherpa_id'], 0);

                            $filtered['alive'] = $alive ? 1 : null;

                            $filtered['last_checked'] = date('Y-m-d H:i:s');

                            $filtered['match'] = 1;
                        }

                    } else {

                        $result[$sherpa_id]['piwik'] = 1;

                        $result[$sherpa_id]['wordpress'] = 1;

                    }

                    $filtered['site_id'] = $filtered['site_id'] ?: null;

                    $result[$sherpa_id]['dashboard'] = $this->model->updateAccount(
                        $filtered['id'], $filtered['sherpa_id'], 
                        $filtered['description'], $filtered['domain'], 
                        $filtered['protocol'], $filtered['type'], 
                        $filtered['active'], $filtered['last_modified'], 
                        $filtered['alive'], $filtered['last_checked'], 
                        $filtered['site_id'], $filtered['flag'],
                        $filtered['notes']);

                    if (array_sum($result[$sherpa_id]) === 3)
                        $result[$sherpa_id]['outcome'] = 1;
                    else
                        $result[$sherpa_id]['outcome'] = 0;
                }
            }

            return $result;

        }

        public function liveCheck($sherpa_id, $update = true)
        {

            $ctx = stream_context_create(array(

                'http'=> array(

                    'timeout' => 30, 

                )

            ));

            $result = false;

            $presponse = file_get_contents(PIWIKDS_URL . '/piwik/index.php?type=send&update=' . ($update ? 1 : 0) . '&cronsafe=mdzzsa32fs4j&path=/home/ping/' . $sherpa_id, false, $ctx);

            if ($presponse) {

                $ping = json_decode($presponse, true);

                if ($ping && isset($ping['outcome']) && $ping['outcome'] == 1) {

                    $lresponse = file_get_contents(PIWIKDS_URL . '/piwik/index.php?type=listen&update=' . ($update ? 1 : 0) . '&cronsafe=mdzzsa32fs4j&path=/home/ping/' . $sherpa_id . '/' . $ping['timestamp'], false, $ctx);

                    if ($lresponse) {

                        $listen = json_decode($lresponse, true);

                        if ($listen && isset($listen['outcome']) && $listen['outcome'] == 1) {

                            $result = true;    

                        }

                    }

                }

            }

            return $result;

        }

        public function hasPiwikAccount ($record)
        {

            // Try Site ID
            if ($record['site_id']) {

                $psites = $this->piwik->getRemoteSiteFromID($record['site_id']);

                if ($psites) {

                    if (parse_url($psites[0]['main_url'], PHP_URL_HOST) ==
                    $record['domain'] || $record['validate']) {

                        return $psites[0]['idsite'];
                    }
                }
            }

            // Try Sherpa ID
            $psites = $this->piwik->getRemoteSiteFromSherpaID($record['sherpa_id']);

            if ($psites) {

                return $psites[0]['idsite'];
            }

            // Try URL
            $psites = $this->piwik->getRemoteSiteFromURL(
                "{$record['protocol']}://{$record['domain']}");

            if ($psites) {

                return $psites[0]['idsite'];
            }

            return null;

        }

        public function updatePiwikAccount ($record)
        {

            if (! $record['domain'])
                return false;

            $result = $this->piwik->updateRemoteSite($record['site_id'], 
                $record['description'], 
                "{$record['protocol']}://{$record['domain']}", 
                $record['sherpa_id']);

            if ($result && ($result['result'] != "error")) {

                // TO DO Add Error Handling
                $this->joinPiwikMetaSite($record['site_id'], $record);

            }

            return $result;

        }

        public function addPiwikAccount ($record)
        {

            $site_id = $this->piwik->addRemoteSite($record['description'], 
                "{$record['protocol']}://{$record['domain']}", 
                $record['sherpa_id']);

            if ($site_id) {

                // TO DO Add Error Handling
                $this->joinPiwikMetaSite($site_id, $record);

            }

            return $site_id;   

        }

        public function joinPiwikMetaSite($site_id, $record)
        {

            $result = true;

            if (! $this->piwik->metasites)

                $this->piwik->metasites = $this->piwik->getMetaSites();

            if (isset($record['team'], $this->piwik->metasites[$record['team']])) {

                $metaSiteId = $this->piwik->metasites[$record['team']]['id'];

                $outcome = $this->piwik->joinMetaSite($site_id, $metaSiteId, 'Team');

                $result = $outcome ? $result : false;

                if (! $outcome) 

                    \PiwikManager\Helpers\Logger::errorMessage("Join {$record['team']} MetaSite ({$metaSiteId}) for {$record['description']} ({$site_id}) Failure.\n" . var_export($outcome, true));

            }

            if (isset($record['brand'], $this->piwik->metasites[$record['brand']])) {

                if (! in_array($site_id, $this->piwik->metasites[$record['brand']]['members'])) {

                    $metaSiteId = $this->piwik->metasites[$record['brand']]['id'];

                    $outcome = $this->piwik->joinMetaSite($site_id, $metaSiteId, true);

                    $result = $outcome ? $result : false;

                    if (! $outcome) 

                        \PiwikManager\Helpers\Logger::errorMessage("Join {$record['brand']} MetaSite ({$metaSiteId}) for {$record['description']} ({$site_id}) Failure.\n" . var_export($outcome, true));

                }

                return $result;

            }

            insert_debugging_log("Piwik Add MetaSite for {$record['description']} " . ($result ? "Success" : "Failure") . ".");

        }

        public function updateWordPressSite ($record)
        {

            if (! $record['site_id'] || ! $record['domain'])
                return false;
                
            $result = false;
            
            $response = false;

            $response = $this->wp->send_request($record['site_id'], 
                "{$record['protocol']}://{$record['domain']}", "ds_piwik_active", 
                $record['active']);
                
            $result = isset($response['response']) && $response['response'] == 200 ? 1 : 0;
                
            $response = $this->updateWPPiwikUrl($record);
            
            $result = (isset($response['response']) && $response['response'] == 200) ? $result : false;
            
            return $result;

        }

        public function updateWPPiwikUrl ($record)
        {

            if (! $record['domain'])
                return false;

            return $this->wp->send_admin_request("{$record['protocol']}://{$record['domain']}", "admin_tracking_url", 
                $this->piwik->getPiwikURL());

        }

        public function getRecordsPerPage ()
        {

            // Let's show 20 accounts per page.
            return 20;

        }

        public function sendPing ($sherpa_id, $siteurl, $update = true, $retry_if_changed = true)
        {

            $result = $this->wp->sendPing($siteurl, $sherpa_id);

            if (! $result || ! $result['outcome']) {

                $records = $this->find($sherpa_id, 1, 0);

                $record = isset($records[$sherpa_id]) ? $records[$sherpa_id] : false;

                if ($record && isset($record['domain']) && $update) {
                    
                    if ($retry_if_changed && $record['alive']) {
                        
                        return $this->sendPing($sherpa_id, $siteurl, $update, false);
                        
                    }

                    $result['updated'] = $this->model->updateAccount(
                        $record['id'], $record['sherpa_id'], 
                        $record['description'], $record['domain'], 
                        $record['protocol'], $record['type'], 
                        $record['active'], $record['last_modified'], 
                        null, date('Y-m-d H:i:s'), $record['site_id'],
                        $record['flag'], $record['notes']);

                } else {

                    $result['updated'] = null;

                }

            }

            return $result;

        }

        public function listenToPing ($sherpa_id, $timestamp, $update = true)
        {

            $result = false;

            if ($sherpa_id && $timestamp) {

                $records = $this->find($sherpa_id, 1, 0);

                if (isset($records[$sherpa_id])) {

                    $record = $records[$sherpa_id];

                    $idsite = isset($record['site_id']) ? $record['site_id'] : false;

                    if ($idsite) {

                        $result = $this->piwik->trackPing($idsite, $timestamp, 
                            $sherpa_id);

                        $success = $result['result'] ? 1 : null;

                        if ($update) {

                            $result['updated'] = $this->model->updateAccount(
                                $record['id'], $record['sherpa_id'], 
                                $record['description'], $record['domain'], 
                                $record['protocol'], $record['type'], 
                                $record['active'], $record['last_modified'], 
                                $success, date('Y-m-d H:i:s'), $record['site_id'],
                                $record['flag'], $record['notes']);

                        } else {

                            $result['update'] = null;

                        }
                    }
                }
            }

            return $result;

        }

        public function getCurrentPage ()
        {

            return $this->page;

        }

        public function getTotalResults ()
        {

            return $this->numberOfAccounts;

        }

        public function getAccountStats ()
        {

            $statistics = array(
                'alive' => 0,
                'active' => 0,
                'applicable' => 0,
                'total' => 0,
            );

            $settings = array(
                'active' => array(
                    'isactive' => array(
                        'criteria' => '1',
                        'fields' => array(
                            'active'
                        ),
                    ),
                    'isincluded' => array(
                        'criteria' => 'null',
                        'fields' => array(
                            'flag',
                        ),
                    ),
                ),
                'alive' => array(
                    'isalive' => array(
                        'criteria' => '1',
                        'fields' => array(
                            'alive',
                        ),
                    ),
                    'isincluded' => array(
                        'criteria' => 'null',
                        'fields' => array(
                            'flag',
                        ),
                    ),
                ),
                'applicable' => array(
                    'isincluded' => array(
                        'criteria' => 'null',
                        'fields' => array(
                            'flag',
                        ),
                    ),
                ),
                'total' => array(
                    'istotal' => array(
                        'criteria' => array(),
                        'fields' => array(),
                    ),
                ),
            );

            $active_products = $this->active_products;

            foreach ($settings as $key => $conditions) {

                if ($active_products)
                {

                    if ($key != 'total') {

                        $conditions['accounts_products'] = array(

                            'criteria' => $active_products,

                            'fields' => array('Accounts_products.Accounts_products_name')

                        );

                        $criteria = $this->searchTerm;

                        $fields = $this->fieldNames;

                    } else {

                        $criteria = array();

                        $fields = array();

                    }

                }

                foreach ($conditions as $ukey => $condition) {

                    $criteria = array_merge(array(
                        $ukey => $condition['criteria']
                        ),
                        $criteria);

                    $fields = array_merge(array(
                        $ukey => $condition['fields']
                        ),
                        $fields);

                }

                $statistics[$key] = $this->model->countAccounts($criteria, $fields, 
                    true);

            }

            return $statistics;

        }

        public function getAccountProducts()
        {

            return $this->model->getAccountProducts();

        }

        public function getSettings ($option_name = null)
        {

            $option = $this->model->getSettings($option_name);

            if ($option && is_array($option)) {

                foreach ($option as &$_option) {

                    if (isset($_option['option_value']) && \PiwikManager\Helpers\isJson($_option['option_value'])) {

                        $_option['option_value'] = json_decode($_option['option_value'], true);

                    }

                }

            }

            return $option;

        }

        public function updateSettings ($option_name, $option_value, 
            $option_desc = '')
        {

            if (is_array($option_value)) {

                $option_value = json_encode(array_filter($option_value), JSON_NUMERIC_CHECK);

            }

            return $this->model->updateSettings($option_name, $option_value, 
                $option_desc);

        }

        public function getTabData ($active = null)
        {

            return array(
                'index' => array(
                    'link' => PIWIKACTION_BASE . 'home/index/',
                    'title' => 'Piwik Accounts'
                ),
                'admin' => array(
                    'link' => PIWIKACTION_BASE . 'home/admin/',
                    'title' => 'Account Admin'
                ),
                'settings' => array(
                    'link' => PIWIKACTION_BASE . 'home/settings/',
                    'title' => 'Global Settings'
                )
            );

        }

        /**
        * Add http to url
        */
        public function addhttp ($url)
        {

            if (! preg_match("~^(?:f|ht)tps?://~i", $url)) {
                $url = "http://" . $url;
            }
            return $url;

        }

        /**
        * Filter Input
        *
        * @param mixed $input            
        * @param string $validate
        *            (optional)
        */
        public function sanitize ($input, $validate = null)
        {

            if (! $input)
            return $input;

            switch ($validate) {

                case 'url':
                {

                    if (! filter_var($this->addhttp($input), 
                    FILTER_VALIDATE_URL)) {

                        return false;
                    }

                    break;
                }

                case 'int':
                {

                    $input = preg_replace("/[^0-9,.]/", "", $input);

                    break;
                }
            }
            
            return mysql_real_escape_string(
                htmlspecialchars($input, ENT_QUOTES, 'UTF-8', false));

        }

        /**
        * Calculate Time Elapsed
        *
        * @param mixed $ptime            
        * @return mixed
        */
        function time_elapsed_string ($ptime)
        {

            if ($ptime < 0)
                return false;

            if (! is_integer($ptime))
                $ptime = strtotime($ptime) ?  : false;

            if (! $ptime)
                return false;

            $etime = time() - $ptime;

            if ($etime < 1) {
                return '0 seconds';
            }

            $a = array(
                365 * 24 * 60 * 60 => 'year',
                30 * 24 * 60 * 60 => 'month',
                24 * 60 * 60 => 'day',
                60 * 60 => 'hour',
                60 => 'minute',
                1 => 'second'
            );
            $a_plural = array(
                'year' => 'years',
                'month' => 'months',
                'day' => 'days',
                'hour' => 'hours',
                'minute' => 'minutes',
                'second' => 'seconds'
            );

            foreach ($a as $secs => $str) {
                $d = $etime / $secs;
                if ($d >= 1) {
                    $r = round($d);
                    return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . ' ago';
                }
            }

        }

        public function cronReady($secs = 60, $job_id = '', $action = null)
        {

            return $this->model->updateCron($secs, $job_id, $action);

        }

        function redirect ($success = true, $url = null)
        {

            $response_code = $success ? 302 : 412;

            $url = $url ?  : (isset($_REQUEST['referrer']) ? $_REQUEST['referrer'] : PIWIKACTION_BASE .
                '/home/index');

            if (isset($_GET['mode']) && $_GET['mode'] == 'ajax' && ! headers_sent()) {

                // where to go after records have been updated
                header('location: ' . $url, $response_code);
            } else {

                echo "<script type='text/javascript'>window.location.href='" . $url .
                "';</script>\n";
            }

        }

        function _array_splice(&$input, $offset, $length, $replacement = array())
        {

            $cloned = $input;

            $replacement = is_array($replacement) ? $replacement : array($replacement);

            $spliced = array_slice($cloned, 0, $offset, true) +

            $replacement +

            array_slice($cloned, ($offset + $length), NULL, true);

            $input = $spliced;

        }

}