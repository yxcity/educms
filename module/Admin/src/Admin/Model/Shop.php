<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Ddl\Constraint\ForeignKey;
use module\Application\src\Model\Tool;
use Zend\Db\Sql\Sql;

class Shop
{

    private $adapter;

    private $table;

    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     *
     * @todo 添加门店
     * @param Array $data            
     * @return Ambigous <boolean, number>
     */
    function addShop ($data)
    {
        $table = new TableGateway('shop', $this->adapter);
        $table->insert($data);
        $tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
    }

    /**
     *
     * @todo 编辑门店
     * @param int $id            
     * @param array $data            
     * @return boolean
     */
    function editShop ($id, $data)
    {
        $id = (int) $id;
        if ($id) {
            $table = new TableGateway('shop', $this->adapter);
            $table->update($data, array(
                'id' => $id
            ));
            return true;
        }
        return false;
    }

    /**
     *
     * @todo 取得门店列表
     * @param int $page            
     * @return \Zend\Paginator\Paginator
     */
    function shopList ($page, $user, $nums=30, $keywords=null)
    {
        $select = new Select('shop');
        $select->order('id desc');
        $where = " 1 ";
        if ($user->power < 3) {
            $where .=" AND uid='{$user->domain}'";
        }
        if ($keywords)
        {
        	$where .=" AND shopname like '%{$keywords}%'";
        }
    	$select->where($where);
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($nums)->setCurrentPageNumber($page);
        return $paginator;
    }
    /**
     * @取得指定用户下的所有店铺
     * @param int $uid
     * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    function userShop($uid,$num=null,$page=1,$user=null)
    {
    	$sql = new Sql($this->adapter);
    	$select = $sql->select('shop');
        if ($user && $user->power == 3){
            $select->where(array());
        }else{
            $select->where(array('uid'=>$uid));            
        }
        //$select->where(array('uid'=>$uid));
    	if ($num)
    	{
    		$select->limit($num);
    		$offset = ($page-1)*$num;
    		$select->offset($offset);
    	}
    	$select->order('id DESC');
    	$rowsSet = $sql->prepareStatementForSqlObject($select);
    	$rows = $rowsSet->execute();
        return $rows;
    }

    /**
     * @根据地图坐标取得账号
     * 
     * @param unknown $x            
     * @param unknown $y            
     * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    function location ($x,$y,$uid=null)
    {
         $where  = ' 1 ';
        if ($uid)
        {
        	$where.=" AND uid='{$uid}'";
        }
        $sql = new Sql($this->adapter);
        $select = $sql->select('shop');
        $select->where($where);
        $select->limit(10);
        $select->offset(0);
        $select->order('locationY ASC,locationX ASC');
        $rowsSet = $sql->prepareStatementForSqlObject($select);
        $rows = $rowsSet->execute();
        if ($rows)
        {   $data = array();
        	//读取数据
        	foreach ($rows as $key=>$val) {
        		$data[$key]=$val;
        	}
        	//整理数据
        	foreach ($data as $key=>$val) {
        		$data[$key]['range']=Tool::getdistance("{$x}","{$y}", "{$val['locationX']}", "{$val['locationY']}");
        	}
        	$rows=Tool::arraySort($data, 'range');
        	unset($data);
        }
        return $rows;
    }

    /**
     * @todo 按关键字取得账号
     * @param unknown $key
     * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    function keyList ($key=null,$uid = null)
    {
        $table = new TableGateway('shop', $this->adapter);
        $where = " 1 ";
        if ($key)
        {
            $where .= " AND shopname like '%{$key}%'";
        }
       
        if ($uid)
        {
            $where .= " AND uid='{$uid}'";
        }
        $resultSet = $table->select($where);
        return $resultSet;
    }
    
    /**
     * 取得用户所有门店
     * 
     * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    function shopAll ($uid)
    {
        $table = new TableGateway('shop', $this->adapter);
        $rows = $table->select(array('uid' => $uid));
        return $rows;
    }
    
    /**
     *
     * @todo 取得门店总总数
     * @param Int $uid            
     * @return Ambigous <number, NULL>
     */
    function shopCount ($uid)
    {
        $table = new TableGateway('shop', $this->adapter);
        $rows = $table->select(array(
            'uid' => $uid
        ));
        $res = $rows->count();
        return $res;
    }

    /**
     *
     * @todo 取得单条门店信息
     * @param int $id            
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getShop ($id)
    {
        $id = (int) $id;
        $table = new TableGateway('shop', $this->adapter);
        if ($id) {
            $rowSet = $table->select(array(
                'id' => $id
            ));
            $row = $rowSet->current();
            return $row ? $row : false;
        }
        return false;
    }

    /**
     *
     * @todo 删除门店
     * @param int $id            
     * @return boolean
     */
    function deleteShop ($id)
    {
        $id = (int) $id;
        $table = new TableGateway('shop', $this->adapter);
        if ($id) {
            $table->delete(array(
                'id' => $id
            ));
            return true;
        }
        return false;
    }
}