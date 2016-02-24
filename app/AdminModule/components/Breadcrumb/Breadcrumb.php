<?php
	namespace AdminModule;
	
	use Nette\Application\UI\Control;
	use Nette\Utils\ArrayHash;
		
	class Breadcrumb extends Control {
		public $breadcrumb = array();
		public $categories = array();
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
			
			$this->createModuleBreadcrumb();
			$this->createPresenterBreadcrumb();
		}
		
		public function createModuleBreadcrumb () {
			$stage = new ArrayHash();
			
			switch ($this->presenter->moduleName) {
				case 'AdminEshop':
					$stage->name = 'E-shop';
					$stage->url = $this->presenter->link(':AdminEshop:Products:');
					
					$this->breadcrumb[] = $stage;
					
					break;
			}
		}
		
		public function createPresenterBreadcrumb () {
			$stage = new ArrayHash();
			$stage2 = new ArrayHash();
			
			switch ($this->presenter->presenterName) {
				case 'Categories':
					$stage->name = 'Kategorie';
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':Categories:');
					
					$this->breadcrumb[] = $stage;
					
					$this->createActionBreadcrumb('category');
					
					break;
				case 'Products':
					$stage->name = 'Produkty';
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':Products:');
						
					$this->breadcrumb[] = $stage;
					
					if ($this->presenter->category) {
						$this->getCategoryTree($this->presenter->category);
						
						foreach (array_reverse($this->categories) as $category) {
							$stage = new ArrayHash();
							
							$stage->name = $category->name;
							$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':Products:', array('category' => $category->id));
							
							$this->breadcrumb[] = $stage;
						}
					}
						
					$this->createActionBreadcrumb('product');
						
					break;
				case 'Orders':
					$stage->name = 'Objednávky';
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':Orders:');
				
					$this->breadcrumb[] = $stage;
				
					$this->createActionBreadcrumb('order');
				
					break;
				case 'Settings':
					$stage->name = 'Nastavení';
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':Settings:');
				
					$this->breadcrumb[] = $stage;
				
					break;
				case 'SettingsHeureka':
					$stage->name = 'Nastavení Heureka.cz';
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':SettingsHeureka:');
				
					$this->breadcrumb[] = $stage;
				
					break;
				case 'SettingsZbozi':
					$stage->name = 'Nastavení Zboží.cz';
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':SettingsZbozi:');
				
					$this->breadcrumb[] = $stage;
				
					break;
				case 'Posts':
					$stage->name = 'Diskuze';
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':Posts:');
				
					$this->breadcrumb[] = $stage;
				
					break;
			}
		}
		
		public function createActionBreadcrumb ($var) {
			$stage = new ArrayHash();
			$stage2 = new ArrayHash();
			
			switch ($this->presenter->action) {
				case 'add':
					$stage->name = 'Přidat';
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':'.$this->presenter->presenterName.':add');
						
					$this->breadcrumb[] = $stage;
						
					break;
				case 'edit':
					$stage->name = $var != 'order' ? $this->presenter->$var->name : '#'.$this->presenter->$var->no;
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':'.$this->presenter->presenterName.':edit', array('id' => $this->presenter->id, 'sid' => $this->presenter->sid));
					
					$this->breadcrumb[] = $stage;
					
					break;
				case 'gallery':
					$stage->name = $this->presenter->$var->name;
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':'.$this->presenter->presenterName.':edit', array('id' => $this->presenter->$var->id, 'sid' => $this->presenter->sid));
						
					$this->breadcrumb[] = $stage;
					
					$stage2->name = 'Galerie';
					$stage2->url = $this->presenter->link(':'.$this->presenter->moduleName.':'.$this->presenter->presenterName.':gallery', array('id' => $this->presenter->id, 'gid' => $this->presenter->sid));
					
					$this->breadcrumb[] = $stage2;
						
					break;
				case 'files':
					$stage->name = $this->presenter->$var->name;
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':'.$this->presenter->presenterName.':edit', array('id' => $this->presenter->$var->id, 'sid' => $this->presenter->sid));
						
					$this->breadcrumb[] = $stage;
					
					$stage2->name = 'Soubory';
					$stage2->url = $this->presenter->link(':'.$this->presenter->moduleName.':'.$this->presenter->presenterName.':files', array('id' => $this->presenter->id, 'fid' => $this->presenter->sid));
						
					$this->breadcrumb[] = $stage2;
				
					break;
				case 'variations':
					$stage->name = $this->presenter->$var->name;
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':'.$this->presenter->presenterName.':edit', array('id' => $this->presenter->$var->id, 'sid' => $this->presenter->sid));
						
					$this->breadcrumb[] = $stage;
					
					$stage2->name = 'Variace';
					$stage2->url = $this->presenter->link(':'.$this->presenter->moduleName.':'.$this->presenter->presenterName.':variations', array('id' => $this->presenter->id, 'sid' => $this->presenter->sid));
						
					$this->breadcrumb[] = $stage2;
				
					break;
				case 'trash':					
					$stage->name = 'Koš';
					$stage->url = $this->presenter->link(':'.$this->presenter->moduleName.':'.$this->presenter->presenterName.':trash');
						
					$this->breadcrumb[] = $stage;
				
					break;
			}
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/breadcrumb.latte');
			
			$this->template->breadcrumb = $this->breadcrumb;
			
			$this->template->render();
		}
		
		public function getCategoryTree ($id) {
			$category = $this->presenter->model->getCategories()->wherePrimary($id)->fetch();
			$categories = array();
			
			$this->categories[] = $category;
			
			if ($category->pid != 0) {
				$this->getCategoryTree($category->pid);
			}
		}
	}