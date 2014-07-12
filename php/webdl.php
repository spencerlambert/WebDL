<?php
    define('WEBDL_ABSPATH', dirname(__FILE__).'/');
    set_include_path(get_include_path() . PATH_SEPARATOR . WEBDL_ABSPATH . 'webdl/library');
    require_once(WEBDL_ABSPATH.'webdl/setup/init.php');
    
    $controller = WebDLControllerBase::get_requested_controller();
    $controller->load();
?>
