<?php

namespace Application\Controller;

use Zend\View\Model\ViewModel;
use module\Application\src\Model\Tool;
use Admin\Model\Commodity;
use Admin\Model\Attribute;
use Admin\Model\Indent;
use Admin\Model\User;
use Admin\Model\Shop;
use Admin\Model\File;
use Zend\Mvc\Controller\AbstractActionController;

class ProductController extends AbstractActionController {

    private $member;

    function __construct() {
        $this->member = json_decode(Tool::getCookie('member'));
    }

    function indexAction() {
        $id = $this->params()->fromQuery('id');
        $s = $this->params()->fromQuery('s');
        Tool::openid($s);
        if (!$id)
            $this->redirect()->toRoute('user', array('action' => 'error'));
        $domain = Tool::domain();
        $viewData = array();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        // 取得商品信息
        $db = new Commodity($adapter);
        $row = $db->getCommodity($id);

        //取得商品图片
        $dbFile = new File($adapter);
        $row['images'] = $dbFile->getFiles(File::$m_goods, $row->id);

		//取得规格信息
		$viewData['specList'] = $this->_genSpecDisplayList($row->cateID,$db->getExistedSpec($row->id));
		
        $db->addclick($id);
        //$countcommodity = $row->count();
        if (!$row || $row['uid'] != $domain)
            $this->redirect()->toRoute('user', array('action' => 'error'));
        $viewData['row'] = $row;
        // 取得门店信息
        //$shop = new Shop($adapter);
        //$viewData['shop'] = $shop->getShop($row['shop']);
        $shop = new Shop($adapter);
        $res = $shop->shopAll($domain);
        $countshop = $res->count();
        //取得其他同类商品
        $rows = $db->proList(5, 1, $domain, $row['shop'], $row['cateID'], null, null, true);
        $viewData['rows'] = $rows;
        $viewData['countshop'] = $countshop;
        //$viewData['countcommodity'] = $countcommodity;
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }
	
