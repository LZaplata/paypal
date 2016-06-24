<?php
	namespace FrontEshopModule;

	use Nette\Utils\Html;

	use Nette\Application\UI\Form;
	
	use Nette\Forms\Rendering\BootstrapFormRenderer;
	
	use Nette\Latte\Engine;

	use Nette\Templating\FileTemplate;
	
	use Nette\Mail\Message;	

	class UsersPresenter extends BasePresenter {
		public $account;
		public $orders;
		public $order;
		
		public function startup() {
			parent::startup();

			$this->template->keywords = "uživatel";
			$this->template->desc = "uživatel";
			$this->template->homepage = false;
			$this->template->title_addition = $this->vendorSettings->title_editors;
		}
		
		public function actionEdit ($id) {
			if (!$this->user->loggedIn) {
				$this->error('', 403);
			}
			
			$this->account = $this->model->getUsers()->wherePrimary($this->user->id);
		}
		
		public function actionChangePassword () {
			if (!$this->user->loggedIn) {
				$this->error('', 403);
			}
				
			$this->account = $this->model->getUsers()->wherePrimary($this->user->id);
		}
		
		public function actionAvatar () {
			if (!$this->user->loggedIn) {
				$this->error('', 403);
			}
				
			$this->account = $this->model->getUsers()->wherePrimary($this->user->id);		
		}
		
		public function actionOrders () {
			if (!$this->user->loggedIn) {
				$this->error('', 403);
			}
			
			$this->orders = $this->model->getOrders()->where('users_id', $this->user->id)->where('state != ?',-1)->order('date DESC');
		}
		
		public function actionOrder ($id) {
			$this->order = $this->model->getOrders()->wherePrimary($id)->fetch();
			
			if ($this->order->users_id != $this->user->id) {
				$this->error('', 403);
			}
		}
		
		public function actionRegistration (){
			if ($this->user->loggedIn) {
				$this->error('', 403);
			}		
		}
		
		public function renderRegistration (){		
			$this->template->title = "Registrace uživatele";
			$this->template->desc = "Registrace uživatele";			
		}

		public function renderEdit () {
			$this->template->title = "Editace uživatele";
			$this->template->desc = "Editace uživatele";
		}
		
		public function renderOrders () {
			$this->template->title = "Objednávky uživatele";
			$this->template->desc = "Objednávky uživatele";			
			$this->template->orders = $this->orders;
		}
		
		public function renderOrder () {
			$this->template->title = "Objednávka č.";
			$this->template->desc = "Objednávka č.";			
			$this->template->order = $this->order;
			$this->template->client = $this->user->identity;
			$this->template->defaultLang = $this->getDefaultLang();
		}
		
		public function renderPassword () {
			$this->template->title = "Zapomenuté heslo";
			$this->template->desc = "Zapomenuté heslo";			
		}
		
		
		public function renderChangePassword () {
			$this->template->title = "Změna hesla";
			$this->template->desc = "Změna hesla";			
		}
		
		public function renderAvatar () {
			$this->template->title = "Změna avatara";
			$this->template->desc = "Změna avatara";			
		}

		public function createComponentAvatar ($name) {
			return new Avatar($this, $name);
		}

		public function createComponentChangePasswordForm () {
			$form = new Form();	
			
			$form->getElementPrototype()->class('form-horizontal');
					
			$form->addPassword('password', 'Heslo:')
			->addRule(Form::FILLED, 'Vyplňte heslo!');
				
			$form->addPassword('password2', 'Heslo znovu:')
			->addConditionOn($form['password'], Form::FILLED)
			->addRule(Form::FILLED, 'Vyplňte znovu heslo!')
			->addRule(Form::EQUAL, 'Hesla se neshodují!', $form['password']);
			
			$form->addSubmit('edit', 'Změnit heslo');
				
			$form->onSuccess[] = callback ($this, 'changePassword');
			
			$form->setRenderer(new BootstrapFormRenderer());			

			return $form;
		}
		
		public function changePassword($form){
			$values = $form->getValues();

			if (!empty($values['password'])) {
				$values['password'] = hash('sha512', $values['password']);
				unset($values['password2']);
				$this->account->update($values);
				$this->flashMessage('Vaše heslo bylo úspěšně změněno.');
			}
			
			$this->redirect('this');	
		}
		
		public function createComponentRegistrationForm () {
			$form = new Form();
			$form->getElementPrototype()->class('form-horizontal');
			$form->addGroup('Osobní údaje');
			$form->addText('name', 'Jméno:')
				->setRequired('Vyplňte jméno!');
			
			$form->addText('surname', 'Příjmení:')
				->setRequired('Vyplňte příjmení!');
			
// 			$form->addText('company', 'Firma (nepovinné):');
			
// 			$form->addText('ic', 'IČ (nepovinné):');

// 			$form->addText('dic', 'DIČ (nepovinné):');
			
// 			$form->addText('street', 'Ulice:')
// 				->setRequired('Vyplňte ulici!');
			
// 			$form->addText('city', 'Město:')
// 				->setRequired('Vyplňte město!');
			
// 			$form->addText('psc', 'PSČ:')
// 				->setRequired('Vyplňte PSČ!');
			
// 			$form->addText('phone', 'Telefon:')
// 				->setRequired('Vyplňte telefon!');
	
// 			$form->addGroup('Údaje pro doručení (nevyplňovat, pokud jsou stejné jako fakturační)');
// 			$form->addText('delivery_name', 'Jméno:');
				
// 			$form->addText('delivery_surname', 'Příjmení:');
			
// 			$form->addText('delivery_street', 'Ulice:');
				
// 			$form->addText('delivery_city', 'Město:');
				
// 			$form->addText('delivery_psc', 'PSČ:');				
			
			$form->addGroup('Přihlašovací údaje');
			$form->addText('email', 'E-mail')
				->setRequired('Vyplňte prosím e-mail!')
				->addRule(Form::EMAIL, 'Nesprávný formát e-mailu');
			
			$form->addPassword('password', 'Heslo:')
				->setRequired('Vyplňte heslo!');
			
			$form->addPassword('password2', 'Heslo znovu:')
				->setRequired('Vyplňte heslo!')			
				->addConditionOn($form['password'], Form::FILLED)
					->addRule(Form::FILLED, 'Vyplňte znovu heslo!')
					->addRule(Form::EQUAL, 'Hesla se neshodují!', $form['password']);	
			
			$form->addGroup()
				->setOption('container', 'fieldset class=last');
			$form->addSubmit('submit', 'Registrovat');			
		
			$form->onSuccess[] = callback ($this, 'addUser');			
			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}
		
		public function createComponentEditUserForm () {
			$form = new Form();
			
			$form->getElementPrototype()->class('form-horizontal');
			
			$form->addGroup('Fakturační údaje');
			$form->addText('name', 'Jméno:')
				->setRequired('Vyplňte jméno!');
			
			$form->addText('surname', 'Příjmení:')
				->setRequired('Vyplňte příjmení!');
			
			$form->addText('company', 'Firma (nepovinné):');
			
			$form->addText('ic', 'IČ (nepovinné):');

			$form->addText('dic', 'DIČ (nepovinné):');
			
			$form->addText('street', 'Ulice:')
				->setRequired('Vyplňte ulici!');
			
			$form->addText('city', 'Město:')
				->setRequired('Vyplňte město!');
			
			$form->addText('psc', 'PSČ:')
				->setRequired('Vyplňte PSČ!');
			
			$form->addText('phone', 'Telefon:')
				->setRequired('Vyplňte telefon!');
	
			$form->addGroup('Údaje pro doručení (nevyplňovat, pokud jsou stejné jako fakturační)');
			$form->addText('delivery_name', 'Jméno:');
				
			$form->addText('delivery_surname', 'Příjmení:');
			
			$form->addText('delivery_street', 'Ulice:');
				
			$form->addText('delivery_city', 'Město:');
				
			$form->addText('delivery_psc', 'PSČ:');
					
			$form->addSubmit('edit', $this->account ? 'Upravit údaje' : 'Registrovat');
			
			$form->onSuccess[] = callback ($this, $this->account ? 'editUser' : 'addUser');

			$form->setRenderer(new BootstrapFormRenderer());			
			
			if ($this->user->loggedIn) {
				$values = $this->account->fetch();
				$form->setValues($values);
			}
			
			return $form;
		}
		
		public function addUser ($form) {
			$values = $form->values;
			$password = $values['password'];
			$values['role'] = 'user';
			
			if (!empty($values['password'])) {
				$values['password'] = hash('sha512', $values['password']);
			}
			
			if ($this->model->getUsers()->where('email', $values['email'])->where('password IS NOT NULL')->fetch()) {
				$form->addError(Html::el('span')->setText($this->translator->translate('Uvedený email se již v databázi nachází. '))->add(Html::el('a')->href($this->link(':FrontEshop:Users:password'))->setText($this->translator->translate('Zapomněli jste heslo?'))->class('alert-link')));
			}
			else {
				unset($values['password2']);
				
				if ($user = $this->model->getUsers()->where('email', $values['email'])->where('password IS NULL')->fetch()) {
					if ($user->role != 'user') {
						unset($values['role']);
					}
						
					$user->update($values);
				}
				else {
					$this->model->getUsers()->insert($values);
				}
				
				$this->flashMessage('Registrace proběhla úspěšně. Jste přihlášen/a');
				$this->user->login($values['email'], $password);

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

				$this->sendRegistrationEmail($values);


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

				$this->redirect(':FrontEshop:Homepage:');
			}
		}
		
		public function editUser ($form) {
			$values = $form->values;
						
			$this->account->update($values);
			
			$this->flashMessage('Vaše informace byly uloženy');
			$this->redirect('this');
		}
		
		public function getMethodName ($id) {
			return $this->model->getShopMethods()->wherePrimary($id)->fetch()->name;
		}
		
		/**
		 * Komponenta pro vykreslení formuláře zapomenutého hesla
		 * @return \Nette\Application\UI\Form
		 */
		public function createComponentPassword () {
			$form = new Form();
			
			$form->getElementPrototype()->class('form-horizontal');
							
			$form->addGroup('Zapomenuté heslo');
			$form->addText('email', 'E-mail')
				->setRequired('Vyplňte prosím e-mail!')
				->addRule(Form::EMAIL, 'Nesprávný formát e-mailu');
				
			$form->addSubmit('send', 'Odeslat heslo');
			
			$form->setRenderer(new BootstrapFormRenderer());			
				
			$form->onSuccess[] = callback($this, 'sendPassword');
				
			return $form;
		}
		
		/**
		 * Funkce pro vygenerování nového hesla a uložení do db
		 * @param \Nette\Application\UI\Form $form
		 */
		public function sendPassword ($form) {
			$values = $form->values;
		
			if ($this->account = $this->model->getUsers()->where('email', $values['email'])->fetch()) {
				$password = '';

				//náhodné vygenerování hesla z ASCII tabulky
				for ($i=1; $i<= 8; $i++) {
					switch (rand(0, 2)) {
						case 1:
							$password .= chr(rand(48, 57));
							break;
						case 2:
							$password .= chr(rand(65, 90));
							break;
						default:
							$password .= chr(rand(97, 122));
							break;
					}
				}
		
				$this->sendPasswordEmail($password);
		
				$this->account->update(array('password' => hash('sha512', $password)));
		
				$this->flashMessage('Nové heslo bylo odesláno');
				$this->redirect(':FrontEshop:Homepage:');
			}
			else {
				$form->addError('Zadaný e-mail se v databázi nenachází!');
			}
		}
		
		/**
		 * Funkce pro odeslání nově vygenerovaného hesla na mail
		 * @param string $password
		 */
		public function sendPasswordEmail ($password) {
			$template = $this->createTemplate();
			$template->setFile(APP_DIR.'/FrontModule/EshopModule/templates/Users/passwordEmail.latte');
			$template->password = $password;
				
			$mail = new Message();
			$mail->setFrom($this->contact->email, $this->contact->name);
			$mail->addTo($this->account->email, $this->account->name.' '.$this->account->surname);
			$mail->setSubject('Nové heslo');
			$mail->setHtmlBody($template);
			$this->presenter->mailer->send($mail);
		}

		public function sendRegistrationEmail($values)
		{
			$latte = new Engine();

			$mail = new Message();
			$mail->setFrom($this->contact->email, $this->contact->name);
			$mail->addTo($values["email"]);
			$mail->setSubject("Potvrzení registrace expresmenu.cz");
			$mail->setHtmlBody($latte->renderToString(APP_DIR . "/FrontModule/EshopModule/templates/Users/registrationEmail.latte", (array)$values));

			$this->mailer->send($mail);
		}
	}