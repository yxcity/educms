<?php
namespace Admin\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\Brand;
use module\Application\src\Model\Tool;
use Admin\Model\File;

class BrandController extends AbstractActionController{
	
	private $viewData=array();
	private static $_dbBrand = NULL;
	
	function __construct(){
		$this->user=Tool::getSession('auth','user');
		$this->viewData['user']=$this->user;
	}
	
	/**
	 * @todo 品牌列表
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
		$db = $this->_getBrandDb();
        $this->viewData['keywords']=$keywords;
		$this->viewData['rows']=$db->brandList($this->user->domain,$page,20,$keywords);
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		return $this->viewData;
	}
	
	
	
	/**
	 * @todo 创建品牌
	 */
	function createAction()
	{
	    $massage = Tool::getCookie('massage');
	    if ($massage)
	    {
	    	$this->viewData['massage']=json_decode($massage);
	    }
	    
	   
	    $db = $this->_getBrandDb();
	    $request=$this->getRequest();
		if ($request->isPost())
		{
		    $postData=$request->getPost()->toArray();
		    $data=array(
				'brand_name' => Tool::filter($postData['brand_name']),
				'brand_desc' => $postData['brand_desc'],
				'site_url' => filter_var($postData['site_url'],FILTER_VALIDATE_URL),
				'sorting' => intval($postData['sorting']),
				'domain' => $this->user->domain,
				'display' => 1//默认显示
			);
	    	
			//品牌LOGO
			if($logo = $this->_handleLogo()){
				$data['brand_logo'] = $logo;
			}
			$tid=$db->addBrand($data);
			if($tid)
			{
			    Tool::setCookie('massage', array('title'=>'添加成功','message'=>"成功添加新品牌",'alert'=>'success'),time()+5);
			    $this->redirect()->toUrl('/brand');
			}else{
				 Tool::setCookie('massage', array('title'=>'添加失败','message'=>"当前品牌已存在",'alert'=>'error'),time()+5);
				 $this->redirect()->toUrl('/brand/create');
			}
		}
		$this->viewData['asset']=array('js'=>array('/lib/brand.js','/js/jquery-foxibox-0.2.min.js'));
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		return $this->viewData;
	}
	
	
	
	
	/**
	 * @todo 编辑品牌
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
		
		$db = $this->_getBrandDb();
	    $brand_id = (int)$this->params()->fromQuery('brand_id');
	    if (empty($brand_id)) $this->redirect()->toUrl('/admin');
		
	    
	    
		//表单提交处理
		$request=$this->getRequest();
	    if ($request->isPost())
	    {
	    	$postData=$request->getPost();
	    	$data=array(
				'brand_name' => Tool::filter($postData['brand_name']),
				'brand_desc' => $postData['brand_desc'],
				'site_url' => filter_var($postData['site_url'],FILTER_VALIDATE_URL),
				'sorting' => intval($postData['sorting'])
			);
	    	
			//品牌LOGO
			if($logo = $this->_handleLogo()){
				$data['brand_logo'] = $logo;
			}
			
	    	if($db->editBrand($brand_id, $data))
	    	{
	    	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>"编辑品牌成功"),time()+5);
	    	}else{
				Tool::setCookie('error', array('title'=>'编辑失败','message'=>'编辑失败，未作更新'),time()+5);
			}
			 $this->redirect()->toUrl("/brand/edit?brand_id={$brand_id}");
	    }
		
		//获取要处理的数据内容
		$row=$db->getBrand($brand_id,$this->user->domain);
	    if(!$row){
			Tool::setCookie('error', array('title'=>'编辑失败','message'=>'该品牌信息可能不存在'),time()+5);
			$this->redirect()->toUrl("/brand");
		}
		$row->thumb = empty($row->brand_logo) ? "" : File::getThumbFile($row->brand_logo,File::$m_brand,400);//获取缩略图
	    $this->viewData['row']=$row;
        $this->viewData['brand_id']=$brand_id;
		$this->viewData['asset']=array('js'=>array('/lib/brand.js'));
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
	    return $this->viewData;
	}
	
	
	
	/**
	 *AJAX异步更新排序序号 
	 * 
	 */
	function ajaxUpdateSortingAction()
	{
		$brand_id = (int)$this->params()->fromQuery('brand_id');
		$sorting = (int)$this->params()->fromQuery('sorting');
		
		
    	$db = $this->_getBrandDb();
		if ($db->updateBrandSorting($sorting,$brand_id,$this->user->domain))
		{
			
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
	 * @todo 删除品牌
	 */
	function deleteAction()
	{
	    $brand_id = (int)$this->params()->fromQuery('brand_id');
    	$db = $this->_getBrandDb();
		if ($brandInfo = $db->delBrand($brand_id,$this->user->domain))
		{
			File::delSrcAndThumbFiles($brandInfo->brand_logo,File::$m_brand);//删除LOGO
			$jsonRst = array(
				'req' => "ok",
				'msg' => "品牌删除成功"
			);	 
			echo json_encode($jsonRst);
			exit(0);   
		}
		
		$jsonRst = array(
			'req' => "error",
			'msg' => "品牌删除失败"
		);
		echo json_encode($jsonRst);
		exit(0);
	}
	
	
	/**
	 * 品牌LOGO图片上传处理 
	 * 
	 */
	private function _handleLogo()
	{
		$request=$this->getRequest();
		$file=$request->getFiles ()->toArray();
    	if ($file && is_array($file))
    	{
    		$thumb = File::doUpload($file,$this->user->id,array('gif','jpg','png'),File::$m_brand);
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
	private function _getBrandDb()
	{
		if(self::$_dbBrand == NULL){
			self::$_dbBrand = new Brand($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));	
		}
		return self::$_dbBrand;
	}
	
	
}	
?>