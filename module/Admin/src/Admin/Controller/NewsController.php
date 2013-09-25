<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\News;
use Admin\Model\File;

/**
 * @todo 消息素材类
 * @author
 * @version
 */
class NewsController extends AbstractActionController
{

    private $user;
    function __construct ()
    {
        $this->user = Tool::getSession('auth','user');
        if (!isset($this->user->domain)){
            $this->forbidden ();
            exit;
        }
    }

    function indexAction ()
    {
        $viewData = array();
        $db =  new News($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));        
        //keywords
        $request = $this->getRequest();
        $keywords = $this->params()->fromQuery('key',null);
        if ($request->isPost())
        {
    	    $postData = $request->getPost();
    		$keywords = Tool::filter($postData['keywords']);
        }
        //
        $viewData['user']=$this->user;
        $page=$this->params('page');
        $res =$db->getGroupdNewsList($page, $this->user,'20',$keywords);
        $viewData['keywords']=$keywords;
        $viewData['rows'] = $res[0];
        $viewData['list'] = $res[1];
        $viewData['action']=$this->params()->fromQuery('action');
        $viewData['asset']=array('css'=>array('/lib/uploadify/uploadify.css'));
        return $viewData;
    }

    /*创建消息接口*/
    function createAction ()
    {
        if(!$this->getRequest()->isPost()) {
            echo '{"code":"error","msg":"post method only!"}';
            exit;
        }
        /*多消息时可能创建只有type字段的空消息，参数校验要区分情况进行*/
        //$this->check_params(array("title","description","pic_url","type","pid"));
        $post_data = $this->getRequest()->getPost();
        $data=array();
        $data['uid'] = $this->user->domain;
        $data['title'] = $post_data['title'];
        $data['description'] = $post_data['description'];
        $data['url'] = $post_data['url'];
        $data['pic_url'] = $post_data['pic_url'];
        $data['type'] = $post_data['type'];
        $data['pid'] = $post_data['pid'];
        $data['content'] = $post_data['content'];
        $data['create_time'] = date("Y-m-d H:i:s");
        $news_obj = new News($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        if ($id = $news_obj ->addNews($data)){
            $data['id'] = $id;
            echo '{"code":"ok","data":'.json_encode($data).'}';
        }else{
            echo '{"code":"error"}';
        }
        exit;
        
    }
    
    private  function forbidden()
    {
        //if (!isset($this->user->domain) || $this->user->power < 1) $this->redirect()->toUrl('/login');
        $this->getResponse()
                    ->setStatusCode(403)
                    ->setContent("Forbidden,<a href=/login>Back</a>")
                    ->send();
    }
    
    function singleAction ()
    {
        $request = $this->getRequest();
        $db  = new News($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        if($this->params()->fromQuery('action') == 'edit'){
            $id = (int)$this->params()->fromQuery('id');
            $viewData['news'] = null;
            if($id >0 && $news = $db->getNews($id)){
                //var_dump($news);
                //die;
                if($news['uid'] == $this->user->domain)
                {
                    $viewData['news'] = $news;
                }
            }
        }else{
            $news = array(
                    'id'=>null,
                    'type'=>0,
                    'title'=>null,
                    'pic_url'=>null,
                    'description'=>null,
                    'url'=>null,
                    'content'=>null,
                    'pic_url'=>null
                );
                $viewData['news'] = $news;
        }
        $viewData['user']=$this->user;
        return $viewData;;
    }
    
    private function check_params($rules)
    {
        $required = array();
        $miss_param = FALSE;
        $post_params = $this->getRequest()->getPost();
        foreach($rules as $key=>$rule){
            if(is_array($rule)){
                if(((!isset($rule['method']) || strtolower($rule['method'])=='post') && ($post_params===NULL  ||!isset($post_params[$key]))) ||
                        (strtolower($rule['method'])=='query' && $this->params()->fromQuery($key)===NULL)){
                    array_push($required,$key);
                    $miss_param = TRUE;
                }
            }elseif (is_string($rule)){
                if(  (!$post_params  ||!isset($post_params[$rule])) && $this->params()->fromQuery($rule) ===NULL){
                        array_push($required,$rule);
                        $miss_param = TRUE;
                }
            }
        }

        if($miss_param){
            echo '{"code":"error","msg":"' . implode(',', $required) . ' required!"}';
            exit;
        }
    }
    
    function multiAction ()
    {
        $request = $this->getRequest();
        $db  = new News($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        if($this->params()->fromQuery('action') == 'edit'){
            $this->check_params(array("id"=>array('method'=>'query')));
            $id = (int)$this->params()->fromQuery('id');
            $viewData['news'] = null;
            if($id >0 && $news = $db->getNews($id)){
                if($news['uid'] == $this->user->domain)
                {
                    $viewData['news'] = $news;
                }
            }
        }else{
            /*添加页面插入一条封面的空记录*/
            $viewData['news'] = $db->getBlankNews($this->user->domain,1);
        }
        $viewData['user']=$this->user;
        return $viewData;;
    }
    
    function autoreplyAction()
    {
        $request = $this->getRequest();
        $db  = new \Admin\Model\Autoreply($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $viewData['rules'] = $db->getAllRules($this->user->domain);
        $viewData['user']=$this->user;
        return $viewData;
    }
	
    /*ajax handle*/
    function editAction ()
    {
        $request = $this->getRequest();
        $this->check_params(array("id"=>array("method"=>'query'),"title","description"));
        $id = (int)$this->params()->fromQuery('id');
        $db  = new News($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $row=$db->getNews($id);
        if (!isset($this->user->domain) || $this->user->domain != $row['uid']){
            echo '{"code":"error","msg":"forbidden"}';
            exit;
        }
        
        $data = array();
        $post_data = $request->getPost();
        $data['title'] = $post_data['title'];
        $data['description'] = $post_data['description'];
        $data['pic_url'] = $post_data['pic_url'];
        
        if(isset($post_data['content']))$data['content'] = $post_data['content'];
        if(isset($post_data['url']))$data['url'] = $post_data['url'];
        if(isset($post_data['pid']))$data['pid'] = $post_data['pid'];
        if(isset($post_data['draft']))$data['draft'] = $post_data['draft'];
        if(isset($post_data['hide']))$data['hide'] = $post_data['hide'];
        if ($db ->editNews($id,$data)){
            echo json_encode(array(
                'code'=>'ok',
                'data'=>$db->getNews($id)
                ));
        }else{
            echo '{"code":"error","msg":"update news error!"}';
        }
        exit;
    }
  
    
    /**
     * @todo 删除消息
     */
    public function deleteAction()
    {
    	$this->check_params(array("id"=>array("method"=>'query')));
        $id = (int)$this->params()->fromQuery('id');
    	if ($id)
    	{
    		$db = new News($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
    		$row = $db->getNews($id);
    		if ($row['uid']==$this->user->domain)
    		{
                        $db->delete($id);
                        @unlink(getcwd()."/public" . $row['pic_url']);
    			echo '{"code":"ok"}';
    			exit();
    		}
    	}
    	echo '{"code":"error"}';
    	exit();
    }
    
     /**
     * @todo 上传图片
     */
    public function uploadAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
        	$post_data = $request->getPost();
        	$data=array();
        	$file=$request->getFiles ()->toArray();
                $jsonObj = array();
        	if ($file && is_array($file)){
                $thumb = File::doUpload($file,$this->user->id,array('gif','jpg','png'),NULL,array('thumb_size'=>400));//md5(id)方式保存文件
        		//$thumb = Tool::uploadfile($file,null,null,array('gif','jpg','png'));
                        if ($thumb['res']){
        			$jsonObj['url']= $thumb['file'];
        		}else{
                               $jsonObj['error']="upload error";
                        }
        	}else{
                    $jsonObj['error']="param error";
                }
                //echo json_encode(array("files"=>[$jsonObj]));
                echo json_encode($jsonObj);
                exit;
        }else{
            $layout = $this->layout();
            $layout->setTemplate('layout/upload');
        }
        return;
    }
    
    function listAction ()
    {
        $request = $this->getRequest();
        if ( $request->isPost()) {
            exit;
        }
     
        /*无权限*/
        if (!isset($this->user->domain)){
            $this->forbidden();
            exit; 
        }
        
        $db =  new News($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $page=1;
        if($page){
            $page = (int)$this->params()->fromQuery('page');
        }
        
        $page_size = 10;
        if($this->params()->fromQuery('page_size')){
            $page_size = (int)$this->params()->fromQuery('page_size');
        }
        
        $page_inator = $db->getNewsList($page, $this->user,$page_size);
        if($page_inator){
            $json_obj = array("code"=>"ok");
            if($page_inator->getCurrentPageNumber() == $page){
                $json_obj["data"]=$page_inator->getCurrentItems()->toArray();
                $json_obj["total"]=$page_inator->getTotalItemCount();
            }else{
                $json_obj["data"]=null;
            }
        }else{
            $json_obj = array(
                "code"=>"error"
                );
        }
        echo json_encode($json_obj);
        exit;
    }
    
}