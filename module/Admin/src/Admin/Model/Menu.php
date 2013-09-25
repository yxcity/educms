<?php

namespace Admin\Model;

class Menu {
	protected $tableGateway;
	protected $adapter;
	public function __construct($tableGateway, $adapter = null) {
		$this->tableGateway = $tableGateway;
		$this->adapter = $adapter;
	}
	
	/**
	 *
	 * @todo 取得微信菜单 access_token
	 * @param string $appId        	
	 * @param string $appSecret        	
	 * @return boolean
	 */
	function accessToken($appId, $appSecret) {
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appId}&secret={$appSecret}";
		$content = json_decode ( file_get_contents ( $url ) );
		return isset ( $content->access_token ) ? $content->access_token : false;
	}
	/**
	 *
	 * @todo 更新菜单数据
	 * @param array $data        	
	 * @param int $id        	
	 * @return boolean unknown
	 */
	function insert($data, $id = null) {
		if ($id) {
			$this->tableGateway->update ( $data, array (
					'id' => $id 
			) );
			return true;
		} else {
			$data ['addtime'] = time ();
			$this->tableGateway->insert ( $data );
			$id = $this->tableGateway->getLastInsertValue ();
			return $id;
		}
	}
	/**
	 *
	 * @todo 取得指定域名的所有菜单
	 * @param string $domain        	
	 * @return unknown
	 */
	function getList($domain, $pid = null) {
		$where = "";
		if (is_numeric ( $pid )) {
			$where .= " and m.pid=$pid ";
		}
		$row_set = $this->adapter->query ( "select m.*,n.title,n.type,n.pic_url from menu as m left join news as n  on  m.news_id=n.id where m.domain='$domain' " . $where, "execute" );
		if ($row_set) {
			return $row_set->toArray ();
		}
		return null;
	}
	function getMenuId($id) {
		$row_set = $this->adapter->query ( "select m.*,n.title,n.type,n.description,n.content,n.pic_url from menu as m left join news as n  on  m.news_id=n.id where m.id=$id", "execute" );
		if ($row_set) {
			$row_set = $row_set->toArray ();
			if (count ( $row_set ) > 0)
				return $row_set [0];
		}
		return null;
	}
	function getNewsByKey($key) {
		$row_set = $this->adapter->query ( "select * from news where id in (select news_id from menu where `key`='$key')", "execute" );
		if ($row_set) {
			$row_set = $row_set->toArray ();
			if (count ( $row_set ) > 0)
				return $row_set [0];
		}
		return null;
	}
	function delete($id) {
		$id = ( int ) $id;
		$ids = array (
				$id 
		);
		$row_set = $this->adapter->query ( "select id from menu where pid= " . $id, "execute" );
		if ($row_set) {
			$row_set = $row_set->toArray ();
			foreach ( $row_set as $row ) {
				array_push ( $ids, ( int ) $row ['id'] );
			}
		}
		foreach ( $ids as $del_id ) {
			$this->tableGateway->delete ( array (
					'id' => $del_id 
			) );
		}
		return $ids;
	}
}
