<?php
	namespace FrontModule;
	
	class FeedPresenter extends \BasePresenter {
		public $products;
		public $eshop;
		
	public function actionHeureka () {
			$this->products = $this->model->getProductsCategories()
									->where('categories.categories_heureka_id IS NOT NULL')
									->where('products.visibility', 1)
									->where('products.trash', 0)
									->group('products.id');
			$this->eshop = $this->getEshopName();
		}
		
		public function renderHeureka () {
			$this->template->products = $this->products;
			$this->template->eshop = $this->eshop;
		}
		
		public function actionGoogle () {
			$this->products = $this->model->getProductsCategories()
									->where('categories.categories_merchants_id IS NOT NULL')
									->where('products.visibility', 1)
									->where('products.trash', 0)
									->group('products.id');
			$this->eshop = $this->getEshopName();
		}
		
		public function renderGoogle () {
			$this->template->products = $this->products;
			$this->template->eshop = $this->eshop;
		}
		
		public function actionSzbozi () {
			$this->products = $this->model->getProductsCategories()
									->where('categories.categories_zbozi_id IS NOT NULL')
									->where('products.visibility', 1)
									->where('products.trash', 0)
									->group('products.id');
			$this->eshop = $this->getEshopName();
		}
		
		public function renderSzbozi () {
			$this->template->products = $this->products;
			$this->template->eshop = $this->eshop;
		}		
		
		public function getZboziCategory($category){
			if($category->categories_zbozi_id){		
				return $category->categories_zbozi->name;
			}else{
				return "";
			}                                                 
		}
		
		public function getEshopName () {
			if ($eshop = $this->model->getPagesModules()->where('modules_id', 3)->where('position', 1)->fetch()) {
				return $eshop->pages->url;
			}
			else return 'eshop';
		}
		
		public function getProductProperties ($id) {
			$productProperties = $this->model->getProductsProperties()->where('products_id', $id)->fetch();
			$shopProperties = $this->model->getShopProperties();
			$properties = new \StdClass;
				
			foreach ($shopProperties as $property) {
				$p = 'p_'.$property->id;
					
				if ($productProperties->$p) {
					$class = new \StdClass;
					$class->name = $property->categories->name;
					$class->val = $property->name;
					
					$properties->$p = $class;
				}
			}
				
			return $properties;
		}
	}