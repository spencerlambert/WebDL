<?php
    define('ABSPATH', dirname(__FILE__).'/');
    require_once(ABSPATH.'setup/init.php');
    
    $controller = ControllerBase::get_requested_controller();
    $controller->load_page();
?>