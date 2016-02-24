<?php
	namespace FrontModule;
	
	use Nette\Application\UI\Control;

	class Tags extends Control {
		public $tags;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
		}
		
		public function getTags ($id) {
			$this->tags = $this->presenter->model->getSectionsTags()->find($id)->fetch(); 
		}
		
		public function getUrl () {
			foreach ($this->presenter->model->getSections()->find($this->tags->id_section)->fetch()->related('pages_modules') as $module) {
				if (!$module->pages->highlight) {
					return $this->getPath($module->pages, $module->pages->url);
				}
			}
		}
		
		public function getPath ($page, $path) {
			if ($page->pid == 0) {
				return $path;
			}
			else {
				$page = $this->presenter->model->getPages()->find($page->id)->fetch();
				$path .= '/'.$page->url;
		
				$this->getPath($page, $path);
			}
		}
		
		public function render ($id) {
			$this->getTags($id);
			
			$this->template->setFile(__DIR__.'/tags.latte');
			$this->template->tags = $this->tags;
			
			$this->template->render();
		}
	}