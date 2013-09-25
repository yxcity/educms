<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\User;

class AnswersController extends AbstractActionController
{

    private $user;
    private $viewData;

    function __construct ()
    {
    	$this->user = Tool::getSession('auth', 'user');
    	$this->viewData['user'] = $this->user;
    }
    /**
     * @todo 提问里列表
     * (non-PHPdoc)
     * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
     */
    function indexAction()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
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
        $page = $this->params('page');
        $db = new User($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $this->viewData['rows']=$db->answers($page, $this->user, '20', $keywords);
        $this->viewData['keywords']=$keywords;
        return $this->viewData;
    }
    /**
     * @todo 提交问题
     * @return Ambigous <mixed, \Admin\Model\Ambigous, \Zend\Paginator\Paginator, \module\Application\src\Model\Ambigous, boolean, unknown, multitype:, ArrayObject, NULL, \ArrayObject>
     */
    function createAction()
    {
    	if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $fid = intval($this->params()->fromQuery('fid'));
    	$request = $this->getRequest();
    	if ($request->isPost())
    	{
    		$data=array();
    	    $postData = $request->getPost();
    		$data ['uid']=$this->user->id;
    		if($fid) $data ['fid']=$fid;
    		$data ['domain']=$this->user->domain;
    		$data ['realname']=Tool::filter($postData['realname']);
    		$data ['contact'] =Tool::filter($postData['contact']);
    		$data ['ask'] = Tool::filter($postData['content']);
    		$data ['askTime'] = time();
    		$db = new User($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
    		$db->addAsk($data);
    		Tool::setCookie('success', array('title'=>'提交成功','message'=>'谢谢你的支持，你的问题提交成功，我们会尽快处理并回复'),time()+5);
    		$this->redirect()->toRoute('answers');
    	}
    	$this->viewData['asset']=array('js'=>array('/lib/answers.js'));
        $this->viewData['fid']=$fid;
        return $this->viewData;
    }
    /**
     * @todo 回答问题
     * @return Ambigous <mixed, \Admin\Model\Ambigous, \Zend\Paginator\Paginator, \module\Application\src\Model\Ambigous, boolean, unknown, multitype:, ArrayObject, NULL, \ArrayObject>
     */
    function answerAction()
    {
        $id = $this->params()->fromQuery('id');
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new User($adapter);
        $row = $db->getAnswers($id);
        if ($this->user->power !=3 || !$row) $this->redirect()->toRoute('answers');
        $request = $this->getRequest();
        if ($request->isPost())
        {
        	$data=array();
            $postData=$request->getPost();
        	$data['answer']=Tool::filter($postData['answer']);
        	$data['answerTime']=time();
        	$db->editAnswers($id,$data);
        	Tool::setCookie('success', array('title'=>'提交成功','message'=>'成功回复问题'),time()+5);
        	$this->redirect()->toRoute('answers');
        }
        $this->viewData['row']=$row;
        return $this->viewData;
    }
    /**
     * @todo 查看问题
     * @return Ambigous <mixed, \Admin\Model\Ambigous, \Zend\Paginator\Paginator, \module\Application\src\Model\Ambigous, boolean, unknown, multitype:, ArrayObject, NULL, \ArrayObject>
     */
    function viewAction()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $id = $this->params()->fromQuery('id');
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new User($adapter);
        $row = $db->getAnswers($id);
        if ((!$row || $row['domain'] != $this->user->domain) && $this->user->power < 3) $this->redirect()->toRoute('answers');
        $this->viewData['row']=$row;
        return $this->viewData;
    }
    
    
    
}