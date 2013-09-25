<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;
use Zend\Config\Config;
use Zend\Config\Writer\PhpArray;
use module\Application\src\Model\Tool;

class Msg
{

    private $adapter;

    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }
    /**
     * @todo 写入错误代码
     * @param array $data
     * @return number
     */
    function insert ($data)
    {
        $table = new TableGateway('error', $this->adapter);
        $table->insert($data);
        $tid = $table->getLastInsertValue();
        return $tid;
    }
    /**
     * @todo 更新错误代码
     * @param Array $data
     * @param int $id
     * @return boolean
     */
    function update ($data, $id)
    {
        $id = (int) $id;
        if ($id) {
            $table = new TableGateway('error', $this->adapter);
            $table->update($data, array(
                'id' => $id
            ));
            return true;
        }
        return false;
    }
    /**
     * @todo 取得单个错误信息
     * @param Int $id
     * @return Ambigous <multitype:, ArrayObject, NULL, \ArrayObject, unknown>
     */
    function getOne ($id)
    {
        $table = new TableGateway('error', $this->adapter);
        $rowSet = $table->select(array(
            'id' => $id
        ));
        $row = $rowSet->current();
        return $row;
    }
    /**
     * @todo 取得错误列表
     * @param int $page
     * @return \Zend\Paginator\Paginator
     */
    function msgList ($page)
    {
        $select = new Select('error');
        $select->order('id desc');
        $adapter = new DbSelect($select, $this->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setItemCountPerPage(30)
            ->setCurrentPageNumber($page)
            ->setPageRange(5);
        return $paginator;
    }
    /**
     * @todo 写入文件
     */
    function writeFile()
    {
    	$table = new TableGateway('error', $this->adapter);
    	$rows =  $table->select();
    	$config = new Config(array(),true);
        if ($rows)
        {
            foreach ($rows as $val) {
        		$config->$val['code']=$val['msg'];
            }
        }
        $writer = new PhpArray();
        $ini=$writer->toString($config);
        $filename='./data/error.php';
        Tool::writeFile($filename,$ini,'w+');
    }
}