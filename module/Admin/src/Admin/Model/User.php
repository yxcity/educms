<?php

namespace Admin\Model;

use Zend\Authentication\Adapter\DbTable as AuthAdapter;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use library\Helper\HCommon;

class User {
	private $adapter;
	private $_dmPrefix = "dm_"; // domain缓存key - prefix
	function __construct($adapter) {
		$this->adapter = $adapter;
	}
	function auth($data) {
		$authAdapter = new AuthAdapter ( $this->adapter );
		$authAdapter->setTableName ( 'users' )->setIdentityColumn ( 'username' )->setCredentialColumn ( 'password' )->setIdentity ( $data ['username'] )->setCredential ( $data ['password'] );
		$authAdapter->authenticate ();
		$res = $authAdapter->getResultRowObject ( array (
				'id',
				'username',
				'realname',
				'domain',
				'power',
				'uid', // 当前登录账户所属父ID
				'roleid', // 新增登录时的角色信息,
				'validity', // 账户有效期,
				'userType'  // 账户类型
				) );
		if (! $res) {
			return FALSE;
		}
		/*
		 * if($res->uid > 0 && $res->power < 2){ $table = new TableGateway('users', $this->adapter); $row = $table->select(array('id'=>$res->uid))->current(); if($row){ $res->real_domain = $row->username; } }else{ $res->real_domain = $res->username;//供跨域登录跳转使用 }
		 */
		if (! empty ( $res->domain )) {
			$res->real_domain = $res->domain;
		}
		return $res;
	}
	
	/**
	 *
	 * @todo 添加账号
	 * @param unknown $data        	
	 * @return Ambigous <boolean, number, \Zend\Db\TableGateway\mixed>
	 */
	function addUser($data, $config = null) {
		$table = new TableGateway ( 'users', $this->adapter );
		$table->insert ( $data );
		$tid = $table->getLastInsertValue ();
		if ($tid) {
			$config ['id'] = $tid;
			self::addUserConfig ( $config );
			return $tid;
		}
		return false;
	}
	
	/**
	 *
	 * @todo 添加账号扩展信息
	 * @param type $data        	
	 * @return type
	 */
	function addUserConfig($data) {
		$table = new TableGateway ( 'users_config', $this->adapter );
		$table->insert ( $data );
		$tid = $table->getLastInsertValue ();
		return $tid;
	}
	
	/**
	 *
	 * @todo 编辑账号
	 * @param Int $id        	
	 * @param Array $data        	
	 */
	function editUser($id, $data = null, $config = null) {
		$id = ( int ) $id;
		if ($id) {
			if ($data) {
				$table = new TableGateway ( 'users', $this->adapter );
				$table->update ( $data, array (
						'id' => $id 
				) );
			}
			
			// $this->_delDMCache($id); //删除domain缓存
			if ($config) {
				self::editUserConfig ( $id, $config );
			}
			self::mkUserCache ( $id );
			return true;
		}
		return false;
	}
	
	/**
	 *
	 * @todo 编辑用户Config 表
	 * @param int $uid        	
	 * @param array $data        	
	 * @return boolean
	 */
	function editUserConfig($id, $data) {
		$table = new TableGateway ( 'users_config', $this->adapter );
		$table->update ( $data, array (
				'id' => $id 
		) );
		// $this->_delDMCache($id); //删除domain缓存
		self::mkUserCache ( $id );
		return true;
	}
	
	/**
	 *
	 * @todo 写入配置换成
	 * @param type $id        	
	 * @return type
	 */
	function mkUserCache($id) {
		$table = new TableGateway ( 'users', $this->adapter );
		$rowset = $table->select ( array (
				'id' => $id 
		) );
		$row = $rowset->current ();
		if ($row) {
			HCommon::setCache ( $this->_mapDMCacheKey ( $row ['domain'] ), $row );
			$config = self::getUserConfig ( $row ['id'] );
			if (! $config) {
				self::addUserConfig ( array (
						'id' => $row ['id'],
						'uid' => $row ['uid'] 
				) );
				$config = self::getUserConfig ( $row ['id'] );
			}
			$row = array_merge ( ( array ) $config, ( array ) $row );
			if ($row) {
				HCommon::setCache ( $this->_mapDMCacheKey ( $id ), $row );
				return $row;
			}
		}
	}
	
	/**
	 *
	 * @todo 取得单条账号信息
	 * @param int $id        	
	 * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
	 */
	function getUser($id = null, $username = null, $email = null) {
		$key = $this->_mapDMCacheKey ( $id );
		if ($id) {
			$data = HCommon::getCache ( $key );
			if ($data) {
				return $data;
			}
		}
		
		$table = new TableGateway ( 'users', $this->adapter );
		
		if ($id) {
			$where = array (
					'id' => $id 
			);
		}
		
		if ($username) {
			$where = array (
					'username' => $username 
			);
		}
		if ($email) {
			$where = array (
					'email' => $email 
			);
		}
		$rowset = $table->select ( $where );
		$row = $rowset->current ();
		if ($row) {
			$config = self::getUserConfig ( $row ['id'] );
			if (! $config) {
				self::addUserConfig ( array (
						'id' => $row ['id'],
						'uid' => $row ['uid'] 
				) );
				$config = self::getUserConfig ( $row ['id'] );
			}
			$row = array_merge ( ( array ) $config, ( array ) $row );
			if ($row) {
				HCommon::setCache ( $key, $row );
				return $row;
			}
		}
		return false;
	}
	
