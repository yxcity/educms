<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Autoreply;
use Admin\Model\User;
use Admin\Model\News;

/**
 * @todo 商品管理类
 * @author
 * @version
 */
class AutoreplyController extends AbstractActionController
{

    private $user;

    function __construct ()
    {
        $this->user = Tool::getSession('auth','user');
    }
    
    /**
     *
     * @todo 添加规则
     * @param 只接受POST请求
     * @return Ambigous <boolean, number>
     */
    function addAction()
    {
        $request = $this->getRequest();
        if (!isset($this->user->domain) || !$request->isPost()) 
            exit;
        $this->check_params(array('name'));
        $post_data = $request->getPost();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $reply_obj  = new Autoreply($adapter);
        $data=array();
        $data['create_time'] = date("Y-m-d H:i:s");  
        $data['name'] = $post_data['name'];/*lwq:统一校验客户端提交数据*/
        $data['uid'] = $this->user->domain;
        $json_obj = array();
        if ($id = $reply_obj ->addRule($data)){
                $json_obj['code'] = 'ok';
                $json_obj['data'] = array(
                    'id' => $id,
                    'name' => $data['name']
                );
        }  else {
            $json_obj['code'] = 'error';
        }
              
        echo json_encode($json_obj);
        exit;
    }
      
    /**
     *
     * @todo 添加关键字
     * @param 只接受POST请求
     * @return Ambigous <boolean, number>
     */
    function addkeywordAction()
    {
        $request = $this->getRequest();
        if (!isset($this->user->domain) || !$request->isPost()) 
            exit;
        $this->check_params(array('keyword','type','autoreply_id'));
        $post_data = $request->getPost();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');/*lwq:controler基本上都需要操作数据，可以统一实例化作为类成员以复用*/
        $reply_obj  = new Autoreply($adapter);
        $data=array();
        $data['create_time'] = date("Y-m-d H:i:s");  
        $data['keyword'] = $post_data['keyword'];/*lwq:统一校验客户端提交数据*/
        $data['autoreply_id'] = $post_data['autoreply_id'];
        $data['type'] = $post_data['type'];/*匹配类型*/
        $row=$reply_obj->getRule($post_data['autoreply_id']);
        /*无权限*/
        if ($this->user->domain != $row['uid'] && $this->user->power<3){
            $this->forbidden();
            exit; 
        }
        $json_obj = array();
        if ($id = $reply_obj ->addKeyword($data)){
                $json_obj['code'] = 'ok';
                $json_obj['data'] = array(
                    'id' => $id,
                    'keyword' => $data['keyword'],
                    'autoreply_id'=>$data['autoreply_id'],
                    'type'=>$data['type']
                );
        }  else {
            $json_obj['code'] = 'error';
        }
              
        echo json_encode($json_obj);
        exit;
    }
    
    /**
     *
     * @todo 添加文本回复消息
     * @param 只接受POST请求
     * @return Ambigous <boolean, number>
     */
    function addreplyAction()
    {
        $request = $this->getRequest();
        if (!isset($this->user->domain) || !$request->isPost()) 
            exit;
        
        $post_data = $request->getPost();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');/*lwq:controler基本上都需要操作数据，可以统一实例化作为类成员以复用*/
        $reply_obj  = new Autoreply($adapter);
        $row=$reply_obj->getRule($post_data['autoreply_id']);
        /*无权限*/
        if ($this->user->domain != $row['uid'] && $this->user->power<3){
            $this->forbidden();
            exit; 
        }
        $news_id = null;
        $news_data =  null;
        $news_obj  = new News($adapter);
        if($post_data['type'] == 2){/*新增文本消息，图文消息只绑定*/
            $this->check_params(array('title','type','description'));
            $news_data=array();
            $news_data['type'] = $post_data['type'];/*匹配类型*/
            $news_data['uid'] = $this->user->domain;
            $news_data['title'] = $post_data['title'];
            $news_data['description'] = $post_data['description'];
            $news_data['create_time'] = date("Y-m-d H:i:s");
            if(!($news_id = $news_obj->addNews($news_data))){
                echo '{"code":"error","msg":"add news error."}';
                exit;
            }
        }else{
            $this->check_params(array('news_id'));
            $news_id = $post_data['news_id'];
            $news_data = $news_obj->getNews($news_id);
            /*校验权限*/
            if( $news_data['uid'] != $this->user->domain){
                $this->forbidden();
                exit;
            }
        }
        
        $reply_data=array();
        $reply_data['autoreply_id'] = $post_data['autoreply_id'];
        $reply_data['news_id'] = $news_id;
        $reply_data['create_time'] = $news_data['create_time'];
        $json_obj = array();
        if ($id = $reply_obj ->addReply($reply_data)){
                $json_obj['code'] = 'ok';
                $json_obj['data'] = $reply_data;
                $json_obj['data']['type'] = $news_data['type'];
                $json_obj['data']['id'] = $id; 
                $json_obj['data']['title'] = $news_data['title'];
                $json_obj['data']['news'] = $news_data;
                $json_obj['data']['description'] = $news_data['description'];
        }  else {
            $json_obj['code'] = 'error';
        }
              
        echo json_encode($json_obj);
        exit;
    }
    
