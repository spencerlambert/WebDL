<?php
/********
 * WebPB is for WebDL Page Block
 * HTML content and forms are created using XML that defines a Page Block.
 */
class WebPBBase {
    protected $html;
    protected $dom_node;
    protected $controller;
    
    public function __construct($dom_node, $controller) {
        $this->dom_node = $dom_node;
        $this->controller = $controller;
        if (get_class($this) == "WebPBBase") {
            $msg = 'Looks like a tag named "'.$dom_node->nodeName.'" has been used in the page content XML, but a corresponding PHP class does not exist. A class that extents WebPBBase with the name "'.$dom_node->nodeName.'" needs to be created.  Look in the '.ABSPATH.'webpb/ folder.';
            UserMessage::output($msg, 'WebPBBase.php');
        }
    }

    public function get_html() {
        return $this->html;
    }

    /***
     * Doesn't work. The examples online show the overloaded data in an array.  I dont' want that.
     * 
    public function __get($name) {
        if (isset($this->$name)) {
            return $this->$name;
        } else {
            UserMessage::output('Error: Trying to get a varible named "'.$name.'" from '.get_class($this).' that is non-existent.');
        }
    }
     */
    
}

?>