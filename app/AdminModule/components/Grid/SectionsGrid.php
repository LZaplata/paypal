<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class Sections extends Grid {
		public $data;
		public $mid;
		
		public function __construct($data, $mid = 1) {
			parent::__construct();
			
			$this->data = $data;
			$this->mid = $mid;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('name', 'Název')
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
				->setLink(function($row) use ($self){return $self->presenter->link('Structure:editModuleSection', array($row['id']));})
				->setAjax(false);
				
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->presenter->link('DeleteSection!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit sekci $row[name]?";});;
			
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){return $self->presenter->handleDeleteSection($id);})
				->setConfirmationDialog("Opravdu smazat všechny vybrané sekce?");			

			$this->setRowFormCallback(function ($values) use ($self) {
				if (isset($values['id'])) {
					$row = $self->data->wherePrimary($values['id']);
			
					$self->presenter->lastEdited->rows[] = $values['id'];
			
					unset($values['id']);
					$row->update($values);
				}
				else {
					$values['modules_id'] = $this->mid;
					
					$self->presenter->model->getSections()->insert($values);
				}
			});
		}
	}