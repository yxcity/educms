<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\User;
use Admin\Model\Role;
use module\Application\src\Model\Tool;
use module\Application\src\Model\Alipay\Alipay;

class UsersController extends AbstractActionController{

	private $viewData = array();
	private $user;

	function __construct(){
		$this->user = Tool::getSession('auth', 'user');
		$this->viewData['user'] = $this->user;
	}

	/**
	* 一键清除所有Memcache缓存信息 
	* 
	*/
	function clearcacheAction(){
		if($this->user->power == 3){//仅超级管理员yjf才有权限清除缓存
			Tool::clearAllCache();
			header("Content-type:text/html;charset=utf-8");
			echo '<script language="javascript">alert("缓存清除成功！请重新登陆系统");window.location.href="/logout";</script>';
			exit(0);
		}
	}

	/**
	* @todo 账号列表
	* (non-PHPdoc)
	* @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
	*/
	function indexAction(){
		if(!$this->user || $this->user->power < 2)
		$this->redirect()->toUrl('/login');
		$success = Tool::getCookie('success');
		if($success){
			$this->viewData['success'] = json_decode($success);
		}
		$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
		//keywords
		$request = $this->getRequest();
		$keywords = $this->params()->fromQuery('key', null);
		if($request->isPost()){
			$postData = $request->getPost();
			$keywords = Tool::filter($postData['keywords']);
		}
		//
		$rDB = new Role($adapter);
		$roles = $rDB->getAllRole();
		$tmpRole = array();
		if($roles){
			foreach($roles as $val){

				$tmpRole[$val['role_id']] = $val['role_name'];
			}
		}
		unset($roles);
		$this->viewData['role'] = $tmpRole;
		$page = $this->params('page', 1);
		$db = new User($adapter);
		$this->viewData['keywords'] = $keywords;
		$this->viewData['rows'] = $db->userList($page, $this->user, '20', $keywords);
		$this->viewData['power'] = $db->power();
		return $this->viewData;
	}

