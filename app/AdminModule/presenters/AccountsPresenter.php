<?php
	namespace AdminModule;
	
	use Nette\Application\UI\Form;
use Nette\Forms\Rendering\BootstrapFormRenderer;

	class AccountsPresenter extends BasePresenter {
		public $accounts;
		public $account;
		public $id;
		public $privileges;
		public $urlID;
		public $section;
		public $groups;
		public $group;
		
		public function startup() {
			parent::startup();
			
			if (!$this->acl->isAllowed($this->user->getIdentity()->role, 'accounts')) {
				$this->error();
			}

			$this->urlID = 0;
		}
		
		public function actionAdmin () {
			$params = $this->request->getParameters();
			if(!isset($params["grid-order"])){
				unset($params["action"]);
				$params["grid-order"] = "email ASC";
				$this->redirect("Accounts:admin",$params);
			}
			$this->accounts = $this->model->getUsers()->where('role != ?', 'user');
		}
		
		public function actionFront () {
			$params = $this->request->getParameters();
			if(!isset($params["grid-order"])){
				unset($params["action"]);
				$params["grid-order"] = "email ASC";
				$this->redirect("Accounts:front",$params);
			}
			$this->accounts = $this->model->getUsers()->where('role = ?', 'user');
		}
		
		public function actionAdd () {
			$this->section = $this->model->getShopSettings()->fetch();
		}
		
		public function actionEdit ($id) {
			$this->id = $id;
			$this->account = $this->model->getUsers()->wherePrimary($id)->fetch();
			$this->section = $this->model->getShopSettings()->fetch();
			
			$this->setView('add');
		}
		
		public function actionPrivileges ($id) {
			$this->id = $id;
			
			$this->privileges = $this->model->getUsersPrivileges()->where('users_id', $id)->fetchPairs('sections_id', 'sections_id');
		}
		
		public function actionGroups () {			
			$this->groups = $this->model->getCategories()->where('sections_id', -3);
			
			if (!$this['groups']->getParameter('order')) {
				$params['groups-order'] = 'name ASC';
			
				$this->redirect('this', $params);
			}
		}
		
		public function actionEditGroup ($id) {
			$this->accounts = $this->model->getUsersInserted($id);
				
			if (!$this['users']->getParameter('order')) {
				$params['users-order'] = 'email ASC';
				$params['users-filter'] = array('vis' => $id);
			
				$this->redirect('this', $params);
			}
				
			$this->group = $this->model->getCategories()->wherePrimary($id)->fetch();
		}
		
		public function renderAdmin () {
			$this->setView('users');
			$this->template->accounts = $this->accounts;
			$this->template->front = false;
		}
		
		public function renderFront () {
			$this->setView('users');
			$this->template->accounts = $this->accounts;
			$this->template->front = true;
		}
		
		public function getReferer() {
			if (!empty($this->context->httpRequest->referer)) {
				return $this->context->httpRequest->referer->absoluteUrl;
			}
			else return $this->link('Accounts:admin', array($this->id));
		}
		
		public function createComponentAddForm () {
			$form = new Form();
			
			$form->getElementPrototype()->addClass('form-horizontal');
			
			$form->addGroup('Základní informace');
			$form->addText('name', 'Jméno:');
			
			$form->addText('surname', 'Přijmení:');
			
			$form->addText('email', 'E-mail:')
				->setRequired('Vyplňte prosím e-mail!')
				->addRule(Form::EMAIL, 'Chybný formát e-mailu');
			
			if ($this->isLinkCurrent('Accounts:add')) {
				$form->addSelect('role', 'Role:', array(
							'admin' => 'Admin',
							'moderator' => 'Moderátor',
							'editor' => 'Přispěvatelé'
						)
				);
			}
				
			$form->addGroup('Přihlašovací údaje');
			
			$form->addPassword('password', 'Heslo:');
			
			$form->addPassword('password2', 'Heslo znovu:');
			
			$form->addHidden('referer', $this->getReferer());
			
			if (!$this->account) {
				$form['password']->setRequired('Vyplňte prosím heslo!');
				$form['password2']->setRequired('Zopakujte prosíme heslo!')
					->addRule(Form::EQUAL, 'Hesla se neshodují!', $form['password']);
			}
			
			if ($this->section && $this->section->discounts) {
				$form->addGroup('Uživatelská cena');
				$form->addText('discount', 'Sleva (%)')
					->addRule(Form::INTEGER, 'Sleva musí být číslo');
			}
			
			if ($this->account && $this->account->role != 'user') {
				$form->addGroup('Posílání oznamovacích e-mailů');
				$form->addCheckbox('posts', 'Diskuze');
			}
			
			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('add', $this->account ? 'Upravit' : 'Vytvořit');
				
			$form->onSuccess[] = callback($this, $this->account ? 'editAccount' : 'addAccount');
			
			if ($this->account) {
				$form->setValues($this->account);
			}
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		public function editAccount ($form) {
			$values = $form->getValues();
			$referer = $values['referer'];
			if (!empty($values['password'])) {
				$values['password'] = hash('sha512', $values['password']);
			}
			
			unset($values['password2']);
			unset($values['referer']);
			
			if (($this->account->email != $values['email']) && $this->model->getUsers()->where('email', $values['email'])->fetch()) {
				$form->addError('E-mail se již v databázi nachází!');
			}
			else {
				$this->model->getUsers()->wherePrimary($this->id)->update($values);
				
				$this->flashMessage('Účet byl upraven');
				$this->redirectUrl($referer);
			}
		}
		
		public function addAccount ($form) {
			$values = $form->getValues();
			$referer = $values['referer'];
			$values['password'] = hash('sha512', $values['password']);
				
			unset($values['password2']);
			unset($values['referer']);

			if ($this->model->getUsers()->where('email', $values['email'])->fetch()) {
				$form->addError('E-mail se již v databázi nachází!');
			}
			else {
				$this->model->getUsers()->insert($values);
				
				$this->flashMessage('Účet byl vytvořen');
				$this->redirectUrl($referer);
			}
		}
		
		public function createComponentPrivilegesForm () {
			$form = new Form();
			
			$form->getElementPrototype()->addClass('form-horizontal');
			
			$form->addGroup('Práva');
			
			/*foreach ($this->model->getSections() as $section) {
				$form->addCheckbox($section->id, $section->name);
			}*/
			
			$form->addMultiSelect('sections_id', 'Sekce:', $this->model->getSections()->fetchPairs('id', 'name'))
				->getControlPrototype()->addClass('chosen');
			
			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('edit', 'Upravit');
			
			$form->onSuccess[] = callback($this, $this->privileges ? 'editPrivileges' : 'addPrivileges');
			
			if ($this->privileges) {
				$values['sections_id'] = $this->privileges;
				
				$form->setValues($values);
			}
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		public function addPrivileges ($form) {
			$values = $form->getValues();
			
			foreach ($values['sections_id'] as $key => $value) {							
				$data['sections_id'] = $value;
				$data['users_id'] = $this->id;
			
				$this->model->getUsersPrivileges()->insert($data);
			}
			
// 			$this->flashMessage('Práva byla změněna');
			$this->redirect('Accounts:admin');
		}
		
		public function editPrivileges ($form) {
			$values = $form->getValues();
			
			$this->model->getUsersPrivileges()->where('users_id', $this->id)->delete();
				
			foreach ($values['sections_id'] as $key => $value) {						
				$data['sections_id'] = $value;
				$data['users_id'] = $this->id;
			
				$this->model->getUsersPrivileges()->insert($data);
			}
				
// 			$this->flashMessage('Práva byla změněna');
			$this->redirect('Accounts:admin');
		}
		
		public function handleDelete ($id) {
			$ids = (array)$id;
			
			$this->model->getUsers()->where('id', $ids)->delete();
			$this->model->getUsersPrivileges()->where('users_id', $ids)->delete();
			$this->model->getUsersCategories()->where('users_id', $ids)->delete();
			$this->model->getEmailsQueue()->where('users_id', $ids)->delete();
			
			$this->flashMessage('Účet byl smazán');
		}

		public function createComponentGrid () {
			return new AccountsGrid($this->accounts);
		}
		
		public function createComponentGroups () {
			return new UsersGroupGrid($this->groups);
		}
		
		public function createComponentUsers () {
			return new MailingGroupUsersGrid($this->accounts);
		}
		
		public function handleSwitchUser ($id, $uid, $vis) {
			$uids = (array)$uid;
				
			foreach ($uids as $val) {
				$data['categories_id'] = $id;
				$data['users_id'] = $val;
		
				if (count($users = $this->model->getUsersCategories()->where($data))) {
					if ($vis == 0) {
						$users->delete();
					}
				}
				else $this->model->getUsersCategories()->insert($data);
			}
		}
	}