	/**
	 *
	 * @todo 读取用户User_Config
	 * @param type $id        	
	 * @return type
	 */
	function getUserConfig($id) {
		$table = new TableGateway ( 'users_config', $this->adapter );
		$rowset = $table->select ( array (
				'id' => $id 
		) );
		$row = $rowset->current ();
		return $row ? $row : false;
	}
	
	/**
	 *
	 * @todo 按访问地址取得用户
	 * @param String $domain        	
	 * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>
	 */
	function clickDomain($domain) {
		$table = new TableGateway ( 'users', $this->adapter );
		$rowSet = $table->select ( "domain = '{$domain}' AND power >= '2'" );
		$row = $rowSet->current ();
		// 从缓存提取数据
		$key = $this->_mapDMCacheKey ( $row ['id'] );
		$data = HCommon::getCache ( $key );
		
		if ($data) {
			return $data;
		} else {
			$row = self::mkUserCache ( $row ['id'] );
		}
		return $row ? $row : false;
	}
	
	/**
	 * 获取某个domain的缓存key
	 * 
	 * @param undefined $domain        	
	 *
	 */
	private function _mapDMCacheKey($domain) {
		return $this->_dmPrefix . $domain;
	}
	
	/**
	 * 删除某个domain的缓存信息
	 * 
	 * @param undefined $userId        	
	 *
	 */
	private function _delDMCache($userId) {
		$user = $this->getUser ( $userId );
		if ($user->domain) {
			HCommon::delCache ( $this->_mapDMCacheKey ( $user->domain ) );
		}
	}
	
	/**
	 *
	 * @todo 验证用户名是否重复
	 * @param string $username        	
	 * @param int $id        	
	 * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>
	 */
	function clickUserName($username, $id) {
		$table = new TableGateway ( 'users', $this->adapter );
		$rowSet = $table->select ( "username = '{$username}' AND id != '{$id}'" );
		$row = $rowSet->current ();
		return $row ? $row : false;
	}
	
	/**
	 *
	 * @todo 取得账号列表
	 * @param unknown $page        	
	 * @return \Zend\Paginator\Paginator
	 */
	function userList($page, $user, $nums = 30, $keywords = null) {
		$select = new Select ( 'users' );
		$select->columns ( array(
				/* 'id',
				'username',
				'realname',
				'email',
				'addtime',
				'validity',
				'power' */
				'*' 
		) );
		if ($user->power < 3) {
			$where = " 1 ";
			$where .= " AND uid='{$user->id}'";
			if ($keywords) {
				$where .= " AND (username like '%{$keywords}%' or realname like '%{$keywords}%')";
			}
		}
		if ($user->power == 3) {
			$where = " uid = '0' AND domain!='0' AND addtime > '0'";
			if ($keywords) {
				$where .= " AND (username like '%{$keywords}%' or realname like '%{$keywords}%')";
			}
		}
		$select->where ( $where );
		$select->order ( 'id desc' );
		$adapter = new DbSelect ( $select, $this->adapter );
		$paginator = new Paginator ( $adapter );
		$paginator->setItemCountPerPage ( $nums )->setCurrentPageNumber ( $page );
		return $paginator;
	}
	
	/**
	 *
	 * @todo 删除账号
	 * @param int $id        	
	 * @return boolean
	 */
	function delUser($id) {
		$id = ( int ) $id;
		$table = new TableGateway ( 'users', $this->adapter );
		$table->delete ( array (
				'id' => $id 
		) );
		$this->_delDMCache ( $id );
		@unlink ( "data/user/{$id}.ini" );
		return true;
	}
	
	/**
	 *
	 * @todo 添加收件地址
	 * @param unknown $data        	
	 * @return Ambigous <boolean, number>
	 */
	function addaddress($data) {
		$table = new TableGateway ( 'address', $this->adapter );
		$table->insert ( $data );
		$tid = $table->getLastInsertValue ();
		return $tid ? $tid : false;
	}
	
	/**
	 *
	 * @todo 读取用户收件地址
	 * @param String $uid        	
	 * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
	 */
	function addressList($uid) {
		$table = new TableGateway ( 'address', $this->adapter );
		$rows = $table->select ( array (
				'uid' => $uid 
		) );
		return $rows;
	}
	
