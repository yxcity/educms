<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\ResultSet\AbstractResultSet;
//use module\Application\src\Model\Tool;

class Brand
{
	private $adapter;
	
	function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }
	
	/**
     *
     * @todo 添加品牌
     * @param unknown $data            
     * @return Ambigous <boolean, number, \Zend\Db\TableGateway\mixed>
     */
    function addBrand ($data)
    {
        $table = new TableGateway('wx_brands', $this->adapter);
		
		//检查是否已重复添加
		$rows = $table->select(array(
    			'domain' => $data['domain'],
				'brand_name'	=> $data['brand_name'],
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
		$table->update(array('sorting'=>$tid),array('brand_id'=>$tid));//自动更新排序ID
        return TRUE;
    }
	
	
	/**
     *
     * @todo 编辑品牌
     * @param Int $id            
     * @param Array $data            
     */
    function editBrand ($brand_id, $data)
    {
        $brand_id = (int) $brand_id;
        if ($brand_id) {
            $table = new TableGateway('wx_brands', $this->adapter);
            $flag = $table->update($data, array('brand_id' => $brand_id));
            return $flag;
        }
        return FALSE;
    }
	
	
	/**
     *
     * @todo 取得单条品牌信息
     * @param int $brand_id 品牌ID
	 * @param char $domain 所在域           
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getBrand ($brand_id,$domain = NULL,$thumbSize = NULL)
    {
        $table = new TableGateway('wx_brands', $this->adapter);
		$where = array();
		if($brand_id){
			$where['brand_id'] = $brand_id; 
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
	 * 查找指定domain下的所有品牌 
	 * @param undefined $domain
	 * 
	 */
	function getAllBrands($domain)
	{
		$table = new TableGateway('wx_brands', $this->adapter);
        $rowset = $table->select(array('domain'=>$domain));
		if($rowset->count() > 0){
			return $rowset;
		}
		return FALSE;
	}
	
	
	/**
     *
     * @todo 取得品牌列表
     * @param unknown $page            
     * @return \Zend\Paginator\Paginator
     */
    function brandList($domain,$page,$nums=30,$keywords=NULL)
    {
        $select = new Select('wx_brands');
		$where = "domain = '$domain' AND display = 1";
		if ($keywords)
    	{
    		$where .=" AND brand_name like '%{$keywords}%'";
		}
   		$select->where($where);
		$select->order('sorting ASC');
		
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($nums)->setCurrentPageNumber($page);
        return $paginator;
    }
	
	
	/**
     * @todo 删除品牌
     * @param int $id
     * @return boolean
     */
    function delBrand($brand_id,$domain)
    {
    	$brand_id = (int)$brand_id;
		$table = new TableGateway('wx_brands', $this->adapter);
		$where = array('brand_id'=>$brand_id,'domain'=>$domain);
		$rowSet = $table->select($where);
		if($rowSet->count() > 0 && $table->delete($where)){
			return $rowSet->current();
		}
    	return FALSE;
    }
	
	
	/**
	 * 更新品牌排序序号
	 * @param undefined $sorting
	 * @param undefined $brand_id
	 * 
	 */
	function updateBrandSorting($sorting,$brand_id,$domain)
	{
		 $table = new TableGateway('wx_brands', $this->adapter);
         $flag = $table->update(array('sorting'=>intval($sorting)), array('brand_id' => intval($brand_id),'domain'=>$domain));
         return $flag;
	}
}


?>