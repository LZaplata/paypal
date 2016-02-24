<?php
	namespace FrontEshopModule\Filters;
	
	use Nette\Application\UI\Control;
		
	class PriceFilter extends Control {
		public $min;
		public $max;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
			
			$this->getPriceRange();
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/priceFilter.latte');
			
			$this->template->min = $this->min;
			$this->template->max = $this->max;
			$this->template->currency = $this->presenter->currency == 'czk' ? $this->presenter->context->parameters['currency'] : $this->presenter->currency;
			$this->template->decimals = $this->presenter->currency == 'czk' ? 0 : 2;
			$this->template->setTranslator($this->presenter->translator);
			
			$this->template->render();
		}
		
		/**
		 * get minimal and maximal price in current category
		 */
		public function getPriceRange () {
			$products = $this->presenter->getProducts();
			$products = $products->fetchPairs('id', 'price_filter');
			
			$this->min = count($products) ? min($products) : 0;
			$this->max = count($products) ? max($products) : 0;
		}
		
		/**
		 * handler for ajax filter
		 */
		public function handleFilter () {
			$values = $_GET;
			$range['min'] = $values['range'][0];
			$range['max'] = $values['range'][1];
			
			unset($values['do']);
			unset($values['range']);
			
			$data = array_merge($values, $range);
			$this->template->url = $this->presenter->link('this', $data);
			
			$this->invalidateControl('url');			
			$this->presenter->invalidateControl('products');
		}
		
		/**
		 * filter products
		 */
		public function filterProducts () {
			if ($this->presenter->isAjax()) {
				$min = isset($_GET['range']) ? $_GET['range'][0] : ($this->presenter->getParameter('min') ? $this->presenter->getParameter('min') : $this->min);
				$max = isset($_GET['range']) ? $_GET['range'][1] : ($this->presenter->getParameter('max') ? $this->presenter->getParameter('max') : $this->max);
			}
			else {
				$min = $this->template->minSelect = $this->presenter->getParameter('min') ? $this->presenter->getParameter('min') : $this->min;
				$max = $this->template->maxSelect = $this->presenter->getParameter('max') ? $this->presenter->getParameter('max') : $this->max;
			}
			
			if ($min == $max) {
				$this->presenter->products->where('price_filter >= ?', ($min - 0.5))->where('price_filter <= ?', ($min + 0.5));
			}
			else $this->presenter->products->where('price_filter >= ?', ($min - 0.5))->where('price_filter <= ?', $max);
		}
	}