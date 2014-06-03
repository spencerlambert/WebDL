<?php
    define('WEBDL_ABSPATH', dirname(__FILE__).'/');
    require_once(WEBDL_ABSPATH.'webdl/setup/init.php');
    
    $controller = WebDLControllerBase::get_requested_controller();
    $controller->load_page();
?>