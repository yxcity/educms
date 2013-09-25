<?php

namespace Admin;

use Zend\Db\ResultSet\ResultSet;
use Admin\Form\ArchivesVerify;
use Zend\Db\TableGateway\TableGateway;
use Admin\Model\Archives;
use Zend\ModuleManager\ModuleManager;
use Admin\Model\Collect;
use Admin\Form\CollectVerify;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Admin\Model\Menu;

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
						'ArchivesTableGateway' => function ($sm) {
							$dbAdapter = $sm->get ( 'Zend\Db\Adapter\Adapter' );
							$resultSet = new ResultSet ();
							$resultSet->setArrayObjectPrototype ( new ArchivesVerify () );
							return new TableGateway ( 'archives', $dbAdapter );
						},
						'Admin\Model\Archives' => function ($sm) {
							$tableGateway = $sm->get ( 'ArchivesTableGateway' );
							$table = new Archives ( $tableGateway );
							return $table;
						},
						'CollectTableGateway'=>function ($sm)
						{
							$dbAdapter=$sm->get('Zend\Db\Adapter\Adapter');
							$resultSet = new ResultSet();
							$resultSet->setArrayObjectPrototype(new CollectVerify());
							return new TableGateway('collect',$dbAdapter);
						},
						'Admin\Model\Collect'=>function ($sm)
						{
							$tableGateway = $sm->get('CollectTableGateway');
							$table = new Collect($tableGateway);
							return $table;
						},
						'MenuTableGateway'=>function ($sm)
						{
							$dbAdapter=$sm->get('Zend\Db\Adapter\Adapter');
							return new TableGateway('menu',$dbAdapter);
						},
						'Admin\Model\Menu'=>function ($sm)
						{
							$tableGateway = $sm->get('MenuTableGateway');
                                                        $dbAdapter=$sm->get('Zend\Db\Adapter\Adapter');
							$table = new Menu($tableGateway,$dbAdapter);
							return $table;
						}
				) 
		);
	}
	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}
}
