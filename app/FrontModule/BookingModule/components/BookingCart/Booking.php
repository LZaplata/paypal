<?php
	namespace FrontBookingModule;

	use Nette\Forms\Rendering\BootstrapFormRenderer;

	use Nette\Utils\Html;

	use Nette\Latte\Engine;

	use Nette\Utils\Strings;

	use Nette\Application\UI\Form;

	use Nette\Application\UI\Control;

	class Booking extends Control {
		
		public $totalPrice;
	
		public $prices;
		
		public $objects;
		
		public $search;
		
		public $order;
		
		public function __construct($index, $objects, $section, $search, $prices) {			
			$this->order = $section;
			$this->prices = $prices[$index];
			$this->totalPrice = array_sum($this->prices);
			$this->objects = $objects[$index];
			if(isset($search)){
				$this->search = $search;
			} 					
		}
		
		public function render () {
			$this->template->totalPrice = $this->totalPrice;
			$this->template->objects = $this->objects;
			$this->template->prices = $this->prices;
			$this->template->setFile(__DIR__.'/addToCart.latte');		
			$this->template->setTranslator($this->presenter->translator);
			$this->template->render();
		}                  
		
		public function createComponentAddToCart () {
			$form = new Form();				
			$form->onSuccess[] = callback($this, 'addToCartSubmitted');						
			return $form;
		}
		
		public function addToCartSubmitted($form){
			$this->order->remove();
			$values = $form->getHttpData();
			foreach($values['objects'] as $key=>$object){
				$data=array();
				$data['booking_objects_id'] = $object;
				$data['dateTo'] = $this->search['dateTo'];
				$data['dateFrom'] = $this->search['dateFrom'];
				$data['price'] = $this->prices[$object];
				$this->order->objects[$object] = (object) $data;
			}
			$this->order->price = $this->totalPrice;
			$this->presenter->redirect('Order:contact');
		}
	}