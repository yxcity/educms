<?php
namespace dtask\task\sub;
use \dtask\task\TaskDb;

abstract class Worker{
    protected  $_name;
    private $_tasks;
    private $_sub_tasks=array();
    protected $_logger;
    public $job;
    protected $_master;
    protected $_handle;
    abstract public function each_run($id);
    function __construct($task_name){
        $this->setTaskName($task_name);
    }
    
    public function setTaskName($task_name){
        $this->_name = $task_name;
    }
    
    public  function setLogger($logger){
        $this->_logger = $logger;
    }
    
    public function setHandle($handle){
        $this->_handle = $handle;
    }

    public function  _init(){
        $this->_logger->addInfo("start sub task.",$this->getLoggerContext());
        $this->uid =isset($this->args['uid'])?$this->args['uid']:null;
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
        if($this->checkEnable()){
            $this->initRedis();
            return true;
        }else{
            return false;
        }
        
    }
    
    private function initRedis(){
        $this->updateHeartbeat();
        $sub_task = TaskDb::getWorkerStatus($this->args['job_id']);
        if(isset($sub_task['master'])){
            $this->_master = $sub_task['master'];
        }
        $master_info = TaskDb::getMasterStatus($this->_master);
        if($master_info){
            /*update worker stat in master task info*/
            foreach($master_info['workers'] as &$worker){
                if($worker['job_id'] == $this->args['job_id']){
                    $worker['stat'] = TaskDb::STATUS_RUNNING;
                    break;
                }
            }
            TaskDb::setJson($this->_master,'info',$master_info);
        }
    }
    
    private function getLoggerContext(){
        return array(
            'handle'=>$this->_handle,
            'job_id'=>$this->args['job_id'],
            'class'=>  get_class($this)
                );
    }
    private function checkEnable(){
        $try_cout = 3;
        if(empty($this->_handle)){
            $this->_logger->addError("handle of sub task is empty.",$this->getLoggerContext());
            return false;
        }
        
        while($try_cout--){
            $enable_flag = TaskDb::getKey($this->args['job_id'],'enable');
            if(empty($enable_flag)){
                $this->_logger->addDebug("sub task enable flag is empty.",$this->getLoggerContext());
                sleep(2);
                continue;
            }else if($enable_flag != $this->_handle){
                $this->_logger->addDebug("sub task enable flag {$enable_flag} dismatch.",$this->getLoggerContext());
                return false;
            }
            
            return true;
        }
        return false;
    }
    public function run()
    {
        $this->_logger->addInfo("sub task call run.",$this->getLoggerContext());
        while(true){
            $this->updateHeartbeat();
            if(!$this->checkEnable()){
                $this->_logger->addDebug("sub task checkEnable false",$this->getLoggerContext());
                break;
            }
            if(!($id = TaskDb::spop($this->args['job_id'],'tasks'))){
                $this->_logger->addDebug("sub task spop empty.",$this->getLoggerContext());
                break;
            }
            $this->_logger->addDebug("each_run:" . $id ,$this->getLoggerContext());
            TaskDb::sadd($this->args['job_id'],'done',$id,$this->getLoggerContext());
            if(!$this->each_run($id)){
                TaskDb::sadd($this->args['job_id'],'fail',$id,$this->getLoggerContext());
            }
        }
    }
    
    private function updateHeartbeat(){
        TaskDb::setKey($this->args['job_id'],'heartbeat',  time());
    }
    public function  _finish(){
        $this->_logger->addInfo("finish sub task",$this->getLoggerContext());
        $master_info = TaskDb::getMasterStatus($this->_master);
        if($master_info){
            foreach($master_info['workers']  as &$worker){
                if($worker['job_id'] == $this->args['job_id']){
                    $worker['stat'] = TaskDb::STATUS_COMPLETE;
                    break;
                }
            }
            TaskDb::setJson($this->_master,'info',$master_info);
        }
        TaskDb::sadd($this->_master,'done',TaskDb::smembers($this->args['job_id'],'done'));
        TaskDb::sadd($this->_master,'fail',TaskDb::smembers($this->args['job_id'],'fail'));
        TaskDb::del($this->args['job_id'],'done');
        TaskDb::del($this->args['job_id'],'fail');
        TaskDb::del($this->args['job_id'],'heartbeat');
    }
    
    protected  function getShareData($key){
        $share_data = TaskDb::getJson($this->_master,'sharedata');
        if($share_data && isset($share_data[$key])){
            return $share_data[$key];
        }else{
            return null;
        }
    }
    }
 ?>