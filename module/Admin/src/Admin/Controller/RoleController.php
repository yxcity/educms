<?php
namespace Admin\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\Role;
use module\Application\src\Model\Tool;

class RoleController extends AbstractActionController{
	private $viewData=array();
	
	function __construct(){
		$this->user=Tool::getSession('auth','user');
		$this->viewData['user']=$this->user;
	}
	
	/**
	 * @todo 角色列表
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
        $error=Tool::getCookie('error');
	    if ($error)
	    {
	    	$this->viewData['error']=json_decode($error);
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
		$db=new Role($adapter);
        $this->viewData['keywords']=$keywords;
		$this->viewData['rows']=$db->roleList($page,$this->user->roleid,20,$keywords,$this->user->domain);
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		$this->viewData['user'] = $this->user;
		return $this->viewData;
	}
	
	
	
	/**
	 * @todo 编辑角色
	 * @return multitype:mixed Ambigous <\Admin\Model\Ambigous, boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>
	 */
	function editAction()
	{
		//设置提示信息及获取请求参数
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
	    $db=new Role($adapter);
	    $role_id = (int)$this->params()->fromQuery('role_id');
	    if (empty($role_id)) $this->redirect()->toUrl('/admin');
		
	    
	    
		//表单提交处理
		$request=$this->getRequest();
	    if ($request->isPost())
	    {
	    	$postData=$request->getPost();
	    	$data=array();
	    	
	    	
	    	$role_name=Tool::filter($postData['role_name']);
			$data['role_name'] = $role_name;
	    	
	    	if($db->editRole($role_id, $data))
	    	{
	    	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>"编辑角色成功"),time()+5);
	    	    $this->redirect()->toUrl("/role/edit?role_id={$role_id}");
	    	}else{
				Tool::setCookie('error', array('title'=>'编辑失败','message'=>'编辑失败，未作更新'),time()+5);
				$this->redirect()->toUrl("/role/edit?role_id={$role_id}");
			}
	    }
		
		//获取要处理的数据内容
		$row=$db->getRole($role_id,$this->user->roleid,$this->user->domain);
	    if(!$row){
			Tool::setCookie('error', array('title'=>'编辑失败','message'=>'你无权编辑该角色信息'),time()+5);
			$this->redirect()->toUrl("/role");
		}
	    $this->viewData['row']=$row;
        $this->viewData['role_id']=$role_id;
		$this->viewData['asset']=array('js'=>array('/lib/role.js'));
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
	    return $this->viewData;
	}
	
	
	/**
	 * @todo 创建角色
	 */
	function createAction()
	{
	    $massage = Tool::getCookie('massage');
	    if ($massage)
	    {
	    	$this->viewData['massage']=json_decode($massage);
	    }
	    
	    $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
	    $db=new Role($adapter);
	    $request=$this->getRequest();
		if ($request->isPost())
		{
		    $postData=$request->getPost()->toArray();
		    $role_name=Tool::filter($postData['role_name'],true);
			$flag = $db->checkRoleExisted($role_name,$this->user->roleid,$this->user->domain);
		    if ($flag)
		    {
		    	Tool::setCookie('massage', array('title'=>'添加失败','message'=>"该角色已存在",'alert'=>'error'),time()+5);
		    	$this->redirect()->toRoute('role',array('action'=>'create'));
		    }else{
				$data=array(
					'role_name' => $role_name,
					'created_time'=> time(),
					'owner_id' => $this->user->roleid,
					'owner_domain' => $this->user->domain
				);
				$tid=$db->addRole($data);
				if($tid)
				{
				    Tool::setCookie('massage', array('title'=>'添加成功','message'=>"成功添加新角色",'alert'=>'success'),time()+5);
				    $this->redirect()->toRoute('role');
				}
			}
		}
		$this->viewData['asset']=array('js'=>array('/lib/role.js'));
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		return $this->viewData;
	}
	
	
	
	
	/**
	 * @todo 删除角色
	 */
	function deleteAction()
	{
	    $role_id = (int)$this->params()->fromQuery('role_id');
		$jsonRst = array(
			'req' => "error",
			'msg' => "角色删除失败"
		);
		
		if($role_id == $this->user->roleid){
			$jsonRst = array(
				'req' => "error",
				'msg' => "不能删除自身所属角色"
			);
		}else{
			$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
	    	$db = new Role($adapter);
			if ($db->delRole($role_id,$this->user->roleid,$this->user->domain))
			{
				$jsonRst = array(
					'req' => "ok",
					'msg' => "角色删除成功"
				);	    
			}	
		}
		
	    echo json_encode($jsonRst);
		exit(0);
	}
	
}