<?php
namespace Admin\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\File;
use Admin\Model\Ads;
class AdvertController extends AbstractActionController
{
	private $user;
	private $viewData=array();
	function __construct()
	{
		$this->user = Tool::getSession('auth','user');
		$this->viewData['user'] = $this->user;
	}

	function indexAction()
	{
		$alert = json_decode(Tool::getCookie('alert'));
		$this->viewData['alert']=$alert;
		$db = new Ads($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
		$page = $this->params()->fromQuery('page',1);
		$this->viewData['rows']=$db->advertList($this->user,30,$page);
		$adsRes = $db->allAds($this->user);
		$ads=array();
		if ($adsRes->count())
		{
			foreach ($adsRes as $val)
			{
				$ads[$val['id']]=$val['title'];
			}
		}
		$this->viewData['ads']=$ads;
		return $this->viewData;
	}
	
	function createAction()
	{
		$db = new Ads($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
		$res = $this->getRequest();
		if ($res->isPost())
		{
			$data = $res->getPost()->toArray();
			$valid=isset($data['valid'])?strtotime("{$data['valid']} 23:59:59"):time()+3600*24*15;
			$data['valid']=$valid;
			unset($data['submit']);
			$file=$res->getFiles ()->toArray();
			if ($file && is_array($file))
			{
				$thumb = File::doUpload($file,$this->user->id,array('gif','jpg','png'),NULL,array('thumb_size'=>array(658,160)));
				if (isset($thumb['file']))
				{
					$data['img']=$thumb['file'];
				}
			}
			$data['uid']=$this->user->id;
			$data['domain']=$this->user->domain;
			$data['addtime']=time();
			$db->insertAdvert($data);
			Tool::setCookie('alert', array('title'=>'提交成功','message'=>'添加广告成功','alert'=>'success'),time()+5);
			$this->redirect()->toRoute('advert');
			
		}
		$adsRes = $db->allAds($this->user);
		$ads=array();
		if ($adsRes->count())
		{
			foreach ($adsRes as $val)
			{
				$ads[$val['id']]=$val['title'];
			}
		}
		$this->viewData['ads']=$ads;
        $this->viewData['asset']=array('js'=>array('/lib/advert.js'));
		return $this->viewData;
	}
	
	function editAction()
	{
		$id = (int)$this->params()->fromQuery('id');
		if (!$id) $this->redirect()->toRoute('advert');
		$db = new Ads($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
	    $res = $this->getRequest();
		if ($res->isPost())
		{
			$data = $res->getPost()->toArray();
			$valid=isset($data['valid'])?strtotime("{$data['valid']} 23:59:59"):time()+3600*24*15;
			$data['valid']=$valid;
			unset($data['submit']);
			$file=$res->getFiles ()->toArray();
			if ($file && is_array($file))
			{
				$thumb = File::doUpload($file,$this->user->id,array('gif','jpg','png'),NULL,array('thumb_size'=>array(658,160)));
				if (isset($thumb['file']))
				{
					$data['img']=$thumb['file'];
				}
			}
			$db = new Ads($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
			$db->insertAdvert($data,$id);
			Tool::setCookie('alert', array('title'=>'编辑成功','message'=>'编辑广告成功','alert'=>'success'),time()+5);
			$this->redirect()->toRoute('advert');
			
		}
		
		$row = $db->getAdvert($id);
		if (!$row || $row['domain']!=$this->user->domain) $this->redirect()->toRoute('advert');
		$this->viewData['row']=$row;
		$adsRes = $db->allAds($this->user);
		$ads=array();
		if ($adsRes->count())
		{
			foreach ($adsRes as $val)
			{
				$ads[$val['id']]=$val['title'];
			}
		}
		$this->viewData['ads']=$ads;
        $this->viewData['asset']=array('js'=>array('/lib/advert.js'));
		return $this->viewData;
	}
	
	function deleteAction(){
		$id = ( int ) $this->params ()->fromQuery ( 'id' );
		if ($id)
		{
			$adapter = $this->getServiceLocator ()->get ( 'Zend\Db\Adapter\Adapter' );
			$db = new Ads ( $adapter );
			$row = $db->getAdvert($id);
			if ($row['domain']==$this->user->domain)
			{
				$db->deleteAdvert($id);
				exit('{"msg":"删除成功","isok":true}');
			}
			exit('{"msg":"删除失败","isok":false}');
		}
		exit('{"msg":"删除失败","isok":false}');
	}
}