<?php

namespace Admin\Form;

use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\Factory;
use Zend\Filter\HtmlEntities;

class ArchivesVerify implements InputFilterAwareInterface {
	public $id;
	public $title;
	public $content;
	protected $inputFilter;
	
	public function exchangeArray($data) {
		$filter=new HtmlEntities();
		$this->id = isset ( $data ['id'] ) ? $data ['id'] : null;
		$this->title = isset ( $data ['title'] ) ? $data ['title'] : null;
		$this->content = isset ( $data ['content'] ) ? $filter->filter($data ['content']) : null;
	}
	
	public function setInputFilter(InputFilterInterface $inputFilter) {
		throw new \Exception ( "Not used" );
	}
	
	public function getInputFilter() {
		if (! $this->inputFilter) {
			$inputFilter = new InputFilter ();
			$factory = new Factory ();
			$inputFilter->add ( $factory->createInput ( array (
					'name' => 'id',
					'required' => true,
					'filters' => array (
							array (
									'name' => 'Int' 
							) 
					) 
			) ) );
			
			$inputFilter->add ( $factory->createInput ( array (
					'name' => 'title',
					'required' => true,
					'filters' => array (
							array (
									'name' => 'StripTags' 
							),
							array (
									'name' => 'StringTrim' 
							) 
					) 
			) ) );
			$this->inputFilter = $inputFilter;
		}
		return $this->inputFilter;
	}
}