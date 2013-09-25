<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Type;


/**
 * @todo 类型管理
 * 
 * @author
 * @version 
 */
class TypeController extends AbstractActionController
{
	private $user;
	private $adapter;
    private static $_CLS_NEWS = 11;//新闻分类
    public function __construct()
	{
		$this->user = Tool::getSession('auth','user');
	}
    
	
	/**
	 * 检查传递过来的类型参数是否有效 
	 * 
	 */
	private function _checkClassExisted()
	{
		$classes = Tool::getClasses();
		$classid = intval($this->params()->fromQuery('classid'));
		if(!in_array($classid,array_keys($classes))){
			echo "ERROR";
			exit(0);
		}
	}
	
	/**
	 * The default action - show the home page
	 */
    public function indexAction()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        
		$this->_checkClassExisted();
		$classid = intval($this->params()->fromQuery('classid'));		
		
		$viewData=array();
        $viewData['user']=$this->user;
        $success = Tool::getCookie('success');
        if ($success)
        {
        	$viewData['success']=json_decode($success);
        }
        $page = $this->params('page',1);
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new Type($adapter);
        
		
		//分页处理数组显示
		$doPager = $this->_doPager($db->getTypeTree($this->user->domain,$classid),$classid);
		if($doPager){
            $deepIdx = $classid == self::$_CLS_NEWS ? -1 : 0;
			$viewData['rows']=Tool::genDisplayTreeList($doPager['show_data'],$deepIdx);
			$viewData['pager_link']=$doPager['pager_link'];	
		}
		
        $viewData['user']=$this->user;
		$viewData['class_label'] = Tool::mapClassLabel($classid);
		$viewData['classid'] = $classid;
		$viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
        return $viewData;
    }
	
	
	
	/**
	 * 分页处理操作列表 
	 * 
	 */
	private function _doPager($data,$classid=0)
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
				$count,$page_num,$this_count,$total_page,$page,$classid
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
	private function _genPagerLink($count,$page_num,$this_count,$total_page,$page,$classid)
	{
		$html = "<div class=\"pagination\">";
		$html.="<ul>";
		$html.="<li><a href=\"javascript:void(0);\">共 $count 组，$page_num 组/页  ,当前页 $this_count 组；共 $total_page 页,当前第 $page 页</a></li>";
		if($page > 1){
			$pre_page = $page - 1;
			$html.="<li><a href=\"/t/?classid=$classid\">首页</a></li>";
			$html.="<li><a href=\"/t/$pre_page/?classid=$classid\">前一页</a></li>";	
		}
		if($page < $total_page){
			$next_page = $page + 1;
			$html.="<li><a href=\"/t/$next_page/?classid=$classid\">下一页</a></li>";
			$html.="<li><a href=\"/t/$total_page/?classid=$classid\">尾页</a></li>";
		}
		$html.="</ul>";
		$html.="</div>";
		return $html;
	}
	
	
    /**
     * @todo 创建分类
     */
    public function createAction()
    {
    	if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
		$this->_checkClassExisted();
		$classid = intval($this->params()->fromQuery('classid'));
		
    	$viewData=array();
    	$viewData['user']=$this->user;
    	$adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
    	$request=$this->getRequest();
    	if ($request->isPost())
    	{
    		$postData = $request->getPost();
    		$data=array();
    		$data['pid']=$postData['pid'];
    		$data['name']=Tool::filter($postData['name'],true);
    		$data['domain']=$this->user->domain;
    		$data['display']=$postData['display'];
			$data['classid']=$classid;
    		$db = new Type($adapter);
    		if ($db->addType($data))
    		{
				//清除缓存
				$key = $this->user->domain."_".$classid."_0";
				Tool::delCache($key);
				
    			Tool::setCookie('success', array('title'=>'添加成功','message'=>'已经成功添加分类'),time()+5);
    		    $this->redirect()->toUrl("/t/index?classid=$classid");
    		}
    	}
    	$t=new Type($adapter);
    	$rows=$t->getTypeTree($this->user->domain,$classid);
		$deepIdx = $classid == self::$_CLS_NEWS ? -1 : 0;
		$viewData['classid'] = $classid;
		$viewData['class_label'] = Tool::mapClassLabel($classid);
		$viewData['select_opts'] = Tool::getTypeTree($rows,"pid","pid",NULL,NULL,$classid,$deepIdx);
    	$viewData['asset']=array('js'=>array('/lib/type.js'));
		$viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
    	return $viewData;
    }
   /**
    * @todo 编辑分类
    * @return multitype:Ambigous <\Admin\Model\Ambigous, boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>
    */
    public function editAction()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
		$viewData=array();
        $viewData['user']=$this->user;
        $id = $this->params()->fromQuery('id');
        if (empty($id)) $this->redirect()->toRoute('type');
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new Type($adapter);
        $request = $this->getRequest();
        if ($request->isPost())
        {
        	$postData=$request->getPost();
        	$data=array();
        	$data['pid']=$postData['pid'];
        	$data['name']=Tool::filter($postData['name'],true);
        	$data['display']=$postData['display'];
			$data['sorting']=intval($postData['sorting']);//增加排序属性
        	if ($db->editType($id, $data))
        	{
				//清除缓存
				$key = $this->user->domain."_".$postData['classid']."_0";
				Tool::delCache($key);
				
        	    $db->editPid($id, array('pid'=>$postData['pid']));
        	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>'已经成功编辑分类'),time()+5);
        	    $this->redirect()->toUrl("/t/index?classid=".$postData['classid']);
        	}
        }
        $row=$db->getType($id);
        if ($row['domain'] != $this->user->domain) $this->redirect()->toRoute('type');
        $viewData['row']=$row;
        $treeData=$db->getTypeTree($this->user->domain,$row->classid);
        $deepIdx = $row['classid'] == self::$_CLS_NEWS ? -1 : 0;
        $viewData['select_opts']=Tool::getTypeTree($treeData,"pid","pid",$row->pid,$id,$row->classid,$deepIdx);
        $viewData['asset']=array('js'=>array('/lib/type.js'));
		$viewData['class_label'] = Tool::mapClassLabel($row->classid);
		$viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
        return $viewData;
    }
    /**
     * @todo 删除分类
     */
    public function deleteAction()
    {
        if (!$this->user) $this->redirect()->toUrl('/login');
        $id = (int)$this->params()->fromQuery('id');
        if ($id)
        {
            $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
            $db = new Type($adapter);
            $row = $db->getType($id);
            if ($row['domain']!=$this->user->domain) $this->redirect()->toRoute('type');
        	if ($this->user->power >= 2)
        	{
        		$db->delete($id);
				$key = $this->user->domain."_".$row['classid']."_0";
				Tool::delCache($key);
        		echo '{"isok":true}';
        		exit();
        	}
        }
        echo '{"isok":false}';
        exit();
    }
	
	
	/**
	 *AJAX异步更新排序序号 
	 * 
	 */
	function ajaxUpdateSortingAction()
	{
		$id = (int)$this->params()->fromQuery('id');
		$sorting = (int)$this->params()->fromQuery('sorting');
		
		$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
    	$role = $db = new Type($adapter);
		if ($data = $db->updateTypeSorting($sorting,$id))
		{
			//清除缓存
			$key = $this->user->domain."_".$data->classid."_0";
			Tool::delCache($key);
			
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
}