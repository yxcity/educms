<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Admin\Form\CollectVerify;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class Collect
{

    protected $tableGateway;

    public function __construct (TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function collectList ($page)
    {
        $select = new Select('collect');
        $select->columns(array(
            'id',
            'title',
            'lasttime'
        ));
        $select->order('id DESC');
        $adapter = new DbSelect($select, $this->tableGateway->adapter);
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($page)->setItemCountPerPage(30);
        return $paginator;
    }

    public function saveCollect ($collect)
    {
        $data=array('name'=>$collect->name,'url'=>$collect->url,'rule'=>$collect->rule);
        $id = (int) $collect->id;
        if ($id == 0) {
            $this->tableGateway->insert($data);
        } else {
            if ($this->getCollectID($id)) {
                $this->tableGateway->update($data, array(
                    'id' => $id
                ));
            } else {
                throw new \Exception('Form id does not exist');
            }
        }
    }

    public function getCollectID ($id)
    {
        $id = (int) $id;
        $rowSet = $this->tableGateway->select(array(
            'id' => $id
        ));
        $row = $rowSet->current();
        if (! $row) {
            throw new \Exception("Could not find row {$row}");
        }
        return $row;
    }
}