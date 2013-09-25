<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Sql;

class Market
{

    private $adapter;
    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     *
     * @todo 添加营销活动
     * @param array $data            
     * @return Ambigous <boolean, number>
     */
    function addActivity($data)
    {
        $table = new TableGateway('market_activity', $this->adapter);
        $table->insert($data);
        $tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
    }
    
    /**
     *
     * @todo 添加抽奖记录
     * @param array $data            
     * @return Ambigous <boolean, number>
     */
    function addRecord($data)
    {
        $table = new TableGateway('market_record', $this->adapter);
        $table->insert($data);
        $tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
    }
    
    /**
     *
     * @todo 编辑中奖记录
     * @param int $id            
     * @param array $data            
     * @return boolean
     */
    function editPrize($id, $data,$uid=null)
    {
        $id = (int) ($id);
        if (!$id) {
            return false;
        }
        $where = array('id' => $id);
        if($uid){
            $where['uid']= $uid;
        }
        
        $table = new TableGateway('market_prize', $this->adapter);
        $table->update($data, $where);
        return true;
    }
    
    /**
     *
     * @todo 生成奖品记录
     * @param array $data            
     * @return Ambigous <boolean, number>
     */
    function addPrizes($activity_id,$uid,$datas)
    {
        $table = new TableGateway('market_prize', $this->adapter);
        $connection = $this->adapter->getDriver()->getConnection();
        try {
            $connection->beginTransaction();
            foreach($datas as $data){
                for($i=$data['prize_count'];$i--;){
                    $prize = array();
                    $prize['activity_id'] = $activity_id;
                    $prize['sn'] = uniqid(rand());
                    $prize['type'] = $data['prize_type'];
                    $prize['status'] = 0;
                    $prize['name'] = $data['prize_name'];
                    $prize['uid'] = $uid;
                    $prize['create_time'] = date("Y-m-d H:i:s");
                    $table->insert($prize);
                }
            }
            $connection->commit();
        }catch (\Exception $e){
            if ($connection instanceof \Zend\Db\Adapter\Driver\ConnectionInterface) {
                $connection->rollback();
            }
            return false;
	}
        return true;
    }

    /**
     *
     * @todo 编辑消息
     * @param int $id            
     * @param array $data            
     * @return boolean
     */
    function editActivity($id, $data)
    {
        $id = (int) ($id);
        if ($id) {
            $table = new TableGateway('market_activity', $this->adapter);
            $table->update($data, array(
                'id' => $id
            ));
            return true;
        }
        return false;
    }

    /**
     * @todo 取得单条营销任务数据
     * @param int $id            
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getActivity($id)
    {
        if ($id) {
            $table = new TableGateway('market_activity', $this->adapter);
            $row_set = $table->select(array(
                'id' => (int)$id
            ));
            
            $row = $row_set->toArray();
            if(count($row) > 0){
                $row[0]['config'] = json_decode($row[0]['config'],true);
                return $row[0];
            }
        }
        return null;
    }
    
     /**
     *
     * @todo 取得营销任务的奖项信息
     * @param int $id            
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getPrizes($activity_id=null,$status=null,$type=null,$memb_id=null,$openid=null)
    {
        $where = array();
        if(is_numeric($activity_id)){
           $where['activity_id'] = (int)$activity_id;
        }
        
        if(is_numeric($status)){
           $where['status'] = (int)$status;
        }
        
        if(is_numeric($type)){
           $where['type'] = (int)$type;
        }
        
        if(is_numeric($memb_id)){
           $where['memb_id'] = (int)$memb_id;
        }
        
        if($openid){
           $where['openid'] = $openid;
        }
        
        $table = new TableGateway('market_prize', $this->adapter);
        $row_set = $table->select($where);
        $row = $row_set->toArray();
        if(count($row) > 0){
            return $row;
        }
        return null;
    }
    
    /**
     *
     * @todo 取得营销任务的奖项信息
     * @param int $id            
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getPrize($id,$status=null,$type=null,$uid=null)
    {
        if (!is_numeric($id)) {
            return null;
        }
        
        $where = array(
            'id' => (int)$id
        );
        
        if(is_numeric($status)){
           $where['status'] = (int)$status;
        }
        
        if(is_numeric($type)){
           $where['type'] = (int)$type;
        }
        
        if($uid){
           $where['uid'] = $uid;
        }
        
        $table = new TableGateway('market_prize', $this->adapter);
        $row_set = $table->select($where);
        $row = $row_set->toArray();
        if(count($row) > 0){
            return $row;
        }
        return null;
    }
    
    /**
     *
     * @todo 取得营销任务的奖项信息
     * @param int $id            
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getPrizeInfo($activity_id,$status=null)
    {
        if(!is_numeric($activity_id)){
            return null;
        }
        
        $where = " where activity_id={$activity_id}";
        if(is_numeric($status)){
            $where .=  " and status = {$status}";
        }
       
        $row_set = $this->adapter->query("select name,count(*) as count,type 
                            from (select * from market_prize {$where}) as tmp 
                            group by type","execute");
        if($row_set){
            $row_set = $row_set->toArray();
            if(count($row_set)){
                return $row_set;
            }
        }
        return null;
    }
    
    /**
     *
     * @todo 取得营销任务的奖项信息
     * @param int $id            
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getVoteStatics($activity_id)
    {
        if(!is_numeric($activity_id)){
            return null;
        }
        
        $where = " where activity_id={$activity_id}";
        $row_set = $this->adapter->query("select data from market_record {$where}","execute");
        if($row_set){
            $set = $row_set->toArray();
            if(count($set)){
               $stat_result = array();
               foreach($set as $item){
                   $vote_data = json_decode($item['data'],true);
                   if(is_array($vote_data)){
                       foreach($vote_data as $option){
                           $option = 'option_'.$option ;
                           $stat_result[$option] = isset($stat_result[$option])?($stat_result[$option]+1):1;
                       }
                   }
               }
               $total = array_sum($stat_result);
               if($total > 0){
                foreach($stat_result as &$option){
                    $option = array(
                        'rate'=>round($option / $total * 100),
                        'count'=>$option,
                    );
                }
                return $stat_result;
               }
            }
        }
        return null;
    }
    
    /**
     *
     * @todo 取得营销任务抽奖记录
     * @param int $id            
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getRecords($activity_id,$uid=null,$openid=null)
    {
        if(!$activity_id) {
            return null;
        }
        
        $where = array('activity_id' => (int)$activity_id);
        if($openid){
            $where['openid'] = $openid;
        }
        
        if($uid){
            $where['memb_id'] = $uid;
        }
        
        $table = new TableGateway('market_record', $this->adapter);
        $row_set = $table->select($where);
        $row = $row_set->toArray();
        if(count($row) > 0){
            return $row;
        }
        return null;
    }
   
    
    /**
     *
     * @todo 取得消息列表
     * @param int $page            
     * @param object $user            
     * @return \Zend\Paginator\Paginator
     */
    function getGroupdActivityList ($page, $user,$page_size = 30,$type=null,$keywords=null)
    {
        $select = new Select('market_activity');
        $where=" 1 ";
        $where .=" and uid='".$user->domain."' ";
        if ($keywords){
            $where .=" AND title like '%{$keywords}%'";
        }
        
        if (is_numeric($type)){
            $where .=" AND type={$type}";
        }
    	
    	$select->where($where);
        $select->order('id desc');
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($page_size)->setCurrentPageNumber($page);
        return $paginator;
    }
    
