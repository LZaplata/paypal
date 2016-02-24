<?php
	namespace AdminEshopModule;
	
	use NiftyGrid\DataSource\NDataSource;

	use NiftyGrid\Grid;

	class AmountDiscount extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
		
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data);
			$this->setDataSource($dataSource);
				
			$self = $this;
		
			$this->addColumn('amount', 'Množství (>=)')
				->setTextEditable();
			
			$this->addColumn('discount', 'Sleva (%)')
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
			$this->setDefaultOrder('amount ASC');
			$this->addGlobalButton(Grid::ADD_ROW);
				
			$this->setRowFormCallback(function($values) use($self) {
				if (isset($values['id'])) {
					$self->data->wherePrimary($values['id'])->fetch()->update($values);
				}
				else {
					$values['products_id'] = $self->presenter->id;
						
					$self->presenter->model->getProductsDiscounts()->insert($values);
				}
			});
		}
		
		public function handleDelete ($id) {
			$ids = (array)$id;
			
			$this->presenter->model->getProductsDiscounts()->where('id', $ids)->delete();
		}
	}