	/**
	* @todo 编辑账号
	* @return multitype:mixed Ambigous <\Admin\Model\Ambigous, boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>
	*/
	function editAction(){

		$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
		$id = (int) $this->params()->fromQuery('id', $this->user->id);
		$db = new User($adapter);
		$row = $db->getUser($id);
		$ac = $this->params()->fromQuery('ac');
		if($ac){
			$msg = 0;
			$username = $this->params()->fromQuery('username');
			if($username){
				$res = $db->clickUserName($username, $row['id']);
				if($res)
				$msg = 1;
			}
			echo $msg;
			exit();
		}

		$message = Tool::getCookie('message');
		if($message){
			$this->viewData['message'] = json_decode($message);
		}
		// $dbRole = new Role($adapter);

		if(empty($id))
		$this->redirect()->toUrl('/admin');
		$request = $this->getRequest();

		if($row['domain'] != $this->user->domain && $this->user->power < 3)
		$this->redirect()->toRoute('admin');
		if($request->isPost()){
			$postData = $request->getPost();
			$clicking = true;
			$data = array();
			$username = strtolower(Tool::filter($postData['username'], true));
			if(strlen($username) >= 6 || preg_match('/^[a-z\d]*$/i', $username)){
				if($db->clickUserName($username, $row['id'])){
					$clicking = false;
					Tool::setCookie('message', array('title' => '编辑失败', 'message' => '对不起，你输入的帐号已经被使用，请换一个', "alert" => "error"), time() + 10);
				}
			}else{
				$clicking = false;
				Tool::setCookie('message', array('title' => '编辑失败', 'message' => '账号需要不少于6位的字母或数字', "alert" => "error"), time() + 10);
			}
            
			if($row['power'] == 0 && $row['addtime'] == 0){
				$data['power'] = 2;
				$data['domain'] = $username;
				$data['addtime'] = time();
				$configData['token'] = Tool::random(20);
			}
            
			if($this->user->power == 3){
				if($row['power'] <= 0){
					$data['power'] = 2;
					$data['domain'] = $username;
					$data['addtime'] = time();
					$configData['token'] = Tool::random(20);
				}
				$data['attestation'] = (int) $postData['attestation'];
				$data['remark'] = $postData['remark'];
				$data['validity'] = strtotime($postData['validity']);
				$configData['shopCount'] = (int) $postData['shopCount'];
				$configData['commodityCount'] = (int) $postData['commodityCount'];
				$configData['mark'] = $postData['mark'];
				if($row['id'] != $this->user->id && $row['power'] < 3){
					$data['power'] = (int) $postData['power'];
				}
			}
			$password = trim($postData['password']);
			if($password){
				$data['password'] = sha1($password);
			}
			$realname = Tool::filter($postData['realname']);
			if($realname){
				$data['realname'] = $realname;
			}
			$data['email'] = Tool::filter($postData['email']);
			$data['userType'] = $this->_mapUserType($this->user->userType, intval($postData['userType']));
			$configData['tel'] = Tool::filter($postData['tel']);
			//$data['roleid'] = $this->_mapRoleId($this->user->roleid);
			if($username && $this->user->power >= 0){
				$data['username'] = $username;
			}
			if($id != $this->user->id){
				$data['roleid'] = intval($postData['roleid']); //仅在修改其他账户信息时可选择账户角色ID信息	
			}

			if($clicking){
				if($db->editUser($id, $data,$configData)){
					if($row['domain'] == 0 && $row['addtime'] == 0){
						echo '<script language="javascript">
						alert("操作成功！ 如果你修改了帐号和密码，请牢记你输入的帐号和密码，为了安全，现在需要重新登陆系统");
						window.location.href="/logout";
						</script>';
						//$this->redirect()->toUrl("/logout");
					}else{
						Tool::setCookie('message', array('title' => '编辑成功', 'message' => "编辑账号成功", "alert" => "success"), time() + 10);
						$this->redirect()->toUrl("/users/edit?id={$id}");
					}
				}else{
					Tool::setCookie('message', array('title' => '编辑失败', 'message' => '编辑失败，写入数据失败', "alert" => "error"), time() + 10);
				}
			}
		}

		if($row['city']){
			$this->viewData['city'] = $db->areas($row['city']);
		}
		$this->viewData['areas'] = $db->areas();
		$this->viewData['row'] = $row;
		$this->viewData['id'] = $id;
		$this->viewData['roles'] = $this->_mapSubRoles($this->user->roleid);
		//$this->viewData['roles'] = $dbRole->getSelectRoles($this->user->roleid,$this->user->domain);//供选择的角色列表
		$this->viewData['asset'] = array('js' => array('/lib/users.js'));
		return $this->viewData;
	}

	function randuserAction(){
		$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
		$uDB = new User($adapter);
		$res = $this->getRequest();
		if($res->isPost()){
			$postData = $res->getPost();
			$role = $postData['roles'];
			$num = $postData['num'];
			if($postData['passmanage'] != '64027441'){
				echo "error";
				exit;
			}
			for($i = 0; $i < $num; $i++){
				$username = 'shop' . Tool::random(8, true);
				$inData['username'] = $username;
				$inData['domain'] = $username;
				$pwd = uniqid();
				$inData['password'] = sha1($pwd);
				$inData['realname'] = '新用户';
				$inData['sitename'] = '唯购';
				$inData['power'] = 0;
				$inData['roleid'] = $role;
				$inData['userType'] = $postData['userType'];
				$inData['validity'] = strtotime($postData['validity']);
				
				$configData['pwd'] = $pwd;
				$configData['commodityCount'] = $postData['commodityCount'];
				$configData['shopCount'] = $postData['shopCount'];
				$uDB->addUser($inData,$configData);
				$this->redirect()->toUrl('/users/randuser');
			}
		}
		$rDB = new Role($adapter);
		$roles = $rDB->getAllRole();
		$page = $this->params('page', 1);
		$this->viewData['rows'] = $uDB->randList($page);
		$this->viewData['roles'] = $roles;
		$this->viewData['action'] = $this->params('action');
		$this->viewData['bp'] = $this->BackendPlugin();
		return $this->viewData;
	}

