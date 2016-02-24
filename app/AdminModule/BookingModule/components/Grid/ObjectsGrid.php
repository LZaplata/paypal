<?php
	namespace AdminBookingModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class ObjectsGrid extends Grid {
		public $data;
		public $parentId;
		public function __construct($data,$parentId) {
			parent::__construct();
			$this->parentId = $parentId;
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data/*->where('pid', $this->pid)*/);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('name', 'Jméno')
				->setTextEditable()
				->setTextFilter();
			
			$this->addColumn('capacity', 'Kapacita')
				->setTextEditable()
				->setTextFilter();
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');	

			$this->addGlobalButton(Grid::ADD_ROW);			

			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			
			$this->addButton('edit', 'Editovat')
			->setClass('fa fa-pencil')
			->setLink(function($row) use ($self){return $self->presenter->link('Rooms:editObject', array($row['id'], $self->presenter->urlID));})
			->setAjax(false);		
			
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){
					return $self->presenter->link('DeleteObject!', array($row['id']));
				})
				->setConfirmationDialog(function($row){return "Opravdu odstranit objekt?";});

			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self) {
					return $self->presenter->handleDeleteObject($id);
				})
				->setConfirmationDialog("Opravdu smazat všechny vybrané položky?");
				
			$this->addAction("editall","Hromadně upravit ceny")
				->setCallback(function($id) use ($self) {
					return $self->presenter->redirect('Rooms:editObjects', array('ids'=>$id));
				})
				->setAjax(false)
				->setConfirmationDialog("Opravdu upravit ceny u vybraných položkách?");			

			$this->setRowFormCallback(function ($values) use ($self) {
			if (isset($values['id'])) {
				$row = $self->data->wherePrimary($values['id']);
			
				$self->presenter->lastEdited->rows[] = $values['id'];
			
				unset($values['id']);
				$row->update($values);
			}else{
				$values['booking_rooms_id'] = $self->parentId;
				$room = $self->presenter->model->getBookingRooms()->wherePrimary($self->parentId);
				$data = $room->fetch();
				$room->update(array('capacity' => $data->capacity + 1 ));
				$self->presenter->model->getBookingObjects()->insert($values);
			}
			});
		}
	}