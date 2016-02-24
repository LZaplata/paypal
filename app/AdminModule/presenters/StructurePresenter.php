<?php
	namespace AdminModule;

	use Nette\Utils\Finder;

	use Nette\Application\BadRequestException;

	use Nette\Utils\Html;
	use Nette\Application\UI\Form;
use Nette\Forms\Rendering\BootstrapFormRenderer;
use Nette\Application\UI\Multiplier;
		
	class StructurePresenter extends BasePresenter {		
		public $pages;
		public $pid;
		public $id;
		public $sid;
		public $urlID;
		public $page;
		public $pageBlockModules;
		public $modules;
		public $section;
		public $sections = array();
		public $dimensions;
		public $fields;

		public function startup() {
			parent::startup();
						
			if (!$this->acl->isAllowed($this->user->getIdentity()->role, 'structure')) {
				$this->error();
			}
		}
		
		public function getPages($pid) {
			return $this->model->getPages()->where("pid", $pid);
		}
		
		public function getPage($id) {
			$this->page = $this->model->getPages()->wherePrimary($id)->fetch();
			$this->pageBlockModules = $this->model->getPagesModules()->where("pages_id", $id);
		}
		
		/**
		 * handler pro zneviditelnění/zviditelnění položky
		 * @param int $id
		 * @param int $vis
		 */
		public function handleVisibility($id, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$this->model->getPages()->where("id", $id)->update(array("visibility" => $vis));
			
			$this->flashMessage('Nastavení zobrazení stránky změněno!');
			if ($this->presenter->isAjax()) {
				$this->invalidateControl('pagesTable');
			}
		}
		
		public function handleHighlight($id, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			
			$this->model->getPages()->wherePrimary($id)->update(array("highlight" => $vis));
			$this->model->getPages()->where('id != ?', $id)->update(array("highlight" => 0));
		
			$this->flashMessage('Nastavení úvodní stránky změněno!');
			if ($this->presenter->isAjax()) {
				$this->invalidateControl('pagesTable');
			}
		}
		
		/**
		 * handler pro smazání položky
		 * @param int $id
		 */
		public function handleDelete($id) {
			$ids = (array)$id;
			
			foreach ($ids as $id) {
				$page = $this->model->getPages()->wherePrimary($id)->fetch();
				$pagesModules = $this->model->getPagesModules()->where("pages_id", $id)->fetchPairs('id', 'id');
				
				foreach ($this->model->getEditors()->where('pages_modules_id', array_values($pagesModules))->where('pid', 0) as $editor) {
					$imgs = $editor->galleries->related('galleries_images')->fetchPairs('id', 'id');
					$files = $editor->filestores->related('filestores_files')->fetchPairs('id', 'id');
				
					$this['gallery']->handleDelete($editor->galleries_id, $imgs);
					$this['files']->handleDelete($editor->filestores_id, $files);
					 
					$this->model->getGalleries()->wherePrimary($editor->galleries_id)->delete();
					$this->model->getFilestores()->wherePrimary($editor->filestores_id)->delete();
					$this->model->getEditors()->where('pid', $editor->pages_modules_id)->delete();
					$editor->delete();
				}
				
				$this->model->getPages()->where('pid', $id)->update(array('pid' => $page->pid, 'position' => 1000));
				$page->delete();
			}
			
			$this->model->getPagesModules()->where("pages_id", $ids)->delete();
				
			$this->flashMessage('Položka/y byla smazána!');
		}		
		
		public function handleDeleteSection ($id) {
			$ids = (array)$id;
			
			foreach ($ids as $id) {
				$this->sid = $id;
				$fields = $this->model->getSectionsFields()->where('sections_id', $id)->fetchPairs('id', 'id');
				
				foreach ($this->model->getPagesModules()->where('sections_id', $id) as $module) {
					$this->model->getModulesCategories()->where('pages_modules_id', $module->id)->delete();
				}
				
				$this['fields']->handleDelete($fields);
			}
			
			$this->model->getSections()->where('id', $ids)->delete();
			$this->model->getSectionsThumbs()->where('sections_id', $ids)->delete();
			$this->model->getPagesModules()->where('sections_id', $ids)->update(array('sections_id' => 0));
			
			$this->flashMessage('Sekce byla/y smazána!');
		}
		
		public function actionDefault() {
			$params = $this->request->getParameters();
			if(!isset($params["grid-order"])){
				unset($params["action"]);
				$params["grid-order"] = "position ASC";			

				$this->redirect("Structure:",$params);
			}
			
			$this->pages = $this->getPages(0);
			$this->urlID = 0;
		}
		
		public function renderDefault() {
			$this->template->pages = $this->pages;
		}
		
		public function actionAdd ($id) {
			$this->id = $id;
			$this->pid = empty($id) ? 0 : $id;
			$this->urlID = empty($id) ? 0 : $id;
		}
		
		public function actionEdit ($id) {
			$this->setView('add');
			$this->id = $id;
			$this->urlID = $id;
			$this->getPage($id);
		}
		
		public function actionModulesSections () {
			$this->modules = $this->model->getModules();
			$this->urlID = 0;
			
			if (!$this['editorsGrid']->getParameter('order')) {
				$this->redirect('this', array('editorsGrid-order' => 'name ASC', 'articlesGrid-order' => 'name ASC'));
			}
		}
		
		public function actionEditModuleSection ($id) {
			$this->id = $id;
			$this->urlID = $id;
			$this->sid = $id;
			$this->getSection($id);		
			$this->getSections();		
			$this->dimensions = $this->model->getSectionsThumbs()->where('sections_id', $id);
			$this->fields = $this->model->getSectionsFields()->where('sections_id', $id);
			
			if (!$this['thumbs']->getParameter('order')) {
				$this->redirect('this', array('thumbs-order' => 'dimension ASC', 'fields-order' => 'position ASC', 'tags-order' => 'name ASC'));
			}
		}
		
		public function renderEditModuleSection () {
			$this->template->dimensions = $this->dimensions;
			$this->template->fields = $this->fields;
			$this->setView('addModuleSection');	
		}
		
		public function actionAddModuleSection ($id) {
			$this->id = $id;
			$this->pid = $id;
			$this->urlID = $id;
			
			$this->actionModulesSections();
		}
		
		public function getReferer() {
			if (!empty($this->context->httpRequest->referer)) {
				return $this->context->httpRequest->referer->absoluteUrl;
			}
			else return $this->link('Structure:', array($this->id));
		}
		
		/**
		 * vytvoří komponentu formuláře pro přidání stránky
		 */
		public function createComponentAddForm () {			
			$form = new Form();
			
			$form->getElementPrototype()->class('form-horizontal');
			
			if ($this->page && (empty($_GET) || (count($_GET) == 1 && isset($_GET['lang'])))) {
				$form->getElementPrototype()->loadBlocks('true');
			}
			
			$form->addGroup('Základní informace');
			$infos = $form->addContainer('infos');
			
			$infos->addText('name', 'Jméno:')
				->setRequired('Vyplňte prosím název stránky!')
				->setAttribute('class', 'input-name')
				->setAttribute('onkeyup', 'createUrl()');
			
			$infos->addText('url', 'URL:')
				->setRequired('Vyplňte prosím url stránky!')
				->setAttribute('class', 'input-url');
			
			$infos->addText('title', 'Titulek:')
				->setRequired('Vyplňte prosím titulek stránky!');
			
			$infos->addText('keywords', 'Klíčová slova:')
				->setRequired('Vyplňte prosím klíčová slova!');
			
			$infos->addText('description', 'Meta popisek:')
				->setRequired('Vyplňte prosím popis!');
			
			$infos->addSelect('pid', 'Nadřazená stránka:', $this->getPagesSelect());
			
			$infos->addRadioList('layout', 'Layout:', $this->createOptions($this->layouts))
				->setAttribute('onclick', 'loadBlocks()')
				->setRequired('Vyberte prosím layout!')
				->getSeparatorPrototype()->setName(null);
			
			$form->addHidden('referer', $this->getReferer());
			
			if ($this->page) {				
				$infos->setValues($this->page);
			}
			else {
				$infos->setValues($infos->getValues());
				$infos->setValues(array('pid' => $this->id ? $this->id : 0));
			}
			
			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('add', $this->page ? 'Upravit' : 'Vytvořit');
		
			$form->onSuccess[] = callback($this, $this->page ? "editPage" : "addPage");
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		/**
		 * vytvoří komponentu formuláře pro úpravu stránky v cizím jazyce
		 */
		public function createComponentAddFormLang () {
			return new Multiplier(function ($key) {
				$key = $key == 'cs' ? '' : '_'.$key;
				
				$form = new Form();
					
				$form->getElementPrototype()->class('form-horizontal');
					
				$form->addGroup('Základní informace');
				$infos = $form->addContainer('infos');
					
				$infos->addText('name'.$key, 'Jméno:')
					->setRequired('Vyplňte prosím název stránky!')
					->setAttribute('class', 'input-name'.$key)
					->setAttribute('onkeyup', 'createUrl()');
					
				$infos->addText('url'.$key, 'URL:')
					->setRequired('Vyplňte prosím url stránky!')
					->setAttribute('class', 'input-url');
					
				$infos->addText('title'.$key, 'Titulek:')
					->setRequired('Vyplňte prosím titulek stránky!');
					
				$infos->addText('keywords'.$key, 'Klíčová slova:')
					->setRequired('Vyplňte prosím klíčová slova!');
					
				$infos->addText('description'.$key, 'Meta popisek:')
					->setRequired('Vyplňte prosím popis!');
					
				$form->addHidden('referer', $this->getReferer());
					
				if ($this->page) {
					$infos->setValues($this->page);
				}
				else {
					$infos->setValues($infos->getValues());
					$infos->setValues(array('pid' => $this->id ? $this->id : 0));
				}
					
				$form->addGroup()
					->setOption('container', 'fieldset class="submit"');
				$form->addSubmit('add', 'Upravit');
			
				$form->onSuccess[] = callback($this, 'editPage');
					
				$form->setRenderer(new BootstrapFormRenderer());
					
				return $form;
			});
		}
		
		public function addPage ($form) {
			$values = $form->httpData;
			$referer = $values['referer'];
			
			$lastPosition = $this->model->getPages()->where('pid', $this->pid)->order('position DESC')->fetch();
			$values['infos']['position'] = !$lastPosition ? 0 : $lastPosition->position+1;
			$values["infos"]["url"] = $this->getUrl($values["infos"]["url"]);
			
			$lastID = $this->model->getPages()->insert($values['infos']);
			
			for ($i = 1; $i <= $this->layoutsBlocks[$values['infos']['layout']]; $i++) {
				$pagesModules['pages_id'] = $lastID;
				$pagesModules['modules_id'] = $values['block'.$i]['module'];
				$pagesModules['sections_id'] = isset($values['block'.$i]['section']) ? $values['block'.$i]['section'] : 0;
				$pagesModules['layout'] = isset($values['block'.$i]['layout']) ? $values['block'.$i]['layout'] : '';
				$pagesModules['detail'] = isset($values['block'.$i]['detail']) ? $values['block'.$i]['detail'] : '';
				$pagesModules['position'] = $i;
				$pagesModules['lmt'] = isset($values['block'.$i]['lmt']) ? $values['block'.$i]['lmt'] : '';
				$pagesModules['order'] = isset($values['block'.$i]['order']) ? $values['block'.$i]['order'] : '';
				$pagesModules['direction'] = isset($values['block'.$i]['direction']) ? $values['block'.$i]['direction'] : '';
				$pagesModules['paginator'] = isset($values['block'.$i]['paginator']) ? 1 : 0;
				$pagesModules['highlight'] = isset($values['block'.$i]['highlight']) ? 1 : 0;
				$pagesModules['contact_form'] = isset($values['block'.$i]['contact_form']) ? true : false;
				$pagesModules['heading_level'] = isset($values['block'.$i]['heading_level']) ? $values['block'.$i]['heading_level'] : false;
				$pagesModules['menu'] = isset($values['block'.$i]['menu']) ? $values['block'.$i]['menu'] : false;
				
				$lastId = $this->model->getPagesModules()->insert($pagesModules);
				
				if (isset($values['block'.$i]['categories_id'])) {
					foreach ($values['block'.$i]['categories_id'] as $category) {
						$data['pages_modules_id'] = $lastId->id;
						$data['categories_id'] = $category;
						$this->model->getModulesCategories()->insert($data);
					}
				}
			}
			
			$this->flashMessage('Stránka byla úspěšně přidána!');
			$this->redirectUrl($referer);
		}
		
		public function editPage ($form) {
			$values = $form->httpData;
			$referer = $values['referer'];

			if ($this->page && ($this->page->url != $values["infos"]["url"])) {
				$values["infos"]["url"] = $this->getUrl($values["infos"]["url"]);
			}
			
			unset($values['add']);
// 			$this->model->getPagesModules()->where('pages_id', $this->id)->delete();
			
			if (isset($values['infos']['layout'])) {
				for ($i = 1; $i <= $this->layoutsBlocks[$values['infos']['layout']]; $i++) {				
					$pagesModules['pages_id'] = $this->id;
					$pagesModules['modules_id'] = $values['block'.$i]['module'];
					$pagesModules['sections_id'] = isset($values['block'.$i]['section']) ? $values['block'.$i]['section'] : 0;
					$pagesModules['layout'] = $values['block'.$i]['layout'];
					$pagesModules['detail'] = isset($values['block'.$i]['detail']) ? $values['block'.$i]['detail'] : '';
					$pagesModules['position'] = $i;
					$pagesModules['lmt'] = isset($values['block'.$i]['lmt']) ? $values['block'.$i]['lmt'] : '';
					$pagesModules['cols'] = isset($values['block'.$i]['cols']) ? $values['block'.$i]['cols'] : 1;
					$pagesModules['order'] = isset($values['block'.$i]['order']) ? $values['block'.$i]['order'] : '';
					$pagesModules['direction'] = isset($values['block'.$i]['direction']) ? $values['block'.$i]['direction'] : '';
					$pagesModules['highlight'] = isset($values['block'.$i]['highlight']) ? true : false;
					$pagesModules['paginator'] = isset($values['block'.$i]['paginator']) ? true : false;
					$pagesModules['contact_form'] = isset($values['block'.$i]['contact_form']) ? true : false;
					$pagesModules['heading_level'] = isset($values['block'.$i]['heading_level']) ? $values['block'.$i]['heading_level'] : false;
					$pagesModules['menu'] = isset($values['block'.$i]['menu']) ? $values['block'.$i]['menu'] : false;
					
					foreach ($this->model->getPagesModules()->where('position > ?', $this->layoutsBlocks[$values['infos']['layout']])->where('pages_id', $this->id) as $val) {
						foreach ($this->model->getEditors()->where('pages_modules_id', $val->id)->where('pid', 0) as $editor) {
							$imgs = $editor->galleries->related('galleries_images')->fetchPairs('id', 'id');
							$files = $editor->filestores->related('filestores_files')->fetchPairs('id', 'id');
										   
							$this['gallery']->handleDelete($editor->galleries_id, $imgs);
							$this['files']->handleDelete($editor->filestores_id, $files);
				   			
							$this->model->getGalleries()->wherePrimary($editor->galleries_id)->delete();
							$this->model->getFilestores()->wherePrimary($editor->filestores_id)->delete();
	
							$this->model->getEditors()->where('pid', $editor->pages_modules_id)->delete();
							$editor->delete();
						}
						
						$val->delete();
					}
					
					if ($module = $this->model->getPagesModules()->where(array('pages_id' => $this->id, 'position' => $i))->fetch()) {
						$module->update($pagesModules);
						$lastID = $module->id;
					}
					else {
						$lastID = $this->model->getPagesModules()->insert($pagesModules);
					}
						
					$this->model->getModulesCategories()->where('pages_modules_id', $lastID)->delete();
					if (isset($values['block'.$i]['categories_id'])) {					
						foreach ($values['block'.$i]['categories_id'] as $category) {
							$data['pages_modules_id'] = $lastID;
							$data['categories_id'] = $category;
							
							$this->model->getModulesCategories()->insert($data);
						}
					}
				}
			}
			
			$this->model->getPages()->where("id", $this->id)->update($values['infos']);
			$this->flashMessage('Stránka byla úspěšně upravena!');
			$this->redirectUrl($referer);
		}
		
		/**
		 * signál pro načtení počtu bloků
		 * @param int $layout
		 */
		public function handleLoadBlocks() {				
			if ($this->page && !isset($_GET['block1'])) {
				$values['infos'] = $this->page;
				foreach ($this->pageBlockModules as $modules) {
					$categories = $this->model->getModulesCategories()->where('pages_modules_id', $modules->id)->fetchPairs('id', 'categories_id');
					$values['block'.$modules->position]['module'] = $modules->modules_id;
					$values['block'.$modules->position]['section'] = $modules->sections_id;
					$values['block'.$modules->position]['layout'] = $modules->layout;
					$values['block'.$modules->position]['lmt'] = $modules->lmt;
					$values['block'.$modules->position]['cols'] = $modules->cols;
					$values['block'.$modules->position]['order'] = $modules->order;
					$values['block'.$modules->position]['direction'] = $modules->direction;
					$values['block'.$modules->position]['categories_id'] = (count($categories)?$categories:array(0));
					$values['block'.$modules->position]['detail'] = $modules->detail;
					$values['block'.$modules->position]['highlight'] = $modules->highlight;
					$values['block'.$modules->position]['paginator'] = $modules->paginator;
					$values['block'.$modules->position]['contact_form'] = $modules->contact_form;
					$values['block'.$modules->position]['heading_level'] = $modules->heading_level;
					$values['block'.$modules->position]['menu'] = $modules->menu;
				}
			}
			else {
				$values = $_GET;
			}
			
			$form = $this->getComponent('addForm');
			
			if (!empty($values['infos']['layout'])) {
				$layout = $values['infos']['layout'];
				
				for ($i=1; $i<$this->layoutsBlocks[$layout]+1; $i++) {
					$module = isset($values['block'.$i]['module']) ? $values['block'.$i]['module'] : 1;
					$sections = array_flip($this->getModuleSections($module));
					
					if (!isset($values['block'.$i]['section']) || !in_array($values['block'.$i]['section'], $sections)) {
						$values['block'.$i]['section'] = $section = reset($sections);
						
						if (empty($values['block'.$i]['section'])) {
							unset($values['block'.$i]['section']);
						}
					}
					else {
						$section = $values['block'.$i]['section'];
					}
					
					$form->addGroup('Nastavení bloku '.$i)
						->setOption('container', 'fieldset class="layout'.$layout.'_'.$i.'"');
					$blocks = $form->addContainer('block'.$i);
					
					$blocks->addSelect('module', 'Modul:', $this->settings->singlepage ? array(1 => 'textové pole') : $this->model->getModules()->fetchPairs("id", "name"))
						->setAttribute('onchange', 'loadBlocks()');
					
					if ($module < 3) {
						$blocks->addSelect('section', 'Sekce:', $this->getModuleSections($module))
							->setAttribute('onchange', 'loadBlocks()')
							->setRequired('Vyberte prosím sekci!');
					}	
							
					if ($module < 4) {
						$this->getModuleOptions($module, $blocks, $section);
					}
					
					$blocks->addRadioList('layout', 'Layout:', $this->getModuleLayouts($module))
						->setRequired('Vyberte prosím layout modulu!')
						->getSeparatorPrototype()->setName(null);
					
					if ($module > 1 && $module != 4) {
						$blocks->addRadioLIst('detail', 'Layout detailu:', $this->getModuleDetailLayouts($module))
							->setRequired('Vyberte prosím layout modulu!')
							->getSeparatorPrototype()->setName(null);
					}
					
					if ($module > 3) {
						unset($values['block'.$i]['layout']);
					}
				}
			}
					
			$this->invalidateControl('addForm');
								
			unset($values['do']);
			$values['referer'] = isset($_GET['referer']) ? $_GET['referer'] : $this->link('Structure:');
			
			$form->setValues($values);
		}
		
		public function getModuleSections($id) {
			return $this->model->getSections()->where("modules_id", $id)->where('slider', 0)->fetchPairs('id', 'name');
		}
		
		public function createOptions ($data) {
			$options = array();
			
			foreach ($data as $key => $val) {
				$options[$key] = Html::el('div')->class('layouts')->style('background-image: url("'.$this->context->httpRequest->url->basePath.'adminModule/images/layouts/layout'.$key.'.png")');
			}
			
			return $options;
		}
		
		public function getModuleLayouts ($module) {
			$radios = array();
			
			for ($i = 1; $i <= $this->countModulesLayouts[$module]; $i++) {
				$radios[$i] = Html::el('div')->class('modulesLayouts')->style('background-image: url("'.$this->context->httpRequest->url->basePath.'adminModule/images/modulesLayouts/module'.$module.'/layout'.$i.'.png")');
			}
			
			return $radios;
		}
		
		public function getModuleDetailLayouts ($module) {
			$radios = array();
				
			for ($i = 1; $i <= $this->countModulesDetailLayouts[$module]; $i++) {
				$radios[$i] = Html::el('div')->class('modulesLayouts')->style('background-image: url("'.$this->context->httpRequest->url->basePath.'adminModule/images/modulesLayouts/module'.$module.'/detail'.$i.'.png")');
			}
				
			return $radios;
		}
		
		public function createComponentAddSectionForm () {
			$form = new Form();
			
			$form->getElementPrototype()->class('form-horizontal');
			
			$form->addGroup(($this->id ? 'Upravit' : 'Vytvořit').' sekci');
			$form->addText('name', 'Název:')
				->setRequired('Vyplňte prosím název sekce!');
			
			$this->sectionSettings($form);
			
			$form->addGroup('')
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('add', $this->pid ? 'Vytvořit' : 'Upravit');
			
			$form->onSuccess[] = callback ($this, $this->pid ? 'addSection' : 'editSection');
			
			if ($this->section) { 
				$values = $this->section;
				
				$form->setValues($values);
			}
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		public function addSection ($form) {
			$values = $form->getValues();
			$values['modules_id'] = $this->pid;
				
			$lastID = $this->model->getSections()->insert($values);
				
			$this->flashMessage('Sekce byla vytvořena');
			$this->redirect('Structure:modulesSections');
		}
		
		public function editSection ($form) {
			$values = $form->getValues();
			
			$this->model->getSections()->wherePrimary($this->id)->update($values);

			$this->flashMessage('Sekce byla upravena');
			$this->redirect('Structure:modulesSections');
		}
		
		public function getSection ($id) {
			$this->section = $this->model->getSections()->wherePrimary($id)->fetch();
			
			$this->dimensions = $this->model->getSectionsThumbs()->where('sections_id', $id);
			
			$this->fields = $this->model->getSectionsFields()->where('sections_id', $id);
			
// 			dump($this->section);
		}
		
		public function getSections () {
			$modules = array(1 => 'Textová pole', 2 => 'Články');
				
			foreach ($this->model->getSections() as $section) {
				$this->sections[$modules[$section->modules_id]][$section->id] = $section->name;
			}
		}
		
		public function sectionSettings ($form) {
			$moduleID = $this->section ? $this->getSectionModule($this->id) : $this->pid;
			
			switch ($moduleID) {
				case 1:
					$this->editorSettings ($form);
					break;
				case 2:
					$this->articleSettings ($form);
					break;
			}
		}
		
		public function getSectionModule ($id) {
			$section = $this->model->getSections()->wherePrimary($id)->fetch();
			
			return $section->modules_id;
		}
		
		public function editorSettings ($form) {
			$form->addGroup('Nastavení');
// 			$form->addCheckbox('visName', 'Zobrazovat jméno?')
// 				->setValue(true);
			
			$form->addCheckbox('title', 'Zobrazovat titulek?')
				->setValue(true);
			
			$form->addCheckbox('keywords', 'Zobrazovat klíčová slova?')
				->setValue(true);
			
			$form->addCheckbox('text', 'Zobrazovat text?')
				->setValue(true);
			
			$form->addCheckbox('date', 'Zobrazovat datum?')
				->setValue(false);
						
			$form->addCheckbox('gallery', 'Přilepit gallerii?');
			
			$form->addCheckbox('files', 'Přilepit soubory?');
			
			$form->addCheckbox('tags', 'Přilepit tagy?');
			
			$form->addCheckbox('versions', 'Možnost verzování obsahu?');
			
			$form->addCheckbox('watermark', 'Přilepit vodoznak k obrázkům?');
			
			return $form;
		}
		
		public function articleSettings ($form) {			
			$form->addGroup('Nastavení');
			$form->addCheckbox('visName', 'Zobrazovat jméno?')
				->setValue(true);
			
			$form->addCheckbox('title', 'Zobrazovat titulek?')
				->setValue(true);
			
			$form->addCheckbox('keywords', 'Zobrazovat klíčová slova?')
				->setValue(true);
			
			$form->addCheckbox('meta_description', 'Zobrazovat popis?')
				->setValue(true);
			
			$form->addCheckbox('categories', 'Zobrazovat kategorie?')
				->setValue(true);
			
			$form->addCheckbox('date', 'Zobrazovat datum?')
				->setValue(true);
			
			$form->addCheckbox('author', 'Zobrazovat autora?');
			
			$form->addCheckbox('expirationDate', 'Zobrazovat platnost?');
			
			$form->addCheckbox('description', 'Zobrazovat krátký popisek?')
				->setValue(true);
			
			$form->addCheckbox('text', 'Zobrazovat text?')
				->setValue(true);
			
			$form->addCheckbox('files', 'Přilepit soubory?');
			
			$form->addCheckbox('gallery', 'Přilepit galerii?');
			
			$form->addCheckbox('tags', 'Přilepit tagy?');
			
			$form->addCheckbox('versions', 'Možnost verzování obsahu?');
			
			$form->addCheckbox('watermark', 'Přilepit vodoznak k obrázkům?');
			
			$form->addCheckbox('slider', 'Funkce slideru?');
			
			return $form;
		}
		
		public function createComponentThumbs () {
			return new ThumbsGrid($this->section->related('sections_thumbs'));
		}
		
		public function createComponentFields () {
			return new Fields($this->model->getSectionsFields()->where('sections_id', $this->sid));
		}
		
		public function createComponentTags () {
			return new Tags($this->model->getSectionsTags()->where('id_section', $this->sid));
		}
		
		public function getModuleOptions($moduleID, $blocks, $section) {
			switch ($moduleID) {
				case 1:
					return $this->getEditorOptions($blocks);
					break;
				case 2:
					return $this->getArticlesOptions($blocks, 2, $section);
					break;
				case 3:
					return $this->getEshopOptions($blocks, 3);
					break;
			}
		}
		
		public function getEditorOptions ($blocks) {
			$blocks->addCheckbox('contact_form', 'Přilepit kontaktní formulář?');
			$blocks->addSelect('heading_level', 'Úroveň nadpisu:', array(0 => '--Bez nadpisu--', 1 => 'H1', 2 => 'H2', 3 => 'H3', 4 => 'H4', 5 => 'H5'))
				->setDefaultValue(1);
			$blocks->addSelect('menu', 'Zobrazit podmenu stránky:', $this->getPagesSelect(0, false))
				->setDefaultValue(0);
		}
		
		public function getArticlesOptions ($blocks, $module, $section) {
			$dataSection = $this->model->getSections()->wherePrimary($section)->fetch();
			
			if($dataSection->categories){
				$blocks->addMultiSelect('categories_id', 'Kategorie:', $this->getCategories($blocks, $module, $section), 5)
					->setRequired('Vyberte alespoň jednu položku!')
					->setDefaultValue(0);
			}
			
			$blocks->addText('lmt', 'Limit článků:');
			$blocks->addSelect('cols', 'Článků na řádek:', array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6));
			$blocks->addSelect('order', 'Řadit podle:', array(1 => 'ID', 2 => 'Pořadí', 3 => 'Datum'));
			$blocks->addSelect('direction', 'Směr:', array(1 => 'Sestupně', 2 => 'Vzestupně'));
			$blocks->addCheckbox('highlight', 'Zobrazovat pouze zvýrazněné články?');
			$blocks->addCheckbox('paginator', 'Povolit stránkování?');
		} 
		
		public function getEshopOptions ($blocks, $module) {
			$blocks->addText('lmt', 'Limit:');
			$blocks->addSelect('cols', 'Produktů na řádek:', array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6));
			$blocks->addCheckbox('paginator', 'Povolit stránkování?');
		}
		
		public function getCategories ($blocks, $module, $section) {
			$selects[0] = '--Nerozhoduje--';
			
			$section = $this->model->getSections()->where(array('id' => $section, 'modules_id' => $module))->fetch();
			$firstModuleSection = $this->model->getSections()->where('modules_id', $module)->order('id ASC')->fetch();
			
			if ($section) {
				$categories = $this->model->getCategories()->where('sections_id', $section);
			}
			else {
				$categories = $this->model->getCategories()->where('sections_id', $firstModuleSection->id);
			}
			

			foreach ($categories as $category) {
				$selects[$category->id] = $category->name;
			}
			
			return $selects;
		}
		
		public function handleChangeOrder () {
			$positions = $_GET['positions'];
				
			foreach ($positions as $key => $value) {
				$values['position'] = $key;
				$this->model->getPages()->wherePrimary($value)->update($values);
			}
				
			$this->flashMessage('Pořadí bylo změněno');
		}
		
		public function createComponentLangs ($name) {
			return new Langs($this, $name);
		}
		
		public function getModules($id) {
			return $this->model->getPagesModules()->where('pages_id', $id)->order('id');
		}
		
		public function handleDeleteThumb ($id, $thumbID, $watermark = false) {
			$thumb = $this->model->getSectionsThumbs()->where('id', $thumbID)->fetch();
			$articlesGalleries = $this->model->getArticles()->where('sections_id', $id)->fetchPairs('id', 'galleries_id');
			$editorsGalleries = $this->model->getEditors()->where('sections_id', $id)->fetchPairs('id', 'galleries_id');
			$galleries = array_merge($articlesGalleries, $editorsGalleries);
			$dir = WWW_DIR . '/files/galleries/';
			
			foreach ($galleries as $gallery) {
				foreach (Finder::findFiles($thumb->dimension.'_g'.$gallery.'-*')->in($dir) as $file) {
					unlink($file->getPathName());
				}
			}
			
			if (!$watermark) {
				$thumb->delete();
				
				$this->flashMessage('Rozměr byl smazán');
			}
		}
		
		public function handleCreateThumbs ($thumb) {
			$articlesGalleries = $this->model->getArticles()->where('sections_id', $this->id)->fetchPairs('id', 'galleries_id');
			$editorsGalleries = $this->model->getEditors()->where('sections_id', $this->id)->fetchPairs('id', 'galleries_id');
			$galleries = array_merge($articlesGalleries, $editorsGalleries);
			
			foreach ($galleries as $gallery) {
				$this['gallery']->handleCreateThumbs($gallery, $thumb);
			}
		}
		
		public function handleDeleteField ($id, $fieldID) {
			$this->model->getSectionsFields()->wherePrimary($fieldID)->delete();
				
			$this->flashMessage('Speciální položka byla smazána');
			$this->invalidateControl('fields');
		}
		
		public function getPagesSelect ($pid = 0, $excludeSelf = true) {
			$pages = $this->model->getPages();
			
			if ($excludeSelf && $this->page) {
				$pages->where('id != ?', $this->id);
			}
			
			$pages = $pages->order('name ASC')->fetchPairs('id', 'name');
			$pages[0] = '--Žádná--';
			
			return $pages;
		}

		public function createComponentGrid () {
			return new StructureGrid($this->pages);
		}
		
		public function actionPosition ($id, $pid) {
			$this->id = $id;
			$this->urlID = $pid;
			
			$this->pages = $this->getPages($pid)->order('position ASC');
		}
		
		public function renderPosition () {
			$this->template->pages = $this->pages;
		}
		
		public function createComponentEditorsGrid () {
			return new Sections($this->model->getSections()->where('modules_id', 1), 1);
		}
		
		public function createComponentArticlesGrid () {
			return new Sections($this->model->getSections()->where('modules_id', 2), 2);
		}
		
		public function createComponentGallery ($name) {
			return new \GalleryPresenter($this, $name);
		}
		
		public function createComponentFiles ($name) {
			return new \FilesPresenter($this, $name);
		}

		/**
		 * get all pages with given url and modify url for new page
		 * @param string $url
		 * @return string
		 */
		public function getUrl($url)
		{
			$pages = $this->model->getPages()->where("url REGEXP ?", "^".$url."[-]?[0-9]*[^a-z]*$")->count("id");

			return $url.($pages > 0 ? "-".$pages : "");
		}
	}