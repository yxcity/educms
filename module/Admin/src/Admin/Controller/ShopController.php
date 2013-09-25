<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Shop;
use Admin\Model\User;
use Admin\Model\File;


/**
 * @todo 门店管理类
 * 
 * @author
 * @version 
 */
class ShopController extends AbstractActionController
{
	private $user;
	private $viewData=array();
    
    public function __construct()
    {
    	$this->user = Tool::getSession('auth','user');
    	$this->viewData['user']=$this->user;
    }
    /**
     * @todo 门店列表
     * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
     */
    public function indexAction()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        
        if ($this->user->power<2) $this->redirect()->toRoute('admin');
        
        $success = Tool::getCookie('success');
        if ($success)
        {
        	$this->viewData['success']=json_decode($success);
        }
        
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $request = $this->getRequest();
        
        //编辑门店管理员
        $action= $this->params()->fromQuery('action',null);
        if ($action=='m')
        {
        	$this->viewData['action'] = $action;
        	$id = $this->params()->fromQuery('id');
        	$this->viewData['id']=$id;
        	$user = new User($adapter);
        	$row=$user->getUser($id);
        	if ($row['uid'] != $this->user->id) $this->redirect()->toRoute('users');
        	$userShop=array();
        	if ($row['shop'])
        	{
        		$userShop=json_decode($row['shop']);
        	}
        	$this->viewData['userShop']=$userShop;
        }
        
        $request = $this->getRequest();
        //keywords
        $keywords = $this->params()->fromQuery('key',null);
        if ($request->isPost())
        {
    	    $postData = $request->getPost();
    		$keywords = Tool::filter($postData['keywords']);
        }
        //
        if ($request->isPost() && !$keywords)
        {
        	$data=$request->getPost();
        	$shop = $data['id']?json_encode($data['id']):null;
        	$user->editUser($id, null,array('shop'=>$shop));
        	Tool::setCookie('success', array('title'=>'操作成功','message'=>"编辑门店管理员成功"),time()+2);
        	$this->redirect()->toUrl("/shop?action=m&id={$id}");
        }
        $page=$this->params('page',1);
        $db = new Shop($adapter);
        $rows = $db->shopList($page,$this->user,'20',$keywords);
        $this->viewData['rows']=$rows;
        $this->viewData['keywords']=$keywords;
        $userDB=new User($adapter);
        $this->viewData['userData']=$userDB->getUser($this->user->id);
        $this->viewData['count'] = $db->shopCount($this->user->domain);
		$this->viewData['bp'] = $this->BackendPlugin();		
		
