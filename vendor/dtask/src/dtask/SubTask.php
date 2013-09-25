<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace dtask;
use \dtask\task\utils\Log;
/**
 * Entry of Sub job
 *
 * @author hellolwq <hellolwq@gmail.com>
 */
class SubTask {
    protected $_task;
    protected $_logger;
    private $_init_ok=true;
    private $_worker;
    public function setUp(){
        $this->_logger = Log::getLogger('WorkerTask');
        $args = $this->args;
        $clas_name = get_class($this);
        if(isset($this->args['task']) && isset($this->args['task']['worker'])){
            $this->_task = $this->args['task'];
        }else{
            $this->_logger->addError('parameter task required.');
            $this->_init_ok = false;
            return;
        }
        
        $this->getWokerInstance();
    }
    
    private function getWokerInstance(){
        if(!class_exists($this->_task['worker'])) {
            $this->_init_ok = false;
            $this->_logger->addError('class ' .$this->_task['worker'] . ' can not found.');
            return;           
        }
        
        $this->_logger->addDebug("getWokerInstance:" . $this->_task['worker'] . " handle:" . $this->job->payload['id']);
        $this->_worker = new $this->_task['worker']($this->args['job_id']);
        $this->_worker->job = $this;
        $this->_worker->args = $this->args;
        $this->_worker->setLogger($this->_logger);
    }
    
    public function  perform(){
        if(!$this->_init_ok){
            $this->_logger->addDebug("perform:init fail.");
            return;
        }
        $this->_logger->addDebug("Start sub stask " . $this->args['job_id']);
        $this->_worker->setHandle($this->job->payload['id']);
        $this->_worker->_init();
        if(method_exists($this->_worker, "init")){
            $this->_worker->init();
        }
        $this->_worker->run();
        
        if(method_exists($this->_worker, "finish")){
            $this->_worker->finish();
        }
        $this->_worker->_finish();
        $this->_logger->addDebug("Finish sub stask " . $this->args['job_id']);
    }
    
    public function tearDown(){
        
    }
    
}

?>
