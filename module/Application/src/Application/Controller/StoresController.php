<?php
namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use module\Application\src\Model\Tool;
use Admin\Model\Shop;
use Admin\Model\Commodity;

class StoresController extends AbstractActionController
{

    function indexAction ()
    {
        $id = (int)$this->params()->fromQuery('id');
        $s = $this->params()->fromQuery('s');
        Tool::openid($s);
        if (empty($id)) $this->redirect()->toRoute('user',array('action'=>'error'));
        $domain = Tool::domain();
        $viewData=array();
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        //取得门店信息
        $db = new Shop($adapter);
        $row = $db->getShop($id);
        if (!$row || $row['uid']!=$domain) $this->redirect()->toRoute('user',array('action'=>'error'));
        $viewData['row']=$row;
        //取得门店下商品
        $co= new Commodity($adapter);
        $c=$co->shopCount($id,true);
        $viewData['pageCount'] = ceil($c->count()/5);
        $viewData['rows'] = $co->proList(5, 1, $domain,$id,null,null,null,true);
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    function listAction ()
    {
        $id = (int)$this->params()->fromQuery('id');
        if (empty($id)) $this->redirect()->toUrl('user/error?msgid=1005');
        $domain = Tool::domain();
        $viewData=array();
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        //取得门店信息
        $db = new Shop($adapter);
    	$res = $db->shopAll($domain);
    	$countshop = $res->count();
        $row = $db->getShop($id);
        if (!$row || $row['uid']!=$domain) $this->redirect()->toRoute('user',array('action'=>'error'));
        $viewData['row']=$row;

        $viewData['countshop']=$countshop;
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    function moreAction()
    {
        $domain = Tool::domain();
        $id = $this->params()->fromQuery('id');
        $keywords = $this->params()->fromQuery('keywords');
        $page = $this->params()->fromQuery('page',2);
        if (empty($id)) $this->redirect()->toRoute('user',array('action'=>'error'));
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $co= new Commodity($adapter);
        $rows = $co->proList(5, $page, $domain,null,$id,$keywords,null,true);
        $html ="";
        if ($rows)
        {
            foreach ($rows as $val) {
                if($keywords){
                  $val['name']=str_replace($keywords, '<font color="red">'.$keywords.'</span>', $val['name']);
        		 }
        		$html.="<li><a href=\"/product?id={$val['id']}\"><img src=\"{$val['thumb']}\" /><h3>{$val['name']}</h3><p class=\"tui_price ts2\"><strong>￥{$val['rebate']}</strong><del>￥{$val['price']}</del><span><cite>{$val['sales']}</cite>人购买</span></p></a></li>";
            }
        }
        echo $html;
        exit();
    }
}