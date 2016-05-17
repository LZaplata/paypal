<?php
	namespace FrontEshopModule;
	
	use Nette\Application\UI\Control;
	use Nette\Application\UI\Form;
	use Nette\Forms\Rendering\BootstrapFormRenderer;
	use Nette\Latte\Engine;
				
	class AddToCart extends Control {
		public $id;
		
		public function  __construct ($id) {
			$this->id = $id;
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/addToCart.latte');

			$this->template->setTranslator($this->presenter->translator);

			$this->template->render();
		}
		
		public function createComponentAddToCart () {
			$form = new Form();
		
			$form->getElementPrototype()->class('ajax form-horizontal');
		
			$form->addText('amount', null)
				->setRequired()
				->addRule(Form::PATTERN, 'Množství musí být větší než 0', '[1-9]{1}[0-9]*')
				->addRule(Form::INTEGER, 'Množství musí být číslo!');
		
			if (!$this->presenter->isLinkCurrent(':FrontEshop:Order:cart')) {
				$form->addSubmit('add', 'Vložit')
					->setDisabled($this->presenter->isLinkCurrent(':FrontEshop:Products:view') ? ($this->presenter->id ? false : (count($this->presenter->propertiesCategories) == 0 ? false : true)) : false);
				
				$values['amount'] = 1;
			}
			else {
				$form->addHidden('update');
				
				if ($this->presenter->user->loggedIn) {
					$values['amount'] = $this->presenter['cart']->products->fetch()->amount;
				}
				else {
					$values['amount'] = $this->presenter['cart']->products[$this->id]->amount;
				}
			}
		
			$form->addHidden('id', $this->id);
		
			$form->onSuccess[] = callback($this->presenter['cart'], 'handleAddToCart');
			
			$form->setDefaults($values);
		
			$form->setRenderer(new BootstrapFormRenderer());
		
			return $form;
		}
	}