<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Commodity;
use Admin\Model\Shop;
use Admin\Model\Type;
use Admin\Model\User;
use Admin\Model\File;
use Admin\Model\Brand;
use Admin\Model\Attribute;

/**
 * @todo 商品管理类
 * @author
 * @version
 */
class CommodityController extends AbstractActionController
{

    private $user;

    function __construct ()
    {
        $this->user = Tool::getSession('auth','user');
    }

    function indexAction ()
    {
    	if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $viewData = array();
        $success = Tool::getCookie('success');
        if ($success)
        {
        	$viewData['success']=json_decode($success);
        }
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db =  new Commodity($adapter);
        $request = $this->getRequest();
        if($request->isPost())
        {
        	$postData = $request->getPost();
        	if ($postData['id'])
        	{
        		foreach ($postData['id'] as $val) {
        			$db->editCommodity($val, array('welcome'=>1));
        		}
        		Tool::setCookie('success', array('title'=>'操作成功','message'=>'设置欢迎商品成功'),time()+5);
        		$this->redirect()->toUrl('/commodity/welcome/');
        	}
        }
        $viewData['user']=$this->user;
        $page=$this->params('page');
        
        $userDB = new User($adapter);
        $row = $userDB->getUser($this->user->id);
        $userShop=false;
        if ($row['shop'])
        {
        	$userShop = "'".implode("','",json_decode($row['shop']))."'";
        }
        $keywords = $this->params()->fromQuery('key',null);
        $request = $this->getRequest();
        if ($request->isPost())
        {
    	    $postData = $request->getPost();
    		$keywords = Tool::filter($postData['keywords']);
        }
        $viewData['rows']=$db->commodityList($page,$this->user, $userShop, $num='20', $keywords);
        //取出门店
        $shopRows = new Shop($adapter);
        $shopRows= $shopRows->userShop($this->user->domain,'','',$this->user);
        $shop=array();
        if ($shopRows)
        {
        	foreach ($shopRows as $val) {
        		$shop[$val['id']]=$val['shopname'];
        	}
        }
        $viewData['shop']=$shop;
        
        //取得所有分类
        $typeRows=new Type($adapter);
        $typeRows = $typeRows->typeAll($this->user->domain,'','',$this->user);
        $type=array();
        if ($typeRows)
        {
        	foreach ($typeRows as $val) {
        		$type[$val['id']]=$val['name'];
        	}
        }
        $viewData['userData']=$row;
        $viewData['count'] = $db->commodityCount($this->user->domain,$this->user);
        $viewData['type']=$type;
        $viewData['keywords'] = $keywords;
        $viewData['action']=$this->params()->fromQuery('action');
		$viewData['bp'] = $this->BackendPlugin();
        $viewData['asset']=array('css'=>array('/lib/uploadify/uploadify.css'));
        return $viewData;
    }

