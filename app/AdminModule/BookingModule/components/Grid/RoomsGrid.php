<?php
	namespace AdminBookingModule;

	use Nette\Utils\Strings;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class RoomsGrid extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data/*->where('pid', $this->pid)*/);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('name', 'Jméno', $self->presenter->urlID != 0 ? '38%' : '88%')
				->setTextEditable()
				->setTextFilter();
			
			if ($self->presenter->urlID != 0) {
				$this->addColumn('capacity', 'Počet objektů', '35%')
					->setTextFilter();
				
				$this->addColumn('pid', 'Areál', '15%')
					->setRenderer(function ($row) use ($self) {
						return $self->presenter->model->getBookingRooms()->wherePrimary($row['pid'])->fetch()->name;
					});
			}
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');			

			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			
			if ($self->presenter->urlID != 0) {
				$this->addButton('edit', 'Editovat')
					->setClass('fa fa-pencil')
					->setLink(function($row) use ($self){return $self->presenter->link('Rooms:edit', array($row['id'], $self->presenter->urlID));})
					->setAjax(false);
			}
			
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){
					return $self->presenter->link('Delete!', array($row['id']));
				})
				->setConfirmationDialog(function($row){return "Opravdu odstranit místnost?";});	
			
			if ($self->presenter->urlID != 0) {
				$this->addSubGrid('objects', 'Zobrazit objekty')
					->setGrid(new ObjectsGrid($this->presenter->model->getBookingObjects()->where('booking_rooms_id', $this->activeSubGridId),$this->activeSubGridId))
					->settings(function($grid){
						$grid->setWidth("90%");
					})
					->setAjax(false);					
			}
				
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self) {
					return $self->presenter->handleDelete($id);
				})
				->setConfirmationDialog("Opravdu smazat všechny vybrané položky?");

			$this->setRowFormCallback(function ($values) use ($self) {
				$row = $self->data->wherePrimary($values['id']);
				$values['url'] = Strings::webalize($values['name']);
			
				$self->presenter->lastEdited->rows[] = $values['id'];
			
				unset($values['id']);
				$row->update($values);
			});
		}
	}