<?php
namespace dtask\task\master;
use dtask\task\master\Worker;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of test
 *
 * @author Administrator
 */
class TestMaster extends Worker {
    //put your code here
    public function splitTask(){
        $this->_tasks = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15);
        //shuffle($this->_tasks);
        $this->_logger->addDebug("Master:".$this->_name);
        return $this->splitBySize(3);
    }
    
}

?>
