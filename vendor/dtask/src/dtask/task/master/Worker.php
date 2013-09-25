<?php
namespace dtask\task\master;
use \dtask\task\TaskDb;
use \dtask\task\Status;
abstract class Worker{
    protected  $_name;
    protected $_tasks;
    private $_sub_tasks=array();
    protected $_logger;
    protected $_handle;
    abstract public function splitTask();
    function __construct($task_name){
        $this->setTaskName($task_name);
    }
    
    public function setTaskName($task_name){
        $this->_name = $task_name;
    }
    
    public function setHandle($handle){
        $this->_handle = $handle;
    }
    
    private function getLoggerContext(){
        $ctx = array(
            'name' =>$this->_name,
            'class'=>  get_class($this)
                );
        if(isset($this->args['task']) && isset($this->args['task']['worker'])){
            $ctx['worker'] = $this->args['task']['worker'];
        }
        return $ctx;
    }
    
    public function  _init(){
        $this->_logger->addInfo("start master task.",$this->getLoggerContext());
        $this->uid =isset($this->args['uid'])?$this->args['uid']:null;
        //$uid = $this->uid;
        //$clas_name = get_class($this);
        //$args = $this->args;
        /*$this->_logger->pushProcessor(function ($record) use($uid,$clas_name,$args){
           if(!empty($uid)){
               $record['context']['uid'] = $uid;
           }
            $record['context']['job'] = $clas_name;
           $record['extra']['args'] = $args;
           return $record;
        });
*/
        if(isset($this->args['config'])){
            if(isset($this->args['config']['db'])){
                if(isset($this->args['config']['db']['dsn'])){
                    list($dbname,$host) = sscanf(str_replace(";", ' ', $this->args['config']['db']['dsn']), "mysql:dbname=%s host=%s");
                    $this->args['config']['db']['hostname'] = $host;
                    $this->args['config']['db']['database'] = $dbname;
                }
            }
            $this->config = $this->args['config'];
        }else{
            $config =  require(getcwd() . '/config.php');
            $this->config = $config['config']; 
        }
    }
    
    protected function splitBySize($size){
        if(empty($size)){
            $size = 10;
        }
        $length = count($this->_tasks);
        $this->_logger->addDebug("splitBySize:length=$length,size=$size");
        $count = (int)($length / $size) + ($length % $size?1:0);
        $this->_sub_tasks = array();
        while($count--){
            $sub_task_info = array(
                    "tasks"=>array_slice($this->_tasks, $count*$size, $size),
                    "job_id"=>'dworker:'.md5(uniqid(rand(),true))
                );
            //提前创建好sub task job_id
            array_push($this->_sub_tasks,$sub_task_info);   
        }
        
        return $this->_sub_tasks;
    }
    
    public  function setLogger($logger){
        $this->_logger = $logger;
    }
    /**/
    private function appendSubTasks(){
        $task_count = count($this->_sub_tasks);
        for($i=0;$i<$task_count;$i++){
            $this->_sub_tasks[$i];
            $args =$this->args;
            $args['job_id'] = $this->_sub_tasks[$i]['job_id'];
            $this->_logger->addInfo("append sub task " . $args['job_id'],$this->getLoggerContext());
            $job_id = \Resque::enqueue('SubTasks','dtask\\SubTask',$args, true);
            
            $this->_sub_tasks[$i]['handle'] = $job_id;
            $this->_sub_tasks[$i]['status'] = TaskDb::STATUS_WAITING;
        }
    }
    
    private function timerCallback(){
        if(method_exists($this, "timer")){
            $status = Status::getMasterTaskInfo($this->_name);
            $this->timer($status);
        }
    }
    
