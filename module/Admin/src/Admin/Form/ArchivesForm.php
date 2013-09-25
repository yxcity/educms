<?php

namespace Admin\Form;

use Zend\Form\Form;

class ArchivesForm extends Form {
	public function __construct($name = null) {
		parent::__construct ( 'archives' );
		
		$this->setAttribute ( 'method', 'post' );
		$this->add ( array (
				'name' => 'id',
				'attributes' => array (
						'type' => 'hidden' 
				) 
		) );
		
		$this->add ( array (
				'name' => 'title',
				'attributes' => array (
						'type' => 'text' 
				),
				'options' => array (
						'label' => 'Title' 
				) 
		) );
		
		$this->add ( array (
				'name' => 'content',
				'attributes' => array (
						'type' => 'textarea',
						'id'=>'content',
						'class'=>'xheditor',
						'rows'=>'12',
						'cols'=>'80',
						'style'=>"width: 80%"
				),
				'options'=>array('label'=>'content')
		) );
		
		$this->add ( array (
				'name' => 'submit',
				'attributes' => array (
						'type' => 'submit',
						'value' => 'Go',
						'id' => 'submitButton' 
				) 
		) );
	}
}