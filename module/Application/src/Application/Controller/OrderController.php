<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\Indent;
use module\Application\src\Model\Tool;
use module\Application\src\Model\Alipay;

class OrderController extends AbstractActionController
{

    function __construct ()
    {}

    function indexAction ()
    {
        $uname = Tool::getCookie('uname');
        $uid = isset($uname['username'])?$uname['username']:null;
        $db = new Indent($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $rows = $db->indentAll($uid);
        $viewModel = new ViewModel(array('rows'=>$rows));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    function payAction()
    {
    	$id = $this->params()->fromQuery('id');
    	$db = new Indent($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
    	$row=$db->getIndent($id);
    	$payData=array('serialnumber'=>$row['serialnumber'],'title'=>$row['name'],'sum'=>$row['sum'],'body'=>$row['name'],'url'=>'http://weixin.youtitle.com/index/alipay');
    	$alipay = new Alipay();
    	$alipay->pay($payData);
    	exit();
    }

}