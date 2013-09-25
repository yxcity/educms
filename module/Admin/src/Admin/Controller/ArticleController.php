<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Article;
use Admin\Model\Type;
use Admin\Model\File;
use Admin\Model\Role;


/**
 * @todo 文章管理
 * 
 * @author
 * @version 
 */
class ArticleController extends AbstractActionController
{
	private $user;
	private $_adapter = NULL;
    private $_dbRole = NULL;
    
    public function __construct()
	{
		$this->user = Tool::getSession('auth','user');
		$this->viewData['user']=$this->user;
	}
    
	
	/**
	 * 检查传递过来的类型参数是否有效 
     * 如果存在，则返回label
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
        Tool::setCache("_class_label",$classes[$classid]);
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
	
    private function _getRoleDb()
    {
        if($this->_dbRole == NULL){
			$this->_dbRole = new Role($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
		}
		return $this->_dbRole;    
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
    * @todo 编辑文章内容
    * @return multitype:Ambigous <\Admin\Model\Ambigous, boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>
    */
    public function aboutAction()
    {
		$viewData=array();
        $viewData['user']=$this->user;
		$this->_checkClassExisted();
        $classid = $this->params()->fromQuery('classid');
		
        $request = $this->getRequest();
        if ($request->isPost())
        {
        	$postData=$request->getPost();
			$data = array(
				'art_title' => $postData['art_title'],
				'art_content'=> $postData['content'],
				'owner_id' => $this->user->id
			);
        	if ($this->_getArtDb()->saveArtInfo($data,$classid,$this->user->domain))
        	{
        	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>'已经成功编辑信息'),time()+5);
        	}else{
				Tool::setCookie('success', array('title'=>'未作更新','message'=>'#'),time()+5);
				
			}
			$this->redirect()->toUrl("/article/about?classid=$classid");
        }
        $row=$this->_getArtDb()->getArtByClassId($classid,$this->user->domain);
        $viewData['row']=$row;
        $viewData['class_label'] = $this->_getRoleDb()->getAliasLabel($this->user->domain,Tool::mapAliasKey($classid),Tool::getCache("_class_label"));
        $viewData['asset']=array('js'=>array('/lib/article.js'));
		
        return $viewData;
    }
    
	private function _getS()
	{
		$s = strtolower($this->params()->fromQuery('s'));
		if($s == "blt"){
			return array('st'=>$s,'sl'=>"公司公告",'classid'=>$this->_getArtDb()->_blt,'sm'=>File::$m_blt);
		}
		return array('st'=>"def",'sl'=>"新闻动态",'classid'=>$this->_getArtDb()->_news,'sm'=>File::$m_news);
	}
	
	/**
	 * 公司动态 
	 * 
	 */
	public function indexAction()
	{
        //keywords
        $request = $this->getRequest();
        $keywords = $this->params()->fromQuery('key',null);
		$sa = $this->_getS();
		$subclassid = 0;
        if ($request->isPost())
        {
    	    $postData = $request->getPost();
    		$keywords = Tool::filter($postData['keywords']);
			$subclassid = (int)$postData['subclassid'];
        }
        //
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
		$this->viewData['subclassid'] = $subclassid;
        $this->viewData['keywords']=$keywords;
		$this->viewData['sa'] = $sa;
		$this->viewData['rows']=$this->_getArtDb()->newsList($page,$sa['classid'],$this->user->domain,'20',$keywords,$subclassid);
		$this->viewData['type']=$type;
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		
		//取得新闻备选分类列表(树型结构显示) 11-新闻分类
		$dbType = new Type($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $this->viewData['select_items']=Tool::genDisplayTreeList($dbType->getTypeTree($this->user->domain,11));
        $this->viewData['asset']=array('css'=>array('/lib/uploadify/uploadify.css'));
		
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
	 * 新增公司动态 
	 * 
	 */
	public function createAction()
	{
		$viewData=array();
        $viewData['user']=$this->user;
        
		$sa = $this->_getS();
        $request = $this->getRequest();
        if ($request->isPost())
        {
        	$postData=$request->getPost();
			$data = array(
				'art_title' => Tool::filter($postData['art_title'],TRUE),
				'art_title_url'=> $postData['art_title_url'],
				'art_content'=> $postData['content'],
				'art_from'=> $postData['art_from'],
				'commend'=> $postData['commend'],
				'owner_id' => $this->user->id,
				'created_time' => time(),
				'domain' => $this->user->domain,
				'classid' => $sa['classid'],
				'subclassid' => intval($postData['subclassid']),//新闻动态子分类
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
	            	$thumb = File::doUpload($file,$this->user->id,array('gif','jpg','png'),$sa['sm']);
	            	if($thumb['file'])
	            	{
						$fileInfo = array(
							'file_desc' => " - ",
							'path' => $thumb['file'],
							'filesize' => filesize(BASE_PATH.$thumb['file']),
							'is_img' => 1,
							'created_time' => time(),
							'module_id' => $sa['sm'],
							'target_id' => $art_id,
							'owner_id' => $this->user->id
						);
						$dbFile = new File($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
						$dbFile->addFile($fileInfo);
	            	}
	            }
				Tool::setFlash('success',array('title'=>'添加成功','message'=>'已经成功添加'.$sa["sl"].'信息'));
        	}else{
				
				Tool::setFlash('error', array('title'=>'添加失败','message'=>'该内容已存在,请勿重复添加'));
			}
			$this->redirect()->toUrl("/article/index?s=".$sa['st']);
        }
		
		//取得新闻备选分类列表(树型结构显示) 11-新闻分类
		$dbType = new Type($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $viewData['select_items']=Tool::genDisplayTreeList($dbType->getTypeTree($this->user->domain,11),-1);
		
		$viewData['sa'] = $sa;
        $viewData['asset']=array('js'=>array('/lib/article.js'));
		$viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		
        return $viewData;
	}
	
	/**
	 * 编辑公司动态 
	 * 
	 */
	public function editAction()
	{
		$viewData=array();
        $viewData['user']=$this->user;
        $art_id = intval($this->params()->fromQuery('art_id'));
		if(empty($art_id)){
			$this->redirect()->toUrl("/article/index");
		}
		$sa = $this->_getS();
        $request = $this->getRequest();
        if ($request->isPost())
        {
        	$postData=$request->getPost();
			$data = array(
				'art_title' => Tool::filter($postData['art_title'],TRUE),
				'art_title_url'=> $postData['art_title_url'],
				'art_content'=> $postData['content'],
				'art_from'=> $postData['art_from'],
				'commend'=> $postData['commend'],
				'subclassid' => intval($postData['subclassid']),//新闻动态子分类
				'author' => $postData['author'] ? $postData['author'] : "",
				'updated_time' => time()
			);
        	if ($this->_getArtDb()->editArt($data,$art_id,$this->user->domain))
        	{
				$this->_addArtTags(Tool::filter($postData['tag'],TRUE),$art_id);
				
				$file=$request->getFiles ()->toArray();
	            if ($file && is_array($file))
	            {
					//更换上传文件并写入数据库
	            	$thumb = File::doUpload($file,$this->user->id,array('gif','jpg','png'),$sa['sm']);
	            	if($thumb['file'])
	            	{
						$fileInfo = array(
							'file_desc' => " - ",
							'path' => $thumb['file'],
							'filesize' => filesize(BASE_PATH.$thumb['file']),
							'is_img' => 1,
							'created_time' => time(),
							'module_id' => $sa['sm'],
							'target_id' => $art_id,
							'owner_id' => $this->user->id
						);
						$dbFile = new File($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
						$dbFile->updateFile($fileInfo,$postData['file_id']);
	            	}
	            }
				
        	    Tool::setFlash('success', array('title'=>'编辑成功','message'=>'已经成功编辑'.$sa["sl"].'信息'));
        	}else{
				
				Tool::setFlash('error', array('title'=>'编辑失败','message'=>$sa["sl"].'信息未作更新'));
			}
			$this->redirect()->toUrl("/article/index?s=".$sa['st']);
        }
		$row = $this->_getArtDb()->getArtById($art_id,$sa['classid'],$this->user->domain);
		if(empty($row)){
			$this->redirect()->toUrl("/article/index?s=".$sa['st']);
		}
		
		//取得新闻备选分类列表(树型结构显示) 11-新闻分类
		$dbType = new Type($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $viewData['select_items']=Tool::genDisplayTreeList($dbType->getTypeTree($this->user->domain,11),-1);
		
		$viewData['sa'] = $sa;
		$viewData['fileInfo'] = $this->_getArtDb()->getArtFileInfo($art_id,400,$sa['sm']);
        $viewData['row'] = $row;
		$viewData['asset']=array('js'=>array('/lib/article.js'));
		$viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		
        return $viewData;
	}
	
	
	/**
	 *删除公司动态
	 */
	function deleteAction()
	{
	    $art_id = (int)$this->params()->fromQuery('art_id');
		$sa = $this->_getS();
		
		if ($this->_getArtDb()->delArt($art_id,$this->user->domain))
		{
			//删除文件及记录
			$dbFile = new File($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
			$dbFile->delFileAndRecord($sa['sm'],$art_id);
			
			$jsonRst = array(
				'req' => "ok",
				'msg' => "信息删除成功"
			);	    
		}else{
			$jsonRst = array(
				'req' => "error",
				'msg' => "信息删除失败"
			);
		}
        
        Tool::outputJson($jsonRst);
	}
}