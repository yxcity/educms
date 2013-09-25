<?php
date_default_timezone_set('GMT');
require 'bad_job.php';
require 'job.php';
require 'php_error_job.php';

function autoload($class){
    $cls_file = strtolower($class) . ".php";
    if(is_file($cls_file)){
        require_once($cls_file);
    }
}

spl_autoload_register("autoload");

require '../resque.php';
?>
