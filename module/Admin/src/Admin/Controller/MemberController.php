<?php
namespace Admin\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\User;
use Admin\Model\Member;
use Admin\Model\Role;
use module\Application\src\Model\Tool;
use module\Application\src\Model\Alipay\Alipay;

class MemberController extends AbstractActionController{
	private $viewData=array();
	private $user;
	function __construct(){
		$this->user=Tool::getSession('auth','user');
		$this->domain=Tool::domain();
		$this->viewData['user']=$this->user;
	}
	/**
	 * @todo 账号列表
	 * (non-PHPdoc)
	 * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
	 */
	function indexAction()
	{
	    if (!$this->user || $this->user->power < 2) $this->redirect()->toUrl('/login');
		$success = Tool::getCookie('success');
		if ($success)
		{
		    $this->viewData['success']=json_decode($success);
		}
        
        //keywords
        $request = $this->getRequest();
        $keywords = $this->params()->fromQuery('key',null);
        if ($request->isPost())
        {
    	    $postData = $request->getPost();
    		$keywords = Tool::filter($postData['keywords']);
        }
        //
        		
		$page=$this->params('page',1);
		$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
		$db=new Member($adapter);
        $this->viewData['keywords']=$keywords;
		$this->viewData['rows']=$db->memberList($page,$this->user,$this->domain,'20',$keywords);
		return $this->viewData;
	}
	/**
	 * @todo 编辑账号
	 * @return multitype:mixed Ambigous <\Admin\Model\Ambigous, boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>
	 */
	function editAction()
	{
	    if (!$this->user) $this->redirect()->toUrl('/login');
		$success = Tool::getCookie('success');
	    if ($success)
	    {
	    	$this->viewData['success']=json_decode($success);
	    }
	    $error=Tool::getCookie('error');
	    if ($error)
	    {
	    	$this->viewData['error']=json_decode($error);
	    }
	    $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
	    $db=new User($adapter);
		$dbRole = new Role($adapter);
	    $id = (int)$this->params()->fromQuery('id',$this->user->id);
	    if (empty($id)) $this->redirect()->toUrl('/admin');
	    $request=$this->getRequest();
	    $row=$db->getUser($id);
	    if ($row['domain'] != $this->user->domain && $this->user->power<3) $this->redirect()->toRoute('admin');
	    if ($request->isPost())
	    {
	    	$postData=$request->getPost();
	    	$data=array();
	    	if ($this->user->power==3)
	    	{
	    	    if(!$row['domain'])
	    	    {
	    	    	$data['domain'] = $postData['username'];
	    	    	$configData['token'] = Tool::random(20);
	    	    }
	    	    $configData['shopCount']=(int)$postData['shopCount'];
	    	    $configData['mark']=$postData['mark'];
	    	    $data['remark']=$postData['remark'];
	    	    $data['validity']=strtotime($postData['validity']);
	    	    if ($row['id'] != $this->user->id && $row['power']<3){
	    	    	$data['power']=(int)$postData['power'];
	    	    }
	    	    
	    	}
	    	$password = trim($postData['password']);
	    	if ($password)
	    	{
	    	    $data['password']=sha1($password);
	    	}
	    	
	    	$realname=Tool::filter($postData['realname']);
	    	if ($realname)
	    	{
	    	    $data['realname']=$realname;
	    	}
	    	$data['email']=Tool::filter($postData['email']);
	    	$configData['tel']=Tool::filter($postData['tel']);
			//$data['roleid'] = intval($postData['roleid']);//账户角色ID
	    	$data['roleid'] = $this->_mapRoleId($this->user->roleid);
			
	    	if ($this->user->power > 1)
	    	{
	    		$username = Tool::filter($postData['username'],true);
	    		if ($username )
	    		{
	    			$data['username']=$username;
	    		}
	    	}
	    	if($db->editUser($id, $data,$configData))
	    	{
	    	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>"编辑账号成功"),time()+5);
	    	    $this->redirect()->toUrl("/users/edit?id={$id}");
	    	}
	    	Tool::setCookie('error', array('title'=>'编辑失败','message'=>'编辑失败，写入数据失败'),time()+5);
	    }
	    
	    if ($row['city'])
	    {
	    	$this->viewData['city']=$db->areas($row['city']);
	    }
	    $this->viewData['areas']=$db->areas();
	    $this->viewData['row']=$row;
        $this->viewData['id']=$id;
		$this->viewData['roles'] = $dbRole->getSelectRoles($this->user->roleid,$this->user->domain);//供选择的角色列表
		$this->viewData['asset']=array('js'=>array('/lib/users.js'));
	    return $this->viewData;
	}
	
	/**
	 * @todo 删除账号
	 */
	function deleteAction()
	{
		if (!$this->user) $this->redirect()->toUrl('/admin');
		$id = (int)$this->params()->fromQuery('id');
	    $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
	    $db = new User($adapter);
	    $row = $db->getUser($id);
		if ($id && ($this->user->power==3 || $row['domain']==$this->user->domain))
		{
		    $db->delUser($id);
		    echo '{"isok":true}';
		}else 
		{
		    echo '{"isok":false}';
		}
		exit();
	}
}