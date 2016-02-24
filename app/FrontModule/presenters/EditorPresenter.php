<?php
	namespace FrontModule;

	use Nette\Application\UI\Control;
	
	class EditorPresenter extends Control {
		public $module;
		public $editor;
		public $page;
		public $paginator;
		
		public function __construct($parent, $name, $module = false) {
			parent::__construct($parent, $name);
			
			$this->module = $module;
			
			$vp = new \VisualPaginator($this, 'paginator');
			$this->paginator = $vp->getPaginator();
			$this->paginator->page = $this->presenter->getParameter('page');
				
		}
		
		public function getEditor () {
			$this->editor = $this->presenter->model->getEditors()->select('*')->where('pages_modules_id', $this->module->id)->order('id DESC')->fetch();
		}
		
		public function getImages ($first = false) {
			$order = array(1 => 'id', 2 => 'position', 3 => 'date');
			$direction = array(1 => 'DESC', 2 => 'ASC');
			$images = $this->editor->galleries->related('galleries_images')->where('visibility', 1)->order($order[$this->editor->galleries->order].' '.$direction[$this->editor->galleries->direction])->where('visibility', 1);
				
			if ($first) {
				return $images->fetch();
			}
			else {
				if ($first === null) {
					$images->where('position != ?', 0);
				}
				
				$this->paginator->itemsPerPage = $this->editor->galleries->lmt;
				$this->paginator->itemCount = count($images);
				$images->page($this->paginator->page, $this->paginator->itemsPerPage);
				
				return $images;
			}
		}
		
		public function getThumb ($place) {
			if ($thumbs = $this->module->sections->related('sections_thumbs')->where('place', array(0, $place))->order('place DESC')->fetch()) {
				return $thumbs->dimension; 
			}
			else return false; 
		}
		
		public function getFiles ($data) {
			$files = $data->filestores->related('filestores_files')->order('highlight DESC, position ASC')->where('visibility', 1);
				
			return $files;
		}
		
		public function render($mid = false, $layout = false, $menu = null, $headingLevel = null) {			
			if ($mid) {
				$this->module = $this->presenter->model->getPagesModules()->wherePrimary($mid)->fetch();
			}
			
			$this->getEditor();
			
			$this->template->setFile(APP_DIR.'/FrontModule/templates/Modules/Module1/layout'.(!$layout ? $this->module->layout : $layout).'.latte');
				
			$this->template->editor = $this->editor;
			$this->template->homepage = $this->presenter->page? ($this->presenter->page->highlight ? true : false) : '';
			$this->template->lang = $this->presenter->lang;
			$this->template->defaultLang = $this->getDefaultLang();
			$this->template->icons = $this->presenter->context->parameters['icons'];
			$this->template->setTranslator($this->presenter->translator);
			$this->template->registerHelper('nbsp', 'Helpers::nbsp');
			$this->template->menu = $menu !== null ? $menu : $this->module->menu;
			$this->template->headingLevel = $headingLevel !== null ? $headingLevel : $this->module->heading_level;
			
			if ($this->module->menu) {
				$this->template->level = $this->getPageLevel($this->module->menu);
			}
				
			$this->template->render();
		}
		
		public function getDefaultLang () {
			if ($lang = $this->presenter->model->getLanguages()->where('highlight', 1)->fetch()) {
				return '_'.$lang->key;
			}
			else return null;
		}
		
		public function getPageLevel ($id, $pid = 0, $level = 0) {
			$pages = $this->presenter->model->getPages()->where('pid', $pid);
			
			foreach ($pages as $page) {
				if ($page->id == $id) {
					return $level+1;
					exit;
				}
				else {
					$this->getPageLevel($id, $page->id, $level+1);
				}
			}
		}
	}