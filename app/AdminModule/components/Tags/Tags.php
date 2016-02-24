<?php
	namespace AdminModule;
	
	use Nette\Utils\Html;

	use Nette\Utils\Strings;

	use NiftyGrid\DataSource\NDataSource;

	use NiftyGrid\Grid;

	class Tags extends Grid {		
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
			
			$this->addColumn('modules_id', 'Modul')
				->setRenderer(function ($row) use ($self) {
					return $self->presenter->model->getSections()->wherePrimary($row['sections_id'])->fetch()->modules->name;
				});
			
			$this->addColumn('sections_id', 'Sekce')
				->setSelectEditable($self->presenter->sections)
				->setRenderer(function ($row) use ($self) {
					return $self->presenter->model->getSections()->wherePrimary($row['sections_id'])->fetch()->name;
				});
				
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->link('Delete!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku?";});
				
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->addGlobalButton(Grid::ADD_ROW);
				
			$this->setRowFormCallback(function($values) use($self) {				
				if (isset($values['id'])) {
					$self->data->wherePrimary($values['id'])->update($values);
				}
				else {
					$values['id_section'] = $self->presenter->sid;
					
					$self->presenter->model->getSectionsTags()->insert($values);
				}
			});
		}
		
		public function handleDelete ($id) {
			$ids = (array)$id;
			
			$this->presenter->model->getSectionsTags()->where('id', $ids)->delete();
		}
	}