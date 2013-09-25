<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Ads;

class AdsController extends AbstractActionController {
	private $viewData = array ();
	private $user;
	
	function __construct() {
		$this->user = Tool::getSession ( 'auth', 'user' );
		$this->viewData ['user'] = $this->user;
	}
	
	function indexAction() {
		$alert = json_decode(Tool::getCookie('alert'));
		$this->viewData['alert']=$alert;
		$db = new Ads($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
		$page = $this->params()->fromQuery('page',1);
		$this->viewData['rows'] = $db->adsList($this->user,30,$page);
		return $this->viewData;
	}
	
	function createAction() {
		$res = $this->getRequest();
		if ($res->isPost())
		{
			$adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
			$data=$res->getPost()->toArray();
			unset($data['submit']);
			$data['uid']=$this->user->id;
			$data['domain']=$this->user->domain;
			$data['addtime']=time();
			$db = new Ads($adapter);
			$db->insertAds($data);
			Tool::setCookie('alert', array('title'=>'提交成功','message'=>'添加广告位成功','alert'=>'success'),time()+5);
			$this->redirect()->toRoute('ads');
		}
		return $this->viewData;
	}
	
	function editAction() {
		$id = ( int ) $this->params ()->fromQuery ( 'id' );
		if (! $id)
			$this->redirect ()->toRoute ( 'ads' );
		$res = $this->getRequest ();
		$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
		$db = new Ads ( $adapter );
		if ($res->isPost ()) {
			$data = $res->getPost ()->toArray ();
			unset ( $data ['submit'] );
			$db->insertAds ( $data ,$id);
			Tool::setCookie ( 'alert', array (
					'title' => '编辑成功',
					'message' => '编辑广告位成功',
					'alert' => 'success' 
			), time () + 5 );
			$this->redirect ()->toRoute ( 'ads' );
		}
		$row = $db->getAds ( $id );
		if (! $row || $row ['domain'] != $this->user->domain)
			$this->redirect ()->toRoute ( 'ads' );
		$this->viewData['row']=$row;
		return $this->viewData;
	}
	
	function deleteAction()
	{
		$id = ( int ) $this->params ()->fromQuery ( 'id' );
		if ($id)
		{
			$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
			$db = new Ads ( $adapter );
			$row = $db->getAds ( $id );
			if ($row['domain']==$this->user->domain)
			{
				$db->deleteAds($id);
				exit('{"msg":"删除成功","isok":true}');
			}
			exit('{"msg":"删除失败","isok":false}');
		}
		exit('{"msg":"删除失败","isok":false}');
	}
	
}