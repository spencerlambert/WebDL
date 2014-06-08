<?php
class WebDLControllerBase {
    protected $permalink;
    protected $name;
    protected $template_tag_replace = array();
    
    public function __construct() {
        $parts = self::get_request_parts();
        $this->permalink = $parts->permalink;
        $this->name = $parts->name;
        $this->template_tag_replace['USER-MESSAGE'] = "";
        $this->template_tag_replace['MAIN-HTML'] = "";
    }
    
    public function display_header() {
        $html = file_get_contents(WEBDL_ABSPATH."webdl/template/".WEBDL_TEMPLATE_NAME."/header.html");
        $html = $this->replace_data($html, true);
    }
    
    public function display_footer() {
        $html = file_get_contents(WEBDL_ABSPATH."webdl/template/".WEBDL_TEMPLATE_NAME."/footer.html");
        $html = $this->replace_data($html, true);
    }

    public function display_page() {
        $html = file_get_contents(WEBDL_ABSPATH."webdl/template/".WEBDL_TEMPLATE_NAME."/page.html");
        $html = $this->replace_data($html, true);        
    }

    public function display_not_found() {
        $html = file_get_contents(WEBDL_ABSPATH."webdl/template/".WEBDL_TEMPLATE_NAME."/not_found.html");
        $html = $this->replace_data($html, true);
    }
    
    // Will replace tags like <!--[**MAIN-HTML**]-->, with the array data named like "MAIN-HTML" in the $this->template_tag_replace
    // The controller object is passed to all of the WebPB... classes.  They can call set_template_tag() to add tags.
    public function replace_data($html, $do_echo = false) {
        foreach ($this->template_tag_replace as $name=>$val) {
            $html = str_replace('<!--[**'.$name.'**]-->', $val, $html);
        }
        if ($do_echo) echo $html;
        return $html;
    }
    
    // Function for adding additional replacement tags in the template files.
    public function set_template_tag($name, $val) {
        $this->template_tag_replace[$name] = $val;
    }
    
    public function load_page() {

        $db = WebDLResourceManager::get("DB_MASTER_PDO");

        $sql = "SELECT * FROM ".WEBDL_MASTER_DB_NAME_WITH_PREFIX."Page WHERE Controller=:name AND Permalink=:permalink";
        $params = array(':name'=>$this->name, ':permalink'=>$this->permalink);
        $sth = $db->prepare($sql);
        $sth->execute($params);

        $page_found = false;
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        if (isset($row['PageID'])) {
            $page_found = true;
            
            // Send the Content XML to the processor so each XML block is converted into a WebPB... class, for things like content, data, forms, widgets like google maps, etc. 
            $webp_objs = $this->process_xml($row['Content']);
            // Append each WebPB... html code to the MAIN-HTML template replacement tag
            foreach ($webp_objs as $webpb_obj) {
                $this->template_tag_replace['MAIN-HTML'] .= $webpb_obj->get_html();
            }
        }
        
        // Get any user messages
        $this->template_tag_replace['USER-MESSAGE'] = WebDLUserMessage::get_msg();
        
        // Page content is built, now display it.
        $this->display_header();
        if ($page_found) {
            $this->display_page();            
        } else {
            $this->display_not_found();            
        }
        $this->display_footer();
    }
        
    public static function get_requested_controller() {
        $parts = self::get_request_parts();
        //All installed controllers must follow the naming convention ie, ControllerApp
        $class_name = "WebDLController".$parts->name;
        return new $class_name;
    }
    
    public static function get_request_parts() {
        
        //Make sure the url is all in lower case
        $redirect_url = "/app/"; // set a controller, for when the PATH_INFO and REDIRECT_URL are not set.
        if (isset($_SERVER['PATH_INFO']))
            $redirect_url = strtolower($_SERVER['PATH_INFO']);
        if (isset($_SERVER['REDIRECT_URL']))
            $redirect_url = strtolower($_SERVER['REDIRECT_URL']);
        
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
        $res->name = "app"; //default controller
        if (in_array($tmp[1], $controllers)) {
            $res->name = $tmp[1];
            $res->permalink = substr($redirect_url, strlen($res->name) + 1);
        } else {
            //If the first part does not match any installed controller types, than the whole thing is the permalink.
            $res->permalink = $redirect_url;
        }
        
        //Make sure permalink always has slashes at the beginning and end, simplifies page matchup for SQL SELECT.
        $res->permalink = "/".trim($res->permalink, "/")."/";
        
        //Capitalize the first letter of the controller name.
        //When installing new controller types, they must follow the naming convention ie, ControllerApp
        $res->name = strtoupper(substr($res->name, 0, 1)).substr($res->name, 1);
        
        return $res;
    }
    
    //This fuction will process and create an array of WebPBs from XML that has been tagged inside <WebPB>...</WebPB>
    public function process_xml($xml) {
        $webpb_array = array();
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $webpbs = $dom->getElementsByTagName('WebDLPB');
        // Should technically only be one in this list, but hey you never know.
        foreach ($webpbs as $nodes) {
            foreach ($nodes->childNodes as $node) {
                if (WebDLSuperAutoloader::can_load($node->nodeName)) {
                    $obj = new $node->nodeName($node, $this);
                } else {
                    $obj = new WebDLPBBase($node, $this);
                }
                $webpb_array[] = $obj;
            }
        }
        return $webpb_array;
    }
    
};
?>