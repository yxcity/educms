<?php
/**
 * 常用功能函数
 */

/*define("LOG_DEBUG",1);
define("LOG_INFO",2);
define("LOG_WARN",3);
define("LOG_ERROR",4);
*/

date_default_timezone_set('Asia/Shanghai');
function logger($msg,$mod='',$level=LOG_INFO){
    global $g_debug;
    if(!is_string($msg)){
        $msg = print_r($msg);
    }
    
    if($level == LOG_DEBUG){
        if($g_debug ){
            $traces = debug_backtrace();
            fwrite(STDOUT,date('Y-m-d H:i:s') ." ". $msg . "[debug ".$traces[1]['function'].":". $traces[1]['line'] . "]\n");
        }
    }
    else{
        //fwrite(STDOUT,date('Y-m-d H:i:s')." ". $msg . "\n");
        fwrite(STDOUT," ". $msg . "\n");
    }
}