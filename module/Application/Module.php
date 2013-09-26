<?php

namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module
{
    public function onBootstrap($e)
    {
        $session = $e->getApplication()->getServiceManager()->get('session');
        if (isset($session->lang)) {
            $translator = $e->getApplication()->getServiceManager()->get('translator');
            $translator->setLocale($session->lang);

            $viewModel = $e->getViewModel();
            $viewModel->lang = str_replace('_', '-', $session->lang);
        }
        $eventManager = $e->getApplication()->getEventManager();

        /**
        * 禁止 404 Layout
        */
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function($e) {
        	$result = $e->getResult();
        	$result->setTerminal(TRUE);
        
        });
        
        $eventManager->attach('route', function ($e) {
            $lang = $e->getRouteMatch()->getParam('lang');

            // If there is no lang parameter in the route, nothing to do
            if (empty($lang)) {
                return;
            }

            $services = $e->getApplication()->getServiceManager();

            // If the session language is the same, nothing to do
            $session = $services->get('session');
            if (isset($session->lang) && ($session->lang == $lang)) {
                return;
            }

            $viewModel  = $e->getViewModel();
            $translator = $services->get('translator');

            $viewModel->lang = $lang;
            $lang = str_replace('-', '_', $lang);
            $translator->setLocale($lang);
            $session->lang = $lang;
        }, -10);

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        /**
         * Log any Uncaught Errors
        */
        $sharedManager = $e->getApplication()->getEventManager()->getSharedManager();
        $sm = $e->getApplication()->getServiceManager();
        $sharedManager->attach('Zend\Mvc\Application', 'dispatch.error',
        		function($e) use ($sm) {
        			if ($e->getParam('exception')){
        				$sm->get('Logger')->crit($e->getParam('exception'));
        			}
        		}
        );
        
        //---此处设置全局调用的布局文件数据---
        /*$sharedManager->attach(__NAMESPACE__,'dispatch',function ($e){
			$route = $e->getRouteMatch();
            $viewModel = $e->getViewModel();
            $dbUser = new User($e->getTarget()->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' ) );
			$_site = $dbUser->clickDomain (str_replace(strchr($_SERVER['HTTP_HOST'],"."),"",$_SERVER['HTTP_HOST']));
            $viewModel->setVariable('_site',$_site);
		},100);*/
        
        
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

   
    public function getAutoloaderConfig()
    {
    	return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

}