	/**
	* 依据当前登录账户角色ID生成子账户的角色ID 
	* @param undefined $thisRoleId
	* 
	*/
	private function _mapRoleId($thisRoleId = 0){
		$data = array(
			1 => 3,
			3 => 5
		);
		if(isset($data[$thisRoleId])){
			return $data[$thisRoleId];
		}
		return 0;
	}

	/**
	* 查找当前登录账户所能添加的子角色类型 
	* @param undefined $thisRoleId
	* 
	*/
	private function _mapSubRoles($thisRoleId = 0){
		$data = array(
			//1=>admin
			1 => array(
				2 => "微商城-尊贵版商户",
				3 => "微商城-高级版商户",
				4 => "销售代理总账号",
				18 => "微站-尊贵版商户",
				19 => "微站-高级版商户",
			),
            //2=>微商城-尊贵版商户
			2 => array(21 => "微商城-尊贵版分店管理员"),
			//3=>微商城-高级版商户
			3 => array(5 => "微商城-高级版分店管理员"),
            //18=>微站-尊贵版商户
			18 => array(22 => "微站-尊贵版分店管理员"),
			//19=>微站-高级版商户
			19 => array(20 => "微站-高级版分店管理员")
		);
		if(isset($data[$thisRoleId])){
			return $data[$thisRoleId];
		}
		return FALSE;
	}

	/**
	* 映射账户类别 
	* @param undefined $thisUserType
	* @param undefined $subUserType
	* 
	*/
	private function _mapUserType($thisUserType, $subUserType = NULL){
		if($thisUserType == 0){//系统账户
			$subUserType = !$subUserType ? $thisUserType : $subUserType;
		}else{
			$subUserType = $thisUserType;
		}
		return $subUserType;
	}

	/**
	* @todo 创建账号
	*/
	function createAction(){

		if(!$this->user || $this->user->power < 2)
		$this->redirect()->toUrl('/admin');
		$massage = Tool::getCookie('massage');
		if($massage){
			$this->viewData['massage'] = json_decode($massage);
		}

		$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
		$db = new User($adapter);
		$dbRole = new Role($adapter);
		$request = $this->getRequest();
		if($request->isPost()){
			$postData = $request->getPost()->toArray();
			$username = trim($postData['username']);
			$clickUser = $db->getUser(null, $username);
			if($clickUser){
				Tool::setCookie('massage', array('title' => '添加失败', 'message' => "用户名已存在", 'alert' => 'error'), time() + 5);
				$this->redirect()->toRoute('users', array('action' => 'create'));
			}
			$data = array();
			if($this->user->power == 3){
				$data['domain'] = $username;
				$data['token'] = Tool::random(20);
				$data['shopCount'] = (int) $postData['shopCount'];
			}
			if($this->user->power == 2){
				$data['domain'] = $this->user->domain;
				$data['uid'] = $this->user->id;
			}
			$data['username'] = Tool::filter($postData['username'], true);
			$data['realname'] = Tool::filter($postData['realname']);
			$data['password'] = sha1($postData['password']);
			$data['email'] = Tool::filter($postData['email']);
			
			$data['validity'] = strtotime($postData['validity']);
			$data['addtime'] = time();
			$data['power'] = $this->user->power == 3 ? 2 : 1;
			
			$data['roleid'] = intval($postData['roleid']);
			$data['userType'] = $this->_mapUserType($this->user->userType, intval($postData['userType']));
			
			$configData['ownerdomain'] = $this->user->domain;
			$configData['tel'] = Tool::filter($postData['tel']);
			$tid = false;
			if(!$clickUser){
				$tid = $db->addUser($data,$configData);
			}
			if($tid){
				Tool::setCookie('massage', array('title' => '添加成功', 'message' => "成功添加账号", 'alert' => 'success'), time() + 5);
				$this->redirect()->toRoute('users');
			}
		}
		$this->viewData['asset'] = array('js' => array('/lib/users.js'));
		$this->viewData['areas'] = $db->areas();
		$this->viewData['roles'] = $this->_mapSubRoles($this->user->roleid);
		//$this->viewData['roles'] = $dbRole->getSelectRoles($this->user->roleid,$this->user->domain);//供选择的角色列表
		return $this->viewData;
	}

