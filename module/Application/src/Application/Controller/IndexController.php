<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use module\Application\src\Model\Tool;
use Admin\Model\Shop;
use Admin\Model\User;
use Admin\Model\Role;
use Admin\Model\Commodity;
use Admin\Model\Type;
use Admin\Model\Article;
use Zend\Log\Logger;
use Zend\Log\Filter\Priority;
use Zend\Db\TableGateway\TableGateway;

class IndexController extends AbstractActionController {
	private $_microSite = 1; // 微站
	private $_microShop = 2; // 微商城
	private $_about = 30; // 关于我们
	private $_contact = 40; // 联系我们
	private $_zhaopin = 50; // 招聘信息
	private $_hezuo = 70; // 合作加盟
	private static $_siteInfo = NULL; // 网站信息
	public function indexAction() {
		$domian = Tool::domain ();
		$s = $this->params ()->fromQuery ( 's' );
		Tool::openid ( $s );
		switch ($domian) {
			case 'login' :
				$view = $this->_login ( $domian );
				break;
			case 'register' :
				$view = $this->_register ( $domian );
				break;
			default :
				$view = $this->_index ( $domian );
				break;
		}
		return $view;
	}
	function _subTree($val) {
		$str = '';
		if ($val ['sub_tree'] && $val ['sub_tree']) {
			foreach ( $val ['sub_tree'] as $val ) {
				$str .= "<a href=\"/product/list?id={$val['id']}\">{$val['name']}</a> &nbsp;";
				$str .= $this->_subTree ( $val );
			}
		}
		return $str;
	}
	
