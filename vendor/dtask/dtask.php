<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
if (!file_exists('vendor/autoload.php')) {
    die('please fix composer.\n');
}

$loader = include 'vendor/autoload.php';
$entry = $loader->findFile('Resque');
if($entry){
    require dirname($entry) . "/../resque.php";
}else{
    die('resque is missing.');
}



?>
