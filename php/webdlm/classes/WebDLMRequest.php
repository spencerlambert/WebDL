<?php
class WebDLMRequest {
    protected $type;
    protected $columns = array();
    protected $match_on = array();
    
    public function __construct() {
        
    }
    
    public function set_type($type) {
        $type = strtoupper($type);
        switch ($type) {
            case "GET":
                $this->type = "GET";
                break;
            case "POST":
            case "ADD":
            case "UPDATE":
                $this->type = "POST";
                break;
            case "DELETE":
                $this->type = "DELETE";
                break;
            default:
                UserMessage::output('Request type '.$type.' not supported, please use GET, POST, or DELETE', 'WebDLMRequest.php');
                return false;
        }
        return true;
    }
    
    public function push_column($c_id, $c_val='') {
        $class = new stdClass();
        $class->c_id = $c_id;
        $class->c_val = $c_val;
        $this->columns[] = $class;
    }
    
    // Currently valid types: AND, OR, and WILDCARD
    public function push_match($c_id, $m_val, $type='AND') {
        $type = strtoupper($type);
        if ($type != 'AND' && $type != 'OR' && $type != 'WILDCARD') {
            UserMessage::output('Unsupported match type '.$type.', please use AND, OR, or WILDCARD', 'WebDLMRequest.php');
            return false;            
        }
        $class = new stdClass();
        $class->c_id = $c_id;
        $class->m_val = $m_val;
        $class->type = $type;
        $this->match_on[] = $class;
        return true;
    }
    
    public function get_type() {
        return $this->type;
    }
    
    public function get_columns() {
        return $this->columns;
    }
    
    public function get_matches() {
        return $this->match_on;
    }
    
}
?>