	/**
	 *
	 * @todo 读取地址信息
	 * @param int $id        	
	 * @return Ambigous <multitype:, ArrayObject, NULL, \ArrayObject, unknown>
	 */
	function getAddress($id) {
		$table = new TableGateway ( 'address', $this->adapter );
		$rowSet = $table->select ( array (
				'id' => $id 
		) );
		$row = $rowSet->current ();
		return $row;
	}
	
	/**
	 *
	 * @todo 修改收件地址
	 * @param Array $data        	
	 * @param String $uid        	
	 * @param Int $id        	
	 * @return boolean
	 */
	function defaultAddress($data, $uid, $id = null) {
		$table = new TableGateway ( 'address', $this->adapter );
		if ($id) {
			$table->update ( $data, array (
					'uid' => $uid,
					'id' => $id 
			) );
		} else {
			$table->update ( $data, array (
					'uid' => $uid 
			) );
		}
		return true;
	}
	
	/**
	 *
	 * @todo 取得省市级名
	 * @param number $pid        	
	 * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
	 */
	function areas($pid = 0) {
		$table = new TableGateway ( 'areas', $this->adapter );
		$rows = $table->select ( array (
				'parentid' => $pid 
		) );
		return $rows;
	}
	
	/**
	 * @用户登录日志
	 * 
	 * @param Array $data        	
	 */
	function logining($data) {
		$table = new TableGateway ( 'log_login', $this->adapter );
		$table->insert ( $data );
		
		self::editUser ( $data ['uid'], array (
				'lastTime' => $data ['addtime'],
				'lastIP' => $data ['ip'] 
		) );
		$this->adapter->query ( 'UPDATE `users` SET `loginCount`=`loginCount`+1 WHERE `id` = ?', array (
				$data ['uid'] 
		) );
	}
	
	/**
	 *
	 * @todo 提交提问
	 * @param Array $data        	
	 * @return number
	 */
	function addAsk($data) {
		$table = new TableGateway ( 'faqs', $this->adapter );
		$table->insert ( $data );
		$tid = $table->getLastInsertValue ();
		return $tid;
	}
	
	/**
	 *
	 * @todo 问题列表
	 * @param int $page        	
	 * @param Object $user        	
	 * @return \Zend\Paginator\Paginator
	 */
	function answers($page, $user, $nums = 30, $keywords = null) {
		$select = new Select ( 'faqs' );
		$where = " 1 ";
		if ($user->power < 3) {
			//$where .= " AND uid='{$user->id}' and fid='0'";
			$where .= " AND uid='{$user->id}'";
		}else{
			//$where .= " and fid='0'";		    
		}
		if ($keywords) {
			$where .= " AND ask like '%{$keywords}%'";
		}
		$select->where ( $where );
		$select->order ( 'id desc' );
		$adapter = new DbSelect ( $select, $this->adapter );
		$paginator = new Paginator ( $adapter );
		$paginator->setItemCountPerPage ( $nums )->setCurrentPageNumber ( $page );
		return $paginator;
	}
	
	/**
	 *
	 * @todo 取得问题总数
	 * @param Int $uid        	
	 * @return Ambigous <number, NULL>
	 */
	function askCount($user) {
		$table = new TableGateway ( 'faqs', $this->adapter );
		$rows = $table->select ( array (
				'domain' => $user 
		) );
		$res = $rows->count ();
		return $res;
	}
	
	/**
	 *
	 * @todo 取得单个提问
	 * @param Int $id        	
	 * @return Ambigous <multitype:, ArrayObject, NULL, \ArrayObject, unknown>
	 */
	function getAnswers($id) {
		$table = new TableGateway ( 'faqs', $this->adapter );
		$rowSet = $table->select ( array (
				'id' => $id 
		) );
		$row = $rowSet->current ();
		return $row;
	}
	function editAnswers($id, $data) {
		$id = ( int ) $id;
		if ($id) {
			$table = new TableGateway ( 'faqs', $this->adapter );
			$table->update ( $data, array (
					'id' => $id 
			) );
			return true;
		}
		return false;
	}
	function randList($page, $nums = 30) {
		$select = new Select ( array (
				'u' => 'users' 
		) );
		$select->join ( array (
				'c' => 'users_config' 
		), 'u.id=c.id' );
		$select->columns ( array (
				'username',
				'roleid',
				'userType',
				'validity' 
		) );
		$where = array (
				"u.power='0' AND u.addtime='0'" 
		);
		$select->where ( $where );
		$select->order ( 'id desc' );
		$adapter = new DbSelect ( $select, $this->adapter );
		$paginator = new Paginator ( $adapter );
		$paginator->setItemCountPerPage ( $nums )->setCurrentPageNumber ( $page );
		return $paginator;
	}
	function power() {
		$p = array (
				- 1 => '注册用户(未审核用户)',
				0 => '未认证会员(库存)',
				1 => '普通账号',
				2 => '商户',
				3 => '管理员' 
		);
		return $p;
	}
}