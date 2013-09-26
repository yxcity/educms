<?php
namespace Admin;

use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use library\Helper\HCommon;
use library\Helper\HTreeView;
use Admin\Model\Role;

class Module {
	public function init(ModuleManager $moduleManager)
	{
		$sharedEvents=$moduleManager->getEventManager()->getSharedManager();
		$sharedEvents->attach(__NAMESPACE__,'dispatch',function ($e){
			$controller=$e->getTarget();
			//$controller->layout('layout/admin');
		},100);
	}
	
	public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
		$moduleRouteListener = new ModuleRouteListener();
		$moduleRouteListener->attach($eventManager);
		$this->initModule($e);
    }
 
    public function initModule(MvcEvent $e)
    {
        $application   = $e->getApplication();
    	$sm            = $application->getServiceManager();
    	$sharedManager = $application->getEventManager()->getSharedManager();
        
        //---此处设置全局调用的布局文件数据---
        $sharedManager->attach(__NAMESPACE__,'dispatch',function ($e){
            
            $user = HCommon::getSession('auth','user');
            $dbRole = new Role($e->getTarget()->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' ) );
            if($user){
                
                //导航菜单
                $viewModel = $e->getViewModel();
                $_menu = HTreeView::genRoleMenu($dbRole->getAccessTree(0,$user->roleid,$user->domain));
                $viewModel->setVariable('_menu',$_menu); 
                
                //操作说明
                $ctrl = explode("\\",$e->getRouteMatch()->getParam('controller'));
        		$ctrl = strtolower($ctrl[2]);
        		$act = strtolower($e->getRouteMatch()->getParam('action'));
        		$ctrl_act = $ctrl."_".$act;
                $actInfo = $dbRole->getAccessById($ctrl_act);
        		if($actInfo){
                    $viewModel->setVariable('acl_help',$actInfo['acl_help']);
        		}   
            }
            
		},100);
     
        $router = $sm->get('router');
    	$request = $sm->get('request');
     
    	$matchedRoute = $router->match($request);
    	if (null !== $matchedRoute) { 
           $sharedManager->attach('Zend\Mvc\Controller\AbstractActionController','dispatch', 
                function($e) use ($sm) {
           			$sm->get('ControllerPluginManager')->get('BackendPlugin')->doCheck($e);  
           		},2);
        }
    }
	
	public function getAutoloaderConfig() {
		return array (
				'Zend\Loader\ClassMapAutoloader' => array (
						__DIR__ . '/autoload_classmap.php' 
				),
				'Zend\Loader\StandardAutoloader' => array (
						'namespaces' => array (
								__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__ 
						) 
				) 
		);
	}
	public function getServiceConfig() {
		return array (
				'factories' => array (
				) 
		);
	}
	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}
}
