<?php
namespace dtask\task;
use \dtask\task\TaskDb;
/*
 * 
 * Helper class for query task info
 */

/**
 * Helper class for query task info.
 *
 * @author hellolwq
 */
class Status {
    static public function getMasterTaskInfo($id,$query_sub_tasks=false){
        $master_info = TaskDb::getJson($id,'info');
        if(!$master_info){
            return null;
        }
        $master_info['done'] = TaskDb::smembers($id,'done');
        $master_info['fail'] = TaskDb::smembers($id,'fail');
        if($query_sub_tasks){
            if(is_array($master_info['workers'])){
                foreach($master_info['workers'] as &$worker){
                    $worker['info'] = self::getWorkerTaskInfo($worker['job_id']);
                }
            }
        }
        return $master_info;
    }
    
    static public function getWorkerTaskInfo($id){
        $sub_task_info = TaskDb::getJson($id,'info');
        if(!$sub_task_info){
            return null;
        }
        $sub_task_info['done'] = TaskDb::smembers($id,'done');
        $sub_task_info['fail'] = TaskDb::smembers($id,'fail');
        return $sub_task_info;
    }
}

?>
