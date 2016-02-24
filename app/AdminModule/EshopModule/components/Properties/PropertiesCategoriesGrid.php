<?php
	namespace AdminEshopModule;
	
	use NiftyGrid\DataSource\NDataSource;

	use NiftyGrid\Grid;

	class PropertiesCategories extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
				
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
			
			$self = $this;

			$this->addColumn('id', 'ID');
			$this->addColumn('name', 'Název')
				->setTextEditable();
			
			$this->addColumn('title', 'Název na webu')
				->setTextEditable();
			
			$this->addColumn('position', 'Priorita')
				->setTextEditable();
			
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->link('Delete!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku $row[title]?";});
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/componentsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->setDefaultOrder('id ASC');
			$this->addGlobalButton(Grid::ADD_ROW);
			
			$this->addSubGrid('properties', 'Zobrazit vlastnosti')
				->setGrid(new Properties($this->presenter->model->getShopProperties(), $this->activeSubGridId))
				->settings(function($grid){
        			$grid->setWidth("90%");
    			});
			
			$this->setRowFormCallback(function($values) use($self) {
				if (isset($values['id'])) {
					$self->data->wherePrimary($values['id'])->fetch()->update($values);
				}
				else {
					$values['sections_id'] = -2;
					
					$self->presenter->model->getCategories()->insert($values);
				}
			});
		}
		
		public function handleDelete($id) {
			$ids = (array)$id;
			
			$this->presenter->model->getCategories()->where('id', $ids)->delete();
			
			$this->presenter->flashMessage('Skupina vlastností byla smazána');
		}
	}