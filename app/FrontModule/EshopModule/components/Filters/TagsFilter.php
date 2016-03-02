<?php
	namespace FrontEshopModule\Filters;
	
	use Nette\Application\UI\Control;
	use Nette\Application\UI\Form;
	use Nette\Forms\Rendering\BootstrapFormRenderer;
	use Nette\Utils\Strings;
	use Nette\Http\Url;
	use Nette\Utils\Json;
								
	class TagsFilter extends Control {
		public $tags;
		public $activeTags;

		public function __construct($parent, $name) {
			parent::__construct($parent, $name);

			$this->tags = $this->presenter->model->getTags()->fetchPairs("id", "name");
			$this->activeTags = $this->presenter->getParameters();
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/tagsFilter.latte');
			
			$this->template->render();
		}

		/**
		 * filter form factory
		 * @return \Nette\Application\UI\Form
		 */
		public function createComponentFilterForm () {
			$form = new Form();
			
			$form->getElementPrototype()->class('form-horizontal');
			
			$form->addCheckboxList("tag", null, $this->tags);

			$form->setRenderer(new BootstrapFormRenderer());
			
			if ($this->activeTags) {
				$form->setValues($this->activeTags);
			}
			
			return $form;
		}
		
		/**
		 * handler for ajax filter
		 */
		public function handleFilter () {
			$values = $_GET;
			parse_str(Url::unescape($values['data']), $data);

			if (isset($values['tag'])) {
				unset($values['tag']);
			}

			unset($values['do']);
			unset($values['data']);
			unset($data['do']);
			
			$this->activeTags = array_merge($values, $data);
			$this->template->url = $this->presenter->link('this', $this->activeTags);
				
			$this->invalidateControl('url');			
			$this->invalidateControl('filter');			
			$this->presenter->invalidateControl('products');
		}
		
		/**
		 * filter products
		 */
		public function filterProducts () {
			$values = $_GET;

			if ($this->presenter->isAjax() && isset($values['data'])) {
				parse_str(Url::unescape($values['data']), $data);
			}
			else {
				$data = $values;
			}

			if (isset($data["tag"])) {
				$products = $this->presenter->model->getProductsTags();
				$array = array();

				foreach ($data["tag"] as $tag) {
					$p = clone $products;
					$p->where("tags_id", $tag);
					$p = $p->fetchPairs("id", "products_id");

					if (empty($array)) {
						$array = $p;
					} else {
						$array = array_intersect($array, $p);
					}
				}

				$this->presenter->products->where('products.id', array_values($array));
			}
		}
	}