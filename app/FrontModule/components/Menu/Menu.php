<?php
	namespace FrontModule;

	use Nette\Application\UI\Control;
	
	class Menu extends Control {	
		public $url;
		public $visibility;
		public $id;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
		}
		
		public function getPages ($pid) {			
			return $this->presenter->model->getPages()->where(array('pid' => $pid, 'visibility' => $this->visibility))->where('menu = ? OR menu = ?', 0, $this->id)->order('position ASC');
		}
		
		public function getPageUrl ($page, $i = 0) {
			$url = 'url'.$this->presenter->lang;
			
			if ($i == 0) {
				$this->url = array();
			}
				
			$this->url[] = $page->$url ? $page->$url : $page->url;
			
			if ($page->pid != 0) {
				$page = $this->presenter->model->getPages()->wherePrimary($page->pid)->fetch();
				$this->getPageUrl($page, $i+1);
			}
			else {
				$this->url = implode('/', array_reverse($this->url));
			}
		}
		
		public function getPage ($url) {
			return $this->presenter->model->getPages()->where('url', $url)->fetch();
		}
		
		public function render ($params = null) {
			$this->visibility = isset($params['onlyHidden']) ? 0 : 1;
			$this->id = isset($params['id']) ? $params['id'] : 0;
			
			$this->template->setFile(__DIR__.'/menu.latte');
			
			$this->template->pages = $this->getPages(isset($params['pid']) ? (is_int($params['pid']) ? $params['pid'] : $this->getPage($params['pid'])->id) : 0);
			$this->template->maxLevel = isset($params['maxLevel']) ? $params['maxLevel'] : 1000;
			$this->template->cats = isset($params['displayCategories']) ? true : false;
			$this->template->level = isset($params['level']) ? $params['level'] : 0;
			
			$this->template->render();
		}
		
		public function renderNavbar ($params = null) {
			$this->visibility = isset($params['onlyHidden']) ? 0 : 1;
			$this->id = isset($params['id']) ? $params['id'] : 0;
				
			$this->template->setFile(__DIR__.'/navbar.latte');
				
			$this->template->pages = $this->getPages(isset($params['pid']) ? (is_int($params['pid']) ? $params['pid'] : $this->getPage($params['pid'])->id) : 0);
			$this->template->maxLevel = isset($params['level']) ? $params['level'] : 1000;
			$this->template->cats = isset($params['displayCategories']) ? true : false;
				
			$this->template->render();
		}
	}