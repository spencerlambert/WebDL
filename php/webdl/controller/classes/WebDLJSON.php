<?php
class WebDLJSON {

    static public function set_header() {
        header('Content-Type: application/json');
    }
    
    static public function json_empty_array() {
        return json_encode(array());
    }
    
    static public function json_error($error) {
        return json_encode(array('error'=>$error));
    }

    static public function json_success() {
        return json_encode(array('success'=>true));
    }

    static public function json_dlm_result($result) {
        return json_encode($result->get_joined_data());
    }
}
?>