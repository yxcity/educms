<?php
namespace Admin\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\Role;
use module\Application\src\Model\Tool;

class AccessController extends AbstractActionController{
	
	
	private $viewData=array();
	
	function __construct(){
		$this->user=Tool::getSession('auth','user');
		$this->viewData['user']=$this->user;
	}
	
	/**
	 * @todo 操作列表
	 * (non-PHPdoc)
	 * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
	 */
	function indexAction()
	{
		
		if (!$this->user || $this->user->power < 2) $this->redirect()->toUrl('/home');
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
		
		//分页处理数组显示
		$doPager = $this->_doPager($db->getAccessTree(0,$this->user->roleid));
		if($doPager){
			$this->viewData['rows']=Tool::genAccessTreeList($doPager['show_data']);
			$this->viewData['pager_link']=$doPager['pager_link'];	
		}
		
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		return $this->viewData;
	}
	
	/**
	 * 分页处理操作列表 
	 * 
	 */
	private function _doPager($data)
	{
		$count = count($data);
		if(!$count){
			return FALSE;
		}
		$page=intval($this->params('page',1));
		$page = $page < 1 ? 1 : $page;
		$page_num = 5;
		$total_page = ceil($count/$page_num);
		$page = $page >= $total_page ? $total_page : $page;
		$start = ($page-1)*$page_num;
		$show_data = array_slice($data,$start,$page_num);
		$this_count = count($show_data);
		return array(
				'show_data'=>$show_data,
				'pager_link'=>$this->_genPagerLink(
				$count,$page_num,$this_count,$total_page,$page
			));
	}
	
	
	/**
	 * 生成数组分页链接
	 * @param undefined $count
	 * @param undefined $page_num
	 * @param undefined $this_count
	 * @param undefined $total_page
	 * @param undefined $page
	 * 
	 */
	private function _genPagerLink($count,$page_num,$this_count,$total_page,$page)
	{
		$html = "<div class=\"pagination\">";
		$html.="<ul>";
		$html.="<li><a href=\"javascript:void(0);\">共 $count 组，$page_num 组/页  ,当前页 $this_count 组；共 $total_page 页,当前第 $page 页</a></li>";
		if($page > 1){
			$pre_page = $page - 1;
			$html.="<li><a href=\"/access/\">首页</a></li>";
			$html.="<li><a href=\"/access/$pre_page/\">前一页</a></li>";	
		}
		if($page < $total_page){
			$next_page = $page + 1;
			$html.="<li><a href=\"/access/$next_page/\">下一页</a></li>";
			$html.="<li><a href=\"/access/$total_page/\">尾页</a></li>";
		}
		$html.="</ul>";
		$html.="</div>";
		return $html;
	}
	
	
	/**
	 * @todo 编辑操作
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
	    $role = $db = new Role($adapter);
	    $acl_id = intval($this->params()->fromQuery('acl_id'));
	    if (empty($acl_id)) $this->redirect()->toUrl('/admin');
		
	    
	    
		//表单提交处理
		$request=$this->getRequest();
	    if ($request->isPost())
	    {
	    	$postData=$request->getPost()->toArray();
	    	$data = array(
				'acl_name'=> Tool::filter($postData['acl_name'],true),
				'acl_icon'=> Tool::filter($postData['acl_icon'],true),
				'acl_url'=>Tool::filter($postData['acl_url'],true),
				'act_key'=>strtolower(Tool::filter($postData['act_key'],true)),
                'alias_key'=> $this->_filterAlias($postData['alias_key']),
				'parent_id'=>intval($postData['parent_id']),
				'is_menu'=>intval($postData['is_menu']),
				'acl_help'=>$postData['acl_help'],
				'acl_sorting'=>intval($postData['acl_sorting']),//增加排序属性
			);
	    	$data['acl_icon'] = $data['acl_icon'] ? $data['acl_icon'] : "#"; 
	    	if($db->editAccess($acl_id, $data))
	    	{
				$db->delAccessCache($this->user->roleid,$db->_menuCache);//更新菜单及操作权限
	    	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>"编辑操作成功"),time()+5);
	    	    $this->redirect()->toUrl("/access/edit?acl_id={$acl_id}");
	    	}else{
				Tool::setCookie('error', array('title'=>'编辑失败','message'=>'编辑失败，未作更新'),time()+5);
				$this->redirect()->toUrl("/access/edit?acl_id={$acl_id}");	
			}
	    	
	    }
		
		//获取要处理的数据内容
		$row=$db->getAccessById($acl_id);
	    if(!$row){
			Tool::setCookie('error', array('title'=>'编辑失败','message'=>'该数据不存在,可能已被删除'),time()+5);
			$this->redirect()->toUrl("/access");
		}
	    $this->viewData['row']=$row;
        $this->viewData['acl_id']=$acl_id;
		$this->viewData['select_opts'] = Tool::getTypeTree($db->getAccessTree(0,$this->user->roleid),"parent_id","parent_id",$row->parent_id,$acl_id);
		$this->viewData['asset']=array('js'=>array('/lib/access.js'));
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
	    return $this->viewData;
	}
	
	
	/**
	 * @todo 创建操作
	 */
	function createAction()
	{
	    $massage = Tool::getCookie('massage');
	    if ($massage)
	    {
	    	$this->viewData['massage']=json_decode($massage);
	    }
	    
	    $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
	    $role = $db = new Role($adapter);
	    $request=$this->getRequest();
		if ($request->isPost())
		{
		    $postData=$request->getPost()->toArray();
			$data = array(
				'acl_name'=> Tool::filter($postData['acl_name'],true),
				'acl_icon'=> Tool::filter($postData['acl_icon'],true),
				'acl_url'=>Tool::filter($postData['acl_url'],true),
				'act_key'=>strtolower(Tool::filter($postData['act_key'],true)),
                'alias_key'=> $this->_filterAlias($postData['alias_key']),
				'parent_id'=>intval($postData['parent_id']),
				'is_menu'=>intval($postData['is_menu']),
				'acl_help'=>$postData['acl_help']
			);
			$data['acl_icon'] = $data['acl_icon'] ? $data['acl_icon'] : "#";
			
		    $dbRst = $db->addAccess($data,$this->user->roleid);
			if(!$dbRst[0]){
				$msg = array('title'=>'添加失败','message'=>$dbRst[1],'alert'=>'error');
				Tool::setCookie('massage', $msg,time()+5);
		    	$this->redirect()->toRoute('access',array('action'=>'create'));
			}else{
				$msg = array('title'=>'添加成功','message'=>$dbRst[1],'alert'=>'success');
				Tool::setCookie('massage', $msg,time()+5);
				$db->delAccessCache($this->user->roleid,$db->_aclCache);
				$this->redirect()->toRoute('access');	
			}
		    
		}
		$this->viewData['select_opts'] = Tool::getTypeTree($db->getAccessTree(0,$this->user->roleid),"parent_id","parent_id");
		$this->viewData['asset']=array('js'=>array('/lib/access.js'));
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		return $this->viewData;
	}
	
	
    /**
     * 过滤菜单操作别名标识 
     * @param undefined $alias_key 仅由英文字母,数字或下划线构成
     * 
     */
    private function _filterAlias($alias_key)
    {
        $alias_key = Tool::filter($alias_key,TRUE);
        if(preg_match("/^[\d\w\_]+$/is",$alias_key)){
            return strtolower($alias_key);
        }
        return NULL;
    }
	
	
	/**
	 * @todo 删除当前操作
	 */
	function deleteAction()
	{
	    $acl_id = (int)$this->params()->fromQuery('acl_id');
		$jsonRst = array(
			'req' => "error",
			'msg' => "当前操作删除失败"
		);
		
		$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
    	$role = $db = new Role($adapter);
		if ($db->delAccess($acl_id))
		{
			//更新菜单及操作权限
			$db->delAccessCache($this->user->roleid,$db->_menuCache);
			$db->delAccessCache($this->user->roleid,$db->_aclCache);
			
			$jsonRst = array(
				'req' => "ok",
				'msg' => "当前操作删除成功"
			);	    
		}	
		
	    echo json_encode($jsonRst);
		exit(0);
	}
	
	
	/**
	 *AJAX异步更新排序序号 
	 * 
	 */
	function ajaxUpdateSortingAction()
	{
		$acl_id = (int)$this->params()->fromQuery('acl_id');
		$acl_sorting = (int)$this->params()->fromQuery('acl_sorting');
		
		$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
    	$role = $db = new Role($adapter);
		if ($db->updateAccessSorting($acl_sorting,$acl_id))
		{
			//更新菜单及操作权限
			$db->delAccessCache($this->user->roleid,$db->_menuCache);
			$db->delAccessCache($this->user->roleid,$db->_aclCache);
			
			$jsonRst = array(
				'req' => "ok",
				'msg' => "排序更新成功"
			);	    
		}else{
			$jsonRst = array(
				'req' => "error",
				'msg' => "排序更新失败"
			);
		}	
		
	    echo json_encode($jsonRst);
		exit(0);
	}
	
	
	/**
	 * 权限分配 
	 * 
	 */
	function rolesAction()
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
	    
