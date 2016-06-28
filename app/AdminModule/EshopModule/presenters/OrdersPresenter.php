<?php
	namespace AdminEshopModule;

	use AdminModule\BasePresenter;

	use FrontModule\Currencies;

	use FrontEshopModule\Cart;

	use AdminModule\OrdersGrid;

	use Nette\Application\Responses\FileResponse;
	use pdf\pdf;

	use Nette\Mail\SmtpMailer;

	use Nette\Mail\Message;

	use Nette\Latte\Engine;

	use Nette\Templating\FileTemplate;

	use Nette\Application\UI\Form;
	use Nette\Forms\Rendering\BootstrapFormRenderer;
	use Nette\Utils\ArrayHash;

	class OrdersPresenter extends BasePresenter {
		public $id;
		public $urlID;
		public $sid;
		public $order;
		public $orders;
		public $products;
		public $product;
		public $category;
		public $properties;
		public $transports;
		public $payments;

		public function actionDefault () {
			$this->urlID = 0;
			$this->orders = $this->model->getOrders()->where('state > ?', -1)->where('trash', 0);

			if (!$this['grid']->getParameter('order') && !$this->isAjax()) {
				$this->redirect('this', array('grid-order' => 'no DESC'));
			}
		}

		public function actionEdit ($id) {
			$this->id = $id;
			$this->urlID = $id;
			$this->sid = 0;
			$this->getOrderInfos($id);
			$this->getComponent('contact')->setDefaults($this->order);
			$this->products = $this->model->getProducts()->where('visibility', 1)->where('trash', 0)->where('pid IS NULL')->fetchPairs('id', 'name');

			$this->transports = $this->model->getShopMethodsRelations()->select('shop_methods.*')->fetchPairs('id', 'name');
			$this->payments = $this->context->database->query("SELECT shop_methods.id AS id, name FROM shop_methods LEFT JOIN shop_methods_relations ON shop_methods.id = shop_methods_relations.id_shop_methods WHERE type IN (?)", array(1, 2, 4))->fetchPairs('id', 'name');

			if (!$this['products']->getParameter('order') && !$this->isAjax()) {
				$this->redirect('this', array('products-order' => 'name DESC','orderProducts-order' => 'amount DESC'));
			}

			if ($this->isAjax()) {
				$this->invalidateControl('price');
			}
		}

		public function actionPdf ($id) {
			$this->getOrderInfos($id);

			$template = new FileTemplate(__DIR__.'/../templates/Orders/pdf.latte');
			$template->order = $this->order;
			$template->products = $this->products;
			$template->client = $this->order->users_id == 0 ? $this->order->users_unregistered : $this->order->users;

			$pdf = new pdf();

			$pdf->setFileName('zkouska.pdf');
			$pdf->setHtml($template);

			$pdf->generate();
		}

		public function renderDefault () {
			$this->template->orders = $this->orders;
		}

		public function renderEdit () {
			$this->template->order = $this->order;
			$this->template->decimals = $this->order->currency == 'czk' ? 0 : 2;
			$this->template->methods = $this->model->getShopMethods()->fetchPairs('id', 'name');

			if ($this->getParameter('search')) {
				$this->template->products = $this->model->getProducts()->where('visibility', 1)->where('trash', 0)->where('pid = ? OR id = ?', $this->getParameter('search')['product'], $this->getParameter('search')['product']);
			}
		}

		public function getOrderInfos ($id) {
			$this->order = $this->model->getOrders()->wherePrimary($id)->fetch();
			$this->products = $this->model->getOrdersProducts()->where('orders_id', $id);
		}


		/**
		 * signal pro odebrani produktu z objednavky
		 */
		public function handleRemoveProductFromOrder($productId){
			$this->model->getOrdersProducts()->wherePrimary($productId)->delete();

			$this['cart']->updateOrder(array('id' => $this->order->id, 'currency' => $this->order->currency));
			$this->updateOrderState($this->order->id);
			$this->getOrderInfos($this->order->id);

			$this->invalidateControl();
		}

		/**
		 * signal pro pridani produktu do objednavky
		 */
		public function handleAddProductToOrder($productId, $orderId){
			$product = $this->model->getProducts()->wherePrimary($productId)->fetch();
			$order = $this->order;

			if ($findProduct = $this->model->getOrdersProducts()->where(array('orders_id' => $orderId,'products_id' => $productId))->fetch()){
				$findProduct->update(array('amount' => $findProduct->amount + 1));
				$price = $this->getDiscountPrice($findProduct->id, $findProduct->price, false, false);
			}else{
				$data=array();
				$data['orders_id'] = $order->id;
				$data['products_id'] = $product->id;
				$data['price'] = $product->price;
				$data['amount'] = 1;
				$data['state'] = 0;
				$this->model->getOrdersProducts()->insert($data);
				$price = $this->getDiscountPrice($product->id, $product->price, false, false);
			}

			$this['cart']->updateOrder(array('id' => $orderId, 'currency' => $this->order->currency));
			$this->updateOrderState($orderId);
			$this->getOrderInfos($orderId);

			$this->invalidateControl('price');
		}

		/**
		 * zavolani metody z komponenty
		 */
		public function getDiscountPrice($id, $price, $amount, $tax = false, $rate = true){
			return $this->getComponent('cart')->getProductDiscountPrice($id, $price, $amount, $tax = false, $rate = true);
		}

		public function handleDelete($id) {
			$ids = (array)$id;

			$this->model->getOrders()->where('id', $ids)->update(array('trash' => 1));
// 			$this->model->getOrdersProducts()->where('orders_id', $ids)->delete();

			$this->flashMessage('Položka/y byla smazána!');
		}

		public function handleDeleteProduct($id, $pid) {
			$this->model->getOrdersProducts()->find($pid)->delete();

			$this->updateOrderPrice();

			$this->flashMessage('Produkt byl smazán!');
			$this->invalidateControl('productsTable');
		}

		public function sendMail ($order) {
			if ($order->state >= 3) {
				$template = new FileTemplate(APP_DIR . '/AdminModule/EshopModule/templates/Orders/StatesEmails/state' . $order->state . '.latte');
				$template->registerFilter(new Engine());
				$template->registerHelperLoader('Nette\Templating\Helpers::loader');
				$template->order = $order;
				$template->presenter = $this;
				$template->currency = $order->currency == 'czk' ? $this->context->parameters['currency'] : $order->currency;
				$template->decimals = $order->currency == 'czk' ? 0 : 2;
				$template->host = $this->context->parameters['host'];
				$template->paymentType = $this->model->getShopMethods()->wherePrimary($order->payment_id)->fetch()->type;
				$template->lang = $this->lang;
				$template->defaultLang = $this->getDefaultLang();
				$template->methods = $this->model->getShopMethods()->fetchPairs('id', 'name');

				$mail = new Message();
				$mail->setFrom($this->contact->email, $this->contact->name);
				$mail->addTo($order->email, $order->name . ' ' . $order->surname);
				$mail->setSubject('Změna stavu objednávky č. ' . $order->no);
				$mail->setHtmlBody($template);

				$this->mailer->send($mail);
			}
		}

		public function getMethodName ($id) {
			return $this->model->getShopMethods()->find($id)->fetch()->name;
		}

		public function handleVisibility($id, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$this->model->getOrders()->where("id", $id)->update(array("visibility" => $vis));

			$this->flashMessage('Objednávka byla nastavena jako zaplacená!');
			if ($this->presenter->isAjax()) {
				$this->invalidateControl('ordersTable');
			}
		}

		public function createComponentAddProduct () {
			$form = new Form();

			$form->getElementPrototype()->class('ajax');

			$form->addGroup('přidat produkt');
			$form->addSelect('products_id', 'Produkt:', $this->model->getProducts()->fetchPairs('id', 'name'));

			$form->addText('amount', 'Ks:')
				->addRule(Form::NUMERIC, 'Počet kusů musí obsahovat pouze čísla')
				->setRequired('Vyplňte prosím počet kusů');

			$form->addSubmit('add', 'Přidat');

			$form->onSuccess[] = callback($this, 'addProduct');

			return $form;
		}

		public function addProduct ($form) {
			$values = $form->values;
			$values['orders_id'] = $this->order->id;
			if ($price = $this->model->getProductsPrices()->where('products_id', $values['products_id'])->order('date DESC')->fetch()) {
				$values['products_prices_id'] = $price->id;
			}

			$this->model->getOrdersProducts()->insert($values);
			$this->updateOrderPrice();

			$this->flashMessage('Produkt byl přidán do objednávky');
			$this->invalidateControl('productsTable');
		}

		public function updateOrderPrice () {
			$price = 0;

			foreach ($this->products as $product) {
				if ($product->products_prices_id == 0) {
					$price += $product->products->price * $product->amount;
				}
				else {
					$price += $product->products_prices->price * $product->amount;
				}
			}

			$values['price'] = $price;

			$this->order->update($values);
		}

		public function updateOrderState ($id) {
			$products = $this->model->getOrdersProducts()->where('orders_id', $id)->fetchPairs('id', 'state');

			//pokud je v objednávce jen jeden produkt, nastaví se stav objednávky podle produktu
			if (count($products) == 1) {
				$values['state'] = reset($products);
			}
			else {
				//uloží do proměnné stav produktu => počet výskytů v objednávce
				$states = array_count_values($products);

				//pokud se vyskytuje jen jeden stav v objednávce, nastaví se stav objednávky podle toho
				if (count($states) == 1) {
					$states = array_flip($states);

					$values['state'] = reset($states);
				}
				else {
					//když objednávka obsahuje produkty se stavem VYŘIZUJE SE a DORUČENÁ - stav objednávky ČÁSTEČNĚ DORUČENÁ
					if (isset($states[0]) && isset($states[1])) {
						$values['state'] = 2;
					}
					//když objednávka obsahuje produkty se stavem VYŘIZUJE SE a neobsahuje DORUČENÁ - stav objednávky VYŘIZUJE SE
					elseif (isset($states[0]) && !isset($states[1])) {
						$values['state'] = 0;
					}
					else {
						$values['state'] = 1;
					}
				}
			}

			$order = $this->model->getOrders()->wherePrimary($id)->fetch();
			$order->update($values);

			$this->sendMail($order);
		}

		public function updateOrderProductsStates ($id, $state) {
			switch ($state) {
				case 0:
					$this->model->getOrdersProducts()->where('orders_id', $id)->where('state != ?', 3)->update(array('state' => 0));
					break;
				case 1:
					$this->model->getOrdersProducts()->where('orders_id', $id)->where('state != ?', 3)->update(array('state' => 1));
					break;
				case 3:
					$this->model->getOrdersProducts()->where('orders_id', $id)->where('state != ?', 1)->update(array('state' => 3));
					break;
			}

// 			$this->sendMail($id);
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
				->setRequired('Vyplňte PSČ!');

			$form->addText('phone', 'Telefon:')
				->setRequired('Vyplňte telefon!');

			$form->addText('email', 'E-mail:')
				->setRequired('Vyplňte e-mail!')
				->addRule(Form::EMAIL, 'Nesprávný formát e-mailu!');

			$form->addGroup('Údaje pro doručení');
			$form->addText('delivery_name', 'Jméno:');

			$form->addText('delivery_surname', 'Příjmení:');

			$form->addText('delivery_street', 'Ulice:');

			$form->addText("delivery_street_number", "Číslo popisné:");

			$form->addText('delivery_city', 'Město:');

			$form->addText('delivery_psc', 'PSČ:');

			$form->addGroup("Doplňující údaje");
			$form->addTextArea("text", "Poznámka:");

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('submit','Upravit');

			$form->onSuccess[] = array($this, 'contactSubmitted');

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		public function contactSubmitted ($form) {
			$values = $form->getValues();
			$this->order->update($values);
			$this->flashMessage('Objednávka úspěšně upravena');
			$this->redirect('this');
		}

		public function createComponentGrid () {
			return new OrdersGrid($this->orders);
		}

		public function createComponentCart ($name) {
			return new Cart($this, $name);
		}

		public function createComponentCurrencies ($name) {
			return new Currencies($this, $name);
		}

		public function createComponentOrderProducts () {
			return new \AdminModule\OrdersProductsGrid($this->model->getOrdersProducts()->select('orders.*, orders_products.*, products.name AS name, products.code AS code')->where('orders_id', $this->order->id));
		}

		public function getProducts () {
			return $this->model->getProducts()->where('pid IS NULL')->group('galleries_id');
		}

		public function createComponentProducts () {
			return new \AdminModule\ProductsGrid($this->getProducts());
		}

		/**
		 * factory form search products in order edit page
		 * @return \Nette\Application\UI\Form
		 */
		public function createComponentSearchForm () {
			$form = new Form;

			$form->getElementPrototype()->addClass('form-horizontal');

			$form->addGroup(null);
			$form->addSelect('product', 'Produkt', $this->products)
				->setDefaultValue($this->getParameter('search') ? $this->getParameter('search')['product'] : null);

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('submit','Vyhledat');

			$form->onSuccess[] = $this->searchFormSucceeded;

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		/**
		 * redirect page after process search form
		 * @param \Nette\Application\UI\Form $form
		 * @param ArrayHash $values
		 */
		public function searchFormSucceeded ($form, $values) {
			$this->redirect('this', array('search' => $values));
		}

		/**
		 * return string of product properties
		 * @param int $id
		 */
		public function getProductProperties ($id) {
			$productProperties = $this->model->getProductsProperties()->where('products_id', $id)->fetch();

			if ($this->properties == null) {
				$this->properties = $this->model->getShopProperties();
			}


			$properties = array();
			foreach ($this->properties as $property) {
				$p = 'p_'.$property->id;

				if ($productProperties->$p) {
					$properties[] = $property->categories->name.' - '.$property->name;
				}
			}

			return implode(', ', $properties);
		}

		public function handlePaymentRequest($id)
		{
			$order = $this->model->getOrders()->wherePrimary($id)->fetch();
			$latte = new Engine();
			$params = array(
				"order" => $order,
				"paymentType" => $this->model->getShopMethods()->wherePrimary($order->payment_id)->fetch()->type,
				"presenter" => $this,
				"host" => $this->context->parameters['host'],
				"decimals" => $this->currency == 'czk' ? 0 : 2,
				"methods" => $this->model->getShopMethods()->fetchPairs('id', 'name'),
				"lang" => $this->lang,
				"defaultLang" => $this->getDefaultLang()
			);

			$mail = new Message();
			$mail->setFrom($this->contact->email, $this->contact->name);
			$mail->addTo($order->email);
			$mail->setSubject("Výzva k platbě");
			$mail->setHtmlBody($latte->renderToString(APP_DIR . "/AdminModule/EshopModule/templates/Orders/paymentRequest.latte", $params));

			$this->mailer->send($mail);

			$this->flashMessage("Výzva k platbě byla odeslána");
		}

		public function handleGetPdf($id)
		{
			$file = WWW_DIR . "/invoices/" . $id . ".pdf";

			if(file_exists($file)) {
				unlink($file);
			}

			$order = $this->model->getOrders()->where('no = ?', $id)->fetch();

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

			$pdf = new \mPDF('', 'A4', '9', 'Arial', 15, 15, 0, 0);
			$pdf->SetHTMLHeaderByName('_default');
			$pdf->SetHTMLFooterByName('_default');
			$pdf->WriteHTML($latte->renderToString(APP_DIR."/AdminModule/EshopModule/templates/Orders/pdf.latte", $params), 2);
			$pdf->Output($file, 'F');

			$this->sendResponse(new FileResponse($file));
		}

		public function getDefaultLang () {
			if ($lang = $this->presenter->model->getLanguages()->where('highlight', 1)->fetch()) {
				return '_'.$lang->key;
			}
			else return null;
		}

		public function getProductProps ($id) {
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

		public function createComponentTransport () {
			$this->getZasilkovnaBranches();

			$form = new Form();

			$form->getElementPrototype()->class('form-horizontal ajax');

			$form->addGroup();
			$form->addRadioList('transport_id', 'Doprava', $this->transports)
				->setRequired('Vyberte druh přepravy!')
				->getLabelPrototype()->class('transport');

			$form->addSelect('zasilkovna', null, $this->zasilkovnaBranches)
				->setPrompt('--Vyberte pobočku--');

			$form->addRadioList('payment_id', 'Platba', $this->payments)
				->setRequired('Vyberte druh platby')
				->getLabelPrototype()->class('payment');

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('edit','Upravit');

			$form->onSuccess[] = $this->changeTransport;

			$form->setRenderer(new BootstrapFormRenderer());

			$form->setValues($this->order);

			return $form;
		}

		public function changeTransport($form, $values)
		{
			$relation = $this->model->getShopMethodsRelations()->where('shop_methods_id', $values['transport_id'])->where('id_shop_methods', $values['payment_id'])->fetch();
			$values["transport"] = $relation->price;

			$this->order->update($values);

			$this->redrawControl("price");
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
	}