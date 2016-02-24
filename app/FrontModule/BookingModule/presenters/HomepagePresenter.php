<?php
	namespace FrontBookingModule;

	class HomepagePresenter extends BasePresenter {
		public $rooms;
		
		public function actionDefault () {
			$this->rooms = $this->model->getBookingRooms()->where('pid', 0);
		} 
	
		public function renderDefault () {
			$page = $this->model->getPagesModules()->select('pages.keywords'.$this->lang.' AS keywords, pages.title'.$this->lang.' AS title, pages.description'.$this->lang.' AS description')->where('modules_id', 4)->fetch();
			
			$this->template->keywords = $page->keywords;
			$this->template->title = $page->title;
			$this->template->desc = $page->description;
			$this->template->rooms = $this->rooms;
		}
	}