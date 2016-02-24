<?php
	namespace AdminBookingModule;

	use AdminModule\BasePresenter;

	use Nette\Utils\Strings;

	use Nette\Application\UI\Form,
		Nette\Utils\Html;	
use Nette\Forms\Rendering\BootstrapFormRenderer;
	
	class RoomsPresenter extends BasePresenter {
		public $id;
		public $ids;
		public $urlID;
		public $rooms;
		public $room;
		public $prices;
		public $hours;
		public $object;
		public $objects;
		public $divisors;
		
		public function startup() {
			parent::startup();
			
			$this->divisors = array(1 => 'minuta', 60 => 'hodina', 1440 => 'noc');
		}
		
		public function actionDefault ($pid) {
			$this->urlID = $pid;
			$this->rooms = $this->model->getBookingRooms()->where('pid'.($pid > 0 ? ' > ?' : ''), 0);
			
			if (!$this['rooms']->getParameter('order')) {
				$this->redirect('this', array('rooms-order' => 'name ASC', 'pid' => $pid));
			}			
		}
		
		public function actionAdd ($id) {
			$this->urlID = $id;
			$this->id = $id;
		}
		
		public function actionEdit ($id, $pid) {
			$this->id = $id;
			$this->urlID = $pid;
			$this->room = $this->model->getBookingRooms()->wherePrimary($id)->fetch();	
			$this->hours = $this->model->getBookingHours()->where('booking_rooms_id',$this->room->id);
			
			if (!$this['hours']->getParameter('order')) {
				$this->redirect('this', array('hours-order' => 'day DESC'));
			}
			
			$this->getComponent('editRoom')->setDefaults($this->room);
		}
		
		public function actionEditObject ($id, $pid) {
			$this->id = $id;
			$this->urlID = $pid;
			$this->object = $this->model->getBookingObjects()->wherePrimary($id)->fetch();
			$this->prices = $this->model->getBookingPrices()->where('booking_objects_id', $id);
		
			if (!$this['prices']->getParameter('order')) {
				$this->redirect('this', array('prices-order' => 'name ASC'));
			}		
		}
		
		public function actionEditObjects (array $ids) {
			$this->urlID = 1;
			$this->ids = $ids;
			$this->objects = $this->model->getBookingObjects()->where('id IN ?',$ids);
			$this->prices = $this->model->getBookingPrices()->where('booking_objects_id IN ?',$ids)->group('dateTo,dateFrom,price');
			
			if (!$this['pricesObjects']->getParameter('order')) {
				$this->redirect('this', array('pricesObjects-order' => 'name ASC'));
			}					
		}
		
		public function renderEditObjects () {
			$this->template->objects = $this->objects;
		}
		
		public function renderAdd () {
			$this->setView('edit');
		}
		
		public function createComponentCapacityForm () {
			$form = new Form;
			$form->addText('capacity', 'Kapacita:');
			$form->addSubmit('submit', 'Změnit');
			$form->onSuccess[] = $this->capacityFormSubmitted;
			return $form;
		}
		
		public function capacityFormSubmitted($form){
			$values = $form->getValues();
			$this->objects->update($values);
			$this->redirect('this');
		}
		
		public function createComponentEditRoom () {
			$form = new Form;
			
			$form->getElementPrototype()->addClass('form-horizontal');
			
			$form->addGroup(null);
			$form->addText('name','Název')
				->setAttribute('class', 'input-name');
			
			if ($this->urlID > 0) {
				$rooms = $this->model->getBookingRooms()->where('pid', 0)->fetchPairs('id','name');
				$form->addSelect('pid','Areál:',$rooms);
	// 			$form->addText('discount','Sleva %');
				$form->addSelect('interval_divisor', 'Jednotka intervalu', $this->divisors);
				$form->addText('interval','Interval:');
	// 			$form->addText('interval_min','Minimální interval');
	// 			$form->addText('interval_max','Maximální interval');
				$form->addText('capacity','Počet objektů:');
	// 			$form->addText('capacity_min','Minimální kapacita');
	// 			$form->addText('capacity_max','Maximální kapacita');
				$form->addRadioList('layout', 'Layout:', $this->createOptions($this->layoutsBooking))
					->setRequired('Vyberte prosím layout!')
					->getSeparatorPrototype()->setName(null);
			}
			
			$form->addHidden('referer', $this->getReferer());

			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('submit',$this->room ? "Upravit" : "Vytvořit");
			$form->onSuccess[] = callback($this, $this->room ? "editRoom" : "addRoom");		

			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		public function editRoom ($form) {
			$values = $form->getValues();
			$values['pid'] = isset($values['pid']) ? $values['pid'] : 0;
			$values['capacity'] = isset($values['capacity']) ? $values['capacity'] : 0;
			$values['url'] = Strings::webalize($values['name']);
			$referer = $values['referer'];			
			unset($values['referer']);
						
			$this->model->getBookingRooms()->wherePrimary($this->room->id)->update($values);
			
			//kapacita je vetsi nez je nastavene v db - prida dalsi objekty
			if($values['pid'] != 0){
				if($this->room->capacity < $values->capacity){
					$data = array();
					$price = array();
					for($i=$this->room->capacity;$i<$values->capacity;$i++){
						$data['name'] = $this->room->name.$i;
						$data['booking_rooms_id'] = $this->room->id;
						$data['capacity'] = 1;
						$lastObject = $this->model->getBookingObjects()->insert($data);
						$price['name'] = "celoročně";
						$price['booking_objects_id'] = $lastObject->id;
						$price['dateFrom'] = date('Y')."-01-01";
						$price['dateTo'] =  date('Y')."-12-31";
						$price['price'] = 100;
						$this->model->getBookingPrices()->insert($price);	
					}					
				}else{
					$objects = $this->model->getBookingObjects()->where('booking_rooms_id',$this->room->id)->order('id DESC')->limit($this->room->capacity - $values->capacity);
					foreach($objects as $object){
						$this->model->getBookingObjects()->wherePrimary($object->id)->delete();
					}
				}
			}
			$this->flashMessage('Místnost byla úspěšně upravena!');		
			$this->redirectUrl($referer);			
		}
		
		public function addRoom ($form) {
			$values = $form->getValues();	
			$values['pid'] = isset($values['pid']) ? $values['pid'] : 0;
			$values['capacity'] = isset($values['capacity']) ? $values['capacity'] : 0;
			$values['url'] = Strings::webalize($values['name']);
			$referer = $values['referer'];
			unset($values['referer']);
			
			$last = $this->model->getBookingRooms()->insert($values);
			$data = array();
			$price = array();
						
			if (isset($values['capacity'])) {
				for($i=0;$i<$values->capacity;$i++){
					$data['name'] = $last->name.$i;
					$data['booking_rooms_id'] = $last->id;
					$data['capacity'] = 1;
					$lastObject = $this->model->getBookingObjects()->insert($data);
					$price['name'] = "celoročně";
					$price['booking_objects_id'] = $lastObject->id;
					$price['dateFrom'] = date('Y')."-01-01";
					$price['dateTo'] =  date('Y')."-12-31";
					$price['price'] = 100;
					$this->model->getBookingPrices()->insert($price);	
				}
			}
			
			$this->redirectUrl($referer);		
		}
		
		public function createOptions ($data) {
			$options = array();			
			foreach ($data as $key => $val) {
				$options[$key] = Html::el('div')->class('layouts')->style('background-image: url("'.$this->context->httpRequest->url->basePath.'adminModule/images/layoutsBooking/layout'.$key.'.png")');
			}			
			return $options;
		}
		
		public function getReferer() {
			if (!empty($this->context->httpRequest->referer)) {
				return $this->context->httpRequest->referer->absoluteUrl;
			}
			else return $this->link(':AdminBooking:Rooms:');
		}
		
		public function handleremoveObject($id){
			$key = array_search($id, $this->ids);
			if (false !== $key) {
			  unset($this->ids[$key]);
			}
			$this->objects = $this->model->getBookingObjects()->where('id IN ?',$this->ids);
			$this->prices = $this->model->getBookingPrices()->where('booking_objects_id IN ?',$this->ids)->group('dateTo,dateFrom,price');			
			$this->redirect('this',array($this->ids));
		}

		public function handleDelete($id) {
			$ids = (array)$id;
			
			$this->model->getBookingRooms()->where('id', $ids)->delete();
		}
		
		public function handleDeleteObject($id) {
			$ids = (array)$id;
			$objects = $this->model->getBookingObjects()->where('id', $ids);
			$data = $objects->fetch();
			$count = $objects->count();
		
			$room = $this->model->getBookingRooms()->wherePrimary($data->booking_rooms_id);
			$data = $room->fetch();
			$room->update(array('capacity'=>$data->capacity - $count));
			
			$this->model->getBookingObjects()->where('id', $ids)->delete();
		}
		
		public function handleDeletePrice ($id, $pid) {
			$ids = (array)$pid;
			
			$this->model->getBookingPrices()->where('id', $ids)->delete();
		}
		
		public function handleDeleteHours($hourId) {
			$ids = (array)$hourId;
			$this->model->getBookingHours()->where('id',$ids)->delete();
		}
		
		public function createComponentRooms () {
			return new RoomsGrid($this->rooms);
		}
		
		public function createComponentPrices () {
			return new PricesGrid($this->prices);
		}
		
		public function createComponentPricesObjects () {
			return new ObjectsPricesGrid($this->prices);
		}
		
		public function createComponentHours () {
			return new HoursGrid($this->hours);
		}
		
		public function createComponentEditObject () {
			$form = new Form();
			
			$form->getElementPrototype()->addClass('form-horizontal');
			
			$form->addGroup(null);
			$form->addText('name', 'Jméno:')
				->setAttribute('class', 'input-name');

			$form->addText('capacity', 'Kapacita:');
			
			$form->addGroup()
				->setOption('container', 'fieldset class="submit"');
			$form->addSubmit('edit', 'Upravit');
			
			$form->addHidden('referer', $this->getReferer());
			
			$form->onSuccess[] = array($this, 'editObject');
			
			$form->setValues($this->object);
			
			$form->setRenderer(new BootstrapFormRenderer());
			
			return $form;
		}
		
		public function editObject ($form) {
			$values = $form->values;
			$referer = $values['referer'];
			
			unset($values['referer']);
			
			$this->model->getBookingObjects()->wherePrimary($this->object->id)->update($values);
			
			$this->flashMessage('Objekt byl úspěšně upraven');
			$this->redirectUrl($referer);
		}
	}