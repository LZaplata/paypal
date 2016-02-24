<?php
	namespace AdminModule;

	use Nette\Application\UI\Form;

	use Nette\Application\UI,
		\DataGrid\DataSources\Dibi\DataSource;
	
	class ContentPresenter extends BasePresenter {
		public $id;
		public $sid;
		public $sections;
		public $page;
		public $pages;
		public $section;
		public $textPages;
		public $urlID;
		
		public function startup() {
			parent::startup();
		}
		
		public function actionDefault () {
			$this->urlID = 0;
		}
		
		public function renderDefault () {
			$this->template->pages = $this->getPages(0);
			$this->template->sections = $this->getSections();
		}
		
		public function getPages($pid) {
			$pages = $this->model->getPages();
			
			if (!$this->user->isInRole('superadmin') && !$this->user->isInRole('admin')) {
				$privileges = $this->model->getUsersPrivileges()->where('users_id', $this->user->id)->fetchPairs('id', 'sections_id');
				$modules = $this->model->getPagesModules()->where('sections_id', array_values($privileges));
				$m = $this->model->getPagesModules()->select('MIN(pages.pid) AS pid')->where('sections_id', array_values($privileges))->fetch();
				
				if ($pid == 0) {					
					$pid = $m->pid;
				}
				
				$pages->where('id', array_values($modules->fetchPairs('id', 'pages_id')));
			}
			
			return $pages->where("pid", $pid)->order('position ASC');
		}
		
		public function getSections () {
			return $this->model->getSections()->where('slider', 1);
		}
		
		/**
		 * Form pro editaci stránky
		 */
		public function createComponentEditForm () {
			$form = new Form();
			
			$form->addGroup('Základní informace');
			
			$form->addText('name', 'Jméno:')
				->setRequired('Vyplňte prosím název stránky!')
				->setAttribute('class', 'input-name')
				->setAttribute('onkeyup', 'createUrl()');
			
			$form->addText('url', 'URL:')
				->setRequired('Vyplňte prosím url stránky!')
				->setAttribute('class', 'input-url');
			
			$form->addText('title', 'Titulek:')
				->setRequired('Vyplňte prosím titulek stránky!');
			
			$form->addText('keywords', 'Klíčová slova:')
				->setRequired('Vyplňte prosím klíčová slova!');
			
			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('edit', 'upravit');
			
			$form->onSuccess[] = callback($this, 'editPage');
			
			$form->setValues($this->page);
			
			return $form;
		}
		
// 		public function getModuleName ($moduleID) {
// 			switch ($moduleID) {
// 				case 1:
// 					return 'Editor';
// 					break;
// 				case 2:
// 					return 'Articles';
// 					break;
// 			}
// 		}
	}