<?php
namespace Admin\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\Level;
use module\Application\src\Model\Tool;
use Admin\Model\File;

class LevelController extends AbstractActionController{
	
	private $viewData=array();
	private static $_dbLevel = NULL;
	
	function __construct(){
		$this->user=Tool::getSession('auth','user');
		$this->viewData['user']=$this->user;
	}
	
	/**
	 * @todo 会员等级列表
	 * (non-PHPdoc)
	 * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
	 */
	function indexAction()
	{
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
		
		$this->viewData['rows']=$this->_getLevelDb()->getAllLevels($this->user->domain);
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		return $this->viewData;
	}
	
	
	
	/**
	 * @todo 创建会员等级
	 */
	function createAction()
	{
	    $massage = Tool::getCookie('massage');
	    if ($massage)
	    {
	    	$this->viewData['massage']=json_decode($massage);
	    }
	    
	   
	    $request=$this->getRequest();
		if ($request->isPost())
		{
		    $postData=$request->getPost()->toArray();
		    $data=array(
				'level_name' => Tool::filter($postData['level_name'],TRUE),
				'level_desc' => Tool::filter($postData['level_desc'],TRUE),
				'level_point' => (int)$postData['level_point'],
				'domain' => $this->user->domain
			);
	    	
			//会员等级ICON
			if($level_icon = $this->_handleIcon()){
				$data['level_icon'] = $level_icon;
			}
			$tid=$this->_getLevelDb()->addLevel($data);
			if($tid)
			{
			    Tool::setCookie('massage', array('title'=>'添加成功','message'=>"成功添加新会员等级",'alert'=>'success'),time()+5);
			    $this->redirect()->toUrl('/level');
			}else{
				 Tool::setCookie('massage', array('title'=>'添加失败','message'=>"当前会员等级已存在",'alert'=>'error'),time()+5);
				 $this->redirect()->toUrl('/level/create');
			}
		}
		$this->viewData['asset']=array('js'=>array('/lib/level.js','/js/jquery-foxibox-0.2.min.js'));
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		return $this->viewData;
	}
	
	
	
	
	/**
	 * @todo 编辑会员等级
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
		
		$db = $this->_getLevelDb();
	    $level_id = (int)$this->params()->fromQuery('level_id');
	    if (empty($level_id)) $this->redirect()->toUrl('/admin');
		
	    
	    
		//表单提交处理
		$request=$this->getRequest();
	    if ($request->isPost())
	    {
	    	$postData=$request->getPost();
	    	$data=array(
				'level_name' => Tool::filter($postData['level_name'],TRUE),
				'level_desc' => Tool::filter($postData['level_desc'],TRUE),
				'level_point' => (int)$postData['level_point']
			);
	    	
			//会员等级ICON
			if($level_icon = $this->_handleIcon()){
				$data['level_icon'] = $level_icon;
			}
			
	    	if($db->editLevel($level_id, $data))
	    	{
	    	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>"编辑会员等级成功"),time()+5);
	    	}else{
				Tool::setCookie('error', array('title'=>'编辑失败','message'=>'编辑失败，未作更新'),time()+5);
			}
			 $this->redirect()->toUrl("/level/edit?level_id={$level_id}");
	    }
		
		//获取要处理的数据内容
		$row=$db->getLevel($level_id,$this->user->domain);
	    if(!$row){
			Tool::setCookie('error', array('title'=>'编辑失败','message'=>'该会员等级信息可能不存在'),time()+5);
			$this->redirect()->toUrl("/level");
		}
	    $this->viewData['row']=$row;
        $this->viewData['level_id']=$level_id;
		$this->viewData['asset']=array('js'=>array('/lib/level.js'));
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
	    return $this->viewData;
	}
	
	
	/**
	 * @todo 删除会员等级
	 */
	function deleteAction()
	{
	    $level_id = (int)$this->params()->fromQuery('level_id');
		if ($levelInfo = $this->_getLevelDb()->delLevel($level_id,$this->user->domain))
		{
			File::delFileFromDisk(BASE_PATH.$levelInfo->level_icon);
			$jsonRst = array(
				'req' => "ok",
				'msg' => "会员等级删除成功"
			);	 
			echo json_encode($jsonRst);
			exit(0);   
		}
		
		$jsonRst = array(
			'req' => "error",
			'msg' => "会员等级删除失败"
		);
		echo json_encode($jsonRst);
		exit(0);
	}
	
	
	/**
	 * 会员等级ICON图片上传处理 
	 * 
	 */
	private function _handleIcon()
	{
		$request=$this->getRequest();
		$file=$request->getFiles ()->toArray();
    	if ($file && is_array($file))
    	{
    		$thumb = File::doUpload($file,$this->user->id,array('gif','jpg','png'),NULL,array('thumb_size'=>100));
    		if (isset($thumb['file']))
    		{
				return $thumb['file'];
    		}
    	}
		return FALSE;
	}
	
	
	/**
	 * 数据库连接适配器 
	 * 
	 */
	private function _getLevelDb()
	{
		if(self::$_dbLevel == NULL){
			self::$_dbLevel = new Level($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));	
		}
		return self::$_dbLevel;
	}
	
	
}	
?>