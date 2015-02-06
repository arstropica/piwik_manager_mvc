<?php

    /*
    * logger class - Custom errors
    *
    * @author David Carr - dave@daveismyname.com - http://www.daveismyname.com
    * @version 2.1
    * @date June 27, 2014
    */
    namespace PiwikManager\Helpers;

    class Logger
    {

        /**
        * determins if error should be displayed
        *
        * @var boolean
        */
        private static $print_error = false;

        /**
        * location of error file
        *
        * @var string
        */
        static $error_file;

        /**
        * in the event of an error show this message
        */
        public static function customErrorMsg ($msg = '', $exit = true)
        {

            if ($msg)
                echo "<p class='content_middle_msg'>The following error occured: <br/>{$msg}.<br/> The error has been reported.<br/>Return to the <a href='javascript:history.go(-1)'>previous page</a>.</p>";
            else
                echo "<p class='content_middle_msg'>An error occured, The error has been reported.<br/>Return to the <a href='javascript:history.go(-1)'>previous page</a>.</p>";

            if ($exit)
                exit();

        }

        /**
        * saved the exception and calls customer error function
        *
        * @param exeption $e            
        */
        public static function exception_handler (\Exception $e)
        {

            self::newMessage($e, self::$print_error);
            self::customErrorMsg($e->getMessage());

        }

        /**
        * saves error message from exception
        *
        * @param numeric $number
        *            error number
        * @param string $message
        *            the error
        * @param string $file
        *            file originated from
        * @param numeric $line
        *            line number
        */
        public static function error_handler ($number, $message, $file, $line)
        {

            $msg = "$message in $file on line $line";

            // if (($number !== E_ERROR) && ($number < 2048)) {
            if ($number === E_ERROR) {
                self::errorMessage($msg, self::$print_error);
                self::customErrorMsg($message);
            }

            return 0;

        }

        /**
        * new exception
        *
        * @param Exception $exception            
        * @param boolean $print_error
        *            show error or not
        * @param boolean $clear
        *            clear the errorlog
        * @param string $error_file
        *            file to save to
        */
        public static function newMessage (Exception $exception, 
            $print_error = false, $clear = false, $error_file = false, $exit = true)
        {

            $additional_info = "";
            if (get_class($exception) == "PDOException") 
                $additional_info = \PiwikManager\Models\Model::$lastQuery;

            $message = $exception->getMessage();
            $code = $exception->getCode();
            $file = $exception->getFile();
            $line = $exception->getLine();
            $trace = $exception->getTraceAsString();
            $date = date('M d, Y G:iA');
            $error_file = $error_file ?  : self::$error_file;

            $log_message = "<h3 class='subtitle'>Exception information:</h3>\n
            <p><strong>Date:</strong> {$date}</p>\n
            <p><strong>Message:</strong> {$message}</p>\n
            <p><strong>Code:</strong> {$code}</p>\n
            <p><strong>File:</strong> {$file}</p>\n
            <p><strong>Line:</strong> {$line}</p>\n
            <h3>Stack trace:</h3>\n
            <pre>{$trace}</pre>\n
            <pre>{$additional_info}</pre>\n
            <hr />\n";

            if (is_file($error_file) === false) {
                file_put_contents($error_file, '');
            }

            if ($clear) {
                $content = '';
            } else {
                $content = file_get_contents($error_file);
            }

            file_put_contents($error_file, $log_message . $content);

            if ($print_error === true) {
                echo $log_message;
                if ($exit)
                    exit();
            }

        }

        /**
        * custom error
        *
        * @param string $error
        *            the error
        * @param boolean $print_error
        *            display error
        * @param string $error_file
        *            file to save to
        */
        public static function errorMessage ($error, $print_error = false, 
            $error_file = false, $exit = true)
        {

            $date = date('M d, Y G:iA');
            $log_message = "<p>Error on $date - $error</p>";
            $error_file = $error_file ?  : self::$error_file;

            if (is_file($error_file) === false) {
                file_put_contents($error_file, '');
            }

            $content = file_get_contents($error_file);
            file_put_contents($error_file, $log_message . $content);

            if ($print_error == true) {
                echo $log_message;

                if ($exit)
                    exit();
            }

        }

    }
