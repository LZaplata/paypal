<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class UsersGroupGrid extends Grid {
		public $data;
		public $data2;
		public $pid;
		
		public function __construct($data, $pid = 0) {
			parent::__construct();
			
			$this->data = $data;
			$this->pid = $pid;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data/*->where('pid', $this->pid)*/);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('name', 'Název')
				->setTextEditable()
				->setTextFilter();
			
			$this->addColumn('discount', 'Sleva (%)')
				->setTextEditable();
			
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
				
			$this->addButton('add', 'Přidat uživatele')
				->setClass('fa fa-plus')
				->setLink(function($row) use ($self){return $self->presenter->link('Accounts:editGroup', array($row['id']));})
				->setAjax(false);
			
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->presenter->link('DeleteGroup!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit skupinu $row[name]?";});;
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->addGlobalButton(Grid::ADD_ROW);
			
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){return $self->presenter->handleDeleteGroup($id);})
				->setConfirmationDialog("Opravdu smazat všechny vybrané skupiny?");

			$this->setRowFormCallback(function ($values) use ($self) {
				if (isset($values['id'])) {
					$row = $self->data->wherePrimary($values['id']);
				
					$self->presenter->lastEdited->rows[] = $values['id'];
				
					unset($values['id']);
					$row->update($values);
				}
				else {
					$values['sections_id'] = -3;
						
					$self->presenter->model->getCategories()->insert($values);
				}
			});
		}
	}