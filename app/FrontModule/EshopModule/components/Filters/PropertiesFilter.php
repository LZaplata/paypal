<?php
	namespace FrontEshopModule\Filters;
	
	use Nette\Application\UI\Control;
	use Nette\Application\UI\Form;
	use Nette\Forms\Rendering\BootstrapFormRenderer;
	use Nette\Utils\Strings;
	use Nette\Http\Url;
	use Nette\Utils\Json;
								
	class PropertiesFilter extends Control {		
		public $categoryCategories;
		public $activeProperties;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
			
			$this->getCategoryCategories();
			
			$this->activeProperties = $this->presenter->getParameters();
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/propertiesFilter.latte');
			
			$this->template->categoryCategories = $this->categoryCategories;
			$this->template->activeProperties = $this->activeProperties;
			
			$this->template->render();
		}
		
		/**
		 * get properties categories of current category
		 */
		public function getCategoryCategories () {
			$this->categoryCategories = $this->presenter->model->getCategoriesCategories()->where('id_category', $this->presenter->cid);
		}
		
		/**
		 * filter form factory
		 * @return \Nette\Application\UI\Form
		 */
		public function createComponentFilterForm () {
			$form = new Form();
			
			$form->getElementPrototype()->class('form-horizontal');
			
			foreach ($this->categoryCategories as $category) {
				$form->addCheckboxList('category'.$category->categories_id, $category->categories->title, $this->getCategoryProperties($category->categories_id));
			}
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			if ($this->activeProperties) {				
				$form->setValues($this->activeProperties);
			}
			
			return $form;
		}
		
// 		public function getCategoryProperties ($cid) {
// 			$categoryProperties = $this->presenter->model->getShopProperties()->where('categories_id', $cid)->fetchPairs('id', 'name');
			
// 			return $categoryProperties;
// 		}
		
		public function getCategoryProperties ($cid) {
			$products = $this->presenter->getProducts();
			$categoryProperties = $this->presenter->model->getShopProperties()->where('categories_id', $cid)->fetchPairs('id', 'name');
			$categoryPropertiesPositions = $this->presenter->model->getShopProperties()->where('categories_id', $cid)->fetchPairs('id', 'position');			
			$productsProperties = array();
			
			foreach ($products as $product) {
				if ($product->properties != null) {
					$productProperties = Json::decode($product->properties);
					
					if (isset($productProperties->$cid)) {
						$productProperties = $productProperties->$cid;
						
						$productsProperties = array_merge($productsProperties, $productProperties);
					}
				}
			}
			
			$properties = array_flip(array_intersect_key($categoryPropertiesPositions, array_flip($productsProperties)));
			$propertiesOrder = array();
			ksort($properties);
			
			foreach ($properties as $property) {
				$propertiesOrder[$property] = $categoryProperties[$property];
			}
			
			return $propertiesOrder;
			
// 			return array_intersect_key($categoryProperties, array_flip($productsProperties));
			
			
			/*$categoryProperties = $this->presenter->model->getShopProperties()->where('categories_id', $cid)->fetchPairs('id', 'name');
			
			foreach ($categoryProperties as $key => $property) {
				if (!$this->presenter->model->getProductsProperties()->where('p_'.$key, true)->where('pid', array_keys($products))->fetch()) {
					unset($categoryProperties[$key]);
				}
			}
			
			return $categoryProperties;*/
		}
		
		/**
		 * handler for ajax filter
		 */
		public function handleFilter () {
			$values = $_GET;
			parse_str(Url::unescape($values['data']), $data);
			
			foreach ($this->categoryCategories as $category) {
				$name = 'category'.$category->categories_id;
				
				if (isset($values[$name])) {
					unset($values[$name]);
				}
			}
			
			unset($values['do']);
			unset($values['data']);
			unset($data['do']);
			
			$this->activeProperties = array_merge($values, $data);
			$this->template->url = $this->presenter->link('this', $this->activeProperties);
				
			$this->invalidateControl('url');			
			$this->invalidateControl('filter');			
			$this->presenter->invalidateControl('products');
		}
		
		/**
		 * filter products
		 */
		public function filterProducts () {
			if (count($this->categoryCategories)) {
				$values = $_GET;
				$products = $this->presenter->model->getProductsProperties();
				
				if ($this->presenter->isAjax() && isset($values['data'])) {
					parse_str(Url::unescape($values['data']), $data);
				}
				else {
					$data = $values;
				}
				
				foreach ($this->categoryCategories as $category) {
					$name = 'category'.$category->categories_id;
					
					if (isset($data[$name])) {
						$values = $data[$name];
						$query = '';
						
						$i = 0;
						foreach ($values as $value) {
							if ($i == 0) {
								$query .= 'p_'.$value.' = 1';
							}
							else {
								$query .= ' OR p_'.$value.' = 1';
							}
	
							$i++;
						}
						
						$products->where($query);
					}
				}
				
				$this->presenter->products->where('products.id', array_values($products->fetchPairs('id', 'pid')));
			}
		}
	}