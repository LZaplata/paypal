<?php
	namespace AdminBookingModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class HoursGrid extends Grid {
		public $data;
		public $days;
		public $hours;
		public $minutes;
		
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
			
			$self = $this;
			
			$this->addColumn('open_hour', 'začátek - hodiny')			
				->setRenderer(function ($row) {
					return $this->hours[$row['open_hour']];
				})
				->setSelectEditable($this->hours);
			$this->addColumn('open_minute', 'začátek - minuty')
				->setRenderer(function ($row) {
					return $this->minutes[$row['open_minute']];
				})
				->setSelectEditable($this->minutes);			
			$this->addColumn('close_hour', 'konec - hodiny')
				->setRenderer(function ($row) {
					return $this->hours[$row['close_hour']];
				})
				->setSelectEditable($this->hours);
			$this->addColumn('close_minute', 'konec - minuty')
				->setRenderer(function ($row) {
					return $this->minutes[$row['close_minute']];
				})
				->setSelectEditable($this->minutes);
			$this->addColumn('day', 'Den')				
				->setRenderer(function ($row) {
					return $this->days[$row['day']];
				})
				->setSelectEditable($this->days);
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->addGlobalButton(Grid::ADD_ROW);			

			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){
					return $self->presenter->link('DeleteHours!', array($row['id']));
				})
				->setConfirmationDialog(function($row){return "Opravdu odstranit otevírací dobu?";});

			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self) {
					return $self->presenter->handleDeleteHours($id);
				})
				->setConfirmationDialog("Opravdu smazat všechny vybrané položky?");

			$this->setRowFormCallback(function ($values) use ($self) {
				if (isset($values['id'])) {
					$row = $self->data->wherePrimary($values['id']);
						
					$self->presenter->lastEdited->rows[] = $values['id'];
						
					unset($values['id']);
					$row->update($values);
				}
				else {
					if($self->presenter->model->getBookingHours()->where('day',$values['day'])->fetch()){
						$self->presenter->flashMessage('V tento den máte již zadanou otevírací dobu');
					}else{
						$values['booking_rooms_id'] = $self->presenter->room->id;
						$self->presenter->model->getBookingHours()->insert($values);
					}
				}
			});
		}
	}