	function configAction(){
		if(!isset($this->user->id))
		$this->redirect()->toUrl('/login');
		$success = Tool::getCookie('success');
		if($success){
			$this->viewData['success'] = json_decode($success);
		}
		$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
		$user = new User($adapter);
		$request = $this->getRequest();
		if($request->isPost()){
			$postData = $request->getPost();
			$data = array();
			if($postData['userType'])
			$data['userType'] = Tool::filter($postData['userType']);
			$data['sitename'] = Tool::filter($postData['sitename']);
			$data['realname'] = Tool::filter($postData['realname']);
			$data['email'] = $postData['email'];
			$configData['tel'] = $postData['tel'];
			$configData['city'] = $postData['city'];
			$configData['area'] = $postData['area'];
			$configData['address'] = Tool::filter($postData['address']);
			$configData['wc'] = $postData['wc'];
			//$arr = array('<p>', '</p>');
			$configData['weixinhao'] = Tool::filter($postData['weixinhao']);
			$configData['intro'] = Tool::filter($postData['intro']);
			$configData['welcome'] = Tool::filter($postData['welcome']);
			$configData['nodata'] = Tool::filter($postData['nodata']);
			$configData['payment'] = $postData['payment'];
			$configData['apitype'] = $postData['apitype'];
			$configData['PID'] = $postData['PID'];
			$configData['KEY'] = $postData['KEY'];
			$configData['alipayEmail'] = $postData['alipayEmail'];
			$user->editUser($this->user->id, $data,$configData);
			Tool::setCookie('success', array('title' => '编辑成功', 'message' => "编辑信息成功"), time() + 5);
			$this->redirect()->toUrl('/users/config');
		}

		$row = $user->getUser($this->user->id);
		$row['welcome'] = htmlspecialchars_decode(stripcslashes($row['welcome']));
		if($row['city']){
			$this->viewData['city'] = $user->areas($row['city']);
		}
		$this->viewData['payment'] = Tool::payment();
		$this->viewData['apiType'] = Tool::apiType();
		$this->viewData['areas'] = $user->areas();
		$this->viewData['row'] = $row;
		$dbRole = new Role($adapter);
		$this->viewData['role_name'] = $dbRole->getRole($this->user->roleid)->role_name;
		return $this->viewData;
	}

	/**
	* @todo 删除账号
	*/
	function deleteAction(){
		if(!$this->user)
		$this->redirect()->toUrl('/admin');
		$id = (int) $this->params()->fromQuery('id');
		$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
		$db = new User($adapter);
		$row = $db->getUser($id);
		if($id && ($this->user->power == 3 || $row['domain'] == $this->user->domain)){
			$db->delUser($id);
			echo '{"isok":true}';
		}else{
			echo '{"isok":false}';
		}
		exit();
	}

