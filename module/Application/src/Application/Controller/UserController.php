<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\User;
use module\Application\src\Model\Tool;
use Admin\Model\Indent;
use module\Application\src\Model\Member;
class UserController extends AbstractActionController
{

    private $member;
    
    function __construct(){
    	$this->member = json_decode(Tool::getCookie('member'));
    }
    /**
     *
     * @todo 用户首页
     *       (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
     */
    function indexAction ()
    {
        
        if (!$this->member->id) $this->redirect()->toUrl('/user/login');
    	$viewData['username']=$this->member->username;
        $view = new ViewModel($viewData);
        $view->setTerminal(true);
        return $view;
    }

    /**
     *
     * @todo 地址列表
     * @return \Zend\View\Model\ViewModel
     */
    function addresslistAction ()
    {
        if (!isset($this->member->id)) $this->redirect()->toUrl('/user/login');
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $viewData = array();
        $id = (int) $this->params()->fromQuery('id');
        $viewData['id'] = $id;
        $user = new User($adapter);
        $viewData['rows'] = $user->addressList($this->member->id);
        $view = new ViewModel($viewData);
        $view->setTerminal(true);
        return $view;
    }


    /**
     *
     * @todo 新增地址
     * @return \Zend\View\Model\ViewModel
     */
    function addressAction ()
    {
    	if (!$this->member->id) $this->redirect()->toUrl('/user/login');
    	$request = $this->getRequest();
    	$viewData = array();
    	$ac = $this->params()->fromQuery('ac');
        $viewData['ac']=$ac;
    	if ($request->isPost()) {
    		$postData = $request->getPost();
    		$data['uid'] = $this->member->id;
    		$data['name'] = $postData['name'];
    		$data['phone'] = $postData['phone'];
    		$data['province'] = $postData['province'];
    		$data['city'] = $postData['city'];
    		$data['area'] = $postData['area'];
    		$data['address'] = $postData['address'];
    		$data['zipcode'] = $postData['zipcode'];
    		$default = isset($postData['default']) ? 1 : 0;
    		$data['default'] = $default;
    		$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
    		$db = new User($adapter);
    		if ($default) {
    			$db->defaultAddress(array(
    					'default' => 0
    			), $this->member->id);
    		}
    		$db->addaddress($data);
    		if ($ac==='indent') {
    			$this->redirect()->toUrl("/product/indent");
    		}else 
    		{
    			$this->redirect()->toUrl("/user/addresslist");
    		}
    	}
    	$view = new ViewModel($viewData);
    	$view->setTerminal(true);
    	return $view;
    }
    
    /**
     *
     * @todo 用户错误提示
     * @return \Zend\View\Model\ViewModel
     */
    function errorAction ()
    {
    	$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
    	$id = $this->params()->fromQuery('msgid');
    	$str = Tool::errorCode($id,$adapter);
    	$view = new ViewModel(array('msg'=>$str));
        $view->setTerminal(true);
        return $view;
    }

    /**
     *
     * @todo 联系我们
     * @return \Zend\View\Model\ViewModel
     */
    function contactAction ()
    {
        $view = new ViewModel();
        $view->setTerminal(true);
        return $view;
    }

    /**
     *
     * @todo 关于我们
     * @return \Zend\View\Model\ViewModel
     */
    function aboutAction ()
    {
        $view = new ViewModel();
        $view->setTerminal(true);
        return $view;
    }

