<?php
if(empty($argv[1])) {
	die('Specify the name of a job to add. e.g, php queue.php PHP_Job');
}

require '../lib/Resque.php';
date_default_timezone_set('GMT');
Resque::setBackend('127.0.0.1:6379');

$args = array(
	'time' => time(),
	'id' =>18,
        'scan_type' =>1,
	'uid' =>'a51dda7c7ff50b61eaea0444371f4a6a9301e501',
        /*"config"=>array(
                    "db"=>array("password"=>"weixin@2013",
                            "username"=>"weixin", 
                            "dsn"=>"mysql:dbname=weixin;host=192.168.1.121", 
                            "driver_options"=>array("1002"=>"SET NAMES utf8"),
                            "driver"=>"Pdo" 
                            ), 
                    "redis"=>array("host"=>"127.0.0.1:6379")
                )*/
);

$jobId = Resque::enqueue('default', $argv[1], $args, true);
echo "Queued job ".$jobId."\n\n";
?>
