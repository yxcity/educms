<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use library\Helper\HCommon;
use Admin\Model\User;
use Admin\Model\Role;
use Zend\Db\TableGateway\TableGateway;

class IndexController extends AbstractActionController {
	
	public function indexAction() {	
    
        $domain = HCommon::getSubDomain();
		switch ($domain) {
			case 'login' :
				$view = $this->_login ();
				break;
			case 'register' :
				$view = $this->_register ();
				break;
			default :
				$view = $this->_index ();
				break;
		}
		return $view;
	}
    
	
	
	public function _login() {
        
        HCommon::getDefConfig();
        //exit(0);
        
		$viewData = array ();
		$host = str_replace(HCommon::getSubDomain(),"",HCommon::getHost());
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
					HCommon::setSession ( 'acl', array (
							'opts' => $role->getExistedRoleAccess ( $res->roleid ) 
					), NULL, $_cookieId ); // 当前可操作的Controller/action
					HCommon::setCookie ( 'auth', array (
							'title' => $res->realname,
							'message' => '欢迎登陆系统' 
					), time () + 5 );
					$updata ['addtime'] = time ();
					$updata ['ip'] = HCommon::getIP ();
					$updata ['uid'] = $res->id;
					$user->logining ( $updata );
					$this->redirect ()->toUrl ( "http://{$res->real_domain}{$host}/home" );
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
		$view->setTerminal ( 200 );
		return $view;
	}
    
    
	function _register() {
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
    
	function _index() {
		
	}
}
