<?php
	namespace FrontModule;

	use Nette\Utils\Strings;

	use Nette\Application\UI\Control;

	class Categories extends Control {	
		public $url;
		public $visibility;
		public $sid;
		public $current = array();
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
		}
		
		public function getCategories ($pid, $sid = false) {
			return $this->presenter->model->getCategories()->where(array('pid' => $pid, 'visibility' => $this->visibility, 'sections_id'.(!$this->sid ? '>?' : '') => 0))->order('position ASC');
		}
		
		public function getCategory ($cid) {
			return $this->presenter->model->getCategories()->wherePrimary($cid)->fetch();
		}
		
		public function getCategoriesTree ($cid, $i = 0) {
			$category = $this->getCategory($cid);
			
			if ($i == 0) {
				$this->current = array();
			}
				
			$this->current[] = $category->id;
			
			if ($category->pid != 0) {
				$this->getCategoriesTree($category->pid, $i+1);
			}
		}
		
		public function getEshopName () {
			$url = 'url'.$this->presenter->lang;
			$defaultUrl = 'url'.$this->getDefaultLang();
			
			if ($page = $this->presenter->model->getPagesModules()->where('modules_id', 3)->where('pages_id != ?', 1)->fetch()) {
				return $page->pages->$url ?: ($page->pages->$defaultUrl ?: $page->pages->url);
			}
			else return 'eshop';
		}
		
		public function getUrl ($category) {	
			$pageModule = $category->sections->related('pages_modules')->order('pages_id DESC')->fetch();
			
			if ($pageModule && $pageModuleCategory = $pageModule->related('pages_modules_categories')->where('categories_id = ? OR categories_id = ?', $category->id, 0)) {
				return $this->getPath($pageModule->pages, 0);
			}
		}
		
		public function getPath ($page, $i) {
			$url = 'url'.$this->presenter->lang;
				
			if ($i == 0) {
				$this->url = array();
			}
				
			$this->url[] = $page->$url;
		
			if ($page->pid != 0) {
				$page = $this->presenter->model->getPages()->wherePrimary($page->pid)->fetch();
				$this->getPath($page, $i+1);
			}
			else {
				$this->url = implode('/', array_reverse($this->url));
			}
		}
 		
		public function render ($params = null) {
			$this->visibility = isset($params['onlyHidden']) ? 0 : 1;
			
			$this->template->setFile(__DIR__.'/categories.latte');
			
			$this->template->categories = $this->getCategories(isset($params['pid']) ? $params['pid'] : 0);
			$this->template->maxLevel = isset($params['level']) ? $params['level'] : 1000;
			$this->template->sid = false;
			
			$this->template->render();
		}
		
		public function renderEshop ($params = null) {
			$this->visibility = 1;
			$this->sid = true;
			
			if ($this->presenter->category) {
				$this->getCategoriesTree($this->presenter->cid);
			}
			
			$this->template->setFile(__DIR__.'/categoriesEshop.latte');
			
			$this->template->categories = $this->getCategories(isset($params['pid']) ? $params['pid'] : 0, $this->sid);
			$this->template->maxLevel = isset($params['level']) ? $params['level'] : 1000;
			$this->template->current = $this->current;
			$this->template->eshop = $this->getEshopName();
			$this->template->defaultLang = $this->getDefaultLang();
			
			$this->template->render();
		}
		
		public function renderEshopGrid ($params = null) {
			$this->visibility = 1;
			$this->sid = true;
			
			if ($this->presenter->category) {
				$this->getCategoriesTree($this->presenter->cid);
			}
			
			$this->template->setFile(__DIR__.'/categoriesEshopGrid.latte');
			
			$this->template->categories = $this->getCategories(isset($params['pid']) ? $params['pid'] : 0, $this->sid);
			$this->template->maxLevel = isset($params['level']) ? $params['level'] : 1000;
			$this->template->current = $this->current;
			$this->template->eshop = $this->getEshopName();
			
			$this->template->render();
		}
		
		public function getDefaultLang () {
			if ($lang = $this->presenter->model->getLanguages()->where('highlight', 1)->fetch()) {
				return '_'.$lang->key;
			}
			else return null;
		}
		
		public function getThumb($place) {
			if ($thumbs = $this->presenter->model->getSectionsThumbs()->where('sections_id', 0)->where('place', array(0, $place))->order('place DESC')->fetch()) {
				return $thumbs->dimension;
			}
			else return false;
		}
	}