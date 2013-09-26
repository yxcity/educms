<?php
namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\User;
use Admin\Model\Role;
use library\Helper\HCommon;

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
		$domain = HCommon::getDomain();
		$url = "http://login.{$domain}";
		$this->redirect()->toUrl($url);
	}
	
	public function logoutAction()
	{
		HCommon::delCache(HCommon::_getIdentify("auth"));
		$domain = HCommon::getDomain();
		setcookie("_identify",NULL,time()-100,"/",$domain);
		setcookie("_this_a",NULL,time()-100,"/");
		setcookie("_this_item",NULL,time()-100,"/");//ǥ�������˵�Cookie
		$url = "http://login.{$domain}";
        $this->redirect()->toUrl($url);
	}
}
