<?php
namespace Application\Controller;

use Zend\View\Model\ViewModel;
use module\Application\src\Model\Tool;
use Admin\Model\News;
use Zend\Mvc\Controller\AbstractActionController;

class NewsController extends AbstractActionController
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
        $news_obj = new News($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $news = $news_obj->getNews($id);
        if(!$news){
            echo 'article not found!';
            exit;
        }
        $viewData['news']=$news;
        $viewData['author'] = "";
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
        $news_obj = new News($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $news = $news_obj->getNews($id);
        if(!$news){
            echo 'article not found!';
            exit;
        }
        $viewData['news']=$news;
        $viewData['author'] = "";
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
        $news_obj = new News($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));
        $news = $news_obj->getNews($id);
        if(!$news){
            echo '{"code":"error","msg":"article not found!"}';
            exit;
        }
        
        if($news_obj->incReadCount($id)){
            echo '{"code":"ok"}';
        }else{
            echo '{"code":"error","msg":"update read count error!"}';
        }
        exit;
    }
}