    private function monitorSubTasks(){
        //发现有出错的worker，重新插入将该子任务插入队列，处在子任务队列的最面后是否会有影响。
        while(true){
            sleep(5);
            $done_tasks_num = 0;
            foreach($this->_sub_tasks as &$sub_task){
                $task_faild = FALSE;
                $handle = $sub_task['handle'];
                $status = new \Resque_Job_Status($handle);
                $task_stat = $status->get();
                $sub_task['stat'] = $task_stat;
                switch($task_stat){
                    case \Resque_Job_Status::STATUS_WAITING:{
                        /*正常情况下不重新调度任务*/
                        $this->_logger->addDebug("Sub Task ".$sub_task['job_id'] ." is waiting.");
                        continue 2;
                        break;
                    }
                    case \Resque_Job_Status::STATUS_COMPLETE:{
                        /*正常情况下不重新调度任务*/
                        $this->_logger->addDebug("Sub Task ".$sub_task['job_id'] ." is complete.");
                        if(++$done_tasks_num == count($this->_sub_tasks)){
                            $this->_logger->addDebug("monitorSubTasks:all sub tasks finished.");
                            return;
                        }else{
                            continue 2;
                        }
                        break;
                    }
                    case \Resque_Job_Status::STATUS_RUNNING:{
                        $this->_logger->addDebug("Sub Task ".$sub_task['job_id'] ." is running.");
                        break;
                    }
                    case \Resque_Job_Status::STATUS_FAILED:{
                        $this->_logger->addError("Sub Task ".$sub_task['job_id'] ." is failed.");
                        $task_faild = true;
                        break;
                    }
                    default:{
                        $this->_logger->addError("Sub Task ".$sub_task['job_id'] ."'s state is unknown.");
                        $task_faild = true;
                        break;
                    }
                }
                /*check dead or long latency worker*/
                $heart_time = TaskDb::getKey($sub_task['job_id'],'heartbeat');
                $this->_logger->addDebug("Check Sub Task " . $sub_task['job_id'] . " heartbeat:{$heart_time},stat={$task_stat}");
                if($task_faild 
                        || empty($heart_time) 
                        ||((time() - $heart_time) > 20 )){
                    $args = array(
                        'job_id'=>  $sub_task['job_id'],
                        'task'=> $this->args['task']
                        );
                    $this->_logger->addInfo("Recreate sub task:" . $sub_task['job_id'],$this->getLoggerContext());
                    $new_handle = \Resque::enqueue('SubTasks','dtask\\SubTask',$args, true);
                    $sub_task['stat'] = TaskDb::STATUS_WAITING;
                    $sub_task['handle'] =$new_handle;
                    TaskDb::setKey($sub_task['job_id'],'enable',$new_handle);
                    $this->updateSubTask($sub_task);
                }else{
                    TaskDb::setKey($sub_task['job_id'],'enable',$handle);
                    $this->_logger->addDebug("Sub Task:" . $sub_task['job_id'] . " check ok.");
                }
            }
            //定期调用master中更新回调函数。
            $this->timerCallback();
        }
    }
    
    private function updateSubTask($task_obj){
        /*update worker info*/
        $sub_info = TaskDb::getJson($task_obj['job_id'],'info');
        $sub_info['stat'] = $task_obj['stat'];
        $sub_info['master'] = $this->_name;
        TaskDb::setJson($task_obj ['job_id'],'info',$sub_info);
        /*update master info*/
        //$master_info = TaskDb::getJson($this->_name,'info' );
    }
    
    private function writeDb(){
         //写子任务信息
        $sub_tasks = array();
        for($i=0;$i<count($this->_sub_tasks);$i++){
            $stat = isset($this->_sub_tasks[$i]['stat'])?$this->_sub_tasks[$i]['stat']:TaskDb::STATUS_WAITING;
            array_push($sub_tasks, 
                    array(
                        "job_id"=>$this->_sub_tasks[$i]['job_id'],
                        'stat'=>$stat
                    ));
            $sub_info = array(
                'stat'=>$stat,
                'total'=>count($this->_sub_tasks[$i]['tasks']),
                'master'=>$this->_name
            );
            /*BUG 有可能出现子进程已经调度，enable标记还没有写,子进程提前退出的情况*/
            TaskDb::setJson($this->_sub_tasks[$i]['job_id'],'info',$sub_info);
            TaskDb::sadd($this->_sub_tasks[$i]['job_id'],'tasks',  $this->_sub_tasks[$i]['tasks']);
            TaskDb::setKey($this->_sub_tasks[$i]['job_id'],'enable',$this->_sub_tasks[$i]['handle']);
        }
        
        $master_info = array(//不常变的数值，用json保存为一个键
            "stat"=>TaskDb::STATUS_RUNNING,
            "total"=>count($this->_tasks),
            "workers"=>  $sub_tasks,
            "handle"=>  $this->_handle,//TODO 存入供后续的检测master机制
        );
        TaskDb::setJson($this->_name,'info',$master_info);
    }
    
    public function runSubTasks(){
        $this->appendSubTasks();
        $this->writeDb();
        $this->monitorSubTasks();
    }
                    
    public  function _finish(){
        /*redis key，谁创建，谁负责删除*/
       foreach($this->_sub_tasks as $task){
           TaskDb::del($task['job_id'],'enable');
           TaskDb::del($task['job_id'],'tasks');
           TaskDb::del($task['job_id'],'info');
       }
       $master_info = TaskDb::getJson($this->_name,'info');
       $master_info['stat'] = TaskDb::STATUS_COMPLETE;
       TaskDb::setJson($this->_name,'info',$master_info);
       $this->timerCallback();
       $this->_logger->addInfo("finish master task.",$this->getLoggerContext());
    }
    
    /*在master的finish事件之前*/
    /*public  function before_finish(){
       
    }*/
    
    protected  function setShareData($key,$value){
        $share_data = TaskDb::getJson($this->_name,'sharedata');
        if(!$share_data){
            $share_data = array();
        }
        $share_data[$key] = $value;
        return TaskDb::setJson($this->_name,'sharedata',$share_data);
    }
    
    protected  function getShareData($key){
        $share_data = TaskDb::getJson($this->_name,'sharedata');
        if($share_data && isset($share_data[$key])){
            return $share_data[$key];
        }else{
            return null;
        }
    }
}
 ?>