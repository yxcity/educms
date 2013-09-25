<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use module\Application\src\Model\Tool;
use Admin\Model\Commodity;
use Admin\Model\Shop;
use Zend\Mvc\Controller\AbstractActionController;

class BusinessController extends AbstractActionController
{
    function indexAction()
    {
    	$domain = Tool::domain();
        $ref = $this->params()->fromQuery('ref');
    	$viewData=array();
    	$db = new Shop($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
    	$res = $db->shopAll($domain);
    	$count = $res->count();
    	$viewData['pageCount']=ceil($count/5);
    	$viewData['ref']=$ref;
    	$viewData['rows']=$db->userShop($domain);
        $view = new ViewModel($viewData);
    	$view->setTerminal(true);
    	return $view;
    }
    
    function mapAction()
    {
    	$id = (int)$this->params()->fromQuery('id');
    	$domain = Tool::domain();
    	if (empty($id)) $this->redirect()->toRoute('user',array('action'=>'error'));
    	$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
    	$viewData = array();
    	$db = new Shop($adapter);
    	$row  = $db->getShop($id);
    	if (! $row || $row['uid']!=$domain) $this->redirect()->toRoute('user',array('action'=>'error'));
    	$viewData['row']=$row;
    	$viewModel = new ViewModel($viewData);
    	$viewModel->setTerminal(true);
    	return $viewModel;
    }
    
    function moreAction()
    {
        $domain = Tool::domain();
        $page = $this->params()->fromQuery('page');
    	$db = new Shop($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
    	$rows=$db->userShop($domain,5,$page);
    	$html ='';
    	if ($rows)
    	{
    	    foreach ($rows as $val) {
    			$html.="<li>
            			<a href=\"/stores?id={$val['id']}\">
            				<img src=\"{$val['thumb']}\" />
            				<h3>{$val['shopname']}</h3>
            			</a>
            			<p class=\"i_shop_phone\">
            				<a href=\"tel:{$val['tel']}\">{$val['tel']}</a>
            			</p>
            			<p class=\"i_shop_address\">
            				<a href=\"/business/map?id={$val['id']}\">地址：{$val['address']}</a>
            			</p>
                        <span class=\"gt\"></span></li>";
    		}
    	}
    	echo $html;
    	exit();
    }
}