	//------------------规格处理逻辑开始处--------------------------------//
	/**
	 * 生成规格选择列表项 
	 * @param undefined $class_id 规格所在分类
	 * @param undefined $existedSpec 已存在的规格
	 * 
	 */
	private function _genSpecDisplayList($class_id = 0,$existedSpec = NULL)
	{
		$dbSpec = new Attribute($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
		return $this->_genSelectedItems($dbSpec->getSpecListByClassId($class_id),$existedSpec);
	}
	
	private function _genSelectedItems($rows,$existedSpec = NULL)
	{
		
		$html = "";
		if(!$rows){
			return $html;
		}
		
		foreach($rows as $r){
			$v = $this->_mapSpecVal($r->id,$existedSpec);
			if(count($r->subTree) == 0 && $v > 0){//有库存才显示
				$html.="<span class=\"p_number spec_item\"  id=\"spec_".$r->id."\"> ".$r->name."</span>";	
			}
			if(count($r->subTree) > 0){
				$html.= "<p class=\"productsizes\">";
				$html.="<span class=\"product_tit\">".$r->name.":</span>";
				$html.= $this->_genSelectedItems($r->subTree,$existedSpec);
				$html.="</p>";
			}
		}
		return $html;	
	}
	
	
	private function _mapSpecVal($spec_id,$existedSpec)
	{
		if(isset($existedSpec[$spec_id])){
			return $existedSpec[$spec_id];
		}	
		return 0;
	}
	
	//--------------规格处理逻辑结束处-----------------------//
	
	
	
	
    function moreinfoAction() {
        $id = $this->params()->fromQuery('id');
        if (!$id)
            $this->redirect()->toRoute('user', array('action' => 'error'));
        $domain = Tool::domain();
        $viewData = array();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        // 取得商品信息
        $db = new Commodity($adapter);
        $row = $db->getCommodity($id);
        if (!$row || $row['uid'] != $domain)
            $this->redirect()->toRoute('user', array('action' => 'error'));
        $viewData['row'] = $row;
        // 取得门店信息
        //$shop = new Shop($adapter);
        //$viewData['shop'] = $shop->getShop($row['shop']);
        $shop = new Shop($adapter);
        $res = $shop->shopAll($domain);
        $countshop = $res->count();
        //取得其他同类商品
        $rows = $db->proList(5, 1, $domain, $row['shop'], $row['cateID'], null, null, true);
        $viewData['rows'] = $rows;
        $viewData['countshop'] = $countshop;
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    function listAction() {
        $domain = Tool::domain();
        $id = (int) $this->params()->fromQuery('id');
        // if (!$id) $this->redirect()->toRoute('user',array('action'=>'error'));
        $viewData = array();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new Commodity($adapter);
        $c = $db->cateCount($domain, $id, true);
        $viewData['pageCount'] = $c->count();
        $viewData['rows'] = $db->proList(5, 1, $domain, null, $id, null, null, true);
        $viewData['id'] = $id;
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    function searchAction() {
        $domain = Tool::domain();
       // $id = $this->params()->fromQuery('id');
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $keywords = Tool::filter($postData['keywords']);
        }
        if (!$keywords)
            $this->redirect()->toUrl('/user/error?msgid=1008');
        $viewData = array();
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new Commodity($adapter);
        $c = $db->keywordsCount($domain, $keywords, true);
        $viewData['pageCount'] = ceil($c->count() / 5);
        $viewData['stotal'] = $c->count();
        $viewData['rows'] = $db->proList(5, 1, $domain, null, null, $keywords, null, true);
        $viewData['keywords'] = $keywords;
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    function indentAction() {
	$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        if (!isset($this->member->id)) {
            //$url = $_SERVER['REQUEST_URI'];
            //Tool::setCookie("_refer", $url, time() + 3600);
            $member = new \module\Application\src\Model\Member($adapter);
            $this->member = $member->randMember();
        }
        $viewData = array();
        $rows = array();
        $db = new \Admin\Model\Commodity($adapter);
        $cartData = $db->cartData();
        $res = $this->getRequest();
        if ($res->isPost()) {
            $data = $res->getPost();
            if (isset($data['domain']) && $data['domain']) {
                foreach ($data['domain'] as $doVal) {
                    $int = array_keys($doVal);
                    $int = "'" . implode("','", $int) . "'";
                    $rows = $db->byID($int);
                    $serialnumber = date("Ymd") . Tool::random(12, true);
                    $in = new Indent($adapter);
                    if ($rows) {
                        foreach ($rows as $val) {
                            $inData['uid'] = $val['uid'];
                            $inData['buyer'] = $this->member->id;
                            $inData['shop'] = $val['shop'];
                            $inData['pid'] = $val['id'];
                            $inData['name'] = $val['name'];
                            $inData['address'] = $data['address'];
                            $inData['thumb'] = $val['thumb'];
                            $num = $cartData[$val['id']]['num'];
                            $inData['amount'] = $num;
                            $sum = $num * $val['rebate'];
                            $inData['sum'] = $sum;
                            $inData['status'] = 2;
                            $inData['addtime'] = time();
                            $inData['payment'] = $data['paytype'];
                            $inData['bank'] = isset($data['bankType']) ? $data['bankType'] : null;
                            $inData['serialnumber'] = $serialnumber;
                            $in->addIndent($inData);
                        }
                    }
                }
            }
            $count = count($data['domain']);
            if ($count>1)
            {
                $this->redirect()->toUrl('/user/indent');
            }else
            {
                $this->redirect()->toUrl("/alipay/pay?id={$serialnumber}");
            }
            $db->deletecartData($this->member);
        }


        if ($cartData && is_array($cartData)) {
            $int = array_keys($cartData);
            $int = "'" . implode("','", $int) . "'";
            $rows = $db->byID($int);
            if ($rows) {
                foreach ($rows as $key => $val) {
                    $rows[$key]['num'] = $cartData[$val['id']]['num'];
                }
            }
        }

        $viewData['rows'] = $rows;
        $user = new User($adapter);
        $viewData['address'] = $user->addressList($this->member->id);
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    function commentsAction() {
        $id = (int) $this->params()->fromQuery('id');
        $domain = Tool::domain();
        if (empty($id))
            $this->redirect()->toRoute('user', array('action' => 'error'));
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $viewData = array();
        $db = new Commodity($adapter);
        $row = $db->getCommodity($id);
        if (!$row || $row['uid'] != $domain)
            $this->redirect()->toRoute('user', array('action' => 'error'));
        $viewData['row'] = $row;
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    /**
     * 将商品图片批量导入至wx_files表 
     * 
     */
    function batTransferPic2FileTblAction() {
        $dbFile = new File($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $dbFile->doTransfer();
    }

}