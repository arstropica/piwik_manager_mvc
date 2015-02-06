<?php

    /**
    * Project: PiwikManager MVC
    * File: /views/home/pingview.php
    * Purpose: Ping View Class.  Handles Output Logic for home/ping route.
    */
    namespace PiwikManager\Views\Home;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    /**
    * @Inject viewmodel
    * @Inject template
    * @Inject acl
    */
    class PingView extends \PiwikManager\Views\View
    {

        public function send ()
        {

            $result = array(
                "outcome" => 0,
                "timestamp" => 0
            );
            
            $update = isset($_REQUEST['update']) ? $_REQUEST['update'] : 1;

            $siteurl = false;

            $sherpa_id = isset($this->viewmodel->request['args'][0]) ? $this->viewmodel->request['args'][0] : false;

            if ($sherpa_id) {

                $records = $this->viewmodel->find($sherpa_id, 1, 0);

                if (isset($records[$sherpa_id])) {

                    $record = $records[$sherpa_id];

                    if (isset($record['protocol'], $record['domain'])) {

                        $siteurl = "{$record['protocol']}://{$record['domain']}/";

                        $result = $this->viewmodel->sendPing($sherpa_id, $siteurl, $update);

                    }
                }
            }

            return json_encode(
                array(
                    "outcome" => $result['outcome'] ? 1 : 0,
                    "siteurl" => $siteurl,
                    "timestamp" => $result['timestamp'],
                    "updated" => $result['updated'],
                    "error" => $result['error'] ?: null,
                    "response" => $result['response'] ?: null,
            ))
            // "debug" => $result // debug
            ;

        }

        public function listen ()
        {

            $success = 0;

            $update = isset($_REQUEST['update']) ? $_REQUEST['update'] : 1;

            $sherpa_id = isset($this->viewmodel->request['args'][0]) ? $this->viewmodel->request['args'][0] : false;

            $timestamp = isset($this->viewmodel->request['args'][1]) ? $this->viewmodel->request['args'][1] : false;

            $result = $this->viewmodel->listenToPing($sherpa_id, $timestamp, $update);

            return json_encode(
                array(
                    "outcome" => empty($result['result']) ? 0 : 1,
                    "sherpa_id" => $sherpa_id,
                    "timestamp" => $timestamp,
                    "debug" => $result
            ));

        }

        public function output ()
        {

            return false;

        }

    }
