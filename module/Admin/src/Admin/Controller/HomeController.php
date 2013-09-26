<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use library\Helper\HCommon;
use Admin\Model\User;
use Admin\Model\Role;

class HomeController extends AbstractActionController {
    
   
    function __construct(){
        
    	$this->user=HCommon::getSession('auth','user');
    }
    
	public function indexAction() {
        
        $adapter=$this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
	    $viewData=array();
	    $viewData['user']=$this->user;
	    $auth=HCommon::getCookie('auth');
	    if ($auth)
	    {
	    	$viewData['auth']=json_decode($auth);
	    }

		//取得角色名称
		$dbRole = new Role($adapter);
		$viewData['role_name'] = $dbRole->getRole($this->user->roleid)->role_name;
		
	    return $viewData;
	}
}