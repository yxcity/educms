<?php
    /**
     * 微信扩展接口测试
     */
    namespace dtask\task\sub;
    use dtask\task\sub\Worker;
    use \dtask\task\utils\Database;
    use \dtask\task\utils\Wechat;
    
    class Massend extends Worker
    {
        var $db;
        var $wechat;
        var $callback;
        private $content;
        public function init(){
           if(!($this->content = $this->getShareData('content'))){
                $this->_logger->addError('miss parameter content.');
                return false; 
            }
            
            if(!isset($this->args['id'])){
                $this->_logger->addError('miss parameter id.');
                return false;
            }
            $this->content = $this->content;
            $this->db = new Database($this->config['db'],null);
            $this->db->open();
            $data = $this->db->row_query_one("select account,password from wx_account where uid in (select uid from massend_task where id=".$this->args['id'].")");
            if(!($data && isset($data['account']) && isset($data['password']))){
                $this->_logger->addError('can not find wechat account from db.',$this->args);
                return false;
            }
            $this->wechat = new Wechat(array(
                'account'=>$data['account'],
                'password'=>$data['password'],
                'datapath'=>getcwd() . '/data/cookie_',
                    'debug'=>true
            ));
            if(!$this->wechat){
                $this->_logger->addError('init wechat error.');
                return false;
            }
            
            if(!$this->wechat->checkValid()){
                $this->_logger->addError('wechat checkValid false.');
                return false;
            }
            return true;
        }
        
        public function each_run($id){
            $ret = $this->wechat->send($id,$this->content);
            if(is_string($ret) && ($ret=json_decode($ret,1))){
                if($ret['ret']==='0' && strtolower($ret['msg'])=='ok'){
                    return true;
                }
            }
            $this->_logger->addError("Send content to {$id} false,ret:" . print_r($ret));
            return false;
            /*
            if(rand(0,1))
                return true;
            else
                return false;*/
        }

        public function finish(){
            if($this->db){
                $this->db->close();
            }
        }
    }