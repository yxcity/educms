<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\Indent;
use module\Application\src\Model\Alipay;

class CartController extends AbstractActionController {

    private $member;

    function __construct() {
        $m = json_encode(array('id' => 100));
        $this->member = json_decode($m);
    }

    function indexAction() {
		
		/**
		 * TODO商品规格,是一个数组,例如 array(100,200) 其中100,200都代表商品规格ID,存放于表wx_goods_spec 
		 * 此信息有可能在订单详情页显示出来,告诉用户购买的商品颜色,尺寸等信息
		 */
		$spec = $this->params()->fromQuery('spec');
		
        $ac = $this->params()->fromQuery('ac');
        $id = (int) $this->params()->fromQuery('id');
        $num = (int) $this->params()->fromQuery('num');
        if ($id && $ac) {
            $db = new \Admin\Model\Commodity($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
            $db->cartData($id, $num, $ac);
            exit('{"msg":"添加购物车成功","isok":true}');
        } else {
            exit('{"msg":"缺少必要参数","isok":false}');
        }

        exit();
    }

    function cartlistAction() {
        $adapter  = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new \Admin\Model\Commodity($adapter);
        $cartData = $db->cartData();
        $rows = array();
        if ($cartData && is_array($cartData)) {
            $int = array_keys($cartData);
            $int = "'" . implode("','", $int) . "'";
            $rows = $db->byID($int);
            if ($rows) {
                foreach ($rows as $key=>$val) {
                    $rows[$key]['num']=$cartData[$val['id']]['num'];
                }
            }
          
        }
        $viewModel = new ViewModel(array('rows' => $rows));
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    function payAction() {
        $id = $this->params()->fromQuery('id');
        $db = new Indent($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $row = $db->getIndent($id);
        $payData = array('serialnumber' => $row['serialnumber'], 'title' => $row['name'], 'sum' => $row['sum'], 'body' => $row['name'], 'url' => 'http://weixin.youtitle.com/index/alipay');
        $alipay = new Alipay();
        $alipay->pay($payData);
        exit();
    }

}