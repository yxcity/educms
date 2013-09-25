<?php
    namespace dtask\task\master;
    use dtask\task\master\Worker;
    use \dtask\task\utils\Database;
    use \dtask\task\utils\Wechat;
    /**
     * 微信扩展接口测试
     */
    define("SCRM_GROUP", "__scrm__");

    class SynUser extends Worker 
    {
        var $db;
        var $callback;
        var $format_data;
        var $wechat;
        var $full_scan;
        var $uid;
        public static $count = 0;
        public static $update_count = 0;
        public function init(){
            if(!isset($this->args['uid'])){
                $this->_logger->addError("uid is missing\n"); 
                return false;
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
                $this->_logger->addError("can not find wechat account info for " . $this->args['uid']); 
                return false;
            }
            
            $this->wechat = new Wechat(array(
                'account'=>$data['account'],
                'password'=>$data['password'],
                'datapath'=>getcwd() . '/data/cookie_',
                'debug'=>true,
                'logger' =>$this->_logger
            ));
            
            $connection = $this->db;
            $format_data  = function($data){
                unset($data['detail']['Groups']);
                if(!isset($data['detail']) || empty($data['detail']['id'])){
                    unset($data['detail']);
                }else{
                    $data = $data['detail'];
                }
                return array(
                    'fakeid'=>$data['id'],
                    'nickname'=>$data['nick_name'],
                    'remarkname'=>$data['remark_name'],
                    'groupid'=>$data['group_id']
                );
            };
            $this->callback = function($data)use($connection,$uid,$format_data){
                foreach($data as $memb){
                    $data = $format_data($memb);
                    $data['uid']=$uid;
                    /*更新重复数据*/
                    if($connection->row_count('members',"uid='".$uid."' and  fakeid='". $data['fakeid']."'")>0){
                        $connection->row_update('members',$data,"fakeid='". $data['fakeid']."'");
                        SynUser::$update_count++;
                    }else{
                        $data['capacity']=10;
                        $connection->row_insert('members',$data);
                    }
                    //if($connection->row_count('members',"uid='".$uid."' and  fakeid='". $data['fakeid']."'")<=0){
                         //$this->_logger->addError("error:".$data['id']);
                    //}
                }
            };
            
            $this->sync_remote_users();
            return true;
        }
        
        private function sync_remote_users()
        {
            $scrm_id = 0;
            if(!($this->db && $this->wechat)){
                $this->_logger->addError($this->uid . ' db or webchat init error.');
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
                        $this->_logger->addError( "create scrm group:" . SCRM_GROUP ." fail!");
                        return;
                    }
                }
                $this->_logger->addDebug($this->uid . " " . SCRM_GROUP . " group id:${scrm_id} is ok.");
                
                if($this->full_scan){//扫描所有分组，创建__scrm__分组，并把未分组用户挪入。
                    $this->_logger->addInfo($this->uid . " begin full scan.");
                    $groups = $this->wechat->getGroups();
                    foreach($groups as $group){
                        $this->wechat->syncGroupMemb($group['id'], $this->callback,$group['id']==0?$scrm_id:-1);
                    }
                }else{//增量扫描，扫描未分组，创建__scrm__分组，并把未分组用户挪入
                    $this->_logger->addInfo($this->uid . " begin increment scan.");
                    $this->wechat->syncGroupMemb(0, $this->callback,$scrm_id);
                }
            }else{
                $this->_logger->addError($this->uid . ' wechat is not valid.');
            }
        }
        
        public function splitTask(){
            return array();
        }
        public function finish(){
            if($this->db){
                $this->db->close();
            }
        }
    }