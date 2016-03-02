<?php
	namespace AdminEshopModule;
	
	use AdminModule\BasePresenter;

	use AdminModule\ThumbsGrid;

	use AdminModule\Fields;

	use AdminModule\Transport;

	use AdminModule\Thumbs;

	use Nette\Utils\Strings;

	use Nette\Application\UI\Form;
	
	use Nette\Utils\Finder;	
use Nette\Forms\Rendering\BootstrapFormRenderer;
		
	class SettingsPresenter extends BasePresenter {
		public $urlID;
		public $section;
		public $methods;
		public $properties;
		public $sid;
		public $id;
		
		public function startup() {
			parent::startup();
		}
	
		public function actionDefault () {
			$this->urlID = 0;
			$this->sid = 0;
			$this->id = 0;
			
			$this->section = $this->model->getShopSettings()->fetch();
			
			$this->methods = $this->model->getShopMethods();
			
			$this->properties = $this->model->getShopProperties();
			
			if (!$this['fields']->getParameter('order')) {
				$this->redirect('this', array('fields-order' => 'name ASC', 'properties-order' => 'id ASC', 'methods-order' => 'id ASC', 'methodsRelations-order' => 'id ASC', 'thumbs-order' => 'dimension ASC', "tags-order" => "id ASC"));
			}
		}
		
		public function createComponentSettingsForm () {
			$form = new Form();
			
			$form->getElementPrototype()->addClass('form-horizontal');
			
			$form->addGroup('Základní vlastnosti produktu');
			$form->addCheckbox('name', 'Jméno')
				->setValue(true);
			
			$form->addCheckbox('title', 'Titulek')
				->setValue(true);
			
			$form->addCheckbox('keywords', 'Klíčová slova')
				->setValue(true);
			
			$form->addCheckbox('ean', 'EAN');
			
			$form->addCheckbox('code', 'Kód produktu');
			
			$form->addCheckbox('expirationDate', 'Platnost');
			
			$form->addCheckbox('price', 'Cena')
				->setValue(true);
			
			$form->addCheckbox('stock', 'Sklad')
				->setValue(true);
			
			$form->addCheckbox('description', 'Krátký popis')
				->setValue(true);
			
			$form->addCheckbox('text', 'Text')
				->setValue(true);
			
			$form->addGroup('Doplňky k produktu');
			
			$form->addCheckbox('files', 'Přilepit soubory');
			
			$form->addCheckbox('siblings', 'Přilepit podobné produkty');
			
			$form->addCheckbox('versions', 'Možnost verzování obsahu');
			
			$form->addCheckbox('watermark', 'Přilepit vodoznak k obrázkům');
			
			$form->addCheckbox('discounts', 'Slevový systém');
			
			$form->addCheckbox('posts', 'Diskuze k produktu');
			
			$form->addCheckbox('dynamicform', 'Dynamický formulář u produktů');
			
			$form->addGroup('Feedy a datové zdroje pro srovnávače cen');
			
			$form->addCheckbox('heureka', 'Heureka.cz');
			
			$form->addCheckbox('zbozi', 'Zboží.cz');
			
			$form->addCheckbox('merchants', 'Google Merchants');
			
			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('add', $this->section ? 'Upravit' : 'Vytvořit');
			
			$form->onSuccess[] = callback($this, $this->section ? 'editSettings' : 'addSettings');
			
			if ($this->section) {				
				$form->setValues($this->section);
			}
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		public function addSettings ($form) {
			$values = $form->getValues();
				
			if (isset($values['fields'])) {
				$data['fields'] = $values['fields'];
				unset($values['fields']);
			}
			
			if (isset($values['properties'])) {
				$this->saveProperties($values['properties']);
				unset($values['properties']);
			}
				
			$lastID = $this->model->getShopSettings()->insert($values);
			
			$this->flashMessage('Nastavení bylo uloženo');
			$this->redirect('Products:');
		}
		
		public function editSettings ($form) {
			$values = $form->getValues();
			
			if (isset($values['properties'])) {
				$this->saveProperties($values['properties']);
				unset($values['properties']);
			}
				
			$this->section->update($values);
				
			$this->flashMessage('Nastavení bylo uloženo');
			$this->redirect('Products:');
		}
		
		public function saveProperties ($properties) {
			for ($i=1; $i<=3; $i++) {
				if (!$this->model->getShopProperties()->where(array('name' => $properties['name'.$i], 'properties' => $properties['properties'.$i]))->fetch() && !empty($properties['name'.$i])) {
					$values['name'] = $properties['name'.$i];
					$values['properties'] = $properties['properties'.$i];
					
					$this->model->getShopProperties()->insert($values);
				}
			}
		}
		
		/*public function handleDeleteThumb ($sid, $id) {
			$ids = (array)$id;
			
			$this->model->getSectionsThumbs()->where('id', $ids)->delete();
			
			$this->flashMessage('Rozměr byl smazán');
		}*/
		
		public function handleDeleteThumb ($sid, $id, $watermark = false) {
			$ids = (array) $id;
			
			foreach ($ids as $val) {			
				$thumb = $this->model->getSectionsThumbs()->where('id', $val)->fetch();
				$productsGalleries = $this->model->getProducts()->fetchPairs('id', 'galleries_id');
				$categoriesGalleries = $this->model->getCategories()->where('sections_id', 0)->fetchPairs('id', 'galleries_id');
				$galleries = array_merge($categoriesGalleries, $productsGalleries);
				$dir = WWW_DIR . '/files/galleries/';
					
				foreach ($galleries as $gallery) {
					foreach (Finder::findFiles($thumb->dimension.'_g'.$gallery.'-*')->in($dir) as $file) {
						unlink($file->getPathName());
					}
				}
					
				if (!$watermark) {
					$thumb->delete();
				}
			}
				
			if (!$watermark) {
				$this->flashMessage('Rozměr byl smazán');
			}
		}
		
		public function handleCreateThumbs ($thumb) {
			$categoryGalleries = $this->model->getProducts()->fetchPairs('id', 'galleries_id');
			$productsGalleries = $this->model->getCategories()->where('sections_id', 0)->fetchPairs('id', 'galleries_id');
			$galleries = array_merge($categoryGalleries, $productsGalleries);
							
			foreach ($galleries as $gallery) {
				$this['gallery']->handleCreateThumbs($gallery, $thumb);
			}
		}
		
		public function createComponentThumbs () {
			return new ThumbsGrid($this->model->getSectionsThumbs()->where('sections_id', 0));
		}
		
		public function createComponentProperties () {
			return new PropertiesCategories($this->model->getCategories()->where('sections_id', -2));
		}
		
		public function createComponentFields () {
			return new Fields($this->model->getSectionsFields()->where('sections_id', $this->sid));
		}
		
		public function createComponentMethods ($name) {
			return new Methods($this->methods);
		}
		
		public function createComponentMethodsRelations ($name) {
			return new MethodsRelations($this->model->getShopMethodsRelations());
		}
		
		public function createComponentGallery ($name) {
			return new \GalleryPresenter($this, $name);
		}

		public function createComponentTags()
		{
			return new Tags($this->model->getTags());
		}
	}