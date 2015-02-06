<?php

/**
 * Project: PiwikManager MVC
 * File: /helpers/autoloader.php
 * Purpose: SPL class to facilitate class autoloading
 */
namespace PiwikManager\Helpers;

if (! defined('PIWIKAPP'))
    exit('No direct script access allowed');
    
    // Register Autoloader
class Autoloader
{

    public function __construct ()
    {

        spl_autoload_register(
                array(
                        $this,
                        '_autoloader'
                ));
    
    }

    /*
     * private function _autoloader($_class) {
     *
     * $class = str_replace(__NAMESPACE__ . "\\", "", $_class);
     * $class_filename = strtolower($class).'.php';
     * $class_root = dirname(dirname(__FILE__));
     * $cache_file = "{$class_root}/core/cache/classpaths.cache";
     * $path_cache = (file_exists($cache_file)) ?
     * unserialize(file_get_contents($cache_file)) : array();
     * $autoloaded = false;
     * if (!is_array($path_cache)) { $path_cache = array(); }
     *
     * if (array_key_exists($class, $path_cache)) {
     * // Load class using path from cache file (if the file still exists)
     * if (file_exists($path_cache[$class])) { require_once $path_cache[$class];
     * }
     *
     * $_parent_class = get_parent_class($class);
     * $parent_class = $_parent_class ? str_replace(__NAMESPACE__ . "\\", "",
     * $_parent_class) : false;
     *
     * if ($parent_class) {
     * if (array_key_exists($parent_class, $path_cache)) {
     * // Load class using path from cache file (if the file still exists)
     * if (file_exists($path_cache[$parent_class])) {
     * require_once $path_cache[$parent_class];
     * $autoloaded = true;
     * }
     * }
     * } else {
     * $autoloaded = true;
     * }
     *
     * }
     *
     * if (! $autoloaded) {
     * // Determine the location of the file within the $class_root and, if
     * found, load and cache it
     * $directories = new \RecursiveDirectoryIterator($class_root);
     * foreach(new \RecursiveIteratorIterator($directories) as $file) {
     * // echo $class . ": " . $class_filename . ": " . $file->getRealPath() .
     * "<br>";
     * if (strtolower($file->getFilename()) == $class_filename) {
     * $full_path = $file->getRealPath();
     * $path_cache[$class] = $full_path;
     * require_once $full_path;
     * // echo $class . "<br>";
     *
     * $_parent_class = get_parent_class($class);
     * $parent_class = $_parent_class ? str_replace(__NAMESPACE__ . "\\", "",
     * $_parent_class) : false;
     *
     * if ($_parent_class) {
     * // echo $_parent_class . "<br>";
     * self::_autoloader($_parent_class);
     * }
     * break;
     * }
     * }
     *
     * }
     *
     * $serialized_paths = serialize($path_cache);
     * if ($serialized_paths != $path_cache) { file_put_contents($cache_file,
     * $serialized_paths); }
     *
     * }
     */
    private function _autoloader ($class)
    {
        
        // project-specific namespace prefix
        $prefix = 'PiwikManager\\';
        
        // base directory for the namespace prefix
        $base_dir = dirname(__DIR__) . '/';
        
        // does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // no, move to the next registered autoloader
            return;
        }
        
        // get the relative class name
        $relative_class = substr($class, $len);
        $relative_class_i = strtolower(substr($class, $len));
        
        // replace the namespace prefix with the base directory, replace
        // namespace
        // separators with directory separators in the relative class name,
        // append
        // with .php
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        $file_i = $base_dir . str_replace('\\', '/', $relative_class_i) . '.php';
        
        // if the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        } elseif (file_exists($file_i)) {
            require_once $file_i;
        }
    
    }

}

?>
