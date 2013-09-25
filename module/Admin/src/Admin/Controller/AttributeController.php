<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Attribute;


/**
 * @todo 商品属性管理
 * 
 * @author
 * @version 
 */
class AttributeController extends AbstractActionController
{
	private $user;
	private $adapter;
	private $attrDb = NULL;
	private $prodClassInfo = NULL;
	
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
		$prodClassId = $this->_getProdClassId();
		$flag = $this->_getAttrDb()->checkProdClassExisted($prodClassId,$this->user->domain);
		if(!$flag){
			echo "ERROR";
			exit(0);
		}
		if($this->prodClassInfo === NULL){
			$this->prodClassInfo = $flag;	
		}
	}
	
	/**
	 * 获取商品分类ID 
	 * 
	 */
	private function _getProdClassId()
	{
		return intval($this->params()->fromQuery('prod_class'));
	}
	
	/**
	 * 初始化数据库连接适配器 
	 * 
	 */
	private function _getAttrDb()
	{
		if($this->attrDb ===  NULL){
			$this->attrDb = new Attribute($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));	
		}
		return $this->attrDb;
	}
	
	/**
	 * The default action - show the home page
	 */
    public function indexAction()
    {
        
		$this->_checkClassExisted();
		$prodClassId = $this->_getProdClassId();
		
		$viewData=array();
        $success = Tool::getCookie('success');
        if ($success)
        {
        	$viewData['success']=json_decode($success);
        }
        
		
		//分页处理数组显示
		$doPager = $this->_doPager($this->_getAttrDb()->getAttrTree($this->user->domain,$prodClassId),$prodClassId);
		if($doPager){
			$viewData['rows']=Tool::genDisplayTreeList($doPager['show_data']);
			$viewData['pager_link']=$doPager['pager_link'];	
		}
		
        $viewData['user']=$this->user;
		$viewData['prodClassInfo'] = $this->prodClassInfo;
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
			$html.="<li><a href=\"/attribute/index/?prod_class=$classid\">首页</a></li>";
			$html.="<li><a href=\"/attribute/index/$pre_page/?prod_class=$classid\">前一页</a></li>";	
		}
		if($page < $total_page){
			$next_page = $page + 1;
			$html.="<li><a href=\"/attribute/index/$next_page/?prod_class=$classid\">下一页</a></li>";
			$html.="<li><a href=\"/attribute/index/$total_page/?prod_class=$classid\">尾页</a></li>";
		}
		$html.="</ul>";
		$html.="</div>";
		return $html;
	}
	
	
	
	/**
     * @todo 创建商品属性
     */
    public function createAction()
    {
		
		$this->_checkClassExisted();
		$prodClassId = $this->_getProdClassId();
		
    	$viewData=array();
    	$viewData['user']=$this->user;
    	
    	$request=$this->getRequest();
    	if ($request->isPost())
    	{
    		$postData = $request->getPost();
    		$data=array();
    		$data['pid']=$postData['pid'];
    		$data['name']=Tool::filter($postData['name'],true);
    		$data['domain']=$this->user->domain;
    		$data['display']=$postData['display'];
			$data['prod_class']=$prodClassId;
			
			$flag = $this->_getAttrDb()->addAttr($data);
    		if ($flag)
    		{
				//清除缓存
				$key = "attr_".$this->user->domain."_".$prodClassId."_0";
				Tool::delCache($key);
    			Tool::setCookie('success', array('title'=>'添加成功','message'=>'已经成功添加商品属性'),time()+5);
    		    $this->redirect()->toUrl("/attribute/index?prod_class=$prodClassId");
    		}
    	}
		
		$rows = $this->_getAttrDb()->getAttrTree($this->user->domain,$prodClassId);
		
		$viewData['select_opts'] = Tool::getTypeTree($rows);
		$viewData['prodClassInfo'] = $this->prodClassInfo;
    	$viewData['asset']=array('js'=>array('/lib/attr.js'));
		$viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
    	return $viewData;
    }
	
	
   
   /**
    * @todo 编辑商品属性
    * @return multitype:Ambigous <\Admin\Model\Ambigous, boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>
    */
    public function editAction()
    {
        
		$viewData=array();
        $viewData['user']=$this->user;
        $id = intval($this->params()->fromQuery('id'));
        if (empty($id)) $this->redirect()->toRoute('admin');
        
        $request = $this->getRequest();
        if ($request->isPost())
        {
        	$postData=$request->getPost();
        	$data=array();
        	$data['pid']=$postData['pid'];
        	$data['name']=Tool::filter($postData['name'],true);
        	$data['display']=$postData['display'];
			$data['sorting']=intval($postData['sorting']);//增加排序属性
        	if ($this->_getAttrDb()->editAttr($data,$id,$this->user->domain))
        	{
				//清除缓存
				$key = "attr_".$this->user->domain."_".$postData['prod_class']."_0";
				Tool::delCache($key);
        	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>'已经成功编辑商品属性'),time()+5);
        	}
			$this->redirect()->toUrl("/attribute/index?prod_class=".$postData['prod_class']);
        }
        $row=$this->_getAttrDb()->getAttrById($id,$this->user->domain);
        if (!$row) $this->redirect()->toRoute('admin');
        $viewData['row']=$row;
		$viewData['prodClassInfo'] = $this->_getAttrDb()->checkProdClassExisted($row->prod_class,$this->user->domain);
		$treeData = $this->_getAttrDb()->getAttrTree($this->user->domain,$row->prod_class);
        $viewData['select_opts']=Tool::getTypeTree($treeData,"pid","pid",$row->pid,$id);
        $viewData['asset']=array('js'=>array('/lib/attr.js'));
		$viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
        return $viewData;
    }
    
	
	/**
     * @删除商品属性
     */
    public function deleteAction()
    {
        $id = (int)$this->params()->fromQuery('id');
		if ($id)
        {
			$row = $this->_getAttrDb()->getAttrById($id,$this->user->domain);
			if(!$row){
				$jsonRst = array(
					'req' => "error",
					'msg' => "商品属性不存在,无法删除"
				);
				echo json_encode($jsonRst);
		        exit(0);
			}
        	if ($this->_getAttrDb()->delAttr($id,$this->user->domain))
        	{
				$key = "attr_".$this->user->domain."_".$row->prod_class."_0";
				Tool::delCache($key);
				$jsonRst = array(
					'req' => "ok",
					'msg' => "商品属性删除成功"
				);
				
				echo json_encode($jsonRst);	
        		exit(0);
        	}
        }
        $jsonRst = array(
			'req' => "error",
			'msg' => "商品属性删除失败"
		);
		echo json_encode($jsonRst);
        exit(0);
    }
	
	
	/**
	 *AJAX异步更新排序序号 
	 * 
	 */
	function ajaxUpdateSortingAction()
	{
		$id = (int)$this->params()->fromQuery('id');
		$sorting = (int)$this->params()->fromQuery('sorting');
		
		if ($data = $this->_getAttrDb()->updateAttrSorting($sorting,$id))
		{
			//清除缓存
			$key = "attr_".$this->user->domain."_".$data->prod_class."_0";
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