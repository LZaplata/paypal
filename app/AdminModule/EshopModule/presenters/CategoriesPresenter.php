<?php
	namespace AdminEshopModule;

	use AdminModule\BasePresenter;

	use AdminModule\CategoriesGrid;

	use Nette\Utils\Strings;

	use AdminModule\Langs;

	use Nette\Application\UI\Form;
use Nette\Forms\Rendering\BootstrapFormRenderer;
use Nette\Application\UI\Multiplier;
			
	class CategoriesPresenter extends BasePresenter {
		public $categories;
		public $id;
		public $urlID;
		public $category;
		public $sid;
		public $section;
		
		/** @var \Nette\Database\ResultSet */
		public $propertiesCategories;
		
		/** @var \Nette\Database\ResultSet */
		public $categoryPropertiesCategories;
		
		public function startup() {
			parent::startup();
			
			$this->sid = 0;
			$this->urlID = 0;
			$this->section = $this->model->getShopSettings()->fetch();
		}
		
		public function actionDefault () {			
			if (!$this['grid']->getParameter('order')) {
				$this->redirect('this', array('grid-order' => 'position ASC'));
			}
		}
		
		public function renderDefault () {
			$this->template->categories = $this->categories;
		}
		
		public function actionEdit ($id) {
			$this->id = $id;
			$this->urlID = $id;
			
			if ($this->id) {
				$this->category = $this->model->getCategories()->wherePrimary($id)->fetch();
				$this->propertiesCategories = $this->model->getCategories()->where('sections_id', -2);
				$this->categoryPropertiesCategories = $this->model->getCategoriesCategories()->where('id_category', $this->id);
			}
			
			$this->setLastEdited('categories');
		}
		
		public function actionAdd ($id) {
			$this->setView('edit');
			
			$this->id = $id;
			$this->urlID = empty($id) ? 0 : $id;
			$this->propertiesCategories = $this->model->getCategories()->where('sections_id', -2);
			
		}
		
		public function actionGallery ($id, $sid, $urlID) {
			$this->id = $id;
			$this->section = $this->model->getShopSettings()->fetch();
			$this->category = $this->model->getCategories()->where('galleries_id', $id)->fetch();
			
			if (!$this['gallery']['grid']->getParameter('order')) {
				$this->redirect('this', array('gallery-grid-order' => 'position ASC'));
			}
			
			if (!$this['gallery']->getDimensions()->where('place = ? OR place = ?', 0, 3)->fetch()) {
				$this->flashMessage('Nastavte nejříve alespoň jeden rozměr obrázků');
				$this->redirect('Settings:');
			}
			
			$this->setLastEdited('categories');
		}
		
		public function actionPosition ($id) {
			$this->categories = $this->getCategories($id);
		}
		
		public function renderPosition () {
			$this->template->categories = $this->categories;
		}
		
		public function getCategories($pid) {
			return $this->model->getCategories()->where(array("pid" => $pid, "sections_id" => 0))->order('position ASC');
		}
		
		/**
		 * call function and add prompt option
		 */
		public function getCategoriesSelect () {
			$this->categories[0] = '--Žádná--';
			$this->getCategoriesTree();
			
			return $this->categories;
		}
		
		/**
		 * create options for categories select in tree view
		 * @param int $pid
		 * @param string $level
		 */
		public function getCategoriesTree ($pid = 0, $level = '') {
			$categories = $this->model->getCategories()->where('sections_id', 0)->where('pid', $pid);
			
			foreach ($categories as $category) {
				$this->categories[$category->id] = $level.$category->name;
				
				$this->getCategoriesTree($category->id, $level.'- ');
			}
		}
		
		public function createComponentAddCategory () {
			return new Multiplier(function ($key) {
				$key = $key == 'cs' ? '' : '_'.$key;
				
				$form = new Form();
				
				$form->getElementPrototype()->addClass('form-horizontal');
				
				$form->addGroup('Základní informace');
				$infos = $form->addContainer('infos');
				$infos->addText('name'.$key, 'Jméno:')
					->setRequired('Vyplňte prosím jméno kategorie!')
					->setAttribute('class', 'input-name');
				
				$infos->addText('url'.$key, 'URL:')
					->setRequired('Vyplňte prosím url stránky!')
					->setAttribute('class', 'input-url');
					
				$infos->addText('title'.$key, 'Titulek:')
					->setRequired('Vyplňte prosím titulek kategorie!');
					
				$infos->addText('keywords'.$key, 'Klíčová slova:')
					->setRequired('Vyplňte prosím klíčová slova kategorie!');
				
				$infos->addText('description'.$key, 'Meta popisek:');
				
				$infos->addSelect('pid', 'Nadřazená kategorie:', $this->getCategoriesSelect());
				
				if ($this->section->heureka) {
					$infos->addSelect('categories_heureka_id', 'Kategorie Heureka.cz', $this->model->getCategoriesHeureka()->fetchPairs('id', 'name'))
						->setPrompt('--Vyberte kategorii--');
				}
				
				if ($this->section->zbozi) {
					$infos->addSelect('categories_zbozi_id', 'Kategorie Zboží.cz', $this->model->getCategoriesZbozi()->fetchPairs('id', 'name'))
						->setPrompt('--Vyberte kategorii--');
				}
				
				if ($this->section->merchants) {
					$infos->addSelect('categories_merchants_id', 'Kategorie Merchants', $this->model->getCategoriesMerchants()->fetchPairs('id', 'name'))
					->setPrompt('--Vyberte kategorii--');
				}
				
				$infos->addTextarea('text'.$key, 'Text:')
					->getControlPrototype()->class('tinymce');
				
				if ($this->section->discounts) {
					$form->addGroup('Nastavení ceny');
					$infos->addText('discount', 'Sleva')
						->addRule(Form::INTEGER, 'Sleva musí být číslo');
				}
				
				if (count($this->propertiesCategories)) {
					$propertiesCategories = $form->addContainer('propertiesCategories');
					$propertiesCategories->addMultiSelect('categories', 'Skupiny vlastností:', $this->propertiesCategories->fetchPairs('id', 'name'))
						->getControlPrototype()->class('chosen');
				}
				
				$form->addHidden('referer', $this->context->httpRequest->referer->absoluteUrl);
				
				$form->addGroup()
					->setOption('container', 'fieldset class="submit"');
				$form->addSubmit('add', $this->category ? 'Upravit' : 'Vytvořit');
					
				$form->onSuccess[] = callback($this, $this->category ? 'editCategory' : 'addCategory');
				
				if ($this->category) {
					$values['propertiesCategories']['categories'] = $this->categoryPropertiesCategories->fetchPairs('categories_id', 'categories_id');
					$values['infos'] = $this->category;
					
					$form->setValues($values);
				}
				else {
					$infos->setValues(array('pid' => $this->id ? $this->id : 0));
				}
				
				$form->setRenderer(new BootstrapFormRenderer());
				
				return $form;
			});
		}
		
		public function addCategory ($form) {
			$values = $form->getValues();
			
			$values['infos']['sections_id'] = 0;
			$values['infos']['pid'] = $this->id ? $this->id : 0;
			$values['infos']['url'] = Strings::webalize($values['infos']['name']);
			$values['infos']['galleries_id'] = $this->model->getGalleries()->insert(array());
			
			$lastPosition = $this->model->getCategories()->where('sections_id', 0)->where('pid', $values['infos']['pid'])->order('position DESC')->fetch();
			$values['infos']['position'] = !$lastPosition ? 0 : $lastPosition->position+1;
			
			$lastID = $this->model->getCategories()->insert($values['infos']);
			
			if (count($this->propertiesCategories)) {	
				foreach ($values['propertiesCategories']['categories'] as $category) {
					$data['id_category'] = $lastID;
					$data['categories_id'] = $category;
						
					$this->model->getCategoriesCategories()->insert($data);
				}
			}
			
			$this->flashMessage('Kategorie byla vytvořena');
			$this->redirectUrl($values['referer']);
		}
		
		public function editCategory ($form) {
			$values = $form->values;
			
			$this->category->update($values['infos']);
			
			if (isset($values['propertiesCategories']['categories'])) {
				$this->model->getCategories()->wherePrimary($this->id)->update($values['infos']);
				
				if (count($this->propertiesCategories)) {
					$this->model->getCategoriesCategories()->where('id_category', $this->id)->delete();
				
					foreach ($values['propertiesCategories']['categories'] as $category) {
						$data['id_category'] = $this->id;
						$data['categories_id'] = $category;
							
						$this->model->getCategoriesCategories()->insert($data);
					}
				}
			}
			
			$this->flashMessage('Kategorie byla upravena');
			$this->redirectUrl($values['referer']);
		}
		

		public function createComponentSeo ($name) {
			return new \AdminModule\Seo($this, $name);
		}
		
		public function createComponentLangs ($name) {
			return new Langs($this, $name);
		}
		
		/**
		 * handler pro zneviditelnění/zviditelnění položky
		 * @param int $sid
		 * @param int $id
		 * @param int $vis
		 */
		public function handleVisibility($sid, $id, $vis) {	
			$vis = $vis == 1 ? 0 : 1;
			$this->model->getCategories()->where("id", $id)->update(array("visibility" => $vis));
			
			$this->flashMessage('Nastavení zobrazení kategorie změněno!');
		}
		
		public function handleHighlight($sid, $id, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$this->model->getCategories()->where('id', $id)->update(array("highlight" => $vis));
		
			$this->flashMessage('Nastavení zvýraznění kategorie změněno!');
		}
		
		/**
		 * move given categories as parents of category
		 * @param int $id
		 */
		public function handleMoveCategories ($id) {
			$ids = (array)$id;
			$pid = $_POST['grid']['action']['pid'];
			
			$this->model->getCategories()->where('id', $ids)->update(array('pid' => $pid));
		}
		
		public function handleDelete($sid, $id) {
			$this->sid = $sid;
			$ids = (array)$id;
				
			foreach ($ids as $val) {
				$category = $this->model->getCategories()->wherePrimary($val)->fetch();
				$imgs = $this->model->getGalleriesImages()->where('galleries_id', $category->galleries_id)->fetchPairs('id', 'id');
				
				$this['gallery']->handleDelete($category->galleries_id, $imgs);
				 
				$this->model->getGalleries()->wherePrimary($category->galleries_id)->delete();
				$this->model->getCategories()->where('pid', $category->id)->update(array('pid' => $category->pid, 'position' => 1000));
			}
		
			$this->model->getCategories()->where('id', $ids)->delete();
			$this->model->getProductsCategories()->where('categories_id', $ids)->delete();
		}
		
		public function handleChangeOrder () {
			$positions = $_GET['positions'];
				
			foreach ($positions as $key => $value) {
				$values['position'] = $key;
				$this->model->getCategories()->wherePrimary($value)->update($values);
			}
				
			$this->flashMessage('Pořadí bylo změněno');
		}
		
		public function createComponentGrid () {
			return new CategoriesGrid($this->getCategories(0));
		}
		
		public function createComponentGallery ($name) {
			return new \GalleryPresenter($this, $name);
		}

		public function handleCopy ($id, $sid, $lang) {
			foreach ($this->category as $key => $val) {
				if (Strings::match($key, '/_'.$lang.'/')) {
					$index = Strings::replace($key, '/_'.$lang.'/');
						
					$values[$key] = $this->category->$index;
				}
			}
				
			if (count($values)) {
				$this->category->update($values);
			}
				
			$this->redirect('this');
		}
	}