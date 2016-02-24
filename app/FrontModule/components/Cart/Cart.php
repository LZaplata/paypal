<?php
	namespace FrontEshopModule;

	use Nette\Forms\Rendering\BootstrapFormRenderer;

	use Nette\Utils\Html;

	use Nette\Latte\Engine;

	use Nette\Utils\Strings;

	use Nette\Application\UI\Form;

	use Nette\Application\UI\Control;

	class Cart extends Control {
		public $products;
		public $order;
		public $tempOrder;
		public $price;
		public $priceWithTax;
		public $id;
		public $currency;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
			
			$this->order = $this->presenter->context->session->getSection('order');
			$this->order->setExpiration('15 minutes');
			
			$this->currency = $this->presenter->parameters['currency'] == null ? 'czk' : $this->presenter->parameters['currency'];
			
			if (!$this->presenter->user->loggedIn) {
				$this->order->products;
				$this->order->currency = $this->presenter->parameters['currency'];
				$this->order->rate = $this->presenter['currencies']->rates[$this->presenter->parameters['currency']];
				$this->order->price;
			}
									
			$this->getOrder();
			
			if ($this->tempOrder) {
				if ($this->tempOrder->currency != $this->currency) {
					$this->updateOrder();
					$this->getOrder();
				}
			}
			
			$this->getProducts();
		}
		
		public function getProduct ($id) {
			return $this->presenter->model->getProducts()->wherePrimary($id)->fetch();
		}
		
		public function getProducts () {		
			$this->price = 0;
			$this->priceWithTax = 0;
			
			if ($this->presenter->user->loggedIn) {
				$this->products = $this->presenter->model->getOrdersProducts()->select('products.*, products.name AS productName, products.amount AS availableAmount, products.products_id AS productsID, orders.*, orders.price AS total, orders_products.*')->where('orders_id', $this->order->id ? $this->order->id : null);	
			}
			else {
				$this->products = isset($this->order->products) ? $this->order->products : array();
			}
						
			foreach ($this->products as $product) {
				$rate = $this->presenter->user->loggedIn ? $product->rate : $this->order->rate;
				
				if (!$this->presenter->user->loggedIn) {
					$product->trash = $this->presenter->model->getProducts()->wherePrimary($product->products_id)->fetch()->trash;
				}
				
				if (!$product->trash) {
					$this->price += ($product->price / $rate) * $product->amount;
					$this->priceWithTax += (($product->price + ($product->price / 100 * $product->tax)) / $rate) * $product->amount;
				}
			}
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/cart.latte');

			$this->template->price = $this->price;
			$this->template->currency = $this->currency == 'czk' ? $this->presenter->context->parameters['currency'] : $this->presenter->currency;
			$this->template->decimals = $this->currency == 'czk' ? 0 : 2;
			$this->template->eshop = $this->presenter->model->getPagesModules()->where('modules_id', 3)->where('position', 1)->where('pages_id != ?', 1)->fetch()->pages->url;
			
			$this->template->setTranslator($this->presenter->translator);
			$this->template->render();
		}	
		
		public function renderAddToCart ($id) {
			$this->id = $id;
			
			$this->template->setFile(__DIR__.'/addToCart.latte');
			
			$this->template->render();
		}
		
		public function createComponentAddToCart () {
			$form = new Form();
				
			$form->getElementPrototype()->class('ajax form-horizontal');
				
			$form->addText('amount', '')
				->setRequired()
				->addRule(Form::PATTERN, 'Množství musí být větší než 0', '[1-9]{1}[0-9]*')
				->addRule(Form::INTEGER, 'Množství musí být číslo!')
				->setValue(1);
				
			$form->addSubmit('add', 'Vložit');
				
			$form->addHidden('id', $this->id);
				
			$form->onSuccess[] = callback($this, 'handleAddToCart');
			
			$form->setRenderer(new BootstrapFormRenderer());
				
			return $form;
		}
		
		public function handleAddToCart ($form) {
			$values = isset($_GET['id']) ? $_GET : $form->values;		
			$product = $this->getProduct($values['id']);
			$cartProduct = $this->isProductInCart($values['id']);
			$amount = !$cartProduct ? $values['amount'] : (isset($values['update']) ? $values['amount'] : $cartProduct->amount + $values['amount']);
	
			$data['price'] = $this->getProductDiscountPrice($product->id, $product->price, $amount, false, false);
	
			if ($this->presenter->user->loggedIn) {
				if (!$this->order->id || $this->order->id == null) {
					$this->createOrder($data);
				}
				else {
					$this->updateOrder();
				}
			}
			
			$data['products_id'] = $product->id;
			$data['orders_id'] = $this->order->id;
			$data['amount'] = $amount;
			
			if ($this->presenter->user->loggedIn) {
				if ($cartProduct) {
					$p = $this->presenter->model->getOrdersProducts()->where('id', $cartProduct->id)->where('orders_id', $this->order->id);
					
					if ($amount == 0) {
						 $p->delete();
					}
					elseif ($amount >= 1) {
						$p->update($data);
					}
				}
				else {
					$this->presenter->model->getOrdersProducts()->insert($data);
				}
			}
			else {
				$name = 'name'.$this->presenter->lang;
				$defaultName = 'name'.$this->presenter->getDefaultLang();
				
				$data['productName'] = $product->$name ?: ($product->$defaultName ?: $product->name);
				$data['tax'] = $product->tax;
				$data['pid'] = $product->pid;
				
				if ($amount == 0) {
					if ($cartProduct) unset($this->order->products[$product->id]);
				}
				elseif ($amount >= 1) {
					$this->order->products[$product->id] = (object) $data;
				}
			}
	
			$this->getProducts();
			$this->updateOrder();
			
			if ($this->presenter->user->loggedIn) {
				$this->getOrder();
			}
			
			$this->invalidateControl('cart');
			$this->presenter->invalidateControl('cartView');
			
			/** popup okénko */
			$template = $this->createTemplate();
			$template->setFile(__DIR__.'/flashMessage.latte');
// 			$template->registerFilter(new Engine());
			$template->product = $product;
			$template->thumb = $this->presenter->model->getSectionsThumbs()->where('sections_id', 0)->where('place', array(0,1))->fetch();
			$template->image = $product->galleries->related('galleries_images')->fetch();
			$template->amount = $amount;
			$template->price = $this->getProductDiscountPrice($product->id, $product->price, $amount, false, true);
			$template->currency = $this->presenter->currency == 'czk' ? $this->presenter->context->parameters['currency'] : $this->currency;
			$template->decimals = $this->presenter->currency == 'czk' ? 0 : 2;
			$template->defaultLang = $this->presenter->getDefaultLang();
			$template->setTranslator($this->presenter->translator);	
			
			if (!isset($_GET['id'])) {
				$this->presenter->flashMessage(Html::el('div')->setHtml($template->__toString()), 'popup');
			}			
		}
		
		public function isProductInCart ($id) {
			if ($this->presenter->user->loggedIn) {
				$products = $this->products;
				
				if ($product = $products->where('orders_products.products_id', $id)->fetch()) {
	 				return $product;
	 			}
	 			else {
	 				return false;
	 			}
			}
			else {
				if (isset($this->order->products[$id])) {
					return (object) $this->order->products[$id];
				}
				else {
					return false;
				}
			}
		}
		
		public function getOrder() {
			$values['state'] = -1;
			$values['users_id'] = $this->presenter->user->id;
			
			if ($this->order->id && $this->order->id != null) {
				$values['id'] = $this->order->id;
			}
				
			if ($this->tempOrder = $this->presenter->model->getOrders()->where($values)->order('date DESC')->fetch()) {
				$this->order->id = $this->tempOrder->id;
			}
			else {
				$values['users_id'] = null;
		
				if ($this->presenter->user->loggedIn && $this->tempOrder = $this->presenter->model->getOrders()->where($values)->order('date DESC')->fetch()) {
					$this->order->id = $this->tempOrder->id;
					$data['users_id'] = $this->presenter->user->id;
						
					$this->updateOrderProducts();
					$this->updateOrder($data);
				}
				else $this->order->id = null;
			}
		}
		
		public function createOrder ($values = array()) {
			$values['users_id'] = $this->presenter->user->id;
			$values['state'] = -1;
			$values['currency'] = $this->presenter->parameters['currency'];
			$values['rate'] = $this->presenter['currencies']->rates[$this->presenter->parameters['currency']];
			$values['date'] = date('Y-m-d H:i:s');
				
			$order = $this->presenter->model->getOrders()->insert($values);
			$this->order->id = $order->id;
		}
		
		public function updateOrder ($values = array()) {
			if ($this->presenter->user->loggedIn) {
				$order = $this->presenter->model->getOrders()->wherePrimary(isset($values['id']) ? $values['id'] : $this->order->id)->fetch();
				$values['currency'] = isset($values['currency']) ? $values['currency'] : $this->presenter->parameters['currency'];
				$values['rate'] = $this->presenter['currencies']->rates[$values['currency']];
				$values['price'] = 0;
				$values['date'] = date('Y-m-d H:i:s');
					
				if ($order) {
					if ($values['currency'] != $order->currency) {
						$values['transport_id'] = 0;
						$values['payment_id'] = 0;
					}
			
					foreach ($order->related('orders_products') as $product) {
						if (!$product->products->trash) {
							$values['price'] += $product->price * $product->amount;
						}
					}
					$values['price'] = round($values['price'], 2);
						
					$order->update($values);
				}
			}
			else {
				$this->order->price = 0;
				
				foreach ($this->order->products as $product) {
					if ($this->presenter->model->getProducts()->wherePrimary($product->products_id)->where('trash', 0)->fetch()) {
						$this->order->price += $product->amount * $product->price;
					}
				}
			}
		}
		
		public function updateOrderProducts () {
			$products = $this->presenter->model->getOrdersProducts()->where('orders_id', $this->order->id);
				
			foreach ($products as $product) {
				$values['price'] = round($this->getProductDiscountPrice($product->products->id, $product->products->price, $product->amount, false, false), 3);
		
				$product->update($values);
			}
		}
		
		public function getProductDiscountPrice ($id, $price, $amount, $tax = false, $rate = true) {
			$product = $this->getProduct($id);
			$currency = $this->presenter->parameters['currency'] == null ? 'czk' : $this->presenter->parameters['currency'];
			$section = $this->presenter->model->getShopSettings()->order('id ASC')->fetch();
			
			if ($section->discounts) {				
				if ($this->presenter->context->parameters['discount'] == 1) {
					$category = $this->presenter->model->getProductsCategories()->select('*, categories.*')->where('products_id', $id)->where('categories.discount IS NOT NULL')->fetch();
					
					if ($discount = $this->presenter->model->getProductsDiscounts()->where('products_id', $id)->where('amount <= ?', $amount)->where('discount != ?', 0)->order('amount DESC')->fetch()) {
						$price = (100 - $discount->discount) / 100 * $product->price;
					}
					elseif ($category && $category->discount != 0) {
						$price = (100 - $category->discount) / 100 * $product->price;
					}
					elseif ($this->presenter->user->loggedIn) {
						if ($userCategory = $this->presenter->model->getUsersCategories()->select('*, categories.*')->where('users_id', $this->presenter->user->id)->where('categories.sections_id', -3)->fetch()) {
							$price = (100 - $userCategory->discount) / 100 * $product->price;
						}
					}
				}
				else {
					$category = $this->presenter->model->getProductsCategories()->select('*, categories.*')->where('products_id', $id)->where('categories.discount IS NOT NULL')->fetch();
					$d = 0;
					
					if ($discount = $this->presenter->model->getProductsDiscounts()->where('products_id', $id)->where('amount <= ?', $amount)->where('discount != ?', 0)->order('amount DESC')->fetch()) {
						$d += $discount->discount;
					}
					elseif ($category && $category->discount != 0) {
						$d += $category->discount;
					}
					
					if ($this->presenter->user->loggedIn) {
						if ($userCategory = $this->presenter->model->getUsersCategories()->select('*, categories.*')->where('users_id', $this->presenter->user->id)->where('categories.sections_id', -3)->fetch()) {
							$d += $userCategory->discount;
						}
					}
					
					$price = (100 - $d) / 100 * $product->price;
				}
			}		
			
			if ($product->price_discount) {
				if ($product->price_discount < $product->price) {
					$price = $product->price_discount;
				}
			}
				
			$price = $rate ? $price / $this->presenter['currencies']->rates[$currency] : $price;
				
			return $tax ? $price / ((100 + $product->tax) / 100) : $price;
		}
	}