<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use module\Application\src\Model\Tool;

class Party
{

    private $adapter;

    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }
    
	
	/**
	 * 通过classid获取文章信息 
	 * @param undefined $classid
	 * @return 数组 | FALSE
	 * 
	 */
	function getPartyByClassId($classid,$domain = NULL)
	{
		if(!$classid){
			return FALSE;
		}
		
		$table= new TableGateway('wx_articles',$this->adapter);
		$rowSet = $table->select(array('classid'=>$classid,'domain'=>$domain));
		if($rowSet->count() < 1){
			return FALSE;
		}
		$row = $rowSet->current();
		$row['created_time_str'] = $row['created_time'] > 0 ? date('Y-m-d',$row['created_time']) : "";
		$row['updated_time_str'] = $row['updated_time'] > 0 ? date('Y-m-d',$row['updated_time']) : "";
		return $row;
	}
	
	
	/**
	 * 保存文章信息 
	 * @param undefined $data
	 * @param undefined $classid
	 * @param undefined $domain
	 * 
	 */
	function saveArtInfo($data,$classid,$domain = NULL)
	{
		//标题与内容为必填项
		if(!isset($data['art_title']) || empty($data['art_title'])){
			return FALSE;
		}
		if(!isset($data['art_content']) || empty($data['art_content'])){
			return FALSE;
		}
		
		//新增|更新内容
		$table = new TableGateway('wx_articles',$this->adapter);
		$row = $this->getArtByClassId($classid,$domain);
		$flag = FALSE;
		if(!$row){
			//新增
			$data['created_time'] = time();
			$data['domain'] = $domain;
			$data['classid'] = $classid;
			$data['display'] = 1;
			$data['usage'] = 0;
			$flag = $table->insert($data);
		}else{
			//更新
			$data['updated_time'] = time();
			$data['display'] = 1;
			$data['usage'] = 0;
			$flag = $table->update($data,array('classid'=>$classid,'domain'=>$domain));
		}
		return $flag;
	}
	
	
	/**
     * @活动列表
     * @param int $page
     * @param Object $user
     * @return \Zend\Paginator\Paginator
     */
    function partyList ($page,$user,$nums=30,$keywords=null)
    {
    	$select = new Select('plug_party');
    	if($user->power==3){
		  $where = " display =  1";
    	}else{
		  $where = "domain = '".$user->domain."' AND display =  1";
    	}
    	if ($keywords)
    	{
    		$where .=" AND (party_title like '%{$keywords}%' OR party_content like '%{$keywords}%')";
    	}
		$select->where($where);
    	$select->order('created_time desc');
    	$adapter = new DbSelect($select, $this->adapter);
    	$paginator = new Paginator($adapter);
    	$paginator->setItemCountPerPage($nums)->setCurrentPageNumber($page);
    	return $paginator;
    }
	
	
	/**
	 * 添加活动
	 * @param undefined $data
	 * 
	 */
	function addParty($data)
	{
		$table = new TableGateway('plug_party', $this->adapter);
		
		//检查是否已重复添加
		$rows = $table->select(array(
    			'party_title' => $data['party_title'],
				'domain'	=> $data['domain'],
				'classid'=>0,
    	));
    	if($rows->count()> 0){
			return FALSE;
		}
		
		//添加一条新的记录
        $table->insert($data);
        $tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
	}
	
	/**
	 * 获取活动内容 
	 * @param undefined $art_id
	 * 
	 */
	function getParty($party_id)
	{
		if(!$party_id){
			return FALSE;
		}
		$table = new TableGateway('plug_party',$this->adapter);
		$rowSet = $table->select(array('party_id'=>$party_id,'display'=>1));
		if($rowSet->count() > 0){
			$row = $rowSet->current();
			return $row;
		}
		return FALSE;
	}
	
	/**
     * @编辑活动
     * @param int $id
     * @param array $data
     * @return boolean
     */

    function editParty ($data,$party_id,$domain)
    {
        
        $table = new TableGateway('plug_party', $this->adapter);
        $flag = $table->update($data, array(
            'party_id' => $party_id,
			'domain'=>$domain
        ));
		if($flag){
			return TRUE;
		}
		return FALSE;
    }
	
	
	/**
     * @todo 删除活动
     * @param int $id
     * @return boolean

		
	 *--|建议:关于db的update/delete的安全写法
	 *--|1.update
	 *	$table = new tableGateway('table_name',$this->adapter);
	 *	$data = array('display' => 0);
	 *	$where = array('party_id' => $party_id);
	 *	$table->update($data,$where);
	 *	
	 *--|2.delete
	 *	$table = new tableGateway('table_name',$this->adapter);	 
	 *	$where = array('id'=>$id);
	 *	$table->delete($where);	
     */
    function delparty($party_id,$domain)
    {
    	$party_id = (int)$party_id;
		$flag = $this->adapter->query("update plug_party set display=0 where party_id={$party_id}",'execute');
		$flags = $this->adapter->query("update plug_party_users set display=0 where parentid={$party_id}",'execute');
		if(!$flag || !$flags){
			return FALSE;
		}
    	return TRUE;
    }
    
    /*删除名单*/
    function delpartyusers($id,$domain)
    {
    	$id = (int)$id;
		$table = new TableGateway('plug_party_users', $this->adapter);
    	$flag = $table->delete(array('id'=>$id,'domain'=>$domain));
		if(!$flag){
			return FALSE;
		}
    	return TRUE;
    }
    
    /*阅读文章*/
    function incReadCount($id)
    {
        $id = (int) $id;
        if($id > 0 ){
            return $this->adapter->query("update plug_party set party_pv=party_pv+1, party_uv=party_uv+1 where party_id={$id}",'execute');
        }else{
            return false;
        }
    }
    
    /*分享文章*/
    function incShareCount($id)
    {
        $id = (int) $id;
        if($id > 0 ){
            return $this->adapter->query("update plug_party set party_share=party_share+1 where party_id={$id}",'execute');
        }else{
            return false;
        }
    }
	
	
	/**
	 * 登记报名
	 * @param undefined $data
	 * 
	 */
	function addPartyusers($id,$nick,$phone)
	{
		$table = new TableGateway('plug_party_users', $this->adapter);
		
		//检查是否已重复添加
		$rows = $table->select(array(
    			'parentid' => $id,
				'realname' => $nick,
				'telephone'   => $phone,
    	));
    	if($rows->count()> 0){
			return FALSE;
		}
		$data ['domain'] = Tool::domain();
		$data ['parentid'] = $id;
		$data ['realname'] = $nick;
		$data ['telephone'] = $phone;
		$data ['addTime'] = time ();
		$data ['addIP'] = Tool::getIP ();
		
		//添加一条新的记录
        $table->insert($data);
        $tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
	}

    /**
     *
     * @todo 取得报名列表
     * @param unknown $page            
     * @return \Zend\Paginator\Paginator
     */
    function userList ($parentid,$page,$user,$domain,$nums=30,$keywords=null)
    {
        $select = new Select('plug_party_users');
         $select->columns(array(
			'*'
        ));
        $where = " 1 ";
    	if($user->power==3){
        	if ($keywords)
        	{
        		$where .=" AND (realname like '%{$keywords}%' or telephone like '%{$keywords}%')";
        	}
    	}else{
            $where .=" AND domain='{$user->domain}' AND display=1";
        	if ($keywords)
        	{
        		$where .=" AND (realname like '%{$keywords}%' or telephone like '%{$keywords}%')";
        	}
    	}
        if($parentid)  $where .=" AND parentid='{$parentid}'";
		$select->where($where);
        $select->order('id desc');
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($nums)->setCurrentPageNumber($page);
        return $paginator;
    }
	
}