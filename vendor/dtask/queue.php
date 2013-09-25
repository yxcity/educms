<?php
if (!file_exists('vendor/autoload.php')) {
    die('please fix composer.\n');
}

$loader = include 'vendor/autoload.php';

if(empty($argv[1])) {
	die('Specify the name of a job to add. e.g, php queue.php PHP_Job');
}

date_default_timezone_set('GMT');
Resque::setBackend('127.0.0.1:6379');
/*
$args = array(
	'time' => time(),
	'task' => array(
            'master'=>'dtask\\task\master\\SynUser',
            'worker'=>'dtask\\task\sub\\SynUser'
            )
    );
    */
$master_id = 'dmaster:'.md5(uniqid(rand(),true));
$args = array(
        'uid' => 'b4663715b411df07d8e6b5a1bbf85dd02ae8982e',
        'id' => 1,
        'time' => time(),
        'config'=>array(
            'db' => array(
                    'hostname'=>'192.168.1.121',
                    'database'=>'weixin',
                    'username'=>'weixin',
                    'password'=>'weixin@2013'
                    ),
            'redis'=>array(
                 'host'=>'192.168.1.111:6379'
                )
        ),
        
        'task' => array(
            'master'=>'dtask\\task\\master\\Massend',
            'worker'=>'dtask\\task\\sub\\Massend',
            'master_id'=>$master_id
            )
    );

$jobId = Resque::enqueue('MasterTasks', $argv[1], $args, true);
echo "Queued job ".$jobId."\n\n";
?>
