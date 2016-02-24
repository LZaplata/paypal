<?php
	namespace AdminEshopModule;
	
	use NiftyGrid\DataSource\NDataSource;

	use NiftyGrid\Grid;

	class Methods extends Grid {
		public $data;
		public $pid;
		
		public function __construct($data) {
			parent::__construct();
		
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
				
			$self = $this;
			$methods = array(0 => 'Doprava', 1 => 'Platba', 3 => 'Doprava - zásilkovna', 4 => "Platba předem");
		
			$this->addColumn('id', 'ID');
			$this->addColumn('name', 'Název')
				->setTextEditable();
			$this->addColumn('type', 'Typ')
				->setSelectEditable($methods)
				->setRenderer(function ($row) use ($methods) {
					return $methods[$row['type']];
				});
				
			$this->addButton(Grid::ROW_FORM, "Rychlá editace")
				->setClass("fa fa-pencil-square-o");
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){return $self->link('Delete!', array($row['id']));})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku?";});
				
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/componentsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
// 			$this->setDefaultOrder('id ASC');
			$this->addGlobalButton(Grid::ADD_ROW);
					
			$this->setRowFormCallback(function($values) use($self) {
				if (isset($values['id'])) {
					$self->data->wherePrimary($values['id'])->update($values);	
				}
				else {
					$self->presenter->model->getShopMethods()->insert($values);
				}
			});
		}
		
		public function handleDelete ($id) {
			$ids = (array)$id;
			
			$this->presenter->model->getShopMethods()->where('id', $ids)->delete();
			$this->presenter->model->getShopMethodsRelations()->where('shop_methods_id', $ids)->delete();
			$this->presenter->model->getShopMethodsRelations()->where('id_shop_methods', $ids)->delete();
			
			$this->presenter->flashMessage('Položka byla smazána');
		}
	} 