<?php
/**
 * 微信扩展接口测试
 */
	require_once("inc/basejob.php");
	require_once("inc/database.php");
    require_once("inc/wechat.php");
	
    class SynMsg extends BaseJob
    {
        var $db;
        var $webchat;
        var $callback;
        public function setUp(){
            global $db_options;
            $this->db = new Database($db_options,null);
            $this->db->open();
            $this->wechat = new Wechat(array(
                'account'=>'lwq@etopshine.com',
                'password'=>'wfnxkj',
                'datapath'=>'data/cookie_',
                    'debug'=>true,
                    'logcallback'=>'func_log'	
            ));
            $this->callback = function($connection,$data){
                /*if($data){
                    return;
                }*/
                
                foreach($data as $msg){
                    /*更新重复数据*/
                    if($connection->row_count('messages','id='. $msg['id'])>0){
                        $connection->row_update('messages',$msg,'id='. $msg['fakeId']);
                    }else{
                        $connection->row_insert('messages',$msg);
                    }
                }
            };
        }
        
        public function perform()
        {
            if(!($this->db && $this->wechat)){
                log('db or webchat init error.');
                return;
            }
            
            if($this->wechat->checkValid()){
                $this->wechat->getUnsyncMsgs($this->db,$this->callback);
            }else{
                log('wechat is not valid.');
            }
        }

        public function tearDown(){
            if($this->db){
                $this->db->close();
            }
        }
    }

	
	function func_log($text){
		file_put_contents('data/syncuser.log',$text."\n",FILE_APPEND);		
	};

    function echo_callback($msg){
        echo("##" . $msg);
    };

    /*test case*/
    $job = new SynMsg();
    $job->setUp();
    $job->perform();
    $job->tearDown();