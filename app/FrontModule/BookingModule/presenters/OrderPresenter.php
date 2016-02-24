<?php
	namespace FrontBookingModule;
	
	use Nette\Mail\Message;

	use Nette\Latte\Engine;

	use Nette\Templating\FileTemplate;

	use Nette\Application\UI\Form;
	
	use Nette\Forms\Rendering\BootstrapFormRenderer;

	class OrderPresenter extends BasePresenter {
	
		public $order;
	
		public function renderDefault () {
			$this->template->keywords = "rezervace, košík";
			$this->template->lang = $this->lang == 'cs' ? null : '_'.$this->lang;
		}
				
		public function actionContact () {
			if (!count($this->sectionSession->objects)) {
				$this->redirect('Room:view');
			}
		}
		
		public function actionSummary () {			
			if (!isset($this->sectionSession->name)) {
				$this->redirect('Order:contact');
			}
		}
		
		public function actionSend () {							
			if (!count($this->sectionSession->objects)) {
				$this->redirect('Room:view');
			}
			
			$objects = $this->sectionSession->objects;
			
			unset($this->sectionSession->objects);
			$this->sectionSession->date = new \DateTime();
			$this->sectionSession->users_id = $this->user->isLoggedIn() ? $this->user->getId() : NULL;
		
			$lastID = $this->model->getBooking()->insert($this->sectionSession);
				
				foreach ($objects as $object) {
					$object->booking_id = $lastID;			
					$this->model->getBookingBookings()->insert((array)$object);
				}
			
			$this->sectionSession->remove();
			//$this['bookingCart']->getObjects();	

			$this->order = $this->model->getBooking()->wherePrimary($lastID)->fetch();
			
			$this->sendOfficeEmail($this->order);
			$this->sendCustomerEmail($this->order);
		}
	
		public function renderContact () {
			$this->renderDefault();
			
			$this->template->title = 'Kontaktní údaje';
			$this->template->keywords = 'Kontaktní údaje';
			$this->template->desc = 'Kontaktní údaje';
		}

		public function renderSummary () {
			$this->renderDefault();
			
			$this->template->title = 'Souhrn objednávky';
			$this->template->keywords = 'Souhrn objednávky';
			$this->template->desc = 'Souhrn objednávky';			
			$this->template->order = $this->sectionSession;
		}
		
		public function renderSend () {
			$this->renderDefault();
			
			$this->template->title = 'Odeslání objednávky';
			$this->template->keywords = 'Odeslání objednávky';
			$this->template->desc = 'Odeslání objednávky';
			$this->template->order = $this->order;
		}	
		
		public function handledeleteFromCart($id){
				if (isset($this->sectionSession->objects[$id])) unset($this->sectionSession->objects[$id]);	
				$this->redirect('this');
		}
		
		public function createComponentContact () {
			$form = new Form();
			
			$form->getElementPrototype()->class('form-horizontal');
			
			$form->addGroup('Kontaktní údaje');
			$form->addText('name', 'Jméno:')
				->setRequired('Vyplňte jméno!');
			
			$form->addText('surname', 'Příjmení:')
				->setRequired('Vyplňte příjmení!');
			
			$form->addText('phone', 'Telefon:')
				->setRequired('Vyplňte telefon!');
			
			$form->addText('email', 'E-mail:')
				->setRequired('Vyplňte e-mail!')
				->addRule(Form::EMAIL, 'Nesprávný formát e-mailu!');
			
			$form->addSubmit('transport', 'Souhrn')
				->onClick[] = callback($this, 'submitContact');
			
			$order = $this->sectionSession;
			
			if (isset($order->name) && $order->name == null || !isset($order->name)) {
				$form->setValues($this->model->getUsers()->wherePrimary($this->user->id)->fetch());
			}
			else $form->setValues($order);
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		public function submitContact ($button) {
			$values = $button->parent->values;

				foreach ($values as $key => $value) {
					$this->sectionSession->$key = $value;
				}
				
			$this->redirect('Order:summary');
			
		}
		
		public function getObject($id){
			return $this->model->getBookingObjects()->wherePrimary($id)->fetch();
		}
		
		public function sendCustomerEmail ($order) {
			$template = new FileTemplate(APP_DIR.'/FrontModule/BookingModule/templates/Order/customerEmail.latte');
			$template->registerFilter(new Engine());
			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
			$template->order = $order;
			$template->presenter = $this;
			$template->host = $this->context->parameters['host'];
			$template->decimals = $this->currency == 'czk' ? 0 : 2;
		
			$mail = new Message();
			$mail->setFrom($this->contact->email, $this->contact->name);
			$mail->addTo($order->email, $order->name.' '.$order->surname);
			$mail->setSubject('Rezervace č. '.$order->id);
			$mail->setHtmlBody($template);
		
			$this->mailer->send($mail);
		}
		
		public function sendOfficeEmail ($order) {
			$template = new FileTemplate(APP_DIR.'/FrontModule/BookingModule/templates/Order/officeEmail.latte');
			$template->registerFilter(new Engine());
			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
			$template->order = $order;
			$template->presenter = $this;
			$template->host = $this->context->parameters['host'];
			$template->decimals = $this->currency == 'czk' ? 0 : 2;
		
			$mail = new Message();
			$mail->setFrom('rezervace@shop.cz');
			$mail->addTo($this->contact->email, $this->contact->name);
			$mail->setSubject('Nová rezervace - č. '.$order->id);
			$mail->setHtmlBody($template);
		
			$this->mailer->send($mail);
		}
	}