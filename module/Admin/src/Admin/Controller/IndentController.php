<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Admin\Model\Indent;
use module\Application\src\Model\Tool;
use Admin\Model\User;
use module\Application\src\Model\Express;
use module\Application\src\Model\Alipay\Alipay;


/**
 * @todo 订单管理类
 * 
 * @author
 * @version 
 */
class IndentController extends AbstractActionController
{
	private $user;
	
    function __construct()
	{
	   $this->user=Tool::getSession('auth','user');	
	  
	}
	/**
	 * The default action - show the home page
	 */
    public function indexAction()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $viewData=array();
        $success = Tool::getCookie('success');
        if ($success)
        {
        	$viewData['success']=json_decode($success);
        }
        $page = $this->params('page',1);
        $s = $this->params()->fromQuery('s',null);
        $viewData['s']=$s;
        if ($s==1)
        {
        	$s = "'1','4'";
        }
        $viewData['user']=$this->user;
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $user = new User($adapter);
        $row = $user->getUser($this->user->id);
        $userShop=false;
        if ($row['shop'])
        {
        	$userShop = "'".implode("','",json_decode($row['shop']))."'";
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
        $db = new Indent($adapter);
        $viewData['rows']= $db->indentList($page, $this->user,$userShop,$s,'20',$keywords);
        $viewData['keywords']=$keywords;
        $viewData['status']=$db->indentStatus();
		$viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
        return $viewData;
    }
    /**
     * @todo 编辑订单
     * @return Ambigous <\module\Application\src\Model\Ambigous, boolean, unknown>
     */
    public function editAction()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $id = $this->params()->fromQuery('id');
        if (empty($id)) $this->redirect()->toRoute('indent');
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new Indent($adapter);
        $resRows=$db->getIndent($id);
        $row=array();
        if ($resRows)
        {
            foreach ($resRows as $key => $val) {
                if ($key) continue;
                $row=$val;
            }
        }
        if ($row['uid']!=$this->user->domain) $this->redirect()->toUrl('/indent');
        if ($row['status']==2 || $row['status']==3 || $row['status']==5) $this->redirect()->toUrl('/indent');
        $request = $this->getRequest();
        if ($request->isPost())
        {
        	$postData = $request->getPost();
        	$transport = $postData['transport'];
        	$express = $postData['express'];
        	$waybill = $postData['waybill'];
        	$data['transport']=$transport;
        	$data['express']=$express;
        	$data['waybill']=$waybill;
        	$data['status']=$postData['status'];
        	$data['content']=Tool::filter($postData['content'],true);
        	if ($transport!=4 && $express && $waybill && $row['trade_no'] && $row['trade_status']=='WAIT_SELLER_SEND_GOODS')
        	{
        		$pData=array();
        		$user=new User($adapter);
        		$uRow=$user->clickDomain($this->user->domain);
                        $PID = $uRow ['apitype'] == 4 ? '2088701598458641' : $uRow['PID'];
                        $KEY = $uRow ['apitype'] == 4 ? '7ort188mko0binqu75ynfng8k11ulltt' : $uRow['KEY'];
        		$pData['PID']=$PID;
        		$pData['KEY']=$KEY;
        		$pData['trade_no']=$row['trade_no'];
        		$pData['logistics_name']=$express;
        		$pData['invoice_no']=$waybill;
        		if ($transport==1){
        			$tr = 'POST';
        		} 
        		elseif ($transport==2 && $express=='EMS'){
        			$tr = 'EMS';
        		}else {
        			$tr = 'EXPRESS';
        		}
        		$pData['transport_type']=$tr;
        		$alipay = new Alipay();
        		$alipay->sendGoods($pData);
        	}
        	
        	$res=$db->update($data,null,$id);
        	if ($res)
        	{
        	    Tool::setCookie('success', array('title'=>'操作成功','message'=>'修改订单成功'),time()+3);
        	    $this->redirect()->toRoute('indent');
        	}
        }
        
        $viewData['row']=$row;
        $viewData['status']=$db->indentStatus();
        $viewData['express']=$db->express();
        $viewData['user']=$this->user;
        return $viewData;
    }
    /**
     * @todo 查看订单
     * @return Ambigous <mixed, \Admin\Model\Ambigous, \Zend\Paginator\Paginator, \module\Application\src\Model\Ambigous, boolean, unknown, multitype:, ArrayObject, NULL, \ArrayObject>
     */
    public function viewAction()
    {
        if (!isset($this->user->domain)) $this->redirect()->toUrl('/login');
        $id = $this->params()->fromQuery('id');
        if (empty($id)) $this->redirect()->toRoute('indent');
        $viewData = array();
        $viewData['id']=$id;
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $db = new Indent($adapter);
        $rows = $db->getIndent($id);
        if (!$rows->count() || $this->user->power!=2) $this->redirect()->toRoute('indent');
        $tmp=array();
        $express=null;
        $waybill=null;
        if ($rows)
        {
            $address = '';
            foreach ($rows as $key => $val) {
                if ($val['uid'] != $this->user->domain) $this->redirect()->toRoute ('indent');
                $tmp[$key]=$val;
                $address = $val['address'];
                $express=$val['express'];
                $waybill=$val['waybill'];
            }
            if ($address)
            {
                $user = new User($adapter);
                $viewData['address'] = $user->getAddress($address);
            }
        }
        unset($rows);
        $viewData['rows'] = $tmp;
        $viewData['status']=$db->indentStatus();
        $viewData['trade']=$db->tradeStatus();
        $viewData['user']=$this->user;
        if ($express && $waybill)
        {
        	$exp = new Express();
        	$expLog=$exp->getorder($express, $waybill);
        	
        	if ($expLog['message']=='ok')
        	{
        		$viewData['expLog']=$expLog['data'];
        		$viewData['ischeck']=$expLog['ischeck'];
        	}
        }
        return $viewData;
    }

    /**
     * @todo 删除订单
     * @return Ambigous <mixed, \Admin\Model\Ambigous, \Zend\Paginator\Paginator, \module\Application\src\Model\Ambigous, boolean, unknown, multitype:, ArrayObject, NULL, \ArrayObject>
     */
    public function deleteAction()
    {
    	$domain = Tool::domain();
        $id = (int)$this->params()->fromQuery('id');
    	$indent = new Indent($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
    	$row=$indent->getIndent($id);
    	if ($id && $this->user->power ==2 && $row['uid']==$domain)
    	{
    	    $indent->update(array('display'=>'0'),$id);
    	    exit('{"isok":true}');
    	}
        exit('{"isok":false}');
    }
}