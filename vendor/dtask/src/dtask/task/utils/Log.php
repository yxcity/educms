<?php
namespace dtask\task\utils;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Logger factory
 *
 * @author Administrator
 */
class Log {
    private static $loggers = array();
    static public function getLogger($name){
        $log_lvl =  Logger::INFO;
        if(getenv('DEBUG')){
            $log_lvl = Logger::DEBUG;
        }
        if(isset(self::$loggers[$name])){
            return self::$loggers[$name]['instance'];
        }
        $instance  = new Logger($name);
        $logging = getenv('LOGGING');
        $verbose = getenv('VERBOSE');
        $vverbose = getenv('VVERBOSE');
        if(empty($logging) && empty($verbose) && empty($vverbose)) {
            $instance->pushHandler(new StreamHandler("log/{$name}.log", $log_lvl));
        }else{
            $instance->pushHandler(new StreamHandler(STDOUT, $log_lvl));
        }
        self::$loggers[$name]['instance'] = $instance;
        self::$loggers[$name]['level'] = $log_lvl;
        return $instance;
    }
}

?>
