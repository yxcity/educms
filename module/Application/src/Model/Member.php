<?php

namespace module\Application\src\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Authentication\Adapter\DbTable;

class Member {
	private $adapter;
	function __construct($adapter) {
		$this->adapter = $adapter;
	}
	/**
	 * @添加会员
	 * 
	 * @param Array $data        	
	 * @return Ambigous <boolean, unknown>
	 */
	function addMember($data) {
		$data ['domain'] = Tool::domain();
		$data ['regTime'] = time ();
		$data ['lastTime'] = time ();
		$data ['lastIP'] = Tool::getIP ();
		$table = new TableGateway ( 'member', $this->adapter );
		$table->insert ( $data );
		$tid = $table->getLastInsertValue ();
		return $tid ? $tid : false;
	}
       /**
        * @随机生成一个会员帐号
        * 
        * @param string $openid
        */
        function randMember()
        {
            $username = uniqid();
            $data['username'] = $username;
            $openid = Tool::getCookie('openid');
    		if ($openid)
    		{
    			$data['openid']=$openid;
    		}
            $data['password'] = sha1($username);
            $data['email'] = '-1';
            $id = self::addMember($data);
            $tmpArr=array('id'=>$id,'username'=>$username,'openid'=>$openid);
            Tool::setCookie('member', $tmpArr,time()+(3600*24*365));
            return (object)$tmpArr;
        }
        
	/**
	 *
	 * @todo 编辑会员
	 * @param int $id        	
	 * @param Array $data        	
	 * @return boolean
	 */
	function editMember($id, $data) {
		$id = ( int ) $id;
		if ($id) {
			$table = new TableGateway ( 'member', $this->adapter );
			$table->update ( $data, array (
					'id' => $id 
			) );
			return true;
		}
		return false;
	}
	/**
	 *
	 * @todo 读取单个会员信息
	 * @param string $id        	
	 * @param string $username        	
	 * @return Ambigous <boolean, mixed>
	 */
	function getMember($id = null, $username = null) {
		$table = new TableGateway ( 'member', $this->adapter );
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
		$rowset = $table->select ( $where );
		$row = $rowset->current ();
		return $row ? $row : false;
	}
	/**
	 *
	 * @todo 删除单个会员
	 * @param int $id        	
	 * @return boolean
	 */
	function delMember($id) {
		$id = ( int ) $id;
		$table = new TableGateway ( 'member', $this->adapter );
		$table->delete ( array (
				'id' => $id 
		) );
		return true;
	}
	/**
	 * @todo  验证登陆
	 * @param array $data
	 * @return Ambigous <stdClass, boolean, unknown, \stdClass>|boolean
	 */
	function auth($data) {
		$authAdapter = new DbTable ( $this->adapter );
		$authAdapter->setTableName ( 'member' )->setIdentityColumn ( 'username' )->setCredentialColumn ( 'password' )->setIdentity ( $data ['username'] )->setCredential ( $data ['password'] );
		$authAdapter->authenticate();
		$res = $authAdapter->getResultRowObject(array(
				'id',
				'username',
				'realname',
				'openid',
		));
		if ($res)
		{
			//Tool::setCookie('member', $res,time()+3600*24*30);
			return $res;
		}
		return false;
	}
}