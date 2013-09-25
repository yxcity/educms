<?php
/**
 * 微信扩展接口测试
 */
    require_once("inc/basejob.php");
    require_once("inc/database.php");
    require_once("inc/wechat.php");
	
    class Massend extends BaseJob
    {
        var $db;
        var $wechat;
        var $callback;
        public function setUp(){
            if(!isset($this->args['id'])){
                $this->logger->addError("no task id\n"); 
                return;
            }
            parent::initConfig();
            $this->logger->addError('init massend task ' .$this->args['id'] .".");
            $this->db = new Database($this->config['db'],null);
            $this->db->open();
            $data = $this->db->row_query_one("select account,password from wx_account where uid in (select uid from massend_task where id=".$this->args['id'].")");
            if(!($data && isset($data['account']) && isset($data['password']))){
                return;
            }
            $this->wechat = new Wechat(array(
                'account'=>$data['account'],
                'password'=>$data['password'],
                'datapath'=>'data/cookie_',
                    'debug'=>true,
                    'logcallback'=>'logger'	
            ));
        }
        
        /*数据库操作过多，可以考虑用存储过程优化*/
        public function perform()
        {
            if(!isset($this->args['id'])){
                $this->logger->addError("no task id\n"); 
                return;
            }
            $task_id = (int)$this->args['id'];
            $this->logger->addError('execute mass send task' .$task_id .".");
            if(!($this->db && $this->wechat && $this->wechat->checkValid())){
                $this->logger->addError($task_id . ' db or wechat init error.');
                return;
            }
            
            $task = $this->db->row_select_one('massend_task',"id=$task_id and status=0");
            if(!$task){
                $this->logger->addError("task ${task_id} is not found in database!"); 
                return;
            }
            
            if(empty($task['news_id'])){
                $this->logger->addError("task ${task_id}'s news id is empty!"); 
                return;
            }
            
            /*获取发送正文*/
            $news = $this->db->row_select_one('news',"id=".$task['news_id']);
            $content=null;
            if($news['type']==2){/*文本消息*/
                $content = $news['description'];
            }else{/*图文消息*/
                $this->logger->addError("news is not supported by mass send");
                return;
            }
            
            if(empty($content)){
                $this->logger->addError("task ${task_id}'s content is empty!"); 
                return;
            }
            /*筛选目标用户*/
            $sql = 'select fakeid from members ';
            $where = " 1=1 and capacity>0 and uid='" . $task['uid']. "' ";
            if(empty($task['to_ids'])){/*未指定发送ID，通过条件检索*/
                if($task['sex']==1 || $task['sex']== 2){
                    $where .= "and sex='". $task['sex'] . "'";
                }
                if(isset($task['cities'])){
                    $cities = explode(',',$task['cities']);
                    $quote_cities = array();
                    foreach($cities as $city){
                        if(!empty($task['cities'])){
                            array_push($quote_cities,"'".$city."'");
                        }
                    }
                    if(count($quote_cities)){
                        $where .= " and city in(". implode(',',$quote_cities) . ")";
                    }
                }
            }else if($task['to_ids'] != '*'){
                $ids = explode(',',$task['to_ids']);
                foreach($ids as &$id){
                    $id="'".$id."'";
                }
                $where .= " and fakeid in(". implode(',',$ids) . ")";/*这样处理id为空或其它非法的情况*/
            }
            $this->logger->addError("task ${task_id}'s filter $where"); 
            /*粉丝过多时要循环多次进行处理*/
            $ids = $this->db->row_select('members',$where,0,'fakeid');
            /*更新任务信息*/
            $data = array(
                'total_count'=>count($ids),
                'status'=>1,/*执行中*/
                'process_time'=>time()
            );
            $this->db->row_update('massend_task',$data,"id=${task_id}");
            $this->logger->addError($ids);
            $suc_ids = array();
            $fail_ids = array();
            $count = 0;
            foreach($ids as $memb){
                if($count>0 && $count%10 == 0){/*发10条更新一下发送情况*/
                    $this->db->row_update('massend_task',array('sent_count'=>$count,'suc_count'=>count($suc_ids)),"id=${task_id}");
                }
                $fakeid = $memb['fakeid'];
                $this->logger->addError("task ${task_id} is sending to ${fakeid}.");
                $ret = $this->wechat->send($fakeid,$content);
                $count++;
                if(is_string($ret) && ($ret=json_decode($ret,1))){
                    if($ret['ret']==='0' && strtolower($ret['msg'])=='ok'){
                        array_push($suc_ids,$fakeid);/*记录失败的ID*/
                        continue;
                    }
                }
                array_push($fail_ids,$fakeid);/*记录失败的ID*/
            }
            
            /*减少成功用户的发送容量*/
            if(count($suc_ids)){
                $this->db->query_unbuffered("update members set capacity=capacity-1 where capacity>0 and fakeid in (". implode(',',$suc_ids)  .")");
            }
            
            /*更新任务完成信息*/
            $data = array(
                'status'=>2,/*完成*/
                'sent_count'=>$count,
                'suc_count'=>count($suc_ids)
            );
            if(count($fail_ids)){
                $data['fail_ids'] = implode(',',$fail_ids);
            }
            $this->db->row_update('massend_task',$data,"id=${task_id}");
            $this->logger->addError("task ${task_id}'s finised!"); 
        }

        public function tearDown(){
            if($this->db){
                $this->db->close();
            }
        }
    }