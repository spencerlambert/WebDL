<?php
class WebDLControllerAjax extends WebDLControllerBase {
    public function __construct() {
        parent::__construct();
    }
    
    public function load_page() {
        if (isset($_SERVER['PATH_INFO'])) {
            $ajax_calling_uri = $_SERVER['PATH_INFO'];
        } else {
            // Bad URI or it's being called incorrectly.
            echo WebDLAjax::json_error("PHP PATH_INFO not set, ether the URI is missing, or Ajax is being called incorrectly.");
            return false;
        }
        
        // Figure out what class and function is being requested for this Ajax call.
        $tmp = explode("/", $ajax_calling_uri);
        if (count($tmp) < 3) {
            // Ajax call format is /ajax/[CLASS]/[FUNCTION]/, so we need at least three items in the array or else we can't continue.
            echo WebDLAjax::json_error("URI is not correct, needs to follow this style, /webdl.php/ajax/[class]/[function]/");
            return false;            
        }
        $class = $tmp[2];
        $function = $tmp[3];
        
        // Security Though: May want to make it so only classes under the pb folder can be called.
        // It's not so bad, because no parameters can be passed, and it will only call static functions.
        if (!WebDLSuperAutoloader::can_load($class)) {
            // No class by this name.
            echo WebDLAjax::json_error("Can't load class: ".$class.", check the name and make sure the class in the webdl folder and inside a classes folder.");
            return false;            
        }
        
        // Security Thought: The function could always be "return_ajax()". This would lock down hackers from calling random function.
        // It might also be nice to be able to make multiple ajax call back types within the same class.
        if (!method_exists($class, $function)) {
            // Function is not part of class
            echo WebDLAjax::json_error("Can't find function: ".$function.", check the function name");
            return false;            
        }
        
        echo $class::$function();
        return true;
        
    }
    
}
?>