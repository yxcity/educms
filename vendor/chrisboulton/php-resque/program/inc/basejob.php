<?php
    use Monolog\Logger;
    use Monolog\Handler\StreamHandler;
    /**
     * 任务基类
     * Author:lwq@etopshine.com
     * Date  :2013.7.29
     */
    abstract class BaseJob
    {
        protected $config;
        protected $uid;
        protected $logger;
        /*function __construct() {
            
        }*/
        abstract public function setUp();
        
        abstract public function perform();
        
        abstract public function tearDown();
        
        protected function  initConfig(){
            $this->uid =isset($this->args['uid'])?$this->args['uid']:null;
            $uid = $this->uid;
            $this->logger = new Logger('AsyncTask');
            $this->logger->pushHandler(new StreamHandler('data/async_task.log', Logger::DEBUG));
            $clas_name = get_class($this);
            $args = $this->args;
            $this->logger->pushProcessor(function ($record) use($uid,$clas_name,$args){
               if(!empty($uid)){
                   $record['context']['uid'] = $uid;
               }
               
               $record['context']['job'] = $clas_name;
               $record['extra']['args'] = $args;
               return $record;
            });
            
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
                global $global_config;
                $this->config = $global_config['config'];
            }
        }
    }