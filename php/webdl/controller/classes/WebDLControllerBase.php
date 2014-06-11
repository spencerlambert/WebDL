<?php
abstract class WebDLControllerBase {
    protected $uri;
    protected $method;
    protected $name;
    
    public function __construct($url_parts) {
        $this->uri = $url_parts->uri;
        $this->method = $url_parts->method;
        $this->name = $url_parts->name;
    }
        
    abstract public function load();
    
    public static function get_requested_controller() {
        $parts = self::get_request_parts();
        //All installed controllers must follow the naming convention ie, ControllerApp
        $class_name = "WebDLController".$parts->name($parts);
        return new $class_name;
    }
    
    // Parse the URL and break it into the required parts for the specific controller
    public static function get_request_parts() {
        
        //Make sure the url is all in lower case
        $redirect_url = "/rest/"; // set a controller, for when the PATH_INFO and REDIRECT_URL are not set.
        if (isset($_SERVER['PATH_INFO']))
            $redirect_url = $_SERVER['PATH_INFO'];
        if (isset($_SERVER['REDIRECT_URL']))
            $redirect_url = $_SERVER['REDIRECT_URL'];
        
        //Build a list of controler types bases on the directories in the controller folder
        $controller_dirs = glob(WEBDL_ABSPATH."/webdl/controller/*", GLOB_ONLYDIR);
        $controllers = array();
        foreach ($controller_dirs as $dir) {
            $tmp = explode("/", $dir);
            $name = $tmp[count($tmp) - 1];
            if ($name == "classes") continue;
            $controllers[] = $name;
        }

        //Parse the parts of the URL.
        $res = new stdClass();
        $tmp = explode("/", $redirect_url);
        $res->name = "rest"; //default controller
        $res->method = $_SERVER['REQUEST_METHOD'];
        if (in_array(strtolower($tmp[1]), $controllers)) {
            $res->name = strtolower($tmp[1]);
            $res->uri = substr($redirect_url, strlen($res->name) + 1);
        } else {
            //If the first part does not match any installed controller types, than the whole thing is the permalink and we use the default controller.
            $res->uri = $redirect_url;
        }
        
        //Make sure permalink always has slashes at the beginning and end, simplifies page matchup for SQL SELECT.
        $res->uri = "/".trim($res->uri, "/")."/";
        
        //Capitalize the first letter of the controller name.
        //When installing new controller types, they must follow the naming convention ie, ControllerApp
        $res->name = strtoupper(substr($res->name, 0, 1)).substr($res->name, 1);
        
        return $res;
    }
    
    
};
?>