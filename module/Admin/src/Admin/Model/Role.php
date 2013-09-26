<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\ResultSet\AbstractResultSet;
use library\Helper\HCommon;


class Role
{

    private $adapter;
	
	public $_menuCache = 10;//导航菜单缓存
	public $_aclCache = 20;//操作权限缓存

    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }

    

    /**
     *
     * @todo 添加角色
     * @param unknown $data            
     * @return Ambigous <boolean, number, \Zend\Db\TableGateway\mixed>
     */
    function addRole ($data)
    {
        $table = new TableGateway('wx_roles', $this->adapter);
        $table->insert($data);
        $tid = $table->getLastInsertValue();
		if(!$tid){
			return FALSE;
		}
		$table->update(array('sort_id'=>$tid),array('role_id'=>$tid));//自动更新排序ID
        return TRUE;
    }

    /**
     *
     * @todo 编辑账号
     * @param Int $id            
     * @param Array $data            
     */
    function editRole ($role_id, $data)
    {
        $role_id = (int) $role_id;
        if ($role_id) {
            $table = new TableGateway('wx_roles', $this->adapter);
            $flag = $table->update($data, array('role_id' => $role_id));
            return $flag;
        }
        return FALSE;
    }

    /**
     *
     * @todo 取得单条去角色信息
     * @param int $role_id 角色ID
	 * @param int $owner_id 角色创建者ID 
	 * @param char $owner_domain 角色创建者所在域           
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getRole ($role_id,$owner_id = 0,$owner_domain = NULL)
    {
        $table = new TableGateway('wx_roles', $this->adapter);
		$where = array();
		if($role_id){
			$where['role_id'] = $role_id; 
		}
		if($owner_id){
			$where['owner_id'] = $owner_id;
		}
		if($owner_domain){
			$where['owner_domain'] = $owner_domain;
		}
        $rowset = $table->select($where);
        $row = $rowset->current();
        return $row ? $row : FALSE;
    }
    
    /**
     *
     * @todo 取得角色列表
     * @param unknown $page            
     * @return \Zend\Paginator\Paginator
     */
    function roleList($page,$role_id,$nums=30,$keywords=NULL,$owner_domain = NULL)
    {
        $select = new Select('wx_roles');
		$where = "(role_id = '$role_id' OR (owner_id = '$role_id' AND owner_domain = '$owner_domain'))";
		if ($keywords)
    	{
    		$where .=" AND role_name like '%{$keywords}%'";
		}
   		$select->where($where);
		$select->order('sort_id ASC');
		
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($nums)->setCurrentPageNumber($page);
        return $paginator;
    }
	
	/**
	 * 查找当前指定角色下的所有子角色 
	 * @param undefined $role_id
	 * @return array()
	 */
	function getSelectRoles($role_id = 0,$owner_domain = NULL)
	{
		$table = new TableGateway('wx_roles', $this->adapter);
		$select = $table->getSql()->select();
		$select->where("role_id = '$role_id' OR (owner_id='$role_id' AND owner_domain='$owner_domain')");// 
		$rowSet = $table->selectWith($select);
		return $rowSet->toArray();
	}
	
    /**
     * @todo 删除角色
     * @param int $id
     * @return boolean
     */
    function delRole($role_id,$owner_id,$owner_domain)
    {
    	$role_id = (int)$role_id;
		$table = new TableGateway('wx_roles', $this->adapter);
		
		
		//删除当前角色
    	$flag = $table->delete(array('role_id'=>$role_id,'owner_id'=>$owner_id,'owner_domain'=>$owner_domain));
		if(!$flag){
			return FALSE;
		}
		
		//查找该角色的子角色并删除
		$rows = $table->select(array('owner_id'=>$role_id));
		if($rows->count()>0){
			foreach($rows as $r){
				$this->delRole($r->role_id,$r->owner_id,$r->owner_domain);
			}
		}
    	return TRUE;
    }
	
	/**
     *
     * @todo 检测role_name是否存在
     * @param Int $uid
     * @return Ambigous <number, NULL>
     */
    function checkRoleExisted ($role_name,$owner_id = 0,$owner_domain = NULL)
    {
    	$table = new TableGateway('wx_roles', $this->adapter);
    	$rows = $table->select(array(
    			'role_name' => $role_name,
				'owner_id'	=> $owner_id,
				'owner_domain' => $owner_domain
    	));
    	if($rows->count()> 0){
			return TRUE;
		}
    	return FALSE;
    }
	
	//-------------以下方法与操作菜单相关-----------------
	
	
	/**
	 * 父角色给子角色分配权限 
	 * @param undefined $acl_id int|array
	 * @param undefined $role_pid int
	 * @param undefined $role_sid int
	 * @return array(true|false,Msg) 
	 */
	function assignAcl($acl_id,$role_pid,$role_sid)
	{
		if(empty($acl_id) && !is_array($acl_id)){
			return array(FALSE,"您没有选择要分配的权限");
		}
		if(!$this->_checkIsParentSub($role_pid,$role_sid)){
			return array(FALSE,"您没有权限给当前角色分配操作权限");
		}
		$existedAcl = $this->_getRoleAcl($role_sid);
		if($existedAcl == array_values($acl_id)){
			return array(FALSE,"权限未作更新");
		}
		
		//删除现有权限并添加新的权限
		$table = new TableGateway('wx_role_acl',$this->adapter);
		$defDiff = array_values(array_diff($existedAcl,$acl_id));
		$addDiff = array_values(array_diff($acl_id,$existedAcl));
		if(!empty($defDiff)){
			$del_sql = "DELETE FROM `wx_role_acl` WHERE role_id = '$role_sid' AND acl_id IN(".implode(',',$defDiff).")";
			if(!$this->adapter->query($del_sql,"execute")){
				return array(FALSE,"未能删除旧有权限,新的权限添加不成功");
			}
		}
		
		foreach($addDiff as $item){
			$table->insert(array(
				'role_id'=>$role_sid,'acl_id'=>$item
			));
		}
		
		//递归删除下级角色多余权限
		$this->_delSubRoleExtAcl($role_sid,$defDiff);
		$this->delAccessCache($role_pid,$this->_menuCache);
		$this->delAccessCache($role_pid,$this->_aclCache);
		return array(TRUE,"当前角色权限更新成功");
	}
	
	/**
	 * 递归删除下级角色多余权限
	 * @param undefined $role_pid
	 * @param undefined $extAcl
	 * 
	 */
	private function _delSubRoleExtAcl($role_pid,$extAcl = array())
	{
		if(empty($extAcl)){
			return FALSE;
		}
		$table = new TableGateway('wx_roles',$this->adapter);
		$rowSet = $table->select(array(
			'owner_id'=> $role_pid
		));
		if($rowSet->count() > 0){
			$rows = $rowSet->toArray();
			foreach($rows as $r){
				$del_sql = "DELETE FROM `wx_role_acl` WHERE role_id = '".$r['role_id']."' AND acl_id IN(".implode(',',$extAcl).")";
				if(!$this->adapter->query($del_sql,"execute")){
					continue;
				}
				$this->_delSubRoleExtAcl($r['role_id'],$extAcl);
			}
		}
	}
	
	/**
	 * 获取操作菜单树结构 
	 * @param undefined $pid
	 * @param int $role_id 当前登录账户的角色ID
	 * @return array
	 * 
	 */
	function getAccessTree($pid=0,$role_id = 0,$domain = NULL)
	{
		//从缓存提取数据
		$key = "act_".$pid."_".$role_id;
        if(!is_null($domain)){
            $key = $domain."_".$key;
        }
		$data = HCommon::getCache($key);
		if($data){
			return $data;
		}
		//从Db提取数据
		$dbRst = $this->_getAccessTree($pid,$role_id,$domain);
		
		//将数据写入缓存
		if($dbRst){
			HCommon::setCache($key,$dbRst);
		}
		return $dbRst;
	}
	 
	private function _getAccessTree($pid=0,$role_id = 0,$domain = NULL)
	{
		$column = is_null($domain) ? "" : ",t3.alias_label";
		$sql = "SELECT t1.*{$column} FROM wx_acl AS t1 INNER JOIN wx_role_acl AS t2 ON t1.acl_id = t2.acl_id AND t2.role_id = '$role_id'";
        if(!is_null($domain)){
            $sql.= " LEFT JOIN `wx_acl_alias` AS t3 ON t1.acl_id = t3.acl_id AND t1.alias_key = t3.alias_key AND t3.domain = '$domain'";    
        }
        $sql.= " WHERE t1.parent_id = '$pid' Order By t1.acl_sorting ASC";
        
		$rowSet = $this->adapter->query($sql,'execute');
		
		$dbRst = array();
		if($rowSet){
			$rows = $rowSet->toArray();
			foreach($rows as $r){
				$r['id'] = $r['acl_id'];
				$r['is_child'] = $r['parent_id'] > 0 ? TRUE : FALSE;
                
                if(isset($r['alias_label']) && !empty($r['alias_label'])){
                    $r['acl_name'] = $r['alias_label'];
                }
                $r['name'] = $r['acl_name'];
				$r['sub_tree'] = $this->_getAccessTree($r['acl_id'],$role_id,$domain);
				$dbRst[] = $r;
			}
		}
		return $dbRst;
	}
    
	
	/**
	 * 查找当前角色创建的所有子角色 
	 * @param undefined $role_id
	 * 
	 */
	function getSubRoles($role_id = 0,$owner_domain = NULL)
	{
		$table = new TableGateway('wx_roles',$this->adapter);
		$where = array(
			'owner_id'=>$role_id,
			'owner_domain'=>$owner_domain
		);
		$rowSet = $table->select($where);
		return $rowSet->count()>0 ? $rowSet->toArray() : FALSE;
	}
	
	
	/**
	 * 查找某个角色当前拥有的操作权限,用于执行Controller/action判断 + 生成导航菜单时使用 
	 * @param undefined $role_id
	 * @return array()
	 * 
	 */
	function getExistedRoleAccess($role_id = 0)
	{
		//从缓存提取数据
		$key = "acl_".$role_id;
		$data = HCommon::getCache($key);
		if($data){
			return $data;
		}
		
		//查找当前账户的可操作权限
		$sql = "SELECT t1.* FROM wx_acl AS t1 INNER JOIN wx_role_acl AS t2 ON t1.acl_id = t2.acl_id AND t2.role_id = '$role_id'";
		$rowSet = $this->adapter->query($sql,'execute');
		
		$dbRst = array();
		if($rowSet->count() > 0){
			$rows = $rowSet->toArray();
			foreach($rows as $r){
				$dbRst[$r['act_key']] = $r['acl_url'];
			}
		}
		
		//将数据写入缓存
		if($dbRst){
			HCommon::setCache($key,$dbRst);
		}
		
		return $dbRst;
	}
	
	
	/**
	 * 查找某个角色当前拥有的操作权限并生成节点树结构,用于查看/选择当前角色权限时使用 
	 * @param undefined $role_pid 父角色ID,也是当前登录账户的角色ID,此账户可以从自身拥有的最大权限中给其子角色分配权限
	 * @param int $role_sid 被分配权限的子角色ID
	 * @return array()
	 * 
	 */
	function getRoleAccessTree($role_pid,$role_sid)
	{
		//检测父子从属关系
		if(!$this->_checkIsParentSub($role_pid,$role_sid)){
			return FALSE;
		}
		
		//获取父角色拥有的权限
		$pAccess = $this->getAccessTree(0,$role_pid);
		
		//获取子角色拥有的权限
		$childAcl = $this->_getRoleAcl($role_sid);
		return array($pAccess,$childAcl);
	}
	
	
	/**
	 * 获取某个角色当前拥有的权限 
	 * @param undefined $role_id
	 * 
	 */
	private function _getRoleAcl($role_id = 0)
	{
		$table = new TableGateway('wx_role_acl',$this->adapter);
		$rowSet = $table->select(array('role_id'=>$role_id));
		
		$childAcl = array();
		if($rowSet->count() > 0){
			$rows = $rowSet->toArray();
			foreach($rows as $r){
				array_push($childAcl,$r['acl_id']);
			}
		}
		return $childAcl;
	}
	
	
	/**
	 * 检测角色的父子从属关系 
	 * 
	 */
	private function _checkIsParentSub($role_pid,$role_sid)
	{
		$role_pid = intval($role_pid);
		$role_sid = intval($role_sid);
		$table = new TableGateway('wx_roles',$this->adapter);
		$rows = $table->select(array(
				'owner_id'	=> $role_pid,
				'role_id' => $role_sid
    	));
    	if($rows->count()> 0){
			return TRUE;
		}
    	return FALSE;
	}
	
	
	/**
	 * 添加一个新的操作 
	 * @param undefined $data
	 * @return array()
	 */
	function addAccess($data,$role_id = 0)
	{
		
		$table = new TableGateway('wx_acl', $this->adapter);
		$table->insert($data);
        $tid = $table->getLastInsertValue();
        if($tid){
			$this->_assignAccess2Role($tid,$role_id);//将新增操作标识分配权限给当前角色
			$this->_updateSorting($tid);//更新排序
			$this->delAccessCache($role_id,$this->_menuCache);//删除菜单相关缓存
			return array(TRUE,"新的操作添加成功");
		}
		return array(FALSE,"新的操作添加失败");	
	}
	
	
	/**
	 * 删除角色相关的操作权限缓存数据 
	 * @param undefined $role_id
	 * @param undefined $cache_type
	 * 
	 */
	public function delAccessCache($role_id,$cache_type = NULL)
	{
		$cachePrefix = $this->_mapCachePrefix($cache_type);
		if(!$cachePrefix){
			return FALSE;
		}
		
		//递归清除缓存
		$this->_clearCacheRev($role_id,$cachePrefix);
	}
	
	
	
	/**
	 * 递归清除缓存 - 当父role_id对应缓存被清除后，其子role_id的缓存也将被清除 
	 * @param undefined $role_id
	 * @param undefined $cache_key
	 * 
	 */
	private function _clearCacheRev($role_id,$cachePrefix)
	{
		HCommon::delCache($cachePrefix.$role_id);
		$table = new TableGateway('wx_roles',$this->adapter);
		$rowSet = $table->select(array('owner_id'=>$role_id));
		if($rowSet->count() > 0){
			$rows = $rowSet->toArray();
			foreach($rows as $r){
				$this->_clearCacheRev($r['role_id'],$cachePrefix);
			}
		}		
	}
	
	
	/**
	 * 获取缓存key-prefix 
	 * @param undefined $cache_type
	 * 
	 */
	private function _mapCachePrefix($cache_type = NULL)
	{
		$data = array(
			$this->_menuCache => 'act_0_',
			$this->_aclCache => 'acl_'
		);
		if(isset($data[$cache_type])){
			return $data[$cache_type];
		}
		return FALSE;
	}
	
	
	/**
	 * 为某个角色分配操作标识 
	 * @param undefined $acl_id
	 * @param undefined $role_id
	 * 
	 */
	private function _assignAccess2Role($acl_id,$role_id = 0)
	{
		$table = new TableGateway('wx_role_acl', $this->adapter);
		$table->insert(array('role_id'=>$role_id,'acl_id'=>$acl_id));
	}
	
	/**
	 * 更新操作序号
	 * @param undefined $acl_id
	 * @param undefined $max_id
	 * 
	 */
	private function _updateSorting($max_id = 0)
	{
		$table = new TableGateway('wx_acl', $this->adapter);
		$table->update(array('acl_sorting'=>$max_id),array('acl_id' => $max_id));	
	}
	
	/**
	 * 可通过acl_id或act_key(操作唯一标识controller_action)来读取操作信息 
	 * @param undefined $acl_id
	 * 
	 */
	function getAccessById($acl_id = 0)
	{
		$where = is_int($acl_id) ? array('acl_id'=>$acl_id) : array('act_key'=>$acl_id);
		$table = new TableGateway('wx_acl', $this->adapter);
        $rowset = $table->select($where);
        $row = $rowset->current();
        return $row ? $row : FALSE;
	} 
	
	
	/**
     *
     * @todo 编辑操作
     * @param $acl_id            
     * @param Array $data            
     */
    function editAccess ($acl_id, $data)
    {
       $acl_id = intval($acl_id);
        if ($acl_id) {
            $table = new TableGateway('wx_acl', $this->adapter);
            $flag = $table->update($data, array('acl_id' => $acl_id));
            return $flag;
        }
        return FALSE;
    }
	
	/**
     * @todo 删除当前操作
     * @param int $id
     * @return boolean
     */
    function delAccess($acl_id)
    {
    	$acl_id = (int)$acl_id;
		$table = new TableGateway('wx_acl', $this->adapter);
		
		
		//删除当前操作
    	$flag = $table->delete(array('acl_id'=>$acl_id));
		if(!$flag){
			return FALSE;
		}
		//查找该操作的子操作并删除
		$rows = $table->select(array('parent_id'=>$acl_id));
		if($rows->count()>0){
			foreach($rows as $r){
				$this->delAccess($r->acl_id);
			}
		}
    	return TRUE;
    }
	
	
	/**
	 * 更新操作排序序号
	 * @param undefined $acl_sorting
	 * @param undefined $acl_id
	 * 
	 */
	function updateAccessSorting($acl_sorting,$acl_id)
	{
		 $table = new TableGateway('wx_acl', $this->adapter);
         $flag = $table->update(array('acl_sorting'=>intval($acl_sorting)), array('acl_id' => intval($acl_id)));
         return $flag;
	}
	/**
	 * @todo 取得所有角色
	 * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
	 */
	function getAllRole()
	{
		$table = new TableGateway('wx_roles', $this->adapter);
		$rows = $table->select('role_id >1');
		return $rows;
	}
    
    
    /**
     *
     * @todo 取得别名操作列表
     * @param unknown $page            
     * @return \Zend\Paginator\Paginator
     */
    function access4AliasList($role_id,$page,$nums=20)
    {
        
        $acls = $this->_getRoleAcl($role_id);
        if(count($acls) < 1){
            return FALSE;
        }
        $select = new Select('wx_acl');
        $where = "acl_id IN(".implode(',',$acls).") AND alias_key IS NOT NULL";
   		$select->where($where);
		$select->order('acl_sorting ASC');
		
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($nums)->setCurrentPageNumber($page);
        return $paginator;
    }
    
    /**
     * 获取操作项别名信息 
     * @param undefined $acl_id
     * @param undefined $domain
     * @param undefined $lang
     * 
     */
    function getAlias($acl_id,$domain,$lang = "cn")
    {
        $sql = "SELECT t1.acl_name,t1.alias_key,t2.alias_icon,t2.alias_label FROM `wx_acl` as t1 LEFT JOIN `wx_acl_alias` as t2";
        $sql.= " ON t1.acl_id = t2.acl_id AND t2.domain = '$domain' AND lang = '$lang' WHERE t1.acl_id = '$acl_id' LIMIT 1";
        $rowSet = $this->adapter->query($sql,"execute");
        if($rowSet->count() > 0){
            return $rowSet->current();
        }
        return FALSE;
    }
    
    
    /**
     * 别名Icon 
     * @param undefined $domain
     * @param undefined $lang
     * @return array() 
     * 参考数组结构 array(
     *                 '_aboutus'=>"icon icon-group",
     *                 '_contact'=>"icon-camera"
     *              )
     * 
     */
    function getAliasIcons($domain,$alias_key = NULL,$lang = "cn")
    {
        $table = new TableGateway('wx_acl_alias', $this->adapter);
		$where = array(
            'domain' => $domain,
            'lang' => $lang
        );
        if($alias_key){
            $where['alias_key'] = $alias_key;
        }
        $rowSet = $table->select($where);
        
        $dbRst = array();
        if($rowSet->count() > 0){
            foreach($rowSet as $r){
                $dbRst[$r->alias_key] = $r->alias_icon;
            }
        }
        return $dbRst;
    }
    
    
    /**
     * 别名映射信息 
     * @param undefined $domain
     * @param undefined $lang
     * 
     */
    function getAliasMapping($domain,$lang = "cn")
    {
        $table = new TableGateway('wx_acl_alias', $this->adapter);
		$rowSet = $table->select(array(
            'domain' => $domain,
            'lang' => $lang
        ));
        
        $dbRst = array();
        if($rowSet->count() > 0){
            foreach($rowSet as $r){
                $dbRst[$r->alias_key] = $r->alias_label;
            }
        }
        return $dbRst;
    }
    
    
    /**
     * 获取别名label 
     * @param undefined $domain
     * @param undefined $alias_key
     * @param undefined $def_label
     * @param undefined $lang
     * 
     */
    function getAliasLabel($domain,$alias_key,$def_label = NULL,$lang = "cn")
    {
       $table = new TableGateway('wx_acl_alias',$this->adapter);
       $rowSet = $table->select(array(
           'alias_key' => $alias_key,
           'lang' => $lang,
           'domain' => $domain
       ));
       if($rowSet->count() > 0){
           $label = $rowSet->current()->alias_label;
           if(!empty($label)){
               return $label;
           } 
       } 
       return $def_label;
    }
    
    
    /**
	 * 保存别名信息 
	 * @param undefined $data
	 * @param undefined $classid
	 * @param undefined $domain
	 * 
	 */
	function saveAliasInfo($data)
	{
		/*if(!isset($data['acl_id']) || empty($data['acl_id'])){
			return FALSE;
		}
        if(!isset($data['alias_key']) || empty($data['alias_key'])){
			return FALSE;
		}
		
        if(!isset($data['alias_label']) || empty($data['alias_label'])){
			return FALSE;
		}
		
        if(!isset($data['alias_icon']) || empty($data['alias_icon'])){
			return FALSE;
		}
        
        if(!isset($data['domain']) || empty($data['domain'])){
			return FALSE;
		}*/
		//新增|更新内容
		$table = new TableGateway('wx_acl_alias',$this->adapter);
		
		$flag = FALSE;
        $d = array(
            'acl_id' => $data['acl_id'],
            'alias_key' => $data['alias_key'],
            'alias_label' => $data['alias_label'],
            'alias_icon' => $data['alias_icon'],
            'domain' => $data['domain']
        );
		if(!$this->_checkAliasExisted($data['acl_id'],$data['alias_key'],$data['domain'])){
			//新增
			$flag = $table->insert($d);
		}else{
			//更新
            $d = array('alias_label' => $data['alias_label'],'alias_icon' => $data['alias_icon']);
            $where = array(
                'acl_id' => $data['acl_id'],
                'alias_key' => $data['alias_key'],
                'domain' => $data['domain']
            );
			$flag = $table->update($d,$where);
		}
		return $flag;
	}
    
    private function _checkAliasExisted($acl_id,$alias_key,$domain)
    {
        $table = new TableGateway('wx_acl_alias', $this->adapter);
	    $rowSet = $table->select(array(
            'acl_id' => $acl_id,
            'alias_key' => $alias_key,
            'domain' => $domain
        ));
        if($rowSet->count() > 0){
            return TRUE;
            //return $rowSet->current();
        }
        return FALSE;
		
    }
}