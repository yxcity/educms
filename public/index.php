<?php
/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
//ZendDeveloperTools
define('REQUEST_MICROTIME', microtime(true));
defined('BASE_PATH') || define('BASE_PATH', realpath(dirname(__FILE__)));
define('BASE_URL', isset($_SERVER['HTTP_HOST'])?'http://'.$_SERVER['HTTP_HOST']:'http://'.$_SERVER['SERVER_NAME']);
chdir(dirname(__DIR__));
// Setup autoloading
include 'init_autoloader.php';

// Run the application!
Zend\Mvc\Application::init(include 'config/application.config.php')->run()->send();
