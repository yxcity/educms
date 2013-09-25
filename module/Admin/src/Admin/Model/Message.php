<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Sql;

class Message
{

    private $adapter;

    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     *
     * @todo 获取所有城市
     * @param 无
     * @return rows|null
     */
    function getAllCities ()
    {
         $table = new TableGateway('city', $this->adapter);
            $row_set = $table->select(array(
                'hide' => 0
            ));
            
         return $row_set->toArray();
    }
    
    /**
     * @todo 删除消息
     * @param unknown $id
     * @return boolean
     */

    function delete ($id)
    {
        $id = (int) $id;
        $table = new TableGateway('messages', $this->adapter);
        $table->delete("pid=${id} or id=${id}");
        return true;
    }
    
     /**
     *
     * @todo 添加群发任务
     * @param array $data            
     * @return Ambigous <boolean, number>
     */
    function addMassSendTask ($data)
    {
        $table = new TableGateway('massend_task', $this->adapter);
        $table->insert($data);
        $tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
    }
    
        /**
     *
     * @todo 取得群发任务列表
     * @param int $page            
     * @param object $user            
     * @return \Zend\Paginator\Paginator
     */
    function getTasksList ($page, $user,$page_size = 30)
    {
        $select = new Select('massend_task');
        $select->where(
            "uid='".$user->domain."'"
        );
        $select->order('id desc');
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($page_size)->setCurrentPageNumber($page);
        return $paginator;
    }
    
    function getWechatAccount($uid)
    {
        $table = new TableGateway('wx_account', $this->adapter);
    	$rowSet=$table->select(array('uid'=>$uid));
    	$row  = $rowSet->current();
    	return $row;
    }
    
    function addWechatAccount($data)
    {
        $table = new TableGateway('wx_account', $this->adapter);
        $table->insert($data);
    	$tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
    }
    
    function saveWechatAccount($uid,$account)
    {
        if($uid) {
            $table = new TableGateway('wx_account', $this->adapter);
            $table->update($data, array('uid' => $uid));
            return true;
    	}
       return false;
    }
    
    function getTasksStat($ids,$uid){
        if(!(is_array($ids) && count($ids) > 0)){
            return null;
        }
        $row_set = $this->adapter->query("select * from massend_task where uid='{$uid}' and id in (".implode(',',$ids).")","execute");
        if($row_set){
            $row_set = $row_set->toArray();
            if(count($row_set)){
                return $row_set;
            }
        }
        
        return null;
    }
}