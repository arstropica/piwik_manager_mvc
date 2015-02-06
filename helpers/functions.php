<?php

    /**
    * Project: PiwikManager MVC
    * File: /helpers/functions.php
    * Purpose: Helper Functions
    */
    namespace PiwikManager\Helpers;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    function get_short_name ($class)
    {

        $reflect = new \ReflectionClass($class);

        return $reflect->getShortName();

    }

    function get_qualified_name ($_class, $namespace = __NAMESPACE__)
    {

        if (is_string($_class)) {

            $class = $namespace ? $namespace . "\\" .
            preg_replace("/" . preg_quote("/{__NAMESPACE__}\\") . "/gi", 
                "", $_class) : $_class;
        } else {

            $class = $_class;
        }

        $reflect = new \ReflectionClass($class);

        return $reflect->getName();

    }

    function get_http_response_code ($url)
    {

        $result = false;

        $output = array();

        try {

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            // curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);

            $r = curl_exec($ch);

            if ($r)

                $headers = split("\n", $r);

            else

            return 404;
        } catch (\Exception $e) {

            return 404;
        }

        $output['response_code'] = substr($headers[0], 9, 3);

        if ($output['response_code'] != '404') {

            $result = @unserialize(end($headers));

            if (isset($result['result'])) {

                $output['result'] = $result['result'];

            } else {

                $output['result'] = "error";

            }

            if (isset($result['message'])) {

                $output['message'] = $result['message'];

            } else {

                $output['message'] = "Unknown Error.";

            }

        } else {

            $output['response_code'] = "404";

            $output['result'] = "error";

            $output['message'] = "Server could not be located.";

        }

        return $output;

    }

    function pretty_dump ($input, $export = false)
    {

        if ($export)

            echo "<pre>" . var_export($input, true) . "</pre><br/>\n";

        else

            echo "<pre>" . print_r($input, true) . "</pre><br/>\n";

    }

    function isJson($string) 
    {

        @json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);

}