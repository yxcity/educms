<?php
/**
  * 后端Admin模块总控插件
  * 提供后端Controller全局调用的公共方法
  */


namespace library\Plugin;
  
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use library\Helper\HCommon;    
use Admin\Model\Role;
     
class BackendPlugin extends AbstractPlugin
{
  
  	public $act_add = 10;//添加操作
	public $act_edit = 20;//编辑操作
	public $act_del = 30;//删除操作
	public $act_link = 40;//普通链接
	
    public function doCheck($e)
    {
		
		//检测是否为Admin模块,此Plugin仅需限制Admin模块
		$ctrl = explode("\\",$e->getRouteMatch()->getParam('controller'));
		if($ctrl[0] != "Admin" && self::getSubDomain() != "login"){
			return FALSE;
		}
		
		
		//检测是否登录
		$this->_checkUserLogin($e);
		
		//检测是否有操作权限
		$this->_checkAcl($e);
    }
	
	/**
	 * 检测当前账户是否登录 
	 * @param undefined $e
	 * 
	 */
	private function _checkUserLogin($e)
	{
		$user = $this->getUserSesion();
		$toRedirect = FALSE;
		$sbDomain = HCommon::getSubDomain();
		if($sbDomain == "login"){
			if($user && isset($user->real_domain) && $user->real_domain){
				$toRedirect = TRUE;
				$host = str_replace("login",$user->real_domain,$_SERVER['HTTP_HOST']);
				$url = "http://".$host."/home";
			}
		}else{
			if($e->getRouteMatch()->getParam('action') == "auth"){
				if($user){
					$toRedirect = TRUE;
					$url = "/home";
				}
			}else{
				if(!$user){
					$toRedirect = TRUE;
					$url = "/login";
				}
			}	
		}
		
		
		if($toRedirect){
			$router = $e->getRouter(); 
            $response = $e->getResponse();
            $response->setStatusCode(302);
            $response->getHeaders()->addHeaderLine('Location', $url);
            $e->stopPropagation();
		}
	}
	
    
    public function getDbAdapter()
    {
        $adapter = $this->getController()->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        return $adapter;
    }
	
