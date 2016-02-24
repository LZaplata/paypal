<?php
	namespace AdminModule;

	use NiftyGrid\DataSource\NDataSource;

	use Nette\Utils\Html;

	use NiftyGrid\Grid;

	class ProductsRelatedGrid extends Grid {
		public $data;
		
		public function __construct($data) {
			parent::__construct();
			
			$this->data = $data;
		}
		
		public function configure($presenter) {
			$dataSource = new NDataSource($this->data/*->where('pid', $this->pid)*/);
			$this->setDataSource($dataSource);
			
			$self = $this;
			
			$this->addColumn('name', 'Název')
				->setSelectEditable($self->presenter->getProducts()->where('galleries_id != ?', $self->presenter->product->galleries_id)->fetchPairs('products_id', 'name'));
			
			$this->setTemplate(APP_DIR.'/AdminModule/templates/Grid/accountsGrid.latte');
			$this->paginate = false;
			$this->setWidth('100%');
			$this->addGlobalButton(Grid::ADD_ROW);
			$this->primaryKey = 'products_id';
			
			$this->addButton('delete', 'Smazat')
				->setClass('fa fa-trash text-danger')
				->setLink(function($row) use ($self){
					return $self->presenter->link('DeleteRelated!', array($self->presenter->id, $row['products_id']));
				})
				->setConfirmationDialog(function($row){return "Opravdu odstranit položku?";});
			
			$this->addAction("delete","Smazat")
				->setCallback(function($id) use ($self){
					return $self->presenter->handleDeleteRelated($self->presenter->id, $id);
				})
				->setConfirmationDialog("Opravdu smazat všechny vybrané položky?");
					
			$this->setRowFormCallback(function ($values) use ($self) {
				$data['id_products'] = $self->presenter->id;
				$data['products_id'] = $values['name'];
				
				$self->presenter->model->getProductsRelated()->insert($data);
			});
		}
	}