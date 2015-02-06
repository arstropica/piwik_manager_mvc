<?php

    /**
    * Project: PiwikManager MVC
    * File: /models/home/wordpressmodel.php
    * Purpose: Model for interacting with the WordPress API 
    */
    namespace PiwikManager\Models\Home;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    class WordPressModel
    {

        /**
        *
        * @var string API Key
        */
        private $api_key;

        /**
        *
        * @var bool Use Proxy
        */
        private static $useproxy = true;

        /**
        *
        * @var array Proxy Connection Settings
        */
        private static $proxy;

        /**
        * Constructor
        */
        function __construct ($api_key = null, $proxy = array())
        {

            $this->api_key = $api_key;
            
            if ($proxy && isset($proxy['domain'], $proxy['port'], $proxy['user'], $proxy['pass'])) {
                
                self::$proxy = $proxy;
                
            }

        }

        /**
        * Send Ping
        *
        * @param mixed $siteurl            
        * @param mixed $sherpa_id            
        * @param bool $useproxy            
        */
        function sendPing ($siteurl, $sherpa_id = null)
        {

            $timestamp = time() - strtotime("today");

            $url = $siteurl . "?ds_piwik_ping=" . $timestamp . "&sherpa_id=" .
            $sherpa_id . "&__api=" . $this->api_key;
            
            // \PiwikManager\Helpers\Logger::errorMessage($url, false, false, false);

            $result = array(
                'outcome' => false
            );
            
            $response = self::_curl($url);

            if ($response['response']) {

                $result['response'] = json_decode($response['response'], true);

                $result['timestamp'] = $timestamp;

                $result['outcome'] = true;
            }

            return $result;

        }

        /**
        * Send a request to the WP Plugin API
        *
        * @param int $idsite            
        * @param string $siteurl            
        * @param string $action            
        * @param string $value            
        * @param bool $useproxy            
        */
        function send_request ($idsite, $siteurl, $action, $value)
        {

            global $dashboard_env;

            if ($dashboard_env == "dev")

                return false;

            $domain = parse_url($siteurl, PHP_URL_HOST);

            $request = self::trailingslashit($siteurl) .
            "?ds_piwik=1&__api={$this->api_key}&ds_piwik_action={$action}&ds_piwik_value={$value}&ds_piwik_domain={$domain}&ds_piwik_idsite={$idsite}&ds_piwik_mode=single";

            $response = self::_curl($request);
 
            return @json_decode($response['response'], true) ?  : false;

        }

        /**
        * Send an admin request to the WP Plugin API
        *
        * @param string $siteurl            
        * @param string $action            
        * @param string $value            
        */
        function send_admin_request ($siteurl, $action, $value)
        {

            global $dashboard_env;

            if ($dashboard_env == "dev")

                return false;

            $domain = parse_url($siteurl, PHP_URL_HOST);

            $request = self::trailingslashit($siteurl) .
            "?ds_piwik=1&__api={$this->api_key}&ds_piwik_action={$action}&ds_piwik_value={$value}&ds_piwik_domain={$domain}&ds_piwik_mode=admin";

            $response = self::_curl($request);
 
            return @json_decode($response['response'], true) ?  : false;

        }

        /**
        * Removes trailing forward slashes and backslashes if they exist.
        *
        * The primary use of this is for paths and thus should be used for paths.
        * It is
        * not restricted to paths and offers no specific path support.
        *
        *
        * @param string $string
        *            What to remove the trailing slashes from.
        * @return string String without the trailing slashes.
        */
        function untrailingslashit ($string)
        {

            return rtrim($string, '/\\');

        }

        /**
        * Appends a trailing slash.
        *
        * Will remove trailing forward and backslashes if it exists already before
        * adding
        * a trailing forward slash. This prevents double slashing a string or path.
        *
        * The primary use of this is for paths and thus should be used for paths.
        * It is
        * not restricted to paths and offers no specific path support.
        *
        * @param string $string
        *            What to add the trailing slash to.
        * @return string String with trailing slash added.
        */
        function trailingslashit ($string)
        {

            return self::untrailingslashit($string) . '/';

        }

        /**
        * CURL Handler
        * 
        * @param mixed $url
        * @param mixed $useproxy
        * @param mixed $headers
        * @param mixed $agent
        */
        private static function _curl($url, $headers = false, $agent = false)
        {

            $result = array();
            
            $agent = $agent ?: "DigitalSherpa Dashboard";

            if (! $headers) {

                $headers = array();
                $headers[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
                $headers[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*\/*;q=0.5";
                $headers[] = "Cache-Control: max-age=0";
                $headers[] = "Connection: keep-alive";
                $headers[] = "Keep-Alive: 300";
                $headers[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
                $headers[] = "Accept-Language: en-us,en;q=0.5";
                $headers[] = "Host: " . parse_url($url, PHP_URL_HOST);
                $headers[] = "Pragma: "; // browsers keep this blank.

            }

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt($ch, CURLOPT_VERBOSE, true);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($ch, CURLOPT_USERAGENT, $agent);

            curl_setopt($ch, CURLOPT_REFERER, 
                "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");

            curl_setopt($ch, CURLOPT_AUTOREFERER, true);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            curl_setopt($ch, CURLOPT_URL, $url);

            if (self::$useproxy && self::$proxy) {
                
                $proxy = self::$proxy;

                $pdomain = "{$proxy['domain']}:{$proxy['port']}";

                $loginpassw = "{$proxy['user']}:{$proxy['pass']}";

                curl_setopt($ch, CURLOPT_PROXY, $pdomain);
                
                // curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);

                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);

            }

            // grab URL and pass it to the browser
            $result['response'] = curl_exec($ch);
            
            if (curl_errno($ch)) {

                $result['error'] = curl_error($ch);
            }

            // close cURL resource, and free up system resources
            curl_close($ch);
            
            return $result;

        }

    }

?>
