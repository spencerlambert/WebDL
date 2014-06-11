<?php
class WebDLControllerRest extends WebDLControllerBase {
    public function __construct($url_parts) {
        parent::__construct($url_parts);
    }
    
    public function load() {

        $parts = explode("/", $this->uri);
        
        $r_id = "";
        $match_col = "";
        $match_val = "";
        
        
        if (count($parts) == 0) {
            echo WebDLJSON::json_error("Can't make a request without identifying a record.");
            return false;            
        }
        
        $r_id = $parts[0];
        if (isset($parts[1])) $match_col = $parts[1];
        if (isset($parts[2])) $match_val = urldecode($parts[2]);
        
        switch($this->method) {
            case "GET":
                $record = new WebDLMRecord($r_id);
                if ($match_col != "") {
                    $res = $record->push_match($match_col, $match_val);
                    if ($res === false) {
                        echo WebDLJSON::json_error("Can't match on ".$match_col);
                        return false;                        
                    }
                }
                break;
            case "POST":
            case "PUT":
            case "DELETE":
            default:
                echo WebDLJSON::json_error("HTTP Method: ".$this->methos." not yet supported");
                return false;
        }
        
        echo WebDLJSON::json_dlm_result(WebDLMController::dlm_record($record));
        return true;
    }
    
}
?>