<?php
class WebDLUserMessage {
    private static $msg = "";
    
    private function __construct() {} // can't create and instance of SuperAutoloader, it's static.

    static public function output($msg, $filename) {
        self::$msg .='
            <div class="alert alert-warning alert-dismissable">
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
              <p><strong>Warning!</strong> '.$msg.'</p><p>Message created by: '.$filename.'</p>
            </div>
        ';
    }
    
    static public function get_msg() {
        return self::$msg;
    }
}
?>