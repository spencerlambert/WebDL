<?php
/**
 * Create class autoloader that searches all folders
 * named "classes", to find a class.  The class name must
 * match the filename .php.  It is advisable that you
 * start each class name created with a prefix.
 *
 * Load the config.php file.
 *
 * Starts a PHP session.
 */

class WebDLSuperAutoloader {
    private static $class_dirs = array();  // All folders named "classes"
    private static $initialized = false;
    private function __construct() {} // can't create and instance of SuperAutoloader, it's static.
    
    private static function initialize() {
        if (self::$initialized) return;

        $working_dirs = glob(WEBDL_ABSPATH."/webdl/*", GLOB_ONLYDIR);
        $next_dirs = array();
        $more_dirs = true;
        if (count($working_dirs) == 0) $more_dirs = false;
        while ($more_dirs) {
            foreach ($working_dirs as $dir) {
                $next_dirs[] = $dir;
                if (substr($dir, -7) === "classes") self::$class_dirs[] = $dir . "/";
            }
            $working_dirs = array();
            foreach ($next_dirs as $dir) {
                $working_dirs = array_merge($working_dirs, glob($dir."/*", GLOB_ONLYDIR));
            }
            $next_dirs = array();
            if (count($working_dirs) == 0) $more_dirs = false;
        }

        self::$initialized = true;
    }
    
    public static function load($class) {
        self::initialize();
        
        $found_class = false;
        foreach (self::$class_dirs as $dir) {
            if (file_exists($dir . $class . '.php')) {
                require_once($dir . $class . '.php');
                $found_class = true;
                break;
            }
        }
        
        if ($found_class === false) {
            $msg = "Class ".$class." not found. It can be automatically loaded if it's in a directory named ".'"classes" and named '.$class.'.php.';
            WebDLUserMessage::output($msg, 'init.php');
        }
        
    }
    
    public static function can_load($class) {
        //if it's already loaded, then it can be.
        if (class_exists($class)) return true;
        
        $found_class = false;
        foreach (self::$class_dirs as $dir) {
            if (file_exists($dir . $class . '.php')) {
                $found_class = true;
                break;
            }
        }
        
        return $found_class;
        
    }
    
};

spl_autoload_register('WebDLSuperAutoloader::load');

include_once(WEBDL_ABSPATH."/webdl/setup/config.php");
include_once(WEBDL_ABSPATH."/webdl/setup/defines.php");

if (WEBDL_DEBUG) {
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
}

session_start();
?>