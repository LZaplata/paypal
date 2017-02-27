<?php
	namespace FrontEshopModule;

	use Latte\Runtime\Filters;
	use Nette\Bridges\ApplicationLatte\ILatteFactory;
	use Nette\Mail\Message;

	use Nette\Templating\FileTemplate;

	use Nette\Latte\Engine;

	use Nette\Utils\Html;

	use Nette\Forms\Rendering\BootstrapFormRenderer;

	use AdminModule\CartProducts;

	use WebPay\WebPay;

	use Nette\Forms\Controls\SubmitButton;

	use Nette\Application\UI\Form;

	class OrderPresenter extends BasePresenter {
		public $products;
		public $transports;
		public $methodPrices;
		public $payments;
		public $transportData;
		public $lastID;
		public $tempOrder;
		public $order;
		public $zasilkovnaBranches;

		/** @var  ILatteFactory @inject */
		public $latteFactory;

		public function beforeRender() {
			if ($this->isLinkCurrent('Order:transport')) {
				$this->getValues();
			}

			$this->template->homepage = false;
			$this->template->title_addition = $this->vendorSettings->title_editors;
		}

		public function renderDefault () {
			$this->template->keywords = "objednávka, košík";
			$this->template->lang = $this->lang == 'cs' ? null : '_'.$this->lang;
			$this->template->settings = $this->settings;
		}

		public function actionContact () {
			if (!count($this['cart']->products) || $this['cart']->price == 0) {
				$this->redirect('Order:cart');
			}
		}

		public function actionTransport () {
			if (!count($this['cart']->products) || $this['cart']->price == 0) {
				$this->redirect('Order:cart');
			}

			if ($this->user->loggedIn) {
				if ($this['cart']->tempOrder->name == null) {
					$this->redirect('Order:contact');
				}
			}
			else {
				if (!isset($this['cart']->order->name)) {
					$this->redirect('Order:contact');
				}
			}

			$shopMethodsRelations = $this->model->getShopMethodsRelations()->select('shop_methods.*');

			if ($this->partner->id) {
				$relation = $this->model->getShopMethodsRelations()->wherePrimary(19)->fetch();
				$values = array();
				$values["transport_id"] = $relation->shop_methods_id;
				$values["payment_id"] = $relation->id_shop_methods;
				$values["transport"] = $relation->price;

				if ($this->user->loggedIn) {
					$this['cart']->tempOrder->update($values);
					$this['cart']->getOrder();
				}
				else {
					foreach ($values as $key => $value) {
						$this['cart']->order->$key = $value;
					}
				}

				$this->redirect("Order:summary");
			} else {
				$shopMethodsRelations = $shopMethodsRelations->where('shop_methods_relations.id != 19');
			}

			$this->transports = $shopMethodsRelations->fetchPairs('id', 'name');
			$this->methodPrices = $this->model->getShopMethods()->select('shop_methods.*')->fetchPairs('id', 'price');
			$this->payments = $this->context->database->query("SELECT shop_methods.id AS id, name FROM shop_methods LEFT JOIN shop_methods_relations ON shop_methods.id = shop_methods_relations.id_shop_methods WHERE type IN (?)", array(1, 2, 4))->fetchPairs('id', 'name');
			$values = $_GET ? $_GET : null;

			$this->unsetMethods($values);
// 			$this->updateTransportPrice();
		}

		public function actionSummary () {
			if (!count($this['cart']->products) || $this['cart']->price == 0) {
				$this->redirect('Order:cart');
			}

			if ($this->user->loggedIn) {
				if ($this['cart']->tempOrder->name == null) {
					$this->redirect('Order:contact');
				}
			}
			else {
				if (!isset($this['cart']->order->name)) {
					$this->redirect('Order:contact');
				}
			}
		}

		public function actionSend () {
			if (!count($this['cart']->products) || $this['cart']->price == 0) {
				$this->redirect('Order:cart');
			}

			$count = count($this->model->getOrders()->where('state >= ?', 0)->where('date >= ?', date('Y').'-01-01 00:00:00')) + 1;
			$no = date('y').str_pad($count, 5, 0, STR_PAD_LEFT);

			if ($this->user->loggedIn) {
				if ($this['cart']->tempOrder->transport_id == null) {
					$this->redirect('Order:transport');
				}

				$this['cart']->tempOrder->update(array('state' => 0, 'date' => date('Y-m-d H:i:s'), 'no' => $no));
			}
			else {
				if (!isset($this['cart']->order->transport_id)) {
					$this->redirect('Order:transport');
				}

				$products = $this['cart']->order->products;

				unset($this['cart']->order->id);
				unset($this['cart']->order->products);

				$this['cart']->order->state = 0;
				$this['cart']->order->date = date('Y-m-d H:i:s');
				$this['cart']->order->no = $no;
				$lastID = $this->model->getOrders()->insert($this['cart']->order);

				foreach ($products as $product) {
					$product->orders_id = $lastID;

					unset($product->tax);
					unset($product->productName);
					unset($product->trash);
					unset($product->pid);

					$this->model->getOrdersProducts()->insert((array)$product);
				}
			}

			$this['cart']->order->remove();
			$this['cart']->getProducts();

			$this->order = $this->model->getOrders()->wherePrimary(isset($lastID) ? $lastID : $this['cart']->tempOrder->id)->fetch();
			$paymentType = $this->model->getShopMethods()->wherePrimary($this->order->payment_id)->fetch();

			// při platbě předem změnit defaultní stav objednávky -
			if ($this->order->payment_id == 4) {
				$this->order->update(array("state" => 2));
			}

			//overeni zakazniky
//			if ($this->vendorSettings->heurekaVerification) {
//				$this->heurekaVerification($this->order);
//			}

			$this->sendOfficeEmail($this->order);
			$this->sendCustomerEmail($this->order, $paymentType);
//			$this->createPdf($this->order);

//			if ($paymentType) {
//				if ($paymentType->type == 2) {
//					$wp = $this->createComponentWebPay();
//
//					$this->redirectUrl($wp->generateLink($this->order->price + $this->order->transport, $this->order->no));
//				}
//			}

			if (isset($this->partner->id)) {
				$this->partner->remove();
			}
		}

		public function actionPayment () {
			if ($this->getParameter('OPERATION')) {
				$wp = $this->createComponentWebPay();
				$response = $wp->getResponse();

				if ($response === true) {
					$order = $this->model->getOrders()->where('no', $this->getParameter('ORDERNUMBER'))->fetch();

					if ($order) {
//						$order->update(array('payed' => 1));
						$order->update(array('state' => 2));

						$this->sendMail($order);
					}
				}

				$this->redirect('this', array('response' => $response));
			}
			else {
// 				if ($this->getParameter('response') == false) {
// 					if (!count($this['cart']->products) || $this['cart']->price == 0) {
// 						$this->redirect('Order:cart');
// 					}
// 				}
			}
		}

		public function renderCart () {
			$this->renderDefault();

			$this->template->title = 'Košík';
			$this->template->keywords = 'Košík';
			$this->template->desc = 'Košík';
			$this->template->currency = $this->currency == 'czk' ? $this->context->parameters['currency'] : $this->currency;
			$this->template->decimals = $this->currency == 'czk' ? 2 : 2;
			$this->template->order = $this->user->loggedIn ? $this['cart']->tempOrder : $this['cart']->order;
		}

		public function renderContact () {
			$this->renderDefault();

			$this->template->title = 'Kontaktní údaje';
			$this->template->keywords = 'Kontaktní údaje';
			$this->template->desc = 'Kontaktní údaje';
		}

		public function renderTransport () {
			$this->renderDefault();

			$this->template->title = 'Doprava a platba';
			$this->template->keywords = 'Doprava a platba';
			$this->template->desc = 'Doprava a platba';
			$this->template->order = $this->user->loggedIn ? $this['cart']->tempOrder : $this['cart']->order;
			$this->template->currency = $this->currency == 'czk' ? $this->context->parameters['currency'] : $this->currency;
			$this->template->decimals = $this->currency == 'czk' ? 2 : 2;
			$this->template->transports = $this->model->getShopMethods()->where('type', 3)->fetchPairs('id', 'name');
			$this->template->methodPrices = $this->methodPrices;
		}

		public function renderSummary () {
			$this->renderDefault();

			$this->template->title = 'Souhrn objednávky';
			$this->template->keywords = 'Souhrn objednávky';
			$this->template->desc = 'Souhrn objednávky';
			$this->template->order = $this->user->loggedIn ? $this['cart']->tempOrder : $this['cart']->order;
			$this->template->currency = $this->currency == 'czk' ? $this->context->parameters['currency'] : $this->currency;
			$this->template->decimals = $this->currency == 'czk' ? 2 : 2;
		}

		public function renderSend () {
			$this->renderDefault();

			$this->template->title = 'Odeslání objednávky';
			$this->template->keywords = 'Odeslání objednávky';
			$this->template->desc = 'Odeslání objednávky';
			$this->template->order = $this->order;
		}

		public function renderPayment () {
			$this->template->response = $this->getParameter('response');
			$this->template->order = $this->order;
		}

		public function getImages ($id, $first = false) {
			if (count($images = $this->model->getProducts()->wherePrimary($id)->fetch()->galleries->related('galleries_images'))) {
				return $first ? $images->fetch() : $images;
			}
			else return false;
		}

		public function getCategory ($id) {
			return $this->model->getProductsCategories()->select('categories.*')->where('products_id', $id)->order('pid DESC')->fetch();
		}

		public function getValues () {
			$order = $this->user->loggedIn ? $this['cart']->tempOrder : $this['cart']->order;

			if (!isset($order->transport_id) || $order->transport_id == null) {
				$relation = $this->model->getShopMethodsRelations()->where('highlight', 1)->fetch();
			}
			else {
				$relation = $this->model->getShopMethodsRelations()->where('shop_methods_id', $order->transport_id)->where('id_shop_methods', $order->payment_id)->fetch();
			}

			$values['transport_id'] = $relation->shop_methods_id;
			$values['payment_id'] = $relation->id_shop_methods;
			$values['transport'] = $order->price > $relation->max ? 0 : $relation->price;
			$values['zasilkovna'] = $order->zasilkovna;

			if ($this->user->loggedIn) {
				$this['cart']->tempOrder->update($values);
				$this['cart']->getOrder();

				$values = $order;
			}
			else {
				foreach ($values as $key => $value) {
					$this['cart']->order->$key = $value;
				}
			}

			return $values;
		}

		public function unsetMethods ($values = false) {
			$values = $values ? $values : $this->getValues();

			$relations = $this->model->getShopMethodsRelations()->where('shop_methods_id', $values['transport_id'])->fetchPairs('id_shop_methods', 'id_shop_methods');
			$this->payments = array_intersect_key($this->payments, $relations);
		}

		public function handleRemoveFromCart ($id) {
			$_GET['id'] = $id;
			$_GET['amount'] = 0;
			$_GET['update'] = true;

			$this['cart']->handleAddToCart($_GET);
		}

		public function handleChangeMethods () {
			$values = $_GET;

			if (isset($values['zasilkovna'])) {
				if ($values['zasilkovna'] == '') {
					$values['zasilkovna'] = null;
				}
			}

			if ($relation = $this->model->getShopMethodsRelations()->where('shop_methods_id', $values['transport_id'])->where('id_shop_methods', $values['payment_id'])->fetch()) {
				$values['transport'] = $relation->price;
			}
			else {
				$relation = $this->model->getShopMethodsRelations()->where('shop_methods_id', $values['transport_id'])->fetch();
				$values['payment_id'] = $relation->id_shop_methods;
				$values['transport'] = $relation->price;
			}

			unset($values['do']);

			if ($this->user->loggedIn) {
				$this['cart']->tempOrder->update($values);
				$this['cart']->getOrder();
			}
			else {
				foreach ($values as $key => $value) {
					$this['cart']->order->$key = $value;
				}
			}

			$this->invalidateControl('transport');
			$this->invalidateControl('transportPrice');
			$this->invalidateControl('totalPrice');
		}

		public function createComponentContact () {
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

			$form->addText("street_number", "Číslo popisné:")
				->setRequired("Vyplňte číslo popisné");

			$form->addText('city', 'Město:')
				->setRequired('Vyplňte město!');

			$form->addText('psc', 'PSČ:')
				->addRule(Form::PATTERN, 'PSČ musí být ve formátu např. 12345', '[0-9]{5}')
				->setRequired('Vyplňte PSČ!')
				->setOption("description", "PSČ musí být ve formátu např. 12345");

			$form->addText('phone', 'Telefon:')
				->addRule(Form::PATTERN, 'Telefon musí být ve formátu např. 600700800', '[0-9]{9}')
				->setRequired('Vyplňte telefon!')
				->setOption("description", "Telefon musí být ve formátu např. 600700800");

			$form->addText('email', 'E-mail:')
				->setRequired('Vyplňte e-mail!')
				->addRule(Form::EMAIL, 'Nesprávný formát e-mailu!');

			if(isset($this->partner->id)) {
				$form->addGroup('Váš dodavatel');

				$form->addText('delivery_name', 'Společnost:')->setDisabled(true);
				$form->addText('delivery_surname', 'E-mail:')->setDisabled(true);

			} else {
				$form->addGroup('Údaje pro doručení (nevyplňovat, pokud jsou stejné jako fakturační)');

				$form->addText('delivery_name', 'Jméno:');
				$form->addText('delivery_surname', 'Příjmení:');
			}

			$form->addText('delivery_street', 'Ulice:')
				->setDisabled(isset($this->partner->id) ? true : false);

			$form->addText("delivery_street_number", "Číslo popisné:")
				->setDisabled(isset($this->partner->id) ? true : false);

			$form->addText('delivery_city', 'Město:')
				->setDisabled(isset($this->partner->id) ? true : false);

			$form->addText('delivery_psc', 'PSČ:')
				->setDisabled(isset($this->partner->id) ? true : false);

			$form->addGroup('Doplňující údaje');
			$form->addTextArea('text', 'Poznámka:');

			$form->addGroup()
				->setOption('container', 'fieldset class=last');
			$form->addSubmit('transport')
				->getControlPrototype()
				->setName("button")
				->setHtml($this->translator->translate("pokračovat v objednávce")." <span class='fa-stack'><i class='fa fa-circle fa-stack-2x'></i><i class='fa fa-arrow-right fa-stack-1x fa-inverse'></i></span>");

			$form["transport"]->onClick[] = callback($this, 'submitContact');

			$order = $this->user->loggedIn ? $this['cart']->tempOrder : $this['cart']->order;

			if (isset($order->name) && $order->name == null || !isset($order->name)) {
				if ($this->user->loggedIn) {
					$form->setValues($this->model->getUsers()->wherePrimary($this->user->id)->fetch());
				}
			}
			else $form->setValues($order);

			if (isset($this->partner->id)) {
				$form->addHidden("partner_id", $this->partner->id);
				$form->addHidden("beg", $this->partner->beg);
				$form->addHidden("fta", $this->partner->fta);

				$form->setValues($this->context->parameters["partners"][$this->partner->id]);
			}

			$form->setRenderer(new BootstrapFormRenderer());
			$form->setTranslator($this->translator);

			return $form;
		}

		public function submitContact ($button) {
			$values = $button->parent->values;

			if ($this->user->loggedIn) {
				unset($values["beg"]);
				unset($values["fta"]);

				$this['cart']->tempOrder->update($values);

				$user = $this->model->getUsers()->wherePrimary($this->user->id)->fetch();

				if (!$this->user->identity->street) {
					unset($values['text']);
					unset($values["partner_id"]);

					$data = array_diff((array)$values, $user->toArray());

					$user->update($data);
				}
			}
			else {
				foreach ($values as $key => $value) {
					$this['cart']->order->$key = $value;
				}

				if (!$this->model->getUsers()->where('email', $values['email'])->fetch()) {
					unset($values['text']);
					unset($values["beg"]);
					unset($values["fta"]);
					unset($values["partner_id"]);

					$values['role'] = 'user';
					$lastID = $this->model->getUsers()->insert($values);

					$this['cart']->order->users_id = $lastID->id;
				}
			}

			$this->redirect('Order:transport');
		}

		public function createComponentTransport () {
			$values = $this->getValues();
			$form = new Form();

			$form->getElementPrototype()->class('form-horizontal');

			$form->addRadioList('transport_id', 'Doprava', $this->transports)
				->setRequired('Vyberte druh přepravy!')
				->getLabelPrototype()->class('transport');

			$form->addRadioList('payment_id', 'Platba', $this->payments)
				->setRequired('Vyberte druh platby')
				->getLabelPrototype()->class('payment');

			if (key_exists($values->transport_id, $this->template->transports)) {
				$this->getZasilkovnaBranches();

				$form->addSelect('zasilkovna', null, $this->zasilkovnaBranches)
					->setPrompt('--Vyberte pobočku--');
			}

			$form->setRenderer(new BootstrapFormRenderer());

			$form->setValues($values);

			return $form;
		}

		public function createComponentDynamicForm () {
			/*
			 $temp = $this->model->getFormGroupsFields()->select('form_fields.*')->where('form_groups_id', array_values(
			 		$this->model->getProductsFormGroups()->where('products_id', $this->pid)->fetchPairs('id', 'form_groups_id')
			 ))->fetchAll();

			foreach ($temp as $field) {
			dump($field);
			}
			*/
		}

		public function submitDynamicForm() {

		}

		public function createComponentWebPay () {
			$wp = new WebPay();

			$wp->setUrl('https://3dsecure.gpwebpay.com/kb/order.do');

			$wp->setMerchantNumber(9672957009);

			$wp->setCurrency(203);

			$wp->setPublicKey(APP_DIR.'/FrontModule/components/WebPay/keys/muzo.signing_prod.pem');

			$wp->setPrivateKey(APP_DIR.'/FrontModule/components/WebPay/keys/private_key.pem', 'Exmenu5');

			$wp->setRedirectUrl('http://www.expresmenu.cz/e-shop/order/payment');

			return $wp;
		}

		public function getParentProduct ($id) {
			$product = $this->model->getProducts()->where('products_id', $id)->fetch();

			if ($product->pid != null) {
				$product = $this->model->getProducts()->wherePrimary($product->pid)->fetch();
			}

			return $product;
		}

// 		public function createComponentAddToCart ($product) {
// 			$form = new Form();

// 			$form->addText('amount')
// 				->setValue($product->amount)
// 				->addRule(Form::PATTERN, 'Musí obsahovat číslici', '[0-9].*')
// 				->getControlPrototype()->class('form-control');

// 			$form->addHidden('id', $product->products_id);
// 			$form->addHidden('update');

// 			return Html::el('div')->class('cartProduct col-sm-5 row form'.$product->products_id)
// 				->add($form['amount']->control)
// 				->add($form['id']->control)
// 				->add($form['update']->control);
// 		}

//		public function sendCustomerEmail ($order, $paymentType) {
//// 			$template = new FileTemplate(APP_DIR.'/FrontModule/EshopModule/templates/Order/customerEmail.latte');
//			$template = new FileTemplate(APP_DIR.'/AdminModule/EshopModule/templates/Orders/StatesEmails/state' . $order->state . '.latte');
//			$template->registerFilter(new Engine());
//			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
//			$template->order = $order;
//			$template->paymentType = $paymentType->type;
//			$template->presenter = $this;
//			$template->host = $this->context->parameters['host'];
//			$template->currency = $order->currency == 'czk' ? $this->context->parameters['currency'] : $order->currency;
//			$template->decimals = $this->currency == 'czk' ? 2 : 2;
//			$template->methods = $this->model->getShopMethods()->fetchPairs('id', 'name');
//			$template->lang = $this->lang;
//			$template->defaultLang = $this->getDefaultLang();
////			$template->setTranslator($this->translator);
//
//			$mail = new Message();
//			$mail->setFrom($this->contact->email, $this->contact->name);
//			$mail->addTo($order->email, $order->name.' '.$order->surname);
//			if ($order->partner_id) {
//				$mail->addTo("info@rybolovnorsko.com");
//			}
//			$mail->setSubject('ExpresMenu.pl – nowe zamówienie nr '.$order->no);
//			$mail->setHtmlBody($this->translator->translate($template->__toString()));
//
//			$this->mailer->send($mail);
//		}

		public function sendCustomerEmail ($order, $paymentType) {
			$file = APP_DIR.'/AdminModule/EshopModule/templates/Orders/StatesEmails/state' . $order->state . '.latte';
			$latte = $this->latteFactory->create();
			$latte->addFilter('translate', $this->translator === NULL ? NULL : array($this->translator, 'translate'));
			$params = array(
				"order" => $order,
				"paymentType" => $paymentType->type,
				"p" => $this,
				"host" => $this->context->parameters['host'],
				"currency" => $order->currency == 'czk' ? $this->context->parameters['currency'] : $order->currency,
				"decimals" => $this->currency == 'czk' ? 2 : 2,
				"methods" => $this->model->getShopMethods()->fetchPairs('id', 'name'),
				"lang" => $this->lang,
				"defaultLang" => $this->getDefaultLang()
			);

			$mail = new Message();
			$mail->setFrom($this->contact->email, $this->contact->name);
			$mail->addTo($order->email, $order->name.' '.$order->surname);
			if ($order->partner_id) {
				$mail->addTo("info@rybolovnorsko.com");
			}
			$mail->setSubject($this->translator->translate('ExpresMenu.pl – nowe zamówienie nr')." ".$order->no);
			$mail->setHtmlBody($latte->renderToString($file, $params));

			$this->mailer->send($mail);
		}

		public function sendOfficeEmail ($order) {
			$template = new FileTemplate(APP_DIR.'/FrontModule/EshopModule/templates/Order/officeEmail.latte');
			$template->registerFilter(new Engine());
			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
			$template->order = $order;
			$template->presenter = $this;
			$template->host = $this->context->parameters['host'];
			$template->currency = $order->currency == 'czk' ? $this->context->parameters['currency'] : $order->currency;
			$template->decimals = $this->currency == 'czk' ? 2 : 2;
			$template->methods = $this->model->getShopMethods()->fetchPairs('id', 'name');
			$template->lang = $this->lang;
			$template->defaultLang = $this->getDefaultLang();

			$mail = new Message();
			$mail->setFrom($order->email, $order->name.' '.$order->surname);
			$mail->addTo($this->contact->email, $this->contact->name);
			$mail->setSubject($this->translator->translate('ExpresMenu.pl – nowe zamówienie nr')." ".$order->no);
			$mail->setHtmlBody($template);

			$this->mailer->send($mail);
		}

		public function heurekaVerification($order){
			try {
				$options = [
					"service" => \Heureka\ShopCertification::HEUREKA_CZ,
				];

				$overeno = new \Heureka\ShopCertification($this->vendorSettings->heurekaVerification, $options);
				// set customer email - MANDATORY
				$overeno->setEmail($order->email);
				// products
				foreach($order->related("orders_products") as $product){
//					$overeno->addProduct($product->products->name);
					$overeno->addProductItemId($product->products_id);
				}
				// add order ID - BIGINT (0 - 18446744073709551615)
				$overeno->setOrderId($order->id);
				// send request
				$overeno->logOrder();
			} catch (HeurekaOverenoException $e) {
				// handle errors
				print $e->getMessage();
			}
		}

		public function getZasilkovnaBranches () {
			if (($key = $this->context->parameters['zasilkovna']['apiKey'])) {
				$xml = simplexml_load_file('http://www.zasilkovna.cz/api/v3/'.$key.'/branch.xml');
				$branches = array();

				foreach ($xml->branches->branch as $branch) {
					$branchName = (string) $branch->nameStreet;

					if ((string) $branch->country == "sk") {
						$this->zasilkovnaBranches[$branchName] = $branchName;
					}
				}
			}
		}

		public function createPdf ($order) {
			$file = WWW_DIR.'/invoices/'.$order->no.'.pdf';
			$latte = new Engine();
			$params = array(
				"order" => $order,
				"presenter" => $this,
				"currency" => $order->currency == 'czk' ? $this->context->parameters['currency'] : $order->currency,
				"decimals" => $order->currency == 'czk' ? 2 : 2,
				"host" => $this->context->parameters['host'],
				"transport" => $this->model->getShopMethods()->wherePrimary($order->transport_id)->fetch(),
				"method" => $this->model->getShopMethods()->wherePrimary($order->payment_id)->fetch()
			);

//			$template = new FileTemplate(APP_DIR.'/AdminModule/EshopModule/templates/Orders/pdf.latte');
//			$template->registerFilter(new Engine());
//			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
//			$template->order = $order;
//			$template->presenter = $this;
//			$template->currency = $order->currency == 'czk' ? $this->context->parameters['currency'] : $order->currency;
//			$template->decimals = $order->currency == 'czk' ? 0 : 2;
//			$template->host = $this->context->parameters['host'];

			$pdf = new \mPDF('', 'A4', '9', 'Arial', 15, 15, 0, 0);
			$pdf->SetHTMLHeaderByName('_default');
			$pdf->SetHTMLFooterByName('_default');
			$pdf->WriteHTML($latte->renderToString(APP_DIR."/AdminModule/EshopModule/templates/Orders/pdf.latte", $params), 2);
			$pdf->Output($file, 'F');
		}

		public function sendMail ($order) {
			$template = new FileTemplate(APP_DIR.'/AdminModule/EshopModule/templates/Orders/email.latte');
			$template->registerFilter(new Engine());
			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
			$template->order = $order;
			$template->presenter = $this;
			$template->decimals = $order->currency == 'czk' ? 2 : 2;
			$template->host = $this->context->parameters['host'];
			$template->paymentType = $this->model->getShopMethods()->wherePrimary($order->payment_id)->fetch()->type;

			$mail = new Message();
			$mail->setFrom($this->contact->email, $this->contact->name);
			$mail->addTo($order->email, $order->name.' '.$order->surname);
			$mail->setSubject('Zmiana statusu zamówienia '.$order->no);
			$mail->setHtmlBody($template);

//			$mail->embedImages();

			$this->mailer->send($mail);
		}
	}