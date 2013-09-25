<?php
namespace Admin\Model;

use Zend\Authentication\Adapter\DbTable as AuthAdapter;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class Member
{

    private $adapter;

    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     *
     * @todo 编辑账号
     * @param Int $id            
     * @param Array $data            
     */
    function editUser ($id, $data)
    {
        $id = (int) $id;
        if ($id) {
            $table = new TableGateway('member', $this->adapter);
            $table->update($data, array('id' => $id));
            return true;
        }
        return false;
    }

    /**
     *
     * @todo 取得账号列表
     * @param unknown $page            
     * @return \Zend\Paginator\Paginator
     */
    function memberList ($page,$user,$domain,$nums=30,$keywords=null)
    {
        $select = new Select('member');
         $select->columns(array(
           /* 'id',
            'username',
            'realname',
            'openid',
            'regtime',
            'lasttime',
            'lastip'*/
			'*'
        ));
        if ($user->power < 3)
        {
            $select->where(array('domain' => $user->domain));
        } 
		
		/*$where = " ((ownerdomain = '".$user->domain."' AND roleid>='".$user->roleid."') OR id = '".$user->id."')";
        if ($keywords)
    	{
    		$where .=" AND (username like '%{$keywords}%' or realname like '%{$keywords}%')";
    	}*/
		if ($user->power < 3) {
            $where = " 1 ";
            $where .=" AND domain='{$user->domain}'";
        	if ($keywords)
        	{
        		$where .=" AND (username like '%{$keywords}%' or realname like '%{$keywords}%')";
        	}
    	   $select->where($where);
        }
        if ($user->power==3)
        {
            $where = " 1 ";
        	if ($keywords)
        	{
        		$where .=" AND (username like '%{$keywords}%' or realname like '%{$keywords}%')";
        	}
    	   $select->where($where);
        }        
		$select->where($where);
        $select->order('id desc');
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($nums)->setCurrentPageNumber($page);
        return $paginator;
    }
    /**
     * @todo 删除账号
     * @param int $id
     * @return boolean
     */
    function delUser($id)
    {
    	$id = (int)$id;
        $table = new TableGateway('member', $this->adapter);
    	$table->delete(array('id'=>$id));
    	return true;
    }
}