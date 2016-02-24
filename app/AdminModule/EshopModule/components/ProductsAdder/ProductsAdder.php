<?php
	namespace AdminEshopModule;

	use Nette\Application\UI\Control;
	use Nette\Application\UI\Form;	

	class ProductsAdder extends Control {		
	  /** @persistent */
		public $product;
		public $properties;	
		public $propertiesCategories;	
		public $categoriesProperties;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);

				$this->propertiesCategories = $this->presenter->model->getCategories()->where('sections_id', -2)->order('position ASC')->fetchPairs('id', 'name');
				$this->product = $this->presenter->model->getProducts()->wherePrimary(1340)->fetch();
				$this->getDefaultProperties();
				$this->getCategoriesProperties();
		}
		
		public function render () {	
			if($this->product){
				$this->getComponent('addToCartForm')->setDefaults(array('product'=>$this->product));
				$this->template->product = $this->product;				
			}		
			$this->template->setFile(__DIR__.'/adder.latte');			
			$this->template->render();
		}
		
		public function createComponentPropertiesForm () {
			$form = new Form();
			
			$form->getElementPrototype()->class('form-horizontal');
			foreach ($this->propertiesCategories as $key => $category) {
				$form->addSelect('category'.$key, $category, $this->categoriesProperties[$key])
					->setAttribute('onChange', 'changeProperties()');
				
				$form->setValues($this->properties);
			}
			
			return $form;
		}		
		
		public function createComponentAddToCartForm () {
			$form = new Form();

			$form->addSelect('product','Produkt',$this->presenter->model->getProducts()->fetchPairs('id','name'))
				->getControlPrototype()->class('chosen product')
				->setAttribute('onChange', 'changeProduct()');
			
			return $form;
		}
		
		public function handlechangeProduct(){
			$values = $_GET;
			$this->product = $values['product'];
			$this->invalidateControl();
						
		}
		
		public function getDefaultProperties () {
			foreach ($this->presenter->model->getProductsProperties()->select('*, shop_properties.categories_id AS category')->where('products_id', $this->product->id) as $property) {
				$properties['category'.$property->category] = $property->shop_properties_id;
			}		
			$this->properties = $properties;
		}
		
		public function getCategoriesProperties () {
			$properties = $this->presenter->model->getProductsProperties()->select('*, shop_properties.categories_id AS category, shop_properties.name AS name')->where('products_id = ? OR pid = ?', $this->product->id, $this->product->id);
			$sortedProperties = $this->properties;
			$this->categoriesProperties = array();
				
			ksort($sortedProperties);
				
			foreach ($sortedProperties as $key => $property) {
				$sortedProperties[$key] = '[0-9]{3}';
			}
				
			foreach ($this->propertiesCategories as $key => $category) {
				$gid = implode('', $sortedProperties);
		
				$properties->where('gid REGEXP ?', '^'.$gid.'$');
		
				foreach ($properties as $p) {
					if ($key == $p->category) {
						$this->categoriesProperties[$p->category][$p->id] = $p->name;
					}
				}
		
				if (count($properties)) {
					if (key_exists($this->properties['category'.$key], $this->categoriesProperties[$key])) {
						$sortedProperties['category'.$key] = str_pad($this->properties['category'.$key], 3, 0, STR_PAD_LEFT);
					}
					else {
						$sortedProperties['category'.$key] = str_pad(key($this->categoriesProperties[$key]), 3, 0, STR_PAD_LEFT);
					}
				}
		
				$array = array_flip($this->categoriesProperties[$key]);
		
				if (!in_array($this->properties['category'.$key], $array)) {
					$this->properties['category'.$key] = key($this->categoriesProperties[$key]);
				}
			}
			
		}
		
		public function handleChangeProperties () {
			$values = $_GET;
				
			unset($values['do']);
			$this->properties = $values;
				
			$this->getCategoriesProperties();
				
			// 			$this->invalidateControl('price');
			$this->invalidateControl();
				
			$this['addToCartForm']->setValues($this->properties);
		}
	} 