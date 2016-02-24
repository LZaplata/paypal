<?php
	namespace AdminModule;
	
	use AdminModule\SecuredPresenter;
	
	class BrowserPresenter extends BasePresenter {
		public $images;
		public $sections;
		public $dimensions;
		public $files;
		public $urlID;
		public $url;
		
		public function actionImage () {
			$this->sections = $this->model->getSections();
			
			if ($this->getParameter('gid')) {
				$this->images = $this->model->getGalleriesImages()->order('highlight DESC, position ASC')->where('galleries_id', $this->getParameter('gid'));
			}
			
			if ($this->getParameter('cid')) {
				$productsGalleries = $this->model->getProductsCategories()->select('products.galleries_id AS gallery')->where('categories_id', $this->getParameter('cid'))->fetchPairs('gallery', 'gallery');
			
				$this->images = $this->model->getGalleriesImages()->order('highlight DESC, position ASC')->where('galleries_id', array_values($productsGalleries));
			}
			
			$this->dimensions = $this->model->getSectionsThumbs()->select('DISTINCT dimension')->order('dimension ASC');
		}
		
		public function actionFile () {			
			$this->actionImage();
			
			$this->files = $this->model->getFilestoresFiles()->select('DISTINCT name, title, filestores_id');
		}
		
		public function renderImage () {
			$this->template->images = $this->images;
			$this->template->sections = $this->sections;
			$this->template->dimensions = $this->dimensions;
		}
		
		public function renderFile () {
			$this->renderImage();
			
			$this->template->files = $this->files;
			$this->template->pages = $this->getPages(0);
		}
		
		public function getPages ($pid) {									
			return $this->presenter->model->getPages()->select('*')->where('pid', $pid)->order('position ASC');
		}
		
		public function getPageUrl ($pid, $url) {
			if ($pid == 0) {
				$this->url = '/'.$url;
			}
			else {
				$page = $this->model->getPages()->wherePrimary($pid)->fetch();
				
				$this->getPageUrl($page->pid, $page->url.'/'.$url);
			}
		}
		
		public function getGalleries ($mid, $sid) {
			switch ($mid) {
				case 1:
					$galleries = $this->model->getEditors()->where('sections_id', $sid);
					break;
				case 2:
					$galleries = $this->model->getArticles()->where('sections_id', $sid);
					break;
				case 3:
					$galleries = $this->model->getProducts()->where('pid IS NULL')->where('trash', 0);
					break;
			}
			
			return $galleries;
		}
	}