    function createAction ()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $viewData=array();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $userShop = array();
        if ($this->user->power==1)
        {
            $user = new User($adapter);
            $row= $user->getUser($this->user->id);
            
            if ($row['shop'])
            {
                $userShop = json_decode($row['shop']);
            }
        }
        $viewData['userShop'] = $userShop;
        $viewData['user']=$this->user;
        $request = $this->getRequest();
        $db  = new Commodity($adapter);
        if ($request->isPost()) {
            $postData = $request->getPost();
            $data=array();
			
			//列表缩略图处理
			if($thumb = $this->_handleThumb()){
				$data['thumb'] = $thumb;
			}
			
			
			$data['freight'] = $this->_handleFreight($postData);//处理运费
            $data['shop'] = $postData['shop'];
            $data['uid'] = $this->user->domain;
            $data['editor'] = $this->user->id;
			$data['brandID'] = (int)$postData['brandID'];
            $data['cateID'] = (int)$postData['cateID'];
            $data['name'] = Tool::filter($postData['wares'],true);
            //$data['images'] = isset($postData['images'])?serialize($postData['images']):null;
            $data['price'] = Tool::filter($postData['price'],true);
            $data['rebate'] = Tool::filter($postData['rebate'],true);
            $data['order'] = $postData['order'] ? Tool::filter($postData['order'], true) : null;
            $data['repertory'] = $postData['repertory'] ? Tool::filter($postData['repertory'], true) : null;
            $data['sold'] = $postData['sold'] ? Tool::filter($postData['sold'], true) : null;
            $data['added'] = $postData['added'];
            $data['commend'] = $postData['commend'];
            $data['weixin'] = Tool::filter($postData['weixin'],true);
            $data['content'] = Tool::filter($postData['content']);
    		$data ['addtime'] = time();
            
            //1.ajax提交的处理
            $gid = intval($postData['goods_id']);
            $this->_handleAjaxAdd($data,$gid,$postData['images']);
            
            //2.AJAX异步添加过商品后提交商品信息时 视为[修改商品信息]
            if($gid > 0){
                $data['updatetime'] = time();
                if(isset($data['addtime'])){
                    unset($data['addtime']);
                }
                if ($db->editCommodity($gid,$data)){
            		Tool::setFlash('success', array('title'=>'添加成功','message'=>'成功添加商品'));
            		$this->redirect()->toRoute('commodity');
            	}
                exit(0);
            }
            
            //3.正常添加商品信息
            if ($goods_id = $db ->addCommodity($data)){
				$dbFile = new File($adapter);
				$dbFile->saveModuleImages(File::$m_goods,$goods_id,$this->user->id,$postData['images']);//添加上传的商品图片
            	Tool::setCookie('success', array('title'=>'添加成功','message'=>'成功添加商品'),time()+5);
            	$this->redirect()->toRoute('commodity');
            }
        }
        $userDB=new User($adapter);
        $viewData['userData']=$userDB->getUser($this->user->id); //验证商品数
        $viewData['count'] = $db->CommodityCount($this->user->domain,$this->user); //验证商品数
        $shopDB=new Shop($adapter);
        $viewData['shop']=$shopDB->shopAll($this->user->domain);
        $typeDB=new Type($adapter);
        //$viewData['type']=$typeDB->typeAll($this->user->domain,null,10,$this->user);
        //取得商品备选分类列表(树型结构显示)
        $viewData['select_items']=Tool::genDisplayTreeList($typeDB->getTypeTree($this->user->domain,10));
	   
	    //取得所有备选品牌信息
	    $dbBrand = new Brand($adapter);
	    $viewData['brands'] = $dbBrand->getAllBrands($this->user->domain);
	   
