<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Sql;

class Autoreply
{

    private $adapter;

    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     *
     * @todo 添加消息
     * @param array $data            
     * @return Ambigous <boolean, number>
     */
    function addRule ($data)
    {
        $table = new TableGateway('autoreply', $this->adapter);
        try{
            $table->insert($data);
        }catch(\Exception  $e){
            return false;
        }
        $tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
    }
    
    /**
     *
     * @todo 添加消息
     * @param array $data            
     * @return Ambigous <boolean, number>
     */
    function addKeyword ($data)
    {
        $table = new TableGateway('autoreply_keyword', $this->adapter);
        try{
            $table->insert($data);
        }catch(\Exception  $e){
            return false;
        }
        $tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
    }
    
     /**
     *
     * @todo 添加回复规则
     * @param array $data            
     * @return Ambigous <boolean, number>
     */
    function addReply($data)
    {
        $table = new TableGateway('autoreply_reply', $this->adapter);
        try{
            $table->insert($data);
        }catch(\Exception  $e){
            return false;
        }
        $tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
    }
    
    /**
     *
     * @todo 编辑关键字
     * @param int $id            
     * @param array $data            
     * @return boolean
     */
    function editKeyword ($id, $data)
    {
        $id = (int) ($id);
        if ($id) {
            $table = new TableGateway('autoreply_keyword', $this->adapter);
            $table->update($data, array(
                'id' => $id
            ));
            return true;
        }
        return false;
    }

        /**
     *
     * @todo 编辑消息
     * @param int $id            
     * @param array $data            
     * @return boolean
     */
    function editRule ($id, $data)
    {
        if (!$id) {
            return false;
        }
        $where = array(
                    'id' => (int)$id
                );
        
        $table = new TableGateway('autoreply', $this->adapter);
        $table->update($data,$where);
        return true;
    }
    
    /**
     *
     * @todo 编辑消息
     * @param int $id            
     * @param array $data            
     * @return boolean
     */
    function editReply ($id, $data,$autoreply_id=null)
    {
        if (!$id) {
            return false;
        }
        $where = null;
        if($autoreply_id){
            $where =  array(
                'id' => $id,
                'autoreply_id' => $autoreply_id
                    );
        }else{
            $where = array(
                    'id' => $id
                    );
        }
        
        $table = new TableGateway('autoreply', $this->adapter);
        $table->update($data,$where);
        return true;
    }

    /**
     *
     * @todo 取得单条消息数据
     * @param int $id            
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getRule ($id)
    {
        $id = (int) $id;
        if ($id) {
            $table = new TableGateway('autoreply', $this->adapter);
            $row_set = $table->select(array(
                'id' => $id
            ));
            return $row_set->current();
        }
        return null;
    }
    
     /**
     * @todo 获取所有的规则
     * @param int $id            
     * @return Ambigous <integer>|rowset
     */
    function getAllRules ($uid)
    {
        if ($uid) {
            $table = new TableGateway('autoreply', $this->adapter);
            $row_set = $table->select(array(
                'uid' => $uid
            ));
            
            if($row_set){
                $row_set = $row_set->toArray();
                foreach($row_set as &$row){
                    $row['keywords'] = $this->getRuleKeywords($row['id']);
                    $row['news'] = $this->getRuleNews($row['id']);
                }
                return $row_set;
            } 
        }
        return null;
    }
    
     /**
     * @todo 获取回复项
     * @param int $id            
     * @return Ambigous <integer>|rowset
     */
    function getRuleReply ($reply_id)
    {
        if ($reply_id) {
            $row_set = $this->adapter->query('SELECT a.*,b.type,b.title,b.pic_url,b.url,b.description FROM `autoreply_reply` as a left join news as b on a.news_id = b.id where a.id=' . (int)$reply_id,'execute');
            if($row_set){
                $row_set = $row_set->toArray();
                if(count($row_set))
                    return $row_set[0];
            }
        }
        return null;
    }
    
    /**
     * @todo 获取所有的规则
     * @param int $id            
     * @return Ambigous <integer>|rowset
     */
    function getRuleKeywords($rule_id)
    {
        if ($rule_id) {
            $table = new TableGateway('autoreply_keyword', $this->adapter);
            $row_set = $table->select(array(
                'autoreply_id' => $rule_id
            ));
            if($row_set){
                return $row_set->toArray();
            } 
        }
        return null;
    }
    
