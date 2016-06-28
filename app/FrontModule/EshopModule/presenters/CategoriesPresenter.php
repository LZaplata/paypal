<?php
	namespace FrontEshopModule;

	use FrontEshopModule\Filters\PriceFilter;
use FrontEshopModule\Filters\PropertiesFilter;
	use FrontEshopModule\Filters\TagsFilter;
	use FrontEshopModule\Sorters\Sorter;
		
	class CategoriesPresenter extends BasePresenter {
		public $categories;
		public $actualUrl;
		public $cat;
		public $products;
		public $cart;
		public $section;
		public $paginator;
		public $cols;
		public $view;

		public function actionView () {
			$this->cols = array(1 => 12, 2 => 6, 3 => 4, 4 => 3, 6 => 2);
			
			$vp = new \VisualPaginator($this, 'paginator');
			$this->paginator = $vp->getPaginator();
			$this->paginator->page = $this->presenter->getParameter('page');			

			$this->getSection();
			$this->getCategory($this->cid);
			$this->getProducts();

			$this->view = $this->session->getSection("view");
			$this->view->setExpiration("1 day");

			if (($view = $this->getParameter("view"))) {
				$this->view->productsView = $view == "grid" ? 1 : 2;
			}

//			$this['priceFilter']->filterProducts();
			$this['tagsFilter']->filterProducts();

//			if (count($this['propertiesFilter']->categoryCategories)) {
//				$this['propertiesFilter']->filterProducts();
//			}

			$this['sorter']->sortProducts();

			$this->paginator->itemsPerPage = $this->module->lmt;
			$this->paginator->itemCount = count($this->products);
			$this->products->page($this->paginator->page, $this->paginator->itemsPerPage);
		}		
		
		public function renderView () {			
			$this->template->keywords = $this->cat->keywords;
			$this->template->title = $this->cat->title;
			$this->template->title_addition = $this->vendorSettings->title_products_categories;
			$this->template->desc = $this->cat->description;
			$this->template->category = $this->cat;
			$this->template->products = $this->products;
			$this->template->layout = $this->view && isset($this->view->productsView) ? $this->view->productsView : $this->module->layout;
			$this->template->settings = $this->settings;
			$this->template->currency = $this->currency == 'czk' ? $this->context->parameters['currency'] : $this->currency;
			$this->template->decimals = $this->currency == 'czk' ? 2 : 2;
			$this->template->homepage = false;
			$this->template->cols = $this->cols[$this->module->cols];
			$this->template->clearfix = $this->module->cols;
			$this->template->setTranslator($this->translator);
		}
		
		public function getCategory ($id) {			
			$this->cat = $this->model->getCategories()->select('*, title'.$this->lang.' AS title, description'.$this->lang.' AS description, keywords'.$this->lang.' AS keywords')->wherePrimary($id)->fetch();
		}
		
		public function getSection () {
			$this->section = $this->model->getShopSettings()->order('id ASC')->fetch();
		}
		
		public function getProducts () {	
			return $this->products = $this->model->getProductsCategories()->select('*, products.*')->where('categories_id', $this->cid)->where('products.pid IS NULL')->where('products.trash', 0)->where('visibility', 1)/*->order('position ASC')*/;
			
			/** pokud jsou verze produktů */
// 			$this->products = $this->model->getProductsInserted(implode(',', $this->getCategoryProducts()), $this->section->expirationDate)->order('position ASC');
		}
		
		public function getCategoryProducts () {
			if (count($categories = $this->model->getProductsCategories()->where('categories_id', $this->cid)->fetchPairs('products_id', 'products_id'))) {
				return $categories;
			}
			else return array(0);
		}
		
		/**
		 * price filter component factory
		 * @param string $name
		 * @return \FrontEshopModule\Filters\PriceFilter
		 */
		public function createComponentPriceFilter ($name) {
			return new PriceFilter($this, $name);
		}
		
		/**
		 * properties filter component factory
		 * @param string $name
		 * @return \FrontEshopModule\Filters\PropertiesFilter
		 */
		public function createComponentPropertiesFilter ($name) {
			return new PropertiesFilter($this, $name);
		}
		
		public function createComponentSorter ($name) {
			return new Sorter($this, $name);
		}

		public function createComponentTagsFilter($name)
		{
			return new TagsFilter($this, $name);
		}
	}