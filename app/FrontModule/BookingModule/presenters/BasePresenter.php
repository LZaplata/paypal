<?php
	namespace FrontBookingModule;
	
	use FrontModule\PagePresenter;
	
	use Nette\Application\UI\Multiplier;

	class BasePresenter extends PagePresenter {
		/** @persistent */
		public $booking;
		/** @persistent */
		public $room;
		/** @persistent */
		public $rid;
		
		public $sectionSession;
				
		public function startup() {
			parent::startup();
			$this->invalidateControl('flashMessages');
			$this->sectionSession =  $this->context->session->getSection('booking');						
			$this->template->setTranslator($this->translator);
		}
	}