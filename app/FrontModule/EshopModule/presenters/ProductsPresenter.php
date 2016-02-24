<?php
	namespace FrontEshopModule;

	use Nette\Mail\Message;

	use Nette\Latte\Engine;

	use Nette\Templating\FileTemplate;

	use Nette\Forms\Rendering\BootstrapFormRenderer;

	use Nette\Application\UI\Form;
	use Nette\Utils\Json;

	class ProductsPresenter extends BasePresenter {
		public $id;
		public $prod;
		public $properties;
		public $propertiesCategories;
		public $categoriesProperties;
		public $productProperties;
		public $productsID;
		/** var array */
		public $posts = array();
		public $parent;
		public $shopProperties;

		public function actionView () {
			if (!$this->getProduct()) {
				$this->error('', 408);
			}

			$this->propertiesCategories = $this->model->getCategoriesCategories()->select('categories.*')->where('id_category', array_values($this->getProductCategories()->fetchPairs('id', 'categories_id')))->order('position ASC')->fetchPairs('id', 'title');
			$this->shopProperties = $this->model->getShopProperties()->fetchPairs('id', 'name');

			if ($this->parent->properties != null) {
				$this->getCategoriesProperties();
			}
		}

		public function renderView () {
			$this->setView('layout'.$this->model->getPagesModules()->where('modules_id', 3)->fetch()->detail);

			$this->template->keywords = $this->prod ? $this->prod->keywords : null;
			$this->template->title = $this->prod ? $this->prod->title: null;
			$this->template->title_addition = $this->vendorSettings->title_products;
			$this->template->desc = $this->prod ? $this->prod->meta_description : null;
			$this->template->product = $this->prod;
			$this->template->settings = $this->settings;
			$this->template->currency = $this->currency == 'czk' ? $this->context->parameters['currency'] : $this->currency;
			$this->template->decimals = $this->presenter->currency == 'czk' ? 0 : 2;
			$this->template->layout = $this->model->getPagesModules()->where('modules_id', 3)->fetch()->layout;
			$this->template->posts = $this->posts;
			$this->template->homepage = false;
			$this->template->icons = $this->presenter->context->parameters['icons'];
			$this->template->setTranslator($this->translator);
		}

		public function getProduct () {
			$product = $this->model->getProducts()->select('*, title'.$this->lang.' AS title, keywords'.$this->lang.' AS keywords, meta_description'.$this->lang.' AS meta_description')->wherePrimary($this->pid)->where('visibility', 1)->where('trash', 0);
			$url = 'url'.$this->lang;

			if ($this->settings->expirationDate) {
				$product->where('expirationDateFrom <= ? AND expirationDateTo >= ?', date("Y-m-d H:i:s"), date("Y-m-d H:i:s"));
			}

			$this->parent = $this->prod = $product->fetch();

			if ($this->prod) {
				$p = $this->model->getProducts()->where('products_id', $this->prod->products_id)->order('date DESC')->fetch();

				if ($this->pid != $p->id) {
					$this->redirect('this', array('product' => $p->$url, 'pid' => $p->id));
				}
				else {
					return $this->prod;
				}
			}
			else return false;
		}

		public function getProductCategories () {
			return $this->model->getProductsCategories()->where('products_id', $this->pid);
		}

		public function createComponentPropertiesForm () {
			$form = new Form();

			$form->getElementPrototype()->class('form-horizontal');

			$prev = 0;
			$first = 0;
			foreach ($this->propertiesCategories as $key => $category) {
				if (isset($this->categoriesProperties[$key])) {
					$form->addSelect('category'.$key, $category, $this->categoriesProperties[$key])
						->setAttribute('onChange', 'changeProperties()')
						->setPrompt('--'.$category.'--')
						->setDisabled($prev == 0 ? false : ($this->getParameter('category'.$prev) && $this->getParameter('category'.$first) ? false : true));

					$prev = $key;

					if ($first == 0) {
						$first = $key;
					}
				}
			}

// 			$form->setValues($this->properties);

			$form->setRenderer(new BootstrapFormRenderer);

			return $form;
		}

// 		public function getDefaultProperties () {
// 			$this->productProperties = $this->model->getProductsProperties()->where('products_id', $this->pid)->fetch()->toArray();
// 			$this->productProperties = array_slice($this->productProperties, 3);
// 			$this->productProperties = array_keys(array_filter($this->productProperties, function ($item) {return $item ? $item : false;}));
// 		}

		public function getCategoriesProperties () {
			$properties = Json::decode($this->parent->properties);
			$shopProperties = $this->model->getShopProperties()->fetchPairs('id', 'position');

			foreach ($properties as $key => $property) {
				$properties->$key = array_flip(array_intersect_key($shopProperties, array_flip($property)));
			}
				
			foreach ($this->propertiesCategories as $key => $category) {
				if (isset($properties->$key)) {
					ksort($properties->$key);
					
					foreach ($properties->$key as $property) {
						$this->categoriesProperties[$key]['p_'.$property] = $this->shopProperties[$property];
					}
				}
			}
			
			/*
			foreach ($this->propertiesCategories as $key => $category) {
				$properties = $this->model->getShopProperties()->where('categories_id', $key);
				
				foreach ($properties as $property) {
					if (count($this->model->getProductsProperties()->where('pid', $this->pid)->where('p_'.$property->id, true))) {
						$this->categoriesProperties[$key]['p_'.$property->id] = $property->name;
					}
					
// 					if (in_array('p_'.$property->id, $this->productProperties)) {
// 						$this->properties['category'.$key] = 'p_'.$property->id;
// 					}
				}
				
				if (isset($this->categoriesProperties[$key])) {
					asort($this->categoriesProperties[$key]);
				}
			}
			*/
		}

		public function handleChangeProperties () {
			$values = $_GET;
			$values = array_filter($values, function ($item) {return $item ? $item : false;});
			$getProduct = true;

			unset($values['do']);
			ksort($values);

			$i = 0;
			foreach ($this->propertiesCategories as $key => $category) {
				if ($i > 0) {
					if (!key_exists('category'.$key, $values)) {
						$getProduct = false;
					}

					$data = $values;

					unset($data['category'.$key]);
					$this->reduceCategoryProperties($data, $key);
				}
				else {
					if (!key_exists('category'.$key, $values)) {
						$getProduct = false;
						$values = array();
					}
				}

				$i++;
			}

			if ($getProduct) {
				$properties = array_flip($values);
				$properties = array_fill_keys(array_keys($properties), true);

				if ($p = $this->model->getProductsProperties()->where('pid', $this->pid)->where($properties)->fetch()) {
					$this->prod = $p->products;
					$this->id = $p->id;
				}

				$this->invalidateControl('info');
				$this->invalidateControl('name');
				$this->invalidateControl('gallery');
				$this->invalidateControl('text');
			}

			$this->invalidateControl('price');
			$this->invalidateControl('propertiesForm');

			foreach ($values as $key => $value) {
				if (key_exists($value, $this->categoriesProperties[preg_replace('/category/', '', $key)])) {
					$this['propertiesForm'][$key]->setValue($value);
				}
			}

// 			$this['propertiesForm']->setValues($values);
		}

		public function reduceCategoryProperties ($values, $cid) {
			$properties = array_flip($values);
			$properties = array_fill_keys(array_keys($properties), true);
			$productsProperties = $this->model->getProductsProperties()->where('products_properties.pid', $this->pid)->where($properties)->where('products.visibility', 1);
			$commonProperties = array();

			foreach ($productsProperties as $productsProperty) {
				$productsProperty = array_slice($productsProperty->toArray(), 3);
				$productsProperty = array_filter($productsProperty, function ($item) {return $item ? $item : false;});

				if (isset($this->categoriesProperties[$cid])) {
					$commonProperties = array_merge($commonProperties, array_intersect_key($this->categoriesProperties[$cid], $productsProperty));
				}
			}

			ksort($commonProperties, SORT_NATURAL);

			$this->categoriesProperties[$cid] = $commonProperties;
		}

		public function getParentProduct ($pid) {
			return $this->model->getProducts()->wherePrimary($pid)->fetch();
		}

		public function getProductsRelated () {
			/** pokud je zaplé verzování */
// 			return $this->model->getProductsRelatedInserted()->where('id_products', $this->prod->products_id)->group('galleries_id');

			return $this->model->getProductsRelated()->select('products.*')->where('id_products', $this->prod->products_id)->group('galleries_id');
		}

		public function handleGetPosts () {
			$this->posts = $this->model->getPosts()
								->where('products_id', $this->pid)
								->where('trash', 0)
								->where('visibility', 1)
								->where('posts_id', 0)->order('update DESC');

			$this->invalidateControl('posts');
			$this->invalidateControl('postsArea');
		}

		public function createComponentAddPost () {
			$form = new Form();

			$form->getElementPrototype()->class('form-horizontal ajax');

			$form->addText('name', 'Jméno')
				->setRequired('Vyplňte prosím jméno!');

			$form->addText('surname', 'Příjmení');

			$form->addText('email', 'E-mail')
				->setRequired('Vyplňte prosím e-mail!')
				->addRule(Form::EMAIL, 'Nesprávný formát e-mailu');

			$form->addTextarea('text', 'Text')
				->setRequired('Vyplňte prosím text!');

			$form->addSubmit('add', 'Vložit');

			$form->addText('nospam', 'Fill in „nospam“')
                ->addRule(Form::FILLED, 'You are a spambot!')
                    ->addRule(Form::EQUAL, 'You are a spambot!', 'nospam');

			$form->onSuccess[] = callback($this, 'addPost');

			if ($form->hasErrors) {
				$this->template->errors = true;
			}

			$this->invalidateControl('user');

			if ($this->user->loggedIn) {
				$values['name'] = $this->user->identity->name;
				$values['surname'] = $this->user->identity->surname;
				$values['email'] = $this->user->identity->email;

				$form->setDefaults($values);
			}

			$form->setRenderer(new BootstrapFormRenderer());

			return $form;
		}

		public function addPost ($form) {
			$values = $form->httpData;
			$texy = new \Texy();

			$values['products_id'] = $this->pid;
			$values['posts_id'] = isset($values['posts_id']) ? $values['posts_id'] : 0;
			$values['date'] = date('Y-m-d H:i:s');
			$values['update'] = date('Y-m-d H:i:s');
			$values['text'] = $texy->process($values['text']);

			if ($this->user->loggedIn) {
				$values['users_id'] = $this->user->id;
			}

			unset($values['add']);
			unset($values['mail']);
			unset($values['do']);
			unset($values['nospam']);

			$post = $this->model->getPosts()->insert($values);

			if ($values['posts_id'] != 0) {
				$post = $this->model->getPosts()->where('id', $values['posts_id'])->fetch();

				if ($values['email'] != $post->email) {
					$this->sendAuthorEmail($post);
				}

				$post->update(array('update' => $values['update']));
			}

			$this->sendOfficeEmail($post);
			$this->handleGetPosts();

			unset($this->template->errors);
			unset($values['text']);

			$form->setValues($values, true);

			$this->invalidateControl('user');
		}

		public function handleDeletePost ($id) {
			$post = $this->model->getPosts()->wherePrimary($id)->fetch();

			if ($post->date->format("Y-m-d H:i:s") >= date("Y-m-d H:i:s", strtotime("-1 minute"))) {
				$post->update(array('trash' => 1));
				$this->model->getPosts()->where('posts_id', $id)->update(array('trash' => 1));

				$this->handleGetPosts();
			}
		}

		public function handleReply ($id) {
			$this['addPost']->addHidden('posts_id', $id);

			$this->template->reply = $this->model->getPosts()->wherePrimary($id)->fetch();
			$this->template->errors = true;

			$this->invalidateControl('user');
			$this->invalidateControl('postsArea');
		}

		public function handleCancelReply () {
			unset($this['addPost']['posts_id']);
			unset($this->template->reply);

			$this->invalidateControl('user');
			$this->invalidateControl('postsArea');
		}

		public function sendAuthorEmail ($post) {
			$template = new FileTemplate(APP_DIR.'/FrontModule/EshopModule/templates/Products/authorEmail.latte');
			$template->registerFilter(new Engine());
			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
			$template->presenter = $this;
			$template->host = $this->context->parameters['host'];
			$template->post = $post;

			$mail = new Message();
			$mail->setFrom($this->contact->email, $this->contact->name);
			$mail->addTo($post->email, $post->name.' '.$post->surname);
			$mail->setSubject('Odpověď na váš příspěvek v diskuzi');
			$mail->setHtmlBody($template);

			$this->mailer->send($mail);
		}

		public function sendOfficeEmail ($post) {
			$template = new FileTemplate(APP_DIR.'/FrontModule/EshopModule/templates/Products/officeEmail.latte');
			$template->registerFilter(new Engine());
			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
			$template->presenter = $this;
			$template->host = $this->context->parameters['host'];
			$template->post = $post;
			$users = $this->model->getUsers()->where('posts', 1);

			if (count($users)) {
				$mail = new Message();
				$mail->setFrom('diskuze@eshop.cz', $this->contact->name);

				foreach ($users as $user) {
					$mail->addTo($user->email, $user->name);
				}

				$mail->setSubject('Nový příspěvek v diskuzi');
				$mail->setHtmlBody($template);

				$this->mailer->send($mail);
			}
		}

		public function getFiles ($data) {
			$files = $data->filestores->related('filestores_files')->order('highlight DESC, position ASC')->where('visibility', 1);

			return $files;
		}
	}