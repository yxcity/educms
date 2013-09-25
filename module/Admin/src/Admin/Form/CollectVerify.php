<?php
namespace Admin\Form;

use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory;

class CollectVerify implements InputFilterAwareInterface
{

    public $id;
    public $name;
    public $url;
    public $rule;
    protected $inputFilter;

    public function exchangeArray ($data)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->name = isset($data['name']) ? $data['name'] : null;
        $this->url = isset($data['url']) ? $data['url'] : null;
        $this->rule = serialize($data['rule']);
    }

    public function setInputFilter (InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }

    public function getInputFilter ()
    {
       // return $this->inputFilter;
    }
}