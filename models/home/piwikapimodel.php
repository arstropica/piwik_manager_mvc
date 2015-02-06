<?php

    /**
    * Project: PiwikManager MVC
    * File: /models/home/piwikapimodel.php
    * Purpose: Model for interacting with the Piwik API 
    */
    namespace PiwikManager\Models\Home;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    /**
    * @Inject template
    */
    class PiwikAPIModel
    {

        /**
        *
        * @var null Auth Token
        */
        private $token;

        /**
        *
        * @var null Piwik API Protocol
        */
        private $protocol;

        /**
        *
        * @var null Piwik API Domain
        */
        private $domain;

        /**
        *
        * @var null Piwik API Domain
        */
        private $endpoint = null;

        /**
        *
        * @var null Piwik Site Info
        */
        public $sites = null;

        /**
        *
        * @var null Piwik MetaSite Info
        */
        public $metasites = null;

        /**
        * @ var template view template
        */
        public $template;

        /**
        * Constructor
        */
        function __construct ($protocol, $domain, $token = null)
        {

            $this->protocol = $protocol;

            $this->domain = $domain;

            $this->token = $token;

        }

        function _handle_di ()
        {

            $settings_check = $this->init();

            if ($settings_check['result'] == "error") {

                $this->template->addMessage("Invalid Piwik Settings.  {$settings_check['message']}", "error");

            }

        }

        /**
        * Setup class members and variables
        */
        function init ()
        {

            $this->endpoint = "{$this->protocol}://{$this->domain}/index.php?module=API&token_auth={$this->token}&format=PHP";

            $response = \PiwikManager\Helpers\get_http_response_code($this->endpoint . "&method=API.getPiwikVersion");

            return $response;

        }
        
        /**
        * Get Piwik URL
        * 
        */
        public function getPiwikURL()
        {
            return "{$this->protocol}://{$this->domain}/index.php";
        }

        /**
        * Get Dashboard URL
        */
        public function getDashboardURL ($site_id)
        {

            return "{$this->protocol}://{$this->domain}/index.php?module=CoreHome&action=index&date=yesterday&period=day&idSite={$site_id}";

        }

        /**
        * Get info for all sites
        */
        public function getAllRemoteSites ()
        {

            $endpoint = $this->endpoint . "&method=SitesManager.getAllSites";
            $response = file_get_contents($endpoint);
            // return preg_replace("/^[^\[{]*/si", "", $response);
            // return $this->sites = json_decode(preg_replace("/^[^\[{]*/si", "",
            // $response), true);
            return $this->sites = unserialize($response);

        }

        /**
        * Get info for site by site id
        *
        * @param
        *            string site_id
        */
        public function getRemoteSiteFromID ($site_id)
        {

            $endpoint = $this->endpoint .
            "&method=SitesManager.getSiteFromId&idSite={$site_id}";
            $response = file_get_contents($endpoint);
            // return preg_replace("/^[^\[{]*/si", "", $response);
            // return json_decode(preg_replace("/^[^\[{]*/si", "", $response),
            // true);
            return unserialize($response);

        }

        /**
        * Get info for site by sherpa id
        *
        * @param
        *            string sherpa_id
        */
        public function getRemoteSiteFromSherpaID ($sherpa_id)
        {

            $endpoint = $this->endpoint .
            "&method=SitesManager.getSitesFromGroup&group={$sherpa_id}";
            $response = file_get_contents($endpoint);
            // return preg_replace("/^[^\[{]*/si", "", $response);
            // return json_decode(preg_replace("/^[^\[{]*/si", "", $response),
            // true);
            return unserialize($response);

        }

        /**
        * Get info for site by URL
        *
        * @param
        *            string siteurl
        */
        public function getRemoteSiteFromURL ($siteurl)
        {

            $output = array();
            $endpoint = $this->endpoint .
            "&method=SitesManager.getSitesIdFromSiteUrl&url=" .
            urlencode($siteurl);
            $response = file_get_contents($endpoint);
            // $site_ids = json_decode(preg_replace("/^[^\[{]*/si", "", $response),
            // true);
            $site_ids = unserialize($response);

            if ($site_ids) {
                foreach ($site_ids as $site) {
                    $site_id = $site['idsite'];
                    $endpoint = $this->endpoint .
                    "&method=SitesManager.getSiteFromId&idSite={$site_id}";
                    $response = file_get_contents($endpoint);
                    // $output[$site_id] = json_decode(preg_replace("/^[^\[{]*/si",
                    // "", $response), true);
                    $output[$site_id] = unserialize($response);
                }
            }

            return $output;

        }

        /**
        * Add new site
        *
        * @param string $siteName            
        * @param string $url            
        * @param string $sherpa_id            
        */
        public function addRemoteSite ($siteName, $siteurl, $sherpa_id)
        {

            global $dashboard_env;

            /*if ($dashboard_env == "dev")

                return false;*/

            $site_id = null;

            $endpoint = $this->endpoint .
            "&method=SitesManager.addSite&timezone=America/New_York&siteName=" .
            urlencode($siteName) . "&urls=" . urlencode($siteurl) .
            "&group=" . urlencode($sherpa_id);
            $response = file_get_contents($endpoint);

            $site_id = unserialize($response);

            return $site_id;

        }

        /**
        * Update existing site
        *
        * @param int $idSite            
        * @param string $siteName            
        * @param string $url            
        * @param string $sherpa_id            
        */
        public function updateRemoteSite ($idSite, $siteName, $siteurl, $sherpa_id)
        {

            global $dashboard_env;

            /*if ($dashboard_env == "dev")

                return false;*/

            $result = null;

            $endpoint = $this->endpoint . "&method=SitesManager.updateSite&idSite=" .
            $idSite . "&siteName=" . urlencode($siteName) . "&urls=" .
            urlencode($siteurl) . "&group=" . urlencode($sherpa_id);
            $response = file_get_contents($endpoint);

            $result = @unserialize($response);

            return $result;

        }

        /**
        * Get simple "stats".
        * This is just a simple demo to show
        * how to use more than one model in a controller (see
        * application/controller/songs.php for more)
        */
        public function getAmountOfRemoteSites ()
        {

            return count($this->sites);

        }

        /**
        * Get site from domain (if it exists)
        */
        public function getSiteFromDomain ($domain)
        {

            if (! isset($this->sites))
                $this->getAllRemoteSites();

            foreach ($this->sites as $site) {
                if (parse_url($site['main_url'], PHP_URL_HOST) == $domain)
                    return $site;
            }

            return false;

        }

        /**
        * Check Piwik Account for Ping Request
        *
        * @param mixed $idsite            
        * @param mixed $timestamp            
        * @param mixed $sherpa_id            
        */
        public function trackPing ($idsite, $timestamp, $sherpa_id = null)
        {

            $result = array(
                'result' => false
            );

            $response = false;

            $outcome = false;

            $endpoint = $this->endpoint . "&method=Live.getLastVisitsDetails&idSite=" .
            $idsite . "&period=day&date=today&minTimestamp=" . (time() - 30) .
            "&segment=customVariableName1%3D%3Dsherpa_id;customVariableValue1%3D%3D" .
            $sherpa_id;

            // \PiwikManager\Helpers\Logger::errorMessage($endpoint, false, false, false);
            
            $response = file_get_contents($endpoint);

            if ($response) {

                $outcome = @unserialize($response);

                if ($outcome && isset($outcome[0]['actionDetails'])) {

                    $actionDetails = $outcome[0]['actionDetails'];

                    foreach ($actionDetails as $event) {

                        if (isset($event['eventValue']) &&
                        $event['eventValue'] == $timestamp) {

                            $result['result'] = true;

                            break;
                        }
                    }
                }
            }

            $result['response'] = $response;

            $result['outcome'] = $outcome;

            return $result;

        }

        /**
        * Get MetaSites
        * 
        * @param mixed $id
        */
        public function getMetaSites($id = null)
        {

            $output = array();

            if ($id) {

                $endpoint = $this->endpoint . "&method=MetaSites.getSitesForMetaSite&id={$id}";

            } else {

                $endpoint = $this->endpoint . "&method=MetaSites.getMetaSites&rowCount=100";

            }

            $response = file_get_contents($endpoint);
            // return preg_replace("/^[^\[{]*/si", "", $response);
            // return json_decode(preg_replace("/^[^\[{]*/si", "", $response),
            // true);
            $metaSites = unserialize($response);    

            if ($metaSites && is_array($metaSites)) {

                foreach ($metaSites as $metaSite) {

                    $output[$metaSite['name']] = array(

                        "id" => $metaSite['id'],

                        "members" => $this->pollMetaSite($metaSite['id']),

                    );

                }

            }

            return $output;

        }

        public function pollMetaSite($id)
        {

            $endpoint = $this->endpoint . "&method=MetaSites.getIdSitesForMetaSite&id={$id}";

            $response = file_get_contents($endpoint);
            // return preg_replace("/^[^\[{]*/si", "", $response);
            // return json_decode(preg_replace("/^[^\[{]*/si", "", $response),
            // true);
            return unserialize($response);    

        }

        /**
        * Create MetaSite
        * 
        * @param mixed $name
        */
        public function createMetaSite($name)
        {

            if (! $name)

                return false;

            $endpoint = $this->endpoint . "&method=MetaSites.create&name=" . urlencode($name);

            $response = file_get_contents($endpoint);
            // return preg_replace("/^[^\[{]*/si", "", $response);
            // return json_decode(preg_replace("/^[^\[{]*/si", "", $response),
            // true);
            return unserialize($response);    

        }

        /**
        * Delete MetaSite
        * 
        * @param mixed $id
        */
        public function deleteMetaSite($id)
        {

            if (! $id)

                return false;

            $endpoint = $this->endpoint . "&method=MetaSites.delete&id={$id}";

            $response = file_get_contents($endpoint);
            // return preg_replace("/^[^\[{]*/si", "", $response);
            // return json_decode(preg_replace("/^[^\[{]*/si", "", $response),
            // true);
            return unserialize($response);    

        }

        /**
        * Connect Site with MetaSite
        * 
        * @param int $idSite
        * @param int $metaSiteid
        * @param string $leave
        */
        public function joinMetaSite($idSite, $metaSiteId, $leave = false)
        {

            if (! $idSite || ! $metaSiteId)

                return false;

            if ($leave === true)
            
                $this->leaveMetaSites($idSite, false);
                
            elseif ($leave)

                $this->leaveMetaSites($idSite, $leave);

            $endpoint = $this->endpoint . "&method=MetaSites.connectSiteWithMetaSite&id={$idSite}&metaSiteId={$metaSiteId}";

            $response = file_get_contents($endpoint);
            // return preg_replace("/^[^\[{]*/si", "", $response);
            // return json_decode(preg_replace("/^[^\[{]*/si", "", $response),
            // true);
            return unserialize($response);    


        }

        public function leaveMetaSites($idSite, $nameFilter = 'Team')
        {

            $output = array();

            if (! $idSite)

                return $output;

            if (! $this->metasites)

                $this->metasites = $this->getMetaSites();

            foreach ($this->metasites as $name => $metasite) {

                if (stristr($name, $nameFilter) || ! $nameFilter) {

                    if (in_array($idSite, $metasite['members'])) {

                        $metaSiteid = $metasite['id'];

                        $output[$metaSiteid] = $this->leaveMetaSite($idSite, $metaSiteid);

                    }

                }

            }

            return $output;

        }


        public function leaveMetaSite($idSite, $metaSiteid)
        {

            if (! $idSite || ! $metaSiteId)

                return false;

            $endpoint = $this->endpoint . "&method=MetaSites.disconnectSiteWithMetaSite&id={$idSite}&metaSiteId={$metaSiteid}";

            $response = file_get_contents($endpoint);
            // return preg_replace("/^[^\[{]*/si", "", $response);
            // return json_decode(preg_replace("/^[^\[{]*/si", "", $response),
            // true);
            return unserialize($response);    

        }

    }

?>
