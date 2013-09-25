<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Party;


/**
 * @todo 插件-活动管理
 * 
 * @author
 * @version 
 */
class PartyController extends AbstractActionController
{
	private $user;
	private $_adapter = NULL;
    public function __construct()
	{
		$this->user = Tool::getSession('auth','user');
		$this->domain=Tool::domain();
		$this->viewData['user']=$this->user;
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
	 * 建立数据库连接 
	 * 
	 */
	private function _getPartyDb()
	{
		if($this->_adapter == NULL){
			$this->_adapter = new Party($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
		}
		return $this->_adapter;
	}
	
	
	/**
	 * 页面输出提示信息 
	 * 
	 */
	private function _displayMsg()
	{
		$msg = array();
		$success = Tool::getCookie('success');
		if ($success)
		{
		    $msg['success']=json_decode($success);
		}
        $error=Tool::getCookie('error');
	    if ($error)
	    {
	    	$msg['error']=json_decode($error);
	    }
		return $msg;
	}
	
	
	/**
	 * 报名活动列表
	 * 
	 */
	public function indexAction()
	{
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
        $this->viewData['keywords']=$keywords;
		$this->viewData['rows']=$this->_getPartyDb()->partyList($page,$this->user,'20',$keywords);
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		
		//页面提示信息
		$msg = $this->_displayMsg();
		if($msg){
			foreach($msg as $mk=>$mv){
				$this->viewData[$mk] = $mv;
			}
		}

		$this->viewData['asset']=array('css'=>array('/lib/uploadify/uploadify.css'));
		return $this->viewData;
	}
	
	
	/**
	 * 新增活动 
	 * 
	 */
	public function createAction()
	{
		$viewData=array();
        $viewData['user']=$this->user;
        
        $request = $this->getRequest();
        if ($request->isPost())
        {
        	$postData=$request->getPost();
			$data = array(
				'party_title' => Tool::filter($postData['party_title'],TRUE),
				'party_content'=> $postData['content'],
				'password'=> $postData['password'],
				'joinname'=> $postData['joinname'],
				'mp'=> $postData['mp'],
				'owner_id' => $this->user->id,
				'created_time' => time(),
				'domain' => $this->user->domain,
				'classid' => 0,
				'display' => 1,
				'usage' => 1,
				'author' => $postData['author'] ? $postData['author'] : ""
			);
        	if ($party_id = $this->_getPartyDb()->addParty($data))
        	{	
        	    Tool::setCookie('success', array('title'=>'添加成功','message'=>'已经成功添加活动'),time()+5);
        	}else{
				
				Tool::setCookie('error', array('title'=>'添加失败','message'=>'该活动已存在,请勿重复添加'),time()+5);
			}
			$this->redirect()->toUrl("/party/index");
        }
		
        $viewData['asset']=array('js'=>array('/lib/party.js'));
		$viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		
		//页面提示信息
		$msg = $this->_displayMsg();
		if($msg){
			foreach($msg as $mk=>$mv){
				$viewData[$mk] = $mv;
			}
		}
		
        return $viewData;
	}
	
	/**
	 * 编辑活动 
	 * 
	 */
	public function editAction()
	{
		$viewData=array();
        $viewData['user']=$this->user;
        $party_id = intval($this->params()->fromQuery('party_id'));
		if(empty($party_id)){
			$this->redirect()->toUrl("/party/index");
		}
		
        $request = $this->getRequest();
        if ($request->isPost())
        {
        	$postData=$request->getPost();
			$data = array(
				'party_title' => Tool::filter($postData['party_title'],TRUE),
				'party_content'=> $postData['content'],
				'password'=> $postData['password'],
				'joinname'=> $postData['joinname'],
				'mp'=> $postData['mp'],
				'author' => $postData['author'] ? $postData['author'] : "",
				'updated_time' => time()
			);
        	if ($this->_getPartyDb()->editParty($data,$party_id,$this->user->domain))
        	{
				
        	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>'已经成功编辑活动'),time()+5);
        	}else{
				
				Tool::setCookie('error', array('title'=>'编辑失败','message'=>'活动未作更新'),time()+5);
			}
			$this->redirect()->toUrl("/party/index");
        }
		$row = $this->_getPartyDb()->getParty($party_id,$this->user->domain);
		if(empty($row)){
			$this->redirect()->toUrl("/party/index");
		}
        $viewData['row'] = $row;
		$viewData['asset']=array('js'=>array('/lib/party.js'));
		$viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		
		//页面提示信息
		$msg = $this->_displayMsg();
		if($msg){
			foreach($msg as $mk=>$mv){
				$viewData[$mk] = $mv;
			}
		}
		
        return $viewData;
	}
	

	/**
	 *删除活动
	 */
	function deleteAction()
	{
		$party_id = (int)$this->params()->fromQuery('party_id');
	
		if ($this->_getPartyDb()->delParty($party_id,$this->user->domain))
		{
			$jsonRst = array(
					'req' => "ok",
					'msg' => "活动删除成功"
			);
		}else{
			$jsonRst = array(
					'req' => "error",
					'msg' => "活动删除失败"
			);
		}
		echo json_encode($jsonRst);
		exit(0);
	}
	/**
	 *删除名单
	 */
	
	function partyusersdeleteAction()
	{
	    $id = (int)$this->params()->fromQuery('id');		
		
		if ($this->_getPartyDb()->delpartyusers($id,$this->user->domain))
		{			
			$jsonRst = array(
				'req' => "ok",
				'msg' => "名单删除成功"
			);	    
		}else{
			$jsonRst = array(
				'req' => "error",
				'msg' => "名单删除失败"
			);
		}
	    echo json_encode($jsonRst);
		exit(0);
	}
	
	/**
	 * @todo 报名列表
	 * (non-PHPdoc)
	 * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
	 */
	function partyusersAction()
	{
	    $parentid = (int)$this->params()->fromQuery('parentid');	
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
		$partydb=new party($adapter);
        $this->viewData['keywords']=$keywords;
        $this->viewData['parentid']=$parentid;
		$this->viewData['rows']=$partydb->userList($parentid,$page,$this->user,$this->domain,'20',$keywords);
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		return $this->viewData;
	}
}