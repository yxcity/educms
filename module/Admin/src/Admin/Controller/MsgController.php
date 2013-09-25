<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Msg;

class MsgController extends AbstractActionController
{

    private $user;
    private $adapter;

    function __construct ()
    {
        $this->user = Tool::getSession('auth', 'user');
    }
    
    function indexAction(){
        $viewData=$this->init();
        $page = $this->params('page',1);
        $db = new Msg($this->adapter);
        $viewData['rows']= $db->msgList($page);
    	return $viewData;
    }
    
    function createAction(){
        $viewData=$this->init();
        $request=$this->getRequest();
        if ($request->isPost())
        {
        	$postData = $request->getPost();
        	$data['code']=$postData['code'];
        	$data['msg']=$postData['msg'];
        	$db=new Msg($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        	$res = $db->insert($data);
        	if ($res)
        	{
        	    $db->writeFile();
        	    Tool::setCookie('success', array('title'=>'操作成功','message'=>'添加错误提示代码成功'),time()+3);
        	    $this->redirect()->toRoute('msg');
        	}
        }
        return $viewData;
    }
    
    function editAction()
    {
        $viewData=$this->init();
        $request=$this->getRequest();
        $id = (int)$this->params()->fromQuery('id');
        if (!$id) $this->redirect()->toRoute('msg');
        $db=new Msg($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $row = $db->getOne($id);
        if (!$row) $this->redirect()->toRoute('msg');
        if ($request->isPost() && $id)
        {
        	$postData = $request->getPost();
        	$data['code']=$postData['code'];
        	$data['msg']=$postData['msg'];
        	$res = $db->update($data, $id);
        	if ($res)
        	{
        	    $db->writeFile();
        	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>'编辑错误提示代码成功'),time()+3);
        		$this->redirect()->toRoute('msg');
        	}
        }
        $viewData['row']=$row;
        return $viewData;
    }
    
    function init()
    {
        if ($this->user->power<3) $this->redirect()->toUrl('error');
        $this->adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $viewData=array();
        $viewData['user']=$this->user;
        $success = Tool::getCookie('success');
        if ($success)
        {
        	$viewData['success']=json_decode($success);
        }
        return $viewData;
    }
    
    
}