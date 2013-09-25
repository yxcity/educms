<?php
namespace Admin\Model;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
class Ads{
	private $adapter;
	function __construct($adapter){
		$this->adapter = $adapter;
	}
	/**
	 * @todo 更新到数据库
	 * @param array $data
	 * @param string $id
	 * @return Ambigous <string, number>
	 */
	function insertAds($data,$id=NULL)
	{
		$table = new TableGateway('ads', $this->adapter);
		if ($id)
		{
			$table->update($data,array('id'=>$id));
		}else
		{
			$table->insert($data);
			$id = $table->getLastInsertValue();
		}
		return $id;
	}
	
	/**
	 * @todo 更新到数据库
	 * @param array $data
	 * @param string $id
	 * @return Ambigous <string, number>
	 */
	function insertAdvert($data,$id=NULL)
	{
		$table = new TableGateway('advert', $this->adapter);
		if ($id)
		{
			$table->update($data,array('id'=>$id));
		}else
		{
			$table->insert($data);
			$id = $table->getLastInsertValue();
		}
		return $id;
	}
	
	
	/**
	 * @todo 取得列表
	 * @param object $user
	 * @param number $num
	 * @param number $page
	 * @return \Zend\Paginator\Paginator
	 */
	function adsList($user,$num=30,$page=1){
		$select = new Select('ads');
		$select->where(array('domain'=>$user->domain));
		$select->order('id desc');
		$adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($num)->setCurrentPageNumber($page);
        return $paginator;
	}
	/**
	 * @todo 所有广告位
	 * @param unknown $domain
	 * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
	 */
	function allAds($user)
	{
		$table = new TableGateway('ads', $this->adapter);
		if ($user->userType)
		{
			$rows = $table->select(array('adType'=>$user->userType));
		}else 
		{
			$rows = $table->select();
		}
		return $rows;
	}
	/**
	 * @todo  取得单条广告位信息
	 * @param int $id
	 * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
	 */
	function getAds($id)
	{
		$table = new TableGateway('ads', $this->adapter);
		$rowSet = $table->select(array('id'=>$id));
		$row = $rowSet->current();
		return $row?$row:false;
	}
	/**
	 * @todo 取得单挑广告内容
	 * @param int $id
	 * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
	 */
	function getAdvert($id)
	{
		$table = new TableGateway('advert', $this->adapter);
		$rowSet = $table->select(array('id'=>$id));
		return $rowSet->current();
	}
	/**
	 * @todo 读取广告位广告内容
	 * @param unknown $ads
	 * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
	 */
	function advert($ads)
	{
		$table = new TableGateway('advert', $this->adapter);
		$rows = $table->select(array('ads'=>$ads));
		return $rows;
	}
	
	/**
	 * @todo 取得列表
	 * @param object $user
	 * @param number $num
	 * @param number $page
	 * @return \Zend\Paginator\Paginator
	 */
	function advertList($user,$num=30,$page=1){
		$select = new Select('advert');
		$select->where(array('domain'=>$user->domain));
		$select->order('id desc');
		$adapter = new DbSelect($select, $this->adapter);
		$paginator = new Paginator($adapter);
		$paginator->setItemCountPerPage($num)->setCurrentPageNumber($page);
		return $paginator;
	}
	/**
	 * @todo 删除广告位数据
	 * @param int $id
	 */
	function deleteAds($id)
	{
		$id = (int)$id;
		$table = new TableGateway('ads', $this->adapter);
		$table->delete(array('id'=>$id));
	}
	/**
	 * @删除广告内容
	 * @param unknown $id
	 */
	function deleteAdvert($id)
	{
		$id = (int)$id;
		$table = new TableGateway('advert', $this->adapter);
		$table->delete(array('id'=>$id));
	}
	
}