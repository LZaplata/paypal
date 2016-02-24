<?php
	namespace FrontEshopModule;

	use AdminModule\SignPresenter;

	use Nette\Application\UI\Control;

	class Userbar extends Control {
		public function __construct($parent, $name) {
			parent::__construct();
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/userbar.latte');
			
			$this->template->eshop = $this->presenter->model->getPagesModules()->where('modules_id', 3)->where('position', 1)->where('pages_id != ?', 1)->fetch()->pages->url;
			$this->template->setTranslator($this->presenter->translator);
			
			$this->template->render();
		}
		
		public function createComponentSignInForm () {
			$form = $this->presenter->createComponentSignInForm();
			
			$form->getElementPrototype()->class('form');
			
			$form['email']->setAttribute('placeholder', 'e-mail');
			$form['password']->setAttribute('placeholder', 'heslo');
			
			$form->onError[] = function () {
				$this->template->error = true;
			};
			
			return $form;
		}
	}