	/**
	* @todo Ajax 验证域名是否使用
	*/
	function clickAction(){
		$request = $this->getRequest();
		$postData = $request->getPost();
		$domain = strtolower(Tool::filter($postData['domain'], true));
		$db = new User($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
		$row = $db->clickDomain($domain);
		if($row){
			echo '{"isok":true}';
		}else{
			echo '{"isok":false}';
		}
		exit();
	}

	/**
	* @todo 验证用户用户名是否存在
	*/
	function clickuserAction(){
		$request = $this->getRequest();
		if($request->isPost()){
			$data = $request->getPost();
			$username = $data['username'];
			$db = new User($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
			$row = $db->getUser(null, $username);
			$people = array("admin", "administrator", "user", "manage", "guanli", "login", "register"); //系统保留用户名
			if($row || in_array($username, $people)){
				exit('{"isok":"true"}');
			}
			exit('{"isok":"false"}');
		}
		exit('{"isok":"true"}');
	}

	/**
	* @todo 注册账户
	* @return \Zend\View\Model\ViewModel
	*/
	function signupAction(){
		$domain = Tool::domain();
		$d = Tool::getDomain();
		if($domain != 'weixin'){
			$d = Tool::getDomain();
			$this->redirect()->toUrl("http://weixin.{$d}/users/signup");
		}
		$request = $this->getRequest();
		$viewData = array();
		$success = Tool::getCookie('success');
		if($success){
			$viewData['success'] = json_decode($success);
		}
		$error = Tool::getCookie('error');
		if($error){
			$viewData['error'] = json_decode($error);
		}
		if($request->isPost()){
			$db = new User($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
			$postData = $request->getPost();
			$username = Tool::filter($postData['username'], true);
			$row = false;
			if($username){
				$row = $db->getUser(null, $username);
				if($row){
					Tool::setCookie('error', array('title' => '登记失败', 'message' => "登记账号失败,该用户已经存在，请换个再试"), time() + 5);
					$this->redirect()->toUrl("http://weixin.{$d}/users/signup");
				}
			}
			$data['username'] = $username;
			$data['realname'] = Tool::filter($postData['realname'], true);
			$data['email'] = Tool::filter($postData['email'], true);
			$data['password'] = sha1($postData['password']);
			$data['addtime'] = time();
			$data['validity'] = time() + (24 * 3600 * 180);
			$configData['pwd'] = $postData['password'];
			$configData['tel'] = Tool::filter($postData['tel'], true);
			if(!$row){
				$res = $db->addUser($data,$configData);
				if($res){
					Tool::setCookie('success', array('title' => '登记成功', 'message' => "登记账号成功,稍后人工处理"), time() + 5);
					$this->redirect()->toUrl("http://weixin.{$d}/users/regok");
				}
			}
		}
		$view = new ViewModel($viewData);
		$view->setTerminal(200);
		return $view;
	}

	/**
	* @todo 支付测试
	*/
	function alipayAction(){
		$user = new User($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
		$uRow = $user->getUser($this->user->id);
		$testCode = Tool::random(20);
		$quantity = 1;
		$logistics_fee = '0.00';
		$logistics_type = 'EXPRESS';
		$logistics_payment = 'SELLER_PAY';
		$receive_name = '天安门';
		$receive_address = '北京市东城区天安门广场';
		$receive_zip = '100000';
		$receive_phone = '01088888888';
		$receive_mobile = '13800138000';
		$PID = $uRow ['apitype'] == 4 ? '2088701598458641' : $uRow['PID'];
		$KEY = $uRow ['apitype'] == 4 ? '7ort188mko0binqu75ynfng8k11ulltt' : $uRow['KEY'];
		$payData = array(
			'serialnumber' => $testCode,
			'title' => '支付宝测试支付，你能看到这个页面，证明支付宝配置正确，无需实际支付',
			'sum' => 0.01,
			'body' => '支付宝测试支付，你能看到这个页面，证明支付宝配置正确，无需实际支付',
			'PID' => $PID,
			'KEY' => $KEY,
			'alipayEmail' => $uRow['alipayEmail'],
			'notify_url' => BASE_URL . "/alipay/notify",
			'return_url' => BASE_URL . "/index/alipay",
			'show_url' => BASE_URL . "/index/alipay",
			'quantity' => $quantity,
			'logistics_fee' => $logistics_fee,
			'logistics_type' => $logistics_type,
			'logistics_payment' => $logistics_payment,
			'receive_name' => $receive_name,
			'receive_address' => $receive_address,
			'receive_zip' => $receive_zip,
			'receive_phone' => $receive_phone,
			'receive_mobile' => $receive_mobile
		);
		// 标准接口支付
		$alipay = new Alipay($payData);
		if($uRow ['apitype'] == 1){
			$alipay->standard();
		}
		// 担保交易接口
		if($uRow ['apitype'] == 2){
			$alipay->guarantee();
		}
		// 及时到账
		if($uRow ['apitype'] == 3 || $uRow ['apitype'] == 4){
			$alipay->immediately();
		}

		//$alipay->pay($payData);
	}

	function regokAction(){
		$view = new ViewModel();
		$view->setTerminal(200);
		return $view;
	}

}