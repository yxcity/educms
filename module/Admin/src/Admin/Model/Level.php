<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\ResultSet\AbstractResultSet;

class Level
{
	private $adapter;
	
	function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }
	
	/**
     *
     * @todo 添加会员等级
     * @param unknown $data            
     * @return Ambigous <boolean, number, \Zend\Db\TableGateway\mixed>
     */
    function addLevel ($data)
    {
        $table = new TableGateway('wx_member_levels', $this->adapter);
		
		//检查是否已重复添加
		$rows = $table->select(array(
    			'domain' => $data['domain'],
				'level_name'	=> $data['level_name'],
    	));
    	if($rows->count()> 0){
			return FALSE;
		}
		
		//添加一条新记录
        $table->insert($data);
        $tid = $table->getLastInsertValue();
		if(!$tid){
			return FALSE;
		}
        return TRUE;
    }
	
	
	/**
     *
     * @todo 编辑会员等级
     * @param Int $id            
     * @param Array $data            
     */
    function editLevel ($level_id, $data)
    {
        $level_id = (int) $level_id;
        if ($level_id) {
            $table = new TableGateway('wx_member_levels', $this->adapter);
            $flag = $table->update($data, array('level_id' => $level_id));
            return $flag;
        }
        return FALSE;
    }
	
	
	/**
     *
     * @todo 取得单条会员等级信息
     * @param int $level_id 会员等级ID
	 * @param char $domain 所在域           
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getLevel ($level_id,$domain = NULL)
    {
        $table = new TableGateway('wx_member_levels', $this->adapter);
		$where = array();
		if($level_id){
			$where['level_id'] = $level_id; 
		}
		if($domain){
			$where['domain'] = $domain;
		}
        $rowset = $table->select($where);
		if($rowset->count() > 0){
			return $rowset->current();
		}
		return FALSE;
    }
	
	
	/**
	 * 查找指定domain下的所有会员等级 
	 * @param undefined $domain
	 * 
	 */
	function getAllLevels($domain)
	{
		$table = new TableGateway('wx_member_levels', $this->adapter);
		$rowset = $table->select(array('domain'=>$domain));
		if($rowset->count() > 0){
			return $rowset;
		}
		return FALSE;
	}
	
	
	/**
     * @todo 删除会员等级
     * @param int $id
     * @return boolean
     */
    function delLevel($level_id,$domain)
    {
    	$level_id = (int)$level_id;
		$table = new TableGateway('wx_member_levels', $this->adapter);
		$where = array('level_id'=>$level_id,'domain'=>$domain);
		$rowSet = $table->select($where);
		if($rowSet->count() > 0 && $table->delete($where)){
			return $rowSet->current();
		}
    	return FALSE;
    }
	
}


?>