<?php
	namespace AdminEshopModule;

	use AdminModule\BasePresenter;

	use AdminModule\ProductsTrashGrid;

	use AdminModule\ProductsRelatedGrid;

	use AdminModule\VersionsGrid;

	use AdminModule\Seo;

	use Nette\Http\Url;

	use Nette\Utils\Strings;

	use AdminModule\Langs;

	use Nette\Forms\Controls\SubmitButton;

	use Nette\Utils\Html;

	use Nette\Application\UI\Form;

	use \DataGrid;
	use Nette\Forms\Rendering\BootstrapFormRenderer;
	use Nette\Utils\Json;
	use Nette\Application\UI\Multiplier;

	class ProductsPresenter extends BasePresenter {
		public $urlID;
		public $pid;
		public $products;
		public $product;
		public $productProperties;
		public $fields = array();
		public $settings;
		public $id;
		public $sid;
		public $section;
		public $filters;
		public $categories;
		public $tags;
		public $properties;
		public $propertiesCategories;
		public $discounts;
		public $versions;
		/** @persistent */
		public $category;

		/** @var  array */
		public $categoriesCategories;

		public function startup() {
			parent::startup();

			$this->section = $this->settings = $this->model->getShopSettings()->fetch();
			$this->fields = $this->model->getSectionsFields()->where('sections_id', 0)->order('position ASC');

// 			if (!$this->section) {
// 				$this->redirect('Settings:');
// 			}
		}

		public function actionDefault () {
			$this->urlID = 0;

			$this->filters = $this->model->getFilters();
			$this->products = $this->getProducts();
// 			$this->products = count($this->products) ? $this->products : $this->model->getProductsCategories()->wherePrimary(0);
			$this->properties = $this->model->getShopProperties()->fetchPairs('id', 'name');
			$this->propertiesCategories = $this->model->getCategories()->where('sections_id', -2)->order('position ASC');

			$this->getCategoriesTree();

			if (!$this['grid']->getParameter('order')) {
				$this->redirect('this', array('grid-order' => ($this->category ? 'position' : 'name').' ASC'));
			}
		}

		public function actionAdd ($id) {
			$this->setView('edit');

			$this->pid = empty($id) ? 0 : $id;
			$this->urlID = empty($id) ? 0 : $id;
			$this->propertiesCategories = $this->model->getCategories()->where('sections_id', -2)->order('position ASC');
			$this->categoriesCategories = $this->model->getCategoriesCategories()->fetchPairs("id", "id_category");
		}

		public function actionEdit ($id, $sid, $version = null) {
			$this->id = $id;
			$this->urlID = $id;
			$this->sid = $sid;

			if ($this->section->versions) {
				$row = $this->model->getProducts()->where('products_id', $id)->order('date DESC')->fetch();
				if ($version == null && $row && !$this->isAjax()) {
					$this->redirect('this', array('sid' => 0, 'version' => $row['id']));
				}
			}

			if (!isset($this->request->getParameters()['amountDiscount-order'])) {
				$this->redirect('this', array('amountDiscount-order' => 'amount ASC', 'productsRelated-order' => 'name ASC'));
			}

			if ($version != null && $this->section->versions) {
				$this->product = $this->model->getProducts()->wherePrimary($version)->fetch();
			}
			else {
				$this->product = $this->model->getProducts()->wherePrimary($id)->fetch();
			}

			$this->categories = $this->model->getProductsCategories()->where('products_id', $id)->fetchPairs('categories_id', 'categories_id');
			$this->tags = $this->model->getProductsTags()->where('products_id', $id)->fetchPairs('tags_id', 'tags_id');
			$this->discounts = $this->model->getProductsDiscounts()->where('products_id', $id);

			if ($this->product) {
				$this->versions = $this->model->getProducts()->select('users.*, products.*, products.date AS date')->where('galleries_id', $this->product->galleries_id)->order('date DESC');
			}

			$productCategories = $this->model->getProductsCategories()->where('products_id', $id)->fetchPairs('id', 'categories_id');
			$propertiesCategoriesCategories = $this->model->getCategoriesCategories()->where('id_category', array_values($productCategories))->fetchPairs('id', 'categories_id');
			$this->propertiesCategories = $this->model->getCategories()->where('id', array_values($propertiesCategoriesCategories))->order('position ASC');
			$properties = $this->model->getShopProperties()->where('categories_id', array_values($this->propertiesCategories->fetchPairs('id', 'id')))->fetchPairs('id', 'categories_id');
			$productProperties = $this->model->getProductsProperties()->where('products_id', $id)->fetch();
			$this->properties = array();

			if ($productProperties) {
				foreach ($productProperties as $key => $property) {
					if (preg_match('/p_/', $key)) {
						if ($property) {
							$p = preg_replace('/p_/', '', $key);

							if (isset($properties[$p])) {
								$this->properties['category'.$properties[$p]] = $p;
							}
						}
					}
				}
			}
			else {
				if (count($this->propertiesCategories)) {
					$data['products_id'] = $this->product->id;
					$data['pid'] = $this->product->pid == null ? $this->product->id : $this->product->pid;

					$this->model->getProductsProperties()->insert($data);
				}
			}
		}

		public function actionGallery ($id, $gid) {
			$this->id = $id;
			$this->sid = 0;
			$this->urlID = 0;
			$this->product = $this->model->getProducts()->where('galleries_id', $id)->fetch();
			$this->section = $this->model->getShopSettings()->fetch();

			if (!$this['gallery']->getDimensions()->where('place = ? OR place = ?', 0, 1)->fetch()) {
				$this->flashMessage('Nastavte nejříve alespoň jeden rozměr obrázků');
				$this->redirect('Settings:');
			}

// 			if ($gid) {
// 				$this->redirect('Products:gallery', $this->product->galleries_id);
// 			}

			if (!isset($this->request->getParameters()['gallery-grid-order'])) {
					$this->redirect('this', array('gallery-grid-order' => 'position ASC'));
			}
		}

		public function actionFiles ($id, $fid) {
			$this->id = $id;
			$this->sid = 0;
			$this->urlID = 0;
			$this->product = $this->model->getProducts()->where('filestores_id', $id)->fetch();

// 			if ($fid) {
// 				$this->redirect('Products:files', $this->product->filestores_id);
// 			}

			if (!isset($this->request->getParameters()['files-grid-order'])) {
				$this->redirect('this', array('files-grid-order' => 'position ASC'));
			}
		}

		public function actionDelete ($id) {
			$this->setView('default');

			$values['visibility'] = -1;

			$this->model->getProducts()->wherePrimary($id)->update($values);

			$this->flashMessage('Položka byla smazána!');
			$this->redirectUrl($this->context->httpRequest->referer->absoluteUrl);
		}

		public function actionPosition ($id) {
			$this->id = $id;
			$this->urlID = 0;
			$this->products = $this->getProducts()->where('categories_id', $id)->order('position ASC');
		}

		public function actionVariations ($id, $sid) {
			$this->id = $id;
			$this->sid = $sid;
			$this->urlID = $sid;
			$this->properties = $this->getProperties();

			$this->actionProperties($id);
		}

		public function actionProperties ($id) {
			$this->urlID = 0;

			$this->product = $this->model->getProducts()->wherePrimary($id)->fetch();
			$productCategories = $this->model->getProductsCategories()->where('products_id', $id)->fetchPairs('id', 'categories_id');
			$propertiesCategoriesCategories = $this->model->getCategoriesCategories()->where('id_category', array_values($productCategories))->fetchPairs('id', 'categories_id');
			$this->propertiesCategories = $this->model->getCategories()->where('id', array_values($propertiesCategoriesCategories))->order('position ASC');
		}

		public function actionTrash () {
			$this->urlID = 0;

			$this->products = $this->model->getProducts()->where('trash', 1)->where('pid IS NULL');
			$this->properties = $this->model->getShopProperties()->fetchPairs('id', 'name');

			if (!$this['trash']->getParameter('order')) {
				$this->redirect('this', array('trash-order' => 'name ASC'));
			}
		}

		public function actionImport()
		{
			$xml = simplexml_load_file("http://www.expresmenu.cz/?feed-heureka,22");

			foreach ($xml->SHOPITEM as $item) {
				$product = array();
				$product["old_id"] = (int) $item->ITEM_ID;
				$product["name"] = $product["title"] = $product["keywords"] = $product["meta_description"] = (string) $item->PRODUCTNAME;
				$product["url"] = Strings::webalize($product["name"]);
				$product["description"] = (string) $item->DESCRIPTION;
				$product["ean"] = (string) $item->EAN;
				$product["delivery_date"] = (int) $item->DELIVERY_DATE;
				$product["price"] = $product["price_filter"] = (float) $item->PRICE_VAT;
				$product["galleries_id"] = $this->model->getGalleries()->insert(array());
				$product["filestores_id"] = $this->model->getFilestores()->insert(array());
				$product["date"] = date("Y-m-d H:i:s");
				$product["amount"] = 1;
				$product["price_discount"] = 0;
				$product["tax"] = 21;

				if ($p = $this->model->getProducts()->where("old_id", $product["old_id"])->fetch()) {
					$product["products_id"] = $p->id;

					$p->update($product);
				} else {
					$lastID = $this->model->getProducts()->insert($product);
					$lastID->update($lastID->id);
				}
			}
		}

		public function renderDefault () {
			$this->template->filters = $this->filters;
		}

		public function renderPosition () {
			$this->template->products = $this->products;
		}

		public function getProperties () {
			foreach ($this->model->getCategories()->where('sections_id', -2) as $category) {
				$properties[$category->id] = $this->model->getShopProperties()->where('categories_id', $category->id)->fetchPairs('id', 'name');
			}

			if (isset($properties)) {
				return $properties;
			}
			else {
				$this->redirect('Settings:');
			}
		}

		public function getProducts () {
			if (!$this->getParameter('category')) {
				return $this->model->getProducts()->where('pid IS NULL')->where('trash', 0)->group('galleries_id');
			}
			else {
				return $this->model->getProductsCategories()->select('*, products.*, products.name AS name')->where('products_categories.categories_id', $this->getParameter('category'))->where('products.pid IS NULL')->where('products.trash', 0);
			}

			//výběr nejnovější verzí produktů - nahovno kvůli dlouhému načítání
// 			return $this->model->getProductsInserted(implode(',', $this->getCategoryProducts()), false, false)->where('pid IS NULL')->group('galleries_id');
		}

		/**
		 * Fukce z 2.0 kvůli verzím, asi bych vyhodil
		 * @return unknown|multitype:number
		 */
		public function getCategoryProducts () {
			if (count($categories = $this->model->getProductsCategories()->fetchPairs('products_id', 'products_id'))) {
				return $categories;
			}
			else return array(0);
		}

		public function createComponentAddForm () {
			return new Multiplier(function ($key) {
				$key = $key == 'cs' ? '' : '_'.$key;

				$form = new Form();

	// 			if ($this->product) {
	// 				$form->getElementPrototype()->changeProductType('true');
	// 			}
				$form->getElementPrototype()->addClass('form-horizontal');

				$form->addGroup('Základní informace');
				$infos = $form->addContainer('infos');
				if ($this->settings->name) {
					$infos->addText('name'.$key, 'Jméno:')
						->setRequired('Vyplňte název produktu!')
						->setAttribute('class', 'input-name');

					$infos->addText('url'.$key, 'URL:')
						->setRequired('Vyplňte prosím url stránky!')
						->setAttribute('class', 'input-url');
				}

				if ($this->settings->title) {
					$infos->addText('title'.$key, 'Titulek:')
						->setRequired('Vyplňte titulek produktu');
				}

				if ($this->settings->keywords) {
					$infos->addText('keywords'.$key, 'Klíčová slova:');
				}

				$infos->addText('meta_description'.$key, 'Meta popisek:');

				if ($this->settings->ean) {
					$infos->addText('ean', 'ean:');
				}

				if ($this->settings->code) {
					$infos->addText('code', 'kód produktu:');
				}

				if ($this->settings->expirationDate) {
					$form->addGroup('Platnost');
					$infos->addText('altExpirationDateFrom', 'Od:');

					$infos->addHidden('expirationDateFrom');

					$infos->addText('altExpirationDateTo', 'Do:');

					$infos->addHidden('expirationDateTo');
				}

				if ($this->settings->description) {
					$infos->addTextarea('description'.$key, 'Krátký popis:');
				}

				if ($this->settings->text) {
					$infos->addTextarea('text'.$key, 'Text:')
						->getControlPrototype()->class('tinymce');
				}

				if ($this->settings->price) {
					$infos->addText('price', 'Cena:')
						->setRequired('Vyplňte prosím cenu!')
						->addRule(Form::FLOAT, 'Cena musí obsahovat pouze čísla')
						->addRule(Form::RANGE, 'Cena musí být větší než 0', array(0.000001, 10000000000000));

					$infos->addText('price_discount', 'Akční cena:')
						->addRule(Form::FLOAT, 'Cena musí obsahovat pouze čísla');

					$infos->addText('tax', 'DPH:')
						->setRequired('Vyplňte prosím DPH!')
						->addRule(Form::RANGE, 'DPH musí být v rozmezí od 1 do '.$this->context->parameters['tax'], array(1, $this->context->parameters['tax']))
						->setValue(!$this->product ? $this->context->parameters['tax'] : null);
				}

				if ($this->settings->stock) {
					$infos->addSelect('delivery_date', 'Dostupnost:', $this->context->parameters['deliveryDates']);

					$infos->addText('amount', 'Množství:')
						->addCondition(Form::FILLED)
						->addRule(Form::INTEGER, 'Množství musí obsahovat pouze čísla');
				}

				$form->addGroup('Zařazení');
	// 			if ($this->settings->categories) {
					$categories = $form->addContainer('categories');
					$categories->addMultiSelect('categories', 'Kategorie:', $this->model->getCategories()->where('sections_id', 0)->fetchPairs('id', 'name'))
						->setRequired('Vyberte alespoň jednu kategorii!')
						->getControlPrototype()->class('chosen');
	// 			}

				$tags = $form->addContainer('tags');
				$tags->addMultiSelect('tags', 'Tagy:', $this->model->getTags()->fetchPairs('id', 'name'))
					->getControlPrototype()->class('chosen');

				if ($this->product) {
					$form->addGroup('Vlastnosti produktu');
					$properties = $form->addContainer('properties');

					foreach ($this->propertiesCategories as $category) {
						$properties->addSelect('category'.$category->id, $category->name, $category->related('shop_properties')->fetchPairs('id', 'name'))
							->setPrompt('--Vyberte--')
							->setRequired();
					}
				}

				$form->addGroup('Speciální položky');
				foreach ($this->fields as $field) {
					switch ($field->type) {
						case 1:
							$infos->addText($field->title.$key, $field->name);
							break;
						case 2:
							$infos->addTextarea($field->title.$key, $field->name)
								->getControlPrototype()->class('tinymce');
							break;
						case 3:
							$infos->addCheckbox($field->title.$key, $field->name);
							break;
						case 5:
							$infos->addTextarea($field->title.$key, $field->name)
								->getControlPrototype()->class('tinymce');
					}
				}

				$form->addGroup()
					->setOption('container', 'fieldset class="submit"');
				if ($this->section->versions && $this->product) {
					$form->addSubmit('addVersion', 'Nová verze')
						->onClick[] = callback($this, 'addVersion');
				}

				$form->addSubmit('add', $this->product ? 'Upravit' : 'Pokračovat');

				$form->onSuccess[] = callback($this, $this->product ? 'editProduct' : 'addProduct');

				$form->addHidden('referer', $this->getReferer());

				$values['infos']['altExpirationDateFrom'] = $this->product && $this->product->expirationDateFrom != null ? $this->product->expirationDateFrom->format('j.n.Y H:i') : null;
				$values['infos']['altExpirationDateTo'] = $this->product && $this->product->expirationDateTo != null ? $this->product->expirationDateTo->format('j.n.Y H:i') : null;

				if ($this->product) {
					$data['infos']['tax'] = $this->product->tax == null ? $this->context->parameters['tax'] : $this->product->tax;

					if ($this->product->pid != 0) {
						$data['settings']['type'] = 1;
						$data['settings']['pid'] = $this->product->pid;
					}

					$data['infos'] = $this->product;
					$date['infos']['expirationDateFrom'] = $this->product->expirationDateFrom != null ? $this->product->expirationDateFrom->format('Y-m-d H:i') : null;
					$date['infos']['expirationDateTo'] = $this->product->expirationDateTo != null ? $this->product->expirationDateTo->format('Y-m-d H:i') : null;
					$data['categories']['categories'] = $this->categories;
					$data['tags']['tags'] = $this->tags;

					if ($this->properties) {
						$data['properties'] = $this->properties;
					}

					$form->setValues($data);
					$form->setValues($date);
				}

				$form->setValues($values);

				$form->setRenderer(new BootstrapFormRenderer());

				return $form;
			});
		}

		public function addProduct ($form) {
			$values = $form->values;

			$values['infos']['date'] = date('Y-m-d H:i:s');
			$values['infos']['users_id'] = $this->user->id;
			$values['infos']['galleries_id'] = $this->model->getGalleries()->insert(array());
			$values['infos']['filestores_id'] = $this->model->getFilestores()->insert(array());
			$values['infos']['pid'] = !empty($values['infos']['pid']) ? $values['infos']['pid'] : null;
			$values['infos']['price'] = $price = Strings::replace($values['infos']['price'], '~,~', '.');
			$values['infos']['price_discount'] = $priceDiscount = Strings::replace($values['infos']['price_discount'], '~,~', '.');
			$values['infos']['price_filter'] = $priceDiscount ? ($priceDiscount < $price ? $priceDiscount : $price) : $price;
			unset($values['infos']['altExpirationDateFrom']);
			unset($values['infos']['altExpirationDateTo']);

			$lastID = $this->model->getProducts()->insert($values['infos']);
			$lastID->update(array('products_id' => !empty($values['infos']['pid']) ? $values['infos']['pid'] : $lastID->id));

			$propertiesRedirect = false;

			if (isset($values['categories']['categories'])) {
				foreach ($values['categories']['categories'] as $category) {
					$this->saveProductsCategories($lastID, $category);

					if (in_array($category, $this->categoriesCategories)) {
						$propertiesRedirect = true;
					}
				}
			}

			if (isset($values['tags']['tags'])) {
				foreach ($values['tags']['tags'] as $tag) {
					$data = array();
					$data["tags_id"] = $tag;
					$data["products_id"] = $lastID;

					$this->model->getProductsTags()->insert($data);
				}
			}

			$this->flashMessage('Produkt byl přidán');

			if ($propertiesRedirect) {
				$this->redirect('Products:properties', array('id' => $lastID));
			}
			else {
				$this->redirect('Products:gallery', array('id' => $values['infos']['galleries_id'], 'gid' => 0));
			}
		}

		public function saveProductsCategories ($id, $category) {
			$cat = $this->model->getCategories()->wherePrimary($category)->fetch();
			$lastPosition = $this->model->getProductsCategories()->where('categories_id', $category)->order('position DESC')->fetch();

			$data['products_id'] = $id;
			$data['categories_id'] = $category;
			$data['position'] = !$lastPosition ? 0 : $lastPosition->position+1;

			if (!$this->model->getProductsCategories()->where('categories_id', $category)->where('products_id', $id)->fetch()) {
				$this->model->getProductsCategories()->insert($data);
			}

			if ($cat->pid != 0) {
				$this->saveProductsCategories($id, $cat->pid);
			}
		}

		public function editProduct ($form) {
			$values = $form->values;
			$values['infos']['expirationDateFrom'] = isset($values['infos']['altExpirationDateFrom']) ? ($values['infos']['altExpirationDateFrom'] == null ? null : $values['infos']['expirationDateFrom']) : null;
			$values['infos']['expirationDateTo'] = isset($values['infos']['altExpirationDateTo']) ? ($values['infos']['altExpirationDateTo'] == null ? null : $values['infos']['expirationDateTo']) : null;
			$values['infos']['price'] = $price = Strings::replace($values['infos']['price'], '~,~', '.');
			$values['infos']['price_discount'] = $priceDiscount = Strings::replace($values['infos']['price_discount'], '~,~', '.');
			$values['infos']['price_filter'] = $priceDiscount ? ($priceDiscount < $price ? $priceDiscount : $price) : $price;

			if (isset($values['infos']['price']) && $values['infos']['price'] != $this->product->price) {
				$price['products_id'] = $this->id;
				$price['price'] = $values['infos']['price'];
				$price['date'] = date('Y-m-d H:i:s');

				$this->model->getProductsPrices()->insert($price);
			}

			if (isset($values['categories']['categories'])) {
				$categories = $this->model->getProductsCategories()->where('products_id', $this->id)->fetchPairs('categories_id', 'categories_id');

				foreach (array_diff($categories, $values['categories']['categories']) as $cat) {
					$this->model->getProductsCategories()->where('products_id', $this->id)->where('categories_id', $cat)->delete();
				}
				foreach (array_diff($values['categories']['categories'], $categories) as $val) {
					$this->saveProductsCategories($this->id, $val);
				}
			}

			if (isset($values['tags']['tags'])) {
				foreach ($values['tags']['tags'] as $tag) {
					$data = array();
					$data["tags_id"] = $tag;
					$data["products_id"] = $this->id;

					if (!$this->model->getProductsTags()->where($data)->fetch()) {
						$this->model->getProductsTags()->insert($data);
					}
				}
			}

			if (isset($values['properties'])) {
				$data = array();

				foreach ($this->properties as $property) {
					$data['p_'. $property] = false;
				}

				foreach ($values['properties'] as $property) {
					$data['p_'.$property] = true;
				}

				if ($data != null) {
					$this->model->getProductsProperties()->where('products_id', $this->product->id)->update($data);
				}

				if (array_diff((array)$values['properties'], $this->properties)) {
					if ($this->product->pid == null) {
						$values['infos']['properties'] = $this->getProductPropertiesJson($this->product->id);
					}
					else {
						$json = $this->getProductPropertiesJson($this->product->pid);

						$this->model->getProducts()->wherePrimary($this->product->pid)->update(array('properties' => $json));
					}
				}
			}

			//change variations price if parent price has changed
			if ($values['infos']['price'] != $this->product->price && $this->product->pid == null) {
				foreach ($this->presenter->model->getProducts()->where('pid', $this->product->id) as $prod) {
					$prod->update(array('price' => $values['infos']['price']));
				}
			}

			//change variations name if parent name has changed
			if ($values['infos']['name'] != $this->product->name && $this->product->pid == null) {
				foreach ($this->presenter->model->getProducts()->where('pid', $this->product->id) as $prod) {
					$prod->update(array('name' => $values['infos']['name']));
				}
			}

// 			$values['infos']['pid'] = !empty($values['infos']['pid']) ? $values['infos']['pid'] : null;

			unset($values['infos']['altExpirationDateFrom']);
			unset($values['infos']['altExpirationDateTo']);

			$this->product->update($values['infos']);

			$this->flashMessage('Produkt byl upraven');
			$this->redirectUrl($values['referer']);
		}

		public function addVersion ($button) {
			$values = $button->parent->values;
			$properties = isset($values['properties']) ? $values['properties']['properties'] : false;
			$values = $values['infos'];

			unset($values['altExpirationDateFrom']);
			unset($values['altExpirationDateTo']);

			if (!$this->model->getProducts()->where((array)$values)->where('id', $this->product->id)->fetch()) {
				$product = $this->product->toArray();
				$values['products_id'] = $this->id;
				$values['date'] = date('Y-m-d H:i:s');
				$values['users_id'] = $this->user->id;

				unset($product['id']);
				$version = $this->model->getProducts()->insert($product);

				$version->update($values);

				if ($properties) {
					$this->model->getProductsProperties()->where('products_id', $this->id)->delete();

					foreach ($properties as $property) {
						$data = array();
						$data['products_id'] = $this->id;
						$data['shop_properties_id'] = $property;

						$this->model->getProductsProperties()->insert($data);
					}
				}

				$this->redirect('this', array('version' => $version->id, 'sid' => 0));
			}
			else $this->redirect('this', array('sid' => 0));
		}

		public function handleAddToCategory($sid, $id){
			$ids = (array)$id;
			$cid = $_POST['grid']['action']['categories_id'];

			foreach ($ids as $id) {
				$this->saveProductsCategories($id, $cid);

				/*if ($this->category && $this->category == 122) {
					$this->model->getProductsCategories()->where('categories_id = ? AND products_id = ?', 122, $id)->delete();
				}*/
			}
		}

		public function handleRemoveFromCategory($sid, $id){
			$ids = (array) $id;

			$this->model->getProductsCategories()->where('categories_id', $this->category)->where('products_id', $ids)->delete();
		}

		public function handleVisibility($sid, $id, $vis, $variation = false) {
			$vis = $vis == 1 ? 0 : 1;
			$ids = (array)$id;

			foreach ($ids as $row) {
				$product = $this->model->getProducts()->where('id = ? OR products_id = ?', $row, $row)->fetch();

				$this->model->getProducts()->where('products_id', $product->products_id)->update(array("visibility" => $vis));

				if ($product->pid == null) {
					$this->model->getProducts()->where('pid', $product->products_id)->update(array("visibility" => $vis));
				}
			}

			if ($variation) {
				$id = reset($ids);

				$product = $this->model->getProducts()->wherePrimary($id)->fetch();
				$product = $this->model->getProducts()->wherePrimary($product->pid)->fetch();
				$json = $this->getProductPropertiesJson($product->id);

				$product->update(array('properties' => $json));
			}

			$this->flashMessage('Nastavení zobrazení produktu změněno!');
		}

		public function handleHighlight($sid, $id, $vis) {
			$vis = $vis == 1 ? 0 : 1;
			$ids = (array)$id;

			foreach ($ids as $row) {
				$product = $this->model->getProducts()->wherePrimary($id)->fetch();

				$this->model->getProducts()->where('products_id', $product->products_id)->update(array("highlight" => $vis));

				if ($product->pid == null) {
					$this->model->getProducts()->where('pid', $product->products_id)->update(array("highlight" => $vis));
				}
			}

			$this->flashMessage('Nastavení zvýraznění produktu změněno!');
		}

		public function handleToTrash($sid, $id) {
			$this->sid = $sid;
			$ids = (array)$id;

			$this->model->getProducts()->where('id IN ? OR pid IN ?', $ids, $ids)->update(array('trash' => 1));

			$this->flashMessage('Vyhození do koše proběhlo úspěšně');
		}

		public function handleRemoveFromTrash($sid, $id) {
			$this->sid = $sid;
			$ids = (array)$id;

			$this->model->getProducts()->where('id IN ? OR pid IN ?', $ids, $ids)->update(array('trash' => 0));

			$this->flashMessage('Obnovení proběhlo úspěšně');
		}

		public function handleDelete($sid, $id) {
			$this->sid = $sid;
			$ids = (array)$id;

			foreach ($ids as $val) {
				$product = $this->model->getProducts()->wherePrimary($val)->fetch();
				$imgs = $this->model->getGalleriesImages()->where('galleries_id', $product->galleries_id)->fetchPairs('id', 'id');
				$files = $this->model->getFilestoresFiles()->where('filestores_id', $product->filestores_id)->fetchPairs('id', 'id');

				if ($product->pid == null) {
					$this['gallery']->handleDelete($product->galleries_id, $imgs);
					$this['files']->handleDelete($product->filestores_id, $files);

					$this->model->getGalleries()->wherePrimary($product->galleries_id)->delete();
					$this->model->getFilestores()->wherePrimary($product->filestores_id)->delete();
				}

				$this->model->getProducts()->where('products_id', $product->products_id)->delete();
				$this->model->getProductsCategories()->where('products_id', $product->products_id)->delete();
				$this->model->getProductsDiscounts()->where('products_id', $product->products_id)->delete();
				$this->model->getProductsPrices()->where('products_id', $product->products_id)->delete();
				$this->model->getProductsProperties()->where('products_id', $product->products_id)->delete();
				$this->model->getProductsRelated()->where('id_products = ? OR products_id = ?', $product->products_id, $product->products_id)->delete();

				if ($p = $this->model->getProducts()->where('pid', $product->id)->order('id ASC')->fetch()) {
//					$p->update(array('pid' => null, 'properties' => $json));
					$p->update(array('pid' => null));
					$this->model->getProducts()->where('pid', $product->id)->update(array('pid' => $p->id));
					$this->model->getProductsProperties()->where('products_id', $p->id)->update(array('pid' => 0));
					$this->model->getProductsProperties()->where('pid', $product->id)->update(array('pid' => $p->id));
				}

				if ($product->pid == null) {
					$json = $this->getProductPropertiesJson($product->id);

					$product->update(array('properties' => $json));
				}
				else {
					$json = $this->getProductPropertiesJson($product->pid);

					$this->model->getProducts()->wherePrimary($product->pid)->update(array('properties' => $json));
				}
			}

			$this->flashMessage('Smazání proběhlo úspěšně');
		}

		public function handleChangeOrder () {
			$positions = $_GET['positions'];

			foreach ($positions as $key => $value) {
				$values['position'] = $key;
				$this->model->getProductsCategories()->where('products_id', $value)->where('categories_id', $this->id)->update($values);
			}

			$this->flashMessage('Pořadí bylo změněno');
		}

		public function createComponentGallery ($name) {
			return new \GalleryPresenter($this, $name);
		}

		public function createComponentFiles ($name) {
			return new \FilesPresenter($this, $name);
		}

		public function createComponentLangs ($name) {
			return new Langs($this, $name);
		}

		public function createComponentSeo ($name) {
			return new Seo($this, $name);
		}

		public function createComponentAddFilter () {
			$form = new Form();

			$form->getElementPrototype()->class('ajax form-horizontal');

			$form->addText('name', 'Název:')
				->setRequired('Vyplňte prosím název');

			$form->addSubmit('add', 'Uložit stávající filtr');

			$form->onSuccess[] = callback($this, 'addFilter');

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		public function addFilter ($form) {
			$values = $form->values;
			$values['url'] = $this->context->httpRequest->url;

			$this->model->getFilters()->insert($values);

			$this->flashMessage('Filtr byl přidán');
			$this->invalidateControl('filters');
		}

		public function handleDeleteFilter ($id) {
			$this->model->getFilters()->wherePrimary($id)->delete();

			$this->flashMessage('Filtr byl smazán');
			$this->invalidateControl('filters');
		}

		public function createComponentCategories () {
			$form = new Form();

			$form->getElementPrototype()->class('form-horizontal');

			$form->addSelect('category', 'Kategorie:', $this->categories)
				->setValue($this->getParameter('category'))
				->setPrompt('--Vyberte kategorii--');

			$form->addSubmit('filter', 'Filtrovat');

			$form->onSuccess[] = array($this, 'filterCategory');

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		public function filterCategory ($form) {
			$values = $form->values;
			$order = $values['category'] == null ? 'name' : 'position';

			$this->redirect('this', array('grid-order' => $order.' ASC', 'category' => $values['category']));
		}

		/**
		 * create options for categories select in tree view
		 * @param int $pid
		 * @param string $level
		 */
		public function getCategoriesTree ($pid = 0, $level = '') {
			$categories = $this->model->getCategories()->where('sections_id', 0)->where('pid', $pid);

			foreach ($categories as $category) {
				$this->categories[$category->id] = $level.$category->name;

				$this->getCategoriesTree($category->id, $level.'- ');
			}
		}

// 		public function handleChangeProductType () {
// 			$values = $_GET;
// 			$values['infos']['pid'] = isset($values['infos']['pid']) ? $values['infos']['pid'] : ($this->product ? $this->product->pid : '');
// 			$values['infos']['default'] = $this->product ? $this->product->default : null;

// 			$form = $this->getComponent('addForm');

// 			if (isset($values['settings']['type']) && $values['settings']['type'] == 1) {
// 				$products = $this->model->getProducts()->where('pid', 0)->order('name ASC');

// 				$form['settings']->addSelect('pid', 'Skupina:', $products->fetchPairs('id', 'name'))
// 					->setPrompt('--Žádná--');

// 				if (isset($values['settings']['pid']) && !empty($values['settings']['pid'])) {
// 					$form['settings']->addCheckbox('default', 'Defaultní produkt:');
// 				}
// 			}

// 			$this->invalidateControl('product');

// // 			unset($values['do']);

// 			$form->setValues($values);
// 		}

		public function handleDeleteVersion ($id, $vid) {
			$this->presenter->model->getProducts()->wherePrimary($vid)->delete();

			$this->presenter->redirect('this', array('version' => null, 'amountDiscount-order' => null));
		}

		public function handleDeleteRelated ($id, $rid) {
			$ids = (array)$rid;

			$this->model->getProductsRelated()->where('id_products', $id)->where('products_id', $ids)->delete();
		}

		public function createComponentGrid () {
			return new \AdminModule\DataGrid($this->products);
		}

		public function createComponentAmountDiscount () {
			return new AmountDiscount($this->discounts);
		}

		public function createComponentVersions () {
			return new VersionsGrid($this->versions);
		}

		public function createComponentProductsRelated () {
			/** když je zaplé verzování */
// 			return new ProductsRelatedGrid($this->model->getProductsRelatedInserted()->select('*')->where('id_products', $this->id)->group('galleries_id'));

			return new ProductsRelatedGrid($this->model->getProductsRelated()->select('products.*')->where('id_products', $this->id)->group('galleries_id'));
		}

		public function createComponentTrash () {
			return new ProductsTrashGrid($this->products);
		}

		public function getReferer() {
			if (!empty($this->context->httpRequest->referer)) {
				return $this->context->httpRequest->referer->absoluteUrl;
			}
			else return $this->link('Products:');
		}

		public function addSelects ($values, $data, $cat, $form) {
			$categories = $this->propertiesCategories->fetchPairs('id', 'id');
			$category = $this->model->getCategories()->where('position > ?', $cat->position)->where('sections_id', -2)->where('id', array_values($categories))->order('position ASC')->fetch();
// 			$shopProperties = $category->related('shop_properties');
// 			$category = $this->propertiesCategories->fetch();

			if ($category) {
				$nextCategory = $this->model->getCategories()->where('position > ?', $category->position)->where('sections_id', -2)->order('position ASC')->fetch();

				foreach ($values as $value) {
					$container = $form->addContainer('container'.$value);

					$container->addMultiselect($category->id, $this->properties[$cat->id][$value].' - '.$category->name, $this->properties[$category->id])
						->setRequired()
						->getControlPrototype()->class('chosen '.($nextCategory ? 'reload' : ''));

					if (isset($data['container'.$value][$category->id])) {
						$this->addSelects($data['container'.$value][$category->id], $data['container'.$value], $category, $container);
					}
				}
			}
		}

		public function createComponentVariations () {
			$this->category = $this->propertiesCategories->fetch();
			$form = new Form();

			$form->getElementPrototype()->addClass('form-horizontal');

			/*if (!$this->isAjax()) {
				$form->getElementPrototype()->changeVariations('true');
			}*/

			$form->addGroup('Variace');
			$form->addMultiselect('root', $this->category->name, $this->properties[$this->category->id])
				->setRequired()
				->getControlPrototype()->class('chosen reload');

			$form->addHidden('referer', $this->getReferer());

			$form->onSuccess[] = array($this, 'addVariations');

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		public function handleChangeVariations () {
			$values = $_GET;
			$form = $this->getComponent('variations');

			if (isset($values['root'])) {
				$this->addSelects($values['root'], $values, $this->category, $form);
			}

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('save', 'Uložit');

			$this->invalidateControl('variations');

			$form->setValues($values);
		}

		public function addVariations ($form) {
			$values = $form->httpData;
			$referer = $values['referer'];

			unset($values['referer']);
			$this->saveVariations($values, true);

			$json = $this->getProductPropertiesJson($this->id);
			$this->model->getProducts()->wherePrimary($this->id)->update(array('properties' => $json));

			$this->flashMessage('Variace produktu uloženy');
			$this->redirectUrl($referer);
		}

		public function saveVariations ($values, $pid = 0, $properties = array()) {
			$array = $pid == 0 ? $values['root'] : reset($values);

			foreach ($array as $value) {
				$properties[$pid] = $value;
				$categories = $this->model->getShopProperties()->fetchPairs('id', 'categories_id');
				$sortedProperties = array_flip($properties);
				$sortedProperties = array_intersect_key($categories, $sortedProperties);

				asort($sortedProperties);

				$sortedProperties = array_flip($sortedProperties);

				if (isset($values['container'.$value])) {
					$this->saveVariations($values['container'.$value], $pid+1, $properties);
				}
				else {
					$data = array();
					$data['pid'] = $this->id;

					foreach ($sortedProperties as $key => $property) {
						$data['p_'.$property] = true;
					}

					if (!$this->model->getProductsProperties()->where($data)->fetch()) {
						$product = $this->product->toArray();
						$lastID = $this->model->getProducts()->insert(array());
						$product['products_id'] = $lastID;
						$product['pid'] = $this->id;
						$product['galleries_id'] = $this->model->getGalleries()->insert(array());
						$product['filestores_id'] = $this->model->getFilestores()->insert(array());
						$product['id'] = $lastID;
						$product['properties'] = null;

						$lastID->update($product);

						foreach ($this->model->getProductsCategories()->where('products_id', $this->id) as $category) {
							$cat = $category->toArray();
							$cat['products_id'] = $lastID;

							unset($cat['id']);
							$this->model->getProductsCategories()->insert($cat);
						}

						foreach ($this->model->getProductsRelated()->where('id_products', $this->id) as $related) {
							$rel = $related->toArray();
							$rel['id_products'] = $lastID;

							unset($rel['id']);
							$this->model->getProductsRelated()->insert($rel);
						}

						$data['products_id'] = $lastID;

						$this->model->getProductsProperties()->insert($data);
					}
				}
			}
		}

		/**
		 * factory for add properties to product
		 * @return \Nette\Application\UI\Form
		 */
		public function createComponentProductProperties () {
			$form = new Form();

			$form->getElementPrototype()->addClass('form-horizontal');

			$form->addGroup('Vlastnosti produktu');
			foreach ($this->propertiesCategories as $category) {
				$form->addSelect('category'.$category->id, $category->name, $category->related('shop_properties')->fetchPairs('id', 'name'));
			}

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('add', 'Pokračovat');

			$form->onSuccess[] = callback($this, 'addProperties');

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		/**
		 * add properties to product
		 * @param \Nette\Application\UI\Form $form
		 */
		public function addProperties ($form) {
			$values = $form->values;
			$data['products_id'] = $this->product->id;
			$data['pid'] = $this->product->id;

			foreach ($values as $property) {
				$data['p_'.$property] = true;
			}

			$this->model->getProductsProperties()->insert($data);

			$json = $this->getProductPropertiesJson($this->product->id);
			$this->product->update(array('properties' => $json));

			$this->redirect('Products:gallery', array('id' => $this->product->galleries_id, 'gid' => 0));
		}

		/**
		 * create multidimensional array from all properties of given product
		 * @param $id
		 * @return null|string
		 * @throws \Nette\Utils\JsonException
         */
		public function getProductPropertiesJson ($id) {
			$properties = $this->model->getShopProperties()->where('categories_id', array_values($this->propertiesCategories->fetchPairs('id', 'id')))->fetchPairs('id', 'categories_id');
			$productProperties = $this->model->getProductsProperties()->where('products_properties.pid', $id)->where('products.visibility', 1);
			$productPropertiesArray = array();

			foreach ($productProperties as $property) {
				foreach ($property->toArray() as $key => $val) {
					if ($val == 1 && strpos($key, 'p_') !== false) {
						$key = Strings::replace($key, '/p_/');

						if (isset($properties[$key])) {
							$productPropertiesArray[$properties[$key]][] = $key;
						}
					}
				}
			}

			foreach ($productPropertiesArray as $key => $val) {
				$productPropertiesArray[$key] = array_unique($val);

				sort($productPropertiesArray[$key]);
			}

			if (count($productPropertiesArray)) {
				return Json::encode($productPropertiesArray);
			}
			else return null;
		}

		public function handleCopy ($id, $sid, $lang) {
			foreach ($this->product as $key => $val) {
				if (Strings::match($key, '/_'.$lang.'/')) {
					$index = Strings::replace($key, '/_'.$lang.'/');

					$values[$key] = $this->product->$index;
				}
			}

			if (count($values)) {
				$this->product->update($values);
			}

			$this->redirect('this');
		}
	}
