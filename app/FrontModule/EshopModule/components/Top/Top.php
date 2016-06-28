<?php
	namespace FrontEshopModule;
	
	use Nette\Application\UI\Control;

	class Top extends Control {
		public $products;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
			
			$this->getProducts();
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/top.latte');
			$this->template->products = $this->products;
			$this->template->settings = $this->presenter->model->getShopSettings()->fetch();
			$this->template->currency = $this->presenter->currency == 'czk' ? $this->presenter->context->parameters['currency'] : $this->presenter->currency;
			$this->template->decimals = $this->presenter->currency == 'czk' ? 2 : 2;
			$this->template->eshop = $this->presenter->model->getPagesModules()->where('modules_id', 3)->where('position', 1)->where('pages_id != ?', 1)->fetch()->pages->url;
			$this->template->registerHelperLoader(array(new \Helpers($this), 'loader'));
			$this->template->setTranslator($this->presenter->translator);
			
			$this->template->render();
		}
		
		public function getProducts () {
			$this->products = $this->presenter->model->getProducts()->where('pid IS NULL')->where('highlight', 1)->where('trash', 0);
		}
		
		public function getThumb($place) {
			if ($thumbs = $this->presenter->model->getSectionsThumbs()->where('sections_id', 0)->where('place', array(0, $place))->order('place DESC')->fetch()) {
				return $thumbs->dimension;
			}
			else return false;
		}
		
		public function getProductCategory ($product) {
			return $this->presenter->model->getProductsCategories()->select('categories.*')->where('products_id', $product->products_id)->order('pid DESC')->fetch();
		}
	}