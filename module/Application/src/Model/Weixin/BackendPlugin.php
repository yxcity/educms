<?php
/**
  * 后端Admin模块总控插件
  * 提供后端Controller全局调用的公共方法
  */


namespace module\Application\src\Model\Weixin;
  
use Zend\Mvc\Controller\Plugin\AbstractPlugin,
    Zend\Session\Container as SessionContainer;
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
		$sbDomain = self::getSubDomain();
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
	
	/**
	 * 取得一个二级域名名称,如取得login.youtitle.com.cn中的login 
	 * 
 	 */
	static function getSubDomain()
	{
		$domain = $_SERVER ['HTTP_HOST'];
		$domain = explode(".",$domain);
		$domain = strtolower($domain[0]);
		return $domain;
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
		$adapter = $this->getController()->getServiceLocator()->get('Zend\Db\Adapter\Adapter');	
		$dbRole = new Role($adapter);
		$actInfo = $dbRole->getAccessById($ctrl_act);
		if($actInfo){
			self::setCache("acl_help",$actInfo['acl_help']);
		}
		
		//通过批量生成的账户没有domain的情况下强制跳转至'账号设置'页面
		if($user->power == 0 && !in_array($ctrl_act,$this->_getIgnoreActions())){
			
			$response = $e->getResponse();
            $response->setStatusCode(302);
            $response->getHeaders()->addHeaderLine('Location', "/users/edit");
            $e->stopPropagation();
			return FALSE;
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
		$adapter = $this->getController()->getServiceLocator()->get('Zend\Db\Adapter\Adapter');	
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
			'commodity_ajaxupload',
			'commodity_ajaxremovefile',
			'type_ajaxupdatesorting',
			'users_clearcache'
		);
	}
	
	
	/**
	 *
	 * @取得SESSION
	 * @param String $key        	
	 * @return Ambigous <boolean, unknown>
	 */
	public function getSession($name, $key,$_cookieId = NULL) {
		$name = self::_getIdentify($name,$_cookieId);
		return self::getCache($name,$key);
	}
	
	
	public function setSession($name, $data,$expire = NULL,$_cookieId = NULL) {
		if (! $data || ! is_array ( $data ))
			return false;
		
		$expire = is_null($expire) ? 7200 : $expire;	
		$name = self::_getIdentify($name,$_cookieId);
		self::setCache($name,$data,$expire);
		return true;
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
		self::setSession('auth',array('user'=>$user),NULL,$user->real_domain.$user->id);
	}
	
	/**
	 * 获取Admin模块账户登录信息 
	 * 
	 */
	public function getUserSesion()
	{
		$_cookieId = isset($_POST['_identify']) ? $_POST['_identify'] : NULL;
		$_identify = self::_getIdentify(NULL,$_cookieId);
		$domain = self::getSubDomain();
		if($domain == "login" || preg_match("/".$domain."/",$_identify)){
			$data = $this->getSession("auth","user",$_identify);
			if(!$data){
				return FALSE;
			}
			return $data;
		}
		return FALSE;
	}
	
	
	
	//-------------------------以下为Memcache相关操作--------------------
	
	static function _getIdentify($key = NULL,$_cookieId = NULL)
	{
		if(!is_null($_cookieId)){
			$_identify = $_cookieId;
		}else{
			$_identify = isset($_COOKIE["_identify"]) ? $_COOKIE["_identify"] : NULL;	
		}
		if(!$_identify){
			return FALSE;
		}
		$_identify = is_null($key) ? $_identify : $_identify."_".$key;//跨域数据唯一标识
		return $_identify;
	}
	
	/**
	 *
	 * @todo 链接Memcache
	 * @return \Memcache
	 */
	static function localCache() {
		$mc = new \Memcache;
		$mc->connect ( "localhost", 11211 );
		return $mc;
	}
	
	/**
	 *
	 * @todo 将数据写入到 Memcache
	 * @param String $key        	
	 * @param string $value        	
	 * @param string $time        	
	 * @return boolean
	 */
	static function setCache($key, $value, $time = null) {
		$key = self::mapCacheKey($key);
		if (strlen ( $key ) > 128)
			return false;
		$time = $time ? $time : 3600 * 24 * 15;
		$cache = @self::localCache ();
		if (! $cache)
			return false;
		if(!$key){
			return FALSE;
		}	
		if ($cache->get ( $key )) {
			return $cache->replace ( $key, $value, MEMCACHE_COMPRESSED, $time );
		}
		return $cache->set ( $key, $value, MEMCACHE_COMPRESSED, $time );
	}
	/**
	 *
	 * @todo 读取 Memcache 数据
	 * @param String $key        	
	 * @return boolean string
	 */
	static function getCache($key,$item = NULL) {
		$key = self::mapCacheKey($key);
		if (strlen ( $key ) > 128)
			return false;
		$cache = @self::localCache ();
		if (! $cache)
			return false;
		if(!$key){
			return FALSE;
		}	
		$data = $cache->get ( $key );
		if(!empty($item) && isset($data[$item])) {
			return $data[$item];
		}	
		return $data;
	}
	
	/**
	 *
	 * @todo 删除缓存
	 * @param string $key        	
	 * @return boolean
	 */
	static function delCache($key) {
		$key = self::mapCacheKey($key);
		if (strlen ( $key ) > 128)
			return false;
		$cache = @self::localCache ();
		if (! $cache)
			return false;
		if(!$key){
			return FALSE;
		}	
		$cache->delete ( $key );
	}
	
	
	/**
	 * 取得带有域的缓存key 例如 testyourdomain.com 
	 * @param undefined $key
	 * 
	 */
	static function mapCacheKey($key)
	{
		$domain = $_SERVER ['HTTP_HOST'];
		$domain = substr($domain,strpos($domain,".")+1);
		return $key.=$domain;	
	}
	
}
?>