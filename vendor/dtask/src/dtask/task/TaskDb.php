<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace dtask\task;
/**
 * Description of Task
 *
 * @author Administrator
 */
class TaskDb {
    const STATUS_WAITING = 1;
    const STATUS_RUNNING = 2;
    const STATUS_FAILED = 3;
    const STATUS_COMPLETE = 4;
    static public function setJson($id,$key,$obj){
        return \Resque::redis()->set("$id:$key", json_encode($obj));
    }
    
    static public function getJson($id,$key){
        return json_decode(\Resque::redis()->get("$id:$key"),1);
    }
    
    static public function setKey($id,$key,$value){
        return \Resque::redis()->set("$id:$key", $value);
    }
    
    static public function expire($id,$key,$time){
        return \Resque::redis()->expire("$id:$key", $time);
    }
    
    static public function spop($id,$key){
        return \Resque::redis()->spop("$id:$key");
    }
    
    static public function del($id,$key){
        return \Resque::redis()->del("$id:$key");
    }
    
    static public function sadd($id,$key,$value){
        if(is_array($value)){
            array_unshift($value, "$id:$key");
            return call_user_func_array(array(\Resque::redis(),'sadd'),$value);
        }else{
            return \Resque::redis()->sadd("$id:$key",$value);
        }
    }
    
    static public function smembers($id,$key){
        return \Resque::redis()->smembers("$id:$key");
    }
    
    static public function scard($id,$key){
        return \Resque::redis()->scard("$id:$key");
    }
    
    static public function getKey($id,$key){
        return \Resque::redis()->get("$id:$key");
    }
    
    static public function incKey($id,$key){
        return \Resque::redis()->incr("$id:$key");
    }
    
    //根据任务ID查询，总任务信息。
    static public function getMasterStatus($id,$detail=false){
        //读取总信息信息
        $master_info = self::getJson($id,'info');
        if(!$master_info){
            return null;
        }
        
        if($detail){
            $master_info['done'] = self::smembers($id,'done');
            $master_info['fail'] = self::smembers($id,'fail');
        }
        return $master_info;
    }
    
    //根据任务ID查询，总任务信息。
    static public function getWorkerStatus($id){
        $task_info = self::getJson($id,'info');
        if(!$task_info){
            return null;
        }
        $task_info['done'] = self::getKey($id,'done');
        $task_info['fail'] = self::getKey($id,'fail');
        $task_info['tasks'] = self::smembers($id,'tasks');
        return $task_info;
    }
}

?>