	/**
	 * 检测当前账户是否有相应的操作权限 
	 * @param undefined $e
	 * 
	 */
	private function _checkAcl($e)
	{
		$user = $this->getUserSesion();
		if(!$user){
			return FALSE;
		}
		
		
		
		$opts = $this->_getOpts($user);
		$ctrl = explode("\\",$e->getRouteMatch()->getParam('controller'));
		$ctrl = strtolower($ctrl[2]);
		$act = strtolower($e->getRouteMatch()->getParam('action'));
		$ctrl_act = $ctrl."_".$act;
		
		//读取操作说明信息
		$adapter = $this->getDbAdapter();	
		$dbRole = new Role($adapter);
		$actInfo = $dbRole->getAccessById($ctrl_act);
		if($actInfo){
			HCommon::setCache("acl_help",$actInfo['acl_help']);
		}
		if(!$this->_checkOptable($ctrl_act)){
			$e->getTarget()->layout('error/forbidden');
			$e->stopPropagation();
		}
	}
	
	
	private function _getOpts($user = NULL)
	{
		if(!$user){
			return FALSE;
		}
        $adapter = $this->getDbAdapter();
		$db = new Role($adapter);
		return $db->getExistedRoleAccess($user->roleid);
	}
	
	
	/**
	 * 检测是否可以执行当前操作 
	 * @param undefined $ctrl_act
	 * 
	 */
	private function _checkOptable($ctrl_act)
	{
		$user = $this->getUserSesion();
		if(!$user){
			return FALSE;
		}
		//$opts = $this->getSession('acl','opts');
		$opts = $this->_getOpts($user);
		if(empty($opts) || (!in_array($ctrl_act,$this->_getIgnoreActions()) && !in_array($ctrl_act,array_keys($opts)))){
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	 * 全局忽略的无需作访问控制的操作列表
	 * @description 数组项采用 controller_action标准格式 
	 * 
	 */
	private function _getIgnoreActions()
	{
		return array(
			'index_logout',
			'users_edit',
			'commodity_ajaxremovefile',
			'type_ajaxupdatesorting',
			'users_clearcache',
            'common_ajaxupload'
		);
	}
	
	
	
	/**
	 * 生成权限控制下的操作链接|按钮 
	 * @param undefined $ctrl_act 操作唯一标识,采用controller_action标准格式(如 home_index)
	 * @param undefined $act_type 操作类型,可选值有 ACT_ADD|ACT_EDIT|ACT_DEL|ACT_LINK
	 * @param undefined $text 链接文字
	 * @param undefined $params url参数 
	 * |--特别说明:若设置了参数$params['route'] = "/xxx/xxx"; //设定路由之后,生成的链接以路由为准
	 * |--而不是由controller_action拼接而成
	 * |--若为删除操作,则需配置参数$params['del_id'] = xxx //如果没有此参数，删除链接不会产生
	 * 
	 */
	public function genActLink($ctrl_act,$act_type,$text = "",$params = NULL)
	{
		$html = "";
		if(!preg_match("/^[\S]+\_[\S]+$/",$ctrl_act)){
			return $html;
		}
		if(!$this->_checkOptable($ctrl_act)){
			return $html;
		}
		list($ctrl,$act) = explode("_",$ctrl_act);
		$url = isset($params['route']) ? $params['route'] : "/".$ctrl."/".$act;
		if(isset($params['route'])){
			unset($params['route']);//清除路由参数,在生成url ?后面的参数时无需路由参数
		}
		switch($act_type){
			case $this->act_add:
			if(is_array($params)){
				$url.="?".http_build_query($params);
			}
			$html.="<a href='$url' class='btn btn-primary'><i class='icon-plus'></i> ".$text."</a>";
			break;
			case $this->act_edit:
			if(is_array($params)){
				$url.="?".http_build_query($params);
			}
			$html.="<a href='$url' title='$text'><i class='icon-pencil'></i></a>";
			break;
			case $this->act_del:
			if(!isset($params['del_id']) || empty($params['del_id'])){
				break;
			}
			$del_id = intval($params['del_id']);
			$del_id.="_item";
			$html.="<a href='#myModal' class='to_del' role='button' title='删除' data-toggle='modal' id='$del_id'><i class='icon-remove'></i></a>";
			break;
			case $this->act_link:
			if(is_array($params)){
				$url.="?".http_build_query($params);
			}
			$html.="<a href='$url'>".$text."</a>";
			break;
			default:
		}
		return $html;
	}
	
	
	/**
	 * 解决跨域存储用户登录信息的问题 
	 * @param undefined $user
	 * 
	 */
	public function setUserSession($user = NULL,$expire = NULL)
	{
		
		if(!$user){
			return FALSE;
		}
		//1._identify作为唯一标识写入Cookie,以供login与二级域名共享登录信息
		$domain = $_SERVER ['HTTP_HOST'];
		$domain = substr($domain,strpos($domain,".")+1);
		$expire = is_null($expire) ? time()+7200 : $expire;
		setcookie ("_identify", $user->real_domain.$user->id, $expire, '/', $domain);
		
		//2.将_identify写入Session
		HCommon::setSession('auth',array('user'=>$user),NULL,$user->real_domain.$user->id);
	}
	
	/**
	 * 获取Admin模块账户登录信息 
	 * 
	 */
	public function getUserSesion()
	{
		$_cookieId = isset($_POST['_identify']) ? $_POST['_identify'] : NULL;
		$_identify = self::_getIdentify(NULL,$_cookieId);
		$domain = HCommon::getSubDomain();
		if($domain == "login" || preg_match("/".$domain."/",$_identify)){
			$data = HCommon::getSession("auth","user",$_identify);
			if(!$data){
				return FALSE;
			}
			return $data;
		}
		return FALSE;
	}
}
?>