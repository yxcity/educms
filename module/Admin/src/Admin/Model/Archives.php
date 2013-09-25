<?php
namespace Admin\Model;

use Zend\Db\TableGateway\TableGateway;
use Admin\Form\ArchivesVerify;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class Archives
{

    protected $tableGateway;

    public function __construct (TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function getArchivesList ($page)
    {
        $select = new \Zend\Db\Sql\Select('dede_archives');
        $select->columns(array(
            'id',
            'title'
        ));
        $select->order('id DESC');
        $adapter = new DbSelect($select, $this->tableGateway->getAdapter());
        $paginator = new Paginator($adapter);
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(30);
        return $paginator;
    }

    public function getArchivesID ($id)
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

    public function saveArchives (ArchivesVerify $archives)
    {
        $data = array(
            'title' => $archives->title,
            'content' => $archives->content
        );
        $id = $archives->id;
        if ($id == 0) {
            $this->tableGateway->insert($data);
        } else {
            if ($this->getArchivesID($id)) {
                $this->tableGateway->update($data, array(
                    'id' => $id
                ));
            } else {
                throw new \Exception('Form id docs not exist');
            }
        }
    }

    public function delete ($id)
    {
        $this->tableGateway->delete(array(
            'id' => $id
        ));
    }
}