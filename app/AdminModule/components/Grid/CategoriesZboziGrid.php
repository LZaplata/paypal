<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class CategoriesZboziGrid extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('name', 'Název')
				->setTextEditable();
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->addGlobalButton(Grid::ADD_ROW);
				
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
		
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->presenter->link('DeleteCategory!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu smazat?";});
			
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){return $self->presenter->handleDeleteCategory($id);})
				->setConfirmationDialog("Opravdu smazat všechny vybrané kategorie?");			

			$this->setRowFormCallback(function ($values) use ($self) {
				if (isset($values['id'])) {
					$row = $this->data->wherePrimary($values['id'])->fetch();
					
					$row->update($values);
				}
				else {
					if (!$self->presenter->model->getCategoriesZbozi()->where($values)->fetch()) {
						$self->presenter->model->getCategoriesZbozi()->insert($values);
					}
				}
			});
		}
	}