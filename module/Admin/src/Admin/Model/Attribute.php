<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;
use module\Application\src\Model\Tool;

class Attribute
{

    private $adapter;

    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     * @todo 添加商品属性
     * @param array $data
     * @return Ambigous <boolean, number>
     */

    function addAttr ($data)
    {
        $table = new TableGateway('wx_goods_spec', $this->adapter);
		
		//检查是否已重复添加
		$rows = $table->select(array(
    			'domain' => $data['domain'],
				'pid'	=> $data['pid'],
				'prod_class'=>$data['prod_class'],
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
	 * 更新属性序号
	 * @param undefined $max_id
	 * 
	 */
	private function _updateSorting($max_id = 0)
	{
		$table = new TableGateway('wx_goods_spec', $this->adapter);
		$table->update(array('sorting'=>$max_id),array('id' => $max_id));	
	}
	
	
	
	/**
	 * 更新商品属性排序序号
	 * @param undefined $acl_sorting
	 * @param undefined $acl_id
	 * 
	 */
	function updateAttrSorting($sorting,$id)
	{
		 $table = new TableGateway('wx_goods_spec', $this->adapter);
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
     * @todo 编辑商品属性
     * @param int $id
     * @param array $data
     * @return boolean
     */

    function editAttr ($data,$id,$domain)
    {
        $id = (int) $id;
        $table = new TableGateway('wx_goods_spec', $this->adapter);
        $flag = $table->update($data, array(
            'id' => $id,
			'domain'=>$domain
        ));
        if($flag){
			return TRUE;
		}
        return FALSE;
    }
	
	
	
	/**
	 * 获取商品属性树结构 
	 * @param undefined $domain
	 * @param undefined $classid
	 * @param undefined $pid
	 * @return array
	 * 
	 */
	function getAttrTree($domain,$classid = 0,$pid=0)
	{
		//从缓存提取数据
		$key = "attr_".$domain."_".$classid."_".$pid;
		$data = Tool::getCache($key);
		if($data){
			return $data;
		}
		
		//从Db提取数据
		
		$sql = "SELECT * FROM `wx_goods_spec` WHERE display = 1 AND domain = '$domain' AND pid = '$pid'";
		if($classid > 0){
			$sql.= " AND prod_class = '$classid'";	
		}
		$sql.= " Order By sorting ASC";
		$rows = $this->adapter->query($sql,"execute");
		
		
		
		$dbRst = array();
		if($rows->count()>0){
			foreach($rows as $r){
				$tmp = array(
					'id'=>$r->id,
					'domain'=>$r->domain,
					'name'=>$r->name,
					'pid'=>$r->pid,
					'sub_tree'=>$this->_getSubTree($r->id),
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
	private function _getSubTree($pid=0)
	{	
		if(!$pid){
			return FALSE;
		}
		
		$sql = "SELECT * FROM `wx_goods_spec` WHERE display = 1 AND pid = '$pid' Order By sorting ASC";
		$rows = $this->adapter->query($sql,"execute");
		
		
		$dbRst = array();
		if($rows->count()>0){
			foreach($rows as $r){
				$tmp = array(
					'id'=>$r->id,
					'domain'=>$r->domain,
					'name'=>$r->name,
					'pid'=>$r->pid,
					'sub_tree'=>$this->_getSubTree($r->id),
					'is_child'=> $r->pid>0 ? TRUE : FALSE,
					'display'=>$r->display,
					'sorting'=>$r->sorting
				);
				$dbRst[] = $tmp;
			}
		}
		return $dbRst;
	}
	
   	
	
	//-------------------以下为新增方法--------------------
	
	/**
	 * 检测当前分类ID是否为一个合法的商品分类ID 
	 * @param undefined $prod_class
	 * @param undefined $domain
	 * 
	 */
	function checkProdClassExisted($prod_class,$domain = NULL)
	{
		$table = new TableGateway('type',$this->adapter);
		$rowSet = $table->select(array('id'=>$prod_class,'domain'=>$domain,'classid'=>10));
		if($rowSet->count() > 0){
			return $rowSet->current();
		}
		return FALSE;
	}
	
	
	/**
     * @删除商品属性
     * @param int $id
     * @return boolean
     */
    function delAttr($id,$domain)
    {
    	$id = (int)$id;
		$table = new TableGateway('wx_goods_spec', $this->adapter);
		
		
		//删除当前属性
    	$flag = $table->delete(array('id'=>$id,'domain'=>$domain));
		if(!$flag){
			return FALSE;
		}
		
		//查找该属性子属性并删除
		$rows = $table->select(array('pid'=>$id));
		if($rows->count()>0){
			foreach($rows as $r){
				$this->delAttr($r->id,$r->domain);
			}
		}
    	return TRUE;
    }
	
	
	/**
	 * 通过ID获取商品属性信息
	 * @param undefined $id
	 * 
	 */
	function getAttrById($id,$domain = NULL)
	{
		$table = new TableGateway('wx_goods_spec',$this->adapter);
		$rowSet = $table->select(array('id'=>$id,'domain'=>$domain));
		if($rowSet->count() > 0){
			return $rowSet->current();
		}
		return FALSE;
	}
	
	/**@todo 此处需加缓存处理
	 * 查找某个分类下的商品所有属性
	 * @param undefined $class_id
	 * 
 	 */
	function getSpecListByClassId($class_id = 0)
	{
		return $this->_getSpecTree($this->_getClassIdStr($class_id));
	}
	
	
	private function _getSpecTree($classStr,$pid = 0)
	{
		$dbRst = array();
		if(is_array($classStr) && count($classStr) > 0){//如果是一个非空的数组 ie array(1,3,5)
			$classStr = implode(",",$classStr);
		}
		$sql = "SELECT * FROM `wx_goods_spec` WHERE pid = '$pid' AND prod_class IN($classStr)";
		$rowSet = $this->adapter->query($sql,"execute");
		if($rowSet->count() > 0){
			foreach($rowSet as $r){
				$r->subTree = $this->_getSpecTree($classStr,$r->id);
				$dbRst[] = $r;
			}
		}
		return $dbRst;
	}
	
	/**
	 *  查找商品当前分类及其父类的ID集合
	 * @param undefined $class_id
	 * @return string id1,id2,id3
	 * 
	 */
	private function _getClassIdStr($class_id = 0)
	{
		$ids = array($class_id);
		$sql = "SELECT pid FROM `type` WHERE pid > 0 AND classid = 10 AND id = '$class_id'";
		$rowSet = $this->adapter->query($sql,"execute");
		if($rowSet->count() > 0){
			$current = $rowSet->current();
			array_push($ids,$current->pid);
			$ids = array_merge($ids,$this->_getClassIdStr($current->pid));
		}
		return array_unique($ids);
	}
}