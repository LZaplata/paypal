<?php
	namespace AdminEshopModule;
	
	use NiftyGrid\DataSource\NDataSource;

	use NiftyGrid\Grid;

	class Properties extends Grid {
		public $data;
		public $pid;
		
		public function __construct($data, $pid) {
			parent::__construct();
		
			$this->data = $data;
			$this->pid = $pid;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data->where('categories_id', $this->pid));
			$this->setDataSource($dataSource);
				
			$self = $this;
		
			$this->addColumn('id', 'ID');
			$this->addColumn('name', 'Název')
				->setTextEditable();

			$this->addColumn("position", "Pořadí")
				->setTextEditable();
				
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->link('Delete!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku?";});
				
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/componentsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->setDefaultOrder('id ASC');
			$this->addGlobalButton(Grid::ADD_ROW);
					
			$this->setRowFormCallback(function($values) use($self) {
				if (isset($values['id'])) {
					$self->data->wherePrimary($values['id'])->fetch()->update($values);
				}
				else {
					$values['categories_id'] = $self->pid;
	
					$lastID = $self->presenter->model->getShopProperties()->insert($values);
					
					$this->addPropertiesColumn($lastID);
				}
			});
		}
		
		public function handleDelete ($id) {
			$ids = (array)$id;
			$database = $this->presenter->context->database;
			
			$this->presenter->model->getShopProperties()->where('id', $id)->delete();
			
			foreach ($ids as $val) {
				$database->query('ALTER TABLE products_properties
					DROP p_'.$val.'
				');
			}			
			
			$this->presenter->flashMessage('Vlastnost byla smazána');
		}
		
		public function addPropertiesColumn ($id) {
			$database = $this->presenter->context->database;
			
			$database->query('ALTER TABLE products_properties
				ADD p_'.$id.' TINYINT(1) NOT NULL DEFAULT 0
			');
		}
	} 