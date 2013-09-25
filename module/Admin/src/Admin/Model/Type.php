<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use module\Application\src\Model\Tool;

class Type
{

    private $adapter;

    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     * @todo 添加分类
     * @param array $data
     * @return Ambigous <boolean, number>
     */

    function addType ($data)
    {
        $table = new TableGateway('type', $this->adapter);
		
		//检查是否已重复添加
		$rows = $table->select(array(
    			'domain' => $data['domain'],
				'pid'	=> $data['pid'],
				'classid'=>$data['classid'],
				'name'=>$data['name']
    	));
    	if($rows->count()> 0){
			return FALSE;
		}
		
		//添加一条新的记录
        $table->insert($data);
        $tid = $table->getLastInsertValue();
		if($tid){
			$this->_updateSorting($tid);//更新排序
		}
        return $tid ? $tid : false;
    }
    
	
	/**
	 * 更新操作序号
	 * @param undefined $acl_id
	 * @param undefined $max_id
	 * 
	 */
	private function _updateSorting($max_id = 0)
	{
		$table = new TableGateway('type', $this->adapter);
		$table->update(array('sorting'=>$max_id),array('id' => $max_id));	
	}
	
	
	
	/**
	 * 更新操作排序序号
	 * @param undefined $acl_sorting
	 * @param undefined $acl_id
	 * 
	 */
	function updateTypeSorting($sorting,$id)
	{
		 $table = new TableGateway('type', $this->adapter);
		 $rowSet = $table->select(array('id'=>$id));
		 if(!$rowSet){
		 	return FALSE;
		 }
		 $data = $rowSet->current();
         $flag = $table->update(array('sorting'=>intval($sorting)), array('id' => intval($id)));
		 if(!$flag){
		 	return FALSE;
		 }
         return $data;//返回被更新的数据
	}
	
	
	
	
    /**
     * @todo 编辑分类
     * @param int $id
     * @param array $data
     * @return boolean
     */

    function editType ($id, $data)
    {
        $id = (int) $id;
        if ($id) {
            $table = new TableGateway('type', $this->adapter);
            $table->update($data, array(
                'id' => $id
            ));
            return true;
        }
        return false;
    }
	
	
    /**
     * @todo 批量修改二级分类
     * @param Int $pid
     * @param Array $data
     * @return boolean
     */
    function editPid($pid,$data)
    {
        $pid = (int) $pid;
        if ($pid) {
        	$table = new TableGateway('type', $this->adapter);
        	$table->update($data, array(
        			'pid' => $pid
        	));
        	return true;
        }
        return false;
    }
    
    /**
     * @todo 分类列表
     * @param int $page
     * @return \Zend\Paginator\Paginator
     */

    function typeList ($page,$domain)
    {
    	$select = new Select('type');
    	$select->where(array('domain'=>$domain));
    	$select->order('id desc');
    	$adapter = new DbSelect($select, $this->adapter);
    	$paginator = new Paginator($adapter);
    	$paginator->setItemCountPerPage(30)->setCurrentPageNumber($page);
    	return $paginator;
    }
	
	
    /**
     * @todo 取得所有分类
	 * @param $domain 所属商户
	 * @param $pid 分类上级ID
	 * @param $classid 分类类型
     * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    function typeAll($domain,$pid=null,$classid = 0,$user=null)
    {
    	$table = new TableGateway('type', $this->adapter);
    	if($user && $user->power==3){
    	    $where=array();
    	}else{
    	    $where=array('display'=>1,'domain'=>$domain);
    	}
        if ($pid != null)
        {
        	$where['pid']=$pid;
        }
    	if($classid > 0){
    		$where['classid'] = $classid;
    	}
        $rows = $table->select ($where);
    	
    	return $rows;
    }
	
	
	/**
	 * 获取分类树结构 
	 * @param undefined $domain
	 * @param undefined $classid
	 * @param undefined $pid
	 * @return array
	 * 
	 */
	function getTypeTree($domain,$classid = 0,$pid=0)
	{
		//从缓存提取数据
		$key = $domain."_".$classid."_".$pid;
		$data = Tool::getCache($key);
		if($data){
			return $data;
		}
		
		//从Db提取数据
		
		$sql = "SELECT * FROM `type` WHERE display = 1 AND domain IN('$domain','system') AND pid = '$pid'";
		if($classid > 0){
			$sql.= " AND classid = '$classid'";	
		}
		$sql.= " Order By sorting ASC";
		$rows = $this->adapter->query($sql,"execute");
		
		/*$table = new TableGateway('type', $this->adapter);
    	$where=array('display'=>1,'domain'=>$domain);*/
    	
		/*if($classid > 0){
			$where['classid'] = $classid;
		}
		$where['pid']=$pid;
    	$rows = $table->select(function(Select $select){
			$select->where($where);
			$select->order('sorting ASC');	
		});*/
		
		$dbRst = array();
		if($rows->count()>0){
			foreach($rows as $r){
				$tmp = array(
					'id'=>$r->id,
					'domain'=>$r->domain,
					'name'=>$r->name,
					'pid'=>$r->pid,
					'sub_tree'=>$this->_getSubTree($r->id,$domain),
					'is_child'=> $r->pid>0 ? TRUE : FALSE,
					'display'=>$r->display,
					'sorting'=>$r->sorting
				);
				$dbRst[] = $tmp;			
			}
		}
		
		//将数据写入缓存
		if($dbRst){
			Tool::setCache($key,$dbRst);
		}
		
		return $dbRst;
	}
	
	/**
	 * 检测是否含有子节点分类 
	 * @param undefined $pid
	 * 
	 */
	private function _getSubTree($pid=0,$domain = NULL)
	{
		$sql = "SELECT * FROM `type` WHERE display = 1 AND pid = '$pid' AND domain = '$domain' Order By sorting ASC";
		$rows = $this->adapter->query($sql,"execute");
		/*$table = new TableGateway('type', $this->adapter);
    	$where=array('display'=>1,'pid'=>$pid);
    	$rows = $table->select(function(Select $select){
			$select->where($where);
			$select->order('sorting ASC');
		});*/
		
		$dbRst = array();
		if($rows->count()>0){
			foreach($rows as $r){
				$tmp = array(
					'id'=>$r->id,
					'domain'=>$r->domain,
					'name'=>$r->name,
					'pid'=>$r->pid,
					'sub_tree'=>$this->_getSubTree($r->id,$domain),
					'is_child'=> $r->pid>0 ? TRUE : FALSE,
					'display'=>$r->display,
					'sorting'=>$r->sorting
				);
				$dbRst[] = $tmp;
			}
		}
		return $dbRst;
	}
	
    
    /**
     * @todo 取得单个分类信息
     * @param int $id
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getType ($id)
    {
        $id = (int) $id;
        if ($id) {
            $table = new TableGateway('type', $this->adapter);
            $rowSet = $table->select(array(
                'id' => $id
            ));
            $row = $rowSet->current();
            return $row ? $row : false;
        }
        return false;
    }
    /**
     * @todo 删除分类
     * @param int $id
     * @return boolean
     */
    function delete ($id)
    {
        $id = (int) $id;
        if ($id) {
            $table = new TableGateway('type', $this->adapter);
            $table->delete(array('id' => $id));
            return true;
        }
        return false;
    }
}