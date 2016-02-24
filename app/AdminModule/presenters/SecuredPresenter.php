<?php
	namespace AdminModule;

	/*
	 * Presenter pro zamezení přístupu nepřihlášeným už.
	 */
	use Nette\Security\Permission;

	abstract class SecuredPresenter extends \BasePresenter {
		public $acl;
		
		public function startup() {
			parent::startup();
	
			if (!$this->getUser()->isLoggedIn()) {
				$this->redirect(':Admin:Sign:in');
			}
			else {
				$this->createAcl();
			}
		}
		
		public function createAcl () {
			$acl = new Permission();
			
			$acl->addRole('editor');
			$acl->addRole('moderator', 'editor');
			$acl->addRole('admin');
			$acl->addRole('superadmin');
			
			$allow = false;
			foreach ($acl->roles as $role) {
				if ($this->user->isInRole($role)) {
					$allow = true;
				}
			}
			
			if (!$allow) {
				$this->user->logout();
				$this->redirect(':Admin:Sign:in');
			}
			
			$acl->addResource('structure');
			$acl->addResource('settings');
			$acl->addResource('mailing');
			$acl->addResource('accounts');
			$acl->addResource('post');
			$acl->addResource('ownPost');
			$acl->addResource('adminSettings');

			$acl->allow('admin', Permission::ALL);
			$acl->allow('superadmin', Permission::ALL);
			
			$acl->allow('moderator', 'ownPost', array('edit', 'add', 'delete'));
			$acl->allow('moderator', 'mailing');
			
			$acl->allow('editor', 'ownPost', array('add', 'edit'));
			
			foreach ($this->model->getSections() as $section) {
				$acl->addResource('section_'.$section->id);
				
				if (count($section->related('users_privileges')->where(array('users_id' => $this->user->getId(), 'sections_id' => $section->id)))) {
					$acl->allow('moderator', 'section_'.$section->id);
					$acl->allow('editor', 'section_'.$section->id);
				}
				else {
					$acl->deny('moderator', 'section_'.$section->id, array('edit'));
					$acl->deny('editor', 'section_'.$section->id);
				}
			}
			
			$acl->deny('moderator', 'post', array('add', 'edit', 'delete'));
			$acl->deny('editor', 'post', array('add', 'edit', 'delete'));
			$acl->deny('moderator', 'structure');
			$acl->deny('editor', 'structure');
			$acl->deny('moderator', 'accounts');
			$acl->deny('editor', 'accounts'); 
			$acl->deny('admin', 'adminSettings'); 
			$acl->deny('moderator', 'adminSettings'); 
			$acl->deny('editor', 'adminSettings'); 
			
			$this->acl = $acl;
		}
	}
?>