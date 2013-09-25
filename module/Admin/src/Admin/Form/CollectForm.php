<?php
namespace Admin\Form;

use Zend\Form\Form;

class CollectForm extends Form
{

    function __construct ()
    {
        parent::__construct('collect');
        $this->setAttribute('method', 'post');
        $this->add(array(
            'name' => 'id',
            'attributes' => array(
                'type' => 'hidden'
            )
        ));
        $this->add(array(
            'name' => 'title',
            'attributes' => array(
                'type' => 'text'
            ),
            'options' => array (
						'label' => '标题' 
				)
        ));
        $this->add(array(
            'name' => 'url',
            'attributes' => array(
                'type' => 'text'
            ),
            'options'=>array('label'=>'网址')
        ));
        $this->add(array('name'=>'regulation_list','attributes'=>array('type'=>'text'),'options'=>array('label'=>'列表标签')));
        $this->add(array('name'=>'regulation_link','attributes'=>array('type'=>'text'),'options'=>array('label'=>'链接标签')));
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'GO',
                'id' => 'collectSubmit'
            )
        ));
    }
}