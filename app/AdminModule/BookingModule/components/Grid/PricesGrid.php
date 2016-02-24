<?php
	namespace AdminBookingModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class PricesGrid extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data/*->where('pid', $this->pid)*/);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('name', 'Název', '22%')
				->setTextEditable();
// 				->setTextFilter();
			
			$this->addColumn('price', 'Cena', '22%')
// 				->setTextFilter()
				->setTextEditable()
				->setRenderer(function ($row) {
					return $row['price'].' czk';
				});
			
			$this->addColumn('dateFrom', 'Platnost od', '22%')
// 				->setDateFilter()
				->setDateEditable()
				->setRenderer(function ($row) {
					return $row['dateFrom']->format('j.n.Y');
				});
			
			$this->addColumn('dateTo', 'Platnost do', '22%')
// 				->setDateFilter()
				->setDateEditable()
				->setRenderer(function ($row) {
					return $row['dateTo']->format('j.n.Y');
				});
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');		

			$this->addGlobalButton(Grid::ADD_ROW);

			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){
					return $self->presenter->link('DeletePrice!', array($self->presenter->id, $row['id']));
				})
				->setConfirmationDialog(function($row){return "Opravdu odstranit cenu?";});	
				
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self) {
					return $self->presenter->handleDeletePrice($self->presenter->id, $id);
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
					$values['booking_objects_id'] = $self->presenter->id;
					
					$self->presenter->model->getBookingPrices()->insert($values);
				}				
			});
		}
	}