<?php
use library\Helper\HCommon;
return array (
		'router' => array (
				'routes' => array (
						'home' => array (
								'type' => 'Segment',
								'options' => array (
										'route' => '/[:lang]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Index',
												'action' => 'index' 
										) 
								) 
						),
						// The following is a route to simplify getting started creating
						// new controllers and actions without needing to create a new
						// module. Simply drop new controllers in, and you can access them
						// using the path /application/:controller/:action
						'application' => array (
								'type' => 'Segment',
								'options' => array (
										'route' => '[:lang]/api[/]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}' 
										),
										'defaults' => array (
												'__NAMESPACE__' => 'Application\Controller',
												'controller' => 'Api',
												'action' => 'index' 
										) 
								) 
						),
						'application_index' => array (
								'type' => 'Segment',
								'options' => array (
										'route' => '[:lang]/index[/:action]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Index',
												'action' => 'index' 
										) 
								) 
						),
						
						'about' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/about[/]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Index',
												'action' => 'aboutus' 
										) 
								) 
						),
						
						'contact' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/contact[/]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Index',
												'action' => 'contact' 
										) 
								) 
						),
						
						'zhaopin' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/zhaopin[/]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Index',
												'action' => 'zhaopin' 
										) 
								) 
						),
						
						'hezuo' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/hezuo[/]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Index',
												'action' => 'hezuo' 
										) 
								) 
						),
						'uploadify' => array (
								'type' => 'Segment',
								'options' => array (
										'route' => '[:lang]/uploadify.php',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Index',
												'action' => 'uploadify' 
										) 
								) 
						),
						'application_order' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/order[/:action][/:id]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Order',
												'action' => 'index' 
										) 
								) 
						),
						'application_cart' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/cart[/:action][/:id]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Cart',
												'action' => 'index' 
										) 
								) 
						),
						'application_product' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/product[/:action][/:id]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Product',
												'action' => 'index' 
										) 
								) 
						),
						'snews' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/s/news[/:action][/:id][/]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\News',
												'action' => 'index' 
										) 
								) 
						),
						'shelp' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/s/help[/:action][/:id][/]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Help',
												'action' => 'index' 
										) 
								) 
						),
						'sparty' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/s/party[/:action][/:id][/]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Party',
												'action' => 'index' 
										) 
								) 
						),
						'stores' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/stores[/:action][/:id]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Stores',
												'action' => 'index' 
										) 
								) 
						),
						'application_type' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/type[/:action][/:id]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Type',
												'action' => 'index' 
										) 
								) 
						),
						'user' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/user[/:action][/:page]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\User',
												'action' => 'index' 
										) 
								) 
						),
						'business' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/business[/:action][/:id][/]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Business',
												'action' => 'index' 
										) 
								) 
						),
						'alipay' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/alipay[/:action][/:page]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Alipay',
												'action' => 'index' 
										) 
								) 
						),
                        'client_activity' => array (
								'type' => 'segment',
								'options' => array (
										'route' => '[/:lang]/s/activity[/:action][/:id][/]',
										'constraints' => array (
												'lang' => '[a-z]{2}(-[A-Z]{2}){0,1}',
												'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
												'id' => '[0-9]+' 
										),
										'defaults' => array (
												'controller' => 'Application\Controller\Activity',
												'action' => 'index' 
										) 
								) 
						),
				)
				 
		),
		'service_manager' => array (
				'factories' => array (
						'translator' => 'Zend\I18n\Translator\TranslatorServiceFactory',
						'Logger' => function ($sm) {
							HCommon::mkdir ( './data/log/' );
							$logger = new \Zend\Log\Logger ();
							$writer = new \Zend\Log\Writer\Stream ( './data/log/' . date ( 'Y-m-d' ) . '-error.log' );
							$logger->addWriter ( $writer );
							return $logger;
						} 
				),
				'services' => array (
						'session' => new Zend\Session\Container ( 'zf2tutorial' ) 
				) 
		),
		'translator' => array (
				'locale' => 'en_US',
				'translation_patterns' => array (
						array (
								'type' => 'gettext',
								'base_dir' => __DIR__ . '/../language',
								'pattern' => '%s.mo' 
						) 
				) 
		),
		'controllers' => array (
				'invokables' => array (
						'Application\Controller\Index' => 'Application\Controller\IndexController',
						'Application\Controller\Order' => 'Application\Controller\OrderController',
						'Application\Controller\Product' => 'Application\Controller\ProductController',
						'Application\Controller\Stores' => 'Application\Controller\StoresController',
						'Application\Controller\Type' => 'Application\Controller\TypeController',
						'Application\Controller\User' => 'Application\Controller\UserController',
						'Application\Controller\Api' => 'Application\Controller\ApiController',
						'Application\Controller\Business' => 'Application\Controller\BusinessController',
						'Application\Controller\News' => 'Application\Controller\NewsController',
						'Application\Controller\Help' => 'Application\Controller\HelpController',
						'Application\Controller\Party' => 'Application\Controller\PartyController',
						'Application\Controller\Cart' => 'Application\Controller\CartController',
						'Application\Controller\Alipay' => 'Application\Controller\AlipayController' ,
						'Application\Controller\Activity' => 'Application\Controller\ActivityController' 
				) 
		),
		'view_manager' => array (
				'display_not_found_reason' => true,
				'display_exceptions' => true,
				'doctype' => 'HTML5',
				'not_found_template' => 'error/404',
				'exception_template' => 'error/index',
				'template_map' => array (
						'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
						'layout/admin' => __DIR__ . '/../view/layout/admin.phtml',
						'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
						'error/404' => __DIR__ . '/../view/error/404.phtml',
						'error/index' => __DIR__ . '/../view/error/index.phtml',
						'auth' => __DIR__ . '/../view/application/index/login.phtml',
						'mobileLogin' => __DIR__ . '/../view/application/index/mobileLogin.phtml',
						'register' => __DIR__ . '/../view/application/index/register.phtml',
						'mobileRegister' => __DIR__ . '/../view/application/index/mobileRegister.phtml',
						'error/forbidden' => BASE_PATH . '/../module/Admin/view/layout/forbidden.phtml',
						'error/expired' => BASE_PATH . '/../module/Admin/view/layout/expired.phtml',
						'_microsite' => __DIR__ . '/../view/layout/_microsite.phtml',
						'_aboutus' => __DIR__ . '/../view/layout/_aboutus.phtml',
						'_contact' => __DIR__ . '/../view/layout/_contact.phtml',
						'_zhaopin' => __DIR__ . '/../view/layout/_zhaopin.phtml',
						'_hezuo' => __DIR__ . '/../view/layout/_hezuo.phtml',
						'_artlist' => __DIR__ . '/../view/layout/_artlist.phtml',
                        '_bltlist' => __DIR__ . '/../view/layout/_bltlist.phtml',
						'_artdetail' => __DIR__ . '/../view/layout/_artdetail.phtml',
                        '_bltdetail' => __DIR__ . '/../view/layout/_bltdetail.phtml'  
				),
				'template_path_stack' => array (
						__DIR__ . '/../view' 
				) 
		) 
);
