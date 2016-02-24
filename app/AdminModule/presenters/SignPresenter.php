<?php
	namespace AdminModule;
	
	
	use FrontEshopModule\Cart;

	use Nette\Forms\Rendering\BootstrapFormRenderer;

	use Nette\Security\AuthenticationException;

	use Nette\Application\UI\Form;

	/**
	 * Presenter pro přihlášení a odhlášení uživatelů
	 */
	class SignPresenter extends \BasePresenter {
		/** @var \WebLoader\Nette\LoaderFactory @inject */
		public $webLoader;
		
		
		
		/**
		 * Componenta přihlašovacího formuláře
		 * @return Nette\Application\UI\Form
		 */
		public function createComponentSignInForm(){
			$form = new Form();
			
			$form->getElementPrototype()->class('form-horizontal');
			
			$form->addText('email', 'E-mail:')
				->setRequired('Vyplňte prosím e-mail!');
	
			$form->addPassword('password', 'Heslo:')
				->setRequired('Prosím vyplňte heslo!');
	
			$form->addCheckbox('remember', 'Pamatovat přihlášení')
				->setValue(true);
	
			$form->addSubmit('login', 'Přihlásit')
				->onClick[] = callback($this, 'signInFormSubmitted');
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
	
		/**
		 * Callback pro přihlašovací formulář
		 */
	
		public function signInFormSubmitted($button) {
			$form = $button->getParent();
			$values = $form->getValues();
			
			try {
				$values = $form->getValues();
				if ($values->remember) {
					$this->getUser()->setExpiration('+ 14 days', FALSE);
				} 
				else {
					$this->getUser()->setExpiration('+ 20 minutes', TRUE);
				}
				$this->getUser()->login($values->email, $values->password);
				
				$this->flashMessage('Přihlášení proběhlo úspěšně');
				
				if ($this->moduleName != "Admin") {
					/** přenesení košíku nepřihlášeného přihlášenému */
					if ($this['cart']->order && $this['cart']->order->products != null) {
						$order = $this['cart']->order;
						$products = $order->products;
							
						unset($order->id);
						unset($order->products);
					
						$this['cart']->order->remove();
						$this['cart']->createOrder($order);
					
						foreach ($products as $product) {
							$product->orders_id = $this['cart']->order->id;
								
							unset($product->tax);
							unset($product->productName);
							unset($product->trash);
							unset($product->pid);
								
							$this->model->getOrdersProducts()->insert((array)$product);
						}
						
						$this['cart']->updateOrder();
					}
					
					$this->redirect('this');
				}
				else {
					$data['users_id'] = $this->user->id;
					$data['date'] = date('Y-m-d H:i:s');
					
					$this->logsModel->getLoginLog()->insert($data);
					
					$this->redirect('Homepage:default');
				}
	
			} catch (AuthenticationException $e) {
				$form->addError('Neplatné přihlašovací údaje!');
			}
		}
		
		public function createComponentCart ($name) {
			return new Cart($this, $name);
		}
		
		/** @return CssLoader */
		protected function createComponentCss () {
			return $this->webLoader->createCssLoader('admin');
		}
		
		/** @return JavaScriptLoader */
		protected function createComponentJs () {
			return $this->webLoader->createJavaScriptLoader('admin');
		}
	}
?>