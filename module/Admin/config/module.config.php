<?php
return array(
	'view_manager'             => array(
		'template_path_stack' => array(
			'Admin'=> __DIR__ . '/../view'
		),
		'template_map'               => array(
			'pagination/paginator'=> __DIR__ . '/../view/pagination/paginator.phtml'
		)
	),

	'controllers'               => array(
		'invokables' => array(
			'Admin\Controller\Home'     => 'Admin\Controller\HomeController',
			'Admin\Controller\Index'    => 'Admin\Controller\IndexController',
			'Admin\Controller\Archives' => 'Admin\Controller\ArchivesController',
			'Admin\Controller\Collect'  => 'Admin\Controller\CollectController',
			'Admin\Controller\Users'    => 'Admin\Controller\UsersController',
			'Admin\Controller\Member'   => 'Admin\Controller\MemberController',
			'Admin\Controller\Commodity'=> 'Admin\Controller\CommodityController',
			'Admin\Controller\Indent'   => 'Admin\Controller\IndentController',
			'Admin\Controller\Shop'     => 'Admin\Controller\ShopController',
			'Admin\Controller\Tenant'   => 'Admin\Controller\TenantController',
			'Admin\Controller\Type'     => 'Admin\Controller\TypeController',
			'Admin\Controller\Keyword'  => 'Admin\Controller\KeywordController',
			'Admin\Controller\Answers'  => 'Admin\Controller\AnswersController',
			'Admin\Controller\Msg'      => 'Admin\Controller\MsgController',
			'Admin\Controller\Autoreply'=> 'Admin\Controller\AutoreplyController',
			'Admin\Controller\News'     => 'Admin\Controller\NewsController',
			'Admin\Controller\Help'     => 'Admin\Controller\HelpController',
			'Admin\Controller\Role'     => 'Admin\Controller\RoleController',
			'Admin\Controller\Access'   => 'Admin\Controller\AccessController',
			'Admin\Controller\Message'  => 'Admin\Controller\MessageController',
			'Admin\Controller\Article'  => 'Admin\Controller\ArticleController',
			'Admin\Controller\Attribute'=> 'Admin\Controller\AttributeController',
			'Admin\Controller\Menu'     => 'Admin\Controller\MenuController',
			'Admin\Controller\Brand'    => 'Admin\Controller\BrandController',
			'Admin\Controller\Party'    => 'Admin\Controller\PartyController',
			'Admin\Controller\Level'    => 'Admin\Controller\LevelController',
			'Admin\Controller\Lottery'  => 'Admin\Controller\LotteryController',
			'Admin\Controller\Market'   => 'Admin\Controller\MarketController',
			'Admin\Controller\Alias'    => 'Admin\Controller\AliasController',
			'Admin\Controller\Ads'      => 'Admin\Controller\AdsController',
			'Admin\Controller\Advert'   => 'Admin\Controller\AdvertController'
		)
	),

	'controller_plugins' => array(
		'invokables' => array(
			'BackendPlugin'=> 'library\Plugin\BackendPlugin',
		)
	),

	'router'                         => array(
		'routes' => array(
			'advert'          => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/advert[/:action][/:page]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Advert',
						'action'    => 'index'
					)
				)
			),
			'ads'             => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/ads[/:action][/:page]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Ads',
						'action'    => 'index'
					)
				)
			),
			'msg'             => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/msg[/:action][/:page]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Msg',
						'action'    => 'index'
					)
				)
			),
			'archives'   => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/archives[/:action][/:page]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Archives',
						'action'    => 'index'
					)
				)
			),
			'admin'         => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/home[/:action][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'id'    => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Home',
						'action'    => 'index'
					)
				)
			),
			'login'         => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/login[/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'id'    => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Index',
						'action'    => 'auth'
					)
				)
			),
			'logout'       => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/logout[/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'id'    => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Index',
						'action'    => 'logout'
					)
				)
			),
			'collect'     => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/collect[/:action][/:page]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Collect',
						'action'    => 'index'
					)
				)
			),
			'users'         => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/users[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Users',
						'action'    => 'index'
					)
				)
			),
			'member'       => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/member[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Member',
						'action'    => 'index'
					)
				)
			),
			'commodity' => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/commodity[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Commodity',
						'action'    => 'index'
					)
				)
			),
			'indent'       => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/indent[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Indent',
						'action'    => 'index'
					)
				)
			),
			'shop'           => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/shop[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Shop',
						'action'    => 'index'
					)
				)
			),
			'tenant'       => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/tenant[/:action][/:page]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Tenant',
						'action'    => 'index'
					)
				)
			),
			'type'           => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/t[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Type',
						'action'    => 'index'
					)
				)
			),
			'help'           => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/help[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Help',
						'action'    => 'index'
					)
				)
			),
			'keyword'     => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/keyword[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Keyword',
						'action'    => 'index'
					)
				)
			),
			'answers'     => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/answers[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Answers',
						'action'    => 'index'
					)
				)
			),
			'autoreply' => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/autoreply[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Autoreply',
						'action'    => 'index'
					)
				)
			),
			'news'           => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/news[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\News',
						'action'    => 'index'
					)
				)
			),
			'role'           => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/role[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Role',
						'action'    => 'index'
					)
				)
			),
			'access'       => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/access[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Access',
						'action'    => 'index'
					)
				)
			),
			'message'     => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/message[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Message',
						'action'    => 'index'
					)
				)
			),
			'article'     => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/article[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Article',
						'action'    => 'index'
					)
				)
			),
			'attribute' => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/attribute[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Attribute',
						'action'    => 'index'
					)
				)
			),
			'menu'           => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/menu[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Menu',
						'action'    => 'index'
					)
				)
			),
			'brand'         => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/brand[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Brand',
						'action'    => 'index'
					)
				)
			),
			'party'         => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/party[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Party',
						'action'    => 'index'
					)
				)
			),
			'market'       => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/market[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Market',
						'action'    => 'index'
					)
				)
			),
			'level'         => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/level[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Level',
						'action'    => 'index'
					)
				)
			),

			'alias'         => array(
				'type'   => 'segment',
				'options' => array(
					'route'      => '[/:lang]/alias[/:action][/:page][/]',
					'constraints' => array(
						'lang'  => '[a-z]{2}(-[A-Z]{2}){0,1}',
						'action'=> '[a-zA-Z][a-zA-Z0-9_-]*',
						'page'  => '[0-9]+'
					),
					'defaults'       => array(
						'controller'=> 'Admin\Controller\Alias',
						'action'    => 'index'
					)
				)
			),
		)
	)
);



