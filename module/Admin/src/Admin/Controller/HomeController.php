<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use module\Application\src\Model\Tool;
use Admin\Model\Shop;
use Admin\Model\User;
use Admin\Model\Commodity;
use Admin\Model\Indent;
use Admin\Model\Keyword;
use Admin\Model\Role;
use Admin\Model\Article;

class HomeController extends AbstractActionController {
    
    public $user;
    
    function __construct(){
    	$this->user=Tool::getSession('auth','user');
    }
	public function indexAction() {
	    //if (!isset($this->user->domain) || $this->user->power < 1) $this->redirect()->toUrl('/login');
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
	    $viewData=array();
	    $viewData['user']=$this->user;
	    $auth=Tool::getCookie('auth');
	    if ($auth)
	    {
	    	$viewData['auth']=json_decode($auth);
	    }
        //取出门店总数；
	    $db = new Shop($adapter);
	    $viewData['shopcount'] = $db->shopCount($this->user->domain);
        //取出订单总数；
        $dbIndent = new Indent($adapter);

        $user = new User($adapter);
        $row = $user->getUser($this->user->id);
        $userShop=false;
        if ($row['shop'])
        {
        	$userShop = "'".implode("','",json_decode($row['shop']))."'";
        }
        $viewData['IndentCount']= $dbIndent->indentCount($this->user->domain, $this->user, $userShop);
        $viewData['rows']= $dbIndent->indentList(null, $this->user,$userShop,null,'5');
        $viewData['status']=$dbIndent->indentStatus();
        //
	    //$userDB = new User($adapter);
	    //$viewData['userData'] = $userDB->getUser($this->user->domain);
        //取出商品总数；
        $dbcom = new Commodity($adapter);
	    $viewData['commodityCount'] = $dbcom->commodityCount($this->user->domain,$this->user, $userShop);
        //取出用户提交总数；
        $dbkeyword = new Keyword($adapter);
	    $viewData['keywordCount'] = $dbkeyword->keyCount($this->user->domain);
	    
	    //$viewData['rows']=$dbkeyword->keyList('text','1',$this->user->domain,'5');
        //取出问题反馈总数；
        $dbask = new User($adapter);
        $viewData['askCount'] = $dbask->askCount($this->user->domain);
        
        //取出公告帮助列表
        $dbart = new Article($adapter);
		$viewData['brows']=$dbart->newsList('1','12','yjf','5','','499');
		$viewData['crows']=$dbart->newsList('1','12','yjf','5','','500');
		$viewData['drows']=$dbart->newsList('1','12','yjf','5','','501');
		
		//取得角色名称
		$dbRole = new Role($adapter);
		$viewData['role_name'] = $dbRole->getRole($this->user->roleid)->role_name;
		
	    return $viewData;
	}
}