<?php
namespace library\Helper;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;

class HModel {
    
    function __construct ($adapter)
    {
        $this->adapter = $adapter;
    }
}

?>