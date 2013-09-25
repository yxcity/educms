<?php
namespace dtask\task\sub;
use dtask\task\sub\Worker;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 *
 */
class TestSub extends Worker{
     public function each_run($id){
        $fail = array(1,5,9,14);
        $die = array(2,4,6,10);
        $sleep_time = rand()%30;
       /*if(in_array($id,$fail)){
            $this->_logger->addDebug("####test sleep {$sleep_time}!");
            sleep($sleep_time);
        }else if(in_array($id,$die)){
            $this->_logger->addDebug('####test die!');
            die;
        }*/
         if($id % 2)
            return true;
        else
            return false;
    }
}
?>
