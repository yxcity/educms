<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Sql;

class News
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
    function addNews ($data)
    {
        $table = new TableGateway('news', $this->adapter);
        $table->insert($data);
        $tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
    }

    /**
     *
     * @todo 编辑消息
     * @param int $id            
     * @param array $data            
     * @return boolean
     */
    function editNews ($id, $data)
    {
        $id = (int) ($id);
        if ($id) {
            $table = new TableGateway('news', $this->adapter);
            $table->update($data, array(
                'id' => $id
            ));
            return true;
        }
        return false;
    }

    /**
     *
     * @todo 取得单条消息数据
     * @param int $id            
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getNews ($id)
    {
        if ($id) {
            $table = new TableGateway('news', $this->adapter);
            $row_set = $table->select(array(
                'id' => (int)$id
            ));
            
            $row = $row_set->toArray();
            if(count($row) > 0){
                $row = $row[0];
                if($row['type'] == 1){/*多条目*/
                    $sub_rows = $table->select(array(
                        'pid'=>$id
                    ));
                    $row['children'] = $sub_rows->toArray();
                }
                return $row;
            }
        }
        return null;
    }

     /**
     *
     * @todo 取一条空消息消息，用作多条目消息创建的默认消息
     * @param int $id            
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getBlankNews ($uid,$type)
    {
        $row_set = $this->adapter->query("select * from news where draft=1 and uid='$uid'","execute");
        if($row_set){
            $row_set = $row_set->toArray();
            if(count($row_set)){
                return $row_set[0];
            }
        }
        
        /*添加页面插入一条封面的空记录*/
        $data['uid'] = $uid;
        $data['type'] = $type;
        $data['draft'] = 1;
        $data['create_time'] = date("Y-m-d H:i:s");
        if($id = $this->addNews($data)){
            return $this->getNews($id);
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
    function getNewsList ($page, $user,$page_size = 30)
    {
        $select = new Select('news');
        $select->where(
            "uid='".$user->domain."' and hide=0 and draft=0 and (type=1 or (type=0 and pid is null))"
        );
        $select->order('id desc');
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($page_size)->setCurrentPageNumber($page);
        return $paginator;
    }
    
    
        /**
     *
     * @todo 取得消息列表
     * @param int $page            
     * @param object $user            
     * @return \Zend\Paginator\Paginator
     */
    function getGroupdNewsList ($page, $user,$page_size = 30,$keywords)
    {
        $select = new Select('news');
        $where=" 1 ";
        $where .=" and uid='".$user->domain."' and hide=0 and draft=0 and (type=1 or (type=0 and pid is null))";
        if ($keywords)
        {
        	$where .=" AND title like '%{$keywords}%'";
        }
    	
    	$select->where($where);
        $select->order('id desc');
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($page_size)->setCurrentPageNumber($page);
        $list=array();
       foreach($paginator as $row){
            if($news_item =$this->getNews($row['id']))
                array_push($list,$news_item);
        }
        return array($paginator,$list);
    }
   
    /**
     * @todo 删除消息
     * @param unknown $id
     * @return boolean
     */

    function delete ($id)
    {
        $id = (int) $id;
        $table = new TableGateway('news', $this->adapter);
        $table->delete("pid=${id} or id=${id}");
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
}