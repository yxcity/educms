<?php
namespace Admin\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Admin\Model\Role;
use module\Application\src\Model\Tool;

class AliasController extends AbstractActionController{
    
    private $viewData=array();
    private static $_dbRole = NULL;
	
	function __construct(){
		$this->user=Tool::getSession('auth','user');
		$this->viewData['user']=$this->user;
	}
    
    /**
	 * @todo 别名列表
	 * (non-PHPdoc)
	 * @see \Zend\Mvc\Controller\AbstractActionController::indexAction()
	 */
	function indexAction()
	{
		
		$page=$this->params('page',1);
		$this->viewData['rows']=$this->_getRoleDb()->access4AliasList($this->user->roleid,$page);
        $this->viewData['map'] = $this->_getRoleDb()->getAliasMapping($this->user->domain);
		$this->viewData['bp'] = $this->BackendPlugin();//Admin模块权限控制插件,用于权限控制及操作链接的生成
		return $this->viewData;
	}   
    
    
    /**
     * @todo 操作项别名编辑
     * @return multitype:Ambigous <\Admin\Model\Ambigous, boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>
     */
    function editAction()
    {
		$viewData=array();
        $viewData['user']=$this->user;
        $acl_id = (int)$this->params()->fromQuery('acl_id');
        $request = $this->getRequest();
        if ($request->isPost())
        {
        	$postData=$request->getPost();
        	if(!$postData['icon']) $postData['icon']="icon-reorder";
			$data = array(
				'acl_id' => $acl_id,
                'alias_key' => $postData['alias_key'],
                'alias_icon' => $postData['icon'],
                'alias_label' => Tool::filter($postData['alias_label'],TRUE),
                'domain' => $this->user->domain
			);
        	if ($this->_getRoleDb()->saveAliasInfo($data))
        	{
                Tool::delCache($this->user->domain."_act_0_".$this->user->roleid);
        	    Tool::setCookie('success', array('title'=>'编辑成功','message'=>'已经成功更新别名信息'),time()+5);
        	}else{
				Tool::setCookie('success', array('title'=>'未作更新','message'=>'#'),time()+5);
			}
			$this->redirect()->toUrl("/alias");
        }
        $row=$this->_getRoleDb()->getAlias($acl_id,$this->user->domain);
        if(!$row){
            Tool::setFlash('error',array('title'=>"访问失败",'message'=>"您请求的资源不存在,可能已被删除"));
            $this->redirect()->toUrl('/alias');
        }
        $viewData['row']=$row;
        $viewData['asset']=array('js'=>array('/lib/alias.js'));
        $viewData['asset']=array('css'=>array('/sitetem/common/css/resource.css','/sitetem/common/css/themes.css'));
        return $viewData;
    }
    
    /**
	 * 数据库连接适配器 
	 * 
	 */
	private function _getRoleDb()
	{
		if(self::$_dbRole == NULL){
			self::$_dbRole = new Role($this->getServiceLocator()->get('Zend\Db\Adapter\Adapter'));	
		}
		return self::$_dbRole;
	}
}
?>