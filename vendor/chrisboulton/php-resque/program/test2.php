<?php
/**
 * 微信扩展接口测试
 */
        $loader = include_once '../../../autoload.php';
        
        use Monolog\Logger;
        use Monolog\Handler\StreamHandler;

        // create a log channel
        $log = new Logger('q');
        $log->pushHandler(new StreamHandler('data/test.log', Logger::WARNING));
        
         $log->pushProcessor(function ($record) {
            //$record['extra']['dummy'] = 'Hello world!';
            //$record['context'] = array('uid'=>'lwq');
            $record['context'] = 'lwq';
            // var_dump($record);
            return $record;
        });
        
        // add records to the log
        $log->addWarning('Foo',array('name'=>'linger','sub'=>array('key'=>'value')));
        $log->addError('Bar');
        $log->addDebug('debug info');
        
       
        /*include("inc/wechat.php");
	
	function logdebug($text){
		file_put_contents('./data/log.txt',$text."\n",FILE_APPEND);		
	};
	$options = array(
		'account'=>'lwq@etopshine.com',
		'password'=>'285bc838bec7084221a200666f7e4d19',
		'datapath'=>'./data/cookie_',
			'debug'=>true,
			'logcallback'=>'logdebug'	
	); 
	$wechat = new Wechat($options);
	if ($wechat->checkValid()) {
		// 获取用户信息
		//$wechat->send('1424293300','test mass send for wechat 5.0');	
		$wechat->send('117558','test mass send for wechat 5.0');	
	}*/

        
