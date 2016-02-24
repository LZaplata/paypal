<?php
	namespace FrontModule;
	
	use Nette\Mail\Message;

	use Nette\Forms\Rendering\BootstrapFormRenderer;

	use Nette\Latte\Engine;

	use Nette\Templating\FileTemplate;

	use Nette\Application\UI\Form;

	use Nette\Application\UI\Control;

	class ContactForm extends Control {
		public $settings;
		
		public function __construct($parent, $name) {
			parent::__construct($parent, $name);
			
			$this->settings = $parent->model->getSettings()->fetch();
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/contactForm.latte');
			
			$this->template->setTranslator($this->presenter->translator);
			
			$this->template->render();
		}
		
		public function createComponentContactForm () {
			$form = new Form();
		
			$form->getElementPrototype()->class('form-horizontal');
		
			$form->addText('name', 'Jméno')
				->setRequired('Vyplňte prosím jméno!');
		
			$form->addText('email', 'E-mail')
				->addRule(Form::EMAIL, 'Špatný formát e-mailu')
				->setRequired('Vyplňte prosím e-mail!');
		
			$form->addTextarea('text', 'Text');
			
			$form->addText('nospam', null)
				->addRule(Form::FILLED, 'You are a spambot!')
				->addRule(Form::EQUAL, 'You are a spambot!', 'nospam')
				->getControlPrototype()->addClass('nospam');
		
			$form->addSubmit('send', 'Odeslat');
		
			$form->onSuccess[] = callback($this, 'sendMail');
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			$form->setTranslator($this->presenter->translator);
		
			return $form;
		}
		
		public function sendMail ($form) {	
			$values = $form->values;
			
			$template = new FileTemplate(__DIR__.'/mail.latte');
			$template->registerFilter(new Engine());
			$template->values = $values;
			$template->names = $form->components;
		
			$mail = new Message();
			$mail->setFrom($values['email'], $values['name']);
			
			if ($this->settings && $this->settings->contact_to) {
				$mail->addTo($this->settings->contact_to);
			}
			else {
				$mail->addTo($this->presenter->context->parameters['contact']['email'], $this->presenter->context->parameters['contact']['name']);
			}
			
			if ($this->settings && $this->settings->contact_cc) {
				$mail->addCc($this->settings->contact_cc);
			}
			
			if ($this->settings && $this->settings->contact_bcc) {
				$mail->addBcc($this->settings->contact_bcc);
			}
			
			$mail->setSubject('Vzkaz z kontaktního formuláře');
			$mail->setHtmlBody($template);
			
			$this->presenter->mailer->send($mail);
		
			$this->presenter->flashMessage('Zpráva byla odeslána');
			
			$form->setValues(array(), true);
			
			if ($this->presenter->isAjax()) {
				$this->invalidateControl('form');
			}
			else {
				$this->presenter->redirect('this');	
			}
		}
	}