		$viewData['alipay']=isset($this->user->alipay)?1:0;
		$viewData['uploadParams'] = array(
		   	'_identify' => Tool::_getIdentify(),//用于Uploadify组件AJAX上传时传递Cookie信息
			'module_id' => File::$m_goods
	    );
        $viewData['asset']=array('js'=>array('/lib/commodity.js','/ueditor/ueditor.all.min.js','/ueditor/ueditor.config.js'));
        return $viewData;
    }

    /**
     * ajax请求提交商品信息的处理逻辑 
     * @param undefined $data
     * @param undefined $goods_id
     * @param undefined $images
     * 
     */
    private function _handleAjaxAdd($data,$goods_id = 0,$images = NULL)
    {
        $request = $this->getRequest();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        if($request->isXmlHttpRequest()){
            $db  = new Commodity($adapter);
            $dbFile = new File($adapter);
            
            if(empty($data['name'])){//没填写任何商品信息
                echo json_encode(array(
                    'req' => "error",
                    'msg' => "没有填写商品信息"
                ));
                exit(0);    
            }
            
            if($goods_id > 0){//修改
                $data['updatetime'] = time();
                if(isset($data['addtime'])){
                    unset($data['addtime']);
                }
                if($db->editCommodity($goods_id,$data)){
                    echo json_encode(array(
                        'req' => "ok",
                        'msg' => "成功修改商品",
                        'goods_id' => $goods_id
                    ));
                }
            }else{//新增
                if($id = $db->addCommodity($data)){
				    $dbFile->saveModuleImages(File::$m_goods,$id,$this->user->id,$images);//添加上传的商品图片
                    echo json_encode(array(
                        'req' => "ok",
                        'msg' => "成功添加商品",
                        'goods_id' => $id
                    ));
                }    
            }
            
            exit(0);
        }
    }
    
    
    
    function editAction ()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $viewData=array();
        $viewData['user']=$this->user;
        $id = $this->params()->fromQuery('id');
        if (empty($id)) $this->redirect()->toRoute('commodity');
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db  = new Commodity($adapter);
        $row=$db->getCommodity($id);
        
        if ($this->user->domain != $row['uid'] || ($this->user->power!=2 && $row['editor']!=$this->user->id)) $this->redirect()->toRoute('commodity');
        //门店管理员
        $userShop = array();
        if ($this->user->power==1)
        {
        	$userDB = new User($adapter);
        	$user=$userDB->getUser($this->user->id);
        	if ($user['shop'])
        	{
        		$userShop = json_decode($user['shop']);
        	}
        }
        $viewData['userShop'] = $userShop;
        
        $request = $this->getRequest();
        if ($request->isPost()) {
        	$postData = $request->getPost();
        	$data=array();
			
			//列表缩略图处理
			if($thumb = $this->_handleThumb()){
				$data['thumb'] = $thumb;
			}
			
			$data['freight'] = $this->_handleFreight($postData);//处理运费
            $data['shop'] = $postData['shop'];
            //$data['uid'] = $this->user->id;
			$data['brandID'] = $postData['brandID'];
            $data['cateID'] = $postData['cateID'];
            $data['name'] = Tool::filter($postData['wares'],true);
            $data['price'] = Tool::filter($postData['price'],true);
            $data['rebate'] = Tool::filter($postData['rebate'],true);
            $data['order'] = $postData['order'] ? Tool::filter($postData['order'], true) : null;
            $data['repertory'] = $postData['repertory'] ? Tool::filter($postData['repertory'], true) : null;
            $data['sold'] = $postData['sold'] ? Tool::filter($postData['sold'], true) : null;
            $data['added'] = $postData['added'];
            $data['commend'] = $postData['commend'];
            $data['weixin'] = Tool::filter($postData['weixin'],true);
            $data['content'] = Tool::filter($postData['content']);
    		$data['updatetime'] = time();
        	if ($db ->editCommodity($id,$data)){
				$this->_handleSpec($postData,$id);//保存商品规格/属性信息
        		Tool::setCookie('success', array('title'=>'编辑成功','message'=>'成功编辑商品'),time()+5);
        		$this->redirect()->toRoute('commodity');
        	}
        }
       
       $viewData['row']=$row;
       $shopDB=new Shop($adapter);
       $viewData['shop']=$shopDB->shopAll($this->user->domain);
       $typeDB=new Type($adapter);
       //$viewData['type']=$typeDB->typeAll($this->user->domain,NULL,0,$this->user);
	   
	   //取得商品备选分类列表(树型结构显示)
       $viewData['select_items']=Tool::genDisplayTreeList($typeDB->getTypeTree($this->user->domain,10));
	   
	   //取得商品所有图片文件
	   $dbFile = new File($adapter);
	   $viewData['files'] = $dbFile->getFiles(File::$m_goods,$id);
	   
	   //取得商品所有属性/规格参数
	   $viewData['specList'] = $this->_genSpecDisplayList($row->cateID,$db->getExistedSpec($id));
	   
	   //取得所有备选品牌信息
	   $dbBrand = new Brand($adapter);
	   $viewData['brands'] = $dbBrand->getAllBrands($this->user->domain);
	   
	   $viewData['uploadParams'] = array(
		   	'_identify' => Tool::_getIdentify(),//用于Uploadify组件AJAX上传时传递Cookie信息
			'module_id' => File::$m_goods,
			'target_id' => $id,
			'thumb_size' => 200
	   );
       $viewData['asset']=array('js'=>array('/lib/commodity.js','/ueditor/ueditor.all.min.js','/ueditor/ueditor.config.js'));
       return $viewData; 
    }
	
	/**
	 * 处理商品规格/属性相关的库存信息 
	 * @param undefined $postData
	 * 
	 */
	private function _handleSpec($postData,$goods_id = 0)
	{
		if(!isset($postData['spec']) || !is_array($postData['spec'])){
			return FALSE;
		}
		foreach($postData['spec'] as $item){
			$data[$item] = (int)$postData["spec_".$item];
		}
        $db  = new Commodity($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
		$db->saveSpecData($data,$goods_id);
	}
	
	/**
	 * 生成表单显示的输入框列表项 
	 * @param undefined $class_id
	 * 
	 */
	private function _genSpecDisplayList($class_id = 0,$existedSpec = NULL)
	{
		$dbSpec = new Attribute($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
	   	return $this->_genFormInputItems($dbSpec->getSpecListByClassId($class_id),$existedSpec);
	}
	
	private function _genFormInputItems($rows,$existedSpec = NULL,$setTitle = TRUE)
	{
		$html = "";
		if(!$rows){
			return $html;
		}
		if($setTitle){
			$html.="<ul class=\"spec\"><li class=\"lbl\">规格</li><li>库存量</li></ul>";	
		}
		foreach($rows as $r){
			$html.="<ul class=\"spec\"><li class=\"lbl\">".$r->name."</li><li>";
			$v = $this->_mapSpecVal($r->id,$existedSpec);
			if(count($r->subTree) == 0){
				$html.="<input type=\"text\" class=\"x-large\" name=\"spec_".$r->id."\" value=\"".$v."\"/>";
				$html.="<input type=\"hidden\" name=\"spec[]\" value=\"".$r->id."\" />";	
			}
			$html.="</li></ul>";
			if(count($r->subTree) > 0){
				$html.=$this->_genFormInputItems($r->subTree,$existedSpec,FALSE);
			}
		}
		return $html;	
	}
	
	
	private function _mapSpecVal($spec_id,$existedSpec)
	{
		if(isset($existedSpec[$spec_id])){
			return $existedSpec[$spec_id];
		}	
		return 0;
	}
	
	/**
	 * 处理商品运费 
	 * @param undefined $postData
	 * 
	 */
	private function _handleFreight($postData = NULL)
	{
		if(!isset($postData['feeby']) || !in_array($postData['feeby'],array('buyer','seller'))){
			return NULL;
		}
		
		return serialize(array(
			'feeby'	=> $postData['feeby'],//谁来承担运费 buyer-买家 seller-卖家
			'mail'	=> (int)$postData['mail'],//平邮
			'ems'	=> (int)$postData['ems'],//EMS
			'wuliu'	=> (int)$postData['wuliu']//物流公司
		));
	}
	
	/**
	 *AJAX异步方式上传各类图片文件公共方法 
	 * 
	 */
	public function ajaxUploadAction()
	{
		$this->user = Tool::getSession('auth','user',$_POST['_identify']);
		$request = $this->getRequest();
		$file=$request->getFiles ()->toArray();
		$postData = $request->getPost();
		
		$module_id = $this->_checkIsValidModule(intval($postData['module_id']));//上传文件所属模块
		$target_id = intval($postData['target_id']);//上传文件所属对象
		$thumb_size = intval($postData['thumb_size']);//返回的图片缩略图尺寸
		
		if(!$module_id){
			$file = NULL;//设为一个非法的文件上传操作
		}
        if ($file && is_array($file))
        {
			//上传文件并写入数据库
        	$thumb = File::doUpload($file,$this->user->id,array('gif','jpg','png'),$module_id);
        	if($thumb['file'])
        	{
				
				//添加商品时上传图片处理
				if(isset($postData['act']) && $postData['act'] == "add"){
					echo $thumb['file'];
					exit(0);
				}
				
				//修改商品时上传图片处理
				$fileInfo = array(
					'file_desc' => " - ",
					'path' => $thumb['file'],
					'filesize' => filesize(BASE_PATH.$thumb['file']),
					'is_img' => 1,
					'created_time' => time(),
					'module_id' => $module_id,
					'target_id' => $target_id,
					'owner_id' => $this->user->id
				);
				$dbFile = new File($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
				if($file_id = $dbFile->addFile($fileInfo)){
					echo json_encode(array(
						'req' => "ok",
						'msg' => "图片上传成功",
						'file_path' => File::getThumbFile($fileInfo['path'],$module_id,$thumb_size),
						'file_id' => $file_id
					));
					exit(0);
				}
			}
        }
		
		//添加商品时上传图片处理
		if(isset($postData['act']) && $postData['act'] == "add"){
			echo "error";
			exit(0);
		}
		
		//修改商品时上传图片处理
		echo json_encode(array(
			'req' => "error",
			'msg' => "图片上传失败,请稍候重试"
		));
		exit(0);
	}
	
	/**
	 * 检查被上传文件所在模块是否合法 
	 * @param undefined $module_id
	 * 
	 */
	private function _checkIsValidModule($module_id = NULL)
	{
		if(File::getImageSizeList($module_id) === FALSE){
			return FALSE;
		}
		return $module_id;
	}
	
	
	/**
	 * 商品封面缩略图上传处理 
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
	
    /**
     * @ todo 欢迎商品
     */
    public function welcomeAction()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $viewData=array();
        $success = Tool::getCookie('success');
        if ($success)
        {
        	$viewData['success']=json_decode($success);
        }
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new Commodity($adapter);
    	$request = $this->getRequest();
    	if ($request->isPost())
    	{
    		$postData = $request->getPost();
    		if ($postData['id'])
    		{
    			foreach ($postData['id'] as $val) {
    				$db->editCommodity($val, array('welcome'=>0));
    			}
    			Tool::setCookie('success', array('title'=>'操作成功','message'=>'取消欢迎商品成功'),time()+5);
    			$this->redirect()->toRoute('commodity',array('action'=>'welcome'));
    		}
    	}
    	
    	$viewData['user']=$this->user;
    	
    	$viewData['rows'] = $db->welcome($this->user->domain);
        //取出门店
        $shopRows = new Shop($adapter);
        $shopRows= $shopRows->userShop($this->user->domain);
        $shop=array();
        if ($shopRows)
        {
        	foreach ($shopRows as $val) {
        		$shop[$val['id']]=$val['shopname'];
        	}
        }
        $viewData['shop']=$shop;
        
        //取得所有分类
        $typeRows=new Type($adapter);
        $typeRows = $typeRows->typeAll($this->user->domain);
        $type=array();
        if ($typeRows)
        {
        	foreach ($typeRows as $val) {
        		$type[$val['id']]=$val['name'];
        	}
        }
        $viewData['type']=$type;
        $viewData['asset']=array('css'=>array('/lib/uploadify/uploadify.css'));
    	return $viewData;
    }
    
    /**
     * @todo 删除商品
     */
    public function deleteAction()
    {
    	if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
    	$id = (int)$this->params()->fromQuery('id');
    	if ($id)
    	{
    		$db = new Commodity($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
    		$row = $db->getCommodity($id);
    		if (($row['uid']==$this->user->domain && $this->user->power == 2) || $this->user->power == 3)
    		{
    			$db->delete($id);
    			echo '{"isok":true}';
    			exit();
    		}
    	}
    	echo '{"isok":false}';
    	exit();
    }
	
	/**
	 * AJAX异步删除图片 
	 * 
	 */
	public function ajaxRemoveFileAction()
	{
		$module_id = (int)$this->params()->fromQuery('module_id');
		$target_id = (int)$this->params()->fromQuery('target_id');
    	$file_id = (int)$this->params()->fromQuery('file_id');
    	if ($file_id)
    	{
    		$dbFile = new File($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
    		if($dbFile->delFileAndRecord($module_id,$target_id,$this->user->id,$file_id)){
				echo json_encode(array(
					'req' => "ok",
					'msg' => "图片删除成功"
				));
				exit(0);
			}
    		
    	}
    	echo json_encode(array(
			'req' => "error",
			'msg' => "图片删除失败,可能不存在或已被删除"
		));
		exit(0);
	}
    
}