    /**
     *
     * @todo 取得中奖序列号列表
     * @param int $page            
     * @param object $user            
     * @return \Zend\Paginator\Paginator
     */
    function getGroupdSNList($page, $user,$page_size = 30,$activity_id=null,$status=null,$type=null)
    {
        $select = new Select('market_prize');
        $where =" uid='".$user->domain."' ";
        if (is_numeric($status)){
            $where .=" and  status={$status} ";
        }
        
        if (is_numeric($type)){
            $where .=" and  type={$type} ";
        }
        
        if (is_numeric($activity_id)){
            $where .=" and  activity_id={$activity_id} ";
        }
        
    	$select->where($where);
        $select->order('status desc')->order('type asc');
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($page_size)->setCurrentPageNumber($page);
        return $paginator;
    }
   
    /**
     * @todo 删除消息
     * @param unknown $id
     * @return boolean
     */

    function delete ($id)
    {
        $id = (int) $id;
        $table = new TableGateway('market_activity', $this->adapter);
        $table->delete("id={$id}");
        
        $table = new TableGateway('market_prize', $this->adapter);
        $table->delete("activity_id={$id}");
        return true;
    }
    
    /*阅读文章*/
    function incReadCount($id)
    {
        $id = (int) $id;
        if($id > 0 ){
            return $this->adapter->query("update news set read_count=read_count+1 where id={$id}",'execute');
        }else{
            return false;
        }
    }
    
    /**
     * @todo 删除消息
     * @param unknown $id
     * @return
     */
    function findActivitysNews($keword,$key_event,$domain){
        $where = "";
        $sql = "";
        if($keword){
            $sql = "select n.*,tmp.id as activity_id,tmp.type as activity_type from news as n,(
                        select  news_id,id,type from market_activity where 
                        `disable`=0
                        and uid='".mysql_escape_string($domain)."'
                        and keyword like '%".mysql_escape_string($keword)."%'
                        and ((time_type=1 and now()>=open_time and now()<=close_time) or time_type=0)
                         ) as  tmp where n.id=tmp.news_id";
        }else if($key_event){
            $sql = "select n.*,tmp.id as activity_id,tmp.type as activity_type from news as n,(select mkt.news_id,mkt.id,mkt.type from market_activity as mkt,menu as mu where mkt.menu_id=mu.id 
                                            and mkt.uid='".mysql_escape_string($domain)."'
                                            and mu.`key`='".mysql_escape_string($key_event)."' 
                                            and ((mkt.time_type=1 and now()>=mkt.open_time and now()<=mkt.close_time) or mkt.time_type=0)
                                            ) as tmp where n.id=tmp.news_id";
        }
       
        $row_set = $this->adapter->query($sql,"execute");
        if($row_set){
            $row_set = $row_set->toArray();
            if(count($row_set)){
                return $row_set;
            }
        }
        return null;
    }
}