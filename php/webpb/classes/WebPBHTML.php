<?php
class WebPBHTML extends WebPBBase {
    
    // The controller is passed so template tags to replace with actual data can be added.
    public function __construct($dom_node, $controller) {
        parent::__construct($dom_node, $controller);
        $this->set_html();
    }
    
    protected function set_html() {
        foreach ($this->dom_node->childNodes as $node) {
            if (strtoupper($node->nodeName) == 'HTML') $this->html = $node->nodeValue;
        }
    }
    
}
?>