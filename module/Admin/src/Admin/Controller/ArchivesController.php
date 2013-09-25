<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Admin\Form\ArchivesForm;
use Admin\Form\ArchivesVerify;
class ArchivesController extends AbstractActionController {
	protected $db;
	public function indexAction() {
		$page=$this->params('page',1);
		$rows = $this->getDB ()->getArchivesList ($page);
		return array (
				'rows' => $rows
		);
	}
	public function createAction() {
		$form = new ArchivesForm ();
		$request = $this->getRequest ();
		if ($request->isPost ()) {
			$archive = new ArchivesVerify ();
			$form->setInputFilter ( $archive->getInputFilter () );
			$form->setData ( $request->getPost () );
			if ($form->isValid ()) {
				$archive->exchangeArray ( $form->getData () );
				$this->getDB ()->saveArchives ( $archive );
				return $this->redirect ()->toRoute ( 'Archives' );
			}
		}
		return array (
				'form' => $form 
		);
	}
	public function editAction() {
		$id = ( int ) $this->params ( 'id' );
		if (! $id) {
			$this->redirect ()->toRoute ( 'Archives', array (
					'action' => 'create' 
			) );
		}
		$row = $this->getDB ()->getArchivesID ( $id );
		$row ['content'] = htmlspecialchars_decode ( stripcslashes ( $row ['content'] ) );
		$form = new ArchivesForm ();
		$form->bind ( $row );
		$request = $this->getRequest ();
		if ($request->isPost ()) {
			$archives = new ArchivesVerify ();
			$form->setInputFilter ( $archives->getInputFilter () );
			$form->setData ( $request->getPost () );
			if ($form->isValid ()) {
				$archives->exchangeArray ( $form->getData () );
				$this->getDB ()->saveArchives ( $archives );
				return $this->redirect ()->toRoute ( 'Archives' );
			}
		}
		//$this->layout ( 'layout/admin' );
		return array (
				'id' => $id,
				'form' => $form 
		);
	}
	public function deleteAction() {
		$id = ( int ) $this->params ( 'id' );
		if (! $id) {
			$this->redirect ()->toRoute ( 'Archives' );
		}
		$request = $this->getRequest ();
		if ($request->isPost ()) {
			$del = $request->getPost ()->get ( 'del', 'No' );
			if ($del == 'Yes') {
				$this->getDB ()->delete ( $id );
			}
			$this->redirect ()->toRoute ( 'Archives' );
		}
		return array (
				'id' => $id,
				'row' => $this->getDB ()->getArchivesID ( $id ) 
		);
	}
	public function getDB() {
		if (! $this->db) {
			$sm = $this->getServiceLocator ();
			$this->db = $sm->get ( 'Admin\Model\Archives' );
		}
		return $this->db;
	}
}