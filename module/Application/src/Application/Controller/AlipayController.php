<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\User;
use Admin\Model\Indent;
use module\Application\src\Model\Alipay\Alipay;
use module\Application\src\Model\Alipay\AlipayNotify;
{

    class AlipayController extends AbstractActionController {

        private $member;

        function __construct() {
            $this->member = json_decode(Tool::getCookie('member'));
        }

        function indexAction() {
            echo 'test';
            exit();
        }

        function payAction() {
	    if (!isset($this->member->id)) $this->redirect()->toUrl('/user/login');
            $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
            $id = $this->params()->fromQuery('id');
            $db = new Indent($adapter);
            $rows = $db->getIndent($id);
            if ($rows) {
                $sum = 0;
                $name = $id;
                $address = '';
                $domain = '';
                foreach ($rows as $val) {
                    $sum = $sum + $val['sum'];
                    $address = $val['address'];
                    $domain = $val['uid'];
                }
            }
            $user = new User($adapter);
            $uRow = $user->clickDomain($domain);
            $PID = $uRow ['apitype'] == 4 ? '2088701598458641' : $uRow['PID'];
            $KEY = $uRow ['apitype'] == 4 ? '7ort188mko0binqu75ynfng8k11ulltt' : $uRow['KEY'];
            if ($PID && $KEY && $uRow ['alipayEmail']) {
                // 创建支付

                $quantity = 1;
                $logistics_fee = '0.00';
                $logistics_type = 'EXPRESS';
                $logistics_payment = 'SELLER_PAY';
                $receive_name = '';
                $receive_address = '';
                $receive_zip = '';
                $receive_phone = '';
                $receive_mobile = '';
                if ($address) {
                    $aRow = $user->getAddress($address);
                    $receive_name = $aRow ['name'];
                    $receive_address = $aRow ['province'] . $aRow ['city'] . $aRow ['area'] . $aRow ['address'];
                    $receive_zip = $aRow ['zipcode'];
                    $receive_mobile = $aRow ['phone'];
                }
                //shop@youtitle.com
                //2088701598458641 
                //7ort188mko0binqu75ynfng8k11ulltt
                $payData = array(
                    'serialnumber' => $id,
                    'title' => $name,
                    'sum' => $sum,
                    'body' => $name,
                    'PID' => $PID, //$uRow['PID'],
                    'KEY' => $KEY, //$uRow['KEY'],
                    'alipayEmail' => $uRow['alipayEmail'],
                    'notify_url' => BASE_URL . "/alipay/notify",
                    'return_url' => BASE_URL . "/alipay/return",
                    'show_url' => BASE_URL . "/user/indent",
                    'quantity' => $quantity,
                    'logistics_fee' => $logistics_fee,
                    'logistics_type' => $logistics_type,
                    'logistics_payment' => $logistics_payment,
                    'receive_name' => $receive_name,
                    'receive_address' => $receive_address,
                    'receive_zip' => $receive_zip,
                    'receive_phone' => $receive_phone,
                    'receive_mobile' => $receive_mobile
                );
                // 标准接口支付
                $alipay = new Alipay($payData);
                if ($uRow ['apitype'] == 1) {
                    $alipay->standard();
                }
                // 担保交易接口
                if ($uRow ['apitype'] == 2) {
                    $alipay->guarantee();
                }
                // 及时到账
                if ($uRow ['apitype'] == 3 || $uRow ['apitype'] == 4) {

                    $alipay->immediately();
                }
            } else {
                $this->redirect()->toUrl("/user/indent");
            }
        }

        function notifyAction() {

            $res = $this->getRequest();
            $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
            $domain = Tool::domain();
            $user = new User($adapter);
            $uRow = $user->clickDomain($domain);
            $PID = $uRow ['apitype'] == 4 ? '2088701598458641' : $uRow['PID'];
            $KEY = $uRow ['apitype'] == 4 ? '7ort188mko0binqu75ynfng8k11ulltt' : $uRow['KEY'];
            $alipay_config = Tool::alipayConfig($PID, $KEY);
            $alipayNotify = new AlipayNotify($alipay_config);
            $verify_result = $alipayNotify->verifyNotify();
            if ($res->isPost() && $verify_result) {
                $data = $res->getPost();
                $update = array();
                $serialnumber = $data ['out_trade_no'];
                $update ['trade_no'] = $data ['trade_no'];
                $trade_status = $data ['trade_status'];
                $update ['trade_status'] = $trade_status;
                $i = new Indent($adapter);
                // || $trade_status=='TRADE_SUCCESS'
                //买家已付款
                if ($trade_status == 'WAIT_SELLER_SEND_GOODS') {
                    $update ['status'] = 1;
                    $update ['payTime'] = time();
                }
                //卖家发货
                if ($trade_status == 'WAIT_BUYER_CONFIRM_GOODS') {
                    $update ['status'] = 4;
                    $update ['payTime'] = time();
                }
                //买家确认收货
                if ($trade_status == 'TRADE_FINISHED') {
                    $update ['status'] = 6;
                    $update ['payTime'] = time();
                }
                
                //及时到帐
                if ($trade_status == 'TRADE_SUCCESS') {
                    $update ['status'] = 1;
                    $update ['payTime'] = time();
                }
                

                $i->update($update, null, $serialnumber);
            }
            $this->getServiceLocator()->get('Logger');
            exit();
        }

        function returnAction() {
            $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
            $domain = Tool::domain();
            $user = new User($adapter);
            $uRow = $user->clickDomain($domain);
            $PID = $uRow ['apitype'] == 4 ? '2088701598458641' : $uRow['PID'];
            $KEY = $uRow ['apitype'] == 4 ? '7ort188mko0binqu75ynfng8k11ulltt' : $uRow['KEY'];
            $alipay_config = Tool::alipayConfig($PID, $KEY);
            $alipayNotify = new AlipayNotify($alipay_config);
            $verify_result = $alipayNotify->verifyReturn();
            if ($verify_result) {
                $serialnumber = $this->params()->fromQuery('out_trade_no');
                $update['trade_no'] = $this->params()->fromQuery('trade_no');
                $trade_status = $this->params()->fromQuery('trade_status');
                $update['trade_status'] = $trade_status;
                if ($trade_status == 'TRADE_SUCCESS' || $trade_status = 'WAIT_SELLER_SEND_GOODS') {
                    $update ['status'] = 1;
                    $update ['payTime'] = time();
                }
                $i = new Indent($adapter);
                $i->update($update, null, $serialnumber);
            }
            $this->redirect()->toUrl('/user/indent?s=1');
        }

    }

}