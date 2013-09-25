<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\User;
use Admin\Model\Role;
use module\Application\src\Model\Tool;
class IndexController extends AbstractActionController
{
	public function indexAction()
	{
	    echo $this->params('domain');
	    $view =  new ViewModel();
	    $view->setTerminal(200);
	    return $view;
	}
	
	public function authAction()
	{
		$domain = Tool::getDomain();
		$url = "http://login.{$domain}";
		$this->redirect()->toUrl($url);
	}
	
	public function logoutAction()
	{
		Tool::delCache(Tool::_getIdentify("auth"));
		$domain = Tool::getDomain();
		setcookie("_identify",NULL,time()-100,"/",$domain);
		setcookie("_this_a",NULL,time()-100,"/");
		setcookie("_this_item",NULL,time()-100,"/");//Çå³ýµ¼º½²Ëµ¥Cookie
		$url = "http://login.{$domain}";
        $this->redirect()->toUrl($url);
	}
}
