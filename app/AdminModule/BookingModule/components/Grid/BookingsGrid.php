<?php
	namespace AdminBookingModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class BookingsGrid extends Grid {
		public $data;
		public $days;
		public $hours;
		public $minutes;
		public $state;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
			
			$this->days = array(
				0 => 'neděle',
				1 => 'ponděli',
				2 => 'útery',
				3 => 'středa',
				4 => 'čtvrtek',
				5 => 'pátek',
				6 => 'sobota'
			);
			for($i=0;$i<=23;$i++){
				$this->hours[$i] = $i;	
			}
			for($i=0;$i<=59;$i++){
				$this->minutes[$i] = $i;	
			}
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data/*->where('pid', $this->pid)*/);
			$this->setDataSource($dataSource);
			$this->state = $this->presenter->statesOrder;			
			
			$self = $this;

			$this->addColumn('email', 'E-mail');
			$this->addColumn('surname', 'Příjmení');						
			$this->addColumn('name', 'Jméno');
			$this->addColumn('price', 'Cena');
			$this->addColumn('date', 'Datum')
				->setRenderer(function ($row) use ($self) {
					return $row['date']->format('j. n. Y H:i');
				});
			$this->addColumn('state', 'Stav', '150px')
				//->setSelectFilter($self->state)
				->setSelectEditable($self->state)
				->setRenderer(function ($row) use ($self) {					
					return $self->state[$row['state']];
				});			
				
			$this->addSubGrid('products')
				->setGrid(new BookingBookingsGrid($self->presenter->model->getBookingBookings()->select('booking.*, booking_bookings.*, booking_objects.name, booking_objects.capacity')->where('booking_id', $this->activeSubGridId)))
				->settings(function($grid){
					$grid->setWidth("90%");
				})
				->setAjax(false);
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');		

			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
				
			$this->addButton('edit', 'Editovat')
				->setClass('fa fa-pencil')
				->setLink(function($row) use ($self){return $self->presenter->link('Orders:edit', array($row['id']));})
				->setAjax(false);				

			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){
					return $self->presenter->link('Delete!', array($row['id']));
				})
				->setConfirmationDialog(function($row){return "Opravdu odstranit rezervaci?";});
				
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self) {
					return $self->presenter->handleDelete($id);
				})
				->setConfirmationDialog("Opravdu smazat všechny vybrané rezervace?");
			
			$this->setRowFormCallback(function ($values) use ($self) {				
				$this->data->wherePrimary($values['id'])->update($values);
				$self->presenter->sendChangeStateMail($values);
			
// 				$self->presenter->lastEdited->rows[] = $values['id'];
			});
		}
	}