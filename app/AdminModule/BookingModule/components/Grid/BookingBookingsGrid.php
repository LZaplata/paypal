<?php
	namespace AdminBookingModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class BookingBookingsGrid extends Grid {
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
			
			$this->addColumn('name', 'Název');
			
			$this->addColumn('capacity', 'Kapacita', '130px');
			
			$this->addColumn('dateFrom', 'Od')
				->setRenderer(function ($row) use ($self) {
					return $row['dateFrom']->format('j. n. Y');
				});
			
			$this->addColumn('dateTo', 'Do')
				->setRenderer(function ($row) use ($self) {
					return $row['dateTo']->format('j. n. Y');
				});

			$this->addColumn('price', 'Cena')
				->setTextEditable();
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');		

			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			
			if($this->presenter->order){				
				$this->addButton('delete', 'Smazat')
					->setClass('fa fa-trash text-danger')
					->setLink(function($row) use ($self){
						return $self->link('Delete!', array($row['id']));
					})
					->setConfirmationDialog(function($row){return "Opravdu chcete odstranit položku?";});					
			}
			
			$this->setRowFormCallback(function ($values) use ($self) {
				$row = $self->data->wherePrimary($values['id']);
				$data2 = $row->fetch();
				$self->presenter->lastEdited->rows[] = $values['id'];
			
				unset($values['id']);
				$oldPrice = $data2->price;
				$price = $values['price'];
				$diff = $oldPrice - $price;
				$order = $self->presenter->model->getBooking()->wherePrimary($data2->booking_id);
				$data = $order->fetch();
								
				$order->update(array('price' => $data->price - $diff));			
				$row->update($values);
				
				$self->presenter->order = $self->presenter->model->getBooking()->wherePrimary($data2->booking_id)->fetch();
				
				if ($data2->price != $values['price']) {
					$self->presenter->sendChangeBookingMail($self->presenter->model->getBooking()->wherePrimary($data2->booking_id)->fetch());
				}
			});
		}
		
		public function handleDelete($objectId){
			$object = $this->presenter->model->getBookingBookings()->wherePrimary($objectId);
			$data = $object->fetch();
			$this->presenter->model->getBooking()->wherePrimary($data->booking_id)->update(array('price'=>($this->presenter->order->price - $data->price)));
			$object->delete();
			
			$this->presenter->sendChangeBookingMail($order = $this->presenter->model->getBooking()->wherePrimary($this->presenter->order->id)->fetch());
			$this->presenter->objects = $this->presenter->searchingObjects();

	 		$this->presenter->order = $order;
			$this->presenter->redrawControl('objects');
			$this->presenter->redrawControl('price');
		}
	}