    /**
     * @todo 获取所有的规则
     * @param int $id            
     * @return Ambigous <integer>|rowset
     */
    function getRuleNews($rule_id)
    {
        if ($rule_id) {
            $row_set = $this->adapter->query('SELECT a.*,b.type,b.title,b.pic_url,b.url,b.description FROM `autoreply_reply` as a left join news as b on a.news_id = b.id where a.autoreply_id=' . $rule_id,'execute');
            
            if($row_set){
                return $row_set->toArray();
            }
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
    function getRuleList ($page, $user)
    {
        $select = new Select('autoreply');
        if ($user->power < 3) {
            $select->where(array(
                'uid' => $user->domain/*lwq:domain用整数对应起来管理更适合*/
            ));
        }
        $select->order('id desc');
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage(30)->setCurrentPageNumber($page);
        return $paginator;
    }
   
    /**
     * @todo 删除规则
     * @param unknown $id
     * @return boolean
     */
    function delete($id)
    {
        $id = (int) $id;
        $this->delkeywordByRuleId($id);/*删除关键字*/
        $this->delReplisByRuleId($id);/*删除相关回复*/
        $table = new TableGateway('autoreply', $this->adapter);
        $table->delete(array(
            'id' => $id
        ));
        
        return true;
    }

    /**
     * @todo 删除规则的关键字
     * @param unknown $id
     * @return boolean
     */
    private function delkeywordByRuleId ($autoreply_id)
    {
        $table = new TableGateway('autoreply_keyword', $this->adapter);
        $table->delete(array(
            'autoreply_id' => (int) $autoreply_id
        ));
        return true;
    }
    
        /**
     * @todo 删除规则的关键字
     * @param unknown $id
     * @return boolean
     */
    private function delReplisByRuleId ($autoreply_id)
    {
        $table = new TableGateway('autoreply_reply', $this->adapter);
        $table->delete(array(
            'autoreply_id' => (int) $autoreply_id
        ));
        return true;
    }
    /**
     * @todo 删除关键字
     * @param unknown $id
     * @return boolean
     */
    function delkeyword ($id)
    {
        $id = (int) $id;
        $table = new TableGateway('autoreply_keyword', $this->adapter);
        $table->delete(array(
            'id' => $id
        ));
        return true;
    }
    
    /**
     * @todo 删除回复规则
     * @param unknown $id
     * @return boolean
     */
    function delreply ($id,$autoreply_id=null)
    {
        $id = (int) $id;
        $table = new TableGateway('autoreply_reply', $this->adapter);
        $where = null;
        if($autoreply_id){/*校验id合法性，避免误删*/
            $where = array(
                'id' => $id,
                'autoreply_id' => (int)$autoreply_id
            );
        }else{
            $where = array(
                'id' => $id
            );
        }
        /*news表文本只有自动回复可建立，固删除规则时删文本回复，图文回复在素材管理栏目管理，只取消关联，留作重用*/
        $this->adapter->query("delete from news where type=2 and id = (select news_id from autoreply_reply where id =${id})",'execute');
        $table->delete($where);
        return true;
    }
    
         /**
     * @todo 查询关键字相关回复
     * @param int $id            
     * @return Ambigous <integer>|rowset
     */
    function findReplyByKeyword ($word,$domain)
    {
        if ($word) {
            /*lwq:使用zend api提供的防注入， 现在先简单处理下单引号*/
            $word = str_replace("'","\\'",$word);
            $row_set = $this->adapter->query("select * from autoreply_keyword where autoreply_id in (select id from autoreply where uid='${domain}' ) and ((type=1 and keyword='${word}') or (type=0 and keyword like '%${word}%'));" ,'execute');
            if($row_set){
                $row_set = $row_set->toArray();
                foreach( $row_set as &$row){
                    $news_set = $this->adapter->query( "select * from news where id in (select news_id from autoreply_reply where autoreply_id=" . $row['autoreply_id']  . " )",'execute');
                    if($news_set){
                        $row['replies'] = $news_set->toArray();
                    }else{
                        $row['replies'] = null;
                    }
                    
                }
                return $row_set;
            }
        }
        return null;
    }
}