        $this->viewData['asset']=array('css'=>array('/lib/uploadify/uploadify.css'));
        return $this->viewData;
    }
   /**
    * @todo 创建门店
    */ 
    public function createAction()
    {
    	if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
    	if ($this->user->power<2) $this->redirect()->toRoute('admin');
    	//判断一下是否可以
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
    	$db = new Shop($adapter);
        $request=$this->getRequest();
    	if ($request->isPost())
    	{
    		$postData = $request->getPost();
    		$data=array();
    		
			//门店缩略图处理
			if($thumb = $this->_handleThumb()){
				$data['thumb'] = $thumb;
			}
    		
    		$data['uid']=$this->user->domain;
    		$data['shopname']=Tool::filter($postData['shopname']);
    		$data['address']=Tool::filter($postData['address']);
    		$data['tel'] = Tool::filter($postData['tel'],true);
    		$data['content']=Tool::filter($postData['content']);
			$data['province']=Tool::filter($postData['province']);
			$data['city']=Tool::filter($postData['city']);
			$data['locationX']=Tool::filter($postData['locationX']);
			$data['locationY']=Tool::filter($postData['locationY']);
    		$data['addtime']=time();
    		if ($shop_id = $db->addShop($data))
    		{
				//添加上传的门店图片
				$dbFile = new File($adapter);
				$dbFile->saveModuleImages(File::$m_shop,$shop_id,$this->user->id,$postData['images']);
				
    		    Tool::setCookie('success', array('title'=>'添加成功','message'=>"成功添加门店"),time()+5);
    		    $this->redirect()->toRoute('shop');
    		}
    	}
        $userDB=new User($adapter);
        $this->viewData['userData']=$userDB->getUser($this->user->id);
        $this->viewData['count'] = $db->shopCount($this->user->domain);
		
		$this->viewData['uploadParams'] = array(
		   	'_identify' => Tool::_getIdentify(),//用于Uploadify组件AJAX上传时传递Cookie信息
			'module_id' => File::$m_shop
	    );
		
    	$this->viewData['asset']=array('css'=>array('/lib/uploadify/uploadify.css'),'js'=>array('/lib/uploadify/jquery.uploadify.min.js','/lib/shop.js','/ueditor/ueditor.all.min.js','/ueditor/ueditor.config.js'));
    	return $this->viewData;
    }
    /**
     * @todo 编辑门店
     * @return multitype:Ambigous <\Admin\Model\Ambigous, boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>
     */
    public function editAction()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        if ($this->user->power<2) $this->redirect()->toRoute('admin');
        $id=(int)$this->params()->fromQuery('id');
        if (empty($id)) $this->redirect()->toRoute('shop');
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new Shop($adapter);
        $row=$db->getShop($id);
        
        if (!$row || $this->user->domain != $row['uid'] || $this->user->power!=2) $this->redirect()->toRoute('shop');
        
        $request = $this->getRequest();
        if ($request->isPost())
        {
            $postData = $request->getPost();
            $data=array();
			
            //门店缩略图处理
			if($thumb = $this->_handleThumb()){
				$data['thumb'] = $thumb;
			}
        	$data['shopname']=Tool::filter($postData['shopname']);
        	$data['address']=Tool::filter($postData['address']);
        	$data['tel'] = Tool::filter($postData['tel'],true);
        	$data['content']=Tool::filter($postData['content']);
			$data['province']=Tool::filter($postData['province']);
		    $data['city']=Tool::filter($postData['city']);
		    $data['locationX']=Tool::filter($postData['locationX']);
		    $data['locationY']=Tool::filter($postData['locationY']);
		   
        	if ($db->editShop($id, $data))
        	{
        	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>"编辑门店成功"),time()+5);
        	    //$this->redirect()->toRoute('shop');
        	}else{
				Tool::setCookie('error', array('title'=>'编辑失败','message'=>'编辑失败，写入数据失败'),time()+5);	
			}
        }
        $this->viewData['row']=$db->getShop($id);
		
		//取得门店所有图片文件
	    $dbFile = new File($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
	    $this->viewData['files'] = $dbFile->getFiles(File::$m_shop,$id);
	   
		$this->viewData['uploadParams'] = array(
		   	'_identify' => Tool::_getIdentify(),//用于Uploadify组件AJAX上传时传递Cookie信息
			'module_id' => File::$m_shop,
			'target_id' => $id,
			'thumb_size' => 200
	    );
	   
        $this->viewData['asset']=array('js'=>array('/lib/shop.js','/ueditor/ueditor.all.min.js','/ueditor/ueditor.config.js'));
        return $this->viewData;
    }
   
    /**
     * @todo
     */
    public function deleteAction()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $id = (int)$this->params()->fromQuery('id');
        if ($id)
        {
            $db = new Shop($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
            $row = $db->getShop($id);
            if ($row['uid']==$this->user->domain && $this->user->power >= 2)
            {
                $db->deleteShop($id);
                echo '{"isok":true}';
                exit();
            }
        }
        echo '{"isok":false}';
        exit();
    }
	
	
	/**
	 * 门店封面缩略图上传处理 
	 * 
	 */
	private function _handleThumb()
	{
		$request=$this->getRequest();
		$file=$request->getFiles ()->toArray();
    	if ($file && is_array($file))
    	{
    		$thumb = File::doUpload($file,$this->user->id,array('gif','jpg','png'),NULL,array('thumb_size'=>200));
    		if (isset($thumb['file']))
    		{
				return $thumb['file'];
    		}
    	}
		return FALSE;
	}
}