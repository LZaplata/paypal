<?php
	namespace AdminBookingModule;

	use AdminModule\BasePresenter;

	use Nette\Mail\Message;

	use Nette\Latte\Engine;

	use Nette\Templating\FileTemplate;

	use Nette\Utils\Strings;

	use Nette\Application\UI\Form,
		Nette\Utils\Html;	
use Nette\Forms\Rendering\BootstrapFormRenderer;
	
	class OrdersPresenter extends BasePresenter {
		public $id;
		public $urlID;
		public $orders;
		public $order;
		public $statesOrder;
		public $booking = true;
		/** @persistent */
		public $search = array();
		public $objects = array();
		
		public function startup() {
			parent::startup();
			$this->statesOrder = $this->context->parameters['orderStatesBooking'];
		}
		
		public function actionDefault () {
			$this->urlID = 0;			
			$this->orders = $this->model->getBooking();		
			
			if (!$this['bookings']->getParameter('order')) {
				$this->redirect('this', array('bookings-order' => 'date DESC'));
			}
		}
		
		public function fooo(){
			$this->redrawControl('price');
		}
		
		public function actionEdit ($id) {
			$this->urlID = 0;
			$this->order = $this->model->getBooking()->wherePrimary($id)->fetch();
			
			if($this->search){
				$this->objects = $this->searchingObjects();
				$this->getComponent('search')->setDefaults($this->search);
			}
			$this->getComponent('order')->setDefaults($this->order);
			
			if (!$this['bookingBookings']->getParameter('order')) {
				$this->redirect('this', array('bookingBookings-order' => 'dateFrom DESC'));
			}
		}
		
		public function renderEdit () {
			$this->template->objects = $this->objects;
			$this->template->order = $this->order;
		} 
		
		public function handleAddToBooking($objectId,$price){
			$data['booking_objects_id'] = $objectId;
			$data['price'] = $price;
			$data['booking_id'] = $this->order->id;
			$data['dateTo'] = $this->search['dateTo'];
			$data['dateFrom'] = $this->search['dateFrom'];
			$object = $this->model->getBookingBookings()->insert($data);
			$price = $object->price;
			$order = $this->model->getBooking()->wherePrimary($this->order->id)->fetch();
			$order->update(array('price'=>$this->order->price + $price));
			
			$this->sendChangeBookingMail($order);
			
			$this->order = $order;
			$this->objects = $this->searchingObjects();
						
			$this->redrawControl('price');
			$this->getComponent('bookingBookings')->redrawControl();
			$this->redrawControl('objects');
			//$this->redirect('this');
		}
		
		public function handleDelete($id) {
			$ids = (array)$id;
			
			foreach ($ids as $id) {
				$order = $this->model->getBooking()->wherePrimary($id)->fetch();
				
				$this->sendDeleteBookingMail($order);
				$order->delete();
			}			
		
			$this->flashMessage('Položka/y byla smazána!');
		}
		
		public function createComponentSearch () {
			$form = new Form;
			
			$form->getElementPrototype()->addClass('form-horizontal');
			
			$form->addGroup(null);
			$form->addText('dateFromAlt','Od')
				->setOmitted()
				->getControlPrototype()->class('datepicker');
			$form->addText('dateToAlt','Do')
				->setOmitted()
				->getControlPrototype()->class('datepicker');
			$form->addSelect('rid','Místnost',$this->model->getBookingRooms()->where('pid  != ?', 0)->fetchPairs('id','name'));
			
			$form->addHidden('dateFrom');
			$form->addHidden('dateTo');
			
			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('submit','Vyhledat');
						
			$form->onSuccess[] = callback($this, 'search');

			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		public function search ($form) {
			$values = $form->getValues(TRUE);
			$this->redirect('this',array('search'=>$values));						
		}	
		
		public function searchingObjects(){
			$values = $this->search;
			if(count($values)){
				$bookings = $this->model->getBookingBookings()->where('(dateFrom <= ? AND dateTo >= ?) OR (dateFrom <= ? AND dateTo >= ?)', $values['dateFrom'], $values['dateFrom'], $values['dateTo'], $values['dateTo'])->fetchPairs('id', 'booking_objects_id');	
				$objects = $this->model->getBookingObjects()->where('booking_rooms_id', $this->search['rid']);
				if (count($bookings)) {                                                                 
					$objects->where('id NOT IN ?', array_values($bookings));
				}			
				return $objects;
			}else{
				return array();
			}
		}			
		
		public function createComponentOrder () {
			$form = new Form;
			
			$form->getElementPrototype()->setClass('form-horizontal');
			
			$form->addGroup(null);
			$form->addText('name', 'Jméno');	
			$form->addText('surname', 'Příjmení');
			$form->addText('email', 'E-mail');
			$form->addText('phone', 'Telefon');
			$form->addSelect('state','Stav', $this->statesOrder);
			
			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('submit','Upravit');
			
			$form->onSuccess[] = callback($this, 'editOrder');
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		public function editOrder($form){
			$values = $form->getValues();
			
			$this->model->getBooking()->wherePrimary($this->order->id)->update($values);
			
			if ($this->order->state != $values['state']) {
				$this->sendChangeStateMail(array('id' => $this->order->id));
			}
			
			$this->sendChangeBookingMail($this->order);
			
			$this->redirect('this');
		}
		
		public function createComponentBookings(){
			return new BookingsGrid($this->orders);
		}
		
		public function createComponentBookingBookings(){
			return new BookingBookingsGrid($this->model->getBookingBookings()->select('booking.*, booking_bookings.*, booking_objects.name, booking_objects.capacity')->where('booking_id', $this->order->id));
		}
		
		public function getObjectPrice ($object) {
			$room = $object->booking_rooms;
			//ceny objektu 
			$prices = $object->related('booking_prices');					
			if ($this->search) {
				//od-do				
				$startDate = strtotime($this->search['dateFrom']);
				$endDate = strtotime($this->search['dateTo']);				
				//pokud bude vice cen pro danny objekt
				if($prices->count() > 1){
					//pocatecni cena
					$price = 0;
					//projizdim od - do 
					while ($startDate < $endDate) {
						$priceObject = clone $prices;
						//ziska jednu cenu od dateFrom 
						$priceObject = $priceObject->where('dateFrom < ?',date('Y-m-d',$startDate))->order('dateFrom DESC')->limit(1)->fetch();
						//dump($priceObject->price);
						$price += $priceObject->price;						
						//pricte dle intervalu a jednotky
						$startDate = strtotime('+'.$room->interval * $room->interval_divisor.' minutes', $startDate);						
					}
				}else{
					$price = $prices->fetch();
					//zjistíme časový interval mezi zadanými daty v minutách				
					$interval = ($endDate - $startDate) / 60;
					//kolikrát se cena objektu musí násobit vzhledem k intervalu u pokoje
					$multi = $interval / ($room->interval * $room->interval_divisor);
					//pokud je nastaven interval na noc, odečteme "1 den"
// 					if ($room->interval_divisor == 1440) {
// 						$multi -= 1;
// 					}
				}
			}
			
			if ($prices->count() > 1) {
				return $price;
			}else{
				return $price->price * $multi;
			}
		}
		
		public function sendChangeStateMail ($values) {
			$order = $this->model->getBooking()->wherePrimary($values['id'])->fetch();
			
			$template = new FileTemplate(APP_DIR.'/AdminModule/BookingModule/templates/Orders/email.latte');
			$template->registerFilter(new Engine());
			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
			$template->order = $order;
			$template->presenter = $this;
			$template->host = $this->context->parameters['host'];
			$template->css = file_get_contents(WWW_DIR.'/adminModule/css/bootstrap.min.css');
				
			$mail = new Message();
			$mail->setFrom($this->contact->email, $this->contact->name);
			$mail->addTo($order->email, $order->name.' '.$order->surname);
			$mail->setSubject('Změna stavu rezervace č. '.$order->id);
			$mail->setHtmlBody($template);
				
			$this->mailer->send($mail);
		}
		
		public function sendChangeBookingMail ($order) {				
			$template = new FileTemplate(APP_DIR.'/AdminModule/BookingModule/templates/Orders/emailChange.latte');
			$template->registerFilter(new Engine());
			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
			$template->order = $order;
			$template->presenter = $this;
			$template->host = $this->context->parameters['host'];
			$template->css = file_get_contents(WWW_DIR.'/adminModule/css/bootstrap.min.css');
		
			$mail = new Message();
			$mail->setFrom($this->contact->email, $this->contact->name);
			$mail->addTo($order->email, $order->name.' '.$order->surname);
			$mail->setSubject('Změna rezervace č. '.$order->id);
			$mail->setHtmlBody($template);
		
			$this->mailer->send($mail);
		}
		
		public function sendDeleteBookingMail ($order) {
			$template = new FileTemplate(APP_DIR.'/AdminModule/BookingModule/templates/Orders/emailDelete.latte');
			$template->registerFilter(new Engine());
			$template->registerHelperLoader('Nette\Templating\Helpers::loader');
			$template->order = $order;
			$template->presenter = $this;
			$template->host = $this->context->parameters['host'];
			$template->css = file_get_contents(WWW_DIR.'/adminModule/css/bootstrap.min.css');
		
			$mail = new Message();
			$mail->setFrom($this->contact->email, $this->contact->name);
			$mail->addTo($order->email, $order->name.' '.$order->surname);
			$mail->setSubject('Zrušení rezervace č. '.$order->id);
			$mail->setHtmlBody($template);
		
			$this->mailer->send($mail);
		}
	}