		//1.查找当前角色创建的子角色清单
	    $this->viewData['rows']=$db->getSubRoles($this->user->roleid,$this->user->domain);
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
	    return $this->viewData;
	}
	
	
	/**
	 * 给指定的角色进行分配权限 
	 * 
	 */
	function assignAction()
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
	    $role_id = intval($this->params()->fromQuery('role_id'));
	    $role_name = $this->params()->fromQuery('role_name');
	    if (empty($role_id)) $this->redirect()->toUrl('/admin');
		
	    
	    
		//表单提交处理
		$request=$this->getRequest();
	    if ($request->isPost())
	    {
	    	$postData=$request->getPost()->toArray();
	    	$dbRst = $db->assignAcl($postData['acl_id'], $this->user->roleid,$role_id);
			
			$key = $dbRst[0] ? 'success' : 'error';
			$title = $dbRst[0] ? '操作成功' : '操作失败';
			$msg = $dbRst[1];
			Tool::setCookie($key, array('title'=>$title,'message'=>$msg),time()+5);
			$this->redirect()->toUrl("/access/assign?role_id={$role_id}");
	    }
		
		//获取要处理的数据内容
		$rows = $db->getRoleAccessTree($this->user->roleid,$role_id);
		if(!$rows){
			Tool::setCookie('error', array('title'=>'禁止访问','message'=>"您没有权限执行此操作"),time()+5);
			$this->redirect()->toUrl("/access/roles");
		}
        $this->viewData['role_id']=$role_id;
        $this->viewData['role_name']=$role_name;
		$this->viewData['select_opts'] = Tool::genAclCheckTree($rows[0],$rows[1]);
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
	    return $this->viewData;
	}
	
}