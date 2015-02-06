<?php

    /**
    * Project: PiwikManager MVC
    * File: /models/home/homemodel.php
    * Purpose: Model for the application home 
    */
    namespace PiwikManager\Models\Home;

    use \PDO;

    if (! defined('PIWIKAPP'))
        exit('No direct script access allowed');

    /**
    * @Inject db
    */
    class HomeModel extends \PiwikManager\Models\Model
    {

        /**
        * Retrieve Account Records
        *
        * @param string $criteria            
        * @param array $fields            
        * @param int $pagination            
        * @param int $offset            
        * @param string $sort            
        * @param string $order            
        * @param boolean $enc            
        *
        * @return array $result
        */
        public function findAccounts ($criteria = null, $fields = array(), $pagination = 20, 
            $offset = 0, $sort = null, $order = "ASC", $enc = true)
        {

            $conditions = array();

            $params = array();

            $comparisions = $this->comparison;

            $orderby = $sort ? ("ORDER BY {$sort} " . ($order ?  : "ASC")) : "";

            if (isset($criteria, $fields)) {

                foreach ($fields as $key => $_fields) {

                    if (isset($criteria[$key])) {

                        foreach ($_fields as $_field) {

                            if (isset($comparisions[$_field])) {

                                $_comparison = $comparisions[$_field];

                            } elseif (is_array($criteria[$key])) {

                                if (strpos($key, "-") === 0) {

                                    $_comparison = "NOT IN";

                                } else {

                                    $_comparison = "IN";

                                }

                            } elseif (strpos($key, "-") === 0) {

                                $_comparison = "!=";

                            } else {

                                $_comparison = "=";

                            }

                            if (is_array($criteria[$key])) {

                                for ($i = 0; $i < count($criteria[$key]); $i ++) {

                                    if ($criteria[$key][$i] === "null") {

                                        $conditions[$key][] = "{$_field} IS NULL";

                                        unset($criteria[$key][$i]);

                                    } elseif ($criteria[$key][$i] === "not null") {

                                        $conditions[$key][] = "{$_field} IS NOT NULL";

                                        unset($criteria[$key][$i]);

                                    }

                                }

                                if (isset($criteria[$key])) {

                                    if ($enc) {

                                        switch ($_comparison) {

                                            case 'RLIKE' : {

                                                $criteria[$key] = array_map('rawurlencode', $criteria[$key]);

                                                break;

                                            }
                                        }

                                    }

                                    switch ($_comparison) {

                                        case 'RLIKE' : {

                                            $conditions[$key][] = "{$_field} {$_comparison} '" . (implode('|', $criteria[$key])) . "'";

                                            break;
                                        }

                                        case '=' :
                                        case 'IN' : 
                                        default : {

                                            $conditions[$key][] = "{$_field} {$_comparison} ('" . (implode("', '", $criteria[$key])) . "')";

                                            break;
                                        }

                                    }

                                }

                            } else {

                                if ($criteria[$key] === "null") {

                                    $conditions[$key][] = "{$_field} IS NULL";

                                } elseif ($criteria[$key] === "not null") {

                                    $conditions[$key][] = "{$_field} IS NOT NULL";

                                } else {

                                    if ($enc) {

                                        switch ($_comparison) {

                                            case 'RLIKE' : {

                                                $criteria[$key] = rawurlencode($criteria[$key]);

                                                break;

                                            }

                                        }

                                    }

                                    $conditions[$key][] = "{$_field} {$_comparison} :{$key}";

                                    $params = array_merge($params, 
                                        array(
                                            ":{$key}" => $criteria[$key]
                                    ));

                                }
                            }
                        }
                    }
                }
            }

            $limit = $pagination ? " LIMIT {$offset}, {$pagination}" : "";

            $sql = "SELECT 
            SQL_CALC_FOUND_ROWS 
            Accounts.Accounts_name,
            Accounts.Accounts_sherpa_id,
            Accounts.Accounts_sherpa_brand,
            Accounts.Team_name,
            IF(ISNULL(Accounts_piwik.id),
            'imported',
            'local') AS status,
            Accounts.Accounts_sugar_id,
            Accounts.Accounts_parent_name,
            Accounts_piwik.id,
            Accounts_piwik.site_id,
            Accounts_piwik.active,
            Accounts_piwik.last_modified,
            Accounts_piwik_details.description,
            Accounts_piwik_details.domain,
            Accounts_piwik_details.protocol,
            Accounts_piwik_details.type,
            Accounts_piwik_details.alive,
            Accounts_piwik_details.last_checked,
            Accounts_piwik_details.flag,
            Accounts_piwik_details.notes,
            GROUP_CONCAT(Accounts_products.Accounts_products_name SEPARATOR ', ') as products
            FROM
            Accounts
            LEFT OUTER JOIN
            Accounts_products ON Accounts.Accounts_sugar_id = Accounts_products.Accounts_products_account_id
            LEFT OUTER JOIN
            Accounts_piwik ON Accounts.Accounts_sherpa_id = Accounts_piwik.sherpa_id
            LEFT OUTER JOIN
            Accounts_piwik_details ON Accounts_piwik.id = Accounts_piwik_details.ap_id
            WHERE
            Accounts.Accounts_status_temp = 1
            AND Accounts.Accounts_status_sugar = 'Active'
            " . ($conditions ? "AND ((" . implode(
                ") AND (", 
                array_map(
                    function  ($_conditions)
                    {
                        return "(" . implode(") OR (", $_conditions) . ")";
                    }, $conditions)) . "))" : "") . " 
            GROUP BY Accounts.Accounts_sherpa_id 
            {$orderby}  
            {$limit};";

            $query = $this->db->prepare($sql);

            self::$lastQuery = $query->queryString;

            $query->execute($params);

            $results['results'] = $query->fetchAll(PDO::FETCH_ASSOC);

            $results['count'] = $this->lastRowCount();

            // \PiwikManager\Helpers\Logger::errorMessage($query->queryString . "\n" . var_export($params, true), false, false, false);

            return $results;

        }

        public function findDeadAccounts()
        {
            $result = array();

            $query = $this->db->prepare("SELECT 
                SQL_CALC_FOUND_ROWS 
                Accounts.Accounts_name,
                Accounts.Accounts_sherpa_id,
                Accounts.Accounts_sherpa_brand,
                Accounts.Team_name,
                IF(ISNULL(Accounts_piwik.id),
                'imported',
                'local') AS status,
                Accounts_piwik.id,
                Accounts.Accounts_assigned_user_name,
                Accounts.Accounts_status_temp,
                Accounts.Accounts_status_sugar,
                Accounts.Accounts_sugar_id,
                Accounts.Accounts_parent_name,
                Accounts_piwik.site_id,
                Accounts_piwik.active,
                Accounts_piwik.last_modified,
                Accounts_piwik_details.description,
                Accounts_piwik_details.domain,
                Accounts_piwik_details.protocol,
                Accounts_piwik_details.type,
                Accounts_piwik_details.alive,
                Accounts_piwik_details.last_checked, 
                Accounts_piwik_details.flag,
                Accounts_piwik_details.notes,
                GROUP_CONCAT(Accounts_products.Accounts_products_name SEPARATOR ', ') as products
                FROM
                Accounts
                LEFT OUTER JOIN
                Accounts_piwik ON Accounts.Accounts_sherpa_id = Accounts_piwik.sherpa_id
                LEFT OUTER JOIN
                Accounts_piwik_details ON Accounts_piwik.id = Accounts_piwik_details.ap_id
                WHERE
                Accounts_sherpa_id NOT IN (
                SELECT a.Accounts_sherpa_id 
                FROM
                Accounts a 
                WHERE a.Accounts_status_temp = 1
                AND a.Accounts_status_sugar = 'Active'
                )
                AND Accounts_piwik.active = 1
            GROUP BY Accounts.Accounts_sherpa_id ");

            self::$lastQuery = $query->queryString;

            $query->execute();

            $results['results'] = $query->fetchAll(PDO::FETCH_ASSOC);

            $results['count'] = $this->lastRowCount();

            return $results;

        }

        /**
        * Count Account Records
        *
        * @param string $criteria            
        * @param array $fields            
        * @param boolean $enc            
        *
        * @return int $count
        */
        public function countAccounts ($criteria = null, $fields = array(), $enc = true)
        {

            $conditions = array();

            $params = array();

            $comparisions = $this->comparison;

            if (isset($criteria, $fields)) {

                foreach ($fields as $key => $_fields) {

                    if (isset($criteria[$key])) {

                        foreach ($_fields as $_field) {

                            if (isset($comparisions[$_field])) {

                                $_comparison = $comparisions[$_field];

                            } elseif (is_array($criteria[$key])) {

                                $_comparison = "IN";

                            } else {

                                $_comparison = "=";

                            }

                            if (is_array($criteria[$key])) {

                                for ($i = 0; $i < count($criteria[$key]); $i ++) {

                                    if ($criteria[$key][$i] === "null") {

                                        $conditions[$key][] = "{$_field} IS NULL";

                                        unset($criteria[$key][$i]);

                                    } elseif ($criteria[$key][$i] === "not null") {

                                        $conditions[$key][] = "{$_field} IS NOT NULL";

                                        unset($criteria[$key][$i]);

                                    }

                                }

                                if (isset($criteria[$key])) {

                                    if ($enc) {

                                        switch ($_comparison) {

                                            case 'RLIKE' : {

                                                $criteria[$key] = array_map('rawurlencode', $criteria[$key]);

                                                break;

                                            }
                                        }

                                    }

                                    switch ($_comparison) {

                                        case 'RLIKE' : {

                                            $conditions[$key][] = "{$_field} {$_comparison} '" . (implode('|', $criteria[$key])) . "'";

                                            break;
                                        }

                                        case '=' :
                                        case 'IN' : 
                                        default : {

                                            $conditions[$key][] = "{$_field} {$_comparison} ('" . (implode("', '", $criteria[$key])) . "')";

                                            break;
                                        }

                                    }

                                }

                            } else {

                                if ($criteria[$key] === "null") {

                                    $conditions[$key][] = "{$_field} IS NULL";

                                } elseif ($criteria[$key] === "not null") {

                                    $conditions[$key][] = "{$_field} IS NOT NULL";

                                } else {

                                    if ($enc) {

                                        switch ($_comparison) {

                                            case 'RLIKE' : {

                                                $criteria[$key] = rawurlencode($criteria[$key]);

                                                break;

                                            }

                                        }

                                    }

                                    $conditions[$key][] = "{$_field} {$_comparison} :{$key}";

                                    $params = array_merge($params, 
                                        array(
                                            ":{$key}" => $criteria[$key]
                                    ));

                                }
                            }
                        }
                    }
                }
            }

            $sql = "SELECT COUNT(*)  
            FROM (
            SELECT Accounts.Accounts_sherpa_id
            FROM 
            Accounts
            LEFT OUTER JOIN
            Accounts_products ON Accounts.Accounts_sugar_id = Accounts_products.Accounts_products_account_id
            LEFT OUTER JOIN
            Accounts_piwik ON Accounts.Accounts_sherpa_id = Accounts_piwik.sherpa_id
            LEFT OUTER JOIN
            Accounts_piwik_details ON Accounts_piwik.id = Accounts_piwik_details.ap_id
            WHERE
            Accounts.Accounts_status_temp = 1
            AND Accounts.Accounts_status_sugar = 'Active'
            " . ($conditions ? "AND ((" . implode(
                ") AND (", 
                array_map(
                    function  ($_conditions)
                    {
                        return "(" . implode(") OR (", $_conditions) . ")";
                    }, $conditions)) . "))" : "") . " 
            GROUP BY Accounts.Accounts_sherpa_id)
            AS count;";

            $query = $this->db->prepare($sql);

            self::$lastQuery = $query->queryString;

            $query->execute($params);

            // \PiwikManager\Helpers\Logger::errorMessage($query->queryString, false, false, false);

            return $query->fetchColumn();

        }

        public function getAccountURLs ($sherpa_id = null, $validonly = false)
        {

            $result = array();
            $sql = "
            SELECT 
            Accounts_credentials.sherpaid,
            CASE Accounts_credentials.DesignSherpaURL
            WHEN 'http://' THEN NULL
            WHEN 'http%3A%2F%2F' THEN NULL
            WHEN '' THEN NULL
            ELSE Accounts_credentials.DesignSherpaURL
            END AS DesignSherpaURL,
            CASE Accounts_credentials.DomainMappingURL
            WHEN 'http://' THEN NULL
            WHEN 'http%3A%2F%2F' THEN NULL
            WHEN '' THEN NULL
            ELSE Accounts_credentials.DomainMappingURL
            END AS DomainMappingURL,
            CASE Accounts_credentials.blogurl
            WHEN 'http://' THEN NULL
            WHEN 'http%3A%2F%2F' THEN NULL
            WHEN '' THEN NULL
            ELSE Accounts_credentials.blogurl
            END AS blogurl,
            Accounts_credentials.Accounts_credentials_id,
            Accounts_credentials.Accounts_credentials_datetime
            FROM
            Accounts_credentials
            INNER JOIN
            Accounts ON Accounts.Accounts_sherpa_id = Accounts_credentials.sherpaid
            WHERE
            Accounts.Accounts_status_temp = 1
            AND Accounts.Accounts_status_sugar = 'Active' 
            " .
            ($sherpa_id ? "AND Accounts.Accounts_sherpa_id = '{$sherpa_id}' " : "") . "
            GROUP BY Accounts_credentials.sherpaid
            ORDER BY Accounts_credentials.Accounts_credentials_id DESC
            ";
            $query = $this->db->prepare($sql);

            self::$lastQuery = $query->queryString;

            $query->execute();

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                if ($validonly === false || isset($row['DesignSherpaURL']) ||
                    isset($row['DomainMappingURL']) || isset($row['blogurl']))
                    $result[$row['sherpaid']] = $row;
            }
            return $result;

        }

        public function getAccountProducts()
        {

            $result = array();

            $query = $this->db->prepare("
                SELECT Accounts_products_name 
                FROM  Accounts_products 
                WHERE Accounts_products_status = 1 
                GROUP BY  Accounts_products_name 
                ORDER BY  Accounts_products_name
            ");

            self::$lastQuery = $query->queryString;

            $query->execute();

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $result[] = $row['Accounts_products_name'];
            }

            return $result;

        }

        /**
        * Update or Insert an Account in the database
        */
        public function updateAccount ($id, $sherpa_id, $description, $domain, 
            $protocol, $type, $active, $last_modified, $alive, $last_checked, 
            $site_id = null, $flag = null, $notes = null)
        {

            if ($id) {

                // Check if account has been saved before
                $sql = "SELECT COUNT(id) FROM Accounts_piwik WHERE id = :id";
                $query = $this->db->prepare($sql);
                self::$lastQuery = $query->queryString;
                $parameters = array(
                    ':id' => $id
                );

                $query->execute($parameters);

                $saved_before = $query->fetchColumn();
            } else {

                $saved_before = false;
            }

            try {

                if ($saved_before) {

                    $accounts_piwik_sql = "UPDATE Accounts_piwik SET site_id = :site_id, active = :active, last_modified = :last_modified WHERE id = :id";
                    $accounts_piwik_details_sql = "UPDATE Accounts_piwik_details SET description = :description, domain = :domain, protocol = :protocol, type = :type, alive = :alive, last_checked = :last_checked, flag = :flag, notes = :notes WHERE ap_id = :id";

                    $accounts_piwik_parameters = array(
                        ':site_id' => $site_id,
                        ':active' => $active,
                        ':last_modified' => $last_modified,
                        ':id' => $id
                    );
                    $accounts_piwik_details_parameters = array(
                        ':description' => $description,
                        ':domain' => $domain,
                        ':protocol' => $protocol,
                        ':type' => $type,
                        ':alive' => $alive,
                        ':last_checked' => $last_checked,
                        ':id' => $id,
                        ':flag' => $flag,
                        ':notes' => $notes,
                    );

                    $accounts_piwik_query = $this->db->prepare($accounts_piwik_sql);
                    $accounts_piwik_details_query = $this->db->prepare(
                        $accounts_piwik_details_sql);

                    self::$lastQuery = $query->queryString;

                    $this->db->beginTransaction();

                    foreach ($accounts_piwik_parameters as $accounts_piwik_paramter => $accounts_piwik_paramter_val) {

                        if (! isset($accounts_piwik_paramter_val))
                            $accounts_piwik_query->bindValue(
                                $accounts_piwik_paramter, NULL, PDO::PARAM_INT);
                        else
                            $accounts_piwik_query->bindValue(
                                $accounts_piwik_paramter, 
                                $accounts_piwik_paramter_val);
                    }

                    $accounts_piwik_query->execute();

                    foreach ($accounts_piwik_details_parameters as $accounts_piwik_details_parameter => $accounts_piwik_details_parameter_val) {

                        if (! isset($accounts_piwik_details_parameter_val))
                            $accounts_piwik_details_query->bindValue(
                                $accounts_piwik_details_parameter, NULL, 
                                PDO::PARAM_INT);
                        else
                            $accounts_piwik_details_query->bindValue(
                                $accounts_piwik_details_parameter, 
                                $accounts_piwik_details_parameter_val);
                    }

                    $accounts_piwik_details_query->execute();

                    $this->db->commit();
                } else {

                    $accounts_piwik_sql = "INSERT INTO Accounts_piwik (sherpa_id, site_id, active, last_modified) VALUES (:sherpa_id, :site_id, :active, :last_modified)";
                    $accounts_piwik_query = $this->db->prepare($accounts_piwik_sql);

                    $accounts_piwik_details_sql = "INSERT INTO Accounts_piwik_details (ap_id, description, domain, protocol, type, alive, last_checked, flag, notes) VALUES (:ap_id, :description, :domain, :protocol, :type, :alive, :last_checked, :flag, :notes)";
                    $accounts_piwik_details_query = $this->db->prepare(
                        $accounts_piwik_details_sql);

                    $this->db->beginTransaction();

                    $accounts_piwik_parameters = array(
                        ':sherpa_id' => $sherpa_id,
                        ':site_id' => $site_id,
                        ':active' => $active,
                        ':last_modified' => $last_modified
                    );

                    foreach ($accounts_piwik_parameters as $accounts_piwik_paramter => $accounts_piwik_paramter_val) {

                        if (! isset($accounts_piwik_paramter_val))
                            $accounts_piwik_query->bindValue(
                                $accounts_piwik_paramter, NULL, PDO::PARAM_INT);
                        else
                            $accounts_piwik_query->bindValue(
                                $accounts_piwik_paramter, 
                                $accounts_piwik_paramter_val);
                    }

                    self::$lastQuery = $accounts_piwik_query->queryString;

                    $accounts_piwik_query->execute();

                    $id = $this->db->lastInsertId();

                    if ($id) {

                        $accounts_piwik_details_parameters = array(
                            ':ap_id' => $id,
                            ':description' => $description,
                            ':domain' => $domain,
                            ':protocol' => $protocol,
                            ':type' => $type,
                            ':alive' => $alive,
                            ':last_checked' => $last_checked,
                            ':flag' => $flag,
                            ':notes' => $notes,
                        );

                        foreach ($accounts_piwik_details_parameters as $accounts_piwik_details_parameter => $accounts_piwik_details_parameter_val) {

                            if (! isset($accounts_piwik_details_parameter_val))
                                $accounts_piwik_details_query->bindValue(
                                    $accounts_piwik_details_parameter, NULL, 
                                    PDO::PARAM_INT);
                            else
                                $accounts_piwik_details_query->bindValue(
                                    $accounts_piwik_details_parameter, 
                                    $accounts_piwik_details_parameter_val);
                        }

                        self::$lastQuery = $accounts_piwik_details_query->queryString;

                        $accounts_piwik_details_query->execute();

                        $this->db->commit();
                    } else {

                        throw new Exception(
                            "Foreign Key Transaction could not be completed.");
                    }
                }
            } catch (PDOException $e) {

                $this->db->rollback();

                // in the event of an error record the error to errorlog.html
                \PiwikManager\Helpers\Logger::newMessage($e, false, false, false, false);

                \PiwikManager\Helpers\Logger::customErrorMsg($e->getMessage(), false);

                return false;
            } catch (\Exception $e) {

                $this->db->rollback();

                // in the event of an error record the error to errorlog.html
                \PiwikManager\Helpers\Logger::newMessage($e, false, false, false, false);

                \PiwikManager\Helpers\Logger::customErrorMsg($e->getMessage(), false);

                return false;
            }

            return $id;

        }

        /**
        * Get Settings Page Values
        *
        * @param string|null $option_name            
        *
        * @return array
        */
        public function getSettings ($option_name = null)
        {

            $params = array();

            $sql = "SELECT * FROM Accounts_piwik_settings";

            if ($option_name) {

                $sql .= " WHERE option_name LIKE :option_name;";

                $params = array(
                    ':option_name' => $option_name
                );
            }

            $query = $this->db->prepare($sql);

            $query->execute($params);

            return $query->fetchAll(PDO::FETCH_ASSOC);

        }

        /**
        * Update Settings Table
        *
        * @param string $option_name            
        * @param mixed $option_value            
        */
        public function updateSettings ($option_name, $option_value, 
            $option_desc = '')
        {

            $exists_query = $this->db->prepare(
                "SELECT * FROM Accounts_piwik_settings WHERE option_name = :option_name");

            $exists_query->execute(
                array(
                    ':option_name' => $option_name
            ));

            if ($exists_query->fetch()) {

                // Update needed
                $query = $this->db->prepare(
                    "UPDATE Accounts_piwik_settings SET option_value = :option_value, option_desc = :option_desc WHERE option_name = :option_name");

                return $query->execute(
                    array(
                        ':option_value' => $option_value,
                        ':option_name' => $option_name,
                        ':option_desc' => $option_desc
                ));
            } else {

                // Insert needed
                $query = $this->db->prepare(
                    "INSERT INTO Accounts_piwik_settings (option_name, option_value, option_desc) VALUES(:option_name, :option_value, :option_desc)");

                return $query->execute(
                    array(
                        ':option_name' => $option_name,
                        ':option_value' => $option_value,
                        ':option_desc' => $option_desc
                ));
            }

        }

        public function updateCron($secs = 60, $job_id = '', $action = null)
        {
            $run = false;

            $last_run = $this->db->prepare(
                "SELECT * FROM Accounts_piwik_cron " . ($action ? "WHERE action = :action" : "") . " ORDER BY last_run DESC LIMIT 1");

            if ($action)

                $param = array(":action" => $action);

            else

                $param = array();

            $last_run->execute($param);

            $results = $last_run->fetch(\PDO::FETCH_ASSOC);

            if ($results) {

                if (time() - strtotime($results['last_run']) > $secs || $results['job_id'] == $job_id) {

                    $run = true;

                    $next_run = $this->db->prepare("UPDATE Accounts_piwik_cron SET last_run = :next_run, job_id = :job_id WHERE id = :id" . ($action ? " AND action = :action" : ""));

                    $params = array(

                        ':next_run' => date('Y-m-d H:i:s'),

                        ':job_id' => $job_id,

                        ':id' => $results['id'],

                    );

                    if ($action)

                        $params[":action"] = $action;

                    $next_run->execute(

                        $params

                    );

                }

            } else {

                $run = true;

                $next_run = $this->db->prepare("INSERT INTO Accounts_piwik_cron (last_run, job_id" . ($action ? ", action" : "") . ") VALUES (:next_run, :job_id" . ($action ? ", :action" : "") . ")");

                $params = array(

                    ':next_run' => date('Y-m-d H:i:s'),

                    ':job_id' => $job_id,

                );

                if ($action)

                    $params[":action"] = $action;

                $next_run->execute(

                    $params

                );

            }

            return $run;

        }

        public function lastRowCount ()
        {

            return $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        }

    }
