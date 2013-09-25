<?php

namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Db\Sql\Sql;

class Commodity {

    private $adapter;

    function __construct($adapter) {
        $this->adapter = $adapter;
    }

    /**
     *
     * @todo 添加商品
     * @param array $data            
     * @return Ambigous <boolean, number>
     */
    function addCommodity($data) {
        $table = new TableGateway('commodity', $this->adapter);
        $table->insert($data);
        $tid = $table->getLastInsertValue();
        return $tid ? $tid : false;
    }

    /**
     *
     * @todo 编辑商品信息
     * @param int $id            
     * @param array $data            
     * @return boolean
     */
    function editCommodity($id, $data) {
        $id = (int) ($id);
        if ($id) {
            $table = new TableGateway('commodity', $this->adapter);
            $table->update($data, array(
                'id' => $id
            ));
            return true;
        }
        return false;
    }

    /**
     *
     * @todo 取得单条商品数据
	 * @增加运费数据转换,得到的数据格式为 
	 * $row->freight = array(
	 * 	'feeby'=>'seller|buyer', seller-卖家(商户) buyer-买家(消费者)
	 * 	'mail'=>平邮费用,
	 * 	'ems'=>EMS费用,
	 * 	'wuliu'=>物流费用
	 * )
     * @param int $id            
     * @return Ambigous <boolean, multitype:, ArrayObject, NULL, \ArrayObject, unknown>|boolean
     */
    function getCommodity($id) {
        $id = (int) $id;
        if ($id) {
            $table = new TableGateway('commodity', $this->adapter);
            $rowSet = $table->select(array(
                'id' => $id
            ));
			if($rowSet->count() > 0){
				$row = $rowSet->current();
				if(!empty($row->freight)){
					$row->freight = unserialize($row->freight);
				}
				return $row;	
			}
        }
        return false;
    }

    /**
     * @todo 取得指定门店商品
     * @param int $id
     * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    function shopCount($id, $added = false) {
        $id = (int) $id;
        $where = array('shop' => $id);
        if ($added) {
            $where['added'] = 1;
        }
        $table = new TableGateway('commodity', $this->adapter);
        $rows = $table->select($where);
        return $rows;
    }

    /**
     * @todo 取得指定分类商品
     * @param Int $id
     * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    function cateCount($domain, $id = null, $added = false) {
        $id = (int) $id;
        $table = new TableGateway('commodity', $this->adapter);
        $where = array('uid' => $domain);
        if ($id) {
            $where['cateID'] = $id;
        }
        if ($added) {
            $where['added'] = 1;
        }
        $rows = $table->select($where);
        return $rows;
    }

    /**
     * @todo 取得指定关键词商品
     * @param Int $id
     * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    function keywordsCount($domain, $keywords = null, $added = false) {
        $table = new TableGateway('commodity', $this->adapter);
        $where = "uid ='{$domain}'";
        if ($keywords) {
            $where .= " AND name like '%{$keywords}%'";
        }
        if ($added) {
            $where .= " AND added='1' ";
        }
        $rows = $table->select($where);
        return $rows;
    }

    /**
     *
     * @todo 取得商品总数
     * @param Int $uid            
     * @return Ambigous <number, NULL>
     */
    function commodityCount($uid, $user = null, $shop = null, $added = false) {
        $table = new TableGateway('commodity', $this->adapter);
        $where = "uid ='{$uid}'";
        $shop = $shop ? $shop : 'NULL';
        if ($user->power == 1) {
            $where.=" AND shop IN({$shop})";
        }
        if ($added) {
            $where.=" AND added = '1'";
        }
        $rows = $table->select($where);
        $res = $rows->count();
        return $res;
    }

    /**
     *
     * @todo 取得商品列表
     * @param int $page            
     * @param object $user            
     * @return \Zend\Paginator\Paginator
     */
    function commodityList($page, $user, $shop = null, $nums = 30, $keywords = null, $added = false) {
        $select = new Select('commodity');
        $where = " 1 ";
        if ($user->power < 3) {
            $where .=" AND uid='{$user->domain}'";
        }
        $shop = $shop ? $shop : 'NULL';
        if ($user->power == 1) {
            $where.=" AND shop IN({$shop})";
        }

        if ($keywords) {
            $where .=" AND name like '%{$keywords}%'";
        }

        if ($added) {
            $where .=" AND added='1'";
        }

        $select->where($where);
        $select->order('id desc');
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage($nums)->setCurrentPageNumber($page);
        return $paginator;
    }

    /**
     * @todo 取得欢迎商品
     * @param String $domain
     * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    function welcome($domain) {
        $table = new TableGateway('commodity', $this->adapter);
        $row = $table->select(array('welcome' => 1, 'uid' => $domain, 'added' => 1));
        return $row;
    }

    /**
     * @todo 按指定ID 取得商品
     * @param type $id
     * @return type
     */
    function byID($id) {
        $table = new TableGateway('commodity', $this->adapter);
        $rows = $table->select("id IN({$id})");
        $tmp = array();
        if ($rows) {
            foreach ($rows as $key => $val) {
                $tmp[$key] = $val;
            }
        }
        return $tmp;
    }

