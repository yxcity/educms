<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace dtask;
use \dtask\task\utils\Log;
/**
 * Entry of Master job
 *
 * @author hellolwq <hellolwq@gmail.com>
 */
class MasterTask {
    protected $_task;
    protected $_logger;
    private $_init_ok=true;
    private $_worker;
    public function setUp(){
       
        $args = $this->args;
        $clas_name = get_class($this);
        $this->_logger = Log::getLogger('MasterTask');    
        if(isset($this->args['task']) && isset($this->args['task']['master']) && isset($this->args['task']['worker'])){
            $this->_task = $this->args['task'];
        }else{
            $this->_logger->addError('parameter task required.',$this->args);
            $this->_init_ok = false;
            return;
        }
        $this->getWokerInstance();
        //$this->_worker = new ;
    }
    
    private function getWokerInstance(){
        if(!class_exists($this->_task['master'])) {
            $this->_init_ok = false;
            $this->_logger->addError('class ' .$this->_task . ' can not found.');
            return;           
        }
        if(isset($this->args['task']['master_id'])){
            $master_id = $this->args['task']['master_id'];
        }else{
            $master_id = 'dmaster:'.md5(uniqid(rand(),true));
        }
        $this->_worker = new $this->_task['master']($master_id);
        $this->_worker->job = $this;
        $this->_worker->args = $this->args;
        $this->_worker->setLogger($this->_logger);
    }
    
    public function  perform(){
        if(!$this->_init_ok){
            return;
        }
        
        $this->_worker->_init();
        
        if(method_exists($this->_worker, "init")){
            $this->_worker->init();
        }
        $this->_worker->setHandle($this->job->payload['id']);
        $tasks = $this->_worker->splitTask();
        $this->_logger->addDebug("Split Count:" . count($tasks),$tasks);
        if(count($tasks)){
            $this->_worker->runSubTasks($tasks);
        }
        
        $this->_worker->_finish();
        if(method_exists($this->_worker, "finish")){
            $this->_worker->finish();
        }
        
    }
    
    public function tearDown(){
        
    }
    
}

?>