    /**
     *
     * @todo 编辑规则的关键字
     * @param 只接受POST请求
     * @return Ambigous <boolean, number>
     */
    function editkeywordAction ()
    {
        $request = $this->getRequest();
        if ( !$request->isPost() || !isset($this->user->domain)) {
            exit;
        }
     
        $post_data = $request->getPost();
        $this->check_params(array('autoreply_id','keyword','id','type'));
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db  = new Autoreply($adapter);
        $row=$db->getRule($post_data['autoreply_id']);
        /*无权限*/
        if ($this->user->domain != $row['uid'] && $this->user->power<3){
            $this->forbidden();
            exit; 
        }
        
        $data=array();
        $data['keyword'] = $post_data['keyword'];/*lwq:统一校验客户端提交数据*/
        $data['autoreply_id'] = $post_data['autoreply_id'];
        $data['id'] = $post_data['id'];
        $data['type'] = $post_data['type'];/*匹配类型*/
        
        if ($db ->editKeyword($post_data['id'],$data)){
            echo '{"code":"ok"}';
        }else{
            echo '{"code":"error"}';
        }
        exit;
    }
    
        /**
     *
     * @todo 编辑规则的回复
     * @param 只接受POST请求
     * @return Ambigous <boolean, number>
     */
    function editreplyAction ()
    {
        $request = $this->getRequest();
        if ( !$request->isPost() || !isset($this->user->domain)) {
            exit;
        }
     
        $post_data = $request->getPost();
        $this->check_params(array('autoreply_id','id'));
        /*校验修改回复规则权限*/
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $reply_obj  = new Autoreply($adapter);
        $row=$reply_obj->getRule($post_data['autoreply_id']);
        if ($this->user->domain != $row['uid'] ){
            $this->forbidden();
            exit; 
        }
        
        /*获取消息ID*/
        if(!(isset($post_data['id']) && $reply_item = $reply_obj->getRuleReply($post_data['id']))){
            echo '{"code":"error","msg":"reply item match error."}';
            exit;
        }
        
        /*文本回复，直接修改消息*/
        if((int)$reply_item['type'] == 2){
            $this->check_params(array('title','description'));
            $news_data=array();
            $news_data['title'] = $post_data['title'];
            $news_data['description'] = $post_data['description'];
            $news_obj  = new News($adapter);
            if(!$news_obj->editNews($reply_item['news_id'],$news_data)){
                echo '{"code":"error","msg":"update news error."}';
                exit;
            }
        }else{/*图文消息只能更换图文ID*/
            $this->check_params(array('news_id','id'));
            $reply_data=array();
            $reply_data['news_id'] = $post_data['news_id'];
            if(!$reply_obj->editReply($reply_data['id'],$post_data,$post_data['autoreply_id'])){
                echo '{"code":"error","msg":"update reply error."}';
                exit;
            }
        }
        echo '{"code":"ok"}';
        exit;
    }
    
    function editAction ()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $request = $this->getRequest();
        $this->check_params(array('id'=>array('method'=>'query'),'name'));
        $id = $this->params()->fromQuery('id');
        if (empty($id) || !$request->isPost()) {
            $this->redirect()->toRoute('/news');
        }
     
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db  = new Autoreply($adapter);
        $row=$db->getRule($id);
        if ($this->user->domain != $row['uid'] && $this->user->power<3) $this->redirect()->toRoute('autoreply');
	
        $post_data = $request->getPost();
        $data=array();
        $data['name'] = $post_data['name'];
        if ($db ->editRule($id,$data)){
            echo '{"code":"ok"}';
        }else{
            echo '{"code":"error"}';
        }
        exit;
    }
   
    /**
     * @todo 删除规则
     */
    public function deleteAction()
    {
    	$request = $this->getRequest();
        if ( !$request->isPost() || !isset($this->user->domain)) {
            exit;
        }
        $this->check_params(array('id'));
        $post_data = $request->getPost();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db  = new Autoreply($adapter);
        $row=$db->getRule($post_data['id']);
        /*无权限*/
        if ($this->user->domain != $row['uid'] && $this->user->power<3){
            $this->forbidden();
            exit; 
        }
        if($db->delete($post_data['id'])){
    		echo '{"code":"ok"}';
        }else{
            echo '{"code":"error"}';
        }
    	exit();
    }
    
    /**
     * @todo 删除关键字
     */
    public function delkeywordAction()
    {
        $request = $this->getRequest();
        if ( !$request->isPost() || !isset($this->user->domain)) {
            exit;
        }
        $this->check_params(array('autoreply_id','id'));
        $post_data = $request->getPost();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db  = new Autoreply($adapter);
        $row=$db->getRule($post_data['autoreply_id']);
        /*无权限*/
        if ($this->user->domain != $row['uid'] && $this->user->power<3){
            $this->forbidden();
            exit; 
        }
        if($db->delkeyword($post_data['id'])){
    		echo '{"code":"ok"}';
        }else{
            echo '{"code":"error"}';
        }
    	exit();
    }
    
        /**
     * @todo 删除关键字
     */
    public function delreplyAction()
    {
        $request = $this->getRequest();
        if ( !$request->isPost() || !isset($this->user->domain)) {
            exit;
        }
        $this->check_params(array("autoreply_id","id"));
        $post_data = $request->getPost();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db  = new Autoreply($adapter);
        $row=$db->getRule($post_data['autoreply_id']);
        /*无权限*/
        if ($this->user->domain != $row['uid'] && $this->user->power<3){
            $this->forbidden();
            exit; 
        }
        
        /*lwq:增加校验id是否属于autoreply_id，避免误删*/
        if($db->delreply($post_data['id'],$post_data['autoreply_id'])){
    		echo '{"code":"ok"}';
        }else{
            echo '{"code":"error"}';
        }
    	exit();
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
            die;
        }
    }
    
}