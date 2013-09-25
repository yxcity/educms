<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Sql;

class Fans
{

    private $adapter;
    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     *
     * @todo 取得单条粉丝数据
     * @param int $id            
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getFanDetail($id)
    {
        if ($id) {
            $table = new TableGateway('members', $this->adapter);
            $row_set = $table->select(array(
                'id' => (int)$id
            ));
            
            $row = $row_set->toArray();
            if(count($row) > 0){
                return $row[0];
            }
        }
        return null;
    }

    /**
     *
     * @todo 取得用户列表
     * @param int $page            
     * @param object $user            
     * @return \Zend\Paginator\Paginator
     */
    function getFansList ($nickname=null,$uid,$page,$page_size = 30)
    {
        $where = "uid='".$uid."'";
        if($nickname){
            $where .= " and nickname like '%{$nickname}%'";
        }
        $select = new Select('members');
        $select->where($where);
        $select->order('nickname asc');
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($page_size)->setCurrentPageNumber($page);
        return $paginator;
    }
    
    
   /**
     *
     * @todo 取得用户列表
     * @param int $page            
     * @param object $user            
     * @return \Zend\Paginator\Paginator
     */
    function getFansListEx($uid,$nickname=null,$ids=null)
    {
        $where = " where uid='$uid'";
        if($nickname){
            $where .= " and nickname like '%{$nickname}%'";
        }
        
        if(is_array($ids)){
            $where .= " and id in (".implode(',',$ids).")";
        }
        
        $row_set = $this->adapter->query("select fakeid from members {$where}" ,"execute");
        if($row_set){
            $row_set = $row_set->toArray();
            if(count($row_set)){
                return $row_set;
            }
        }
        return null;
    }
}