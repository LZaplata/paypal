<?php
	namespace AdminModule;

	use Nette\Latte\Engine;

	use Nette\Templating\FileTemplate;

	use Nette\Application\UI\Form;
use Nette\Forms\Rendering\BootstrapFormRenderer;
	
	class MailingPresenter extends BasePresenter {
		public $urlID;
		public $groups;
		public $group;
		public $emails;
		public $email;
		public $emailContent;
		public $id;
		public $sid;
		public $settings;
		public $section;
		public $users;
		
		public function startup() {
			parent::startup();
			
			$this->urlID = 0;
		}
		
		public function actionDefault () {
			$params = $this->request->getParameters();
			if(!isset($params["grid-order"])){
				unset($params["action"]);
				$params["grid-order"] = "date DESC";
				$this->redirect("Mailing:default",$params);
			}
			$this->emails = $this->model->getEmails();
		}
		
		public function actionEdit ($id) {
			$this->id = $id;
			
			if (!$this['mailContent']->getParameter('order') && !$_GET) {
				$this->redirect('this', array('mailContent-order' => 'position ASC'));
			}
			
			$this->email = $this->model->getEmails()->wherePrimary($id)->fetch();
			$this->emailContent = $this->model->getEmailsContent()->where('emails_id', $id)->order('position ASC');
		}
		
		public function actionGroups () {
			
			$params = $this->request->getParameters();
			if(!isset($params["groupGrid-order"]) && $params["action"] == 'groups'){
			//	unset($params["action"]);
				$params["groupGrid-order"] = "name ASC";
				$this->redirect("Mailing:groups",$params);
			}
			$this->groups = $this->model->getCategories()->where('sections_id', -1);
		}
		
		public function actionAddGroup () {
			$this->setView('editGroup');
			
			$this->actionGroups();
		}
		
		public function actionEditGroup ($id) {
			$this->users = $this->model->getUsersInserted($id);
			
			if (!$this['users']->getParameter('order')) {
				$params['users-order'] = 'email ASC';
				$params['users-filter'] = array('vis' => $id);
				
				$this->redirect('this', $params);
			}
			
			$this->group = $this->model->getCategories()->wherePrimary($id)->fetch();
			
			
			$this->actionGroups();
		}
		
		public function actionFiles ($id) {
			$this->id = $id;
			$this->sid = -1;
			$this->urlID = 0;
			
			$this->email = $this->model->getEmails()->where('filestores_id', $id)->fetch();
			
			if (!$this['files']['grid']->getParameter('order')) {
				$this->redirect('this', array('files-grid-order' => 'position ASC'));
			}
		}
		
		public function actionPreview ($id) {
			$this->urlID = 0;
			$this->id = $id;
			
			$this->email = $this->model->getEmails()->wherePrimary($id)->fetch();
			
			if (!$this['queue']->getParameter('order')) {
				$this->redirect('this', array('queue-order' => 'date DESC'));
			}
		}
		
		public function actionGraph ($id) {
			$this->urlID = 0;
			$this->id = $id;
			
			$this->email = $this->model->getEmails()->wherePrimary($id)->fetch();
		}
		
		public function actionUsers () {			
			$this->users = $this->model->getUsers()->where('newsletter', 1);
			
			if (!$this['mails']->getParameter('order')) {
				$this->redirect('this', array('mails-order' => 'email ASC'));
			}
		}
		
		public function actionPosition ($id) {
			$this->id = $id;
			$this->urlID = 0;
			$this->emailContent = $this->model->getEmailsContent()->where('emails_id', $id)->order('position ASC');
		}
		
		public function renderDefault () {
			$this->template->emails = $this->emails;
		}
		
		public function renderAdd () {
			$this->template->contents = array();
		}
		
		public function renderGroups () {
			$this->template->groups = $this->groups;
		}
		
		public function renderEdit() {
			$this->template->contents = $this->emailContent;
			
			$this->setView('add');
		}
		
		public function renderPreview () {
			$template = $this->createTemplate(); 
			$template->setFile(APP_DIR.'/FrontModule/templates/Mailing/layout.latte');
// 			$template->registerFilter(new Engine());
// 			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
			$template->host = $this->context->parameters['host'];
			$template->email = $this->email;
			$template->contents = $this->email->related('emails_content')->order('position ASC');
			$template->editors = $this->model->getEditors();
			$template->model = $this->model;
			
			$this->template->html = $template;
		}
		
		public function renderGraph () {
			$this->template->email = $this->email;
		}
		
		public function renderPosition () {
			$this->template->emailContent = $this->emailContent;
		}
		
		public function createComponentGroupForm () {
			$form = new Form();
			
			$form->addGroup('skupina');
			$form->addMultiSelect('users', 'Uživatelé:', $this->model->getUsers()->where('newsletter', 1)->fetchPairs('id', 'email'));
			
			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('add', $this->group ? 'Přidat' : 'Vytvořit');
			
			$form->addHidden('referer', $this->getReferer());
			
			$form->onSuccess[] = callback($this, $this->group ? 'editGroup' : 'addGroup');
			
			if ($this->group) {						
				$form->setValues($this->group);
			}
			
			return $form;
		}
		
		public function editGroup ($form) {
			$values = $form->values;
			
			foreach ($values['users'] as $user) {
				$data['categories_id'] = $this->group->id;
				$data['users_id'] = $user;
				
				if (!$this->model->getUsersCategories()->where('categories_id', $this->group->id)->where('users_id', $user)->fetch()) {
					$this->model->getUsersCategories()->insert($data);
				}
			}
			
			$this->flashMessage('Skupina uživatelů byla upravena');
			$this->redirectUrl($values['referer']);
		}
		
		public function createComponentAddMail () {
			$form = new Form();
			
			$form->getElementPrototype()->addClass('form-horizontal');
			
			$form->addGroup('E-mail');
			$mail = $form->addContainer('email');
			$mail->addText('name', 'Jméno:')
				->setRequired('Vyplňte prosím název e-mailu!');
			
			$mail->addText('subject', 'Předmět:')
				->setRequired('Vyplňte prosím předmět e-mailu!');
			
			$mail->addTextarea('text', 'Text:')
				->getControlPrototype()->class('tinymce');
			
			if ($this->email) {
				$form->addGroup('Připojení obsahu');
				$content = $form->addContainer('content');
			
				$content->addSelect('modules_id', 'Modul:', $this->model->getModules()->fetchPairs('id', 'name'))
					->setPrompt('--Vyberte možnost--');
				
// 				$form->addGroup('připojení přílohy');
// 				$attachment = $form->addContainer('attachment');
				
// 				$attachment->addMultiSelect('attachments', 'Přílohy:', $this->model->getFilestoresFiles()->where('filestores_id', $this->email->filestores_id)->fetchPairs('id', 'name'));
			}
			
			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('add', $this->email ? 'Upravit' : 'Vytvořit');
				
			$form->onSuccess[] = callback($this, $this->email ? 'editEmail' : 'addEmail');
			
			if ($this->email) {
				$values['email'] = $this->email;
// 				$values['users']['groups'] = $this->email->related('emails_categories')->fetchPairs('categories_id', 'categories_id');
// 				$values['attachment']['attachments'] = $this->email->related('emails_attachments')->fetchPairs('filestores_files_id', 'filestores_files_id');
				
				$form->setValues($values);
			}
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		public function addEmail ($form) {
			$values = $form->values;
			$values['email']['galleries_id'] = $this->model->getGalleries()->insert(array());
			$values['email']['filestores_id'] = $this->model->getFilestores()->insert(array());
			$values['email']['date'] = date('Y-m-d H:i:s');
			
			$lastID = $this->model->getEmails()->insert($values['email']);
			
			$this->flashMessage('Email byl vytvořen');
			$this->redirect('Mailing:');
		}
		
		public function editEmail ($form) {
 			$values = $form->values;
			
 			$email = $this->model->getEmails()->wherePrimary($this->email->id)->fetch();
 			$email->update($values['email']);
			
			$this->flashMessage('Email byl upraven');
			$this->redirect('Mailing:');
		}
		
		public function handleAddContent () {
			$values = $_GET;
			
			$content = array('articles_id', 'editors_id', 'products_id');
			
			$data = array();
			foreach ($content as $value) {
				if (isset($values['content'][$value])) {
					foreach ($values['content'][$value] as $id) {
						$lastPosition = $this->model->getEmailsContent()->where('emails_id', $this->email->id)->order('position DESC')->fetch();
							
						$data[$value] = $id;
						$data['emails_id'] = $this->email->id;
						$data['position'] = !$lastPosition ? 0 : $lastPosition->position+1;
							
						$this->model->getEmailsContent()->insert($data);
					}
				}
			}
			
			$this->flashMessage('Byl přidán obsah');
			$this->invalidateControl('addMail');
		}
		
		public function handleDeleteGroup ($id) {
			$ids = (array)$id;
			
			$this->model->getCategories()->where('id', $ids)->delete();
			$this->model->getUsersCategories()->where('categories_id', $ids)->delete();
			$this->model->getEmailsQueue()->where('categories_id', $ids)->delete();
			
			$this->flashMessage('Skupina uživatelů byla smazána');
		}
		
		public function handleDeleteMailQueue ($id, $queueID) {
			$ids = (array)$queueID;
			
// 			foreach ($ids as $row) {
// 				$queue = $this->model->getEmailsQueue()->wherePrimary($row)->fetch();
				
// 				$this->model->getEmailsQueue()->where('pid', $queue->pid)->where('date', $queue->date)->delete();
// 			}			

			$this->model->getEmailsQueue()->where('id', $ids)->delete();
			$this->model->getEmailsQueue()->where('pid', $ids)->delete();
			
			$this->flashMessage('Skupina byla smazána');
		}
		
		public function handleDeleteMailContent ($id, $cid) {
			$this->model->getEmailsContent()->wherePrimary($cid)->delete();
				
			$this->flashMessage('Položka byla smazána');
		}
		
		public function handleDelete ($id) {
			$ids = (array)$id;
			
			$this->model->getEmails()->where('id', $ids)->delete();
			$this->model->getEmailsQueue()->where('emails_id', $ids)->delete();
			
			$this->flashMessage('Email byl smazán');
		}
		
		public function createComponentGallery ($name) {
			return new \GalleryPresenter($this, $name);
		}
		
		public function createComponentFiles ($name) {
			return new \FilesPresenter($this, $name);
		}
		
		public function editSettings ($form) {
			$values = $form->values;
			
			$this->model->getSectionsThumbs()->where('sections_id', -1)->delete();
				
			for ($i=1; $i<=3; $i++) {
				if (!empty($values['dimensions'.$i])) {
					$data['dimension'] = $values['dimensions'.$i];
					$data['operation'] = $values['operation'.$i];
					$data['sections_id'] = -1;
						
					$this->model->getSectionsThumbs()->insert($data);
				}
			}
			
			$this->flashMessage('Nastavení bylo uloženo');
			$this->redirect('Mailing:');
		}
		
		public function getEmails () {
			$emails = $this->model->getEmailsQueue();
			
			if ($this->email) {
				$emails->where('emails_id', $this->email->id);
			}
			
			return $emails;
		}
		
		public function handleChangeMailContent () {
			$values = $_GET;
			
			$form = $this->getComponent('addMail');
			
			if ($values['content']['modules_id'] == 1) {
				$form['content']->addMultiSelect('editors_id', 'Textové pole:', $this->model->getEditors()->fetchPairs('id', 'name'))
					->setAttribute('class', 'chosen');
				
				$form['content']->addButton('addContent', 'Přidat obsah')
    					->setAttribute('onclick', 'addContent()');
			}
			
			if ($values['content']['modules_id'] == 2) {
				$form['content']->addMultiSelect('sections_id', 'Sekce:', $this->model->getSections()->where('modules_id', 2)->fetchPairs('id', 'name'))
					->setAttribute('onchange', 'changeMailContent()')
					->setAttribute('class', 'chosen');
			
				if (isset($values['content']['sections_id'])) {
					$form['content']->addMultiSelect('articles_id', 'Články:', $this->model->getArticles()->where('sections_id', $values['content']['sections_id'])->fetchPairs('id', 'name'))
						->setAttribute('class', 'chosen');
					
					$form['content']->addButton('addContent', 'Přidat obsah')
    					->setAttribute('onclick', 'addContent()');
				}
			}
			
			if ($values['content']['modules_id'] == 3) {
				$form['content']->addMultiSelect('categories_id', 'Kategorie:', $this->model->getCategories()->where('sections_id', 0)->fetchPairs('id', 'name'))
					->setAttribute('class', 'chosen')
					->setAttribute('onchange', 'changeMailContent()');
				
				if (isset($values['content']['categories_id'])) {
					$products = $this->model->getProductsCategories()->where('categories_id', $values['content']['categories_id'])->fetchPairs('products_id', 'products_id');
					$form['content']->addMultiSelect('products_id', 'Produkty:', $this->model->getProducts()->where('id', array_keys($products))->where('pid IS NULL')->where('trash', 0)->fetchPairs('id', 'name'))
						->setAttribute('class', 'chosen');
					
					$form->addGroup()
						->setOption('container', 'fieldset class="submit"');
					$form['content']->addButton('addContent', 'Přidat obsah')
    					->setAttribute('onclick', 'addContent()');
				}
			}
			
			$this->invalidateControl('addMail');
			$form->setValues($values);
		}
		
		public function handleDeleteContent ($id, $contentID) {
			$this->model->getEmailsContent()->wherePrimary($contentID)->delete();
			
			$this->flashMessage('Obsah byl smazán');
			$this->invalidateControl('content');
		}
		
		public function handleChangeOrder () {
			$positions = $_GET['positions'];
			unset($positions['do']);
				
			foreach ($positions as $key => $value) {
				$values['position'] = $key;
				$this->model->getEmailsContent()->wherePrimary($value)->update($values);
			}
				
			$this->flashMessage('Pořadí bylo změněno');
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
		
		public function handleLogout ($id) {
			$ids = (array)$id;
			
			$this->model->getUsers()->where('id', $ids)->update(array('newsletter' => 0));
			$this->model->getUsersCategories()->where('users_id', $ids)->delete();
			$this->model->getEmailsQueue()->where('users_id', $ids)->where('date > ?', date('Y-m-d H:i:s'))->delete();
		}
		
		public function createComponentThumbs ($name) {
			return new Thumbs($this, $name);
		}
		
		public function createComponentGrid () {
			return new MailingGrid($this->emails);
		}
		
		public function createComponentGroupGrid () {
			return new MailingGroupGrid($this->groups);
		}
		
		public function createComponentMailContent () {
			return new MailingContentGrid($this->model->getEmailsContent()->where('emails_id', $this->id));
		}
		
		public function getReferer() {
			if (!empty($this->context->httpRequest->referer)) {
				return $this->context->httpRequest->referer->absoluteUrl;
			}
			else return $this->link('Mailing:groups');
		}
		
		public function createComponentQueue () {
			return new MailingMailQueueGrid($this->model->getEmailsQueue()->select('categories.*, emails_queue.*')->where('emails_id', $this->id)->where('emails_queue.pid', 0));
		}
		
		public function createComponentUsers () {
			return new MailingGroupUsersGrid($this->users);
		}
		
		public function createComponentMails () {
			return new AccountsGrid($this->users);
		}
		
		public function createComponentAddEmails () {
			$form = new Form();
				
			$form->getElementPrototype()->addClass('form-horizontal');
			
			$form->addGroup('Nahrání mailových adres');
			$form->addTextarea('emails', 'Adresy:')
				->setRequired()
				->setAttribute('desctiption', 'Emailové adresy oddělujte čárkou!');
				
			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('upload', 'Nahrát');
				
			$form->onSuccess[] = callback($this, 'addEmails');
				
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		public function addEmails ($form) {
			$values = $form->values;
				
			preg_match_all('~[0-9a-zA-Z.-_]*@[a-z]*[.]{1}[a-z]{2,3}~', $values->emails, $emails);
				
			foreach ($emails[0] as $email) {
				$save = false;
				$data['categories_id'] = $this->group->id;
		
				if ($user = $this->model->getUsers()->where('email', $email)->fetch()) {
					$data['users_id'] = $user->id;
				}
				else {
					$lastID = $this->model->getUsers()->insert(array('email' => $email, 'newsletter' => 1));
						
					$data['users_id'] = $lastID;
						
				}
					
				if (!$this->model->getUsersCategories()->where($data)->fetch()) {
					$this->model->getUsersCategories()->insert($data);
				}
			}
				
			$this->redirect('this');
		}
	}