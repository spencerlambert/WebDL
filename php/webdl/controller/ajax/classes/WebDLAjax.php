<?php
class WebDLAjax {
    public function __construct() {
        
    }
    
    static public function json_empty_array() {
        return json_encode(array());
    }
    
    static public function json_error($error) {
        return json_decode(array('error'=>$error));
    }
}
?>