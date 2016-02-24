<?php
	namespace FrontModule;

	use Nette\Iterators\CachingIterator;

	use Nette\Application\UI\Control;

	class Breadcrumb extends Control {
		public $breadcrumb = array();
		public $categories = array();
		public $eshop;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
		} 
		
		public function render ($separator = '>') {
			if (count($this->presenter->pages)) {
				$this->getPagesBreadcrumb();
			}			
			if ($this->presenter->cid) {
				if ($this->presenter->moduleName == 'FrontEshop') {
					$this->getEshopPageName(false);
				}
				
				$this->getCategoriesTree($this->presenter->cid);
			}
			if ($this->presenter->moduleName == 'FrontEshop') {
				$this->getEshopPageName();
				$this->getProduct();
			}
			if ($this->presenter->aid) {
				$this->getArticle();
			}
			
			$this->template->setFile(__DIR__.'/breadcrumb.latte');
			$this->template->breadcrumb = $this->breadcrumb;
			$this->template->separator = $separator;
			
			$this->template->render();
		}
		
		public function getCategoriesTree ($cid, $i = 0) {
			$category = $this->presenter->getComponent('categories')->getCategory($cid);
			$name = 'name'.$this->presenter->lang;
				
			if ($this->presenter->moduleName == 'Front') {
				array_unshift($this->categories, (object) array('name' => ($category->$name == null ? $category->name : $category->$name), 'url' => $this->presenter->defaultLink(':Front:Page:', array('url' => $this->presenter->url, 'category' => $category->url, 'cid' => $category->id))));	
			}
			else {
				array_unshift($this->breadcrumb, (object) array('name' => ($category->$name == null ? $category->name : $category->$name), 'url' => $this->presenter->defaultLink(':FrontEshop:Categories:view', array('category' => $category->url, 'cid' => $category->id, 'eshop' => $this->eshop))));
			}
				
			if ($category->pid != 0) {				
				$this->getCategoriesTree($category->pid, $i+1);
			}
			else {
				if ($this->presenter->moduleName == 'Front') {
					$this->breadcrumb = array_merge_recursive($this->breadcrumb, $this->categories);
				}
			}
		}
		
		public function getPagesBreadcrumb () {
			$iterator = new CachingIterator($this->presenter->pages);
			foreach ($iterator as $page) {
				$page = $this->presenter->model->getPages()->where('url'.$this->presenter->lang, $page)->where('pid', isset($pid) ? $pid : 0)->fetch();
				$name = 'name'.$this->presenter->lang;
				$pid = $page->id;
				
				if ($page->visibility) {
					$this->breadcrumb[] = (object) array('name' => ($page->$name == null ? $page->name : $page->$name), 'url' => $this->presenter->defaultLink('Page:', array('url' => implode('/', array_slice($this->presenter->pages, 0, $iterator->counter)))));
				}
			}
		}
		
		public function getEshopPageName ($breadcrumb = true) {
			$page = $this->presenter->model->getPagesModules()->where('modules_id', 3)->where('pages.highlight', 0)->fetch()->pages;
			$name = 'name'.$this->presenter->lang;
			$url = 'url'.$this->presenter->lang;
			$this->eshop = $page->$url == null ? $page->url : $page->$url;
			
			if ($breadcrumb) {
				array_unshift($this->breadcrumb, (object) array('name' => ($page->$name == null ? $page->name : $page->$name), 'url' => $this->presenter->defaultLink(':FrontEshop:Homepage:', array('eshop' => $this->eshop))));
			}
		}
		
		public function getArticle () {
			$article = $this->presenter['articles']->article;
			$name = 'name'.$this->presenter->lang;
			
			array_push($this->breadcrumb, (object) array('name' => ($article->$name == null ? $article->name : $article->$name), 'url' => $this->presenter->link('Page:', array('pages' => implode('/', $this->presenter->pages), 'aid' => $article->id, 'article' => $article->url))));
		}
		
		public function getProduct () {
			if ($this->presenter->product) {
				$product = $this->presenter->model->getProducts()->wherePrimary($this->presenter->pid)->fetch();
				$category = $this->presenter->model->getCategories()->wherePrimary($this->presenter->cid)->fetch();
				$name = 'name'.$this->presenter->lang;
				$url = 'url'.$this->presenter->lang;
					
				array_push($this->breadcrumb, (object) array('name' => ($product->$name == null ? $product->name : $product->$name), 'url' => $this->presenter->defaultLink(':FrontEshop:Products:view', array('category' => ($category->$url ? $category->$url : $category->url), 'cid' => $category->id, 'product' => ($product->$url ? $product->$url : $product->url), 'pid' => $product->id))));
			}
		}
	}