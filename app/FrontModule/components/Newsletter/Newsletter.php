<?php
	namespace FrontModule;

	use Nette\Application\UI\Form;

	use Nette\Application\UI\Control;
	
	use Nette\Latte\Engine;

	use Nette\Templating\FileTemplate;
use Nette\Mail\Message;
	
	class Newsletter extends Control {
		public function __construct ($parent, $name) {
			parent::__construct($parent, $name);
		}
		
		public function createComponentNewsletter () {
			$form = new Form();
			
			$form->addText('email', 'Váš e-mail')
				->addRule(Form::EMAIL, 'Nesprávný formát e-mailu')
				->setRequired('Vyplňte prosím e-mail');
			
			$form->addHidden('newsletter', 1);
			
			$form->addSubmit('send', 'Odeslat');
			
			$form->onSuccess[] = callback ($this, 'saveEmail');
			
			return $form;
		}
		
		public function saveEmail ($form) {
			$values = $form->values;
			$ok = false;
			
			if ($user = $this->presenter->model->getUsers()->where('email', $values['email'])->fetch()) {
				if ($user->newsletter == 1) {
					$this->presenter->flashMessage('Uvedený e-mail již newsletter odebírá', 'error');	
				} 
				else {
					$user->update($values);
					$ok = true;
				}
			}
			else {
				$this->presenter->model->getUsers()->insert($values);
				$ok = true;
			}
			
			if ($ok) {
				$this->sendEmail($values);
				$this->sendInfoEmail($values);
					
				$this->presenter->flashMessage('Byl jste přihlášen k odběru newsletteru');
			}

			$this->presenter->redirect('this');
		}
		
		public function sendEmail ($values) {
			$template = $this->createTemplate()->setFile(__DIR__.'/mail.latte');
			$template->values = $values;
			
			$mail = new Message();
			$mail->addTo($values['email']);
			$mail->setFrom($this->presenter->context->parameters['contact']['email'], $this->presenter->context->parameters['contact']['name']);
			$mail->setSubject('Přihlášení k odběru newsletteru');
			$mail->setHtmlBody($template);
			
			$this->presenter->mailer->send($mail);
		}
		
		public function sendInfoEmail ($values) {
			$template = $this->createTemplate()->setFile(__DIR__.'/mailInfo.latte');
			$template->email = $values['email'];
				
			$mail = new Message();
			$mail->setFrom($values['email']);
			$mail->addTo($this->presenter->context->parameters['contact']['email'], $this->presenter->context->parameters['contact']['name']);
			$mail->setSubject('Nový odběratel newsletteru');
			$mail->setHtmlBody($template);
			
			$this->presenter->mailer->send($mail);
		}
		
		public function handleCancel () {		
			$values = $_GET;
			
			$user = $this->presenter->model->getUsers()->where('email', $values['email'])->fetch();
			$user->update(array('newsletter' => 0));
			
			$this->presenter->model->getUsersCategories()->where('users_id', $user->id)->delete();
			
			if (isset($values['id'])) {
				$this->presenter->model->getEmailsQueue()->wherePrimary($values['id'])->update(array('logout' => 1));
			}
			
			$this->presenter->flashMessage('Odběr newletteru byl zrušen');
			$this->redirect('this');
		}
		
		public function handleViewEmail () {
			$values = $_GET;
			
			if ($row = $this->presenter->model->getEmailsQueue()->wherePrimary($values['id'])->fetch()) {
				$row->update(array('view' => date('Y-m-d G:i')));
			}
		}
		
		public function render () {
			$this->template->setFile(__DIR__.'/newsletter.latte');
			$this->template->inline = false;
			$this->template->render();
		}
		
		public function renderInline () {
			$this->template->setFile(__DIR__.'/newsletter.latte');
			$this->template->inline = true;
			$this->template->render();
		}
	}