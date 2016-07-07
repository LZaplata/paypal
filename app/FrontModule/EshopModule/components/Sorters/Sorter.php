<?php
	namespace FrontEshopModule\Sorters;
	
	use Nette\Http\Url;

	use Nette\Application\UI\Form;

	use Nette\Application\UI\Control;
use Nette\Forms\Rendering\BootstrapFormRenderer;
	
	class Sorter extends Control {
		public $activeSorters;
		public $sorters;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
			
			$this->activeSorters = $this->presenter->getParameters();
			$this->sorters = array (
					'price ASC' => 'od nejlevnějšího',
					'price DESC' => 'od nejdražšího',
//					'name ASC' => 'A-Z',
//					'name DESC' => 'Z-A'
			);
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/sorter.latte');
			
			$this->template->activeSorters = $this->activeSorters;
			
			$this->template->render();
		}
		
		public function createComponentSorterForm () {
			$form = new Form();
			
			$form->getElementPrototype()->class('form-horizontal');
			
			$form->addSelect('sort', null, $this->sorters)
				->setPrompt('--řadit--')
				->getControlPrototype()->addClass('input-sm');
			
			if ($this->activeSorters) {
				$form->setValues($this->activeSorters);
			}
			
			$form->setRenderer(new BootstrapFormRenderer());
			$form->setTranslator($this->presenter->translator);
			
			return $form;
		}
		
		/**
		 * handler for ajax sort
		 */
		public function handleSort () {
			$values = $_GET;
			parse_str(Url::unescape($values['data']), $data);
			
			unset($values['do']);
			unset($values['data']);
			unset($data['do']);
			
			$this->activeSorters = array_merge($values, $data);
			
			$this->template->url = $this->presenter->link('this', $this->activeSorters);
				
			$this->invalidateControl('url');		
			$this->invalidateControl('sorter');	
			$this->presenter->invalidateControl('products');
		}
		
		public function sortProducts () {
			$values = $_GET;
			
			if ($this->presenter->isAjax() && isset($values['data'])) {
				parse_str(Url::unescape($values['data']), $data);

				$sorter = empty($data['sort']) ? "position ASC" : $data["sort"];
			}
			else {			
				$sorter = isset($values['sort']) ? $values['sort'] : 'position ASC';
			}

			if ($sorter == 'name ASC') {
				$sorter = $sorter;
			}
			
			if ($sorter == 'name DESC') {
				$sorter = $sorter;
			}

			$this->presenter->products->order($sorter);
		}
	}