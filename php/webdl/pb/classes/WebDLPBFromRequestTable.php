<?php
class WebDLPBFromRequestTable extends WebDLPFromRequest {
    
    protected $header = array();
    
    public function __construct($unique_id) {
        parent::__construct($unique_id);
    }
    
    public function finish() {
        $this->result = WebDLMController::dlm_request($this->request);
        $this->set_html();
    }
    
    private function set_html() {
        $this->html = $this->get_angularjs();
        $this->html .= $this->get_div();
    }
    
    private function get_div() {
        
    }
    
    
    
}
?>