    /**
     * @todo 按关键字取得商品
     * @param unknown $key
     * @return Ambigous <\Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    function keyList($key = null, $uid = null, $welcome = null, $added = false) {
        $table = new TableGateway('commodity', $this->adapter);
        $where = '1 ';
        if ($key) {
            $where .= "AND name like '%{$key}%'";
        }

        if ($uid) {
            $where .= " AND uid='{$uid}'";
        }

        if ($welcome) {
            $where .= " AND welcome='1'";
        }

        if ($added) {
            $where .= " AND added='1'";
        }
        $resultSet = $table->select($where);
        return $resultSet;
    }

    /**
     * @todo 按分类域名取得商品
     * @param Int $num
     * @param number $page
     * @param String $domian
     * @param string $tid
     * @return Ambigous <\Zend\Db\Sql\Select, \Zend\Db\Sql\Select>
     */
    function proList($num, $page, $domain, $shop = null, $cateID = null, $keywords = null, $commend = null, $added = false, $order = 'id') {
        $sql = new Sql($this->adapter);
        $select = $sql->select('commodity');
        $where = "uid ='{$domain}'";
        if ($shop) {
            $where .= " AND shop='{$shop}'";
        }
        if ($cateID) {
            $where .= " AND cateID='{$cateID}'";
        }
        if ($keywords) {
            $where .= " AND name like '%{$keywords}%'";
        }
        if ($commend) {
            $where .= " AND commend='{$commend}'";
        }
        if ($added) {
            $where .= " AND added='1'";
        }
        $select->where($where);
        $select->order($order . ' DESC');
        $select->limit($num);
        $offset = ($page - 1) * $num;
        $select->offset($offset);
        $rowsSet = $sql->prepareStatementForSqlObject($select);
        $rows = $rowsSet->execute();
        return $rows;
    }

    /**
     * @todo 删除商品
     * @param unknown $id
     * @return boolean
     */
    function delete($id) {
        $id = (int) $id;
        $table = new TableGateway('commodity', $this->adapter);
        $table->delete(array(
            'id' => $id
        ));
        return true;
    }

    /**
     * @商品点击量 By John  
     * @param Array $data
     */
    function addclick($id) {
        $id = (int) $id;
        return $this->adapter->query("update commodity set click=click+1 where id=${id}", 'execute');
    }

    /**
     * 保存商品各规格/属性库存信息
     * @param undefined $data 规格信息
     * @param undefined $goods_id 商品编号
     * 
     */
    function saveSpecData($data, $goods_id = 0) {
        if (!$goods_id) {
            return FALSE;
        }
        $table = new TableGateway('wx_goods_stock', $this->adapter);
        foreach ($data as $key => $val) {
            $where = array(
                'goods_id' => $goods_id,
                'goods_spec_id' => $key
            );
            $info = array(
                'goods_id' => $goods_id,
                'goods_spec_id' => $key,
                'goods_stock_cnt' => $val
            );
            $rowSet = $table->select($where);

            if ($rowSet->count() > 0) {
                $table->update($info, $where);
            } else {
                $table->insert($info);
            }
        }
    }

    /**
     * 查找某个商品的所有规格/属性相关的库存信息 
     * @param undefined $goods_id
     * 
     */
    function getExistedSpec($goods_id) {
        $table = new TableGateway('wx_goods_stock', $this->adapter);
        $rowSet = $table->select(array('goods_id' => $goods_id));
        if ($rowSet->count() > 0) {
            $dbRst = array();
            foreach ($rowSet as $r) {
                $dbRst[$r->goods_spec_id] = $r->goods_stock_cnt;
            }
            return $dbRst;
        }
        return FALSE;
    }

    /**
     * @todo 写入购物车
     * @param type $id
     * @param type $num
     * @param type $event
     * @return type
     */
    function cartData($id = null, $num = null, $event = null, $member = null) {
        $cartBuyer = unserialize(\module\Application\src\Model\Tool::getCookie('CartData'));
        if (!$cartBuyer && isset($member->id)) {

            $cartBuyer = \module\Application\src\Model\Tool::getCache('CartUser_' . $member->id);
        }

        if ($id && $num && $event === 'add') {
            if (isset($cartBuyer[$id])) {
                $cartBuyer[$id]['num'] = $cartBuyer[$id]['num'] + $num;
            } else {
                $cartBuyer[$id]['num'] = $num;
            }
        }

        if ($id && $event === 'subtract') {
            if (isset($cartBuyer[$id]['num']) && $cartBuyer[$id]['num'] > $num) {
                $cartBuyer[$id]['num'] = $cartBuyer[$id]['num'] - $num;
            } else {
                unset($cartBuyer[$id]);
            }
        }
		
			if ($id && $event === 'del') {
					unset($cartBuyer[$id]);
			}

        if ($id && $num && $event === 'import') {
            $cartBuyer[$id]['num'] = $num;
        }


        if (isset($member->id)) {
            \module\Application\src\Model\Tool::setCache('CartUser_' . $member->id, $cartBuyer);
            \module\Application\src\Model\Tool::setCookie('CartData', '', time() - 7200, true);
        } else {
            \module\Application\src\Model\Tool::setCookie('CartData', serialize($cartBuyer), time() + (3600 * 24 * 365), true);
        }

        return $cartBuyer;
    }

    /**
     * @todo 清空购物车数据
     * @param type $member
     */
    function deletecartData($member) {
        if (isset($member->id)) {
            \module\Application\src\Model\Tool::delCache('CartUser_' . $member->id);
        }
        \module\Application\src\Model\Tool::setCookie('CartData', null, time() - 7200, true);
    }

}