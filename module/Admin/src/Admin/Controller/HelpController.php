<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Article;
use Admin\Model\File;
use Admin\Model\Type;


/**
 * @todo 系统公告帮助管理
 * 
 * @author
 * @version 
 */
class HelpController extends AbstractActionController
{
	private $user;
	private $_adapter = NULL;
    public function __construct()
	{
		$this->user = Tool::getSession('auth','user');
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
	private function _getArtDb()
	{
		if($this->_adapter == NULL){
			$this->_adapter = new Article($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
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
	 * 公告帮助
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
        
        //取得所有分类
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $typeRows=new Type($adapter);
        $typeRows = $typeRows->typeAll($this->user->domain,'','',$this->user);
        $type=array();
        if ($typeRows)
        {
        	foreach ($typeRows as $val) {
        		$type[$val['id']]=$val['name'];
        	}
        }
		
		$page=$this->params('page',1);
        $this->viewData['type']=$type;
        $this->viewData['keywords']=$keywords;
		$this->viewData['rows']=$this->_getArtDb()->newsList($page,$this->_getArtDb()->_help,$this->user->domain,'20',$keywords);
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		
		//页面提示信息
		$msg = $this->_displayMsg();
		if($msg){
			foreach($msg as $mk=>$mv){
				$this->viewData[$mk] = $mv;
			}
		}
		
		return $this->viewData;
	}
	
	
	/**
	 * 添加文章标签 
	 * @param undefined $tags
	 * 
	 */
	private function _addArtTags($tags,$art_id)
	{
		if(empty($tags)){
			return FALSE;
		}
		if(!preg_match("/^[\S]+(,)*/",$tags)){
			return FALSE;
		}
		$tags = explode(",",$tags);
		$this->_getArtDb()->addTag($tags,$art_id);
	}
	
	
	/**
	 * 新增公告帮助 
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
				'art_title' => Tool::filter($postData['art_title'],TRUE),
				'art_content'=> $postData['content'],
				'owner_id' => $this->user->id,
				'created_time' => time(),
				'domain' => $this->user->domain,
				'classid' => $this->_getArtDb()->_help,
				'subclassid'=> (int)$postData['subclassid'],
				'display' => 1,
				'is_removable' => 1,
				'author' => $postData['author'] ? $postData['author'] : ""
			);
        	if ($art_id = $this->_getArtDb()->addArt($data))
        	{
				$this->_addArtTags(Tool::filter($postData['tag'],TRUE),$art_id);
				
				$file=$request->getFiles ()->toArray();
	            if ($file && is_array($file))
	            {
					//上传文件并写入数据库
	            	$thumb = File::doUpload($file,$this->user->id,array('gif','jpg','png'),File::$m_news);
	            	if($thumb['file'])
	            	{
						$fileInfo = array(
							'file_desc' => " - ",
							'path' => $thumb['file'],
							'filesize' => filesize(BASE_PATH.$thumb['file']),
							'is_img' => 1,
							'created_time' => time(),
							'module_id' => File::$m_news,
							'target_id' => $art_id,
							'owner_id' => $this->user->id
						);
						$dbFile = new File($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
						$dbFile->addFile($fileInfo);
	            	}
	            }
				
        	    Tool::setCookie('success', array('title'=>'添加成功','message'=>'已经成功添加公告帮助信息'),time()+5);
        	}else{
				
				Tool::setCookie('error', array('title'=>'添加失败','message'=>'该内容已存在,请勿重复添加'),time()+5);
			}
			$this->redirect()->toUrl("/help/index");
        }
		
		//取得公告备选分类列表(树型结构显示),分类ID为12代表是帮助分类
		$dbType = new Type($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $viewData['select_items']=Tool::genDisplayTreeList($dbType->getTypeTree($this->user->domain,12));
		
        $viewData['asset']=array('js'=>array('/lib/article.js'));
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
	 * 编辑公告帮助 
	 * 
	 */
	public function editAction()
	{
		$viewData=array();
        $viewData['user']=$this->user;
        $art_id = intval($this->params()->fromQuery('art_id'));
		if(empty($art_id)){
			$this->redirect()->toUrl("/help/index");
		}
		
        $request = $this->getRequest();
        if ($request->isPost())
        {
        	$postData=$request->getPost();
			$data = array(
				'art_title' => Tool::filter($postData['art_title'],TRUE),
				'art_content'=> $postData['content'],
				'author' => $postData['author'] ? $postData['author'] : "",
				'subclassid' => (int)$postData['subclassid'],
				'updated_time' => time()
			);
        	if ($this->_getArtDb()->editArt($data,$art_id,$this->user->domain))
        	{
				$this->_addArtTags(Tool::filter($postData['tag'],TRUE),$art_id);
				
				$file=$request->getFiles ()->toArray();
	            if ($file && is_array($file))
	            {
					//更换上传文件并写入数据库
	            	$thumb = File::doUpload($file,$this->user->id,array('gif','jpg','png'),File::$m_news);
	            	if($thumb['file'])
	            	{
						$fileInfo = array(
							'file_desc' => " - ",
							'path' => $thumb['file'],
							'filesize' => filesize(BASE_PATH.$thumb['file']),
							'is_img' => 1,
							'created_time' => time(),
							'module_id' => File::$m_news,
							'target_id' => $art_id,
							'owner_id' => $this->user->id
						);
						$dbFile = new File($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
						$dbFile->updateFile($fileInfo,$postData['file_id']);
	            	}
	            }
				
        	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>'已经成功编辑公告帮助信息'),time()+5);
        	}else{
				
				Tool::setCookie('error', array('title'=>'编辑失败','message'=>'公告帮助信息未作更新'),time()+5);
			}
			$this->redirect()->toUrl("/help/index");
        }
		$row = $this->_getArtDb()->getArtById($art_id,$this->_getArtDb()->_help,$this->user->domain);
		if(empty($row)){
			$this->redirect()->toUrl("/help/index");
		}
		$viewData['fileInfo'] = $this->_getArtDb()->getArtFileInfo($art_id,400);
        $viewData['row'] = $row;
		$viewData['asset']=array('js'=>array('/lib/article.js'));
		$viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		
		//取得公告备选分类列表(树型结构显示),分类ID为12代表是帮助分类
		$dbType = new Type($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $viewData['select_items']=Tool::genDisplayTreeList($dbType->getTypeTree($this->user->domain,12));
		
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
	 *删除公告帮助
	 */
	function deleteAction()
	{
	    $art_id = (int)$this->params()->fromQuery('art_id');
		
		
		if ($this->_getArtDb()->delArt($art_id,$this->user->domain))
		{
			//删除文件及记录
			$dbFile = new File($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
			$dbFile->delFileAndRecord(File::$m_news,$art_id);
			
			$jsonRst = array(
				'req' => "ok",
				'msg' => "文章删除成功"
			);	    
		}else{
			$jsonRst = array(
				'req' => "error",
				'msg' => "文章删除失败"
			);
		}
	    echo json_encode($jsonRst);
		exit(0);
	}
}