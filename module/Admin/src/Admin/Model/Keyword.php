<?php
namespace Admin\Model;


use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\TableGateway\TableGateway;

class Keyword
{

    private $adapter;

    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }
    
    function keyList($msgType,$page,$domain=null,$perpage='30',$keywords=null,$user=null)
    {
    	$select = new Select('keywords');
    	if($user && $user->power == 3){ 
            $where = " 1 ";
    	}else{
    	    $where = "uid ='{$domain}'";
    	}
    	if ($msgType)
    	{
    		$where .= " AND msgType='{$msgType}'";
    	}
    	if ($keywords)
    	{
    		$where .=" AND content like '%{$keywords}%'";
    	}
    	$select->where($where);
    	$select->order('id DESC');
    	$adapter = new DbSelect($select, $this->adapter);
    	$paginator = new Paginator($adapter);
    	$paginator->setItemCountPerPage($perpage)->setCurrentPageNumber($page);
    	return $paginator;
    }
    
    /**
     *
     * @todo 取得用户提交总数
     * @param Int $uid            
     * @return Ambigous <number, NULL>
     */
    function keyCount ($uid)
    {
        $table = new TableGateway('keywords', $this->adapter);
        $rows = $table->select(array(
            'uid' => $uid
        ));
        $res = $rows->count();
        return $res;
    }
}