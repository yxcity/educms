<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use module\Application\src\Model\Tool;
use Zend\Mvc\Controller\AbstractActionController;
use Admin\Model\Article;

class HelpController extends AbstractActionController
{
	private $user;
	private $_adapter = NULL;
    public function __construct()
	{
	    //$domain = $_SERVER['SERVER_NAME'];
	    //$n = preg_match('/(.*\.)?\w+\.\w+$/', $domain, $matches);
	    //print_r($matches);
	    //exit;
	}
    function indexAction ()
    {
        $id = $this->params()->fromRoute('id');
        $s = $this->params()->fromRoute('s');
        Tool::openid($s);
        if(!$id){
            echo 'id required!';
            exit;
        }
        $Art_obj = new Article($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $Art = $Art_obj->getArtById($id,'12','yjf');
        if(!$Art){
            echo 'article not found!';
            exit;
        }
        $viewData['news']=$Art;
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
        $Art_obj = new Article($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $art = $Art_obj->getArtById($id,'12','yjf');
        if(!$art){
            echo 'article not found!';
            exit;
        }
        $viewData['news']=$art;
        $viewModel = new ViewModel($viewData);
        $viewModel->setTerminal(true);
        return $viewModel;
    }
    
    /*增加指定文章的阅读读数*/
    function readAction ()
    {
        $id = $this->params()->fromRoute('id');
        if(!$id){
            echo '{"code":"error","msg":"id required!"}';
            exit;
        }
        $Art_obj = new Article($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $art = $Art_obj->getArtById($id,'12','yjf');
        if(!$art){
            echo '{"code":"error","msg":"article not found!"}';
            exit;
        }
        
        if($Art_obj->incReadCount($id)){
            echo '{"code":"ok"}';
        }else{
            echo '{"code":"error","msg":"update read count error!"}';
        }
        exit;
    }
}