<?php
class WebDLPBFromRequest extends WebDLPBBase {
    
    protected $request;
    protected $result;
    protected $header = array();
    
    public function __construct($unique_id) {
        $this->request = new WebDLRequest();
        parent::__construct($unique_id);
    }
    
    public function push_column($c_id, $c_name) {
        $this->request->push_column($c_id);
        $this->header[$c_id] = $c_name;
    }

    public function push_match($c_id, $m_val, $type='AND') {
        $this->request->push_match($c_id, $m_val, $type); 
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