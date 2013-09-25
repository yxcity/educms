<?php
date_default_timezone_set('GMT');
require_once ('inc/utils.php');
require_once '../../../autoload.php';
$global_config = require('inc/config.php');
function autoload($class){
    $cls_file = strtolower($class) . ".php";
    if(is_file($cls_file)){
        require_once($cls_file);
    }
}

spl_autoload_register("autoload");
require_once '../resque.php';

?>