	/**
	 *
	 * @todo 支付测试页
	 */
	function alipayAction() {
		echo '<meta charset="utf-8">';
		$domain = Tool::domain ();
		$action = $this->params ()->fromQuery ( 'action' );
		if ($action == 'ok') {
			$success = $this->params ()->fromQuery ( 'is_success' );
			$emali = $this->params ()->fromQuery ( 'seller_email' );
			$user = new User ( $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' ) );
			$row = $user->clickDomain ( $domain );
			if ($row ['alipayEmail'] == $emali) {
				$user->editUser ( $row ['id'], null, array (
						'alipay' => 1 
				) );
				echo '绑定成功';
				exit ();
			}
		}
		echo '测试支付';
		exit ();
	}
	
	public function uploadifyAction() {
		$request = $this->getRequest ();
		$file = $request->getFiles ()->toArray ();
		if ($file && is_array ( $file )) {
			$thumb = Tool::uploadfile ( $file );
			if ($thumb ['res']) {
				echo $thumb ['file'];
			}
		}
		exit ();
	}
	
	public function _login($domian) {
		$viewData = array ();
		$host = Tool::getDomain ();
		$request = $this->getRequest ();
		if ($request->isPost ()) {
			$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
			$user = new User ( $adapter );
			$role = new Role ( $adapter );
			$message = '用户或密码错误';
			$data = $request->getPost ();
			if (empty ( $data ['username'] )) {
				$message = '请输入用户名';
			}
			if (empty ( $data ['password'] )) {
				$message = '请输入密码';
			}
			if (empty ( $data ['username'] ) && empty ( $data ['password'] )) {
				$message = '请输入用户名和密码';
			}
			if ($data ['username'] && $data ['password']) {
				$res = $user->auth ( array (
						'username' => $data ['username'],
						'password' => sha1 ( $data ['password'] ) 
				) );
				if ($res && $res->power > - 1) {
					
					if ($res->validity && $res->validity < time ()) {
						$view = new ViewModel ( array () );
						$view->setTemplate ( 'error/expired' );
						$view->setTerminal ( 200 );
						return $view;
					}
					
					// 保存账户登录信息
					$this->BackendPlugin ()->setUserSession ( $res );
					$_cookieId = $res->real_domain . $res->id;
					Tool::setSession ( 'acl', array (
							'opts' => $role->getExistedRoleAccess ( $res->roleid ) 
					), NULL, $_cookieId ); // 当前可操作的Controller/action
					
					/*
					 * Tool::setSession ( 'auth', array ( 'user' => $res ) );
					 */
					Tool::setCookie ( 'auth', array (
							'title' => $res->realname,
							'message' => '欢迎登陆系统' 
					), time () + 5 );
					$updata ['addtime'] = time ();
					$updata ['ip'] = Tool::getIP ();
					$updata ['uid'] = $res->id;
					$user->logining ( $updata );
					$this->redirect ()->toUrl ( "http://{$res->real_domain}.{$host}/home" );
				} else {
					$message = '用户名或密码错误！';
				}
			}
			$viewData ['hint'] = array (
					'title' => '登陆失败',
					'message' => $message 
			);
		}
		$viewData ['host'] = $host;
		$view = new ViewModel ( $viewData );
		$view->setTemplate ( 'auth' );
		if (Tool::is_mobile ()) {
			$view->setTemplate ( 'mobileLogin' );
		}
		$view->setTerminal ( 200 );
		return $view;
	}
	function _register($domian) {
		$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
		$uDB = new User ( $adapter );
		$ac = $this->params ()->fromQuery ( 'ac' );
		if ($ac) {
			$msg = 0;
			$email = $this->params ()->fromQuery ( 'email' );
			if ($email) {
				$res = $uDB->getUser ( null, null, $email );
				if ($res)
					$msg = 1;
			}
			$username = $this->params ()->fromQuery ( 'username' );
			if ($username) {
				$res = $uDB->getUser ( null, $username );
				if ($res)
					$msg = 1;
			}
			echo $msg;
			exit ();
		}
		$request = $this->getRequest ();
		$viewData = array ();
		$success = Tool::getCookie ( 'success' );
		if ($success) {
			$viewData ['success'] = json_decode ( $success );
		}
		$error = Tool::getCookie ( 'error' );
		if ($error) {
			$viewData ['error'] = json_decode ( $error );
		}
		if ($request->isPost ()) {
			$postData = $request->getPost ();
			$username = Tool::filter ( $postData ['username'], true );
			$cUserName = $cEmail = false;
			if ($username) {
				$cUserName = $uDB->getUser ( null, $username );
				if ($cUserName || ! $username) {
					Tool::setCookie ( 'error', array (
							'title' => '登记失败',
							'message' => "登记账号失败,该用户已经存在，请换个再试" 
					), time () + 5 );
					$this->redirect ()->toUrl ( "http://register." . Tool::getDomain () );
				}
			}
			$email = Tool::filter ( $postData ['email'], true );
			if ($email) {
				$cEmail = $uDB->getUser ( null, null, $email );
				if ($cEmail) {
					Tool::setCookie ( 'error', array (
							'title' => '登记失败',
							'message' => "登记账号失败,邮箱已被注册，请换个再试" 
					), time () + 5 );
					$this->redirect ()->toUrl ( "http://register." . Tool::getDomain () );
				}
			}
			if (! $cUserName && ! $cEmail && $username) {
				$data ['username'] = $username;
				$data ['domain'] = $username;
				$data ['realname'] = Tool::filter ( $postData ['realname'], true );
				$data ['email'] = $email;
				$data ['password'] = sha1 ( $postData ['password'] );
				$data ['validity'] = time () + (24 * 3600 * 180);
				$configData ['pwd'] = $postData ['password'];
				$configData ['tel'] = Tool::filter ( $postData ['tel'], true );
				// $data ['addtime'] = time ();
				$res = $uDB->addUser ( $data, $configData );
				if ($res) {
					Tool::setCookie ( 'success', array (
							'title' => '登记成功',
							'message' => "登记账号成功,稍后人工处理" 
					), time () + 5 );
					$this->redirect ()->toUrl ( "http://register." . Tool::getDomain () );
				}
			}
		}
		$viewData ['host'] = Tool::getDomain ();
		$view = new ViewModel ( $viewData );
		$view->setTemplate ( 'register' );
		if (Tool::is_mobile ()) {
			$view->setTemplate ( 'mobileRegister' );
		}
		$view->setTemplate ( 'register' );
		$view->setTerminal ( 200 );
		return $view;
	}
	function _index($domian) {
		$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
		$user = new User ( $adapter );
		$dbArt = new Article ( $adapter );
		$dbRole = new Role ( $adapter );
		$viewData = array ();
		$row = $user->clickDomain ( $domian );
		if (! $row)
			$this->redirect ()->toUrl ( '/error' );
		
		if (self::$_siteInfo == NULL) {
			self::$_siteInfo = $row;
		}
		
		// 微站首页
		if ($row ['userType'] == $this->_microSite) {
			$viewData ['_siteInfo'] = self::$_siteInfo;
			$viewData ['_flash'] = $dbArt->getArtImages ( Tool::domain () ); // 首页flash图片
			$viewData = $this->_fillViewData ( $viewData );
			$view = new ViewModel ( $viewData );
			$view->setTemplate ( '_microsite' );
			$view->setTerminal ( 200 );
			return $view;
		}
		
		// 微商城首页
		$co = new Commodity ( $adapter );
		$rows = $co->proList ( 9, 1, $domian, '', '', '', '', true );
		$hrows = $co->proList ( 5, 1, $domian, '', '', '', '3', true );
		$crows = $co->proList ( 9, 1, $domian, '', '', '', '2', true );
		$trows = $co->proList ( 9, 1, $domian, '', '', '', '', true, 'click' );
		
		$db = new Type ( $adapter );
		$tmpRows = $db->getTypeTree ( $domian, 10 );
		$str = '';
		if ($tmpRows) {
			
			foreach ( $tmpRows as $val ) {
				$str .= '<li>';
				if ($val ['pid'] == 0) {
					$str .= "<a class=\"font_b red\" href=\"/product/list?id={$val['id']}\"><b>[{$val['name']}]</b></a>&nbsp;";
					$str .= $this->_subTree ( $val );
				}
				$str .= '<li>';
			}
		}
		unset ( $tmpRows );
		$dbshop = new Shop ( $adapter );
		$shops = $dbshop->shopAll ( $domian );
		
		$view = new ViewModel ( array (
				'rows' => $rows,
				'crows' => $crows,
				'hrows' => $hrows,
				'trows' => $trows,
				'row' => $row,
				'type' => $str,
				'shops' => $shops 
		) );
		$view->setTerminal ( 200 );
		return $view;
	}
	function testAction() {
		exit ();
	}
	
	/**
	 * 公司动态－新闻列表页－微站
	 * function getArtList($domain,$pageSize = 0,$order = NULL,$thisPage = 1,$getCount = TRUE,$subclassid = 0)
	 */
	function artlistAction() {
		$this->_checkIsMicroSite ();
		$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
		$dbArt = new Article ( $adapter );
		$dbRole = new Role ( $adapter );
		
		// $page=$this->params('page',1);
		$page = ( int ) $this->params ()->fromQuery ( 'page' );
		$keyword = Tool::filter ( $this->params ()->fromQuery ( 'keyword' ), TRUE );
		if (empty ( $keyword ) || preg_match ( "/请输入你要搜索的关键词/", $keyword )) {
			$keyword = NULL;
		}
		
		$pageSize = 20;
		$subclassid = ( int ) $this->params ()->fromQuery ( 'sc' );
		$count = $dbArt->getArtList ( Tool::domain (), 0, NULL, 1, TRUE, $subclassid, $keyword );
		$totalPage = ceil ( $count / $pageSize );
		if ($page < 1) {
			$page = 1;
		}
		if ($page > $totalPage) {
			$page = $totalPage;
		}
		$viewData = array (
				'types' => $this->_getAllTypes (),
                'navInfo' => $this->_getNavClassInfo($subclassid),
                'ssbclass' => $this->_getSubClasses ($subclassid) 
		);
		$viewData = $this->_fillViewData ( $viewData );
		if ($count > 0) {
			$data = $dbArt->getArtList ( Tool::domain (), $pageSize, "art_id", $page, FALSE, $subclassid, $keyword );
			$baseUrl = "/index/artlist?sc=" . $subclassid . "&keyword=" . $keyword;
			$viewData ['artlist'] = $data;
			$viewData ['pager'] = $this->_genPagerLink ( $count, $pageSize, $totalPage, $page, $baseUrl );
		}
		
		$viewData ['_siteInfo'] = self::$_siteInfo;
		$view = new ViewModel ( $viewData );
		$view->setTemplate ( "_artlist" );
		$view->setTerminal ( 200 );
		return $view;
	}
	
    /**
     * 各级分类导航信息
     * @param undefined $subclassid
     * @return array()
     * 
     */
    private function _getNavClassInfo($subclassid = 0)
    {
        $navStr = $scLabel = "";
        $adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
		$dbArt = new Article ( $adapter );
        $navClasses = $dbArt->getClassAndParentIds($subclassid);
        if($navClasses){
            foreach($navClasses as $nk=>$nv){
                if($nv['id'] == $subclassid){
                    $scLabel = $nv['name'];
                }
                $navStr.="》<a href='/index/artlist?sc=".$nv['id']."'>".$nv['name']."</a>";
            }
        }
        return array('navStr'=>$navStr,'scLabel'=>$scLabel);
    }
    
	/**
	 * 公司公告列表页－微站
	 * function getArtList($domain,$pageSize = 0,$order = NULL,$thisPage = 1,$getCount = TRUE,$subclassid = 0)
	 */
	function bltlistAction() {
		$this->_checkIsMicroSite ();
		$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
		$dbArt = new Article ( $adapter );
		$dbRole = new Role ( $adapter );
		
		// $page=$this->params('page',1);
		$page = ( int ) $this->params ()->fromQuery ( 'page' );
		$keyword = Tool::filter ( $this->params ()->fromQuery ( 'keyword' ), TRUE );
		if (empty ( $keyword ) || preg_match ( "/请输入你要搜索的关键词/", $keyword )) {
			$keyword = NULL;
		}
		
		$pageSize = 20;
		$count = $dbArt->getBltList ( Tool::domain (), 0, NULL, 1, TRUE, $keyword );
		$totalPage = ceil ( $count / $pageSize );
		if ($page < 1) {
			$page = 1;
		}
		if ($page > $totalPage) {
			$page = $totalPage;
		}
		$viewData = array();
		$viewData = $this->_fillViewData ( $viewData );
		if ($count > 0) {
			$data = $dbArt->getBltList ( Tool::domain (), $pageSize, "art_id", $page, FALSE, $keyword );
			$baseUrl = "/index/bltlist?keyword=" . $keyword;
			$viewData ['bltlist'] = $data;
			$viewData ['pager'] = $this->_genPagerLink ( $count, $pageSize, $totalPage, $page, $baseUrl );
		}
		
		$viewData ['_siteInfo'] = self::$_siteInfo;
		$view = new ViewModel ( $viewData );
		$view->setTemplate ( "_bltlist" );
		$view->setTerminal ( 200 );
		return $view;
	}
	
	/**
	 * 生成分页链接
	 * 
	 * @param undefined $count        	
	 * @param undefined $page_num        	
	 * @param undefined $total_page        	
	 * @param undefined $page        	
	 *
	 */
	private function _genPagerLink($count, $page_num, $total_page, $page, $base_url) {
		$html = "";
		if ($page > 1) {
			$pre_page = $base_url . "&page=" . ($page - 1);
			$html .= "<div class=\"c-p-pre\"><span class=\"c-p-p\"><em></em></span><a href=\"$pre_page\">上一页</a></div>";
		} else {
			$html .= "<div class=\"c-p-pre  c-p-grey  \"><span class=\"c-p-p\"><em></em></span><a>上一页</a></div>";
		}
		$html .= "<div class=\"c-p-cur\"><div class=\"c-p-arrow c-p-down\"><span>{$page}/{$total_page}</span><span></span></div>";
		$html .= "<select class=\"c-p-select\" onchange=\"MM_jumpMenu('parent',this,0)\" id=\"cPage\">";
		for($i = 1; $i <= $total_page; $i ++) {
			$link = $base_url . "&page=" . $i;
			$selected = $i == $page ? "selected=\"selected\"" : "";
			$html .= "<option value=\"$link\" $selected >第{$i}页</option>";
		}
		$html .= "</select></div>";
		if ($page < $total_page) {
			$next_page = $base_url . "&page=" . ($page + 1);
			$html .= "<div class=\"c-p-next  \"><a href=\"$next_page\">下一页</a><span class=\"c-p-p\"><em></em></span></div>";
		} else {
			$html .= "<div class=\"c-p-next c-p-grey\"><a>下一页</a><span class=\"c-p-p\"><em></em></span></div>";
		}
		return $html;
	}
	
	/**
	 * 查找新闻动态所有子分类
	 */
	private function _getSubClasses($subclassid = 60) {
		// 取得新闻备选分类列表(树型结构显示) 11-新闻分类 60-新闻动态总分类
		$subclassid = $subclassid < 1 ? 60 : $subclassid;
		$dbType = new Type ( $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' ) );
		return $dbType->getTypeTree ( Tool::domain (), 11, $subclassid );
	}
	
	/**
	 * 查找所有新闻分类(无层级关系)
	 * 11-新闻分类
	 */
	private function _getAllTypes() {
		$dbType = new Type ( $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' ) );
		$types = $dbType->typeAll ( Tool::domain (), 0, 11 );
		$map = array ();
		if ($types) {
			foreach ( $types as $tk => $tv ) {
				$map [$tv->id] = $tv->name;
			}
		}
		return $map;
	}
	
	/**
	 * 公司动态 - 新闻详细页 - 微站
	 */
	function artdetailAction() {
		$this->_checkIsMicroSite ();
		$art_id = ( int ) $this->params ()->fromQuery ( 'art_id' );
		if (! $art_id) {
			echo 'article not found!';
			exit ();
		}
		$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
		$dbArt = new Article ( $adapter );
		$dbRole = new Role ( $adapter );
		$art = $dbArt->getArtById ( $art_id, $dbArt->_news, Tool::domain () );
		if (! $art) {
			echo 'article not found!';
			exit ();
		}
		
		if ($art ['art_title_url']) {
			$url = $art ['art_title_url'];
			echo "<script language='javascript' type='text/javascript'>";
			echo "window.location.href='$url'";
			echo "</script>";
		}
		
		$viewData = array (
				'art' => $art,
                'navInfo' => $this->_getNavClassInfo($art['subclassid']),
                'ssbclass' => $this->_getSubClasses($art['subclassid'])
		);
		$viewData = $this->_fillViewData ( $viewData );
		$viewData ['_siteInfo'] = self::$_siteInfo;
		$view = new ViewModel ( $viewData );
		$view->setTemplate ( "_artdetail" );
		$view->setTerminal ( 200 );
		return $view;
	}
	
	/**
	 * 公司公告 - 新闻详细页 - 微站
	 */
	function bltdetailAction() {
		$this->_checkIsMicroSite ();
		$art_id = ( int ) $this->params ()->fromQuery ( 'art_id' );
		if (! $art_id) {
			echo 'article not found!';
			exit ();
		}
		$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
		$dbArt = new Article ( $adapter );
		$dbRole = new Role ( $adapter );
		$art = $dbArt->getArtById ( $art_id, $dbArt->_blt, Tool::domain () );
		if (! $art) {
			echo 'article not found!';
			exit ();
		}

		if ($art ['art_title_url']) {
			$url = $art ['art_title_url'];
			echo "<script language='javascript' type='text/javascript'>";
			echo "window.location.href='$url'";
			echo "</script>";
		}
		
		$viewData = array (
				'art' => $art
		);
		$viewData = $this->_fillViewData ( $viewData );
		$viewData ['_siteInfo'] = self::$_siteInfo;
		$view = new ViewModel ( $viewData );
		$view->setTemplate ( "_bltdetail" );
		$view->setTerminal ( 200 );
		return $view;
	}
	
	/* 增加指定文章的阅读读数 */
	function readAction() {
		$art_id = ( int ) $this->params ()->fromQuery ( 'art_id' );
		if (! $art_id) {
			echo '{"code":"error","msg":"id required!"}';
			exit ();
		}
		$art_obj = new Article ( $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' ) );
		/*
		 * $art = $art_obj->getArtById($art_id,$art_obj->_news,Tool::domain()); if(!$art){ echo '{"code":"error","msg":"article not found!"}'; exit; }
		 */
		
		if ($art_obj->incReadCount ( $art_id )) {
			echo '{"code":"ok"}';
		} else {
			echo '{"code":"error","msg":"update read count error!"}';
		}
		exit ();
	}
	
	/**
	 * 微站关于我们
	 */
	function aboutusAction() {
		return $this->_singlePage ( $this->_about );
	}
	
	/**
	 * 微站联系我们
	 */
	function contactAction() {
		return $this->_singlePage ( $this->_contact );
	}
	
	/**
	 * 微站招聘信息
	 */
	function zhaopinAction() {
		return $this->_singlePage ( $this->_zhaopin );
	}
	
	/**
	 * 微站合作加盟
	 */
	function hezuoAction() {
		return $this->_singlePage ( $this->_hezuo );
	}
	
	/**
	 * 单页内容读取通用方法
	 *
	 * @param undefined $page_id        	
	 *
	 */
	private function _singlePage($page_id = 0) {
		$this->_checkIsMicroSite ();
		
		$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
		$dbArt = new Article ( $adapter );
		$dbRole = new Role ( $adapter );
		
		$art = $dbArt->getArtByClassId ( $page_id, Tool::domain () );
		
		$viewData = array (
				'art' => $art 
		);
		$viewData ['_siteInfo'] = self::$_siteInfo;
		$mapping = array (
				$this->_about => "_aboutus",
				$this->_contact => "_contact",
				$this->_zhaopin => "_zhaopin",
				$this->_hezuo => "_hezuo" 
		);
		
		$template = isset ( $mapping [$page_id] ) ? $mapping [$page_id] : $mapping [$this->_about];
		$viewData ['_class_label'] = $dbRole->getAliasLabel ( Tool::domain (), $template, $this->_getSingePageLabel ( $template ) );
		$viewData = $this->_fillViewData ( $viewData );
		$view = new ViewModel ( $viewData );
		$view->setTemplate ( $template );
		$view->setTerminal ( 200 );
		return $view;
	}
	
	/**
	 * 别名相关数据
	 * 
	 * @param undefined $data
	 *        	@Example Icon前端调用示例方法 $this->icons['_aboutus']
	 */
	private function _fillViewData($data = NULL) {
		$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
		$dbRole = new Role ( $adapter );
		
		$ext = array ('subclasses'=>$this->_getSubClasses());
		$icons = array (
				'icons' => $dbRole->getAliasIcons ( Tool::domain () ) 
		);
		$items = array (
				'_zhaopin',
				'_hezuo',
				'_aboutus',
				'_contact',
				'_artlist',
				'_commoditylist',
				'_bulletin' 
		);
		foreach ( $items as $it ) {
			$ext [$it] = $dbRole->getAliasLabel ( Tool::domain (), $it, $this->_getSingePageLabel ( $it ) );
		}
		if (is_array ( $data ) && count ( $data ) > 0) {
			return array_merge ( $data, $ext, $icons );
		}
		return array_merge ( $ext, $icons );
	}
	private function _getSingePageLabel($key) {
		return Tool::mapClassLabel ( Tool::mapAliasIndex ( $key ) );
	}
	
	/**
	 * 检测是否为微站账户
	 */
	private function _checkIsMicroSite() {
		$domian = Tool::domain ();
		$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
		$dbUser = new User ( $adapter );
		$row = $dbUser->clickDomain ( $domian );
		if (! $row || $row ['userType'] != $this->_microSite) {
			$this->redirect ()->toUrl ( '/error' );
			exit ( 0 );
		}
		
		if (self::$_siteInfo == NULL) {
			self::$_siteInfo = $row;
		}
	}
	
	/**
	 * 获取客户端硬件ID
	 */
	public function getClientIdAction() {
		echo ($this->_getPhoneNumber ());
		exit ( 0 );
	}
	
	/**
	 * 函数名称: getPhoneNumber
	 * 函数功能: 取手机号
	 * 输入参数: none
	 * 函数返回值: 成功返回号码，失败返回false
	 * 其它说明: 说明
	 */
	private function _getPhoneNumber() {
		if (isset ( $_SERVER ['HTTP_X_NETWORK_INFO'] )) {
			$str1 = $_SERVER ['HTTP_X_NETWORK_INFO'];
			$getstr1 = preg_replace ( '/(.*,)(11[d])(,.*)/i', "", $str1 );
			Return $getstr1;
		} elseif (isset ( $_SERVER ['HTTP_X_UP_CALLING_LINE_ID'] )) {
			$getstr2 = $_SERVER ['HTTP_X_UP_CALLING_LINE_ID'];
			Return $getstr2;
		} elseif (isset ( $_SERVER ['HTTP_X_UP_SUBNO'] )) {
			$str3 = $_SERVER ['HTTP_X_UP_SUBNO'];
			$getstr3 = preg_replace ( '/(.*)(11[d])(.*)/i', "", $str3 );
			Return $getstr3;
		} elseif (isset ( $_SERVER ['DEVICEID'] )) {
			Return $_SERVER ['DEVICEID'];
		} else {
			Return false;
		}
	}
}
