<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use module\Application\src\Model\Tool;
use Zend\Mvc\Controller\AbstractActionController;
use Admin\Model\Party;

class PartyController extends AbstractActionController
{
    function indexAction ()
    {
        $id = $this->params()->fromRoute('id');
        $s = $this->params()->fromRoute('s');
        Tool::openid($s);
        if(!$id){
            echo 'id required!';
            exit;
        }
        $adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        //取得报名信息
        $party_obj = new Party($adapter);
        $party = $party_obj->getParty($id);
		
        if(!$party){
            echo 'party not found!';
            exit;
        }
        $viewData['party']=$party;
        if(Tool::cutStr(strip_tags($party['party_content']),'30')){
            $viewData['party_intro'] = Tool::cutStr(strip_tags($party['party_content']),'30');
        }else{
            $viewData['party_intro'] = $party['party_title'];            
        }
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    function sAction ()
    {
        $id = $this->params()->fromRoute('id');
        if(!$id){
            echo 'id required!';
            exit;
        }
        $party_obj = new Party($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $party = $party_obj->getParty($id);
        if(!$party){
            echo 'article not found!';
            exit;
        }
        $viewData['party']=$party;
        $viewData['author'] = "";
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    /*报名提交*/
    function joinpartyAction ()
    {
        $id = $this->params()->fromRoute('id');
        if(!$id){
            echo '{"code":"error","msg":"id required!"}';
            exit;
        }
        $party_obj = new Party($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $party = $party_obj->getParty($id);
        if(!$party){
            echo '{"code":"error","msg":"party not found!"}';
            exit;
        }
        $nick = urldecode($this->params()->fromQuery('nick'));
        $phone = $this->params()->fromQuery('phone');
        if($party_obj->addPartyusers($id,$nick,$phone)){
            echo '{"state":"2"}';
        }else{
            echo '{"state":"0","msg":"update read count error!"}';
        }
        exit;
    }
    
    /*增加指定文章的阅读读数*/
    function readAction ()
    {
        $id = $this->params()->fromRoute('id');
        if(!$id){
            echo '{"code":"error","msg":"id required!"}';
            exit;
        }
        $party_obj = new Party($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $party = $party_obj->getParty($id);
        if(!$party){
            echo '{"code":"error","msg":"party not found!"}';
            exit;
        }
        
        if($party_obj->incReadCount($id)){
            echo '{"code":"ok"}';
        }else{
            echo '{"code":"error","msg":"update read count error!"}';
        }
        exit;
    }
    
    /*增加指定文章的分享读数*/

	/**
	 * 建议JSON格式数据输出,可以采用以下方式:
	 * 	
	 *	$data = array('code'=>"error",'msg'=>"id required");
	 *	echo json_encode($data);//这里是重点,PHP自带的将数组转换为JSON字串的函数
	 *	exit(0);
	 *
	 */
    function shareAction ()
    {
        $id = (int)str_replace('party/','',$this->params()->fromQuery('s'));
        if(!$id){
            echo '{"code":"error","msg":"id required!"}';
            exit;
        }
        $party_obj = new Party($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $party = $party_obj->getParty($id);
        if(!$party){
            echo '{"code":"error","msg":"party not found!"}';
            exit;
        }
        
        if($party_obj->incShareCount($id)){
            echo '{"code":"ok"}';
        }else{
            echo '{"code":"error","msg":"update read count error!"}';
        }
        header("Location: http://".$party['domain'].".veigou.com/s/party/".$id);
        exit;
    }
}