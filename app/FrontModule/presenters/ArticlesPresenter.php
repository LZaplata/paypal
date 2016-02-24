<?php
	namespace FrontModule;

	use Nette\Application\UI\Control;

	class ArticlesPresenter extends Control {
		public $module;
		public $articles = array();
		public $article;
		public $images = array();
		public $section;
		public $paginator;
		public $order;
		public $direction;
		public $url;
		public $categories;
		public $limit;
		public $cols;
		
		public function __construct($parent, $name, $module = false) {
			parent::__construct($parent, $name);
			
			$this->module = $module;
			
			$this->order = array(1 => 'id', 2 => 'position', 3 => 'date');
			$this->direction = array(1 => 'DESC', 2 => 'ASC');
			$this->cols = array(1 => 12, 2 => 6, 3 => 4, 4 => 3, 6 => 2);
			
			$vp = new \VisualPaginator($this, 'paginator');
			$this->paginator = $vp->getPaginator();
			$this->paginator->page = $this->presenter->getParameter('page');
		}
		
		protected function createTemplate($class = NULL) {
			$template = parent::createTemplate($class);
				
			$template->registerHelper('nbsp', function ($text) {
				return $text;
			});
					
			return $template;
		}
		
		public function getModuleCategories () {
			$moduleCategories = $this->module->related('pages_modules_categories')->fetchPairs('categories_id', 'categories_id');
			
			if (count($moduleCategories) == 1 && reset($moduleCategories) == 0) {
				$moduleCategories = $this->module->sections->related('categories')->fetchPairs('id', 'id');
			}
			
			return array_values($moduleCategories);
		}
		
		public function getArticlesCategories() {
			$articlesCategories = $this->presenter->model->getArticlesCategories();
			
			return array_values($articlesCategories->where('categories_id', $this->presenter->cid ? : $this->getModuleCategories())->fetchPairs('articles_id', 'articles_id'));
		}
		
		public function getArticlesTags () {
			$articlesTags = $this->presenter->model->getArticlesTags();
			
			return array_values($articlesTags->where('articles_id', $this->presenter->tag)->fetchPairs('id_articles', 'id_articles'));
		}
		
		public function getArticles () {				
			$query = $this->presenter->model->getArticles()->select('*');
			
			if (($this->presenter->cid && $this->presenter->moduleName == 'Front') || count($this->getModuleCategories())) {
				if (count($this->getArticlesCategories())) {
					$query->where('id IN ('.implode(",",$this->getArticlesCategories()).') OR pid IN ('.implode(",",$this->getArticlesCategories()).')');
				}
// 				else $query->where('id', null); 
				else $query->where('id IS NOT NULL');
			}
			if ($this->presenter->tag && !$this->presenter->cid) {
				$query->where('id IN ('.$this->getArticlesTags().')');
			}
			if ($this->module->sections->expirationDate) {
				$query->where('expirationDateFrom <= "'.date("Y-m-d H:i").'"')->where('expirationDateTo >= "'.date("Y-m-d H:i").'"');
			}
			
			$query->where('sections_id = '.$this->module->sections_id)->where('visibility = 1')/*->order('created DESC')*/;
			
// 			$this->articles = $this->presenter->model->getArticlesInserted($query->getSql())->select('*')->group('galleries_id');
 			$this->articles = $query;
			
			if ($this->module->highlight) {
				$this->articles->where('highlight', 1);
			}
			
			$this->articles->order('highlight DESC')->order($this->order[$this->module->order].' '.$this->direction[$this->module->direction]);
						
			$this->paginator->itemsPerPage = $this->limit ? $this->limit : $this->module->lmt;
			$this->paginator->itemCount = count($this->articles);
			$this->articles->page($this->paginator->page, $this->paginator->itemsPerPage);
		}
		
		public function getArticle () {
			if (!$this->article = $this->presenter->model->getArticles()->select('*, title'.$this->presenter->lang.' AS title, keywords'.$this->presenter->lang.' AS keywords, meta_description'.$this->presenter->lang.' AS meta_description')->where('id = ? OR pid = ?', $this->presenter->aid, $this->presenter->aid)->order('created DESC')->fetch()) {
				$this->presenter->error('', 404);
			}
			else {
				$this->article->visibility ? (!$this->presenter->getPathError() ?: $this->presenter->error('', 301)) : $this->presenter->error('', 408);
			}
			
			return $this->article;
		}
		
		public function getUrl ($article) {	
			$categories = $this->getArticleCategories($article->id);
			$cats = clone $categories;
			$homepage = $this->presenter->model->getPages()->where('highlight', 1)->fetch();
			
			if (count($categories)) {				
				if ($this->presenter->page->highlight != 1) {
					$pagesModulesCategories = array_values($this->module->related('pages_modules_categories')->fetchPairs('pages_modules_id', 'pages_modules_id'));
				}
				else {
					if (!count($pagesModulesCategories = array_values($this->presenter->model->getModulesCategories()->where('categories_id', $categories->fetch()->categories_id)->fetchPairs('pages_modules_id', 'pages_modules_id')))) {
						$pagesModulesCategories = array_values($article->sections->related('pages_modules')->fetchPairs('id', 'id'));
					}
				}
				
				$pagesModules = $this->presenter->model->getPagesModules()->where('id', $pagesModulesCategories)->where('pages_id != ?', $homepage->id)->fetch();
				$page = $pagesModules->pages;
			}	
			else {	
				$page = $article->sections->related('pages_modules')->where('pages_id != ?', $homepage->id)->fetch()->pages;
			}
			
			return $this->getPath($page, 0, $cats);
		}
		
		public function getPath ($page, $i, $categories) {				
			$url = 'url'.$this->presenter->lang;
			$defaultUrl = 'url'.$this->getDefaultLang();
			
			if ($i == 0) {
				$this->url = array();
				
				if (count($categories) && !$this->presenter->cid) {
					$category = $categories->fetch()->categories;
					
					$this->url[] = ($category->$url ?: ($category->$defaultUrl ?: $category->url)).'+c'.$category->id;
				}
			}
			
			$this->url[] = $page->$url ?: ($page->$defaultUrl ?: $page->url);
				
			if ($page->pid != 0) {
				$page = $this->presenter->model->getPages()->wherePrimary($page->pid)->fetch();
				$this->getPath($page, $i+1, $categories);
			}
			else {
				$this->url = implode('/', array_reverse($this->url));
			}
		}
		
		public function getModule($sectionID) {
			if ($sectionID) {
				return $this->module = $this->presenter->model->getPagesModules()->select('*')->where('sections_id', $sectionID)->fetch();
			}
		}
		
		public function getImages ($data, $first = false) {
			$images = $data->galleries->related('galleries_images')->order('highlight DESC, '.$this->order[$data->galleries->order].' '.$this->direction[$data->galleries->direction])->where('visibility', 1);
			
			if ($first){
				return $images->fetch();
			}
			else {
				if ($first === null) {
					$images->where('position != ?', 0);
				}
				
				$this->paginator->itemsPerPage = $data->galleries->lmt;
				$this->paginator->itemCount = count($images);
				$images->page($this->paginator->page, $this->paginator->itemsPerPage);
				
				return $images;
			}
		}
		
		public function getFiles ($data) {
			$files = $data->filestores->related('filestores_files')->order('highlight DESC, position ASC')->where('visibility', 1);
				
			return $files;
		}
		
		public function getThumb ($data, $place) {
			if ($dimension = $data->sections->related('sections_thumbs')->where('place', array(0, $place))->order('place DESC')->fetch()) {
				return $dimension->dimension;
			}
			else return false;
		}
		
		public function getCategory () {
			if ($this->presenter->cid) {
				return $this->presenter->model->getCategories()->select('*, title'.$this->presenter->lang.' AS title, keywords'.$this->presenter->lang.' AS keywords, description'.$this->presenter->lang.' AS description')->wherePrimary($this->presenter->cid)->fetch();
			}
		}
		
		public function getArticleCategory ($article) {
			return $article->related('articles_categories')->fetch()->categories;
		}
		
		public function getArticleCategories ($id) {
			return $this->categories = $this->presenter->model->getArticlesCategories()->where('articles_id', $id);
		}
		
		/*
		public function getArticleModule ($id) {
			if ($categories = $this->presenter->model->getArticles()->wherePrimary($id)->fetch()->related('articles_categories')->fetch()) {
				if ($moduleCategory = $this->presenter->model->getModulesCategories()->where('categories_id', $categories->categories_id)->fetch()) {
					return $moduleCategory->pages_modules_id;
				}
				else return $this->module->id;
			}
			else return $this->module->id;
		}*/
		
		public function getArticleModule ($id) {
			if ($categories = $this->presenter->model->getArticles()->wherePrimary($id)->fetch()->related('articles_categories')->fetchPairs('id', 'categories_id')) {
				if ($moduleCategory = $this->presenter->model->getModulesCategories()->where('categories_id', array_values($categories))->order('pages_modules_id DESC')->fetchPairs('id', 'pages_modules_id')) {
					return $moduleCategory;
				}
				else return (array) $this->module->id;
			}
			else return (array) $this->module->id;
		}
		
		public function getTags ($article) {
			return $this->presenter->model->getArticlesTags()->select('*, articles.*')->where('id_articles', $article->id);
		}
		
		public function render($sectionID = false, $layout = false, $limit = false) {
			$this->getModule($sectionID);
			$this->limit = $limit;
			
// 			if ($this->presenter->aid && $this->module->id == $this->getArticleModule($this->presenter->aid) && !$sectionID) {
			if ($this->presenter->aid && in_array($this->module->id, $this->getArticleModule($this->presenter->aid)) && !$sectionID) {
				$this->getArticle();
				
				$this->template->setFile(APP_DIR.'/FrontModule/templates/Modules/Module2/detail'.$this->module->detail.'.latte');
			}
			else {
				$this->getArticles();
				
				if (count($this->getModuleCategories()) > 1 && !$this->presenter->cid && !$sectionID && !$this->presenter->tag && !$this->presenter->template->homepage) {
					$this->template->setFile(APP_DIR.'/FrontModule/templates/Modules/Module2/categories.latte');
				}
				else { 
					$this->template->setFile(APP_DIR.'/FrontModule/templates/Modules/Module2/layout'.($layout ? $layout : $this->module->layout).'.latte');
				}
			}
			
			$this->template->articles = $this->articles;
			$this->template->article = $this->article;
			$this->template->categories = $this->presenter->model->getCategories()->where('id', $this->getModuleCategories())->where('visibility', 1);
			$this->template->lang = $this->presenter->lang;
			$this->template->defaultLang = $this->getDefaultLang();
			$this->template->icons = $this->presenter->context->parameters['icons'];
			$this->template->cols = $this->cols[$this->module->cols];
			$this->template->clearfix = $this->module->cols;
			$this->template->setTranslator($this->presenter->translator);
			
			$this->template->render();
		}
		
		public function getDefaultLang () {
			if ($lang = $this->presenter->model->getLanguages()->where('highlight', 1)->fetch()) {
				return '_'.$lang->key;
			}
			else return null;
		}
	}