<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Keyword;
use Admin\Model\User;

class KeywordController extends AbstractActionController
{

    private $user;

    function __construct ()
    {
        $this->user = Tool::getSession('auth', 'user');
    }

    function indexAction ()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $viewData = array();
        $viewData['user'] = $this->user;
        $keywords = $this->params()->fromQuery('key',null);
        $request = $this->getRequest();
        if ($request->isPost())
        {
    	    $postData = $request->getPost();
    		$keywords = Tool::filter($postData['keywords']);
        }
        $viewData['keywords'] = $keywords;
        $page = $this->params('page',1);
        $db = new Keyword($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $viewData['rows']=$db->keyList('text',$page,$this->user->domain,'20',$keywords,$this->user);
        return $viewData;
    }

    function imagesAction ()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $page = $this->params('page',1);
        $viewData = array();
        $viewData['user'] = $this->user;
        $db = new Keyword($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $viewData['rows']=$db->keyList('image', $page,$this->user->domain);
        return $viewData;
    }

    function locationAction ()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $page = $this->params('page',1);
        $viewData = array();
        $viewData['user'] = $this->user;
        $db = new Keyword($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $viewData['rows']=$db->keyList('location', $page,$this->user->domain);
        return $viewData;
    }

    function linkAction ()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $page = $this->params('page',1);
        $viewData = array();
        $viewData['user'] = $this->user;
        $db = new Keyword($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $rows=$db->keyList('link', $page,$this->user->domain);
        $viewData['rows']=$rows;
        return $viewData;
    }
}