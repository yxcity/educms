<?php
/**
 * 微信扩展接口测试
 */
    namespace dtask\task\master;
    use dtask\task\master\Worker;
    use \dtask\task\utils\Database;
    use \dtask\task\TaskDb;
    use \dtask\task\Status;
	
    class Massend extends Worker
    {
        var $db;
        var $wechat;
        var $callback;
        private $massend_task;
        protected $uid;
        public function init(){
            if(!isset($this->args['id'])){
                $this->_logger->addError("no task id"); 
                return;
            }
            $this->db = new Database($this->config['db'],null);
            $this->db->open();
            $data = $this->db->row_query_one("select account,password,uid from wx_account where uid in (select uid from massend_task where id=".$this->args['id'].")");
            if(!($data && isset($data['account']) && isset($data['password']))){
                $this->_logger->addError("can not find we chat info from db."); 
                return;
            }
            $this->uid = $data['uid'];
            $this->massend_task = $this->get_massend_task();
            if(!$this->massend_task){
                $this->_logger->addError("can not find massend task info from db."); 
                return ;
            }
            $this->setShareData('content',$this->get_send_content($this->massend_task));
        }
        
        public function splitTask(){
            $this->_tasks = $this->get_send_ids($this->massend_task);
            return $this->splitBySize(100);
        }
        
        private function get_massend_task(){
            $task_id = (int)$this->args['id'];
            $task = $this->db->row_select_one('massend_task',"id=$task_id and status=" .TaskDb::STATUS_WAITING );
            if(!$task){
                $this->_logger->addError("task ${task_id} is not found in database!"); 
                return null;
            }
            return $task;
        }
        
        private function get_send_content($task){
            if(empty($task['news_id'])){
                $this->_logger->addError("task ${task_id}'s news id is empty!"); 
                return null;
            }
            /*获取发送正文*/
            $news = $this->db->row_select_one('news',"id=".$task['news_id']);
            $content=null;
            if($news['type']==2){/*文本消息*/
                $content = $news['description'];
            }else{/*图文消息*/
                $this->_logger->addError("news is not supported by mass send");
                return null;
            }
            if(empty($content)){
                $this->_logger->addError("task ${task_id}'s content is empty!"); 
                return null;
            }
            return $content;
        }
        /*数据库操作过多，可以考虑用存储过程优化*/
        private function get_send_ids($task)
        {
            $task_id = $task['id'];
           /*筛选目标用户*/
            $where = "capacity>0 and uid='" . $task['uid']. "' ";
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
                $to_ids = json_decode($task['to_ids'],true);
                foreach($to_ids as &$id){
                    $id="'".$id."'";
                }
                $where .= " and fakeid in (". implode(',',$to_ids) . ")";/*这样处理id为空或其它非法的情况*/
            }
            $this->_logger->addDebug("task {$task_id}'s filter $where"); 
            /*粉丝过多时要循环多次进行处理*/
            $id_rows = $this->db->row_select('members',$where,0,'fakeid');
            /*更新任务信息*/
            $data = array(
                'total_count'=>count($id_rows),
                'status'=> TaskDb::STATUS_RUNNING,/*执行中*/
                'process_time'=>time()
            );
            $this->db->row_update('massend_task',$data,"id={$task_id}");
            $ids = array();
            foreach($id_rows as $memb){
                array_push($ids, $memb['fakeid']);
            }
            return $ids;
        }
        
        protected  function timer($task){
            $data = array(
                'status'=>$task['stat'],
                'sent_count'=>count($task['done']),
                'suc_count'=>count($task['done'])-count($task['fail']),
                'fail_ids'=>json_encode($task['fail'])
            );
            if($this->db){
                $this->db->row_update('massend_task',$data,"id=" . $this->massend_task['id']);
            }
        }
        
        public function finish(){
            $task = Status::getMasterTaskInfo($this->_name);
            //更新所有群发ID
            $this->db->row_update('massend_task',
                    array('to_ids'=>json_encode($task['done'])),
                    "id=" . $this->massend_task['id']);
            //减少用户允许发送的次数
            $suc_ids = array_diff($task['done'],$task['fail']);
            foreach($suc_ids as &$id){
                    $id="'".$id."'";
            }
            $this->db->query_unbuffered("update members set capacity=capacity-1 where uid='".$this->uid."' and  fakeid in (" . implode(',',$suc_ids) . ")");
            if($this->db){
                $this->db->close();
            }
        }
    }