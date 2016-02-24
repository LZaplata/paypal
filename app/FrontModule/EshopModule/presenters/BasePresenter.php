<?php
	namespace FrontEshopModule;
	
	use Nette\Application\UI\Form;

	use FrontModule\PagePresenter;

	class BasePresenter extends PagePresenter {
		/** @persistent */
		public $product;
		/** @persistent */
		public $pid;
		/** @persistent */
		public $eshop;
		public $cart;
		public $settings;
		public $client;
		public $subPrice;
		public $methods;
		public $module;
		
		public function startup() {
			parent::startup();
			
			$session = $this->context->session;
			$this->cart = $session->getSection('cart');

			$this->invalidateControl('flashMessages');
			
			$this->module = $this->presenter->model->getPagesModules()->where('modules_id', 3)->where('pages_id != ?', 1)->fetch();
			
			$this->getSettings();

			$this->client = $session->getSection('client');
			$this->methods = $session->getSection('methods');
			
			$this->template->setTranslator($this->translator);
			$this->template->defaultLang = $this->getDefaultLang();
		}
		
		public function setLang($lang) {
			$this->lang = $lang == null ? null : $lang;
		}
		
		public function getThumb($place) {
			if ($thumbs = $this->model->getSectionsThumbs()->where('sections_id', 0)->where('place', array(0, $place))->order('place DESC')->fetch()) {
				return $thumbs->dimension;
			}
			else return false;
		}
		
		public function getSettings () {
			$this->settings = $this->model->getShopSettings()->fetch();
		}
		
		public function getProductCategory ($product) {
			return $this->model->getProductsCategories()->select('categories.*')->where('products_id', $product->products_id)->fetch();
		}
		
		public function getProductProperties ($id) {		
			$productProperties = $this->model->getProductsProperties()->where('products_id', $id)->fetch();
			$shopProperties = $this->model->getShopProperties();
			$properties = array();
			
			foreach ($shopProperties as $property) {
				$p = 'p_'.$property->id;
			
				if ($productProperties->$p) {
					$properties[] = $property->categories->name.' - '.$property->name;
				}
			}
			
			return implode(', ', $properties);
		}
		
		public function getDefaultLang () {
			if ($lang = $this->presenter->model->getLanguages()->where('highlight', 1)->fetch()) {
				return '_'.$lang->key;
			}
			else return null;
		}
	}