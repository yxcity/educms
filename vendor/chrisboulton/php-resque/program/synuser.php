<?php
/**
 * 微信扩展接口测试
 */
    require_once("inc/basejob.php");
    require_once("inc/database.php");
    require_once("inc/wechat.php");
    define("SCRM_GROUP", "__scrm__");

    class SynUser extends BaseJob
    {
        var $db;
        var $callback;
        var $wechat;
        var $full_scan;
        var $uid;
        public static $count = 0;
        public static $update_count = 0;
        public function setUp(){
            parent::initConfig();
            global $wx_options,$db_options;
            if(!isset($this->args['uid'])){
                $this->logger->addError("uid is missing\n"); 
                return;
            }
            $uid = $this->args['uid'];
            if(isset($this->args['scan_type']) && $this->args['scan_type']==1){
                $this->full_scan = true;
            }else{
                $this->full_scan = false; 
            }
             
            $this->db = new Database($this->config['db'],null);
            $this->db->open();
            $data = $this->db->row_query_one("select account,password from wx_account where  uid='".$this->args['uid']."'");
            if(!($data && isset($data['account']) && isset($data['password']))){
                return;
            }
            $this->wechat = new Wechat(array(
                'account'=>$data['account'],
                'password'=>$data['password'],
                'datapath'=>'data/cookie_',
                    'debug'=>true
            ));
            $connection = $this->db;
            $this->callback = function($data)use($connection,$uid){
                foreach($data as $memb){
                    unset($memb['detail']['Groups']);
                    $data = null;
                    if(!isset($memb['detail']) || empty($memb['detail']['fakeId'])){
                        unset($memb['detail']);
                        $data = $memb;
                    }else{
                        $data = $memb['detail'];
                    }
                    $data['uid']=$uid;
                    /*更新重复数据*/
                    if($connection->row_count('members',"uid='".$uid."' and  fakeid=". $data['fakeId'])>0){
                        $connection->row_update('members',$data,'fakeid='. $data['fakeId']);
                        SynUser::$update_count++;
                    }else{
                        $data['capacity']=10;
                        $connection->row_insert('members',$data);
                    }
                    if($connection->row_count('members',"uid='".$uid."' and  fakeid=". $data['fakeId'])<=0){
                         $this->logger->addError("error:".$data['fakeId']);
                    }
                }
            };
        }
        
        public function perform()
        {
            $scrm_id = 0;
            if(!($this->db && $this->wechat)){
                $this->logger->addError($this->uid . ' db or webchat init error.');
                return;
            }
            
            if($this->wechat->checkValid()){
                $groups = $this->wechat->getGroups();
                $scrm_exists = false;
                foreach($groups as $group){
                    if(SCRM_GROUP == $group['name']){
                        $scrm_exists = true;
                        $scrm_id = $group['id'];
                        break;
                    }
                }
                if($scrm_id <= 0){
                    $ret = $this->wechat->addGroup(SCRM_GROUP);
                    if($ret){
                        $scrm_id = $ret;
                    }else{
                        $this->logger->addError( "create scrm group:" . SCRM_GROUP ." fail!");
                        return;
                    }
                }
                $this->logger->addDebug($this->uid . " " . SCRM_GROUP . " group id:${scrm_id} is ok.");
                
                if($this->full_scan){//扫描所有分组，创建__scrm__分组，并把未分组用户挪入。
                    $this->logger->addError($this->uid . " begin full scan.");
                    $groups = $this->wechat->getGroups();
                    foreach($groups as $group){
                        $this->wechat->syncGroupMemb($group['id'], $this->callback,$group['id']==0?$scrm_id:-1);
                    }
                }else{//增量扫描，扫描未分组，创建__scrm__分组，并把未分组用户挪入
                    $this->logger->addError($this->uid . " begin increment scan.");
                    $this->wechat->syncGroupMemb(0, $this->callback,$scrm_id);
                }
            }else{
                $this->logger->addError($this->uid . ' wechat is not valid.');
            }
        }

        public function tearDown(){
            if($this->db){
                $this->db->close();
            }
        }
    }