    function indentAction ()
    {
        if (!isset($this->member->id)) $this->redirect()->toUrl('/user/login');
    	$domain = Tool::domain();
        $viewData = array();
        $db = new Indent($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $viewData['status'] = $db->indentStatus();
        $s = $this->params()->fromQuery('s', null);
        $viewData['s'] = $s;
        if ($s == 1) {
            $s = "'1','4'";
        }
        $rows = $db->userIndent($domain, $this->member->id, $s);
        $tmpRows=array();
        if ($rows)
        {
            foreach ($rows as $val) {
                $tmpRows[$val['serialnumber']][]=$val;
            }
        }
        unset($rows);
        $viewData['rows'] = $tmpRows;
        $viewData['count'] = count($tmpRows);
        $view = new ViewModel($viewData);
        $view->setTerminal(true);
        return $view;
    }

    /**
     *
     * @todo 订单详情
     * @return \Zend\View\Model\ViewModel
     */
    function waybillAction ()
    {
        if (!isset($this->member->id)) $this->redirect()->toUrl('/user/login');
        $domain = Tool::domain();
        $id = $this->params()->fromQuery('id');
        if (!$id) $this->redirect()->toUrl('/user/error?msgid=1006');
        $viewData = array();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new Indent($adapter);
        $rows = $db->getIndent($id);
        $tmp=array();
        if ($rows)
        {
            $address = '';
            foreach ($rows as $key => $val) {
                if ($val['uid'] != $domain || $val['buyer']!=$this->member->id) $this->redirect()->toUrl ('/user/indent');
                $tmp[$key]=$val;
                $address = $val['address'];
            }
            if ($address)
            {
                $user = new User($adapter);
                $viewData['address'] = $user->getAddress($address);
            }
        }
        unset($rows);
        $viewData['rows'] = $tmp;
        $viewData['status'] = $db->indentStatus();
        $viewData['express'] = $db->express();
        $view = new ViewModel($viewData);
        $view->setTerminal(true);
        return $view;
    }

    /**
     * @支付宝 支付回调
     */
    function alipayAction ()
    {
        $serialnumber = $this->params('serialnumber');
        if (! $serialnumber)
            exit();
        $db = new Indent($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $db->update(array(
            'status' => 1,
            'payTime' => time()
        ), null, $serialnumber);
        $this->redirect()->toRoute('user', array(
            'action' => 'indent'
        ));
        // exit();
    }

    function registerAction()
    {
    	$adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
    	$db = new Member($adapter);
    	
    	$ac = $this->params()->fromQuery('ac');
    	if ($ac=='checking')
    	{
    		$msg = 0;
    		$username = $this->params()->fromQuery('username');
    		$res=$db->getMember(null,$username);
    		if($res) $msg=1;
    		echo $msg;
    		exit();
    	}
    	
    	if (isset($this->member->id)) $this->redirect()->toRoute('user');
    	$res = $this->getRequest();
    	if ($res->isPost())
    	{
    		$data =  $res->getPost();
    		$inData=array();
    		$openid = Tool::getCookie('openid');
    		if ($openid)
    		{
    			$inData['openid']=$openid;
    		}
    		$username = Tool::filter($data['username'],true);
    		if (!$username)
    		{
    			echo '<script type="text/javascript">
    					alert(\'请填写用户名\');
    					window.location.href="/user/register";
    					</script>';
    		}
    		//$realname = Tool::filter($data['realname']);
    		$email = Tool::filter($data['email']);
    		$inData['username']=$username;
    		$inData['email']=$email;
    		$inData['password']=sha1($data['password']);
    		$res = $db->getMember(null,$username);
    		if (!$res)
    		{
    			$id = $db->addMember($inData);
    			if ($id)
    			{
    				Tool::setCookie('member', array('id'=>$id,'username'=>$username,'openid'=>$openid),time()+(3600*24*365));
    				echo '<script type="text/javascript">
    					alert(\'注册成功\');
    					window.location.href="/user";
    					</script>';
    				//$this->redirect()->toRoute('user');
    			}
    		}else 
    		{
    			echo '<script type="text/javascript">
    					alert(\'注册失败，用户名已经被注册\');
    					window.location.href="/user/register";
    					</script>';
    		}
    		
    	}
    	$view = new ViewModel();
    	$view->setTerminal(200);
    	return $view;
    }
    
    function loginAction()
    {
    	if (isset($this->member->id)) $this->redirect()->toRoute('user');
    	if (!isset($this->member->id))
    	{    	
    	    $refer = Tool::getCookie("_refer");
    	    if($refer){
    	        if(isset($_SERVER['HTTP_REFERER'])){
    		      $url = $_SERVER['HTTP_REFERER'];
    		      Tool::setCookie("_refer",$url,time()+3600);
    	        }
    	    }
    	}else{
    	    $this->redirect()->toRoute('user');
    	}
    	$res = $this->getRequest();
    	if ($res->isPost())
    	{
    		$data=$res->getPost();
    		$p=array();
    		$p['username'] = $data['username'];
    		$p['password'] = sha1($data['password']);
    		$adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
    		$db = new Member($adapter);
    		$mData = $db ->auth($p);
    		if ($mData)
    		{
    			$openid = Tool::getCookie('openid');
    			if ($openid && !$mData->openid)
    			{
    				$upData['openid']=$openid;
    				$db->editMember($mData->id, $upData);
    				$mData->openid=$openid;
    			}
    			Tool::setCookie('member', (array)$mData,time()+3600*24*365);
				
				$refer = Tool::getCookie("_refer");
				Tool::setCookie("_refer",$refer,time()-100);
				
				if($refer){
					$this->redirect()->toUrl($refer);
				}else{
					$this->redirect()->toRoute('user');	
				}
    		}
    		echo '<script type="text/javascript">alert(\'登陆失败，用户名或密码错误\');</script>';
    	}
    	
    	$view = new ViewModel();
    	$view->setTerminal(200);
    	return $view;
    }
}